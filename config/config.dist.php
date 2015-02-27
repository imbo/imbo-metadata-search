<?php

namespace Imbo\MetadataSearch;

return [
    'eventListeners' => [
        'metadata' => [
            'listener' => new EventListener\MetadataOperations([
                'backend' => new Backend\ElasticSearch(new \Elasticsearch\Client())
            ])
        ],
    ],
];