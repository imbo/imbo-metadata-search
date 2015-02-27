<?php

namespace Imbo\MetadataSearch\EventListener;

use Imbo\EventListener\ListenerInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\MetadataSearch\Interfaces\SearchBackendInterface;

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
        $this->backend->delete($request->getPublicKey(), $request->getImageIdentifier());
    }
}