<?php

namespace ImboMetadataSearchUnitTest\Dsl\Transformations;

use Imbo\MetadataSearch\Dsl\Parser,
    Imbo\MetadataSearch\Dsl\Transformations\ElasticSearchDsl;

class ElasticSearchDslTest extends \PHPUnit_Framework_TestCase {
    private $transformation;

    public function setUp() {
        $this->transformation = new ElasticSearchDsl();
    }

    protected function buildExpectedQuery($query = [], $filters = []) {
        return [
            'query' => [
                'filtered' => [
                    'query' => $query,
                    'filter' => $filters
                ]
            ]
        ];
    }

    public function getQueries() {
        return [
            'a simple query' => [
                'query' => '{"foo": "bar"}',
                'expected' => $this->buildExpectedQuery(['match' => ['metadata.foo' => 'bar']], []),
            ],
            'another simple query' => [
                'query' => '{"foo": "bar", "baz": "blargh"}',
                'expected' => $this->buildExpectedQuery([], [['and' => [
                                                                ['query' => ['match' => ['metadata.foo' => 'bar']]],
                                                                ['query' => ['match' => ['metadata.baz' => 'blargh']]],
                                                            ]]]),
            ],
            'a simple less-than query' => [
                'query' => '{"foo": {"$lt": 5}}',
                'expected' => $this->buildExpectedQuery([], [['range' => ['metadata.foo' => ['lt' => 5]]]]),
            ],
        ];
    }

    /**
     * @dataProvider getQueries
     */
    public function testTransformationsUsingAQuery($query, $expected) {
        $this->assertEquals($expected, $this->transformation->transform(Parser::parse($query)));
    }
}
