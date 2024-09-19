<?php

namespace Finxp\Flexcube\Tests\Feature;

use Finxp\Flexcube\Models\TransactionPaymentUrl;
use Finxp\Flexcube\Models\AccountLimit;
use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Finxp\Flexcube\Tests\Mocks\Models\Service;
use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;
use Finxp\Flexcube\Tests\Mocks\Models\MerchantAccount;
use Finxp\Flexcube\Tests\Mocks\Models\User;
use Finxp\Flexcube\Tests\Mocks\Models\Transaction;
use Finxp\Flexcube\Tests\Mocks\Models\RetailAccount;
use Finxp\Flexcube\Tests\Mocks\Models\Token;

class InternalTransferControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function itShouldTransferMerchantAmount()
    {
        $this->withoutExceptionHandling();

        $this->setMerchantProvider();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $merchant_id = Merchant::factory()->create()->id;

        $account1 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);
        
        $account2 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT42CMUF18226151525333875158862'
        ]);

        AccountLimit::factory()->create([
            'type' => 'daily',
            'limit' => 2,
            'account_id' => $account1->id
        ]);

        AccountLimit::factory()->create([
            'type' => 'transaction',
            'limit' => 1,
            'account_id' => $account1->id
        ]);

        AccountLimit::factory()->create([
            'type' => 'monthly',
            'limit' => 3,
            'account_id' => $account1->id
        ]);

        $transaction = Transaction::factory()->create([
            'merchant_id' => $merchant_id,
            'service_id' => null,
            'initiating_party_id' => $user->id
        ]);

        $code = Token::factory()->create(['identifier' => $transaction->uuid])->code;

        $payload = array(
            'identifier' => $transaction->uuid,
            'code' => $code,
            'debtor_iban' => $account1->iban,
            'amount' => 1,
            'creditor_iban' => $account2->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR'
        );

        $this->mockInternalTransfer();

        $response = $this->postJson(route('internalTransfer.process.transfer', ['uuid' => $transaction->uuid]), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'code', 'message', 'status'
            ]);
            
        $content = $response->decodeResponseJson();
        $this->assertArrayHasKey('transaction_uuid', $content['data']);
    }
    
    /** @test */
    public function itShouldTransferRetailAmount()
    {
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $service = Service::factory()->create(['name' => 'CoreRetail']);

        $account1 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);
        
        $account2 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT42CMUF18226151525333875158862'
        ]);

        $transaction = Transaction::factory()->create([
            'initiating_party_id' => $user->id,
            'service_id' => $service->id
        ]);

        $code = Token::factory()->create(['identifier' => $transaction->uuid])->code;

        $payload = array(
            'identifier' => $transaction->uuid,
            'code' => $code,
            'debtor_iban' => $account1->iban,
            'amount' => 1,
            'creditor_iban' => $account2->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR',
            'account' => $account1->account_number
        );
        
        $this->mockInternalTransfer();

        $response = $this->postJson(route('internalTransfer.process.transfer', ['uuid' => $transaction->uuid]), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'code', 'message', 'status'
            ]);
            
        $content = $response->decodeResponseJson();
        $this->assertArrayHasKey('transaction_uuid', $content['data']);
    }

    /** @test */
    public function itShouldFailMerchantIfResponseFailure()
    {
        $this->withoutExceptionHandling();

        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $merchant_id = Merchant::factory()->create()->id;
        
        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);
        
        $account2 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT42CMUF18226151525333875158862'
        ]);

        $transaction = Transaction::factory()->create(['merchant_id' => $merchant_id, 'service_id' => null]);

        $code = Token::factory()->create(['identifier' => $transaction->uuid])->code;

        $payload = array(
            'identifier' => $transaction->uuid,
            'code' => $code,
            'debtor_iban' => $account->iban,
            'amount' => 1,
            'creditor_iban' => $account2->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR'
        );

        $this->setMerchantProvider();
        $this->mockFailedInternalTransfer();

        $response = $this->postJson(route('internalTransfer.process.transfer', ['uuid' => $transaction->uuid]), $payload)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'code', 'message', 'status'
            ]);
            
            
        $content = $response->decodeResponseJson();

    }
    
    /** @test */
    public function itShouldFailRetailIfResponseFailure()
    {
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $service = Service::factory()->create(['name' => 'CoreRetail']);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);

        $account2 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT42CMUF18226151525333875158862'
        ]);

        $transaction = Transaction::factory()->create([
            'initiating_party_id' => $user->id, 
            'service_id' => $service->id
        ]);

        $code = Token::factory()->create(['identifier' => $transaction->uuid])->code;

        $payload = array(
            'identifier' => $transaction->uuid,
            'code' => $code,
            'debtor_iban' => $account->iban,
            'amount' => 1,
            'creditor_iban' => $account2->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR',
            'account' => $account->account_number
        );

        $this->mockFailedInternalTransfer();

        $this->postJson(route('internalTransfer.process.transfer', ['uuid' => $transaction->uuid]), $payload)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'code', 'message', 'status'
            ]);
    }

    /** @test */
    public function itShouldInitiateTransferAmount()
    {
        $this->setMerchantProvider();

        $merchant_id = Merchant::factory()->create()->id;

        $account = RetailAccount::factory()->create([
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $payload = array(
            'debtor_iban' => $account->iban,
            'amount' => 1,
            'creditor_iban' => 'MT74GHAY96474221414996141885437',
            'remarks' => 'Test from web service',
            'currency' => 'EUR',
            'initiating_party' => [
                'id' => $merchant_id
            ],
        );

        $response = $this->withHeaders($headers)
            ->postJson(route('internalTransfer.initiate.transfer'), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'transaction_id'
            ]);
    }

    /** @test */
    public function itShouldCancelTransferAmount()
    {
        $this->setMerchantProvider();

        $merchant_id = Merchant::factory()->create()->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $transaction = Transaction::factory()->create(['merchant_id' => $merchant_id]);

        $this->withHeaders($headers)
        ->putJson(route('internalTransfer.cancel.transfer', ['uuid' => $transaction->uuid]))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'code', 'message', 'status'
            ]);
    }

    /** @test */
    public function itShouldInitiateIndividualTransferAmount()
    {
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639',
            'account_number' => '00010012345001'
        ]);
        
        $account2 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT42CMUF18226151525333875158862'
        ]);

        AccountLimit::factory()->create([
            'type' => 'daily',
            'limit' => 2,
            'account_id' => $account->id
        ]);

        AccountLimit::factory()->create([
            'type' => 'transaction',
            'limit' => 1,
            'account_id' => $account->id
        ]);

        AccountLimit::factory()->create([
            'type' => 'monthly',
            'limit' => 3,
            'account_id' => $account->id
        ]);

        $payload = array(
            'debtor_iban' => $account->iban,
            'account' => '0001123',
            'amount' => 1,
            'creditor_iban' => $account2->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR',
            'initiating_party' => [
                'id' => $user->id
            ],
        );

        $this->mockAccountsBicAndBalanceWithLimit();

        $this->postJson(route('transfer.individual.initiate'), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'transaction_id'
            ]);
    }
    
    /** @test */
    public function itShouldErrorInitiateIndividualTransferAmount()
    {

        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639',
        ]);
        
        $account2 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT42CMUF18226151525333875158862'
        ]);

        $payload = array(
            'debtor_iban' => $account->iban,
            'amount' => 1,
            'creditor_iban' => $account->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR',
            'initiating_party' => [
                'id' => $user->id
            ],
        );

        $this->postJson(route('transfer.individual.initiate'), $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'message', 'errors'
            ]);
    }
    
    /** @test */
    public function itShouldInitiateIndividualTransferAmountNoAccountWhenOtherUser()
    {

        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        
        $user2 = User::create(['email' => 'test2@gmail.com', 'password' => 'secret']);

        $this->actingAs($user2);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);
        
        $account2 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT42CMUF18226151525333875158862'
        ]);

        $payload = array(
            'debtor_iban' => $account->iban,
            'account' => '0001123',
            'amount' => 1,
            'creditor_iban' => $account2->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR',
            'initiating_party' => [
                'id' => $user->id
            ],
        );

        $this->mockBicValue();

        $this->postJson(route('transfer.individual.initiate'), $payload)
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);
    }
    
    /** @test */
    public function itShouldInitiateIndividualTransferAmountNoAccount()
    {

        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        

        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);

        $payload = array(
            'debtor_iban' => 'MT42CMUF18226151525333875158862',
            'account' => '0001123',
            'amount' => 1,
            'creditor_iban' => $account->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR',
            'initiating_party' => [
                'id' => $user->id
            ],
        );

        $this->postJson(route('transfer.individual.initiate'), $payload)
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);
    }
    
    /** @test */
    public function itShouldInitiateIndividualTransferAmountInsufficient()
    {
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        
        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639',
            'account_number' => '00010012345001'
        ]);
        
        $account2 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT42CMUF18226151525333875158862'
        ]);

        $payload = array(
            'debtor_iban' => $account->iban,
            'account' => '0001123',
            'amount' => 10,
            'creditor_iban' => $account2->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR',
            'initiating_party' => [
                'id' => $user->id
            ],
        );

        $this->mockAccountsBicAndBalance();

        $this->postJson(route('transfer.individual.initiate'), $payload)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);
    }
    
    /** @test */
    public function itShouldInitiateIndividualTransferAmountNoLimitType()
    {
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        
        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639',
            'account_number' => '00010012345001'
        ]);
        
        $account2 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT42CMUF18226151525333875158862'
        ]);

        $account->limits()->delete();

        $payload = array(
            'debtor_iban' => $account->iban,
            'account' => '0001123',
            'amount' => 0.1,
            'creditor_iban' => $account2->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR',
            'initiating_party' => [
                'id' => $user->id
            ],
        );

        $this->mockAccountsBicAndBalance();

        $res = $this->postJson(route('transfer.individual.initiate'), $payload)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);
            
        $content = json_decode($res->getContent(), true);

        $this->assertEquals('Account Limit Type Not Found: transaction, daily, monthly', $content['message']);


    }
    
    /** @test */
    public function itShouldRequestNewAccountPIQ()
    {
        $this->setMerchantProvider();

        $merchant_id = Merchant::factory()->create()->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $payload = [
            'type' => 'PAYOUT_NEWIBAN4U',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'paymentiq'
        ];

        $response = $this->withHeaders($headers)
            ->postJson(route('provider.process.new'), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'code', 'message', 'status'
            ]);
            
        $content = $response->decodeResponseJson();
        $this->assertArrayHasKey('redirect_url', $content['data']);

    }
    
    /** @test */
    public function itShouldTransferIndividualAmount()
    {
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $service = Service::factory()->create(['name' => 'CoreRetail']);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);
        
        $account2 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT42CMUF18226151525333875158862'
        ]);

        $transaction = Transaction::factory()->create([
            'initiating_party_id' => $user->id, 
            'service_id' => $service->id
        ]);

        $code = Token::factory()->create(['identifier' => $transaction->uuid])->code;

        $payload = array(
            'identifier' => $transaction->uuid,
            'code' => $code,
            'debtor_iban' => $account->iban,
            'amount' => 1,
            'creditor_iban' => $account2->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR',
            'account' => $account->account_number
        );
        
        $this->mockInternalTransfer();

        $response = $this->postJson(route('transfer.individual.process', ['uuid' => $transaction->uuid]), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'code', 'message', 'status'
            ]);
            
        $content = $response->decodeResponseJson();
        $this->assertArrayHasKey('transaction_uuid', $content['data']);
    }
    
    
    /** @test */
    public function itShouldFailIndividualProcessIfResponseFailure()
    {
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $service = Service::factory()->create(['name' => 'CoreRetail']);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);
        
        $account2 = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT42CMUF18226151525333875158862'
        ]);

        $transaction = Transaction::factory()->create([
            'initiating_party_id' => $user->id, 
            'service_id' => $service->id
        ]);

        $code = Token::factory()->create(['identifier' => $transaction->uuid])->code;

        $payload = array(
            'identifier' => $transaction->uuid,
            'code' => $code,
            'debtor_iban' => $account->iban,
            'amount' => 1,
            'creditor_iban' => $account2->iban,
            'remarks' => 'Test from web service',
            'currency' => 'EUR',
            'account' => $account->account_number
        );

        $this->mockFailedInternalTransfer();

        $this->postJson(route('transfer.individual.process', ['uuid' => $transaction->uuid]), $payload)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'code', 'message', 'status'
            ]);
    }

    /** @test */
    public function itShouldInitiateProviderTransferAmount()
    {    
        $user = User::factory()->create([
            'customer_account_number' => '123123123'
        ]);

        $account = RetailAccount::factory()->create([
            'account_number' => '00010012345001',
            'user_id' => $user->id,
            'iban' => 'MT89123456677788888885668'
        ]);

        $transactionPayment = TransactionPaymentUrl::factory()->create([
            'type' => 'PAYIN_MERCHANT',
            'sender_iban' => $account->iban,
            'reference_id' => 'test',
            'provider' => 'paymentiq'
        ]); 

        $payload = array(
            'type' => $transactionPayment->type,
            'account' => $account->account_number,
            'sender_name' => $transactionPayment->sender_name,
            'sender_iban' => $transactionPayment->sender_iban,
            'recipient_name' => $transactionPayment->recipient_name,
            'recipient_iban' => $transactionPayment->recipient_iban,
            'amount' => $transactionPayment->amount,
            'currency' => $transactionPayment->currency,
            'remarks' => 'test',
            'reference_id' => $transactionPayment->reference_id,
            'provider' => $transactionPayment->provider
        );

        $response = $this->postJson(route('provider.transfer.initiate'), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'transaction_id'
            ]);
            
        $content = $response->decodeResponseJson();
    }

    /** @test */
    public function itShouldShowProviderTransaction()
    {
        $this->withoutExceptionHandling();

        $merchant_id = Merchant::factory()->create()->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);
        
        $transaction = Transaction::factory()->create();

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $response = $this->withHeaders($headers)
            ->get(route('show.transaction', ['uuid' => $transaction->uuid]))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'code', 'data'
            ]);
    }

    /** @test */
    public function itShouldUnAuthDirectTransferAmount()
    {    
        $user = User::factory()->create([
            'customer_account_number' => '123123123'
        ]);

        $account = RetailAccount::factory()->create([
            'account_number' => '00010012345001',
            'user_id' => $user->id,
            'iban' => 'MT89123456677788888885668'
        ]);

        $payload = array(
            'type' => 'PAYOUT_SEPACT',
            'sender_name' => 'Test Sender',
            'sender_iban' => $account['iban'],
            'recipient_name' => 'Test Recipient',
            'recipient_iban' => 'NL90INGB7537184356',
            'amount' => 0.10,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'PaymentIQ'
        );

        $this->postJson(route('provider.process.transfer'), $payload)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJsonStructure([
                'status',
                'code',
                'message'
            ]);
    }

    /** @test */
    public function itShouldNotFoundAccountDirectTransferAmount()
    {    
        $user = User::factory()->create([
            'customer_account_number' => '123123123'
        ]);

        $account = RetailAccount::factory()->create([
            'account_number' => '00010012345001',
            'user_id' => $user->id,
            'iban' => 'MT89123456677788888885668'
        ]);

        $payload = array(
            'type' => 'PAYOUT_SEPACT',
            'sender_name' => 'Test Sender',
            'sender_iban' => $account['iban'],
            'recipient_name' => 'Test Recipient',
            'recipient_iban' => 'NL90INGB7537184356',
            'amount' => 0.10,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'PaymentIQ'
        );

        $merchant_id = Merchant::factory()->create()->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->withHeaders($headers)
            ->postJson(route('provider.process.transfer'), $payload)
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function itShouldFailedDirectTransferAmount()
    {    
        $merchant_id = Merchant::factory()->create([])->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $account = MerchantAccount::factory()->create([
            'merchant_id' => $merchant_id,
            'account_number' => '00010012345001',
            'iban_number' => 'MT89123456677788888885668'
        ]);

        $payload = array(
            'type' => 'PAYOUT_SEPACT',
            'sender_name' => 'Test Sender',
            'sender_iban' => $account['iban_number'],
            'recipient_name' => 'Test Recipient',
            'recipient_iban' => 'NL90INGB7537184356',
            'amount' => 0.10,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'PaymentIQ'
        );

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->withHeaders($headers)
            ->postJson(route('provider.process.transfer'), $payload)
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }
    

    /** @test */
    public function itShouldDirectTransferAmount()
    {    
        $this->mockDirectTransfer();

        $merchant_id = Merchant::factory()->create([])->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $account = MerchantAccount::factory()->create([
            'merchant_id' => $merchant_id,
            'account_number' => '00010012345001',
            'iban_number' => 'MT89123456677788888885668'
        ]);

        $payload = array(
            'type' => 'PAYOUT_SEPACT',
            'sender_name' => 'Test Sender',
            'sender_iban' => $account['iban_number'],
            'recipient_name' => 'Test Recipient',
            'recipient_iban' => 'NL90INGB7537184356',
            'amount' => 0.10,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'PaymentIQ'
        );

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $response = $this->withHeaders($headers)
            ->postJson(route('provider.process.transfer'), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message'
            ]);
            
        $content = $response->decodeResponseJson();
        
        $this->assertEquals($content['code'], Response::HTTP_OK);
        $this->assertEquals($content['message'], 'Transaction is submitted for processing.');
    }

    /** @test */
    public function itShouldInitiateIndividualDirectTransfer()
    {
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639',
            'account_number' => '00010012345001'
        ]);

        AccountLimit::factory()->create([
            'type' => 'daily',
            'limit' => 2,
            'account_id' => $account->id
        ]);

        AccountLimit::factory()->create([
            'type' => 'transaction',
            'limit' => 1,
            'account_id' => $account->id
        ]);

        AccountLimit::factory()->create([
            'type' => 'monthly',
            'limit' => 3,
            'account_id' => $account->id
        ]);
        
        $iban = 'NL15ABNA3659362247';

        $payload = array(
            'debtor_iban' => $account->iban,
            'creditor_iban' => $iban,
            'creditor_name' => 'Test',
            'amount' => 1,
            'remarks' => 'Test from web service',
            'currency' => 'EUR'
        );

        $this->mockAccountsBicAndBalanceWithLimit(true);

        $this->postJson(route('transfer.individual.initiate'), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'transaction_id'
            ]);
    }

    /** @test */
    public function itShouldUnprocessEntityInitiateIndividualDirectTransfer()
    {
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639',
            'account_number' => '00010012345001'
        ]);

        AccountLimit::factory()->create([
            'type' => 'daily',
            'limit' => 2,
            'account_id' => $account->id
        ]);

        AccountLimit::factory()->create([
            'type' => 'transaction',
            'limit' => 1,
            'account_id' => $account->id
        ]);

        AccountLimit::factory()->create([
            'type' => 'monthly',
            'limit' => 3,
            'account_id' => $account->id
        ]);
        
        $iban = 'NL15ABNA3659362247';

        $payload = array(
            'debtor_iban' => $account->iban,
            'creditor_iban' => $iban,
            'amount' => 1,
            'remarks' => 'Test from web service',
            'currency' => 'EUR'
        );

        $this->mockAccountsBicAndBalanceWithLimit(true);

        $this->postJson(route('transfer.individual.initiate'), $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function itShouldDirectTransferIndividualAmount()
    {
        $this->mockDirectTransfer();
        
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $this->actingAs($user);

        $service = Service::factory()->create(['name' => 'CoreRetail']);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);
        
        $iban = 'NL15ABNA3659362247';

        $transaction = Transaction::factory()->create([
            'initiating_party_id' => $user->id, 
            'service_id' => $service->id
        ]);

        $code = Token::factory()->create(['identifier' => $transaction->uuid])->code;

        $payload = array(
            'identifier' => $transaction->uuid,
            'creditor_iban' => $iban,
            'debtor_iban' => $account->iban,
            'currency' => 'EUR',
            'remarks' => 'Test from web service',
            'amount' => 1,
            'code' => $code,
            'type' => 'SEPACT'
        );
        
        $this->mockInternalTransfer();

        $response = $this->postJson(route('transfer.individual.process', ['uuid' => $transaction->uuid]), $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'code', 'message', 'status'
            ]);
            
        $content = $response->decodeResponseJson();
        $this->assertArrayHasKey('transaction_uuid', $content['data']);
    }

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
            'iban_number' => 'MT89123456677788888885668'
        ]);

        $payload = array(
            'account' => $account['account_number'],
            'sender_name' => 'Test Sender',
            'sender_iban' => $account['iban_number'],
            'recipient_name' => 'Test Recipient',
            'recipient_iban' => 'NL90INGB7537184356',
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
            'iban_number' => 'MT89123456677788888885668'
        ]);

        $account2 = MerchantAccount::factory()->create([
            'merchant_id' => $merchant2_id,
            'account_number' => '00010012345002',
            'iban_number' => 'MT89123456677788888885669'
        ]);

        $payload = array(
            'account' => $account->account_number,
            'sender_name' => 'Test Sender',
            'sender_iban' => $account2['iban_number'],
            'recipient_name' => 'Test Recipient',
            'recipient_iban' => 'NL90INGB7537184356',
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
            'iban_number' => 'MT89123456677788888885668'
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
            'recipient_iban' => 'NL90INGB7537184356',
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
            'iban_number' => 'MT89123456677788888885668'
        ]);

        $payload = array(
            'account' => $account['account_number'],
            'sender_name' => 'Test Sender',
            'sender_iban' => $account['iban_number'],
            'recipient_name' => 'Test Recipient',
            'recipient_iban' => 'NL90INGB7537184356',
            'amount' => 0.10,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'iban4u'
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
