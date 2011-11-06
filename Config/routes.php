<?php
	$prefixes = array('admin');
        $plugin = array('Acl');

        App::uses('PluginShortRoute', 'Routing/Route');
        foreach ($plugin as $key => $value) {
                $plugin[$key] = Inflector::underscore($value);
        }
        $pluginPattern = implode('|', $plugin);
        $match = array('plugin' => $pluginPattern);
        $shortParams = array('routeClass' => 'PluginShortRoute', 'plugin' => $pluginPattern);

        foreach ($prefixes as $prefix) {
                $params = array('prefix' => $prefix, $prefix => true);
                $indexParams = $params + array('action' => 'index');
                Router::connect("/{$prefix}/:plugin", $indexParams, $shortParams);
                Router::connect("/{$prefix}/:plugin/:controller", $indexParams, $match);
                Router::connect("/{$prefix}/:plugin/:controller/:action/*", $params, $match);
        }

	foreach ($prefixes as $prefix) {
		$params = array('prefix' => $prefix, $prefix => true);
		$indexParams = $params + array('action' => 'index');
		Router::connect("/{$prefix}/:controller", $indexParams);
		Router::connect("/{$prefix}/:controller/:action/*", $params);
	}

	$namedConfig = Router::namedConfig();
	if ($namedConfig['rules'] === false) {
		Router::connectNamed(true);
	}

	unset($namedConfig, $params, $indexParams, $prefix, $prefixes, $shortParams, $match,
		$pluginPattern, $plugins, $key, $value);