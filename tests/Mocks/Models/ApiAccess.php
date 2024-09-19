<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Finxp\Flexcube\Database\Factories\ApiAccessFactory;

class ApiAccess extends Model
{
    use HasFactory;

    const STATUS_REVOKED = 1;
    const STATUS_ACTIVE = 0;

    CONST KEY_PREFIX = 'ap_';
    const SECRET_PREFIX = 'sk_';

    protected $table = 'api_access';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'secret', 'merchant_id', 'revoked'
    ];

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
        'revoked' => 'boolean'
    ];

    public function revokeToken(): void
    {
        $this->update([
            'revoked' => true
        ]);
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
        return ApiAccessFactory::new();
    }
}
