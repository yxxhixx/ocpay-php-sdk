# Laravel Integration Guide

Complete guide for integrating OneClickDz OCPay PHP SDK into your Laravel application.

## Installation

### Step 1: Install the SDK

```bash
composer require oneclickdz/ocpay-php-sdk
```

### Step 2: Add API Key to Environment

Add your OneClickDz API key to `.env`:

```env
ONECLICK_API_KEY=your-api-key-here
```

### Step 3: Publish Configuration (Optional)

Create a config file for the SDK:

```bash
php artisan vendor:publish --tag=ocpay-config
```

Or manually create `config/ocpay.php`:

```php
<?php

return [
    'api_key' => env('ONECLICK_API_KEY'),
    'timeout' => env('ONECLICK_TIMEOUT', 30),
];
```

## Service Provider Setup

### Option 1: Service Container Binding

Add to `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OneClickDz\OCPay\OCPay;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OCPay::class, function ($app) {
            return new OCPay(config('ocpay.api_key', env('ONECLICK_API_KEY')));
        });
    }
}
```

### Option 2: Facade (Optional)

Create `app/Facades/OCPay.php`:

```php
<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use OneClickDz\OCPay\OCPay as OCPaySDK;

class OCPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OCPaySDK::class;
    }
}
```

Add to `config/app.php` aliases:

```php
'aliases' => [
    // ... other aliases
    'OCPay' => App\Facades\OCPay::class,
],
```

## Basic Usage

### Creating a Payment Link

```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use OneClickDz\OCPay\OCPay;
use OneClickDz\OCPay\DTO\CreateLinkRequest;
use OneClickDz\OCPay\DTO\ProductInfo;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private OCPay $ocpay
    ) {}

    public function createPayment(Request $request, Order $order)
    {
        $productInfo = new ProductInfo(
            title: "Order #{$order->id}",
            amount: $order->total,
            description: "Payment for order #{$order->id}"
        );

        $paymentRequest = new CreateLinkRequest(
            productInfo: $productInfo,
            feeMode: CreateLinkRequest::FEE_MODE_NO_FEE,
            successMessage: "Thank you! Your order #{$order->id} is being processed.",
            redirectUrl: route('payment.success', $order)
        );

        try {
            $response = $this->ocpay->createLink($paymentRequest);

            // Save payment reference to order
            $order->update([
                'payment_ref' => $response->paymentRef,
                'payment_url' => $response->paymentUrl,
            ]);

            // Redirect customer to payment page
            return redirect($response->paymentUrl);

        } catch (\OneClickDz\OCPay\Exception\ApiException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }
}
```

### Checking Payment Status

```php
public function checkPayment(Order $order)
{
    if (!$order->payment_ref) {
        return response()->json(['error' => 'No payment reference'], 400);
    }

    try {
        $status = $this->ocpay->checkPayment($order->payment_ref);

        if ($status->isConfirmed()) {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Fulfill order
            event(new OrderPaid($order));

            return response()->json([
                'status' => 'confirmed',
                'message' => 'Payment confirmed successfully'
            ]);
        }

        return response()->json([
            'status' => $status->status->value,
            'message' => $status->message
        ]);

    } catch (\OneClickDz\OCPay\Exception\ApiException $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

## Complete E-commerce Integration

### 1. Migration

Create migration for orders table:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_ref')->nullable()->after('status');
            $table->text('payment_url')->nullable()->after('payment_ref');
            $table->timestamp('paid_at')->nullable()->after('payment_ref');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_ref', 'payment_url', 'paid_at']);
        });
    }
};
```

### 2. Order Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total',
        'status',
        'payment_ref',
        'payment_url',
        'paid_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function isPaid(): bool
    {
        return $this->status === 'paid' && $this->paid_at !== null;
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }
}
```

### 3. Payment Service

Create `app/Services/PaymentService.php`:

```php
<?php

namespace App\Services;

