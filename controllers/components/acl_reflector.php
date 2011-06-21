<?php
class AclReflectorComponent
{
	private $controller = null;

	/****************************************************************************************/
    
    public function initialize(&$controller)
	{
	    $this->controller = $controller;
	}
	
	/****************************************************************************************/
	
	public function getPluginName($ctrlName = null)
	{
		$arr = String::tokenize($ctrlName, '/');
		if (count($arr) == 2) {
			return $arr[0];
		} else {
			return false;
		}
	}
	public function getPluginControllerName($ctrlName = null)
	{
		$arr = String::tokenize($ctrlName, '/');
		if (count($arr) == 2) {
			return $arr[1];
		} else {
			return false;
		}
	}
	public function get_controller_classname($controller_name)
	{
	    if(strrpos($controller_name, 'Controller') !== strlen($controller_name) - strlen('Controller'))
	    {
	        /*
	         * If $controller does not already end with 'Controller'
	         */
	        
    	    if(stripos($controller_name, '/') === false)
    	    {
    	        $controller_classname = $controller_name . 'Controller';
    	    }
    	    else
    	    {
    	        /*
    	         * Case of plugin controller
    	         */
    	        $controller_classname = substr($controller_name, strripos($controller_name, '/') + 1) . 'Controller';
    	    }
    	    
    	    return $controller_classname;
	    }
	    else
	    {
	        return $controller_name;
	    }
	}
	
	/****************************************************************************************/
	
	public function get_all_plugins_paths()
	{
		$plugin_names = array();
		
		$folder =& new Folder();
		
		$folder->cd(APP . 'plugins');
		$app_plugins = $folder->read();
		foreach($app_plugins[0] as $plugin_name)
		{
			$plugin_names[] = APP . 'plugins' . DS . $plugin_name;
		}
		
		$folder->cd(ROOT . DS . 'plugins');
		$root_plugins = $folder->read();
		foreach($root_plugins[0] as $plugin_name)
		{
			$plugin_names[] = ROOT . DS . 'plugins' . DS . $plugin_name;
		}
		
		return $plugin_names;
	}
	public function get_all_plugins_names()
	{
		$plugin_names = array();
		
		$folder =& new Folder();
		
		$folder->cd(APP . 'plugins');
		$app_plugins = $folder->read();
		if(!empty($app_plugins))
		{
			$plugin_names = array_merge($plugin_names, $app_plugins[0]);
		}
		
		$folder->cd(ROOT . DS . 'plugins');
		$root_plugins = $folder->read();
		if(!empty($root_plugins))
		{
			$plugin_names = array_merge($plugin_names, $root_plugins[0]);
		}
		
		return $plugin_names;
	}
	public function get_all_plugins_controllers($filter_default_controller = true)
	{
		$plugin_paths = $this->get_all_plugins_paths();
		
		$plugins_controllers = array();
		$folder =& new Folder();

		// Loop through the plugins
		foreach($plugin_paths as $plugin_path)
		{
			$didCD = $folder->cd($plugin_path . DS . 'controllers');
			
			if(!empty($didCD))
			{
				$files = $folder->findRecursive('.*_controller\.php');
	
				$plugin_name = substr($plugin_path, strrpos($plugin_path, DS) + 1);
				
				foreach($files as $fileName)
				{
					$file = basename($fileName);
	
					// Get the controller name
					$controller_class_name = Inflector::camelize(substr($file, 0, strlen($file) - strlen('_controller.php')));
					
					if(!$filter_default_controller || Inflector::humanize($plugin_name) != $controller_class_name)
					{
    					if (!preg_match('/^'. Inflector::humanize($plugin_name) . 'App/', $controller_class_name))
    					{
    						if (!App::import('Controller', $plugin_name . '.' . $controller_class_name))
    						{
    							debug('Error importing ' . $controller_class_name . ' for plugin ' . $plugin_name);
    						}
    						else
    						{
    						    $plugins_controllers[] = array('file' => $fileName, 'name' => Inflector::humanize($plugin_name) . "/" . $controller_class_name);
    						}
    					}
					}
				}
			}
		}
		
		sort($plugins_controllers);
		
		return $plugins_controllers;
	}
	public function get_all_plugins_controllers_actions($filter_default_controller = true)
	{
		$plugin_controllers = $this->get_all_plugins_controllers();
		
		$plugin_controllers_actions = array();
		
		foreach($plugin_controllers as $plugin_controller)
		{
			$plugin_name     = $this->getPluginName($plugin_controller['name']);
			$controller_name = $this->getPluginControllerName($plugin_controller['name']);
			
			if(!$filter_default_controller || $plugin_name != $controller_name)
			{
				$controller_class_name = $controller_name . 'Controller';
				
				$ctrl_cleaned_methods = $this->get_controller_actions($controller_class_name);
				
				foreach($ctrl_cleaned_methods as $action)
				{
					$plugin_controllers_actions[] = $plugin_name . '/' . $controller_name . '/' . $action;
				}
			}
		}
		
		sort($plugin_controllers_actions);
		
		return $plugin_controllers_actions;
	}
	
