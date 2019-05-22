<?
function active_sync_send_mail($user, $mime)
	{
	$host = active_sync_get_domain(); # needed for user@host

#	$mime = active_sync_mail_body_smime_sign($mime);
#	$mime = active_sync_mail_body_smime_encode($mime);

	$mail_struct = active_sync_mail_split($mime); # head, body

	$head_parsed = active_sync_mail_parse_head($mail_struct["head"]);

	$additional_headers = array();

	foreach($head_parsed as $key => $val)
		{
		if(($key == "Received") || ($key == "Subject") || ($key == "To"))
			continue;

		$additional_headers[] = implode(": ", array($key, $val));
		}

	# don't we need a recipient here? by settting to null we got an empty field.

	mail($head_parsed["To"], (isset($head_parsed["Subject"]) === false ? "" : $head_parsed["Subject"]), $mail_struct["body"], implode("\n", $additional_headers), "-f no-reply@" . $host);
	}
?>
