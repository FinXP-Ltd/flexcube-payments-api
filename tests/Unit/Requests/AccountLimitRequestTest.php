<?php

namespace Finxp\Flexcube\Tests\Unit\Requests;

use Finxp\Flexcube\Http\Requests\AccountLimitRequest;
use Finxp\Flexcube\Models\AccountLimit;
use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Validation\Rule;

class AccountLimitRequestTest extends TestCase
{
    protected $formRequest;

    public function setUp(): void
    {
        parent::setUp();
        $this->formRequest = new AccountLimitRequest();
    }

    /** @test */
    public function itShouldContainAllExpectedValidationRules()
    {
        $this->assertEquals([
            'limit' => 'required|numeric',
            'type' => 'required|string|' . Rule::in(AccountLimit::ACCOUNT_LIMIT_TYPE),
        ], $this->formRequest->rules());
    }

    /** @test */
    public function itShouldFailWhenNoRequiredDataProvided()
    {
        $validator = \Validator::make([], $this->formRequest->rules());

        $errorKeys = $validator->errors()->keys();

        $this->assertFalse($validator->passes());
        $this->assertContains('limit', $errorKeys);
        $this->assertContains('type', $errorKeys);
    }

    /** @test */
    public function itShouldPassValidationRules()
    {
        $this->setMerchantProvider();

        $validator = \Validator::make(
            [
                'limit' => 1,
                'type' => AccountLimit::TYPE_DAILY,
            ],
            $this->formRequest->rules()
        );

        $this->assertTrue($validator->passes());
    }
}
