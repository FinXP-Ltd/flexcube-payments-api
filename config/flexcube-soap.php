<?php

    return [
        'fcubs_base_url' => env('FCUBS_BASE_URL', 'http://10.11.5.3:5005'),
        'pmweb_base_url' => env('PMWEB_BASE_URL', 'http://10.11.5.3:5003'),

        'services' => [
            'FCUBSCustomer'          => env('FCUBSCustomer_WSDL', '/FCUBSCustomerService/FCUBSCustomerService?WSDL'),
            'FCUBSAcc'               => env('FCUBSAcc_WSDL', '/FCUBSAccService/FCUBSAccService?WSDL'),
            'FCUBSInwardRemittance'  => env('FCUBSInwardRemittance_WSDL', '/PMWeb/InwardRemittanceQueryService?WSDL'),
            'FCUBSOutwardRemittance' => env('FCUBSOutwardRemittance_WSDL', '/PMWeb/OutwardRemittanceQueryService?WSDL'),
            'FCUBSPMSinglePayout'    => env('FCUBSPMSinglePayout_WSDL', '/PMWeb/PMSinglePayOutService?WSDL'),
            'FCUBSPMAchOut'          => env('FCUBSPMAchOut_WSDL', '/PMWeb/PMAchOutService?WSDL'),
            'FCUBSPMCommonSingleTxn' => env('FCUBSPMCommonSingleTxn_WSDL', '/PMWeb/CommonSingleTxnQueryService?WSDL')
        ],

        'header' => [
            'source'   => env('FCUBSource', ''),
            'ubscomp'  => env('FCUBUbsComp', ''),
            'userid'   => env('FCUBUserId', ''),
            'branch'   => env('FCUBBranch', '000'),
            'moduleid' => env('FCUBModuleId', '')
        ],

        'providers' => [
            'models' => [
                'transaction'           => \App\Models\Common\Transaction::class,
                'merchant'              => \App\Models\Common\Merchant::class,
                'api'                   => \App\Models\Settings\ApiAccess::class,
                'merchant_account'      => \App\Models\Common\MerchantAccount::class,
            ],
        ],

        'mail' => [
            'paymentNotificationTo' => env('PAYMENT_NOTIFICATION_TO')
        ],

        'prefix' => 'api/fc',

        'webhook_header_keys' => [
            'branchCode'      => env('FCUBBranch', '000'),
            'appId'           => env('FCUBSAppId', 'SRVADAPTER'),
            'userId'          => '',
            'ECID-Context'    => ''
        ],

        'fc_app' => env('FC_FRONTEND_APP'),

        'banking_api'      => [
            'base_url'     => env('BANKING_API_BASE_URL', 'http://10.1.100.6:82/api/v1'),
            'credentials'  => [
                'username' => env('BANKING_API_AUTH_KEY', ''),
                'password' => env('BANKING_API_AUTH_SECRET', '')
            ]
        ],

        'enable_webpush_notification' => env('ENABLE_WEBPUSH_NOTIF', false),

        'general' => [
            'secret_key' => env('SIGNED_SECRET_KEY'),
            'signed_providers' => env('SIGNED_PROVIDERS')
        ],
        
        'payment_notification_url' => env('PAYMENT_NOTIFICATION_URL'),
    ];
