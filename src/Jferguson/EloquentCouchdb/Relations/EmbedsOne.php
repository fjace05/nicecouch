<?php namespace Jferguson\EloquentCouchdb\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
/**
 * Modified from jenssegers \laravel-mongodb\Relations\EmbedsOne.php
 */

class EmbedsOne extends EmbedsOneOrMany {
  /**
   * Get the results of the relationship.
   *
   * @return \Illuminate\Database\Eloquent\Model
   */
  public function getResults()
  {

    return $this->toModel($this->getEmbedded());
  }
  /**
   * Save a new model and attach it to the parent model.
   *
   * @param  \Illuminate\Database\Eloquent\Model  $model
   * @return \Illuminate\Database\Eloquent\Model
   */
  public function performInsert(Model $model, array $values = array())
  {
    // For deeply nested documents, let the parent handle the changes.
    //This should always be true.
    //if ($this->isNested())
    if($model->getParentRelation())
    {
      $this->associate($model);
      $this->parent->save();
      return $this->getResults();
    }

    /////THIS SHIZ SHOULDN'T EVER GET CALLED.
    $result = $this->getBaseQuery()->update(array($this->localKey => $model->getAttributes()));
    // Attach the model to its parent.
    if ($result) $this->associate($model);
    return $result ? $model : false;
  }
  /**
   * Save an existing model and attach it to the parent model.
   *
   * THIS REALLY ISN"T NEEDED
   *
   * @param  \Illuminate\Database\Eloquent\Model  $model
   * @return Model|bool
   */
  public function performUpdate(Model $model, array $values = array())
  {
    if ($this->isNested())
    {
      return $this->parent->save();
    }
    // Use array dot notation for better update behavior.
    $values = array_dot($model->getDirty(), $this->localKey . '.');
    $result = $this->getBaseQuery()->update($values);
    // Attach the model to its parent.
    if ($result) $this->associate($model);
    return $result ? $model : false;
  }
  /**
   * Delete an existing model and detach it from the parent model.
   *
   * @param  Model  $model
   * @return int
   */
  public function performDelete(Model $model)
  {
    // For deeply nested documents, let the parent handle the changes.
    if ($this->isNested())
    {
      $this->dissociate($model);
      return $this->parent->save();
    }

    //THIS SHOULDN"T EVER GET CALLED EITHER
    // Overwrite the local key with an empty array.
    $result = $this->getBaseQuery()->update(array($this->localKey => null));
    // Detach the model from its parent.
    if ($result) $this->dissociate();
    return $result;
  }


  /**
   * Attach the model to its parent.
   *
   * @param  \Illuminate\Database\Eloquent\Model  $model
   * @return \Illuminate\Database\Eloquent\Model
   */
  protected function associate(Model $model)
  {
    return $this->setEmbedded($model->getAttributes());
  }
  /**
   * Detach the model from its parent.
   *
   * @return \Illuminate\Database\Eloquent\Model
   */
  public function dissociate()
  {
    return $this->setEmbedded(null);
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

 
        
}