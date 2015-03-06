# imbo-metadata-search
Imbo plugin that enables metadata search.


[![Current build Status](https://secure.travis-ci.org/imbo/imbo-metadata-search.png)](http://travis-ci.org/imbo/imbo-metadata-search)

## Installation
### Setting up the depenencies
If you've installed Imbo through composer getting the metadata search up and running is really simple. Simply add `imbo/imbo-metadata-search` as a dependency. 

In addition to the metadata search plugin you'll need a search backend client. Right now the plugin ships with support for Elasticsearch only, so you'll want to add `elasticsearch/elasticsearch` as well in order to be able to use Elasticsearch as search backend.

At the time of writing this, the `imbo-metadata-search` has not yet been published to packagist.com, so you'll need to add the github.com url as a repository in your composer.json.

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
