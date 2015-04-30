<?php

class QueryBuilderTest extends TestCase {

	public function tearDown()
	{
		DB::type('users')->truncate();
		DB::type('items')->truncate();
	}

	public function testType()
	{
		$this->assertInstanceOf('Jferguson\EloquentCouchdb\Query\Builder', DB::type('users'));
	}

	public function testGet()
	{
		$users = DB::type('users')->get();
		$this->assertEquals(0, count($users));

		DB::type('users')->insert(['name' => 'John Doe']);

		$users = DB::type('users')->get();
		$this->assertEquals(1, count($users));
	}

	public function testNoDocument()
	{
		$items = DB::type('items')->get();
		$this->assertEquals([], $items);

		$item = DB::type('items')->first();
		$this->assertEquals(null, $item);

		$item = DB::type('items')->where('_id', '51c33d8981fec6813e00000a')->first();
		$this->assertEquals(null, $item);
	}

	public function testInsert()
	{
		DB::type('users')->insert([
			'tags' => ['tag1', 'tag2'],
			'name' => 'John Doe',
		]);

		$users = DB::type('users')->get();
		$this->assertEquals(1, count($users));

		$user = $users[0];
		$this->assertEquals('John Doe', $user['name']);
		$this->assertTrue(is_array($user['tags']));
	}

	public function testInsertGetId()
	{
		$id = DB::type('users')->insertGetId(['name' => 'John Doe']);
		$this->assertNotNull( $id);
	}

	public function testBatchInsert()
	{
		DB::type('users')->insert([
			[
				'tags' => ['tag1', 'tag2'],
				'name' => 'Jane Doe',
			],
			[
				'tags' => ['tag3'],
				'name' => 'John Doe',
			],
		]);

		$users = DB::type('users')->get();
		$this->assertEquals(2, count($users));
		$this->assertTrue(is_array($users[0]['tags']));
	}

	public function testFind()
	{
		$id = DB::type('users')->insertGetId(['name' => 'John Doe']);

		$user = DB::type('users')->find($id);
		$this->assertEquals('John Doe', $user['name']);
	}

	public function testFindNull()
	{
		$user = DB::type('users')->find(null);
		$this->assertEquals(null, $user);
	}

	public function testCount()
	{
		DB::type('users')->insert([
			['name' => 'Jane Doe'],
			['name' => 'John Doe']
		]);

		$this->assertEquals(2, DB::type('users')->count());
	}

	public function testUpdate()
	{
		$jane = DB::type('users')->insertGetId(['name' => 'Jane Doe', 'age' => 20]);
		$john = DB::type('users')->insertGetId(['name' => 'John Doe', 'age' => 21]);

		DB::type('users')->where('_id', $john)->update(['age' => 100]);
		$users = DB::type('users')->get();

		$john = DB::type('users')->where('_id', $john)->first();
		$jane = DB::type('users')->where('_id', $jane)->first();
		$this->assertEquals(100, $john['age']);
		$this->assertEquals(20, $jane['age']);
	}

	public function testDelete()
	{
		$jane = DB::type('users')->insertGetId(['name' => 'Jane Doe', 'age' => 20]);
		$john = DB::type('users')->insertGetId(['name' => 'John Doe', 'age' => 21]);

		DB::type('users')->where('_id', $jane)->delete();
		$this->assertEquals(1, DB::type('users')->count());
	}

	public function testTruncate()
	{
		DB::type('users')->insert(['name' => 'John Doe']);
		DB::type('users')->truncate();
		$this->assertEquals(0, DB::type('users')->count());
	}

	/**
	 * Not implemented.
	 */
	/*public function testSubKey()
	{
		DB::type('users')->insert([
			[
				'name' => 'John Doe',
				'address' => ['country' => 'Belgium', 'city' => 'Ghent']
			],
			[
				'name' => 'Jane Doe',
				'address' => ['country' => 'France', 'city' => 'Paris']
			]
		]);

		$users = DB::type('users')->where('address.country', 'Belgium')->get();
		$this->assertEquals(1, count($users));
		$this->assertEquals('John Doe', $users[0]['name']);
	}*/

	/**
	 * Not implemented
	 */
	/*public function testInArray()
	{
		DB::type('items')->insert([
			[
				'tags' => ['tag1', 'tag2', 'tag3', 'tag4']
			],
			[
				'tags' => ['tag2']
			]
		]);

		$items = DB::type('items')->where('tags', 'tag2')->get();
		$this->assertEquals(2, count($items));

		$items = DB::type('items')->where('tags', 'tag1')->get();
		$this->assertEquals(1, count($items));
	}*/

