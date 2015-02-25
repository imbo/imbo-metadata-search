<?php

namespace Imbo\MetadataSearch\EventListener;

use Imbo\EventListener\ListenerInterface;
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
        return [
            'metadata.post' => 'post',
            'metadata.put' => 'put',
            'metadata.delete' => 'delete'
        ];
    }

    /**
     * Partial metadata update (POST) handler
     *
     * @param EventInterface $event The current event
     */
    public function post($event) {
        // Post operation
    }

    /**
     * Add/replace metadata (PUT) handler
     *
     * @param EventInterface $event The current event
     */
    public function put($event) {
        // Post operation
    }

    /**
     * Remove metadata (DELETE) handler
     *
     * @param EventInterface $event The current event
     */
    public function delete($event) {
        // Post operation
    }
}