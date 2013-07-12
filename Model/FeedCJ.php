<?php

class _FeedCJ extends FeedsAppModel {
	
	public $useDbConfig = 'commissionjunction';
	
	public $name = 'FeedCJ';
	
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
			$conditions['page-number'] = 1;
			$conditions['records-per-page'] = 1;	
		}
		
		//Set Search to Always All so it is mapped properly in datasource
		$typesearch = $this->_metaType('all', $query);
		$results = parent::find($typesearch, $query);
		//Checks the array for error messages
		
		//Checks for errors
		if(isset($results['FeedCJ']['error-message'])) {
		
			throw new BadRequestException($results['FeedCJ']['error-message'], 1);
			
		}
		
		//Returns empty array if nothing is found
		if(!isset($results['FeedCJ']['products']['product'])) {
			$results['FeedCJ']['products']['product'] = array();
		}
		
		//Creates Ids that we can search by
		if($type == 'first') {
			$results['FeedCJ']['products']['product']['id'] = $this->_createIds($results['FeedCJ']['products']['product']);
		}else {	
			foreach ($results['FeedCJ']['products']['product'] as $key => $product) {
				$product['id'] = $this->_createIds($product);
				$results['FeedCJ']['products']['product'][$key] = $product;
			}
		}
		
		return $results; 
	}
	
	
	public function afterFind($results, $primary = false) {
		if ( !empty($results) ) {

			if ( $results['FeedCJ']['products']['@records-returned'] == '1' ) {
				$results['FeedCJ']['products']['product'] = $this->detectClothingType($results['FeedCJ']['products']['product']);
			} elseif ( (int)$results['FeedCJ']['products']['@records-returned'] > 1  ) {
				foreach ( $results['FeedCJ']['products']['product'] as &$result ) {
					//debug($result);
					$result = $this->detectClothingType($result);
				}
			}
			
		}
		return $results;
	}
	
	
	public function detectClothingType($result) {
		if ( !empty($result['name']) ) {
			$tops = array('shirt', 'blouse', 'coat', 'jacket', 'sweater');
			$bottoms = array('jeans', 'pants');

			$result['type'] = 'unknown';
			
			foreach ( $tops as $top ) {
				if ( stripos($result['name'], $top) !== false || stripos($result['description'], $top) !== false ) {
					$result['type'] = 'top';
				}
			}

			foreach ( $bottoms as $bottom ) {
				if ( stripos($result['name'], $bottom) !== false || stripos($result['description'], $bottom) !== false ) {
					$result['type'] = 'bottom';
				}
			}
		}
		
		return $result;
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
		
		return (isset($results['FeedCJ']['products']['product']) && count($results['FeedCJ']['products']['product']) > 0);
	}
	
	/**
	 * Generates a Unique Id for Foreign Key saves
	 * @param $product an array from feed product
	 * @return $id
	 */
	
	private function _createIds ($product) {
		//set defaults	
		return implode("__", array(
				$product['advertiser-id'],
				$product['sku'],
				$product['manufacturer-name'],
				$product['manufacturer-sku'],
				$product['upc'],
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
     * Explode generated Rating Id 
     * @param $id array generated by for ratings
     * @return product array
     */
    
    public function explodeRatingIds ($id) {
        $id = explode("__", $id);
        $product['manufacturer-name'] = isset($id[0]) ? $id[0] : 0;
        $product['manufacturer-sku'] = isset($id[1]) ? $id[1] : 0;
        $product['upc'] = isset($id[2]) ? $id[2] : 0;
        
        foreach($product as $k => $v) {
            if(empty($v)) {
                unset($product[$k]);
            }
        }

        return $product;
    }
}

if (!isset($refuseInit)) {
    class FeedCJ extends _FeedCJ {}
}
