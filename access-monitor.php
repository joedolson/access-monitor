<?php
/*
Plugin Name: Access Monitor
Plugin URI: http://www.joedolson.com/access-monitor/
Description: Inspect & monitor WordPress sites for accessibility issues using the Tenon accessibility API.
Author: Joseph C Dolson
Author URI: http://www.joedolson.com
Text Domain: access-monitor
Domain Path: lang
Version: 1.0.4
*/
/*  Copyright 2014-2015  Joe Dolson (email : joe@joedolson.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//define('TENON_API_URL', 'https://www.tenon.io/api/');
define( 'TENON_API_URL', 'https://tenon.io/api/' );
define( 'AM_DEBUG', false );

define( 'WAVE_API_URL', 'http://wave.webaim.org/api/request' );

require_once( 't/tenon.php' );
require_once( 't/wave.php' );
require_once( 'am-post-inspection.php' );

$am_version = '1.0.4';

add_filter( 'the_content', 'am_pass_query' );
function am_pass_query( $content ) {
	if ( isset( $_GET['tenon'] ) && $_GET['tenon'] == 'true' ) {
		$permalink = get_the_permalink();
		$results = am_query_tenon( array( 'url'=>$permalink ) );
		$results = $results['formatted'];
		return $results.$content;
	}
	return $content;
}

function am_query_tenon( $post ) {
	// creates the $opts array from the $post data
	// only sets items that are non-blank. This allows Tenon to revert to defaults
	$expectedPost = array( 'src', 'url', 'level', 'certainty', 'priority',
		'docID', 'projectID', 'viewPortHeight', 'viewPortWidth',
		'uaString', 'importance', 'ref', 'importance', 'fragment', 'store' );
		
	foreach ( $post AS $k => $v ) {
		if ( in_array($k, $expectedPost ) ) {
			if ( strlen( trim( $v ) ) > 0 ) {
				$opts[$k] = $v;
			}
		}
	}
		
	$settings = get_option( 'am_settings' );
	$key = $settings['tenon_api_key'];
	/* Tenon uses the application ID to credit specific application authors with API usage. Please leave this intact! Thanks! */
	$appID = '8cfaff90a1bd9eaa59c8bbc2e75f8657';
	if ( $key ) {
		$opts['key'] = $key;
		$opts['appID'] = $appID;
		$tenon = new tenon( TENON_API_URL, $opts );
		$tenon->submit( AM_DEBUG );
		$body = $tenon->tenonResponse['body'];
		$formatted = am_format_tenon( $body );
		$object = json_decode( $body );
		if ( property_exists( $object, 'resultSet' ) ) {
			$results = $object->resultSet;
		} else {
			$results = array();
		}
		$grade = am_percentage( $object );
		return array( 'formatted'=> $formatted, 'results' => $results, 'grade' => $grade );
	} else {
		return false;
	}
}

function am_format_tenon( $body ) {
	if ( $body === false ) {
		return __( 'No Tenon API Key provided', 'access-monitor' );
	}
	$object = json_decode( $body );
	if ( property_exists( $object, 'resultSummary' ) ) {
		// unchecked object references
		$errors = $object->resultSummary->issues->totalIssues;
	} else {
		$errors = 0;
	}
	
	if ( property_exists( $object, 'resultSet' ) ) {
		$results = $object->resultSet;
	} else {
		$results = array();
	}
	$return = am_format_tenon_array( $results, $errors );
	
	return $return;
}

function am_format_tenon_array( $results, $errors ) {
	$return = "<section><h2>".sprintf( __( '%s accessibility issues identified.', 'access-monitor' ), "<em>$errors</em>" )."</h2>";
	$i = 0;
	if ( !empty( $results ) ) {
		foreach( $results as $result ) {
			$i++;
			switch( $result->certainty ) {
				case ( $result->certainty >= 80 ) : $cert = 'high'; break;
				case ( $result->certainty >= 40 ) : $cert = 'medium'; break;
				default: $cert = 'low';
			}
			switch( $result->priority ) {
				case ( $result->priority >= 80 ) : $prio = 'high'; break;
				case ( $result->priority >= 40 ) : $prio = 'medium'; break;
				default: $prio = 'low';
			}
			$bpID = $result->bpID;
			$tID = $result->tID;
			$href = esc_url( add_query_arg( array( 'bpID'=>$bpID, 'tID'=>$tID ), 'http://tenon.io/bestpractice.php' ) );
			$ref = "<strong>" . __( 'Read more:', 'access-monitor' ) . "</strong> <a href='$href'>$result->resultTitle</a>";
			$return .= "
				<div class='tenon-result'>
					<h3>
						<span>$i</span>. $result->errorTitle 
					</h3>
					<p class='meta'>
						<span class='certainty $cert'>". sprintf( __( 'Certainty: %s', 'access-monitor' ), "$result->certainty%" ). "</span>  
						<span class='priority $prio'>". sprintf( __( 'Priority: %s', 'access-monitor' ), "$result->priority%" ). "</span>
					</p>
					<p>$result->errorDescription $ref</p>
					<h4>Error Source</h4>
					<pre lang='html'>".$result->errorSnippet."</pre>
					<h4>Xpath:</h4> <pre><code>$result->xpath</code></pre>
	
				</div>";
		}
	} else {
		$return .= "<p><strong>Congratulations!</strong> Tenon didn't find any issues on this page.</p>";
	}
	return $return . "</section><hr />";
}

