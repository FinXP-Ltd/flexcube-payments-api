<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Finxp\Flexcube\Database\Factories\WebhookFactory;

class Webhook extends Model
{
    use HasFactory;

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /** Events */
    const EVENT_TRANSACTION_CREATED = 'transaction.created';
    const EVENT_TRANSACTION_UPDATED = 'transaction.updated';
    const EVENT_COT_APPLICATIONS_CREATED = 'cot.applications.created';
    const EVENT_COT_APPLICATIONS_UPDATED = 'cot.applications.updated';
    const EVENT_COT_TRANSFER_UPDATED = 'cot.transfer.updated';
    const EVENT_FC_TRANSACTION_RECEIVED = 'fc.transaction_received';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'url', 'event', 'merchant_id', 'is_active',
        'custom_headers', 'is_sandbox'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webhooks';

    /**
     * The attributes that are not assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    /**
     * Fields that should be casts to another type
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_sandbox' => 'boolean'
    ];

    public function getCustomHeadersAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setCustomHeadersAttribute($value)
    {
        $this->attributes['custom_headers'] = json_encode($value);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo('Finxp\Flexcube\Tests\Mocks\Models\Merchant');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return WebhookFactory::new();
    }
}
