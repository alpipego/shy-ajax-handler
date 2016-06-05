<?php
/*
Plugin Name: Shy Ajax Handler
Plugin URI: https://sebastian-gaertner.com
Description: Only load necessary plugins for ajax responses
Version: 0.2.0
Author: Sebastian Gärtner
Author URI: https://sebastian-gaertner.com
Co-Author: Alexander Goller
License: GPLv2
*/

if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX || ! isset( $_REQUEST['action'] ) ) {
	// get the plugin that registered the ajax handler
	// based on http://stackoverflow.com/a/26680808/2105015
	function get_handling_plugin( $hook = '' ) {
		global $wp_filter;

		$hooks = isset( $wp_filter[ $hook ] ) ? $wp_filter[ $hook ] : [];
		$hooks = call_user_func_array( 'array_merge', $hooks );
		$file  = '';

		foreach ( $hooks as &$item ) {
			// function name as string or static class method eg. 'Foo::Bar'
			if ( is_string( $item['function'] ) ) {
				$ref  = strpos( $item['function'], '::' ) ? new ReflectionClass( strstr( $item['function'], '::', true ) ) : new ReflectionFunction( $item['function'] );
				$file = $ref->getFileName();
				// array( object, method ), array( string object, method ), array( string object, string 'parent::method' )
			} elseif ( is_array( $item['function'] ) ) {
				$ref = new ReflectionClass( $item['function'][0] );
				$file = $ref->getFileName();
				// closures
			} elseif ( is_callable( $item['function'] ) ) {
				$ref = new ReflectionFunction( $item['function'] );
				$file = $ref->getFileName();
			}
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// taken from http://wordpress.stackexchange.com/a/124605/48863
		$plugin_dir  = @explode( '/', plugin_basename( $file ) )[0];
		$plugin_file = @array_keys( get_plugins( "/$plugin_dir" ) )[0];

		// if a plugin registered the handler, return this
		if ( isset( $plugin_dir ) && isset( $plugin_file ) ) {
			$return = $plugin_dir . '/' . $plugin_file;
			// if it's from a theme return theme
		} elseif (strpos($file, get_template_directory()) === 0) {
			$return = 'theme';
			// else return false
		} else {
			$return = false;
		}

		return $return;
	}

	// if this is not an ajax call enqueue js
	// get all registered ajax handlers and pass them to js
	add_action( 'wp_enqueue_scripts', function () {
		// get all attached ajax handlers
		global $wp_filter;
		$collector = [ ];
		foreach ( $wp_filter as $filter => $params ) {
			if ( strpos( $filter, 'wp_ajax_' ) !== 0 ) {
				continue;
			}
			// get the plugin for each ajax handler
			try {
				$collector[ $filter ] = get_handling_plugin( $filter );
			} catch ( Exception $e ) {
				// ignore
			}
		}

		// enqueue the script
		wp_enqueue_script( 'shy_ajax', plugins_url( 'shy_ajax.js', __FILE__ ), [ 'jquery' ], '1.0.0', true );
		// pass all found ajax calls to js
		wp_localize_script( 'shy_ajax', 'shy_ajax', [
			'collection' => json_encode( $collector ),
			'loggedin' => is_user_logged_in(),
		] );
	} );
} else {
	add_filter( 'option_active_plugins', function() {
		$plugins = [];
		// manually add always active plugins, (translation plugins, acf... anything your plugin or theme may depend on)
		// or use conditionals either based on plugins, e.g.
		// if ( $_REQUEST['plugin'] == 'addon-plugin/addon-plugin.php' ) {
		//     $plugins[] = 'parent-plugin/parent-plugin.php';
		// }
		// or base it on the action, e.g. this call from my theme depends on a specific plugin
		// if ( $_REQUEST['action'] === 'wp_ajax_my_theme_handler') {
		//     $plugins[] = 'plugin/plugin.php';
		// }

		if ($_REQUEST['handler'] !== 'theme') {
			// activate the plugin with the ajax handler
			$plugins[] = $_REQUEST['handler'];
			// don't load the theme
			define('TEMPLATEPATH', '');
			define('STYLESHEETPATH', '');
		}

		return $plugins;
	}, 2 );
}
