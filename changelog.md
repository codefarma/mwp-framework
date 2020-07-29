# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [2.2.6] - 2020-07-29

- (changed) - HTML using the data-view-model attribute can now have arbitrary view model objects embedded in the attribute

## [2.2.5] - 2020-07-22

- (added) Settings classes can now specify a new isNetworkGlobal=true property make the settings network wide

## [2.2.4] - 2020-07-02

- (added) New form builder method to embed active record tables into forms more easily
- (added) New support for creating extensions that add additional columns to ActiveRecord models

## [2.2.3] - 2020-05-27

- (added) New validation checks when building plugins to ensure composer dependencies are not missing

## [2.2.2] - 2020-03-18

- (fixed) Updated for compatibility with facebook-for-woocommerce

## [2.2.1] - 2019-12-05

- (added) - Default form generation for ActiveRecords has been abstracted to allow it to be re-usable

## [2.2.0] - 2019-11-25

- (changed) Annotations are now permanently cached and included in each plugin build to avoid crashes on systems where docblocks cant be read

## [2.1.10] - 2019-06-10

- (added) Added copy() method to the ActiveRecord class for cloning records

## [2.1.9] - 2091-06-04

- (fixed) Quieted some PHP notices
- (added) Admin controllers now have capabilities that can be used to control user access to them

## [2.1.8] - 2019-05-14

- (fixed) Fixed problem with class autoloader not able to find classes when using WP CLI

## [2.1.7] - 2019-02-20

- (fixed) Adjusted default form fields for models to better support data columns that allow null values
- (fixed) Styling fixed for active record tables nested inside of forms
- (fixed) Labeling for collection form field type items
- (added) Added new `loadTasks()` method to `Task` class

## [2.1.6] - 2018-10-22

- (fixed) Default record display not showing a value if field value was zero
- (fixed) Task runner now pulls tasks from the queue in an atomic way to prevent multiple runners from pulling the same task
- (added) New tasks view to display currently running tasks
- (added) New form styling for the fieldset/legend html elements

## [2.1.5] - 2018-10-05

- (changed) Abstracted the database connection used to CRUD active records so classes can load/save to alternate databases

## [2.1.4] - 2018-09-06

- (changed) Set caches are no longer tracked by the framework to improve caching performance
- (fixed) various php notices
- (fixed) incorrect network admin url in controllers when not in the network admin
- (added) column definitions on active records can specify if the field should be shown on edit/create forms
- (added) more exception handling to wrap symfony forms error conditions in a more user friendly way

## [2.1.3] - 2018-08-01

- (added) Added new class scaffold cli option for 'controller'
- (fixed) PHP Warnings
- (fixed) Conflict between bulk action form param and active records named 'action'

## [2.1.2] - 2018-07-24

- (added) Bottom seperator option for active record table row action links
- (fixed) Broken task maintenance database queries
- (fixed) Duplicate table search box on task view table
- (fixed) Paging issue on active record tables
- (fixed) Excessive time limits being set on task iterations

## [2.1.1] - 2018-07-09

- (added) Added ability to remove form fields using removeField() method on form helper

## [2.1.0] - 2018-07-03

- (added) Avoid errors from missing mbstring php extension using a symfony polyfill
- (added) New annotation to register a controller to a post page (front end controllers)
- (added) Active Record Controllers are now usable on the front end
- (added) Shortcode annotation can now be used on a controller class
- (changed) Improved tracking of of deployed active record tables in plugin meta
- (changed) The 'AdminController' pattern class was refactored to a more generic 'Controller'
- (changed) Certain active record class _get methods now accept a boolean flag to return their prefixed name
- (fixed) Active record table action button styling
- (fixed) The current page number for active record tables is now only read when the read_inputs() method is called

## [2.0.8.1] - 2018-06-20

- (added) Title of column now displays when viewing a record in the default view

## [2.0.8] - 2018-06-13

- (fixed) Bad method reference in task runner
- (changed) sequence_col and parent_col on ActiveRecord are protected by default
- (changed) Active record controller styling now closely mimics core styling
- (changed) Removed unused dependency tracking scripts
- (added) New support for admin pages to add them to the network admin menu
- (fixed) Various php notices
- (fixed) Cross contamination of css styles affecting bootstrap popovers

## [2.0.7] - 2018-06-01

- (changed) Changed the default record view template to better format record data
- (changed) Opted ActiveRecord and Singleton framework base classes into the extensibility pattern
- (fixed) Fixed some more display table awkwardness
- (fixed) Fixed db schema helper to avoid core dbDelta function from detecting changes on an unchanged table

## [2.0.6] - 2018-05-29

- (changed) Made database related static properties on ActiveRecord class protected by default
- (added) Added hard protection for core tables to prevent them from being dropped on plugin uninstall
- (added) Scaffolding of different php classes using the --type parameter of the 'add-class' CLI command

