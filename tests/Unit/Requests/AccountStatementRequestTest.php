<?php

namespace Finxp\Flexcube\Tests\Unit\Requests;

use Finxp\Flexcube\Http\Requests\AccountStatementRequest;
use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Validation\Rule;

class AccountStatementRequestTest extends TestCase
{
    protected $formRequest;

    public function setUp(): void
    {
        parent::setUp();
        $this->formRequest = new AccountStatementRequest();
    }

    /** @test */
    public function itShouldContainAllExpectedValidationRules()
    {
        $this->assertEquals([
            'currency' => 'required|string|size:3|alpha',
            'customer_ac_no' => 'required|string',
            'from_date' => 'sometimes|date_format:Y-m-d',
            'to_date' => 'sometimes|date_format:Y-m-d'
        ], $this->formRequest->rules());
    }

    /** @test */
    public function itShouldFailWhenNoRequiredDataProvided()
    {
        $validator = \Validator::make([], $this->formRequest->rules());

        $errorKeys = $validator->errors()->keys();

        $this->assertFalse($validator->passes());
        $this->assertContains('currency', $errorKeys);
        $this->assertContains('customer_ac_no', $errorKeys);
    }

    /** @test */
    public function itShouldPassValidationRules()
    {
        $validator = \Validator::make(
            [
                'currency' => 'EUR',
                'customer_ac_no' => '0001230001',
            ],
            $this->formRequest->rules()
        );

        $this->assertTrue($validator->passes());
    }
}
