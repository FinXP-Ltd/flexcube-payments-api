<?php

namespace Finxp\Flexcube\Http\Middlewares;

use Closure;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VerifyMerchant
{
    public function handle(Request $request, Closure $next)
    {

        try {
            
            $model = app(config('flexcube-soap.providers.models.api'));
            $apiKeys = [
                'key' => $request->getUser(),
                'secret' => $request->getPassword(),
                'revoked' => false
            ];
    
            $access = $model->where($apiKeys)->first();
    
            if (!$access) {
                throw new AuthorizationException('Unauthorized to make a request!');
            }
    
            $merchant = $access->merchant;
    
            if (!$merchant->is_active) {
                throw new AuthorizationException('Unauthorized to make a request!');
            }
    
            if ($merchant->id == config('piq.provider_merchant_id')) {
            
                $merchantHeaderId = $request->header('X-merchant') ?? null;
    
                if (is_null($merchantHeaderId)) {
                    throw new AuthorizationException('Must provide merchant id!');
                }
    
                if (!in_array($merchantHeaderId, config('piq.merchants'))) {
                    throw new AuthorizationException('Not a valid PaymentIQ Merchant!');
                }
    
                $merchantModel =  app(config('flexcube-soap.providers.models.merchant'));
                $merchant = $merchantModel->where('id', $merchantHeaderId)->first();
    
                $request->request->set('merchant', $merchant);
                
            } else {
    
                $request->merge([
                    'merchant' => $access->merchant,
                ]);
    
            }
    
            if($request->has('provider')) {
                $request->request->set('debtor_iban', $request->sender_iban);
                $request->request->set('creditor_iban', $request->recipient_iban);
            }
    
            return $next($request);

        } catch (Exception $e) {

            info($e);
            
            if($e instanceof AuthorizationException) {
                return response()->json([
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'status' => 'failed',
                    'message' => $e->getMessage()
                ], Response::HTTP_UNAUTHORIZED);
            }
        }
    }
}

