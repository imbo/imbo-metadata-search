Feature: Use elasticsearch as search backend for the metadata search pluin
    In order to test the elasticsearch backend
    I must enable the MetadataOperations event listener with the ElasticSearch backend

    Background:
        Given I use "publickey" and "privatekey" for public and private keys
        And I add the following images to Imbo:
            | file                             | metadata                              |
            | tests/fixtures/red-panda.jpg     | {"animal":"Red Panda", "color":"red"} |
            | tests/fixtures/giant-panda.jpg   | {"animal":"Giant Panda"}              |
            | tests/fixtures/hedgehog.jpg      | {"animal":"Hedgehog"}                 |
            | tests/fixtures/kitten.jpg        | {"animal":"Cat", "color":"red"}       |
        And I use "user1" and "privatekey" for public and private keys
        And I add the following images to Imbo:
            | file                             | metadata                              |
            | tests/fixtures/prairie-dog.jpg   | {"animal":"Dog"}                      |
        And I have flushed the elasticsearch transaction log

    Scenario: Updating metadata
        Given I use "publickey" and "privatekey" for public and private keys
        When I set the following metadata on an image with identifier "574e32fb252f3c157c9b31babb0868c2":
        """
        {"foo":"bar"}
        """
        Then I should get a response with "200 OK"
        And Elasticsearch should have the following metadata for "574e32fb252f3c157c9b31babb0868c2":
        """
        {"foo":"bar"}
        """

    Scenario: Deleting metadata
        Given I use "publickey" and "privatekey" for public and private keys
        When I delete metadata from image "574e32fb252f3c157c9b31babb0868c2"
        Then I should get a response with "200 OK"
        And Elasticsearch should not have metadata for "574e32fb252f3c157c9b31babb0868c2"

    Scenario: Patch metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I patch the metadata of the image with identifier "3012ee0319a7f752ac615d8d86b63894" with:
        """
        {"foo": "bar"}
        """
        Then I should get a response with "200 OK"
        And Elasticsearch should have the following metadata for "3012ee0319a7f752ac615d8d86b63894":
        """
        {"animal":"Giant Panda","foo":"bar"}
        """

    Scenario: Search single users images without using an access token
        When I search for images from "publickey" using {"animal":"Snake"}
        Then I should get a response with "400 Missing access token"

    Scenario Outline: Search in a single users images using metadata queries
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I set the "limit" query param to "<limit>"
        And I set the "page" query param to "<page>"
        When I search for images from "publickey" using <metadata>
        Then I should get a response with "200 OK"
        And I should get the <imageIdentifers> in the image response list
        And the hit count should be "<hits>"

        Examples:
        | metadata                       | page | limit | imageIdentifers                                                   | hits |
        | {"animal":"Snake"}             | 1    | 20    |                                                                   | 0    |
        | {"animal":"Hedgehog"}          | 1    | 20    | ce3e8c3de4b67e8af5315be82ec36692                                  | 1    |
        | {"color":"red"}                | 1    | 20    | d3712bb23cf4e191e65cf938d55e8982,574e32fb252f3c157c9b31babb0868c2 | 2    |
        | {"color":"red"}                | 1    | 1     | d3712bb23cf4e191e65cf938d55e8982                                  | 2    |
        | {"color":"red"}                | 2    | 1     | 574e32fb252f3c157c9b31babb0868c2                                  | 2    |
        | {"animal":"Cat","color":"red"} | 1    | 20    | d3712bb23cf4e191e65cf938d55e8982                                  | 1    |

    Scenario Outline: Search and sort the search result
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I sort by <sort>
        When I search for images from "publickey" using {"color":"red"}
        Then I should get a response with "200 OK"
        And I should get the <imageIdentifiers> in the image response list

        Examples:
        | sort                           | imageIdentifiers                                                  |
        | {"size":"asc"}                 | d3712bb23cf4e191e65cf938d55e8982,574e32fb252f3c157c9b31babb0868c2 |
        | {"size":"desc"}                | 574e32fb252f3c157c9b31babb0868c2,d3712bb23cf4e191e65cf938d55e8982 |
        | {"width":"desc","size":"desc"} | d3712bb23cf4e191e65cf938d55e8982,574e32fb252f3c157c9b31babb0868c2 |

    Scenario Outline: Search across multiple users using the global search
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I search in images belonging to the users <users> using <metadata>
        Then I should get a response with "200 OK"
        And I should get the <imageIdentifiers> in the image response list

        Examples:
        | users                  | metadata                          | imageIdentifiers                                                  |
        | ["publickey", "user1"] | {"animal":{"$in":["cat", "dog"]}} | 92aa7029b22263ea0b64ba12b4cbf760,d3712bb23cf4e191e65cf938d55e8982 |
        | ["user1", "user2"]     | {"animal":{"$in":["dog"]}}        | 92aa7029b22263ea0b64ba12b4cbf760                                  |
