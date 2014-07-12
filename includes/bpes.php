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

	$request_args = array(
		'method' => 'PUT',
		'index'  => 'bp', // todo - this should be based on site URL maybe?
		'type'   => $document_builder->get_type(),
		'id'     => $document_builder->get_id( $r ),
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

function bpes_request( $args = array() ) {
	$ep = 'http://localhost:9200';

	// Build request URL
	$r_url = trailingslashit( $ep ) . trailingslashit( $args['index'] ) . trailingslashit( $args['type'] ) . trailingslashit( $args['id'] );

	$request = wp_remote_request( $r_url, array(
		'method' => $args['method'],
		'body' => json_encode( $args['data'] ),
	) );
	$body = json_decode( wp_remote_retrieve_body( $request ) );

	return $body;
}
