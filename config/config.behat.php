<?php

namespace Imbo\MetadataSearch;

use Imbo\Auth\AccessControl as ACL;

$config = [
    'accessControl' => function() {
        return new ACL\Adapter\ArrayAdapter([
            [
                'publicKey' => 'publickey',
                'privateKey' => 'privatekey',
                'acl' => [
                    [
                        'resources' => ACL\Adapter\ArrayAdapter::getReadWriteResources(),
                        'users' => ['publickey', 'user1'],
                    ]
                ]
            ],
            [
                'publicKey' => 'user1',
                'privateKey' => 'privatekey',
                'acl' => [
                    [
                        'resources' => ACL\Adapter\ArrayAdapter::getReadWriteResources(),
                        'users' => ['user1'],
                    ]
                ]
            ]
        ]);
    },

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
                    'metadatasearch_integration'
                )
            ])
        ],
    ],

    'resources' => [],

    'routes' => [],

    'eventListenerInitializers' => [
        'imagick' => 'Imbo\EventListener\Initializer\Imagick',
    ],

    'transformationPresets' => [],

    'trustedProxies' => [],
];

return $config;
