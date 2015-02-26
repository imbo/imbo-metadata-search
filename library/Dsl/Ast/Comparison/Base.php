<?php

namespace Imbo\MetadataSearch\Dsl\Ast\Comparison;

use Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface AS AstComparison;

class Base implements AstComparison {
    private $value;
    public function __construct($value) {
        $this->value = $value;
    }
    public function value() {
        return $this->value;
    }
}
