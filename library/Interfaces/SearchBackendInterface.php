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

    /**
     * Query the backend and return imageIdentifiers for matching images
     *
     * The following query params must be provided.
     *
     * page     => Page number
     * limit    => Limit to a number of images pr. page
     * from     => Unix timestamp to fetch from. Pass null to omit
     * to       => Unit timestamp to fetch to. Pass null to omit
     *
     * @param string $publicKey
     * @param Imbo\MetadataSearch\Interfaces/DslAstInterface $ast AST to base querying on
     * @param array $queryParams
     * @return string[] Array with imageIdentifiers
     */
    public function search($publicKey, DslAstInterface $ast, array $queryParams);
}