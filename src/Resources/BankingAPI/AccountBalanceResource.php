<?php

namespace Finxp\Flexcube\Resources\BankingAPI;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountBalanceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'currency' => $this[ 'currency' ],
            'opnbal'   => number_format( $this[ 'opnbal' ], 2 ),
            'curbal'   => number_format( $this[ 'curbal' ], 2 ),
            'avlbal'   => number_format( $this[ 'avlbal' ], 2 )
        ];
    }
}