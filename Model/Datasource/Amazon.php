<?php
App::uses('HttpSocket', 'Network/Http');

class Amazon extends DataSource {

/**
 * An optional description of your datasource
 */
    public $description = 'Amazon Product Advertising';

/**
 * Our default config options. These options will be customized in our
 * ``app/Config/database.php`` and will be merged in the ``__construct()``.
 */
    public $config = array(
        'AWSAccessKeyId' => 'AKIAIXQZBQCNQ2WYDJJA',
		'AssociateTag' => 'theh06e-20',
		'Condition' => 'New',
		'Keywords' => 'Clothing',
		'Operation' => 'ItemSearch',
		'ResponseGroup' => 'Medium',
		'SearchIndex' => 'Apparel',
		'Service' => 'AWSECommerceService',
		'Timestamp' => '',
		'Version' => '2011-08-01',
    );
	
	public $httpRequest = array(
			'method' => 'GET',
			'uri' => array(
				'host' => 'webservices.amazon.com',
				'port' => 80,
				'scheme' => 'http',
				'user' => null,
				'pass' => null,
				'path' => '/onca/xml',
				'query' => null,
				'fragment' => null
			),
			'version' => '1.1',
			'body' => '',
			'line' => null,
			
			'raw' => null,
			'redirect' => false,
			'cookies' => array()
    	);
	


/**
 * Create our HttpSocket and handle any config tweaks.
 */
    public function __construct($config) {
        parent::__construct($config);
		
		if(isset($config['private_key'])) {
			$this->private_key = $config['private_key'];
			unset($config['private_key']);
		}else {
			throw new Exception('No Amazon key found');
		}
		
		$this->config['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
		
		$this->Http = new HttpSocket();
        
    }

/**
 * listSources() is for caching. You'll likely want to implement caching in
 * your own way with a custom datasource. So just ``return null``.
 */
    public function listSources($data = null) {
        return null;
    }

/**
 * describe() tells the model your schema for ``Model::save()``.
 *
 * You may want a different schema for each model but still use a single
 * datasource. If this is your case then set a ``schema`` property on your
 * models and simply return ``$model->schema`` here instead.
 */
    public function describe($model) {
        return $this->_schema;
    }

/**
 * calculate() is for determining how we will count the records and is
 * required to get ``update()`` and ``delete()`` to work.
 *
 * We don't count the records here but return a string to be passed to
 * ``read()`` which will do the actual counting. The easiest way is to just
 * return the string 'COUNT' and check for it in ``read()`` where
 * ``$data['fields'] === 'COUNT'``.
 */
    public function calculate(Model $model, $func, $params = array()) {
        return 'COUNT';
    }

/**
 * Implement the R in CRUD. Calls to ``Model::find()`` arrive here.
 * 
 * This used product search found http://help.cj.com/en/web_services/web_services.htm
 * 
 */
    public function read(Model $model, $queryData = array(), $recursive = null) {
		//Build Query
		$query = array();
		
		if(!empty($queryData['conditions'])) {
			$query = $queryData['conditions'];
		}
		
		//find('first') has been called use item lookup
		if(isset($query['first']) && isset($query['ASIN'])) {
			$this->config['Operation'] = 'ItemLookup';
			$this->config['ItemId'] = $query['ASIN'];
			unset($this->config['Keywords']);
            unset($query['Keywords']);
			unset($this->config['SearchIndex']);
			ksort($this->config);
		}

		if(isset($query['Model'])) {
			unset($query['Model']);
		}
		
		unset($this->config['datasource']);
		
		$query = array_merge($this->config, $query);
		
	    $key = array_pop($query);
        
        ksort($query);
        
        $query['private_key'] =  $key;
		
		$this->httpRequest['uri']['query'] = $query;
	
		$canonicalized_query = array();
		foreach ( $query as $param => $value ) {
			$param = str_replace("%7E", "~", rawurlencode($param));
			$value = str_replace("%7E", "~", rawurlencode($value));
			$canonicalized_query[] = $param . "=" . $value;
		}
		$string_to_sign = $this->httpRequest['method'] . "\n" . $this->httpRequest['uri']['host'] . "\n" . $this->httpRequest['uri']['path'] . "\n" . implode("&", $canonicalized_query);
	
		// calculate HMAC with SHA256 and base64-encoding
		$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $this->private_key, True));
		
		// encode the signature for the request
		$signature = str_replace("%7E", "~", rawurlencode($signature));
		
		$this->httpRequest['uri']['query']['Signature'] = $signature;
		

		$xmlString = $this->Http->get('http://'.$this->httpRequest['uri']['host'].$this->httpRequest['uri']['path'].'?'.implode("&", $canonicalized_query).'&Signature='.$signature)->body();
		
        if($xmlString !== '') {
        	$xmlArray = Xml::toArray(Xml::build($xmlString));
        }
	    
        //return empty array if no results found
        if(isset($xmlArray['ItemSearchResponse']['Items']['Request']['Errors'])) {
            return array();
        }
        
		if (isset($xmlArray['ItemSearchResponse']['Items'])) {
			return array($model->alias => $xmlArray['ItemSearchResponse']['Items']);
		}else {
			return array($model->alias => $xmlArray['ItemLookupResponse']['Items']);
		}
        
    }

/**
 * Implement the C in CRUD. Calls to ``Model::save()`` without $model->id
 * set arrive here.
 */
    public function create(Model $model, $fields = null, $values = null) {
      	throw new CakeException("Can't Save Data to Read Only Feed", 1);
    }

/**
 * Implement the U in CRUD. Calls to ``Model::save()`` with $Model->id
 * set arrive here. Depending on the remote source you can just call
 * ``$this->create()``.
 */
    public function update(Model $model, $fields = null, $values = null, $conditions = null) {
        throw new CakeException("Can't Save Data to Read Only Feed", 1);
    }

/**
 * Implement the D in CRUD. Calls to ``Model::delete()`` arrive here.
 */
    public function delete(Model $model, $id = null) {
       throw new CakeException("Can't Delete Data to Read Only Feed", 1);
    }

}