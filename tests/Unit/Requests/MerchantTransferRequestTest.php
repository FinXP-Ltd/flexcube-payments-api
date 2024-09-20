<?php

namespace Finxp\Flexcube\Tests\Unit\Requests;

use Finxp\Flexcube\Http\Requests\MerchantTransferRequest;
use Finxp\Flexcube\Tests\TestCase;
use Finxp\Flexcube\Rules\Iban;

class MerchantTransferRequestTest extends TestCase
{
    protected $formRequest;

    public function setUp(): void
    {
        parent::setUp();
        $this->formRequest = new MerchantTransferRequest();
    }

    /** @test */
    public function itShouldContainAllExpectedValidationRules()
    {   
        $this->assertEquals([
            'account' => 'required|string',
            'amount' => 'required|numeric|min:0.01|decimal:0,2',
            'currency' => 'required|string|size:3|alpha',
            'sender_name' => 'required|string',
            'sender_iban' => [
                'required',
                'string',
                new Iban
            ],
            'recipient_name' => 'required|string',
            'recipient_iban' => [
                'required',
                'string',
                'different:sender_iban',
                new Iban
            ],
            'reference_id' => 'required|string',
            'remarks' => 'required|string'
        ], $this->formRequest->rules());
    }

    /** @test */
    public function itShouldFailWhenNoRequiredDataProvided()
    {

        $validator = \Validator::make([], $this->formRequest->rules());

        $errorKeys = $validator->errors()->keys();

        $this->assertFalse($validator->passes());
        $this->assertContains('sender_iban', $errorKeys);
        $this->assertContains('amount', $errorKeys);
        $this->assertContains('recipient_iban', $errorKeys);
        $this->assertContains('remarks', $errorKeys);
        $this->assertContains('currency', $errorKeys);
    }

    /** @test */
    public function itShouldPassValidationRules()
    {

        $validator = \Validator::make(
            [   
                'account' => '00000012312300',
                'amount' => 1,
                'currency' => 'EUR',
                'sender_name' => 'Test',
                'sender_iban' => 'MT43PAUU92005050500020012285001',
                'recipient_name' => 'Test',
                'recipient_iban' => 'MT91LSHH75645568837825495468916',
                'reference_id' => 'test0001',
                'remarks' => 'Test',
            ],
            $this->formRequest->rules()
        );

        $this->assertTrue($validator->passes());
    }
}
