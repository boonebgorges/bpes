<?php
/*
Plugin Name: BPES
Version: 0.1-alpha
Description: Elasticsearch indexing and search for BuddyPress content
Author: Boone Gorges
Author URI: http://boone.gorg.es
Plugin URI: PLUGIN SITE HERE
Text Domain: bpes
Domain Path: /languages
*/

define( 'BPES_DIR', trailingslashit( dirname( __FILE__ ) ) );

function bpes_include() {
	require BPES_DIR . 'includes/bpes.php';
}
add_action( 'bp_include', 'bpes_include' );
