<?php

namespace Finxp\Flexcube\Tests\Unit\Requests;

use Finxp\Flexcube\Http\Requests\InternalIndividualTransferRequest;
use Finxp\Flexcube\Tests\TestCase;
use Finxp\Flexcube\Rules\Iban;

class InternalIndividualTransferRequestTest extends TestCase
{
    protected $formRequest;

    public function setUp(): void
    {
        parent::setUp();
        $this->formRequest = new InternalIndividualTransferRequest();
    }

    /** @test */
    public function itShouldContainAllExpectedValidationRules()
    {
        
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
            'currency' => 'required|string|size:3|alpha',
        ], $this->formRequest->rules());
    }

    /** @test */
    public function itShouldFailWhenNoRequiredDataProvided()
    {

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
}
