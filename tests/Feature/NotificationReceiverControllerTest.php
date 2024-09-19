<?php

namespace Finxp\Flexcube\Tests\Feature;

use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

use Finxp\Flexcube\Events\TransactionReceived;
use Finxp\Flexcube\FlexcubePackageServiceProvider;
use Finxp\Flexcube\Tests\Mocks\Models\MerchantAccount;
use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Listeners\SendTransactionNotification;
use Finxp\Flexcube\Notifications\PaymentNotification;
use Finxp\Flexcube\Tests\Mocks\Models\Webhook;
use Finxp\Flexcube\Tests\Mocks\Jobs\WebhookCallJob;
use Illuminate\Support\Facades\Queue;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;
use Finxp\Flexcube\Tests\Mocks\Models\RetailUser;
use Finxp\Flexcube\Models\InboundTransaction;
use Finxp\Flexcube\Tests\Mocks\Models\FCTransactions;
use Finxp\Flexcube\Tests\Mocks\Models\InboundTransaction as ModelsInboundTransaction;
use Finxp\Flexcube\Tests\Mocks\Models\InboundTransactionDetails;
use Finxp\Flexcube\Tests\Mocks\Models\RetailAccount;
use Finxp\Flexcube\Tests\Mocks\Models\Transaction;
use Finxp\Flexcube\Tests\Mocks\Models\User;

class NotificationReceiverControllerTest extends TestCase
{
    /** @test */
    public function itShouldNotAcceptPayloadIfMissingHeaderKeys()
    {
        $payload = [
            'extRefId' => '123456'
        ];

        $headers = [
            'branchCode' => '000',
            'userId' => '',
            'ECID-Context' => ''
        ];

        $this->withHeaders($headers)
            ->postJson(route('notification.receiver'), $payload)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function itShouldCallPIQWebhookWhenATransactionIsReceived()
    {
        
        $this->withoutExceptionHandling();

        Event::fake();

        $merchant = Merchant::factory()->create();

        MerchantAccount::factory()->create([
            'merchant_id' => $merchant->id,
            'account_number' => '00010012345003',
            'iban_number' => 'MT89123456677788888885669',
            'is_notification_active' => 1
        ]);

        Transaction::factory()->create([
            'type' => 'PAYOUT_MERCHANT',
            'reference_no' => '2027601152380000',
        ]);

        $size = rand(1, 3);
        Webhook::factory()->count($size)->create([
            'merchant_id' => $merchant->id
        ]);

        $payload = [
            'extRefId' => '2027601152380000',
            'status' => 'S'
        ];

        $headers = [
            'branchCode' => '000',
            'appId' => 'SRVADAPTER',
            'userId' => '',
            'ECID-Context' => ''
        ];

        $this->mockSingleTransactionDetails();

        $this->withHeaders($headers)
            ->postJson(route('notification.receiver'), $payload)
            ->assertStatus(Response::HTTP_OK);
    }

    /** @test */
    public function itShouldEmitEventWhenATransactionIsReceived()
    {
        
        $this->withoutExceptionHandling();

        Event::fake();

        $user = RetailUser::factory()->create();

        $account = RetailAccount::factory()->create([
            'user_id'        => $user->id,
            'account_number' => '00010012345003',
            'is_active'      => 1
        ]);
        
        $transaction = Transaction::factory()->create([
            'reference_no' => '2027601152380000',
        ]);

        $payload = [
            'extRefId' => $transaction->reference_no,
            'status' => 'S'
        ];

        $headers = [
            'branchCode' => '000',
            'appId' => 'SRVADAPTER',
            'userId' => '',
            'ECID-Context' => ''
        ];

        $this->mockSingleTransactionDetails();

        $this->withHeaders($headers)
            ->postJson(route('notification.receiver'), $payload)
            ->assertStatus(Response::HTTP_OK);

        // Event::assertDispatched(TransactionReceived::class, function ($event) use ( $transaction ) {
        //     return $event->transactionDetails['transaction_ref_no'] === $transaction->reference_no;
        // });
    }

    /** @test */
    public function itShouldListenToDispatchedEvent()
    {
        
        $this->withoutExceptionHandling();

        Notification::fake();
        Queue::fake();
        Queue::assertNothingPushed();

        $merchant = Merchant::factory()->create();
        ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);
        $size = rand(1, 3);

        Webhook::factory()->count($size)->create([
            'merchant_id' => $merchant->id
        ]);

        $data['initiating_party'] = $merchant;

        Http::fake(
            [
                '*' => Http::Response(
                        [
                            'status' => 'success',
                            'code' => Response::HTTP_OK
                        ],
                        Response::HTTP_OK,
                        [
                            'Content-Type' => 'application/json'
                        ]
                    )
            ]
        );

        $dataArr = $this->mockSingleTransactionData();

        (new SendTransactionNotification())->handle(
            new TransactionReceived( $dataArr, $data )
        );

        Notification::assertSentTo(
            $data,
            PaymentNotification::class,
            function ($notification, $channel, $notifiable) use ($dataArr) {
                return true;
            }
        );

        Queue::assertPushed(WebhookCallJob::class);
    }

