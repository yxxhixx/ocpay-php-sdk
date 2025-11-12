<?php

/**
 * Laravel Payment Service Example
 * 
 * Copy this file to: app/Services/PaymentService.php
 * Don't forget to register it in your service provider!
 */

namespace App\Services;

use App\Models\Order;
use OneClickDz\OCPay\OCPay;
use OneClickDz\OCPay\DTO\CreateLinkRequest;
use OneClickDz\OCPay\DTO\ProductInfo;
use OneClickDz\OCPay\Exception\ApiException;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private OCPay $ocpay
    ) {}

    /**
     * Create a payment link for an order
     *
     * @param Order $order
     * @return string Payment URL
     * @throws ApiException
     * @throws \InvalidArgumentException If amount is outside valid range
     */
    public function createPaymentLink(Order $order): string
    {
        // Validate amount before creating payment link
        $amount = (int) round((float) $order->total);
        
        // Check minimum amount
        if ($amount < 500) {
            throw new \InvalidArgumentException(
                "Order total ({$order->total} DZD) is below minimum payment amount (500 DZD). " .
                "Please ensure order total meets the minimum requirement."
            );
        }
        
        // Check maximum amount
        if ($amount > 500000) {
            throw new \InvalidArgumentException(
                "Order total ({$order->total} DZD) exceeds maximum payment amount (500,000 DZD). " .
                "Please split large orders into multiple payments."
            );
        }

        // Use fromDecimalAmount to handle any decimal amounts gracefully
        $productInfo = ProductInfo::fromDecimalAmount(
            title: "Order #{$order->id}",
            amount: $order->total,
            description: "Payment for order #{$order->id}"
        );

        $request = new CreateLinkRequest(
            productInfo: $productInfo,
            feeMode: CreateLinkRequest::FEE_MODE_NO_FEE,
            successMessage: "Thank you! Your order #{$order->id} is being processed.",
            redirectUrl: route('orders.show', $order)
        );

        try {
            $response = $this->ocpay->createLink($request);

            // Store payment reference, URL, and creation time for expiry tracking
            $order->update([
                'payment_ref' => $response->paymentRef,
                'payment_url' => $response->paymentUrl,
                'status' => 'pending_payment',
                'payment_link_created_at' => now(), // Track when link was created
            ]);

            return $response->paymentUrl;

        } catch (ApiException $e) {
            Log::error('Payment link creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'request_id' => $e->getRequestId(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify payment status for an order
     * Handles payment expiry (20 minutes) and retries
     *
     * @param Order $order
     * @return bool True if payment is confirmed
     */
    public function verifyPayment(Order $order): bool
    {
        if (!$order->payment_ref) {
            return false;
        }

        // Check if payment link has expired (20 minutes)
        if ($order->payment_link_created_at) {
            $expiryTime = $order->payment_link_created_at->copy()->addMinutes(20);
            if (now()->isAfter($expiryTime)) {
                // Link expired, mark as failed
                $order->update([
                    'status' => 'payment_failed',
                    'payment_failed_reason' => 'Payment link expired (20 minutes)',
                ]);
                Log::info('Payment link expired', [
                    'order_id' => $order->id,
                    'payment_ref' => $order->payment_ref,
                    'created_at' => $order->payment_link_created_at,
                ]);
                return false;
            }
        }

        try {
            $status = $this->ocpay->checkPayment($order->payment_ref);

            if ($status->isConfirmed()) {
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                Log::info('Payment confirmed', [
                    'order_id' => $order->id,
                    'payment_ref' => $order->payment_ref,
                    'amount' => $status->transactionDetails?->amount ?? null,
                ]);

                return true;
            }

            if ($status->isFailed()) {
                $order->update([
                    'status' => 'payment_failed',
                    'payment_failed_reason' => $status->message,
                ]);

                Log::info('Payment failed', [
                    'order_id' => $order->id,
                    'payment_ref' => $order->payment_ref,
                    'reason' => $status->message,
                ]);
            }

            return false;

        } catch (\OneClickDz\OCPay\Exception\PaymentExpiredException $e) {
            // Payment link expired on API side
            $order->update([
                'status' => 'payment_failed',
                'payment_failed_reason' => 'Payment link expired',
            ]);

            Log::warning('Payment link expired (API)', [
                'order_id' => $order->id,
                'payment_ref' => $order->payment_ref,
            ]);

            return false;

        } catch (ApiException $e) {
            Log::error('Payment verification failed', [
                'order_id' => $order->id,
                'payment_ref' => $order->payment_ref,
                'error' => $e->getMessage(),
                'request_id' => $e->getRequestId(),
                'status_code' => $e->getStatusCode(),
            ]);

            // Don't mark as failed on API errors - might be temporary
            // Let the job retry
            throw $e;
        }
    }

    /**
     * Check if payment link is expired
     *
     * @param Order $order
     * @return bool
     */
    public function isPaymentLinkExpired(Order $order): bool
    {
        if (!$order->payment_link_created_at) {
            return false;
        }

        $expiryTime = $order->payment_link_created_at->copy()->addMinutes(20);
        return now()->isAfter($expiryTime);
    }
}

