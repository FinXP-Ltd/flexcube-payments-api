<?php

namespace Finxp\Flexcube\Resources\BankingAPI;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionDetailsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'transaction_ref_no' => $this[ 'transaction_ref_no' ],
            'transaction_status' => $this[ 'transaction_status' ],
            'transfer_currency'  => $this[ 'transfer_currency' ],
            'transfer_amount'    => number_format( $this[ 'transfer_amount' ], 2 ),
            'user_ref_no'        => $this[ 'user_ref_no' ],
            'remarks'            => $this['remarks'],
            'creditor' => [
                'name'  => $this[ 'creditor' ][ 'name' ],
                'iban'  => $this[ 'creditor' ][ 'iban' ],
                'ac_no' => $this[ 'creditor' ][ 'ac_no' ]
            ],
            'debtor' => [
                'iban'     => $this[ 'debtor' ][ 'iban' ],
                'ac_no'    => $this[ 'debtor' ][ 'ac_no' ],
                'currency' => $this[ 'debtor' ][ 'currency' ] ?? '',
                'name'     => $this[ 'debtor' ][ 'name' ],
                'country'  => $this[ 'debtor' ][ 'country' ],
                'address1' => $this[ 'debtor' ][ 'address1' ],
                'address2' => $this[ 'debtor' ][ 'address2' ]
            ],
            'creditor_bank_code'   => $this[ 'creditor_bank_code' ],
            'debtor_bank_code'     => $this[ 'debtor_bank_code' ],
            'customer_no'          => $this[ 'customer_no' ],
            'instruction_date'     => $this[ 'instruction_date' ],
            'creditor_value_date'  => $this[ 'creditor_value_date'],
            'debtor_value_date'    => $this[ 'debtor_value_date' ],
            'org_instruction_date' => $this[ 'org_instruction_date' ],
            'end_to_end_id'        => $this[ 'end_to_end_id' ],
            'additional_details'   => $this[ 'additional_details']
        ];
    }
}