<?php
auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

html_page_top( 'Settings for Send weekly report' );

print_manage_menu( );

function plugin_print_send_weekly_user_list_option_list($config_name) {
	$option_list_array = array();
	$user_ids = explode("|", plugin_config_get($config_name, ''));
	//if( !is_array($user_ids) || count($user_ids)==0 ) return '';
	foreach($user_ids as $user_id) {
		if(user_exists($user_id)) {
			$user = user_get_row($user_id);
			$option_list_array[] = '<option value="'.$user['id'].'">'.$user['username'].'</option>';
		}
	}
	print implode("", $option_list_array);
}
	
?>

<br/>
<form action="<?php echo plugin_page( 'config_edit' )?>" method="post">
<?php echo form_security_field( 'plugin_send_weekly_report_config_edit' ) ?>
<table align="center" class="width75" cellspacing="1">

<tr <?php echo helper_alternate_class( )?>>
	<td class="form-title">
		Settings for Send weekly report
	</td>
	<td class="center"><p><b>User</b><br />Selection will be added to "Send to" list.</p></td>
	<td class="center"><p><b>Send To</b><br />Selection will be deleted from "Send to" list.</p></td>
</tr>


<tr <?php echo helper_alternate_class( )?>>
	<td class="category">
		Deutsch
	</td>
	<td class="center">
		<select name="user_ids_de[]" multiple="multiple" size="10">
			<?php print_project_user_list_option_list( $f_project_id ) ?>
		</select>
	</td>
	<td class="center">
		<select name="send_weekly_to_de[]" multiple="multiple" size="10">
			<?php plugin_print_send_weekly_user_list_option_list('send_weekly_to_de'); ?>
		</select>
	</td>
</tr>

<tr <?php echo helper_alternate_class( )?>>
	<td class="category">
		English
	</td>
	<td class="center">
		<select name="user_ids_en[]" multiple="multiple" size="10">
			<?php print_project_user_list_option_list( $f_project_id ) ?>
		</select>
	</td>
	<td class="center">
		<select name="send_weekly_to_en[]" multiple="multiple" size="10">
			<?php plugin_print_send_weekly_user_list_option_list('send_weekly_to_en'); ?>
		</select>
	</td>
</tr>

<tr>
	<td class="center" colspan="3">
		<input type="submit" class="button" value="<?php echo lang_get( 'change_configuration' )?>" />
	</td>
</tr>

</table>
<form>

<?php
html_page_bottom();
