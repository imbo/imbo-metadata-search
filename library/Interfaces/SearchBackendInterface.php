<?php

namespace Imbo\MetadataSearch\Interfaces;

/**
 * Metadata search backend interface
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.net>
 */
interface SearchBackendInterface {
    /**
     * Set data for an image in the search backend
     *
     * @param string $user What user the image belongs to
     * @param string $imageIdentifier Image identifier
     * @param array $imageData Image data array containing key value pairs
     * @return bool
     */
    public function set($user, $imageIdentifier, array $imageData);

    /**
     * Delete metadata for an image in the metadata search backend
     *
     * @param string $user What user the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return bool
     */
    public function delete($user, $imageIdentifier);

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
     * @param string[] $users Users whom images we should search
     * @param Imbo\MetadataSearch\Interfaces/DslAstInterface $ast AST to base querying on
     * @param array $queryParams
     * @return Imbo\MetadataSearch\Model\BackendResponse
     */
    public function search(array $users, DslAstInterface $ast, array $queryParams);
}
