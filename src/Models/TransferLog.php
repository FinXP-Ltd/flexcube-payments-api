<?php
namespace Finxp\Flexcube\Models;

use Illuminate\Database\Eloquent\Model;

class TransferLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transfer_logs';

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
        'payload', 'status', 'transaction_ref_no', 'response', 'transfer_type'
    ];
}
