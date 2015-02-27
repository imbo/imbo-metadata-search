<?php
namespace Imbo\MetadataSearch\Dsl\Ast;

use Imbo\MetadataSearch\Interfaces\DslAstInterface as AstNode,
    ArrayObject;

/**
 * A disjunction (logical OR) node for our AST. Used to represent a list
 * of AST terms where at least one must hold.
 */
class Disjunction extends ArrayObject implements AstNode  {}