add_action('admin_enqueue_scripts', 'am_admin_enqueue_scripts');
function am_admin_enqueue_scripts() {
	global $current_screen;
	if ( $current_screen->id == 'customize' || $current_screen->id == 'press-this' ) {
		// We don't want any of this on these screens.
	} else {
		// The customizer doesn't have an adminbar; so no reason to enqueue this. Also, it breaks the customizer. :)
		wp_enqueue_script( 'am.functions', plugins_url( 'js/jquery.ajax.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script( 'am.functions', 'am_ajax_url', admin_url( 'admin-ajax.php' ) );
		wp_localize_script( 'am.functions', 'am_ajax_action', 'am_ajax_query_tenon' );
		wp_localize_script( 'am.functions', 'am_current_screen', $current_screen->id );
		wp_enqueue_style( 'am.styles', plugins_url( 'css/am-styles.css', __FILE__ ) );	
	}
}

add_action('wp_enqueue_scripts', 'am_wp_enqueue_scripts');
function am_wp_enqueue_scripts() {
	wp_enqueue_style( 'am.styles', plugins_url( 'css/am-styles.css', __FILE__ ) );
}

add_action('wp_ajax_am_ajax_query_tenon', 'am_ajax_query_tenon');
add_action('wp_ajax_nopriv_am_ajax_query_tenon', 'am_ajax_query_tenon');

function am_ajax_query_tenon() {
	if ( isset( $_REQUEST['tenon'] ) ) {
		$screen = $_REQUEST['current_screen'];
		$args = array();
		
		if ( $screen == 'dashboard' ) {
			$args = array( 'src'=>stripslashes( $_REQUEST['tenon'] ) );
		} else {
			$args = array( 'src'=>stripslashes( $_REQUEST['tenon'] ), 'fragment' => 1 );
		}
		
		if ( isset( $_REQUEST['level'] ) ) {
			$args['level'] = $_REQUEST['level'];
		}

		if ( isset( $_REQUEST['fragment'] ) ) {
			$args['fragment'] = $_REQUEST['fragment'];
		}	

		if ( isset( $_REQUEST['certainty'] ) ) {
			$args['certainty'] = $_REQUEST['certainty'];
		}
		
		if ( isset( $_REQUEST['priority'] ) ) {
			$args['priority'] = $_REQUEST['priority'];
		}
	
		$results = am_query_tenon( $args );		
		
		wp_send_json(
			array( 
				'response'=>1, 
				'results'=>$results['results'], 
				'formatted' => $results['formatted'], 
				'grade' => $results['grade'] 
			) 
		);
	}
}

add_action( 'admin_footer', 'am_admin_footer' );
function am_admin_footer() {
	echo "<div aria-live='assertive' class='feedback' id='tenon' style='color: #333;background:#fff;padding: 2em 2em 4em 14em;clear:both;border-top:1px solid #333'></div>";
}

add_action( 'admin_bar_menu','am_admin_bar', 200 );
function am_admin_bar() {
	global $wp_admin_bar;
	if ( is_admin() ) {
		$url = '#tenon';
	} else {
		global $post_id; 
		$url = add_query_arg(  'tenon', 'true', get_permalink( $post_id ) );
	}
	$args = array( 'id'=>'tenonCheck', 'title'=>__( 'A11y Check','access-monitor' ), 'href'=>$url );
	$wp_admin_bar->add_node( $args );
}


add_filter( 'the_content', 'am_wave_pass_query' );
function am_wave_pass_query( $content ) {
	if ( isset( $_GET['wave'] ) && is_numeric( $_GET['wave'] ) ) {
		$permalink = get_the_permalink();
		$reporttype = (int) $_GET['wave'];
		$results = am_query_wave( array( 'url'=>$permalink, 'reporttype'=>$reporttype ) );
		return $results.$content;
	}
	return $content;
}

function am_query_wave( $post ) {
	// creates the $opts array from the $post data
	// only sets items that are non-blank. This allows Tenon to revert to defaults
	$expectedPost = array( 'key', 'url', 'reporttype', 'format' );

	foreach ( $post AS $k => $v ) {
		if ( in_array($k, $expectedPost ) ) {
			if ( strlen( trim( $v ) ) > 0 ) {
				$opts[$k] = $v;
			}
		}
	}
	$settings = get_option( 'am_settings' );
	$key = $settings['wave_api_key'];
	if ( $key ) {
		$opts['key'] = $key;
		$wave = new wave( WAVE_API_URL, $opts );
		$wave->submit( AM_DEBUG );
		$body = $wave->waveResponse['body'];
		$results = am_format_wave( $body, $opts['reporttype'] );
		return $results;
	} else {
		return false;
	}
}

function am_format_wave( $body, $reporttype=1 ) {
	$body = json_decode( $body );
	
	$credits = $body->statistics->creditsremaining;
	$itemcount = $body->statistics->allitemcount;
	$waveurl = $body->statistics->waveurl;
	$categories = $body->categories;
	$report = '<ul>';
	foreach ( $categories as $cat => $category ) {
		$report .= "<li><strong>$category->description</strong>: $category->count</li>";
	}
	$report .= "</ul>";
	$report = "
	<div class='wave-result'>
		<p>
		Credits Remaining: $credits &bull; <a href='$waveurl'>Test at WAVE</a>
		</p>
		$report
	</div>";
	return $report;
}


add_action( 'init', 'am_posttypes' );
function am_posttypes() {
	$value = array( 
			__( 'accessibility report','access-monitor' ),
			__( 'accessibility reports','access-monitor' ),
			__( 'Accessibility Report','access-monitor' ),
			__( 'Accessibility Reports','access-monitor' ),
		);
		$labels = array(
		'name' => $value[3],
		'singular_name' => $value[2],
		'add_new' => __( 'Add New' , 'access-monitor' ),
		'add_new_item' => sprintf( __( 'Create New %s','access-monitor' ), $value[2] ),
		'edit_item' => sprintf( __( 'Modify %s','access-monitor' ), $value[2] ),
		'new_item' => sprintf( __( 'New %s','access-monitor' ), $value[2] ),
		'view_item' => sprintf( __( 'View %s','access-monitor' ), $value[2] ),
		'search_items' => sprintf( __( 'Search %s','access-monitor' ), $value[3] ),
		'not_found' =>  sprintf( __( 'No %s found','access-monitor' ), $value[1] ),
		'not_found_in_trash' => sprintf( __( 'No %s found in Trash','access-monitor' ), $value[1] ), 
		'parent_item_colon' => ''
	);
	$args = array(
		'labels' => $labels,
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_icon' => 'dashicons-universal-access',
		'supports' => array( 'title' )
	); 
	register_post_type( 'tenon-report', $args );
}


add_action( 'admin_menu', 'am_add_outer_box' );

// begin add boxes
function am_add_outer_box() {
	add_meta_box( 'am_report_div', __('Accessibility Report', 'access-monitor'), 'am_add_inner_box', 'tenon-report', 'normal','high' );
	add_meta_box( 'am_about_div', __('About this Report', 'access-monitor'), 'am_add_about_box', 'tenon-report', 'side', 'high' );
	add_meta_box( 'am_related_div', __('Related Reports', 'access-monitor'), 'am_add_related_box', 'tenon-report', 'side','high' );	
}

function am_add_inner_box() {
	global $post;
	$content = stripslashes( $post->post_content );
	$data = get_post_meta( $post->ID, '_tenon_json', true );
	$content .= "<h2>".__('JSON Submission Data', 'access-monitor').":</h2><pre><code>".print_r( $data, 1 )."</code></pre>";
	echo '<div class="am_post_fields">'.$content.'</div>';
}

function am_add_related_box() {
	global $post;
	$related = '';
	$relatives = get_posts( array(
			'post_type'  => 'tenon-report',
			'meta_key'   => '_tenon_parent',
			'meta_value' => $post->ID
		) );
	if ( !empty( $relatives ) ) {
		foreach ( $relatives as $relative ) {
			$title = $relative->post_title;
			$id = $relative->ID;
			$link = get_edit_post_link( $id );
			$date = get_the_time( 'M j, Y @ H:i', $id );
			if ( $id != $post->ID ) {
				$related .= "<li><a href='$link'>$title</a>: <strong>$date</strong></li>";
			}
		}
		echo "<ul>$related</ul>";
	}

	if ( empty( $relatives ) ) {
		echo "<p>" . __( 'No related reports.', 'access-monitor' ) . "</p>";
	}
}


function am_add_about_box() {
	global $post;
	$urls = $parameters = '';
	$pages = get_post_meta( $post->ID, '_tenon_pages', true );
	$params = get_post_meta( $post->ID, '_tenon_params', true );
	$total = get_post_meta( $post->ID, '_tenon_total', true );
	echo "<p class='error-total'>" . sprintf( __( '%s unique errors', 'access-monitor' ), "<span>$total</span>" ) . "</p>";
	if ( is_array( $pages ) ) {
		foreach ( $pages as $url ) {
			$page = str_replace( array( 'http://', 'https://', 'http://www.', 'https://www.' ), '', $url );
			$urls .= "<li><a href='$url'>$page</a></li>";
		}
		unset( $params['url'] );
		foreach ( $params as $key => $value ) {
			$key = stripslashes( trim( $key ) );
			$value = stripslashes( trim( $value ) );
			if ( $value == '' ) { $value = '<em>' . __( 'Default', 'access-monitor' ) . '</em>'; }
			$label = ucfirst( $key );
			$parameters .= "<li><strong>$label</strong>: $value</li>";
		}
		echo "<h4>" . __( 'URLs Tested', 'access-monitor' ) . "</h4><ul>$urls</ul>";
		echo "<h4>" . __( 'Test Parameters', 'access-monitor' ) . "</h4><ul>$parameters</ul>";
		echo "<p><span class='dashicons dashicons-editor-help' aria-hidden='true'></span><a href='http://tenon.io/documentation/understanding-request-parameters.php'>" . __( 'Tenon Request Parameters', 'access-monitor' ) . "</a></p>";
	} else {
		echo "<p>" . __( 'No pages tested yet.', 'access-monitor' ) . "</p>";
	}
}

add_action('admin_menu', 'am_remove_menu_item');
function am_remove_menu_item() {
    global $submenu;
    unset( $submenu['edit.php?post_type=tenon-report'][10] ); // Removes 'Add New'.
}

function am_set_report( $name = false ) {
	if ( !$name ) { 
		$name = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) ); 
	}
	$report_id = wp_insert_post( array( 'post_content'=>'', 'post_title'=>$name, 'post_status'=>'draft', 'post_type'=>'tenon-report' ) );
	//$reports = ( get_option( 'am_reports' ) != '' ) ? get_option( 'am_reports' ) : array();
	
	return $report_id;
}

