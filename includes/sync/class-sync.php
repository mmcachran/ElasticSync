<?php
/**
 * Syncs WordPress posts with Elasticsearch.
 *
 * @package  ElasticSync
 */

namespace ElasticSync\Sync;

/**
 * Class to sync WordPress posts with Elasticsearch.
 */
class Sync {
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
		// Handles post related sync activities.
		$this->post = new \ElasticSync\Sync\Post( $this );
	} // END OF PLUGIN CLASSES FUNCTION

}
