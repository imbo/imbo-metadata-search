<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Gherkin\Node\StepNode;
use Assert\Assertion;
use Elasticsearch\ClientBuilder;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RESTContext implements Context, SnippetAcceptingContext {
    public function __construct($url, $documentRoot, $router, $httpdLog, $timeout) {
        parent::__construct($url, $documentRoot, $router, $httpdLog, $timeout);

        $this->elasticsearch = ClientBuilder::create()->build();
        $this->images = [];
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
        $elasticsearch = ClientBuilder::create()->build();

        try {
            $elasticsearch->indices()->delete([
                'index' => 'metadatasearch_integration'
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
     * @Given I have flushed the elasticsearch transaction log
     */
    public function flushElasticsearch() {
        $this->elasticsearch->indices()->flush();
    }

    /**
     * @Given I add the following images to the user named :user:
     */
    public function theFollowingImagesExistInImbo($user, TableNode $images) {
        foreach ($images as $image) {
            $this->addImageToImbo($image['file'], $user, json_decode($image['metadata'], true));
        }
    }

    /**
     * @Given /^"([^"]*)" exists under the "([^"]*)" user with metadata (.*)$/
     */
    public function addImageToImbo($imageName, $user, $metadata) {
        $this->imbo->setUser($user);

        $res = $this->imbo->addImage('tests/fixtures/' . $imageName . '.jpg');

        // Add image to images var so we have a way of looking them up later
        $this->images[$imageName] = $res->get('imageIdentifier');

        if ($metadata) {
            $this->setImageMetadata($imageName, $metadata);
        }
    }

    /**
     * @When /^I set the following metadata on the "([^"]*)" image:$/
     */
    public function setImageMetadata($imageName, $metadata) {
        if ($metadata instanceof PyStringNode) {
            $metadata = json_decode($metadata, true);
        }

        $this->imbo->replaceMetadata($this->images[$imageName], $metadata);
    }

    /**
     * @Given I patch the metadata of the :imageName image with:
     */
    public function iPatchTheMetadataOfTheImageWithIdentifierWith($imageName, PyStringNode $metadata) {
        if ($metadata instanceof PyStringNode) {
            $metadata = json_decode($metadata, true);
        }

        $this->imbo->editMetadata($this->images[$imageName], $metadata);
    }

    /**
     * @Then /^Elasticsearch should have the following metadata for the "([^"]*)" image:$/
     */
    public function elasticsearchShouldHaveTheFollowingMetadataFor($imageName, PyStringNode $metadata) {
        $params = [
            'index' => 'metadatasearch_integration',
            'type' => 'metadata',
            'id' => $this->images[$imageName]
        ];

        $retDoc = $this->elasticsearch->get($params);

        if ($metadata && !$retDoc) {
            throw new \Exception('Image ' . $imageName . ' not found in ES');
        }

        Assertion::eq(json_encode($retDoc['_source']['metadata']), (string) $metadata);
    }

    /**
     * @Then Elasticsearch should have an empty metadata object for the :imageName image
     */
    public function elasticsearchShouldHaveAnEmptyMetadataObjectFor($imageName) {
        $this->elasticsearchShouldHaveTheFollowingMetadataFor(
            $imageName,
            new PyStringNode(['[]'], null)
        );
    }

    /**
     * @Then Elasticsearch should not have metadata for the :imageName image
     */
    public function elasticsearchShouldNotHaveMetadataFor($imageName) {
        $params = [
            'index' => 'metadatasearch_integration',
            'type' => 'metadata',
            'id' => $this->images[$imageName]
        ];

        $exception;
        try {
            $this->elasticsearch->get($params);
        } catch (Exception $e) {
            $exception = $e;
        }

        Assertion::isInstanceOf($exception, 'Elasticsearch\Common\Exceptions\Missing404Exception');
    }

    /**
     * @When I delete metadata from the :imageName image
     */
    public function deleteMetadataFromImage($imageName) {
        $this->imbo->deleteMetadata($this->images[$imageName]);
    }

    /**
     * @When I delete the :imageName image
     */
    public function deleteImage($imageName) {
        $this->imbo->deleteImage($this->images[$imageName]);
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
     * @Then /^I should get (.*?) in the image response list$/
     */
    public function imagesShouldExistInTheResponseList($imageNames) {
        $responseBody = $this->getLastResponse()->json();

        // Build list of expected values
        $expectedIdentifiers = array_map(function($imageName) {
            return $this->images[$imageName];
        }, array_filter(explode(',', $imageNames)));

        $actualIdentifiers = array_map(function($image) {
            return $image['imageIdentifier'];
        }, $responseBody['images']);

        try {
            Assertion::eq($expectedIdentifiers, $actualIdentifiers);
        } catch (\Exception $e) {
            print_r([
                'expected' => $expectedIdentifiers,
                'actual' => $actualIdentifiers
            ]);

            throw $e;
        }
    }

    /**
     * @Then the hit count should be :hits
     */
    public function theHitCountShouldBe($hits) {
        $body = $this->getLastResponse()->json();

        Assertion::eq($body['search']['hits'], $hits);
    }

    /**
     * @When /^I search for images from "(.*?)" using (.*?)$/
     */
    public function iSearchForImagesUsing($user, $metadata) {
        try {
            $this->rawRequest('/users/' . $user . '/images', 'SEARCH', $this->queryParams, $metadata);
        } catch (Exception $e) {
            // We'll assert the status and such later, if we're interested
        }
    }

    /**
     * @When /^I search in images belonging to the users "(.*?)" using (.*?)$/
     */
    public function iSearchInImagesBelongingToTheUsersUsingMetadata($users, $metadata) {
        $this->setQueryParam('users', array_filter(array_map('trim', explode(',', $users))));

        try {
            $this->rawRequest('/images', 'SEARCH', $this->queryParams, $metadata);
        } catch (Exception $e) {
            // We'll assert the status and such later, if we're interested
        }
    }

    /**
     * @Given /^I sort by (.*)$/
     */
    public function setSortParam($sortParams) {
        $sortParams = json_decode($sortParams, true);

        $sortArray = [];

        foreach ($sortParams as $key => $dir) {
            $sortArray[] = sprintf('%s:%s', $key, $dir);
        }

        $this->setQueryParam('sort', $sortArray);
    }
}
