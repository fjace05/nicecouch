<?php namespace Jferguson\Eloquent;

use Jferguson\EloquentCouchdb\Query\Builder;
use Jferguson\EloquentCouchdb\Relations\BelongsTo;

abstract class Model extends \Illuminate\Database\Eloquent\Model {
    /**
     * Get a new query builder instance for the connection.
     *
     * @return Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();
        // Check the connection type to see if it one of the couch models.
        if ($connection instanceof \Jferguson\EloquentCouchdb\Connection)
        {
            return new Builder($connection, $connection->getPostProcessor());
        }

        //It's not, return a normal builder;
        return parent::newBaseQueryBuilder();
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param  string  $related
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relatinoships.
        if (is_null($relation))
        {
            list(, $caller) = debug_backtrace(false);
            $relation = $caller['function'];
        }
        // Check if it is a relation with an original model.
        if (!is_subclass_of($related, 'Jferguson\EloquentCouchdb\Model'))
        {
            return parent::belongsTo($related, $foreignKey, $otherKey, $relation);
        }
        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey))
        {
            $foreignKey = snake_case($relation).'_id';
        }
        $instance = new $related;
        // Once we have the foreign key names, we'll just create a new Eloquent query
        // for the related models and returns the relationship instance which will
        // actually be responsible for retrieving and hydrating every relations.
        $query = $instance->newQuery();
        $otherKey = $otherKey ?: $instance->getKeyName();
        return new BelongsTo($query, $this, $foreignKey, $otherKey, $relation);
    }


}
?>