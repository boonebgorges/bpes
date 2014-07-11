<?php

/**
 * wp-cli command
 */

WP_CLI::add_command( 'bpes', 'BPES_CLI_Command' );

class BPES_CLI_Command extends WP_CLI_Command {
	/**
	 * Index
	 */
	public function index( $args, $assoc_args ) {
		$index_args = array();
		foreach ( $assoc_args as $aa_key => $aa_value ) {
			$key = str_replace( '-', '_', $aa_key );
			$index_args[ $key ] = $aa_value;
		}

		if ( ! empty( $index_args['item_id'] ) && 'all' === $index_args['item_id'] ) {
			$result = bpes_bulk_index( $index_args );
		} else {
			$result = bpes_index( $index_args );
		}

		if ( $result ) {
			WP_CLI::success( 'Doc added to ES index' );
		} else {
			WP_CLI::error( 'Could not add doc to ES index' );
		}
	}
}
