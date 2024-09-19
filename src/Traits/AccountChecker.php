<?php

namespace Finxp\Flexcube\Traits;

use Illuminate\Http\Response;
use UnexpectedValueException;

trait AccountChecker
{
    use RetailAccount;

    protected static $PAYIN_MERCHANT = 'PAYIN_MERCHANT';
    protected static $PAYOUT_MERCHANT = 'PAYOUT_MERCHANT';
    protected static $PAYOUT_SEPACT = 'PAYOUT_SEPACT';
    protected static $PAYOUT_SEPAINST = 'PAYOUT_SEPAINST';
    protected static $SEPACT = 'SEPACT';
    protected static $SENDER_NOT_FOUND = 'Sender account not found.';
    protected static $ACCOUNT_MODEL = 'flexcube-soap.providers.models.retail_account';
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

    public function checkSenderAndRecipientAccount($request)
    {
        $accountModel = app(config(self::$ACCOUNT_MODEL));
        $merchantAccountModel = app(config(self::$MERCHANT_ACCOUNT_MODEL));

        if($request->type == self::$PAYOUT_MERCHANT) {

            $sender = $merchantAccountModel::where('iban_number', $request['sender_iban'] ?? $request['debtor_iban'])
                        ->where('merchant_id', $request->merchant->id)
                        ->exists();
            $recipient = $accountModel::where('iban',
                $request['recipient_iban'] ?? $request['creditor_iban'])->exists();

            $this->getError(empty($sender), empty($recipient));

        } else if ($request->type == self::$PAYIN_MERCHANT) {
            
            $sender = $accountModel::where('iban', $request['sender_iban'] ?? $request['debtor_iban'])->exists();
            $recipient = $merchantAccountModel::where('iban_number',
                $request['recipient_iban'] ?? $request['creditor_iban'])->exists();

            $this->getError(empty($sender), empty($recipient));
        } else if ($request->type == self::$PAYOUT_SEPACT ||
            $request->type == self::$PAYOUT_SEPAINST) {

            $sender = $merchantAccountModel::where('iban_number', $request['sender_iban'] ?? $request['debtor_iban'])
                ->where('merchant_id', $request->merchant->id)
                ->exists();
            $this->throwSenderError($sender);
        } else {
            $sender = $accountModel::where('iban', $request['sender_iban'] ?? $request['debtor_iban'])->exists();
            $this->throwSenderError($sender);
        }
    }

    private function getInitiatingId($request)
    { 
        $accountModel = app(config(self::$ACCOUNT_MODEL));
        $merchantAccountModel = app(config(self::$MERCHANT_ACCOUNT_MODEL));
        
        $iban = $request['sender_iban'] ?? $request['debtor_iban'];
        $response = auth()->user()->id ?? null;

        if ($request->type == self::$PAYOUT_MERCHANT || $request->type == self::$PAYOUT_SEPACT || $request->type == self::$PAYOUT_SEPAINST) {
            $account = $merchantAccountModel::where('iban_number', $iban)->firstOrFail();
            $response = $account->merchant_id;
            
        } else if($request->type == self::$PAYIN_MERCHANT) { 
            $account = $accountModel::where('iban', $iban)->firstOrFail();
            $response = $account->user_id;
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