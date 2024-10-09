<?php

namespace Finxp\Flexcube\Traits;

use Throwable;
use UnexpectedValueException;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\Validator;
use Finxp\Flexcube\Exceptions\TransferException;
use Finxp\Flexcube\Models\TransferLog;

trait PrepareTransfer
{
    const INTERNAL_TRANSFER = 'INTERNAL';
    const SEPACT_TRANSFER = 'SEPA CT';
    const SEPAINST_TRANSFER = 'SEPA INST';

    protected static $SENDER_NOT_FOUND = 'Sender account not found.';
    protected static $MERCHANT_ACCOUNT_MODEL = 'flexcube-soap.providers.models.merchant_account';

    const REQUIRED_STRING = 'required|string';

    public function checkMerchantAccountAndBalance($request)
    {
        $merchantAccountModel = app(config(self::$MERCHANT_ACCOUNT_MODEL));
        $sender = $merchantAccountModel::where('iban_number', $request['sender_iban'] ?? $request['debtor_iban'])
                    ->where('account_number', $request['account'])
                    ->where('merchant_id', $request->merchant->id);

        $this->throwSenderError($sender->exists());

        $response = $this->api->getAccountBalance( $sender->first()->account_number );

        if ( !$response ) {
            throw new UnexpectedValueException('Balance Check Error.', Response::HTTP_BAD_REQUEST);
            
        } else if($response['avlbal'] < $request['amount']) {
            throw new UnexpectedValueException('Insufficient Balance.', Response::HTTP_BAD_REQUEST);
        }

        return $response;
    }

    private function getError($sender, $recipient)
    {
        if($sender && $recipient) {
            throw new UnexpectedValueException('Sender and Recipient account not found.', Response::HTTP_NOT_FOUND);
        } else if($sender) {
            throw new UnexpectedValueException(self::$SENDER_NOT_FOUND, Response::HTTP_NOT_FOUND);
        } else if($recipient) {
            throw new UnexpectedValueException('Recipient account not found.', Response::HTTP_NOT_FOUND);
        }
    }

    private function throwSenderError($sender)
    {
        if (empty($sender)) {
            throw new UnexpectedValueException(self::$SENDER_NOT_FOUND, Response::HTTP_NOT_FOUND);
        }
    }

    protected function checkTransferType($request) 
    {
        $bic = $this->api->getBicValue(['iban' => $request['recipient_iban'] ?? $request['creditor_iban']]);
        $bic = json_decode(json_encode($bic));
        $has_bic_code = isset($bic?->data?->data[0]?->bic);
        $bic_code = null;
        $sepaInstEnabled = false;
        $transferType = null;

        if($has_bic_code) {
            $bic_code = $bic?->data?->data[0]?->bic;

            $sepaInst = $this->api->getSepaInstEnabled(['bic_code' => $bic_code]);
            $sepaInst = json_decode(json_encode($sepaInst));
            $sepaInstEnabled = isset($sepaInst?->data?->data[0]);

            if($bic_code === config('flexcube-soap.bic')) {
                $transferType = self::INTERNAL_TRANSFER;
            } else {
                $transferType = self::SEPACT_TRANSFER;
                $this->validateSEPATransfer($request->toArray());
            }
    
            if($sepaInstEnabled) {
                $transferType = self::SEPAINST_TRANSFER;
            }
        }

        return $transferType;
    }

    
    protected function validateSEPATransfer($request)
    {
        $data = [
            'creditor_name' => $request['recipient_name'] ?? $request['creditor_name']
        ];

        $validator = Validator::make($data, [
            'creditor_name' => self::REQUIRED_STRING,
        ]);

        if ($validator->fails()) {
            throw new TransferException($validator->messages(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }


    protected function callTransferApis($api, $type, $data, $transactionUuid)
    {
        $isSEPA = ($type === self::SEPACT_TRANSFER || $type === self::SEPAINST_TRANSFER);

        return  $isSEPA ? $this->directTransfer($api, $type, $data, $transactionUuid) : $api->internalTransfer( $data );
    }
    
    protected function directTransfer($api, $type, $data, $transactionUuid)
    {
        $referenceId = $data?->reference_id ?? $transactionUuid ?? null;

        $payload = [
            'reference_id' => $referenceId,
            'debtor_iban' => $data['sender_iban'] ?? $data['debtor_iban'],
            'debtor_name' => $data['sender_name'] ?? $data['debtor_name'] ?? null,
            'creditor_iban' => $data['recipient_iban'] ?? $data['creditor_iban'],
            'creditor_name' => $data['recipient_name'] ?? $data['creditor_name'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'remarks' => $data['remarks'],
        ];

        try {

            $response = ($type === self::SEPACT_TRANSFER) ? $api->sepaCTTransfer($payload) : $api->sepaINSTTransfer($payload);

        } catch (Throwable $e) {
            info($e);
            return $this->createFailedResponse(
                'Direct transfer error. ' . 'reference_id :' .  $referenceId,
                Response::HTTP_BAD_REQUEST
            );

        }

        return $response;
    }

    protected function saveTransferLogs($data, $response, $response_ref_no, $status, $transfer_type )
    {
        TransferLog::create([
            'payload' => json_encode($data),
            'status' => $status,
            'transfer_type' => $transfer_type,
            'transaction_ref_no' => $response_ref_no,
            'response' => json_encode($response),
        ]);
    }
    
}
