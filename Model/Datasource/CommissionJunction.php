<?php
App::uses('HttpSocket', 'Network/Http');

class CommissionJunction extends DataSource {

/**
 * An optional description of your datasource
 */
    public $description = 'Commission Junction Web Services';

/**
 * Our default config options. These options will be customized in our
 * ``app/Config/database.php`` and will be merged in the ``__construct()``.
 */
    public $config = array(
        'apiKey' => '',
        'website-id' => '',
    );
	
	public $urls = array(
		'url' => 'https://product-search.api.cj.com/v2/product-search',
      	'catUrl' => 'https://support-services.api.cj.com/v2/categories',
      	'advUrl' => 'https://advertiser-lookup.api.cj.com/v3/advertiser-lookup'
	);
	
	public $httpRequest = array(
			'method' => 'GET',
			'uri' => array(
				'host' => 'product-search.api.cj.com',
				'port' => 443,
				'scheme' => 'https',
				'user' => null,
				'pass' => null,
				'path' => null,
				'query' => null,
				'fragment' => null
			),
			'version' => '1.1',
			'body' => '',
			'line' => null,
			'header' => array(
				'Connection' => 'close',
				'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8'
			),
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
		
		//Build Query
		$query = array();
		if(!empty($queryData['conditions'])) {
			$query = $queryData['conditions'];
		}
		
		if(isset($this->config['website-id'])) {
			$query['website-id'] = $this->config['website-id'];
		}
		else {
			throw new Exception("No Website ID", 1);
			
		}
		
		$request = array(
			'header' => array(
				'Authorization' => $this->config['apiKey'],
				'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8'
			));
				
		$xmlString = $this->Http->get($this->urls['url'], $query, $request)->body();
		
		$xmlArray = array();
        if($xmlString !== '') {
        	$xmlArray = Xml::toArray(Xml::build($xmlString));
        }
		
        return array($model->alias => $xmlArray['cj-api']);
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
	
	
	/*
	 * Get Categories
	 * 
	 * http://help.cj.com/en/web_services/web_services.htm
	 * 
	 */
	
	public function categories() {
		$request = array(
			'header' => array(
				'Authorization' => $this->config['apiKey'],
				'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8'
			));
				
		$xmlString = $this->Http->get($this->urls['catUrl'], array(), $request)->body();
		
		$xmlArray = array();
        if($xmlString !== '') {
        	$xmlArray = Xml::toArray(Xml::build($xmlString));
        }
		if(isset($xmlArray['cj-api']['error-message'])) {
			throw new CakeException($xmlArray['cj-api']['error-message'], 1);
		}
		
        return $xmlArray['cj-api']['categories']['category'];
			
	}
	
	/*
	 * Get Advertisers
	 * 
	 * See http://help.cj.com/en/web_services/web_services.htm
	 * 
	 */
	
	public function advertisers($advertiserIds = null, $advertiserName = null, $keywords = array()) {
		
		if($advertiserIds == null && $advertiserName == null && empty($keywords)) {
			throw new CakeException("Need to enter at least one paramater", 1);
		}	
			
		$request = array(
			'header' => array(
				'Authorization' => $this->config['apiKey'],
				'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8'
			));
				
		$query = array();
		if($advertiserIds !== null) {
			$query['advertiser-ids'] = $advertiserIds;
		}
		if($advertiserName !== null) {
			$query['advertiser-name'] = $advertiserName;
		}
		if(!empty($keywords)) {
			$query['keywords'] = implode(' ', $keywords);
		}
		
		$xmlString = $this->Http->get($this->urls['advUrl'], $query, $request)->body();
		
		$xmlArray = array();
        if($xmlString !== '') {
        	$xmlArray = Xml::toArray(Xml::build($xmlString));
        }
		if(isset($xmlArray['cj-api']['error-message'])) {
			throw new CakeException($xmlArray['cj-api']['error-message'], 1);
		}
		
        return $xmlArray['cj-api']['advertisers']['advertiser'];
			
	}
}