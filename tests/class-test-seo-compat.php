<?php
/**
 * Test Plugin Pings of IndexNow
 *
 * @package SimpleSearchSubmission
 */

namespace PWCC\SimpleSearchSubmission\Tests;

use PWCC\SimpleSearchSubmission\SEOCompat;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use WP_Mock;

/**
 * Test SEO Compatibility features.
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
	}

	/**
	 * Test is_noindex function with no SEO plugins active.
	 *
	 * @covers SEOCompat\is_noindex
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
	 * Test with Yoast SEO Mocked to index a post.
	 *
	 * @todo Mock both YoastSEO and that the function exists.
	 */
	public function test_is_noindex_yoastseo() {
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
