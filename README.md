# A Google Cloud Firestore driver for Laravel Cache and Session

[![Latest Version on Packagist](https://img.shields.io/packagist/v/richan-fongdasen/firestore-laravel.svg?style=flat-square)](https://packagist.org/packages/richan-fongdasen/firestore-laravel)
[![License: MIT](https://poser.pugx.org/richan-fongdasen/firestore-laravel/license.svg)](https://opensource.org/licenses/MIT)
[![PHPStan](https://github.com/richan-fongdasen/firestore-laravel/actions/workflows/phpstan.yml/badge.svg?branch=main)](https://github.com/richan-fongdasen/firestore-laravel/actions/workflows/phpstan.yml)
[![Test](https://github.com/richan-fongdasen/firestore-laravel/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/richan-fongdasen/firestore-laravel/actions/workflows/test.yml)
[![Coding Style](https://github.com/richan-fongdasen/firestore-laravel/actions/workflows/coding-style.yml/badge.svg?branch=main)](https://github.com/richan-fongdasen/firestore-laravel/actions/workflows/coding-style.yml)
[![codecov](https://codecov.io/gh/richan-fongdasen/firestore-laravel/graph/badge.svg?token=RjW6ewweRy)](https://codecov.io/gh/richan-fongdasen/firestore-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/richan-fongdasen/firestore-laravel.svg?style=flat-square)](https://packagist.org/packages/richan-fongdasen/firestore-laravel)

This package allows you to use Google Cloud Firestore as a driver for Cache and Session store in Laravel application. This package is built on top of the official [Google Cloud Firestore PHP client library](https://github.com/googleapis/google-cloud-php-firestore).

> [!WARNING]  
> This package is not yet compatible with [Laravel Octane](https://github.com/laravel/octane).

## Requirements

-   PHP 8.2 or higher
-   Laravel 10.0 or higher
-   PHP Extension: `grpc`
-   PHP Extension: `protobuf`
-   Google Cloud Firestore Credentials

## Installation

You can install the package via composer:

```bash
composer require richan-fongdasen/firestore-laravel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="firestore-laravel-config"
```

This is the contents of the published config file:

```php
return [
    'project_id'  => env('GOOGLE_CLOUD_PROJECT'),
    'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    'database'    => env('FIRESTORE_DATABASE', '(default)'),

    'cache' => [
        'collection'           => env('FIRESTORE_CACHE_COLLECTION', 'cache'),
        'key_attribute'        => env('FIRESTORE_CACHE_KEY_ATTR', 'key'),
        'value_attribute'      => env('FIRESTORE_CACHE_VALUE_ATTR', 'value'),
        'expiration_attribute' => env('FIRESTORE_CACHE_EXPIRATION_ATTR', 'expired_at'),
    ],

    'session' => [
        'collection' => env('FIRESTORE_SESSION_COLLECTION', 'sessions'),
    ],
];
```

## Configuration

### Setting Up Firestore Authentication

Please see the [Authentication guide](https://github.com/googleapis/google-cloud-php/blob/main/AUTHENTICATION.md) for more information on authenticating your Google Cloud Firestore client.

### Package Configuration

You can configure the package by setting the following environment variables in your `.env` file.

```bash
GOOGLE_CLOUD_PROJECT=your-google-cloud-project-id
GOOGLE_APPLICATION_CREDENTIALS="/path-to/your-service-account.json"
FIRESTORE_DATABASE="(default)"
FIRESTORE_CACHE_COLLECTION=cache
FIRESTORE_CACHE_KEY_ATTR=key
FIRESTORE_CACHE_VALUE_ATTR=value
FIRESTORE_CACHE_EXPIRATION_ATTR=expired_at
FIRESTORE_SESSION_COLLECTION=sessions
```

### Cache Store Configuration

In order to use Firestore as a cache store, you need to append the following configuration into the `config/cache.php` file.

```php
'stores' => [
    'firestore' => [
        'driver' => 'firestore',
    ],
],
```

### Session Driver Configuration

In order to use Firestore as a session store, you need to modify the `SESSION_DRIVER` environment variable in your `.env` file.

```bash
SESSION_DRIVER=firestore
```

## Usage

There is no special usage for this package. You can use the Cache and Session store as you normally do in Laravel.

```php
// Cache store
Cache::put('key', 'value', 60); // Store a value in the cache for 60 seconds

$value = Cache::get('key'); // Retrieve a value from the cache

// Session
session(['key' => 'value']); // Store a value in the session

$value = session('key'); // Retrieve a value from the session
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Richan Fongdasen](https://github.com/richan-fongdasen)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
