<?php
return [
    'eventListeners' => [
        'metadata' => [
            'listener' => 'Imbo\MetadataSearch\EventListener\MetadataOperations',
            'params' => [
                'backend' => new Imbo\MetadataSearch\Backend\ElasticSearch(),
            ],
        ],
    ],
];