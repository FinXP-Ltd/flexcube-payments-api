<?php

namespace Finxp\Flexcube\Traits;

use Finxp\Flexcube\Traits\Support\HasPagination;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

trait PrepareTransactions
{
    use HasPagination;

    protected $DAYS_MIN_LIMIT = 'flexcube-soap.transaction.days_min_limit';
    protected $DAYS_MAX_LIMIT = 'flexcube-soap.transaction.days_max_limit';

    public function transactionHistoryOracle(Request $request, $account, $filter)
    {
        $sortBy = request()->input('sort') ?? 'transaction_datetime';
        $sortDirection = request()->input('direction') ?? 'desc';
        $days = $filter['hasFilter'] ? config($this->DAYS_MIN_LIMIT, 30) : config($this->DAYS_MAX_LIMIT, 90);

        $params = [
            'customer_number' => $account->merchant->customer_account_number,
            'customer_ac_no' => $account->account_number,
            'from_date' => $request->from_date ? date('Y-m-d', strtotime($request->from_date)) : Carbon::now()->subDays($days)->format('Y-m-d'),
            'to_date' => $request->to_date ? Carbon::parse($request->to_date)->addDay()->format('Y-m-d') : Carbon::now()->addDay()->format('Y-m-d')
        ];

        if ($sortBy && $sortDirection) {
            $params['sort'] = $sortBy;
            $params['direction'] = $sortDirection;
        }

        $params = array_merge($params, $filter['filters']);
        
        $allTransactions = collect($this->api->getAllTransactionHistory($params));

        $collection = $allTransactions;

        if ($sortBy === 'iban' || $sortBy === 'name') {
            $collection = $collection->sortBy(
                $sortBy,
                SORT_NATURAL|SORT_FLAG_CASE,
                strtolower($sortDirection) === 'desc'
            );
        }
            
        if ($this->isPaginated()) {
            return $this->paginate($collection);
        }

        $items = $collection->values()->toArray();

        return $this->response([
            "data" => $items
        ]);
    }

    public function getStatementFlexcube(Request $request, $customerNumber, $accountNumber)
    {
        $days = config($this->DAYS_MIN_LIMIT, 180);
        
        $params = [
            'customer_number' => $customerNumber,
            'customer_ac_no' => $accountNumber,
            'from_date' => $request->from_date ? date('Y-m-d', strtotime($request->from_date)) : Carbon::now()->subDays($days)->format('Y-m-d'),
            'to_date' => $request->to_date ? Carbon::parse($request->to_date)->addDay()->format('Y-m-d') : Carbon::now()->addDay()->format('Y-m-d')
        ];

        return collect($this->api->getAllTransactions($params));
    }
}
