<?php namespace Jferguson\EloquentCouchdb\Relations;

class BelongsTo extends \Illuminate\Database\Eloquent\Relations\BelongsTo {
    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints)
        {
            // For belongs to relationships, which are essentially the inverse of has one
            // or has many relationships, we need to actually query on the primary key
            // of the related models matching on the foreign key that's on a parent.
            $this->query->where($this->otherKey, '=', $this->parent->{$this->foreignKey});
        }
    }

}