<?php

namespace Finxp\Flexcube\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountStatementRequest extends FormRequest
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
            'currency' => self::REQUIRED_STRING.'|size:3|alpha',
            'customer_ac_no' => self::REQUIRED_STRING,
            'from_date' => 'sometimes|date_format:Y-m-d',
            'to_date' => 'sometimes|date_format:Y-m-d'
        ];
    }
}