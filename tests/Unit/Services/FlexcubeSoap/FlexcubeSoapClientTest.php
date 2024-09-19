<?php
namespace Finxp\Flexcube\Tests\Unit\Services\FlexcubeSoap;

use Finxp\Flexcube\Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

use Artisaninweb\SoapWrapper\SoapWrapper;
use Finxp\Flexcube\Services\FlexcubeSoap\Facade\FlexcubeSoapService;
use Finxp\Flexcube\Services\FlexcubeSoap\Client\BaseSoapClient;
use Finxp\Flexcube\Services\FlexcubeSoap\Client\FlexcubeSoapClient;

class FlexcubeClientTest extends TestCase
{
    /** @test */
    public function itShouldQueryCustByCustNo()
    {
        $params = array (
            'Customer-IO' => array(
                'CUSTNO' => '12345'
            )
        );

        $mockResponse = $this->mockQueryCustomerResponse();

        FlexcubeSoapService::shouldReceive('queryCustByNo')
            ->once()
            ->with($params)
            ->andReturn($mockResponse);

        $res = FlexcubeSoapService::queryCustByNo($params);

        $this->assertEquals(
            $mockResponse,
            $res
        );
    }

    /** @test */
    public function itShouldQueryCustAccDetailByNo()
    {
        $params = array (
            'Sttms-Customer-IO' => array(
                'CUSTNO' => '12345'
            )
        );

        $mockResponse = $this->mockQueryCustAccDetail();

        FlexcubeSoapService::shouldReceive('queryCustAccDetailByNo')
            ->once()
            ->with($params)
            ->andReturn($mockResponse);

        $res = FlexcubeSoapService::queryCustAccDetailByNo($params);

        $this->assertEquals(
            $mockResponse,
            $res
        );
    }

    /** @test */
    public function itShouldQueryCustBlkByAccNo()
    {
        $params = array (
            'Account-IO' => array(
                'ACCOUNT' => '00010012345001',
                'BRANCH' => '000'
            )
        );

        $mockResponse = $this->mockQueryCustBlk();

        FlexcubeSoapService::shouldReceive('queryCustBlkByNo')
            ->once()
            ->with($params)
            ->andReturn($mockResponse);

        $res = FlexcubeSoapService::queryCustBlkByNo($params);

        $this->assertEquals(
            $mockResponse,
            $res
        );
    }

    /** @test */
    public function itShouldQueryAccBalByAccNo()
    {
        $params = array (
            'ACC-Balance' => array(
                'ACC_BAL' => array(
                    'CUST_AC_NO' => '00010012345001',
                    'BRANCH_CODE' => '000'
                )
            )
        );

        $mockResponse = $this->mockQueryAccBalByAccNo();

        FlexcubeSoapService::shouldReceive('queryAccBalByAccNo')
            ->once()
            ->with($params)
            ->andReturn($mockResponse);

        $res = FlexcubeSoapService::queryAccBalByAccNo($params);

        $this->assertEquals(
            $mockResponse,
            $res
        );
    }

    /** @test */
    public function itShouldQueryAccSummByCustNo()
    {
        $params = array (
            'Stvw-Account-Sumary-IO' => array(
                'CUST_NO' => '12345'
            )
        );

        $mockResponse = $this->mockQueryAccSummByCustNo();

        FlexcubeSoapService::shouldReceive('queryAccSummByCustNo')
            ->once()
            ->with($params)
            ->andReturn($mockResponse);

        $res = FlexcubeSoapService::queryAccSummByCustNo($params);

        $this->assertEquals(
            $mockResponse,
            $res
        );
    }

    /** @test */
    public function itShouldQueryAcctBalByAccNo()
    {
        $params = array (
            'Custbal-IO' => array(
                'CUST_AC_NO' => '00010012345001',
                'BRHCODE' => '000'
            )
        );

        $mockResponse = $this->mockQueryAcctBalByAccNo();

        FlexcubeSoapService::shouldReceive('queryAcctBalByAccNo')
            ->once()
            ->with($params)
            ->andReturn($mockResponse);

        $res = FlexcubeSoapService::queryAcctBalByAccNo($params);

        $this->assertEquals(
            $mockResponse,
            $res
        );
    }

    /** @test */
    public function itShouldQueryInwardRemittance()
    {
        $params = array (
            'In-Rmt-Fetch-Full' => array(
                'CUSTOMER_NO' => '12345',
                'CR_AC_NO' => '00010012345001',
                'TXN_BRANCH' => '000',
                'NETWORK_CODE' => 'SEPACT',
                'TRANSFER_CCY' => 'EUR'
            )
        );

        $mockResponse = $this->mockInwardRemittance();

        FlexcubeSoapService::shouldReceive('queryInwardRemittanceFS')
            ->once()
            ->with($params)
            ->andReturn($mockResponse);

        $res = FlexcubeSoapService::queryInwardRemittanceFS($params);

        $this->assertEquals(
            $mockResponse,
            $res
        );
    }

