<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Finxp\Flexcube\Traits\Uuids;
use Finxp\Flexcube\Database\Factories\RetailUserFactory;
use Finxp\Flexcube\Traits\HasInboundTransaction;

class RetailUser extends Authenticatable
{
    use HasInboundTransaction, HasFactory, Uuids;

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email', 'password', 'title', 'first_name', 'last_name',
        'mobile', 'dob', 'address_line_1', 'address_line_2', 'town',
        'state', 'country', 'postal_code', 'is_active', 'last_ip',
        'last_logged_in', 'password_updated', 'email_verified_at'
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
    */
    protected static function newFactory()
    {
        return RetailUserFactory::new();
    }
}