register_deactivation_hook( __FILE__, 'am_deactivate_cron' );
function am_deactivate_cron() {
	wp_clear_scheduled_hook( 'amcron' );
}

add_action( 'amcron', 'am_schedule_report', 10, 4 );
function am_schedule_report( $report_id, $pages, $name, $params ) {
	$new_report = am_generate_report( $name, $pages, $report_id, $params ); // 'none' to prevent this from being auto-scheduled again
	$url = admin_url( "post.php?post=$new_report&action=edit" );
	update_post_meta( $new_report, '_tenon_parent', $report_id );
	add_post_meta( $report_id, '_tenon_child', $new_report );
	wp_mail( 
		apply_filters( 'am_cron_notification_email', get_option( 'admin_email' ), $name ), 
		sprintf( __( 'Scheduled Accessibility Report on %s', 'access-monitor' ), get_option( 'blogname' ) ), 
		sprintf( __( "View accessibility report: %s", 'access-monitor' ), $url ) 
	);
}

function am_generate_report( $name, $pages = false, $schedule = 'none', $params = array() ) {
	$report_id = am_set_report( $name );
	if ( is_array( $pages ) ) {
		$pages = $pages;
	} else {
		$pages = array( home_url() );
	}
	if ( $schedule != 'none' ) {
		if ( ! is_numeric( $schedule ) ) {
			$timestamp = ( $schedule == 'weekly' ) ? current_time( 'timestamp' ) + 60*60*24*7 : current_time( 'timestamp' ) + ( 60*60*24*30.5 );
			$args = array( 'report_id'=>$report_id, 'pages'=>$pages, 'name'=>$name, 'params'=>$params );
			wp_schedule_event( $timestamp, $schedule, 'amcron', $args );
			update_post_meta( $report_id, '_tenon_schedule', $schedule );			
		} else {
			update_post_meta( $report_id, '_tenon_schedule', $schedule );
		}
	}
	
	foreach ( $pages as $page ) {
		if ( is_numeric( $page ) ) { 
			$url = get_permalink( $page );
		} else {
			$url = $page;
		}
		if ( esc_url( $url ) ) {
			$params['url'] = $url;
			$report = am_query_tenon( $params );
			$report = $report['results'];
			$saved[$url] = $report;
			
		} else {
			continue;
		}
	}
	$data = am_format_tenon_report( $saved, $name );
	$total = $data['total'];
	$formatted = $data['html'];
	remove_action( 'save_post', 'am_run_report' );
	wp_update_post( array( 
					'ID'=>$report_id, 
					'post_content'=> $formatted
					) );
	update_post_meta( $report_id, '_tenon_total', $total );
	update_post_meta( $report_id, '_tenon_json', $saved );
	if ( isset( $params['projectID'] ) && $params['projectID'] != '' ) {
		update_post_meta( $report_id, '_tenon_projectID', $params['projectID'] );
	}
	update_post_meta( $report_id, '_tenon_params', $params );
	update_post_meta( $report_id, '_tenon_pages', $pages );
	wp_publish_post( $report_id );
	add_action( 'save_post', 'am_run_report' );
	return $report_id;
}

add_action( 'save_post', 'am_run_report' );
/**
 * Re-run a test cycle when updating post.
 */
function am_run_report( $id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $id ) || !( get_post_type( $id ) == 'tenon-report' ) ) {
		return;
	}
	$post = get_post( $id );
	if ( $post->post_status != 'publish' ) {
		return;
	} 
	
	$name = get_the_title( $id );
	$pages = get_post_meta( $id, '_tenon_pages', true );
	$params = get_post_meta( $id, '_tenon_params', true );
	$params = ( empty( $params ) ) ? array() : $params;
	if ( empty( $pages ) ) {
		return;
	}
	remove_action( 'save_post', 'am_run_report' );
	$report_id = am_generate_report( $name, $pages, 'none', $params );
	add_post_meta( $id, '_tenon_child', $report_id );
	update_post_meta( $report_id, '_tenon_parent', $id );
	add_action( 'save_post', 'am_run_report' );
}

function am_show_report( $report_id = false ) {
	$report_id = ( isset( $_GET['report'] ) && is_numeric( $_GET['report'] ) ) ? $_GET['report'] : false;
	$output = $name = '';
	if ( $report_id ) {
		$report = get_post( $report_id );
		$output = $report->post_content;
		$name = $report->post_title;
	} else {
		$reports = wp_get_recent_posts( array( 'numberposts'=>1, 'post_type'=>'tenon-report', 'post_status'=>'publish' ), 'OBJECT' );
		$report = end( $reports );
		if ( $report ) {
			$output = $report->post_content;
			$name = $report->post_title;
		}
	}
	if ( $output != '' ) {
		echo $output;
	} else {
		$data = am_format_tenon_report( get_post_meta( $report_id, '_tenon_json', true ), $name );
		$formatted = $data['html'];
		$total = $data['total'];
		wp_update_post( array( 'ID'=>$report_id, 'post_content' => $formatted ) );
		update_post_meta( $report_id, '_tenon_total', $total );
		echo $formatted;		
	}
}


