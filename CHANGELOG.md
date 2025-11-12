# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-15

### Added

- Initial release of OneClickDz OCPay PHP SDK
- `OCPay` main class for SDK initialization
- `OCPayService` for payment operations
- `Client` class for HTTP communication
- `createLink()` method for creating payment links
- `checkPayment()` method for checking payment status
- Complete DTO classes:
  - `ProductInfo` - Product information
  - `CreateLinkRequest` - Payment link creation request
  - `CreateLinkResponse` - Payment link creation response
  - `PaymentLink` - Payment link details
  - `CheckPaymentResponse` - Payment status response
  - `TransactionDetails` - Transaction information
  - `PaymentStatus` enum - Payment status values
- Exception classes:
  - `OCPayException` - Base exception
  - `ApiException` - General API exceptions
  - `ValidationException` - Validation errors (400)
  - `UnauthorizedException` - Authentication errors (403)
  - `NotFoundException` - Resource not found (404)
  - `PaymentExpiredException` - Payment expired (410)
- Comprehensive PHPDoc documentation
- Full README with examples
- Composer configuration with PSR-4 autoloading
- MIT License

### Features

- Support for creating single-use payment links
- Support for checking payment status
- Full type safety with PHP 8.1+ features
- Custom exception handling
- Request/response validation
- Support for all fee modes (NO_FEE, SPLIT_FEE, CUSTOMER_FEE)
- Support for custom success messages and redirect URLs

[1.0.0]: https://github.com/oneclickdz/ocpay-php-sdk/releases/tag/v1.0.0

