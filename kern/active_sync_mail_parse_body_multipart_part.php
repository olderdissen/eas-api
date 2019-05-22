<?
function active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, & $data, $mail)
	{
	$mail_struct = active_sync_mail_split($mail);

	$head_parsed = active_sync_mail_parse_head($mail_struct["head"]);

	active_sync_mail_parse_body($user, $collection_id, $server_id, $data, $head_parsed, $mail_struct["body"]);
	}
?>
