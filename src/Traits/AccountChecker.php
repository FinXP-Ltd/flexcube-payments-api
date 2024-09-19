<?php

namespace Finxp\Flexcube\Traits;

use Illuminate\Http\Response;
use UnexpectedValueException;

trait AccountChecker
{
    protected static $PAYIN_MERCHANT = 'PAYIN_MERCHANT';
    protected static $PAYOUT_MERCHANT = 'PAYOUT_MERCHANT';
    protected static $PAYOUT_SEPACT = 'PAYOUT_SEPACT';
    protected static $PAYOUT_SEPAINST = 'PAYOUT_SEPAINST';
    protected static $SEPACT = 'SEPACT';
    protected static $SENDER_NOT_FOUND = 'Sender account not found.';
    protected static $MERCHANT_ACCOUNT_MODEL = 'flexcube-soap.providers.models.merchant_account';

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

    public function throwSenderError($sender)
    {
        if (empty($sender)) {
            throw new UnexpectedValueException(self::$SENDER_NOT_FOUND, Response::HTTP_NOT_FOUND);
        }
    }

}