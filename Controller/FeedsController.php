<?php

class _FeedsController extends FeedsAppController {
    
    
    //Array of search categories
    // $value => 'Dispaly Name'
    
    public $allowedActions = array('get_categories');
    
    public function shop () {
        
        $conditions = array();
        
        if(!empty($this->request['named'])) {
            $conditions = $this->request['named'];
        }
        
        //Create Defaults
        $keywords = isset($conditions['keywords']) ? $conditions['keywords'] : '';
        $category = isset($conditions['category']) ? $conditions['category'] : '';
        $records_per_page = isset($conditions['records-per-page']) ? $conditions['records-per-page'] : 10;
        $page_number = isset($conditions['page-number']) ? $conditions['page-number'] : 1;
        
        $search = array(
            
        );
        if($this->Session->check('Search')) {
            $search = $this->Session->read('Search');
        }
        
        //if search is different requery results, otherwise just use session data
        if($search['keywords'] != $keywords || $search['category'] != $category) {
        
            try{
                
                $products = array();
                foreach($this->uses as $k => $model) {
                    $model = explode('.', $model);
                    if(count($model) > 1 && $model[0] == 'Feeds') {
                        $products = array_merge($products, $this->$model[1]->find('all', array(
                            'conditions' => $conditions,
                        )));
                        
                    }
                }
                
                
                if (in_array('Ratings', CakePlugin::loaded()) && !empty($products)) {
                    foreach($products as $k => $item) {
                          $products[$k]['Ratings'] = $this->_getProductRating($item['Rating_id']);
                    }
                }
                
            }catch(Exception $e) {
                $this->Session->setFlash($e->message);
            }
            $this->_sortRel($products, $conditions);
            $keywords = str_replace('+', '', $keywords);
            $this->Session->write('Search.results', $products);
            $this->Session->write('Search.keywords', $keywords);
            $this->Session->write('Search.category', $category);
        }else {
            $products = $search['results'];
        }
        
        $total_results = count($products);
        $products = array_slice($products, $page_number * $records_per_page, $records_per_page);
        
        $this->Session->write('Search.page_number', $page_number);
        $this->Session->write('Search.records_per_page', $records_per_page);
        
        $this->set('pagetitle', 'Shop for Products');
        $this->set('categories', $this->categories);
        $this->set('keywords', $keywords);
        $this->set('category', $category);
        $this->set('records_per_page', $records_per_page);
        $this->set('products', $products);
        $this->set('pageNumber', $page_number);
        $this->set('totalMatches', $total_results);
        
    }

    public function view($id = null) {
        
        if($id == null) {
            throw new NotFoundException('Could not find that product');
        }
        
        $Model = $this->_returnModelName($id);
        
        $this->$Model->id = $id;
        
        $results = $this->$Model->find('first');
       
        $fromUsers = null;
        
        // get the ratings of this item if possible
        if (CakePlugin::loaded('Ratings') && !empty($results)) {
                $results[0]['Rating'] = $this->_getProductRating($results[0]['Rating_id']);
        };
       
        $this->set('title_for_layout', $results[0]['name'] . ' | ' . __SYSTEM_SITE_NAME);
        $this->set('product', $results[0]);
    }
    

    public function retrieveItems ($type, $userId) {
        $favorites = $this->Favorite->getFavorites($userId, array('type' => $type));
        $results = array();
        foreach ( $favorites as $favorite ) {
            $Model = $this->_returnModelName($favorite['Favorite']['foreign_key']);
            $this->$Model->id = $favorite['Favorite']['foreign_key'];
            $results = array_merge($results, $this->$Model->find('first'));
        }
        
        if (in_array('Ratings', CakePlugin::loaded()) && !empty($results)) {
                foreach($results as $k => $item) {
                      $results[$k]['Ratings'] = $this->_getProductRating($item['Rating_id']);
                }
        }
        
        return $results;
    }
    
    
    public function __construct($request = null, $response = null) {
        
        //Inits the feeds we have datasources for throws exception if none configured
        App::uses('ConnectionManager', 'Model');
        $sources = ConnectionManager::enumConnectionObjects ();
        
        if(count($sources) == 2 && isset($sources['default']) && isset($sources['test'])) {
            throw new MethodNotAllowedException('No Feed Sources Configured', 1);
        }
        
        if(isset($sources['commissionjunction'])) {
            $this->uses[] = 'Feeds.FeedCj';
        }
        
        if(isset($sources['amazon'])) {
            $this->uses[] = 'Feeds.FeedAmazon';
        }
    
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
    
    
    protected function _getProductRating($id, $fromUsers = false) {
        
        if(empty($id)) {
            return array();
        }
        
        $conditions = array('Rating.foreign_key' => $id);
        
        if ( $fromUsers ) {
            $conditions['Rating.user_id'] = $fromUsers;
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
    
    /**
     * Function to return user categories
     * @return the $categories aray
     */
    
    public function get_categories() {
        $this->autoRender = false; //For request action only
        $categories = array();
         foreach($this->uses as $k => $model) {
            $model = explode('.', $model);
            if(count($model) > 1 && $model[0] == 'Feeds') {
                $categories = array_merge($categories, $this->$model[1]->categories);
            }
        }
        return $categories;
    }
    
    /**
     * Function to sort the feed array for relavency by conditions
     */
    
    protected function _sortRel($products = array(), $conditions) {
        
       if(empty($conditions['keywords'])) {
           return $products;
       }
       $keywords = explode(' ', $conditions['keywords']);
       
       if(isset($conditions['category'])) {
           array_push($keywords, $conditions['category']);
       }
       
       foreach($products as $k => $product){
           $relrating = 0;
           foreach($keywords as $keyword) {
               //By title * 2
               $relrating = $relrating + substr_count(strtolower($product['name']), strtolower($keyword)) * 2 ; 
               $relrating = $relrating + substr_count(strtolower($product['descritpion']), strtolower($keyword)); 
               $relrating = $relrating + substr_count(strtolower($product['category']), strtolower($keyword)); 
               $relrating = $relrating + substr_count(strtolower($product['manufacturer_name']), strtolower($keyword));
           }
           $product['Relevancy'] = $relrating;
           $products[$k] = $product;
       }
       return Set::sort($products, '{n}.Relevancy', 'desc');
       
    }

}

if (!isset($refuseInit)) {
    class FeedsController extends _FeedsController {}
}
