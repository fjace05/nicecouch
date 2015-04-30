<?php namespace Jferguson\CouchDB;

use Doctrine\CouchDB\CouchDBClient as BaseClient;

class CouchDBClient extends BaseClient 
{
 
/**
     * Find a document by ID and revision and return the HTTP response.
     *
     * @param  string $id
     * @param string rev
     * @return HTTP\Response
     */
    public function findDocumentRevision($id, $rev)
    {
        $documentPath = '/' . $this->databaseName . '/' . urlencode($id) . "?rev=" . urlencode($rev);
        return $this->getHttpClient()->request( 'GET', $documentPath );
    }
}


?>