# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [2.0.0 Unreleased]

- (added) changelog.md
- (added) New [class extensibility pattern][1] that allows any class prefixed with underscore to be internally decorated
- (added) CLI will now build using both 1.x and 2.x versions of MWP depending on the target plugin framework
- (added) New shutdown method on `MWP\Framework\Task` and ability to specify a [shutdown callback][2] action for tasks
- (added) New methods for `ActiveRecord` to [set table/controller][3] classes and [create/get controllers][4] by name
- (added) New [template filter assignment][5] via plugin method named `addTemplateFilter()`
- (added) New [javascript framework events][6]
- (added) Annotations on overridden parent methods are also read and attached unless `@MWP\Annotations\Override` is used
- (changed) Default support for [form building][7] using the Symfony Forms implementation

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
 