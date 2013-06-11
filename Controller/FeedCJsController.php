<?php
App::uses('FeedsController', 'Feeds.Controller');

/**
 * See for params http://help.cj.com/en/web_services/product_catalog_search_service_rest.htm
 */

class FeedCJsController extends FeedsController {
		
	public $uses = array('Feeds.FeedCJ');
	
	public $viewPath = 'Feeds';
	
	
	public function index () {
		try{
			$conditions = array();
			
			if(!empty($this->request['named'])) {
				$conditions = $this->request['named'];
			}
			
			//Add default Keywords
			$conditions['keywords'] = $this->defaultKeywords . ';' . $conditions['keywords'];
			
			$results = $this->FeedCJ->find('all', array(
				'conditions' => $conditions,
			));
			
			$products = $results['FeedCJ']['products']['product'];
			if (in_array('Ratings', CakePlugin::loaded()) && !empty($products)) {
				foreach($products as $k => $product) {
					if(!empty($product['manufacturer-name']) && !empty($product['manufacturer-sku'])) {
						$id = implode('__', array($product['manufacturer-name'], $product['manufacturer-sku']));
						
						$products[$k]['ratings'] = $this->_getRating($id);
					}
				}
			}
		}catch(Exception $e) {
			$this->Session->setFlash($e);
		}
		$this->set('products', $products);
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
		
		$results = $this->FeedCJ->find('first');
		$product = $results['FeedCJ']['products']['product'];
		
		/** @todo Probably should be in a custom controller **/
		
//		// get user's sizing data if possible
		$fromUsers = null;
//		try {
//		   $this->loadModel('Users.UserMeasurement');
//		   $fromUsers = $this->UserMeasurement->findSimilarUsers($this->Auth->user('id'));
//		} catch(MissingModelException $e) {		
//			// guess we're not using Measurement data
//		}
		
		// get the ratings of this item if possible
		if (in_array('Ratings', CakePlugin::loaded()) && !empty($results)) {
			if(!empty($product['manufacturer-name']) && !empty($product['manufacturer-sku'])) {
				$id = implode('__', array($product['manufacturer-name'], $product['manufacturer-sku']));
				$product['ratings'] = $this->_getRating($id, $fromUsers);
			}
		};
		/** end change scope **/
		
		$this->set('product', $product);
	}
	
	/**
	 * @todo Definitely should be in a custom Controller
	 * 
	 * @param type $productId
	 */
	public function fitMe ($productId) {
		
		if ( $productId == null ) {
			throw new NotFoundException('Could not find that product');
		} else {
			$this->FeedCJ->id = $productId;
		}
		
		$results = $this->FeedCJ->find('first');
		$product = $results['FeedCJ']['products']['product'];
		
		// get user's sizing data if possible
		$fromUsers = null;
		try {
		   $this->loadModel('Users.UserMeasurement');
		   $fromUsers = $this->UserMeasurement->findSimilarUsers($this->Auth->user('id'));
		} catch(MissingModelException $e) {		
			// guess we're not using Measurement data
		}
		
		// get the ratings of this item if possible
		if ( in_array('Ratings', CakePlugin::loaded()) && !empty($results) ) {
			if ( !empty($product['manufacturer-name']) && !empty($product['manufacturer-sku']) ) {
				$id = implode('__', array($product['manufacturer-name'], $product['manufacturer-sku']) );
				$product['ratings'] = $this->_getRating($id, $fromUsers);
			}
		};
		
		if ( $this->request->isAjax() ) {
			$this->layout = null;
		}
		
		$this->set('product', $product);
		
	}
	
	public function advertisers ($keywords = array()) {
		
		if(!empty($this->request['named'])) {
			$advertiserName = $this->request['named']['name'];
		}
		$advertisers = $this->FeedCJ->getAdvertisers($keywords, $advertiserName);
		$this->set('advertisers', $advertisers);
		
	}
	
	/**
	 * Saves Rating for Feed item.
	 * Will Return not found if not handed and Rating.
	 * 
	 * @param $id string of created feed id. See $model->createIds()
	 */

