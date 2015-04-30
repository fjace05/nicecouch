<?php namespace Jferguson\EloquentCouchdb;

use Jferguson\EloquentCouchdb\Eloquent\Builder;
use Jferguson\EloquentCouchdb\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Jferguson\EloquentCouchdb\Relations\EmbedsOne;
use Jferguson\EloquentCouchdb\Relations\EmbedsMany;
use Jferguson\EloquentCouchdb\Relations\EmbedsOneOrMany;


abstract class Model extends \Jferguson\Eloquent\Model
{
  protected $type;

  protected $parentRelation;

  protected $primaryKey = '_id';

  public $incrementing = true;



  public function getTable(){

    if(!is_null($this->type)) {
      return $this->type;
    }

    return parent::getTable();

  }

  public function getKey(){
    return parent::getKey();
    return $this->attributes[$this->getKeyName()];
  }

  public function getQualifiedKeyName(){
    return $this->getKeyName();
  }

  public function setType($type){
    $this->type = $type;
  }

  public function setTable($table){
    $this->setType($table);
  }

  protected function getDateFormat(){
    return 'Y-m-d\TH:i:s.uP';
  }

  public function getAttribute($key){

    $camelKey = camel_case($key);
    $methodExists = method_exists($this, $camelKey) && $key[0] != "_";
    $inAttributes = array_has($this->attributes, $key);

    //Exclude attributes where the method exists, as that is actually a relation.
    if(($inAttributes && !$methodExists) || $this->hasGetMutator($key)){
      return $this->getAttributeValue($key);
    }

    // If the "attribute" exists as a method on the model, it may be an
    // embedded model. If so, we need to return the result before it
    // is handled by the parent method.
    if (method_exists($this, $camelKey))
    {
      $relations = $this->$camelKey();

      // This attribute matches an embedsOne or embedsMany relation so we need
      // to return the relation results instead of the interal attributes.
      if ($relations instanceof EmbedsOneOrMany)
      {
        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
        if (array_key_exists($key, $this->relations))
        {
          return $this->relations[$key];
        }
        // Get the relation results.
        return $this->getRelationshipFromMethod($key, $camelKey);
      }
    }

    return parent::getAttribute($key);

  }

  public function getAttributeFromArray($key){
    if(array_has($this->attributes, $key)){
      return array_get($this->attributes, $key);
    }

    //Something about the parent get.....
  }

  public function setAttribute($key, $value){
    if($this->hasSetMutator($key)){
      $method = 'set'.studly_case(str_replace('.', '_', $key)) . 'Attribute';
      return $this->{$method}($value);
    }
    // If an attribute is listed as a "date", we'll convert it from a DateTime
    // instance into a form proper for storage on the database tables using
    // the connection grammar's date format. We will auto set the values.
    elseif (in_array($key, $this->getDates()) && $value)
    {
      $value = $this->fromDateTime($value);
    }

    array_set($this->attributes, $key, $value);

  }

  public function hasGetMutator($key){

    $key = str_replace('.', '_', $key);

    return method_exists($this, 'get'.studly_case($key). 'Attribute');
  }

  public function hasSetMutator($key){

    $key = str_replace('.', '_', $key);

    return method_exists($this, 'set'.studly_case($key). 'Attribute');
  }

  public function attributesToArray()
  {
    return parent::attributesToArray();
  }

  public function drop($columns){
    if(!is_array($columns)){
      $columns = [$columns];
    }

    foreach($columns as $c){
      $this->__unset($c);
    }

    return $this->newQuery()->where($this->getKeyName(), $this->getKey())->unset($columns);
  }

  /**
   * Insert the given attributes and set the ID and REV on the model.
   *
   * @param  \Illuminate\Database\Eloquent\Builder  $query
   * @param  array  $attributes
   * @return void
   */
  protected function insertAndSetId(\Illuminate\Database\Eloquent\Builder $query, $attributes)
  {
    $idAndRev = $query->insertGetId($attributes, $keyName = $this->getKeyName());

    $this->setAttribute($keyName, $idAndRev[0]);
    $this->setAttribute("_rev", $idAndRev[1]);
  }


  public function newEloquentBuilder($query){
    return new Builder($query);
  }

  /**
   * Set the parent relation.
   *
   * @param Relation $relation
   */
  public function setParentRelation(Relation $relation)
  {
    $this->parentRelation = $relation;
  }
  /**
   * Get the parent relation.
   *
   * @return Relation
   */
  public function getParentRelation()
  {
    return $this->parentRelation;
  }

