<?php

namespace Imbo\MetadataSearch\Backend;

use Imbo\MetadataSearch\Dsl\Transformations\ElasticSearchDsl,
    Imbo\MetadataSearch\Interfaces\SearchBackendInterface,
    Imbo\MetadataSearch\Interfaces\DslAstInterface,
    Imbo\MetadataSearch\Model\ElasticsearchResponse,
    Elasticsearch\Client as ElasticsearchClient,
    Imbo\Exception\RuntimeException;

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
    protected $indexPrefix;

    public function __construct(ElasticsearchClient $client, $indexPrefix = 'metadata-') {
        $this->client = $client;
        $this->indexPrefix = $indexPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function set($publicKey, $imageIdentifier, array $imageData) {
        $params = $this->prepareParams($publicKey, $imageIdentifier, $imageData);

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
    public function delete($publicKey, $imageIdentifier) {
        $params = $this->prepareParams($publicKey, $imageIdentifier);

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
    public function search($publicKey, DslAstInterface $ast, array $queryParams) {
        $astTransformer = new ElasticSearchDsl();

        // Transform AST to ES query
        $query = $astTransformer->transform($ast);

        // Get sort array
        $sort = isset($queryParams['sort']) ? $queryParams['sort'] : [];

        $params = $this->prepareParams(
            $publicKey,
            null,
            $query,
            $sort
        );

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
        } catch (\Exception $e) {
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

        $rangeFilter = [];

        if ($from) {
            $rangeFilter['gte'] = $from;
        }

        if ($to) {
            $rangeFilter['lte'] = $to;
        }

        $params['body'] = [
            'query' => [
                'filtered' => [
                    'query' => [
                        'match' => $params['body']['query']['match']
                    ],
                    'filter' => [
                        'range' => [
                            'added' => $rangeFilter
                        ]
                    ]
                ]
            ]
        ];

        return $params;
    }

    /**
     * Creates a params array that can be consumed by the elasticsearch client
     *
     * @param string $publicKey
     * @param string $imageIdentifier
     * @param array $metadata
     * @return array
     */
    protected function prepareParams($publicKey, $imageIdentifier = null, $body = null, $sort = []) {
        $params = [
            'index' => $this->getIndexName($publicKey),
            'type' => 'metadata',
            'body' => []
        ];

        if ($imageIdentifier !== null) {
            $params['id'] = $imageIdentifier;
        }

        if ($body !== null) {
            $params['body'] = array_merge($params['body'], $body);
        }

        if ($sort) {
            $params['body']['sort'] = $sort;
        }

        return $params;
    }

    public function getIndexName($publicKey) {
        return $this->indexPrefix . $publicKey;
    }
}