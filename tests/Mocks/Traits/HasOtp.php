<?php

namespace Finxp\Flexcube\Tests\Mocks\Traits;

use Illuminate\Support\Carbon;

trait HasOtp
{
    protected static function bootHasOtp(): void
    {
        static::creating(function ($model) {
            do {
                $code = self::generateOtp();
                $condition = [
                    'code' => $code,
                    'identifier' => $model->identifier
                ];
            } while (self::where($condition)->exists());

            $model->code = self::generateOtp();
        });
    }

    protected static function generateOtp(): string
    {
        $characters = '0123456789';

        $length = strlen($characters);
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[rand($i < 1 ? 1 : 0, $length - 1)];
        }

        return $code;
    }

    /**
     * Get the date time the token will expire
     *
     * @return \Illuminate\Support\Carbon;
     */
    public function expiresAt(): Carbon
    {
        return Carbon::parse($this->created_at)
            ->addMinutes(config('sca.otp.expires') / 60);
    }

    /**
     * Determine if the token is valid
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->revoked || $this->expiresAt()
            ->isPast();
    }

    /**
     * Invalidate the token
     *
     * @return void
     */
    public function invalidate(): void
    {
        $this->update(['revoked' => true]);
    }

    /**
     * Alias for invalidating the token
     *
     * @return void
     */
    public function revoke(): void
    {
        $this->invalidate();
    }
}
