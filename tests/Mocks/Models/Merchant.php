<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Finxp\Flexcube\Database\Factories\MerchantFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Finxp\Flexcube\Traits\HasInboundTransaction;

class Merchant extends Model
{
    use HasPushSubscriptions, HasFactory, HasInboundTransaction;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'point_of_contact', 'street', 'unit_no',
        'city', 'country', 'postal', 'short_code', 'is_active',
        'slug', 'is_iso', 'skip_computation'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_iso' => 'boolean'
    ];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            'Finxp\Flexcube\Tests\Mocks\Models\Service',
            'merchant_has_services',
            'merchant_id',
            'service_id'
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            'Finxp\Flexcube\Tests\Mocks\Models\Transaction'
        );
    }

    public function apiAccess(): HasMany
    {
        return $this->hasMany(
            'Finxp\Flexcube\Tests\Mocks\Models\ApiAccess',
            'merchant_id',
            'id'
        );
    }

    public function getApiKeys()
    {
        $api = $this->apiAccess()->where('revoked', ApiAccess::STATUS_ACTIVE)
            ->first();

        if (!$api) {
            throw \Exception('error');
        }

        return (object) [
            'api' => $api->key,
            'secret' => $api->secret
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(
            'Finxp\Flexcube\Tests\Mocks\Models\MerchantAccount'
        );
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(
            'Finxp\Flexcube\Tests\Mocks\Models\User',
            'merchant_has_users',
            'merchant_id',
            'user_id'
        );
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(
            'Finxp\Flexcube\Tests\Mocks\Models\Webhook',
            'merchant_id',
            'id'
        );
    }

    public function pushSubscriptionBelongsToUser($subscription)
    {
        return true;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return MerchantFactory::new();
    }
}
