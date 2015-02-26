<?php
namespace Imbo\MetadataSearch\Interfaces;

use Imbo\MetadataSearch\Interfaces\DslAstInterface AS AstNode;

interface DslTransformationInterface {
    public static function transform(AstNode $query);
}
