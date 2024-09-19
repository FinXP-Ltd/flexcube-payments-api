<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Finxp\Flexcube\Database\Factories\AccountLimitSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class AccountLimitSetting extends Model
{
    use HasFactory;

    const TYPE_TRANSACTION = 'transaction';
    const TYPE_DAILY = 'daily';
    const TYPE_MONTHLY = 'monthly';

    protected $table = 'account_limit_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'limit', 'provider'
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
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return AccountLimitSettingFactory::new();
    }
}
