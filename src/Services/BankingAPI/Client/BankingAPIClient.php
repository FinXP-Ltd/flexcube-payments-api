<?php
namespace Finxp\Flexcube\Services\BankingAPI\Client;

use Finxp\Flexcube\Services\BankingAPI\Client\AbstractAPI;

class BankingAPIClient extends AbstractAPI
{
    public function getAccounts( $customerNumber )
    {
        return $this->client->request( 'GET', '/accounts', [
            'customer_number' => $customerNumber
        ]);
    }

    public function getStatementAccountBalance( $customerAccountNumber )
    {
        return $this->client->request( 'GET', '/accounts/statement-balance', [
            'customer_ac_no' => $customerAccountNumber
        ]);
    }

    public function getAccountBalance( $customerAccountNumber )
    {
        return $this->client->request( 'GET', '/accounts/balance', [
            'customer_ac_no' => $customerAccountNumber
        ]);
    }

    public function getTransactionDetails( $type, $transactionRefNo )
    {
        return $this->client->request( 'GET', '/accounts/single-transaction', [
            'transaction_ref_no' => $transactionRefNo,
            'type'               => $type
        ]);
    }

    public function getTransactions( $params = [] )
    {
        return $this->client->request( 'GET', '/accounts/inward-outward-transactions', $params );
    }
    
    public function getTransactionHistory( $params = [] )
    {
        return $this->client->request( 'POST', '/accounts/transactions/history', $params );
    }
    
    public function getAllTransactions( $params = [] )
    {
        return $this->client->request( 'GET', '/accounts/transactions', $params );
    }

    public function internalTransfer( $params = [] )
    {
        return $this->client->request( 'POST', '/payments/transfer/INT', $params );
    }

    public function sepaINSTTransfer( $params = [] )
    {
        return $this->client->request( 'POST', '/payments/transfer/INST', $params );
    }

    public function sepaCTTransfer( $params = [] )
    {
        return $this->client->request( 'POST', '/payments/transfer/CT', $params );
    }

    public function checkTransactionRefNo( $method = 'POST', $params = [] )
    {
        return $this->client->request( $method, '/payments/notifications', $params );
    }

    public function getCustomerDetails( $customerNumber )
    {
        return $this->client->request( 'GET', '/customers', [
            'customer_number' => $customerNumber
        ]);
    }
    
    public function getDispatchValue( $params = [] )
    {
        return $this->client->request( 'GET', '/accounts/dispatch-value', $params );
    }
    
    public function getBicValue( $params = [] )
    {
        return $this->client->request( 'GET', '/accounts/bic-value', $params );
    }
    
    public function getSepaInstEnabled( $params = [] )
    {
        return $this->client->request( 'GET', '/accounts/sepa-enabled', $params );
    }

    public function getSumOutgoingAmount($params)
    {
        return $this->client->request('POST', '/accounts/transactions/outgoing/amount', $params);
    }
}