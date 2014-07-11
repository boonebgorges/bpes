<?php

// Create the group index on group creation
add_action( 'groups_created_group', 'bpes_group_index' );

// Recreate the group index on group modification events
add_action( 'groups_settings_updated', 'bpes_group_index' );
add_action( 'updated_group_meta', 'bpes_group_index_on_groupmeta', 10, 3 );

/**
 * Group indexer.
 *
 * @param int $group_id
 */
function bpes_group_index( $group_id ) {
	return bpes_index( array(
		'type' => 'group',
		'item_id' => $group_id,
	) );
}

/**
 * Wrapper for group index on groupmeta update.
 *
 * @param int $meta_id
 * @param int $object_id
 * @param string $meta_key
 */
function bpes_group_index_on_groupmeta( $meta_id, $object_id, $meta_key ) {
	if ( 'last_activity' === $meta_key ) {
		return bpes_group_index( $object_id );
	}
}
