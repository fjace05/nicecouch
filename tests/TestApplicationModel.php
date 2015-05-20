<?php namespace Jferguson\EloquentCouchdb\testing;
class TestApplicationModel extends TestCase
{
  public function tearDown(){
    //DB::type('Application')->truncate();
  }
  
  public function testSave(){
    $a = new Application();
    $a->fill(array(
        "authorizations" => "Bananas",
        "student_id" => 87465465,
        "homeless" => false
        ));
    
    $a->save();
    $this->assertNotEmpty($a->getKey());
    
  }
  
  public function testFind(){
    $a = new Application();
    $a->fill(array(
        "authorizations" => "Bananas",
        "student_id" => 87465465,
        "homeless" => false
    ));
    
    $a->save();
        
     $app = Application::find($a->getKey());
    $this->assertTrue($app->exists());
  }
  
  public function testEmbedsOneAssociate(){
    $a = new Application();
    $a->fill(array(
        "authorizations" => "Bananas",
        "student_id" => 87465465,
        "homeless" => false
        ));
    
    $s = new Survey(array(
        "relationship" => "Mom",
        "guardian_language" => "English",
        "student_talking" => 8,
        "exit_ell" => 12
        ));
    
    $a->Survey()->associate($s);
    
    $a->save();
       
    $this->assertEquals("Mom", $a->Survey()->get()->relationship);    
    $this->assertEquals("English", $a->survey->guardian_language);    
  }
    
}
?>