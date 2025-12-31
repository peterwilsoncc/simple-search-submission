<?php
/**
 * Simple Search Submission for IndexNow
 *
 * @package           SimpleSearchSubmission
 */

namespace PWCC\SimpleSearchSubmission\SEOCompat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	/*
	 * Check in order of number of active installs.
	 *
	 * To optimize performance for the majority of users, the
	 * checks are ordered by the popularity of the SEO plugins.
	 *
	 * Popularity data from the WordPress plugin repository.
	 */

	// Check for Yoast SEO noindex (10 million+).
	if ( is_yoast_noindex( $post ) ) {
		return true;
	}

	// Check All in One SEO noindex (3 million+).
	if ( is_aioseo_noindex( $post ) ) {
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

/**
 * Check All in One SEO noindex status.
 *
 * @param \WP_Post|int $post The post object or ID.
 * @return bool True if AIOSEO sets the post to noindex, false otherwise.
 */
function is_aioseo_noindex( $post ) {
	if ( ! function_exists( 'aioseo' ) ) {
		return false; // AIOSEO is not active.
	}

	$post    = get_post( $post );
	$post_id = $post->ID;

	$post_meta = aioseo()->meta->metaData->getMetaData( $post_id );

	if ( ! $post_meta->robots_default && isset( $post_meta->robots_noindex ) ) {
		return $post_meta->robots_noindex;
	}

	return aioseo()->helpers->isPostTypeNoindexed( get_post_type( $post ) );
}
