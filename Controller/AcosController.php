<?php
/**
 *
 * @author   Nicolas Rod <nico@alaxos.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.alaxos.ch
 */
class AcosController extends AclAppController {

	var $name = 'Acos';
	//var $components = array('Acl', 'Acl.AclManager');
	 
	function admin_index()
	{
	    
	}
	
	function admin_empty_acos($run = null)
	{
	    if(isset($run))
	    {
    		if($this->Aco->deleteAll(array('id > 0')))
    	    {
    	        $this->Session->setFlash(__d('acl', 'The ACO table has been cleared', true), 'flash_message', null, 'plugin_acl');
    	    }
    	    else
    	    {
    	        $this->Session->setFlash(__d('acl', 'The ACO table could not be cleared', true), 'flash_error', null, 'plugin_acl');
    	    }
    	    
    	    $this->set('run', true);
	    }
	    else
	    {
	        $this->set('run', false);
	    }
	}
	
	function admin_build_acl($run = null)
	{
	    if(isset($run))
	    {
    		$logs = $this->AclManager->create_acos();
    		
    		$this->set('logs', $logs);
    		$this->set('run', true);
	    }
	    else
	    {
	        $this->set('run', false);
	    }
	}

	
}
?>