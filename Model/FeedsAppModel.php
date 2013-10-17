<?php

class FeedsAppModel extends AppModel {
	
	public $useTable = false;
	
	public $actAs = array();
	
	public $fieldmap = array(
		'name' => '',
		'description' => '',
		'advertiser_name' => '',
		'advertiser_id',
		'category' => '',
		'image_url' => '',
		'manufacturer_name' => '',
		'manufacturer_identifier' => '',
		'upc' => '',
		'isbn' => '',
		'retail_price' => '',
		'price' => '',
		'sale_price' => '',
		'currency' => '',
		'buy_url' => '',
		'product_id' => 'id'
	);
	
	//Total Results found 
	public $totalResults = 0;
	
	
	/**
 	* Constructor
 	*/
	public function __construct($id = false, $table = null, $ds = null) {
		
		//Adds Rateable Behavior.
		if (CakePlugin::loaded('Ratings')) {
			$this->actsAs[] = 'Ratings.Ratable';
		}
		
		//Adds Favorable Behavior
		if (in_array('Favorites', CakePlugin::loaded())) {
			$this->actsAs[] = 'Favorites.Favorite';
		}
		
		parent::__construct($id, $table, $ds);
		
		
	}
	
	protected function _renderproductdata($items = array()) {
		$products = array();
		if(!empty($items)) {
			foreach($items as $index => $item) {
				foreach($this->fieldmap as $key => $value) {
					$products[$index][$key] = $item[$value];
				}
			}
		}
        
		return $products;
		
	}
	
	protected function detectClothingType($result) {
        if ( !empty($result['name']) ) {
            $types = array(
                'dress' => array('dress'),
                'skirt' => array('skirt', 'skirts', 'mini skirt'),
                'tshirt' => array('t-shirt', 't shirt', 'tee', 'tees'),
                'pants' => array('jeans', 'pants', 'slacks', 'trousers', 'pant'),
                'shorts' => array('shorts'),
                'shirt' => array('shirt', 'long sleeve', 'sweatshirt'),
                'shoes' => array('shoes', 'sneaker', 'sneakers', 'heels', 'boots', 'sandals', 'clogs'),
                'jacket' => array('coat', 'jacket', 'jackets', 'wind breaker')
            );
            
            $result['type'] = 'unknown';
            
            foreach ( $types as $type => $words ) {
                foreach($words as $word) {
                    if ( strpos(strtolower($result['name']), $word) !== false || strpos(strtolower($result['description']), $word) !== false ) {
                        $result['type'] = $type;
                        return $result;
                    }
                }   
            }
            
        }
        
        return $result;
    }

    /**
     * Explode generated Rating Id 
     * @param $id array generated by for ratings
     * @return product array
     */
    
    public function explodeRatingIds ($id, $map = false) {
        $id = explode("__", $id);
        $product['manufacturer_name'] = isset($id[0]) ? $id[0] : 0;
        $product['manufacturer_idenifier'] = isset($id[1]) ? $id[1] : 0;
        $product['upc'] = isset($id[2]) ? $id[2] : 0;
        $product['isbn'] = isset($id[3]) ? $id[3] : 0;
        
        foreach($product as $k => $v) {
            if(empty($v)) {
                unset($product[$k]);
            }
        }
		
		if($map) {
			$mapped = array();
			foreach($product as $field => $value) {
				$mapped[$this->fieldmap[$field]] = $value;
			}
			$product = $mapped;
		}
		
        return $product;
    }
    
    /**
     * Generated Rating Id 
     * @param $product with proper values created with _renderproductdata
     * @return id for rating
     */
    
    protected function _createRatingIds ($product) {
        if((!empty($product['manufacturer_name']) && !empty($product['manufacturer_idenifier'])) || !empty($product['upc']) || !empty($product['isbn'])) {
                
            return implode("__", array(
                    str_replace('__', '', $product['manufacturer_name']),
                    str_replace('__', '', $product['manufacturer_idenifier']),
                    str_replace('__', '', $product['upc']),
                    str_replace('__', '', $product['isbn']),
            ));
        }
        
        return '';
    }
	
	/**
	 * Custom After find, because record permissions aren't available of fake objects
	 */
	public function afterFind($results, $primary = false) {
	   
        if (CakePlugin::loaded('Ratings')) {
                foreach($results as $k => $item) {
                      $results[$k]['Rating_id'] = $this->_createRatingIds($item);
                }
        }
	    
        //Add Clothing Info
        foreach($results as $k => $item) {
               $results[$k] = $this->detectClothingType($item);
        }
        
		return $results;
		
	}
	
    

}

