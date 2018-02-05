MWP Application Framework
==================================

This "plugin" provides a foundation of object oriented design patterns, bootstrap classes, and api abstractions for wordpress that enable very rapid development of new wordpress plugins. It also provides several utilities to auto generate new plugin resources and automatically manage new plugin builds/releases.

## Documentation

- [Annotations](https://github.com/Miller-Media/modern-wordpress/wiki/@Annotations)
- [Framework Classes](https://github.com/Miller-Media/modern-wordpress/wiki)
- [WP CLI](https://github.com/Miller-Media/modern-wordpress/wiki/WP-CLI)
- [Boilerplate](https://github.com/Miller-Media/wp-plugin-boilerplate)

## Main features

* Simply document your functions using [@annotations](https://github.com/Miller-Media/modern-wordpress/wiki/@Annotations) and let the framework automatically hook them into core.
* Develop rapidly by [extending base classes](https://github.com/Miller-Media/wp-plugin-boilerplate) that bootstrap your plugin, settings pages, widgets, post types, and more.
* Use [the command line](https://github.com/Miller-Media/modern-wordpress/wiki/WP-CLI) to create new plugin stylesheets, scripts, templates, and php classes.
* Safely add dependencies on php libraries or other wordpress plugins and they will be managed automatically.
* Easily keep all your html in individual re-usable templates that maintain theme override capabilities.
* Leverage a built in task runner to easily send routine tasks off to a managed queue to be ran by cron.
* Create tables for your plugin and let the framework automatically track and update their changes on new releases.
* Build new release packages with a single command and all your plugin files are versioned automatically.

## How to get started:

1) **Install the packaged plugin and any dependencies**

Once you have WP CLI installed:
```
$ wp plugin install https://github.com/Miller-Media/modern-wordpress/raw/master/builds/mwp-framework-stable.zip --activate
```
> Note: If installing to a multisite wordpress, use --activate-network instead of --activate to activate the plugin from the command line.

2) Enable developer mode 

> To enable developer mode: Create or edit the **dev_config.php** file in the *wp-content/plugins/mwp-framework/* directory and add:
```php
<?php
define( 'MWP_FRAMEWORK_DEBUG', TRUE );
```

### Create A New Plugin
Bootstrap the creation of your own new plugin by cloning and customizing the [boilerplate plugin](https://github.com/Miller-Media/wp-plugin-boilerplate) using [WP CLI](https://wp-cli.org/):
```
$ wp mwp update-boilerplate
$ wp mwp create-plugin "Awesome New Plugin" --vendor="My Company" --author="My Name"
```
**Note**: By using the WP CLI to create your plugin, the boilerplate is automatically customized with your plugin details!

### Make It Do Something
To begin programming the functionality of your new plugin, just start adding methods to the *`./your-plugin-dir/classes/Plugin.php`* file, which can be hooked into wordpress [using @annotations](https://github.com/Miller-Media/modern-wordpress/wiki/@Annotations). At some point, you will likely want to separate your code out into separate files to keep things logically organized.

You can easily create new javascript modules, css stylesheets, html templates, and php classes all from the WP CLI.
```
$ wp mwp add-js myplugin-slug script-name
$ wp mwp add-css myplugin-slug stylesheet-name
$ wp mwp add-template myplugin-slug views/template-name
$ wp mwp add-class myplugin-slug New\Class
```

Visit the [boilerplate repository](https://github.com/Miller-Media/wp-plugin-boilerplate) to get a quick rundown of the development tools available to you through mwp application framework.

### Distribute It
When you are ready to build a new release of your plugin, that's easy too:

```
$ wp mwp build-plugin myplugin-slug --version-update=minor
```
A packaged .zip file that contains your new version will be created in the `/builds` subdirectory of your plugin. That zip file can be used to install the plugin on any other wordpress site.

Thats it. Have fun!

## Further Reading

* [Creating PHP Classes](https://github.com/Miller-Media/wp-plugin-boilerplate/blob/master/README.md#php-classes)
* [Using templates](https://github.com/Miller-Media/wp-plugin-boilerplate/blob/master/README.md#html-templating)
* [Using settings](https://github.com/Miller-Media/wp-plugin-boilerplate/blob/master/README.md#plugin-settings)
* [Using widgets](https://github.com/Miller-Media/wp-plugin-boilerplate/blob/master/README.md#widgets)
* [Using stylesheets and scripts](https://github.com/Miller-Media/wp-plugin-boilerplate/blob/master/README.md#stylesheets-and-scripts)
* [Using javascript modules](https://github.com/Miller-Media/wp-plugin-boilerplate/blob/master/README.md#javascript-module-programming)
* [Using active records](https://github.com/Miller-Media/wp-plugin-boilerplate/blob/master/README.md#database-records)
* [Using display tables](https://github.com/Miller-Media/wp-plugin-boilerplate/blob/master/README.md#active-record-display-tables)
* [Using the form helper](https://github.com/Miller-Media/wp-plugin-boilerplate/blob/master/README.md#form-helper)
* [Using task queues](https://github.com/Miller-Media/wp-plugin-boilerplate/blob/master/README.md#task-queues)
* [Unit testing your plugin](https://github.com/Miller-Media/wp-plugin-boilerplate/blob/master/README.md#testing-your-plugin)
