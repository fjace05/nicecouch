<?php namespace Jferguson\EloquentCouchdb;

use Jferguson\EloquentCouchdb\Query\Builder;

class Collection extends \Illuminate\Support\Collection
{
  protected $builder;
  
  public function __construct(Builder $b, array $items = array()){
    $this->builder = $b;
    
    parent::__construct($items);
  }
  
  public function delete(){
    $this->each(function($item){
      $this->builder->delete([$item["_id"], $item["_rev"]]);
    });
  }
  
  public function __call($method, $parameters){
    return call_user_func_array([$this->builder, $method], $parameters);    
  }
  
}

?>