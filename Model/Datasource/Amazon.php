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
		'Keywords' => '',
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
 * If we want to create() or update() we need to specify the fields
 * available. We use the same array keys as we do with CakeSchema, eg.
 * fixtures and schema migrations.
 */
    protected $_schema = array(
        'ad-id' => array(
            'type' => 'integer',
            'null' => false,
            'key' => 'primary',
        ),
        'advertiser-id' => array(
            'type' => 'integer',
            'null' => false,
            'key' => 'primary',
        ),
        'advertiser-name' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'advertiser-category' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'buy-url' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'catalog-id' => array(
            'type' => 'integer',
            'null' => false,
        ),
        'currency' => array(
            'type' => 'string',
            'null' => true,
            'length' => 5,
        ),
        'description' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'image-url' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'in-stock' => array(
            'type' => 'boolean',
       		'length' => 1,
       		'null' => true,
        ),
        'isbn' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'manufacturer-name' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'manufacturer-sku' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'name' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'price' => array(
            'type' => 'float',
            'null' => true,
        ),
        'retail-price' => array(
            'type' => 'float',
            'null' => true,
        ),
        'sale-price' => array(
            'type' => 'float',
            'null' => true,
        ),
        'sku' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
        'upc' => array(
            'type' => 'string',
            'null' => true,
            'length' => 255,
        ),
    );

/**
 * Create our HttpSocket and handle any config tweaks.
 */
    public function __construct($config) {
        parent::__construct($config);
		
		$this->config['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
		
		$this->Http = new HttpSocket();
        
    }

/**
 * Since datasources normally connect to a database there are a few things
 * we must change to get them to work without a database.
 */

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
		$private_key = '1cmj0ZYkaeqTx0X/3okXhZUvfrEgLTnjS5wYlP0V';
		//Build Query
		$query = array();
		if(!empty($queryData['conditions'])) {
			$query = $queryData['conditions'];
		}
		
		unset($this->config['datasource']);
		
		$query = array_merge($query, $this->config);
		
		$this->httpRequest['uri']['query'] = $query;
	
		$canonicalized_query = array();
		foreach ( $query as $param => $value ) {
			$param = str_replace("%7E", "~", rawurlencode($param));
			$value = str_replace("%7E", "~", rawurlencode($value));
			$canonicalized_query[] = $param . "=" . $value;
		}
		
		$string_to_sign = $this->httpRequest['method'] . "\n" . $this->httpRequest['uri']['host'] . "\n" . $this->httpRequest['uri']['path'] . "\n" . implode("&", $canonicalized_query);
	
		// calculate HMAC with SHA256 and base64-encoding
		$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $private_key, True));
		
		// encode the signature for the request
		$signature = str_replace("%7E", "~", rawurlencode($signature));
		
		$this->httpRequest['uri']['query']['Signature'] = $signature;
		

		$xmlString = $this->Http->get('http://'.$this->httpRequest['uri']['host'].$this->httpRequest['uri']['path'].'?'.implode("&", $canonicalized_query).'&Signature='.$signature)->body();
		
        if($xmlString !== '') {
        	$xmlArray = Xml::toArray(Xml::build($xmlString));
        }
		
        return array($model->alias => $xmlArray['ItemSearchResponse']['Items']);
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