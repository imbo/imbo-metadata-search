# imbo-metadata-search
The metadata search event listener hooks onto metadata updates for your images and keeps the search backend of your choice up to date, and allows you to find images by querying its metadata.

[![Current build Status](https://secure.travis-ci.org/imbo/imbo-metadata-search.png)](http://travis-ci.org/imbo/imbo-metadata-search)

## Installation
### Setting up the depenencies
If you've installed Imbo through composer, getting the metadata search up and running is really simple. Simply add `imbo/imbo-metadata-search` as a dependency. 

In addition to the metadata search plugin you'll need a search backend client. Right now the plugin ships with support for elasticsearch only, so you'll want to add `elasticsearch/elasticsearch` as well in order to be able to use it as search backend.

At the time of writing, the `imbo-metadata-search` has not yet been published to [Packagist](http://packagist.com), so you'll need to add the github.com url as a repository in your `composer.json`.

```json
{
    "require": {
        "imbo/imbo": "~1.2",
        "imbo/imbo-metadata-search": "dev-master",
        "elasticsearch/elasticsearch": "~1.3"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/imbo/imbo-metadata-search"
        }
    ]
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
The metadata search is queried by requesting the `/users/<publickey>/search` resource using HTTP GET. Supported query parameters are:

| Param | Description |
| ----- | ----------- |
| `q` | Metadata query (Imbo DSL) represented as JSON string |
| `page` | The page number. Defaults to 1. |
| `limit` | Number of images per page. Defaults to 20. |
| `metadata` | Whether or not to include metadata in the output. Defaults to 0, set to 1 to enable. |
| `fields[]` | An array with fields to display. When not specified all fields will be displayed. |
| `sort[]` | An array with fields to sort by. The direction of the sort is specified by appending asc or desc to the field, delimited by :. If no direction is specified asc will be used. Example: ?sort[]=size&sort[]=width:desc is the same as ?sort[]=size:asc&sort[]=width:desc. If no sort is specified the search backend will rank by relevance. |

```sh
$ curl 'http://imbo/users/<user>/search.json?q={"foo:bar"}&limit=1&metadata=1'
```

Results in:

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
      "publicKey": "<user>",
      "metadata": {
        "key": "value",
        "foo": "bar"
      }
    }
  ]
}
```

## Imbo DSL
Description of the Imbo DSL goes here.

# License
Copyright (c) 2015, [Kristoffer Brabrand](mailto:kristoffer@brabrand.no) and [Morten Fangel](mailto:fangel@sevengoslings.net)

Licensed under the MIT License
