<?php namespace Jferguson\EloquentCouchdb\Query;

use \InvalidArgumentException;

use Jferguson\EloquentCouchdb\Collection;
use Jferguson\EloquentCouchdb\Query\Processor;
use Jferguson\EloquentCouchdb\Connection;

use Illuminate\Database\Query\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
  protected $type = null;

  protected $operators = array('=');

  protected $updateType = "merge";

  protected $ids = [];

  public function __construct(Connection $connection, Processor $processor){
    $this->connection = $connection;
    $this->processor = $processor;
  }

  /**
   * Returns an array of uuids to be used as couchdbids.
   */
  public function getUuids($count = 1){
    return $this->connection->getUuids($count);
  }

  /**
   * Sets the "type" of document to return;
   */
  public function from($type){
    $this->type = $type;
    return parent::from($type);
  }

  public function newQuery(){
    return new Builder($this->connection, $this->processor);
  }

  /**
   * Find will find a specific document by it's id (and
   * possibly revision) and returnt the requested fields.
   *
   * @param array|string $id - When id is a string, the latest revision
   * will be returned. If it is an an id/rev array, the requested revision will be returned.
   * @param array|string $fields - The fields from the document to return.
   */
  public function find($id, $columns = array('*')){
    return $this->where('_id', '=', $id)->first($columns);
  }

  /**
   * Queries couchdb based on parameters that were setup.
   * @param string|array fields - The fields of each documnet to return
   * @return Collection
   */
  public function getFresh($columns = array('*')){

    if(is_null($this->columns)){
      $this->columns = $columns;
    }

    $this->compileWheres();

    $query = $this->connection->getTypeQuery();
    if(is_null($this->aggregate)){
      $query->setIncludeDocs(true);
    }

    /*
     * TODO: Increment the limit by one to store the next
    * doc id to make lookups super faster.
    */
    if(!is_null($this->limit)){
      $query->setLimit($this->limit);
    }
    
    if(!is_null($this->offset)){
      $query->setSkip($this->offset);
    }

    if(!empty($this->ids)){
      $query->setKeys($this->ids);
    }
    //If we're not searching by ids, then we can specify the type here.
    else{
      $query->setStartKey(array($this->type));
    }

    $response = $query->execute();

    if(!is_null($this->aggregate)){
      if($this->aggregate["function"] == "count"){
        return ["aggregate" => count($response)];
      }
    }
    else
    {
      $docs = [];

      foreach($response as $doc){

        $docs[] = $this->processor->processRequestedFields($this, $doc["doc"], $this->columns);
      }

      return $docs;
    }
  }

  /**
   * Right now, we can only accept _id = type of where clauses.
   */
  protected function compileWheres() {
    $wheres = $this->wheres ? : array();

    foreach ($wheres as $i => &$where){
      //Just in case.
      if($where["column"] != '_id'){
        continue;
      }

      $this->whereIdIn($where["value"]);

    }
  }

  public function generateCacheKey(){
    $key = array(
        'type' => $this->type,
        'ids' => $this->ids,
        'columns' => $this->columns,
        'offset' => $this->offset,
        'limit' => $this->limit
    );

    return md5(serialize(array_values($key)));

  }


  public function pluck($column){
    $result = $this->first(array($column));

    return (count($result)) > 0 ? $result[$column] : null;
  }


  /**
   * Adds ids to an internal array and
   * when a get is requested, only the documenets with
   * the supplied ids will be retrieved.
   *
   * @param array|string ids - The arrays to lookup
   * @return Builder
   */
  public function whereIdIn($ids){
    if(is_string($ids)){
      $this->ids[] = array($this->type, $ids);
    }
    else if(is_array($ids)){
      foreach($ids as $id){
        $this->ids[] = array($this->type, $id);
      }
    }

    return $this;
  }

  public function whereIn($column, $values, $boolean = 'and', $not = false){

    if($not){
      throw new \InvalidArgumentException("'False' whereIn clauses not allowed.");
    }

    $type = $not ? 'NotIn' : 'In';

    if($column != "_id"){
      throw new \InvalidArgumentException("'whereIn' clauses only allowed on the _id fields.");
    }

    if($boolean != 'and'){
      throw new \InvalidArgumentException("Only 'and' whereIn clauses are permitted.");
    }

    if($values instanceof Closure){
      throw new \InvalidArgumentException("Closure-based 'where-in' clauses are not permitted.");
    }

    if(!is_array($values)){
      $values = [$values];
    }

    $this->wheres[] = compact('type', 'column', 'values', 'boolean');

    $this->addBinding($values, 'where');

    return $this;
  }

  /**
   * At this point, we'll only accept _id as the column and '=' as operator.
   */
  public function where($column, $operator = null, $value = null, $boolean = 'and')
  {
    if($column != "_id"){
      throw new \InvalidArgumentException("Only where statements on _id are permitted.");
    }

    if($boolean != 'and'){
      throw new \InvalidArgumentException("Only 'and' where clauses are permitted.");
    }

    // Here we will make some assumptions about the operator. If only 2 values are
    // passed to the method, we will assume that the operator is an equals sign
    // and keep going. Otherwise, we'll require the operator to be passed in.
    if (func_num_args() == 2)
    {
      list($value, $operator) = array($operator, '=');
    }
    elseif ($this->invalidOperatorAndValue($operator, $value))
    {
      throw new \InvalidArgumentException("Value must be provided.");
    }

    // If the given operator is not found in the list of valid operators we will
    // assume that the developer is just short-cutting the '=' operators and
    // we will set the operators to '=' and set the values appropriately.
    if ( ! in_array(strtolower($operator), $this->operators, true))
    {
      list($value, $operator) = array($operator, '=');
    }

    $type = 'Basic';

    $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');

    if(!$value instanceOf Expression){
      $this->addBinding($value, 'where');
    }


    return $this;

  }

  /**
   * Performs an insert into the couchdb database.
   *
   * The only case where this doesn't work is if the document
   * format is made up of all arrays. If that is the case,
   * each array will be viewed as a separate document. There
   * just isn't a good way around that.....
   *
   * @param array $values - The documents to insert.
   * @return boolean;
   */
  public function insert(array $values){
    $batch = true;

    foreach($values as $value){
      if(!is_array($value)){
        $batch = false; break;
      }
    }

    if(!$batch){
      $values = [$values];
    }

    $bu = $this->connection->getBulkUpdater();

    foreach($values as $v){
      if(isset($v["id"]) && !isset($v["_id"])){
        $v["_id"] = $v["_id"];
      }
      $v["type"] = $this->type;
      $bu->updateDocument($v);
    }

    $response = $bu->execute();

    return ($response->status == 200 || $response->status == 201);
  }

  public function insertGetIdAndRev(array $values){
    $values["type"] = $this->type;
    return $this->connection->getClient()->postDocument($values);
  }

  public function insertGetId(array $values, $sequence=null){
    return $this->insertGetIdAndRev($values);
  }

  /**
   *  Updates a document in the database.
   *
   *
   *
   */
  public function update(array $values){
    //Strip out _id or _rev
    if(isset($values["_id"])) {
      unset($values["_id"]);
    }
    if(isset($values["_rev"])) {
      unset($values["_rev"]);
    }

    $this->compileWheres();

    /*
     * We'll see if the doc is of the correct type by querying
    * the type view on the database.
    */
    $query = $this->connection->getTypeQuery();
    $query->setIncludeDocs(true);
    $bulkUpdater = $this->connection->getBulkUpdater();
    $bulkUpdater->setAllOrNothing(true);

    if(!empty($this->ids)){
      $query->setKeys($this->ids);
    }
    else {
      $query->setStartKey(array($this->type));
    }

    $response = $query->execute();

    //We're deleting one or more specific docs.
    try {
       
      foreach($response as $doc){
        //Need to merge together the type, id, and rev
        if($this->updateType == "merge"){
          $saveData = array_merge($doc["doc"], $values);

        }
        else if($this->updateType == "drop"){
          $saveData = array_diff_key($doc["doc"], $values);
        }
        else if($this->updateType == "update"){
          $saveData = $values;
        }
        else {
          throw new \InvalidArgumentException("Invalid update type.");
        }
        $saveData["_id"] = $doc["value"][0];
        $saveData["_rev"] = $doc["value"][1];
        $saveData["type"] = $this->type;
        $bulkUpdater->updateDocument($saveData);
      }

      $response = $bulkUpdater->execute();
      return ($response->status == 200 || $response->status == 201);

    }
    catch(Exception $e){
      return false;
    }

  }

  /**
   * Deletes one or more documents from the type.
   *
   * If the id parameter is passed in, it must be a two element array
   * with id first and then the revision. A check to make sure that
   * document is of the correct type will be done before the document is deleted.
   *
   * If the id parameter is null, all of the documents of the type
   * will be deleted!
   *
   *
   * @param array $id
   * @return boolean
   */
  public function delete($id = null){
    if(!is_null($id)){
      $this->whereIdIn($id);
    }

    $this->compileWheres();

    if(!empty($this->ids)){
      //We're deleting one or more specific docs.
      try {
        /*
         * We'll see if the doc is of the correct type by querying
        * the type view on the database.
        */
        $query = $this->connection->getTypeQuery();
        $query->setKeys($this->ids);
        $response = $query->execute();

        //Now do bulk deletion
        $bulkUpdater = $this->connection->getBulkUpdater();
        $bulkUpdater->setAllOrNothing(true);

        foreach($response as $doc){
          $bulkUpdater->deleteDocument($doc["value"][0], $doc["value"][1]);
        }
        $response = $bulkUpdater->execute();

        return ($response->status == 200 || $response->status == 201);

      }
      catch(Exception $e){
        return false;
      }
    }
    else {
      //If ids are empty then we're truncating the table.
      return $this->truncate();
    }

    //We should delete all from the 'type', but I'm too scared to do taht.

    //To be on the safe side, we'll only delete when there is an id.
    return true;

  }

  /**
   * Remove one or more fields.
   *
   * @param mixed $columns
   * @return ......
   */
  public function drop($columns){
    if(!is_array($columns)){
      $columns = [$columns];
    }

    $this->updateType = "drop";

    return $this->update(array_flip($columns));
  }
  
  public function truncate(){
    //Handle deleting all docs of one type
    $query = $this->connection->getTypeQuery();
    $query->setStartKey(array($this->type));
    $response = $query->execute();
    $bulkUpdater = $this->connection->getBulkUpdater();

    foreach($response as $doc){
      $bulkUpdater->deleteDocument($doc["value"][0], $doc["value"][1]);
    }


    $response = $bulkUpdater->execute();

    return ($response->status == 200 || $response->status == 201);
  }

  /**
   * Get an array with the values of a given column.
   *
   * @param  string  $column
   * @param  string  $key
   * @return array
   */
  public function lists($column, $key = null){
    
    
  }




  public function selectRaw($expression){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function distinct(){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function join($table, $one, $operator = null, $two = null, $type = 'inner', $where = false){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function joinWhere($table, $one, $operator, $two, $type = 'inner'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function leftJoin($table, $first, $operator = null, $second = null){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function leftJoinWhere($table, $one, $operator, $two){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function rightJoin($table, $first, $operator = null, $second = null){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function rightJoinWhere($table, $one, $operator, $two){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orWhere($column, $operator = null, $value = null){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereRaw($sql, array $bindings = array(), $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orWhereRaw($sql, array $bindings = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereBetween($column, array $values, $boolean = 'and', $not = false){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orWhereBetween($column, array $values){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereNotBetween($column, array $values, $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orWhereNotBetween($column, array $values){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereNested(Closure $callback, $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function addNestedWhereQuery($query, $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  protected function whereSub($column, $operator, Closure $callback, $boolean){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereExists(Closure $callback, $boolean = 'and', $not = false){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereNotExists(Closure $callback, $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orWhereNotExists(Closure $callback){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orWhereIn($column, $values){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereNotIn($column, $values, $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orWhereNotIn($column, $values){
    throw self::methodNotImplementedException(__METHOD__);
  }
   
  public function whereNull($column, $boolean = 'and', $not = false){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orWhereNull($column){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereNotNull($column, $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orWhereNotNull($column){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereDate($column, $operator, $value, $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereDay($column, $operator, $value, $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereMonth($column, $operator, $value, $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function whereYear($column, $operator, $value, $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function dynamicWhere($method, $parameters){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function groupBy(){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function having($column, $operator = null, $value = null, $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orHaving($column, $operator = null, $value = null){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function havingRaw($sql, array $bindings = array(), $boolean = 'and'){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orHavingRaw($sql, array $bindings = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function orderByRaw($sql, $bindings = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function union($query, $all = false){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function unionAll($query){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function lock($value = true){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function lockForUpdate(){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function sharedLock(){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function aggregate($function, $columns = array('*')){
    if($function != 'count'){
      throw new BadMethodCallException($function . " is not implemented for couchdb queries.");
    }
    $this->aggregate = compact('function', 'columns');

    $previousColumns = $this->columns;

    $results = $this->get($columns);

    // Once we have executed the query, we will reset the aggregate property so
    // that more select queries can be executed against the database without
    // the aggregate value getting in the way when the grammar builds it.
    $this->aggregate = null;

    $this->columns = $previousColumns;

    return $results['aggregate'];
  }

  public function increment($column, $amount = 1, array $extra = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function decrement($column, $amount = 1, array $extra = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function raw($value){
    throw self::methodNotImplementedException(__METHOD__);
  }

  public function toSql(){
    throw new \BadMethodCallException(__METHOD__ . " doesn't make sense for couchdb queries.");
  }

  public function getGrammer(){
    throw new \BadMethodCallException(__METHOD__ . " doesn't make sense for couchdb queries.");
  }

  public function useWritePdo(){
    throw new \BadMethodCallException(__METHOD__ . " doesn't make sense for couchdb queries.");
  }

  public static function methodNotImplementedException($methodName){
    return new \BadMethodCallException($methodName . " is not implemented for couchdb queries.");
  }
  
  public function __call($method, $parameters){
    //Have to do it this way since unset is reserved.
    if($method == "unset"){
      return call_user_func_array(array($this, 'drop'), $parameters);
    }
    
    return parent::__call($method, $parameters);
  }



}
?>