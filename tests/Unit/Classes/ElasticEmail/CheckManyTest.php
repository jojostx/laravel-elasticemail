<?php

namespace Jojostx\ElasticEmail\Tests\Unit\Classes\ElasticEmail;

use Jojostx\ElasticEmail\Classes\ElasticEmail;
use Jojostx\ElasticEmail\Tests\Unit\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Jojostx\ElasticEmail\Classes\ObjectSerializer;
use Jojostx\ElasticEmail\Enums\EmailValidationStatus;

class CheckManyTest extends TestCase
{
    /** @test */
    public function many_emails_can_be_validated_via_the_api()
    {
        Carbon::setTestNow(now());

        Cache::shouldReceive('get')
            ->once()
            ->withArgs(['elasticemail_result_mail@jojostx.co.uk'])
            ->andReturnNull();

        Cache::shouldReceive('get')
            ->once()
            ->withArgs(['elasticemail_result_support1@jojostx.co.uk'])
            ->andReturnNull();

        $emailUrl1 = 'https://api.elasticemail.com/v4/verifications/' . ObjectSerializer::toPathValue('mail@jojostx.co.uk');
        $emailUrl2 = 'https://api.elasticemail.com/v4/verifications/' . ObjectSerializer::toPathValue('support1@jojostx.co.uk');

        Http::fake([
            $emailUrl1         => Http::response([
                'account'        => 'mail',
                'domain'      => 'jojostx.co.uk',
                'email'       => 'mail@jojostx.co.uk',
                'suggestedSpelling'  => '',
                'role'        => true,
                'disposable'  => false,
                'reason'        => "The email may be a role email",
                'dateAdded' => now(),
                'result'        => 'valid',
            ]),
            $emailUrl2         => Http::response([
                'email'        => 'support1@jojostx.co.uk',
                'suggestedSpelling'  => 'support@jojostx.co.uk',
                'account'      => 'support1',
                'domain'       => 'jojostx.co.uk',
                'role'         => false,
                'disposable'   => true,
                'reason'        => "The email may be a role email",
                'dateAdded' => now(),
                'result'        => 'risky',
            ]),
        ]);

        $ElasticEmail = new ElasticEmail(123);

        $result = $ElasticEmail->checkMany(['mail@jojostx.co.uk', 'support1@jojostx.co.uk']);
        $this->assertInstanceOf(Collection::class, $result);

        $this->assertSame('mail@jojostx.co.uk', $result['mail@jojostx.co.uk']->email);
        $this->assertSame('', $result['mail@jojostx.co.uk']->suggestedSpelling);
        $this->assertSame('mail', $result['mail@jojostx.co.uk']->account);
        $this->assertSame('jojostx.co.uk', $result['mail@jojostx.co.uk']->domain);
        $this->assertTrue($result['mail@jojostx.co.uk']->role);
        $this->assertFalse($result['mail@jojostx.co.uk']->disposable);
        $this->assertSame('The email may be a role email', $result['mail@jojostx.co.uk']->reason);
        $this->assertTrue(now()->isSameAs($result['mail@jojostx.co.uk']->dateAdded));
        $this->assertSame(EmailValidationStatus::VALID, $result['mail@jojostx.co.uk']->result);

        $this->assertSame('support1@jojostx.co.uk', $result['support1@jojostx.co.uk']->email);
        $this->assertSame('support@jojostx.co.uk', $result['support1@jojostx.co.uk']->suggestedSpelling);
        $this->assertSame('support1', $result['support1@jojostx.co.uk']->account);
        $this->assertSame('jojostx.co.uk', $result['support1@jojostx.co.uk']->domain);
        $this->assertFalse($result['support1@jojostx.co.uk']->role);
        $this->assertTrue($result['support1@jojostx.co.uk']->disposable);
        $this->assertSame('The email may be a role email', $result['support1@jojostx.co.uk']->reason);
        $this->assertTrue(now()->isSameAs($result['support1@jojostx.co.uk']->dateAdded));
        $this->assertSame(EmailValidationStatus::RISKY, $result['support1@jojostx.co.uk']->result);
    }

    /** @test */
    public function many_emails_can_be_validated_via_the_cache()
    {
        // Set a cached value that we can get.
        Cache::shouldReceive('get')
            ->once()
            ->withArgs(['elasticemail_result_mail@jojostx.co.uk'])
            ->andReturn([
                'account'        => 'mail',
                'domain'      => 'jojostx.co.uk',
                'email'       => 'mail@jojostx.co.uk',
                'suggestedSpelling'  => '',
                'role'        => false,
                'disposable'  => false,
                'reason'        => "The email may be a role email",
                'dateAdded' => now()->subDays(5)->startOfDay(),
                'result'        => 'valid',
            ]);

        // Set a cached value that we can get.
        Cache::shouldReceive('get')
            ->once()
            ->withArgs(['elasticemail_result_support1@jojostx.co.uk'])
            ->andReturn([
                'account'        => 'support1',
                'domain'      => 'jojostx.co.uk',
                'email'       => 'support1@jojostx.co.uk',
                'suggestedSpelling'   => 'support@jojostx.co.uk',
                'role'        => false,
                'disposable'  => true,
                'reason'        => "The email may be a role email",
                'dateAdded' => now()->subYear()->startOfDay(),
                'result'        => 'risky',
            ]);

        // Assert that the HTTP client is never called.
        Http::shouldReceive('pool')->never();

        $ElasticEmail = new ElasticEmail(123);

        $result = $ElasticEmail->checkMany(['mail@jojostx.co.uk', 'support1@jojostx.co.uk']);
        $this->assertInstanceOf(Collection::class, $result);

        $this->assertSame('mail@jojostx.co.uk', $result['mail@jojostx.co.uk']->email);
        $this->assertSame('', $result['mail@jojostx.co.uk']->suggestedSpelling);
        $this->assertSame('mail', $result['mail@jojostx.co.uk']->account);
        $this->assertSame('jojostx.co.uk', $result['mail@jojostx.co.uk']->domain);
        $this->assertFalse($result['mail@jojostx.co.uk']->role);
        $this->assertFalse($result['mail@jojostx.co.uk']->disposable);
        $this->assertTrue($result['mail@jojostx.co.uk']->dateAdded->isSameAs(now()->subDays(5)->startOfDay()));
        $this->assertSame(EmailValidationStatus::VALID, $result['mail@jojostx.co.uk']->result);

        $this->assertSame('support1@jojostx.co.uk', $result['support1@jojostx.co.uk']->email);
        $this->assertSame('support@jojostx.co.uk', $result['support1@jojostx.co.uk']->suggestedSpelling);
        $this->assertSame('support1', $result['support1@jojostx.co.uk']->account);
        $this->assertSame('jojostx.co.uk', $result['support1@jojostx.co.uk']->domain);
        $this->assertFalse($result['support1@jojostx.co.uk']->role);
        $this->assertTrue($result['support1@jojostx.co.uk']->disposable);
        $this->assertTrue($result['support1@jojostx.co.uk']->dateAdded->isSameAs(now()->subYear()->startOfDay()));
        $this->assertSame(EmailValidationStatus::RISKY, $result['support1@jojostx.co.uk']->result);
    }
}