    /** @test */
    public function itShouldQueryOutwardRemittance()
    {
        $params = array (
            'Out-Rmt-Fetch-Full' => array(
                'CUSTOMER_NO' => '12345',
                'DR_AC_NO' => '00010012345001',
                'TXN_BRANCH' => '000',
                'NETWORK_CODE' => 'SEPACT',
                'TRANSFER_CCY' => 'EUR'
            )
        );

        $mockResponse = $this->mockOutwardRemittance();

        FlexcubeSoapService::shouldReceive('queryOutwardRemittanceFS')
            ->once()
            ->with($params)
            ->andReturn($mockResponse);

        $res = FlexcubeSoapService::queryOutwardRemittanceFS($params);

        $this->assertEquals(
            $mockResponse,
            $res
        );
    }

    /** @test */
    public function itShouldQueryTransactionDetails()
    {
        $params = array(
            'Single-Txn-Query-Full' => array(
                'TXN_REF_NO' => '123456789',
                'PAYMENT_TYPE' => 'A',
                'PAYMENT_TXN_TYPE' => 'I'
            )
        );

        $mockResponse = $this->mockQuerySingleTransaction();

        FlexcubeSoapService::shouldReceive('queryFetchSingleTransaction')
            ->once()
            ->with($params)
            ->andReturn($mockResponse);

        $res = FlexcubeSoapService::queryFetchSingleTransaction($params);

        $this->assertEquals(
            $mockResponse,
            $res
        );
    }

    private function mockQueryHeaderResponse()
    {
        $headerVal = new \stdClass();
        $headerVal->SOURCE = 'TEST';
        $headerVal->UBSCOMP  = 'TEST';
        $headerVal->MSGID = '12345';
        $headerVal->BRANCH = '000';

        $fcubsHeader = new \stdClass;
        $fcubsHeader->FCUBS_HEADER = $headerVal;

        return $fcubsHeader;
    }

    private function mockQueryCustomerResponse()
    {

        $customerFullVal = new \stdClass();
        $customerFullVal->CUSTNO = '12345';
        $customerFullVal->CTYPE = 'C';
        $customerFullVal->NAME = 'TEST NAME';
        $customerFullVal->ADDRLN1 = '123 Test';
        $customerFullVal->ADDRLN3 = 'test';
        $customerFullVal->COUNTRY = 'MT';

        $bodyVal = new \stdClass();
        $bodyVal->{'Customer-Full'} = $customerFullVal;

        $bodyWrapper = new \stdClass();
        $bodyWrapper->FCUBS_BODY = $bodyVal;

        $header = $this->mockQueryHeaderResponse();
        $header->FCUBS_HEADER->OPERATION = 'QueryCustomer';

        return (object) array_merge((array)$header, (array)$bodyWrapper);
    }

    private function mockQueryCustAccDetail()
    {
        $customerNo = new \stdClass();
        $customerNo->CUSTNO = '12345';

        $customerFullVal = new \stdClass();
        $customerFullVal->ACCOUNT_TYPE = 'U';
        $customerFullVal->ACC_STATUS = 'NORM';
        $customerFullVal->ADDRESS1 = '123 Test';
        $customerFullVal->ADDRESS2 = 'Test Rd.';
        $customerFullVal->ADDRESS3 = 'Another Test';
        $customerFullVal->BRANCH_CODE = '000';
        $customerFullVal->CCY = 'EUR';
        $customerFullVal->CHECKER_DT_STAMP = '2020-12-01 13:56:28';
        $customerFullVal->CUST_AC_NO = '00010012345001';
        $customerFullVal->IBAN_AC_NO = 'MT89123456677788888885668';

        $bodyVal = new \stdClass();
        $bodyVal->{'Sttms-Customer-Full'} = $customerFullVal;

        $fullBody = (object) array_merge((array)$customerNo, (array)$bodyVal);

        $bodyWrapper = new \stdClass();
        $bodyWrapper->FCUBS_BODY = $fullBody;

        $header = $this->mockQueryHeaderResponse();
        $header->FCUBS_HEADER->OPERATION = 'QueryCustAccDetail';

        return (object) array_merge((array)$header, (array)$bodyWrapper);
    }

