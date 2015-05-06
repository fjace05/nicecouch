<?php namespace Jferguson\EloquentCouchdb\Relations;

//use Illuminate\Database\Eloquent\Model;
use Jferguson\EloquentCouchdb\Model;

/**
 * Modified from jenssegers \laravel-mongodb\Relations\EmbedsOne.php
 */

class EmbedsOne extends EmbedsOneOrMany {
    /**
     * Get the results of the relationship.
     *
     * @return \Jferguson\EloquentCouchdb\Model
     */
    public function getResults()
    {
        return $this->toModel($this->getEmbedded());
    }

    /**
     * Delete an existing model and detach it from the parent model.
     *
     * @return boolean
     */
    public function performDelete()
    {
        $model = $this->dissociate();
        //We should probably delete the model just to be safe
        //$model->delete();
        return $this->parent->save();
    }


    /**
     * Associates the model to it's parent, but doesn't save it.
     *
     * @param  \Jferguson\EloquentCouchdb\Model  $model
     * @return \Jferguson\EloquentCouchdb\Model
     */
    public function associate(Model $model)
    {
        $model->setParentRelation($this);
        $this->setEmbedded($model->getAttributes());
        return $this->getResults();

    }

    /**
     * Detach the model from its parent.
     *
     * The model still exists and can be saved or
     * associated with another object.
     *
     * @return \Jferguson\EloquentCouchdb\Model
     */
    public function dissociate()
    {
        $model = $this->getResults();
        $model->clearParentRelation();
        $this->setEmbedded(null);

        return $model;
    }

    /**
     * Delete all embedded models.
     *
     * @return int
     */
    public function delete()
    {
        $model = $this->getResults();
        return $this->performDelete($model);
    }

    /**
     * Embeds the model into the parent.
     * @param Model $model
     */
    public function embed(Model $model){
        $this->setEmbedded($model->getAttributes());
    }

    /**
     * Attach a model instance to the parent model.
     *
     * @param  Model  $model
     * @return Model
     */
    public function save(Model $model)
    {
        $model->setParentRelation($this);
        return $model->save() ? $model : false;
    }

    /**
     * Create a new instance of the related model.
     *
     * @param  array  $attributes
     * @return Model
     */
    public function create(array $attributes)
    {
        // Here we will set the raw attributes to avoid hitting the "fill" method so
        // that we do not have to worry about a mass accessor rules blocking sets
        // on the models. Otherwise, some of these attributes will not get set.
        $instance = $this->related->newInstance($attributes);
        $instance->setParentRelation($this);
        $instance->save();
        return $instance;
    }



}