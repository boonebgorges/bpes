<?php

class BPES_Search_Request {
	protected $query;

	/**
	 * The logic is basically: turn associative arrays into properties,
	 * and keep non-associative arrays as arrays.
	 */
	public function __construct( $args = array() ) {
		// Use json_decode() to convert to object
		$this->query = json_decode( json_encode( $args ) );
	}

	public function get_query() {
		return $this->query;
	}
}
