# OneClickDz OCPay PHP SDK

[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Packagist](https://img.shields.io/badge/packagist-oneclickdz%2Focpay--php--sdk-blue.svg)](https://packagist.org/packages/oneclickdz/ocpay-php-sdk)

Official PHP SDK for integrating OneClickDz OCPay payment gateway. This SDK provides a simple and intuitive interface for creating payment links and checking payment status.

## Features

- ✅ **Simple API** - Clean, intuitive interface
- ✅ **Type Safety** - Full PHP 8.1+ type hints and return types
- ✅ **PSR-4 Autoloading** - Composer-ready structure
- ✅ **Comprehensive Documentation** - Full PHPDoc annotations
- ✅ **Error Handling** - Custom exceptions for different error types
- ✅ **Modern PHP** - Uses latest PHP features and best practices
- ✅ **Guzzle HTTP Client** - Reliable HTTP communication

## Requirements

- PHP 8.1 or higher
- Composer
- `ext-json` extension
- `ext-curl` extension

## Installation

Install via Composer:

```bash
composer require oneclickdz/ocpay-php-sdk
```

Or add to your `composer.json`:

```json
{
    "require": {
        "oneclickdz/ocpay-php-sdk": "^1.0"
    }
}
```

## Quick Start

### 1. Initialize the SDK

```php
<?php

use OneClickDz\OCPay\OCPay;

// Initialize with your API access token
$ocpay = new OCPay('your-api-access-token');
```

### 2. Create a Payment Link

```php
use OneClickDz\OCPay\DTO\CreateLinkRequest;
use OneClickDz\OCPay\DTO\ProductInfo;

// Create product information
$productInfo = new ProductInfo(
    title: 'Premium Subscription',
    amount: 5000, // Amount in DZD (500 - 500,000)
    description: 'Monthly access to all premium features'
);

// Create payment link request
$request = new CreateLinkRequest(
    productInfo: $productInfo,
    feeMode: CreateLinkRequest::FEE_MODE_NO_FEE, // Merchant pays fees
    successMessage: 'Thank you for your purchase!',
    redirectUrl: 'https://yourstore.com/success?orderId=12345'
);

// Create the payment link
try {
    $response = $ocpay->createLink($request);
    
    // Share this URL with your customer
    echo "Payment URL: " . $response->paymentUrl . "\n";
    
    // Save this reference for tracking
    echo "Payment Reference: " . $response->paymentRef . "\n";
    
    // Store paymentRef in your database for order tracking
    // saveOrderPaymentRef($orderId, $response->paymentRef);
    
} catch (\OneClickDz\OCPay\Exception\ValidationException $e) {
    // Handle validation errors (400)
    echo "Validation error: " . $e->getMessage() . "\n";
} catch (\OneClickDz\OCPay\Exception\UnauthorizedException $e) {
    // Handle authentication errors (403)
    echo "Authentication error: " . $e->getMessage() . "\n";
} catch (\OneClickDz\OCPay\Exception\ApiException $e) {
    // Handle other API errors
    echo "API error: " . $e->getMessage() . "\n";
}
```

### 3. Check Payment Status

```php
// Check payment status using the payment reference
try {
    $status = $ocpay->checkPayment('OCPL-A1B2C3-D4E5');
    
    if ($status->isConfirmed()) {
        // Payment successful - fulfill the order
        echo "Payment confirmed! Amount: " . $status->transactionDetails->amount . " DZD\n";
        fulfillOrder($orderId);
        
    } elseif ($status->isFailed()) {
        // Payment failed - mark order as failed
        echo "Payment failed: " . $status->message . "\n";
        markOrderFailed($orderId);
        
    } else {
        // Still pending - poll again later
        echo "Payment pending...\n";
        schedulePolling($orderId);
    }
    
} catch (\OneClickDz\OCPay\Exception\NotFoundException $e) {
    echo "Payment not found: " . $e->getMessage() . "\n";
} catch (\OneClickDz\OCPay\Exception\PaymentExpiredException $e) {
    echo "Payment link expired: " . $e->getMessage() . "\n";
} catch (\OneClickDz\OCPay\Exception\ApiException $e) {
    echo "API error: " . $e->getMessage() . "\n";
}
```

## Complete Example: E-commerce Order Flow

```php
<?php

require_once 'vendor/autoload.php';

use OneClickDz\OCPay\OCPay;
use OneClickDz\OCPay\DTO\CreateLinkRequest;
use OneClickDz\OCPay\DTO\ProductInfo;
use OneClickDz\OCPay\Exception\ApiException;

// Initialize SDK
$ocpay = new OCPay(getenv('ONECLICK_API_KEY'));

// Step 1: Create order in your system
$orderId = createOrder([
    'customer_id' => 123,
    'items' => [
        ['name' => 'Product A', 'price' => 5000],
        ['name' => 'Product B', 'price' => 3000],
    ],
    'total' => 8000,
]);

// Step 2: Create payment link
try {
    $productInfo = new ProductInfo(
        title: "Order #{$orderId}",
        amount: 8000,
        description: "Payment for order #{$orderId}"
    );
    
    $request = new CreateLinkRequest(
        productInfo: $productInfo,
        feeMode: CreateLinkRequest::FEE_MODE_NO_FEE,
        successMessage: "Thank you! Your order #{$orderId} is being processed.",
        redirectUrl: "https://yourstore.com/orders/{$orderId}/success"
    );
    
    $response = $ocpay->createLink($request);
    
    // Step 3: Save payment reference to order
    updateOrder($orderId, [
        'payment_ref' => $response->paymentRef,
        'payment_url' => $response->paymentUrl,
        'status' => 'pending_payment',
    ]);
    
    // Step 4: Redirect customer to payment page
    header("Location: " . $response->paymentUrl);
    exit;
    
} catch (ApiException $e) {
    // Handle error
    error_log("Payment link creation failed: " . $e->getMessage());
    showErrorPage("Failed to create payment link. Please try again.");
}

// Step 5: Poll payment status (in background job or webhook)
function checkOrderPayment(string $orderId): void
{
    $ocpay = new OCPay(getenv('ONECLICK_API_KEY'));
    $order = getOrder($orderId);
    
    if (!$order || !$order['payment_ref']) {
        return;
    }
    
    try {
        $status = $ocpay->checkPayment($order['payment_ref']);
        
        if ($status->isConfirmed()) {
            // Mark order as paid and fulfill
            updateOrder($orderId, [
                'status' => 'paid',
                'paid_at' => date('Y-m-d H:i:s'),
            ]);
            fulfillOrder($orderId);
            
        } elseif ($status->isFailed()) {
            // Mark order as failed
            updateOrder($orderId, [
                'status' => 'payment_failed',
            ]);
        }
        // If pending, do nothing and check again later
        
    } catch (ApiException $e) {
        error_log("Payment status check failed: " . $e->getMessage());
    }
}
```

## API Reference

### OCPay Class

Main entry point for the SDK.

#### Constructor

```php
public function __construct(string $accessToken, array $options = [])
```

**Parameters:**
- `$accessToken` (string) - Your OneClickDz API access token
- `$options` (array) - Optional client configuration:
  - `timeout` (int) - Request timeout in seconds (default: 30)
  - Any other [Guzzle client options](https://docs.guzzlephp.org/en/stable/request-options.html)

#### Methods

##### `createLink(CreateLinkRequest $request): CreateLinkResponse`

Creates a new payment link.

**Parameters:**
- `$request` (CreateLinkRequest) - Payment link creation request

**Returns:** `CreateLinkResponse` - Response containing payment URL and reference

**Throws:**
- `ValidationException` - Invalid request data (400)
- `UnauthorizedException` - Authentication failed (403)
- `ApiException` - Other API errors

##### `checkPayment(string $paymentRef): CheckPaymentResponse`

Checks the status of a payment.

**Parameters:**
- `$paymentRef` (string) - Payment reference code (e.g., "OCPL-A1B2C3-D4E5")

**Returns:** `CheckPaymentResponse` - Payment status response

**Throws:**
- `NotFoundException` - Payment not found (404)
- `PaymentExpiredException` - Payment link expired (410)
- `ApiException` - Other API errors

### DTOs (Data Transfer Objects)

#### ProductInfo

Product information for payment link creation.

```php
new ProductInfo(
    title: string,        // Product/service name (1-200 characters)
    amount: int,          // Amount in DZD (500 - 500,000)
    description?: string  // Optional description (max 1000 characters)
)
```

#### CreateLinkRequest

Payment link creation request.

```php
new CreateLinkRequest(
    productInfo: ProductInfo,              // Required: Product information
    feeMode?: string,                       // Optional: Fee mode (default: NO_FEE)
    successMessage?: string,                // Optional: Success message (max 500 chars)
    redirectUrl?: string                   // Optional: Redirect URL after payment
)
```

**Fee Modes:**
- `CreateLinkRequest::FEE_MODE_NO_FEE` - Merchant pays all fees (default)
- `CreateLinkRequest::FEE_MODE_SPLIT_FEE` - Fees split 50/50
- `CreateLinkRequest::FEE_MODE_CUSTOMER_FEE` - Customer pays all fees

#### CreateLinkResponse

Response from payment link creation.

```php
$response->paymentLink      // PaymentLink object with full details
$response->paymentUrl        // Complete URL to share with customers
$response->paymentRef       // Payment reference code (SAVE THIS)
```

#### CheckPaymentResponse

Payment status response.

```php
$response->status                    // PaymentStatus enum (PENDING, CONFIRMED, FAILED)
$response->message                   // Status message
$response->paymentRef               // Payment reference code
$response->transactionDetails       // TransactionDetails object (if available)
$response->isConfirmed()            // Helper: Check if confirmed
$response->isPending()              // Helper: Check if pending
$response->isFailed()               // Helper: Check if failed
```

### Exception Classes

All exceptions extend `OCPayException`:

- `ApiException` - Base API exception
- `ValidationException` - Request validation failed (400)
- `UnauthorizedException` - Authentication failed (403)
- `NotFoundException` - Resource not found (404)
- `PaymentExpiredException` - Payment link expired (410)

All API exceptions include:
- `getRequestId()` - Get request ID for support
- `getStatusCode()` - Get HTTP status code
- `getErrorData()` - Get error data array

## Important Notes

### Merchant Validation

**Required**: Complete merchant validation at [https://oneclickdz.com/#/OcPay/merchant-info](https://oneclickdz.com/#/OcPay/merchant-info) before using the API.

### Amount Limits

- **Minimum**: 500 DZD
- **Maximum**: 500,000 DZD
- **Format**: Must be whole numbers (no decimals)

### Fee Structure

- **0%** if using OneClick balance
- **1%** withdrawal fee only (configurable per transaction)

### Link Expiration

Payment links expire **20 minutes** after creation if payment is not initiated. After expiration, the status will be `FAILED`.

### Payment Status Flow

1. **PENDING** - Payment is in progress, wait and poll again
2. **CONFIRMED** - Payment completed successfully, fulfill the order
3. **FAILED** - Payment was declined, expired, or cancelled

## Error Handling

```php
try {
    $response = $ocpay->createLink($request);
} catch (\OneClickDz\OCPay\Exception\ValidationException $e) {
    // Handle validation errors (400)
    echo "Validation error: " . $e->getMessage() . "\n";
    echo "Request ID: " . $e->getRequestId() . "\n";
    
} catch (\OneClickDz\OCPay\Exception\UnauthorizedException $e) {
    // Handle authentication errors (403)
    echo "Authentication failed. Check your API key.\n";
    
} catch (\OneClickDz\OCPay\Exception\ApiException $e) {
    // Handle other API errors
    echo "API error: " . $e->getMessage() . "\n";
    echo "Status code: " . $e->getStatusCode() . "\n";
    echo "Request ID: " . $e->getRequestId() . "\n";
}
```

## Examples

Complete integration examples are available in the `examples/` directory:

- **Basic Usage** - Simple payment link creation and status checking
- **E-commerce Integration** - Complete order flow with payment processing
- **Laravel Integration** - Full Laravel integration guide with service provider, controllers, and migrations

### Laravel Integration

For Laravel applications, see the complete integration guide:

```bash
# Install the SDK
composer require oneclickdz/ocpay-php-sdk
```

See [examples/laravel/README.md](examples/laravel/README.md) for:
- Service provider setup
- Payment service implementation
- Controller examples
- Database migrations
- Background job for payment polling
- Error handling

## Testing

### Unit Tests

```bash
composer install --dev
vendor/bin/phpunit
```

### Using Sandbox Mode

The API automatically uses sandbox mode for test accounts. Check the `isSandbox` property in responses:

```php
$response = $ocpay->createLink($request);
if ($response->paymentLink->isSandbox) {
    echo "This is a test payment link\n";
}
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This SDK is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

## Support

- **Documentation**: [https://docs.oneclickdz.com](https://docs.oneclickdz.com)
- **API Reference**: [https://docs.oneclickdz.com/api-reference/ocpay](https://docs.oneclickdz.com/api-reference/ocpay)
- **Support Email**: support@oneclickdz.com

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes and version history.

