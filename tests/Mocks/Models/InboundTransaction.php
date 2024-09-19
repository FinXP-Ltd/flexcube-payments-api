<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Finxp\Flexcube\Database\Factories\InboundTransactionFactory;

class InboundTransaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fc_inbound_transactions';

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
        'transaction_ref_no', 'initiating_party_id', 'initiating_party_type', 'initiating_party_account_id', 'initiating_party_type',
    ];

    public function detail(): HasOne
    {
        return $this->hasOne(
            'Finxp\Flexcube\Tests\Mocks\Models\InboundTransaction',
            'fc_inbound_transaction_id',
            'id'
        );
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return InboundTransactionFactory::new();
    }
}
