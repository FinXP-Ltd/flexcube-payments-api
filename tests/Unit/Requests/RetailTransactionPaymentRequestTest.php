<?php

namespace Finxp\Flexcube\Tests\Unit\Requests;

use Finxp\Flexcube\Http\Requests\RetailTransactionPaymentRequest;
use Finxp\Flexcube\Tests\TestCase;
use Finxp\Flexcube\Rules\Iban;

class RetailTransactionPaymentUrlRequestTest extends TestCase
{
    const REQUIRED_STRING = 'required|string';
    protected $formRequest;

    public function setUp(): void
    {
        parent::setUp();
        $param = [
            'provider' => 'paymentiq'
        ];

        $this->formRequest = new RetailTransactionPaymentRequest($param);
    }

    /** @test */
    public function itShouldContainAllExpectedValidationRules()
    {
        $this->assertEquals([
            'type' => self::REQUIRED_STRING,
            'sender_name' => self::REQUIRED_STRING,
            'sender_iban' => self::REQUIRED_STRING,
            'recipient_name' => self::REQUIRED_STRING,
            'recipient_iban' => self::REQUIRED_STRING . '|different:sender_iban',
            'reference_id' => self::REQUIRED_STRING,
            'amount' => 'required|numeric',
            'currency' => self::REQUIRED_STRING . '|size:3|alpha',
            'remarks' => self::REQUIRED_STRING,
            'provider_redirect_url' => self::REQUIRED_STRING,
        ], $this->formRequest->rules());
    }

    /** @test */
    public function itShouldFailWhenNoRequiredDataProvided()
    {
        $validator = \Validator::make([], $this->formRequest->rules());

        $errorKeys = $validator->errors()->keys();

        $this->assertFalse($validator->passes());
        $this->assertContains('amount', $errorKeys);
        $this->assertContains('currency', $errorKeys);
    }

    /** @test */
    public function itShouldPassValidationRules()
    {
        $validator = \Validator::make(
            [
                'type' => 'PAYIN_MERCHANT',
                'sender_name' => 'Test Sender',
                'sender_iban' => 'NL88ABNA3215513765',
                'recipient_name' => 'Test Receiver',
                'recipient_iban' => 'NL88ABNA3215513725',
                'amount' => 1.4,
                'currency' => 'EUR',
                'remarks' => 'test',
                'reference_id' => 'test',
                'provider_redirect_url' => 'google.com'
            ],
            $this->formRequest->rules()
        );

        $this->assertTrue($validator->passes());
    }
}
