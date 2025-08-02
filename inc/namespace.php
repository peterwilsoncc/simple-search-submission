<?php
/**
 * No Fuss IndexNow
 *
 * @package           NoFussIndexNow
 */

namespace PWCC\NoFussIndexNow;

const PLUGIN_VERSION = '1.0.0';

/**
 * Bootstrap the plugin.
 *
 * Runs on the `after_setup_theme, 20` action.
 */
function bootstrap() {
	add_filter( 'query_vars', __NAMESPACE__ . '\\register_query_vars' );
	add_action( 'init', __NAMESPACE__ . '\\add_key_rewrite_rule' );
	add_action( 'parse_request', __NAMESPACE__ . '\\handle_key_file_request' );
	add_action( 'transition_post_status', __NAMESPACE__ . '\\maybe_ping_indexnow', 10, 3 );

	/**
	 * Filter to allow asynchronous pings.
	 *
	 * This can be used to defer the ping to IndexNow of content changes to run
	 * asynchronously via a wp-cron job. This can be useful to prevent delays when
	 * updating a post.
	 *
	 * By default, the plugin will ping IndexNow synchronously. It is recommended to
	 * ping asynchronously only if you are using a proper cron system. Read this guide
	 * as to how to set one up: https://peterwilson.cc/real-wordpress-cron-with-wp-cli/
	 *
	 * @since 1.0.0
	 *
	 * @param bool $notify_async Whether to notify IndexNow asynchronously.
	 *                           Default is false, meaning synchronous pings.
	 */
	$notify_async = apply_filters( 'pwcc/index-now/notify-async', false );

	if ( $notify_async ) {
		// Use the async ping action.
		add_action( 'pwcc/index-now/ping', __NAMESPACE__ . '\\async_ping_indexnow', 10, 1 );
		add_action( 'pwcc/index-now/async_ping', __NAMESPACE__ . '\\ping_indexnow', 10, 1 );
	} else {
		// Use the synchronous ping action.
		add_action( 'pwcc/index-now/ping', __NAMESPACE__ . '\\ping_indexnow', 10, 1 );
	}
}

/**
 * Register query vars.
 *
 * @param array $vars Array of query vars.
 * @return array Modified array of query vars.
 */
function register_query_vars( $vars ) {
	$vars[] = 'pwcc_indexnow_key';
	return $vars;
}

/**
 * Add rewrite rule for the IndexNow key file.
 */
function add_key_rewrite_rule() {
	$key = get_indexnow_key();

	add_rewrite_rule(
		'pwcc-indexnow-' . $key . '$',
		'index.php?pwcc_indexnow_key=' . $key,
		'top'
	);
}

/**
 * Handle the IndexNow key file request.
 *
 * @param \WP $wp WordPress instance.
 */
function handle_key_file_request( $wp ) {
	if ( empty( $wp->query_vars['pwcc_indexnow_key'] ) ) {
		return;
	}

	$key = get_indexnow_key();
	if ( ! $key || $wp->query_vars['pwcc_indexnow_key'] !== $key ) {
		$error = 'Invalid key: ' . get_query_var( 'pwcc_indexnow_key' );
		wp_die( esc_html( $error ), 'IndexNow Key Error', array( 'response' => 403 ) );
		return;
	}

	// Set the content type to text/plain.
	header( 'Content-Type: text/plain' );
	header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + YEAR_IN_SECONDS ) . ' GMT' );
	header( 'Cache-Control: public, max-age=' . YEAR_IN_SECONDS );
	header( 'X-Robots-Tag: noindex', true );

	// Output the key.
	echo esc_html( $key );
	exit;
}

/**
 * Generate and store the IndexNow key if it doesn't exist.
 *
 * @return string Unique site key.
 */
function get_indexnow_key(): string {
	$key = get_option( 'pwcc_indexnow_key' );

	if ( ! $key ) {
		// Generate a random key that meets IndexNow requirements.
		// Must be 8-128 hexadecimal characters (a-f, 0-9).
		$key = strtolower( wp_generate_password( 32, false, false ) );

		update_option( 'pwcc_indexnow_key', $key );

		// Flush the rewrite rules.
		flush_rewrite_rules();
	}

	return $key;
}

/**
 * Ping IndexNow when a post status changes.
 *
 * Runs on the `transition_post_status` action.
 *
 * @param string   $new_status New post status.
 * @param string   $old_status Old post status.
 * @param \WP_Post $post       Post object.
 */
