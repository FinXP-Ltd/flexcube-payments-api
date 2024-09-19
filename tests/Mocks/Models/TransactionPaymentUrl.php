<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Finxp\Flexcube\Tests\Mocks\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;

class TransactionPaymentUrl extends Model
{
    use Uuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fc_transaction_payment_urls';

    /**
     * The attributes that are not assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type', 'sender_name', 'sender_iban', 'recipient_name', 'recipient_iban',
        'amount', 'currency', 'redirect_url', 'concluded'
    ];
}
