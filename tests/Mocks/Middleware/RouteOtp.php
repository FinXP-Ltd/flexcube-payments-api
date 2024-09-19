<?php

namespace Finxp\Flexcube\Tests\Mocks\Middleware;

use Closure;

use Illuminate\Http\Request;

class RouteOtp extends BaseOtp
{
    public function handle(Request $request, Closure $next, $parameterKeyName)
    {
        $payload = [
            'code' => $request->input('code'),
            'identifier' => $request->route($parameterKeyName)
        ];

        $this->validateOtp($request, $payload);

        return $next($request);
    }
}
