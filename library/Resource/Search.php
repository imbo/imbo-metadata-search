<?php

namespace Imbo\MetadataSearch\Resource;

use Imbo\EventManager\EventInterface,
    Imbo\Resource\ResourceInterface,
    Imbo\Model;

class Search implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return ['HEAD', 'GET'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'search.get' => 'search',
            'search.head' => 'search',
        );
    }

    /**
     * Handle GET and HEAD requests
     *
     * @param EventInterface $event The current event
     */
    public function search(EventInterface $event) {
        $event->getManager()->trigger('metadata.search');
    }
}