function am_column($cols) {
	$cols['am_total'] = __( 'Errors', 'access-monitor' );
	$cols['am_schedule'] = __( 'Schedule', 'access-monitor' );
	$cols['am_tested'] = __( 'Level', 'access-monitor' );
	return $cols;
}

// Echo the ID for the new column
function am_custom_column( $column_name, $id ) {
	switch ( $column_name ) {
		case 'am_total' :
			$total = get_post_meta( $id, '_tenon_total', true );
			echo $total;
		break;
		case 'am_tested' :
			$params = get_post_meta( $id, '_tenon_params', true );
			echo isset( $params['level'] ) ? $params['level'] : '';
		break;		
		case 'am_schedule' :
			$schedule = get_post_meta( $id, '_tenon_schedule', true );
			if ( is_numeric( $schedule ) ) {
				$edit_url = admin_url( "post.php?post=$schedule&amp;action=edit" );
				$edit_link = "<a href='$edit_url'><span class='dashicons dashicons-clock'></span> " . __( 'View Original Test', 'access-monitor' ) . "</a>";
				echo $edit_link;
			} else {
				if ( $schedule ) {
					echo ucfirst( $schedule );
				} else {
					_e( 'One-time report', 'access-monitor' );
				}
			}
		break;		
	}
}

function am_return_value( $value, $column_name, $id ) {
	if ( $column_name == 'am_total' || $column_name == 'am_schedule' || $column_name == 'am_level' ) {
		$value = $id;
	}
	return $value;
}

// Actions/Filters for message tables and css output
add_action('admin_init', 'am_add');
function am_add() {
	add_filter( "manage_tenon-report_posts_columns", 'am_column' );			
	add_action( "manage_tenon-report_posts_custom_column", 'am_custom_column', 10, 2 );
}

function am_format_tenon_report( $results, $name ) {
	$header = "<h4>".stripslashes( $name )."; ".__( 'Results from %d pages tested', 'access-monitor' ). "</h4>";
	$return = $tbody = '';
	$displayed = false;
	$i = $count = $total = 0;
	if ( !empty( $results ) ) {
		$reported = array();
		$count = count( $results );
		foreach ( $results as $url => $resultSet ) {
			$tbody = $thead = '';
			$result_count = count( $resultSet );
			if ( $result_count > 0 ) {
				foreach ( $resultSet as $result ) {
					$i++;
					$hash = md5( $result->resultTitle . $result->ref . $result->errorSnippet . $result->xpath );
					if ( !in_array( $hash, $reported ) ) {
						$displayed = true;
						$total++;
						// right now, tenon.io is broken over https. So, strip back to non-https.
						$href = str_replace( 'https', 'http', $result->ref );						
						$ref = "<a href='$href'>$result->errorTitle</a>";
						$tbody .= "
							<tr>
								<td>$ref<p><strong>$result->resultTitle</strong>; $result->errorDescription</p></td>
								<td>$result->certainty</td>
								<td>$result->priority</td>
								<td><button class='snippet' data-target='snippet$i' aria-controls='snippet$i' aria-expanded='false'>Source</button> <div class='codepanel' id='snippet$i'><button class='close'><span class='screen-reader-text'>Close</span><span class='dashicons dashicons-no' aria-hidden='true'></span></button> <code class='am_code'>$result->errorSnippet</code></div></td>
								<td><button class='snippet' data-target='xpath$i' aria-controls='xpath$i' aria-expanded='false'>xPath</button> <div class='codepanel' id='xpath$i'><button class='close'><span class='screen-reader-text'>Close</span><span class='dashicons dashicons-no' aria-hidden='true'></span></button> <code class='am_code'>$result->xpath</code></div></td>
							</tr>";
					} else {
						$displayed = false;
					}
					$reported[] = $hash;
				}
				if ( !$displayed ) {
					$return .= "<h4>" . sprintf( __( "Errors found on %s.", 'access-monitor' ), "<a href='$url'>$url</a>" ) . " (<strong>$result_count</strong>)</h4><p>" . sprintf( __( 'The %d errors found on this page were also found on other pages tested.', 'access-monitor' ), $result_count ) . "</p>";
				} else {
					$thead = "<table class='widefat tenon-report'>";
					$thead .= "<caption>" . sprintf( __( "Errors found on %s.", 'access-monitor' ), "<a href='$url'>$url</a>" ) . " (<strong>$result_count</strong>)</caption>";
					$thead .= "
						<thead>
							<tr>
								<th scope='col'>".__( 'Issue', 'access-monitor' )."</th>
								<th scope='col'>".__( 'Certainty', 'access-monitor' )."</th>
								<th scope='col'>".__( 'Priority', 'access-monitor' )."</th>
								<th scope='col'>".__( 'Source', 'access-monitor' )."</th>
								<th scope='col'>".__( 'Xpath', 'access-monitor' )."</th>
							</tr>
						</thead>
						<tbody>";				
					$tfoot = "</tbody>
						<tfoot>
							<tr>
								<th scope='col'>".__( 'Issue', 'access-monitor' )."</th>
								<th scope='col'>".__( 'Certainty', 'access-monitor' )."</th>
								<th scope='col'>".__( 'Priority', 'access-monitor' )."</th>
								<th scope='col'>".__( 'Source', 'access-monitor' )."</th>
								<th scope='col'>".__( 'Xpath', 'access-monitor' )."</th>
							</tr>
						</tfoot>			
						</table>";
					$return .= $thead . $tbody . $tfoot;
				}
			} else {
				$return .= "<p>".sprintf( __( "No errors found on %s.", 'access-monitor' ), "<a href='$url'>$url</a>" )."</p>"; 
			}
		}
	} else {
		$return .= "<p><strong>Congratulations!</strong> Tenon didn't find any issues on this page.</p>";
	}
	$header = sprintf( $header, $count );
	return array( 'total'=>$total, 'html' => $header . $return );
}

add_filter( 'cron_schedules', 'am_custom_schedules' );
function am_custom_schedules( $schedules ) {
 	// Adds once weekly to the existing schedules.
 	$schedules['weekly'] = array(
 		'interval' => 604800,
 		'display' => __( 'Once Weekly', 'access-monitor' )
 	);
 	$schedules['monthly'] = array(
 		'interval' => 2635200,
 		'display' => __( 'Once Monthly', 'access-monitor' )
 	);	
 	return $schedules;	
}

