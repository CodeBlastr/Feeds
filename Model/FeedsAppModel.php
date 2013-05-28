<?php

class FeedsAppModel extends AppModel {
	
	public $useTable = false;
	
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

