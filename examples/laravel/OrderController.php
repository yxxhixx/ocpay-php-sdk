<?php

/**
 * Laravel Order Controller Example
 * 
 * Copy relevant methods to your OrderController
 */

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Initiate payment checkout
     */
    public function checkout(Order $order): RedirectResponse
    {
        // Check if already paid
        if ($order->isPaid()) {
            return redirect()->route('orders.show', $order)
                ->with('message', 'Order already paid');
        }

        try {
            $paymentUrl = $this->paymentService->createPaymentLink($order);
            return redirect($paymentUrl);

        } catch (\Exception $e) {
            return back()->withErrors([
                'payment' => 'Failed to create payment link. Please try again.'
            ]);
        }
    }

    /**
     * Handle payment callback
     */
    public function callback(Order $order): RedirectResponse
    {
        // Verify payment status when customer returns
        $isPaid = $this->paymentService->verifyPayment($order);

        if ($isPaid) {
            return redirect()->route('orders.show', $order)
                ->with('success', 'Payment confirmed! Your order is being processed.');
        }

        return redirect()->route('orders.show', $order)
            ->with('error', 'Payment not confirmed. Please try again.');
    }

    /**
     * Show order details
     */
    public function show(Order $order)
    {
        // If payment is pending, verify status
        if ($order->isPendingPayment()) {
            $this->paymentService->verifyPayment($order);
            $order->refresh();
        }

        return view('orders.show', compact('order'));
    }
}

