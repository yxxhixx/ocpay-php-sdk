<?php

declare(strict_types=1);

namespace OneClickDz\OCPay;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use OneClickDz\OCPay\Exception\ApiException;
use OneClickDz\OCPay\Exception\NotFoundException;
use OneClickDz\OCPay\Exception\PaymentExpiredException;
use OneClickDz\OCPay\Exception\UnauthorizedException;
use OneClickDz\OCPay\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;

/**
 * Main HTTP client for OneClickDz OCPay API
 *
 * Handles all HTTP communication with the API, including request/response
 * processing and error handling.
 */
class Client

{
    private const BASE_URL = 'https://api.oneclickdz.com';
    private const HEADER_ACCESS_TOKEN = 'X-Access-Token';
    private const HEADER_CONTENT_TYPE = 'Content-Type';
    private const CONTENT_TYPE_JSON = 'application/json';

    private GuzzleClient $httpClient;
    private string $accessToken;

    /**
     * Create a new API client instance
     *
     * @param string $accessToken Your OneClickDz API access token
     * @param array<string, mixed> $options Additional Guzzle client options
     * @param GuzzleClient|null $httpClient Custom HTTP client (for testing)
     */
    public function __construct(
        string $accessToken,
        array $options = [],
        ?GuzzleClient $httpClient = null
    ) {
        $this->accessToken = $accessToken;

        if ($httpClient !== null) {
            $this->httpClient = $httpClient;
        } else {
            $defaultOptions = [
                'base_uri' => self::BASE_URL,
                'timeout' => 30,
                'headers' => [
                    self::HEADER_ACCESS_TOKEN => $this->accessToken,
                    self::HEADER_CONTENT_TYPE => self::CONTENT_TYPE_JSON,
                ],
            ];

            $mergedOptions = array_merge($defaultOptions, $options);
            $this->httpClient = new GuzzleClient($mergedOptions);
        }
    }

    /**
     * Make a POST request to the API
     *
     * @param string $endpoint API endpoint (e.g., '/ocpay/createLink')
     * @param array<string, mixed> $data Request body data
     * @return array<string, mixed> Decoded JSON response
     * @throws ApiException
     */
    public function post(string $endpoint, array $data): array
    {
        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => $data,
            ]);

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            throw $this->handleException($e);
        } catch (GuzzleException $e) {
            throw new ApiException(
                sprintf('HTTP request failed: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Make a GET request to the API
     *
     * @param string $endpoint API endpoint (e.g., '/ocpay/checkPayment/OCPL-XXX')
     * @return array<string, mixed> Decoded JSON response
     * @throws ApiException
     */
    public function get(string $endpoint): array
    {
        try {
            $response = $this->httpClient->get($endpoint);

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            throw $this->handleException($e);
        } catch (GuzzleException $e) {
            throw new ApiException(
                sprintf('HTTP request failed: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Handle API response
     *
     * @param ResponseInterface $response
     * @return array<string, mixed>
     * @throws ApiException
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException(
                sprintf('Invalid JSON response: %s', json_last_error_msg()),
                $statusCode
            );
        }

        // Check if response indicates an error
        if (isset($data['success']) && $data['success'] === false) {
            $message = $data['message'] ?? 'API request failed';
            $requestId = $data['meta']['requestId'] ?? null;

            throw new ApiException(
                $message,
                $statusCode,
                null,
                $requestId,
                $statusCode,
                $data
            );
        }

        return $data;
    }

    /**
     * Handle HTTP exceptions and convert to appropriate API exceptions
     *
     * @param RequestException $e
     * @return ApiException
     */
    private function handleException(RequestException $e): ApiException
    {
        $response = $e->getResponse();
        $statusCode = $response ? $response->getStatusCode() : 0;
        $body = $response ? (string) $response->getBody() : '';
        $data = json_decode($body, true);
        $requestId = $data['meta']['requestId'] ?? null;
        $message = $data['message'] ?? $e->getMessage();

        return match ($statusCode) {
            400 => new ValidationException($message, $statusCode, $e, $requestId, $statusCode, $data),
            403 => new UnauthorizedException($message, $statusCode, $e, $requestId, $statusCode, $data),
            404 => new NotFoundException($message, $statusCode, $e, $requestId, $statusCode, $data),
            410 => new PaymentExpiredException($message, $statusCode, $e, $requestId, $statusCode, $data),
            default => new ApiException($message, $statusCode, $e, $requestId, $statusCode, $data),
        };
    }
}

