<?php

namespace Finxp\Flexcube\Traits\Support;

trait HasFilter
{
    use HasHelper;
    public function filterTransactionForOracle()
    {
        $hasFilter = false;
        $filters = [
            'name' => request()->input('name'),
            'iban' => request()->input('iban'),
            'bic' => request()->input('bic'),
            'service' => request()->input('service'),
            'reference_no' => request()->input('reference_no'),
            'detail' => request()->input('description'),
            'from_amount' => request()->input('min_amount'),
            'to_amount' => request()->input('max_amount'),
            'status' => $this->getFilterStatus(strtoupper(request()->input('status'))),
        ];
        
        foreach ($filters as $param => $val) {
            if ($val) {
                $hasFilter = true;
                break;
            }
        }

        return [
            'hasFilter' => $hasFilter,
            'filters' => $filters
        ];
    }
}