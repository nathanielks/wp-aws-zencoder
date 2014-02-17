<?php
use Aws\S3\S3Client;

class WP_AWS_Zencoder extends AWS_Plugin_Base {
	private $aws, $s3client;

	const SETTINGS_KEY = 'tantan_wordpress_s3';

	function __construct( $plugin_file_path, $aws ) {

		$this->plugin_title = __( 'WP AWS Zencoder', 'waz' );
		$this->plugin_menu_title = __( 'Zencoder', 'waz' );

		// lets do this before anything else gets loaded
		$this->requires();

		parent::__construct( $plugin_file_path );

		$this->aws = $aws;

		add_action( 'aws_admin_menu', array( $this, 'admin_menu' ) );

		add_filter( 'wp_generate_attachment_metadata', array( $this, 'wp_generate_attachment_metadata' ), 30, 2 );
	}

	function requires(){
		if( !class_exists( 'Services_Zencoder') ){
			$file = WAZ_PATH . '/vendor/autoload.php';
			if( file_exists( $file ) ){
				require_once( $file );
			} else {
				// Need to figure out a good way to alert people the required
				// library doesn't exist. Maybe refer them to a website? Or the
				// github readme?
				$msg = __( 'Oh no! It would appear the required Zencoder library doesn\'t exist.', 'waz' );
				$msg .= '<br /><br />';
				$msg .= sprintf(
					__( '%s has been deactivated until the issue has been resolved. ', 'waz' ),
					$this->plugin_title
				);
				$msg .= sprintf(
					__( 'Please refer to the <a href="%s">documentation</a> for more information.', 'waz' ),
					'https://github.com/nathanielks/wp-aws-zencoder'
				);
				$msg .= '<br /><br />';
				$msg .= sprintf(
					__( '<a href="%s">Return to the previous page.</a>', 'waz' ),
					esc_url( $_SERVER['HTTP_REFERER'] )
				);
				waz_plugin_die( $msg );
			}
		}
	}

	function wp_generate_attachment_metadata( $data, $post_id ) {

		$type = get_post_mime_type( $post_id );

		if ( $this->is_video( $type ) ) {
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
