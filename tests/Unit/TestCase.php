<?php

namespace Jojostx\ElasticEmail\Tests\Unit;

use Jojostx\ElasticEmail\Facades\ElasticEmail;
use Jojostx\ElasticEmail\Providers\ElasticEmailProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [ElasticEmailProvider::class];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'elasticemail' => ElasticEmail::class,
        ];
    }
}
