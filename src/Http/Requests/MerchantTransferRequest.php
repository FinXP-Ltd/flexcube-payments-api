<?php

namespace Finxp\Flexcube\Http\Requests;

use Finxp\Flexcube\Rules\Iban;
use Finxp\Flexcube\Traits\CheckProvider;

use Illuminate\Foundation\Http\FormRequest;

class MerchantTransferRequest extends FormRequest
{
    const REQUIRED_STRING = 'required|string';

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account' => 'required|string',
            'amount' => 'required|numeric|min:0.01|decimal:0,2',
            'currency' => 'required|string|size:3|alpha',
            'sender_iban' => [
                'required',
                'string',
                new Iban
            ],
            'sender_name' => self::REQUIRED_STRING,
            'recipient_iban' => [
                'required',
                'string',
                'different:sender_iban',
                new Iban
            ],
            'recipient_name' => self::REQUIRED_STRING,
            'reference_id' => self::REQUIRED_STRING,
            'remarks' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'amount.decimal' => 'The amount should not exceed 2 decimal places.'
        ];
    }
}
