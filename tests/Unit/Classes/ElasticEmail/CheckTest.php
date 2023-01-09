<?php

namespace Jojostx\ElasticEmail\Tests\Unit\Classes\ElasticEmail;

use Jojostx\ElasticEmail\Classes\ElasticEmail;
use Jojostx\ElasticEmail\Classes\ValidationResult;
use Jojostx\ElasticEmail\Facades\ElasticEmail as ElasticEmailFacade;
use Jojostx\ElasticEmail\Tests\Unit\TestCase;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Jojostx\ElasticEmail\Classes\ObjectSerializer;
use Jojostx\ElasticEmail\Enums\EmailValidationStatus;

class CheckTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now());
    }

    /** @test */
    public function result_is_returned_from_cache_if_fresh_is_set_to_false_and_it_exists_in_the_cache()
    {
        // Set a cached value that we can get.
        Cache::shouldReceive('get')
            ->once()
            ->withArgs(['elasticemail_result_mail@jojostx.co.uk'])
            ->andReturn($this->validResponseStructure());

        // Assert that the HTTP client is never called.
        Http::shouldReceive('get')->never();

        $elasticEmail = new ElasticEmail(123);

        $result = $elasticEmail->check('mail@jojostx.co.uk');

        $this->assertValidationResultIsCorrect($result);
    }

    /** @test */
    public function result_is_returned_from_the_api_if_fresh_is_set_to_false_but_it_does_not_exist_in_the_cache()
    {
        // Set a cached value that we can get.
        Cache::shouldReceive('get')
            ->once()
            ->withArgs(['elasticemail_result_mail@jojostx.co.uk'])
            ->andReturnNull();

        // Mock the API response.
        Http::fake(function () {
            return Http::response($this->validResponseStructure());
        });

        $elasticEmail = new ElasticEmail(123);

        $result = $elasticEmail->check('mail@jojostx.co.uk');

        $this->assertValidationResultIsCorrect($result);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.elasticemail.com/v4/verifications/' . ObjectSerializer::toPathValue('mail@jojostx.co.uk');
        });
    }

    /** @test */
    public function result_is_returned_from_the_api_if_fresh_is_set_to_true()
    {
        Cache::shouldReceive('forget')
            ->withArgs(['elasticemail_result_mail@jojostx.co.uk'])
            ->once()
            ->andReturnTrue();

        Cache::shouldReceive('get')->never();

        Cache::shouldReceive('forever')->never();

        // Mock the API response.
        Http::fake(function () {
            return Http::response($this->validResponseStructure());
        });

        $elasticEmail = new ElasticEmail(123);

        $result = $elasticEmail->fresh()->check('mail@jojostx.co.uk');

        $this->assertValidationResultIsCorrect($result);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.elasticemail.com/v4/verifications/' . ObjectSerializer::toPathValue('mail@jojostx.co.uk');
        });
    }

    /** @test */
    public function result_is_cached_if_should_bust_cache_is_set_to_true()
    {
        // Mock the API response.
        Http::fake(function () {
            return Http::response($this->validResponseStructure());
        });

        Cache::shouldReceive('get')->once()->andReturnNull();

        Cache::shouldReceive('forever')
            ->withArgs([
                'elasticemail_result_mail@jojostx.co.uk',
                $this->validResponseStructure(),
            ])
            ->once()
            ->andReturnTrue();

        $elasticEmail = new ElasticEmail(123);

        $result = $elasticEmail->shouldCache()->check('mail@jojostx.co.uk');

        $this->assertValidationResultIsCorrect($result);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.elasticemail.com/v4/verifications/' . ObjectSerializer::toPathValue('mail@jojostx.co.uk');
        });
    }

    /** @test */
    public function validation_can_be_carried_out_using_the_facade()
    {
        // Set the API key in the config so that it can be used when
        // creating the facade.
        config(['elasticemail.api_key' => 123]);

        // Mock the API response.
        Http::fake(function () {
            return Http::response($this->validResponseStructure());
        });

        $result = ElasticEmailFacade::check('mail@jojostx.co.uk');

        $this->assertValidationResultIsCorrect($result);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.elasticemail.com/v4/verifications/' . ObjectSerializer::toPathValue('mail@jojostx.co.uk');
        });
    }

    private function validResponseStructure(): array
    {
        return [
            "account"         => "mail",
            "domain"       => "jojostx.co.uk",
            "email"        => "mail@jojostx.co.uk",
            "suggestedSpelling" => "",
            "disposable"   => false,
            "role"         => true,
            "reason"        => "",
            "result"        => "valid",
            "dateAdded" => now()->toDateTimeString(),
        ];
    }

    private function invalidResponseStructure(): array
    {
        return [
            'account'         => 'support',
            'domain'       => 'jojostx.co.uk',
            'email'        => 'support@jojostx.co.uk',
            'suggestedSpelling' => '',
            'disposable'   => false,
            'role'         => true,
            'reason'        => "Invalid email",
            'dateAdded' => now(),
            'result'        => "invalid",
        ];
    }

    private function assertValidationResultIsCorrect(ValidationResult $result): void
    {
        $this->assertSame('mail@jojostx.co.uk', $result->email);
        $this->assertSame('', $result->suggestedSpelling);
        $this->assertSame('mail', $result->account);
        $this->assertSame('jojostx.co.uk', $result->domain);
        $this->assertFalse($result->disposable);
        $this->assertTrue($result->role);
        $this->assertSame('', $result->reason);
        $this->assertSame(EmailValidationStatus::VALID, $result->result);
        $this->assertTrue(now()->isSameAs($result->dateAdded));
    }
}
