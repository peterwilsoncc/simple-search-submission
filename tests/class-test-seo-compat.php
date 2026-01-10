<?php
/**
 * Test Plugin Pings of IndexNow
 *
 * @package SimpleSearchSubmission
 */

namespace PWCC\SimpleSearchSubmission\Tests;

use PWCC\SimpleSearchSubmission\SEOCompat;
use WP_UnitTest_Factory;

/**
 * Test SEO Compatibility features.
 *
 * @covers SEOCompat\is_noindex
 */
class Test_SEO_Compat extends Base_Test_Case {

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
		$wp_rewrite->set_permalink_structure( '/%year%/%postname%/' );
		$wp_rewrite->flush_rules();

		$statuses = array( 'publish', 'draft', 'pending', 'private' );
		foreach ( $statuses as $status ) {
			$post_id                   = $factory->post->create(
				array(
					'post_status' => $status,
					'post_title'  => 'Test Post ' . ucfirst( $status ),
					'post_name'   => 'test-post-' . $status,
					'post_date'   => '2025-06-01 12:00:00', // Set date for predictable URLs.
				)
			);
			self::$post_ids[ $status ] = $post_id;
		}

		self::mock_no_index();
		foreach ( $statuses as $status ) {
			$post_id                               = $factory->post->create(
				array(
					'post_status' => $status,
					'post_title'  => 'Test No Indexed Post ' . ucfirst( $status ),
					'post_name'   => 'test-no-indexed-post-' . $status,
					'post_date'   => '2025-06-01 12:00:00', // Set date for predictable URLs.
				)
			);
			self::$post_ids[ "noindex-{$status}" ] = $post_id;
		}
	}

	/**
	 * Set up each test.
	 */
	public function set_up() {
		parent::set_up();
		// Remove any mock filters.
		remove_all_filters( 'simple_search_submission_pre_is_noindex' );
	}

	/**
	 * Create filter to mock a no index response.
	 */
	public static function mock_no_index() {
		add_filter( 'simple_search_submission_pre_is_noindex', '__return_true' );
	}

	/**
	 * Test is_noindex function with no SEO plugins active.
	 *
	 * @covers SEOCompat\is_yoast_noindex
	 * @covers SEOCompat\is_aioseo_noindex
	 *
	 * @dataProvider data_all_post_statuses
	 *
	 * @param string $post_status The post status to test.
	 */
	public function test_is_noindex_no_seo_plugins( $post_status ) {
		$this->assertFalse( SEOCompat\is_noindex( self::$post_ids[ $post_status ] ), 'Post is expected to be indexable when no SEO plugin is available.' );
	}

	/**

	 * Ensure a new post triggers a ping to IndexNow.
	 */
	public function test_no_ping_on_noindex_publish() {
		$this->mock_no_index();

		$this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_title'  => 'Test Post',
				'post_name'   => 'no-ping-on-post-publish',
				'post_date'   => '2025-06-01 12:00:00', // Set date for predictable URLs.
			)
		);

		$this->assertNotPing( home_url( '/2025/no-ping-on-post-publish/' ), 'Noindexed posts should not be pinged on publish.' );
	}

	/**
	 * Test changing a noindexed post to indexed triggers a ping.
	 */
	public function test_ping_on_no_indexed_to_indexed() {
		$post_id = self::$post_ids['noindex-publish'];

		// Update post to publish status without noindex (ie, not mocked in this test).
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => 'Updated content',
			)
		);

		$this->assertPing( home_url( '/2025/test-no-indexed-post-publish/' ), 'Post changing from noindex to indexable should trigger a ping.' );
	}

	/**
	 * Test updating a noindexed post does not trigger a ping.
	 */
	public function test_no_ping_on_updated_noindexed_post() {
		$this->mock_no_index();
		$post_id = self::$post_ids['noindex-publish'];

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => 'Updated content',
			)
		);

		$this->assertNotPing( home_url( '/2025/test-no-indexed-post-publish/' ), 'Updating a noindexed post should not trigger a ping.' );
	}

	/**
	 * Test updating an indexed post to noindexed triggers a ping.
	 */
	public function test_ping_on_updated_indexed_to_noindexed_post() {
		$this->mock_no_index();
		$post_id = self::$post_ids['publish'];

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => 'Updated content',
			)
		);

		$this->assertPing( home_url( '/2025/test-post-publish/' ), 'Updating a post that is now noindexed should trigger a ping.' );
	}

	/**
	 * Test updating a private post to a noindexed public post does not trigger a ping.
	 */
	public function test_no_ping_on_private_to_noindexed_public_post() {
		$this->mock_no_index();
		$post_id = self::$post_ids['private'];

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => 'Updated content',
				'post_status'  => 'publish',
			)
		);
		$this->assertNotPing( home_url( '/2025/test-post-private/' ), 'Updating a private post to noindexed public post should not trigger a ping.' );
	}

	/**
	 * Data provider for test_is_noindex_no_seo_plugins.
	 *
	 * @return array
	 */
	public function data_all_post_statuses() {
		return array(
			array( 'publish' ),
			array( 'draft' ),
			array( 'pending' ),
			array( 'private' ),
		);
	}
}
