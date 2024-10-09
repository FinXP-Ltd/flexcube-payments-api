<?php
namespace Finxp\Flexcube;

use Illuminate\Support\ServiceProvider;
use Finxp\Flexcube\Http\Middlewares\VerifyMerchant;

class FlexcubePackageServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/flexcube-soap.php', 'flexcube-soap');

        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        
        $this->app->bind(
            'Finxp\Flexcube\Repositories\BankingAPI\BankingAPIRepositoryInterface',
            'Finxp\Flexcube\Repositories\BankingAPI\BankingAPIRepository'
        );
    }

    public function provides()
    {
        return ['flexcube'];
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->publishes([
                __DIR__ . '/../config/flexcube-soap.php' => config_path('flexcube-soap.php')
            ], 'config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations')
            ], 'migrations');
        }

        $this->loadTranslationsFrom(
            __DIR__ . '/../resources/lang',
            'flexcube'
        );

        $this->registerMiddlewares();
    }

    protected function registerMiddlewares(): void
    {
        $this->app['router']
            ->aliasMiddleware('verify.fc-merchant', VerifyMerchant::class);
    }
}
