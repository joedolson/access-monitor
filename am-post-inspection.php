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
	$post_ID = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : false;
		if ( $post_ID && am_in_post_type( $post_ID ) ) {
		$control = '';
		if ( current_user_can( 'manage_options' ) ) {
			$control = "<p><input type='checkbox' id='am_override' value='1' name='am_override' /> <label for='am_override'>" . __( 'Override Accessibility Test Results', 'access-monitor' ) . "</label></p>";
		} else {
			$settings = get_option( 'am_settings' );
			$notify = ( isset( $settings['am_notify'] ) && is_email( $settings['am_notify'] ) );
			if ( $notify ) {
				$control = ' ' . "<p><button type='button' class='button' id='am_notify'>" . __( 'Request A11y Review', 'access-monitor' ) . "</button></p><div id='am_notified' aria-live='assertive'></div>";
			}
		}
		echo '
			<div class="misc-pub-section misc-pub-section-last" style="border-top: 1px solid #eee;">
				<button type="button" class="inspect-a11y button"><span class="dashicons dashicons-universal-access" aria-hidden="true"></span> ' . __( 'Check Accessibility', 'access-monitor' ) . '</button>' . 
				$control . '
			</div>';
	}
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
					'container'      => ( isset( $args['container'] ) ) ? $args['container'] : '.entry-content',
					'store'          => ( isset( $args['store'] ) ) ? $args['store'] : '0',
					'grade'          => ( isset( $args['grade'] ) ) ? $args['grade'] : '90',
					'hide'           => __( 'Hide issues', 'access-monitor' ),
					'show'           => __( 'Show issues', 'access-monitor' ),
					'failed'         => __( 'Could not retrieve content from your content area. Set your content container in Access Monitor settings.', 'access-monitor' )
				);
				wp_localize_script( 'tenon.inspector', 'am', $settings );
				wp_localize_script( 'tenon.inspector', 'am_ajax_notify', 'am_ajax_notify' );
				
				$user_ID = get_current_user_ID();
				$post_ID = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : false;
				$security = wp_create_nonce( 'am_notify_admin' );

				$notify = array( 
					'user'     => $user_ID,
					'post_ID'  => $post_ID,
					'security' => $security,
					'error'    => __( "Accessibility Review Request failed to send.", 'access-monitor' )
				);
				wp_localize_script( 'tenon.inspector', 'amn', $notify );				
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
	$status = $results->status;
	if ( $status == 200 ) {
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
	} else {
		$return = false;
	}
	return apply_filters( 'am_modify_grade', $return, $results );
}

add_action('wp_ajax_am_ajax_notify', 'am_ajax_notify');
function am_ajax_notify() {
	if ( isset( $_REQUEST['security'] ) ) {
		if ( ! check_ajax_referer( 'am_notify_admin', 'security', false ) ) {
			wp_send_json( array( 'response' => 0, 'message' => __( 'Invalid Security Check', 'access-monitor' ) ) );
		} else {
			$settings = get_option( 'am_settings' );
			$notify = ( isset( $settings['am_notify'] ) && is_email( $settings['am_notify'] ) );
			if ( $notify ) {
				$email = $settings['am_notify'];
				$post_ID = $_REQUEST['post_ID'];
				$user_ID = $_REQUEST['user'];
				$post = get_post( $post_ID );
				$user = get_user_by( 'id', $user_ID );
				$edit_link = get_edit_post_link( $post_ID );
				
				$body = sprintf( __( 'Accessibility review on "%s" requested by %s. Review post: %s', 'access-monitor' ), $post->post_title, $user->user_login, $edit_link );
				
				$notice = wp_mail( $email, __( 'Request for Accessibility Review', 'access-monitor' ), $body );
				wp_send_json(
					array( 
						'response' => 1, 
						'message' => __( 'Review request sent!', 'access-monitor' ) 
					) 
				);
			} else {
				wp_send_json( array( 'response' => 0, 'message' => __( 'Invalid Email provided for accessibility reviewer', 'access-monitor' ) ) );
			}
		}
	}
}

