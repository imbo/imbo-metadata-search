<?php
namespace Imbo\MetadataSearch\Dsl\Ast;

use Imbo\MetadataSearch\Interfaces\DslAstInterface as AstNode,
    Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface as AstComparison;

/**
 * A criteria on a fields value. Represents the name of the field and a
 * comparison against a value.
 */
class Field implements AstNode {
    /**
     * @var string The name of the field the criteria is on
     */
    private $field;

    /**
     * @var Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface
     */
    private $comparison;

    /**
     * Construct a new Field criteria.
     *
     * @param string $field Name of the field for the criteria
     * @param Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface $comparison
     * @return Imbo\MetadataSearch\Dsl\Ast\Field
     */
    public function __construct($field, AstComparison $comparison) {
        $this->field = $field;
        $this->comparison = $comparison;
    }

    /**
     * Get the name of the field used in this comparison
     *
     * @return string
     */
    public function field() {
        return $this->field;
    }

    /**
     * Get the comparison that this field criteria is using
     *
     * @return Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface
     */
    public function comparison() {
        return $this->comparison;
    }
}