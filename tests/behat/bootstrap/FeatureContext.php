<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Gherkin\Node\StepNode;
use Assert\Assertion;
use Elasticsearch\Client as ElasticsearchClient;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RESTContext implements Context, SnippetAcceptingContext
{
    public function __construct($url, $documentRoot, $router, $timeout) {
        parent::__construct($url, $documentRoot, $router, $timeout);

        $this->elasticsearch = new ElasticsearchClient();
    }

    /**
     * @BeforeScenario
     * @AfterSuite
     */
    public static function cleanup() {
        // Drop mongo test databases
        $mongo = new MongoClient();
        $mongo->metadatasearch_integration_db->drop();
        $mongo->metadatasearch_integration_storage->drop();

        // Delete the elasticsearch metadata test index
        $elasticsearch = new ElasticsearchClient();

        try {
            $elasticsearch->indices()->delete([
                'index' => 'metadatasearch_integration-*'
            ]);
        } catch (Exception $e) {
            // We'll get a 404 if the index is non-existant - ignore it
            if ($e->getCode() === 404) {
                return;
            }

            // If this was something other than a 404 it's more interesting
            throw $e;
        }
    }

    /**
     * @Given The following images exist in Imbo:
     */
    public function theFollowingImagesExistInImbo(TableNode $images)
    {
        foreach ($images as $image) {
            $this->addImageToImbo($image['file'], json_decode($image['metadata'], true));
        }
    }

    /**
     * @Given I have flushed the elasticsearch transaction log
     */
    public function flushElasticsearch() {
        $this->elasticsearch->indices()->flush();
    }

    /**
     * @Given /^"([^"]*)" exists in Imbo with metadata (.*)$/
     */
    public function addImageToImbo($imagePath, $metadata) {
        $res = $this->imbo->addImage($imagePath);

        if ($metadata) {
            $this->setImageMetadata($res['imageIdentifier'], $metadata);
        }
    }

    /**
     * @When /^I set the following metadata on an image with identifier "([^"]*)":$/
     */
    public function setImageMetadata($imageIdentifier, $metadata) {
        if ($metadata instanceof PyStringNode) {
            $metadata = json_decode($metadata, true);
        }

        $this->imbo->replaceMetadata($imageIdentifier, $metadata);
    }

    /**
     * @Given I patch the metadata of the image with identifier :imageIdentifer with:
     */
    public function iPatchTheMetadataOfTheImageWithIdentifierWith($imageIdentifer, PyStringNode $metadata)
    {
        if ($metadata instanceof PyStringNode) {
            $metadata = json_decode($metadata, true);
        }

        $this->imbo->editMetadata($imageIdentifer, $metadata);
    }

    /**
     * @Then Elasticsearch should have the following metadata for :imageIdentifer:
     */
    public function elasticsearchShouldHaveTheFollowingMetadataFor($imageIdentifer, PyStringNode $metadata)
    {
        $publicKey = 'publickey';

        $params = [
            'index' => 'metadatasearch_integration-' . $publicKey,
            'type' => 'metadata',
            'id' => $imageIdentifer
        ];

        $retDoc = $this->elasticsearch->get($params);

        if ($metadata && !$retDoc) {
            throw new \Exception('Image metadata for ' . $imageIdentifer . ' not found in ES');
        }

        Assertion::eq(json_encode($retDoc['_source']), (string) $metadata);
    }

    /**
     * @Then Elasticsearch should not have metadata for :imageIdentifer
     */
    public function elasticsearchShouldNotHaveMetadataFor($imageIdentifer)
    {
        $publicKey = 'publickey';

        $params = [
            'index' => 'metadatasearch_integration-' . $publicKey,
            'type' => 'metadata',
            'id' => $imageIdentifer
        ];

        try {
            $this->elasticsearch->get($params);
        } catch (\Exception $e) {
            Assertion::eq(404, $e->getCode());

            return;
        }

        throw new \Exception('Image metadata found for image ' . $imageIdentifer . ' in ES');
    }

    /**
     * @When I delete metadata from image :imageIdentifier
     */
    public function deleteMetadataFromImage($imageIdentifier)
    {
        $this->imbo->deleteMetadata($imageIdentifier);
    }

    /**
     * @Given /^I include an access token in the query$/
     */
    public function appendAccessToken() {
        $this->imbo->getEventDispatcher()->addListener('request.before_send', function($event) {
            $request = $event['request'];
            $request->getQuery()->remove('accessToken');
            $accessToken = hash_hmac('sha256', urldecode($request->getUrl()), $this->imbo->getConfig('privateKey'));
            $request->getQuery()->set('accessToken', $accessToken);
        }, -100);
    }

    /**
     * @Then /^I should get the (.*?) in the image response list$/
     */
    public function iShouldGetTheInTheImageResponseList($imageIdentifers)
    {
        $responseBody = $this->getLastResponse()->json();

        // Build list of expected values
        $expectedIdentifiers = array_filter(explode(',', $imageIdentifers));

        // Sort the expected identifiers
        sort($expectedIdentifiers);

        $actualIdentifiers = array_map(function($image) {
            return $image['imageIdentifier'];
        }, $responseBody['images']);

        // Sort the actual identifiers
        sort($actualIdentifiers);

        Assertion::eq($expectedIdentifiers, $actualIdentifiers);
    }

    /**
     * @Then the hit count should be :hits
     */
    public function theHitCountShouldBe($hits)
    {
        $body = $this->getLastResponse()->json();

        Assertion::eq($body['search']['hits'], $hits);
    }
}
