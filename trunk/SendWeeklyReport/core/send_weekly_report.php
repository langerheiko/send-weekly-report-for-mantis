<?php
error_reporting(E_ALL);

global $g_bypass_headers;
$g_bypass_headers = 1;
 
//require mantis APIs
//require_once '/var/www/mantisbt/core.php';
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ). DIRECTORY_SEPARATOR . 'core.php';
require_once 'email_api.php';
require_once 'SendWeeklyReport_email_api.php';
require_once 'database_api.php';
require_once 'plugin_api.php';
require_once 'lang_api.php';

//select the used plugin, because this file will be started standalone
plugin_push_current( 'SendWeeklyReport' );

//define language => configuration_name
$users_to_languages = array(
	'german' => 'send_weekly_to_de'
	,'english' => 'send_weekly_to_en'
);


//function for converting enum strings to arrays
function make_key_value($str) {
	$ret = array();	
	$arr1 = explode(',',$str);
	foreach($arr1 as $value) {
		$arr2 = array();
		$arr2 = explode(':',$value);
		$ret[$arr2[0]] = $arr2[1];
	}
	return $ret;
}
 
 
foreach($users_to_languages as $language => $value) {
	 
	$email_body = '';
	echo "<br /><br />".$language."<br />";
	
	//get users email addresses
	$email_list_array = array();
	$user_ids = explode("|", plugin_config_get($value, ''));
	foreach($user_ids as $user_id) {
		if(user_exists($user_id)) {
			$user = user_get_row($user_id);
			$email_list_array[] = $user['email'];
		}
	}
	$recipients = $email_list_array;
	 
	//no user? then next language
	if(count($recipients) == 0) break;
	
	//add admins email address to every language
	$recipients[] = config_get_global( 'administrator_email' );
	
	echo "Will send to: ".implode('; ', $recipients)."<br />";
	
	//lang_push( 'german' );
	lang_push( $language );
	
	
	//get enum string arrays
	$access_levels_enum_string = lang_get('access_levels_enum_string');
	$access_levels = make_key_value($access_levels_enum_string);
	 
	$status_enum_string = lang_get('status_enum_string');
	$status = make_key_value($status_enum_string);
	
	$priority_enum_string = lang_get('priority_enum_string');
	$priority = make_key_value($priority_enum_string);
	
	$severity_enum_string = lang_get('severity_enum_string');
	$severity = make_key_value($severity_enum_string);
	
	$resolution_enum_string = lang_get('resolution_enum_string');
	$resolution = make_key_value($resolution_enum_string);
	
	
	
	$rows_open = array();
	$rows_done = array();
	
	//query mantis database
	if (db_connect( false, config_get_global('hostname'), config_get_global('db_username'), config_get_global('db_password'), config_get_global('database_name'))){
		$rs = db_query('SELECT m.id, severity, priority, u.username as assigned, m.status, resolution, mp.name as project, mc.name as category, summary, m.date_submitted, m.due_date, m.last_updated 
	FROM mantis_bug_table m LEFT JOIN mantis_user_table u ON (u.id=m.handler_id) LEFT JOIN mantis_category_table mc ON (mc.id=m.category_id AND mc.project_id=m.project_id) JOIN mantis_project_table mp ON (m.project_id=mp.id)
	ORDER BY m.id ASC');
	
		//print_r(db_fetch_array($rs));
		 
		do {
			$result = db_fetch_array($rs);
			if($result !== false) {
				if($result['status'] >= config_get('bug_resolved_status_threshold')) {
					if($result['last_updated'] > strtotime('-1 week')) $rows_done[] = $result;
				} else {
					$rows_open[] = $result;
				}
				//$num = count($rows) - 1;
				//$rows[$num]['severity'] = $severity[$rows[$num]['severity']];
			}
		} while ($result !== false);
	}
	
	//write email text
	$email_body.='<html>
	  <head>
	    <title>
	      '.lang_get('plugin_SendWeeklyReport_weekly_report', $language).'
	    </title>
	  </head>
	  <body bgcolor="#FFFFFF">';
	
	$email_body.= lang_get('plugin_SendWeeklyReport_weekly_report_body_text', $language);
	
	$email_body.= '<h2>'.lang_get('plugin_SendWeeklyReport_weekly_report_bugs_open', $language).'</h2>';
	$email_body.= '<table width="100%">';
	$email_body.= '<tr><th>ID</th><th>'.lang_get('severity', $language).'</th><th>'.lang_get('priority', $language).'</th><th>'.lang_get('assigned_to', $language).'</th><th>'.lang_get('status', $language).'</th><th>'.lang_get('resolution', $language).'</th><th>'.lang_get('project_name', $language).'</th><th>'.lang_get('category', $language).'</th><th>'.lang_get('summary', $language).'</th><th>'.lang_get('date_submitted', $language).'</th><th>'.lang_get('last_update', $language).'</th></tr>';
	foreach($rows_open as $row){
		$email_body.= '<tr>';	
		$email_body.= '<td>'.$row['id'].'</td><td>'.$severity[$row['severity']].'</td><td>'.$priority[$row['priority']].'</td><td>'.$row['assigned'].'</td><td>'.$status[$row['status']].'</td><td>'.$resolution[$row['resolution']].'</td><td>'.$row['project'].'</td><td>'.$row['category'].'</td><td>'.$row['summary'].'</td><td>'.date('Y-m-d H:i:s', $row['date_submitted']).'</td><td>'.date('Y-m-d H:i:s', $row['last_updated']).'</td>';
		$email_body.= '</tr>';	
	}
	$email_body.= '</table>';
	
	$email_body.= '<h2>'.lang_get('plugin_SendWeeklyReport_weekly_report_bugs_done_last_week').'</h2>';
	$email_body.= '<table width="100%">';
	$email_body.= '<tr><th>ID</th><th>'.lang_get('severity', $language).'</th><th>'.lang_get('priority', $language).'</th><th>'.lang_get('assigned_to', $language).'</th><th>'.lang_get('status', $language).'</th><th>'.lang_get('resolution', $language).'</th><th>'.lang_get('project_name', $language).'</th><th>'.lang_get('category', $language).'</th><th>'.lang_get('summary', $language).'</th><th>'.lang_get('date_submitted', $language).'</th><th>'.lang_get('last_update', $language).'</th></tr>';
	foreach($rows_done as $row){
		$email_body.= '<tr>';	
		$email_body.= '<td>'.$row['id'].'</td><td>'.$severity[$row['severity']].'</td><td>'.$priority[$row['priority']].'</td><td>'.$row['assigned'].'</td><td>'.$status[$row['status']].'</td><td>'.$resolution[$row['resolution']].'</td><td>'.$row['project'].'</td><td>'.$row['category'].'</td><td>'.$row['summary'].'</td><td>'.date('Y-m-d H:i:s', $row['date_submitted']).'</td><td>'.date('Y-m-d H:i:s', $row['last_updated']).'</td>';
		$email_body.= '</tr>';	
	}
	$email_body.= '</table></body></html>';
	
	
	//send email to every recipient seperatly
	foreach($recipients as $address) {
		$t_email_data = new EmailData;
		$t_email_data->email = $address;
		$t_email_data->subject = '[MantisBT] '.lang_get('plugin_SendWeeklyReport_weekly_report', $language).' ('.lang_get('plugin_SendWeeklyReport_weekly_report_week_no', $language).' '.date('W').') - '.lang_get('plugin_SendWeeklyReport_weekly_report_open', $language).': '.count($rows_open).', '.lang_get('plugin_SendWeeklyReport_weekly_report_done_last_week', $language).': '.count($rows_done);
		$t_email_data->body = $email_body;
		$t_email_data->metadata['priority'] = config_get( 'mail_priority' );
		$t_email_data->metadata['charset'] = 'utf-8';
		
		if(function_exists('plugin_email_send')) {
			$email_ret = plugin_email_send( $t_email_data, true );
		} else {
			$email_ret = email_send( $t_email_data );
		}		

		if( $email_ret ) {
			echo "<br />Email has been sent successfully to ".$address." at ".date('Y-m-d H:i:s').'.';
		} else {
			echo "<br />No email for ".$address;
		}
	}
	
	//unset some variables
	unset($recipients);
	unset($email_list_array);
	unset($t_email_data);
}

