<?php
namespace Imbo\MetadataSearch\Interfaces;

use Imbo\MetadataSearch\Interfaces\DslAstInterface AS AstNode;

/**
 * Interface for DSL transformation classes. A transformation class is
 * responsible for taking an instance of our query-DSL and transforming it into
 * a query that a certain backend can understand.
 */
interface DslTransformationInterface {
    /**
     * Transform a query-DSL AST into a query in some concrete query-syntax
     *
     * @param Imbo\MetadataSearch\Interfaces\DslAstInterface query
     * @return mixed
     */
    public function transform(AstNode $query);
}