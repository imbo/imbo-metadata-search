<?php

namespace Imbo\MetadataSearch\Interfaces;

/**
 * Metadata search backend interface
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.net>
 */
interface SearchBackendInterface {
    /**
     * Set metadata for an image in the metadata search backend
     *
     * @param string $publicKey Public key the image belongs to
     * @param string $imageIdentifier Image identifier
     * @param array $metadata Metadata array containing key value pairs
     * @return bool
     */
    public function set($publicKey, $imageIdentifier, array $metadata);

    /**
     * Delete metadata for an image in the metadata search backend
     *
     * @param string $publicKey Public key the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return bool
     */
    public function delete($publicKey, $imageIdentifier);
}