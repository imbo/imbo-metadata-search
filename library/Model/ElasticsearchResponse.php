<?php

namespace Imbo\MetadataSearch\Model;

/**
 * Elasticsearch backend search response
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 */
class ElasticsearchResponse extends BackendResponse {
    /**
     * Search response constructor for elasticserach
     *
     * @param array $queryResult Elasticsearch query result
     */
    public function __construct(array $queryResult = []) {
        $identifiers = array_map(function($hit) {
            return $hit['_id'];
        }, $queryResult['hits']['hits']);

        $this->setImageIdentifiers($identifiers);
        $this->setHits($queryResult['hits']['total']);
    }
}