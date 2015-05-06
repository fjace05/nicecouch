<?php namespace Jferguson\EloquentCouchdb\Eloquent;

use Illuminate\Database\Eloquent\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
  
  protected $passthru = array('insert', 'insertGetId', 'pluck', 'count', 'exists', 'getUuids');
  
  public function type($type){
    return $this->from($type);
  }
  
  
  public function update(array $values)
  {
    //May need to do some relation updates
    
    //For now, just call update on the Eloquent\Builder.
    //That will call update on the queryBuilder that gets passed into this builder
    return parent::update($values);
  }
  
  
  public function insert(array $values)
  {
    //May need to do some relation updates

    //For now just pass it up the chain.
    return parent::insert($values);
  }
  
  public function insertGetId(array $values, $sequence)
  {
    //May need to do relation related insert
  
        
    //For now, pass it to the query builder
    return parent::insertGetId($values);
      
  }
  
  public function delete(){
    
    return parent::delete();
  }
  
  
  public function lists($column, $key = null){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  public function increment($count, $amount = 1, array $extra = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  public function decrement($count, $amount = 1, array $extra = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  public function orWhere($column, $operator = null, $value = null){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  public static function methodNotImplementedException($methodName){
    return new BadMethodCallException($methodName . " is not implemented for couchdb queries. Yet...");
  }
}

?>