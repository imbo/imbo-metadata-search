<?php
namespace Imbo\MetadataSearch\Dsl\Ast;

use Imbo\MetadataSearch\Interfaces\DslAstInterface as AstNode
  , Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface as AstComparison;

class Field implements AstNode {
    private $field;
    private $comparison;
    public function __construct($field, AstComparison $comparison) {
        $this->field = $field;
        $this->comparison = $comparison;
    }

    public function field() {
        return $this->field;
    }

    public function comparison() {
        return $this->comparison;
    }
}
