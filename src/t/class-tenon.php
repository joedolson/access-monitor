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
 *    This class submits a request against the Tenon API for automatic
 *    accessibility testing.
 *
 *    Essentially all this does is populate a variable, $tenonResponse, with the JSON response from Tenon
 *
 */
class Tenon {
    protected $url, $opts;
    public $tenonResponse;

    /**
     * Class constructor
     *
     * @param string $url the API url to post your request to.
     * @param array  $opts options for the request.
     */
    public function __construct( $url, $opts ) {
        $this->url           = $url;
        $this->opts          = $opts;
        $this->tenonResponse = '';
    }

    /**
     * Submits the HTML source for testing
     *
     * @param boolean $printInfo whether or not to print the output from curl_getinfo (usually for debugging only).
     *
     * @return string the results, formatted as JSON
     */
    public function submit( $printInfo = false ) {

        if ( true == $printInfo ) {
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

        if ( true == $printInfo ) {
            echo '<h2>Query Info</h2><pre>';
            print_r( $result );
            echo '</pre>';
        }

		if ( is_wp_error( $result ) ) {
			$this->tenonResponse = $result->errors;
		} else {
			//the test results.
			$this->tenonResponse = $result;
		}
    }
}
