<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Finxp\Flexcube\Database\Factories\InboundTransactionDetailsFactory;

class InboundTransactionDetails extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fc_inbound_transaction_details';

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
     * @var array
     */
    protected $fillable = [
        'fc_inbound_transaction_id', 'transfer_currency', 'transfer_amount', 'user_ref_no', 'remarks',
        'creditor','debtor','creditor_bank_code','debtor_bank_code','customer_no','instruction_date',
        'creditor_value_date', 'debtor_value_date', 'org_instruction_date', 'end_to_end_id', 'additional_details'
    ];
    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return InboundTransactionDetailsFactory::new();
    }
}
