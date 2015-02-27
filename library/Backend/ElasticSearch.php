<?php

namespace Imbo\MetadataSearch\Backend;

use Imbo\MetadataSearch\Interfaces\SearchBackendInterface;
use Imbo\MetadataSearch\Interfaces\DslAstInterface;
use Elasticsearch\Client as ElasticsearchClient;

class ElasticSearch implements SearchBackendInterface {
    /**
     * @var Elasticsearch\Client
     */
    protected $client;

    public function __construct(ElasticsearchClient $client) {
        $this->client = $client;
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

    }

    /**
     * Creates a params array that can be consumed by the elasticsearch client
     *
     * @param string $publicKey
     * @param string $imageIdentifier
     * @param array $metadata
     * @return array
     */
    protected function prepareParams($publicKey, $imageIdentifier, $metadata = null) {
        $params = [
            'index' => 'metadata-' . $publicKey,
            'type' => 'metadata',
            'id' => $imageIdentifier
        ];

        if ($metadata !== null) {
            $params['body'] = $metadata;
        }

        return $params;
    }
}