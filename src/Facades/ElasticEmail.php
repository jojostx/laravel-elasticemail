<?php

namespace Jojostx\ElasticEmail\Facades;

use Jojostx\ElasticEmail\Classes\ValidationResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ValidationResult check(string $emailAddress)
 * @method static Collection checkMany(array $emailAddresses)
 * @method static self shouldCache(bool $shouldCache = true)
 * @method static self fresh(bool $fresh = true)
 *
 * @see \Jojostx\ElasticEmail\Classes\ElasticEmail
 */
class ElasticEmail extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'elasticemail';
    }
}
