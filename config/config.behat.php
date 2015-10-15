<?php

namespace Imbo\MetadataSearch;

use Imbo\Auth\AccessControl as ACL;

$config = require __DIR__ . '/../vendor/imbo/imbo/config/config.default.php';
$config = array_replace_recursive($config, [
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

    'eventListeners' => [
        'metadata' => [
            'listener' => new EventListener\MetadataOperations([
                'backend' => new Backend\ElasticSearch(
                    new \Elasticsearch\Client(),
                    'metadatasearch_integration'
                )
            ])
        ],
    ],
]);

return $config;
