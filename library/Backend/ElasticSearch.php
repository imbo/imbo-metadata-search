<?php

namespace Imbo\MetadataSearch\Backend;

use Imbo\MetadataSearch\Dsl\Transformations\ElasticSearchDsl,
    Imbo\MetadataSearch\Interfaces\SearchBackendInterface,
    Imbo\MetadataSearch\Interfaces\DslAstInterface,
    Imbo\MetadataSearch\Model\ElasticsearchResponse,
    Elasticsearch\Client as ElasticsearchClient,
    Imbo\Exception\RuntimeException,
    Exception;

/**
 * Elasticsearch search backend for metadata search
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.net>
 */
class ElasticSearch implements SearchBackendInterface {
    /**
     * @var Elasticsearch\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $indexName;

    public function __construct(ElasticsearchClient $client, $indexName = 'imbo_metadata') {
        $this->client = $client;
        $this->indexName = $indexName;
    }

    /**
     * {@inheritdoc}
     */
    public function set($user, $imageIdentifier, array $imageData) {
        $params = $this->prepareParams($imageIdentifier, $imageData);

        try {
            return !!$this->client->index($params);
        } catch (Exception $e) {
            trigger_error('Elasticsearch metadata indexing failed for image: ' . $imageIdentifier, E_USER_WARNING);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($user, $imageIdentifier) {
        $params = [
            'index' => $this->getIndexName(),
            'type' => 'metadata',
            'id' => $imageIdentifier
        ];

        try {
            return !!$this->client->delete($params);
        } catch (Exception $e) {
            trigger_error('Elasticsearch metadata deletion failed for image: ' . $imageIdentifier, E_USER_WARNING);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function search(array $users, DslAstInterface $ast, array $queryParams) {
        $astTransformer = new ElasticSearchDsl();

        // Transform AST to ES query
        $query = $astTransformer->transform($ast);

        // Get sort array
        $sort = isset($queryParams['sort']) ? $queryParams['sort'] : [];

        $params = $this->prepareParams(
            null,
            $query,
            $sort
        );

        // Set which users to get images from
        $params = $this->addUserFilter($params, $users);

        // Set page and limit
        $params = $this->setPageAndLimit(
            $params,
            $queryParams['page'],
            $queryParams['limit']
        );

        // Limit resultset by creation time
        $params = $this->addDateRangeFilter(
            $params,
            $queryParams['from'],
            $queryParams['to']
        );

        try {
            $queryResult = $this->client->search($params);
        } catch (Exception $e) {
            throw new RuntimeException('Metadata search failed: ' . $e->getMessage(), 503);
        }

        // Create and return search response model
        return new ElasticsearchResponse($queryResult);
    }

    /**
     * Add page and limit to params elasticsearch params array
     *
     * @param array $params Params array
     * @param int $page
     * @param int $limit
     * @return array Modified params array
     */
    protected function setPageAndLimit(array $params, $page, $limit) {
        $params['from'] = ($page - 1) * $limit;
        $params['size'] = $limit;

        return $params;
    }

    /**
     * Add date range filter to params
     *
     * @param array $params Params array
     * @param int $from Start of date range
     * @param int $to End of date range
     * @return array Modified params array
     */
    protected function addDateRangeFilter(array $params, $from = null, $to = null) {
        if (!$from && !$to) {
            return $params;
        }

        $rangeFilter = [
            'range' => [
                'added' => []
            ]
        ];

        if ($from) {
            $rangeFilter['range']['added']['gte'] = $from;
        }

        if ($to) {
            $rangeFilter['range']['added']['lte'] = $to;
        }

        $params['body']['query']['filtered']['filter']['and'][] = $rangeFilter;

        return $params;
    }

    /**
     * Add user filter to params
     *
     * @param array $params Params array
     * @param array $users Users to filter on
     * @return array Modified params array
     */
    protected function addUserFilter(array $params, array $users) {
        $userFilter = [
            'or' => array_map(function($user) {
                return [
                    'term' => [
                        'user' => $user
                    ]
                ];
            }, $users)
        ];

        $params['body']['query']['filtered']['filter']['and'][] = $userFilter;

        return $params;
    }

    /**
     * Creates a params array that can be consumed by the elasticsearch client
     *
     * @param string $imageIdentifier
     * @param array $query
     * @return array
     */
    protected function prepareParams($imageIdentifier = null, $query = null, $sort = []) {
        $params = [
            'index' => $this->getIndexName(),
            'type' => 'metadata',
            'body' => []
        ];

        if ($imageIdentifier !== null) {
            $params['id'] = $imageIdentifier;
        }

        if ($query !== null) {
            $params['body'] = array_merge($params['body'], $query);
        }

        if (isset($params['body']['query']['filtered']['filter'][0])) {
            $params['body']['query']['filtered']['filter'] = [
                'and' => [
                    $params['body']['query']['filtered']['filter'][0]
                ]
            ];
        } else {
            $params['body']['query']['filtered']['filter'] = ['and' => []];
        }

        if ($sort) {
            $params['body']['sort'] = $sort;
        }

        return $params;
    }

    public function getIndexName() {
        return $this->indexName;
    }
}
