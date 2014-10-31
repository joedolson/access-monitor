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

define( 'WAVE_API_KEY', 'mw8fhDUr125' );
//define('TENON_API_URL', 'https://www.tenon.io/api/');
define( 'WAVE_API_URL', 'http://wave.webaim.org/api/request' );

require_once( 't/tenon.php' );
require_once( 't/wave.php' );

add_filter( 'the_content', 'tc_pass_query' );
function tc_pass_query( $content ) {
	if ( isset( $_GET['tenon'] ) && $_GET['tenon'] == 'true' ) {
		$permalink = get_the_permalink();
		$results = tc_query_tenon( array( 'url'=>$permalink ) );
		return $results.$content;
	}
	return $content;
}

function tc_query_tenon( $post ) {
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
		//$opts['level'] = 'AA'; -> this feature is currently broken; pending notice from Karl.
		$tenon = new tenon( TENON_API_URL, $opts );
		$tenon->submit( DEBUG );
		$body = $tenon->tenonResponse['body'];
		$results = tc_format_tenon( $body );
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
					<h2><span>$i</span>. $result->errorTitle</h2>
					<p>$result->errorDescription</p>
					<p>See: <a href='$result->ref'>$result->resultTitle</a> <span title='Links not currently live at Tenon.io'>[Note]</span></p>
					<h3>Error Source</h3>
					<pre lang='html'>".$result->errorSnippet."</pre>
					<p>Xpath: <code>$result->xpath</code></p>
					<div class='meta'>
						<span class='certainty $cert'>Certainty: %$result->certainty</span> &bull; 
						<span class='priority $prio'>Priority: %$result->priority</span>
					</div>				
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
	
	$opts['key'] = WAVE_API_KEY;
	$wave = new wave( WAVE_API_URL, $opts );
	$wave->submit( DEBUG );
	$body = $wave->waveResponse['body'];
	$results = tc_format_wave( $body, $opts['reporttype'] );
	return $results;
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


function tc_settings() {
	if ( isset( $_POST['tc_settings'] ) ) {
		$nonce=$_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce,'theme-checker-nonce') ) die( "Security check failed" );	
		$tenon_api_key = $_POST['tenon_api_key'];
		update_option( 'tc_settings', array( 'tenon_api_key'=>$tenon_api_key ) );
	}
	$settings = array_merge( array( 'tenon_api_key'=>'' ), get_option( 'tc_settings' ) );

	echo "
	<form method='post' action='".admin_url('options-general.php?page=theme-checker/theme-checker.php')."'>
		<div><input type='hidden' name='_wpnonce' value='".wp_create_nonce('theme-checker-nonce')."' /></div>
		<div><input type='hidden' name='tc_settings' value='update' /></div>
		<p>
			<label for='tenon_api_key'>".__( 'Tenon API Key', 'theme-checker' )."</label> <input type='text' name='tenon_api_key' id='tenon_api_key' value='". esc_attr( $settings['tenon_api_key'] ) ."' />
		</p>
		<div>";
		echo tc_setup_report();
		echo "<p>
		<input type='submit' value='".__('Update Settings','theme-checker')."' name='tc_settings' class='button-primary' />
		</p>
		</div>
	</form>";
}


function tc_setup_report() {
	$return = "";
	return $return;
}


function tc_support_page() {
	?>
	<div class="wrap" id="theme-checker">
		<h2><?php _e('Theme Checker','theme-checker'); ?></h2>
		<div id="tc_settings_page" class="postbox-container" style="width: 70%">
			<div class='metabox-holder'>

				<div class="tc-settings meta-box-sortables">
					<div class="postbox" id="settings">
						<h3><?php _e('Theme Checker Settings','theme-checker'); ?></h3>
						<div class="inside">
							<?php tc_settings(); ?>
						</div>
					</div>
				</div>
				<div class="tc-support meta-box-sortables">
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
				<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
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
		 add_action( 'admin_head-'. $plugin_page, 'tc_styles' );
    }
}

function tc_styles() {
	return;
}

function tc_get_support_form() {
	return;
}