<?php
App::uses('FeedsController', 'Feeds.Controller');

/**
 * See for params http://help.cj.com/en/web_services/product_catalog_search_service_rest.htm
 */

class FeedCJsController extends FeedsController {
		
	public $uses = array('Feeds.FeedCJ');
	
	public $viewPath = 'Feeds';
	
	public $defaultKeywords = '+Clothing/Apparel';
	
	
	public function index () {
	
		$conditions = array();
		
		if(!empty($this->request['named'])) {
			$conditions = $this->request['named'];
		}
		
		//Add default Keywords
		$conditions['keywords'] = $this->defaultKeywords . ';' . $conditions['keywords'];
		
		$results = $this->FeedCJ->find('all', array(
			'conditions' => $conditions,
		));
		
		$this->set('products', $results['FeedCJ']['products']['product']);
		$this->set('pageNumber', $results['FeedCJ']['products']['@page-number']);
		$this->set('recPerPage', $results['FeedCJ']['products']['@records-returned']);
		$this->set('totalMatches', $results['FeedCJ']['products']['@total-matched']);
		
	}

	public function view() {
		
		if(!empty($this->request['named'])) {
			$conditions = $this->request['named'];
		}
		
		//Set Defaults
		$conditions['page-number'] = 1;
		$condtions['record-per-page'] = 1;
		
		$results = $this->FeedCJ->find('all', array(
			'conditions' => $conditions,
		));
		
		$this->set('product', $results['FeedCJ']['products']['product']);
	}
	
	public function advertisers ($keywords = array()) {
		
		if(!empty($this->request['named'])) {
			$advertiserName = $this->request['named']['name'];
		}
		$advertisers = $this->FeedCJ->getAdvertisers($keywords, $advertiserName);
		$this->set('advertisers', $advertisers);
		
	}
	
	public function __construct($request = null, $response = null) {
	
		//Adds Rateable Helpers.
		if (in_array('Ratings', CakePlugin::loaded())) {
			$this->helpers[] = 'Ratings.Rating';
		}
		
		//Adds Favorable Helpers
		if (in_array('Favorites', CakePlugin::loaded())) {
			$this->helpers[] = 'Favorites.Favorites';
			$this->uses[] = 'Favorites.Favorite';
		}
		
		parent::__construct($request, $response);
	}
	
	public function beforeRender() {
		parent::beforeRender();
		
		//Adds User Favorites to Views
		if (in_array('Favorites', CakePlugin::loaded())) {
			$userId = $this->Session->read('Auth.User.id');
			$this->set('userFavorites', $this->Favorite->getAllFavorites($userId));
		}
		
	}
}