use App\Models\Order;
use OneClickDz\OCPay\OCPay;
use OneClickDz\OCPay\DTO\CreateLinkRequest;
use OneClickDz\OCPay\DTO\ProductInfo;
use OneClickDz\OCPay\Exception\ApiException;

class PaymentService
{
    public function __construct(
        private OCPay $ocpay
    ) {}

    public function createPaymentLink(Order $order): string
    {
        $productInfo = new ProductInfo(
            title: "Order #{$order->id}",
            amount: (int) ($order->total * 100), // Convert to cents/smallest unit
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
            \Log::error('Payment link creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

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
            \Log::error('Payment verification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
```

### 4. Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function checkout(Order $order)
    {
        if ($order->isPaid()) {
            return redirect()->route('orders.show', $order)
                ->with('message', 'Order already paid');
        }

        try {
            $paymentUrl = $this->paymentService->createPaymentLink($order);
            return redirect($paymentUrl);

        } catch (\Exception $e) {
            return back()->withErrors(['payment' => 'Failed to create payment link']);
        }
    }

    public function callback(Order $order)
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
```

### 5. Routes

```php
// routes/web.php

Route::middleware('auth')->group(function () {
    Route::get('/orders/{order}/checkout', [OrderController::class, 'checkout'])
        ->name('orders.checkout');
    
    Route::get('/orders/{order}/callback', [OrderController::class, 'callback'])
        ->name('orders.callback');
    
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->name('orders.show');
});
```

## Background Job for Payment Polling

Create a job to poll payment status:

```php
<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckPaymentStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function handle(PaymentService $paymentService): void
    {
        // Only check if order is still pending
        if ($this->order->isPendingPayment()) {
            $paymentService->verifyPayment($this->order);
        }
    }
}
```

Schedule in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Check pending payments every 5 minutes
    $schedule->call(function () {
        Order::where('status', 'pending_payment')
            ->where('created_at', '>', now()->subHours(24))
            ->each(function ($order) {
                CheckPaymentStatus::dispatch($order);
            });
    })->everyFiveMinutes();
}
```

## Error Handling

Create a custom exception handler:

```php
<?php

namespace App\Exceptions;

use OneClickDz\OCPay\Exception\ApiException;
use OneClickDz\OCPay\Exception\ValidationException;
use OneClickDz\OCPay\Exception\UnauthorizedException;

class Handler extends ExceptionHandler
{
    public function render($request, \Throwable $exception)
    {
        if ($exception instanceof ValidationException) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $exception->getMessage()
            ], 400);
        }

        if ($exception instanceof UnauthorizedException) {
            return response()->json([
                'error' => 'Authentication failed',
                'message' => 'Invalid API key or merchant not validated'
            ], 403);
        }

        if ($exception instanceof ApiException) {
            return response()->json([
                'error' => 'Payment API error',
                'message' => $exception->getMessage()
            ], 500);
        }

        return parent::render($request, $exception);
    }
}
```

## Testing

### Feature Test Example

```php
<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_payment_link(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total' => 5000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get(route('orders.checkout', $order));

        $response->assertRedirect();
        $this->assertStringContainsString('pay.ocdz.link', $response->headers->get('Location'));

        $order->refresh();
        $this->assertNotNull($order->payment_ref);
    }
}
```

## Best Practices

1. **Always verify payment on backend** - Never trust frontend payment status
2. **Store payment reference** - Save `paymentRef` with your order
3. **Handle webhooks** - If available, use webhooks instead of polling
4. **Set timeouts** - Payment links expire in 20 minutes
5. **Log errors** - Always log payment API errors for debugging
6. **Use queues** - Use background jobs for payment status checking
7. **Validate amounts** - Always verify payment amount matches order total

## Support

- [SDK Documentation](../../README.md)
- [API Documentation](https://docs.oneclickdz.com/api-reference/ocpay)
- [OneClickDz Support](https://oneclickdz.com)

