<?php

declare(strict_types=1);

namespace OneClickDz\OCPay;

use OneClickDz\OCPay\DTO\CheckPaymentResponse;
use OneClickDz\OCPay\DTO\CreateLinkRequest;
use OneClickDz\OCPay\DTO\CreateLinkResponse;
use OneClickDz\OCPay\Exception\ApiException;

/**
 * OCPay Service
 *
 * Provides methods for creating payment links and checking payment status
 * using the OneClickDz OCPay API.
 *
 * @see https://docs.oneclickdz.com/api-reference/ocpay/create-link
 * @see https://docs.oneclickdz.com/api-reference/ocpay/check-payment
 */
class OCPayService
{
    private Client $client;

    /**
     * Create a new OCPay service instance
     *
     * @param Client $client API client instance
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create a payment link
     *
     * Creates a secure, single-use payment link with customizable settings.
     * Perfect for e-commerce orders, service payments, and subscription renewals.
     *
     * **Merchant Validation Required**: Complete merchant validation at
     * https://oneclickdz.com/#/OcPay/merchant-info before using this endpoint.
     *
     * **Amount Limits:**
     * - Minimum: 500 DZD
     * - Maximum: 500,000 DZD
     * - Must be whole numbers (no decimals)
     *
     * **Fee Structure:**
     * - 0% if using OneClick balance
     * - 1% withdrawal fee only (configurable per transaction)
     *
     * **Link Expiration:**
     * Payment links expire 20 minutes after creation if payment is not initiated.
     *
     * @param CreateLinkRequest $request Payment link creation request
     * @return CreateLinkResponse Payment link response with URL and reference
     * @throws ApiException
     *
     * @example
     * ```php
     * use OneClickDz\OCPay\DTO\CreateLinkRequest;
     * use OneClickDz\OCPay\DTO\ProductInfo;
     *
     * $productInfo = new ProductInfo(
     *     title: 'Premium Subscription',
     *     amount: 5000,
     *     description: 'Monthly access to premium features'
     * );
     *
     * $request = new CreateLinkRequest(
     *     productInfo: $productInfo,
     *     feeMode: CreateLinkRequest::FEE_MODE_NO_FEE,
     *     successMessage: 'Thank you for your purchase!',
     *     redirectUrl: 'https://yourstore.com/success?orderId=12345'
     * );
     *
     * $response = $ocpayService->createLink($request);
     * echo $response->paymentUrl; // Share this URL with customer
     * echo $response->paymentRef; // Save this for tracking
     * ```
     */
    public function createLink(CreateLinkRequest $request): CreateLinkResponse
    {
        $response = $this->client->post('/v3/ocpay/createLink', $request->toArray());

        if (!isset($response['data'])) {
            throw new ApiException('Invalid API response: missing data field');
        }

        return CreateLinkResponse::fromArray($response['data']);
    }

    /**
     * Check payment status
     *
     * Check payment status in real-time using the payment reference.
     * Essential for order processing, status polling, and payment verification.
     *
     * **Payment Status Values:**
     * - `PENDING`: Payment is in progress, wait and poll again
     * - `CONFIRMED`: Payment completed successfully, fulfill the order
     * - `FAILED`: Payment was declined, expired, or cancelled
     *
     * **Link Expiration:**
     * Payment links expire 20 minutes after creation if no payment is initiated.
     * After expiration, the status will be `FAILED`.
     *
     * @param string $paymentRef Payment reference code (e.g., "OCPL-A1B2C3-D4E5")
     * @return CheckPaymentResponse Payment status response
     * @throws ApiException
     *
     * @example
     * ```php
     * $response = $ocpayService->checkPayment('OCPL-A1B2C3-D4E5');
     *
     * if ($response->isConfirmed()) {
     *     // Payment successful - fulfill order
     *     fulfillOrder($orderId);
     * } elseif ($response->isFailed()) {
     *     // Payment failed - mark order as failed
     *     markOrderFailed($orderId);
     * } else {
     *     // Still pending - poll again later
     *     schedulePolling($orderId);
     * }
     * ```
     */
    public function checkPayment(string $paymentRef): CheckPaymentResponse
    {
        $endpoint = sprintf('/v3/ocpay/checkPayment/%s', urlencode($paymentRef));
        $response = $this->client->get($endpoint);

        if (!isset($response['data'])) {
            throw new ApiException('Invalid API response: missing data field');
        }

        return CheckPaymentResponse::fromArray($response['data']);
    }
}

