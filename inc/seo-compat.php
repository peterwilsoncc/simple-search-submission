<?php
/**
 * Simple Search Submission for IndexNow
 *
 * @package           SimpleSearchSubmission
 */

namespace PWCC\SimpleSearchSubmission\SEOCompat;

/**
 * Determine if a post is set to noindex by an SEO plugin.
 *
 * @param \WP_Post|int $post The post object or ID.
 * @return bool True if the post is set to noindex, false otherwise.
 */
function is_noindex( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return true; // No post found, treat as noindex.
	}

	// Check for Yoast SEO noindex.
	if ( is_yoast_noindex( $post ) ) {
		return true;
	}

	return false; // Default to not noindex.
}

/**
 * Check Yoast SEO noindex status.
 *
 * @param \WP_Post|int $post The post object or ID.
 * @return bool True if Yoast SEO sets the post to noindex, false otherwise.
 */
function is_yoast_noindex( $post ) {
	if ( ! function_exists( 'YoastSEO' ) ) {
		return false; // Yoast SEO is not active.
	}

	$post    = get_post( $post );
	$post_id = $post->ID;

	$robots = YoastSEO()->meta->for_post( $post_id )->robots;

	if ( ! isset( $robots['index'] ) ) {
		return false; // No index directive found.
	}

	return 'noindex' === $robots['index'];
}
