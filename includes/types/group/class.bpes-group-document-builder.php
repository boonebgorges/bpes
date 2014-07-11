<?php

class BPES_Group_Document_Builder extends WPES_Abstract_Document_Builder {
	public function get_id( $args ) {
		// Pass through for now - does this need to be namespaced?
		return intval( $args['item_id'] );
	}

	public function get_type( $args = array() ) {
		return 'group';
	}

	public function doc( $args ) {
		$field_builder = new BPES_Group_Field_Builder();
		$field_args = array(
			'group_id' => $args['item_id'],
		);
		$data = $field_builder->get_all_fields( $field_args );
		return $data;
	}

	public function update( $args ) {}
	public function is_indexable( $args ) {
		// pass through for now
		// @todo Does it make sense for an item not to be indexable? hiddens?
		return true;
	}
}
