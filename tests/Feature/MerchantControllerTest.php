<?php

namespace Finxp\Flexcube\Tests\Feature;

use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Http\Response;

use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;

class MerchantControllerTest extends TestCase
{

    /** @test */
    public function itShouldGetMerchantAccountsV2()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $this->mockGetAccounts();

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.accounts.list'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data'
            ]);

        $data = json_decode($res->getContent(), true);
        $this->assertTrue(sizeof($data) > 0);
    }
}
