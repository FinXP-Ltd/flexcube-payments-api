<?php

namespace Finxp\Flexcube\Traits\Support;

trait HasHelper
{

    public function checkIfAccountExists( $merchantAccounts, $customerAcNumber )
    {
        if ($merchantAccounts) {

            $existingAccount = collect($merchantAccounts)->firstWhere('cust_ac_no', $customerAcNumber);
            
            return $existingAccount ? true : false;
        }

        return false;
    }
    
    public function checkTransactionOwner($merchant, $accountNumber)
    {
        $accounts = $merchant->accounts->pluck('account_number')->all();

        return array_intersect($accountNumber, $accounts) ? true : false;
    }
    
    public function getFilterStatus($status)
    {
        switch($status) {
            case 'FAILED':
                $status = ['E', 'X', 'RE', 'RC', 'R'];
                break;
            case 'SUCCESS':
                $status = ['S', 'SUCCESS'];
                break;
            case 'PROCESSING':
                $status = ['F', 'P'];
                break;
            case 'CANCELED':
                $status = "'C'";
                break;
            default;
                $status = null;
                break;
        }

        return is_array($status) ? "'" . implode ( "', '", $status ) . "'" : $status;
    }
}