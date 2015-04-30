<?php namespace Jferguson\EloquentCouchdb\Query;

use Doctrine\CouchDB\HTTP\Response;

class Processor 
{
  public function processGetRequest(Builder $b, Response $response){
    return ($response->status != 200) ? null : $response->body;  
  }  
  
  /**
   * This method returns only the requested fields from the
   * document array.
   * 
   * @param Builder $b
   * @param Array $documentFields - The JSON document array from the response
   * @param string $requestedFields - an array of fields to return
   * @return Array
   */
  public function processRequestedFields(Builder $b, $documentArray, $requestedFields = array('*')){
    if(in_array('*', $requestedFields) || is_null($requestedFields)){
      return $documentArray;
    }
    
    $returnFields = [];
    if(is_string($requestedFields)){
      $requestedFields = [$requestedFields];
    }
    foreach($requestedFields as $fieldKey){
      //It sucks to have to do array_has and then an array_get
      //but there isn't a good value to check for with the array_get to know for sure if we got it.
      if(array_has($documentArray, $fieldKey)){
        array_set($returnFields, $fieldKey, array_get($documentArray, $fieldKey));
      }
    }
    
    return $returnFields;
  }
  
  public function processSelect(Builder $b, array $results){
    
  }
}

?>