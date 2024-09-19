<?php

namespace Finxp\Flexcube\Http\Controllers\v1;

use Exception;
use Illuminate\Http\Response;

use Finxp\Flexcube\Http\Controllers\Controller;
use Finxp\Flexcube\Http\Requests\MerchantTransferRequest;
use Finxp\Flexcube\Models\FCTransactions;
use Finxp\Flexcube\Repositories\BankingAPI\BankingAPIRepositoryInterface;
use Finxp\Flexcube\Traits\CheckProvider;
use Finxp\Flexcube\Traits\AccountChecker;
use Finxp\Flexcube\Exceptions\InternalTransferException;
use Illuminate\Support\Facades\Validator;
use Throwable;
use UnexpectedValueException;

class InternalTransferController extends Controller
{
    use AccountChecker;

    const SOURCE_CODE = 'INTERNETBANKING';
    const NETWORK_CODE = 'INTERNAL';
    const FAILURE_CODE = 'FAILURE';
    const HOUR_MINUTE = 'H:m:s';
    const SEPA = 'SEPA';
    const REQUIRED_STRING = 'required|string';
    const PROVIDER_ZAZOO = 'flexcube-soap.zazoo';
    const DB_SERVICE_ID = 'flexcube-soap.db_services.id';

    protected $api;
    protected $date;
    protected $time;
    protected $transactionModel;

    public function __construct(
        BankingAPIRepositoryInterface $api
    ) {
        $this->api = $api;
        $this->date = now()->format('Y-m-d');
        $this->time = now()->format(self::HOUR_MINUTE);
        $this->transactionModel = app(config('flexcube-soap.providers.models.transaction'));
    }


    public function merchantTransfer(MerchantTransferRequest $request)
    {
        
        $isFailed = false;

        $data = $request->only([
            'amount',
            'remarks',
            'currency',
            'debtor_iban',
            'sender_name',
            'creditor_iban',
            'recipient_name',
        ]);

        try {

            $type = $this->api->getBicValue(['iban' => $request['recipient_iban'] ?? $request['creditor_iban']]);

            $isInternal = $type['data']['data'][0]['bic'] == config('flexcube-soap.bic');

            $this->checkMerchantAccountAndBalance($request);

            $this->validateSEPATransfer($isInternal, $request->toArray());

            $initiateTransaction = $this->transactionModel::create([
                'type' =>  $isInternal ? 'INTERNAL' : 'SEPA',
                'account' => $request['account'],
                'amount' => $request['amount'],
                'currency' => $request['currency'],
                'service_id' => null,
                'status' => $this->transactionModel::STATUS_PENDING,
                'transaction_date' => $this->date,
                'transaction_time' => $this->time,
                'transaction_payment_url_id' => $request['transaction_payment_url_id'] ?? null,
                'external_transaction_id' => $request['reference_id'] ?? null,
                'initiating_party_id' => null,
                'provider' => $request->provider ?? null
            ]);

            $response = $this->callTransferApis($initiateTransaction, $data);
    
            $isFailed = (($response[ 'code' ] ===  Response::HTTP_BAD_REQUEST)
                            || ($response[ 'code' ] === Response::HTTP_INTERNAL_SERVER_ERROR)
                            || ($response[ 'code' ] === Response::HTTP_UNPROCESSABLE_ENTITY)
                            || ($response[ 'code' ] === Response::HTTP_UNAUTHORIZED));

            $status = $isFailed ? $this->transactionModel::STATUS_FAILED : $this->transactionModel::STATUS_PROCESSING;

            $response_ref_no = isset($response['data']['data']) ? $response['data']['data'] : null;

            $initiateTransaction->update([
                'status' =>  $status,
                'concluded_date' => $isFailed ? now()->format('Y-m-d') : null,
                'concluded_time' => $isFailed ? now()->format(self::HOUR_MINUTE) : null,
                'reference_no' => $response_ref_no,
                'is_concluded' => $isFailed
            ]);

            if ($isFailed) {
                throw new InternalTransferException($response['data']['message'] ?? 'Transfer failed.', $response['data']['code'] ?? Response::HTTP_BAD_REQUEST);
            }

            $res = [ 'transaction_uuid' => $initiateTransaction->uuid, 'transaction_ref_no' => $response_ref_no, 'reference_id' => $initiateTransaction->external_transaction_id ?? null];

        } catch (Throwable $e) {

            info($e);

            if($e instanceof UnexpectedValueException || $e instanceof InternalTransferException) {
                return $this->createFailedResponse(
                    $e->getMessage(),
                    $e->getCode()
                );
            }
            
            return $this->createFailedResponse(
                'Failed to process transfer.',
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->createSuccessfulResponse(
            $res,
            __('flexcube::response.transfer.success')
        );
    }

    private function callTransferApis($initiateTransaction, $data)
    {
        $type = $initiateTransaction->type;
        return $type == self::SEPA ? $this->directTransfer($type, $data, $initiateTransaction) : $this->api->internalTransfer( $data );
    }
    
    private function directTransfer($type, $data, $transaction)
    {
        $payload = [
            'reference_id' => $transaction->uuid,
            'debtor_iban' => $data['debtor_iban'],
            'debtor_name' => $data['sender_name'] ?? null,
            'creditor_iban' => $data['creditor_iban'],
            'creditor_name' => $data['recipient_name'] ?? $transaction->creditor_name,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'remarks' => $data['remarks'],
        ];

        try {

            $response = ($transaction->type === 'SEPA') ? $this->api->sepaCTTransfer($payload) : $this->api->sepaINSTTransfer($payload);

        } catch (Throwable $e) {
            info($e);
            return $this->createFailedResponse(
                'Direct transfer error. ' . 'reference_id :' .  $transaction->external_transaction_id,
                Response::HTTP_BAD_REQUEST
            );

        }

        return $response;
    }

    private function validateSEPATransfer($isInternal, $request)
    {
        if (!$isInternal) {

            $validator = Validator::make($request, [
                'creditor_name' => self::REQUIRED_STRING,
            ]);
    
            if ($validator->fails()) {
                throw new InternalTransferException($validator->messages(), Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
    }
}
