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
     */
    public function createPaymentLink(Order $order): string
    {
        $productInfo = new ProductInfo(
            title: "Order #{$order->id}",
            amount: (int) $order->total, // Amount in DZD
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

            $order->update([
                'payment_ref' => $response->paymentRef,
                'payment_url' => $response->paymentUrl,
                'status' => 'pending_payment',
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
     *
     * @param Order $order
     * @return bool True if payment is confirmed
     */
    public function verifyPayment(Order $order): bool
    {
        if (!$order->payment_ref) {
            return false;
        }

        try {
            $status = $this->ocpay->checkPayment($order->payment_ref);

            if ($status->isConfirmed()) {
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                return true;
            }

            if ($status->isFailed()) {
                $order->update(['status' => 'payment_failed']);
            }

            return false;

        } catch (ApiException $e) {
            Log::error('Payment verification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'request_id' => $e->getRequestId(),
            ]);

            return false;
        }
    }
}

