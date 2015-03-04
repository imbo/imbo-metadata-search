<?php

namespace Imbo\MetadataSearch\Backend;

use Imbo\MetadataSearch\Interfaces\SearchBackendInterface,
    Imbo\MetadataSearch\Interfaces\DslAstInterface,
    Imbo\MetadataSearch\Model\BackendResponse,
    Elasticsearch\Client as ElasticsearchClient;

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
        // Transform $ast to ES query here, query and return imageIdentifers

        $response = new BackendResponse();

        $response->setImageIdentifiers([
            'a43e8662ed3476e0e22f80c01b0b28d8'
        ]);

        $response->setHits(1);

        return $response;
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