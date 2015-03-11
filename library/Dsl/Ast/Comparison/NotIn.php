<?php

namespace Imbo\MetadataSearch\Dsl\Ast\Comparison;

use Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface AS AstComparison,
    Imbo\MetadataSearch\Dsl\Ast\Comparison\Base;

/**
 * A comparison against in-set with the stored value
 *
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class NotIn extends Base implements AstComparison {}