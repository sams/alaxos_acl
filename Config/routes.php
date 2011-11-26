<?php

	Router::parseExtensions();

        App::uses('PluginShortRoute', 'Routing/Route');
	$plugin = Inflector::underscore('Acl');
        $match = array('plugin' => 'acl');
        $shortParams = array('routeClass' => 'PluginShortRoute', 'plugin' => 'acl');

	$params = array('plugin' => 'acl', 'prefix' => 'admin', 'admin' => true);
	$indexParams = $params + array('action' => 'index');
	Router::connect("/admin/acl", $indexParams, $shortParams);
	Router::connect("/admin/acl/:controller", $indexParams, $match);
	Router::connect("/admin/acl/:controller/:action/*", $params, $match);