<?php
/**
 * Test Plugin Pings of IndexNow
 *
 * @package NoFussIndexNow
 */

namespace PWCC\NoFussIndexNow\Tests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;

/**
 * Test Plugin Pings of IndexNow
 */
class Test_IndexNow_Pings extends WP_UnitTestCase {
	/**
	 * Array to store pinged URLs.
	 *
	 * This will be populated by the mocked_ping_response method
	 * when a ping is made to the IndexNow API.
	 *
	 * @var array
	 */
	private $pings = array();

	/**
	 * Array to store post IDs created for testing.
	 *
	 * Keyed by post status, value is the post ID.
	 *
	 * @var array
	 */
	private static $post_ids = array();

	/**
	 * Set up shared fixtures.
	 *
	 * @global \WP_Rewrite $wp_rewrite The WordPress rewrite object.
	 *
	 * @param WP_UnitTest_Factory $factory The factory to create test data.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		// WP_UnitTestCase::set_permalink_structure() is not a static method, so this the hard way.
		global $wp_rewrite;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		$wp_rewrite->flush_rules();

		$statuses = array( 'publish', 'draft', 'pending', 'private' );
		foreach ( $statuses as $status ) {
			$post_id                   = $factory->post->create(
				array(
					'post_status' => $status,
					'post_title'  => 'Test Post ' . ucfirst( $status ),
				)
			);
			self::$post_ids[ $status ] = $post_id;
		}
	}

	/**
	 * Set up the test environment.
	 */
	public function set_up() {
		parent::set_up();
		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
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

	/**
	 * Ensure a new post triggers a ping to IndexNow.
	 */
	public function test_ping_on_post_publish() {
		$post_id = $this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_title'  => 'Test Post',
			)
		);

		$this->assertPing( get_permalink( $post_id ), 'Ping should include the post URL on publish.' );
	}

	/**
	 * Ensure a post update triggers a ping to IndexNow.
	 */
	public function test_ping_on_post_update() {
		$post_id = self::$post_ids['publish'];

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => 'Updated Test Post',
			)
		);

		$this->assertPing( get_permalink( $post_id ), 'Ping should include the post URL on update.' );
	}

	/**
	 * Ensure trashing a previously published post pings the old URL.
	 */
	public function test_ping_on_post_trash() {
		$post_id            = self::$post_ids['publish'];
		$original_permalink = get_permalink( $post_id );

		wp_trash_post( $post_id );
		$trashed_permalink = get_permalink( $post_id );

		$this->assertPing( $original_permalink, 'Ping should include the post URL on trash.' );
		$this->assertNotPing( $trashed_permalink, 'Ping should not include the trashed post URL.' );
	}
}
