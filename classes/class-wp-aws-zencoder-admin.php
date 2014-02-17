<?php

use Aws\S3\S3Client;
class WP_AWS_Zencoder_Admin {

	add_action( 'aws_admin_menu', array( $this, 'admin_menu' ) );

	function __construct() {
	}

	function admin_menu( $aws ) {
		$hook_suffix = $aws->add_page( $this->plugin_title, $this->plugin_menu_title, 'manage_options', $this->plugin_slug, array( $this, 'render_page' ) );
		add_action( 'load-' . $hook_suffix , array( $this, 'plugin_load' ) );
	}

	function render_page() {
		$this->aws->render_view( 'header', array( 'page_title' => $this->plugin_title ) );

		$aws_client = $this->aws->get_client();

		if ( is_wp_error( $aws_client ) ) {
			$this->render_view( 'error', array( 'error' => $aws_client ) );
		}
		else {
			$this->render_view( 'settings' );
		}

		$this->aws->render_view( 'footer' );
	}

	function plugin_load(){
		// silence
	}

}
