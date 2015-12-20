<?php

namespace Imbo\MetadataSearch\Dsl\Ast\Comparison;

use Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface AS AstComparison,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\Base;

/**
 * A comparison for in-set against the stored value
 *
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class In extends Base implements AstComparison {}
