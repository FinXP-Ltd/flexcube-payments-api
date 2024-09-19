<?php

namespace Finxp\Flexcube\Resources\BankingAPI;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountTransactionResource extends JsonResource
{
    public function toArray($request): array
    {
         return [
            'account_details' => $this[ 'account_details' ],
            'transactions'    => $this[ 'transactions' ],
            'date_from'       => $this[ 'date_from' ],
            'date_to'         => $this[ 'date_to' ],
        ];
    }
}