	public function get_all_app_controllers()
	{
		$controllers = array();
		$folder =& new Folder();
		
		$didCD = $folder->cd(APP . 'controllers');
		if(!empty($didCD))
		{
		    $files = $folder->findRecursive('.*_controller\.php');
		    
		    foreach($files as $fileName)
			{
				$file = basename($fileName);

				// Get the controller name
				$controller_class_name = Inflector::camelize(substr($file, 0, strlen($file) - strlen('_controller.php')));
				
				if (!App::import('Controller', $controller_class_name))
				{
					debug('Error importing ' . $controller_class_name . ' from APP controllers');
				}
				else
				{
				    $controllers[] = array('file' => $fileName, 'name' => $controller_class_name);
				}
			}
		}
		
		sort($controllers);
		
		return $controllers;
	}
	public function get_all_app_controllers_actions()
	{
		$controllers = $this->get_all_app_controllers();
		
		$controllers_actions = array();
		
		foreach($controllers as $controller)
		{
		    $controller_class_name = $controller['name'] . 'Controller';
		    
		    $ctrl_cleaned_methods = $this->get_controller_actions($controller_class_name);
				
			foreach($ctrl_cleaned_methods as $action)
			{
				$controllers_actions[] = $controller['name'] . '/' . $action;
			}
		}
		
		sort($controllers_actions);
		
		return $controllers_actions;
	}
	
	public function get_all_controllers()
	{
	    $app_controllers    = $this->get_all_app_controllers();
	    $plugin_controllers = $this->get_all_plugins_controllers();
	    
	    return array_merge($app_controllers, $plugin_controllers);
	}
	public function get_all_actions()
	{
	    $app_controllers_actions     = $this->get_all_app_controllers_actions();
	    $plugins_controllers_actions = $this->get_all_plugins_controllers_actions();
	    
	    return array_merge($app_controllers_actions, $plugins_controllers_actions);
	}
	
	/**
	 * Return the methods of a given class name.
	 * Depending on the $filter_base_methods parameter, it can return the parent methods.
	 *
	 * @param string $controller_class_name (eg: 'AcosController')
	 * @param boolean $filter_base_methods
	 */
	public function get_controller_actions($controller_classname, $filter_base_methods = true)
	{
	    $controller_classname = $this->get_controller_classname($controller_classname);
		$methods = get_class_methods($controller_classname);
		
		if(isset($methods) && !empty($methods))
		{
    		if($filter_base_methods)
    		{
    			$baseMethods = get_class_methods('Controller');
    		
    			$ctrl_cleaned_methods = array();
    		    foreach($methods as $method)
    		    {
    		        if(!in_array($method, $baseMethods) && strpos($method, '_') !== 0)
    				{
    				    $ctrl_cleaned_methods[] = $method;
    				}
    		    }
    		    
    		    return $ctrl_cleaned_methods;
    		}
    		else
    		{
    			return $methods;
    		}
		}
		else
		{
		    return array();
		}
	}
	
}