  protected function embedsOne($related, $localKey = null, $foreignKey = null, $relation = null){
    // If no relation name was given, we will use this debug backtrace to extract
    // the calling method's name and use that as the relationship name as most
    // of the time this will be what we desire to use for the relatinoships.
    if (is_null($relation))
    {
      list(, $caller) = debug_backtrace(false);
      $relation = $caller['function'];
    }
    if (is_null($localKey))
    {
      $localKey = $relation;
    }
    if (is_null($foreignKey))
    {
      $foreignKey = snake_case(class_basename($this));
    }
    $query = $this->newQuery();
    $instance = new $related;
    return new EmbedsOne($query, $this, $instance, $localKey, $foreignKey, $relation);
  }

  /**
   * Define an embedded one-to-many relationship.
   *
   * @param  string  $related
   * @param  string  $collection
   * @return \Illuminate\Database\Eloquent\Relations\EmbedsMany
   */
  protected function embedsMany($related, $localKey = null, $foreignKey = null, $relation = null)
  {
    // If no relation name was given, we will use this debug backtrace to extract
    // the calling method's name and use that as the relationship name as most
    // of the time this will be what we desire to use for the relatinoships.
    if (is_null($relation))
    {
      list(, $caller) = debug_backtrace(false);
      $relation = $caller['function'];
    }
    if (is_null($localKey))
    {
      $localKey = $relation;
    }
    if (is_null($foreignKey))
    {
      $foreignKey = snake_case(class_basename($this));
    }
    $query = $this->newQuery();
    $instance = new $related;
    return new EmbedsMany($query, $this, $instance, $localKey, $foreignKey, $relation);
  }

  /**
   * Save the model to the database.
   *
   * @param  array  $options
   * @return bool
   */
  public function save(array $options = array())
  {

    if(($relation = $this->getParentRelation()) &&  ($relation instanceof EmbedsOneOrMany)){
      $saved = $this->saveEmbeded($options);

      if($saved) $this->finishSave($options);

      return $saved;
    }
    else {
      return parent::save($options);
    }
  }

  public function delete(){
    if(($relation = $this->getParentRelation()) &&  ($relation instanceof EmbedsOneOrMany)){

      if($this->fireModelEvent('deleting') === false){
        return false;
      }

      $this->touchOwners();

      $relation->performDelete($this);

      $this->exists = false;

      $this->fireModelEvent('deleted', false);

      return true;
    }
    else {
      return parent::delete();
    }
  }

  protected function saveEmbeded($options = array()){
    $relation = $this->getParentRelation();

    if ($this->getKeyName() == '_id' && !$this->getKey())
    {
      $this->setAttribute('_id', $relation->getQuery()->getUuids()[0]);
    }

    if($this->fireModelEvent('saving') === false){
      return false;
    }

    

    if ($this->fireModelEvent('creating') === false) return false;

    // First we'll need to create a fresh query instance and touch the creation and
    // update timestamps on this model, which are maintained by us for developer
    // convenience. After, we will just continue saving these model instances.
    if ($this->timestamps && array_get($options, 'timestamps', true))
    {
      $this->updateTimestamps();
    }

    // If the model has an incrementing key, we can use the "insertGetId" method on
    // the query builder, which will give us back the final inserted ID for this
    // table from the database. Not all tables have to be incrementing though.
    $attributes = $this->attributes;

    if($relation instanceof EmbedsMany){
      $relation->associate($this);
    }
    else if($relation instanceof EmbedsOne){
      //Set the attributes on the parent object through the relation.
      $relation->setEmbedded($this->attributes);
    }


    //Save the parent document that has the embedded document.
    $relation->getParent()->save();

    // We will go ahead and set the exists property to true, so that it is set when
    // the created event is fired, just in case the developer tries to update it
    // during the event. This will allow them to do so and run an update here.
    $this->exists = true;

    $this->fireModelEvent('created', false);

    return true;

  }
  
  /**
   * Destroy the models for the given IDs.
   *
   * @param  array|int  $ids
   * @return int
   */
  public static function destroy($ids)
  {
    // We'll initialize a count here so we will return the total number of deletes
    // for the operation. The developers can then check this number as a boolean
    // type value or get this total count of records deleted for logging, etc.
    $count = 0;
  
    $ids = is_array($ids) ? $ids : func_get_args();
  
    $instance = new static;
  
    // We will actually pull the models from the database table and call delete on
    // each of them individually so that their events get fired properly with a
    // correct set of attributes in case the developers wants to check these.
    $key = $instance->getKeyName();
  
    foreach ($instance->whereIdIn($ids)->get() as $model)
    {
      if ($model->delete()) $count++;
    }
  
    return $count;
  }
  
  public function __call($method, $parameters){
    if($method == "unset"){
      return call_user_func_array(array($this, 'drop'), $parameters);
    }
    return parent::__call($method, $parameters);
  }


}


?>