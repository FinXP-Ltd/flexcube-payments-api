<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Finxp\Flexcube\Database\Factories\MerchantAccountFactory;

class MerchantAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid', 'merchant_id', 'account_number', 'iban_number', 'is_notification_active', 'account_desc'
    ];

    /**
     * The attributes that are not assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(
            'Finxp\Flexcube\Tests\Mocks\Models\Merchant',
            'merchant_id'
        );
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return MerchantAccountFactory::new();
    }
}