function am_update_settings() {
	if ( isset( $_POST['am_settings'] ) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'access-monitor-nonce') ) die( "Security check failed" );	
		$tenon_api_key = ( isset( $_POST['tenon_api_key'] ) ) ? $_POST['tenon_api_key'] : '';
		$wave_api_key = ( isset( $_POST['wave_api_key'] ) ) ? $_POST['wave_api_key'] : '';
		$tenon_pre_publish = ( isset( $_POST['tenon_pre_publish'] ) ) ? 1 : 0;
		$am_post_types = ( isset( $_POST['am_post_types'] ) ) ? $_POST['am_post_types'] : array();
		$am_criteria = ( isset( $_POST['am_criteria'] ) ) ? $_POST['am_criteria'] : array();
		$am_notify = ( isset( $_POST['am_notify'] ) ) ? $_POST['am_notify'] : '';
		
		update_option( 'am_settings', 
			array( 
				'tenon_api_key'     => $tenon_api_key, 
				'wave_api_key'      => $wave_api_key,
				'am_post_types'     => $am_post_types,
				'tenon_pre_publish' => $tenon_pre_publish,
				'am_criteria'       => $am_criteria,
				'am_notify'         => $am_notify
			) 
		);
		echo "<div class='updated'><p>" . __( 'Access Monitor Settings Updated', 'access-monitor' ) . "</p></div>";
	}
}


add_action( 'admin_head', 'am_setup_admin_notice' );
function am_setup_admin_notice() {
	if ( !( isset( $_POST['tenon_api_key'] ) && $_POST['tenon_api_key'] != '' ) ) {
		$settings = ( is_array( get_option( 'am_settings' ) ) ) ? get_option( 'am_settings' ) : array();
		if ( empty( $settings ) || !isset( $settings['tenon_api_key'] ) || $settings['tenon_api_key'] == '' ) {
			if ( isset( $_GET['page'] ) && $_GET['page'] == 'access-monitor/access-monitor.php' ) {
				$url = '#settings';
			} else {
				$url = admin_url('edit.php?post_type=tenon-report&page=access-monitor/access-monitor.php');
			}
			$message = sprintf(__("You must <a href='%s'>enter a Tenon API key</a> to use Access Monitor.", 'access-monitor'), $url );
			add_action('admin_notices', create_function( '', "if ( ! current_user_can( 'manage_options' ) ) { return; } else { echo \"<div class='error'><p>$message</p></div>\";}" ) );
		}
	}
}


function am_settings() {
	$settings = ( is_array( get_option( 'am_settings' ) ) ) ? get_option( 'am_settings' ) : array();
	$settings = array_merge( array( 'tenon_api_key'=>'', 'wave_api_key'=>'', 'tenon_pre_publish' => '', 'am_post_types' => array(), 'am_post_grade' => '', 'am_criteria' => array() ), $settings );

	$post_types    = get_post_types( array( 'public' => true ), 'objects' );
	$am_post_types = isset( $settings['am_post_types'] ) ? $settings['am_post_types'] : array();
	$am_criteria = isset( $settings['am_criteria'] ) ? $settings['am_criteria'] : array();
	$am_notify   = isset( $settings['am_notify'] ) ? $settings['am_notify'] : get_option( 'admin_email' );
	$am_post_type_options = '';

	foreach ( $post_types as $type ) {
		if ( in_array( $type->name, $am_post_types ) ) {
			$selected = ' checked="checked"';
		} else {
			$selected = '';
		}
		$am_post_type_options .= "<input type='checkbox' id='am_$type->name' name='am_post_types[]' value='$type->name'$selected><label for='am_$type->name'>" . $type->labels->name . "</label> ";
	}
	
	echo "
	<form method='post' action='".admin_url('edit.php?post_type=tenon-report&page=access-monitor/access-monitor.php')."'>
		<div><input type='hidden' name='_wpnonce' value='".wp_create_nonce('access-monitor-nonce')."' /></div>
		<div><input type='hidden' name='am_settings' value='update' /></div>
		<p>
			<label for='tenon_api_key'>".__( 'Tenon API Key', 'access-monitor' )."</label> <input type='text' name='tenon_api_key' id='tenon_api_key' value='". esc_attr( $settings['tenon_api_key'] ) ."' />
		</p>
		<p class='checkbox'>
			<input type='checkbox' name='tenon_pre_publish' id='tenon_pre_publish' value='1' ". checked( $settings['tenon_pre_publish'], 1, false ) ."' /> <label for='tenon_pre_publish'>".__( 'Prevent inaccessible posts from being published', 'access-monitor' )."</label>
		</p>";
		if ( $settings['tenon_pre_publish'] == 1 ) {
			?>
				<fieldset>
					<legend><?php _e( 'Test these post types before publishing:', 'my-tickets' ); ?></legend>				
					<p class='checkbox'><?php echo $am_post_type_options; ?></p>
					<p>
						<label for='am_notify'><?php _e( 'Email address to request accessibility review', 'access-monitor' ); ?></label>
						<input type='email' value='<?php esc_attr_e( $am_notify ); ?>' id='am_notify' name='am_notify' />
					</p>
				</fieldset>
			<?php
			$criteria = array( 
				'level' => array( 'label' => __( 'Required WCAG Level', 'access-monitor' ), 'default' => 'AA' ),
				'certainty' => array( 'label' =>  __( 'Minimum certainty', 'access-monitor' ), 'default' => '0' ),
				'priority' => array( 'label' => __( 'Minimum priority', 'access-monitor' ), 'default' => '0' ),
				'grade' => array( 'label' => __( 'Minimum percentage grade to publish', 'access-monitor' ),  'default' => '90' ),
				'container' => array( 'label' => __( 'Post content container', 'access-monitor' ), 'default' => '.entry-content' ),
				'store' => array( 'label' => __( 'Store data at Tenon.io?', 'access-monitor' ), 'default' => '0' ),				
			);
			echo "<ul>";
			foreach ( $criteria as $key => $values ) {
				$label = $values['label'];
				$value = ( isset( $am_criteria[$key] ) && $am_criteria[$key] != '' ) ? $am_criteria[$key] : $values['default'];
				if ( $key == 'store' ) {
					echo "<li><label for='am_$key'>$label</label> <select name='am_criteria[$key]' id='am_$key'><option value='1' " . selected( $value, 1, false ) . ">" . __( 'Yes', 'access-monitor' ) . "</option><option value='0' " . selected( $value, 0, false ) . ">" . __( 'No', 'access-monitor' ) . "</option></select></li>";
				} else {
					if ( is_numeric( $value ) ) {
						echo "<li><label for='am_$key'>$label</label> <input type='number' min='0' max='100' name='am_criteria[$key]' id='am_$key' value='" . intval( $value ) . "' /></li>";
					} else {
						echo "<li><label for='am_$key'>$label</label> <input type='text' name='am_criteria[$key]' id='am_$key' value='" . esc_attr( $value ) . "' /></li>";
					}
				}
			}
			echo "</ul>";
		}
		
		
		
/* Removing WAVE settings for now.
		<p>
			<label for='wave_api_key'>".__( 'WAVE API Key', 'access-monitor' )."</label> <input type='text' name='wave_api_key' id='wave_api_key' value='". esc_attr( $settings['wave_api_key'] ) ."' />
		</p>
*/		
		echo "<div>";
		echo "<p>
		<input type='submit' value='".__('Update Settings','access-monitor')."' name='am_settings' class='button-primary' />
		</p>
		</div>
	</form>";
}

