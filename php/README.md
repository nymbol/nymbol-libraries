# Nymbol for PHP

The Nymbol PHP library is a really simple way of getting off the ground, with a remotely-hosted
PhoneGap app, or as a way of integrating your Nymbol collections with other websites.

## Getting started

1. Download the nymbol.php file and put it somewhere in your project directory
2. Visit <https://nymbol.co.uk/mobile/apps/> and create a new app
3. Click the name of the app and copy the key and secret for use later (see the first code example below)
4. Check out <http://nymbol.co.uk/develop/> for more documentation on the Nymbol API

## Basic usage

```php
$nymbol = new Nymbol('key', 'secret');
$assets = $nymbol->assets->filter(
	array(
		'collection_id' => 1
	) // Filter the query
)->get(
	array(
		'thumbsize' => '300x300',
		'thumbdensity' => 'high',
		'rpp' => 10
	) // Add options and pagination
);
```

## Chaining filters

You can chain your filter expressions together, like this:

```php
$asset = $nymbol->assets->filter(
	array('collection_id' => 1)
)->filter(
	array('id' => 2) // Get a specific asset
)->get();
```

## Caching

The Nymbol PHP library can cache your queries in the filesystem. Wherever you've put your nymbol.php
file, create a directory that is writeable by PHP called `nymbol.cache`, then define the
`NYMBOL_CACHE_ADAPTER` constant to point to the `NymbolFileCacheAdapter`, like this:

```php
define('NYMBOL_CACHE_ADAPTER', 'NymbolFileCacheAdapter');
```

By default, the PHP library
uses `NymbolDummyCacheAdapter`, which only caches on a per-request basis..

## More on filtering

Below is a list of the things you can query for, and the basic options that are available. The PHP
library uses these options to construct a URL, but you can also add further filtering options which are
appended to the API call via querystring parameters. Where you see them listed in the
[documentation](http://nymbol.co.uk/develop/), you can use those parameter names in your `filter()` or
`get()` call.

## Query methods

You can query the following things:

### Collections

```php
$nymbol->collections->get();
```

#### Parameters

* `id` - (optional) ID of the collection

### Taxonomies

```php
$nymbol->taxonomies->get();
```

#### Parameters

* `collection_id` - (required) ID of the collection the taxonomy belongs to
* `id` - (optional) ID of the taxonomy

### Taxonomy terms

```php
$nymbol->terms->get();
```

#### Parameters

* `collection_id` - (required) ID of the collection the taxonomy and terms belong to
* `taxonomy_id` - (required) ID of the taxonomy the term belongs to
* `id` - (optional) ID of the term

### Assets

```php
$nymbol->assets->get();
```

#### Parameters

* `collection_id` - (required) ID of the collection the asset belongs to
* `id` - (optional) ID of the asset

### Resources

```php
$nymbol->resources->get();
```

#### Parameters

* `collection_id` - (required) ID of the collection the asset and resource belong to
* `asset_id` - (required) ID of the asset the resource belongs to
* `id` - (optional) ID of the resource

## Common query options

You can alsao specify the following options via the `get()` function:

* `rpp` - The number of items per page (defaults to 100)
* `page` - The page number, for paginating through results
* `thumbsize` - The size of thumbnails (all taxonomy and asset related API calls can return thumbnails)
* `thumbdensity` - The resolution of the thumbnail (`high`, `low` or `normal`)

## To do

1. Add more query options
2. Add HTTPS as the default, with a `secure` property to return to standard HTTP