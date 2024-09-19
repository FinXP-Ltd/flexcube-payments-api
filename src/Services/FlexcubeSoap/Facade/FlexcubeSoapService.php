<?php

namespace Finxp\Flexcube\Services\FlexcubeSoap\Facade;

use Illuminate\Support\Facades\Facade;
use Finxp\Flexcube\Services\FlexcubeSoap\Client\FlexcubeSoapClient;

class FlexcubeSoapService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FlexcubeSoapClient::class;
    }
}