function am_report() {
	$settings = ( is_array( get_option( 'am_settings' ) ) ) ? get_option( 'am_settings' ) : array();
	$settings = array_merge( array( 'tenon_api_key'=>'', 'wave_api_key'=>'' ), $settings );
	
	if ( $settings['tenon_api_key'] == '' ) {
		$disabled = " disabled='disabled'";
		$message = "<p><strong><a href='http://tenon.io/register.php'>" . __( 'Sign up with Tenon to get an API key', 'access-monitor' ) . "</a></strong> &bull; <a href='#settings'>" . __( 'Add your API key', 'access-monitor' ) . "</a></p>";
	} else {
		$disabled = $message = '';
	}
	
	echo am_setup_report();
	$theme = wp_get_theme();
	$theme_name = $theme->Name;
	$theme_version = $theme->Version;		
	$name = $theme_name . ' ' . $theme_version;
	echo "$message
	<form method='post' action='".admin_url('edit.php?post_type=tenon-report&page=access-monitor/access-monitor.php')."'>
		<div><input type='hidden' name='_wpnonce' value='".wp_create_nonce('access-monitor-nonce')."' /></div>
		<div><input type='hidden' name='am_get_report' value='report' /></div>";
		echo "
		<div>
		<p>
			<label for='am_report_name'>".__( 'Report Name', 'access-monitor' )."</label> <input type='text' name='am_report_name' id='am_report_name' value='". esc_attr( $name ) ."' />
		</p>
		<ul aria-live='assertive'>
			<li id='field1' class='clonedInput'>
			<label for='am_report_pages'>".__( 'URL or post ID to test', 'access-monitor' )."</label>
			<input type='text' class='widefat' id='am_report_pages' name='am_report_pages[]' value='".esc_url( home_url() )."' />
			</li>";
			$last = wp_get_recent_posts( array( 'numberposts' => 1, 'post_type' => 'page', 'post_status'=>'publish' ) );
			$last_link = get_permalink( $last[0]['ID'] );
			$last_title = $last[0]['post_title'];
		echo "
			<li>
			<label for='am_report_pages_second'>".__( 'Most recent page', 'access-monitor' )." ($last_title)</label>
			<input type='text' class='widefat' id='am_report_pages_second' name='am_report_pages[]' value='".esc_url( $last_link )."' />
			</li>";
			$last = wp_get_recent_posts( array( 'numberposts' => 1, 'post_status'=>'publish' ) );
			$last_link = get_permalink( $last[0]['ID'] );
			$last_title = $last[0]['post_title'];
		echo "
			<li>
			<label for='am_report_pages_last'>".__( 'Most recent post', 'access-monitor' )." ($last_title)</label>
			<input type='text' class='widefat' id='am_report_pages_last' name='am_report_pages[]' value='".esc_url( $last_link )."' />
			</li>
		</ul>
		<div>
			<input type='button' id='add_field' value='".__( 'Add a test URL', 'access-monitor' )."' class='button' />
			<input type='button' id='del_field' value='".__( 'Remove last test', 'access-monitor' )."' class='button' />
		</div>
		<p>
			<label for='report_schedule'>" . __( 'Schedule report', 'access-monitor' ) . "</label>
			<select id='report_schedule' name='report_schedule'>
				<option value='none'>" . __( 'One-time report', 'access-monitor' ) . "</option>
				<option value='weekly'>" . __( 'Weekly', 'access-monitor' ) . "</option>
				<option value='monthly'>" . __( 'Monthly', 'access-monitor' ) . "</option>
			</select>
		</p>
		<button class='toggle-options closed' aria-controls='report-options' aria-expanded='false'>" . __( 'Tenon report options', 'access-monitor' ) . "<span class='dashicons dashicons-arrow-right' aria-hidden='true'></span></button>
		<div class='report-options' id='report-options'>
			<fieldset>
				<legend>" . __( 'Set Accessibility Test Options', 'access-monitor' ) . "</legend>
				<p>
					<label for='certainty'>" . __( 'Minimum Certainty', 'access-monitor' ) . "</label>
					<select name='certainty' id='certainty' aria-describedby='certainty-desc'>
						<option value='0'>0% (". __( 'More issues' ) . ")</option>
						<option value='20'>20%</option>
						<option value='40'>40%</option>
						<option value='60'>60%</option>
						<option value='80'>80%</option>
						<option value='100'>100% (". __( 'Fewer issues' ) . ")</option>
					</select> <span id='certainty-desc'>" . __( 'Higher values means Tenon.io has more confidence in the results.', 'access-monitor' ) . "</span>
				</p>
				<p>
					<label for='priority'>" . __( 'Minimum Priority', 'access-monitor' ) . "</label>
					<select name='priority' id='priority' aria-describedby='priority-desc'>
						<option value='0'>0% (". __( 'More issues' ) . ")</option>
						<option value='20'>20%</option>
						<option value='40'>40%</option>
						<option value='60'>60%</option>
						<option value='80'>80% (". __( 'Fewer issues' ) . ")</option>
					</select> <span id='priority-desc'>" . __( 'Higher values means Tenon.io lists this as a high priority issue.', 'access-monitor' ) . "</span>
				</p>
				<p>
					<label for='level'>" . __( 'Minimum WCAG Level', 'access-monitor' ) . "</label>
					<select name='level' id='level'>
						<option value='A'>A</option>
						<option value='AA' selected='selected'>AA</option>
						<option value='AAA'>AAA</option>
					</select>
				</p>
				<p>
					<label for='uaString'>" . __( 'User-agent String', 'access-monitor' ) . "</label>				
					<select name='uaString' id='uaString'>
						<option value=''>Default</option>
						<option value='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.114 Safari/537.36'>Chrome</option>
						<option value='Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)'>Internet Explorer</option>
						<option value='Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14'>Opera</option>
						<option value='Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0'>Firefox</option>
						".apply_filters( 'am_add_uastring_test', '' )."
					</select>
				</p>
				<p>
					<label for='viewport'>" . __( 'Viewport Size', 'access-monitor' ) . "</label>
					<select name='viewport' id='viewport'>
						<option value='1024x768'>1024x768</option>
						<option value='1280x1024'>1280x1024</option>
						<option value='1366x768'>1366x768</option>
						<option value='320x480'>320x480</option>
						<option value='640'>960</option>
						<option value='800'>600</option>
					</select>			
				</p>
				<p>
					<label for='projectID'>" . __( 'Project ID', 'access-monitor' ) . "</label>
					<input type='text' id='projectID' name='projectID' class='widefat' />
				</p>
				<p class='checkbox'>
					<label for='store'>" . __( 'Store test results at Tenon.io', 'access-monitor' ) . "</label>
					<input type='checkbox' id='store' name='store' value='1' />
				</p>				
		</div>
		<p>
			<input id='tenon-submit' type='submit'$disabled value='".__('Create Accessibility Report','access-monitor')."' name='am_generate' class='button-primary' />
		</p>
		</div>
	</form>";
}

