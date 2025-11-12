# Integration Examples

This directory contains complete integration examples for OneClickDz OCPay PHP SDK.

## Available Examples

### Basic Usage (`basic-usage.php`)

Simple example demonstrating:
- SDK initialization
- Creating a payment link
- Checking payment status

**Best for:** Quick testing and understanding the SDK basics

### E-commerce Integration (`ecommerce-integration.php`)

Complete e-commerce order flow:
- Order creation
- Payment link generation
- Payment status polling
- Order fulfillment

**Best for:** Understanding complete payment flow in e-commerce applications

### Laravel Integration (`laravel/`)

Full Laravel framework integration:
- Service provider setup
- Payment service class
- Controller implementation
- Database migrations
- Background jobs for payment polling
- Error handling

**Best for:** Laravel applications

See [laravel/README.md](laravel/README.md) for complete documentation.

## Running Examples

### Basic Usage

```bash
php examples/basic-usage.php
```

### E-commerce Integration

```bash
php examples/ecommerce-integration.php
```

### Laravel Integration

1. Copy files from `examples/laravel/` to your Laravel project
2. Follow the guide in `examples/laravel/README.md`
3. Install dependencies: `composer require oneclickdz/ocpay-php-sdk`

## Requirements

All examples require:
- PHP 8.1+
- Composer dependencies installed (`composer install`)
- Valid OneClickDz API key (set in environment variable `ONECLICK_API_KEY`)

## Getting Your API Key

1. Sign up at [https://oneclickdz.com](https://oneclickdz.com)
2. Complete merchant validation at [https://oneclickdz.com/#/OcPay/merchant-info](https://oneclickdz.com/#/OcPay/merchant-info)
3. Get your API access token from the dashboard

## Setting API Key

**Windows PowerShell:**
```powershell
$env:ONECLICK_API_KEY = "your-api-key-here"
```

**Windows CMD:**
```cmd
set ONECLICK_API_KEY=your-api-key-here
```

**Linux/Mac:**
```bash
export ONECLICK_API_KEY="your-api-key-here"
```

## Need Help?

- [SDK Documentation](../README.md)
- [API Documentation](https://docs.oneclickdz.com/api-reference/ocpay)
- [OneClickDz Support](https://oneclickdz.com)

