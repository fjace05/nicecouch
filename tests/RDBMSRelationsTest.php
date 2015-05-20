<?php namespace Jferguson\EloquentCouchdb\testing;

class RDBMSRelationsTest extends TestCase {

    public function setUp()
    {
        parent::setUp();

        RDBMSUser::executeSchema();
        RDBMSBook::executeSchema();
        RDBMSRole::executeSchema();
    }

    public function tearDown()
    {
        RDBMSUser::truncate();
        RDBMSBook::truncate();
        RDBMSRole::truncate();
        Book::truncate();
        Role::truncate();
    }

    public function testRDBMSRelations()
    {
        $user = new RDBMSUser;
        $this->assertInstanceOf('RDBMSUser', $user);
        $this->assertInstanceOf('Illuminate\Database\SQLiteConnection', $user->getConnection());

        // RDBMS User
        $user->name = "John Doe";
        $user->save();
        $this->assertTrue(is_int($user->id));

        // SQL has many
        $book = new Book(['title' => 'Game of Thrones']);
        $user->books()->save($book);
        $user = RDBMSUser::find($user->id); // refetch
        $this->assertEquals(1, count($user->books));

        // MongoDB belongs to
        $book = $user->books()->first(); // refetch
        $this->assertEquals('John Doe', $book->rdbmsAuthor->name);

        // SQL has one
        $role = new Role(['type' => 'admin']);
        $user->role()->save($role);
        $user = RDBMSUser::find($user->id); // refetch
        $this->assertEquals('admin', $user->role->type);

        // MongoDB belongs to
        $role = $user->role()->first(); // refetch
        $this->assertEquals('John Doe', $role->rdbmsUser->name);

        // MongoDB User
        $user = new User;
        $user->name = "John Doe";
        $user->save();

        // MongoDB has many
        $book = new RDBMSBook(['title' => 'Game of Thrones']);
        $user->rdbmsBooks()->save($book);
        $user = User::find($user->_id); // refetch
        $this->assertEquals(1, count($user->rdbmsBooks));

        // SQL belongs to
        $book = $user->rdbmsBooks()->first(); // refetch
        $this->assertEquals('John Doe', $book->author->name);

        // MongoDB has one
        $role = new RDBMSRole(['type' => 'admin']);
        $user->rdbmsRole()->save($role);
        $user = User::find($user->_id); // refetch
        $this->assertEquals('admin', $user->rdbmsRole->type);

        // SQL belongs to
        $role = $user->rdbmsRole()->first(); // refetch
        $this->assertEquals('John Doe', $role->user->name);
    }
}
