<?php
/**
 * This function sends an email message based on the supplied email data.
 * This function implements a second param for HTML emails
 *
 * @param EmailData $p_email_data
 * @param bool $isHtml set to true, if email body contains html tags; defaults to false to keep compatibility
 * @return bool
 */
function plugin_email_send( $p_email_data , $isHtml = false) {
	global $g_phpMailer;

	$t_email_data = $p_email_data;

	$t_recipient = trim( $t_email_data->email );
	$t_subject = string_email( trim( $t_email_data->subject ) );
	$t_message = string_email_links( trim( $t_email_data->body ) );

	$t_debug_email = config_get( 'debug_email' );
	$t_mailer_method = config_get( 'phpMailer_method' );

	$t_log_msg = 'ERROR: Message could not be sent - ';

	if( is_null( $g_phpMailer ) ) {
		if ( $t_mailer_method == PHPMAILER_METHOD_SMTP ) {
			register_shutdown_function( 'email_smtp_close' );
		}
		$mail = new PHPMailer(true);
	} else {
		$mail = $g_phpMailer;
	}

	if( isset( $t_email_data->metadata['hostname'] ) ) {
		$mail->Hostname = $t_email_data->metadata['hostname'];
	}

	# @@@ should this be the current language (for the recipient) or the default one (for the user running the command) (thraxisp)
	$t_lang = config_get( 'default_language' );
	if( 'auto' == $t_lang ) {
		$t_lang = config_get( 'fallback_language' );
	}
	$mail->SetLanguage( lang_get( 'phpmailer_language', $t_lang ) );

	# Select the method to send mail
	switch( config_get( 'phpMailer_method' ) ) {
		case PHPMAILER_METHOD_MAIL:
			$mail->IsMail();
			break;

		case PHPMAILER_METHOD_SENDMAIL:
			$mail->IsSendmail();
			break;

		case PHPMAILER_METHOD_SMTP:
			$mail->IsSMTP();

			// SMTP collection is always kept alive
			$mail->SMTPKeepAlive = true;

			if ( !is_blank( config_get( 'smtp_username' ) ) ) {
				# Use SMTP Authentication
				$mail->SMTPAuth = true;
				$mail->Username = config_get( 'smtp_username' );
				$mail->Password = config_get( 'smtp_password' );
			}

			if ( !is_blank( config_get( 'smtp_connection_mode' ) ) ) {
				$mail->SMTPSecure = config_get( 'smtp_connection_mode' );
			}

			$mail->Port = config_get( 'smtp_port' );

			break;
	}

	$mail->IsHTML( $isHtml );              # set email format to plain text
	$mail->WordWrap = 80;              # set word wrap to 50 characters
	$mail->Priority = $t_email_data->metadata['priority'];  # Urgent = 1, Not Urgent = 5, Disable = 0
	$mail->CharSet = $t_email_data->metadata['charset'];
	$mail->Host = config_get( 'smtp_host' );
	$mail->From = config_get( 'from_email' );
	$mail->Sender = config_get( 'return_path_email' );
	$mail->FromName = config_get( 'from_name' );
	$mail->AddCustomHeader('Auto-Submitted:auto-generated');
	$mail->AddCustomHeader('X-Auto-Response-Suppress: All');

	if( OFF !== $t_debug_email ) {
		$t_message = 'To: ' . $t_recipient . "\n\n" . $t_message;
		$t_recipient = $t_debug_email;
	}

	try {
		$mail->AddAddress( $t_recipient, '' );
	}
	catch ( phpmailerException $e ) {
		log_event( LOG_EMAIL, $t_log_msg . $mail->ErrorInfo );
		$t_success = false;
		$mail->ClearAllRecipients();
		$mail->ClearAttachments();
		$mail->ClearReplyTos();
		$mail->ClearCustomHeaders();
		return $t_success;
	}

	$mail->Subject = $t_subject;
	$mail->Body = make_lf_crlf( "\n" . $t_message );

	if( isset( $t_email_data->metadata['headers'] ) && is_array( $t_email_data->metadata['headers'] ) ) {
		foreach( $t_email_data->metadata['headers'] as $t_key => $t_value ) {
			switch( $t_key ) {
				case 'Message-ID':
					/* Note: hostname can never be blank here as we set metadata['hostname']
					   in email_store() where mail gets queued. */
						if ( !strchr( $t_value, '@' ) && !is_blank( $mail->Hostname ) ) {
							$t_value = $t_value . '@' . $mail->Hostname;
						}
					$mail->set( 'MessageID', "<$t_value>" );
					break;
				case 'In-Reply-To':
					$mail->AddCustomHeader( "$t_key: <{$t_value}@{$mail->Hostname}>" );
					break;
				default:
					$mail->AddCustomHeader( "$t_key: $t_value" );
					break;
			}
		}
	}

	try {
		$t_success = $mail->Send();
		if ( $t_success ) {
			$t_success = true;

			if ( $t_email_data->email_id > 0 ) {
				email_queue_delete( $t_email_data->email_id );
			}
		} else {
			# We should never get here, as an exception is thrown after failures
			log_event( LOG_EMAIL, $t_log_msg . $mail->ErrorInfo );
			$t_success = false;
		}
	}
	catch ( phpmailerException $e ) {
		log_event( LOG_EMAIL, $t_log_msg . $mail->ErrorInfo );
		$t_success = false;
	}

	$mail->ClearAllRecipients();
	$mail->ClearAttachments();
	$mail->ClearReplyTos();
	$mail->ClearCustomHeaders();

	return $t_success;
}

