<?php

use Jferguson\EloquentCouchdb\Model as Eloquent;

class Role extends Eloquent {

	protected $type = 'roles';
	protected static $unguarded = true;

    public function user()
    {
    	return $this->belongsTo('User');
    }

    public function rdbmsUser()
    {
    	return $this->belongsTo('RDBMSUser');
    }

}
