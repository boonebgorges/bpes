<?php

/** Indexing tasks ***********************************************************/

// Create the group index on group creation
add_action( 'groups_created_group', 'bpes_group_index' );

// Recreate the group index on group modification events
add_action( 'groups_settings_updated', 'bpes_group_index' );
add_action( 'updated_group_meta', 'bpes_group_index_on_groupmeta', 10, 3 );

/** Search tasks *************************************************************/

// Hook into bp_has_groups() queries to use ES for search_terms
// @todo Can't do it this way. Screws up the template too bad. Will resort to
// something more brute force for now
add_filter( 'bp_after_has_groups_parse_args', 'bpes_bp_has_groups_args' );

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

/**
 * Filter the bp_has_groups() parsed arguments to use ES for search.
 *
 * @param array $r Argument array for bp_has_groups().
 * @return array
 */
function bpes_bp_has_groups_args( $r ) {
	if ( ! empty( $r['search_terms'] ) ) {
		$results = bpes_search_groups( $search_terms, array(
			'show_hidden' => $r['show_hidden'],
			'per_page' => $r['per_page'],
			'page' => $r['page'],
		) );

		$found_ids = wp_parse_id_list( wp_list_pluck( $results['hits'], '_id' ) );

		// Stash this data in the buddypress() global object for use in
		// later filters
		if ( count( $results['hits'] ) > $r['per_page'] ) {
			$results['hits'] = array_slice( $results['hits'], 0, $r['per_page'] );
		}

		buddypress()->groups->bpes_results = $results;

		if ( empty( $found_ids ) ) {
			$r['include'] = array( 0 );
		} else {
			// If other items have been passed to 'include', we should match
			// only the intersection of them
			if ( ! empty( $r['include'] ) ) {
				$r['include'] = array_intersect( wp_parse_id_list( $r['include'], $found_ids ) );
			} else {
				$r['include'] = $found_ids;
			}
		}

		// Don't use BP's native search
		$r['search_terms'] = false;

		// We'll need to filter the template object after the query
		add_filter( 'groups_get_groups', 'bpes_groups_get_groups' );
	}

	return $r;
}

/**
 * Filter the $groups_template global object after a groups search.
 *
 * @param bool $has_groups
 * @return bool
 */
function bpes_groups_get_groups( $groups ) {
	global $groups_template;

	if ( empty( buddypress()->groups->bpes_results ) ) {
		return $has_groups;
	}

	$results = buddypress()->groups->bpes_results;

	$groups_template->group_count = count( $results['hits'] );
	$groups_template->total_group_count = $results['total'];

	return $has_groups;
}

function bpes_search_groups( $search_terms, $args = array() ) {
	$r = array_merge( array(
		'show_hidden' => false,
		'per_page' => 20,
		'page' => 1,
	), $args );

	$terms_query = array(
		'query_string' => array(
			'query' => $search_terms,
			'fields' => array(
				'name^3',
				'description^2',
				'slug',
			),
		),
	);

	$status_filter = array(
		'or' => array(
			array(
				'term' => array(
					'status' => 'public',
				),
			),
			array(
				'term' => array(
					'status' => 'private',
				),
			),
		),
	);

	if ( $r['show_hidden'] ) {
		$status_filter['or'][] = array(
			'term' => array(
				'status' => 'hidden',
			),
		);
	}

	$search_query = new BPES_Search_Request( array(
		'query' => array(
			'filtered' => array(
				'query' => $terms_query,
				'filter' => $status_filter,
			),
		),
		'size' => $r['per_page'] * 2, // Round up in case of empty weirdness. Will trim later
		'from' => $r['per_page'] * ( $r['page'] - 1 ),
	) );

	$results = bpes_search( array(
		'query' => $search_query->get_query(),
		'type' => 'group',
		'index' => 'bp',
	) );

	return $results;
}

function bpes_search_clauses_for_groups( $args = array() ) {
	$r = array_merge( array(
		'show_hidden' => false,
	), $args );

	$status_filter = array(
		'or' => array(
			0 => array(
				'term' => array(
					'status' => 'public',
				),
			),
			1 => array(
				'term' => array(
					'status' => 'private',
				),
			),
		),
	);

	if ( $r['show_hidden'] ) {
		$status_filter['or'][] = array(
			'term' => array(
				'status' => 'hidden',
			),
		);
	}

	$type_filter = array(
		'type' => array(
			'value' => 'group',
		),
	);

	return array(
		'and' => array(
			0 => $type_filter,
			1 => $status_filter,
		),
	);

}

/**
 * Add group-specific clauses to the unified filter clauses
 */
function bpes_unified_filter_clauses_groups( $clauses ) {
	$clauses['or'][] = bpes_search_clauses_for_groups();
	return $clauses;
}
add_filter( 'bpes_unified_filter_clauses', 'bpes_unified_filter_clauses_groups' );
