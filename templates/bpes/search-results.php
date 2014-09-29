<?php
$search_terms = '';
if ( ! empty( $_GET['st'] ) ) {
	$search_terms = urldecode( $_GET['st'] );
}

// Separate queries for each content type
$group_hits = bpes_search_groups( $search_terms );

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

$group_filter = array(
	'type' => array(
		'value' => 'group',
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

//var_Dump( $status_filter );

$filter = array(
	'or' => array(
		0 => array(
			'filter' => array(
				'and' => array(
					$status_filter,
					$group_filter,
				),
			),
		),
	),
);

/* Known working
$filter = array(
	'or' => array(
		0 => array(
			'and' => array(
				0 => $group_filter,
				1 => $status_filter,
			),
		),
		1 => array(
			'and' => array(
				0 => array(
					'term' => array(
						'status' => 'hidden',
					),
				),
			),
		),
	),
);*/

$filter = bpes_unified_filter_clauses();

$search_query = new BPES_Search_Request( array(
	'query' => array(
		'filtered' => array(
			'query' => $terms_query,
			'filter' => $filter,
		),
	),
) );

/*
$search_query = new BPES_Search_Request( array(
	'query' => array(
		'filtered' => array(
			'query' => $terms_query,
			'filter' => $status_filter,
		),
	),
//	'size' => $r['per_page'] * 2, // Round up in case of empty weirdness. Will trim later
//	'from' => $r['per_page'] * ( $r['page'] - 1 ),
) );
*/

$results = bpes_search( array(
	'query' => $search_query->get_query(),
	'index' => 'bp',
) );
var_Dump( $results );
?>

<div class="bpes-search-results-type bpes-search-results-groups">
	<?php if ( ! empty( $group_hits ) ) : ?>

	<?php endif ?>
</div>
