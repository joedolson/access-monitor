<?php

/**
 *    This class submits a request against the WAVE API for automatic
 *    accessibility testing.
 *
 *    Essentially all this does is populate a variable, $waveResponse, with the JSON response from WAVE
 *
 */
class wave
{
    protected $url, $opts;
    public $waveResponse;

    /**
     * Class constructor
     *
     * @param   string $url  the API url to post your request to
     * @param    array $opts options for the request
     */
    public function __construct($url, $opts)
    {
        $this->url = $url;
        $this->opts = $opts;
        $this->waveResponse = '';
    }

    /**
     * Submits the HTML source for testing
     *
     * @param   bool $printInfo whether or not to print the output from curl_getinfo (usually for debugging only)
     *
     * @return    string    the results, formatted as JSON
     */
    public function submit( $printInfo = false ) {
		
		$wave_url = esc_url_raw( add_query_arg( $this->opts, $this->url ) );
		
		$args = array( 'method'=>'GET', 'body' => '', 'headers' => '', 'sslverify' => false, 'timeout' => 60 );
		$result = wp_remote_post( $wave_url, $args );

        if ( true == $printInfo ) {
            echo '<h2>Query Info</h2><pre>';
            print_r( $result );
            echo '</pre>';
        }
		
		if ( is_wp_error( $result ) ) {
			$this->waveResponse = $result->errors;
		} else {
			//the test results
			$this->waveResponse = $result;
		}
		
    }
}