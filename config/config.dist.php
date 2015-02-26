<?php

namespace Imbo\MetadataSearch;

return [
    'eventListeners' => [
        'metadata' => [
            'listener' => 'Imbo\MetadataSearch\EventListener\MetadataOperations',
            'params' => [
                'backend' => new Backend\ElasticSearch(new \Elasticsearch\Client()),
            ],
        ],
    ],
];