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
    Imbo\MetadataSearch\Dsl\Ast\Comparison\GreaterThanEquals,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\Exists;


class ParserTest extends \PHPUnit_Framework_TestCase {
    public function getValidDslQueries() {
        return [
            'empty string' => [
                'query' => '',
                'expected' => new Conjunction([]),
            ],
            'minimal text' => [
                'query' => '{}',
                'expected' => new Conjunction([]),
            ],
            'minimal associative array' => [
                'query' => [],
                'expected' => new Conjunction([]),
            ],
            'already lowercased' => [
                'original' => ['foo' => 'bar'],
                'expected' => new Field('foo', new Equals('bar')),
            ],
            'mixed case' => [
                'original' => ['Foo' => 'Bar'],
                'expected' => new Field('Foo', new Equals('Bar')),
            ],
            'implicit $and at the root, as string' => [
                'original' => '{"foo": "bar", "baz": "blargh"}',
                'expected' => new Conjunction([
                                  new Field('foo', new Equals('bar')),
                                  new Field('baz', new Equals('blargh'))
                              ]),
            ],
            'root as explicit $and, as string' => [
                'original' => '{"$and": [{"foo": "bar"}, {"baz": "blargh"}]}',
                'expected' => new Conjunction([
                                  new Field('foo', new Equals('bar')),
                                  new Field('baz', new Equals('blargh'))
                              ]),
            ],
            'implicit $and inside an $or, as string' => [
                'original' => '{"$or": [{"foo": "bar"}, {"bar": "baz", "baz": "blargh"}]}',
                'expected' => new Disjunction([
                                  new Field('foo', new Equals('bar')),
                                  new Conjunction([
                                      new Field('bar', new Equals('baz')),
                                      new Field('baz', new Equals('blargh'))
                                  ])
                              ]),
            ],
            'a single operator on a field' => [
                'original' => '{"foo": {"$gt": 5}}',
                'expected' => new Field('foo', new GreaterThan(5)),
            ],
            'multiple operators on a field' => [
                'original' => '{"foo": {"$gt": 5, "$lte": 15}}',
                'expected' => new Conjunction([
                                  new Field('foo', new GreaterThan(5)),
                                  new Field('foo', new LessThanEquals(15)),
                              ])
            ],
            'in and not-in is accepted with arrays' => [
                'original' => '{"$or": [{"field": {"$in": [1, 2, 3]}}, {"field": {"$nin": [5, 6 ,7]}}]}',
                'expected' => new Disjunction([
                                  new Field('field', new In([1, 2, 3])),
                                  new Field('field', new NotIn([5, 6, 7])),
                              ])
            ],
            'exists is accepted with booleans' => [
                'original' => '{"foo": {"$exists": true}}',
                'expected' => new Field('foo', new Exists(true))
            ],
            'a larger, more complex query' => [
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
            ],
        ];
    }

    public function getInvalidDslQueries() {
        return [
            'invalid json' => [
                'query' => 'not json',
                'message' => 'Query must be valid JSON',
            ],
            'query is a string' => [
                'query' => '"a string"',
                'message' => 'Query must be a JSON object or array',
            ],
            'query is a number' => [
                'query' => 3.1415,
                'message' => 'Query must be a JSON object or array',
            ],
            'unsupported expression' => [
                'query' => '{"$nor": [{"foo": "bar"}, {"baz": "blargh"}]}',
                'message' => 'Expressions of the type $nor not allowed. Only allowed expressions are: $or, $and',
            ],
            'using a operator as an expression' => [
                'query' => '{"$in": [1, 2, 3]}',
                'message' => 'Expressions of the type $in not allowed. Only allowed expressions are: $or, $and',
            ],
            'using an operator that is not supported ($regex)' => [
                'query' => ['category' => ['$regex' => '(foo|bar|baz)']],
                'message' => 'Operator of the type $regex not allowed',
            ],
            'specifying a non-array for a operator' => [
                'query' => '{"$or": 3}',
                'message' => 'Contents of the $or-expression is not an array',
            ],
            'not specifing an operator on a field' => [
                'query' => '{"category": []}',
                'message' => 'No operations defined for the criteria on "category"',
            ],
            'performing a exact-embedded document search' => [
                'query' => '{"category": {"foo": "bar", "baz": "blargh"}}',
                'message' => 'Imbo does not support exact matches on embedded documents. Please use dot-syntax instead',
            ],
            'performing a less-than on an array' => [
                'query' => '{"foo": {"$gt": [1, 2, 3]}}',
                'message' => 'The operator $gt must not be called with an array',
            ],
            'performing a in on a string' => [
                'query' => '{"foo": {"$in": "a string"}}',
                'message' => 'The operator $in must be called with an array',
            ],
            'performing an exists on a string' => [
                'query' => '{"foo": {"$exists": "yes"}}',
                'message' => 'The operator $exists must be called with an boolean'
            ]
        ];
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
