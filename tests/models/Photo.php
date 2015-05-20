<?php namespace Jferguson\EloquentCouchdb\testing;

use Jferguson\EloquentCouchdb\Model as Eloquent;

class Photo extends Eloquent {

	protected $type = 'photos';
	protected static $unguarded = true;

    public function imageable()
    {
        return $this->morphTo();
    }

}
