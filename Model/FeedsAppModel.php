<?php

class FeedsAppModel extends AppModel {
	
	public $useTable = false;
	
	
	/**
 	* Constructor
 	*/
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		
		//Adds Rateable Behavior.
		if (in_array('Ratings', CakePlugin::loaded())) {
			$this->actsAs[] = 'Ratings.Ratable';
		}
		
		//Adds Favorable Behavior
		if (in_array('Favorites', CakePlugin::loaded())) {
			$this->actsAs['Favorites.Favorite'] = array(
				'Feed' => array('limit' => null, 'model' => 'Feed'),
			);
		}
		
		
	}
	
	/**
	 * Find Method Find From Feed Datasource.
	 * 
	 * @param $query - See model
	 */
	
	public function find($type = 'all', $query = array()) {
		$this->findQueryType = $type;
		$this->id = $this->getID();
		
		$query = $this->buildQuery($type, $query);
		if (is_null($query)) {
			return null;
		}

		$results = $this->getDataSource()->read($this, $query);
		$this->resetAssociations();

		if ($query['callbacks'] === true || $query['callbacks'] === 'after') {
			$results = $this->_filterResults($results);
		}

		$this->findQueryType = null;

		if ($type === 'all') {
			return $results;
		} else {
			if ($this->findMethods[$type] === true) {
				return $this->{'_find' . ucfirst($type)}('after', $query, $results);
			}
		}
	}

}

