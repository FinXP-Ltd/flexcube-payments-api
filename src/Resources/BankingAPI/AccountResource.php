<?php

namespace Finxp\Flexcube\Resources\BankingAPI;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'cust_ac_no'   => $this[ 'cust_ac_no' ],
            'iban_ac_no'   => $this[ 'iban_ac_no' ],
            'account_desc' => $this[ 'account_desc' ]
        ];
    }
}