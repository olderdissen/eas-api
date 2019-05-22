<?
function active_sync_mail_parse($user, $collection_id, $server_id, $mime)
	{
	$data = array();

	$data["AirSyncBase"]["NativeBodyType"] = 4;

	active_sync_mail_add_container_m($data, $mime);

	$mail_struct = active_sync_mail_split($mime);

	$head_parsed = active_sync_mail_parse_head($mail_struct["head"]);

	foreach(array("text/plain" => 1, "text/html" => 2, "application/rtf" => 3) as $content_type => $value)
		{
		if(isset($head_parsed["Content-Type"]) === false)
			continue;

		if($head_parsed["Content-Type"] != $content_type)
			continue;

		$data["AirSyncBase"]["NativeBodyType"] = $value;

		break;
		}

	if(isset($head_parsed["Date"]) === false)
		$data["Email"]["DateReceived"] = date("Y-m-d\TH:i:s.000\Z");
	else
		$data["Email"]["DateReceived"] = date("Y-m-d\TH:i:s.000\Z", strtotime($head_parsed["Date"]));

	if(isset($data["Email"]["Subject"]) === false)
		$data["Email"]["Subject"] = "...";

	foreach(array("ContentClass" => "urn:content-classes:message", "Importance" => 1, "MessageClass" => "IPM.Note", "Read" => 0) as $token => $value)
		$data["Email"][$token] = $value;

	foreach(array("low" => 0, "normal" => 1, "high" => 2) as $test => $importance)
		{
		if(isset($head_parsed["Importance"]) === false)
			continue;

		if($head_parsed["Importance"] != $test)
			continue;

		$data["Email"]["Importance"] = $importance;
		}

	foreach(array(5 => 0, 3 => 1, 1 => 2) as $test => $importance)
		{
		if(isset($head_parsed["X-Priority"]) === false)
			continue;

		if($head_parsed["X-Priority"] != $test)
			continue;

		$data["Email"]["Importance"] = $importance;
		}

	$translation_table = array();

	$translation_table["Email"] = array("From" => "From", "To" => "To", "Cc" => "Cc", "Subject" => "Subject", "ReplyTo" => "Reply-To");
	$translation_table["Email2"] = array("ReceivedAsBcc" => "Bcc", "Sender" => "Sender");

	foreach($translation_table as $codepage => $token_translation)
		{
		foreach($token_translation as $token => $field)
			{
			if(isset($head_parsed[$field]) === false)
				continue;

			if(strlen($head_parsed[$field]) == 0)
				continue;

			$data[$codepage][$token] = $head_parsed[$field];
			}
		}

#	$thread_topic = $data["Email"]["Subject"];

#	if(active_sync_mail_is_forward($thread_topic) == 1)
#		list($null, $thread_topic) = explode(":", $thread_topic, 2);

#	if(active_sync_mail_is_reply($thread_topic) == 1)
#		list($null, $thread_topic) = explode(":", $thread_topic, 2);

#	$data["Email"]["ThreadTopic"] = trim($thread_topic);

	active_sync_mail_parse_body($user, $collection_id, $server_id, $data, $head_parsed, $mail_struct["body"]);

	return($data);
	}
?>
