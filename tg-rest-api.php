<?php
/*
Plugin Name: Tg-rest-api
Version: 0.2.1
Description: WP JSON REST API 拡張 ( appwoman.jp 用 )
Author: wokamoto
Author URI: https://www.digitalcube.jp/
Plugin URI: https://www.digitalcube.jp/
Text Domain: tg-rest-api
Domain Path: /languages
*/

require_once(dirname(__FILE__).'/includes/class-tg-rest-api.php');

$tg_rest_api = TG_REST_API::get_instance();

// switch theme ( PC or Mobile )
add_action( 'plugins_loaded', array($tg_rest_api, 'switch_theme') );

// regist routes
add_action( 'wp_json_server_before_serve', function() {
	$tg_rest_api = TG_REST_API::get_instance();
	add_filter( 'json_endpoints', array( $tg_rest_api, 'register_routes' ) );
});

// Nginx Caching
add_action( 'wp_json_server_before_serve', array( $tg_rest_api, 'nginx_cache_controle' ) );
add_filter( 'nginxchampuru_get_post_type', array( $tg_rest_api, 'nginxchampuru_get_post_type' ) );
add_filter( 'nginxchampuru_get_post_id',   array( $tg_rest_api, 'nginxchampuru_get_post_id' ) );

// json rest api bug fix
add_filter( 'json_prepare_term', array( $tg_rest_api, 'json_prepare_term'), 10, 2 );
add_action( 'plugins_loaded', function(){
    remove_action( 'deprecated_function_run', 'json_handle_deprecated_function', 10 );
    remove_action( 'deprecated_argument_run', 'json_handle_deprecated_argument', 10 );
});
