<?php

if(!defined('NYMBOL_REQUEST_ADAPTER')) {
	define('NYMBOL_REQUEST_ADAPTER', 'NymbolCurlRequestAdapter');
}

if(!defined('NYMBOL_CACHE_ADAPTER')) {
	define('NYMBOL_CACHE_ADAPTER', 'NymbolDummyCacheAdapter'); // Don't cache
}

if(!defined('NYMBOL_CACHE_LIFESPAN')) {
	define('NYMBOL_CACHE_LIFESPAN', 60); // Cache for one hour
}

if(!defined('NYMBOL_NON_SSL')) {
	define('NYMBOL_NON_SSL', false); // Force SSL connection
}

class NymbolCacheAdapterBase {
	protected function _putData($hash, $data, $death) {
		throw new Exception('Method not implemented.');
	}
	
	protected function _getData($hash) {
		throw new Exception('Method not implemented.');
	}
	
	protected function _getDataTime($hash) {
		throw new Exception('Method not implemented.');
	}
	
	protected function _invalidateData($hash) {
		throw new Exception('Method not implemented.');
	}
	
	public function put($query, $options, $data, $timeout = NYMBOL_CACHE_LIFESPAN) {
		$hash = $query->hash($options);
		$now = mktime();
		$death = $now + ($timeout * 60);
		// print("<pre>Putting data for cached item $hash. Death in " . (($death - $now) / 60) . " minute(s).</pre>");
		
		$this->_putData($hash, $data, $death);
	}
	
	public function get($query, $options) {
		$hash = $query->hash($options);
		$death = $this->_getDataTime($hash);
		
		if($death && $death <= mktime()) {
			// print("<pre>Cached item $hash is already dead. Invalidate.</pre>");
			$this->_invalidateData($hash);
		} else {
			$data = $this->_getData($hash);
			if($data) {
				// print("<pre>Getting cached item $hash.</pre>");
				return $data;
			} else {
				// print("<pre>Item $hash not in cache.</pre>");
			}
		}
		
		return null;
	}
}

class NymbolDummyCacheAdapter extends NymbolCacheAdapterBase {
	private $_cache = array();
	
	protected function _putData($hash, $data, $death) {
		$this->_cache[$hash] = $data;
	}
	
	protected function _getData($hash) {
		return isset($this->_cache[$hash]) ? $this->_cache[$hash] : null;
	}
	
	protected function _getDataTime($hash) {
		if(isset($this->_cache[$hash])) {
			return mktime();
		}
		
		return 0;
	}
	
	protected function _invalidateData($hash) {
		if(isset($this->_cache[$hash])) {
			unset($this->_cache[$hash]);
		}
	}
}

class NymbolFileCacheAdapter extends NymbolCacheAdapterBase {
	protected function _putData($hash, $data, $death) {
		$dirname = dirname(__file__) . '/nymbol.cache';
		
		if(!is_dir($dirname)) {
			$made = @mkdir($dirname);
			
			if(!$made) {
				trigger_error('Unable to write to cache directory ' . $dirname);
				return;
			}
		}
		
		$filename = "$dirname/${hash}.cache";
		if(is_file($filename)) {
			unlink($filename);
		}
		
		// print('<pre>Serialising data</pre>');
		file_put_contents($filename, serialize($data));
		touch($filename, $death);
	}
	
	protected function _getData($hash) {
		$filename = dirname(__file__) . "/nymbol.cache/${hash}.cache";
		if(is_file($filename)) {
			// print('<pre>Unserialising data</pre>');
			return unserialize(file_get_contents($filename));
		}
	}
	
	protected function _getDataTime($hash) {
		$filename = dirname(__file__) . "/nymbol.cache/${hash}.cache";
		
		if(is_file($filename)) {
			return filemtime($filename);
		}
		
		return 0;
	}
	
	protected function _invalidateData($hash) {
		$filename = dirname(__file__) . "/nymbol.cache/${hash}.cache";
		
		if(is_file($filename)) {
			unlink($filename);
		}
	}
}

class NymbolRequestAdapterBase {
	function _request($url, $method = 'GET', $params = null, $data = null, $headers = null) {
		throw new Exception('Method not implemented.');
	}
	
	function get($url, $headers = array()) {
		return $this->_request($url, 'GET', null, $headers);
	}
	
	function post($url, $data, $headers = array()) {
		return $this->_request($url, 'POST', $data, $headers);
	}
	
	function put($url, $data, $headers = array()) {
		return $this->_request($url, 'PUT', $data, $headers);
	}
	
	function delete($url, $headers = array()) {
		return $this->_request($url, 'DELETE', null, $headers);
	}
}

class NymbolCurlRequestAdapter extends NymbolRequestAdapterBase {
	function _request($url, $method = 'GET', $data = null, $headers = null) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
		if(is_array($headers)) {
			$newheaders = array();
			foreach($headers as $key => $value) {
				$newheaders[] = "$key: $value";
			}
			
			curl_setopt($curl, CURLOPT_HTTPHEADER, $newheaders);
		}
		
		if($method == 'POST' || $method == 'PUT') {
			if(is_array($data) && count($data) > 0) {
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			}
		}
		
