<?php
/**
 * Handles indices in Elasticsearch
 *
 * @package  ElasticSync
 */

namespace ElasticSync\Elasticsearch;

/**
 * Class to create indices in Elasticsearch.
 */
class Elasticsearch {
	/**
	 * Parent plugin class
	 *
	 * @var ElasticSync
	 * @since  0.1.0
	 */
	protected $elasticsync = null;

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 *
	 * @param \ElasticSync\ElasticSync $plugin The main plugin class.
	 * @return  void
	 */
	public function __construct( \ElasticSync\ElasticSync $plugin ) {
		// Register hooks.
		$this->elasticsync = $plugin;

		// Initialize classes.
		$this->plugin_classes();
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  0.0.0
	 */
	public function plugin_classes() {
		// Handles index related activities.
		$this->indices = new \ElasticSync\Elasticsearch\Indices( $this );

		// Handles requests to Elasticsearch.
		$this->request = new \ElasticSync\Elasticsearch\Request( $this );

	} // END OF PLUGIN CLASSES FUNCTION
}