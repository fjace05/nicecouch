<?php namespace Jferguson\EloquentCouchdb\testing;

use \Illuminate\Support\Facades\Schema;
use Jferguson\Eloquent\Model as Eloquent;

class RDBMSBook extends Eloquent {

    protected $connection = 'sqlite';
	protected $table = 'books';
	protected static $unguarded = true;
	protected $primaryKey = 'title';

    public function author()
    {
        return $this->belongsTo('User', 'author_id');
    }

    /**
     * Check if we need to run the schema
     * @return [type] [description]
     */
    public static function executeSchema()
    {
        $schema = Schema::connection('sqlite');

        if (!$schema->hasTable('books'))
        {
            Schema::connection('sqlite')->create('books', function($table)
            {
                $table->string('title');
                $table->string('author_id');
                $table->timestamps();
            });
        }
    }

}
