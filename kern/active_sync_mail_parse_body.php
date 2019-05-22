<?
function active_sync_mail_parse_body($user, $collection_id, $server_id, & $data, $head_parsed, $body)
	{
	$content_transfer_encoding = "";

	if(isset($head_parsed["Content-Transfer-Encoding"]) === true)
		$content_transfer_encoding = active_sync_mail_header_parameter_decode($head_parsed["Content-Transfer-Encoding"], "");

	$content_disposition = "";

	if(isset($head_parsed["Content-Disposition"]) === true)
		$content_disposition = active_sync_mail_header_parameter_decode($head_parsed["Content-Disposition"], "");

	$content_type = "";
	$content_type_charset = "";
	$content_type_boundary = "";

	if(isset($head_parsed["Content-Type"]) === true)
		{
		$content_type = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "");
		$content_type_charset = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "charset");
		$content_type_boundary = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "boundary");
		}

	if($content_transfer_encoding == "")
		{
		}
	elseif($content_transfer_encoding == "base64")
		$body = base64_decode($body);
	elseif($content_transfer_encoding == "7bit")
		{
		}
	elseif($content_transfer_encoding == "quoted-printable")
		$body = quoted_printable_decode($body);

	if($content_type == "")
		{
		if($content_type_charset != "UTF-8")
			$body = utf8_encode($body);

		$body_h = active_sync_mail_convert_plain_to_html($body);
		$body_p = $body;

		active_sync_mail_add_container_p($data, $body_p);

		active_sync_mail_add_container_h($data, $body_h);
		}
	elseif($content_disposition == "attachment")
		{
		active_sync_mail_parse_body_part($user, $collection_id, $server_id, $data, $head_parsed, $body);
		}
	elseif($content_disposition == "inline")
		{
		active_sync_mail_parse_body_part($user, $collection_id, $server_id, $data, $head_parsed, $body);
		}
	elseif($content_type == "multipart/alternative")
		{
		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index = $index + 1)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "multipart/mixed")
		{
		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index = $index + 1)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "multipart/related")
		{
		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index = $index + 1)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "multipart/report")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "REPORT.IPM.Note.NDR";

		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index = $index + 1)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "multipart/signed")
		{
		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index = $index + 1)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "application/pgp-signature")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME.MultipartSigned";
		}
	elseif($content_type == "application/pkcs7-mime")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME";
		}
	elseif($content_type == "application/pkcs7-signature")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME.MultipartSigned";
		}
	elseif($content_type == "application/rtf")
		{
		active_sync_mail_add_container_r($data, $body);
		}
	elseif($content_type == "application/x-pkcs7-mime")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME";
		}
	elseif($content_type == "application/x-pkcs7-signature")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME.MultipartSigned";
		}
	elseif($content_type == "text/calendar")
		active_sync_mail_add_container_c($data, $body, $user);
	elseif($content_type == "text/html")
		{
		if($content_type_charset != "UTF-8")
			$body = utf8_encode($body);

		$body_h = $body;
		$body_p = active_sync_mail_convert_html_to_plain($body);

		active_sync_mail_add_container_p($data, $body_p);
		active_sync_mail_add_container_h($data, $body_h);
		}
	elseif($content_type == "text/plain")
		{
		if($content_type_charset != "UTF-8")
			$body = utf8_encode($body);

		$body_h = active_sync_mail_convert_plain_to_html($body);
		$body_p = $body;

		active_sync_mail_add_container_p($data, $body_p);
		active_sync_mail_add_container_h($data, $body_h);
		}
	elseif($content_type == "text/x-vCalendar")
		{
		active_sync_mail_add_container_c($data, $body, $user);
		}
	else
		{
		active_sync_mail_parse_body_part($user, $collection_id, $server_id, $data, $head_parsed, $body);
		}
	}
?>
