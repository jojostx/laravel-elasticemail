<?php

namespace Jojostx\ElasticEmail\Tests\Unit\Classes\ValidationResult;

use Jojostx\ElasticEmail\Classes\ValidationResult;
use Jojostx\ElasticEmail\Tests\Unit\TestCase;
use Carbon\Carbon;
use Jojostx\ElasticEmail\Enums\EmailValidationStatus;

class MakeFromResponseTest extends TestCase
{
    /** @test */
    public function new_object_is_returned_with_correct_fields_set_and_the_dateAdded_date_is_already_set()
    {
        Carbon::setTestNow(now());

        $responseData = [
            'account'         => 'mail',
            'domain'       => 'jojostx.co.uk',
            'email'        => 'mail@jojostx.co.uk',
            'suggestedSpelling' => 'mail@jojostx.co.uk',
            'disposable'   => false,
            'role'         => true,
            'reason'        => "invalid email",
            'dateAdded' => now(),
            'result'        => "None",
        ];

        $newObject = ValidationResult::makeFromResponse($responseData);

        $this->assertSame('mail@jojostx.co.uk', $newObject->email);
        $this->assertSame('mail@jojostx.co.uk', $newObject->suggestedSpelling);
        $this->assertSame('mail', $newObject->account);
        $this->assertSame('jojostx.co.uk', $newObject->domain);
        $this->assertTrue($newObject->role);
        $this->assertFalse($newObject->disposable);
        $this->assertSame(EmailValidationStatus::NONE, $newObject->result);
        $this->assertSame('invalid email', $newObject->reason);
        $this->assertEquals(now(), $newObject->dateAdded);
    }

    /** @test */
    public function new_object_is_returned_with_correct_fields_set_and_the_dateAdded_date_is_not_already_set()
    {
        Carbon::setTestNow(now());

        $responseData = [
            'account'         => 'mail',
            'domain'       => 'jojostx.co.uk',
            'email'        => 'mail@jojostx.co.uk',
            'suggestedSpelling' => 'mail@jojostx.co.uk',
            'disposable'   => false,
            'role'         => true,
            'reason'        => "invalid email",
            'result'        => "Risky",
        ];

        $newObject = ValidationResult::makeFromResponse($responseData);

        $this->assertSame('mail@jojostx.co.uk', $newObject->email);
        $this->assertSame('mail@jojostx.co.uk', $newObject->suggestedSpelling);
        $this->assertSame('mail', $newObject->account);
        $this->assertSame('jojostx.co.uk', $newObject->domain);
        $this->assertTrue($newObject->role);
        $this->assertFalse($newObject->disposable);
        $this->assertSame(EmailValidationStatus::RISKY, $newObject->result);
        $this->assertSame('invalid email', $newObject->reason);
        $this->assertEquals(now(), $newObject->dateAdded);
    }
}
