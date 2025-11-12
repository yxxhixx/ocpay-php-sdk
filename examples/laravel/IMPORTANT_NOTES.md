# Important Notes & Edge Cases

## Payment Amount Validation

### Issue: Fractional Amounts

The OneClickDz API only accepts whole numbers (no decimals) between 500 and 500,000 DZD.

**Solution:**
- Use `ProductInfo::fromDecimalAmount()` to automatically round decimal amounts
- Validate amounts before creating payment links
- Show clear error messages to users if amount is outside range

**Example:**
```php
// If your order total is 499.99 DZD
try {
    $productInfo = ProductInfo::fromDecimalAmount(
        title: "Order #123",
        amount: 499.99 // Will round to 500
    );
} catch (\InvalidArgumentException $e) {
    // Handle: "Amount must be at least 500 DZD"
}
```

### Issue: Amount Outside Range

**Minimum (500 DZD):**
- If order total < 500 DZD, payment link creation will fail
- Solution: Combine multiple small orders or add minimum order requirement

**Maximum (500,000 DZD):**
- If order total > 500,000 DZD, payment link creation will fail
- Solution: Split large orders into multiple payments

## Payment Link Expiry

### Issue: 20 Minute Expiry

Payment links expire **20 minutes** after creation if payment is not initiated.

**Solution:**
1. Track `payment_link_created_at` in your database
2. Check expiry before making API calls
3. Mark orders as failed if expired
4. Don't mark expired payments as "completed"

**Implementation:**
```php
// Check expiry before API call
if ($paymentService->isPaymentLinkExpired($order)) {
    $order->update([
        'status' => 'payment_failed',
        'payment_failed_reason' => 'Payment link expired (20 minutes)',
    ]);
    return;
}
```

## Background Job Processing

### Issue: Job Failures & Delays

If background jobs fail or are delayed, orders might get stuck as "pending".

**Solutions:**

1. **Configure Retries:**
   ```php
   public int $tries = 3; // Retry 3 times
   public int $backoff = 60; // Wait 60s between retries
   ```

2. **Monitor Queue:**
   - Use Laravel Horizon or queue monitoring
   - Set up alerts for failed jobs
   - Monitor queue length

3. **Scale Workers:**
   - Ensure sufficient queue workers
   - Use Supervisor for process management
   - Scale horizontally if needed

4. **Handle Timeouts:**
   ```php
   public int $timeout = 30; // 30 second timeout
   ```

5. **Prevent Overlaps:**
   ```php
   ->withoutOverlapping() // Prevent duplicate executions
   ```

### Issue: Timezone Consistency

Ensure all servers use the same timezone:

```php
// config/app.php
'timezone' => 'Africa/Algiers', // Or your timezone
```

## Error Handling Best Practices

### 1. Don't Mark as Failed on Temporary Errors

```php
try {
    $paymentService->verifyPayment($order);
} catch (ApiException $e) {
    // Don't mark as failed - might be temporary
    // Let job retry
    throw $e;
}
```

### 2. Log All Errors

```php
Log::error('Payment verification failed', [
    'order_id' => $order->id,
    'payment_ref' => $order->payment_ref,
    'error' => $e->getMessage(),
    'request_id' => $e->getRequestId(),
]);
```

### 3. Handle Specific Exceptions

```php
catch (PaymentExpiredException $e) {
    // Handle expiry specifically
} catch (ApiException $e) {
    // Handle other API errors
}
```

## Production Checklist

- [ ] Queue workers running and monitored
- [ ] Supervisor configured for auto-restart
- [ ] Timezone set correctly
- [ ] Error logging configured
- [ ] Alerts set up for failed jobs
- [ ] Payment expiry handling implemented
- [ ] Amount validation before payment link creation
- [ ] Retry mechanism configured
- [ ] Database indexes on payment_ref and status
- [ ] Monitoring dashboard for payment status

## Database Indexes

Add indexes for better performance:

```php
Schema::table('orders', function (Blueprint $table) {
    $table->index('payment_ref');
    $table->index('status');
    $table->index(['status', 'payment_link_created_at']);
});
```

## Testing

Test these scenarios:

1. **Amount Validation:**
   - Order < 500 DZD
   - Order > 500,000 DZD
   - Decimal amounts (499.99)

2. **Expiry:**
   - Payment link created 21 minutes ago
   - Payment link created 19 minutes ago

3. **Job Failures:**
   - API timeout
   - Network error
   - Invalid payment reference

4. **Concurrency:**
   - Multiple jobs processing same order
   - Race conditions

