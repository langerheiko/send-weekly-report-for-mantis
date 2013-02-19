<?php

form_security_validate( 'plugin_send_weekly_report_config_edit' );

auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

/**
* adds users to config setting
*/
function plugin_send_weekly_add_user(array $add_users, $config_field) {
	if ( plugin_config_get( $config_field, '' ) != implode('|', $add_users) ) {
		$current_users = explode('|', plugin_config_get( $config_field , ''));
		$new_users = array_merge($current_users, $add_users);
		$new_users = array_unique($new_users);
		plugin_config_set( $config_field, implode('|', $new_users) );
	}
}


/**
* deletes users from config setting
*/
function plugin_send_weekly_delete_user(array $delete_users, $config_field) {
	if ( count($delete_users) > 0 ) {
		$current_users = explode('|', plugin_config_get( $config_field , ''));
		$new_users = array_diff($current_users, $delete_users);
		plugin_config_set( $config_field, implode('|', $new_users));
	}
}


//first delete users
$delete_users_de = gpc_get_string_array( 'send_weekly_to_de', array() );
plugin_send_weekly_delete_user($delete_users_de, 'send_weekly_to_de');

$delete_users_en = gpc_get_string_array( 'send_weekly_to_en', array() );
plugin_send_weekly_delete_user($delete_users_en, 'send_weekly_to_en');

//then add users
$add_users_de = gpc_get_string_array( 'user_ids_de', array() );
plugin_send_weekly_add_user($add_users_de, 'send_weekly_to_de');

$add_users_en = gpc_get_string_array( 'user_ids_en', array() );
plugin_send_weekly_add_user($add_users_en, 'send_weekly_to_en');




form_security_purge( 'plugin_send_weekly_report_config_edit' );

print_successful_redirect( plugin_page( 'config', true ) );
