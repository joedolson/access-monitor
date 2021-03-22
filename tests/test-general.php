<?php
/**
 * Class Tests_Access_Monitor_General
 *
 * @package Access Monitor
 */

/**
 * Sample test case.
 */
class Tests_Access_Monitor_General extends WP_UnitTestCase {
	/**
	 * Verify not in debug mode.
	 */
	public function test_am_not_in_debug_mode() {
		// Verify that the constant AM_DEBUG is false.
		$this->assertFalse( AM_DEBUG );
	}
}
