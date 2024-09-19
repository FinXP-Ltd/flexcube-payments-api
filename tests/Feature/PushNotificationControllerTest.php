<?php

namespace Finxp\Flexcube\Tests\Feature;

use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Http\Response;

use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;

class PushNotificationControllerTest extends TestCase
{
    /** @test */
    public function itShouldStoreSubscription()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/c2LwpdkORMk:ABCDEFGH',
            'keys'     => [
                'auth'        => '9wZB2123',
                'p256dh' => 'BD8MqqjLH'
            ]
        ];

        $this->withHeaders($headers)
            ->postJson(route('notification.store'), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'code', 'status'
            ]);
    }

    /** @test */
    public function itShouldFailToStoreIfMissingHasMissingData()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/c2LwpdkORMk:ABCDEFGH',
            'keys'     => [
                'auth'        => '9wZB2123'
            ]
        ];

        $this->withHeaders($headers)
            ->postJson(route('notification.store'), $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
