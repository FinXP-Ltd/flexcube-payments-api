<?php

use Finxp\Flexcube\Http\Controllers\v1\TransferController;
use Finxp\Flexcube\Http\Controllers\v1\MerchantAccountController;

Route::prefix(config('flexcube-soap.prefix'))
    ->middleware('api')
    ->group(function () {
        
        //Merchant Routes
        Route::middleware(['verify.fc-merchant'])
            ->group(function () {
                
                Route::get('/merchant/accounts', [MerchantAccountController::class, 'getAccounts'])
                    ->name('merchant.accounts.list');

                Route::get('/merchant/accounts/{uuid}/balance', [MerchantAccountController::class, 'getBalance'])
                    ->name('merchant.accounts.balance');
                    
                Route::get('/merchant/accounts/{uuid}/transactions', [MerchantAccountController::class, 'getTransactionDetails'])
                    ->name('merchant.account.specific');
                    
                Route::get('/merchant/accounts/{uuid}/history', [MerchantAccountController::class, 'geTransactionHistory'])
                    ->name('merchant.account.history');
                    
                Route::get('/merchant/accounts/{uuid}/statement', [MerchantAccountController::class, 'getStatement'])
                    ->name('merchant.account.statement');

                Route::post('/merchant/process/transfer', [TransferController::class, 'processTransfer'])
                    ->name('merchant.process.transfer');
            });
    });
