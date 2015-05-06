<?php namespace Jferguson\EloquentCouchdb\Relations;

use Jferguson\EloquentCouchdb\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Jferguson\EloquentCouchdb\Eloquent\Collection;

class EmbedsMany extends EmbedsOneOrMany {

    /**
     * Get the results of the relationship.
     *
     * @return Collection
     */
    public function getResults()
    {
        return $this->toCollection($this->getEmbedded());
    }


    /**
     * Delete an existing model and detach it from the parent model.
     *
     * @param  Model  $model
     * @return int
     */
    public function performDelete(Model $model)
    {

        $this->dissociate($model);

        return $this->parent->save();
    }

    /**
     * Associate the model instance to the given parent, without saving it to the database.
     *
     * @param  Model  $model
     * @return Model
     */
    public function associate(Model $model)
    {
        if ( !$this->contains($model))
        {
            $this->associateNew($model);
        }
        else
        {
            $this->associateExisting($model);
        }

        $model->setParentRelation($this);
        return $model;
    }

    /**
     * Associate a new model instance to the given parent, without saving it to the database.
     *
     * @param  Model  $model
     * @return Model
     */
    protected function associateNew($model)
    {
        // Create a new key if needed.
        if ( !$model->getAttribute('_id'))
        {
            $model->setAttribute('_id', $this->query->getUuids()[0]);
        }

        $records = $this->getEmbedded();

        // Add the new model to the embedded documents.
        $records[] = $model->getAttributes();

        $this->setEmbedded($records);
    }

    /**
     * Associate an existing model instance to the given parent, without saving it to the database.
     *
     * @param  Model  $model
     * @return Model
     */
    protected function associateExisting($model)
    {
        // Get existing embedded documents.
        $records = $this->getEmbedded();

        $primaryKey = $this->related->getKeyName();

        $key = $model->getKey();

        // Replace the document in the parent model.
        foreach ($records as &$record)
        {
            if ($record[$primaryKey] == $key)
            {
                $record = $model->getAttributes();
                break;
            }
        }

        $this->setEmbedded($records);
    }

    /**
     *
     * Checks to see if the given model is already in the
     * current embedded document.
     *
     * @param Model $model
     * @return boolean
     */
    /*public function contains(Model $model){
        $collection = $this->getResults();
        return $collection->contains($model->getKey());
    }*/

    /**
     * Dissociate the model instance from the given parent, without saving it to the database.
     *
     * @param  mixed $ids
     * @return Model
     */
    public function dissociate($ids = array())
    {
        $ids = $this->getIdsArrayFrom($ids);

        $records = $this->getEmbedded();

        $primaryKey = $this->related->getKeyName();

        // Remove the document from the parent model.
        foreach ($records as $i => $record)
        {
            if (in_array($record[$primaryKey], $ids))
            {
                unset($records[$i]);
            }
        }

        $this->setEmbedded($records);

        // We return the total number of deletes for the operation. The developers
        // can then check this number as a boolean type value or get this total count
        // of records deleted for logging, etc.
        return count($ids);
    }

    /**
     * Destroy the embedded models for the given IDs.
     *
     * @param  mixed  $ids
     * @return int
     */
    public function destroy($ids = array())
    {
        $count = 0;

        $ids = $this->getIdsArrayFrom($ids);

        // Get all models matching the given ids.
        $models = $this->getResults()->only($ids);

        // Pull the documents from the database.
        foreach ($models as $model)
        {
            if ($model->delete()) $count++;
        }

        return $count;
    }

    /**
     * Delete all embedded models.
     *
     * @return int
     */
    public function delete()
    {
        //Set the embeddedness
        $this->setEmbedded(array());

        //Then request that the parent save
        return $this->parent->save();
    }

    /**
     * Destroy alias.
     *
     * @param  mixed  $ids
     * @return int
     */
    public function detach($ids = array())
    {
        return $this->destroy($ids);
    }

    /**
     * Save alias.
     *
     * @param  Model  $model
     * @return Model
     */
    public function attach(Model $model)
    {
        return $this->save($model);
    }



    /**
     * Get a paginator for the "select" statement.
     *
     * @param  int    $perPage
     * @param  array  $columns
     * @return \Illuminate\Pagination\Paginator
     */
//    public function paginate($perPage = null, $columns = array('*'))
//    {
//        $page = Paginator::resolveCurrentPage();
//        $perPage = $perPage ?: $this->related->getPerPage();
//
//        $results = $this->getEmbedded();
//
//        $total = count($results);
//
//        $start = ($page - 1) * $perPage;
//        $sliced = array_slice($results, $start, $perPage);
//
//        return new LengthAwarePaginator($sliced, $total, $perPage, $page, [
//            'path' => Paginator::resolveCurrentPath()
//        ]);
//    }

    /**
     * Get the embedded records array.
     *
     * @return array
     */
    protected function getEmbedded()
    {
        return parent::getEmbedded() ?: array();
    }

    /**
     * Set the embedded records array.
     *
     * @param array $models
     * @return void
     */
    public function setEmbedded($models)
    {
        if ( ! is_array($models)) $models = array($models);

        return parent::setEmbedded(array_values($models));
    }

    /**
     * Handle dynamic method calls to the relationship.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Collection methods
        if (method_exists('Jferguson\EloquentCouchdb\Eloquent\Collection', $method))
        {
            return call_user_func_array(array($this->getResults(), $method), $parameters);
        }

        return parent::__call($method, $parameters);
    }


    /**
     * Embeds the model into the parent.
     * @param Model $model
     */
    public function embed(Model $model){

        $this->associate($model);
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
     * Attach an array of models to the parent instance.
     *
     * @param  array  $models
     * @return array
     */
    public function saveMany(array $models)
    {
        array_walk($models, array($this, 'save'));
        return $models;
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
        $this->save($instance);
        return $instance;
    }

    /**
     * Create an array of new instances of the related model.
     *
     * @param  array  $records
     * @return array
     */
    public function createMany(array $records)
    {
        $instances = array();
        foreach ($records as $record)
        {
            $instances[] = $this->create($record);
        }
        return $instances;
    }

}