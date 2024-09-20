<?php

namespace Finxp\Flexcube\Tests\Feature;

use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Http\Response;

use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;
use Finxp\Flexcube\Tests\Mocks\Models\MerchantAccount;

class AccountStatementControllerTest extends TestCase
{

    /** @test */
    public function itShouldGetMerchantAccountsBalance()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $payload = [
            'customer_ac_no' => $merchantAccount->account_number,
            'uuid' => $merchantAccount->uuid
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockAccountsAndBalance();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.accounts.balance', $payload))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'code', 'status'
            ]);

        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey( 'currency', $data['data'] );
    }
    
    /** @test */
    public function itShouldGetMerchantAccountsBalance401()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $payload = [
            'customer_ac_no' => $merchantAccount->account_number,
            'uuid' => $merchantAccount->uuid
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{SECRET}")
        ];

        $this->mockAccountsAndBalance();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.accounts.balance', $payload))
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJsonStructure([
                'message', 'code', 'status'
            ]);

        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey( 'message', $data );
    }
    
    /** @test */
    public function itShouldGetMerchantAccountsBalanceAccountNotFound()
    {
        $merchant = Merchant::factory()
            ->create();
        
        $merchant2 = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $merchantAccount2 = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant2->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885669',
                'account_number' => '00010012345002'
            ]);

        $payload = [
            'customer_ac_no' => $merchantAccount2->account_number,
            'uuid' => $merchantAccount2->uuid
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockAccounts2AndBalance();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.accounts.balance', $payload))
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'message', 'code', 'status'
            ]);

        $data = json_decode($res->getContent(), true);
        $this->assertEquals( $data['message'], 'Account not found!' );
    }

    /** @test */
    public function itShouldGetMerchantAccountsBalanceInexistent()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $payload = [
            'customer_ac_no' => $merchantAccount->account_number,
            'uuid' => 'aaa'
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockAccountsAndBalance();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.accounts.balance', $payload))
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }
    
    /** @test */
    public function itShouldGetMerchantAccountsHistory()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $payload = [
            'uuid' => $merchantAccount->uuid
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockAccountsAndHistory();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.account.history', $payload))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data'
            ]);

        $data = json_decode($res->getContent(), true);
    
        $this->assertArrayHasKey( 'currency', $data['data'][0] );
    }
    
    /** @test */
    public function itShouldGetMerchantAccountsHistory404()
    {
        $merchant = Merchant::factory()
            ->create();

        $merchant2 = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $merchantAccount2 = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant2->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885670',
                'account_number' => '00010012345003'
            ]);

        $payload = [
            'uuid' => $merchantAccount2->uuid
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockAccountsAndHistory();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.account.history', $payload))
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);

        $data = json_decode($res->getContent(), true);
        
        $this->assertEquals( $data['message'], 'Account not found!' );
    }
    
    /** @test */
    public function itShouldGetSingleTransaction()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010000278001'
            ]);

        $payload = [
            'transaction_ref_no' => '2027601152380000',
            'uuid' => $merchantAccount->uuid
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockAccountSingleTransactionDetails();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.account.specific', $payload))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data'
            ]);

        $data = json_decode($res->getContent(), true);
    
        $this->assertArrayHasKey('transaction_status', $data['data']);
    }

    /** @test */
    public function itShouldGetSingleTransactionReturn404()
    {
        $merchant = Merchant::factory()
            ->create();

        $merchant2 = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010000278001'
            ]);

        $merchantAccount2 = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant2->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885699',
                'account_number' => '00010000278221'
            ]);

        $payload = [
            'transaction_ref_no' => '2027601152380000',
            'uuid' => $merchantAccount2->uuid
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockAccountSingleTransactionDetails();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.account.specific', $payload))
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'message'
            ]);

        $data = json_decode($res->getContent(), true);
        
        $this->assertEquals( $data['message'], 'Account not found!' );
    }
    
    /** @test */
    public function itShouldGetMerchantAccountsStatement404()
    {
        $merchant = Merchant::factory()
            ->create();

        $merchant2 = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $merchantAccount2 = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant2->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885670',
                'account_number' => '00010012345003'
            ]);

        $payload = [
            'uuid' => $merchantAccount2->uuid,
            'from_date' => '2023-10-11',
            'to_date' => '2023-10-30'
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockAccountsAndStatement();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.account.statement', $payload))
            ->assertStatus(Response::HTTP_NOT_FOUND);

        $data = json_decode($res->getContent(), true);
        
        $this->assertEquals( $data['message'], 'Account not found!' );
    }
    
    /** @test */
    public function itShouldGetMerchantAccountsStatement422Dates()
    {
        $merchant = Merchant::factory()
            ->create();

        $merchant2 = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $merchantAccount2 = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant2->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885670',
                'account_number' => '00010012345003'
            ]);

        $payload = [
            'uuid' => $merchantAccount2->uuid
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockAccountsAndStatement();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.account.statement', $payload))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($res->getContent(), true);
        
        $this->assertEquals( $data['errors']['from_date'][0], 'The from date field is required.' );
        
        $this->assertEquals( $data['errors']['to_date'][0], 'The to date field is required.' );
    }
    
    /** @test */
    public function itShouldGetMerchantAccountsStatement422ToDate()
    {
        $merchant = Merchant::factory()
            ->create();

        $merchant2 = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $merchantAccount2 = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant2->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885670',
                'account_number' => '00010012345003'
            ]);

        $payload = [
            'uuid' => $merchantAccount2->uuid,
            'from_date' => '2023-10-11'
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockAccountsAndStatement();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.account.statement', $payload))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($res->getContent(), true);
        
        $this->assertEquals( $data['errors']['to_date'][0], 'The to date field is required.' );
    }
}
