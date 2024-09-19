<?php

namespace Finxp\Flexcube\Http\Controllers\v1;

use Illuminate\Http\Request;
use Finxp\Flexcube\Http\Controllers\Controller;
use Finxp\Flexcube\Resources\MerchantAccountResource;
use Finxp\Flexcube\Repositories\BankingAPI\BankingAPIRepositoryInterface;

class MerchantAccountController extends Controller
{
    protected $api;

    public function __construct( BankingAPIRepositoryInterface $api )
    {
        $this->api = $api;
    }

    public function getMerchantAccounts(Request $request)
    {
        $merchant = $request->merchant;
        
        $merchantAccounts = $merchant->accounts;
     
        return MerchantAccountResource::collection($merchantAccounts);
    }
}