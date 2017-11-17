<?php

namespace Acacha\ForgePublish\Providers;

use Acacha\ForgePublish\Commands\PublishInit;
use Acacha\ForgePublish\Commands\PublishLogin;
use Acacha\ForgePublish\Commands\PublishPush;
use Illuminate\Support\ServiceProvider;

/**
 * Class AcachaForgePublishServiceProvider.
 */
class AcachaForgePublishServiceProvider extends ServiceProvider
{

    public function register()
    {
        if (!defined('ACACHA_FORGE_PUBLISH_PATH')) {
            define('ACACHA_FORGE_PUBLISH_PATH', realpath(__DIR__.'/../../'));
        }

        $this->mergeConfigFrom(
            ACACHA_FORGE_PUBLISH_PATH.'/config/forge-publish.php', 'forge-publish'
        );
    }

    /**
     * Boot
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishInit::class,
                PublishPush::class,
                PublishLogin::class,
            ]);
        }
        
        $this->publishConfig();
    }

    protected function publishConfig()
    {
        $this->publishes([
            ACACHA_FORGE_PUBLISH_PATH .'/config/forge-publish.php' => config_path('forge-publish.php'),
        ]);
    }

}