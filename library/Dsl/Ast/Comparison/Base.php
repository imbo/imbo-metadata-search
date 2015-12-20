<?php

namespace Imbo\MetadataSearch\Dsl\Ast\Comparison;

use Imbo\MetadataSearch\Interfaces\DslAstComparisonInterface AS AstComparison;

/**
 * An abstract base-comparison that just stores a value. The point of the
 * comparisons is simply to have different concrete class-names to match against
 * - not to store the value differenty.
 *
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
abstract class Base implements AstComparison {
    /**
     * @var mixed The value stored for the comparison
     */
    private $value;

    /**
     * Construct a new comparison with a stored value inside it
     *
     * @param mixed $value The value to store with this comparison
     */
    public function __construct($value) {
        $this->value = $value;
    }

    /**
     * Get the value stored for this comparison
     *
     * @return mixed
     */
    public function value() {
        return $this->value;
    }
}
