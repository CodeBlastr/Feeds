<?php
App::uses('FeedsController', 'Feeds.Controller');

/**
 * See for params http://help.cj.com/en/web_services/product_catalog_search_service_rest.htm
 */

class FeedCJsController extends FeedsController {
		
	public $uses = array('Feeds.FeedCJ');
	
	public $viewPath = 'Feeds';
	
	
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

	public function view($id = null) {
		
		if($id == null) {
			throw new NotFoundException('Could not find that product');
		}else {
			$this->FeedCJ->id = $id;
		}
		
		$results = $this->FeedCJ->find('first', array(
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
	
	
	public function rate ($id) {

		if ($id == null) {
			throw new NotFoundException('Please provide and id');
		}
		
		if (!in_array('Ratings', CakePlugin::loaded())) {
			throw new MethodNotAllowedException('Please Install Ratings Plugin');
		}
		
		if(!isset($this->request->data['Rating'])) {
			throw new NotFoundException('Item Not Found');
		}
		
		$this->FeedCJ->id = $id;
		$product = $this->FeedCJ->find('first');
		
		$product = $product['FeedCJ']['products']['product'];
		if(!empty($product)) {
			if(empty($product['manufacturer-name']) || empty($product['manufacturer-sku'])) {
				throw new MethodNotAllowedException('Unable to Rate this item');
			}	
			$this->request->data['Rating']['foreign_key'] = implode('__', array($product['manufacturer-name'], $product['manufacturer-sku']));
			$this->request->data['Rating']['data'] = serialize($product);
			$this->Rating->create();
			debug($this->Rating->save($this->request->data));
		}
		debug($product);
		debug($this->request);
		break;

		
		
	}
	
	
	
	public function __construct($request = null, $response = null) {
	
		//Adds Rateable Helpers.
		if (in_array('Ratings', CakePlugin::loaded())) {
			$this->helpers[] = 'Ratings.Rating';
			$this->uses[] = 'Ratings.Rating';
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

