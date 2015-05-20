<?php namespace Jferguson\EloquentCouchdb\testing;

class Survey extends \Jferguson\EloquentCouchdb\Model
{
  protected $type = 'Survey';
  
  public $timestamps = false;
  
  public $fillable = array(
      "relationship",
      "guardian_language",
      "student_talking",
      "exit_ell"
      );
}
?>