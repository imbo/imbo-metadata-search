<?php

namespace Imbo\MetadataSearch\Dsl\Transformations;

use Imbo\MetadataSearch\Interfaces\DslTransformationInterface,
    Imbo\MetadataSearch\Interfaces\DslAstInterface AS AstNode,
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

/**
 * A transformation from our query-DSL into the ElasticSearch-php query-DSL.
 *
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class ElasticSearchDsl implements DslTransformationInterface {
    /**
     * Transform a AST node into a instance of the ElasticSearch-php query-DSL.
     *
     * @param Imbo\MetadataSearch\Interfaces\DslAstInterface $query
     * @return array
     */
    public function transform(AstNode $query) {
        $transformed = $this->transformAstToQuery($query);

        switch (key($transformed)) {
            // A compound-query was returned, so we simply need to wrap it in
            // a `query`-clause.
            case 'bool':
                return [
                    'query' => $transformed,
                ];

            default:
                // A simple query was returned. We upcast it to a `bool`-query,
                // so that it will be easier for the ES backend to add filters
                // to it (e.g. filter to a specific username).
                return [
                    'query' => [
                        'bool' => [
                            'must' => [
                                $transformed
                            ]
                        ]
                    ]
                ];
        }
    }

    /**
     * Transform a AST node into a instance of the ElasticSearch-php query-DSL
     *
     * @param Imbo\MetadataSearch\Interfaces\DslAstInterface $query
     * @return array
     */
    public function transformAstToQuery(AstNode $query) {
        switch (true) {
            case $query instanceof Conjunction:
                // We map conjunctions into `and`-filters.
                return ['bool' => [
                    'must' => array_map([$this, 'transformAstToQuery'], $query->getArrayCopy())
                ]];

            case $query instanceof Disjunction:
                // ... and disjunctions into `or`-filters
                return ['bool' => [
                    'should' => array_map([$this, 'transformAstToQuery'], $query->getArrayCopy()),
                    'minimum_should_match' => 1
                ]];

            case $query instanceof Field:
                // We have a field, so let's look at the type of comparison we
                // are making, to determine how to transform it into a ES-php
                // DSL query
                $field = 'metadata.' . $query->field();
                $comparison = $query->comparison();

                switch (true) {
                    case $comparison instanceof Equals:
                        // Equality we make into `match`-queries
                        return ['match' =>
                            [$field => $comparison->value()]
                        ];

                    case $comparison instanceof NotEquals:
                        // And not-equals we make into a `bool`-filter with a
                        // `must_not`-clause containing a `match`-qyuery inside
                        // it
                        return ['bool' => ['must_not' => ['match' =>
                            [$field => $comparison->value()]
                        ]]];

                    case $comparison instanceof In:
                        // We make in-set checks into a `terms`-filter
                        return ['terms' => [
                            $field => $comparison->value(),
                        ]];

                    case $comparison instanceof NotIn:
                        // And likewise a not-in into a `bool`-filter with a
                        // `must_not`-clause containing a `terms`-filter.
                        return ['bool' => ['must_not' => ['terms' => [
                            $field => $comparison->value(),
                        ]]]];

                    case $comparison instanceof LessThan:
                        // Less-than we do with a `range`-filter
                        return ['range' => [
                            $field => [
                                'lt' => $comparison->value(),
                            ],
                        ]];

                    case $comparison instanceof LessThanEquals:
                        // And like-wise with less-than-or-equals
                        return ['range' => [
                            $field => [
                                'lte' => $comparison->value(),
                            ],
                        ]];

                    case $comparison instanceof GreaterThan:
                        // ... and with greater-than
                        return ['range' => [
                            $field => [
                                'gt' => $comparison->value(),
                            ],
                        ]];

                    case $comparison instanceof GreaterThanEquals:
                        // and unsurprisingly also with greater-than-or-equals
                        return ['range' => [
                            $field => [
                                'gte' => $comparison->value(),
                            ],
                        ]];
                }
        }
    }
}
