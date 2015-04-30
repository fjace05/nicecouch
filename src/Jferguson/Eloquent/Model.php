<?php namespace Jferguson\Eloquent;

use Jferguson\EloquentCouchdb\Query\Builder;

abstract class Model extends \Illuminate\Database\Eloquent\Model {
  /**
   * Get a new query builder instance for the connection.
   *
   * @return Builder
   */
  protected function newBaseQueryBuilder()
  {
    $connection = $this->getConnection();
    // Check the connection type to see if it one of the couch models.
    if ($connection instanceof \Jferguson\EloquentCouchdb\Connection)
    {
      return new Builder($connection, $connection->getPostProcessor());
    }
    
    //It's not, return a normal builder;
    return parent::newBaseQueryBuilder();
  }
  
 
}
?>