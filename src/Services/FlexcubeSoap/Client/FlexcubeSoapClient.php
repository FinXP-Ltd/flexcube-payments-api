<?php
namespace Finxp\Flexcube\Services\FlexcubeSoap\Client;

use Finxp\Flexcube\Services\FlexcubeSoap\Client\BaseSoapClient;

class FlexcubeSoapClient extends BaseSoapClient
{
    public function queryCustByNo($params)
    {
        return $this->queryCustomerService('QueryCustomer', 'QueryCustomer.QueryCustomerIO', $params);
    }

    public function queryCustAccDetailByNo($params)
    {
        return $this->queryCustomerService('QueryCustAccDetail', 'QueryCustAccDetail.QueryCustAccDetailIO', $params);
    }

    public function queryCustBlkByNo($params)
    {
        return $this->queryCustomerService('QueryBlk', 'QueryBlk.QueryBlkIO', $params);
    }

    public function queryAccBalByAccNo($params)
    {
        return $this->queryAccService('QueryAccBal', 'QueryAccBal.QueryAccBalIO', $params);
    }

    public function queryAccSummByCustNo($params)
    {
        return $this->queryAccService('QueryAccSumm', 'QueryAccSumm.QueryAccSummIO', $params);
    }

    public function queryAcctBalByAccNo($params)
    {
        return $this->queryAccService('QueryAcctBal', 'QueryAcctBal.QueryAcctBalIO', $params);
    }

    public function queryInwardRemittanceFS($params)
    {
        return $this->queryInwardRemittanceService('QueryFetchInwardRemittance', 'QueryFetchInwardRemittance.CreateFetchInwardRemittanceFS', $params);
    }

    public function queryOutwardRemittanceFS($params)
    {
        return $this->queryOutwardRemittanceService('QueryFetchOutwardRemittance', 'QueryFetchOutwardRemittance.CreateFetchOutwardRemittanceFS', $params);
    }

    public function createPMSinglePayout($params)
    {
        return $this->pmSinglePayoutService('CreatePMSinglePayOut', 'CreatePMSinglePayOut.CreatePMSinglePayOutFS', $params);
    }

    public function queryOutboundTransaction($params)
    {
        return $this->pmAchOutService('QueryAchOut', 'QueryAchOut.QueryAchOutIO', $params);
    }

    public function queryFetchSingleTransaction($params)
    {
        return $this->pmCommonSingleTxnQueryService('QueryFetchSingleCommonTxn', 'QueryFetchSingleCommonTxn.CreateFetchSingleCommonTxnFS', $params);
    }
}