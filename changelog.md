# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

- (added) changelog.md

### Breaking Changes
- (changed) all class namespacing has been changed from "Modern\Wordpress" to "MWP\Framework"
- (changed) class ModernWordpressFramework => MWPFramework
- (changed) annotations base namespace to WordPress (i.e. @WordPress\Action )
- (changed) hooks:
  > modern_wordpress_init => mwp_framework_init
  > modern_wordpress_find_plugins => mwp_framework_plugins
  > modern_wordpress_queue_run => mwp_framework_queue_run
  > modern_wordpress_queue_maintenance => mwp_framework_queue_maintenance
  > mwp_form_class => mwp_fw_form_class
  > mwp_form_implementation => mwp_fw_form_implementation
  > mwp_tmpl => mwp_fw_tmpl
- (changed) Constant MODERN_WORDPRESS_DEV changed to MWP_FRAMEWORK_DEBUG

## [1.4.0] - 2018-01-29
- 1.4.0 release