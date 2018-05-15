<?php
/**
 * Plugin HTML Template
 *
 * Created:  May 15, 2018
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    2.0.2
 *
 * @param	Plugin		$this		The plugin instance which is loading this template
 *
 * @param	ActiveRecord		$record			The record being deleted row
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>

<div class="panel panel-danger">
  <div class="panel-heading">
    <h3 class="panel-title"><?php _e( 'Alert', 'mwp-framework' ) ?></h3>
  </div>
  <div class="panel-body">
    <p class="text-center"><?php _e( 'You are about to delete this record. Are you sure you want to do this?', 'mwp-framework' ) ?></p>
  </div>
</div>
