<?php
/**
 * On the fly inspections of post content checked before publication.
 *
 * @category Publish
 * @package  Access Monitor
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/access-monitor/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'post_submitbox_misc_actions', 'am_inspect_post' );
/**
 * Insert 'test accessibility' button in Publish box.
 */
function am_inspect_post() {
	global $post;
	$status = 'new';
	if ( isset( $_GET['post'] ) ) {
		$status = 'edit';
	}
	if ( $post->ID && am_in_post_type( $post->ID ) ) {
		$control = '';
		if ( current_user_can( 'manage_options' ) ) {
			$control = "<p><input type='checkbox' id='am_override' value='1' name='am_override' /> <label for='am_override'>" . __( 'Override Accessibility Test Results', 'access-monitor' ) . '</label></p>';
		} else {
			$settings = get_option( 'am_settings' );
			$notify   = ( isset( $settings['am_notify'] ) && is_email( $settings['am_notify'] ) );
			if ( $notify ) {
				$control = ' ' . "<p><button type='button' class='button' id='am_notify'>" . __( 'Request A11y Review', 'access-monitor' ) . "</button></p><div id='am_notified' aria-live='assertive'></div>";
			}
		}
		echo '
			<div class="misc-pub-section misc-pub-section-last" style="border-top: 1px solid #eee;">
				<input type="hidden" value="' . $status . '" name="am-status" id="am_status" />
				<button type="button" class="inspect-a11y button"><span class="dashicons dashicons-universal-access" aria-hidden="true"></span> ' . __( 'Check Accessibility', 'access-monitor' ) . '</button>' .
				$control . '
				<input type="submit" name="publish" id="ampublish" class="screen-reader-text" value="Publish" />
			</div>';
	}
}

add_action( 'admin_enqueue_scripts', 'am_pre_publish' );
/**
 * Enqueue JS that executes when 'Publish' pressed and executes the command if Tenon results pass.
 *
 * @param string $hook current screen.
 */
