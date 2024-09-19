<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Finxp\Flexcube\Database\Factories\AccountLimitLogsFactory;
use Finxp\Flexcube\Tests\Mocks\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountLimitLog extends Model
{
    use HasFactory, HasUuid;
    const TYPE_OPERATIONS = 'operations';
    const TYPE_USER = 'user';
    const LEVEL_SETTING = 'settings';
    const LEVEL_USER = 'user';

    protected $table = 'account_limit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'requestor_id', 'settings_id', 'account_id', 'account_limit_id', 'level', 'type', 'old_value', 'new_value'
    ];

    /**
     * The attributes that are not assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    public function modelFilter()
    {
        return $this->provideFilter(CoreRetailAccountLimitLogFilter::class);
    }

    protected static function newFactory()
    {
        return AccountLimitLogsFactory::new();
    }
}
