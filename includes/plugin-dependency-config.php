<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/* TGM Plugin Dependency Manager */
include_once 'class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', function() 
{
	$base_dir = dirname( dirname( __FILE__ ) );
	$dependencies = array(
		array( 
			'name'             => 'Piklist',
			'slug'             => 'piklist',
			'required'         => true,
			'force_activation' => true,
		),
	);
	
	$config = array(
		'id'           => basename( $base_dir ),
		'default_path' => $base_dir . '/bundles',
		'menu'         => 'tgmpa-install-plugins',
		'parent_slug'  => 'plugins.php',
		'capability'   => 'manage_options',
		'has_notices'  => true,
		'dismissable'  => false,
		'is_automatic' => true,
		'message'      => '',
		'strings'      => array(
			'notice_can_install_required'     => _n_noop(
				'<em>MWP Application Framework</em> requires the following plugin: %1$s.',
				'<em>MWP Application Framework</em> requires the following plugins: %1$s.',
				basename( $base_dir )
			),
			'notice_can_install_recommended'  => _n_noop(
				'<em>MWP Application Framework</em> recommends the following plugin: %1$s.',
				'<em>MWP Application Framework</em> recommends the following plugins: %1$s.',
				basename( $base_dir )
			),
			'notice_ask_to_update'            => _n_noop(
				'The following plugin needs to be updated to its latest version to ensure maximum compatibility with <em>MWP Application Framework</em>: %1$s.',
				'The following plugins need to be updated to their latest version to ensure maximum compatibility with <em>MWP Application Framework</em>: %1$s.',
				basename( $base_dir )
			),
		),
	);
	
	/* Register dependencies */
	tgmpa( $dependencies, $config );
});	
