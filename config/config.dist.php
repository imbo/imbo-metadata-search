<?php

namespace Imbo\MetadataSearch;

return [
    'eventListeners' => [
        'metadata-access-token' => new EventListener\AccessToken(),
        'metadata' => [
            'listener' => new EventListener\MetadataOperations([
                'backend' => new Backend\ElasticSearch(new \Elasticsearch\Client())
            ])
        ],
    ],

    'resources' => [
        'search' => new Resource\Search(),
    ],

    'routes' => [
        'search' => '#^/search(\.(?<extension>json|xml))?$#',
    ],
];