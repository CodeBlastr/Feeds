<?php

class _FeedCj extends FeedsAppModel {
	
	public $useDbConfig = 'commissionjunction';
	
	public $name = 'FeedCj';
    
    public $feedName = 'cj';
	
	public $useTable = false;
	
	public $defaultKeywords = '+Clothing/Apparel';
	
	/*
	 * Property for Advertiser id lookup
	 * Limits the results to a set of particular advertisers (CIDs) using one of the following four values.
	 * 
	 * CIDs: You may provide list of one or more advertiser CIDs, separated by commas, to limit the results to a specific sub-set of merchants.
	 * Empty String: You may provide an empty string to remove any advertiser-specific restrictions on the search.
	 * joined: This special value (joined) restricts the search to advertisers with which you have a relationship.
	 * notjoined: This special value (notjoined) restricts the search to advertisers with which you do not have a relationship.
	 */
	public $advertiserParam = 'joined'; 
	
	public $fieldmap = array(
		'name' => 'name',
		'description' => 'description',
		'advertiser_name' => 'advertiser-name',
		'advertiser_id' => 'advertiser-id',
		'category' => 'advertiser-category',
		'image_url' => 'image-url',
		'manufacturer_name' => 'manufacturer-name',
		'manufacturer_idenifier' => 'manufacturer-sku',
		'upc' => 'upc',
		'isbn' => 'isbn',
		'retail_price' => 'retail-price',
		'price' => 'price',
		'sale_price' => 'sale-price',
		'currency' => 'currency',
		'buy_url' => 'buy-url',
		'product_id' => 'id'
	);

	public function getCategories() {
		App::uses('HttpSocket', 'Network/Http');
		
		return $this->getDataSource()->categories();
	}
	
	public function getAdvertisers($keywords = array(), $advertiserName = null) {
		App::uses('HttpSocket', 'Network/Http');
		
		return $this->getDataSource()->advertisers($this->advertiserParam, $advertiserName, $keywords);
	}
	
	/*
	 * Overriding the find method, so we can add our custom query params
	 */
	public function find($type = 'first', $query = array()) {
		$conditions = array();
		if(!empty($this->id)) {
			$conditions = $this->_explodeIds($this->id);
		}
		
        $query['conditions'] = $this->_cleanConditions($query['conditions']);
        
		if(isset($query['conditions']) && !empty($query['conditions'])) {
			$query['conditions'] = array_merge($query['conditions'], $conditions);
		}else {
			$query['conditions'] = $conditions;
		}
		
		if(!isset($query['conditions']['keywords'])) {
			if(!isset($query['conditions']['advertiser-ids'])) {
				$query['conditions']['keywords'] = $this->defaultKeywords;	
			}
		}else {
			$query['conditions']['keywords'] = $query['conditions']['keywords'].' '.$this->defaultKeywords;
		}
		
		if(!isset($query['conditions']['advertiser-ids'])) {
			$query['conditions']['advertiser-ids'] = $this->advertiserParam;
		}
		
		if($type == 'first') {
			//Set Defaults
			$query['conditions']['page-number'] = 1;
			$query['conditions']['records-per-page'] = 1;	
		}
		
		//Set Search to Always All so it is mapped properly in datasource
		$typesearch = $this->_findType('all', $query);
        
        //So we can do our own probably could have overridden the enitre find
        $callback =  $query['callbacks'];
        $query['callbacks'] = false;
		$results = parent::find($typesearch, $query);
		$query['callbacks'] = isset($callback) ? $callback : true;
		//Checks the array for error messages
		if(isset($results['FeedCj']['error-message'])) {
		
			throw new BadRequestException($results['FeedCj']['error-message'], 1);
			
		}
		
		//Returns empty array if nothing is found
		if(!isset($results['FeedCj']['products']['product'])) {
			$results['FeedCj']['products']['product'] = array();
		}
		
		$this->totalResults = $results['FeedCj']['products']['@total-matched'];
		
		if($this->totalResults == 0) {
		    return array();
		}
		//Creates Ids that we can search by
		if(!isset($results['FeedCj']['products']['product'][0])) {
			$results['FeedCj']['products']['product'] = array($results['FeedCj']['products']['product']);
		}

        foreach ($results['FeedCj']['products']['product'] as $key => $product) {
                $product['id'] = $this->_createIds($product);
                $results['FeedCj']['products']['product'][$key] = $product;
        }
		
		$this->feedData = $results;
        
        $results = $this->_renderproductdata($results['FeedCj']['products']['product']);
        
        //Run our callbacks
        if ($query['callbacks'] === true || $query['callbacks'] === 'after') {
            $results = $this->_filterResults($results);
        }
        
		return $results;
		
	}

