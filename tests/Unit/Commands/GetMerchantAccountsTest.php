<?php
namespace Finxp\Flexcube\Tests\Unit\Commands;

use Finxp\Flexcube\Tests\TestCase;
use Finxp\Flexcube\Tests\Mocks\Models\MerchantAccount;
use Finxp\Flexcube\Tests\Mocks\Models\Merchant;
use Finxp\Flexcube\Services\FlexcubeSoap\Facade\FlexcubeSoapService;

class GetMerchantAccountsTest extends TestCase
{
    /** @test */
    public function itShouldCallTheCommand()
    {
        $this->artisan('fc:get-merchant-accounts')
            ->expectsOutput('Processing request..')
            ->assertExitCode(0);
    }

    /** @test */
    public function itShouldStoreAccountsIfEmptyOnLocal()
    {
        $merchant = Merchant::factory()
            ->create();

        $this->setMerchantProvider();

        $this->mockGetAccounts();

        $this->artisan('fc:get-merchant-accounts --merchant-id='. $merchant->id)
            ->expectsOutput('Processing request..')
            ->expectsOutput('Done: Successfully sync merchant accounts!')
            ->assertExitCode(1);

        $this->assertDatabaseHas('merchant_accounts', [
            'account_number' => '00010012345001',
            'merchant_id' => $merchant->id
        ]);
    }

    /** @test */
    public function itShouldUpdateAccountsListIfLocalNotEmpty()
    {

        $merchant = Merchant::factory()
            ->create();

        MerchantAccount::factory()
            ->create([
                'merchant_id' => $merchant->id,
                'is_notification_active' => 1,
                'iban_number' => 'MT89123456677788888885668',
                'account_number' => '00010012345001'
            ]);

        $this->setMerchantProvider();

        $this->mockGetAccounts();

        $this->artisan('fc:get-merchant-accounts --merchant-id='. $merchant->id)
            ->expectsOutput('Processing request..')
            ->expectsOutput('Done: Successfully sync merchant accounts!')
            ->assertExitCode(1);

        $this->assertDatabaseHas('merchant_accounts', [
            'account_number' => '00010012345001',
            'merchant_id' => $merchant->id,
            'is_notification_active' => 1
        ]);
    }
}
