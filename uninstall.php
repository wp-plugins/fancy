<?php

if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	// delete options
	delete_option('fancy_anywhere_username');
	delete_option('fancy_anywhere_custom_button');

	// delete the anywhere cache table
	global $wpdb;
	$wpdb->query('DROP TABLE '.$wpdb->prefix.'fancy_anywhere;');
}
