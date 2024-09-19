<?php

namespace Finxp\Flexcube\Tests\Mocks\Services\Freshdesk\Facade;

use Finxp\Flexcube\Tests\Mocks\Services\Freshdesk\Client\Factory;
use Illuminate\Support\Facades\Facade;

class FreshDesk extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }
}
