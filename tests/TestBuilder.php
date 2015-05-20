<?php namespace Jferguson\EloquentCouchdb\testing;


class TestBuilder extends TestCase
{
  public function tearDown(){
    DB::type('User')->truncate();    
  }
  
  public function testGet()
  {
    $users = DB::type('User')->get();
    $this->assertEquals(0, count($users));
    
    DB::type('User')->insert(['name' => 'John Doe']);
    
    $users = DB::type('User')->get();
    $this->assertEquals(1, count($users));
  }
  
  public function testInsert(){
    DB::type('User')->insert(array(
      "name" => "John Doe",
      "role" => "User",
      "dob" => '5/15/1988'
    ));
    
    $users = DB::type('User')->get();
    $this->assertEquals(1, count($users));
    
    $user = $users[0];
    $this->assertEquals('John Doe', $user["name"]);
    
  }
  
  public function testInsertGetIdAndRev()
  {
    $id = DB::type('User')->insertGetIdAndRev(['name' => 'John Doe']);
    $this->assertTrue(is_array($id));
    //Returns the id and rev in an array
    $this->assertEquals(2, count($id));
  }
  

  public function testFind()
  {
    $id = DB::type('User')->insertGetIdAndRev(['name' => 'John Doe']);
    $user = DB::type('User')->find($id[0]);
    $this->assertEquals('John Doe', $user['name']);
    
    $user["name"] = "Jane Doe";
    $newId = DB::type('User')->update($user);
    
    $this->assertEquals('Jane Doe', $user['name']);
  }
  
  public function testUpdate()
  {
    $id = DB::type('User')->insertGetIdAndRev(['name' => 'Jane Doe', 'age' => 20]);
    $id2 = DB::type('User')->insertGetIdAndRev(['name' => 'John Doe', 'age' => 300]);
    
    $this->assertEquals(20, DB::type('User')->where('_id', $id[0])->first()["age"]);
    DB::type('User')->where('_id', $id[0])->update(['age' => 100]);
    $this->assertEquals('Jane Doe', DB::type('User')->where('_id', $id[0])->first()["name"]);
    $this->assertEquals(100, DB::type('User')->where('_id', $id[0])->first()["age"]);
    
    $this->assertEquals('John Doe', DB::type('User')->where('_id', $id2[0])->first()["name"]);
    $this->assertEquals(300, DB::type('User')->where('_id', $id2[0])->first()["age"]);
    
  }
  
  public function testDelete()
  {
    DB::type('User')->insert([
    ['name' => 'Jane Doe', 'age' => 20],
    ['name' => 'John Doe', 'age' => 21]
    ]);
        
    $this->assertEquals(2, count(DB::type('User')->get()));
    DB::type('User')->delete();
    $this->assertEquals(0, count(DB::type('User')->get()));

    
    $id = DB::type('User')->insertGetIdAndRev(['_id' => 'test', 'name' => 'John Dow']);
    
    DB::type('User')->insert([
    ['name' => 'Jane Doe', 'age' => 20],
    ['name' => 'John Doe', 'age' => 21]
    ]);
    $this->assertEquals(3, count(DB::type('User')->get()));
    DB::type('User')->where("_id", $id)->delete();
    $this->assertEquals(2, count(DB::type('User')->get()));
    
  }
  
  public function testTruncate()
  {
    DB::type('User')->insert(['name' => 'John Doe']);
    DB::type('User')->truncate();
    $this->assertEquals(0, count(DB::type('User')->get()));
  }
  
  public function testDrop(){
    $id = DB::type('User')->insertGetIdAndRev(['name' => 'Jane Doe', 'age' => 20]);
    $id2 = DB::type('User')->insertGetIdAndRev(['name' => 'John Doe', 'age' => 300]);
    
    //_id, _rev, name, age, type
    $this->assertEquals(5, count(DB::type('User')->where("_id", $id[0])->first()));
    
    DB::type('User')->where("_id", $id[0])->drop('name');
    
    $this->assertEquals(4, count(DB::type('User')->where("_id", $id[0])->first()));
    $this->assertEquals(5, count(DB::type('User')->where("_id", $id2[0])->first()));
    
  }
  
  public function testPluck()
  {
    DB::type('User')->insert([
    ['name' => 'Jane Doe', 'age' => 20],
    ['name' => 'John Doe', 'age' => 25]
    ]);
    $age = DB::type('User')->pluck('age');
    $this->assertEquals(20, $age);
  }


}
?>