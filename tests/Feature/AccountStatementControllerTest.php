<?php

namespace Finxp\Flexcube\Tests\Feature;

use Carbon\Carbon;
use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

use Finxp\Flexcube\Services\BankingAPI\Facade\BankingAPIService;
use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Tests\Mocks\Models\ApiAccess;
use Finxp\Flexcube\Tests\Mocks\Models\User;
use Finxp\Flexcube\Tests\Mocks\Models\Role;
use Finxp\Flexcube\Tests\Mocks\Models\Account;
use Finxp\Flexcube\Tests\Mocks\Models\FCTransactions;
use Finxp\Flexcube\Tests\Mocks\Models\MerchantAccount;
use Finxp\Flexcube\Tests\Mocks\Models\InboundTransaction;
use Finxp\Flexcube\Tests\Mocks\Models\InboundTransactionDetails;
use Finxp\Flexcube\Tests\Mocks\Models\RetailAccount;
use Finxp\Flexcube\Tests\Mocks\Models\Transaction;

class AccountStatementControllerTest extends TestCase
{
    /** @test */
    public function itShouldGetAccountStatementRawData()
    {
        $merchant = Merchant::factory()
            ->create();

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant->id
            ]);

        $customerNo = '0000123';
        $customerAcNo = '00001230001';

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];
        $payload = [
            'customer_no' => $customerNo,
            'currency' => 'EUR',
            'customer_ac_no' => $customerAcNo
        ];

        $this->mockGetTransactions();

        $res = $this->withHeaders($headers)
            ->getJson(route('account.accountStatementAndBal', $payload))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'code', 'status', 'message'
            ]);

        $data = json_decode($res->getContent(), true);
        $this->assertTrue(sizeof($data) > 0);
        $this->assertArrayHasKey('transactions', $data['data']);
    }

    /** @test */
    public function itShouldGetAccountBalance()
    {
        parent::setAuthApi();
        $user = User::create([
            'email' => 'test@gmail.com',
            'password' => 'secret'
        ]);
        $role = Role::create(['name' => 'operations']);

        $user->roles()->sync($role->id);

        $this->actingAs($user);

        $merchant = Merchant::factory()
            ->create();

        $merchantAccount = MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $this->setMerchantProvider();
        $this->mockAccountsAndBalance();

        $res = $this->getJson(
            route('merchants.account.balance',
            [
                'merchantId'     => $merchant->id,
                'customer_ac_no' => $merchantAccount->account_number
            ])
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['code', 'status', 'data']);

        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('currency', $data['data']);
    }

    /** @test */
    public function itShouldReturnAccountNotFound()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $role = Role::create(['name' => 'operations']);

        $user->roles()->sync($role->id);

        $this->actingAs($user);

        $merchant = Merchant::factory()
            ->create();

        $this->setMerchantProvider();
        $this->mockAccountsAndBalance();

        $this->getJson(
            route('merchants.account.balance',
                [
                    'merchantId'     => $merchant->id,
                    'customer_ac_no' => '12345'
                ])
        )
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure(['code', 'status']);
    }

    /** @test */
    public function itShouldGetMerchantAccountBalance()
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
            'customer_ac_no' => $merchantAccount->account_number
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockAccountsAndBalance();

        $res = $this->withHeaders($headers)
            ->getJson(route('merchant.account.balance', $payload))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'code', 'status'
            ]);

        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey( 'currency', $data['data'] );
    }

    public function itShouldGetTransactionDetails()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $role = Role::create(['name' => 'operations']);

        $user->roles()->sync($role->id);

        $this->actingAs($user);

        $transRefNo = '00010012345001';

        $this->mockSingleTransactionDetails();

        $res = $this->json(Request::METHOD_GET, route('operations.account.getProofOfPayment', ['transaction_ref_no' => $transRefNo]))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure( ['code', 'status', 'data', 'message'] );

        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey( 'transaction_ref_no', $data['data'] );
    }

    /** @test */
    public function itShouldReturnFailIfNoTransactionDetails()
    {
        parent::setAuthApi();
        $user = User::create(['email' => 'test@gmail.com', 'password' => 'secret']);
        $role = Role::create(['name' => 'operations']);

        $user->roles()->sync($role->id);

        $this->actingAs($user);

        $transRefNo = '00010012345001';

        $this->mockNoSingleTransactionDetails();

        $this->json(Request::METHOD_GET, route('operations.account.getProofOfPayment', ['transaction_ref_no' => $transRefNo]))
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure(['code', 'status', 'message']);
    }

    /** @test */
    public function itShouldGetMerchantAccountTransactions()
    {
        $this->setMerchantProvider();
        $merchant_id = Merchant::factory()->create()->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $merchant_account_id = MerchantAccount::factory()->create(['merchant_id' => $merchant_id])->id;

        $size = rand(5, 10);
        $inbound_transactions = InboundTransaction::factory()->count($size)
            ->create(['initiating_party_type' => 'merchant', 'initiating_party_account_type' => 'merchant', 'initiating_party_id' => $merchant_id, 'initiating_party_account_id' => $merchant_account_id]);

        $selected = rand(0, sizeof($inbound_transactions) - 1);
        fwrite(STDERR, print_r($selected, TRUE));

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $query = [
            'page' => 1,
            'limit' => 10,
            'filter[]' => null
        ];

        $response = $this->withHeaders($headers)
            ->json(
                Request::METHOD_GET,
                route(
                    'merchant.account.transactions',
                    ['merchantAccountId' => $merchant_account_id]
                ) . '?' . http_build_query($query)
            )->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'meta'
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($size, count($content['data']));
    }

    /** @test */
    public function itShouldGetMerchantAccountTransapctionsWithTransactionNumber()
    {
        $this->setMerchantProvider();

        $merchant_id = Merchant::factory()->create()->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $merchant_account_id = MerchantAccount::factory()->create(['merchant_id' => $merchant_id])->id;

        $size = rand(5, 10);
        $inbound_transactions = InboundTransaction::factory()->count($size)->create(['initiating_party_id' => $merchant_id, 'initiating_party_account_id' => $merchant_account_id]);

        $selected = rand(0, sizeof($inbound_transactions) - 1);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $query = [
            'page' => 1,
            'limit' => 10,
            'filter[transaction_ref_no]' => $inbound_transactions[$selected]->id
        ];

        $this->withHeaders($headers)
            ->json(
                Request::METHOD_GET,
                route(
                    'merchant.account.transactions',
                    ['merchantAccountId' => $merchant_account_id]
                ) . '?' . http_build_query($query)
            )->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'meta'
            ]);
    }

    /** @test */
    public function itShouldGetMerchantAccountTransactionDetail()
    {
        $this->setMerchantProvider();
        
        $merchant_id = Merchant::factory()->create()->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $merchant_account_id = MerchantAccount::factory()->create(['merchant_id' => $merchant_id])->id;

        $inbound_transaction_id = InboundTransaction::factory()->create(['initiating_party_id' => $merchant_id, 'initiating_party_account_id' => $merchant_account_id])->id;

        $inbound_transaction_detail = InboundTransactionDetails::factory()->create(['fc_inbound_transaction_id' => $inbound_transaction_id]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $response = $this->withHeaders($headers)
            ->getJson(route('merchant.account.transaction.detail', ['transactionID' => $inbound_transaction_id]))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'status', 'code'
            ]);

        $content = $response->decodeResponseJson();

        $this->assertEquals($inbound_transaction_detail->id, $content['data']['fc_inbound_transaction_id']);
    }

    /** @test */
    public function itShouldMerchantCustomerDetails()
    {
        $merchant_id = Merchant::factory()->create()->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}")
        ];

        $this->mockCustomerDetails();

        $this->withHeaders($headers)
            ->getJson(route('merchant.customer.details'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data', 'status', 'code'
            ]);
    }

    #Retail
    
    /** @test */
    public function itShouldGetCustomerDetails()
    {
   
        $user = $this->user();

        $this->actingAs($user);

        $this->mockCustomerDetails();

        $res = $this->json(Request::METHOD_GET, route('customer.account.detail'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['code', 'status', 'data']);

        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('customer_no', $data['data']);
    }
    
    /** @test */
    public function itShouldGetCustomerAccounts()
    {
        $user = $this->user();

        $this->actingAs($user);

        $this->mockAccountsAndBalance();

        $res = $this->json(Request::METHOD_GET, route('customer.accounts'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['code', 'status', 'data']);

        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(2, $data['data']);
    }

    /** @test */
    public function itShouldGetCustomerAccountBalanceAndInfo()
    {
        $user = $this->user();

        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'account_number' => '00010012345001'
        ]);

        $this->mockAccountBalanceAndInfo();
        // $this->mockStatementAccountBalance();

        $res = $this->json(Request::METHOD_GET, route('customer.account.info', [$account->id]))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['code', 'status', 'data']);
        ;
        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('currency', $data['data']);
        $this->assertArrayHasKey('blocked_amount', $data['data']);
    }
    
    /** @test */
    public function itShouldNotGetCustomerAccountBalanceNotOwned()
    {
        $user = $this->user();

        $this->actingAs($user);

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'account_number' => '000100123450011'
        ]);

        $account2 = Account::factory()->create([
            'account_number' => '000100123450011'
        ]);

        $this->mockAccountBalanceAndInfo();

        $this->json(Request::METHOD_GET, route('customer.account.info', ['id' => $account2->id]))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
    
    /** @test */
    public function itShouldGetCustomerAccountBalanceNotFound()
    {
        $user = $this->user();

        $this->actingAs($user);

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'account_number' => '000100123450011'
        ]);

        $this->mockAccountBalanceAndInfo();

        $this->json(Request::METHOD_GET, route('customer.account.info', ['id' => $account->id]))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function itShouldGetTransactionStatement()
    {
        $user = $this->user();

        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'account_number' => '00010012345001'

        ]);
        
        $transaction = Transaction::factory()->create([
            'initiating_party_id' => $user->id
        ]);

        FCTransactions::factory()->create([
            'transaction_id' => $transaction->id,
            'debtor_iban' => $account->iban
        ]);

        $this->mockGetAllTransactionHistory();

        $payload = [
            'account_id' => $account->id
        ];

        $res = $this->json(Request::METHOD_POST, route('customer.transaction.statement'), $payload)
            ->assertStatus(Response::HTTP_OK);
        
        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('data', $data);
    }

    /** @test */
    public function itShouldGetAllTransactionHistory()
    {
        $user = $this->user();

        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'account_number' => '00010012345001'

        ]);
        
        $transaction = Transaction::factory()->create([
            'initiating_party_id' => $user->id
        ]);

        FCTransactions::factory()->create([
            'transaction_id' => $transaction->id,
            'debtor_iban' => $account->iban
        ]);

        $this->mockGetAllTransactionHistory();

        $payload = [
            'account_id' => $account->id,
            'group_date' => true,
        ];

        $res = $this->json(Request::METHOD_POST, route('customer.transaction.list'), $payload)
            ->assertStatus(Response::HTTP_OK);
        
        $data = json_decode($res->getContent(), true);
        $this->assertArrayHasKey('transaction_id', $data['data'][0]['data'][0]);
    }
    

    /** @test */
    public function itShouldGetAllTransactionHistoryWithFilter()
    {
        $user = $this->user();

        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'account_number' => '00010012345001'

        ]);
        
        $transaction = Transaction::factory()->create([
            'initiating_party_id' => $user->id
        ]);

        FCTransactions::factory()->create([
            'transaction_id' => $transaction->id,
            'debtor_iban' => $account->iban
        ]);

        $this->mockGetAllTransactionHistory();

        $payload = [
            'account_id' => $account->id,
            'iban' => $account->iban,
            'from_date' =>  date('Y-m-d', strtotime(now())),
            'from_to' =>  date('Y-m-d', strtotime(now())),
            'group_date' => true,
        ];

        $res = $this->json(Request::METHOD_POST, route('customer.transaction.list'), $payload)
            ->assertStatus(Response::HTTP_OK);
        
        $data = json_decode($res->getContent(), true);
        
        $this->assertArrayHasKey('transaction_id', $data['data'][0]['data'][0]);
    }

    /** @test */
    public function itShouldGetAllTransactionHistoryWithFilterValidateDate()
    {
        $user = $this->user();

        $this->actingAs($user);

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id,
            'account_number' => '00010012345001'

        ]);
        
        $transaction = Transaction::factory()->create([
            'initiating_party_id' => $user->id
        ]);

        FCTransactions::factory()->create([
            'transaction_id' => $transaction->id,
            'debtor_iban' => $account->iban
        ]);

        $this->mockGetAllTransactionHistory();

        $payload = [
            'account_id' => $account->id,
            'iban' => $account->iban,
            'from_date' =>  Carbon::now()->subMonths(6)->format('Y-m-d'),
            'from_to' =>  Carbon::now()->format('Y-m-d')
        ];

        $this->json(Request::METHOD_POST, route('customer.transaction.list'), $payload)
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    /** @test */
    public function itShouldNotAuthorizeGetAllTransactions()
    {
        $user = $this->user();

        $this->actingAs($user);

        $user2 = User::create([
            'email' => 'test2@gmail.com', 
            'password' => 'secret', 
            'customer_account_number' => '00001'
        ]);

        $role = Role::create(['name' => 'operations']);

        $user2->roles()->sync($role->id);

        $account = RetailAccount::factory()->create([
            'user_id' => $user2->id,
            'account_number' => '00010012345001'

        ]);

        $payload = [
            'account_id' => $account->id
        ];

        $res = $this->json(Request::METHOD_POST, route('customer.transaction.list'), $payload)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
    
    /** @test */
    public function itShouldUnprocessEntityGetAllTransactions()
    {
        $user = $this->user();

        $this->actingAs($user);

        $payload = [];

        $res = $this->json(Request::METHOD_POST, route('customer.transaction.list'), $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function itShouldGetAllMerchantTransactions()
    {
        $this->mockGetAllTransactions();

        $this->setMerchantProvider();

        $merchant_id = Merchant::factory()->create()->id;

        $merchant_account = MerchantAccount::factory()->create(
            [
                'merchant_id' => $merchant_id,
                'account_number' => '00010012345001'
            ]
        );

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);
        
        $transaction = Transaction::factory()->create([
            'initiating_party_id' => $merchant_account->id,
            'reference_no' => '2107001462348000'
        ]);

        FCTransactions::factory()->create([
            'transaction_id' => $transaction->id,
            'debtor_iban' => $merchant_account->iban_number
        ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}"),
            'x-merchant' => $merchant_account->id
        ];

        $response = $this->withHeaders($headers)
            ->getJson(route('transaction.list'))
            ->assertStatus(Response::HTTP_OK);
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('balance', $data[0]);
    }

    /** @test */
    public function itShouldNotFoundAllMerchantTransactions()
    {

        $this->setMerchantProvider();

        $merchant_id = Merchant::factory()->create()->id;

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);
        
        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}"),
            'x-merchant' => 4
        ];

        $this->withHeaders($headers)
            ->getJson(route('transaction.list'))
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function itShouldUnauthorizeGetAllMerchantTransactions()
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

        $this->withHeaders($headers)
            ->getJson(route('transaction.list'))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function itShouldGetAllFlexcubeTransactionsWithEmptyReturn()
    {
        $this->mockGetAllTransactions();

        $this->setMerchantProvider();

        $merchant_id = Merchant::factory()->create()->id;

        $merchant_account = MerchantAccount::factory()->create(
            [
                'merchant_id' => $merchant_id,
                'account_number' => '00010012345001'
            ]
        );

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}"),
            'X-merchant' => $merchant_id
        ];

        BankingAPIService::shouldReceive('getAllTransactions')
            ->once()
            ->withAnyArgs()
            ->andReturn([]);

        $response = $this->withHeaders($headers)
            ->getJson(route('transaction.list.all'))
            ->assertStatus(Response::HTTP_OK);
            
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('message', $data);
    }

    /** @test */
    public function itShouldGetAllFlexcubeTransactionsUnauthAccountNumber()
    {
        $this->mockGetAllTransactions();

        $this->setMerchantProvider();

        $merchant_id = Merchant::factory()->create()->id;

        $merchant_account = MerchantAccount::factory()->create(
            [
                'merchant_id' => Merchant::factory()->create()->id,
                'account_number' => '00010012345002'
            ]
        );

        $merchant_account = MerchantAccount::factory()->create(
            [
                'merchant_id' => $merchant_id,
                'account_number' => '00010012345001'
            ]
        );

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}"),
            'x-merchant' => $merchant_id
        ];

        $payload = [
            'account_number' => '00010012345003'
        ];

        $response = $this->withHeaders($headers)
            ->getJson(route('transaction.list.all', $payload))
            ->assertStatus(Response::HTTP_FORBIDDEN);
            
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals($data['message'], 'Account not found!');
    }

    /** @test */
    public function itShouldNotGetSingleFlexcubeTransaction()
    {
        $this->mockGetAllTransactions();

        $this->setMerchantProvider();

        $merchant_id = Merchant::factory()->create()->id;

        $merchant_account = MerchantAccount::factory()->create(
            [
                'merchant_id' => $merchant_id,
                'account_number' => '00010012345001'
            ]
        );

        $apiAccess = ApiAccess::factory()
            ->create([
                'merchant_id' => $merchant_id
            ]);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$apiAccess->key}:{$apiAccess->secret}"),
            'X-merchant' => $merchant_id
        ];

        $payload = [
            'transaction_ref_no' => '2323601144594000'
        ];

        BankingAPIService::shouldReceive('getTransactionDetails')
            ->once()
            ->withAnyArgs()
            ->andReturn([
                'creditor' => [
                    'ac_no' => '00010012345001'
                ]
            ]);

        $response = $this->withHeaders($headers)
            ->getJson(route('transaction.single', $payload))
            ->assertStatus(Response::HTTP_BAD_REQUEST);
            
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals($data['message'], 'Failed to retrieve data.');
    }

    ## Payments V2

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