	/**
	 * Not implemented
	 */
	/*public function testPush()
	{
		$id = DB::type('users')->insertGetId([
			'name' => 'John Doe',
			'tags' => [],
			'messages' => [],
		]);

		DB::type('users')->where('_id', $id)->push('tags', 'tag1');

		$user = DB::type('users')->find($id);
		$this->assertTrue(is_array($user['tags']));
		$this->assertEquals(1, count($user['tags']));
		$this->assertEquals('tag1', $user['tags'][0]);

		DB::type('users')->where('_id', $id)->push('tags', 'tag2');
		$user = DB::type('users')->find($id);
		$this->assertEquals(2, count($user['tags']));
		$this->assertEquals('tag2', $user['tags'][1]);

		// Add duplicate
		DB::type('users')->where('_id', $id)->push('tags', 'tag2');
		$user = DB::type('users')->find($id);
		$this->assertEquals(3, count($user['tags']));

		// Add unique
		DB::type('users')->where('_id', $id)->push('tags', 'tag1', true);
		$user = DB::type('users')->find($id);
		$this->assertEquals(3, count($user['tags']));

		$message = ['from' => 'Jane', 'body' => 'Hi John'];
		DB::type('users')->where('_id', $id)->push('messages', $message);
		$user = DB::type('users')->find($id);
		$this->assertTrue(is_array($user['messages']));
		$this->assertEquals(1, count($user['messages']));
		$this->assertEquals($message, $user['messages'][0]);

		// Raw
		DB::type('users')->where('_id', $id)->push(['tags' => 'tag3', 'messages' => ['from' => 'Mark', 'body' => 'Hi John']]);
		$user = DB::type('users')->find($id);
		$this->assertEquals(4, count($user['tags']));
		$this->assertEquals(2, count($user['messages']));

		DB::type('users')->where('_id', $id)->push(['messages' => ['date' => new MongoDate(), 'body' => 'Hi John']]);
		$user = DB::type('users')->find($id);
		$this->assertEquals(3, count($user['messages']));
	}*/

	/**
	 * Not implemented
	 */
	/*public function testPull()
	{
		$message1 = ['from' => 'Jane', 'body' => 'Hi John'];
		$message2 = ['from' => 'Mark', 'body' => 'Hi John'];

		$id = DB::type('users')->insertGetId([
			'name' => 'John Doe',
			'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
			'messages' => [$message1, $message2]
		]);

		DB::type('users')->where('_id', $id)->pull('tags', 'tag3');

		$user = DB::type('users')->find($id);
		$this->assertTrue(is_array($user['tags']));
		$this->assertEquals(3, count($user['tags']));
		$this->assertEquals('tag4', $user['tags'][2]);

		DB::type('users')->where('_id', $id)->pull('messages', $message1);

		$user = DB::type('users')->find($id);
		$this->assertTrue(is_array($user['messages']));
		$this->assertEquals(1, count($user['messages']));

		// Raw
		DB::type('users')->where('_id', $id)->pull(['tags' => 'tag2', 'messages' => $message2]);
		$user = DB::type('users')->find($id);
		$this->assertEquals(2, count($user['tags']));
		$this->assertEquals(0, count($user['messages']));
	}*/

	/**
	 * Not implemented
	 */
	/*public function testDistinct()
	{
		DB::type('items')->insert([
			['name' => 'knife', 'type' => 'sharp',],
			['name' => 'fork',  'type' => 'sharp'],
			['name' => 'spoon', 'type' => 'round'],
			['name' => 'spoon', 'type' => 'round']
		]);

		$items = DB::type('items')->distinct('name')->get(); sort($items);
		$this->assertEquals(3, count($items));
		$this->assertEquals(['fork', 'knife', 'spoon'], $items);

		$types = DB::type('items')->distinct('type')->get(); sort($types);
		$this->assertEquals(2, count($types));
		$this->assertEquals(['round', 'sharp'], $types);
	}*/

