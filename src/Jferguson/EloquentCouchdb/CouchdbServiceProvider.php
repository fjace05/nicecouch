<?php namespace Jferguson\EloquentCouchdb;

use Jferguson\EloquentCouchdb\Model;
use Illuminate\Support\ServiceProvider;

class CouchdbServiceProvider extends ServiceProvider {
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);
        Model::setEventDispatcher($this->app['events']);
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving('db', function($db)
        {
            $db->extend('couchdb', function($config)
            {
                return new Connection($config);
            });
        });
    }
}