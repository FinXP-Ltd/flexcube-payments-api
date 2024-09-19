<?php

namespace Finxp\Flexcube\Repositories\BankingAPI;

use Finxp\Flexcube\Resources\BankingAPI\AccountAllTransactionResource;
use Finxp\Flexcube\Resources\BankingAPI\AccountBalanceResource;
use Finxp\Flexcube\Resources\BankingAPI\AccountResource;
use Finxp\Flexcube\Resources\BankingAPI\CustomerResource;
use Finxp\Flexcube\Resources\BankingAPI\TransactionDetailsResource;
use Finxp\Flexcube\Resources\BankingAPI\AccountTransactionHistoryResource;
use Finxp\Flexcube\Services\BankingAPI\Facade\BankingAPIService;
use Finxp\Flexcube\Resources\BankingAPI\AccountTransactionResource;

class BankingAPIRepository implements BankingAPIRepositoryInterface
{
    public function getAccounts( $customerNumber )
    {
        try {

            $response = BankingAPIService::getAccounts( $customerNumber );
            return AccountResource::collection(
                $response[ 'data' ]
            )->resolve();
        } catch (\Throwable $e) {

            return [];
        }
    }

    public function getAccountBalance( $customerAccountNumber )
    {
        try {

            $response = BankingAPIService::getAccountBalance( $customerAccountNumber );
            return AccountBalanceResource::make( $response[ 'data' ][ 'data' ] )->resolve();

        } catch (\Throwable $e) {

            return [];
        }
    }

    public function getStatementAccountBalance( $customerAccountNumber )
    {
        try {

            $response = BankingAPIService::getStatementAccountBalance( $customerAccountNumber );
            return $response['data']['data'][0];

        } catch (\Throwable $e) {

            return [];
        }
    }

    public function getTransactionDetails( $transactionRefNo, $type = 'I' )
    {
        try {

            $response = BankingAPIService::getTransactionDetails( $type, $transactionRefNo );
            return TransactionDetailsResource::make( $response[ 'data' ][ 'data'] )->resolve();

        } catch (\Throwable $e) {

            return [];
        }
    }

    public function getAllTransactionHistory( $params = [] )
    {
        try {

            $response = BankingAPIService::getTransactionHistory( $params );
            
            return AccountTransactionHistoryResource::collection( $response[ 'data' ]['data'] )->resolve();

        } catch (\Throwable $e) {

            return [];
        }
    }

    public function getTransactions( $params = [] )
    {
        try {

            $response = BankingAPIService::getTransactions( $params );
            return AccountTransactionResource::make( $response[ 'data' ][ 'data' ] )->resolve();

        } catch (\Throwable $e) {

            return [];
        }
    }

    public function getAllTransactions( $params = [] )
    {
        try {

            $response = BankingAPIService::getAllTransactions( $params );
            return AccountAllTransactionResource::collection( $response[ 'data' ]['data'] )->resolve();

        } catch (\Throwable $e) {

            return [];
        }
    }

    public function getSumOutgoingAmount( $params = [] )
    {
        try {

            $response = BankingAPIService::getSumOutgoingAmount($params);
            
            return $response['data']['data']['total_outgoing_amount'];

        } catch (\Throwable $e) {

            return [];
        }
    }

    public function internalTransfer( $params = [] )
    {
        return BankingAPIService::internalTransfer( $params );
    }

    public function sepaINSTTransfer( $params = [] )
    {
        return BankingAPIService::sepaINSTTransfer( $params );
    }

    public function sepaCTTransfer( $params = [] )
    {
        return BankingAPIService::sepaCTTransfer( $params );
    }

    public function getCustomerDetails( $customerNo )
    {
        try {

            $response = BankingAPIService::getCustomerDetails( $customerNo );
            return CustomerResource::make( $response[ 'data' ][ 'data'] )->resolve();

        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getDispatchValue( $params = [] )
    {
        try {

            return BankingAPIService::getDispatchValue( $params );

        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getBicValue( $params = [] )
    {
        try {

            return BankingAPIService::getBicValue( $params );

        } catch (\Throwable $e) {
            return [];
        }
    }
}