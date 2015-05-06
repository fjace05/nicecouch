<?php

use \Illuminate\Support\Facades\Schema;
use Jferguson\Eloquent\Model as Eloquent;

class RDBMSUser extends Eloquent {

	protected $connection = 'sqlite';
    protected $table = 'users';
    protected static $unguarded = true;

    public function books()
    {
        return $this->hasMany('Book', 'author_id');
    }

    public function role()
    {
        return $this->hasOne('Role');
    }

    /**
     * Check if we need to run the schema
     * @return [type] [description]
     */
    public static function executeSchema()
    {
        $schema = Schema::connection('sqlite');

        if (!$schema->hasTable('users'))
        {
            Schema::connection('sqlite')->create('users', function($table)
            {
                $table->increments('id');
                $table->string('name');
                $table->timestamps();
            });
        }
    }

}
