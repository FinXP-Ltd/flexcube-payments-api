<?php

namespace Finxp\Flexcube\Traits;

trait HttpRequest
{
    public function httpApiRequest( $method, $request, $url, $payload = array() )
    {

        switch($method) {
            default:
            case 'GET':
                $response = $request
                    ->get(
                        $url,
                        $payload
                    );
                break;
            case 'POST':
                $response = $request
                    ->post(
                        $url,
                        $payload
                    );
                break;
            case 'PUT':
                $response = $request
                    ->put(
                        $url,
                        $payload
                    );
                break;
        }

         return $response;
    }

    protected function _responseData( $request )
    {
        $this->_logError( $request );

        return [
            'code' => $request->status(),
            'data' => $request->json()
        ];
    }

    private function _logError( $request ): void
    {
        if ($request->clientError() || $request->serverError() || ! $request->successful()) {
            info(print_r($request->json(), true)); // Log the error to possibly see the error
        }
    }
}