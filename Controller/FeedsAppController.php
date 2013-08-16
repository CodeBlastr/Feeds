<?php

class FeedsAppController extends AppController {
	
        
    protected function _returnModelName($id = null) {
        
        if (empty($id) && empty($this->id)) {
            throw new Exception('Id not provided');
        }else {
            $id = !empty($id) ?  $id : $this->id;
        }
        
        $id = explode('__', $id);
        
        return 'Feed' . ucfirst(array_pop($id));
        
    }

}

