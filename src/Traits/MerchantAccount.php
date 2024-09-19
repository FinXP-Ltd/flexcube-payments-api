<?php

namespace Finxp\Flexcube\Traits;

use DB;

trait MerchantAccount
{
    protected static $FAILURE_CODE = 'FAILURE';

    public function checkIfAccountExists( $merchantAccounts, $customerAcNumber )
    {
        if ($merchantAccounts) {

            $existingAccount = collect($merchantAccounts)->firstWhere('cust_ac_no', $customerAcNumber);
            
            return $existingAccount ? true : false;
        }

        return false;
    }

    public function createAccountsArr($accounts)
    {
        $accountArr = [];
        if (isset($accounts->FCUBS_BODY->{'Sttms-Customer-Full'}->{'Stvws-Stdaccqy'})) {
            $accNode = $accounts->FCUBS_BODY->{'Sttms-Customer-Full'}->{'Stvws-Stdaccqy'};

            if (is_array($accNode)) {
                $accountArr = collect($accNode)->map(function($account) {
                    return [
                        'cust_ac_no' => $account->CUST_AC_NO,
                        'iban_ac_no' => $account->IBAN_AC_NO,
                        'account_desc' => $account->AC_DESC ?? ''
                    ];
                })->toArray();
            } else {
                $accountArr[] = [
                    'cust_ac_no' => $accNode->CUST_AC_NO,
                    'iban_ac_no' => $accNode->IBAN_AC_NO,
                    'account_desc' => $accNode->AC_DESC ?? ''
                ];
            }
        }

        return $accountArr;
    }

    public function createAccountsData(array $accounts, $merchant = '')
    {
        if ($merchant) {
            return collect($accounts)->map(function($account) use ($merchant){
                $existingAccount = collect($merchant->accounts)->firstWhere('account_number', $account['cust_ac_no']);
                return [
                    'account_number' => $account['cust_ac_no'],
                    'iban_number' => $account['iban_ac_no'],
                    'account_desc' => $account['account_desc'] ?? '',
                    'is_notification_active' => $existingAccount ? $existingAccount->is_notification_active : 0
                ];
            })->toArray();
        } else {
            return collect($accounts)->map(function ($account) {
                return [
                    'account_number' => $account['cust_ac_no'],
                    'iban_number' => $account['iban_ac_no'],
                    'account_desc' => $account['account_desc'] ?? ''
                ];
            })->toArray();
        }
    }

    public function storeAccounts($merchant, array $accounts)
    {
        DB::beginTransaction();

        try {

            $data = $merchant->accounts()->createMany($accounts);

            DB::commit();
        } catch (\Throwable $e) {

            DB::rollback(); // rollback

            info($e->getMessage());

            return [];
        }

        return $data;
    }
}