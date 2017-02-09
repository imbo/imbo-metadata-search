Feature: Use elasticsearch as search backend for the metadata search pluin
    In order to test the elasticsearch backend
    I must enable the MetadataOperations event listener with the ElasticSearch backend

    Background:
        Given I use "publickey" and "privatekey" for public and private keys
        And I add the following images to the user named "user":
            | file          | metadata                                                      |
            | kitten        | {"sort":4, "animal":"Cat", "color":"red", "age": 5}           |
            | red-panda     | {"sort":1, "animal":"Red Panda", "color":"red", "age": 7}     |
            | giant-panda   | {"sort":2, "animal":"Giant Panda", "color":"white", "age": 9} |
            | hedgehog      | {"sort":3, "animal":"Hedgehog", "color": "brown", "age": 2}   |
        And I use "user2" and "privatekey" for public and private keys
        And I add the following images to the user named "user2":
            | file          | metadata                                                      |
            | prairie-dog   | {"sort":5, "animal":"Dog", "color":"brown", "age": 4}         |
        And I have flushed the elasticsearch transaction log

    Scenario: Updating metadata
        Given I use "publickey" and "privatekey" for public and private keys
        When I set the following metadata on the "red-panda" image:
        """
        {"foo":"bar"}
        """
        Then I should get a response with "200 OK"
        And Elasticsearch should have the following metadata for the "red-panda" image:
        """
        {"foo":"bar"}
        """

    Scenario: Deleting an image
        Given I use "publickey" and "privatekey" for public and private keys
        When I delete the "giant-panda" image
        Then I should get a response with "200 OK"
        And Elasticsearch should not have metadata for the "giant-panda" image

    Scenario: Deleting metadata
        Given I use "publickey" and "privatekey" for public and private keys
        When I delete metadata from the "giant-panda" image
        Then I should get a response with "200 OK"
        And Elasticsearch should have an empty metadata object for the "giant-panda" image

    Scenario: Patch metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I patch the metadata of the "giant-panda" image with:
        """
        {"foo": "bar"}
        """
        Then I should get a response with "200 OK"
        And Elasticsearch should have the following metadata for the "giant-panda" image:
        """
        {"sort":2,"animal":"Giant Panda","color":"white","age":9,"foo":"bar"}
        """

    Scenario: Search single users images without using an access token
        When I search for images from "user" using {"animal":"Snake"}
        Then I should get a response with "400 Missing access token"

    Scenario Outline: Search in a single users images using metadata queries and pagination
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I set the "limit" query param to "<limit>"
        And I set the "page" query param to "<page>"
        And I sort by {"metadata.sort":"asc"}
        When I search for images from "user" using <metadata>
        Then I should get a response with "200 OK"
        And I should get <images> in the image response list
        And the hit count should be "<hits>"

        Examples:
        | metadata                                     | page | limit | images                | hits |
        | {"animal":"Snake"}                           | 1    | 20    |                       | 0    |
        | {"animal":"Hedgehog"}                        | 1    | 20    | hedgehog              | 1    |
        | {"color":"red"}                              | 1    | 20    | red-panda,kitten      | 2    |
        | {"color":"red"}                              | 1    | 1     | red-panda             | 2    |
        | {"color":"red"}                              | 2    | 1     | kitten                | 2    |
        | {"animal":"Cat","color":"red"}               | 1    | 20    | kitten                | 1    |
        | {"animal":"Cat","color":"blue"}              | 1    | 20    |                       | 0    |
        | {"$or": [{"animal":"Cat"},{"color":"blue"}]} | 1    | 20    | kitten                | 1    |
        | {"age": {"$lt": 5}}                          | 1    | 20    | hedgehog              | 1    |
        | {"age": {"$lte": 5}}                         | 1    | 20    | hedgehog,kitten       | 2    |
        | {"age": {"$gt": 7}}                          | 1    | 20    | giant-panda           | 1    |
        | {"age": {"$gte": 7}}                         | 1    | 20    | red-panda,giant-panda | 2    |

    Scenario Outline: Search on sub-object in descending order using metadata queries and pagination
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I set the "limit" query param to "<limit>"
        And I set the "page" query param to "<page>"
        And I sort by {"metadata.sort":"desc"}
        When I search for images from "user" using <metadata>
        Then I should get a response with "200 OK"
        And I should get <images> in the image response list
        And the hit count should be "<hits>"

        Examples:
        | metadata                       | page | limit | images           | hits |
        | {"animal":"Snake"}             | 1    | 20    |                  | 0    |
        | {"animal":"Hedgehog"}          | 1    | 20    | hedgehog         | 1    |
        | {"color":"red"}                | 1    | 20    | kitten,red-panda | 2    |
        | {"color":"red"}                | 1    | 1     | kitten           | 2    |
        | {"color":"red"}                | 2    | 1     | red-panda        | 2    |
        | {"animal":"Cat","color":"red"} | 1    | 20    | kitten           | 1    |

    Scenario Outline: Search and sort the search result
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I sort by <sort>
        When I search for images from "user" using {"color":"red"}
        Then I should get a response with "200 OK"
        And I should get <images> in the image response list

        Examples:
        | sort                           | images           |
        | {"size":"asc"}                 | kitten,red-panda |
        | {"size":"desc"}                | red-panda,kitten |
        | {"width":"desc","size":"desc"} | kitten,red-panda |

    Scenario: Search globally with metadata without specifying a user
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I search in images belonging to the users "" using {"foo":"bar"}
        Then I should get a response with "400 One or more users must be specified"

    Scenario: Search for the images of a user the publickey does not have access to
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I search in images belonging to the users "random-user" using {"foo":"bar"}
        Then I should get a response with "400 Public key does not have access to the users: [random-user]"

    Scenario Outline: Search across multiple users using the global search
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I sort by {"metadata.sort":"asc"}
        When I search in images belonging to the users "<users>" using <metadata>
        Then I should get a response with "200 OK"
        And I should get <images> in the image response list

        Examples:
        | users      | metadata                          | images             |
        | user       | {"animal":{"$in":["cat", "dog"]}} | kitten             |
        | user,user2 | {"animal":{"$in":["cat", "dog"]}} | kitten,prairie-dog |
        | user2      | {"animal":{"$in":["dog"]}}        | prairie-dog        |
