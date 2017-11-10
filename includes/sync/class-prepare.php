<?php
/**
 * Prepares data to sync with Elasticsearch.
 *
 * @package  ElasticSync
 */

namespace ElasticSync\Sync;

/**
 * Class to prepare data to sync with Elasticsearch.
 */
class Prepare {
	/**
	 * Parent plugin class
	 *
	 * @var ElasticSync
	 * @since  0.1.0
	 */
	protected $elasticsync = null;

	/**
	 * Maximum value for Java integers.
	 *
	 * @var int
	 * @since  0.1.0
	 */
	const MAX_JAVA_INT = 9223372036854775807;

	/**
	 * Prepare a post for sync'ing with ES.
	 *
	 * @param  int $post_id The ID of the post to prepare.
	 * @return array        The post args to sync.
	 */
	protected function prepare( $post_id ) {
		// Get the WP_Post object.
		$post = get_post( $post_id );

		// Bail early if no post.
		if ( empty( $post->ID ) ) {
			return [];
		}
		// Get date info from post.
		$post_date = $post->post_date;
		$post_date_gmt = $post->post_date_gmt;
		$post_modified = $post->post_modified;
		$post_modified_gmt = $post->post_modified_gmt;

		// Get comment data.
		$comment_count = absint( $post->comment_count );
		$comment_status = absint( $post->comment_status );

		// Get ping status and menu order.
		$ping_status = absint( $post->ping_status );
		$menu_order = absint( $post->menu_order );

		// Prepared content for post.
		$post_args = array(
			'post_id'           => $post_id,
			'ID'                => $post_id,
			'post_author'       => $this->get_author_data( $post ),
			'post_date'         => $post_date,
			'post_date_gmt'     => $post_date_gmt,
			'post_title'        => $this->prepare_text_content( get_the_title( $post_id ) ),
			'post_excerpt'      => $this->prepare_text_content( $post->post_excerpt ),
			'post_content'      => $this->prepare_text_content( apply_filters( 'the_content', $post->post_content ) ),
			'post_status'       => $post->post_status,
			'post_name'         => $post->post_name,
			'post_modified'     => $post_modified,
			'post_modified_gmt' => $post_modified_gmt,
			'post_parent'       => $post->post_parent,
			'post_type'         => $post->post_type,
			'post_mime_type'    => $post->post_mime_type,
			'permalink'         => get_permalink( $post_id ),
			// 'terms'             => $this->prepare_terms( $post ),
			'post_meta'         => $this->prepare_meta( $post ),
			'date_terms'        => $this->prepare_date_terms( $post_date ),
			'comment_count'     => $comment_count,
			'comment_status'    => $comment_status,
			'ping_status'       => $ping_status,
			'menu_order'        => $menu_order,
			'guid'				=> $post->guid,
		);

		/**
		 * Filter to modify parameters to sync to Elasticsearch.
		 *
		 * Allows for modifying data getting indexed in Elasticsearch.
		 *
		 * @since 0.1.0
		 *
		 * @param array    Original arguments to sync.
		 * @param int      The post ID being sync'd.
		 * @param \WP_post The post object.
		 */
		$post_args = apply_filters( 'es_sync_args', $post_args, $post_id, $post );

		// Prepare meta values for indexing in Elasticsearch.
		$post_args['meta'] = $this->prepare_meta_types( $post_args['meta'] );

		echo '<pre>'; var_dump( $post_args ); die;

	}

	/**
	 * Prepare text for ES: Strip html, strip line breaks, etc.
	 *
	 * @param  string $content The content to prepare.
	 * @return string
	 */
	protected function prepare_text_content( $content ) {
		// Bail early if not a string.
		if ( ! is_string( $content ) ) {
			return $content;
		}

		// Strip tags.
		$content = strip_tags( $content );

		// Remove line breaks.
		$content = preg_replace( '#[\n\r]+#s', ' ', $content );

		return $content;
	}

	/**
	 * Get author data for a post.
	 *
	 * @param  \WP_Post $post The post that's being prepared.
	 * @return array         Author data for the post.
	 */
	protected function get_author_data( \WP_Post $post ) {
		$author = get_userdata( $post->post_author );

		// Bail early if no author data.
		if ( ! ( $author instanceof WP_User ) ) {
			return  array(
				'raw'          => '',
				'login'        => '',
				'display_name' => '',
				'id'           => '',
			);
		}

		return array(
			'raw'          => $author->user_login,
			'login'        => $author->user_login,
			'display_name' => $author->display_name,
			'id'           => $author->ID,
		);
	}

	/**
	 * Prepare date terms to send to ES.
	 *
	 * @param string $date The timestamp to convert.
	 * @return array 	   Arguments for the timestamp.
	 */
	protected function prepare_date_terms( $date ) {
		$timestamp = strtotime( $date );

		// Bail early if not a timestamp.
		if ( empty( $timestamp ) ) {
			return [];
		}

		// Date arguments for sync.
		return array(
			'year' => (int) date( 'Y', $timestamp ),
			'month' => (int) date( 'm', $timestamp ),
			'week' => (int) date( 'W', $timestamp ),
			'dayofyear' => (int) date( 'z', $timestamp ),
			'day' => (int) date( 'd', $timestamp ),
			'dayofweek' => (int) date( 'w', $timestamp ),
			'dayofweek_iso' => (int) date( 'N', $timestamp ),
			'hour' => (int) date( 'H', $timestamp ),
			'minute' => (int) date( 'i', $timestamp ),
			'second' => (int) date( 's', $timestamp ),
			'm' => (int) (date( 'Y', $timestamp ) . date( 'm', $timestamp ) ), // yearmonth.
		);
	}

