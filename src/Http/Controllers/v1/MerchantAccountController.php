<?php

namespace Finxp\Flexcube\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

use Finxp\Flexcube\Http\Controllers\Controller;
use Finxp\Flexcube\Resources\MerchantAccountResource;
use Finxp\Flexcube\Repositories\BankingAPI\BankingAPIRepositoryInterface;
use Finxp\Flexcube\Traits\Support\HasPagination;
use Finxp\Flexcube\Traits\Support\HasFilter;
use Finxp\Flexcube\Traits\PrepareTransactions;
use Finxp\Flexcube\Traits\Support\HasHelper;

class MerchantAccountController extends Controller
{
    use HasPagination, PrepareTransactions, HasHelper, HasFilter;

    protected $api;
    protected $merchantAccountModel;
    
    //Models
    const MERCHANT_MODEL = 'flexcube-soap.providers.models.merchant';
    const MERCHANT_ACCOUNT_MODEL = 'flexcube-soap.providers.models.merchant_account';
    
    //Validation
    const REQUIRED_STRING = 'required|string';
    const NULLABLE_DATE = 'nullable|date_format:Y-m-d';
    const REQUIRED_DATE = 'required|date_format:Y-m-d';
    const VALIDATION_TO_DATE = 'required_if:from_date,after_or_equal:from_date|nullable|date_format:Y-m-d|before_or_equal:today';

    //Response
    const ACCOUNT_NOT_FOUND = 'flexcube::response.account_not_found';
    const FAILED_MESSAGE = 'flexcube::response.general.failed';
    const NO_TRANSACTIONS_MESSAGE = 'flexcube::response.no_transactions';

    public function __construct( BankingAPIRepositoryInterface $api )
    {
        //Initiate Banking API Repository
        $this->api = $api;
        //Initiate Mechant Account Model
        $this->merchantAccountModel = app(config(self::MERCHANT_ACCOUNT_MODEL));
    }

    /**
     * Get Merchant Accounts from Portal Database using merchant_accounts table
     *
     * @return array|pagination
     */
    public function getAccounts(Request $request)
    {
        /**
         * Get merchant from Middleware
         */
        $merchant = $request->merchant;
        $collection = $merchant->accounts;

        if ($this->isPaginated()) {
            
            return $this->paginate($collection);
        }
     
        return MerchantAccountResource::collection($collection);
    }

    /**
     * Get Merchant Specific Account Balance from Banking API
     * query path param: account uuid
     * @return array|pagination
     */
    public function getBalance(Request $request)
    {
        //Get Merchant Account base from account uuid
        $merchantAccount = $request->merchant->accounts()->where('uuid', $request->uuid)->firstOrFail();

        /**
         * Get Accounts From Flexcube using Banking API
         * param: CIF/Customer Number
         * @return array
         */
        $accounts = $this->api->getAccounts($request->merchant->customer_number);

        /**
         * Validate Mechant Account Exists
         * param:
         *    accounts
         *    account
         * @return JSON
         */
        if ($merchantAccount &&
            !$this->checkIfAccountExists(
                $accounts,
                $merchantAccount->account_number
            )
        ) {
            return $this->createFailedResponse(
                __(self::ACCOUNT_NOT_FOUND),
                Response::HTTP_NOT_FOUND
            );
        }

        /**
         * Get balance from Banking API
         * param:
         *    account
         * @return JSON
         */
        $response = $this->api->getAccountBalance($merchantAccount->account_number);

        if ( !$response ) {
            return $this->createFailedResponse(
                __( self::FAILED_MESSAGE ),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->createSuccessfulResponse( $response );
    }

    /**
     * Get Merchant Specific Account Transaction History from Banking API
     * query path param: account uuid
     * query param
     *   from_date
     *   to_date
     *   status
     * @return array|pagination
     */
    public function geTransactionHistory(Request $request)
    {
        $this->validate($request, [
            'from_date' => self::NULLABLE_DATE,
            'to_date' => self::VALIDATION_TO_DATE,
            'status' => [
                'nullable',
                Rule::in(['PROCESSING', 'SUCCESS', 'FAILED', 'CANCELED']),
            ]
        ]);

        $merchantAccount = $this->merchantAccountModel->where('uuid', $request->uuid)->firstOrFail();
        $accounts = $this->api->getAccounts($request->merchant->customer_number);

        if ($merchantAccount &&
            !$this->checkIfAccountExists(
                $accounts,
                $merchantAccount->account_number
            )
        ) {
            return $this->createFailedResponse(
                __(self::ACCOUNT_NOT_FOUND),
                Response::HTTP_NOT_FOUND
            );
        }

        $filter = $this->filterTransactionForOracle();

        return $this->transactionHistoryOracle($request, $merchantAccount, $filter);
    }
   
    public function getTransactionDetails(Request $request)
    {
        $this->validate(
            $request,
            [
                'transaction_ref_no' => self::REQUIRED_STRING
            ]
        );

        $transRefNo = $request->input('transaction_ref_no');

        $merchantAccount = $this->merchantAccountModel->where('uuid', $request->uuid)->firstOrFail();
        $accounts = $this->api->getAccounts($request->merchant->customer_number);

        if ($merchantAccount &&
            !$this->checkIfAccountExists(
                $accounts,
                $merchantAccount->account_number
            )
        ) {
            return $this->createFailedResponse(
                __(self::ACCOUNT_NOT_FOUND),
                Response::HTTP_NOT_FOUND
            );
        }

        $transactionDetails = $this->api->getTransactionDetails( $transRefNo, 'O' );

        if (!$transactionDetails) {
            return $this->createFailedResponse(
                __( self::FAILED_MESSAGE ),
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$this->checkTransactionOwner($request->merchant, [$transactionDetails['creditor']['ac_no'], $transactionDetails['debtor']['ac_no']])) {
            return $this->createFailedResponse(
                __('flexcube::response.general.unauthorize'),
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $this->createSuccessfulResponse($transactionDetails);
    }

    public function getStatement(Request $request) {
        $this->validate($request, [
            'from_date' => self::REQUIRED_DATE,
            'to_date' => 'required|date_format:Y-m-d|before_or_equal:today|after_or_equal:from_date'
        ]);

        $merchantAccount = $this->merchantAccountModel->where('uuid', $request->uuid)->firstOrFail();
        $accounts = $this->api->getAccounts($request->merchant->customer_number);

        if ($merchantAccount &&
            !$this->checkIfAccountExists(
                $accounts,
                $merchantAccount->account_number
            )
        ) {
            return $this->createFailedResponse(
                __(self::ACCOUNT_NOT_FOUND),
                Response::HTTP_NOT_FOUND
            );
        }

        $statement = $this->getStatementFlexcube(
            $request,
            $request->merchant->customer_number,
            $merchantAccount->account_number
        );

        if (count($statement) ===  0) {
            return response()->json(
                    [
                        'code' => Response::HTTP_OK,
                        'status' => 'success',
                        'message' => __(self::NO_TRANSACTIONS_MESSAGE)
                    ]
            );
        }

        return $this->response([
            'data' => $statement
        ]);
    }
}