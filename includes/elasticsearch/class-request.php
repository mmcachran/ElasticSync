<?php
/**
 * Helpers for the Indices.
 *
 * @package  ElasticSync
 */

namespace ElasticSync\Elasticsearch;

/**
 * Class to hold helpers for ElasticSync.
 */
class Request {
	/**
	 * Parent plugin class
	 *
	 * @var ElasticSync
	 * @since  0.1.0
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 *
	 * @param \ElasticSync\Elasticsearch\Elasticsearch $plugin The main plugin class.
	 * @return  void
	 */
	public function __construct( \ElasticSync\Elasticsearch\Elasticsearch $plugin ) {
		// Register hooks.
		$this->plugin = $plugin;
	}

	/**
	 * Wrapper for wp_remote_request
	 *
	 * This is a wrapper function for wp_remote_request to account for request failures.
	 *
	 * @since 0.1.0
	 *
	 * @param string $path Site URL to retrieve.
	 * @param array  $args Optional. Request arguments. Default empty array.
	 * @param array  $query_args Optional. The query args originally passed to WP_Query.
	 *
	 * @return WP_Error|array The response or WP_Error on failure.
	 */
	public function remote_request( $path, $args = array(), $query_args = array() ) {
		$query = array(
			'time_start'   => microtime( true ),
			'time_finish'  => false,
			'args'         => $args,
			'blocking'     => true,
			'failed_hosts' => array(),
			'request'      => false,
			'host'         => ep_get_host(),
			'query_args'   => $query_args,
		);

		// Add the API Header.
		$args['headers'] = $this->format_request_headers();

		$request = false;
		$failures = 0;

		// Optionally let us try back up hosts and account for failures.
		while ( true ) {
			$query['host'] = apply_filters( 'es_pre_request_host', $query['host'], $failures, $path, $args );
			$query['url'] = apply_filters( 'es_pre_request_url', esc_url( trailingslashit( $query['host'] ) . $path ), $failures, $query['host'], $path, $args );

			// Try the existing host to avoid unnecessary calls.
			$request = wp_remote_request( $query['url'], $args );

			if ( false === $request || is_wp_error( $request ) || ( isset( $request['response']['code'] ) && 0 !== strpos( $request['response']['code'], '20' ) ) ) {
				++$failures;

				if ( $failures >= apply_filters( 'es_max_remote_request_tries', 1, $path, $args ) ) {
					break;
				}
			} else {
				break;
			}
		}

		// Return now if we're not blocking, since we won't have a response yet.
		if ( isset( $args['blocking'] ) && ( false === $args['blocking'] ) ) {
			$query['blocking'] = true;
			$query['request']  = $request;
			$this->_add_query_log( $query );

			return $request;
		}

		$query['time_finish'] = microtime( true );
		$query['request'] = $request;
		$this->_add_query_log( $query );

		return $request;
	}

	/**
	 * Add appropriate request headers
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function format_request_headers() {
		$headers = array();

	    /**
	     * ES Shield Username & Password
	     * Adds username:password basic authentication headers
	     *
	     * Define the constant ES_SHIELD in your wp-config.php
	     * Format: 'username:password' (colon separated)
	     * Example: define( 'ES_SHIELD', 'es_admin:password' );
	     */
		if ( defined( 'ES_SHIELD' ) && ES_SHIELD ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( ES_SHIELD );
		}

		$headers = apply_filters( 'es_format_request_headers', $headers );

		return $headers;
	}

}
