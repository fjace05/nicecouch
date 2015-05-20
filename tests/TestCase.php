<?php namespace Jferguson\EloquentCouchdb\testing;


class TestCase extends Orchestra\Testbench\TestCase {

/**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders()
    {
        return [
            'Jferguson\EloquentCouchdb\CouchdbServiceProvider',
        ];
    }
    /**
     * Define environment setup.
     *
     * @param  Illuminate\Foundation\Application    $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // reset base path to point to our package's src directory
        //$app['path.base'] = __DIR__ . '/../src';
        $config = require 'config/database.php';
        $app['config']->set('database.default', 'nicecouch');
        $app['config']->set('database.connections.nicecouch', $config['connections']['nicecouch']);
        $app['config']->set('database.connections.sqlite', $config['connections']['sqlite']);
    }
    
    public function testNothing(){
      $this->assertTrue(true);
    }
  

}
