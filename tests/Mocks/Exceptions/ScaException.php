<?php

namespace Finxp\Flexcube\Tests\Mocks\Exceptions;

use Illuminate\Http\Response;

class ScaException extends BaseException
{
    public static function otpInvalid(): self
    {
        return new static(
            'Invalid OTP',
            Response::HTTP_BAD_REQUEST,
            'Invalid OTP'
        );
    }

    public static function otpResendLimiting($time): self
    {
        return new static(
            'Please wait for before requesting a new OTP.',
            Response::HTTP_PRECONDITION_FAILED,
            'Please wait for before requesting a new OTP.'
        );
    }
}
