<?php
namespace Finxp\Flexcube\Services\BankingAPI\Client;

use Finxp\Flexcube\Services\BankingAPI\Client\BaseClient;

abstract class AbstractAPI
{
    public function __construct(BaseClient $client)
    {
        $this->client = $client;
    }
}