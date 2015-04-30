<?php namespace Jferguson\EloquentCouchdb;

use Doctrine\CouchDB\View\FolderDesignDocument;

use Jferguson\EloquentCouchdb\Query\Builder;
use Jferguson\EloquentCouchdb\Query\Processor;
use Jferguson\CouchDB\CouchDBClient;
use Doctrine\CouchDB\Utils\BulkUpdater;

/*
 * The database manager must have an Illuminate database connection or
 * else it doesn't work properly.
 */
use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Database\ConnectionInterface;
use \BadMethodCallException;

/**
 * @author Jace Ferguson
 *
 */
class Connection extends BaseConnection implements ConnectionInterface
{
  
  protected $client;  
  protected $bulkUpdater;
  protected $designDoc;

  
  public function __construct(array $config){
    
    if(!isset($config["database"])){
      throw new \InvalidArgumentException("'database' is a required configuration parameters.");
    }    
    
    //CouchDBClient expends the database to be in dbname field.
    $config["dbname"] = $config["database"];
    $this->config = $config;
    try {
      $this->createConnection($config);
      $this->useDefaultPostProcessor();
    }
    catch(Exception $e){
      throw $e;
    }
    
  }
  
  /**
   * Creates a CouchDBClient connection
   * 
   * @param array $config
   * @throws Exception
   */
  protected function createConnection($config){
    try {
      $this->client = CouchDBClient::create($config);
      $this->designDoc = new FolderDesignDocument($config["eloquentDesignDocFolder"] . '/' . $config['eloquentDesignDocName']);
    } 
    catch(Exception $e){
      throw $e;
    }
  }
  
  /**
   * Gets a type query for the couchdb
   * 
   * @return \Doctrine\CouchDB\View\Query
   */
  public function getTypeQuery(){
    return $this->client->createViewQuery($this->config["eloquentDesignDocName"], $this->config["eloquentTypeViewName"], $this->designDoc);
  }
  
  protected function getDefaultPostProcessor(){
    return new Processor();
  }
  
  
/*  public function setPdo($readPdo){
    return $this;
  }
  
  public function setReadPdo($pdo){
    return $this;
  }*/
  
  public function type($type){
    $processor = $this->getPostProcessor();
    $query = new Builder($this, $processor);

    return $query->from($type);
  }
  
  public function table($type){
    return $this->type($type);
  }
  
  
  
  public function getClient(){
    return $this->client;
  }
  
  public function getBulkUpdater(){
    $this->bulkUpdater = $this->client->createBulkUpdater();
    $this->bulkUpdater->setAllOrNothing(true);
    return $this->bulkUpdater;
  }
  
  public function disconnect(){
    //The client doesn't really have a disconnect
    //Looks like this is t echnically a "safe" way to disconnect
    $this->client = null;
    $this->bulkUpdater = null;
  }
  
  public function getDriverName(){
    return 'couchdb';
  }
  
  public static function methodNotImplementedException($methodName){
    return new BadMethodCallException($methodName . " is not implemented for couchdb connections.");
  }
  
  /**
   * Get a new raw query expression.
   *
   * @param  mixed  $value
   * @return \Illuminate\Database\Query\Expression
   */
  public function raw($value){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Run a select statement and return a single result.
   *
   * @param  string  $query
   * @param  array   $bindings
   * @return mixed
  */
  public function selectOne($query, $bindings = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Run a select statement against the database.
   *
   * @param  string  $query
   * @param  array   $bindings
   * @return array
  */
  public function select($query, $bindings = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Run an insert statement against the database.
   *
   * @param  string  $query
   * @param  array   $bindings
   * @return bool
  */
  public function insert($query, $bindings = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Run an update statement against the database.
   *
   * @param  string  $query
   * @param  array   $bindings
   * @return int
  */
  public function update($query, $bindings = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Run a delete statement against the database.
   *
   * @param  string  $query
   * @param  array   $bindings
   * @return int
  */
  public function delete($query, $bindings = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Execute an SQL statement and return the boolean result.
   *
   * @param  string  $query
   * @param  array   $bindings
   * @return bool
  */
  public function statement($query, $bindings = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Run an SQL statement and get the number of rows affected.
   *
   * @param  string  $query
   * @param  array   $bindings
   * @return int
  */
  public function affectingStatement($query, $bindings = array()){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Run a raw, unprepared query against the PDO connection.
   *
   * @param  string  $query
   * @return bool
  */
  public function unprepared($query){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Prepare the query bindings for execution.
   *
   * @param  array  $bindings
   * @return array
  */
  public function prepareBindings(array $bindings){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Execute a Closure within a transaction.
   *
   * @param  \Closure  $callback
   * @return mixed
   *
   * @throws \Exception
  */
  public function transaction(Closure $callback){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Start a new database transaction.
   *
   * @return void
  */
  public function beginTransaction(){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Commit the active database transaction.
   *
   * @return void
  */
  public function commit(){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Rollback the active database transaction.
   *
   * @return void
  */
  public function rollBack(){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Get the number of active transactions.
   *
   * @return int
  */
  public function transactionLevel(){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  /**
   * Execute the given callback in "dry run" mode.
   *
   * @param  \Closure  $callback
   * @return array
  */
  public function pretend(Closure $callback){
    throw self::methodNotImplementedException(__METHOD__);
  }
  
  public function __call($method, $parameters){
    return call_user_func_array(array($this->client, $method), $parameters);
  }
  
}

?>