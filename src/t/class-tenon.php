<?php
/**
 * Tenon class
 *
 * @category Tenon
 * @package  Access Monitor
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/access-monitor/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submit a request against the Tenon API for accessibility testing.
 *
 * @category  Tenon
 * @package   Access Monitor
 * @author    Joe Dolson
 * @copyright 2015
 * @license   GPLv2 or later
 * @version   1.0
 *
 * @param string $url URL to post request to.
 * @param array  $opts Options to pas to tenon.
 * @param object $tenon_response Server response from tenon.
 */
class Tenon {
	protected $url, $opts;
	public $tenon_response;

	/**
	 * Class constructor
	 *
	 * @param string $url the API url to post your request to.
	 * @param array  $opts options for the request.
	 */
	public function __construct( $url, $opts ) {
		$this->url            = $url;
		$this->opts           = $opts;
		$this->tenon_response = '';
	}

	/**
	 * Submits the HTML source for testing
	 *
	 * @param boolean $print_info whether or not to print the output from curl_getinfo (usually for debugging only).
	 *
	 * @return string the results, formatted as JSON
	 */
	public function submit( $print_info = false ) {

		if ( true == $print_info ) {
			echo '<h2>Options Passed To Tenon</h2><pre><br>';
			var_dump( $this->opts );
			echo '</pre>';
		}

		$args = array(
			'method'    => 'POST',
			'body'      => $this->opts,
			'headers'   => '',
			'sslverify' => false,
			'timeout'   => 60,
		);
		$result = wp_remote_post( $this->url, $args );

		if ( true == $print_info ) {
			echo '<h2>Query Info</h2><pre>';
			print_r( $result );
			echo '</pre>';
		}

		if ( is_wp_error( $result ) ) {
			$this->tenon_response = $result->errors;
		} else {
			// the test results.
			$this->tenon_response = $result;
		}
	}
}
