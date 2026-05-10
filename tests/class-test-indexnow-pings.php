<?php
/**
 * Test Plugin Pings of IndexNow
 *
 * @package SimpleSearchSubmission
 */

namespace PWCC\SimpleSearchSubmission\Tests;

use PWCC\SimpleSearchSubmission;
use WP_UnitTest_Factory;

/**
 * Test Plugin Pings of IndexNow
 */
class Test_IndexNow_Pings extends Base_Test_Case {
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
	 * Ensure a new post triggers a ping to IndexNow.
	 */
	public function test_ping_on_post_publish() {
		$this->factory->post->create(
			array(
				'post_status' => 'publish',
				'post_title'  => 'Test Post',
				'post_name'   => 'ping-on-post-publish',
				'post_date'   => '2025-06-01 12:00:00', // Set date for predictable URLs.
			)
		);

		$this->assertPing( home_url( '/2025/ping-on-post-publish/' ), 'Ping should include the post URL on publish.' );
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

		$this->assertPing( home_url( '/2025/test-post-publish/' ), 'Ping should include the post URL on update.' );
	}

	/**
	 * Ensure trashing a previously published post pings the old URL.
	 */
	public function test_ping_on_post_trash() {
		$post_id = self::$post_ids['publish'];

		wp_trash_post( $post_id );

		$this->assertPing( home_url( '/2025/test-post-publish/' ), 'Ping should include the post URL on trash.' );
		$this->assertNotPing( home_url( "/?p={$post_id}" ), 'Ping should not include the trashed post URL.' );
	}

	/**
	 * Ensure unpublishing a post pings the old URL.
	 */
	public function test_ping_on_unpublishing() {
		$post_id = self::$post_ids['publish'];

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);

