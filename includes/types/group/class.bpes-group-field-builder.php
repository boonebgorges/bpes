<?php

class BPES_Group_Field_Builder extends WPES_Abstract_Field_Builder {
	public function get_mappings( $args ) {


	}


	public function get_all_fields( $args ) {
		$fields = array();

		if ( ! isset( $args['group_id'] ) ) {
			return $fields;
		}

		$group = groups_get_group( array(
			'group_id' => $args['group_id'],
			'populate_extras' => true,
		) );

		if ( empty( $group->name ) ) {
			return $fields;
		}

		$fields['name']          = $group->name;
		$fields['slug']          = $group->slug;
		$fields['description']   = $group->description;
		$fields['last_activity'] = $group->last_activity;
		$fields['date_created']  = $group->date_created;
		$fields['status']        = $group->status;

		return $fields;
	}

	public function get_update_script( $args ) {

	}
}
