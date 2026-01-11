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

	/**
	 * Filter to preflight the post's No Index status.
	 *
	 * This allow for developers to provide custom logic to determine whether
	 * a post should be considered noindex or not. This filter can be used by
	 * developers of SEO plugins that are not directly supported by this function.
	 *
	 * @param bool|null $preflight_noindex The preflight noindex decision.
	 *                                     Default is `null`, meaning use the default logic.
	 * @param \WP_Post  $post              The post object.
	 */
	$preflight_noindex = apply_filters( 'simple_search_submission_pre_is_noindex', null, $post );

	if ( null !== $preflight_noindex ) {
		return (bool) $preflight_noindex;
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
	if (
		! function_exists( 'YoastSEO' )
		|| ! is_a( YoastSEO(), 'Yoast\\WP\\SEO\\Main' )
	) {
		return false; // Yoast SEO is not active.
	}

	if (
		! isset( YoastSEO()->meta )
		|| ! is_a( YoastSEO()->meta, 'Yoast\\WP\\SEO\\Surfaces\\Meta_Surface' )
	) {
		return false; // Yoast SEO meta class not available.
	}

	if ( ! method_exists( YoastSEO()->meta, 'for_post' ) ) {
		return false; // Yoast SEO meta method not available.
	}

	$post           = get_post( $post );
	$post_id        = $post->ID;
	$yoast_for_post = YoastSEO()->meta->for_post( $post_id );

	if (
		! is_a( $yoast_for_post, 'Yoast\\WP\\SEO\\Surfaces\\Values\\Meta' )
		|| ! isset( $yoast_for_post->robots )
	) {
		return false; // Yoast SEO post meta not available.
	}

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
	if (
		! function_exists( 'aioseo' )
		|| ! is_a( aioseo(), 'AIOSEO\\Plugin\\AIOSEO' )
	) {
		return false; // AIOSEO is not active.
	}

	if (
		! isset( aioseo()->meta )
		|| ! is_a( aioseo()->meta, 'AIOSEO\\Plugin\\Common\\Meta\\Meta' )
	) {
		return false; // AIOSEO meta class not available.
	}

	if (
		! isset( aioseo()->meta->metaData )
		|| ! is_a( aioseo()->meta->metaData, 'AIOSEO\\Plugin\\Common\\Meta\\MetaData' )
	) {
		return false; // AIOSEO metaData class not available.
	}

	if ( ! method_exists( aioseo()->meta->metaData, 'getMetaData' ) ) {
		return false; // AIOSEO getMetaData method not available.
	}

	if (
		! isset( aioseo()->helpers )
		|| (
			! is_a( aioseo()->helpers, 'AIOSEO\\Plugin\\Lite\\Utils\\Helpers' )
			&& ! is_a( aioseo()->helpers, 'AIOSEO\\Plugin\\Pro\\Utils\\Helpers' )
		)
	) {
		return false; // AIOSEO helpers class not available.
	}

	if ( ! method_exists( aioseo()->helpers, 'isPostTypeNoindexed' ) ) {
		return false; // AIOSEO isPostTypeNoindexed method not available.
	}

	$post    = get_post( $post );
	$post_id = $post->ID;

	$post_meta = aioseo()->meta->metaData->getMetaData( $post_id );

	if ( ! $post_meta->robots_default && isset( $post_meta->robots_noindex ) ) {
		return $post_meta->robots_noindex;
	}

	return aioseo()->helpers->isPostTypeNoindexed( get_post_type( $post ) );
}