function am_setup_report() {
	if ( isset( $_POST['am_generate'] ) ) {
		$name = ( isset( $_POST['am_report_name'] ) ) ? sanitize_text_field( $_POST['am_report_name'] ) : false;
		$pages = ( isset( $_POST['am_report_pages'] ) && !empty( $_POST['am_report_pages'] ) ) ? $_POST['am_report_pages'] : false;
		$schedule = ( isset( $_POST['report_schedule'] ) ) ? $_POST['report_schedule'] : 'none';
		
		$store = ( isset( $_POST['store'] ) ) ? 1 : 0;
		$projectID = ( isset( $_POST['projectID'] ) ) ? sanitize_text_field( $_POST['projectID'] ) : '';
		$viewport = ( isset( $_POST['viewport'] ) ) ? explode( 'x', $_POST['viewport'] ) : array( '1024', '768' );
		$viewportHeight = $viewport[1];
		$viewportWidth = $viewport[0];
		$level = ( isset( $_POST['level'] ) ) ? $_POST['level'] : 'AA';
		$priority = ( isset( $_POST['priority'] ) ) ? (int) $_POST['priority'] : 0;
		$certainty = ( isset( $_POST['certainty'] ) ) ? (int) $_POST['certainty'] : 0;
		
		$args = array( 
					'store' => $store,
					'projectID' => $projectID,
					'viewPortHeight' => $viewportHeight,
					'viewPortWidth' => $viewportWidth,
					'level' => $level,
					'priority' => $priority,
					'certainty' => $certainty
				);
		/*
		Set up arguments here; pass to am_generate_report
			importance (not present yet; field specific; associate with URL)
		*/
		am_generate_report( $name, $pages, $schedule, $args );
		am_show_report();
	}
}

function am_list_reports( $count = 10 ) {
	$count = (int) $count;
	$reports = wp_get_recent_posts( array( 'post_type'=>'tenon-report', 'numberposts'=>$count, 'post_status'=>'publish' ), 'OBJECT' );
	if ( is_array( $reports ) ) {
		echo "<ul>";
		foreach ( $reports as $report_post ) {
			$report = json_decode( $report_post->post_content );
			$report_id = $report_post->ID;
			$link = get_edit_post_link( $report_id );
			$date = get_the_time( 'Y-m-d H:i:s', $report_post );
			$name = $report_post->post_title;
			echo "<li><a href='$link'>".stripslashes( $name )."</a> ($date)</li>";
		}
		echo "</ul>";
	} else {
		echo "<p>".__( 'No accessibility reports created yet.', 'access-monitor' )."</p>";
	}
}

function am_support_page() {
	?>
	<div class="wrap" id='access-monitor'>
	<?php am_update_settings(); ?>
		<h2><div class='dashicons dashicons-universal-access' aria-hidden="true"></div><?php _e('Access Monitor','access-monitor'); ?></h2>
		<div id="am_settings_page" class="postbox-container" style="width: 70%">
			<div class='metabox-holder'>
				<div class="am-settings meta-box-sortables">
					<div class="postbox" id="report">
						<h3><?php _e('Create Accessibility Report','access-monitor'); ?></h3>
						<div class="inside">
							<?php 
							if ( isset( $_GET['report'] ) && is_numeric( $_GET['report'] ) ) { 
								am_show_report();
							}
							?>
							<?php am_report(); ?>
						</div>
					</div>
				</div>
			</div>
			<div class='metabox-holder'>
				<div class="am-settings meta-box-sortables">
					<div class="postbox" id="recent">
						<h3><?php _e('Recent Accessibility Reports','access-monitor'); ?></h3>
						<div class="inside">
							<?php 
								$count = apply_filters( 'am_recent_reports', 10 );
								am_list_reports( $count ); 
							?>
						</div>
					</div>
				</div>
			</div>			
			<div class='metabox-holder'>
				<div class="am-settings meta-box-sortables">
					<div class="postbox" id="settings" tabindex='-1'>
						<h3><?php _e('Access Monitor Settings','access-monitor'); ?></h3>
						<div class="inside">
							<?php am_settings(); ?>
						</div>
					</div>
				</div>
			</div>
			<div class='metabox-holder' tabindex='-1' id='support-form'>			
				<div class="am-settings meta-box-sortables">
					<div class="postbox" id="get-support">
						<h3><?php _e('Get Plug-in Support','access-monitor'); ?></h3>
						<div class="inside">
							<div class='am-support-me'>
								<p>
									<?php printf(
										__( 'Please, consider a <a href="%s">donation</a> to support Access Monitor!', 'access-monitor' )
									, "https://www.joedolson.com/donate/" ); ?>
								</p>
							</div>
							<?php am_get_support_form(); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php am_show_support_box(); ?>
	</div>
	<?php
}

