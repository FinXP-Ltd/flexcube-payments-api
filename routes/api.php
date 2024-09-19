<?php

use Finxp\Flexcube\Http\Controllers\v1\AccountStatementController;
use Finxp\Flexcube\Http\Controllers\v1\InternalTransferController;
use Finxp\Flexcube\Http\Controllers\v1\MerchantAccountController;

Route::prefix(config('flexcube-soap.prefix'))
    ->middleware('api')
    ->group(function () {
        
        //Merchant Routes
        Route::middleware(['verify.fc-merchant'])
            ->group(function () {
                
                Route::get('/merchant/accounts', [MerchantAccountController::class, 'getMerchantAccounts'])
                    ->name('merchant.accounts.list');

                Route::get('/merchant/accounts/{uuid}/balance', [AccountStatementController::class, 'getMerchantAccountBalance'])
                    ->name('merchant.accounts.balance');
                    
                Route::get('/merchant/accounts/{uuid}/transactions', [AccountStatementController::class, 'getMerchantSingleTransactionDetails'])
                    ->name('merchant.account.specific');
                    
                Route::get('/merchant/accounts/{uuid}/history', [AccountStatementController::class, 'getAllMerchantAccountTransactionHistory'])
                    ->name('merchant.account.history');
                    
                Route::get('/merchant/accounts/{uuid}/statement', [AccountStatementController::class, 'getAllMerchantAccountStatementFromFlexcube'])
                    ->name('merchant.account.statement');

                Route::post('/merchant/process/transfer', [InternalTransferController::class, 'merchantTransfer'])
                    ->name('merchant.process.transfer');
            });
    });
