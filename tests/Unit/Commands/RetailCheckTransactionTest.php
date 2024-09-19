<?php
namespace Finxp\Flexcube\Tests\Unit\Commands;

use Finxp\Flexcube\Tests\TestCase;
use Finxp\Flexcube\Services\BankingAPI\Facade\BankingAPIService;
use Finxp\Flexcube\Tests\Mocks\Models\RetailAccount;
use Finxp\Flexcube\Tests\Mocks\Models\Transaction;
use Finxp\Flexcube\Tests\Mocks\Models\User;

class RetailCheckTransactionTest extends TestCase
{
    public function testItShouldCallTheCommand()
    {
        $this->artisan('fc:retail-check-transactions')
            ->expectsOutput('Getting dispatch values of processing transactions')
            ->assertExitCode(0);
    }

    public function testItShouldUpdateToSuccessed()
    {
        $user = User::factory()->create();

        $account = RetailAccount::factory()->create([
            'user_id'        => $user->id,
            'account_number' => '00010012345003',
            'is_active'      => 1
        ]);

        $transaction = Transaction::factory()->create([
            'account' => $account->account_number,
            'reference_no' => '2027601152380000',
            'status' => Transaction::STATUS_PROCESSING,
            'concluded_date' => null,
            'concluded_time' => null,
        ]);

        BankingAPIService::shouldReceive('getTransactionDetails')
            ->once()
            ->withAnyArgs($transaction->reference_no)
            ->andReturn($this->mockSingTransactionReturn());

        $this->artisan('fc:retail-check-transactions')
            ->expectsOutput('Getting dispatch values of processing transactions')
            ->assertExitCode(0);

        $txn = Transaction::where('id', $transaction->id)->firstOrFail();

        $this->assertTrue($txn->status === Transaction::STATUS_SUCCESS);
    }

    public function testItShouldNotUpdateToSuccess()
    {
        $user = User::factory()->create();

        $account = RetailAccount::factory()->create([
            'user_id'        => $user->id,
            'account_number' => '000100123450031',
            'is_active'      => 1
        ]);

        $transaction = Transaction::factory()->create([
            'account' => $account->account_number,
            'reference_no' => '20276011523800002',
            'status' => Transaction::STATUS_PROCESSING,
            'concluded_date' => null,
            'concluded_time' => null,
        ]);

        BankingAPIService::shouldReceive('getTransactionDetails')
            ->once()
            ->withAnyArgs()
            ->andReturn($this->mockSingTransactionReturn());

        $this->artisan('fc:retail-check-transactions')
            ->expectsOutput('Getting dispatch values of processing transactions')
            ->assertExitCode(0);

        $txn = Transaction::where('id', $transaction->id)->firstOrFail();

        $this->assertTrue($txn->status === Transaction::STATUS_PROCESSING);
    }

    private function mockSingTransactionReturn()
    {
        return [
            'data' => [
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
            ]
        ];
    }
}
