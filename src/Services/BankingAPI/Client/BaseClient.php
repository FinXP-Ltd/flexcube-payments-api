<?php
namespace Finxp\Flexcube\Services\BankingAPI\Client;

use Finxp\Flexcube\Traits\HttpRequest;
use Illuminate\Support\Facades\Http;

class BaseClient
{
    use HttpRequest;

    protected $baseUrl;

    protected $apiCredentials;

    public function __construct()
    {
        $this->baseUrl = config('flexcube-soap.banking_api.base_url');
        $this->apiCredentials = array_values(config('flexcube-soap.banking_api.credentials', []));
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    public function request( $method, $resource = '', $payload = array() )
    {
        $request = $this->api_request( $method, $resource, $payload );

        return $this->_responseData($request);
    }

    private function api_request( $method, $resource = '', $payload = array() )
    {
        $request = Http::withBasicAuth(...$this->apiCredentials)
            ->withHeaders($this->getHeaders());

        $url = $this->baseUrl.$resource;

        return $this->httpApiRequest($method, $request, $url, $payload);
    }
}