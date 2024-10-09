<?php

namespace Finxp\Flexcube\Http\Controllers\v1;

use Finxp\Flexcube\Exceptions\TransferException;
use Throwable;
use UnexpectedValueException;

use Illuminate\Http\Response;
use Finxp\Flexcube\Traits\PrepareTransfer;
use Finxp\Flexcube\Http\Controllers\Controller;
use Finxp\Flexcube\Http\Requests\MerchantTransferRequest;
use Finxp\Flexcube\Repositories\BankingAPI\BankingAPIRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    use PrepareTransfer;

    const HOUR_MINUTE = 'H:m:s';

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


    public function processTransfer(MerchantTransferRequest $request)
    {
        $isFailed = false;

        $data = $request->only([
            'account',
            'amount',
            'currency',
            'sender_iban',
            'sender_name',
            'recipient_iban',
            'recipient_name',
            'reference_id',
            'remarks',
            'debtor_iban',
            'creditor_iban'
        ]);

        DB::beginTransaction();

        try {

            $this->checkMerchantAccountAndBalance($request);

            $type = $this->checkTransferType($request);

            if(!$type) {
                throw new TransferException('Transfer Type Failed.');
            }

            $initiateTransaction = $this->transactionModel::create([
                'type' =>  $type,
                'account' => $request['account'],
                'amount' => $request['amount'],
                'currency' => $request['currency'],
                'status' => $this->transactionModel::STATUS_PENDING,
                'transaction_date' => $this->date,
                'transaction_time' => $this->time,
                'external_transaction_id' => $request?->reference_id ?? null,
                'provider' => $request->provider ?? null
            ]);

            if($request?->reference_id) {
                $data['end_to_end_id'] = [$request?->reference_id];
            }

            $response = $this->callTransferApis($this->api, $type, $data, $initiateTransaction->uuid);

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

            $this->saveTransferLogs($data, $response, $response_ref_no, $status, $type);

            if ($isFailed) {
                throw new TransferException($response['data']['message'] ?? 'Transfer failed.', $response['data']['code'] ?? Response::HTTP_BAD_REQUEST);
            }

            $res = ['transaction_ref_no' => $response_ref_no, 'reference_id' => $request?->reference_id ?? null];

            DB::commit();
        } catch (Throwable $e) {

            DB::rollBack();
            info($e);

            if($e instanceof UnexpectedValueException || $e instanceof TransferException) {
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
}
