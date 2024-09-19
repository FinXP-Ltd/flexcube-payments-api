<?php

namespace Finxp\Flexcube\Resources\BankingAPI;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountAllTransactionResource extends JsonResource
{
    public function toArray($request): array
    {
         return [
            'transaction_ref_no' => $this[ 'transaction_ref_no' ],
            'transfer_currency' => $this[ 'transfer_currency' ],
            'debit' => $this[ 'debit' ],
            'credit' => $this[ 'credit' ],
            'opening_balance' => $this[ 'opening_balance' ],
            'closing_balance' => $this[ 'closing_balance' ],
            'balance' => $this[ 'balance' ],
            'sender_receiver' => $this[ 'sender_receiver' ],
            'description' => $this[ 'description' ],
            'transaction_date' => $this[ 'transaction_date' ],
        ];
    }
}