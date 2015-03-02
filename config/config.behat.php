<?php

namespace Imbo\MetadataSearch;

$config = [
    'auth' => [
        'key' => 'foobar'
    ],

    'database' => function() {
        return new \Imbo\Database\MongoDB();
    },

    'storage' => function() {
        return new \Imbo\Storage\GridFS();
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
                'backend' => new Backend\ElasticSearch(new \Elasticsearch\Client())
            ])
        ],
    ],

    'eventListenerInitializers' => [
        'imagick' => 'Imbo\EventListener\Initializer\Imagick',
    ],

    'transformationPresets' => [],
    'resources' => [],
    'routes' => [],
    'trustedProxies' => [],
];

return $config;
