<?php

use Jferguson\EloquentCouchdb\Model as Eloquent;

class Group extends Eloquent {

	protected $type = 'groups';
	protected static $unguarded = true;

	public function users()
	{
		return $this->belongsToMany('User', null, 'groups', 'users');
	}
}
