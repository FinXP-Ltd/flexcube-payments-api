<?php

namespace Finxp\Flexcube\Tests\Feature;

use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Http\Response;
use Mockery;
use Mockery\MockInterface;

use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;
use Finxp\Flexcube\Tests\Mocks\Models\User;
use Finxp\Flexcube\Tests\Mocks\Models\Role;
use Finxp\Flexcube\Services\FlexcubeSoap\Facade\FlexcubeSoapService;
use Finxp\Flexcube\Tests\Mocks\Models\MerchantAccount;

class MerchantControllerTest extends TestCase
{
    /** @test */
    public function itShouldGetMerchantAccounts()
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
            ->getJson(route('merchant.account.list'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data'
            ]);

        $data = json_decode($res->getContent(), true);
        $this->assertTrue(sizeof($data) > 0);
    }

    /** @test */
    public function itShouldFailGetMerchantAccounts()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $this->mockFailedGetAccounts();

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->withHeaders($headers)
            ->getJson(route('merchant.account.list'))
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'message', 'code', 'status'
            ]);
    }

    /** @test */
    public function itShouldGetMerchantAccountsMerchantAdmin()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $role = Role::create(['name' => 'merchant_admin']);

        $user->roles()->sync($role->id);

        $this->actingAs($user);

        $merchant = Merchant::factory()->create();

        $merchant->staff()->attach($user->id);

        MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $this->setMerchantProvider();
        $this->mockGetAccounts();

        $this->getJson(route('merchants.accounts', ['merchantId' => $merchant->id]))
           ->assertStatus(Response::HTTP_OK)
           ->assertJsonStructure([
            'data'
           ]);
    }

    /** @test */
    public function itShouldUpdateIsNotificationActiveField()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $role = Role::create(['name' => 'merchant_admin']);

        $user->roles()->sync($role->id);

        $this->actingAs($user);

        $merchant = Merchant::factory()->create();

        $merchant->staff()->attach($user->id);

        $merchantAccounts = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $payload = [
          'is_notification_active'
        ];

        $this->setMerchantProvider();

        $this->putJson(
            route('merchant.account.updateAccountNotification',
            ['merchantId' => $user, 'id' => $merchantAccounts->id]),
            $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'code', 'message', 'status'
            ]);
    }

    /** @test */
    public function itShouldSyncMerchantAccounts()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $role = Role::create(['name' => 'operations']);

        $user->roles()->sync($role->id);

        $this->actingAs($user);

        $merchant = Merchant::factory()->create();

        $this->setMerchantProvider();
        $this->mockGetAccounts();

        $this->postJson(route('operations.merchants.accounts.updateMerchantAccountsList',
            ['merchantId' => $merchant->id])
        )->assertJsonStructure([
            'status', 'code', 'message'
        ]);
    }

    // Payments V2
    
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
