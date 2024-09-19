<?php
namespace Finxp\Flexcube\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

use Finxp\Flexcube\Http\Controllers\Controller;
use Finxp\Flexcube\Traits\MerchantAccount;
use Finxp\Flexcube\Repositories\BankingAPI\BankingAPIRepositoryInterface;
use Finxp\Flexcube\Traits\PrepareTransactions;
use Illuminate\Auth\Access\AuthorizationException;

class AccountStatementController extends Controller
{
    use PrepareTransactions, MerchantAccount;

    const FAILED_MESSAGE = 'flexcube::response.general.failed';

    const NO_TRANSACTIONS_MESSAGE = 'flexcube::response.no_transactions';

    const REQUIRED_STRING = 'required|string';

    const UNAUTH_ERROR_MESSAGE = 'response.error.unauthorized';

    const REQUIRED_NUMERIC = 'required|numeric';

    const NULLABLE_DATE = 'nullable|date_format:Y-m-d';

    const MERCHANT_MODEL = 'flexcube-soap.providers.models.merchant';

    const MERCHANT_ACCOUNT_MODEL = 'flexcube-soap.providers.models.merchant_account';

    const UNAUTH_STRING = 'Unauthorized to make a request!';

    const REQUIRED_DATE = 'required|date_format:Y-m-d';

    const VALIDATION_TO_DATE = 'required_if:from_date,after_or_equal:from_date|nullable|date_format:Y-m-d|before_or_equal:today';

    const ACCOUNT_NOT_FOUND = 'flexcube::response.account_not_found';

    protected $api;

    public function __construct( BankingAPIRepositoryInterface $api )
    {
        $this->api      = $api;
    }

    public function getMerchantAccountBalance(Request $request)
    {
        $merchantAccountModel = app(config(self::MERCHANT_ACCOUNT_MODEL));
        $merchantAccount = $merchantAccountModel->where('uuid', $request->uuid)->firstOrFail();
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

        $response = $this->api->getAccountBalance($merchantAccount->account_number);

        if ( !$response ) {
            return $this->createFailedResponse(
                __( self::FAILED_MESSAGE ),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->createSuccessfulResponse( $response );
    }

    public function getAllMerchantAccountTransactionHistory(Request $request, $uuid)
    {
        $this->validate($request, [
            'from_date' => self::NULLABLE_DATE,
            'to_date' => self::VALIDATION_TO_DATE,
            'status' => [
                'nullable',
                Rule::in(['PROCESSING', 'SUCCESS', 'FAILED', 'CANCELED']),
            ]
        ]);

        $merchantAccountModel = app(config(self::MERCHANT_ACCOUNT_MODEL));
        $merchantAccount = $merchantAccountModel->where('uuid', $request->uuid)->firstOrFail();
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

        $account = $request->merchant->accounts()->where('uuid', $uuid)->first();

        return $this->allTransactionFromOracle($request, $account, $filter, 'merchant');
    }

    public function getAllMerchantAccountStatementFromFlexcube(Request $request, $uuid) {
        $this->validate($request, [
            'from_date' => self::REQUIRED_DATE,
            'to_date' => 'required|date_format:Y-m-d|before_or_equal:today|after_or_equal:from_date'
        ]);

        $merchantAccountModel = app(config(self::MERCHANT_ACCOUNT_MODEL));
        $merchantAccount = $merchantAccountModel->where('uuid', $request->uuid)->firstOrFail();
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

        $allTransactions = $this->allTransactionsFromFlexcube(
            $request,
            $request->merchant->customer_number,
            $merchantAccount->account_number
        );

        if (count($allTransactions) ===  0) {
            return response()->json(
                    [
                        'code' => Response::HTTP_OK,
                        'status' => 'success',
                        'message' => 
            __(self::NO_TRANSACTIONS_MESSAGE)
                    ]
            );
        }

        return $this->response([
            'data' => $allTransactions
        ]);
    }

    public function getMerchantSingleTransactionDetails(Request $request)
    {
        $this->validate(
            $request,
            [
                'transaction_ref_no' => self::REQUIRED_STRING
            ]
        );

        $transRefNo = $request->input('transaction_ref_no');

        $merchantAccountModel = app(config(self::MERCHANT_ACCOUNT_MODEL));
        $merchantAccount = $merchantAccountModel->where('uuid', $request->uuid)->firstOrFail();
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

        if (!$this->checkIfTransactionOwnedByMerchant($request->merchant, [$transactionDetails['creditor']['ac_no'], $transactionDetails['debtor']['ac_no']])) {
            throw new AuthorizationException(self::UNAUTH_STRING);
        }

        return $this->createSuccessfulResponse($transactionDetails);
    }

    private function checkIfTransactionOwnedByMerchant($merchant, $accountNumber)
    {
        $accounts = $merchant->accounts->pluck('account_number')->all();

        return array_intersect($accountNumber, $accounts) ? true : false;
    }
}
