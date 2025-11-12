<?php

/**
 * Laravel Background Job for Payment Status Checking
 * 
 * Copy this file to: app/Jobs/CheckPaymentStatus.php
 * 
 * This job includes:
 * - Automatic retries on failure
 * - Expiry checking
 * - Timeout handling
 * - Proper error logging
 */

namespace App\Jobs;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use OneClickDz\OCPay\Exception\ApiException;

class CheckPaymentStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum number of seconds the job can run
     */
    public int $timeout = 30;

    /**
     * Number of seconds to wait before retrying the job
     */
    public int $backoff = 60;

    public function __construct(
        public Order $order
    ) {
        // Set unique job ID to prevent duplicate processing
        $this->onQueue('payments');
    }

    /**
     * Execute the job
     */
    public function handle(PaymentService $paymentService): void
    {
        // Refresh order from database to get latest state
        $this->order->refresh();

        // Skip if order is no longer pending
        if (!$this->order->isPendingPayment()) {
            Log::debug('Skipping payment check - order not pending', [
                'order_id' => $this->order->id,
                'status' => $this->order->status,
            ]);
            return;
        }

        // Check if payment link has expired before making API call
        if ($paymentService->isPaymentLinkExpired($this->order)) {
            Log::info('Payment link expired, marking as failed', [
                'order_id' => $this->order->id,
                'payment_ref' => $this->order->payment_ref,
            ]);

            $this->order->update([
                'status' => 'payment_failed',
                'payment_failed_reason' => 'Payment link expired (20 minutes)',
            ]);

            return;
        }

        try {
            // Verify payment status
            $paymentService->verifyPayment($this->order);

        } catch (ApiException $e) {
            // Log the error but don't fail the job yet
            // Let it retry based on $tries configuration
            Log::warning('Payment verification API error, will retry', [
                'order_id' => $this->order->id,
                'payment_ref' => $this->order->payment_ref,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;

        } catch (\Exception $e) {
            // Unexpected error - log and fail
            Log::error('Unexpected error in payment verification', [
                'order_id' => $this->order->id,
                'payment_ref' => $this->order->payment_ref,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Payment status check job failed after all retries', [
            'order_id' => $this->order->id,
            'payment_ref' => $this->order->payment_ref,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Optionally: Notify admin, send alert, etc.
        // You might want to mark the order for manual review
    }

    /**
     * Calculate the number of seconds to wait before retrying the job
     */
    public function backoff(): array
    {
        // Exponential backoff: 60s, 120s, 240s
        return [60, 120, 240];
    }
}

