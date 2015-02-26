<?php
namespace Imbo\MetadataSearch\Dsl\Transformations;

use Imbo\MetadataSearch\Interfaces\DslTransformationInterface
  , Imbo\MetadataSearch\Interfaces\DslAstInterface AS AstNode
  , Imbo\MetadataSearch\Dsl\Ast\Conjunction
  , Imbo\MetadataSearch\Dsl\Ast\Disjunction
  , Imbo\MetadataSearch\Dsl\Ast\Field
  , Imbo\MetadataSearch\Dsl\Ast\Comparison\Equals
  , Imbo\MetadataSearch\Dsl\Ast\Comparison\NotEquals
  , Imbo\MetadataSearch\Dsl\Ast\Comparison\In
  , Imbo\MetadataSearch\Dsl\Ast\Comparison\NotIn
  , Imbo\MetadataSearch\Dsl\Ast\Comparison\LessThan
  , Imbo\MetadataSearch\Dsl\Ast\Comparison\LessThanEquals
  , Imbo\MetadataSearch\Dsl\Ast\Comparison\GreaterThan
  , Imbo\MetadataSearch\Dsl\Ast\Comparison\GreaterThanEquals;


class ElasticSearchDsl implements DslTransformationInterface {
    /**
     * Transform a AST node into a instance of the ElasticSearch-php query-DSL.
     * @param Imbo\MetadataSearch\Interfaces\DslAstInterface $query
     * @return array
     */
    public static function transform(AstNode $query) {
        $transformed = self::_transform($query);
        if (key($transformed) !== 'query') {
            $transformed = array('filter' => $transformed);
        }
        return $transformed;
    }

    /**
     * Transform a AST node into a instance of the ElasticSearch-php query-DSL
     * @param Imbo\MetadataSearch\Interfaces\DslAstInterface $query
     * @return array
     */
    public static function _transform(AstNode $query) {
        switch(true) {
            case $query instanceof Conjunction:
                return array('and' => array_map(['self', '_transform'], $query->getArrayCopy()));
            case $query instanceof Disjunction:
                return array('or' => array_map(['self', '_transform'], $query->getArrayCopy()));
            case $query instanceof Field:
                $field = $query->field();
                $comparison = $query->comparison();
                switch (true) {
                    case $comparison instanceof Equals:
                        return array('query' => array('match' => array(
                            $field => $comparison->value(),
                        )));
                    case $comparison instanceof NotEquals:
                        return array('not' => array('query' => array('match' => array(
                            $field => $comparison->value(),
                        ))));
                    case $comparison instanceof In:
                        return array('terms' => array(
                            $field => $comparison->value(),
                        ));
                    case $comparison instanceof NotIn:
                        return array('not' => array('terms' => array(
                            $field => $comparison->value(),
                        )));
                    case $comparison instanceof LessThan:
                        return array('range' => array(
                            $field => array(
                                'lt' => $comparison->value(),
                            ),
                        ));
                    case $comparison instanceof LessThanEquals:
                        return array('range' => array(
                            $field => array(
                                'lte' => $comparison->value(),
                            ),
                        ));
                    case $comparison instanceof GreaterThan:
                        return array('range' => array(
                            $field => array(
                                'gt' => $comparison->value(),
                            ),
                        ));
                    case $comparison instanceof GreaterThanEquals:
                        return array('range' => array(
                            $field => array(
                                'gte' => $comparison->value(),
                            ),
                        ));
                }
        }
    }
}
