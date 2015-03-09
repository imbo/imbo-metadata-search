<?php

namespace Imbo\MetadataSearch\EventListener;

use Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
    Imbo\Exception\InvalidArgumentException,
    Imbo\MetadataSearch\Interfaces\SearchBackendInterface,
    Imbo\MetadataSearch\Dsl\Parser as DslParser,
    Imbo\Exception\RuntimeException,
    Imbo\Model\Images as ImagesModel,
    Imbo\Model\Image as ImageModel;

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
            'image.delete' => ['delete' => -1000],
            'images.post' => ['set' => -1000],
            'image.post' => ['set' => -1000],
            'metadata.post' => ['set' => -1000],
            'metadata.put' => ['set' => -1000],
            'metadata.delete' => ['set' => -1000],
            'metadata.search' => 'search',
        ];
    }

    public function getImageData($event, $imageIdentifier) {
        $image = new ImageModel();

        $event->getDatabase()->load(
            $event->getRequest()->getPublicKey(),
            $imageIdentifier,
            $image
        );

        // Get image metadata
        $metadata = $event->getDatabase()->getMetadata(
            $event->getRequest()->getPublicKey(),
            $imageIdentifier
        );

        // Set image metadata on the image model
        $image->setMetadata($metadata);

        return $image;
    }

    /**
     * Sorts the images in a response model given a correct order defined by
     * the order of the imageIdentifiers in the second argument
     *
     * @param $responseModel Response containing the images
     * @param string[] List of identifiers as returned from backend
     * @return void
     */
    public function sortSearchResponse($responseModel, $identifiers) {
        $images = $responseModel->getImages();

        $result = [];
        foreach ($images as $image) {
           $key = array_search($image->getImageIdentifier(), $identifiers);
           $result[$key] = $image;
        }

        ksort($result);

        $responseModel->setImages($result);
    }

    /**
     * Update image data
     *
     * @param Imbo\EventListener\ListenerInterface $event The current event
     */
    public function set(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $imageIdentifier = $request->getImageIdentifier();

        // The imageIdentifier was not part of the URL
        if (!$imageIdentifier) {
            $responseData = $response->getModel()->getData();

            if (!isset($responseData['imageIdentifier'])) {
                return;
            }

            $imageIdentifier = $responseData['imageIdentifier'];
        }

        // Get image information
        $image = $this->getImageData($event, $imageIdentifier);

        // Pass image data to the search backend
        $this->backend->set(
            $request->getPublicKey(),
            $imageIdentifier,
            [
                'publicKey' => $request->getPublicKey(),
                'size' => $image->getFilesize(),
                'extension' => $image->getExtension(),
                'mime' => $image->getMimeType(),
                'metadata' => $image->getMetadata(),
                'added' => $image->getAddedDate()->getTimestamp(),
                'updated' => $image->getUpdatedDate()->getTimestamp(),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
            ]
        );
    }

    /**
     * Remove image information (DELETE) handler
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

        if ($queryParams['page'] < 1) {
            throw new RuntimeException('Invalid param. "page" must be a positive number.', 400);
        }

        if ($queryParams['limit'] < 1) {
            throw new RuntimeException('Invalid param. "limit" must be a positive number.', 400);
        }

        // Parse the query JSON and transform it to an AST
        $ast = DslParser::parse($metadataQuery);

        // Query backend using the AST
        $backendResponse = $this->backend->search(
            $request->getPublicKey(),
            $ast,
            $queryParams
        );

        // If we didn't get hits in the search backend, prepare a response
        if (!$backendResponse->getImageIdentifiers()) {
            // Create the model and set some pagination values
            $model = new ImagesModel();
            $model->setLimit($queryParams['limit'])
                  ->setPage($queryParams['page'])
                  ->setHits($backendResponse->getHits());

            $response = $event->getResponse();
            $response->setModel($model);

            return;
        }

        $imageIdentifiers = $backendResponse->getImageIdentifiers();

        // Set the ids to fetch from the Imbo backend
        $params->set('ids', $imageIdentifiers);

        // In order to paginate the already paginated resultset, we'll
        // set the page param to 0 before triggering db.images.load
        $params->set('page', 0);

        // Trigger image loading from imbo DB
        $event->getManager()->trigger('db.images.load');
        $responseModel = $event->getResponse()->getModel();

        // Set the actual page used for querying search backend on the response
        $responseModel->setPage($queryParams['page']);
        $responseModel->setHits($backendResponse->getHits());

        // Sort the response image so they match the order of identifiers
        // returned from search backend
        $this->sortSearchResponse($responseModel, $imageIdentifiers);
    }
}