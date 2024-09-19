<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Finxp\Flexcube\Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    const STATUS_FAILED = 'FAILED';
    const STATUS_PENDING = 'PENDING';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_CANCELLED = 'CANCELLED';

    /**
     * The attributes that are not assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that will be used to search
     *
     * @var array
     */
    protected $searchable = [
        'id', 'type', 'amount', 'currency', 'status', 'transaction_date', 'transaction_time', 'concluded_date',
        'concluded_time', 'merchant.name', 'service.name', 'bin', 'country', 'website', 'created_at', 'reference_no'
    ];

    protected $sortable = [
        'id', 'created_at', 'amount', 'merchant.name', 'service.name',
        'status'
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            do {
                $uuid = (string) Str::uuid();
                $isUuidExists = self::where('uuid', $uuid)->exists();

            } while (! empty($isUuidExists));

            $model->uuid = $uuid;
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(
            'Finxp\Flexcube\Tests\Mocks\Models\Service',
            'service_id'
        );
    }

    public function paymentUrl() : HasOne
    {
        return $this->hasOne('Finxp\Flexcube\Models\TransactionPaymentUrl', 'id', 'transaction_payment_url_id');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return TransactionFactory::new();
    }
}
