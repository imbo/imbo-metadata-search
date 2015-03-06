<?php

namespace ImboMetadataSearchUnitTest\Dsl;

use Imbo\MetadataSearch\Dsl\Parser,
    Imbo\MetadataSearch\Dsl\Ast\Conjunction,
    Imbo\MetadataSearch\Dsl\Ast\Disjunction,
    Imbo\MetadataSearch\Dsl\Ast\Field,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\Equals,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\NotEquals,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\In,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\NotIn,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\LessThan,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\LessThanEquals,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\GreaterThan,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\GreaterThanEquals;


class ParserTest extends \PHPUnit_Framework_TestCase {
    public function getValidDslQueries() {
        return array(
            'empty string' => array(
                'query' => '',
                'expected' => new Conjunction([]),
            ),
            'minimal text' => array(
                'query' => '{}',
                'expected' => new Conjunction([]),
            ),
            'minimal associative array' => array(
                'query' => [],
                'expected' => new Conjunction([]),
            ),
            'already lowercased' => array(
                'original' => array('foo' => 'bar'),
                'expected' => new Field('foo', new Equals('bar')),
            ),
            'mixed case' => array(
                'original' => array('Foo' => 'Bar'),
                'expected' => new Field('Foo', new Equals('Bar')),
            ),
            'implicit $and at the root, as string' => array(
                'original' => '{"foo": "bar", "baz": "blargh"}',
                'expected' => new Conjunction([
                                  new Field('foo', new Equals('bar')),
                                  new Field('baz', new Equals('blargh'))
                              ]),
            ),
            'root as explicit $and, as string' => array(
                'original' => '{"$and": [{"foo": "bar"}, {"baz": "blargh"}]}',
                'expected' => new Conjunction([
                                  new Field('foo', new Equals('bar')),
                                  new Field('baz', new Equals('blargh'))
                              ]),
            ),
            'implicit $and inside an $or, as string' => array(
                'original' => '{"$or": [{"foo": "bar"}, {"bar": "baz", "baz": "blargh"}]}',
                'expected' => new Disjunction([
                                  new Field('foo', new Equals('bar')),
                                  new Conjunction([
                                      new Field('bar', new Equals('baz')),
                                      new Field('baz', new Equals('blargh'))
                                  ])
                              ]),
            ),
            'a single operator on a field' => array(
                'original' => '{"foo": {"$gt": 5}}',
                'expected' => new Field('foo', new GreaterThan(5)),
            ),
            'multiple operators on a field' => array(
                'original' => '{"foo": {"$gt": 5, "$lte": 15}}',
                'expected' => new Conjunction([
                                  new Field('foo', new GreaterThan(5)),
                                  new Field('foo', new LessThanEquals(15)),
                              ])
            ),
            'a larger, more complex query' => array(
                'original' => '{
                                "name": { "$ne": "Wit" },
                                "$or": [
                                    { "brewery": "Nøgne Ø" },
                                    {
                                        "abv": { "$gte": 5.5 },
                                        "style": { "$in": [ "IPA", "Imperial Stout" ] },
                                        "brewery": { "$in": [ "HaandBryggeriet", "Ægir" ] }
                                    }
                                ]
                               }',
                'expected' => new Conjunction([
                                  new Field('name', new NotEquals('Wit')),
                                  new Disjunction([
                                      new Field('brewery', new Equals('Nøgne Ø')),
                                      new Conjunction([
                                          new Field('abv', new GreaterThanEquals(5.5)),
                                          new Field('style', new In(['IPA', 'Imperial Stout'])),
                                          new Field('brewery', new In(['HaandBryggeriet', 'Ægir'])),
                                      ]),
                                  ]),
                              ]),
            ),
        );
    }

    public function getInvalidDslQueries() {
        return array(
            'invalid json' => array(
                'query' => 'not json',
                'message' => 'Query must be valid JSON',
            ),
            'query is a string' => array(
                'query' => '"a string"',
                'message' => 'Query must be a JSON object or array',
            ),
            'query is a number' => array(
                'query' => 3.1415,
                'message' => 'Query must be a JSON object or array',
            ),
            'unsupported expression' => array(
                'query' => '{"$nor": [{"foo": "bar"}, {"baz": "blargh"}]}',
                'message' => 'Expressions of the type $nor not allowed. Only allowed expressions are: $or, $and',
            ),
            'using a operator as an expression' => array(
                'query' => '{"$in": [1, 2, 3]}',
                'message' => 'Expressions of the type $in not allowed. Only allowed expressions are: $or, $and',
            ),
            'using an operator that is not supported ($regex)' => array(
                'query' => array('category' => array('$regex' => '(foo|bar|baz)')),
                'message' => 'Operator of the type $regex not allowed',
            ),
            'not specifing an operator on a field' => array(
                'query' => '{"category": []}',
                'message' => 'No operations defined for the criteria on "category"',
            ),
            'performing a exact-embedded document search' => array(
                'query' => '{"category": {"foo": "bar", "baz": "blargh"}}',
                'message' => 'Imbo does not support exact matches on embedded documents. Please use dot-syntax instead',
            ),
            'performing a less-than on an array' => array(
                'query' => '{"foo": {"$gt": [1, 2, 3]}}',
                'message' => 'The operator $gt must not be called with an array',
            ),
            'performing a in on a string' => array(
                'query' => '{"foo": {"$in": "a string"}}',
                'message' => 'The operator $in must be called with an array',
            ),
        );
    }

    /**
     * @dataProvider getValidDslQueries
     */
    public function testParserAcceptsValidQueriesUsingAValidDslQuery($query, $expected) {
        $this->assertEquals($expected, Parser::parse($query));
    }

    /**
     * @dataProvider getInvalidDslQueries
     */
    public function testParserThrowsExceptionOnInvalidQueriesUsingAnInvalidDslQuery($query, $expectedError) {
        try {
            Parser::parse($query);
            $this->fail('Expected the query to fail with an exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($expectedError, $e->getMessage());
            return true;
        }
        $this->fail('Expected the query to fail with a InvalidArgumentException exception');
    }
}
