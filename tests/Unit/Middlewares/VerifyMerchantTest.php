<?php

namespace Tests\Unit\Middlewares;

use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;

use Finxp\Flexcube\Http\Middlewares\VerifyMerchant;
use Illuminate\Http\Response;

class VerifyMerchantTest extends TestCase
{

    public function testItShouldPassTheMiddleware()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'revoked' => false
            ]);

        $request = new Request();

        $request->headers->replace(
            array_merge(
                $request->headers->all(),
                [
                    'PHP_AUTH_USER' => $apiAccess->key,
                    'PHP_AUTH_PW'   => $apiAccess->secret,
                    'Content-Type'  => 'application/json'
                ]
            )
        );

        $middleware = app(VerifyMerchant::class);

        $merchantResult = Merchant::find($merchant->id);

        $middleware->handle($request, function ($req) use ($merchantResult) {
            $this->assertEquals($merchantResult->id, $req->get('merchant')->id);
        });
    }

    public function testItShouldResponseError()
    {

        $this->setPiqConfig();

        $request = new Request();

        $request->headers->replace(
            array_merge(
                $request->headers->all(),
                [
                    'X-merchant' => '4',
                    'PHP_AUTH_PW'   => 'test123',
                    'Content-Type'  => 'application/json'
                ]
            )
        );

        $middleware = app(VerifyMerchant::class);

        $response = $middleware->handle($request, function ($req) {});

        $content = json_decode($response->getContent(), true);
            
        $this->assertEquals($response->getStatusCode(), Response::HTTP_UNAUTHORIZED);
        $this->assertEquals($content['code'], Response::HTTP_UNAUTHORIZED);
        $this->assertEquals($content['message'], 'Unauthorized to make a request!');
    }

    public function testItShouldThrowAuthExceptionOnPIQWithoutMerchantHeader()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $this->setPiqConfig();

        $request = new Request();

        $request->headers->replace(
            array_merge(
                $request->headers->all(),
                [
                    'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}"),
                    'PHP_AUTH_USER' => $apiAccess->key,
                    'PHP_AUTH_PW'   => $apiAccess->secret,
                    'Content-Type'  => 'application/json'
                ]
            )
        );

        $middleware = app(VerifyMerchant::class);

        $response = $middleware->handle($request, function ($req) {});

        $content = json_decode($response->getContent(), true);
            
        $this->assertEquals($response->getStatusCode(), Response::HTTP_UNAUTHORIZED);
        $this->assertEquals($content['code'], Response::HTTP_UNAUTHORIZED);
        $this->assertEquals($content['message'], 'Must provide merchant id!');
    }

    public function testItShouldThrowAuthExceptionOnPIQWitInvalidMerchantHeader()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $this->setPiqConfig();

        $request = new Request();

        $request->headers->replace(
            array_merge(
                $request->headers->all(),
                [
                    'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}"),
                    'X-Merchant' => '44',
                    'PHP_AUTH_USER' => $apiAccess->key,
                    'PHP_AUTH_PW'   => $apiAccess->secret,
                    'Content-Type'  => 'application/json'
                ]
            )
        );

        $middleware = app(VerifyMerchant::class);

        $response = $middleware->handle($request, function ($req) {});

        $content = json_decode($response->getContent(), true);
            
        $this->assertEquals($response->getStatusCode(), Response::HTTP_UNAUTHORIZED);
        $this->assertEquals($content['code'], Response::HTTP_UNAUTHORIZED);
        $this->assertEquals($content['message'], 'Not a valid PaymentIQ Merchant!');
    }
}
