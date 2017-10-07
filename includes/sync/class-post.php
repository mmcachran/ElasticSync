<?php
/**
 * Handles post sync'ing in Elasticsearch
 *
 * @package  ElasticSync
 */

namespace ElasticSync\Sync;

/**
 * Class to handle post syncs in Elasticsearch.
 */
class Post extends \ElasticSync\Sync\Prepare {
	/**
	 * Parent plugin class
	 *
	 * @var \ElasticSync\Sync\Sync
	 * @since  0.1.0
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 *
	 * @param \ElasticSync\Sync\Sync $plugin The main plugin class.
	 * @return  void
	 */
	public function __construct( \ElasticSync\Sync\Sync $plugin ) {
		// Register hooks.
		$this->plugin = $plugin;

		// Initialize Hooks.
		$this->hooks();
	}

	/**
	 * Initiate hooks
	 *
	 * @since 0.1.0
	 *
	 * @return  void
	 */
	protected function hooks() {
		// Action for when a post is created or updated.
		add_action( 'wp_insert_post', array( $this, 'action_sync' ), 999, 3 );
	}

	/**
	 * Syncs a post with Elasticsearch.
	 *
	 * @param  int $post_id The ID of the post to sync.
	 * @return bool         True is sync'd, false otherwise.
	 */
	public function action_sync( $post_id ) {
		// Bail early if autosaving.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		// Bail early if this is a revision.
		if ( 'revision' === $post_type ) {
			return false;
		}

		$this->sync( $post_id );
	}

	/**
	 * Sync a post with Elasticsearch.
	 *
	 * @param  int     $post_id  The post id to sync.
	 * @param  boolean $blocking Whether this is a blocking request or not.
	 * @return boolean           True if indexed, false on failure.
	 */
	public function sync( $post_id, $blocking = true ) {
		// Bail early if post is not real.
		if ( false === get_post_status( $post_id ) ) {
			return false;
		}

		// Compile the post arguments for indexing.
		$post_args = $this->prepare( $post_id );

		echo '<pre>'; var_dump( $post_args ); die;
	}

}
