<?php namespace Jferguson\EloquentCouchdb;

use Jferguson\EloquentCouchdb\Eloquent\Builder;
use Jferguson\EloquentCouchdb\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Jferguson\EloquentCouchdb\Relations\EmbedsOne;
use Jferguson\EloquentCouchdb\Relations\EmbedsMany;
use Jferguson\EloquentCouchdb\Relations\EmbedsOneOrMany;


abstract class EmbeddedModel extends \Jferguson\EloquentCouchdb\Model
{
  /**
   * Save the model to the database.
   *
   * @param  array  $options
   * @return bool
   */
  public function save(array $options = array())
  {
    if(!$this->getParentRelation()){
      throw new Exception("Unable to save. The embedded model has not been attached to a parent yet.");
    }
    else {
      if($this->getParentRelation() instanceof EmbedsOneOrMany){
        $saved = $this->saveEmbedded($options);
        if($saved) $this->finishSave($options);
        return $saved;
      }
      //I think this can go
      else {
        return parent::save($options);
      }
    }
  }

  public function delete(){
    //This is just a delete on the model object since it wasn't associated with anything
    if(!$this->getParentRelation()){
      parent::delete();
    }
    else if($this->getParentRelation() instanceof EmbedsOneOrMany){
      
      if($this->fireModelEvent('deleting') === false){
        return false;
      }

      $this->touchOwners();

      //Pass off the delete to the relation since it has access to the parent.
      $this->getParentRelation()->performDelete($this);

      $this->exists = false;

      $this->fireModelEvent('deleted', false);

      return true;
    }
  }



}


?>