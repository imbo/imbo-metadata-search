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
    public function set($publicKey, $imageIdentifier, array $metadata) {
        $params = $this->prepareParams($publicKey, $imageIdentifier, $metadata);

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
        $limit = isset($queryParams['limit']) ? (int) $queryParams['limit'] : 10;
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;

        // Ensure limit are both positive values
        $limit = max($limit, 1);
        $page = max($page, 1);

        $astTransformer = new ElasticSearchDsl();

        // Transform AST to ES query
        $query = $astTransformer->transform($ast);

        $params = $this->prepareParams(
            $publicKey,
            null,
            $query
        );

        $params['from'] = ($page - 1) * $limit;
        $params['size'] = $limit;

        try {
            $queryResult = $this->client->search($params);
        } catch (\Exception $e) {
            throw new RuntimeException('Metadata search failed: ' . $e->getMessage(), 503);
        }

        // Create and return search response model
        return new ElasticsearchResponse($queryResult);
    }

    /**
     * Creates a params array that can be consumed by the elasticsearch client
     *
     * @param string $publicKey
     * @param string $imageIdentifier
     * @param array $metadata
     * @return array
     */
    protected function prepareParams($publicKey, $imageIdentifier = null, $body = null) {
        $params = [
            'index' => $this->getIndexName($publicKey),
            'type' => 'metadata'
        ];

        if ($imageIdentifier !== null) {
            $params['id'] = $imageIdentifier;
        }

        if ($body !== null) {
            $params['body'] = $body;
        }

        return $params;
    }

    public function getIndexName($publicKey) {
        return $this->indexPrefix . $publicKey;
    }
}