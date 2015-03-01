<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given Imbo uses the :arg1 configuration
     */
    public function imboUsesTheConfiguration($arg1)
    {
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
     * @Given the request body contains {:arg1::arg2}
     */
    public function theRequestBodyContains($arg1, $arg2)
    {
        throw new PendingException();
    }

    /**
     * @When I request :arg1 using HTTP :arg2
     */
    public function iRequestUsingHttp($arg1, $arg2)
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
     * @Then Elasticsearch should have {:arg1::arg2} for 574e32fb252f3c157c9b31babb0868c2
     */
    public function elasticsearchShouldHaveForEfbfccbbabbc($arg1, $arg2)
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
     * @Then Elasticsearch should have {:arg1::arg2, :arg3::arg4} for 3012ee0319a7f752ac615d8d86b63894
     */
    public function elasticsearchShouldHaveForEeafacddb($arg1, $arg2, $arg3, $arg4)
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
     * @Given I include a metadata search for {:arg1::arg2} in the query
     */
    public function iIncludeAMetadataSearchForInTheQuery($arg1, $arg2)
    {
        throw new PendingException();
    }

    /**
     * @When I request :arg1
     */
    public function iRequest($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then I should get  in the images response list
     */
    public function iShouldGetInTheImagesResponseList()
    {
        throw new PendingException();
    }

    /**
     * @Then I should get ce3e8c3de4b67e8af5315be82ec36692 in the images response list
     */
    public function iShouldGetCeecdebeafbeecInTheImagesResponseList()
    {
        throw new PendingException();
    }

    /**
     * @Then I should get 574e32fb252f3c157c9b31babb0868c2,d3712bb23cf4e191e65cf938d55e8982 in the images response list
     */
    public function iShouldGetEfbfccbbabbcDbbcfeecfdeInTheImagesResponseList()
    {
        throw new PendingException();
    }

    /**
     * @Then I should get 574e32fb252f3c157c9b31babb0868c2 in the images response list
     */
    public function iShouldGetEfbfccbbabbcInTheImagesResponseList()
    {
        throw new PendingException();
    }

    /**
     * @Then I should get d3712bb23cf4e191e65cf938d55e8982 in the images response list
     */
    public function iShouldGetDbbcfeecfdeInTheImagesResponseList()
    {
        throw new PendingException();
    }

    /**
     * @Given I include a metadata search for {:arg1::arg2,:arg3::arg4} in the query
     */
    public function iIncludeAMetadataSearchForInTheQuery2($arg1, $arg2, $arg3, $arg4)
    {
        throw new PendingException();
    }
}
