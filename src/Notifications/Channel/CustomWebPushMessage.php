<?php

namespace Finxp\Flexcube\Notifications\Channel;

use Illuminate\Support\Arr;

use NotificationChannels\WebPush\WebPushMessage;

class CustomWebPushMessage extends WebPushMessage
{
    /**
     * Get an array representation of the message.
     *
     * @return array
     */
    public function toArray()
    {
        return ['notification' => Arr::except(array_filter(get_object_vars($this)), ['options'])];
    }
}
