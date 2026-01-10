<?php
/**
 * Base tests Case class for Simple Search Submission tests.
 *
 * @package SimpleSearchSubmission
 */

namespace PWCC\SimpleSearchSubmission\Tests;

use WP_UnitTestCase;

/**
 * Base Test Case class for Simple Search Submission tests.
 *
 * Used for HTTP mocks and custom assertions.
 */
class Base_Test_Case extends WP_UnitTestCase {
	/**
	 * Array to store pinged URLs.
	 *
	 * This will be populated by the mocked_ping_response method
	 * when a ping is made to the IndexNow API.
	 *
	 * @var array
	 */
	protected $pings = array();

	/**
	 * Set up the test environment.
	 */
	public function set_up() {
		parent::set_up();
		$this->set_permalink_structure( '/%year%/%postname%/' );
		$this->pings = array();
		add_filter( 'pre_http_request', array( $this, 'mocked_ping_response' ), 10, 3 );
	}

	/**
	 * Mock the HTTP response for IndexNow pings.
	 *
	 * Runs on the 'pre_http_request' filter to intercept HTTP requests.
	 *
	 * @param mixed  $pre     The pre-HTTP request value.
	 * @param array  $request The HTTP request arguments.
	 * @param string $url     The URL being requested.
	 * @return array The mocked response.
	 */
	public function mocked_ping_response( $pre, $request, $url ) {
		if ( ! str_starts_with( $url, 'https://api.indexnow.org/indexnow' ) ) {
			return $pre; // Only mock requests IndexNow URLs.
		}

		// Store the pinged URLs.
		$body        = $request['body'] ?? array();
		$data        = json_decode( $body, true );
		$this->pings = $data['urlList'] ?? array();

		// Simulate a successful response.
		$response = array(
			'headers'  => array(),
			'body'     => array(),
			'response' => array(
				'code'    => 200, // HTTP status code.
				'message' => 'OK', // HTTP status message.
			),
			'cookies'  => array(),
			'filename' => '',
		);
		return $response;
	}

	/**
	 * Assert that a specific URL was pinged.
	 *
	 * @param string $expected_url The expected URL to be pinged.
	 * @param string $message      Optional. Message to display on failure.
	 */
	public function assertPing( $expected_url, $message = '' ) {
		$this->assertContains( $expected_url, $this->pings, $message );
	}

	/**
	 * Assert that a specific URL was not pinged.
	 *
	 * @param string $expected_url The expected URL to not be pinged.
	 * @param string $message      Optional. Message to display on failure.
	 */
	public function assertNotPing( $expected_url, $message = '' ) {
		$this->assertNotContains( $expected_url, $this->pings, $message );
	}
}
