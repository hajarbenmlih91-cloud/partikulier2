<?php
/**
 * PHPStan-only symbols that exist when the maintenance script runs under WP-CLI.
 *
 * @package Partikulier
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	final class WP_CLI {
		public static function log( $message ): void {
		}
	}
}
