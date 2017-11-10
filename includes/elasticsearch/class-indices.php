<?php
/**
 * Handles indices in Elasticsearch
 *
 * @package  ElasticSync
 */

namespace ElasticSync\Elasticsearch;

/**
 * Class to handle indices in Elasticsearch.
 */
class Indices {
	/**
	 * Parent plugin class
	 *
	 * @var ElasticSync\Elasticsearch\Elasticsearch
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
	 * Parse an index name and extract the site ID.
	 *
	 * @since 0.1.0
	 *
	 * @param  string $index_name The index name.
	 * @return int                The site's ID.
	 */
	public function parse_site_id( $index_name ) {
		return (int) preg_replace( '#^.*\-([0-9]+)$#', '$1', $index_name );
	}

	/**
	 * Generates the index name for a site.
	 *
	 * @param  int $blog_id     The blog id to generate an index name for.
	 * @return string           The blog's index name.
	 */
	public function get_index_name( $blog_id = false ) {
		// Default to the current site.
		if ( false === $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		// Get the site's URL.
		$site_url = get_site_url( $blog_id );

		// If we have a site URL, sanitize it for the index name.
		$index_name = ! empty( $site_url ) ? $this->get_index_or_alias_from_url( $site_url ) : false;

		return apply_filters( 'es_index_name', $index_name, $blog_id );
	}

	/**
	 * Get the network alias name.
	 *
	 * @return string The network alias name in Elasticsearch.
	 */
	public function get_alias_name() {
		// Get the network URL.
		$network_url = network_alias_url();

		// If we have a network URL, sanitize it for the index name.
		$alias_name = ! empty( $network_url )
			? $this->get_index_or_alias_from_url( $network_url ) . '-global'
			: false;

		return apply_filters( 'es_network_alias', $alias_name );
	}

	/**
	 * Get the index or alias name from a URL.
	 *
	 * @param  string  $string  The string to get index/alias from.
	 * @param  integer $blog_id The blog's ID.
	 * @return string           The index or alias name.
	 */
	public function get_index_or_alias_from_url( $string, $blog_id = false ) {
		$index_name = preg_replace( '#https?://(www\.)?#i', '', $string );
		$index_name = preg_replace( '#[^\w]#', '', $index_name );

		return ! ( false === $blog_id ) ? $index_name . '-' . $blog_id : $index_name;
	}

}
