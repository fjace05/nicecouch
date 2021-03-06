<?php namespace Jferguson\EloquentCouchdb\testing;

use Jferguson\EloquentCouchdb\Model as Eloquent;

class Client extends Eloquent {

	protected $type = 'clients';
	protected static $unguarded = true;

	public function users()
	{
		return $this->belongsToMany('User');
	}

	public function photo()
    {
        return $this->morphOne('Photo', 'imageable');
    }

    public function addresses()
    {
        return $this->hasMany('Address', 'data.address_id', 'data.client_id');
    }
}
