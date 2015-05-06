<?php

use \Illuminate\Support\Facades\Schema;
use Jferguson\Eloquent\Model as Eloquent;

class RDBMSRole extends Eloquent {

    protected $connection = 'sqlite';
	protected $table = 'roles';
	protected static $unguarded = true;

    public function user()
    {
    	return $this->belongsTo('User');
    }

    public function rdbmsUser()
    {
    	return $this->belongsTo('RDBMSUser');
    }

    /**
     * Check if we need to run the schema
     * @return [type] [description]
     */
    public static function executeSchema()
    {
        $schema = Schema::connection('sqlite');

        if (!$schema->hasTable('roles'))
        {
            Schema::connection('sqlite')->create('roles', function($table)
            {
                $table->string('type');
                $table->string('user_id');
                $table->timestamps();
            });
        }
    }

}
