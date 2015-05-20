<?php namespace Jferguson\EloquentCouchdb\testing;

class Application extends \Jferguson\EloquentCouchdb\Model
{
  protected $type = 'Application';
  
  public $timestamps = false;
  
  public $fillable = array(
      "authorizations",
      "student_id",
      "homeless",
      );
  
  public function Survey(){
    return $this->embedsOne('Survey');
  }
}
?>