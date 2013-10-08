# Nymbol for JavaScript

The Nymbol JavaScript library is a really simple way of getting off the ground, with a remotely-hosted
PhoneGap app, or as a way of integrating your Nymbol collections with other websites.

## Usage

This library should be used with frameworks like PhoneGap, where access to the htML or JavaScript source
is not trivial. This library should *never* be used in a standard website environment, as authentication
details are passed in a manner than can be replicated by those able to view the source of the web page.

If you're in any way concerned about the security of your data, whether in a standard browser or an HTML5
app, you should consider making requests via the [PHP](https://github.com/Nymbol/nymbol-libraries/tree/master/php) library, and funnelling data between your HTML5 app and your PHP scripts.

## Requirements

Currently, the Nymbol JavaScript library requires jQuery for HTTP requests.

## Getting started

1. Download the nymbol.js file and put it somewhere in your project directory
2. Include a `<script>` tag in your HTML pointing to the JavaScript file
3. Visit <https://nymbol.co.uk/mobile/apps/> and create a new app
4. Click the name of the app and copy the key and secret for use later (see the first code example below)
5. Check out <http://nymbol.co.uk/develop/> for more documentation on the Nymbol API

## Basic usage

```javascript
var nymbol = new Nymbol('hash');
var assets = nymbol.assets.filter(
	{
		collection_id: 1
	} // Filter the query
).read(
	function(data) {
		// Callback
	},
	{
		thumbsize: '300x300',
		thumbdensity: 'high',
		rpp: 10
	} // Add options and pagination
);
```

## RequireJS

The plugin works with and without [RequireJS](http://requirejs.org/). If you use it, you can instantiate
the library like this:

```javascript
require(
	['nymbol'],
	function(Nymbol) {
		var nymbol = new Nymbol('hash');
		var assets = nymbol.assets.filter(
			{
				collection_id: 1
			} // Filter the query
		).read(
			function(data) {
				// Callback
			},
			{
				thumbsize: '300x300',
				thumbdensity: 'high',
				rpp: 10
			} // Add options and pagination
		);
	}
);
```

## Chaining filters

You can chain your filter expressions together, like this:

```javascript
var asset = nymbol.assets.filter(
	{
		collection_id: 1
	}
).filter(
	{
		id: 2 // Get a specific asset
	}
).read(
	function(data) {
		// Callback
	}
);
```

## More on filtering

Below is a list of the things you can query for, and the basic options that are available. The JavaScript
library uses these options to construct a URL, but you can also add further filtering options which are
appended to the API call via querystring parameters. Where you see them listed in the
[documentation](http://nymbol.co.uk/develop/), you can use those parameter names in your `filter()` or
`read(callback)` call.

## Query methods

You can query the following things:

### Collections

```javascript
nymbol.collections.read(callback);
```

#### Parameters

* `id` - (optional) ID of the collection

### Taxonomies

```javascript
nymbol.taxonomies.read(callback);
```

#### Parameters

* `collection_id` - (required) ID of the collection the taxonomy belongs to
* `id` - (optional) ID of the taxonomy

### Taxonomy terms

```javascript
nymbol.terms.read(callback);
```

#### Parameters

* `collection_id` - (required) ID of the collection the taxonomy and terms belong to
* `taxonomy_id` - (required) ID of the taxonomy the term belongs to
* `id` - (optional) ID of the term

### Assets

```javascript
nymbol.assets.read(callback);
```

#### Parameters

* `collection_id` - (required) ID of the collection the asset belongs to
* `id` - (optional) ID of the asset

### Resources

```javascript
nymbol.resources.read(callback);
```

#### Parameters

* `collection_id` - (required) ID of the collection the asset and resource belong to
* `asset_id` - (required) ID of the asset the resource belongs to
* `id` - (optional) ID of the resource

## Common query options

You can also specify the following options via the `read()` function:

* `rpp` - The number of items per page (defaults to 100)
* `page` - The page number, for paginating through results
* `thumbsize` - The size of thumbnails (all taxonomy and asset related API calls can return thumbnails)
* `thumbdensity` - The resolution of the thumbnail (`high`, `low` or `normal`)

Pass them in after the `callback` argument, like so:

```javascript
nymbol.assets.read(callback,
	{
		rpp: 10,
		thumbsize: '150x150'
	}
);
```

## Disabling SSL (not recommended)

By default, all API requests are performed over SSL. You can turn this off by setting the `nonSSL` option
when instantiating the `Nymbol` class.

```javascript
var nymbol = new Nymbol('hash',
	{
		nonSSL: true
	}
);
```

## To do

1. Add more query options