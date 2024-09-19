<?php
namespace Finxp\Flexcube\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Finxp\Flexcube\FlexcubePackageServiceProvider;
use Finxp\Flexcube\Tests\Mocks\Models\Role;
use Finxp\Flexcube\Tests\Mocks\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends OrchestraTestCase
{
    use DatabaseMigrations;

    protected $connectionsToTransact = ['testing'];

    protected $apiUrl;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        $this->apiUrl = config('flexcube-soap.banking_api.base_url');
    }

    protected function getPackageProviders($app)
    {
        return [FlexcubePackageServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);

        $app['config']->set('flexcube-soap.providers.models', [
            'transaction' => \Finxp\Flexcube\Tests\Mocks\Models\Transaction::class,
            'merchant' => \Finxp\Flexcube\Tests\Mocks\Models\Merchant::class,
            'api' => \Finxp\Flexcube\Tests\Mocks\Models\ApiAccess::class,
            'webhook' => \Finxp\Flexcube\Tests\Mocks\Models\Webhook::class,
            'merchant_account' => \Finxp\Flexcube\Tests\Mocks\Models\MerchantAccount::class,
            'service' => \Finxp\Flexcube\Tests\Mocks\Models\Service::class,
            'sca' => \Finxp\Flexcube\Tests\Mocks\Models\Token::class,
            'user' => \Finxp\Flexcube\Tests\Mocks\Models\User::class,
            'retail_account' => \Finxp\Flexcube\Tests\Mocks\Models\RetailAccount::class,
            'account_limit_setting' => \Finxp\Flexcube\Tests\Mocks\Models\AccountLimitSetting::class,
            'account_limit_log' => \Finxp\Flexcube\Tests\Mocks\Models\AccountLimitLog::class
        ]);

        $app['config']->set('flexcube-soap.middleware.role', \Finxp\Flexcube\Tests\Mocks\Middleware\RoleMiddleware::class);

        $app['config']->set('webpush.model', \NotificationChannels\WebPush\PushSubscription::class);

        $app['config']->set('flexcube-soap.providers.services.webhook', \Finxp\Flexcube\Tests\Mocks\Services\WebhookCallService::class);

        $app['config']->set('flexcube-soap.providers.services.freshdesk', \Finxp\Flexcube\Tests\Mocks\Services\Freshdesk\Facade\FreshDesk::class);

        $app['config']->set('flexcube-soap.middleware.routeotp', \Finxp\Flexcube\Tests\Mocks\Middleware\RouteOtp::class);

        $app['config']->set('queue.name', 'app');

        $app['config']->set('flexcube-soap.enable_webpush_notification', true);

        $app['config']->set('flexcube-soap.provider.piq_webhook_url', 'https://eoenlmn4xj6rruo.m.pipedream.net');

        $app['config']->set('columnsortable.default_first_column', 'id');
        
        $app['config']->set('eloquentfilter.namespace', 'Finxp\\Flexcube\\Tests\\Mocks\\Models\\ModelFilters\\');
        
        $app['config']->set('zazoo.webhook_url', 'https://eoenlmn4xj6rruo.m.pipedream.net');
        
        $app['config']->set('zazoo.webhook_secret', 'test');
        
        $app['config']->set('freshdesk.general.base_url', 'https://eoenlmn4xj6rruo.m.pipedream.net');
        
        $app['config']->set('freshdesk.general.api_key', 'test');
        
        $app['config']->set('flexcube-soap.payment_notification_url', 'https://eoenlmn4xj6rruo.m.pipedream.net');
        
        Request::macro(
            'allFilled',
            function (array $keys) {
                foreach ($keys as $key) {
                    if (! $this->filled($key)) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    protected function setUpDatabase($app): void
    {
        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('app_uid')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('customer_account_number', 20)->nullable();
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('user_has_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
        });

        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('merchants', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->string('name', 150);
                $table->string('short_code', 10)->nullable();
                $table->string('slug')->unique();
                $table->string('point_of_contact')->nullable();
                $table->string('street')->nullable();
                $table->string('unit_no')->nullable();
                $table->string('city')->nullable();
                $table->string('country')->nullable();
                $table->string('postal')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_iso')->default(false);
                $table->boolean('skip_computation')->default(false);
                $table->string('customer_number')->nullable();
                $table->timestamps();
            });

        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('services', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 150);
                $table->timestamps();
            });

        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('api_access', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('key', 36);
                $table->string('secret', 36);
                $table->unsignedBigInteger('merchant_id');
                $table->boolean('revoked')->default(false);
                $table->timestamps();

                $table->foreign('merchant_id')
                    ->references('id')
                    ->on('merchants')
                    ->onDelete('cascade');

                $table->unique(['key', 'secret']);
            });

        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('merchant_accounts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid');
                $table->string('account_number');
                $table->string('iban_number');
                $table->boolean('is_notification_active')->default(0);
                $table->string('account_desc')->nullable();
                $table->unsignedBigInteger('merchant_id');
                $table->timestamps();

                $table->foreign('merchant_id')
                    ->references('id')
                    ->on('merchants');
            });

        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('accounts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('account_number');
                $table->string('iban');
                $table->string('description')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users');
            });
            
        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('account_limit_settings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->double('limit', 8, 2);
                $table->string('provider')->nullable();
                $table->timestamps();
            });
        
        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('account_limit_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->bigInteger('requestor_id');
                $table->bigInteger('settings_id')->index('settings_id')->nullable();
                $table->bigInteger('account_id')->index('account_id')->nullable();
                $table->bigInteger('account_limit_id')->index('account_limit_id')->nullable();
                $table->enum('level', ['settings', 'user']);
                $table->enum('type', ['operations', 'user']);
                $table->double('old_value', 8, 2)->index('old_value');
                $table->double('new_value', 8, 2)->index('new_value');
                $table->timestamps();
            });

        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('merchant_has_users', function (Blueprint $table) {
                $table->unsignedBigInteger('merchant_id');
                $table->unsignedBigInteger('user_id');

                $table->foreign('merchant_id')
                    ->references('id')
                    ->on('merchants')
                    ->onDelete('cascade');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->primary(['merchant_id', 'user_id']);
            });

        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('push_subscriptions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->morphs('subscribable');
                $table->string('endpoint', 255)->unique();
                $table->string('public_key')->nullable();
                $table->string('auth_token')->nullable();
                $table->string('content_encoding')->nullable();
                $table->timestamps();
            });

        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('webhooks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('url');
                $table->string('event', 100);
                $table->text('custom_headers')->nullable();
                $table->boolean('is_active');
                $table->unsignedBigInteger('merchant_id')->nullable();
                $table->timestamps();

                $table->foreign('merchant_id')
                    ->references('id')
                    ->on('merchants')
                    ->onDelete('cascade');
            });

        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('webhook_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('webhook_id');
                $table->string('url');
                $table->text('headers')->nullable();
                $table->longText('payload')->nullable();
                $table->string('status', 20);
                $table->timestamps();

                $table->foreign('webhook_id')
                    ->references('id')
                    ->on('webhooks')
                    ->onDelete('cascade');
            });

        $app['db']
            ->connection(config('database.sandbox'))
            ->getSchemaBuilder()
            ->create('transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid');
                $table->string('type')->nullable();
                $table->decimal('amount', 13, 2)->default(0);
                $table->string('currency', 3)->nullable();
                $table->string('status', 15)->nullable();
                $table->string('account', 45)->nullable();
                $table->date('transaction_date')->nullable();
                $table->time('transaction_time')->nullable();
                $table->date('concluded_date')->nullable();
                $table->time('concluded_time')->nullable();
                $table->unsignedBigInteger('service_id')->nullable();
                $table->unsignedBigInteger('merchant_id')->nullable();
                $table->unsignedBigInteger('initiating_party_id')->nullable();
                $table->unsignedBigInteger('initiating_receiver_id')->nullable();
                $table->string('creditor_name')->nullable();
                $table->string('reference_no')->nullable();
                $table->string('redirect_url')->nullable();
                $table->string('cancel_url')->nullable();
                $table->string('response_url')->nullable();
                $table->string('external_transaction_id', 120)->nullable();
                $table->string('bin', 6)->nullable();
                $table->string('country', 3)->nullable();
                $table->string('provider')->nullable();
                $table->string('website', 255)->nullable();
                $table->boolean('is_concluded')->default(false);
                $table->ipAddress('ip_address')->default('0.0.0.0');
                $table->mediumText('user_agent')->nullable();
                $table->timestamps();
            });

        
            $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('otp_tokens', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('identifier');
                $table->string('code', 6);
                $table->string('type');
                $table->boolean('revoked')->default(false);
                $table->binary('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();

                $table->unique(['identifier', 'code']);
            });

        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('retail_users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('app_uid', 10)->nullable();
                $table->string('email');
                $table->string('first_name', 100);
                $table->string('middle_name')->nullable();
                $table->string('last_name', 100);
                $table->date('dob')->nullable();
                $table->string('phone_number');
                $table->tinyInteger('status')->default(0);
                $table->string('iban', 50)->nullable();
                $table->string('bic', 20)->nullable();
                $table->string('provider')->nullable();
                $table->text('hash_tokens')->nullable();
                $table->mediumText('notes')->nullable();
                $table->timestamps();
            });

        $app['db']
            ->connection()
            ->getSchemaBuilder()
            ->create('retail_accounts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('user_id');
                $table->string('account_number', 100)->nullable();
                $table->text('description');
                $table->string('iban', 100)->nullable();
                $table->string('bic', 50)->default('PAUUMTM1');
                $table->string('status')->default('pending');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('retail_users');
            });
    }

    protected function mockGetTransactions()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [
                        'data' => [
                            'account_details' => [
                                'customer_no' => '0000123',
                                'cust_name' => 'Test',
                                'address1' =>  '',
                                'address2' => '',
                                'address3' => ''
                            ],
                            'transactions'    => [
                                [
                                    'customer_no' => '0000123',
                                    'cr_ac_no' => '00010012345001',
                                    'dr_bank_code' => 'HYVEDEXXX',
                                    'transfer_amount' => '1',
                                    'transfer_ccy' => 'EUR',
                                    'txn_ref_no' => '027601152380000',
                                    'source_ref_no' => '2027601523841111',
                                    'counter_party_name' => 'Paymentworld Europe Ltd',
                                    'book_date' => '2020-10-02',
                                    'instruction_date' => '2020-10-02',
                                    'activation_date' => '2020-10-02'
                                ]
                            ],
                            'date_from'       => '',
                            'date_to'         => '',
                        ]
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockGetAccounts()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [
                        [
                            'cust_ac_no' => '00010012345001',
                            'iban_ac_no' => 'MT89123456677788888885668',
                            'account_desc' => ''
                        ],
                        [
                            'cust_ac_no' => '00010012345002',
                            'iban_ac_no' => 'MT89123456677788888885669',
                            'account_desc' => ''
                        ],
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockFailedGetAccounts()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [],
                    Response::HTTP_BAD_REQUEST,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }
    

    protected function mockGetAccountsAndBalance()
    {
        Http::fake(
            [
                $this->apiUrl . '/accounts' => Http::Response(
                    [
                        [
                            'cust_ac_no' => '00010012345001',
                            'iban_ac_no' => 'MT89123456677788888885668',
                            'account_desc' => ''
                        ],
                        [
                            'cust_ac_no' => '00010012345002',
                            'iban_ac_no' => 'MT89123456677788888885669',
                            'account_desc' => ''
                        ],
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ],
            [
                $this->apiUrl . '/balance' => Http::Response(
                    [
                        'data' => [
                            'currency' => 'EUR',
                            'opnbal' => 1,
                            'curbal' => 1,
                            'avlbal' => 1
                        ]
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockGetBalance()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [
                        'data' => [
                            'currency' => 'EUR',
                            'opnbal' => 1,
                            'curbal' => 1,
                            'avlbal' => 1
                        ]
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockAccountsAndBalance()
    {
        Http::fakeSequence( $this->apiUrl . '/*')
            ->push(
                [
                    [
                        'cust_ac_no' => '00010012345001',
                        'iban_ac_no' => 'MT89123456677788888885668',
                        'account_desc' => ''
                    ],
                    [
                        'cust_ac_no' => '00010012345002',
                        'iban_ac_no' => 'MT89123456677788888885669',
                        'account_desc' => ''
                    ],
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    'data' => [
                        'currency' => 'EUR',
                        'opnbal' => 1,
                        'curbal' => 1,
                        'avlbal' => 1
                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            );
    }

    protected function mockAccounts2AndBalance()
    {
        Http::fakeSequence( $this->apiUrl . '/*')
            ->push(
                [
                    [
                        'cust_ac_no' => '00010012345001',
                        'iban_ac_no' => 'MT89123456677788888885668',
                        'account_desc' => ''
                    ],
                    [
                        'cust_ac_no' => '00010012345003',
                        'iban_ac_no' => 'MT89123456677788888885670',
                        'account_desc' => ''
                    ],
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    'data' => [
                        'currency' => 'EUR',
                        'opnbal' => 1,
                        'curbal' => 1,
                        'avlbal' => 1
                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            );
    }
    
    protected function mockAccountBalanceAndInfo()
    {
        Http::fakeSequence( $this->apiUrl . '/*')
            ->push(
                [
                    [
                        'cust_ac_no' => '00010012345001',
                        'iban_ac_no' => 'MT89123456677788888885668',
                        'account_desc' => 'PWE_TRANSIT_IN'
                    ],
                    [
                        'cust_ac_no' => '00010012345002',
                        'iban_ac_no' => 'MT89123456677788888885669',
                        'account_desc' => ''
                    ],
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    'data' => [
                        'currency' => 'EUR',
                        'opnbal' => 1,
                        'curbal' => 1,
                        'avlbal' => 1
                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    'data' => [
                        [
                            'acy_eca_blocked_amt' => 1,
                        ]
                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            );
    }

    protected function mockAccountSingleTransactionDetails()
    {
        Http::fakeSequence( $this->apiUrl . '/*')
            ->push(
                [
                    [
                        'cust_ac_no' => '00010000278001',
                        'iban_ac_no' => 'MT89123456677788888885668',
                        'account_desc' => ''
                    ],
                    [
                        'cust_ac_no' => '00010012345002',
                        'iban_ac_no' => 'MT89123456677788888885669',
                        'account_desc' => ''
                    ],
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    'data' => [
                        'transaction_ref_no' => '2027601152380000',
                        'transaction_status'  => 'S',
                        'transfer_currency'  => 'EUR',
                        'transfer_amount'    => 1.00,
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
                        'creditor_bank_code'   => '123',
                        'debtor_bank_code'     => '123',
                        'customer_no'          => '0000123',
                        'instruction_date'     => '2022-01-01',
                        'creditor_value_date'  => '2022-01-01',
                        'debtor_value_date'    => '2022-01-01',
                        'org_instruction_date' => '2022-01-01',
                        'end_to_end_id'        => '1234123',
                        'additional_details'   => ''
                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            );
    }

    protected function mockSingleTransactionDetails()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [
                        'data' => [
                            'transaction_ref_no' => '2027601152380000',
                            'transaction_status'  => 'S',
                            'transfer_currency'  => 'EUR',
                            'transfer_amount'    => 1.00,
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
                            'creditor_bank_code'   => '123',
                            'debtor_bank_code'     => '123',
                            'customer_no'          => '0000123',
                            'instruction_date'     => '2022-01-01',
                            'creditor_value_date'  => '2022-01-01',
                            'debtor_value_date'    => '2022-01-01',
                            'org_instruction_date' => '2022-01-01',
                            'end_to_end_id'        => '1234123',
                            'additional_details'   => ''
                        ]
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockIncomingSingleTransactionDetails()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [
                        'data' => [
                            'transaction_ref_no' => '2315801143754002',
                            'transaction_status'  => 'S',
                            'transfer_currency'  => 'EUR',
                            'transfer_amount'    => 1.00,
                            'user_ref_no'        => '2315801143754002',
                            'remarks'            => 'Test',
                            'creditor' => [
                                'name'  => 'Test',
                                'iban'  => 'MT89123456677788888885668',
                                'ac_no' => '00010000278001'
                            ],
                            'debtor' => [
                                'iban'     => 'DE72100110012621280346',
                                'ac_no'    => '00010012345003',
                                'currency' => 'EUR',
                                'name'     => 'Test2',
                                'country'  => '',
                                'address1' => '',
                                'address2' => ''
                            ],
                            'creditor_bank_code'   => '123',
                            'debtor_bank_code'     => '123',
                            'customer_no'          => '0000123',
                            'instruction_date'     => '2022-01-01',
                            'creditor_value_date'  => '2022-01-01',
                            'debtor_value_date'    => '2022-01-01',
                            'org_instruction_date' => '2022-01-01',
                            'end_to_end_id'        => '1234123',
                            'additional_details'   => ''
                        ]
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockNoSingleTransactionDetails()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [
                        'data' => []
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockInternalTransfer()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [
                        'data'    => '2027601152380000',
                        'status'  => 'success',
                        'code'    => Response::HTTP_OK
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockFailedInternalTransfer()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [
                        'status'  => 'success',
                        'code'    => Response::HTTP_BAD_REQUEST,
                        'message' => 'Failed'
                    ],
                    Response::HTTP_BAD_REQUEST,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockCustomerDetails()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                        [
                            'status'  => 'success',
                            'code'    => Response::HTTP_OK,
                            'message' => '',
                            'data' => [
                                'private_customer' => 'N',
                                'customer_no'      => '000123',
                                'ctype'            => 'C',
                                'name'             => 'Test Name',
                                'full_name'        => 'Test Name',
                                'addrln1'           => '',
                                'addrln2'           => '',
                                'addrln4'           => '',
                                'country'           => '',
                                'sname'             => '',
                                'lbrn'              => '000',
                                'category'          => '',
                                'registration_date' => '2020-01-01',
                                'customer_personal' => [
                                    'mobile_no'       => '',
                                    'mobile_isd_code' => ''
                                ],
                                'customer_corporate'  => [
                                    'corp_name'       => '',
                                    'reg_add1'        => '',
                                    'reg_add2'        => '',
                                    'mobile_no'       => '',
                                    'mobile_isd_code' => '',
                                    'r_pin_code'      => ''
                                ]
                            ]
                        ],
                        Response::HTTP_OK,
                        [
                            'Content-Type' => 'application/json'
                        ]
                    )
            ]
        );
    }

    protected function mockGetAllTransactionHistory()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [
                        // 'data' => [
                            'data' => [
                                [
                                    'transaction_id' =>  'TXN_REF_NO',
                                    'transaction_uuid' =>  'TXN_REF_NO',
                                    'reference_no' =>  'TXN_REF_NO',
                                    'transfer_currency'  => 'EUR',
                                    'service'           => 'INTERNAL',
                                    'amount'              => '0.2',
                                    'name'             => 'NAME',
                                    'iban'              => 'IBAN',
                                    'cr_iban'             =>'IBAN',
                                    'cr_acno'             => 'IBAN',
                                    'cr_name'             => 'IBAN',
                                    'dr_iban'             => 'IBAN',
                                    'dr_acno'             => 'IBAN',
                                    'bic'                 => 'PAUUMTM1XXX',
                                    'status'        => 'S',
                                    'trn_type'    =>  'INTERNAL',
                                    'description'          => 'REMARKS',
                                    'transaction_datetime'   => '2023-03-01'
                                ]

                            ]
                        // ]
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockGetAllTransactions()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [
                        [
                            'transaction_ref_no' => '2107001462348000',
                            'transfer_currency' => 'EUR',
                            'debit' => 0,
                            'credit' => '5',
                            'opening_balance' => '0',
                            'closing_balance' => '21.09',
                            'balance' => '5.00',
                            'sender_receiver' => 'Paymentworld Europe Ltd  00010000395001',
                            'description' => 'Internal/Own Account Transactions|For testing Purpose   ',
                            'transaction_date' => '2021-03-12 00:00:00'
                        ],
                        [
                            'transaction_ref_no' => '2108201052578000',
                            'transfer_currency' => 'EUR',
                            'debit' => 1.1,
                            'credit' => null,
                            'opening_balance' => '0',
                            'closing_balance' => '21.09',
                            'balance' => '3.90',
                            'sender_receiver' => 'FinXP Ltd  00010000395001',
                            'description' => 'Internal/Own Account Transactions|33   ',
                            'transaction_date' => '2021-03-23 00:00:00'
                        ],
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }
    protected function mockDirectTransfer()
    {
        Http::fake(
            [
                $this->apiUrl . '/*' => Http::Response(
                    [
                        'data'    => '2027601152380000',
                        'status'  => 'success',
                        'code'    => Response::HTTP_OK
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockAccountsBicAndBalance($isSEPA = false)
    {
        Http::fakeSequence( $this->apiUrl . '/*')
            ->push(
                [
                    'data' => [
                        [
                            'bic' => $isSEPA ? 'TEST' : 'PAUUMTM1XXX'
                        ]
                    ],
                    'status'  => 'success',
                    'code'    => Response::HTTP_OK
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    [
                        'cust_ac_no' => '00010012345001',
                        'iban_ac_no' => 'MT89123456677788888885668',
                        'account_desc' => ''
                    ],
                    [
                        'cust_ac_no' => '00010012345002',
                        'iban_ac_no' => 'MT89123456677788888885669',
                        'account_desc' => ''
                    ],
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    'data' => [
                        'currency' => 'EUR',
                        'opnbal' => 1,
                        'curbal' => 1,
                        'avlbal' => 1
                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            );
    }

    protected function mockBicAndBalance($isSEPA = false)
    {
        Http::fakeSequence( $this->apiUrl . '/*')
            ->push(
                [
                    'data' => [
                        [
                            'bic' => $isSEPA ? 'TEST' : 'PAUUMTM1XXX'
                        ]
                    ],
                    'status'  => 'success',
                    'code'    => Response::HTTP_OK
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    'data' => [
                        'currency' => 'EUR',
                        'opnbal' => 1,
                        'curbal' => 1,
                        'avlbal' => 1
                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    'data' => '2331901049656000',
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Transaction is submitted for processing.'
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            );
    }

    protected function mockAccountsBicAndBalanceWithLimit($isSEPA = false)
    {
        Http::fakeSequence( $this->apiUrl . '/*')
            ->push(
                [
                    'data' => [
                        [
                            'bic' => $isSEPA ? 'TEST' : 'PAUUMTM1XXX'
                        ]
                    ],
                    'status'  => 'success',
                    'code'    => Response::HTTP_OK
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    [
                        'cust_ac_no' => '00010012345001',
                        'iban_ac_no' => 'MT89123456677788888885668',
                        'account_desc' => ''
                    ],
                    [
                        'cust_ac_no' => '00010012345002',
                        'iban_ac_no' => 'MT89123456677788888885669',
                        'account_desc' => ''
                    ],
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    'data' => [
                        'currency' => 'EUR',
                        'opnbal' => 1,
                        'curbal' => 1,
                        'avlbal' => 1
                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )->push(
                [
                    'data' => [
                        'total_outgoing_amount' => '0.9'
                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )->push(
                [
                    'data' => [
                        'total_outgoing_amount' => '0.9'
                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            );
    }

    protected function mockBicValue()
    {
        Http::fake([
            $this->apiUrl . '/*' => Http::Response(
                [
                    'data' => [
                        [
                            'bic' => 'PAUUMTM1XXX'
                        ]
                    ],
                    'status'  => 'success',
                    'code'    => Response::HTTP_OK
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
        ]);
    }
    

    protected function mockTicketCreated()
    {
        Http::fake(
            [
                $this->baseUrl . '/*' => Http::Response(
                    [
                       "id" => '123555'
                    ],
                    Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json'
                    ]
                )
            ]
        );
    }

    protected function mockAccountsAndHistory()
    {
        Http::fakeSequence( $this->apiUrl . '/*')
            ->push(
                [
                    [
                        'cust_ac_no' => '00010012345001',
                        'iban_ac_no' => 'MT89123456677788888885668',
                        'account_desc' => ''
                    ],
                    [
                        'cust_ac_no' => '00010012345002',
                        'iban_ac_no' => 'MT89123456677788888885669',
                        'account_desc' => ''
                    ],
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    'data' => [
                        [
                            'transaction_id' =>  'TXN_REF_NO',
                            'transaction_uuid' =>  'TXN_REF_NO',
                            'reference_no' =>  'TXN_REF_NO',
                            'transfer_currency'  => 'EUR',
                            'service'           => 'INTERNAL',
                            'amount'              => '0.2',
                            'name'             => 'NAME',
                            'iban'              => 'IBAN',
                            'cr_iban'             =>'IBAN',
                            'cr_acno'             => 'IBAN',
                            'cr_name'             => 'IBAN',
                            'dr_iban'             => 'IBAN',
                            'dr_acno'             => 'IBAN',
                            'bic'                 => 'PAUUMTM1XXX',
                            'status'        => 'S',
                            'trn_type'    =>  'INTERNAL',
                            'description'          => 'REMARKS',
                            'transaction_datetime'   => '2023-03-01'
                        ]

                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            );
    }

    protected function mockAccountsAndStatement()
    {
        Http::fakeSequence( $this->apiUrl . '/*')
            ->push(
                [
                    [
                        'cust_ac_no' => '00010012345001',
                        'iban_ac_no' => 'MT89123456677788888885668',
                        'account_desc' => ''
                    ],
                    [
                        'cust_ac_no' => '00010012345002',
                        'iban_ac_no' => 'MT89123456677788888885669',
                        'account_desc' => ''
                    ],
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            )
            ->push(
                [
                    'data' => [
                        [
                            'transaction_ref_no' => '2107001462348000',
                            'transfer_currency' => 'EUR',
                            'debit' => 0,
                            'credit' => '5',
                            'opening_balance' => '0',
                            'closing_balance' => '21.09',
                            'balance' => '5.00',
                            'sender_receiver' => 'Paymentworld Europe Ltd  00010000395001',
                            'description' => 'Internal/Own Account Transactions|For testing Purpose   ',
                            'transaction_date' => '2021-03-12 00:00:00'
                        ]

                    ]
                ],
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json'
                ]
            );
    }

    protected function setAuthApi()
    {
        // $this->app['config']->set('auth.defaults.guard', 'api');
        // $this->app['config']->set('auth.guards.api.driver', 'token');
    }

    protected function user()
    {
        $user = User::create([
            'email' => 'test@gmail.com', 
            'password' => 'secret', 
            'customer_account_number' => '00001'
        ]);

        $role = Role::create(['name' => 'operations']);

        $user->roles()->sync($role->id);

        return $user;
    }

    protected function setMerchantProvider()
    {
        $this->app['config']->set('app.name', 'FinXP Ltd.');
    }

    protected function setPiqConfig()
    {
        $this->app['config']->set('piq.provider_merchant_id', 1);
        $this->app['config']->set('piq.merchants', explode(',', '2,3'));
    }
}
