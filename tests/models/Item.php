<?php namespace Jferguson\EloquentCouchdb\testing;

use Jferguson\EloquentCouchdb\Model as Eloquent;

class Item extends Eloquent {

	protected $type = 'items';
	protected static $unguarded = true;

	public function user()
    {
        return $this->belongsTo('User');
    }

    public function scopeSharp($query)
    {
    	return $query->where('type', 'sharp');
    }

}
