<?php

namespace Jojostx\ElasticEmail\Classes;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ElasticEmail
{
    private const BASE_URL = 'https://api.elasticemail.com/v4';

    /**
     * Whether or not the email validation result should
     * be cached.
     *
     * @var bool
     */
    private $shouldCache = false;

    /**
     * Whether or not a fresh result should be fetched from
     * the API. Setting this field to true will ignore
     * any cached values. It will also delete the
     * previously cached result if one exists.
     *
     * @var bool
     */
    private $fresh = false;

    /**
     * ElasticEmail constructor.
     *
     * @param  string  $apiKey
     */
    public function __construct(
        private string $apiKey,
        private int $timeout = 20
    ) {
    }

    /**
     * Run a validation check against the email address.
     * Once this has been done, return the results in
     * a ValidationObject.
     *
     * @param  string  $email
     * @return ValidationResult
     *
     * @throws \Exception
     */
    public function check(string $email): ValidationResult
    {
        $cacheKey = $this->buildCacheKey($email);

        $result = $this->fetchFromCache($email);

        if (is_null($result)) {
            $result = $this->fetchFromApi($email);
        }

        if ($this->shouldCache) {
            Cache::forever($cacheKey, $result->prepareForCaching()); // reset result enum to string value
        }

        return $result;
    }

    /**
     * Run validation checks on more than one email address.
     * Add each of the results to a Collection and then
     * return it.
     *
     * @param  array|Collection  $emails
     * @return Collection
     *
     * @throws \Exception
     */
    public function checkMany(array|Collection $emails)
    {
        $emails = is_array($emails) ? collect($emails) : $emails;

        $cachedValidationResults = $emails
            ->map(fn (string $email) => $this->fetchFromCache($email))
            ->filter(); // removes all the falsy values and keeps all the validationResults

        $emails = $emails
            ->reject(
                fn ($email) => $cachedValidationResults->contains(
                    fn (ValidationResult $value) => $value->email === $email
                )
            ); // reject the emails that were pulled from the cache

        if (blank($emails)) {
            return $cachedValidationResults->keyBy(fn (ValidationResult $result) => $result->email);
        }

        $responses = Http::pool(
            fn (Pool $pool) => $emails->map(
                fn ($email) => $this->makePoolRequest($pool, $email)
            )
        ); // make the validation request to elastic email for the emails that don't reside in the cache

        return \collect($responses)
            ->transform(fn (Response $response) => ValidationResult::makeFromResponse($response->json()))
            ->concat($cachedValidationResults) // concat the cached result with the fresh results
            ->keyBy(fn (ValidationResult $result) => $result->email);
    }

    /**
     * Whether or not the email validation result should
     * be cached after it's fetched from the API.
     *
     * @param  bool  $shouldCache
     * @return $this
     */
    public function shouldCache(bool $shouldCache = true): self
    {
        $this->shouldCache = $shouldCache;

        return $this;
    }

    /**
     * Whether or not a fresh result should be fetched from
     * the API. Setting field this to true will ignore
     * any cached values. It will also delete the
     * previously cached result if one exists.
     *
     * @param  bool  $fresh
     * @return $this
     */
    public function fresh(bool $fresh = true): self
    {
        $this->fresh = $fresh;

        return $this;
    }

    /**
     * Make a request to the API and fetch a new result.
     *
     * @param  string  $email
     * @return ValidationResult
     *
     * @throws \Exception
     */
    private function fetchFromApi(string $email): ValidationResult
    {
        $request = $this->buildRequest();

        $response = $request->post(static::BASE_URL . '/verifications/' . ObjectSerializer::toPathValue($email));

        if ($response->failed()) {
            throw $response->toException();
        }

        return ValidationResult::makeFromResponse($response->json());
    }

    /**
     * fetch a result from the cache.
     *
     * @param  string  $email
     * @return ValidationResult|null
     */
    private function fetchFromCache($email): ?ValidationResult
    {
        $result = null;

        $cacheKey = $this->buildCacheKey($email);

        if ($this->fresh) {
            Cache::forget($cacheKey);
        } else {
            $cached = Cache::get($cacheKey);

            if (filled($cached)) {
                $result = ValidationResult::makeFromResponse($cached);
            }
        }

        return $result;
    }

    /**
     * Build the default request to be sent to the elastic email api.
     *
     * @return PendingRequest
     */
    private function buildRequest(): PendingRequest
    {
        return Http::withHeaders($this->getHeaders())
            ->acceptJson()
            ->timeout($this->timeout);
    }

    /**
     * Build and make a pool request to elastic email api
     */
    private function makePoolRequest(Pool $pool, string $email)
    {
        return $pool->as($email)
            ->withHeaders($this->getHeaders())
            ->acceptJson()
            ->timeout($this->timeout)
            ->post(static::BASE_URL . '/verifications/' . ObjectSerializer::toPathValue($email));
    }

    /**
     * Build and return the key that will be used when
     * setting or getting the validation result from
     * the cache.
     *
     * @param  string  $email
     * @return string
     */
    private function buildCacheKey(string $email): string
    {
        return 'elasticemail_result_' . $email;
    }

    /**
     * return the headers for the request
     * 
     * @return array
     */
    private function getHeaders()
    {
        return ['X-ElasticEmail-ApiKey' => $this->apiKey];
    }
}
