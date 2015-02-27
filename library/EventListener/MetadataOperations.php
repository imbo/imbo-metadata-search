<?php

namespace Imbo\MetadataSearch\EventListener;

use Imbo\EventListener\ListenerInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\MetadataSearch\Interfaces\SearchBackendInterface;
use Imbo\MetadataSearch\Dsl\Parser as DslParser;

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
        // Return event subscriptions and make sure
        // they fire after anything else
        return [
            'metadata.post'   => ['post' => -1000],
            'metadata.put'    => ['put' => -1000],
            'metadata.delete' => ['delete' => -1000],
            'metadata.search' => 'search',
        ];
    }

    /**
     * Partial metadata update (POST) handler
     *
     * @param Imbo\EventListener\ListenerInterface $event The current event
     */
    public function post(EventInterface $event) {
        // Post operation
    }

    /**
     * Add/replace metadata (PUT) handler
     *
     * @param Imbo\EventListener\ListenerInterface $event The current event
     */
    public function put(EventInterface $event) {
        $request = $event->getRequest();
        $metadata = json_decode($request->getContent(), true);

        $this->backend->set(
            $request->getPublicKey(),
            $request->getImageIdentifier(),
            $metadata
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
        $query = $request->getContent();

        $publicKey = $request->getPublicKey();

        $queryParams = [
            'page' => $params->get('page', 1),
            'limit' => $params->get('limit', 20),
            'from' => $params->get('from'),
            'to' => $params->get('to'),
        ];

        // Parse the query JSON and transform it to an AST
        $ast = DslParser::parse($query);

        // Query backend using the AST
        $imageIdentifiers = $this->backend->search($publicKey, $ast, $queryParams);

        // Modify the request parameters on the event here to include an explicit
        // list of ids to fetch from the database backend using db.images.load
    }
}