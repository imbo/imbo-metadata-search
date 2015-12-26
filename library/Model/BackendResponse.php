<?php

namespace Imbo\MetadataSearch\Model;

use Imbo\Model\ModelInterface;

/**
 * Search backend response wrapper
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 */
class BackendResponse implements ModelInterface {
    /**
     * Image identifiers returned from backend
     *
     * @var string[]
     */
    private $imageIdentifiers = [];

    /**
     * Query hits
     *
     * @var int
     */
    private $hits;

    /**
     * Set the image identifiers
     *
     * @param string[]
     * @return self
     */
    public function setImageIdentifiers(array $imageIdentifiers) {
        $this->imageIdentifiers = $imageIdentifiers;

        return $this;
    }

    /**
     * Get the image identifiers
     *
     * @return string[]
     */
    public function getImageIdentifiers() {
        return $this->imageIdentifiers;
    }

    /**
     * Set the hits property
     *
     * @param int $hits The amount of query hits
     * @return self
     */
    public function setHits($hits) {
        $this->hits = (int) $hits;

        return $this;
    }

    /**
     * Get the hits property
     *
     * @return int
     */
    public function getHits() {
        return $this->hits;
    }
}
