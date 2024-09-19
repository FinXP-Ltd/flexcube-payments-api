<?php

namespace Finxp\Flexcube\Tests\Mocks\Jobs;

use Exception;

class WebhookException extends Exception
{
    public static function urlNotSet(): self
    {
        return new static('Could not call webhook because the url has not been set.');
    }

    public static function secretNotSet(): self
    {
        return new static('Could not call webhook because no secret has been set.');
    }

    public static function webhookIdNotSet(): self
    {
        return new static('Could not call the webhook because no id has been set.');
    }
}
