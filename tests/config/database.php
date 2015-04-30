<?php
return [
'connections' => [
"nicecouch" => array(
    'driver' => 'couchdb',
    'database' => 'nicetest',
    'host' => 'localhost',
    'port' => '5984',
    'username' => 'jace',
    'password' => 'Maverick04',
    'eloquentDesignDocFolder' => __DIR__ . '/../src/designDocuments',
    'eloquentDesignDocName' => 'eloquent',
    'eloquentTypeViewName' => 'TypeView'

)
]]
?>