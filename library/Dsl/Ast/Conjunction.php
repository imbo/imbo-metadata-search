<?php
namespace Imbo\MetadataSearch\Dsl\Ast;

use Imbo\MetadataSearch\Interfaces\DslAstInterface as AstNode
    , ArrayObject;

/**
 * A conjunction (logical AND) node for our AST. Used to represent a list
 * of AST terms that must all hold in the query.
 */
class Conjunction extends ArrayObject implements AstNode  {}