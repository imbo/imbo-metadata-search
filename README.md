[![Current build Status](https://secure.travis-ci.org/imbo/imbo-metadata-search.png)](http://travis-ci.org/imbo/imbo-metadata-search)

# Metadata search plugin for Imbo
The metadata search event listener hooks onto metadata updates for your images and keeps the search backend of your choice up to date, and allows you to find images by querying its metadata.

## Installation
### Setting up the dependencies
If you've installed Imbo through composer, getting the metadata search up and running is really simple. Simply add `imbo/imbo-metadata-search` as a dependency.

In addition to the metadata search plugin you'll need a search backend client. Right now the plugin ships with support for elasticsearch only, so you'll want to add `elasticsearch/elasticsearch` as well in order to be able to use it as search backend.

```json
{
    "require": {
        "imbo/imbo-metadata-search": "dev-master",
        "elasticsearch/elasticsearch": "~1.3"
    }
}
```

### Metadata search setup
In order for the metadata search plugin to be registered and actually do something usedful for your Imbo installation you need to add a config file which declares the routes, resource and event listeners.

After installing with composer you will find a basic config file for the metadata search in `vendor/imbo/imbo-metadata-search/config.dist.php`. If you want to make changes to the file you should copy it to your config folder.

## Indexing
Updates in the search backend is triggered whenever one of the following events are fired; `image.delete`, `images.post`, `image.post`, `metadata.post`, `metadata.put`, `metadata.delete`.

The `image.delete` event triggers a delete in the indexed object in the search backend, and the other ones trigger an update of the full object. When indexing, data in addition to metadata is provided to the search backend for indexin in order to sorting and such.

The data provided to the backend are;

| Data        | Description           |
| ------------- |:-------------|
| `publicKey` | The publickey "owning" the image |
| `size` | Byte size of image. |
| `extension` | File extension. |
| `mime` | Mime type of file. |
| `metadata` | Image metadata. |
| `added` | Timestamp representation of when the image was added. |
| `updated` | Timestamp representation of when the image was last updated. |
| `width` | Width of image in pixels. |
| `height` | Height of image in pixels. |

## Querying
Querying is done by issuing an HTTP SEARCH request to `/users/<user>/images` if you want to search in the images of a single user, or `/images` if you want to search across multiple users. Supported query parameters are:

| Param | Description |
| ----- | ----------- |
| `page` | The page number. Defaults to 1. |
| `limit` | Number of images per page. Defaults to 20. |
| `metadata` | Whether or not to include metadata in the output. Defaults to 0, set to 1 to enable. |
| `fields[]` | An array with fields to display. When not specified all fields will be displayed. |
| `sort[]` | An array with fields to sort by. The direction of the sort is specified by appending asc or desc to the field, delimited by :. If no direction is specified asc will be used. Example: ?sort[]=size&sort[]=width:desc is the same as ?sort[]=size:asc&sort[]=width:desc. If no sort is specified the search backend will rank by relevance. |

The query is sent in the request body.

### Examples

**Querying one user**

```sh
$ curl 'http://imbo/users/<user>/images?limit=1&metadata=1' -d '{"foo": "bar"}'
```

**Querying multiple users**

```sh
$ curl 'http://imbo/images?users[]=<user1>&user[]=<user2>&limit=1&metadata=1' -d '{"foo": "bar"}'
```

Both these requests results in a response that looks like this:

```json
{
  "search": {
    "hits": 3,
    "page": 1,
    "limit": 1,
    "count": 1
  },
  "images": [
    {
      "added": "Mon, 10 Dec 2012 11:57:51 GMT",
      "updated": "Mon, 10 Dec 2012 11:57:51 GMT",
      "checksum": "<checksum>",
      "originalChecksum": "<originalChecksum>",
      "extension": "png",
      "size": 6791,
      "width": 1306,
      "height": 77,
      "mime": "image/png",
      "imageIdentifier": "<image>",
      "user": "<user>",
      "metadata": {
        "key": "value",
        "foo": "bar"
      }
    }
  ]
}
```

## Imbo DSL

The query language used by Imbo Metadata Search is a subset of the MongoDB query
DSL. The query is a JSON-encoded object including ``key => value`` matches
and/or a combination of the supported operators, sent to Imbo in the request body.
This section lists all operators and includes a number of examples showing you how
to find images using the metadata query.

**Note**: The results of the different queries *might* end up with slightly
different results depending on the backend you use the for metadata.

#### Key/value matching

The simplest form of a metadata query is a simple `key => value` match, where
the expressions are AND-ed together if there is more than one key/value match in
the query.

```js
 {"key":"value","otherkey":"othervalue"}
```

The above search would result in images that have the metadata key `key` set to
`value` **and** `otherkey` set to `othervalue`

#### Greater than - `$gt`

This operator can be used to check for values greater than the value specified.

```js
{"age":{"$gt":35}}
```

#### Greater than or equal - `$gte`

Check for values greater than or equal to the value specified.

```js
 {"age":{"$gte":35}}
```

#### Less than - `$lte`

Check for values less than to the value specified.

```js
{"age":{"$lt":35}}
```

#### Less than or equal - `$lte`

Check for values less than or equal to the value specified.

```js
{"age":{"$lte":35}}
```

#### Not equal - `$ne`

Matches values that are not equal to the value specified.

```js
{"name":{"$ne":"christer"}}
```

#### In - `$in`

Look for values that appear in the specified set.

```js
{"styles":{"$in":["IPA","Imperial Stout","Lambic"]}}
```

#### Not in - `$nin`

Look for values that does not appear in the specified set.

```js
{"styles":{"$nin":["Pilsner"]}}
```

#### Conjunctions - `$and`

This operator can be used to combine a list of criteria that must all match. It
takes an array of queries.

```js
{"$and": [{"name": {"$in": ["kristoffer", "morten"]}}, {"age": {"$lt": 30}}]}
```

Would find images where the key `name` is either `kristoffer` or `morten` and
where the `age` key is less than `30`.

#### Disjunction - `$or`

This operator can be used to combine a list of criteria where at least one must
match. It takes an array of queries.

```js
{"$or":[{"key":"value"},{"otherkey":"othervalue"}]}
```

Would fetch images that have a key named `key` with the value `value` and/or a
key named `otherkey` which has the value of `othervalue`.

#### Using several operators in one query

All the above operators can be combined into one query. Consider a collection of
images of beers which have all been tagged with the name of the brewery, the
name of the beer, the style of the beer and the ABV. If we wanted to find all
images of beers within a set of styles, above a specific ABV, from two different
breweries, and all images of beers from Nøgne Ø, regardless of style and ABV,
but not beers called Wit, regardless of brewery, style or ABV, the query could
look like this (formatted for easier reading):

```js
{
    "name":
    {
        "$ne": "Wit"
    },
    "$or":
    [
        {
            "brewery": "Nøgne Ø"
        },

        {
            "$and":
            [
                {
                    "abv":
                    {
                        "$gte": 5.5
                    }
                },

                {
                    "style":
                    {
                        "$in":
                        [
                            "IPA",
                            "Imperial Stout"
                        ]
                    }
                },

                {
                    "brewery":
                    {
                        "$in":
                        [
                            "HaandBryggeriet",
                            "Ægir"
                        ]
                    }
                }
            ]
        }
    ]
}
```

Keep in mind that large complex queries against large image collections can take
a while to finish, and might cause performance issues on the Imbo server(s).

# License
Copyright (c) 2015, [Kristoffer Brabrand](mailto:kristoffer@brabrand.no) and [Morten Fangel](mailto:fangel@sevengoslings.net)

Licensed under the MIT License
