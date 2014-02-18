<?php
use Aws\S3\S3Client;

class WP_AWS_Zencoder extends AWS_Plugin_Base {
	private $aws, $s3client;

	const SETTINGS_KEY = 'tantan_wordpress_s3';

	function __construct( $plugin_file_path, $aws ) {

		$this->plugin_title = __( 'WP AWS Zencoder', 'waz' );
		$this->plugin_menu_title = __( 'Zencoder', 'waz' );

		// lets do this before anything else gets loaded
		$this->require_zencoder();

		parent::__construct( $plugin_file_path );

		$this->aws = $aws;
		$this->zen = new Services_Zencoder( $this->get_api_key() );

		// Admin
		add_action( 'aws_admin_menu', array( $this, 'admin_menu' ) );

		// On metadata generation, let's create the Zencoder Job
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'wp_generate_attachment_metadata' ), 30, 2 );

		// Rewrites
		add_action( 'wp_loaded', array( $this, 'flush_rules' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );

		// Catch notifications from zencoder
		add_action( 'pre_get_posts', array( $this, 'zencoder_notification' ) );

	}

	function require_zencoder(){
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

	/*
	 *Admin
	 */

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
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$src = plugins_url( 'assets/js/script' . $suffix . '.js', $this->plugin_file_path );
		wp_enqueue_script( 'waz-script', $src, array( 'jquery' ), $this->get_installed_version(), true );

		$this->handle_post_request();
	}

	function are_key_constants_set(){
		return defined( 'AWS_ZENCODER_API_KEY' );
	}

	function get_api_key() {
		if ( $this->are_key_constants_set() ) {
			return AWS_ZENCODER_API_KEY;
		}

		return $this->get_setting( 'api_key' );
	}

	function handle_post_request() {
		if ( empty( $_POST['action'] ) || 'save' != $_POST['action'] ) {
			return;
		}

		if ( empty( $_POST['_wpnonce'] ) || !wp_verify_nonce( $_POST['_wpnonce'], 'waz-save-settings' ) ) {
			die( __( "Cheatin' eh?", 'waz' ) );
		}

		// Make sure $this->settings has been loaded
		$this->get_settings();

		$post_vars = array( 'api_key' );
		foreach ( $post_vars as $var ) {
			if ( !isset( $_POST[$var] ) ) {
				continue;
			}

			$this->set_setting( $var, $_POST[$var] );
		}

		$this->save_settings();
	}

	/*
	 *Logic
	 */

	function wp_generate_attachment_metadata( $data, $post_id ) {

		$type = get_post_mime_type( $post_id );

		if ( $this->is_video( $type ) ) {
			$s3info = get_post_meta( $post_id, 'amazonS3_info', true );
			if( ! empty( $s3info ) ) {
				update_post_meta( $post_id, 'waz_encode_status', 'pending' );
				$encoding_job = null;
				try {

					$input = "s3://{$s3info['bucket']}/{$s3info['key']}";
					$pathinfo = pathinfo( $input );
					$key = trailingslashit( dirname( $input ) );

					// New Encoding Job
					$job = $this->zen->jobs->create(
						array(
							"input" => $input,
							"outputs" => array(
								array(
									"label" => "web",
									"url" => $key . $pathinfo['filename'] . '.mp4',
									"notifications" => array(
										array(
											"url" => get_home_url( get_current_blog_id(), '/waz_zencoder_notification/' )
										)
									)
								)
							)
						)
					);
					update_post_meta( $post_id, 'waz_encode_status', 'submitting' );

				} catch (Services_Zencoder_Exception $e) {
					update_post_meta( $post_id, 'waz_encode_status', 'failed' );
				}

				update_post_meta( $post_id, 'waz_encode_status', 'transcoding' );
				update_post_meta( $post_id, 'waz_job_id', $job->id );
				update_post_meta( $post_id, 'waz_outputs', (array)$job->outputs );
			} // !empty
		} // !in_array

		return $data;
	}

	function is_video( $type ){
		return in_array( $type, $this->accepted_mime_types() );
	}

	function accepted_mime_types(){
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

	function flush_rules() {
		$rules = get_option( 'rewrite_rules' );

		if ( ! isset( $rules['waz_zencoder_notification?$'] ) ) {
			waz_network_flush_rewrite_rules();
		}
	}

	function network_flush_rules(){
		global $wp_rewrite;
		//If multisite, we loop through all sites
		if (is_multisite()) {
			$sites = wp_get_sites();
			foreach ($sites as $site) {
				switch_to_blog($site['blog_id']);
				//Rebuild rewrite rules for this site
				$wp_rewrite->init();
				//Flush them
				$wp_rewrite->flush_rules();
				restore_current_blog();
			}
			$wp_rewrite->init();
		} else {
			//Flush rewrite rules
			$wp_rewrite->flush_rules();
		}
	}

	function rewrite_rules( $rules ) {
		$newrules = array();
		$newrules['waz_zencoder_notification?$'] = 'index.php?waz_zencoder_notification=true';
		return $newrules + $rules;
	}

	function query_vars( $vars ) {
		array_push( $vars, 'waz_zencoder_notification' );
		return $vars;
	}

	function zencoder_notification(){
		if( true == get_query_var('waz_zencoder_notification') ){
			try{
				$notification = $this->zen->notifications->parseIncoming();
				$this->process_notification( $notification );
			} catch( Services_Zencoder_Exception $e ){
				die( $e->getMessage() );
			}
			die(0);
		}
	}

	function process_notification( $notification ){

		$post_id = $this->get_post_id_from_job_id( $notification->job->id );

		// If you're encoding to multiple outputs and only care when all of the outputs are finished
		// you can check if the entire job is finished.
		if($notification->job->state == "finished") {

			$output = $notification->job->outputs['web'];

			// Get the Attachment
			$meta = $_meta = wp_get_attachment_metadata( $post_id );

			require_once( ABSPATH . '/wp-includes/ID3/getid3.lib.php' );
			require_once( ABSPATH . '/wp-includes/ID3/getid3.php' );
			require_once( ABSPATH . '/wp-includes/ID3/module.audio-video.quicktime.php' );

			// Let's start modifying the metadata
			// TODO figure out a viable way to use built in WP ID3
			$meta['filesize'] = $output->file_size_in_bytes;
			$meta['mime_type'] = 'video/mp4';
			$meta['length'] = ceil( $output->duration_in_ms * 0.001 );
			$meta['length_formatted'] = getid3_lib::PlaytimeString( $meta['length'] );
			$meta['width'] = $output->width;
			$meta['height'] = $output->height;
			$meta['fileformat'] = 'mp4';
			$meta['dataformat'] = $output->format;
			$meta['codec'] = $output->video_codec;

			// TODO this needs to take into consideration other file formats
			// other than quicktime
			$id3 = new getID3();
			$qt = new getid3_quicktime( $id3 );
			$meta['audio'] = array(
				'dataformat' => $output->format,
				'codec' => $qt->QuicktimeAudioCodecLookup( $output->audio_codec ),
				'sample_rate' => $output->audio_sample_rate,
				'channels' => $output->channels,
				//'bits_per_sample' => 16,
				'lossless' => false,
				'channelmode' => 'stereo',
			);


			// Let's update the S3 information
			$s3info = $_s3info = get_post_meta( $post_id, 'amazonS3_info', true );
			$parsed = parse_url( $output->url );
			$key = ltrim ($parsed['path'],'/');
			$s3info['key'] = $key;
			update_post_meta( $post_id, 'amazonS3_info', $s3info );

			// And we're done!
			update_post_meta( $post_id, 'waz_encode_status', 'finished' );

		} elseif ($notification->job->outputs[0]->state == "cancelled") {
			update_post_meta( $post_id, 'waz_encode_status', 'cancelled' );
		} else {
			update_post_meta( $post_id, 'waz_encode_status', 'failed' );
		}

	}

	function get_post_id_from_job_id( $job_id ){
		global $wpdb;
		$results = $wpdb->get_results( "select post_id from $wpdb->postmeta where meta_value = $job_id" );
		if( !empty( $results ) ){
			return (int)$results[0]->post_id;
		}
		return 0;
	}

}
