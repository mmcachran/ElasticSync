<?php
/**
 * Autoloader for ElasticSync
 *
 * @package  ElasticSync
 */

namespace ElasticSync\Autoloader;

/**
 * Class for autoloader.
 */
class Autoloader {
	/**
	 * Autoloads files with classes when needed.
	 *
	 * @since  0.0.0
	 * @param  string $class_name Name of the class being requested.
	 */
	public static function autoload_classes( $class_name ) {
		// Bail early if the class name doesn't include our namespace.
		if ( false === strpos( $class_name, 'ElasticSync' ) ) {
			return;
		}

		// Get the file parts.
		$file_parts = explode( '\\', $class_name );
		$file_parts_count = count( $file_parts );

		// Default namespace to null until we can fill it in.
		$namespace = '';

		// Do a reverse loop through $file_parts to build the path to the file.
		for ( $i = $file_parts_count - 1; $i > 0; --$i ) {
			// Read the current component of the file part.
			$current = strtolower( $file_parts[ $i ] );
			$current = str_ireplace( '_', '-', $current );

			// If we're at the first entry, then we're at the filename.
			if ( $file_parts_count - 1 === $i ) {
				$filename = "class-{$current}.php";
			} elseif ( ! ( 'elasticsync' === $current ) ) {
				$namespace = $current . '/' . $namespace;
			}
		}

		// Determine what the full filepath is.
		$filepath = 'includes/' . trailingslashit( $namespace ) . $filename;

		// Include our file.
		\ElasticSync\ElasticSync::include_file( $filepath );
	}

}
