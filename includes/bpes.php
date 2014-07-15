<?php

// wp-cli command
if ( defined( 'WP_CLI' ) ) {
	require 'command.php';
}

// wpes library
require BPES_DIR . 'lib/class.wpes-abstract-field-builder.php';
require BPES_DIR . 'lib/class.wpes-abstract-document-builder.php';
require BPES_DIR . 'lib/class.wpes-abstract-iterator.php';

// bpes base classes
require BPES_DIR . 'includes/class.bpes-abstract-type.php';
require BPES_DIR . 'includes/class.bpes-search-request.php';

function bpes_content_types() {
	$types = array(
		'group' => array(
			'name' => 'Group',
			'field_builder' => 'BPES_Group_Field_Builder',
			'document_builder' => 'BPES_Group_Document_Builder',
			'iterator' => 'BPES_Group_Iterator',
			'enabled' => bp_is_active( 'groups' ),
		),
	);

	return $types;
}

function bpes_content_type( $type ) {
	$types = bpes_content_types();

	if ( isset( $types[ $type ] ) ) {
		return $types[ $type ];
	} else {
		return false;
	}
}

// bpes content types

foreach ( bpes_content_types() as $type => $type_data ) {
	if ( ! $type_data['enabled'] ) {
		continue;
	}

	require BPES_DIR . 'includes/types/' . $type . '/' . $type . '.php';
	require BPES_DIR . 'includes/types/' . $type . '/class.bpes-' . $type . '-field-builder.php';
	require BPES_DIR . 'includes/types/' . $type . '/class.bpes-' . $type . '-document-builder.php';
	require BPES_DIR . 'includes/types/' . $type . '/class.bpes-' . $type . '-iterator.php';
}

/**
 * Index a single item.
 */
function bpes_index( $args = array() ) {
	$r = wp_parse_args( $args, array(
		'type' => '',
		'item_id' => 0,
		'delete_existing' => true,
	) );

	if ( empty( $r['type'] ) ) {
		// todo better error reporting
		return false;
	}

	$type = $r['type'];

	$registered_types = bpes_content_types();
	if ( ! isset( $registered_types[ $type ]['document_builder'] ) || ! class_exists( $registered_types[ $type ]['document_builder'] ) ) {
		return false;
	}

	$document_builder = new $registered_types[ $type ]['document_builder'];

	if ( $r['delete_existing'] ) {
		bpes_request( array(
			'method' => 'DELETE',
			'index'  => 'bp',
			'type'   => $document_builder->get_type(),
			'id'     => $document_builder->get_id( $r ),
		) );
	}

	$uri_paths = array(
		'bp', // index
		$document_builder->get_type(),
		$document_builder->get_id( $r ),
	);

	$request_args = array(
		'method' => 'PUT',
		'uri_paths' => $uri_paths,
		'data'   => $document_builder->doc( $r ),
	);

	$response = bpes_request( $request_args );

	if ( isset( $response->created ) && 1 == $response->created ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Bulk indexer.
 */
function bpes_bulk_index( $args = array() ) {
	$r = wp_parse_args( $args, array(
		'name' => false,
		'index' => null,
		'type' => false,
		'start' => 0,
	) );

	$types = explode( ',', $r['type'] );
	$registered_types = bpes_content_types();

	foreach ( $types as $type ) {
		if ( empty( $registered_types[ $type ]['document_builder'] ) ) {
			continue;
		}

		$document_builder = new $registered_types[ $type ]['document_builder']();

		$iterator = new $registered_types[ $type ]['iterator']();

		$docs = array();
		while ( ! $iterator->is_done() ) {
			$ids = $iterator->get_ids( array() );

			foreach ( $ids as $id ) {
				$ndoc = bpes_index( array(
					'item_id' => $id,
					'type' => $type,
				) );

				if ( false !== $ndoc ) {
					$docs[] = $id;
				}
			}
		}
	}

	if ( ! empty( $docs ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Perform an ES search.
 */
function bpes_search( $args = array() ) {
	$r = wp_parse_args( $args, array(
		'index' => 'bp',
		'type' => '',
		'query' => array(),
	) );

	$uri_paths = array(
		$r['index'], // index
		$r['type'],
		'_search',
	);

	$request_args = array(
		'method' => 'POST',
		'uri_paths' => $uri_paths,
		'data' => $r['query'],
	);

	$response = bpes_request( $request_args );

	$retval = array(
		'hits' => $response->hits->hits,
		'total' => $response->hits->total,
	);

	return $retval;
}

/**
 * Make an ES request using the WP HTTP API, and parse the results.
 *
 * @param array $args {
 *     @type string $method A valid HTTP request method. @todo Sanitize and
 *           provide default.
 *     @type array $uri_paths Ordered array of paths to be appended to endpoint
 *           base URI to form the request URI. Example:
 *               array(
 *                   'bp',    // index name
 *                   'group', // item type
 *                   '54',    // item ID
 *               )
 *     @type array $data
 * }
 * @return mixed
 */
function bpes_request( $args = array() ) {
	$ep = 'http://localhost:9200';

	// Build request URL
	$r_url = trailingslashit( $ep );
	foreach ( $args['uri_paths'] as $up ) {
		$r_url .= trailingslashit( $up );
	}

	$request = wp_remote_request( $r_url, array(
		'method' => $args['method'],
		'body' => json_encode( $args['data'] ),
	) );
	$body = json_decode( wp_remote_retrieve_body( $request ) );

	return $body;
}
