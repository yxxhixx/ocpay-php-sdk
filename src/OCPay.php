<?php

declare(strict_types=1);

namespace OneClickDz\OCPay;

use OneClickDz\OCPay\DTO\CheckPaymentResponse;
use OneClickDz\OCPay\DTO\CreateLinkRequest;
use OneClickDz\OCPay\DTO\CreateLinkResponse;

/**
 * OneClickDz OCPay PHP SDK
 *
 * Official PHP SDK for integrating OneClickDz OCPay payment gateway.
 *
 * This SDK provides a simple and intuitive interface for:
 * - Creating single-use payment links
 * - Checking payment status
 * - Handling payment callbacks
 *
 * @package OneClickDz\OCPay
 * @see https://docs.oneclickdz.com/api-reference/ocpay
 *
 * @example
 * ```php
 * use OneClickDz\OCPay\OCPay;
 * use OneClickDz\OCPay\DTO\CreateLinkRequest;
 * use OneClickDz\OCPay\DTO\ProductInfo;
 *
 * // Initialize SDK
 * $ocpay = new OCPay('your-api-key');
 *
 * // Create a payment link
 * $productInfo = new ProductInfo(
 *     title: 'Premium Subscription',
 *     amount: 5000,
 *     description: 'Monthly access to premium features'
 * );
 *
 * $request = new CreateLinkRequest(
 *     productInfo: $productInfo,
 *     successMessage: 'Thank you for your purchase!',
 *     redirectUrl: 'https://yourstore.com/success'
 * );
 *
 * $response = $ocpay->createLink($request);
 * echo "Payment URL: " . $response->paymentUrl;
 *
 * // Check payment status
 * $status = $ocpay->checkPayment($response->paymentRef);
 * if ($status->isConfirmed()) {
 *     echo "Payment confirmed!";
 * }
 * ```
 */
class OCPay
{
    private OCPayService $ocpayService;

    /**
     * Create a new OCPay SDK instance
     *
     * @param string $accessToken Your OneClickDz API access token
     * @param array<string, mixed> $options Additional client options (timeout, etc.)
     */
    public function __construct(string $accessToken, array $options = [])
    {
        $client = new Client($accessToken, $options);
        $this->ocpayService = new OCPayService($client);
    }

    /**
     * Create a payment link
     *
     * @param CreateLinkRequest $request Payment link creation request
     * @return CreateLinkResponse Payment link response
     * @throws \OneClickDz\OCPay\Exception\ApiException
     */
    public function createLink(CreateLinkRequest $request): CreateLinkResponse
    {
        return $this->ocpayService->createLink($request);
    }

    /**
     * Check payment status
     *
     * @param string $paymentRef Payment reference code
     * @return CheckPaymentResponse Payment status response
     * @throws \OneClickDz\OCPay\Exception\ApiException
     */
    public function checkPayment(string $paymentRef): CheckPaymentResponse
    {
        return $this->ocpayService->checkPayment($paymentRef);
    }

    /**
     * Get the underlying OCPay service instance
     * (Useful for advanced use cases or testing)
     *
     * @return OCPayService
     */
    public function getService(): OCPayService
    {
        return $this->ocpayService;
    }
}

