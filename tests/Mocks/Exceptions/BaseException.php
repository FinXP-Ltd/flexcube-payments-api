<?php

namespace Finxp\Flexcube\Tests\Mocks\Exceptions;

use Exception;

abstract class BaseException extends Exception
{
    /** @var string */
    protected $code;

    /**
     * @param string $message
     * @param int    $statusCode
     * @param string $code
     */
    public function __construct($message, $statusCode, $code)
    {
        parent::__construct($message, $statusCode);
        $this->code = $code;
    }

    public function getErrorCode(): string
    {
        return $this->code;
    }
}
