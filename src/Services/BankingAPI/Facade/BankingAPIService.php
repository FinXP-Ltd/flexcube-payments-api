<?php

namespace Finxp\Flexcube\Services\BankingAPI\Facade;

use Illuminate\Support\Facades\Facade;
use Finxp\Flexcube\Services\BankingAPI\Client\BankingAPIClient;

class BankingAPIService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BankingAPIClient::class;
    }
}
