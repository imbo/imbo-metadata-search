<?php

namespace Imbo\MetadataSearch\Dsl\Ast\Comparison;

use Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface AS Ast_Comparison;

class Base implements Ast_Comparison {
    private $value;
    public function __construct($value) {
        $this->value = $value;
    }
    public function value() {
        return $this->value;
    }
}
