<?php

namespace Finxp\Flexcube\Tests\Unit\Requests;

use Finxp\Flexcube\Http\Requests\InternalTransferRequest;
use Finxp\Flexcube\Tests\TestCase;
use Finxp\Flexcube\Rules\Iban;

class InternalTransferRequestTest extends TestCase
{
    protected $formRequest;

    public function setUp(): void
    {
        parent::setUp();
        $this->formRequest = new InternalTransferRequest();
    }

    /** @test */
    public function itShouldContainAllExpectedValidationRules()
    {
        $this->setMerchantProvider();
        
        $this->assertEquals([
            'debtor_iban' => [
                'required',
                'string',
                new Iban
            ],
            'amount' => 'required|numeric|min:0.01|decimal:0,2',
            'creditor_iban' => [
                'required',
                'string',
                'different:debtor_iban',
                new Iban
            ],
            'remarks' => 'required|string',
            'currency' => 'required|string|size:3|alpha'
        ], $this->formRequest->rules());
    }

    /** @test */
    public function itShouldFailWhenNoRequiredDataProvided()
    {
        $this->setMerchantProvider();

        $validator = \Validator::make([], $this->formRequest->rules());

        $errorKeys = $validator->errors()->keys();

        $this->assertFalse($validator->passes());
        $this->assertContains('debtor_iban', $errorKeys);
        $this->assertContains('amount', $errorKeys);
        $this->assertContains('creditor_iban', $errorKeys);
        $this->assertContains('remarks', $errorKeys);
        $this->assertContains('currency', $errorKeys);
    }

    /** @test */
    public function itShouldPassValidationRules()
    {
        $this->setMerchantProvider();

        $validator = \Validator::make(
            [
                'debtor_iban' => 'MT33VXQO54555745395517169247593',
                'amount' => 1,
                'creditor_iban' => 'MT29IZLN78218334749552679151639',
                'remarks' => 'Test',
                'currency' => 'EUR'
            ],
            $this->formRequest->rules()
        );

        $this->assertTrue($validator->passes());
    }

    
    /** @test */
    public function itShouldContainAllExpectedProviderValidationRules()
    {
        $this->formRequest = new InternalTransferRequest(['provider' => 'paymentiq']);
        $this->assertEquals([
            'type' => 'required|string',
            'sender_name' => 'required|string',
            'sender_iban' => 'required|string',
            'recipient_name' => 'required|string',
            'recipient_iban' => 'required|string|different:sender_iban',
            'reference_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01|decimal:0,2',
            'remarks' => 'required|string',
            'currency' => 'required|string|size:3|alpha'
        ], $this->formRequest->rules());
    }

    /** @test */
    public function itShouldFailWhenNoRequiredDataProvidedProvider()
    {
        $this->formRequest = new InternalTransferRequest(['provider' => 'paymentiq']);

        $validator = \Validator::make([], $this->formRequest->rules());

        $errorKeys = $validator->errors()->keys();

        $this->assertFalse($validator->passes());
        $this->assertContains('amount', $errorKeys);
        $this->assertContains('remarks', $errorKeys);
        $this->assertContains('currency', $errorKeys);
    }

    /** @test */
    public function itShouldPassValidationProviderRules()
    {
        $this->formRequest = new InternalTransferRequest(['provider' => 'paymentiq']);

        $validator = \Validator::make(
            [
                'type' => 'PAYIN_MERCHANT',
                'account' => '0000012312312',
                'sender_name' => 'Test Sender',
                'sender_iban' => 'NL88ABNA3215513765',
                'recipient_name' => 'Test Receiver',
                'recipient_iban' => 'NL88ABNA3215513725',
                'amount' => 1.4,
                'currency' => 'EUR',
                'remarks' => 'test',
                'reference_id' => 'test'
            ],
            $this->formRequest->rules()
        );

        $this->assertTrue($validator->passes());
    }
}