	/**
	 * Prepare meta for sync'ing a post with Elasticsearch.
	 *
	 * @param  \WP_Post $post The post to prepare meta for.
	 * @return array          Meta to index.
	 */
	protected function prepare_meta( \WP_Post $post ) {
		// Holds all prepared meta.
		$prepared_meta = [];

		// Get all meta for the post.
		$meta = (array) get_post_meta( $post_id );

		// Bail early if no meta for the post.
		if ( empty( $meta ) ) {
			return $prepared_meta;
		}

		/**
		 * Filter index-able private meta
		 *
		 * Allows for specifying private meta keys that may be indexed in the same manor as public meta keys.
		 *
		 * @since 0.1.0
		 *
		 * @param         array Array of index-able private meta keys.
		 * @param WP_Post $post The current post to be indexed.
		 */
		$allowed_protected_keys = apply_filters( 'es_prepare_meta_allowed_protected_keys', array(), $post );

		/**
		 * Filter non-indexed public meta
		 *
		 * Allows for specifying public meta keys that should be excluded from the ElasticPress index.
		 *
		 * @since 0.1.0
		 *
		 * @param         array Array of public meta keys to exclude from index.
		 * @param WP_Post $post The current post to be indexed.
		 */
		$excluded_public_keys = apply_filters( 'es_prepare_meta_excluded_public_keys', array(), $post );

		// Loop through meta and prepare allowed terms.
		foreach ( $meta as $key => $value ) {
			// Check if meta is allowed to be indexed in Elasticsearch.
			$allow_index = is_protected_meta( $key )
				? in_array( $key, (array) $allowed_protected_keys, true )
				: ! in_array( $key, (array) $excluded_public_keys, true );

			/**
			 * Filter if meta is allowed to be indexed.
			 *
			 * Allows for specifying if meta should be indexed in Elasticsearch.
			 *
			 * @since 0.1.0
			 *
			 * @param boolean Whether or not meta key is allowed to be indexed.
			 * @param string  The meta key in question.
			 * @param string  The meta value for the key.
			 * @param WP_Post The current post to be indexed.
			 */
			$allow_index = apply_filters( 'es_prepare_meta_allow_index', $allow_index, $key, $value, $post );

			// Skip if we don't want this meta indexed.
			if ( false === $allow_index ) {
				continue;
			}

			// Add to the list of prepared meta.
			$prepared_meta[ $key ] = maybe_unserialize( $value );
		}

		return $prepared_meta;
	}

	/**
	 * Prepare meta types for indexing.
	 *
	 * @param  array $meta Meta being indexed.
	 * @return array       Formatted meta for indexing.
	 */
	protected function prepare_meta_types( $meta ) {
		// Loop through and prepare types.
		foreach ( $meta as $key => $value ) {
			$meta['key'] = array_map( array( $this, 'prepare_meta_values' ), (array) $value );
		}

		return $meta;
	}

	/**
	 * Prepare meta values for indexing in Elasticsearch.
	 *
	 * @param  array $value  Meta values to prepare.
	 * @return array         Prepared meta values.
	 */
	protected function prepare_meta_values( $value ) {
		// Holds all meta types.
		$meta_types = [];

		// Serialize the value if an array or object.
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = serialize( $value );
		}
		// Set value, raw, and boolean.
		$meta_types['value']   = $value;
		$meta_types['raw']     = $value;
		$meta_types['boolean'] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );

		// Add numeric values.
		if ( is_numeric( $value ) ) {
			$long = intval( $value );

			if ( $max_java_int_value < $long ) {
				$long = $max_java_int_value;
			}

			$double = floatval( $value );

			if ( ! is_finite( $double ) ) {
				$double = 0;
			}

			$meta_types['long']   = $long;
			$meta_types['double'] = $double;
		}

		// Add string values.
		if ( is_string( $value ) ) {
			// Get the timestamp.
			$timestamp = strtotime( $value );

			// Default values.
			$date     = '1971-01-01';
			$datetime = '1971-01-01 00:00:01';
			$time     = '00:00:01';

			if ( ! ( false === $timestamp ) ) {
				$date     = date_i18n( 'Y-m-d', $timestamp );
				$datetime = date_i18n( 'Y-m-d H:i:s', $timestamp );
				$time     = date_i18n( 'H:i:s', $timestamp );
			}

			// Set meta types.
			$meta_types['date']     = $date;
			$meta_types['datetime'] = $datetime;
			$meta_types['time']     = $time;
		}

		return $meta_types;
	}
	/**
	 * Prepare terms for sync'ing a post with Elasticsearch.
	 *
	 * @param  \WP_Post $post The post to prepare terms for.
	 * @return array          Terms to index.
	 */
	protected function prepare_terms( $post ) {
		// Get a list of taxonomies the post is in.
		$taxonomies = get_object_terms( $post->post_type, 'objects' );
	}

}
