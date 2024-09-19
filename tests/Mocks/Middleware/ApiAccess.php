<?php

namespace Finxp\Flexcube\Tests\Mocks\Middleware;

use Closure;

use Illuminate\Http\Request;

use Finxp\Flexcube\Tests\Mocks\Models\Merchant;

class ApiAccess
{
    public function handle(Request $request, Closure $next)
    {
        $merchant = Merchant::factory()->create();

        $request->request->set('merchant', $merchant);

        return $next($request);
    }
}
