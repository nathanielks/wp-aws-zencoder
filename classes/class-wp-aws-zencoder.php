<?php
use Aws\S3\S3Client;

class WP_AWS_Zencoder extends AWS_Plugin_Base {
	private $aws, $s3client;

	const SETTINGS_KEY = 'tantan_wordpress_s3';

	function __construct( $plugin_file_path, $aws ) {
		parent::__construct( $plugin_file_path );

		$this->aws = $aws;

		add_action( 'aws_admin_menu', array( $this, 'admin_menu' ) );

		$this->plugin_title = __( 'WP AWS Zencoder', 'waz' );
		$this->plugin_menu_title = __( 'Zencoder', 'waz' );

		add_filter( 'wp_generate_attachment_metadata', array( $this, 'wp_generate_attachment_metadata' ), 30, 2 );
	}

	function wp_generate_attachment_metadata( $data, $post_id ) {

		$type = get_post_mime_type( $post_id );

		if ( $this->is_video( $type ) ):
			$s3info = get_post_meta( $post_id, 'amazonS3_info', true );
			if( ! empty( $s3info ) ) {

			} // !empty
		} // !in_array

		return $data;
	}

	function is_video( $type ){
		return in_array( $type, $this->accepted_mime_types() );
	}

	function accepted_mime_types( $type ){
		return array(
			'video/x-ms-asf',
			'video/x-ms-wmv',
			'video/x-ms-wmx',
			'video/x-ms-wm',
			'video/avi',
			'video/divx',
			'video/x-flv',
			'video/quicktime',
			'video/mpeg',
			'video/mp4',
			'video/ogg',
			'video/webm',
			'video/x-matroska'
		);
	}
}
