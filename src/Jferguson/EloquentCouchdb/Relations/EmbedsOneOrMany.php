<?php namespace Jferguson\EloquentCouchdb\Relations;

/**
 * Modified from jenssegers \laravel-mongodb\Relations\EmbedsOneOrMany.php
 */

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as BaseCollection;
use Jferguson\EloquentCouchdb\Eloquent\Collection;

abstract class EmbedsOneOrMany extends Relation
{
    /**
     * The local key of the parent model.
     *
     * @var string
     */
    protected $localKey;
    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;
    /**
     * The "name" of the relationship.
     *
     * @var string
     */
    protected $relation;
    /**
     * Create a new embeds many relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model    $parent
     * @param  string  $localKey
     * @param  string  $foreignKey
     * @param  string  $relation
     * @return void
     */
    public function __construct(Builder $query, Model $parent, Model $related, $localKey, $foreignKey, $relation)
    {
        /*
         * The call to the Relation constructor is inadvisable as
         * it will try to set the related model based on the $query parameter.
         * Which won't work for embeded models.
         */
        //parent::__construct($query, $parent);
        $this->query = $query;
        $this->parent = $parent;
        $this->related = $related;
        //Keys for related models are irrelevant for now.
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;
        $this->relation = $relation;
        // If this is a nested relation, we need to get the parent query instead.
        if ($parentRelation = $this->getParentRelation())
        {
            $this->query = $parentRelation->getQuery();
        }
        $this->addConstraints();
    }
    /**
     * Set the base constraints on the relation query.
     *
     * Hokay, we really need this to pull the Couchdb record associated with
     * the topmost parent. So, to work properly, we need to recurse up the
     * tree of embedded documents until we get to a model that doesn't have a parent.
     * Then we'll set that here.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints)
        {

            $this->query->where($this->getRootParentKeyName(), '=', $this->getRootParentKey());
        }
    }
    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        // There are no eager loading constraints.
    }
    /**
     * Initialize the relation on a set of models.
     *
     * @param  array   $models
     * @param  string  $relation
     * @return void
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model)
        {
            $model->setParentRelation($this);
            $model->setRelation($relation, $this->related->newCollection());
        }
        return $models;
    }
    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, BaseCollection $results, $relation)
    {
        foreach ($models as $model)
        {
            $results = $model->$relation()->getResults();
            $model->setParentRelation($this);
            $model->setRelation($relation, $results);
        }
        return $models;
    }
    /**
     * Shorthand to get the results of the relationship.
     *
     * @return Jferguson\EloquentCouchdb\Eloquent\Collection
     */
    public function get()
    {
        return $this->getResults();
    }
    /**
     * Get the number of embedded models.
     *
     * @return int
     */
    public function count()
    {
        return count($this->getEmbedded());
    }


    /**
     * Transform single ID, single Model or array of Models into an array of IDs
     *
     * @param  mixed  $ids
     * @return array
     */
    protected function getIdsArrayFrom($ids)
    {
        if ( ! is_array($ids)) $ids = array($ids);
        foreach ($ids as &$id)
        {
            if ($id instanceof Model) $id = $id->getKey();
        }
        return $ids;
    }
    /**
     * Get the embedded records array.
     *
     * @return array
     */
    protected function getEmbedded()
    {
        // Get raw attributes to skip relations and accessors.
        $attributes = $this->parent->getAttributes();
        return isset($attributes[$this->localKey]) ? $attributes[$this->localKey] : null;
    }
    /**
     * Set the embedded records array.
     *
     * @param  array $records
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function setEmbedded($records)
    {
        $attributes = $this->parent->getAttributes();
        $attributes[$this->localKey] = $records;
        // Set raw attributes to skip mutators.
        $this->parent->setRawAttributes($attributes);
        // Set the relation on the parent.
        return $this->parent->setRelation($this->relation, $this->getResults());
    }
    /**
     * Get the foreign key value for the relation.
     *
     * @param  mixed $id
     * @return mixed
     */
    protected function getForeignKeyValue($id)
    {
        if ($id instanceof Model)
        {
            $id = $id->getKey();
        }
        // Convert the id to MongoId if necessary.
        return $id;
    }
    /**
     * Convert an array of records to a Collection.
     *
     * @param  array  $records
     * @return Jferguson\EloquentCouchdb\Eloquent\Collection
     */
    protected function toCollection(array $records = array())
    {
        $models = array();
        foreach ($records as $attributes)
        {
            $models[] = $this->toModel($attributes);
        }
        if (count($models) > 0)
        {
            $models = $this->eagerLoadRelations($models);
        }
        return new Collection($models);
    }
    /**
     * Create a related model instanced.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function toModel($attributes = array())
    {
        if (is_null($attributes)) return null;
        $model = $this->related->newFromBuilder((array) $attributes);
        $model->setParentRelation($this);
        $model->setRelation($this->foreignKey, $this->parent);
        // If you remove this, you will get segmentation faults!
        $model->setHidden(array_merge($model->getHidden(), array($this->foreignKey)));
        return $model;
    }
    /**
     * Get the relation instance of the parent.
     *
     * @return Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function getParentRelation()
    {
        return $this->parent->getParentRelation();
    }
    /**
     * Get the underlying query for the relation.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getQuery()
    {
        // Because we are sharing this relation instance to models, we need
        // to make sure we use separate query instances.
        return clone $this->query;
    }
    /**
     * Get the base query builder driving the Eloquent builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getBaseQuery()
    {
        // Because we are sharing this relation instance to models, we need
        // to make sure we use separate query instances.
        return clone $this->query->getQuery();
    }
    /**
     * Check if this relation is nested in another relation.
     *
     * @return boolean
     */
    protected function isNested()
    {
        return $this->getParentRelation() != null;
    }
    /**
     * Get the fully qualified local key name.
     *
     * @return string
     */
    protected function getPathHierarchy($glue = '.')
    {
        if ($parentRelation = $this->getParentRelation())
        {
            return $parentRelation->getPathHierarchy($glue) . $glue . $this->localKey;
        }
        return $this->localKey;
    }
    /**
     * Get the parent's fully qualified key name.
     *
     * @return string
     */
    public function getQualifiedParentKeyName()
    {
        if ($parentRelation = $this->getParentRelation())
        {
            return $parentRelation->getPathHierarchy() . '.' . $this->parent->getKeyName();
        }
        return $this->parent->getKeyName();
    }

    /**
     * Iterate up through the hierarchy of embedded documents
     * until you get to the top. THen return the id of
     * the top document.
     *
     * @return mixed
     */
    public function getRootParentKey(){
        $parent = $this->getParent();
        while($parent->getParentRelation()){
            $parent = $parent->getParentRelation()->getParent();
        }

        return $parent->getKey();
    }

    public function getRootParentKeyName(){
        $parent = $this->getParent();
        while($parent->getParentRelation()){
            $parent = $parent->getParentRelation()->getParent();
        }

        return $parent->getKeyName();
    }

    public function getParent(){
        return $this->parent;
    }


    /**
     * Get the primary key value of the parent.
     *
     * @return string
     */
    protected function getParentKey()
    {
        return $this->parent->getKey();
    }

}


?>