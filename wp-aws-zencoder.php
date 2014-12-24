<?php
/*
Plugin Name: AWS Zencoder
Plugin URI: http://github.com/nathanielks/wp-aws-zencoder
Description: Automatically submits transcode jobs based on MIME type of uploaded media. Made for use with http://wordpress.org/plugins/amazon-s3-and-cloudfront/
Author: Nathaniel Schweinberg
Version: 0.1.0
Author URI: http://fightthecurrent.org

// Copyright (c) 2013 Nathaniel Schweinberg. All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if( ! defined( 'WAZ_PATH' ) )
	define( 'WAZ_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

$GLOBALS['aws_meta']['wp-aws-zencoder']['version'] = '0.1.0';

add_action( 'plugins_loaded', 'waz_check_required_plugins' );
function waz_check_required_plugins() {

    if ( !is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        return;
	}

    if ( class_exists( 'Amazon_Web_Services' ) ) {
		add_action( 'aws_init', 'waz_check_as3cf' );
        return;
	}
	waz_activate_aws();

}

function waz_activate_aws(){
	$url = 'http://wordpress.org/plugins/amazon-web-services/';
	$title = 'AWS&nbsp;Zencoder';
	$required = 'Amazon&nbsp;Web&nbsp;Services';
	$slug = 'amazon-web-services';
	$file = $slug . '/amazon-web-services.php';

	waz_activate_or_install_message( $title, $url, $required, $slug, $file );
}

function waz_check_as3cf() {
    if ( class_exists( 'Amazon_S3_And_CloudFront' ) ) {
        return;
	}
	waz_activate_as3cf();
}

function waz_activate_as3cf(){
	$url = 'http://wordpress.org/plugins/amazon-s3-and-cloudfront/';
	$title = 'AWS&nbsp;Zencoder';
	$required = 'Amazon&nbsp;S3&nbsp;and&nbsp;CloudFront';
	$slug = 'amazon-s3-and-cloudfront';
	$file = $slug . '/wordpress-s3.php';

	$msg = waz_activate_or_install_message( $title, $url, $required, $slug, $file );
	waz_plugin_die( $msg );
}

function waz_plugin_die( $msg ){
    require_once ABSPATH . '/wp-admin/includes/plugin.php';
    deactivate_plugins( __FILE__ );

	wp_die( $msg );
}

function waz_activate_or_install_message( $title, $url, $required, $slug, $file ){
	$msg = sprintf(
		__( '%s has been deactivated as it requires the <a href="%s">%s</a> plugin.', 'waz' ),
		$title,
		$url,
		$required
	) . '<br /><br />';

    if ( file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {
        $activate_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $file, 'activate-plugin_' .$file );
        $msg .= sprintf( __( 'It appears to already be installed. <a href="%s">Click here to activate it.</a>', 'waz' ), $activate_url );
    }
    else {
        $install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ), 'install-plugin_' . $slug );
        $msg .= sprintf( __( '<a href="%s">Click here to install it automatically.</a> Then activate it. ', 'waz' ), $install_url );
	}
	$msg .= '<br /><br />';
	$msg .=	sprintf( __( 'Once it has been activated, you can activate %s.', 'waz' ), $title);
	return $msg;
}

function waz_init( $aws ) {
	global $waz;
    require_once 'classes/class-wp-aws-zencoder.php';
    $waz = new WP_AWS_Zencoder( __FILE__, $aws );
}

add_action( 'aws_init', 'waz_init' );