function maybe_ping_indexnow( $new_status, $old_status, $post ): void {
	/**
	 * Filter to preflight the IndexNow ping.
	 *
	 * This allow for developers to provide custom logic to determine whether
	 * to ping IndexNow. Return `true` to ping, `false` to skip ping, or
	 * `null` to use the default logic.
	 *
	 * @param bool|null $preflight_ping The preflight ping decision.
	 *                                  Default is `null`, meaning use the default logic.
	 * @param string    $new_status     The new post status.
	 * @param string    $old_status     The old post status.
	 * @param \WP_Post  $post           The post object.
	 */
	$preflight_ping = apply_filters( 'pwcc/index-now/pre-maybe-ping-indexnow', null, $new_status, $old_status, $post );

	if ( is_bool( $preflight_ping ) ) {
		if ( true === $preflight_ping ) {
			/**
			 * Fire the action to ping IndexNow.
			 *
			 * @param \WP_Post $post The post object.
			 */
			do_action( 'pwcc/index-now/ping', $post );
		}
		return;
	}

	// Do not ping during an import.
	if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
		return;
	}

	/*
	 * Skip if post type isn't viewable.
	 *
	 * The post type shouldn't change under normal circumstances,
	 * so it's safe to assume that both the old and new post are
	 * not viewable.
	 */
	if ( ! is_post_type_viewable( $post->post_type ) ) {
		return;
	}

	/*
	 * Skip if both old an new statuses are private.
	 *
	 * The page will have been a 404 before and after.
	 *
	 * For pages that are newly a 404, we still ping IndexNow
	 * to encourage removal of the URL from search engines.
	 */
	if (
		! is_post_status_viewable( $new_status )
		&& ! is_post_status_viewable( $old_status )
	) {
		return;
	}

	/*
	 * Prevent double pings for block editor legacy meta boxes.
	 */
	if (
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		isset( $_GET['meta-box-loader'] )
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, Universal.Operators.StrictComparisons.LooseEqual -- form input.
		&& '1' == $_GET['meta-box-loader']
	) {
		return;
	}

	/** This action is documented in inc/namespace.php */
	do_action( 'pwcc/index-now/ping', $post );
}

/**
 * Ping IndexNow with the post URL.
 *
 * @param \WP_Post|int $post The post ID or object.
 */
function ping_indexnow( $post ) {

	$key = get_indexnow_key();
	if ( ! $key ) {
		return;
	}

	$url = get_permalink( $post );
	if ( ! $url ) {
		return;
	}

	/**
	 * Filters the URL or URLs to be submitted to IndexNow.
	 *
	 * @param array $url_list Array of URLs to submit.
	 *                        Default is an array with the single URL of the post.
	 */
	$url_list = apply_filters( 'pwcc/index-now/url-list', array( $url ) );

	if ( empty( get_option( 'permalink_structure' ) ) ) {
		$key_location = home_url( '?pwcc_indexnow_key=' . $key );
	} else {
		$key_location = trailingslashit( home_url( 'pwcc-indexnow-' . $key ) );
	}

	/**
	 * Filters the location of the IndexNow key file.
	 *
	 * @param string  $key_location The URL where the key file is located.
	 * @param array   $url_list     The list if URLs to be submitted.
	 * @param WP_Post $post         The post object.
	 */
	$key_location = apply_filters( 'pwcc/index-now/key-location', $key_location, $url_list, $post );

	$data    = array(
		'host'        => wp_parse_url( $key_location, PHP_URL_HOST ),
		'key'         => $key,
		'keyLocation' => $key_location,
		'urlList'     => $url_list,
	);
	$request = array(
		'body'    => wp_json_encode( $data, JSON_UNESCAPED_SLASHES ),
		'headers' => array(
			'Content-Type' => 'application/json; charset=utf-8',
		),
	);

	if ( wp_get_environment_type() !== 'production' ) {
		// In development, log the request for debugging.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
		error_log( 'IndexNow ping request: ' . print_r( $request, true ) );

		// Do not send the request in development.
		return;
	}

	// Ping IndexNow.
	$response = wp_remote_post(
		'https://api.indexnow.org/indexnow',
		$request
	);

	// Log the response for debugging. As per https://www.indexnow.org/documentation#response, either 200 or 202 is acceptable.
	if ( is_wp_error( $response ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
		error_log( 'IndexNow ping failed: ' . $response->get_error_message() . print_r( $request, true ) );
		return;
	}

	$status = wp_remote_retrieve_response_code( $response );
	if ( ! in_array( $status, array( 200, 202 ), true ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
		error_log( 'IndexNow ping failed: ' . $status . print_r( $request, true ) );
	}
}

/**
 * Asynchronous ping to IndexNow.
 *
 * @param \WP_Post|int $post The post ID or object to ping.
 */
function async_ping_indexnow( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}

	$post_id = $post->ID;

	if ( ! wp_next_scheduled( 'pwcc/index-now/async_ping', array( $post_id ) ) ) {
		wp_schedule_single_event( time() + 5, 'pwcc/index-now/async_ping', array( $post_id ) );
	}
}