	//This Overirides the exists function to search by sku
	public function exists($id = null) {
		if ($id === null) {
			$id = $this->getID();
		}
		if ($id === false) {
			return false;
		}
		$conditions = $this->_explodeIds($id);
		$conditions['keywords'] = $this->defaultKeywords;
		$query = array(
			'conditions' => $conditions);
		$results = $this->find('all', $query);
		
		return (count($results) > 0);
	}
	
	/**
	 * Generates a Unique Id for Foreign Key saves
	 * @param $product an array from feed product
	 * @return $id
	 */
	
	private function _createIds ($product) {
		//set defaults	
		return implode("__", array(
				str_replace('__', '', $product['advertiser-id']),
				str_replace('__', '', $product['sku']),
				str_replace('__', '', $product['manufacturer-name']),
				str_replace('__', '', $product['manufacturer-sku']),
				str_replace('__', '', $product['upc']),
				$this->feedName,
		));
	}
	
	/**
	 * Exploded generated Id 
	 * @param $id array generated by _createIds
	 * @return product array
	 */
	
	public function _explodeIds ($id) {
		$id = explode("__", $id);
		$product['advertiser-ids'] = isset($id[0]) ? $id[0] : 0;
		$product['advertiser-sku'] = isset($id[1]) ? $id[1] : 0;
		$product['manufacturer-name'] = isset($id[2]) ? $id[2] : 0;
		$product['manufacturer-sku'] = isset($id[3]) ? $id[3] : 0;
		$product['upc'] = isset($id[4]) ? $id[4] : 0;
		
		foreach($product as $k => $v) {
			if(empty($v)) {
				unset($product[$k]);
			}
		}

		return $product;
	}

    
    /**
     * Function to clean search params to work with CJ Feed Source
     */
    
    protected function _cleanConditions($conditions) {
        if(!empty($conditions)) {
                
            //Create a fake $condtions array for extract
            $condExtract = $conditions;
            //Clean up the array, so values can be extracted
            foreach($condExtract as $k => $v) {
                $k = str_replace('-', '_', $k);
                unset($condExtract[$k]);
                $condExtract[$k] = $v;
            }
            
            //Create Variables for all passed $conditions
            extract($condExtract);
            
            //Create Defaults
            $keywords = isset($keywords) ? $keywords : '';
            $category = isset($category) ? ' +'.$category : '';
            
            //This is how CJ feed handles category
            if (isset($conditions['category'])) {
                unset($conditions['category']);
            }
            
            if(!empty($keywords)) {
                $keyworksarr = explode(' ', $keywords);
                foreach($keyworksarr as $k => $keyword) {
                    if(strpos($keyword, '+') === FALSE) {
                        $keyword = '+'.$keyword;
                    }
                    $keyworksarr[$k] = $keyword;
                }
                $conditions['keywords'] = implode(' ', $keyworksarr);
            }else {
                $conditions['keywords'] = $keywords;
            }
            
            $conditions['keywords'] = $conditions['keywords'] . $category;
            
        }
        
        $conditions['records-per-page'] = 100;
            
        return $conditions;
    }
}

if (!isset($refuseInit)) {
    class FeedCj extends _FeedCj {}
}
