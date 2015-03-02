<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Imbo\Application as Imbo;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    /**
     * @var Imbo\Application
     */
    public $imbo;

    /**
     * @var array
     */
    protected $imboConfig = null;

    /**
     * @var bool
     */
    protected $imboConfigLoaded = false;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->imbo = new Imbo();
    }

    /**
     * Load the configuration and "run" the application
     */
    protected function loadConfig($config)
    {
        $this->imbo->run($this->imboConfig);

        $this->imboConfigLoaded = true;
    }

    /**
     * @Given Imbo uses the :config configuration
     */
    public function imboUsesTheConfiguration($config)
    {
        if ($this->imboConfigLoaded) {
            throw new \Exception('Cannot change Imbo config after loading');
        }

        $this->imboConfig = $config;
    }

    /**
     * @When I request :arg1 using HTTP :arg2
     */
    public function iRequestUsingHttp($arg1, $arg2)
    {
        if (!$this->imboConfigLoaded && $this->imboConfig) {
            $this->loadConfig($this->imboConfig);
        }

        if (!$this->imboConfigLoaded && !$this->imboConfig) {
            throw new \Exception('No imbo configuration set');
        }

        throw new PendingException();
    }

     /**
     * @Given The following images exist in Imbo:
     */
    public function theFollowingImagesExistInImbo(TableNode $table)
    {
        throw new PendingException();
    }

    /**
     * @Given I use :arg1 and :arg2 for public and private keys
     */
    public function iUseAndForPublicAndPrivateKeys($arg1, $arg2)
    {
        throw new PendingException();
    }

    /**
     * @Given I sign the request
     */
    public function iSignTheRequest()
    {
        throw new PendingException();
    }

    /**
     * @Given the request body contains:
     */
    public function theRequestBodyContains(PyStringNode $string)
    {
        throw new PendingException();
    }

    /**
     * @Then I should get a response with :arg1
     */
    public function iShouldGetAResponseWith($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then Elasticsearch should have the following metadata for :arg1:
     */
    public function elasticsearchShouldHaveTheFollowingMetadataFor($arg1, PyStringNode $string)
    {
        throw new PendingException();
    }

    /**
     * @Then Elasticsearch should not have metadata for :arg1
     */
    public function elasticsearchShouldNotHaveMetadataFor($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given I include this metadata in the query:
     */
    public function iIncludeThisMetadataInTheQuery(PyStringNode $string)
    {
        throw new PendingException();
    }

    /**
     * @Given I include an access token in the query
     */
    public function iIncludeAnAccessTokenInTheQuery()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I include (\{.*\}) in the query$/
     */
    public function iIncludeInTheQuery($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given I set the :arg1 query param to :arg2
     */
    public function iSetTheQueryParamTo($arg1, $arg2)
    {
        throw new PendingException();
    }

    /**
     * @Then /^I should get the (.*) in the image response list$/
     */
    public function iShouldGetTheInTheImageResponseList($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then the hit count should be :arg1
     */
    public function theHitCountShouldBe($arg1)
    {
        throw new PendingException();
    }
}
