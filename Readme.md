# NiceCouch

An "Eloquent" way to use CouchDB with Laravel.

This library draws heavily from Jens Segers MongoDB Eloquent Model located at https://github.com/jenssegers/laravel-mongodb.

*WARNING* 
This is a work in progress. 

The architecture of CouchDB does not lend itself well to many of the common relations baked into most Eloquent Models. 
With the EloquentCouchdb Model, you can embedOne, embedMany and also use BelongsTo relations. Relations like 
HasOne, HasMany, and Morph simply do not work because of the incompatibility of CouchDB and the Eloquent model. 
Additionally, if you use the QueryBuilder (which the Eloquent model does) you can only use the '_id' field in where 
clauses. If you try to add a clause with any other field, an exception will be thrown. 
Again, this is because CouchDB does not provide an easy way to lookup records based on arbitrary fields.