	/**
	 * Not implemeted
	 */
	/*public function testCustomId()
	{
		DB::type('items')->insert([
			['_id' => 'knife', 'type' => 'sharp', 'amount' => 34],
			['_id' => 'fork',  'type' => 'sharp', 'amount' => 20],
			['_id' => 'spoon', 'type' => 'round', 'amount' => 3]
		]);

		$item = DB::type('items')->find('knife');
		$this->assertEquals('knife', $item['_id']);

		$item = DB::type('items')->where('_id', 'fork')->first();
		$this->assertEquals('fork', $item['_id']);

		DB::type('users')->insert([
			['_id' => 1, 'name' => 'Jane Doe'],
			['_id' => 2, 'name' => 'John Doe']
		]);

		$item = DB::type('users')->find(1);
		$this->assertEquals(1, $item['_id']);
	}*/

	public function testTake()
	{
		DB::type('items')->insert([
			['name' => 'knife', 'type' => 'sharp', 'amount' => 34],
			['name' => 'fork',  'type' => 'sharp', 'amount' => 20],
			['name' => 'spoon', 'type' => 'round', 'amount' => 3],
			['name' => 'spoon', 'type' => 'round', 'amount' => 14]
		]);

		$items = DB::type('items')->take(2)->get();
		$this->assertEquals(2, count($items));
		$this->assertEquals('fork', $items[1]['name']);
	}

	public function testSkip()
	{
		DB::type('items')->insert([
			['name' => 'knife', 'type' => 'sharp', 'amount' => 34],
			['name' => 'fork',  'type' => 'sharp', 'amount' => 20],
			['name' => 'spoon', 'type' => 'round', 'amount' => 3],
			['name' => 'spoon', 'type' => 'round', 'amount' => 14]
		]);

		$items = DB::type('items')->skip(2)->get();
		$this->assertEquals(2, count($items));
		$this->assertEquals('spoon', $items[0]['name']);
	}

	public function testPluck()
	{
		$jane = DB::type('users')->insertGetId(['name' => 'Jane Doe', 'age' => 20]);
		$john = DB::type('users')->insertGetId(['name' => 'John Doe', 'age' => 25]);

		$age = DB::type('users')->where('_id', $john)->pluck('age');
		$this->assertEquals(25, $age);
	}

	/**
	 * Not implemented
	 */
	/*public function testList()
	{
		DB::type('items')->insert([
			['name' => 'knife', 'type' => 'sharp', 'amount' => 34],
			['name' => 'fork',  'type' => 'sharp', 'amount' => 20],
			['name' => 'spoon', 'type' => 'round', 'amount' => 3],
			['name' => 'spoon', 'type' => 'round', 'amount' => 14]
		]);

		$list = DB::type('items')->lists('name');
		sort($list);
		$this->assertEquals(4, count($list));
		$this->assertEquals(['fork', 'knife', 'spoon', 'spoon'], $list);

		$list = DB::type('items')->lists('type', 'name');
		$this->assertEquals(3, count($list));
		$this->assertEquals(['knife' => 'sharp', 'fork' => 'sharp', 'spoon' => 'round'], $list);

		$list = DB::type('items')->lists('name', '_id');
		$this->assertEquals(4, count($list));
		$this->assertEquals(24, strlen(key($list)));
	}*/

	public function testAggregate()
	{
		DB::type('items')->insert([
			['name' => 'knife', 'type' => 'sharp', 'amount' => 34],
			['name' => 'fork',  'type' => 'sharp', 'amount' => 20],
			['name' => 'spoon', 'type' => 'round', 'amount' => 3],
			['name' => 'spoon', 'type' => 'round', 'amount' => 14]
		]);

		//$this->assertEquals(71, DB::type('items')->sum('amount'));
		$this->assertEquals(4, DB::type('items')->count('amount'));
		//$this->assertEquals(3, DB::type('items')->min('amount'));
		//$this->assertEquals(34, DB::type('items')->max('amount'));
		//$this->assertEquals(17.75, DB::type('items')->avg('amount'));

		//$this->assertEquals(2, DB::type('items')->where('name', 'spoon')->count('amount'));
		//$this->assertEquals(14, DB::type('items')->where('name', 'spoon')->max('amount'));
	}

	/**public function testSubdocumentAggregate()
	{
		DB::type('items')->insert([
			['name' => 'knife', 'amount' => ['hidden' => 10, 'found' => 3]],
			['name' => 'fork',  'amount' => ['hidden' => 35, 'found' => 12]],
			['name' => 'spoon', 'amount' => ['hidden' => 14, 'found' => 21]],
			['name' => 'spoon', 'amount' => ['hidden' => 6, 'found' => 4]]
		]);

		$this->assertEquals(65, DB::type('items')->sum('amount.hidden'));
		$this->assertEquals(4, DB::type('items')->count('amount.hidden'));
		$this->assertEquals(6, DB::type('items')->min('amount.hidden'));
		$this->assertEquals(35, DB::type('items')->max('amount.hidden'));
		$this->assertEquals(16.25, DB::type('items')->avg('amount.hidden'));
	}**/


