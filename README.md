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

## Usage
```js
GET /search.json?q={"animal":"dog"}
{
    search: {
        hits: 2,
        page: 1,
        limit: 20,
        count: 0
    },
    images: [
        {
            added: "Tue, 03 Mar 2015 09:15:31 GMT",
            updated: "Thu, 05 Mar 2015 12:21:51 GMT",
            checksum: "3012ee0319a7f752ac615d8d86b63894",
            originalChecksum: "3012ee0319a7f752ac615d8d86b63894",
            extension: "jpg",
            size: 68012,
            width: 800,
            height: 600,
            mime: "image/jpeg",
            imageIdentifier: "3012ee0319a7f752ac615d8d86b63894",
            publicKey: "publickey"
        },
        {
            added: "Tue, 03 Mar 2015 09:15:31 GMT",
            updated: "Wed, 04 Mar 2015 12:42:27 GMT",
            checksum: "ce3e8c3de4b67e8af5315be82ec36692",
            originalChecksum: "ce3e8c3de4b67e8af5315be82ec36692",
            extension: "jpg",
            size: 63602,
            width: 640,
            height: 425,
            mime: "image/jpeg",
            imageIdentifier: "ce3e8c3de4b67e8af5315be82ec36692",
            publicKey: "publickey"
        }
    ]
}
```