function am_pre_publish( $hook ) {
	global $post;
	$options = get_option( 'am_settings' );
	$check   = isset( $options['tenon_pre_publish'] ) ? $options['tenon_pre_publish'] : 0;
	$args    = isset( $options['am_criteria'] ) ? $options['am_criteria'] : array();
	if ( 1 === absint( $check ) ) {
		if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
			if ( am_in_post_type( $post->ID ) ) {
				$user_ID  = get_current_user_ID();
				$post_ID  = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : false;
				$security = wp_create_nonce( 'am_notify_admin' );
				wp_enqueue_script( 'tenon.inspector', plugins_url( 'js/inspector.js', __FILE__ ), array( 'jquery' ), '', true );
				$settings = array(
					'level'       => ( isset( $args['level'] ) ) ? $args['level'] : 'AA',
					'certainty'   => ( isset( $args['certainty'] ) ) ? $args['certainty'] : '60',
					'priority'    => ( isset( $args['priority'] ) ) ? $args['priority'] : '20',
					'container'   => ( isset( $args['container'] ) && ! empty( $args['container'] ) ) ? $args['container'] : '.access-monitor-content',
					'store'       => ( isset( $args['store'] ) ) ? $args['store'] : '0',
					'hide'        => __( 'Hide issues', 'access-monitor' ),
					'show'        => __( 'Show issues', 'access-monitor' ),
					'failed'      => __( 'Could not retrieve content from your content area. Set your content container in Access Monitor settings.', 'access-monitor' ),
					'error'       => __( '<strong>Post may not be published</strong>: does not meet minimum accessibility requirements.', 'access-monitor' ),
					'pass'        => __( '<strong>Post may be published!</strong> This post meets your accessibility requirements.', 'access-monitor' ),
					'fail'        => __( '<strong>Post may not be published.</strong> This post does not meet your accessibility requirements.', 'access-monitor' ),
					'ajaxerror'   => __( '<strong>There was an error sending your post to Tenon.io</strong>.', 'access-monitor' ),
					'ajax_action' => 'am_ajax_notify',
					'ajax_query'  => 'am_ajax_query_tenon',
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'user'        => $user_ID,
					'post_ID'     => $post_ID,
					'security'    => $security,
					'send_error'  => __( 'Accessibility Review Request failed to send.', 'access-monitor' ),
				);
				wp_localize_script(
					'tenon.inspector',
					'amp',
					$settings
				);
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
	$settings           = get_option( 'am_settings' );
	$post_type_settings = isset( $settings['am_post_types'] ) ? $settings['am_post_types'] : array();
	if ( is_array( $post_type_settings ) && ! empty( $post_type_settings ) ) {
		$type = get_post_type( $id );
		if ( in_array( $type, $post_type_settings, true ) ) {
			return true;
		}
	}

	return false;
}

add_action( 'edit_form_top', 'am_edit_form_after_title' );
/**
 * Add hidden notice to post edit page.
 *
 * @param object $post Post object.
 */
function am_edit_form_after_title( $post ) {
	if ( am_in_post_type( $post->ID ) ) {
		$options  = get_option( 'am_settings' );
		$criteria = $options['am_criteria'];
		$errors   = ( isset( $criteria['maxerrors'] ) ? $criteria['maxerrors'] : 3 );
		$warnings = ( isset( $criteria['maxwarnings'] ) ? $criteria['maxwarnings'] : 5 );
		$levela   = ( isset( $criteria['maxa'] ) ? $criteria['maxa'] : 1 );
		$levelaa  = ( isset( $criteria['maxaa'] ) ? $criteria['maxaa'] : 3 );
		$levelaaa = ( isset( $criteria['maxaaa'] ) ? $criteria['maxaaa'] : 5 );
		// Translators: number of permitted errors.
		$errors = sprintf( __( '<strong>Errors:</strong> <span class="errors"></span> found; %s allowed.', 'access-monitor' ), $errors );
		// Translators: number of permitted warnings.
		$warnings = sprintf( __( '<strong>Warnings:</strong> <span class="warnings"></span> found; %s allowed.', 'access-monitor' ), $warnings );
		// Translators: number of permitted Level A warnings/errors.
		$levela = sprintf( __( '<strong>Level A:</strong> <span class="levela"></span> found; %s allowed.', 'access-monitor' ), $levela );
		// Translators: number of permitted Level AA warnings/errors.
		$levelaa = sprintf( __( '<strong>Level AA:</strong> <span class="levelaa"></span> found; %s allowed.', 'access-monitor' ), $levelaa );
		// Translators: number of permitted Level AAA warnings/errors.
		$levelaaa = sprintf( __( '<strong>Level AAA:</strong> <span class="levelaaa"></span> found; %s allowed.', 'access-monitor' ), $levelaaa );
		// Translators: Score container, message container, toggle to show results.
		echo '<div class="am-errors"><p>' . sprintf( __( '%1$s %2$s', 'access-monitor' ), '<span class="am-message"></span>', '<a href="#am-errors" class="am-toggle" aria-expanded="false">Show Results</a>' )
			. "</p><ul><li>$errors</li><li>$warnings</li><li>$levela</li><li>$levelaa</li><li>$levelaaa</li></ul><div class='am-errors-display' id='am-errors'></div>
		</div>";
	}
}

/**
 * Generate percentage grade for page. For now, use overall error density scoring. Later, add error-specific exits.
 *
 * @param object $results Tenon results object.
 *
 * @return mixed float/boolean Percentage or false.
 */
function am_percentage( $results ) {
	$status  = $results->status;
	$results = (array) $results;
	if ( 200 === absint( $status ) ) {
		$stats = (array) $results['globalStats'];
		$max   = $stats['allDensity'] + ( 3 * $stats['stdDev'] );

		// test against all errors & warnings.
		$score = (array) $results['resultSummary']->density;
		$score = $score['allDensity'];
		$min   = 0;

		if ( $score > $max ) {
			$return = 0;
		} elseif ( $score <= $min ) {
			$return = 100;
		} else {
			$return = 100 - ( ( $score / $max ) * 100 );
		}
	} else {
		$return = false;
	}

	return apply_filters( 'am_modify_grade', $return, $results );
}

/**
 * Return Tenon.io error counts.
 *
 * @param object $results Tenon results object.
 *
 * @return mixed float/boolean Percentage or false.
 */
function am_score( $results ) {
	$status  = $results->status;
	$results = (array) $results;
	if ( 200 === absint( $status ) ) {

		// test against all errors & warnings.
		$issues   = (array) $results['resultSummary']->issues;
		$errors   = $issues['totalErrors'];
		$warnings = $issues['totalWarnings'];

		$bylevel  = (array) $results['resultSummary']->issuesByLevel;
		$levela   = $bylevel['A']->count;
		$levelaa  = $bylevel['AA']->count;
		$levelaaa = $bylevel['AAA']->count;

		$return = array(
			'errors'   => $errors,
			'warnings' => $warnings,
			'levela'   => $levela,
			'levelaa'  => $levelaa,
			'levelaaa' => $levelaaa,
		);

	} else {
		$return = false;
	}

	return apply_filters( 'am_tenon_score', $return, $results );
}

/**
 * Parse grade information into pass/fail.
 *
 * @param array $grade Array of error information.
 *
 * @return bool
 */
function am_parse_grade( $grade ) {
	$options  = get_option( 'am_settings' );
	$criteria = $options['am_criteria'];
	$errors   = ( isset( $criteria['maxerrors'] ) ? $criteria['maxerrors'] : 3 );
	$warnings = ( isset( $criteria['maxwarnings'] ) ? $criteria['maxwarnings'] : 5 );
	$levela   = ( isset( $criteria['maxa'] ) ? $criteria['maxa'] : 1 );
	$levelaa  = ( isset( $criteria['maxaa'] ) ? $criteria['maxaa'] : 3 );
	$levelaaa = ( isset( $criteria['maxaaa'] ) ? $criteria['maxaaa'] : 5 );
	$pass     = true;
	// If any of these criteria are failed, fail the document.
	if ( $grade['errors'] >= $errors ) {
		$pass = false;
	}
	if ( $grade['warnings'] >= $errors ) {
		$pass = false;
	}
	if ( $grade['levela'] >= $levela ) {
		$pass = false;
	}
	if ( $grade['levelaa'] >= $levelaa ) {
		$pass = false;
	}
	if ( $grade['levelaaa'] >= $levelaaa ) {
		$pass = false;
	}

	return $pass;
}

add_action( 'wp_ajax_am_ajax_notify', 'am_ajax_notify' );
/**
 * Send notification to a11y approver email that a post needs manual review.
 */
function am_ajax_notify() {
	if ( isset( $_REQUEST['security'] ) ) {
		if ( ! check_ajax_referer( 'am_notify_admin', 'security', false ) ) {
			wp_send_json(
				array(
					'response' => 0,
					'message'  => __( 'Invalid Security Check', 'access-monitor' ),
				)
			);
		} else {
			$settings = get_option( 'am_settings' );
			$notify   = ( isset( $settings['am_notify'] ) && is_email( $settings['am_notify'] ) );
			if ( $notify ) {
				$email     = $settings['am_notify'];
				$post_ID   = $_REQUEST['post_ID'];
				$user_ID   = $_REQUEST['user'];
				$post      = get_post( $post_ID );
				$user      = get_user_by( 'id', $user_ID );
				$edit_link = get_edit_post_link( $post_ID );

				// Translators: Post title, requesting user, edit link.
				$body = sprintf( __( 'Accessibility review on "%1$s" requested by %2$s. Review post: %3$s', 'access-monitor' ), $post->post_title, $user->user_login, $edit_link );

				$notice = wp_mail( $email, __( 'Request for Accessibility Review', 'access-monitor' ), $body );
				wp_send_json(
					array(
						'response' => 1,
						'message'  => __( 'Review request sent!', 'access-monitor' ),
					)
				);
			} else {
				wp_send_json(
					array(
						'response' => 0,
						'message'  => __( 'Invalid Email provided for accessibility reviewer', 'access-monitor' ),
					)
				);
			}
		}
	}
}

add_filter( 'the_content', 'am_testable_post_content' );
/**
 * If custom value is not set for Access Monitor content container, add independent container and test that.
 *
 * @param  string $content Post content to test.
 *
 * @return content
 */
function am_testable_post_content( $content ) {
	global $post;
	if ( is_object( $post ) ) {
		if ( am_in_post_type( $post->ID ) ) {
			$options   = get_option( 'am_settings' );
			$args      = isset( $options['am_criteria'] ) ? $options['am_criteria'] : array();
			$test      = ( isset( $options['tenon_pre_publish'] ) && 1 === absint( $options['tenon_pre_publish'] ) ) ? true : false;
			$container = isset( $args['container'] ) ? $args['container'] : false;
			if ( $test ) {
				if ( ! $container || '.access-monitor-content' === $container ) {
					$content = '<div class="access-monitor-content">' . $content . '</div>';
				}
			}
		}
	}

	return $content;
}

