<?php

namespace Finxp\Flexcube\Repositories\BankingAPI;

interface BankingAPIRepositoryInterface
{
    public function getAccounts( $customerNumber );

    public function getAccountBalance( $customerAccountNumber );

    public function getStatementAccountBalance( $customerAccountNumber );

    public function getTransactionDetails( $transactionRefNo, $type = 'I' );

    public function getAllTransactionHistory( $params = [] );

    public function getTransactions( $params = [] );

    public function getAllTransactions( $params = [] );

    public function internalTransfer( $params = [] );

    public function sepaCTTransfer( $params = [] );

    public function sepaINSTTransfer( $params = [] );

    public function getCustomerDetails( $customerNumber );
    
    public function getDispatchValue( $params = [] );
    
    public function getBicValue( $params = [] );
    
    public function getSepaInstEnabled( $params = [] );
    
}