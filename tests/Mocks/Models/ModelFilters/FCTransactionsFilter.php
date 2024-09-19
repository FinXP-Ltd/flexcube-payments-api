<?php

namespace Finxp\Flexcube\Tests\Mocks\Models\ModelFilters;

use EloquentFilter\ModelFilter;

class FCTransactionsFilter extends ModelFilter
{

    public function search($keyword)
    {
        return $this->where(function($query) use ($keyword) {
            $query->where('creditor_iban', $keyword)
                ->orWhereLike('debtor_iban', $keyword);
        })->orWhereHas('parent', function ($query) use ($keyword) {
            $query->where('id', $keyword)
                ->orWhereLike('uuid', $keyword)
                ->orWhereLike('reference_no', $keyword)
                ->orWhereLike('amount', $keyword)
                ->orWhereLike('status', $keyword)
                ->orWhereLike('transaction_date', $keyword)
                ->orWhereLike('transaction_time', $keyword);
        })->orWhereHas('creditorAccount', function ($query) use ($keyword) {
            $this->searchBicAndUser($query, $keyword);
        })->orWhereHas('debtorAccount', function ($query) use ($keyword) {
            $this->searchBicAndUser($query, $keyword);
        });
    }

    private function searchBicAndUser($query , $keyword)
    {
        return $query->where('bic', 'LIKE', "%" . $keyword . "%")
            ->orWhereHas('user', function ($query) use ($keyword) {
                $query->whereRaw('CONCAT_WS(" ", lower(trim(first_name)), lower(trim(last_name))) like "%' . strtolower($keyword) . '%"')
                        ->orWhereLike('first_name', $keyword)
                        ->orWhereLike('last_name', $keyword);
            });
    }
}
