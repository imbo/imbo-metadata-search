<?php
namespace Imbo\MetadataSearch\Dsl\Ast;

use Imbo\MetadataSearch\Interfaces\DslAstInterface as AstNode
    , ArrayObject;

class Disjunction extends ArrayObject implements AstNode  {}
