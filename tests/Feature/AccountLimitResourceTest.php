<?php

namespace Finxp\Flexcube\Tests\Feature;

use Illuminate\Http\Response;
use Finxp\Flexcube\Tests\TestCase;
use Finxp\Flexcube\Models\AccountLimit;
use Finxp\Flexcube\Tests\Mocks\Models\AccountLimitSetting;
use Finxp\Flexcube\Tests\Mocks\Models\RetailAccount as Account;
use Finxp\Flexcube\Tests\Mocks\Models\User;

class AccountLimitResourceTest extends TestCase
{
    /** @test */
    public function itShouldGetUserAccountLimits()
    {
        $this->withoutExceptionHandling();

        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $account = Account::factory()->create([
            'user_id' => $user->id
        ]);

        AccountLimit::factory(2)->create([
            'account_id' => $account->id
        ]);

        $this->getJson(route('customer.limits.list', ['id' => $account->id]))
            ->assertStatus(Response::HTTP_OK);
    }

    public function itShouldNotgetOtherUserAccountLimits()
    {
        $this->withoutExceptionHandling();

        parent::setAuthApi();
        $userOne = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $accountOne = Account::factory()->create([
            'user_id' => $userOne->id
        ]);

        AccountLimit::factory()->create([
            'account_id' => $accountOne->id
        ]);

        parent::setAuthApi();
        $userTwo = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $accountTwo = Account::factory()->create([
            'user_id' => $userTwo->id
        ]);

        AccountLimit::factory()->create([
            'account_id' => $accountTwo->id
        ]);

        $this->getJson(route('customer.limits.list', ['id' => $account->id]))
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function itShouldUpdateAccountLimit()
    {
        $this->withoutExceptionHandling();

        $user = $this->user();
        $this->actingAs($user);

        $account = Account::factory()->create([
            'user_id' => $user->id
        ]);

        AccountLimitSetting::factory()->create([
            'name' => 'daily',
            'limit' => 20
        ]);
        
        $accountLimit = AccountLimit::factory()->create([
            'type' => AccountLimit::TYPE_DAILY,
            'account_id' => $account->id
        ]);

        $payload = [
            'limit' => 10,
            'type' => AccountLimit::TYPE_DAILY
        ];

        $res = $this->putJson(route('customer.limits.update', ['id' => $account->id]), $payload)
            ->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('account_limits', [
            'limit'      => 10,
            'account_id' => $account->id
        ]);
    }

    /** @test */
    public function itShouldNotAllowToUpdateAccountLimitNotOwned()
    {
        $this->withoutExceptionHandling();

        $user = $this->user();
        $this->actingAs($user);

        $account = Account::factory()->create([
            'user_id' => $user->id
        ]);

        $account2 = Account::factory()->create([]);

        AccountLimitSetting::factory()->create([
            'name' => 'daily',
            'limit' => 20
        ]);
        
        AccountLimit::factory()->create([
            'type' => AccountLimit::TYPE_DAILY,
            'account_id' => $account->id
        ]);
        
        $accountLimit2 = AccountLimit::factory()->create([
            'type' => AccountLimit::TYPE_DAILY,
            'account_id' => $account2->id
        ]);

        $payload = [
            'limit' => 10,
            'type' => AccountLimit::TYPE_DAILY,
        ];

        $this->putJson(route('customer.limits.update', ['id' => $account2->id]), $payload)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function itShouldCreateTicketExceedLimit()
    {
        $this->withoutExceptionHandling();

        $this->mockTicketCreated();

        $user = $this->user();
        $this->actingAs($user);

        $account = Account::factory()->create([
            'user_id' => $user->id
        ]);

        AccountLimitSetting::factory()->create([
            'name' => 'daily',
            'limit' => 20
        ]);
        
        $accountLimit = AccountLimit::factory()->create([
            'type' => AccountLimit::TYPE_DAILY,
            'account_id' => $account->id
        ]);

        $payload = [
            'type' => AccountLimit::TYPE_DAILY,
            'limit' => 300
        ];

        $res = $this->putJson(route('customer.limits.update', ['id' => $account->id]), $payload)
            ->assertStatus(Response::HTTP_OK);
        
        $data = json_decode($res->getContent(), true);

        $this->assertEquals("Your account limit request is for processing.", $data['message']);
    }

    /** @test */
    public function itShouldUpdateAccountLimitMultiple()
    {
        $this->withoutExceptionHandling();

        $user = $this->user();
        $this->actingAs($user);

        $account = Account::factory()->create([
            'user_id' => $user->id
        ]);

        AccountLimitSetting::factory()->create([
            'name' => 'transaction',
            'limit' => 10
        ]);

        AccountLimitSetting::factory()->create([
            'name' => 'daily',
            'limit' => 50
        ]);

        AccountLimitSetting::factory()->create([
            'name' => 'monthly',
            'limit' => 100
        ]);
        
        AccountLimit::factory()->create([
            'type' => AccountLimit::TYPE_DAILY,
            'account_id' => $account->id
        ]);
        
        AccountLimit::factory()->create([
            'type' => AccountLimit::TYPE_TRANSACTION,
            'account_id' => $account->id
        ]);
        
        AccountLimit::factory()->create([
            'type' => AccountLimit::TYPE_MONTHLY,
            'account_id' => $account->id
        ]);

        $payload = [
            [
                'limit' => 2,
                'type' => AccountLimit::TYPE_DAILY
            ],
            [
                'limit' => 1,
                'type' => AccountLimit::TYPE_TRANSACTION
            ],
            [
                'limit' => 10,
                'type' => AccountLimit::TYPE_MONTHLY
            ],
        ];

        $res = $this->putJson(route('customer.limits.update', ['id' => $account->id]), $payload)
            ->assertStatus(Response::HTTP_OK);
    }
    

    /** @test */
    public function itShouldCreateTicketExceedLimitMultiple()
    {
        $this->withoutExceptionHandling();

        $this->mockTicketCreated();

        $user = $this->user();
        $this->actingAs($user);

        $account = Account::factory()->create([
            'user_id' => $user->id
        ]);

        AccountLimitSetting::factory()->create([
            'name' => 'transaction',
            'limit' => 10
        ]);

        AccountLimitSetting::factory()->create([
            'name' => 'daily',
            'limit' => 50
        ]);

        AccountLimitSetting::factory()->create([
            'name' => 'monthly',
            'limit' => 100
        ]);
        
        AccountLimit::factory()->create([
            'type' => AccountLimit::TYPE_DAILY,
            'account_id' => $account->id
        ]);
        
        AccountLimit::factory()->create([
            'type' => AccountLimit::TYPE_TRANSACTION,
            'account_id' => $account->id
        ]);
        
        AccountLimit::factory()->create([
            'type' => AccountLimit::TYPE_MONTHLY,
            'account_id' => $account->id
        ]);

        $payload = [
            [
                'limit' => 20,
                'type' => AccountLimit::TYPE_TRANSACTION
            ],
            [
                'limit' => 60,
                'type' => AccountLimit::TYPE_DAILY
            ],
            [
                'limit' => 200,
                'type' => AccountLimit::TYPE_MONTHLY
            ],
        ];

        $res = $this->putJson(route('customer.limits.update', ['id' => $account->id]), $payload)
            ->assertStatus(Response::HTTP_OK);
        
        $data = json_decode($res->getContent(), true);

        $this->assertEquals("Your account limit request for transaction, daily, monthly is processing.", $data['message']);
    }
}