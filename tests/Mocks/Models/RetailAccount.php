<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Finxp\Flexcube\Traits\HasUuid;
use Finxp\Flexcube\Database\Factories\RetailAccountFactory;
use Finxp\Flexcube\Traits\HasAccountLimit;

class RetailAccount extends Model
{
    use HasFactory, HasUuid, HasAccountLimit;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', 'iban', 'bic', 'account_number', 'description', 'status', 'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];


    /**
     * Check if the user is in active state
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function user()
    {
        return $this->belongsTo(
            'Finxp\Flexcube\Tests\Mocks\Models\User',
            'user_id'
        );
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return RetailAccountFactory::new();
    }
}