    public function itShouldStoreTransactionWhenATransactionIsReceived()
    {
        Event::fake();

        $merchant = Merchant::factory()->create();

        $account = MerchantAccount::factory()->create([
            'merchant_id' => $merchant->id,
            'account_number' => '00010012345003',
            'iban_number' => 'MT122333333',
            'is_notification_active' => 1
        ]);

        $transaction = Transaction::factory()->create([
            'type' => 'PAYOUT_MERCHANT',
            'reference_no' => '2027601152380000',
            'concluded_date' => null,
            'concluded_time' => null
        ]);

        $payload = [
            'txnRefNumber' => $transaction->reference_no,
            'status' => 'S'
        ];

        $headers = [
            'branchCode' => '000',
            'appId' => 'SRVADAPTER',
            'userId' => '',
            'ECID-Context' => ''
        ];

        $this->setMerchantProvider();
        $this->mockSingleTransactionDetails();

        $this->withHeaders($headers)
            ->postJson(route('notification.receiver'), $payload)
            ->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('fc_inbound_transactions', [
            'transaction_ref_no'            => $payload['extRefId'] ?? $payload['txnRefNumber'],
            'initiating_party_type'         => 'PAYOUT_MERCHANT',
            'initiating_party_id'           => $merchant->id,
            'initiating_party_account_type' => config('flexcube-soap.providers.models.merchant_account'),
            'initiating_party_account_id'   => $account->id,
        ]);

        $fcTrans = InboundTransaction::where( [
            'transaction_ref_no' => $payload['extRefId'] ?? $payload['txnRefNumber'],
        ])->first();

        $this->assertDatabaseHas('fc_inbound_transaction_details', [
            'fc_inbound_transaction_id' => $fcTrans->id
        ]);
    }

    /** @test */
    public function itShouldStoreRetailUserTransaction()
    {
        
        $this->withoutExceptionHandling();

        $this->app['config']->set('flexcube-soap.db_services.id', 2);
        $this->app['config']->set('flexcube-soap.db_services.core_id', 3);

        Event::fake();

        $user = RetailUser::factory()->create();

        $account = RetailAccount::factory()->create([
            'user_id'        => $user->id,
            'account_number' => '00010012345003',
            'is_active'      => 1
        ]);
        
        $transaction = Transaction::factory()->create([
            'reference_no' => '2027601152380000',
        ]);

        $payload = [
            'extRefId' => $transaction->reference_no,
            'status' => 'S'
        ];

        $headers = [
            'branchCode' => '000',
            'appId' => 'SRVADAPTER',
            'userId' => '',
            'ECID-Context' => ''
        ];

        $this->mockSingleTransactionDetails();

        $this->withHeaders($headers)
            ->postJson(route('notification.receiver'), $payload)
            ->assertStatus(Response::HTTP_OK);

        $inbound = ModelsInboundTransaction::factory()->create([
            'transaction_ref_no'            => $payload['extRefId'],
            'initiating_party_type'         => 'Finxp\Flexcube\Tests\Mocks\Models\RetailUser',
            'initiating_party_id'           => $user->id,
            'initiating_party_account_type' => config('flexcube-soap.providers.models.retail_account'),
        ]);

        $this->assertDatabaseHas('fc_inbound_transactions', [
            'id'                            => $inbound->id,
            'transaction_ref_no'            => $payload['extRefId'],
            'initiating_party_type'         => 'Finxp\Flexcube\Tests\Mocks\Models\RetailUser',
            'initiating_party_id'           => $user->id,
            'initiating_party_account_type' => config('flexcube-soap.providers.models.retail_account'),
            'initiating_party_account_id'   => $account->id,
            'created_at'                    => $inbound->created_at,
            'updated_at'                    => $inbound->updated_at
        ]);

        InboundTransactionDetails::factory()->create([
            'fc_inbound_transaction_id' => $inbound->id
        ]);

        $this->assertDatabaseHas('fc_inbound_transaction_details', [
            'fc_inbound_transaction_id' => $inbound->id
        ]);
    }

