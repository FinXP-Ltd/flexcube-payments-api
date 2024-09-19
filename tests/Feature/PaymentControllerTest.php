<?php

namespace Tests\Feature;

use Finxp\Flexcube\Models\TransactionPaymentUrl;
use Finxp\Flexcube\Tests\Mocks\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Finxp\Flexcube\Tests\TestCase;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;
use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Tests\Mocks\Models\MerchantAccount;
use Finxp\Flexcube\Tests\Mocks\Models\RetailAccount;

class PaymentControllerTest extends TestCase
{
    /** @test */
    public function testItShouldCheckSenderAccount()
    {

        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);

        MerchantAccount::factory()->create([
            'merchant_id' => $merchant->id,
            'iban_number' => 'MT42CMUF18226151525333875158862'
        ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $payload = [
            'type' => 'PAYIN_MERCHANT',
            'sender_name' => 'Test Sender',
            'sender_iban' => 'MT09VGIU15313216436188582861735',
            'recipient_name' => 'Test Receiver',
            'recipient_iban' => 'MT42CMUF18226151525333875158862',
            'amount' => 1.4,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'paymentiq',
            'provider_redirect_url' => 'https://test.com'
        ];

        $response = $this->withHeaders($headers)
            ->postJson(route('payments.store', $payload))
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);
    }

    /** @test */
    public function testItShouldCheckRecipientAccount()
    {

        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);

        RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $payload = [
            'type' => 'PAYIN_MERCHANT',
            'sender_name' => 'Test Sender',
            'sender_iban' => 'MT29IZLN78218334749552679151639',
            'recipient_name' => 'Test Receiver',
            'recipient_iban' => 'MT09VGIU15313216436188582861735',
            'amount' => 1.4,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'paymentiq',
            'provider_redirect_url' => 'https://test.com'
        ];

        $response = $this->withHeaders($headers)
            ->postJson(route('payments.store', $payload))
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);
    }
    
    /** @test */
    public function testItShouldFailCheckSenderAndRecipientAccountSepaCt()
    {

        $merchant = Merchant::factory()
            ->create();

        $merchant2 = Merchant::factory()
            ->create();

        $merchantAccount = MerchantAccount::factory()->create([
                'merchant_id' => $merchant->id
            ]);

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant2->id
            ]);

        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $payload = [
            'type' => 'PAYOUT_SEPACT',
            'sender_name' => 'Test Sender',
            'sender_iban' => $merchantAccount->iban_number,
            'recipient_name' => 'Test Receiver',
            'recipient_iban' => 'MT09VGIU15313216436188582861735',
            'amount' => 1.4,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'paymentiq',
            'provider_redirect_url' => 'https://test.com'
        ];

        $response = $this->withHeaders($headers)
            ->postJson(route('payments.store', $payload))
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);
    }
    
    /** @test */
    public function testItShouldFailCheckSenderAndRecipientAccount()
    {

        $merchant = Merchant::factory()
            ->create();

        $merchant2 = Merchant::factory()
            ->create();

        $merchantAccount = MerchantAccount::factory()->create([
                'merchant_id' => $merchant->id
            ]);

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant2->id
            ]);

        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $payload = [
            'type' => 'PAYOUT_MERCHANT',
            'sender_name' => 'Test Sender',
            'sender_iban' => $merchantAccount->iban_number,
            'recipient_name' => 'Test Receiver',
            'recipient_iban' => 'MT09VGIU15313216436188582861735',
            'amount' => 1.4,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'paymentiq',
            'provider_redirect_url' => 'https://test.com'
        ];

        $response = $this->withHeaders($headers)
            ->postJson(route('payments.store', $payload))
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);
    }

    /** @test */
    public function testItShouldCheckSenderAndRecipientAccount()
    {

        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $payload = [
            'type' => 'PAYIN_MERCHANT',
            'sender_name' => 'Test Sender',
            'sender_iban' => 'MT29IZLN78218334749552679151639',
            'recipient_name' => 'Test Receiver',
            'recipient_iban' => 'MT09VGIU15313216436188582861735',
            'amount' => 1.4,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'paymentiq',
            'provider_redirect_url' => 'https://test.com'
        ];

        $response = $this->withHeaders($headers)
            ->postJson(route('payments.store', $payload))
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);
    }

    /** @test */
    public function testItShouldStoreProviderTransactionPayment()
    {

        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'iban' => 'MT29IZLN78218334749552679151639'
        ]);

        $merchantAccount = MerchantAccount::factory()->create([
            'merchant_id' => $merchant->id,
            'iban_number' => 'MT42CMUF18226151525333875158862'
        ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $payload = [
            'type' => 'PAYIN_MERCHANT',
            'sender_name' => 'Test Sender',
            'sender_iban' => $account->iban,
            'recipient_name' => 'Test Receiver',
            'recipient_iban' => 'MT42CMUF18226151525333875158862',
            'amount' => 1.4,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'paymentiq',
            'provider_redirect_url' => 'https://test.com'
        ];

        $response = $this->withHeaders($headers)
            ->postJson(route('payments.store', $payload))
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'data', 'code', 'status', 'message'
            ]);
    }

    /** @test */
    public function testItShouldFailedStoreProviderTransactionPayment()
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
            'sender_name' => 'Test Sender',
            'sender_iban' => 'NL88ABNA3215513765',
            'recipient_name' => 'Test Receiver',
            'recipient_iban' => 'NL88ABNA3215513725',
            'amount' => 1.4,
            'currency' => 'EUR',
            'remarks' => 'test',
            'reference_id' => 'test',
            'provider' => 'paymentiq',
            'provider_redirect_url' => 'https://test.com'
        ];

        $response = $this->withHeaders($headers)
            ->postJson(route('payments.store', $payload))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function testItShouldGetGeneratedTransactionPayment()
    {

        $user = User::factory()->create([
            'customer_account_number' => '123123123'
        ]);

        $account = RetailAccount::factory()->create([
            'account_number' => '00010012345001',
            'user_id' => $user->id,
            'iban' => 'MT89123456677788888885668'
        ]);

        $transaction = TransactionPaymentUrl::factory()->create([
            'sender_iban' => $account->iban
        ]);

        $this->mockGetBalance();
        
        $response = $this->json(Request::METHOD_GET, route('payments.show', ['userTransaction' => $transaction]))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'code', 'data', 'message'
            ]);
    }

    /** @test */
    public function testItShouldFailedGetGeneratedTransactionPayment()
    {

        $user = User::factory()->create([
            'customer_account_number' => '123123123'
        ]);

        $account = RetailAccount::factory()->create([
            'account_number' => '00010012345001',
            'user_id' => $user->id,
            'iban' => 'MT89123456677788888885668'
        ]);

        $transaction = TransactionPaymentUrl::factory()->create([
            'sender_iban' => 'MT89123456677788888885628'
        ]);
        
        $response = $this->json(Request::METHOD_GET, route('payments.show', ['userTransaction' => $transaction->id]))
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function testItShouldCancelTransactionPayment()
    {

        $user = User::factory()->create([
            'customer_account_number' => '123123123'
        ]);

        $account = RetailAccount::factory()->create([
            'account_number' => '00010012345001',
            'user_id' => $user->id,
            'iban' => 'MT89123456677788888885668'
        ]);

        $transaction = TransactionPaymentUrl::factory()->create([
            'sender_iban' => $account->iban
        ]);
        
        $response = $this->json(Request::METHOD_PUT, route('payments.cancel', ['uuid' => $transaction->id]))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);
    }
}