	public function rate ($id = null) {
		try{
			if ($id == null) {
				throw new NotFoundException('Please provide and id');
			}

			$this->FeedCJ->id = $id;
			$product = $this->FeedCJ->find('first');
			
			if(!empty($this->request->data)) {
					if (!in_array('Ratings', CakePlugin::loaded())) {
						throw new MethodNotAllowedException('Please Install Ratings Plugin');
					}
					
					if(!isset($this->request->data['Rating'])) {
						$this->Session->setFlash('Need to provide a rating');
						$this->redirect($this->referer());
					}
					//rate(Model $Model, $foreignKey = null, $userId = null, $rating = null, $options = array(), $parent_id = null)
					$product = $product['FeedCJ']['products']['product'];
					if(!empty($product['manufacturer-name']) && !empty($product['manufacturer-sku'])) {
						$foreignKey = implode('__', array($product['manufacturer-name'], $product['manufacturer-sku']));
						$userId = $this->Session->read('Auth.User.id');
						$rating = $this->request->data['Rating']['value'];
						$options = array();
						$parent = array();
						//Build the Parent Element
						$parent['user_id'] = $userId;
						$parent['foreign_key'] = $foreignKey;
						$parent['model'] = 'FeedCJ';
						$parent['value'] = $rating;
						$parent['title'] = $this->request->data['Rating']['title'];
						$parent['review'] = $this->request->data['Rating']['review'];
						$parent['data'] = serialize($product);
						$parent['parent_id'] = null;
						
						$options['records']['Rating'][] = $parent;
						//Save the Parent Rating
						if(!empty($this->request->data['Rating']['SubRating'])) {
							foreach($this->request->data['Rating']['SubRating'] as $k => $subRating) {
								$child = array();
								$child['user_id'] = $parent['user_id'];
								$child['foreign_key'] = $parent['foreign_key'];
								$child['model'] = $parent['model'];
								$child['value'] = $subRating;
								$child['type'] = $k;
								$options['records']['SubRatings'][] = $child;
							}
						}
						if($this->FeedCJ->rate($foreignKey, $userId, $rating, $options)) {
							$this->Session->setFlash('Thank you for your input');
							$this->redirect($this->referer());
						}else {
							throw new CakeException('Unable to save rating');
						}
						
						
						
					}else {
						throw new OutOfBoundsException(__d('ratings', 'Can only Rate Items with valid Manufacturer Name and SKU'));
					}
				}
			
			
				if($this->request->isAjax()) {
					$this->layout=null;
				}
				$this->set('product', $product['FeedCJ']['products']['product']);
			}
			catch (Exception $e) {
					$this->Session->setFlash('Error: ' . $e->getMessage());
					$this->redirect($this->referer());
			}
	}
	

	public function retrieveItems ($type, $userId) {
		$favorites = $this->Favorite->getFavorites($userId, array('type' => $type));

		foreach ( $favorites as $favorite ) {
			$this->FeedCJ->id = $favorite['Favorite']['foreign_key'];
			$results = $this->FeedCJ->find('first');
			$items[] = $results['FeedCJ']['products']['product'];
		}
		return $items;
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
	
	private function _getRating($id, $fromUsers = false) {
		
		$conditions = array('Rating.foreign_key' => $id);
		
		if ( $fromUsers ) {
			$conditions[] = array('Rating.user_id' => $fromUsers);
		}
		
		$ratings['Ratings'] = $this->Rating->find('all', array(
				'conditions' => $conditions,
			));
		$overall = array();
		$subratings = array();
		
		if(!empty($ratings['Ratings'])) {
			foreach($ratings['Ratings'] as $k => $rating) {
				$type = $rating['Rating']['type'];
				if(empty($type)) {
					$overall[] = $rating['Rating']['value'];
				}else{
					$subratings[$type][] = $rating['Rating']['value'];
				}
			}
		}else {
			return array();
		}
		
		$ratings['overall'] = !empty($overall) ? array_sum($overall)/count($overall) : $overall;
		if(!empty($subratings)) {
			foreach($subratings as $t => $sub) {
				$ratings['SubRatings'][$t] = array_sum($sub)/count($sub);
			}
		}
		
		return $ratings;
	}
}

