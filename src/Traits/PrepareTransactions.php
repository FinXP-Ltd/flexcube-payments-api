<?php

namespace Finxp\Flexcube\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

use Finxp\Flexcube\Models\InboundTransaction;
use Finxp\Flexcube\Resources\InboundTransactionResource;
use Finxp\Flexcube\Models\FCTransactions;
use Finxp\Flexcube\Resources\FCMerchantTransactionResources;
use Finxp\Flexcube\Resources\OutboundTransactionResource;
use Finxp\Flexcube\Resources\FCTransactionResources;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait PrepareTransactions
{
    protected $DAYS_MIN_LIMIT = 'flexcube-soap.transaction.days_min_limit';
    protected $DAYS_MAX_LIMIT = 'flexcube-soap.transaction.days_max_limit';

    public function allIndividualTransaction(Request $request, $account)
    {

        $isAmountSort = request()->input('sort') == 'amount' ?? false;
                
        $filters = [
            'name' => request()->input('name'),
            'iban' => request()->input('iban'),
            'bic' => request()->input('bic'),
            'reference_no' => request()->input('reference_no'),
            'min_amount' => request()->input('min_amount'),
            'max_amount' => request()->input('max_amount'),
            'from_date' => request()->input('from_date'),
            'to_date' => request()->input('to_date'),
            'status' => request()->input('status'),
        ];
        
        $params = [
            'customer_number' => $account->user->customer_account_number,
            'customer_ac_no' => $account->account_number,
            'from_date' => $request->from_date ? date('Y-m-d', strtotime($request->from_date)) : Carbon::now()->subMonths(6)->format('Y-m-d'),
            'to_date' => $request->to_date ? date('Y-m-d', strtotime($request->to_date)) : Carbon::now()->format('Y-m-d')
        ];

        $fcTransactions = FCTransactions::where('debtor_iban', $account->iban)->orWhere('creditor_iban', $account->iban)
                            ->filter($filters)->sortable();

        if( $isAmountSort && request()->input('direction')) {
            $fcTransactions = $fcTransactions->get()->values()->map(function ($trans) use ($account) {
                $trans->amount = $trans['debtor_iban'] == $account['iban'] ? $trans['credit'] : $trans['debit'];
                return $trans;
            })->sortBy('amount', SORT_REGULAR, request()->input('direction') == 'desc' ? true : false);
        }

        $allLocalTransactions = FCTransactionResources::customCollection(
            $account,
            $isAmountSort ? $fcTransactions : $fcTransactions->get()
        );
        
        $allTransactions = collect($this->api->getAllTransactions($params));
        
        $collection = $allLocalTransactions->map( function($transactions) use ($allTransactions){
            $transaction = $allTransactions->where('transaction_ref_no', $transactions->parent->reference_no)->first();
            
            return collect($transactions)->merge([
                'running_balance' => $transaction['balance'] ?? null
            ]);
        });

        if ($this->isPaginated()) {
            
            $page = request()->input('page');
            $limit = request()->input('limit');

            $currentItems = array_slice($collection->values()->toArray(), $limit * ($page - 1), $limit);

            $paginate = new LengthAwarePaginator(
                $currentItems,
                $collection->count(),
                $limit,
                $page
            );

            return $this->response(
                [
                    $paginate
                ]
            );
        }

        $items = $collection->values()->toArray();

        return $this->response($items);
    }

    public function allTransactionFromOracle(Request $request, $account, $filter, $accountType = 'individual')
    {
        $sortBy = request()->input('sort') ?? 'transaction_datetime';
        $sortBy = ($sortBy === 'date') ? 'transaction_datetime' : $sortBy;
        $sortDirection = request()->input('direction') ?? 'desc';
        $days = $filter['hasFilter'] ? config($this->DAYS_MIN_LIMIT, 30) : config($this->DAYS_MAX_LIMIT, 90);

        $params = [
            'customer_number' => ($accountType == 'merchant') ? $account->merchant->customer_account_number : $account->user->customer_account_number,
            'customer_ac_no' => $account->account_number,
            'from_date' => $request->from_date ? date('Y-m-d', strtotime($request->from_date)) : Carbon::now()->subDays($days)->format('Y-m-d'),
            'to_date' => $request->to_date ? date('Y-m-d', strtotime($request->to_date)) : Carbon::now()->format('Y-m-d')
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

        if (request()->has('group_date') && request()->input('group_date') === true) {

            $collection = $collection->groupBy(function($item) {
                return Carbon::parse($item['transaction_datetime'])->format('Y-m-d'); 
            })->map(function ($item, $key) {
                return [ 
                    'date' => Carbon::parse($key)->format('jS F Y'),
                    'total_amount' => number_format($item->sum('amount'), 2),
                    'data' => $item
                ]; 
            });

            if ($sortBy && $sortDirection && strtolower($sortBy) == 'amount') {
            
                $collection = $collection->sortBy(
                    'total_amount',
                    SORT_NATURAL|SORT_FLAG_CASE,
                    strtolower($sortDirection) === 'desc'
                );
            }
        }
            
        if ($this->isPaginated()) {
            
            $page = request()->input('page');
            $limit = request()->input('limit');

            $currentItems = array_slice($collection->values()->toArray(), $limit * ($page - 1), $limit);

            $paginate = new LengthAwarePaginator(
                $currentItems,
                $collection->count(),
                $limit,
                $page
            );

            return $this->response($paginate->toArray());
        }

        $items = $collection->values()->toArray();

        return $this->response([
            "data" => $items
        ]);
    }


    protected function filterTransactionForOracle()
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
            'status' => $this->getStatus(strtoupper(request()->input('status'))),
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

    private function getStatus($status)
    {
        switch($status) {
            case 'FAILED':
                $status = ['E', 'X', 'RE', 'RC', 'R'];
                break;
            case 'SUCCESS':
                $status = ['S', 'SUCCESS'];
                break;
            case 'PROCESSING':
                $status = ['F', 'P'];
                break;
            case 'CANCELED':
                $status = "'C'";
                break;
            default;
                $status = null;
                break;
        }

        return is_array($status) ? "'" . implode ( "', '", $status ) . "'" : $status;
    }

    public function allTransactionsFromFlexcube(Request $request, $customerNumber, $accountNumber)
    {
        $days = config($this->DAYS_MIN_LIMIT, 180);
        
        $params = [
            'customer_number' => $customerNumber,
            'customer_ac_no' => $accountNumber,
            'from_date' => $request->from_date ? date('Y-m-d', strtotime($request->from_date)) : Carbon::now()->subDays($days)->format('Y-m-d'),
            'to_date' => $request->to_date ? date('Y-m-d', strtotime($request->to_date)) : Carbon::now()->format('Y-m-d')
        ];

        return collect($this->api->getAllTransactions($params));
    }
}
