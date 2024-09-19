<?php

namespace Finxp\Flexcube\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Model;

use Finxp\Flexcube\Tests\Mocks\Traits\Uuids;
use Finxp\Flexcube\Tests\Mocks\Traits\HasOtp;
use Finxp\Flexcube\Tests\Mocks\Contracts\TokenContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Finxp\Flexcube\Database\Factories\TokenFactory;

class Token extends Model implements TokenContract
{
    use Uuids, HasOtp, HasFactory;

    const TYPE_TRANSFER = 'transfer';
    const TYPE_TRUSTED  = 'trusted';
    const TYPE_LOGIN    = 'login';

    /**
     * The encrypted fields
     */
    protected static $ENCRYPTED_FIELDS = [
        'meta'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'otp_tokens';

    /**
     * The attributes that are not assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that are mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'identifier', 'code', 'type', 'revoked', 'meta'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'revoked' => 'boolean'
    ];

    /**
     * Token validation rules
     *
     * @return array
     */
    public static function rules(): array
    {
        return [
            'code'          => 'required|string|size:6',
            'identifier'    => 'required|string'
        ];
    }

    /**
     * Determine if token is a transfer type
     *
     * @return bool
     */
    public function isTransfer(): bool
    {
        return $this->type === self::TYPE_TRANSFER;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return TokenFactory::new();
    }
}
