<?php

namespace Finxp\Flexcube\Tests\Mocks\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Finxp\Flexcube\Tests\Mocks\Models\Token;
use Finxp\Flexcube\Tests\Mocks\Exceptions\ScaException;
use Finxp\Flexcube\Tests\Mocks\Contracts\TokenContract;

abstract class BaseOtp
{
    protected function validateOtp(Request $request, array $payload): void
    {
        $this->validateRequest($payload);

        $token = Token::where($payload)->first();

        if (!$token) {
            throw ScaException::otpInvalid();
        }

        $token->revoke();

        $request->macro('otpToken', function () use ($token): TokenContract {
            return $token->fresh();
        });
    }

    protected function validateRequest(array $payload): void
    {
        $validator = Validator::make($payload, Token::rules());

        if ($validator->fails()) {
            throw ScaException::otpInvalid();
        }
    }
}
