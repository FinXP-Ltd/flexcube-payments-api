<?php

namespace Finxp\Flexcube\Tests\Mocks\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Notifications\Notification;

interface TokenContract
{
    /**
     * Get the date time the token will expire
     *
     * @return \Illuminate\Support\Carbon;
     */
    public function expiresAt(): Carbon;

    /**
     * Determine if the token is valid
     *
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * Invalidate the token
     *
     * @return void
     */
    public function invalidate(): void;

    /**
     * Alias for invalidating the token
     *
     * @return void
     */
    public function revoke(): void;
}
