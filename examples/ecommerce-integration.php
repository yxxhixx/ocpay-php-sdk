<?php

/**
 * E-commerce Integration Example
 *
 * This example demonstrates a complete e-commerce order flow:
 * 1. Create order
 * 2. Create payment link
 * 3. Store payment reference
 * 4. Check payment status (polling)
 * 5. Fulfill order when payment confirmed
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OneClickDz\OCPay\OCPay;
use OneClickDz\OCPay\DTO\CreateLinkRequest;
use OneClickDz\OCPay\DTO\ProductInfo;
use OneClickDz\OCPay\Exception\ApiException;

// Initialize SDK
$apiKey = getenv('ONECLICK_API_KEY') ?: 'your-api-key-here';
$ocpay = new OCPay($apiKey);

// Simulate database functions (replace with your actual database code)
function createOrder(array $data): string
{
    // In real implementation, save to database
    $orderId = 'ORD-' . uniqid();
    echo "✓ Order created: {$orderId}\n";
    return $orderId;
}

function updateOrder(string $orderId, array $data): void
{
    // In real implementation, update database
    echo "✓ Order updated: {$orderId}\n";
    foreach ($data as $key => $value) {
        echo "  - {$key}: " . (is_string($value) ? $value : json_encode($value)) . "\n";
    }
}

function getOrder(string $orderId): ?array
{
    // In real implementation, fetch from database
    return [
        'id' => $orderId,
        'payment_ref' => 'OCPL-A1B2C3-D4E5', // Example
        'status' => 'pending_payment',
    ];
}

function fulfillOrder(string $orderId): void
{
    // In real implementation, process order fulfillment
    echo "✓ Order fulfilled: {$orderId}\n";
}

function markOrderFailed(string $orderId): void
{
    // In real implementation, mark order as failed
    echo "✗ Order marked as failed: {$orderId}\n";
}

// ============================================
// STEP 1: Create Order
// ============================================
echo "=== E-commerce Order Flow ===\n\n";

$orderId = createOrder([
    'customer_id' => 123,
    'items' => [
        ['name' => 'Product A', 'price' => 5000],
        ['name' => 'Product B', 'price' => 3000],
    ],
    'total' => 8000,
]);

// ============================================
// STEP 2: Create Payment Link
// ============================================
echo "\n2. Creating Payment Link...\n";

try {
    $productInfo = new ProductInfo(
        title: "Order #{$orderId}",
        amount: 8000,
        description: "Payment for order #{$orderId} - Product A, Product B"
    );

    $request = new CreateLinkRequest(
        productInfo: $productInfo,
        feeMode: CreateLinkRequest::FEE_MODE_NO_FEE,
        successMessage: "Thank you! Your order #{$orderId} is being processed.",
        redirectUrl: "https://yourstore.com/orders/{$orderId}/success"
    );

    $response = $ocpay->createLink($request);

    echo "✓ Payment link created\n";
    echo "  URL: " . $response->paymentUrl . "\n";
    echo "  Reference: " . $response->paymentRef . "\n";

    // ============================================
    // STEP 3: Store Payment Reference
    // ============================================
    updateOrder($orderId, [
        'payment_ref' => $response->paymentRef,
        'payment_url' => $response->paymentUrl,
        'status' => 'pending_payment',
    ]);

    // In a real application, you would redirect the customer here:
    // header("Location: " . $response->paymentUrl);
    // exit;

    // ============================================
    // STEP 4: Check Payment Status (Polling)
    // ============================================
    echo "\n3. Checking Payment Status (Polling)...\n";
    echo "   (In production, this would be done via webhook or background job)\n\n";

    // Simulate checking payment status
    $maxAttempts = 3;
    $attempt = 0;

    while ($attempt < $maxAttempts) {
        $attempt++;
        echo "  Attempt {$attempt}...\n";

        try {
            $status = $ocpay->checkPayment($response->paymentRef);

            echo "    Status: " . $status->status->value . "\n";

            if ($status->isConfirmed()) {
                // ============================================
                // STEP 5: Fulfill Order
                // ============================================
                echo "\n✓ Payment confirmed!\n";
                updateOrder($orderId, [
                    'status' => 'paid',
                    'paid_at' => date('Y-m-d H:i:s'),
                ]);
                fulfillOrder($orderId);
                break;

            } elseif ($status->isFailed()) {
                echo "\n✗ Payment failed: " . $status->message . "\n";
                markOrderFailed($orderId);
                break;

            } else {
                echo "    ⏳ Payment still pending...\n";
                if ($attempt < $maxAttempts) {
                    echo "    (Waiting 5 seconds before next check...)\n";
                    sleep(5);
                }
            }
        } catch (ApiException $e) {
            echo "    ✗ Error checking status: " . $e->getMessage() . "\n";
            break;
        }
    }

    if ($attempt >= $maxAttempts) {
        echo "\n⚠ Maximum polling attempts reached. Payment still pending.\n";
        echo "   (In production, you would continue polling via background job)\n";
    }

} catch (ApiException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    if ($e->getRequestId()) {
        echo "  Request ID: " . $e->getRequestId() . "\n";
    }
}

echo "\n=== Integration Example Complete ===\n";

