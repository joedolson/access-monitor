<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * AJAX function to do an on-the-fly inspection. Trigger from post meta box. On the fly inspection only checks container if that option is set.
 */
 
/**
 * Insert 'test accessibility' button in Publish box.
 */
add_action( 'post_submitbox_misc_actions', 'am_inspect_post' );
function am_inspect_post() {
	echo '
		<div class="misc-pub-section misc-pub-section-last" style="border-top: 1px solid #eee;">
			<button class="inspect-a11y button"><span class="dashicons dashicons-universal-access" aria-hidden="true"></span> ' . __( 'Inspect Accessibility', 'access-monitor' ) . '</button>
		</div>';
}
 
/** 
 * JS that executes when 'Publish' pressed and only executes the command if Tenon results pass.
 */
add_action( 'admin_enqueue_scripts', 'am_pre_publish' );
function am_pre_publish( $hook ) { 
	global $post;
	$options = get_option( 'am_settings' );
	$check = isset( $options['tenon_pre_publish'] ) ? $options['tenon_pre_publish'] : 0;
	$args = isset( $options['am_criteria'] ) ? $options['am_criteria'] : array();
	if ( $check == 1 ) {
		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			if ( am_in_post_type( $post->ID ) ) {
				wp_enqueue_script( 'tenon.inspector', plugins_url( 'js/inspector.js', __FILE__ ), array( 'jquery' ), '', true );
				$settings = array(
					'level'          => ( isset( $args['level'] ) ) ? $args['level'] : 'AA',
					'certainty'      => ( isset( $args['certainty'] ) ) ? $args['certainty'] : '0',
					'priority'       => ( isset( $args['priority'] ) ) ? $args['priority'] : '0',
					'container'      => ( isset( $args['container'] ) ) ? $args['container'] : '.post-content',
					'store'          => ( isset( $args['store'] ) ) ? $args['store'] : '0',
					'grade'          => ( isset( $args['grade'] ) ) ? $args['grade'] : '90',
					'hide'           => __( 'Hide issues', 'access-monitor' ),
					'show'           => __( 'Show issues', 'access-monitor' ),
					'failed'         => __( 'Could not retrieve content from your content area. Set your content container in Access Monitor settings.', 'access-monitor' )
				);
				wp_localize_script( 'tenon.inspector', 'am', $settings );
			}
		}
	}
}
 
/**
 * Check whether a given post is in an allowed post type and has an update template configured.
 * 
 * @param integer $id Post ID.
 *
 * @return boolean True if post is allowed, false otherwise.
 */
function am_in_post_type( $id ) {
	$settings = get_option( 'am_settings' );
	$post_type_settings = $settings['am_post_types'];
	if ( is_array( $post_type_settings ) && !empty( $post_type_settings ) ) {
		$type               = get_post_type( $id );
		if ( in_array( $type, $post_type_settings ) ) {
			return true;
		}
	}
	
	return false;
}

/**
 * Add hidden notice to post edit page.
 */
add_action( 'edit_form_top', 'am_edit_form_after_title' );
function am_edit_form_after_title( $post ) {
	if ( am_in_post_type( $post->ID ) ) { 
		echo '
		<div class="am-errors"><p>' 
			. sprintf( __( 'Score: %s / <strong>Post may not be published</strong>: does not meet minimum accessibility requirements. %s', 'access-monitor' ), '<strong class="score"></strong>', '<a href="#am-errors" class="am-toggle" aria-expanded="false">Show Results</a>' ) 
			. "</p><div class='am-errors-display' id='am-errors'></div>
		</div>";
	}
}

/**
 * Generate percentage grade for page.
 * For now, use overall error density scoring. Later, add error-specific exits.
 */
function am_percentage( $results ) {
	$stats = $results->globalStats;	
	$max = $stats->allDensity + (3 * $stats->stdDev);
	$errors = $stats->errorDensity;
	$warnings = $stats->warningDensity;
	$score = $results->resultSummary->density->allDensity;
	
	$min = 0;
	
	if ( $score > $max ) {
		$return = 0;
	} else if ( $score <= $min ) {
		$return = 100;
	} else {
		$return = 100 - (( $score / $max ) * 100 );
	}
	
	return apply_filters( 'am_modify_grade', $return, $results );
	
}