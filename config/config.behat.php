<?php

namespace Imbo\MetadataSearch;

$config = [
    'auth' => [
        'publickey' => 'privatekey'
    ],

    'database' => function() {
        return new \Imbo\Database\MongoDB([
            'databaseName' => 'metadatasearch_integration_db'
        ]);
    },

    'storage' => function() {
        return new \Imbo\Storage\GridFS([
            'databaseName' => 'metadatasearch_integration_storage'
        ]);
    },

    'contentNegotiateImages' => true,

    'eventListeners' => [
        'accessToken' => 'Imbo\EventListener\AccessToken',
        'auth' => 'Imbo\EventListener\Authenticate',
        'statsAccess' => [
            'listener' => 'Imbo\EventListener\StatsAccess',
            'params' => [
                'allow' => ['127.0.0.1', '::1'],
            ],
        ],

        'imagick' => 'Imbo\EventListener\Imagick',

        'metadata-access-token' => new EventListener\AccessToken(),
        'metadata' => [
            'listener' => new EventListener\MetadataOperations([
                'backend' => new Backend\ElasticSearch(
                    new \Elasticsearch\Client(),
                    'metadata_integration-'
                )
            ])
        ],
    ],

    'resources' => [
        'search' => new Resource\Search(),
    ],

    'routes' => [
        'search' => '#^/(?<publicKey>[a-z0-9_-]{3,})/search(\.(?<extension>json|xml))?$#',
    ],

    'eventListenerInitializers' => [
        'imagick' => 'Imbo\EventListener\Initializer\Imagick',
    ],

    'transformationPresets' => [],

    'trustedProxies' => [],
];

return $config;
