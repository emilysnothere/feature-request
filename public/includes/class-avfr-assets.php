<?php

/**
 * Asset loader class
 */
class Avfr_Assets {

	function __construct(){
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
	}

	function scripts(){

		global $wp_query, $post;

		$disable_css    = avfr_get_option('avfr_disable_css','avfr_settings_advanced');
	 	$max 			=  $wp_query->max_num_pages;
	 	$paged 			= ( get_query_var('paged') > 1 ) ? get_query_var('paged') : 1;

	    if ( is_post_type_archive( 'avfr' ) || 'avfr' == get_post_type() || has_shortcode( isset( $post->post_content ) ? $post->post_content : null, 'feature_request') ):

	    	if ( 'on' !== $disable_css ) {

	    		wp_enqueue_style('dashicons');
	    		wp_enqueue_style('avfr-style', AVFR_URL . '/public/assets/css/main.css', AVFR_VERSION, true);
			}

			wp_enqueue_script('avfr-script', AVFR_URL.'/public/assets/js/feature-request.js', array('jquery'), AVFR_VERSION, true);
			wp_localize_script('feature-request-script', 'feature_request', avfr_localized_args( $max , $paged) );

		endif;

	}
}
new Avfr_Assets;