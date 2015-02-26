<?php

namespace ImboMetadataSearchUnitTest\Dsl\Transformations;

use Imbo\MetadataSearch\Dsl\Parser
  , Imbo\MetadataSearch\Dsl\Transformations\ElasticSearchDsl;

class ElasticSearchDslTest extends \PHPUnit_Framework_TestCase {
    public function getQueries() {
        return array(
            'a simple query' => array(
                'query' => '{"foo": "bar"}',
                'expected' => array('query' => array('match' => array('foo' => 'bar'))),
            ),
            'another simple query' => array(
                'query' => '{"foo": "bar", "baz": "blargh"}',
                'expected' => array('filter' => array('and' => array(
                                                        array('query' => array('match' => array('foo' => 'bar'))),
                                                        array('query' => array('match' => array('baz' => 'blargh'))),
                                                    ))),
            ),
            'a simple less-than query' => array(
                'query' => '{"foo": {"$lt": 5}}',
                'expected' => array('filter' => array('range' => array('foo' => array('lt' => 5)))),
            ),
        );
    }

    /**
     * @dataProvider getQueries
     */
    public function testTransformationsUsingAQuery($query, $expected) {
        $this->assertEquals($expected, ElasticSearchDsl::transform(Parser::parse($query)));
    }
}
