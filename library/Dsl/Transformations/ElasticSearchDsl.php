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
            // A simple query was returned, use it for the query part
            // of the filtered query and set an empty filter
            case 'query':
                return [
                    'query' => [
                        'filtered' => [
                            'query' => $transformed['query'],
                            'filter' => []
                        ]
                    ]
                ];

            default:
                // A filter was returned from transformAstToQuery. Add it
                // to the list of filters and set an empty query.
                return [
                    'query' => [
                        'filtered' => [
                            'query' => [],
                            'filter' => [
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
                return ['and' => array_map([$this, 'transformAstToQuery'], $query->getArrayCopy())];

            case $query instanceof Disjunction:
                // ... and disjunctions into `or`-filters
                return ['or' => array_map([$this, 'transformAstToQuery'], $query->getArrayCopy())];

            case $query instanceof Field:
                // We have a field, so let's look at the type of comparison we
                // are making, to determine how to transform it into a ES-php
                // DSL query
                $field = 'metadata.' . $query->field();
                $comparison = $query->comparison();

                switch (true) {
                    case $comparison instanceof Equals:
                        // Equality we make into `match`-queries
                        return ['query' => ['match' =>
                            [$field => $comparison->value()]
                        ]];

                    case $comparison instanceof NotEquals:
                        // And not-equals we make into a not-filter with a
                        // `match`-filter inside it
                        return ['not' => ['query' => ['match' =>
                            [$field => $comparison->value()]
                        ]]];

                    case $comparison instanceof In:
                        // We make in-set checks into a `terms`-filter
                        return ['terms' => [
                            $field => $comparison->value(),
                        ]];

                    case $comparison instanceof NotIn:
                        // And likewise a not-in into a `not` filter wrapping a
                        // `terms`-filter.
                        return ['not' => ['terms' => [
                            $field => $comparison->value(),
                        ]]];

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