<?php

namespace Finxp\Flexcube\Tests\Mocks\Services\Freshdesk\Client;

use Finxp\FreshDesk\Exceptions\FreshDeskException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class Factory
{
    const CARD_PREFIX = 'card/';

    /** @var string */
    private $baseUrl;

    /** @var string */
    private $apiKey;
    const REQUEST_NOT_VALID = 'Request not valid!';
    const HEADER_APP_JSON = 'application/json';

    public function __construct()
    {
        $this->baseUrl = config('freshdesk.general.base_url');
        $this->apiKey = config('freshdesk.general.api_key');
    }

    private function _validateRequest($request): void
    {
        if ($request->serverError() || $request->clientError() || !$request->successful()) {
            throw new FreshDeskException($request['message'] ?? $request, $request->getStatusCode());
        }
    }
    
    protected function validate($payload)
    {
        return Validator::make($payload, [
            'email' => 'required',
            'subject' => 'required',
            'description' => 'required',
            'status' => 'required',
            'priority' => 'required',
            'source' => 'required',
        ]);

    }

    protected function payload($requestBody)
    {
       return [
            'unique_external_id' => $requestBody['uuid'] ?? null,
            'email' => $requestBody['email'],
            'subject' => $requestBody['subject'],
            'description' => $requestBody['description'],
            'status' => $requestBody['status'] ?? 2,
            'priority' => $requestBody['priority'] ?? 1,
            'source' => $requestBody['source'] ?? 2,
            'tags' => $requestBody['tags'] ?? [],
        ];
    }

    public function createTicket($requestBody)
    {
        $payload = $this->payload($requestBody);

        if ($this->validate($payload)->fails()) {
            throw new FreshDeskException(self::REQUEST_NOT_VALID, Response::HTTP_BAD_REQUEST);
        }

        $response = Http::withBasicAuth($this->apiKey, 'X')->withHeaders([
            'Content-Type' => self::HEADER_APP_JSON
        ])->post($this->baseUrl . '/api/v2/tickets', $payload);

        $this->_validateRequest($response);

        return $response->json();
    }
}