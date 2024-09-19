<?php
namespace Finxp\Flexcube\Tests\Unit\Models;

use Finxp\Flexcube\Tests\Mocks\Models\FCTransactions;
use Finxp\Flexcube\Tests\Mocks\Models\RetailAccount;
use Finxp\Flexcube\Tests\Mocks\Models\User;
use Finxp\Flexcube\Tests\TestCase;

class FCTransactionModelTest extends TestCase
{

    /** @test */
    public function itShouldHaveCreditorAccount()
    {
        $user = User::factory()->create();

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id
        ]);

        $fcTransaction = FCTransactions::factory()->create([
            'creditor_iban' => $account->iban
        ]);
        
        $this->assertEquals($fcTransaction->creditorAccount->iban, $account->iban);

    }

    /** @test */
    public function itShouldHaveDebtorAccount()
    {
        $user = User::factory()->create();

        $account = RetailAccount::factory()->create([
            'user_id' => $user->id
        ]);

        $fcTransaction = FCTransactions::factory()->create([
            'debtor_iban' => $account->iban
        ]);
        
        $this->assertEquals($fcTransaction->debtorAccount->iban, $account->iban);

    }
}
