<?php

namespace Finxp\Flexcube\Tests\Feature;

use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;
use Finxp\Flexcube\Tests\Mocks\Models\MerchantAccount;

class InternalTransferControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function itShouldMerchantTransferSuccess()
    { 
        $merchant_id = Merchant::factory()->create([])->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $account = MerchantAccount::factory()->create([
            'merchant_id' => $merchant_id,
            'account_number' => '00010012345001',
            'iban_number' => 'MT43PAUU92005050500020012285001'
        ]);

        $payload = array(
            'account' => $account['account_number'],
            'amount' => 0.10,
            'currency' => 'EUR',
            'sender_name' => 'Test Sender',
            'sender_iban' => $account['iban_number'],
            'recipient_name' => 'Test Recipient',
            'recipient_iban' => 'MT33PAUU9200505050001TRANSIT007',
            'reference_id' => 'test',
            'remarks' => 'test'
        );

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockBicAndBalance();

        $this->withHeaders($headers)
            ->postJson(route('merchant.process.transfer'), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'status',
                'code',
                'message'
            ]);
    }

    /** @test */
    public function itShouldMerchantTransferNotOwnedIBAN()
    { 
        $merchant_id = Merchant::factory()->create([])->id;
        $merchant2_id = Merchant::factory()->create([])->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $account = MerchantAccount::factory()->create([
            'merchant_id' => $merchant_id,
            'account_number' => '00010012345001',
            'iban_number' => 'MT43PAUU92005050500020012285001'
        ]);

        $account2 = MerchantAccount::factory()->create([
            'merchant_id' => $merchant2_id,
            'account_number' => '00010012345002',
            'iban_number' => 'MT98PAUU920050505PINTERIM395009'
        ]);

        $payload = array(
            'account' => $account['account_number'],
            'amount' => 0.10,
            'currency' => 'EUR',
            'sender_name' => 'Test Sender',
            'sender_iban' => $account2['iban_number'],
            'recipient_name' => 'Test Recipient',
            'recipient_iban' => 'MT33PAUU9200505050001TRANSIT007',
            'reference_id' => 'test',
            'remarks' => 'test'
        );

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockBicAndBalance();

        $this->withHeaders($headers)
            ->postJson(route('merchant.process.transfer'), $payload)
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'status',
                'code',
                'message'
            ]);
    }

    /** @test */
    public function itShouldMerchantTransferNotOwnedAccount()
    { 
        $merchant_id = Merchant::factory()->create([])->id;
        $merchant2_id = Merchant::factory()->create([])->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $account = MerchantAccount::factory()->create([
            'merchant_id' => $merchant_id,
            'account_number' => '00010012345001',
            'iban_number' => 'MT43PAUU92005050500020012285001'
        ]);

        $account2 = MerchantAccount::factory()->create([
            'merchant_id' => $merchant2_id,
            'account_number' => '00010012345002',
            'iban_number' => 'MT89123456677788888885669'
        ]);

        $payload = array(
            'account' => $account2->account_number,
            'sender_name' => 'Test Sender',
            'sender_iban' => $account['iban_number'],
            'recipient_name' => 'Test Recipient',
            'recipient_iban' => 'MT33PAUU9200505050001TRANSIT007',
            'amount' => 0.10,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'iban4u'
        );

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockBicAndBalance();

        $this->withHeaders($headers)
            ->postJson(route('merchant.process.transfer'), $payload)
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'status',
                'code',
                'message'
            ]);
    }
    
    /** @test */
    public function itShouldMerchantTransferSuccessExternal()
    { 
        $merchant_id = Merchant::factory()->create([])->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $account = MerchantAccount::factory()->create([
            'merchant_id' => $merchant_id,
            'account_number' => '00010012345001',
            'iban_number' => 'MT43PAUU92005050500020012285001'
        ]);

        $payload = array(
            'account' => $account['account_number'],
            'amount' => 0.10,
            'currency' => 'EUR',
            'sender_name' => 'Test Sender',
            'sender_iban' => $account['iban_number'],
            'recipient_name' => 'Test Recipient',
            'recipient_iban' => 'NL91ABNA0417164300',
            'reference_id' => 'test',
            'remarks' => 'test'
        );

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockBicAndBalance(true);

        $this->withHeaders($headers)
            ->postJson(route('merchant.process.transfer'), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'status',
                'code',
                'message'
            ]);
    }
}
