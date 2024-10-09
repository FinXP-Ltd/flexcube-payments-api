<?php

namespace Finxp\Flexcube\Resources\BankingAPI;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountTransactionHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
         return [
            'reference_no'          => $this['reference_no'],
            'service'               => $this['service'],
            'currency'              => 'EUR',
            'amount'                => $this['amount'],
            'name'                  => trim(preg_replace('/\s+/', ' ', $this['name'])),
            'iban'                  => $this['iban'],
            'bic'                   => $this['trn_type'] === 'INTERNAL' ? 'PAUUMTM1XXX': null,
            'status'                => $this->getStatus($this['status']),
            'description'           => $this['description'],
            'transaction_datetime'  => $this['transaction_datetime']
        ];
    }

    private function getStatus(string $status)
    {
        switch($status) {
            case 'E':
            case 'X':
            case 'RE':
            case 'RC':
            case 'R':
                $status = 'FAILED';
                break;
            case 'S':
            case 'SUCCESS':
                $status = 'SUCCESS';
                break;
            case 'F':
                $status = 'PROCESSING';
                break;
            case 'C':
                $status = 'CANCELED';
                break;
            default;
                $status = 'PROCESSING';
        }

        return $status;
    }
}