# shy-ajax-handler
WordPress plugin to specify which plugins should be active while answering an ajax call to admin-ajax.php - much better performance.

When a WordPress plugin calls admin-ajax.php WordPress will load every active plugin to answer the request. 
In a lot of cases only the calling plugin is needed to answer the request. 
The overhead of loading the other plugins will slow the answer down. For autocomplete and similar services, this can be painful.

## Install and use the plugin

This plugin should be put in the mu-plugins folder within wp-content (maybe you need to create the folder). MU-Plugins are loaded before all the other plugins.

This plugin is intended to be used by developers. There is no GUI. See the example code in the plugin file on how to use it. 

If the called ajax handler is registered in a plugin, this plugin is automatically ~~loaded while the theme `functions.php` is not loaded~~. If it's in a theme, no plugins are loaded at all. 

If the ajax call is depending on a plugin that is not the handler of the call, this plugin must be passed as 'active' manually, check the `option_active_plugins` filter for examples.

## Caveats
* all plugins that hold ajax handlers are exposed in the front end
