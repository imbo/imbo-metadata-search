<?php

namespace Imbo\MetadataSearch\EventListener;

use Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
    Imbo\Exception\InvalidArgumentException,
    Imbo\MetadataSearch\Interfaces\SearchBackendInterface,
    Imbo\MetadataSearch\Dsl\Parser as DslParser,
    Imbo\Exception\RuntimeException;

/**
 * Metadata event listener
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.net>
 */
class MetadataOperations implements ListenerInterface {
    /**
     * Imbo\MetadataSearch\Interface\SearchBackendInterface
     */
    private $backend;

    public function __construct(array $params = []) {
        if (!isset($params['backend'])) {
            throw new InvalidArgumentException('No search backend provided for metadata search', 500);
        }

        if (!($params['backend'] instanceof SearchBackendInterface)) {
            throw new InvalidArgumentException('Invalid backend. Backend must implement the SearchBackendInterface', 500);
        }

        $this->backend = $params['backend'];
    }

    public static function getSubscribedEvents() {
        return [
            'metadata.post'   => ['set' => -1000],
            'metadata.put'    => ['set' => -1000],
            'metadata.delete' => ['delete' => -1000],
            'metadata.search' => 'search',
        ];
    }

    /**
     * Update metadata for an image
     *
     * @param Imbo\EventListener\ListenerInterface $event The current event
     */
    public function set(EventInterface $event) {
        $request = $event->getRequest();

        // Get the metadata set by imbo
        $metadata = $event->getResponse()->getModel();

        // Trigger update of metadata in search backend
        $this->backend->set(
            $request->getPublicKey(),
            $request->getImageIdentifier(),
            $metadata->getData()
        );
    }

    /**
     * Remove metadata (DELETE) handler
     *
     * @param Imbo\EventListener\ListenerInterface $event The current event
     */
    public function delete(EventInterface $event) {
        $request = $event->getRequest();

        $this->backend->delete($request->getPublicKey(), $request->getImageIdentifier());
    }

    /**
     * Handle metadata search operation - return list of imageIdentifers
     *
     * page     => Page number. Defaults to 1
     * limit    => Limit to a number of images pr. page. Defaults to 20
     * metadata => Whether or not to include metadata pr. image. Set to 1 to enable
     * query    => urlencoded json data to use in the query
     * from     => Unix timestamp to fetch from
     * to       => Unit timestamp to fetch to
     *
     * @param Imbo\EventListener\ListenerInterface $event The current event
     * @param string[] Array with image identifiers
     */
    public function search(EventInterface $event) {
        $request = $event->getRequest();
        $params = $request->query;

        // Extract query
        $metadataQuery = $params->get('q');;

        // If no metadata is provided, we'll let db.images.load take over
        if (!$metadataQuery) {
            $event->getManager()->trigger('db.images.load');
            return;
        }

        // Check access token
        $event->getManager()->trigger('checkAccessToken');

        // Build query params array
        $queryParams = [
            'page' => $params->get('page', 1),
            'limit' => $params->get('limit', 20),
            'from' => $params->get('from'),
            'to' => $params->get('to'),
        ];

        // Parse the query JSON and transform it to an AST
        try {
            $ast = DslParser::parse($metadataQuery);
        } catch (\Exception $e) {
            throw new RuntimeException('Invalid metadata query', 400);
        }

        // Query backend using the AST
        $backendResponse = $this->backend->search(
            $request->getPublicKey(),
            $ast,
            $queryParams
        );

        // Modify the request params before triggering images load
        $params->set('ids', $backendResponse->getImageIdentifiers());
        $params->set('page', 0);

        $event->getManager()->trigger('db.images.load');
        $responseModel = $event->getResponse()->getModel();

        // Set the actual page used for querying search backend on the response
        $responseModel->setPage($queryParams['page']);
        $responseModel->setHits($backendResponse->getHits());
    }
}