<?php

namespace ImboMetadataSearchUnitTest\Dsl\Transformations;

use Imbo\MetadataSearch\Dsl\Parser,
    Imbo\MetadataSearch\Dsl\Transformations\ElasticSearchDsl;

class ElasticSearchDslTest extends \PHPUnit_Framework_TestCase {
    private $transformation;

    public function setUp() {
        $this->transformation = new ElasticSearchDsl();
    }

    public function getQueries() {
        return [
            'a single match-query' => [
                'query' => '{"foo": "bar"}',
                'expected' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['match' => ['metadata.foo' => 'bar']]
                            ]
                        ]
                    ]
                ]
            ],
            'two match-queries combined using and' => [
                'query' => '{"foo": "bar", "baz": "blargh"}',
                'expected' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['match' => ['metadata.foo' => 'bar']],
                                ['match' => ['metadata.baz' => 'blargh']]
                            ]
                        ]
                    ]
                ]
            ],
            'a less-than query' => [
                'query' => '{"foo": {"$lt": 5}}',
                'expected' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['range' => ['metadata.foo' => ['lt' => 5]]]
                            ]
                        ]
                    ]
                ]
            ],
            'an or-query' => [
                'query' => '{"$or": [{"foo": "bar"}, {"baz": "blargh"}]}',
                'expected' => [
                    'query' => [
                        'bool' => [
                            'should' => [
                                ['match' => ['metadata.foo' => 'bar']],
                                ['match' => ['metadata.baz' => 'blargh']]
                            ],
                            'minimum_should_match' => 1
                        ]
                    ]
                ]
            ],
            'a not-in-query' => [
                'query' => '{"foo": {"$nin": ["bar", "baz"]}}',
                'expected' => [
                    'query' => [
                        'bool' => [
                            'must_not' => [
                                'terms' => [
                                    'metadata.foo' => ['bar', 'baz']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'Searching for empty strings' => [
                'query' => '{"foo": ""}',
                'expected' => [
                    'query' => [
                        'bool' => [
                            'must_not' => [
                                'wildcard' => [
                                    'metadata.foo' => '*'
                                ],
                            ],
                            'must' => [
                                ['exists' => [
                                    'field' => 'metadata.foo',
                                ]],
                            ]
                        ],
                    ],
                ],
            ],
            'Searching for not-empty strings' => [
                'query' => '{"foo": { "$ne": "" }}',
                'expected' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['wildcard' => [
                                    'metadata.foo' => '*'
                                ]],
                            ],
                        ],
                    ],
                ],
            ],
            'Searching for existing fields' => [
                'query' => '{"foo": {"$exists": true}}',
                'expected' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['exists' => [
                                    'field' => 'metadata.foo'
                                ]],
                            ],
                        ],
                    ],
                ],
            ],
            'Searching for non-existing fields' => [
                'query' => '{"foo": { "$exists": false }}',
                'expected' => [
                    'query' => [
                        'bool' => [
                            'must_not' => [
                                'exists' => [
                                    'field' => 'metadata.foo'
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'a terribly complex query' => [
                'query' => '{
                    "name": {"$ne": "Wit"},
                    "$or": [
                        {"brewery": "Nøgne Ø"},
                        {"$and": [
                            {"abv": {"$gte": 5.5}},
                            {"style": {"$in": ["IPA", "Imperial Stout"]}},
                            {"brewery": {"$in": ["HaandBryggeriet", "Ægir"]}}
                        ]}
                    ]
                }',
                'expected' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['bool' => ['must_not' => ['match' => ['metadata.name' => 'Wit']]]],
                                ['bool' => [
                                    'should' => [
                                        ['match' => ['metadata.brewery' => 'Nøgne Ø']],
                                        ['bool' => ['must' => [
                                            ['range' => ['metadata.abv' => ['gte' => 5.5]]],
                                            ['terms' => ['metadata.style' => ['IPA', 'Imperial Stout']]],
                                            ['terms' => ['metadata.brewery' => ['HaandBryggeriet', 'Ægir']]]
                                        ]]]
                                    ],
                                    'minimum_should_match' => 1
                                ]]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider getQueries
     */
    public function testTransformationsUsingAQuery($query, $expected) {
        $this->assertEquals($expected, $this->transformation->transform(Parser::parse($query)));
    }
}
