<?php

namespace Imbo\MetadataSearch\Dsl\Ast\Comparison;

use Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface AS AstComparison,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\Base;

/**
 * A comparison for equality against the stored value
 *
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class Equals extends Base implements AstComparison {}
