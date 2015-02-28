Feature: Use elasticsearch as search backend for the metadata search pluin
    In order to test the elasticsearch backend
    As an Imbo admin
    I must enable the MetadataOperations event listener with the ElasticSearch backend

    Background:
        Given Imbo uses the "metadata-search-elasticsearch.php" configuration
        And "tests/fixtures/red-panda.jpg" exists in Imbo

    Scenario Outline: Updating metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And the request body contains <metadata>
        When I request "/users/publickey/images/<imageIdentifer>/metadata" using HTTP "PUT"
        Then I should get a response with "200 OK"
        And Elasticsearch should have <metadata> for <imageIdentifer>

        Examples:
            | imageIdentifer                   | metadata      |
            | fc7d2d06993047a0b5056e8fac4462a2 | {"foo":"bar"} |

    Scenario: Deleting metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/metadata" using HTTP "DELETE"
        Then I should get a response with "200 OK"
        And Elasticsearch should not have metadata for "fc7d2d06993047a0b5056e8fac4462a2"