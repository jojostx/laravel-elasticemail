<?php

namespace Jojostx\ElasticEmail\Classes;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Str;
use Jojostx\ElasticEmail\Enums\EmailValidationStatus;

class ValidationResult
{
    /**
     * The local part of the email address. Example:
     * 'mail' in 'mail@jojostx.co.uk'.
     *
     * @var string
     */
    public $account;

    /**
     * The domain part of the email address. Example:
     * 'jojostx.co.uk' in 'mail@jojostx.co.uk'.
     *
     * @var string
     */
    public $domain;

    /**
     * The email address that the validation was carried
     * out on.
     *
     * @var string
     */
    public $email;

    /**
     * A suggested email address in case a typo was detected.
     *
     * @var string
     */
    public $suggestedSpelling;

    /**
     * Whether or not the requested email is disposable.
     * Example: 'hello@mailinator.com'.
     *
     * @var bool
     */
    public $disposable;

    /**
     * Whether or not the requested email is a role email
     * address. Example: 'support@jojostx.co.uk'.
     *
     * @var bool
     */
    public $role;

    /**
     * All detected issues.
     *
     * @var string
     */
    public $reason;

    /**
     * The result of the validation check.
     *
     * @var EmailValidationStatus
     */
    public $result;

    /**
     * The date amd time when the validation check was run
     *  via API.
     *
     * @var Carbon
     */
    public $dateAdded;

    /**
     * Build a new ValidationObject from the API response
     * data, set the properties and then return it. If
     * we are making the object from an API response
     * rather than from a cached result, we will
     * also set the validatedAt date.
     *
     * @param  array  $response
     * @return static
     */
    public static function makeFromResponse(array $response): self
    {
        $validationResult = new static;

        foreach ($response as $fieldName => $value) {
            $objectFieldName = Str::camel((string) $fieldName);
            $validationResult->{$objectFieldName} = $value;
        }

        try {
            $validationResult->dateAdded = SupportCarbon::parse($validationResult->dateAdded);
        } catch (InvalidFormatException $e) {
            $validationResult->dateAdded = now();
        }

        $validationResult->result = empty($validationResult->result) ?
            EmailValidationStatus::NONE :
            EmailValidationStatus::from(Str::lower($validationResult->result));

        return $validationResult;
    }

    public function prepareForCaching(): array
    {
        $result = (array) $this;

        return \array_merge($result, ['result' => $this->result->value ?? 'none']);
    }
}
