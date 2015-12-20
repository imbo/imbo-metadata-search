<?php

namespace Imbo\MetadataSearch;

use Elasticsearch\ClientBuilder;
use Imbo\Resource;
use Imbo\Auth\AccessControl\Adapter\ArrayAdapter;

$config = require __DIR__ . '/../vendor/imbo/imbo/config/config.default.php';
$config = array_replace_recursive($config, [
    'accessControl' => function() {
        return new ArrayAdapter([
            [
                'publicKey' => 'publickey',
                'privateKey' => 'privatekey',
                'acl' => [
                    [
                        'resources' => Resource::getReadWriteResources(),
                        'users' => ['user', 'user2'],
                    ]
                ]
            ],
            [
                'publicKey' => 'user2',
                'privateKey' => 'privatekey',
                'acl' => [
                    [
                        'resources' => Resource::getReadWriteResources(),
                        'users' => ['user2'],
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

    'eventListeners' => [
        'metadata' => [
            'listener' => new EventListener\MetadataOperations([
                'backend' => new Backend\ElasticSearch(
                    ClientBuilder::create()->build(),
                    [
                        'index' => ['name' => 'metadatasearch_integration'],
                    ]
                )
            ])
        ],
    ],
]);

return $config;