	public function testUnset()
	{
		$id1 = DB::type('users')->insertGetId(['name' => 'John Doe', 'note1' => 'ABC', 'note2' => 'DEF']);
		$id2 = DB::type('users')->insertGetId(['name' => 'Jane Doe', 'note1' => 'ABC', 'note2' => 'DEF']);

		DB::type('users')->where('_id', $id1)->unset('note1');

		$user1 = DB::type('users')->find($id1);
		$user2 = DB::type('users')->find($id2);

		$this->assertFalse(isset($user1['note1']));
		$this->assertTrue(isset($user1['note2']));
		$this->assertTrue(isset($user2['note1']));
		$this->assertTrue(isset($user2['note2']));

		DB::type('users')->where('_id', $id2)->unset(['note1', 'note2']);

		$user2 = DB::type('users')->find($id2);
		$this->assertFalse(isset($user2['note1']));
		$this->assertFalse(isset($user2['note2']));
	}

	/**
	 * NOT IMPLEMENTED AT THE MOMENT
	 */
	/*public function testUpdateSubdocument()
	{
		$id = DB::type('users')->insertGetId(['name' => 'John Doe', 'address' => ['country' => 'Belgium']]);

		DB::type('users')->where('_id', $id)->update(['address.country' => 'England']);

		$check = DB::type('users')->find($id);
		$this->assertEquals('England', $check['address']['country']);
	}*/

	/*public function testDates()
	{
		DB::type('users')->insert([
			['name' => 'John Doe', 'birthday' => new MongoDate(strtotime("1980-01-01 00:00:00"))],
			['name' => 'Jane Doe', 'birthday' => new MongoDate(strtotime("1981-01-01 00:00:00"))],
			['name' => 'Robert Roe', 'birthday' => new MongoDate(strtotime("1982-01-01 00:00:00"))],
			['name' => 'Mark Moe', 'birthday' => new MongoDate(strtotime("1983-01-01 00:00:00"))],
		]);

		$user = DB::type('users')->where('birthday', new MongoDate(strtotime("1980-01-01 00:00:00")))->first();
		$this->assertEquals('John Doe', $user['name']);

		$user = DB::type('users')->where('birthday', '=', new DateTime("1980-01-01 00:00:00"))->first();
		$this->assertEquals('John Doe', $user['name']);

		$start = new MongoDate(strtotime("1981-01-01 00:00:00"));
		$stop = new MongoDate(strtotime("1982-01-01 00:00:00"));

		$users = DB::type('users')->whereBetween('birthday', [$start, $stop])->get();
		$this->assertEquals(2, count($users));
	}*/

