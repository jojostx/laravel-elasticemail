## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
    - [Requirements](#requirements)
    - [Install the Package](#install-the-package)
    - [Publish the Config](#publish-the-config)
    - [Getting Your ElasticEmail API Key](#getting-your-elasticemail-api-key)
- [Usage](#usage)
    - [Methods](#methods)
        - [Validating One Email Address](#validating-one-email-address)
        - [Validating Multiple Email Addresses](#validating-multiple-email-addresses)
    - [Facade](#facade)
    - [Available Validation Result Properties](#available-validation-result-properties)
    - [Caching](#caching)
        - [Caching Validation Results](#caching-validation-results)
        - [Busting the Cached Validation Results](#busting-the-cached-validation-results)
- [Testing](#testing)
- [Security](#security)
- [Contribution](#contribution)
- [Credits](#credits)
- [Changelog](#changelog)
- [License](#license)
    
## Overview
Laravel ElasticEmail is a lightweight wrapper Laravel package that can be used for validating email addresses via the
[ElasticEmail API](https://elasticemail.com/). The package supports caching so that you can start validating email addresses instantly.

## Installation

### Requirements
The package has been developed and tested to work with the following minimum requirements:

- PHP 8.0+
- Laravel 8+

### Install the Package
You can install the package via Composer:

```bash
composer require jojostx/laravel-elasticemail
```

### Publish the Config
You can then publish the package's config file (so that you can make changes to them) by using the following command:
```bash
php artisan vendor:publish --provider="Jojostx\ElasticEmail\Providers\ElasticEmailProvider"
```

### Getting Your ElasticEmail API Key
To use this package and interact with the ElasticEmail API, you'll need to register on the [ElasticEmail API](https://elasticemail.com/)
website and get your API key. Once you have the key, you can set it in your ` .env ` file as shown below:

```
ElasticEmail_API_KEY=your-api-key-here
```

## Usage
### Methods
#### Validating One Email Address

To validate a single email address, you can use the ` check() ` method that is provided in the package. This method returns a ` ValidationResult ` object.

The example below shows how to validate a single email address:

```php
use Jojostx\ElasticEmail\Classes\ElasticEmail;

$ElasticEmail = new ElasticEmail('api-key-here');
$validationResult = $ElasticEmail->check('example@domain.com');
```

#### Validating Multiple Email Addresses

To validate multiple email addresses, you can use the ` checkMany() ` method that is provided in the package. This method returns a ` Collection ` of ` ValidationResult ` objects.

The example below shows how to validate multiple email addresses:

```php
use Jojostx\ElasticEmail\Classes\ElasticEmail;

$ElasticEmail = new ElasticEmail('api-key-here');
$validationResults = $ElasticEmail->checkMany(['example@domain.com', 'test@test.com']);
```


### Facade
If you prefer to use facades in Laravel, you can choose to use the provided ` ElasticEmail ` facade instead of instantiating the 
``` Jojostx\ElasticEmail\Classes\ElasticEmail ```
class manually.

The example below shows an example of how you could use the facade to validate an email address:

```php     
use ElasticEmail;
    
return ElasticEmail::check('example@domain.com');
```

### Available Validation Result Properties

| Field        | Description                                                                                           |
|--------------|-------------------------------------------------------------------------------------------------------|
| email        | The email address that the validation was carried out on.                                             |
| suggestedSpelling  | A suggested email address in case a typo was detected.                                                |
| account        | The local part of the email address. Example: 'mail' in 'mail@jojostx.co.uk'.                  |
| domain       | The domain part of the email address. Example: 'jojostx.co.uk' in 'mail@jojostx.co.uk'. |
| role         | Whether or not the requested email is a role email address. Example: 'support@jojostx.co.uk'.  |
| disposable   | Whether or not the requested email is disposable. Example: 'hello@mailinator.com'.                    |
| reason       | A short description for the result of the check.     |
| result       | An enum (``` Jojostx\ElasticEmail\Enums\EmailValidationStatus ```) representing the value of the check. ['Valid', 'Invalid', 'Risky', 'Unknown', 'None']     |
| addedAt      | A ` Carbon ` object containing the date and time that the original validation API request was made.   |

### Caching
#### Caching Validation Results
There might be times when you want to cache the validation results for an email. This can have significant performance benefits for if
you try to validate the email again, due to the fact that the results will be fetched from the cache rather than from a new API request.

As an example, if you were importing a CSV containing email addresses, you might want to validate each of the addresses. However, if the
CSV contains some duplicated email addresses, it could lead to unnecessary API calls being made. So, by using the caching, each unique
address would only be fetched once from the API. To do this, you can use the ` shouldCache() ` method.

Using caching is recommended as it reduces the chances of you reaching the monthly request limits or rate limits that are
used by ElasticEmail. Read more about the [API limits here](https://elasticemail.com/documentation#rate_limits).

The example below shows how to cache the validation results:

```php
use Jojostx\ElasticEmail\Classes\ElasticEmail;

$ElasticEmail = new ElasticEmail('api-key-here');

// Result fetched from the API.
$validationResults = $ElasticEmail->shouldCache()->check('example@domain.com');

// Result fetched from the cache.
$validationResults = $ElasticEmail->shouldCache()->check('example@domain.com');
```

#### Busting the Cached Validation Results
By default, the package will always try to fetch the validation results from the cache before trying to fetch them via the API.
As mentioned before, this can lead to multiple performance benefits.

However, there may be times that you want to ignore the cached results and make a new request to the API. As an example, you
might have a cached validation result that is over 6 months old and could possibly be outdated or inaccurate, so it's likely
that you want to update the validation data and ensure it is correct. To do this, you can use the ` fresh() ` method.

The example below shows how to fetch a new validation result:

```php
use Jojostx\ElasticEmail\Classes\ElasticEmail;

$ElasticEmail = new ElasticEmail('api-key-here');

$validationResults = $ElasticEmail->fresh()->check('example@domain.com');
```

## Testing

```bash
vendor/bin/phpunit
```

## Security

If you find any security related issues, please contact me directly at [Jojostx](ikuskid7@gmail.com) to report it.

## Contribution

If you wish to make any changes or improvements to the package, feel free to make a pull request.

To contribute to this library, please use the following guidelines before submitting your pull request:

- Write tests for any new functions that are added. If you are updating existing code, make sure that the existing tests
pass and write more if needed.
- Follow [PSR-2](https://www.php-fig.org/psr/psr-2/) coding standards.
- Make all pull requests to the ``` master ``` branch.

## Credits

- [Jojostx](ikuskid7@gmail.com)
- [All Contributors](https://github.com/jojostx/laravel-elasticemail/graphs/contributors)

## Changelog

Check the [CHANGELOG](CHANGELOG.md) to get more information about the latest changes.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
