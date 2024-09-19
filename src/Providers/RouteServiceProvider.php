<?php

namespace Finxp\Flexcube\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

use Finxp\Flexcube\Models\TransactionPaymentUrl;


class RouteServiceProvider extends ServiceProvider
{

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        Route::bind(
            'userTransaction',
            fn ($value) => TransactionPaymentUrl::where([
                ['concluded', false],
                ['status', TransactionPaymentUrl::STATUS_PENDING]
            ])->findOrfail($value)
        );
    }
}
