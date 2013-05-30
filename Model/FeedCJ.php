<?php

class FeedCJ extends FeedsAppModel {
	
	public $useDbConfig = 'commissionjunction';
	
	public $name = 'FeedCJ';
	
	public $useTable = false;
	
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
		if(!isset($query['advertiser-ids'])) {
			$query['advertiser-ids'] = $this->advertiserParam;
		}
		$type = $this->_metaType($type, $query);
		$results = parent::find($type, $query);
		foreach ($results['FeedCJ']['products']['product'] as $key => $product) {
			$product['id'] = $this->_createIds($product);
			$results['FeedCJ']['products']['product'][$key] = $product;
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
		$id = $this->_explodeIds($id);
		$conditions['advertiser-ids'] = $id[0];
		$conditions['sku'] = $id[1];
		$conditions['keywords'] = $this->defaultKeywords;
		$query = array(
			'conditions' => $conditions);
		$results = $this->find('all', $query);
		return (isset($results['FeedCJ']['products']['product']) && count($results['FeedCJ']['products']['product']) > 0);
	}
	
	private function _createIds ($product) {
		return implode("__", array(
				$product['advertiser-id'],
				$product['sku'],
				));
	}

	private function _explodeIds ($id) {
		return explode("__", $id);
	}
}