## [2.0.5] - 2018-05-22

- (fixed) Fixed broken sorting in active record table class
- (fixed) Issue where task maintenance routine was crossing multisite barrier
- (added) Added 'extras' property to active record table for generating extra navigation/sorting elements
- (added) Implementation of ajax for active record tables specifying the 'ajax' constructor option 
- (added) Ability to filter default admin controller getActions() using a function provided in the 'getActions' controller config
- (changed) Fixed the framework initialization to accomodate manual initialization for plugin un-installation
- (added) Plugin settings will also be deleted on plugin uninstall
- (fixed) Various PHP notices

## [2.0.4] - 2018-05-20

- (added) added ability to specify database field type information in the active record column definition
- (added) new CLI command to 'deploy-table' using an active record columns definition
- (added) new default field types for editing form based on the database columns definition for an active record
- (changed) all references to active record static properties should now go through the associated static method
- (changed) when an active record is saved, only the properties which have been changed are updated in the database row

## [2.0.3] - 2018-05-15

- (added) New default styling for viewing a record in a record controller
- (added) Bootstrap button grouping for active record controller actions
- (added) Added a new delete notice when deleting an active record

- (changed) Script and Style annotations will default to the plugin version for cache busting

- (fixed) Fixed the display of the helper for draggable/sortable records
- (fixed) Fixed an issue where form attributes were being output twice on rendering

## [2.0.2] - 2018-05-11

- (fixed) Fixed return data type of active record countWhere() method

## [2.0.1] - 2018-05-03

- (fixed) Fixed bug with media upload field

## [2.0.0] - 2018-04-26

- (added) changelog.md
- (added) New [class extensibility pattern][1] that allows any class prefixed with underscore to be internally decorated
- (added) CLI will now build using both 1.x and 2.x versions of MWP depending on the target plugin framework
- (added) New shutdown method on `MWP\Framework\Task` and ability to specify a [shutdown callback][2] action for tasks
- (added) New methods for `ActiveRecord` to [set table/controller][3] classes and [create/get controllers][4] by name
- (added) New [template filter assignment][5] via plugin method named `addTemplateFilter()`
- (added) New [javascript framework events][6]
- (added) Annotations on overridden parent methods are also read and attached unless `@MWP\Annotations\Override` is used
- (changed) Default support for [form building][7] using the Symfony Forms implementation
- (added) New methods to get/set/clear plugin cache data

### Breaking Changes
- (changed) all class namespacing has been changed from `Modern\Wordpress` to `MWP\Framework`
- (changed) class `ModernWordpressFramework` changed to `MWPFramework`
- (changed) annotations base namespace to `MWP\WordPress` (i.e. @MWP\WordPress\Action )
- (changed) hooks:
  > `modern_wordpress_init` *to* `mwp_framework_init`  
  > `modern_wordpress_find_plugins` *to* `mwp_framework_plugins`  
  > `modern_wordpress_queue_run` *to* `mwp_framework_queue_run`  
  > `modern_wordpress_queue_maintenance` *to* `mwp_framework_queue_maintenance`  
  > `mwp_form_class` *to* `mwp_fw_form_class`  
  > `mwp_form_implementation` *to* `mwp_fw_form_implementation`  
  > `mwp_tmpl` *to* `mwp_fw_tmpl`  
- (changed) Constant `MODERN_WORDPRESS_DEV` changed to `MWP_FRAMEWORK_DEV`
- (changed) removed the automatic `init()` method call on singleton instances, replaced with `constructed()`
- (changed) moved the framework init back to the 'after_setup_theme' hook to allow themes to extend plugins
- (changed) relocated the plugin templates overrides to the 'templates' subfolder of the plugin slug directory in the theme
- (changed) `ActiveRecord::getDeleteForm()` changed to `ActiveRecord::createDeleteForm()`
- (changed) Removed built in support for the Piklist form builder implementation

## [1.4.0] - 2018-01-29
- 1.4.0 release

- (added) Custom form building implementation of the Symfony forms library

 [1]: https://www.codefarma.com/docs/mwp-framework/classes-patterns/extensibility/
 [2]: https://www.codefarma.com/docs/mwp-framework/classes-patterns/tasks/#task-action-callbacks
 [3]: https://www.codefarma.com/docs/mwp-framework/classes-patterns/models/#settableclass
 [4]: https://www.codefarma.com/docs/mwp-framework/classes-patterns/models/#setcontrollerclass
 [5]: https://www.codefarma.com/docs/mwp-framework/guide/templating/#filters
 [6]: https://www.codefarma.com/docs/mwp-framework/javascript/
 [7]: https://www.codefarma.com/docs/mwp-framework/classes-patterns/forms/
 
