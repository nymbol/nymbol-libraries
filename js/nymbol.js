(
	function(factory) {
		if(typeof(define) === 'function' && define.amd) {
			define(['jquery'], factory);
		} else {
			Nymbol = factory(jQuery);
		}
	}
)(
	function($) {
		var ns = function(hash, defaults) {
			var platform = navigator.appVersion.toLowerCase();
			var device = 'desktop';
			var domain = 'nymbol.co.uk';
			var reservedOpts = ['rpp', 'page', 'thumbsize', 'thumbdensity', 'order'];
			var controlOpts = ['autoPaginate', 'callback', 'error'];
			var protocol = 'https://';
			
			if(platform.indexOf('ipad') > -1) {
				device = 'ipad';
				platform = 'ios';
			} else if(platform.indexOf('iphone') > -1) {
				device = 'iphone';
				platform = 'ios';
			} else {
				platform = 'html5';
			}
			
			if(typeof(defaults) != 'object') {
				defaults = {};
			}
			
			if(typeof(defaults.nonSSL) == 'bool' && defaults.nonSSL) {
				protocol = 'http://';
			}
			
			function nEscape(key, value) {
				values = [];
				
				if(Object.prototype.toString.call(value) == '[object Array]') {
					for(var i = 0; i < value.length; i ++) {
						values.push(escape(key) + '=' & escape(values[i]));
					}
				} else if(typeof(value) == 'object') {
					for(var k in value) {
						values.push(escape(key) + '[' + escape(k) + ']=' + escape(value[k]));
					}
				} else {
					values.push(escape(key) + '=' + escape(value));
				}
				
				return values.join('&');
			}
			
			function request(method, url, callback, options, params) {
				var ex = new RegExp(/\(\/\:([^\)]+)\)/g);
				var newURL = url;
				var getOpts = [];
				var matches = newURL.match(ex);
				var opts = $.extend({}, params);
				
				if(matches != null) {
					for(var i = 0; i < matches.length; i ++) {
						var key = matches[i].substr(3);
						
						key = key.substr(0, key.length - 1);
						if(key in opts) {
							newURL = newURL.replace(matches[i], '/' + opts[key]);
							delete(opts[key]);
						} else {
							newURL = newURL.replace(matches[i], '');
							if(key in opts) {
								getOpts.push(key + '=' + escape(opts[key]));
							}
						}
					}
				}
				
				for(var key in opts) {
					if(typeof(key) == 'undefined' || typeof(opts[key]) == 'undefined') {
						continue;
					}
					
					if(reservedOpts.indexOf(key) === -1 && controlOpts.indexOf(key) === -1) {
						getOpts.push(nEscape(key, opts[key]));
					}
				}
				
				if(typeof(options) == 'object') {
					for(var key in options) {
						if(typeof(key) == 'undefined' || typeof(options[key]) == 'undefined') {
							continue;
						}
						
						if(controlOpts.indexOf(key) === -1) {
							getOpts.push(nEscape(key, options[key]));
						}
					}
				}
				
				newURL = newURL.replace(ex, '');
				if(getOpts.length > 0) {
					if(newURL.indexOf('?') === -1) {
						newURL += '?';
					} else {
						newURL += '&';
					}
					
					newURL += getOpts.join('&');
				}
				
				var apiURL = protocol + domain + '/api/manager/' + newURL;
				var q = apiURL.indexOf('?');
				
				if(q > -1) {
					apiURL = apiURL.substr(0, q) + '.json' + apiURL.substr(q);
				} else {
					apiURL += '.json';
				}
				
				console.log('GET', apiURL);
				jQuery.ajax(
					{
						url: apiURL,
						headers: {
							'Authorization': hash,
							'X-Platform': platform,
							'X-Device': device
						},
						type: 'GET',
						dataType: 'json',
						crossDomain: true,
						success: function(data) {
							if(typeof(data) == 'object' && typeof(callback) == 'function') {
								callback(data);
							}
						},
						error: function() {
							if(typeof(options.error) == 'function') {
								options.error();
							}
						}
					}
				);
			}
			
			ns.Query = function(url, parent, params) {
				var self = this;
				
				self.filter = function(opts) {
					return new ns.Query(url, self, opts);
				};
				
				self.create = function(callback, options) {
					request('POST', url, callback, options, self._params);
				};
				
				self.read = function(callback, options) {
					request('GET', url, callback, options, self._params);
				};
				
				self.update = function(callback, options) {
					request('PUT', url, callback, options, self._params);
				};
				
				self.delete = function(callback, options) {
					request('DELETE', url, callback, options, self._params);
				};
				
				self._params = params;
				if(typeof(parent) == 'object') {
					self.parent = parent;
					$.extend(self._params, parent._params);
				} else {
					self.parent = null;
				}
				
				return this;
			}
			
			ns.collections = new ns.Query('collection(/:id)');
			ns.taxonomies = new ns.Query('collection(/:collection_id)/taxonomies(/:id)');
			ns.terms = new ns.Query('collection(/:collection_id)/taxonomies(/:taxonomy_id)/terms(/:id)');
			ns.assets = new ns.Query('collection(/:collection_id)/assets(/:id)');
			ns.resources = new ns.Query('collection(/:collection_id)/assets(/:asset_id)/resources(/:id)');
			
			return ns;
		};
		
		return ns;
	}
);