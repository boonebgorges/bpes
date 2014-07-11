<?php

class BPES_Group_Iterator extends WPES_Abstract_Iterator {

	public function count_potential_docs() {
		return BP_Groups_Group::get_total_group_count();
	}

	// Prepare a set of docs for bulk indexing
	//   returns:
	//     false - no more docs to prepare
	//     array( <int> ) - List of ids to be indexed
	//     WP_Error
	public function get_ids( $doc_args ) {
		global $wpdb;

		$bp = buddypress();

		$this->curr_id = $this->last_id + 1;

		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$bp->groups->table_name} WHERE id >= %d ORDER BY id ASC LIMIT %d",
			$this->curr_id,
			$this->batch_size
		) );

		if ( empty( $ids ) ) {
			$this->done = true;
			return false;
		}

		$this->curr_ids = array();
		$this->last_id = end( $ids );
		$this->first_id = reset( $ids );

		// Does nothing for now - is_indexable() passes through
		foreach ( $ids as $id ) {
			$is_indexable = $this->doc_builder->is_indexable( array(
				'id' => $id,
			) );

			if ( $is_indexable ) {
				$this->curr_ids = $id;
			}
		}

		return $this->curr_ids;
	}

	public function get_pre_delete_filter() {}  //deletes all
	public function get_delete_filter() {}      //deletes a range
	public function get_post_delete_filter() {} //deletes anything after the final bulk indexing
}
