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


	if ( class_exists( 'WPSEO_Meta' ) ) {
		// Yoast SEO.
		$meta_value = \WPSEO_Meta::get_value( 'meta-robots-noindex', $post->ID );
		if ( '1' === $meta_value ) {
			return true;
		}
	}

}