		$this->assertPing( home_url( '/2025/test-post-publish/' ), 'Ping should include the post URL on unpublishing.' );
		$this->assertNotPing( home_url( "/?p={$post_id}" ), 'Ping should not include the unpublished post URL.' );
	}

	/**
	 * Ensure publishing a draft post pings the URL.
	 */
	public function test_ping_on_draft_publish() {
		$post_id = self::$post_ids['draft'];

		wp_publish_post( $post_id );

		$this->assertPing( home_url( '/2025/test-post-draft/' ), 'Ping should include the post URL on draft publish.' );
	}

	/**
	 * Ensure a private CPT does not ping.
	 */
	public function test_no_ping_on_private_cpt() {
		register_post_type(
			'private_cpt',
			array(
				'public'   => false,
				'supports' => array( 'title', 'editor' ),
			)
		);
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'private_cpt',
				'post_status' => 'publish',
			)
		);

		unregister_post_type( 'private_cpt' );
		$this->assertNotPing( get_permalink( $post_id ), 'Private CPT should not ping.' );
	}

	/**
	 * Ensure a custom private post status does not ping.
	 */
	public function test_no_ping_on_custom_private_post_status() {
		global $wp_post_statuses;
		register_post_status(
			'custom_private',
			array(
				'public' => false,
			)
		);
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'custom_private',
			)
		);

		// unregister_post_status doesn't exist.
		unset( $wp_post_statuses['custom_private'] );
		$this->assertNotPing( get_permalink( $post_id ), 'Custom private post status should not ping.' );
	}

	/**
	 * Ensure de-indexed URLs are pinged once only.
	 */
	public function test_no_duplicate_pings_on_deindexed_url() {
		$post_id = self::$post_ids['publish'];

		// Update the post slug.
		wp_update_post(
			array(
				'ID'        => $post_id,
				'post_name' => 'updated-test-post-publish',
			)
		);

		// Ensure both old and new URLs are pinged.
		$this->assertPing( home_url( '/2025/test-post-publish/' ), 'Ping should include the old post URL after slug change.' );
		$this->assertPing( home_url( '/2025/updated-test-post-publish/' ), 'Ping should include the new post URL after slug change.' );
		// Ensure last ping URL is the updated URL.
		$last_ping_url = SimpleSearchSubmission\get_last_post_ping_url( $post_id );
		$this->assertSame( home_url( '/2025/updated-test-post-publish/' ), $last_ping_url, 'Last ping URL should be the updated URL.' );

		// Clear the ping list.
		$this->pings = array();

		// Update the post content.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => 'Another update to Test Post',
			)
		);

		// Ensure only the new URL is pinged again.
		$this->assertNotPing( home_url( '/2025/test-post-publish/' ), 'Old post URL should not be pinged again after content update.' );
		$this->assertPing( home_url( '/2025/updated-test-post-publish/' ), 'New post URL should be pinged again after content update.' );
		// Ensure last ping URL is the updated URL.
		$last_ping_url = SimpleSearchSubmission\get_last_post_ping_url( $post_id );
		$this->assertSame( home_url( '/2025/updated-test-post-publish/' ), $last_ping_url, 'Last ping URL should be the updated URL after the second ping.' );
	}

	/**
	 * Ensure de-indexed URLs are pinged only once for private post type transitions.
	 */
	public function test_no_duplicate_pings_on_private_post_type_transition() {
		$post_id = self::$post_ids['publish'];

		// Set the post to a private status.
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'private',
			)
		);

		// Ensure the old URL is pinged.
		$this->assertPing( home_url( '/2025/test-post-publish/' ), 'Old post URL should be pinged after status change.' );

		// Clear the ping list.
		$this->pings = array();

		// Set the post to a draft.
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);
		// Ensure the old URL is not pinged again.
		$this->assertNotPing( home_url( '/2025/test-post-publish/' ), 'Old post URL should not be pinged again after second status change.' );
	}

	/**
	 * Ensure previously deindexed URLs that now redirect are re-pinged.
	 */
	public function test_republished_post_with_slug_change_pings_old_urls() {
		$post_id = self::$post_ids['publish'];

		// Set the post to draft.
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);

		// Ensure the old post URL is pinged.
		$this->assertPing( home_url( '/2025/test-post-publish/' ), 'Old post URL should be pinged after unpublishing.' );

		// Clear the ping list.
		$this->pings = array();

		// Update the post slug and re-publish all in one update.
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_name'   => 'final-test-post-publish',
				'post_status' => 'publish',
			)
		);

		// Ensure both URLs are pinged as the old URL is now a redirect.
		$this->assertPing( home_url( '/2025/test-post-publish/' ), 'Old post URL should be pinged after slug and status update to index the redirect.' );
		$this->assertPing( home_url( '/2025/final-test-post-publish/' ), 'New post URL should be pinged after slug and status update.' );
	}

	/**
	 * Ensure updating the pinged URLs lists appends the new URLs correctly.
	 */
	public function test_update_post_ping_urls_appends_correctly() {
		$post_id = self::$post_ids['publish'];

		// Update the post slug for the first time.
		wp_update_post(
			array(
				'ID'        => $post_id,
				'post_name' => 'first-update-test-post-publish',
			)
		);

		$last_ping_url = SimpleSearchSubmission\get_last_post_ping_url( $post_id );
		$this->assertSame( home_url( '/2025/first-update-test-post-publish/' ), $last_ping_url, 'Last ping URL should be the first updated URL.' );

		// Update the post slug for the second time.
		wp_update_post(
			array(
				'ID'        => $post_id,
				'post_name' => 'second-update-test-post-publish',
			)
		);

		$last_ping_url = SimpleSearchSubmission\get_last_post_ping_url( $post_id );
		$this->assertSame( home_url( '/2025/second-update-test-post-publish/' ), $last_ping_url, 'Last ping URL should be the second updated URL.' );

		// Restore the first updated URL.
		wp_update_post(
			array(
				'ID'        => $post_id,
				'post_name' => 'first-update-test-post-publish',
			)
		);

		$last_ping_url = SimpleSearchSubmission\get_last_post_ping_url( $post_id );
		$this->assertSame( home_url( '/2025/first-update-test-post-publish/' ), $last_ping_url, 'Last ping URL should be the restored first updated URL.' );
	}

	/**
	 * Ensure URL list never contains duplicates.
	 */
	public function test_update_post_ping_urls_no_duplicates() {
		$post_id = self::$post_ids['publish'];

		// Update the ping list (no need to actually ping).
		SimpleSearchSubmission\add_post_ping_urls( $post_id, array( home_url( '/2025/test-post-publish/' ) ) );
		SimpleSearchSubmission\add_post_ping_urls( $post_id, array( home_url( '/2025/test-post-publish-two/' ) ) );
		SimpleSearchSubmission\add_post_ping_urls( $post_id, array( home_url( '/2025/test-post-publish-two/' ) ) );
		SimpleSearchSubmission\add_post_ping_urls( $post_id, array( home_url( '/2025/test-post-publish/' ) ) );

		// Ensure there are no duplicates.
		$ping_urls = SimpleSearchSubmission\get_post_ping_urls( $post_id );
		$this->assertCount( 2, $ping_urls, 'Ping URL list should not contain both URLs.' );

		$expected = array(
			home_url( '/2025/test-post-publish-two/' ),
			home_url( '/2025/test-post-publish/' ),
		);
		$this->assertSame( $expected, $ping_urls, 'Ping URL list should not contain duplicates.' );
	}
}
