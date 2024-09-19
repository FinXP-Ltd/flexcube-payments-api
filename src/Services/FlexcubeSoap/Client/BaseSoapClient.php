<?php
namespace Finxp\Flexcube\Services\FlexcubeSoap\Client;

use Artisaninweb\SoapWrapper\SoapWrapper;

abstract class BaseSoapClient
{
    /**
     * @var SoapWrapper
     */
    protected $soapWrapper;

    protected $source;

    protected $ubsComp;

    protected $userId;

    protected $branch;

    protected  $moduleId;

    const PMWEB_BASE_CONFIG = 'flexcube-soap.pmweb_base_url';

    public function __construct(SoapWrapper $soapWrapper)
    {
        $this->soapWrapper = $soapWrapper;
        $this->source = config('flexcube-soap.header.source');
        $this->ubsComp = config('flexcube-soap.header.ubscomp');
        $this->userId = config('flexcube-soap.header.userid');
        $this->branch = config('flexcube-soap.header.branch');
        $this->moduleId = config('flexcube-soap.header.moduleid');
    }

    public function queryCustomerService($operation, $method, $bodyParams = [], $outerWrapper = 'QUERYCUSTOMER_IOFS_REQ')
    {
        $wsdl = config('flexcube-soap.fcubs_base_url'). '/' .config('flexcube-soap.services.FCUBSCustomer');

        return $this->query($operation, $wsdl, $method, 'FCUBSCustomerService', $bodyParams, $outerWrapper);
    }

    public function queryAccService($operation, $method, $bodyParams = [], $outerWrapper = 'QUERYACCBAL_IOFS_REQ')
    {
        $wsdl = config('flexcube-soap.fcubs_base_url'). '/' .config('flexcube-soap.services.FCUBSAcc');

        return $this->query($operation, $wsdl, $method, 'FCUBSAccService', $bodyParams, $outerWrapper);
    }

    public function queryInwardRemittanceService($operation, $method, $bodyParams = [], $outerWrapper = 'CREATEFETCHINWARDREMITTANCE_FSFS_REQ')
    {
        $wsdl = config(self::PMWEB_BASE_CONFIG). '/' .config('flexcube-soap.services.FCUBSInwardRemittance');

        return $this->query($operation, $wsdl, $method, 'InwardRemittanceQueryService', $bodyParams, $outerWrapper);
    }

    public function queryOutwardRemittanceService($operation, $method, $bodyParams = [], $outerWrapper = 'CREATEFETCHOUTWARDREMITTANCE_FSFS_REQ')
    {
        $wsdl = config(self::PMWEB_BASE_CONFIG). '/' .config('flexcube-soap.services.FCUBSOutwardRemittance');

        return $this->query($operation, $wsdl, $method, 'OutwardRemittanceQueryService', $bodyParams, $outerWrapper);
    }

    public function pmSinglePayoutService($operation, $method, $bodyParams = [], $outerWrapper = 'CreatePMSinglePayOut_FSFS_REQ')
    {
        $wsdl = config(self::PMWEB_BASE_CONFIG). '/' .config('flexcube-soap.services.FCUBSPMSinglePayout');

        return $this->query($operation, $wsdl, $method, 'PMSinglePayOutService', $bodyParams, $outerWrapper);
    }

    public function pmAchOutService($operation, $method, $bodyParams = [], $outerWrapper = 'QUERYACHOUT_IOFS_REQ')
    {
        $wsdl = config(self::PMWEB_BASE_CONFIG). '/' .config('flexcube-soap.services.FCUBSPMAchOut');

        return $this->query($operation, $wsdl, $method, 'PMAchOutService', $bodyParams, $outerWrapper);
    }

    public function pmCommonSingleTxnQueryService($operation, $method, $bodyParams = [], $outerWrapper = 'CREATEFETCHSINGLECOMMONTXN_FSFS_REQ')
    {
        $wsdl = config(self::PMWEB_BASE_CONFIG). '/' .config('flexcube-soap.services.FCUBSPMCommonSingleTxn');

        return $this->query($operation, $wsdl, $method, 'CommonSingleTxnQueryService', $bodyParams, $outerWrapper);
    }

    private function query($operation, $wsdl, $method, $service, $bodyParams, $outerWrapper)
    {
        try {

            if (!$this->soapWrapper->has($operation)) {
                $this->soapWrapper->add($operation, function ($service)  use ($wsdl){
                    $service
                        ->wsdl($wsdl)
                        ->trace(true);
                });
            }

            return $this->soapWrapper->call($method, array(
                $outerWrapper => array(
                    'FCUBS_HEADER' => array(
                        'SOURCE' => $this->source,
                        'UBSCOMP'   => $this->ubsComp,
                        'USERID'    => $this->userId,
                        'BRANCH'    => $this->branch,
                        'MODULEID'  => $this->moduleId,
                        'SERVICE'   => $service,
                        'OPERATION' => $operation
                    ),
                    'FCUBS_BODY' => $bodyParams
                )
            ));
        } catch (\Exception $exception) {

            info($exception->getMessage());

            return [];
        }

        return [];
    }
}