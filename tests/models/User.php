<?php

use Jferguson\EloquentCouchdb\Model as Eloquent;


class User extends Eloquent   {

	protected $dates = ['birthday', 'entry.date'];
	protected static $unguarded = true;

	public function books()
    {
        return $this->hasMany('Book', 'author_id');
    }

    public function rdbmsBooks()
    {
        return $this->hasMany('RDBMSBook', 'author_id');
    }

    public function items()
    {
        return $this->hasMany('Item');
    }

    public function role()
    {
        return $this->hasOne('Role');
    }

    public function rdbmsRole()
    {
        return $this->hasOne('RDBMSRole');
    }

	public function clients()
	{
		return $this->belongsToMany('Client');
	}

    public function groups()
    {
        return $this->belongsToMany('Group', null, 'users', 'groups');
    }

    public function photos()
    {
        return $this->morphMany('Photo', 'imageable');
    }

    public function addresses()
    {
        return $this->embedsMany('Address');
    }

    public function father()
    {
        return $this->embedsOne('User');
    }

    protected function getDateFormat()
    {
        return 'l jS \of F Y h:i:s A';
    }
}
