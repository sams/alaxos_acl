ACL Plugin for CakePHP 1.3
===========================

Version: 1.0.6
Date: 2011-04-27
Developer: Nicolas Rod <nico@alaxos.com>
Website: http://www.alaxos.net/blaxos/pages/view/plugin_acl
License: http://www.opensource.org/licenses/mit-license.php The MIT License

This CakePHP plugin is an interface to manage an ACL protected web application.

It is made to work with ACL already configured. The documentation to configure the ACL 
can be found on the following links :

- http://book.cakephp.org/view/1242/Access-Control-Lists
- http://book.cakephp.org/view/1543/Simple-Acl-controlled-Application


Installation
-------------

- Download the plugin and put it in your 'app/plugins' or 'plugins' folder
- Configure the 'admin' route (see http://book.cakephp.org/view/1565/Library-Classes)
- Configure the plugin according to your web application:

	Some settings have to be read by CakePHP when the plugin is accessed. They can be found
	in the 'acl/config/bootstrap.php' file.
	
	You have two options to use these settings, as CakePHP doesn't automatically load 
	the bootstrap.php files in plugins:
	
	1.	Copy all the settings in your app/config/bootstrap.php file
	
	or
	
	2.	Include the ACL plugin bootstrap.php file in your app/config/bootstrap.php file
    
        require_once(ROOT . DS . 'plugins' . DS . 'acl' . DS . 'config' . DS . 'bootstrap.php');
        or
        require_once(APP . DS . 'plugins' . DS . 'acl' . DS . 'config' . DS . 'bootstrap.php');

- Access the ACL plugin by navigating to /admin/acl
