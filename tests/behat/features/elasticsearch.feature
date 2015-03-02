Feature: Use elasticsearch as search backend for the metadata search pluin
    In order to test the elasticsearch backend
    As an Imbo admin
    I must enable the MetadataOperations event listener with the ElasticSearch backend

    Background:
        Given The following images exist in Imbo:
            | file                             | metadata                              |
            | tests/fixtures/red-panda.jpg     | {"animal":"Red Panda", "color":"red"} |
            | tests/fixtures/giant-pada.jpg    | {"animal":"Giant Panda"}              |
            | tests/fixtures/hedgehog.jpg      | {"animal":"Hedgehog"}                 |
            | tests/fixtures/kitten.jpg        | {"animal":"Cat", "color":"red"}       |
            | tests/fixtures/prairie-dog.jpg   | {"animal":"Dog"}                      |

    Scenario: Updating metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And the request body contains:
        """
        {"foo": "bar"}
        """
        When I request "/users/publickey/images/574e32fb252f3c157c9b31babb0868c2/metadata" using HTTP "PUT"
        Then I should get a response with "200 OK"
        And Elasticsearch should have the following metadata for "574e32fb252f3c157c9b31babb0868c2":
        """
        {"foo": "bar"}
        """

    Scenario: Deleting metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        When I request "/users/publickey/images/574e32fb252f3c157c9b31babb0868c2/metadata" using HTTP "DELETE"
        Then I should get a response with "200 OK"
        And Elasticsearch should not have metadata for "574e32fb252f3c157c9b31babb0868c2"

    Scenario: Patch metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And the request body contains:
        """
        {"foo": "bar"}
        """
        When I request "/users/publickey/images/3012ee0319a7f752ac615d8d86b63894/metadata" using HTTP "POST"
        Then I should get a response with "200 OK"
        And Elasticsearch should have the following metadata for "3012ee0319a7f752ac615d8d86b63894":
        """
        {"animal": "Giant Panda", "foo": "bar"}
        """

    Scenario: Request the search endpoint without accessToken
        Given I include this metadata in the query:
        """
        {"foo":"bar"}
        """
        When I request "user/publickey/search.json" using HTTP "GET"
        Then I should get a response with "400 Missing access token"

    Scenario Outline: Search using metadata queries
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I include <metadata> in the query
        And I set the "limit" query param to "<limit>"
        And I set the "page" query param to "<page>"
        When I request "/users/publickey/search.json" using HTTP "GET"
        Then I should get a response with "200 OK"
        And I should get the "<imageIdentifers>" in the image response list
        And the hit count should be "<hits>"

        Examples:
        | metadata                       | page | limit | imageIdentifers                                                   | hits |
        | {"animal":"Snake"}             | 1    | 20    |                                                                   | 0    |
        | {"animal":"Hedgehog"}          | 1    | 20    | ce3e8c3de4b67e8af5315be82ec36692                                  | 1    |
        | {"color":"red"}                | 1    | 20    | 574e32fb252f3c157c9b31babb0868c2,d3712bb23cf4e191e65cf938d55e8982 | 2    |
        | {"color":"red"}                | 1    | 1     | 574e32fb252f3c157c9b31babb0868c2                                  | 2    |
        | {"color":"red"}                | 2    | 1     | d3712bb23cf4e191e65cf938d55e8982                                  | 2    |
        | {"animal":"Cat","color":"red"} | 1    | 20    | d3712bb23cf4e191e65cf938d55e8982                                  | 1    |