    private function mockQueryAccBalByAccNo()
    {
        $header = $this->mockQueryHeaderResponse();
        $header->FCUBS_HEADER->OPERATION = 'QueryAccBal';

        $accBalValues = new \stdClass();
        $accBalValues->BRANCH_CODE = '000';
        $accBalValues->CUST_AC_NO = '00010012345001';
        $accBalValues->CCY = 'EUR';
        $accBalValues->TRNDT = '2021-07-14';
        $accBalValues->OPNBAL = '7.12';
        $accBalValues->CURBAL = '7.12';
        $accBalValues->AVLBAL = '0';
        $accBalValues->UNCOLAMT = '0';
        $accBalValues->MTDTOVCR = '0';
        $accBalValues->MTDTOVDR = '0';

        $accBal = new \stdClass();
        $accBal->ACC_BAL = $accBalValues;

        $accBalWrapper = new \stdClass();
        $accBalWrapper->{'ACC-Balance'} = $accBal;

        $bodyWrapper = new \stdClass();
        $bodyWrapper->FCUBS_BODY = $accBalWrapper;

        return (object) array_merge((array)$header, (array)$bodyWrapper);
    }

    private function mockQueryAccSummByCustNo()
    {
        $header = $this->mockQueryHeaderResponse();
        $header->FCUBS_HEADER->OPERATION = 'QueryAccSumm';

        $customerNo = new \stdClass();
        $customerNo->CUSTNO = '12345';

        $accSummAValues = new \stdClass();
        $accSummAValues->CUSTACNO = '00010012345001';
        $accSummAValues->BRANCH_CODE = '000';
        $accSummAValues->CCY = 'EUR';
        $accSummAValues->CURRBAL = '10.09';
        $accSummAValues->ACCOUNT_TYPE = 'U';
        $accSummAValues->CUSTOMER_NAME = 'Test Name';
        $accSummAValues->AC_DESC = 'Test desc';
        $accSummAValues->ACCOUNT_CLASS = '0001';
        $accSummAValues->ACCLASSDESC = 'Test class desc';

        $accSummA = new \stdClass();
        $accSummA->{'Stvw-Account-Sumary--A'} = $accSummAValues;

        $accSummFullValues = (object) array_merge((array)$customerNo, (array)$accSummA);

        $accSummFull = new \stdClass();
        $accSummFull->{'Stvw-Account-Sumary-Full'} = $accSummFullValues;

        $bodyWrapper = new \stdClass();
        $bodyWrapper->FCUBS_BODY = $accSummFull;

        return (object) array_merge((array)$header, (array)$bodyWrapper);
    }

    private function mockQueryAcctBalByAccNo()
    {
        $header = $this->mockQueryHeaderResponse();
        $header->FCUBS_HEADER->OPERATION = 'QueryAcctBal';

        $custBalFullValues = new \stdClass();
        $custBalFullValues->CUST_AC_NO = '00010012345001';
        $custBalFullValues->AC_DESC = 'Test class desc';
        $custBalFullValues->CUSTOMER_NO = '12345';
        $custBalFullValues->CCY = 'EUR';
        $custBalFullValues->CURRBAL = '10.09';
        $custBalFullValues->AVLBAL = '0';
        $custBalFullValues->DORMANT = 'N';
        $custBalFullValues->ACSTATNCR = 'N';
        $custBalFullValues->NODEBIT = 'N';
        $custBalFullValues->ACSTATFRZN = 'N';
        $custBalFullValues->STATUS = 'NORMAL STATUS';
        $custBalFullValues->CUST_NAME = 'Test Name';
        $custBalFullValues->BRHCODE = '000';
        $custBalFullValues->PREV_DAY_BOOK_BAL = '7.12';
        $custBalFullValues->ACY_BLOCKED_AMOUNT1 = '0';
        $custBalFullValues->AVL_TOD = '0';
        $custBalFullValues->CURRENT_BALANCE = '7.12';
        $custBalFullValues->ACY_UNCOLLECTED = '0';
        $custBalFullValues->NET_BAL = '0';

        $custBalFull = new \stdClass();
        $custBalFull->{'Custbal-Full'} = $custBalFullValues;

        $bodyWrapper = new \stdClass();
        $bodyWrapper->FCUBS_BODY = $custBalFull;

        return (object) array_merge((array)$header, (array)$bodyWrapper);
    }

