<?php
/*
Plugin Name: Theme Checker
Plugin URI: http://checker.accessiblewebdesign.org
Description: Inspect WordPress for accessibility issues.
Author: Joseph C Dolson
Author URI: http://www.joedolson.com
Text Domain: theme-checker
Domain Path: lang
Version: 0.1.0
*/
/*  Copyright 2014  Joe Dolson (email : joe@joedolson.com)

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
define( 'TENON_API_URL', 'http://beta.tenon.io/api/' );
define( 'DEBUG', false );

//define('TENON_API_URL', 'https://www.tenon.io/api/');
define( 'WAVE_API_URL', 'http://wave.webaim.org/api/request' );

require_once( 't/tenon.php' );
require_once( 't/wave.php' );

$tc_version = '0.1.0';

add_filter( 'the_content', 'tc_pass_query' );
function tc_pass_query( $content ) {
	if ( isset( $_GET['tenon'] ) && $_GET['tenon'] == 'true' ) {
		$permalink = get_the_permalink();
		$results = tc_query_tenon( array( 'url'=>$permalink ) );
		return $results.$content;
	}
	return $content;
}

function tc_query_tenon( $post, $format = true ) {
	// creates the $opts array from the $post data
	// only sets items that are non-blank. This allows Tenon to revert to defaults
	$expectedPost = array( 'src', 'url', 'level', 'certainty', 'priority',
		'docID', 'systemID', 'reportID', 'viewPortHeight', 'viewPortWidth',
		'uaString', 'importance', 'ref', 'importance', 'fragment', 'store' );

	foreach ( $post AS $k => $v ) {
		if ( in_array($k, $expectedPost ) ) {
			if ( strlen( trim( $v ) ) > 0 ) {
				$opts[$k] = $v;
			}
		}
	}
	$settings = get_option( 'tc_settings' );
	$key = $settings['tenon_api_key'];
	if ( $key ) {
		$opts['key'] = $key;
		//$opts['level'] = 'AA'; // -> this feature is currently broken; pending notice from Karl.
		$tenon = new tenon( TENON_API_URL, $opts );
		$tenon->submit( DEBUG );
		$body = $tenon->tenonResponse['body'];
		if ( $format == true ) {
			$results = tc_format_tenon( $body );
		} else {
			$object = json_decode( $body );
			if ( property_exists( $object, 'resultSet' ) ) {
				$results = $object->resultSet;
			} else {
				$results = array();
			}
		}
		return $results;
	} else {
		return false;
	}
}

function tc_format_tenon( $body ) {
	if ( $body === false ) {
		return __( 'No Tenon API Key provided', 'theme-checker' );
	}
	$object = json_decode( $body );
	if ( property_exists( $object, 'resultSummary' ) ) {
		// unchecked object references
		$errors = $object->resultSummary->issues->totalErrors;
	} else {
		$errors = 0;
	}
	
	if ( property_exists( $object, 'resultSet' ) ) {
		$results = $object->resultSet;
	} else {
		$results = array();
	}
	$return = tc_format_tenon_array( $results, $errors );
	return $return;
}

function tc_format_tenon_array( $results, $errors ) {
	$return = "<section><h1>".sprintf( __( '%s accessibility issues identified.', 'theme-checker' ), "<em>$errors</em>" )."</h1>";
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
			$return .= "
				<div class='tenon-result'>
					<h2>
						<span>$i</span>. $result->errorTitle 
						<span class='meta'>
							<span class='certainty $cert'>". sprintf( __( 'Certainty: %s', 'theme-checker' ), "$result->certainty%" ). "</span>  
							<span class='priority $prio'>". sprintf( __( 'Priority: %s', 'theme-checker' ), "$result->priority%" ). "</span>
						</span>
					</h2>
					<p>$result->errorDescription <strong>Read more:</strong> <a href='$result->ref'>$result->resultTitle</a> <span title='Links not currently live at Tenon.io'>[Note]</span></p>
					<h3>Error Source</h3>
					<pre lang='html'>".$result->errorSnippet."</pre>
					<h3>Xpath:</h3> <pre><code>$result->xpath</code></pre>
	
				</div>";
		}
	} else {
		$return .= "<p><strong>Congratulations!</strong> Tenon didn't find any issues on this page.</p>";
	}
	return $return . "</section><hr />";
}

add_action('admin_enqueue_scripts', 'tc_admin_enqueue_scripts');
function tc_admin_enqueue_scripts() {
	global $current_screen;
	wp_enqueue_script( 'tc.functions', plugins_url( 'js/jquery.ajax.js', __FILE__ ), array( 'jquery' ) );
	wp_localize_script( 'tc.functions', 'tc_ajax_url', admin_url( 'admin-ajax.php' ) );
	wp_localize_script( 'tc.functions', 'tc_ajax_action', 'tc_ajax_query_tenon' );
	wp_localize_script( 'tc.functions', 'tc_current_screen', $current_screen->id );
	wp_enqueue_style( 'tc.styles', plugins_url( 'css/tc-styles.css', __FILE__ ) );	
}

add_action('wp_enqueue_scripts', 'tc_wp_enqueue_scripts');
function tc_wp_enqueue_scripts() {
	wp_enqueue_style( 'tc.styles', plugins_url( 'css/tc-styles.css', __FILE__ ) );
}

add_action('wp_ajax_tc_ajax_query_tenon', 'tc_ajax_query_tenon');
add_action('wp_ajax_nopriv_tc_ajax_query_tenon', 'tc_ajax_query_tenon');

function tc_ajax_query_tenon() {
	if ( isset( $_REQUEST['tenon'] ) ) {
		$screen = $_REQUEST['current_screen'];
		if ( $screen == 'dashboard' ) {
			$args = array( 'src'=>stripslashes( $_REQUEST['tenon'] ) );
		} else {
			$args = array( 'src'=>stripslashes( $_REQUEST['tenon'] ), 'fragment' => 1 );
		}
		echo tc_query_tenon( $args );
		die;
	}
}

add_action( 'admin_footer', 'tc_admin_footer' );
function tc_admin_footer() {
	echo "<div aria-live='assertive' class='feedback' id='tenon' style='color: #333;background:#fff;padding: 2em 2em 4em 14em;clear:both;border-top:1px solid #333'></div>";
}

add_action( 'admin_bar_menu','tc_admin_bar', 200 );
function tc_admin_bar() {
	global $wp_admin_bar;
	if ( is_admin() ) {
		$url = '#tenon';
	} else {
		global $post_id; 
		$url = add_query_arg(  'tenon', 'true', get_permalink( $post_id ) );
	}
	$args = array( 'id'=>'tenonCheck', 'title'=>__( 'A11y Check','theme-checker' ), 'href'=>$url );
	$wp_admin_bar->add_node( $args );
}


add_filter( 'the_content', 'tc_wave_pass_query' );
function tc_wave_pass_query( $content ) {
	if ( isset( $_GET['wave'] ) && is_numeric( $_GET['wave'] ) ) {
		$permalink = get_the_permalink();
		$reporttype = (int) $_GET['wave'];
		$results = tc_query_wave( array( 'url'=>$permalink, 'reporttype'=>$reporttype ) );
		return $results.$content;
	}
	return $content;
}

function tc_query_wave( $post ) {
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
	$settings = get_option( 'tc_settings' );
	$key = $settings['wave_api_key'];
	if ( $key ) {
		$opts['key'] = $key;
		$wave = new wave( WAVE_API_URL, $opts );
		$wave->submit( DEBUG );
		$body = $wave->waveResponse['body'];
		$results = tc_format_wave( $body, $opts['reporttype'] );
		return $results;
	} else {
		return false;
	}
}

function tc_format_wave( $body, $reporttype=1 ) {
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


add_action( 'init', 'bs_posttypes' );
function bs_posttypes() {
	$value = array( 
			__( 'accessibility report','theme-checker' ),
			__( 'accessibility reports','theme-checker' ),
			__( 'Accessibility Report','theme-checker' ),
			__( 'Accessibility Reports','theme-checker' ),
		);
		$labels = array(
		'name' => $value[3],
		'singular_name' => $value[2],
		'add_new' => __( 'Add New' , 'theme-checker' ),
		'add_new_item' => sprintf( __( 'Create New %s','theme-checker' ), $value[2] ),
		'edit_item' => sprintf( __( 'Modify %s','theme-checker' ), $value[2] ),
		'new_item' => sprintf( __( 'New %s','theme-checker' ), $value[2] ),
		'view_item' => sprintf( __( 'View %s','theme-checker' ), $value[2] ),
		'search_items' => sprintf( __( 'Search %s','theme-checker' ), $value[3] ),
		'not_found' =>  sprintf( __( 'No %s found','theme-checker' ), $value[1] ),
		'not_found_in_trash' => sprintf( __( 'No %s found in Trash','theme-checker' ), $value[1] ), 
		'parent_item_colon' => ''
	);
	$args = array(
		'labels' => $labels,
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_icon' => 'dashicons-universal-access',
		'supports' => array( 'title', 'editor', 'custom-fields' )
	); 
	register_post_type( 'tenon-report', $args );
}

add_action('admin_menu', 'tc_remove_menu_item');
function tc_remove_menu_item() {
    global $submenu;
    unset( $submenu['edit.php?post_type=tenon-report'][10] ); // Removes 'Add New'.
}

function tc_set_report( $name = false ) {
	if ( !$name ) { 
		$name = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) ); 
	}
	$report_id = wp_insert_post( array( 'post_content'=>'', 'post_title'=>$name, 'post_status'=>'draft', 'post_type'=>'tenon-report' ) );
	//$reports = ( get_option( 'tc_reports' ) != '' ) ? get_option( 'tc_reports' ) : array();
	
	return $report_id;
}

function tc_generate_report( $name, $pages = false ) {
	$settings = get_option( 'tc_settings' );
	$report_id = tc_set_report( $name );
	if ( isset( $settings['pages'] ) && !$pages ) {
		$pages = $settings['pages']; 
	} elseif ( is_array( $pages ) ) {
		$pages = $pages;
	} else {
		$pages = array( home_url() );
	}
	foreach ( $pages as $page ) {
		if ( is_numeric( $page ) ) { 
			$url = get_permalink( $page );
		} else {
			$url = $page;
		}
		if ( esc_url( $url ) ) {
			$report = tc_query_tenon( array( 'url'=>$url ), false );
			$saved[$url] = $report;
			
		} else {
			continue;
		}
	}
	$formatted = tc_format_tenon_report( $saved, $name );	
	wp_update_post( array( 
					'ID'=>$report_id, 
					'post_content'=> $formatted
					) );
	update_post_meta( $report_id, '_tenon_json', $saved );
	wp_publish_post( $report_id );
}

function tc_show_report( $report_id = false ) {
	$report_id = ( isset( $_GET['report'] ) && is_numeric( $_GET['report'] ) ) ? $_GET['report'] : false;
	$output = $name = '';
	if ( $report_id ) {
		//$output = get_option( "tc_report_$report_id" );
		$report = get_post( $report_id );
		$output = $report->post_content;
		$name = $report->post_title;
	} else {
		$reports = wp_get_recent_posts( array( 'numberposts'=>1, 'post_type'=>'tenon-report' ), 'OBJECT' );
		$report = end( $reports );
		if ( $report ) {
			//$output = get_option( "tc_report_$report" );
			$output = $report->post_content;
			$name = $report->post_title;
		}
	}
	if ( $output != '' ) {
		echo $output;
	} else {
		$formatted = tc_format_tenon_report( get_post_meta( $report_id, '_tenon_json', true ), $name );
		wp_update_post( array( 'ID'=>$report_id, 'post_content' => $formatted ) );
		echo $formatted;		
	}
}

function tc_format_tenon_report( $results, $name ) {
	$header = "<h4>".stripslashes( $name )."; ".__( 'Results from %d pages tested', 'theme-checker' ). "</h4>";
	$return = '';
	$i = $count = 0;
	if ( !empty( $results ) ) {
		foreach ( $results as $url => $resultSet ) {
			$count = count( $url );
			$return .= "<table class='widefat tenon-report'>";
			$return .= "<caption>".__( "Errors found on <a href='$url'>$url</a>", 'theme-checker' )."</caption>";
			$return .= "
				<thead>
					<tr>
						<th scope='col'>".__( 'Issue', 'theme-checker' )."</th>
						<th scope='col'>".__( 'Certainty', 'theme-checker' )."</th>
						<th scope='col'>".__( 'Priority', 'theme-checker' )."</th>
						<th scope='col'>".__( 'Source', 'theme-checker' )."</th>
						<th scope='col'>".__( 'Xpath', 'theme-checker' )."</th>
					</tr>
				</thead>
				<tbody>";
			foreach ( $resultSet as $result ) {
				$i++;
				$return .= "
					<tr>
						<td><a href='$result->ref'>$result->resultTitle</a></td>
						<td>$result->certainty</td>
						<td>$result->priority</td>
						<td><button class='snippet' data-target='snippet$i' aria-controls='snippet$i' aria-expanded='false'>Source</button> <div class='codepanel' id='snippet$i'><button class='close'><span class='screen-reader-text'>Close</span><span class='dashicons dashicons-no' aria-hidden='true'></span></button> <code class='tc_code'>$result->errorSnippet</code></div></td>
						<td><button class='snippet' data-target='xpath$i' aria-controls='xpath$i' aria-expanded='false'>xPath</button> <div class='codepanel' id='xpath$i'><button class='close'><span class='screen-reader-text'>Close</span><span class='dashicons dashicons-no' aria-hidden='true'></span></button> <code class='tc_code'>$result->xpath</code></div></td>
					</tr>";
			}
				$return .= "</tbody>
				</table>";
		}
	} else {
		$return .= "<p><strong>Congratulations!</strong> Tenon didn't find any issues on this page.</p>";
	}
	$header = sprintf( $header, $count );
	return $header . $return;
}


function tc_settings() {
	if ( isset( $_POST['tc_settings'] ) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'theme-checker-nonce') ) die( "Security check failed" );	
		$tenon_api_key = $_POST['tenon_api_key'];
		$wave_api_key = $_POST['wave_api_key'];
		update_option( 'tc_settings', array( 'tenon_api_key'=>$tenon_api_key, 'wave_api_key'=>$wave_api_key ) );
	}
	$settings = ( is_array( get_option( 'tc_settings' ) ) ) ? get_option( 'tc_settings' ) : array();
	$settings = array_merge( array( 'tenon_api_key'=>'', 'wave_api_key'=>'' ), $settings );

	echo "
	<form method='post' action='".admin_url('options-general.php?page=theme-checker/theme-checker.php')."'>
		<div><input type='hidden' name='_wpnonce' value='".wp_create_nonce('theme-checker-nonce')."' /></div>
		<div><input type='hidden' name='tc_settings' value='update' /></div>
		<p>
			<label for='tenon_api_key'>".__( 'Tenon API Key', 'theme-checker' )."</label> <input type='text' name='tenon_api_key' id='tenon_api_key' value='". esc_attr( $settings['tenon_api_key'] ) ."' />
		</p>
		<p>
			<label for='wave_api_key'>".__( 'WAVE API Key', 'theme-checker' )."</label> <input type='text' name='wave_api_key' id='wave_api_key' value='". esc_attr( $settings['wave_api_key'] ) ."' />
		</p>		
		<div>";
		echo "<p>
		<input type='submit' value='".__('Update Settings','theme-checker')."' name='tc_settings' class='button-primary' />
		</p>
		</div>
	</form>";
}

function tc_report() {
	echo tc_setup_report();
	$theme = wp_get_theme();
	$theme_name = $theme->Name;
	$theme_version = $theme->Version;		
	$name = $theme_name . ' ' . $theme_version;
	echo "
	<form method='post' action='".admin_url('options-general.php?page=theme-checker/theme-checker.php')."'>
		<div><input type='hidden' name='_wpnonce' value='".wp_create_nonce('theme-checker-nonce')."' /></div>
		<div><input type='hidden' name='tc_get_report' value='report' /></div>";
		echo "
		<div>
		<p>
			<label for='tc_report_name'>".__( 'Report Name', 'theme-checker' )."</label> <input type='text' name='tc_report_name' id='tc_report_name' value='". esc_attr( $name ) ."' />
		</p>
		<ul>
			<li>
			<label for='tc_report_pages1'>".__( 'URL or post ID to test (1)', 'theme-checker' )."</label>
			<input type='text' id='tc_report_pages1' name='tc_report_pages[]' value='".esc_url( home_url() )."' />
			</li>
			<li>
			<label for='tc_report_pages2'>".__( 'URL or post ID to test (2)', 'theme-checker' )."</label>
			<input type='text' id='tc_report_pages2' name='tc_report_pages[]' />
			</li>
			<li>
			<label for='tc_report_pages3'>".__( 'URL or post ID to test (3)', 'theme-checker' )."</label>
			<input type='text' id='tc_report_pages3' name='tc_report_pages[]' />
			</li>
			<li>
			<label for='tc_report_pages4'>".__( 'URL or post ID to test (4)', 'theme-checker' )."</label>
			<input type='text' id='tc_report_pages4' name='tc_report_pages[]' />
			</li>			
		</ul>
		<p>
			<input type='submit' value='".__('Create Accessibility Report','theme-checker')."' name='tc_generate' class='button-primary' />
		</p>
		</div>
	</form>";	
}

function tc_setup_report() {
	if ( isset( $_POST['tc_generate'] ) ) {
		$name = ( isset( $_POST['tc_report_name'] ) ) ? sanitize_text_field( $_POST['tc_report_name'] ) : false;
		$pages = ( isset( $_POST['tc_report_pages'] ) && !empty( $_POST['tc_report_pages'] ) ) ? $_POST['tc_report_pages'] : false;
		tc_generate_report( $name, $pages );
		tc_show_report();
	}
}

function tc_list_reports( $count = 10 ) {
	$count = (int) $count;
	$reports = wp_get_recent_posts( array( 'post_type'=>'tenon-report', 'numberposts'=>$count ), 'OBJECT' );
	if ( is_array( $reports ) ) {
		echo "<ul>";
		foreach ( $reports as $report_post ) {
			$report = json_decode( $report_post->post_content );
			$report_id = $report_post->ID;
			$link = admin_url( "options-general.php?page=theme-checker/theme-checker.php&report=$report_id" );
			$date = get_the_time( 'Y-m-d H:i:s', $report_post );
			$name = $report_post->post_title;
			echo "<li><a href='$link'>".stripslashes( $name )."</a> ($date)</li>";
		}
		echo "</ul>";
	} else {
		echo "<p>".__( 'No accessibility reports created yet.', 'theme-checker' )."</p>";
	}
}

function tc_support_page() {
	?>
	<div class="wrap" id="theme-checker">
		<h2><?php _e('Theme Checker','theme-checker'); ?></h2>
		<div id="tc_settings_page" class="postbox-container" style="width: 70%">
			<div class='metabox-holder'>
				<div class="tc-settings meta-box-sortables">
					<div class="postbox" id="report">
						<h3><?php _e('Accessibility Report','theme-checker'); ?></h3>
						<div class="inside">
							<?php 
							if ( isset( $_GET['report'] ) && is_numeric( $_GET['report'] ) ) { 
								tc_show_report();
							}
							?>
							<?php tc_report(); ?>
						</div>
					</div>
				</div>
			</div>
			<div class='metabox-holder'>
				<div class="tc-settings meta-box-sortables">
					<div class="postbox" id="report">
						<h3><?php _e('Recent Accessibility Reports','theme-checker'); ?></h3>
						<div class="inside">
							<?php 
								$count = apply_filters( 'tc_recent_reports', 10 );
								tc_list_reports( $count ); 
							?>
						</div>
					</div>
				</div>
			</div>			
			<div class='metabox-holder'>
				<div class="tc-settings meta-box-sortables">
					<div class="postbox" id="settings">
						<h3><?php _e('Theme Checker Settings','theme-checker'); ?></h3>
						<div class="inside">
							<?php tc_settings(); ?>
						</div>
					</div>
				</div>
			</div>
			<div class='metabox-holder'>			
				<div class="tc-settings meta-box-sortables">
					<div class="postbox" id="get-support">
						<h3><?php _e('Get Plug-in Support','theme-checker'); ?></h3>
						<div class="inside">
							<?php tc_get_support_form(); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php tc_show_support_box(); ?>
	</div>
	<?php
}

function tc_show_support_box() {
?>
<div class="postbox-container" style="width:20%">
<div class="metabox-holder">
	<div class="meta-box-sortables">
		<div class="postbox">
		<h3><?php _e('Support this Plug-in','theme-checker'); ?></h3>
		<div id="support" class="inside resources">
		<ul>
			<li>		
			<p>
				<a href="https://twitter.com/intent/follow?screen_name=joedolson" class="twitter-follow-button" data-size="small" data-related="joedolson">Follow @joedolson</a>
				<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if (!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
			</p>
			</li>
			<li><p><?php _e('<a href="http://www.joedolson.com/donate/">Make a donation today!</a> Every donation counts - donate $2, $10, or $100 and help me keep this plug-in running!','theme-checker'); ?></p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<div>
					<input type="hidden" name="cmd" value="_s-xclick" />
					<input type="hidden" name="hosted_button_id" value="8490399" />
					<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="Donate" />
					<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
					</div>
				</form>
			</li>
			<li><a href="http://profiles.wordpress.org/users/joedolson/"><?php _e('Check out my other plug-ins','theme-checker'); ?></a></li>
			<li><a href="http://wordpress.org/extend/plugins/theme-checker/"><?php _e('Rate this plug-in','theme-checker'); ?></a></li>		
		</ul>
		</div>
		</div>
	</div>
</div>
</div>
<?php
}

add_action( 'admin_menu', 'tc_add_support_page' );
// Add the administrative settings to the "Settings" menu.
function tc_add_support_page() {
    if ( function_exists( 'add_submenu_page' ) ) {
		 $plugin_page = add_options_page( 'Theme Checker Support', 'Theme Checker', 'manage_options', __FILE__, 'tc_support_page' );
		 add_action( 'admin_head-'. $plugin_page, 'tc_admin_styles' );
    }
}

function tc_admin_styles() {
	wp_enqueue_style( 'tc-admin-styles', plugins_url( 'css/tc-admin-styles.css', __FILE__ ) );
}

function tc_get_support_form() {
	global $current_user, $tc_version;
	get_currentuserinfo();
	// send fields for Theme Checker
	$version = $tc_version;
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
	if ( isset($_POST['tc_support']) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'theme-checker-nonce') ) die("Security check failed");	
		$request = stripslashes($_POST['support_request']);
		$has_donated = ( isset( $_POST['has_donated'] ) && $_POST['has_donated'] == 'on')?"Donor":"No donation";
		$has_read_faq = ( isset( $_POST['has_read_faq'] ) && $_POST['has_read_faq'] == 'on')?"Read FAQ":true; // has no faq, for now.
		$subject = "Theme Checker support request. $has_donated";
		$message = $request ."\n\n". $data;
		// Get the site domain and get rid of www. from pluggable.php
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
				$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;		
		$from = "From: \"$current_user->display_name\" <$from_email>\r\nReply-to: \"$current_user->display_name\" <$current_user->user_email>\r\n";

		if ( !$has_read_faq ) {
			echo "<div class='message error'><p>".__('Please read the FAQ and other Help documents before making a support request.','theme-checker')."</p></div>";
		} else {
			wp_mail( "plugins@joedolson.com",$subject,$message,$from );
		
			if ( $has_donated == 'Donor' ) {
				echo "<div class='message updated'><p>".__('Thank you for supporting the continuing development of this plug-in! I\'ll get back to you as soon as I can.','theme-checker')."</p></div>";		
			} else {
				echo "<div class='message updated'><p>".__('I\'ll get back to you as soon as I can, after dealing with any support requests from plug-in supporters.','theme-checker')."</p></div>";				
			}
		}
	} else {
		$request = '';
	}
	echo "
	<form method='post' action='".admin_url( 'options-general.php?page=theme-checker/theme-checker.php' )."'>
		<div><input type='hidden' name='_wpnonce' value='".wp_create_nonce( 'theme-checker-nonce' )."' /></div>
		<div>
		<p>
		<input type='checkbox' name='has_donated' id='has_donated' value='on' /> <label for='has_donated'>".__( 'I have <a href="http://www.joedolson.com/donate/">made a donation to help support this plug-in</a>.','theme-checker' )."</label>
		</p>
		<p>
		<label for='support_request'>Support Request:</label><br /><textarea name='support_request' required aria-required='true' id='support_request' cols='80' rows='10'>".stripslashes($request)."</textarea>
		</p>
		<p>
		<input type='submit' value='".__('Send Support Request','theme-checker')."' name='tc_support' class='button-primary' />
		</p>
		<p>".
		__('The following additional information will be sent with your support request:','theme-checker')
		."</p>
		<div class='tc_support'>
		".wpautop($data)."
		</div>
		</div>
	</form>";
}