function am_show_support_box() {
?>
<div class="postbox-container" style="width:20%">
<div class="metabox-holder">
	<div class="meta-box-sortables">
		<div class="postbox">
		<h3><?php _e('Support this Plug-in','access-monitor'); ?></h3>
		<div id="support" class="inside resources">
		<ul>
			<li>		
			<p>
				<a href="https://twitter.com/intent/follow?screen_name=joedolson" class="twitter-follow-button" data-size="small" data-related="joedolson">Follow @joedolson</a>
				<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if (!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
			</p>
			</li>
			<li><p><?php _e('<a href="http://www.joedolson.com/donate/">Make a donation today!</a> Every donation counts - donate $10, $20, or $100 and help me keep this plug-in running!','access-monitor'); ?></p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<div>
					<input type="hidden" name="cmd" value="_s-xclick" />
					<input type="hidden" name="hosted_button_id" value="B75RYAX9BMX6S" />
					<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt=	"Donate" />
					<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1" />
					</div>
				</form>
				
			</li>
			<li><a href="http://profiles.wordpress.org/users/joedolson/"><?php _e('Check out my other plug-ins','access-monitor'); ?></a></li>
			<li><a href="http://wordpress.org/extend/plugins/access-monitor/"><?php _e('Rate this plug-in','access-monitor'); ?></a></li>		
		</ul>
		</div>
		</div>
	</div>
	<div class="meta-box-sortables">
		<div class="postbox">
		<h3><?php _e('Disclaimer','access-monitor'); ?></h3>
		<div id="support" class="inside resources">
			<p>
				<?php _e( 'Access Monitor uses Tenon.io. The Tenon.io API is designed to examine aspects of accessibility that are machine-testable in a reliable way. No errors does not mean that your site is accessible.', 'access-monitor' ); ?>
			</p>
			<p>
				<?php echo "<a href='http://tenon.io/documentation/what-tenon-tests.php'>" . __( 'What Tenon Tests', 'access-monitor' ) . "</a>"; ?>
			</p>
		</div>
		</div>
	</div>	
	
	<div class="meta-box-sortables">
		<div class="postbox">
		<h3><?php _e('Get Help','access-monitor'); ?></h3>
		<div id="support" class="inside resources">
			<p>
				<?php printf( __( 'Access Monitor has two parts: the plug-in, and the API it interacts with. If your issue is in the plug-in, use the <a href="%s">support form</a>. If your issue is with the API or on tenon.io, <a href="mailto:support@tenon.io">email Tenon support</a>. Thanks!', 'access-monitor' ), '#support-form' ); ?>
			</p>
		</div>
		</div>
	</div>		

</div>
</div>
<?php
}

add_action( 'admin_menu', 'am_add_support_page' );
// Add the administrative settings to the "Settings" menu.
function am_add_support_page() {
    if ( function_exists( 'add_submenu_page' ) ) {
		$permissions = apply_filters( 'am_use_monitor', 'manage_options' );
		$plugin_page = add_submenu_page( 'edit.php?post_type=tenon-report', __( 'Access Monitor > Add New Report', 'access-monitor' ), __( 'Add Report/Settings', 'access-monitor' ), $permissions, __FILE__, 'am_support_page' );
		add_action( 'load-'. $plugin_page, 'am_load_admin_styles' );
    }
}

function am_load_admin_styles() {
	add_action( 'admin_enqueue_scripts', 'am_admin_styles' );
}

function am_admin_styles() {
	wp_enqueue_style( 'am-admin-styles', plugins_url( 'css/am-admin-styles.css', __FILE__ ) );
}

function am_get_support_form() {
	global $current_user, $am_version;
	get_currentuserinfo();
	// send fields for Access Monitor
	$version = $am_version;
	// send fields for all plugins
	$wp_version = get_bloginfo('version');
	$home_url = home_url();
	$wp_url = site_url();
	$language = get_bloginfo('language');
	$charset = get_bloginfo('charset');
	// server
	$php_version = phpversion();

	// theme data
	$theme = wp_get_theme();
	$theme_name = $theme->Name;
	$theme_uri = $theme->ThemeURI;
	$theme_parent = $theme->Template;
	$theme_version = $theme->Version;	

	// plugin data
	$plugins = get_plugins();
	$plugins_string = '';
		foreach( array_keys($plugins) as $key ) {
			if ( is_plugin_active( $key ) ) {
				$plugin =& $plugins[$key];
				$plugin_name = $plugin['Name'];
				$plugin_uri = $plugin['PluginURI'];
				$plugin_version = $plugin['Version'];
				$plugins_string .= "$plugin_name: $plugin_version; $plugin_uri\n";
			}
		}
	$data = "
================ Installation Data ====================
Version: $version

==WordPress:==
Version: $wp_version
URL: $home_url
Install: $wp_url
Language: $language
Charset: $charset

==Extra info:==
PHP Version: $php_version
Server Software: $_SERVER[SERVER_SOFTWARE]
User Agent: $_SERVER[HTTP_USER_AGENT]

==Theme:==
Name: $theme_name
URI: $theme_uri
Parent: $theme_parent
Version: $theme_version

==Active Plugins:==
$plugins_string
";
	if ( isset($_POST['am_support']) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'access-monitor-nonce') ) die("Security check failed");	
		$request = stripslashes( $_POST['support_request'] );
		$has_donated = ( isset( $_POST['has_donated'] ) && $_POST['has_donated'] == 'on')?"Donor":"No donation";
		$has_read_faq = ( isset( $_POST['has_read_faq'] ) && $_POST['has_read_faq'] == 'on')?"Read FAQ":true; // has no faq, for now.
		$subject = "Access Monitor support request. $has_donated";
		$message = $request ."\n\n". $data;
		// Get the site domain and get rid of www. from pluggable.php
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
				$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;		
		$from = "From: \"$current_user->display_name\" <$from_email>\r\nReply-to: \"$current_user->display_name\" <$current_user->user_email>\r\n";

		if ( !$has_read_faq ) {
			echo "<div class='message error'><p>".__('Please read the FAQ and other Help documents before making a support request.','access-monitor')."</p></div>";
		} else {
			wp_mail( "plugins@joedolson.com",$subject,$message,$from );
		
			if ( $has_donated == 'Donor' ) {
				echo "<div class='message updated'><p>".__('Thank you for supporting the continuing development of this plug-in! I\'ll get back to you as soon as I can.','access-monitor')."</p></div>";		
			} else {
				echo "<div class='message updated'><p>".__('I\'ll get back to you as soon as I can, after dealing with any support requests from plug-in supporters.','access-monitor')."</p></div>";				
			}
		}
	} else {
		$request = '';
	}
	echo "
	<form method='post' action='".admin_url( 'edit.php?post_type=tenon-report&page=access-monitor/access-monitor.php' )."'>
		<div><input type='hidden' name='_wpnonce' value='".wp_create_nonce( 'access-monitor-nonce' )."' /></div>
		<div>
		<p class='checkbox'>
		<input type='checkbox' name='has_donated' id='has_donated' value='on' /> <label for='has_donated'>".__( 'I have <a href="http://www.joedolson.com/donate/">made a donation to help support this plug-in</a>.','access-monitor' )."</label>
		</p>
		<p>
		<label for='support_request'>Support Request:</label><br /><textarea name='support_request' required aria-required='true' id='support_request' cols='80' rows='10'>".stripslashes($request)."</textarea>
		</p>
		<p>
		<input type='submit' value='".__('Send Support Request','access-monitor')."' name='am_support' class='button-primary' />
		</p>
		<p>".
		__('The following additional information will be sent with your support request:','access-monitor')
		."</p>
		<div class='am_support'>
		".wpautop($data)."
		</div>
		</div>
	</form>";
}

add_filter( 'gettext', 'change_publish_button', 10, 2 );
/**
 * Changes the publish button from saying 'Update' to 'Re-run this test'
 *
 */
function change_publish_button( $translation, $text ) {
	if ( is_admin() && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
		global $post;
		if ( is_object( $post ) ) {
			if ( $text == 'Update' && $post->post_type == 'tenon-report' ) {
				$translation = __( 'Re-run this test', 'access-monitor' );
			}
		}
	}
	return $translation;
}

add_action( 'current_screen', 'am_redirect_new' );
/** 
 * Prevents the default add new post screen from showing.
 *
 */
function am_redirect_new() {
	$screen = get_current_Screen();
	if ( $screen->id == 'tenon-report' && !isset( $_GET['action'] ) ) {
		wp_safe_redirect( admin_url('edit.php?post_type=tenon-report&page=access-monitor/access-monitor.php') );
	}
}