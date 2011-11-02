<?php
class AclManagerComponent extends Component
{
    var $components = array('Acl', 'Acl.AclReflector', 'Session');
    
    /**
     * @var AclAppController
     */
	private $controller = null;
	private $controllers_hash_file;

	/****************************************************************************************/
    
    public function initialize(&$controller)
	{
	    $this->controller = $controller;
	    $this->controllers_hash_file = CACHE . 'persistent' . DS . 'controllers_hashes.txt';
	}
	
	/****************************************************************************************/
	
	/**
	 * Check if the file containing the stored controllers hashes can be created,
	 * and create it if it does not exist
	 *
	 * @return boolean true if the file exists or could be created
	 */
	private function check_controller_hash_tmp_file()
	{
	    if(is_writable(dirname($this->controllers_hash_file)))
	    {
	        App :: uses('File', 'Utility');
	        $file = new File($this->controllers_hash_file, true);
	        return $file->exists();
	    }
	    else
	    {
	        $this->Session->setFlash(sprintf(__d('acl', 'the %s directory is not writable'), dirname($this->controllers_hash_file)), 'flash_error', null, 'plugin_acl');
	        return false;
	    }
	}
	
	/****************************************************************************************/
	
	public function check_user_model_acts_as_acl_requester($model_classname)
	{
//		if(!isset($this->controller->{$model_classname}))
//		{
//			/*
//			 * Do not use $this->controller->loadModel, as calling it from a plugin may prevent correct loading of behaviors
//			 */
//			$user_model = ClassRegistry :: init($model_classname);
//		}
//		else
//		{
//			$user_model = $this->controller->{$model_classname};
//		}
		
	    $user_model = $this->get_model_instance($model_classname);
	    
		$behaviors = $user_model->actsAs;
		if(!empty($behaviors) && array_key_exists('Acl', $behaviors))
		{
			$acl_behavior = $behaviors['Acl'];
			if($acl_behavior == 'requester')
			{
				return true;
			}
			elseif(is_array($acl_behavior) && isset($acl_behavior['type']) && $acl_behavior['type'] == 'requester')
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Check if a given field_expression is an existing fieldname for the given model
	 *
	 * If it doesn't exist, a virtual field called 'alaxos_acl_display_name' is created with the given expression
	 *
	 * @param string $model_classname
	 * @param string $field_expression
	 * @return string The name of the field to use as display name
	 */
	public function set_display_name($model_classname, $field_expression)
	{
	    $model_instance = $this->get_model_instance($model_classname);
	    
	    $schema = $model_instance->schema();
	    
	    if(array_key_exists($field_expression, $schema)
	        ||
	       array_key_exists(str_replace($model_classname . '.', '', $field_expression), $schema)
	        ||
	       array_key_exists($field_expression, $model_instance->virtualFields))
	    {
	        /*
	         * The field does not need to be created as it already exists in the model
	         * as a datatable field, or a virtual field configured in the model
	         */
	        
	        /*
	         * Eventually remove the model name
	         */
	        if(strpos($field_expression, $model_classname . '.') === 0)
	        {
	            $field_expression = str_replace($model_classname . '.', '', $field_expression);
	        }
	        
	        return $field_expression;
	    }
	    else
	    {
	        /*
	         * The field does not exist in the model
	         * -> create a virtual field with the given expression
	         */
	        
	        $this->controller->{$model_classname}->virtualFields['alaxos_acl_display_name'] = $field_expression;
	        
	        return 'alaxos_acl_display_name';
	    }
	}
	
	/**
	 * Return an instance of the given model name
	 *
	 * @param string $model_classname
	 * @return Model
	 */
	private function get_model_instance($model_classname)
	{
	    if(!isset($this->controller->{$model_classname}))
		{
			/*
			 * Do not use $this->controller->loadModel, as calling it from a plugin may prevent correct loading of behaviors
			 */
			$model_instance = ClassRegistry :: init($model_classname);
		}
		else
		{
			$model_instance = $this->controller->{$model_classname};
		}
		
		return $model_instance;
	}
	 
	/**
	 * return the stored array of controllers hashes
	 *
	 * @return array
	 */
	public function get_stored_controllers_hashes()
	{
	    $file = new File($this->controllers_hash_file);
		$file_content = $file->read();
		
		if(!empty($file_content))
		{
			$stored_controller_hashes = unserialize($file_content);
		}
		else
		{
			$stored_controller_hashes = array();
		}
		
		return $stored_controller_hashes;
	}
	
	/**
	 * return an array of all controllers hashes
	 *
	 * @return array
	 */
	public function get_current_controllers_hashes()
	{
	    $controllers = $this->AclReflector->get_all_controllers();
	    
	    $current_controller_hashes = array();
	    
	    foreach($controllers as $controller)
	    {
	        $ctler_file = new File($controller['file']);
	        $current_controller_hashes[$controller['name']] = $ctler_file->md5();
	    }
	    
	    return $current_controller_hashes;
	}
	
	/**
	 * Get a list of plugins, controllers and actions that don't have any corresponding ACO.
	 * To run faster, the method only checks controllers that have not already been checked or that have been modified.
	 *
	 * Depending on the $update_hash_file, the method may return the missing ACOs only once
	 * (in order to show the alert message only once in the view)
	 *
	 * @param boolean $update_hash_file If true, the method update the controller hash file, making the method returning missing ACOs only once
	 * @return array Array of missing ACO nodes by comparing with each existing plugin, controller and action
	 */
	public function get_missing_acos($update_hash_file = true)
	{
	    if($this->check_controller_hash_tmp_file())
	    {
	        $missing_aco_nodes = array();
	        
    		$stored_controller_hashes  = $this->get_stored_controllers_hashes();
    		$current_controller_hashes = $this->get_current_controllers_hashes();
    		
    		/*
    		 * Store current controllers hashes on disk
    		 */
    		if($update_hash_file)
    		{
        		$file = new File($this->controllers_hash_file);
        		$file->write(serialize($current_controller_hashes));
    		}
    		
    		/*
    		 * Check what controllers have changed
    		 */
    		$updated_controllers = array_keys(Set :: diff($current_controller_hashes, $stored_controller_hashes));
    		
    		if(!empty($updated_controllers))
    		{
    			$aco =& $this->Acl->Aco;
    			
    			foreach($updated_controllers as $controller_name)
    			{
    			    if($controller_name !== 'App')
    			    {
        			    $controller_classname = $this->AclReflector->get_controller_classname($controller_name);
        			    
        			    $methods = $this->AclReflector->get_controller_actions($controller_classname);
        			    
        			    $aco =& $this->Acl->Aco;
        			    foreach($methods as $method)
        			    {
        			        $methodNode = $aco->node('controllers/' . $controller_name . '/' . $method);
        			        if(empty($methodNode))
        			        {
        			            $missing_aco_nodes[] = $controller_name . '/' . $method;
        			        }
        			    }
    			    }
    			}
    		}
    		
    		return $missing_aco_nodes;
	    }
	}

	/**
	 * Store missing ACOs for all actions in the datasource
	 * If necessary, it creates actions parent nodes (plugin and controller) as well
	 */
	public function create_acos()
	{
	    $aco =& $this->Acl->Aco;
	    
	    $log = array();
	    
	    $controllers = $this->AclReflector->get_all_controllers();
	    
	    /******************************************
	     * Create 'controllers' node if it does not exist
	     */
	    $root = $aco->node('controllers');
		if (empty($root))
		{
		    /*
		     * root node does not exist -> create it
		     */
		    
			$aco->create(array('parent_id' => null, 'model' => null, 'alias' => 'controllers'));
			$root              = $aco->save();
			$root['Aco']['id'] = $aco->id;
			
			$log[] = __d('acl', 'Created Aco node for controllers');
		}
		else
		{
			$root = $root[0];
		}
	    
	    foreach($controllers as $controller)
	    {
	        $controller_name = $controller['name'];
	        
	        if($controller_name !== 'App')
	        {
    	        $plugin_name     = $this->AclReflector->getPluginName($controller_name);
    	        $pluginNode      = null;
    	        
    	        if(!empty($plugin_name))
    	        {
    	            /*
    	             * Case of plugin controller
    	             */
    	            
    	            $controller_name = $this->AclReflector->getPluginControllerName($controller_name);
    	            
    	            /******************************************
    	             * Check plugin node
    	             */
    	            $pluginNode = $aco->node('controllers/' . $plugin_name);
    	            if(empty($pluginNode))
    	            {
    	                /*
    	                 * plugin node does not exist -> create it
    	                 */
    	                
    	                $aco->create(array('parent_id' => $root['Aco']['id'], 'model' => null, 'alias' => $plugin_name));
    					$pluginNode              = $aco->save();
    					$pluginNode['Aco']['id'] = $aco->id;
    					
    					$log[] = sprintf(__d('acl', 'Created Aco node for %s plugin'), $plugin_name);
    	            }
    	        }
    	        
    	        
    	        /******************************************
    	         * Check controller node
    	         */
    	        $controllerNode = $aco->node('controllers/' . (!empty($plugin_name) ? $plugin_name . '/' : '') . $controller_name);
                if(empty($controllerNode))
                {
                    /*
                     * controller node does not exist -> create it
                     */
                    
                    if(isset($pluginNode))
                    {
                        /*
                         * The controller belongs to a plugin
                         */
    
                        $plugin_node_aco_id = isset($pluginNode[0]) ? $pluginNode[0]['Aco']['id'] : $pluginNode['Aco']['id'];
                        
                        $aco->create(array('parent_id' => $plugin_node_aco_id, 'model' => null, 'alias' => $controller_name));
    					$controllerNode              = $aco->save();
    					$controllerNode['Aco']['id'] = $aco->id;
    					
    					$log[] = sprintf(__d('acl', 'Created Aco node for %s/%s'), $plugin_name, $controller_name);
                    }
                    else
                    {
                        /*
                         * The controller is an app controller
                         */
                        
                        $aco->create(array('parent_id' => $root['Aco']['id'], 'model' => null, 'alias' => $controller_name));
    					$controllerNode              = $aco->save();
    					$controllerNode['Aco']['id'] = $aco->id;
    					
    					$log[] = sprintf(__d('acl', 'Created Aco node for %s'), $controller_name);
                    }
                }
                else
    			{
    				$controllerNode = $controllerNode[0];
    			}
    	        
    	        
    	        /******************************************
    	         * Check controller actions node
    	         */
        	    $actions = $this->AclReflector->get_controller_actions($controller_name);
        	    
        	    foreach($actions as $action)
        	    {
        	        $actionNode = $aco->node('controllers/' . (!empty($plugin_name) ? $plugin_name . '/' : '') . $controller_name . '/' . $action);
        	        
        	        if(empty($actionNode))
        	        {
        	            /*
        	             * action node does not exist -> create it
        	             */
        	            
        	            $aco->create(array('parent_id' => $controllerNode['Aco']['id'], 'model' => null, 'alias' => $action));
    					$methodNode = $aco->save();
    					
    					$log[] = sprintf(__d('acl', 'Created Aco node for %s'), (!empty($plugin_name) ? $plugin_name . '/' : '') . $controller_name . '/' . $action);
        	        }
        	    }
	        }
	    }
	    
	    return $log;
	}
	
	/**
	 *
	 * @param AclNode $aro_nodes The Aro model hierarchy
	 * @param string $aco_path The Aco path to check for
	 * @param string $permission_type 'deny' or 'allow', 'grant', depending on what permission (grant or deny) is being set
	 */
	public function save_permission($aro_nodes, $aco_path, $permission_type)
	{
	    if(isset($aro_nodes[0]))
	    {
	        $aco_path = 'controllers/' . $aco_path;
	        
	        $pk_name = 'id';
	        if($aro_nodes[0]['Aro']['model'] == Configure :: read('acl.aro.role.model'))
	        {
	            $pk_name = $this->controller->_get_role_primary_key_name();
	        }
	        elseif($aro_nodes[0]['Aro']['model'] == Configure :: read('acl.aro.user.model'))
	        {
	            $pk_name = $this->controller->_get_user_primary_key_name();
	        }
	        
	        $aro_model_data = array($aro_nodes[0]['Aro']['model'] => array($pk_name => $aro_nodes[0]['Aro']['foreign_key']));
	        $aro_id         = $aro_nodes[0]['Aro']['id'];
    	    
	    	$specific_permission_right  = $this->get_specific_permission_right($aro_nodes[0], $aco_path);
	    	$inherited_permission_right = $this->get_first_parent_permission_right($aro_nodes[0], $aco_path);
	    	
	    	if(!isset($inherited_permission_right) && count($aro_nodes) > 1)
	    	{
	    	    /*
	    	     * Get the permission inherited by the parent ARO
	    	     */
	    	    $specific_parent_aro_permission_right = $this->get_specific_permission_right($aro_nodes[1], $aco_path);
	    	    
	    	    if(isset($specific_parent_aro_permission_right))
	    	    {
	    	        /*
	    	         * If there is a specific permission for the parent ARO on the ACO, the child ARO inheritates this permission
	    	         */
	    	        $inherited_permission_right = $specific_parent_aro_permission_right;
	    	    }
	    	    else
	    	    {
	    	        $inherited_permission_right = $this->get_first_parent_permission_right($aro_nodes[1], $aco_path);
	    	    }
	    	}
	        
	    	/*
    	     * Check if the specific permission is necessary to get the correct permission
    	     */
	    	if(!isset($inherited_permission_right))
	    	{
	    	    $specific_permission_needed = true;
	    	}
	    	else
	    	{
        	    if($permission_type == 'allow' || $permission_type == 'grant')
        	    {
        	        $specific_permission_needed = ($inherited_permission_right != 1);
        	    }
        	    else
        	    {
        	        $specific_permission_needed = ($inherited_permission_right == 1);
        	    }
	    	}
    	    
    	    if($specific_permission_needed)
    	    {
    	        if($permission_type == 'allow' || $permission_type == 'grant')
    	        {
        	        if($this->Acl->allow($aro_model_data, $aco_path))
        	        {
        	            return true;
        	        }
        	        else
        	        {
        	            trigger_error(__d('acl', 'An error occured while saving the specific permission'), E_USER_NOTICE);
        	            return false;
        	        }
    	        }
    	        else
    	        {
    	            if($this->Acl->deny($aro_model_data, $aco_path))
        	        {
        	            return true;
        	        }
        	        else
        	        {
        	            trigger_error(__d('acl', 'An error occured while saving the specific permission'), E_USER_NOTICE);
        	            return false;
        	        }
    	        }
    	    }
    	    elseif(isset($specific_permission_right))
    	    {
    	        $aco_node = $this->Acl->Aco->node($aco_path);
            	if(!empty($aco_node))
            	{
            	    $aco_id = $aco_node[0]['Aco']['id'];
            	    
        	        $specific_permission = $this->Acl->Aro->Permission->find('first', array('conditions' => array('aro_id' => $aro_id, 'aco_id' => $aco_id)));
        	        
        	        if($specific_permission !== false)
        	        {
        	            if($this->Acl->Aro->Permission->delete(array('Permission.id' => $specific_permission['Permission']['id'])))
        	            {
        	                return true;
        	            }
        	            else
        	            {
        	                trigger_error(__d('acl', 'An error occured while deleting the specific permission'), E_USER_NOTICE);
        	                return false;
        	            }
        	        }
        	        else
        	        {
        	            /*
        	             * As $specific_permission_right has a value, we should never fall here, but who knows... ;-)
        	             */
        	            
        	            trigger_error(__d('acl', 'The specific permission id could not be retrieved'), E_USER_NOTICE);
        	            return false;
        	        }
            	}
            	else
            	{
            	    /*
    	             * As $specific_permission_right has a value, we should never fall here, but who knows... ;-)
    	             */
            	    trigger_error(__d('acl', 'The child ACO id could not be retrieved'), E_USER_NOTICE);
        	        return false;
            	}
    	    }
    	    else
    	    {
    	        /*
    	         * Right can be inherited, and no specific permission exists => there is nothing to do...
    	         */
    	    }
	    }
	    else
	    {
	        trigger_error(__d('acl', 'Invalid ARO'), E_USER_NOTICE);
	        return false;
	    }
	}
	
	private function get_specific_permission_right($aro_node, $aco_path)
	{
	    $pk_name = 'id';
        if($aro_node['Aro']['model'] == Configure :: read('acl.aro.role.model'))
        {
            $pk_name = $this->controller->_get_role_primary_key_name();
        }
        elseif($aro_node['Aro']['model'] == Configure :: read('acl.aro.user.model'))
        {
            $pk_name = $this->controller->_get_user_primary_key_name();
        }
	    
	    $aro_model_data = array($aro_node['Aro']['model'] => array($pk_name => $aro_node['Aro']['foreign_key']));
    	$aro_id         = $aro_node['Aro']['id'];
    	
    	/*
    	 * Check if a specific permission of the ARO's on the ACO already exists in the datasource
    	 * =>
    	 * 		1) the ACO node must exist in the ACO table
    	 * 		2) a record with the aro_id and aco_id must exist in the aros_acos table
    	 */
    	$aco_id                    = null;
    	$specific_permission       = null;
    	$specific_permission_right = null;
    	
    	$aco_node = $this->Acl->Aco->node($aco_path);
    	if(!empty($aco_node))
    	{
    	    $aco_id = $aco_node[0]['Aco']['id'];
    	    
    	    $specific_permission = $this->Acl->Aro->Permission->find('first', array('conditions' => array('aro_id' => $aro_id, 'aco_id' => $aco_id)));
    	    
    	    if($specific_permission !== false)
    	    {
    	        /*
    	         * Check the right (grant => true / deny => false) of this specific permission
    	         */
    	        $specific_permission_right = $this->Acl->check($aro_model_data, $aco_path);
    	        
    	        if($specific_permission_right)
    	        {
    	            return 1;    // allowed
    	        }
    	        else
    	        {
    	            return -1;    // denied
    	        }
    	    }
    	}
    	
    	return null; // no specific permission found
	}
	
	private function get_first_parent_permission_right($aro_node, $aco_path)
	{
	    $pk_name = 'id';
        if($aro_node['Aro']['model'] == Configure :: read('acl.aro.role.model'))
        {
            $pk_name = $this->controller->_get_role_primary_key_name();
        }
        elseif($aro_node['Aro']['model'] == Configure :: read('acl.aro.user.model'))
        {
            $pk_name = $this->controller->_get_user_primary_key_name();
        }
        
	    $aro_model_data = array($aro_node['Aro']['model'] => array($pk_name => $aro_node['Aro']['foreign_key']));
    	$aro_id         = $aro_node['Aro']['id'];
    	
    	while(strpos($aco_path, '/') !== false && !isset($parent_permission_right))
        {
            $aco_path = substr($aco_path, 0, strrpos($aco_path, '/'));
            
	        $parent_aco_node = $this->Acl->Aco->node($aco_path);
	    	if(!empty($parent_aco_node))
	    	{
	    	    $parent_aco_id = $parent_aco_node[0]['Aco']['id'];
	    	    
    	    	$parent_permission = $this->Acl->Aro->Permission->find('first', array('conditions' => array('aro_id' => $aro_id, 'aco_id' => $parent_aco_id)));
	    	    
	    	    if($parent_permission !== false)
	    	    {
	    	        /*
	    	         * Check the right (grant => true / deny => false) of this first parent permission
	    	         */
	    	        $parent_permission_right = $this->Acl->check($aro_model_data, $aco_path);
	    	        
    	    	    if($parent_permission_right)
        	        {
        	            return 1;    // allowed
        	        }
        	        else
        	        {
        	            return -1;    // denied
        	        }
	    	    }
	    	}
        }
        
        return null; // no parent permission found
	}
}