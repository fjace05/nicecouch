<?php namespace Jferguson\EloquentCouchdb\testing;

use Jferguson\EloquentCouchdb\Model as Eloquent;

class Book extends Eloquent {

	protected $type = 'books';
	protected static $unguarded = true;
	protected $primaryKey = '_id';

    public function author()
    {
        return $this->belongsTo('User', 'author_id');
    }

    public function rdbmsAuthor()
    {
        return $this->belongsTo('RDBMSUser', 'author_id');
    }
}