		$response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		if($status == 404) {
			throw new Exception('Object could not be found.');
		}
		
		if($status >= 400 && $status <= 499) {
			throw new Exception('The API call could not be authenticated. Check your credentials.');
		}
		
		if($status == 500) {
			throw new Exception('An internal server error occurred while making the request.');
		}
		
		curl_close($curl);
		return json_decode($response, true);
	}
}

class NymbolQuery {
	private $_api;
	private $_url;
	protected $_parent;
	protected $_params;
	private $_reserved_options = array('rpp', 'page', 'thumbsize', 'thumbdensity', 'order');
	private $_domain = 'nymbol.co.uk';
	private $_cache = null;
	
	function __construct($api, $url, $parent = null, $params = null) {
		$this->_api = $api;
		$this->_url = $url;
		$this->_parent = $parent;
		$this->_params = is_array($params) ? $params : array();
		
		$request_class = NYMBOL_REQUEST_ADAPTER;
		$this->_request = new $request_class();
		
		$cache_class = NYMBOL_CACHE_ADAPTER;
		$this->_cache = $parent ? $parent->_cache : new $cache_class();
	}
	
	private function _escape($key, $value) {
		$values = array();
		
		if(is_array($value) && array_keys($value) == range(0, count($value) - 1)) { // Actual array
			foreach($value as $v) {
				$values[] = urlencode($key) . '=' . urlencode($v);
			}
		} else if(is_array($value)) { // Dictionary
			foreach($value as $k => $v) {
				$values[] = urlencode($key) . '[' . urlencode($k) . ']=' . urlencode($v);
			}
		} else {
			$values[] = urlencode($key) . '=' . urlencode($value);
		}
		
		return implode('&', $values);
	}
	
	protected function _all_params() {
		$params = array_merge(array(), $this->_params);
		$parent = $this->_parent;
		
		while($parent) {
			$params = array_merge($params, $parent->_params);
			$parent = $parent->_parent;
		}
		
		return $params;
	}
	
	private function _make_url($url, $opts) {
		$ex = '/\(\/\:([^\)]+)\)/i';
		$newURL = $url;
		$getOpts = array('_ts=' . mktime());
		$params = $this->_all_params();
		
		if(preg_match_all($ex, $newURL, $matches)) {
			foreach($matches[0] as $match) {
				$key = substr($match, 3);
				$key = substr($key, 0, strlen($key) - 1);
				
				if(isset($params[$key])) {
					$newURL = str_replace($match, '/' . $params[$key], $newURL);
					unset($params[$key]);
				} else {
					$newURL = str_replace($match, '', $newURL);
				}
			}
		}
		
		foreach($params as $key => $value) {
			if(!$key || !$value) {
				continue;
			}
			
			if(!in_array($key, $this->_reserved_options)) {
				$getOpts[] = $this->_escape($key, $value);
			}
		}
		
		foreach($opts as $key => $value) {
			if($key && $value) {
				$getOpts[] = $this->_escape($key, $value);
			}
		}
		
		$newURL = preg_replace($ex, '', $newURL);
		if(count($getOpts)) {
			if(strpos($newURL, '?') === false) {
				$newURL .= '?';
			} else {
				$newURL .= '&';
			}
			
			$newURL .= implode('&', $getOpts);
		}
		
		$protocol = NYMBOL_NON_SSL ? 'http://' : 'https://';
		$apiURL = $protocol . $this->_domain . '/api/manager/' . $newURL;
		$q = strpos($apiURL, '?');
		
		if($q > -1) {
			$apiURL = substr($apiURL, 0, $q) . '.json' . substr($apiURL, $q);
		} else {
			$apiURL .= '.json';
		}
		
		return $apiURL;
	}
	
	function filter($opts) {
		return new NymbolQuery($this->_api, $this->_url, $this, $opts);
	}
	
	function get($opts = null) {
		$data = $this->_cache->get($this, $opts);
		if($data) {
			return $data;
		}
		
		$data = $this->_request->get(
			$this->_make_url($this->_url,
				is_array($opts) ? $opts : array()
			),
			array(
				'Authorization' => md5(
					$this->_api->key . ':' .
					$this->_api->secret
				)
			)
		);
		
		$this->_cache->put($this, $opts, $data);
		return $data;
	}
	
	function hash($opts = array()) {
		if(!is_array($opts)) {
			$opts = array();
		}
		
		return md5(
			$this->_make_url($this->_url,
				is_array($opts) ? $opts : array()
			)
		);
	}
}

class Nymbol {
	public $key;
	public $secret;
	
	function __construct($key, $secret) {
		$this->key = $key;
		$this->secret = $secret;
		$this->collections = new NymbolQuery($this, 'collection(/:id)');
		$this->taxonomies = new NymbolQuery($this, 'collection(/:collection_id)/taxonomies(/:id)');
		$this->terms = new NymbolQuery($this, 'collection(/:collection_id)/taxonomies(/:taxonomy_id)/terms(/:id)');
		$this->assets = new NymbolQuery($this, 'collection(/:collection_id)/assets(/:id)');
		$this->resources = new NymbolQuery($this, 'collection(/:collection_id)/assets(/:asset_id)/resources(/:id)');
	}
} ?>