    private function mockSingleTransactionData()
    {
        return [
            'transaction_ref_no' => '2027601152380000',
            'transfer_currency'  => 'EUR',
            'transfer_amount'    => '1',
            'user_ref_no'        => '2027601523841111',
            'remarks'            => 'Test',
            'creditor' => [
                'name'  => 'Test',
                'iban'  => 'MT89123456677788888885668',
                'ac_no' => '00010000278001'
            ],
            'debtor' => [
                'iban'     => 'MT89123456677788888885669',
                'ac_no'    => '00010012345003',
                'currency' => 'EUR',
                'name'     => 'Test2',
                'country'  => '',
                'address1' => '',
                'address2' => ''
            ],
            'creditor_bank_code'   => 'XXXX',
            'debtor_bank_code'     => '',
            'customer_no'          => '0000123',
            'instruction_date'     => '2022-01-01',
            'creditor_value_date'  => '2022-01-01',
            'debtor_value_date'    => '2022-01-01',
            'org_instruction_date' => '2022-01-01',
            'end_to_end_id'        => '',
            'additional_details'   => ''
        ];
    }

    /** @test */
    public function itShouldCallPIQWebhookWhenDirectTransferProcessed()
    {
        
        $this->withoutExceptionHandling();

        $transaction = Transaction::factory()->create([
            'type' => 'PAYOUT_SEPACT',
            'reference_no' => '2027601152380000',
        ]);

        $payload = [
            'uuid' => $transaction->uuid
        ];

        $this->putJson(route('notification.direct.transfer', $payload))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'code', 'status', 'message'
            ]);
    }

    /** @test */
    public function itShouldFailedCallPIQWebhookWhenDirectTransferProcessed()
    {
        $payload = [
            'uuid' => 'test123'
        ];

        $this->putJson(route('notification.direct.transfer', $payload))
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }
    
    /** @test */
    public function itShouldReceivedInternalTransfer()
    {

        $retailuser = RetailUser::factory()->create([
            'provider' => 'ziyl'
        ]);

        $user = User::factory()->create([
            'app_uid' => $retailuser->app_uid
        ]);

        $account = RetailAccount::factory()->create([
            'user_id'        => $user->id,
            'account_number' => '00010012345003',
            'is_active'      => 1
        ]);
        
        
        $retailuser2 = RetailUser::factory()->create([
            'provider' => 'ziyl'
        ]);

        $user2 = User::factory()->create([
            'app_uid' => $retailuser2->app_uid
        ]);

        RetailAccount::factory()->create([
            'user_id'        => $user2->id,
            'account_number' => '00010000278001',
            'is_active'      => 1
        ]);

        $transaction = Transaction::factory()->create([
            'initiating_party_id' => $user->id,
            'account' => $account->account_number,
            'reference_no' => '2027601152380000',
            'concluded_date' => null,
            'concluded_time' => null,
        ]);

        $payload = [
            'extRefId' => $transaction->reference_no,
            'status' => 'S'
        ];

        $headers = [
            'branchCode' => '000',
            'appId' => 'SRVADAPTER',
            'userId' => '',
            'ECID-Context' => ''
        ];

        $this->mockSingleTransactionDetails();

        $this->withHeaders($headers)
            ->postJson(route('notification.receiver'), $payload)
            ->assertStatus(Response::HTTP_OK);
    }

    /** @test */
    public function itShouldReceivedIncomingTransfer()
    {
        $retailuser = RetailUser::factory()->create([
            'provider' => 'ziyl'
        ]);

        $user = User::factory()->create([
            'app_uid' => $retailuser->app_uid
        ]);

        RetailAccount::factory()->create([
            'user_id'        => $user->id,
            'account_number' => '00010000278001',
            'iban' => 'MT89123456677788888885668',
            'is_active'      => 1
        ]);

        $payload = [
            'extRefId' => '2315801143754002',
            'status' => 'S'
        ];

        $headers = [
            'branchCode' => '000',
            'appId' => 'SRVADAPTER',
            'userId' => '',
            'ECID-Context' => ''
        ];

        $this->mockIncomingSingleTransactionDetails();

        $this->withHeaders($headers)
            ->postJson(route('notification.receiver'), $payload)
            ->assertStatus(Response::HTTP_OK);
    }
    
    
    
    /** @test */
    public function itShouldNotSendTestNotification()
    {
        $payload = [
            'reference_no' => '2420801059848000'
        ];

        $this->postJson(route('notification.test'), $payload)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
    
    /** @test */
    public function itShouldSendTestNotification()
    {
        $payload = [
            'reference_no' => '2420801059848000'
        ];
        
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->withHeaders($headers)
            ->postJson(route('notification.test'), $payload)
            ->assertStatus(Response::HTTP_OK);
    }
}