	/*public function testOperators()
	{
		DB::type('users')->insert([
			['name' => 'John Doe', 'age' => 30],
			['name' => 'Jane Doe'],
			['name' => 'Robert Roe', 'age' => 'thirty-one'],
		]);

		$results = DB::type('users')->where('age', 'exists', true)->get();
		$this->assertEquals(2, count($results));
		$resultsNames = [$results[0]['name'], $results[1]['name']];
		$this->assertContains('John Doe', $resultsNames);
		$this->assertContains('Robert Roe', $resultsNames);

		$results = DB::type('users')->where('age', 'exists', false)->get();
		$this->assertEquals(1, count($results));
		$this->assertEquals('Jane Doe', $results[0]['name']);

		$results = DB::type('users')->where('age', 'type', 2)->get();
		$this->assertEquals(1, count($results));
		$this->assertEquals('Robert Roe', $results[0]['name']);

		$results = DB::type('users')->where('age', 'mod', [15, 0])->get();
		$this->assertEquals(1, count($results));
		$this->assertEquals('John Doe', $results[0]['name']);

		$results = DB::type('users')->where('age', 'mod', [29, 1])->get();
		$this->assertEquals(1, count($results));
		$this->assertEquals('John Doe', $results[0]['name']);

		$results = DB::type('users')->where('age', 'mod', [14, 0])->get();
		$this->assertEquals(0, count($results));

		DB::type('items')->insert([
			['name' => 'fork',  'tags' => ['sharp', 'pointy']],
			['name' => 'spork', 'tags' => ['sharp', 'pointy', 'round', 'bowl']],
			['name' => 'spoon', 'tags' => ['round', 'bowl']],
		]);

		$results = DB::type('items')->where('tags', 'all', ['sharp', 'pointy'])->get();
		$this->assertEquals(2, count($results));

		$results = DB::type('items')->where('tags', 'all', ['sharp', 'round'])->get();
		$this->assertEquals(1, count($results));

		$results = DB::type('items')->where('tags', 'size', 2)->get();
		$this->assertEquals(2, count($results));

		$results = DB::type('items')->where('tags', '$size', 2)->get();
		$this->assertEquals(2, count($results));

		$results = DB::type('items')->where('tags', 'size', 3)->get();
		$this->assertEquals(0, count($results));

		$results = DB::type('items')->where('tags', 'size', 4)->get();
		$this->assertEquals(1, count($results));

		$regex = new MongoRegex("/.*doe/i");
		$results = DB::type('users')->where('name', 'regex', $regex)->get();
		$this->assertEquals(2, count($results));

		$regex = new MongoRegex("/.*doe/i");
		$results = DB::type('users')->where('name', 'regexp', $regex)->get();
		$this->assertEquals(2, count($results));

		$results = DB::type('users')->where('name', 'REGEX', $regex)->get();
		$this->assertEquals(2, count($results));

		$results = DB::type('users')->where('name', 'regexp', '/.*doe/i')->get();
		$this->assertEquals(2, count($results));

		$results = DB::type('users')->where('name', 'not regexp', '/.*doe/i')->get();
		$this->assertEquals(1, count($results));

		DB::type('users')->insert([
			[
				'name' => 'John Doe',
				'addresses' => [
					['city' => 'Ghent'],
					['city' => 'Paris']
				]
			],
			[
				'name' => 'Jane Doe',
				'addresses' => [
					['city' => 'Brussels'],
					['city' => 'Paris']
				]
			]
		]);

		$users = DB::type('users')->where('addresses', 'elemMatch', ['city' => 'Brussels'])->get();
		$this->assertEquals(1, count($users));
		$this->assertEquals('Jane Doe', $users[0]['name']);
	}*/

	/*public function testIncrement()
	{
		DB::type('users')->insert([
			['name' => 'John Doe', 'age' => 30, 'note' => 'adult'],
			['name' => 'Jane Doe', 'age' => 10, 'note' => 'minor'],
			['name' => 'Robert Roe', 'age' => null],
			['name' => 'Mark Moe'],
		]);

		$user = DB::type('users')->where('name', 'John Doe')->first();
		$this->assertEquals(30, $user['age']);

		DB::type('users')->where('name', 'John Doe')->increment('age');
		$user = DB::type('users')->where('name', 'John Doe')->first();
		$this->assertEquals(31, $user['age']);

		DB::type('users')->where('name', 'John Doe')->decrement('age');
		$user = DB::type('users')->where('name', 'John Doe')->first();
		$this->assertEquals(30, $user['age']);

		DB::type('users')->where('name', 'John Doe')->increment('age', 5);
		$user = DB::type('users')->where('name', 'John Doe')->first();
		$this->assertEquals(35, $user['age']);

		DB::type('users')->where('name', 'John Doe')->decrement('age', 5);
		$user = DB::type('users')->where('name', 'John Doe')->first();
		$this->assertEquals(30, $user['age']);

		DB::type('users')->where('name', 'Jane Doe')->increment('age', 10, ['note' => 'adult']);
		$user = DB::type('users')->where('name', 'Jane Doe')->first();
		$this->assertEquals(20, $user['age']);
		$this->assertEquals('adult', $user['note']);

		DB::type('users')->where('name', 'John Doe')->decrement('age', 20, ['note' => 'minor']);
		$user = DB::type('users')->where('name', 'John Doe')->first();
		$this->assertEquals(10, $user['age']);
		$this->assertEquals('minor', $user['note']);

		DB::type('users')->increment('age');
		$user = DB::type('users')->where('name', 'John Doe')->first();
		$this->assertEquals(11, $user['age']);
		$user = DB::type('users')->where('name', 'Jane Doe')->first();
		$this->assertEquals(21, $user['age']);
		$user = DB::type('users')->where('name', 'Robert Roe')->first();
		$this->assertEquals(null, $user['age']);
		$user = DB::type('users')->where('name', 'Mark Moe')->first();
		$this->assertEquals(1, $user['age']);
	}*/


}
