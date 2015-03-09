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
 */
class ElasticSearchDsl implements DslTransformationInterface {
    /**
     * Transform a AST node into a instance of the ElasticSearch-php query-DSL.
     *
     * @param Imbo\MetadataSearch\Interfaces\DslAstInterface $query
     * @return array
     */
    public function transform(AstNode $query) {
        $transformed = $this->_transform($query);

        if (key($transformed) !== 'query') {
            // If the outer-most comparison wasn't `query`, it means it was an
            // and or an or, which can only be used in filters, so we ensure
            // the query will be a filter...
            $transformed = array('filter' => $transformed);
        }

        return $transformed;
    }

    /**
     * Transform a AST node into a instance of the ElasticSearch-php query-DSL
     *
     * @param Imbo\MetadataSearch\Interfaces\DslAstInterface $query
     * @return array
     */
    public function _transform(AstNode $query) {
        switch(true) {
            case $query instanceof Conjunction:
                // We map conjunctions into `and`-filters.
                return array('and' => array_map([$this, '_transform'], $query->getArrayCopy()));

            case $query instanceof Disjunction:
                // ... and disjunctions into `or`-filters
                return array('or' => array_map([$this, '_transform'], $query->getArrayCopy()));

            case $query instanceof Field:
                // We have a field, so let's look at the type of comparison we
                // are making, to determine how to transform it into a ES-php
                // DSL query
                $field = 'metadata.' . $query->field();
                $comparison = $query->comparison();

                switch (true) {
                    case $comparison instanceof Equals:
                        // Equality we make into `match`-queries
                        return array('query' => array('match' =>
                            array($field => $comparison->value())
                        ));

                    case $comparison instanceof NotEquals:
                        // And not-equals we make into a not-filter with a
                        // `match`-filter inside it
                        return array('not' => array('query' => array('match' =>
                            array($field => $comparison->value())
                        )));

                    case $comparison instanceof In:
                        // We make in-set checks into a `terms`-filter
                        return array('terms' => array(
                            $field => $comparison->value(),
                        ));

                    case $comparison instanceof NotIn:
                        // And likewise a not-in into a `not` filter wrapping a
                        // `terms`-filter.
                        return array('not' => array('terms' => array(
                            $field => $comparison->value(),
                        )));

                    case $comparison instanceof LessThan:
                        // Less-than we do with a `range`-filter
                        return array('range' => array(
                            $field => array(
                                'lt' => $comparison->value(),
                            ),
                        ));

                    case $comparison instanceof LessThanEquals:
                        // And like-wise with less-than-or-equals
                        return array('range' => array(
                            $field => array(
                                'lte' => $comparison->value(),
                            ),
                        ));

                    case $comparison instanceof GreaterThan:
                        // ... and with greater-than
                        return array('range' => array(
                            $field => array(
                                'gt' => $comparison->value(),
                            ),
                        ));

                    case $comparison instanceof GreaterThanEquals:
                        // and unsurprisingly also with greater-than-or-equals
                        return array('range' => array(
                            $field => array(
                                'gte' => $comparison->value(),
                            ),
                        ));
                }
        }
    }
}