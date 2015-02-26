<?php

namespace Imbo\MetadataSearch\Backend;

use Imbo\MetadataSearch\Interfaces\SearchBackendInterface;

class ElasticSearch implements SearchBackendInterface {
    /**
     * {@inheritdoc}
     */
    public function set($publicKey, $imageIdentifier, array $metadata) {

    }

    /**
     * {@inheritdoc}
     */
    public function delete($publicKey, $imageIdentifier) {

    }
}