    private function mockQueryCustBlk()
    {
        $header = $this->mockQueryHeaderResponse();
        $header->FCUBS_HEADER->OPERATION = 'QueryBlk';

        $accountFullValues = new \stdClass();
        $accountFullValues->BRANCH = '000';
        $accountFullValues->ACCOUNT = '00010012345001';
        $accountFullValues->AC_DESC = 'Test desc';
        $accountFullValues->CCY = 'EUR';
        $accountFullValues->ACCOUNT_TYPE = 'U';

        $accountFull = new \stdClass();
        $accountFull->{'Account-Full'} = $accountFullValues;

        $bodyWrapper = new \stdClass();
        $bodyWrapper->FCUBS_BODY = $accountFull;

        return (object) array_merge((array)$header, (array)$bodyWrapper);
    }

    private function mockInwardRemittance()
    {
        $header = $this->mockQueryHeaderResponse();
        $header->FCUBS_HEADER->OPERATION = 'QueryFetchInwardRemittance';

        $trans = new \stdClass();
        $trans->ACTIVATION_DATE = '2020-10-02';
        $trans->BOOK_DATE = '2020-10-02';
        $trans->CR_AC_NO = '00010012345001';
        $trans->CUSTOMER_NO = '12345';
        $trans->DR_BANK_CODE = 'HYVEDEXXX';
        $trans->PAYMENT_TYPE = 'A';
        $trans->SOURCE_REF_NO = '2027601523841111';
        $trans->TRANSFER_AMT = '1';
        $trans->TRANSFER_CCY = 'EUR';
        $trans->TXN_REF_NO = '2027601152380000';
        $trans->TXN_STATUS = 'S';
        $trans->COUNTER_PARTY_NAME = 'Paymentworld Europe Ltd';

        $responseFull = new \stdClass();
        $responseFull->CUSTOMER_NO = '12345';
        $responseFull->NETWORK_CODE = 'TEST';
        $responseFull->TRANSFER_CCY = 'EUR';
        $responseFull->TXN_BRANCH = '000';
        $responseFull->CR_AC_NO = '00010012345001';
        $responseFull->{'In-Rmt-Query'} = $trans; // can be multiple

        $bodyWrapper = new \stdClass();
        $bodyWrapper->FCUBS_BODY = $responseFull;

        return (object) array_merge((array)$header, (array)$bodyWrapper);
    }

    private function mockOutwardRemittance()
    {
        $header = $this->mockQueryHeaderResponse();
        $header->FCUBS_HEADER->OPERATION = 'QueryFetchOutwardRemittance';

        // this can be an array of results
        $trans = new \stdClass();
        $trans->ACTIVATION_DATE = '2020-10-02';
        $trans->BOOK_DATE = '2020-10-02';
        $trans->DR_AC_NO = '00010012345001';
        $trans->CUSTOMER_NO = '12345';
        $trans->CR_BANK_CODE = 'HYVEDEXXX';
        $trans->PAYMENT_TYPE = 'A';
        $trans->SOURCE_REF_NO = '2027601523841111';
        $trans->TRANSFER_AMT = '1';
        $trans->TRANSFER_CCY = 'EUR';
        $trans->TXN_REF_NO = '2027601152380000';
        $trans->TXN_STATUS = 'S';
        $trans->SOURCE_CODE = 'INTERNETBANKING';
        $trans->COUNTER_PARTY_NAME = 'FirstTest Limited';

        $responseFull = new \stdClass();
        $responseFull->CUSTOMER_NO = '12345';
        $responseFull->NETWORK_CODE = 'TEST';
        $responseFull->TRANSFER_CCY = 'EUR';
        $responseFull->TXN_BRANCH = '000';
        $responseFull->DR_AC_NO = '00010012345001';
        $responseFull->{'Out-Rmt-Query'} = $trans; // can be multiple

        $bodyWrapper = new \stdClass();
        $bodyWrapper->FCUBS_BODY = $responseFull;

        return (object) array_merge((array)$header, (array)$bodyWrapper);
    }

    private function mockQuerySingleTransaction()
    {
        $header = $this->mockQueryHeaderResponse();
        $header->FCUBS_HEADER->OPERATION = 'QueryFetchSingleCommonTxn';
        $header->FCUBS_HEADER->MSGSTAT = 'SUCCESS';

        $data = new \stdClass();
        $data->TXN_DETAIL = "Full Details";
        $data->TXN_REF_NO = "123456789";
        $data->PAYMENT_TXN_TYPE = 'I';
        $data->PAYMENT_TYPE = 'A';

        $responseFull = new \stdClass();
        $responseFull->{'Single-Txn-Query-Full'} = $data;
        $bodyWrapper = new \stdClass();
        $bodyWrapper->FCUBS_BODY = $responseFull;

        return (object) array_merge((array)$header, (array)$bodyWrapper);
    }
}