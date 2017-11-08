<?php

namespace Imbo\MetadataSearch\Dsl;

use Imbo\Exception\InvalidArgumentException,
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

/**
 * Imbo DSL parser
 *
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class Parser {
    /**
     * Valid expressions for metadata queries
     *
     * @var array
     */
    private static $validQueryDslExpressions = [
        '$or'       => true,
        '$and'      => true,
    ];

    /**
     * Valid operators for metadata queries
     *
     * @var array
     */
    private static $validQueryDslOperators = [
        '$gt'       => true,
        '$gte'      => true,
        '$in'       => true,
        '$lt'       => true,
        '$lte'      => true,
        '$ne'       => true,
        '$nin'      => true,
        '$exists'   => true,
        '$wildcard' => false, // We want to support this - but not built yet
    ];

    /**
     * Parse (and validate) a DSL search query.
     *
     * @param $input mixed
     * @throws InvalidArgumentException
     * @return array
     */
    public static function parse($input) {
        if (is_string($input)) {
            $json = json_decode($input, true);
        } else {
            $json = $input;
        }

        if (is_null($json)) {
            if ($input === "") {
                $json = [];
            } else {
                throw new InvalidArgumentException('Query must be valid JSON', 400);
            }
        }

        if (is_string($json) || is_numeric($json)) {
            throw new InvalidArgumentException('Query must be a JSON object or array', 400);
        }

        $normalizedQuery = self::normalizeExpression($json);

        $ast = self::convertQueryToAst($normalizedQuery);

        return $ast;
    }

    /**
     * Normalize a Mongo-subset query into a more normal-form where all
     * expressions have exactly one term in them.
     * Will throw exceptions if the expression wasn't actually a Mongo-subset
     * query
     *
     * @param array $ast A Mongo-subset query
     * @return array Normalized Mongo-subset query.
     * @throws \InvalidArgumentException
     */
    private static function normalizeExpression(array $ast) {
        $result = [];

        if (count($ast) === 1) {
            // The expression we are normalizing already only have one element.
            // This is the form we want, but we may need to normalize the
            // contents of the field.
            $key = key($ast);
            if (substr($key, 0, 1) === '$') {
                // The expression is an actual expression, so we check if its
                // one that we allow
                if (empty(self::$validQueryDslExpressions[$key])) {
                    // It wasn't an allowed expression, so throw an exception
                    throw new InvalidArgumentException('Expressions of the type ' . $key . ' not allowed. Only allowed expressions are: ' . implode(', ', array_keys(self::$validQueryDslExpressions)), 400);
                }

                if (is_array($ast[$key])) {
                    // Now normalize the contents of the expressions
                    $result[$key] = array_map(['self', 'normalizeExpression'], $ast[$key]);
                } else {
                    // Our expression wasn't actually an expression, because it
                    // didn't have any parameters/data associated with it...
                    throw new InvalidArgumentException('Contents of the ' . $key . '-expression is not an array', 400);
                }
            } else {
                // Our expression was a field-operation, so we normalize the it
                // using normalizeField. Normalize field can change the key of
                // the field, if it lifts our $and's.
                list($key, $value) = self::normalizeField($key, $ast[$key]);
                $result = [$key => $value];
            }
        } else {
            // The expression wasn't normalized to only have one term in each
            // expression, so we convert it into a $and-expression where each
            // clause consists of one term. These terms are normalized too.
            $result = ['$and' => []];
            foreach ($ast AS $key => $node) {
                $result['$and'][] = self::normalizeExpression([$key => $node]);
            }
        }

        return $result;
    }

    /**
     * Normalize a field-operation in our Mongo-subset language. This means
     * ensuring that there is only one operation per field. If there is more,
     * they are transformed into $and-expressions of multiple smaller field-
     * operataions.
     *
     * Will throw an exception if the field-operation wasn't actually part of
     * the Mongo-subset language.
     *
     * @throws \InvalidArgumentException
     * @param string $field
     * @param mixed $value
     * @return array (operator-key * operator-value)-tuple
     */
    private static function normalizeField($field, $value) {
        if (is_array($value)) {
            // Our field-operation is a operation on the field

            if (empty($value)) {
                // Except it wasn't, because it was an empty array
                throw new InvalidArgumentException('No operations defined for the criteria on "' . $field . '"', 400);
            }

            $result = [];
            // Iterate over the operations on the field
            foreach ($value AS $key => $value) {
                if (substr($key, 0, 1) !== "$") {
                    // We have found something that wasn't an operation. This
                    // means that it was actually an embedded document that we
                    // do not support.
                    throw new InvalidArgumentException('Imbo does not support exact matches on embedded documents. Please use dot-syntax instead', 400);
                }

                // Check that the operator is allowed
                if (empty(self::$validQueryDslOperators[$key])) {
                    throw new InvalidArgumentException('Operator of the type ' . $key . ' not allowed', 400);
                }

                // Do some type-checking on the value for the operation
                if (in_array($key, ['$in', '$nin'])) {
                    // If the operator is $in or $nin (not in), we check that
                    // the argument given is actually an array
                    if (!is_array($value)) {
                        throw new InvalidArgumentException('The operator ' . $key . ' must be called with an array', 400);
                    }
                } else if (in_array($key, ['$exists'])) {
                    // If the operator is $exists, we check that the argument
                    // given is a boolean
                    if (!is_bool($value)) {
                        throw new InvalidArgumentException('The operator ' . $key . ' must be called with an boolean', 400);
                    }
                } else {
                    // The operator is not working on sets, we the value can't
                    // be an array
                    if (is_array($value)) {
                        throw new InvalidArgumentException('The operator ' . $key . ' must not be called with an array', 400);
                    }
                }

                // Then simply lift the operator out to a seperate operation.
                $result[] = [$field => [$key => $value]];
            }

            if (count($result) === 1) {
                // There was only a single operation defined, so we unlift it
                // to not introduce unnecessary $and-constructs
                return [$field, $result[0][$field]];
            }
            else {
                // There was multiple different operations defined, so we use
                // the liftings, and return it as a '$and'-expression instead
                // of a field-operation
                return ['$and', $result];
            }
        } else {
            // The field-operation is a simple equality-check, so we use it
            // as-is
            return [$field, $value];
        }
    }

    /**
     * Convert a normalized Mongo-subset query into an instance of our DSL AST
     *
     * @param array $query
     * @return Imbo\MetadataSearch\Interfaces\DslAstInterface
     */
    private static function convertQueryToAst(array $query) {
        $key = key($query);
        $value = $query[$key];

        switch ($key) {
            case '$and':
                return new Conjunction(array_map(['self', 'convertQueryToAst'], $value));
            case '$or':
                return new Disjunction(array_map(['self', 'convertQueryToAst'], $value));
            default:
                return new Field($key, self::convertFieldToAst($value));
        }
    }

    /**
     * Converts a field-value into an instance of our DSL AST comparison
     *
     * @param mixed $value
     * @return Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface
     */
    private static function convertFieldToAst($value) {
        if (is_array($value)) {
            $key = key($value);
            $value = $value[$key];
            switch ($key) {
                case '$ne':
                    return new NotEquals($value);
                case '$in':
                    return new In($value);
                case '$nin':
                    return new NotIn($value);
                case '$lt':
                    return new LessThan($value);
                case '$lte':
                    return new LessThanEquals($value);
                case '$gt':
                    return new GreaterThan($value);
                case '$gte':
                    return new GreaterThanEquals($value);
                case '$exists':
                    return new Exists($value);
            }
        } else {
            return new Equals($value);
        }
    }
}
