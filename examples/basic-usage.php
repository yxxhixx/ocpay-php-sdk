<?php

/**
 * Basic Usage Example
 *
 * This example demonstrates how to use the OneClickDz OCPay PHP SDK
 * to create payment links and check payment status.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OneClickDz\OCPay\OCPay;
use OneClickDz\OCPay\DTO\CreateLinkRequest;
use OneClickDz\OCPay\DTO\ProductInfo;
use OneClickDz\OCPay\Exception\ApiException;
use OneClickDz\OCPay\Exception\ValidationException;
use OneClickDz\OCPay\Exception\UnauthorizedException;

// Initialize SDK with your API access token
// In production, use environment variables or secure configuration
$apiKey = getenv('ONECLICK_API_KEY') ?: 'your-api-key-here';
$ocpay = new OCPay($apiKey);

echo "=== OneClickDz OCPay PHP SDK Example ===\n\n";

// Example 1: Create a Payment Link
echo "1. Creating Payment Link...\n";

try {
    // Create product information
    $productInfo = new ProductInfo(
        title: 'Premium Subscription',
        amount: 5000, // Amount in DZD (500 - 500,000)
        description: 'Monthly access to all premium features including unlimited storage and priority support'
    );

    // Create payment link request
    $request = new CreateLinkRequest(
        productInfo: $productInfo,
        feeMode: CreateLinkRequest::FEE_MODE_NO_FEE,
        successMessage: 'Thank you for your purchase! Your subscription is now active.',
        redirectUrl: 'https://yourstore.com/success?orderId=12345'
    );

    // Create the payment link
    $response = $ocpay->createLink($request);

    echo "✓ Payment link created successfully!\n";
    echo "  Payment URL: " . $response->paymentUrl . "\n";
    echo "  Payment Reference: " . $response->paymentRef . "\n";
    echo "  Amount: " . $response->paymentLink->productInfo->amount . " DZD\n";
    echo "  Sandbox Mode: " . ($response->paymentLink->isSandbox ? 'Yes' : 'No') . "\n\n";

    // Example 2: Check Payment Status
    echo "2. Checking Payment Status...\n";
    echo "   (Using the payment reference from above)\n";

    $status = $ocpay->checkPayment($response->paymentRef);

    echo "  Status: " . $status->status->value . "\n";
    echo "  Message: " . $status->message . "\n";

    if ($status->isConfirmed()) {
        echo "  ✓ Payment confirmed!\n";
        if ($status->transactionDetails) {
            echo "  Amount: " . $status->transactionDetails->amount . " " . $status->transactionDetails->currency . "\n";
            echo "  Created: " . $status->transactionDetails->createdDate . "\n";
        }
    } elseif ($status->isPending()) {
        echo "  ⏳ Payment is still pending...\n";
        echo "  (Poll again later to check status)\n";
    } elseif ($status->isFailed()) {
        echo "  ✗ Payment failed\n";
    }

} catch (ValidationException $e) {
    echo "✗ Validation Error: " . $e->getMessage() . "\n";
    if ($e->getRequestId()) {
        echo "  Request ID: " . $e->getRequestId() . "\n";
    }
} catch (UnauthorizedException $e) {
    echo "✗ Authentication Error: " . $e->getMessage() . "\n";
    echo "  Please check your API key.\n";
} catch (ApiException $e) {
    echo "✗ API Error: " . $e->getMessage() . "\n";
    echo "  Status Code: " . $e->getStatusCode() . "\n";
    if ($e->getRequestId()) {
        echo "  Request ID: " . $e->getRequestId() . "\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Example Complete ===\n";

