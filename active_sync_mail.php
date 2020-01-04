<?php
function active_sync_mail_add_container_calendar(& $data, $body, $user)
	{
	$host = active_sync_get_domain(); # needed for user@host

	$temp = $body;
	$ics = active_sync_ics_to_data($body);
	$body = $temp;

	$vcalendar = [];

	if(isset($ics["VCALENDAR"]))
		$vcalendar = $ics["VCALENDAR"];

	$vevent = [];

	if(isset($vcalendar["VEVENT"]))
		$vevent = $vcalendar["VEVENT"];

	foreach(active_sync_get_default_meeting() as $token => $value)
		$data["Meeting"]["Email"][$token] = $value;

	$data["Meeting"]["Email"]["TimeZone"] = str_repeat("A", 230) . "==";

	$codepage_table = [
		"Email" => [
			"DTSTART" => "StartTime",
			"DTSTAMP" => "DtStamp",
			"DTEND" => "EndTime",
			"LOCATION" => "Location"
			],
		"Calendar" => [
			"UID" => "UID"
			]
		];

	foreach($codepage_table as $codepage => $null)
		foreach($codepage_table[$codepage] as $key => $token)
			if(isset($vevent[$key]))
				$data["Meeting"][$codepage][$token] = $vevent[$key];

#	$data["Meeting"]["Email"]["MeetingStatus"] = 0;

	$organizer = (isset($vevent["ORGANIZER"][$user . "@" . $host]) ? 1 : 0);

	foreach(["CANCEL" => [7, 5], "REQUEST" => [3, 1]] as $key => $value)
		if($vcalendar["METHOD"] == $key)
			$data["Meeting"]["Email"]["MeetingStatus"] = $value[$organizer];

	$data["Meeting"]["Email2"]["MeetingMessageType"] = 0;

	foreach(["CANCEL" => 0, "REPLY" => 3, "REQUEST" => 1] as $key => $value)
		if($vcalendar["METHOD"] == $key)
			$data["Meeting"]["Email2"]["MeetingMessageType"] = $value;

	if(isset($vevent["CLASS"]))
		foreach(["DEFAULT" => 0, "PUBLIC" => 1, "PRIVATE" => 2, "CONFIDENTIAL" => 3] as $key => $value)
			if($vevent["CLASS"] == $key)
				$data["Meeting"]["Email"]["Sensitivity"] = $value;

#	$data["Meeting"]["Email"]["AllDayEvent"] = 0;

	if(isset($vevent["X-MICROSOFT-CDO-ALLDAYEVENT"]))
		foreach(["FALSE" => 0, "TRUE" => 1] as $key => $value)
			if($vevent["X-MICROSOFT-CDO-ALLDAYEVENT"] == $key)
				$data["Meeting"]["Email"]["AllDayEvent"] = $value;

#	$data["Meeting"]["Email"]["Organizer"] = $user . "@" . $host;

	if(isset($vevent["ORGANIZER"]))
		foreach($vevent["ORGANIZER"] as $key => $null)
			$data["Meeting"]["Email"]["Organizer"] = $key;

	if(isset($vevent["ATTENDEE"][$user . "@" . $host]["RVSP"]))
		foreach(["FALSE" => 0, "TRUE" => 1] as $key => $value)
			{
			$data["Meeting"]["Email"]["ResponseRequested"] = 0;

			if($vevent["ATTENDEE"]["RVSP"] == $key)
				$data["Meeting"]["Email"]["ResponseRequested"] = $value;
			}

	if(isset($vevent["VALARM"]["TRIGGER"]))
		$data["Meeting"]["Email"]["Reminder"] = substr($vevent["VALARM"]["TRIGGER"], 3, 0 - 1); # -PT*M

	$create_new_message = true;

	if(isset($data["Body"]))
		foreach($data["Body"] as $body)
			if(isset($body["Type"]))
				if($body["Type"] == 1)
					$create_new_message = false;

	if($create_new_message)
		{
		$new_temp_message = [
			"Wann: " . date("d.m.Y H:i", strtotime($vevent["DTSTART"]))
			];

		if(isset($vevent["LOCATION"]))
			$new_temp_message[] = "Wo: " . $vevent["LOCATION"];

		$new_temp_message[] = "*~*~*~*~*~*~*~*~*~*";

		if(isset($vevent["DESCRIPTION"]))
			$new_temp_message[] = $vevent["DESCRIPTION"];

#		if(isset($vevent["SUMMARY"]))
#			$new_temp_message[] = $vevent["SUMMARY"]; # this must be calendar:body:data (text), not calendar:subject, but calendar:body:data (text) from calendar is not available

		$new_temp_message = implode("\n", $new_temp_message);

		active_sync_mail_add_container_plain($data, $new_temp_message);
		}

	if(! isset($data["Email"]["From"]))
		$data["Email"]["From"] = $user . "@" . $host;

	list($from_name, $from_mail) = active_sync_mail_parse_address($data["Email"]["From"]);
	list($to_name, $to_mail) = active_sync_mail_parse_address($data["Email"]["To"]);

	########################################################################
	# just check
	# if we are an attendee and have to delete a meeting from calendar or
	# if we are an organizer and have to update an attendee status.
	# nothing else!
	########################################################################

	if(! isset($vcalendar["METHOD"]))
		return(false);

	if($vcalendar["METHOD"] == "CANCEL")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
		$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Canceled";

		if(! isset($vevent["ORGANIZER"][$from_mail]))
			if(isset($vevent["ATTENDEE"][$from_mail]))
				{
				$server_id = active_sync_get_calendar_by_uid($user, $vevent["UID"]);

				$collection_id = active_sync_get_collection_id_by_type($user, 8);

				if($server_id != "")
					unlink(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/". $collection_id . "/" . $server_id . ".data");
				}
		}

	if($vcalendar["METHOD"] == "PUBLISH")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
		$data["Email"]["MessageClass"] = "IPM.Appointment";
		}

	if($vcalendar["METHOD"] == "REPLY")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
		$data["Email"]["MessageClass"] = "IPM.Notification.Meeting.Resp.*";

		if(isset($vevent["ORGANIZER"][$from_mail]))
			if(isset($vevent["ATTENDEE"][$from_mail]))
				{
				$server_id = active_sync_get_calendar_by_uid($user, $vevent["UID"]);

				if($server_id)
					switch($vevent["ATTENDEE"][$from_mail]["PARTSTAT"])
						{
						case("DECLINED"):
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Neg";

							active_sync_put_attendee_status($user, $server_id, $from_mail, 4);

							break;
						case("ACCEPTED"):
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Pos";

							active_sync_put_attendee_status($user, $server_id, $from_mail, 3);

							break;
						case("TENTATIVE"):
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Tent";

							active_sync_put_attendee_status($user, $server_id, $from_mail, 2);

							break;
						}
				}
		}

	if($vcalendar["METHOD"] == "REQUEST")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
		$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Request";

		if(! isset($vevent["ORGANIZER"][$from_mail]))
			if(isset($vevent["ATTENDEE"][$from_mail]))
				if($vevent["ATTENDEE"][$from_mail]["PARTSTAT"] == "NEEDS-ACTION")
					$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Request";
				else
					$data["Email"]["MessageClass"] = "IPM.Notification.Meeting";
		}

	return(true);
	}

function active_sync_mail_add_container_html(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = [
		"Type" => 2,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		];
	}

function active_sync_mail_add_container_mime(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = [
		"Type" => 4,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		];
	}

function active_sync_mail_add_container_plain(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = [
		"Type" => 1,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		];
	}

function active_sync_mail_add_container_rtf(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = [
		"Type" => 3,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		];
	}

function active_sync_mail_body_smime_decode($mime)
	{
	$file = active_sync_create_guid();

	$mail = active_sync_mail_split($mime);

	$head = iconv_mime_decode_headers($mail["head"]);

	list($to_name, $to_mail) = active_sync_mail_parse_address($head["To"]);

	$public = __DIR__ . "/certs/public/" . $to_mail . ".pem";
	$private = __DIR__ . "/certs/private/" . $to_mail . ".pem";

	if(file_exists($public) && file_exists($private))
		{
		$crt = file_get_contents($public);
		$key = file_get_contents($private);

		file_put_contents("/tmp/" . $file . ".enc", $mime);

		if(! openssl_pkcs7_decrypt("/tmp/" . $file . ".enc", "/tmp/" . $file . ".dec", $crt, [$key, ""]))
			$new_temp_message = $mime;
		elseif(! openssl_pkcs7_verify("/tmp/" . $file . ".dec", PKCS7_NOVERIFY, "/tmp/" . $file . ".ver"))
			$new_temp_message = $mime;
		elseif(! openssl_pkcs7_verify("/tmp/" . $file . ".dec", PKCS7_NOVERIFY, "/tmp/" . $file . ".ver", [], "/tmp/" . $file . ".ver", "/tmp/" . $file . ".dec"))
			$new_temp_message = $mime;
		else
			{
			foreach(["Content-Description", "Content-Disposition", "Content-Transfer-Encoding", "Content-Type", "Received"] as $key)
				unset($head[$key]);

			$new_temp_message = [];

			foreach($head as $key => $val)
				$new_temp_message[] = sprintf("%s: %s", $key, $val);

			$new_temp_message[] = "";
			$new_temp_message = file_get_contents("/tmp/" . $file . ".dec");

			$new_temp_message = implode("\n", $new_temp_message);
			}

		foreach(["dec", "enc", "ver"] as $extension)
			if(file_exists("/tmp/" . $file . "." . $extension))
				unlink("/tmp/" . $file . "." . $extension);
		}
	else
		$new_temp_message = $mime;

	return($new_temp_message);
	}

function active_sync_mail_body_smime_encode($mime) # almost copy of sign
	{
	$file = active_sync_create_guid();

	$mail = active_sync_mail_split($mime);

	$head = iconv_mime_decode_headers($mail["head"]);

	list($to_name, $to_mail) = active_sync_mail_parse_address($head["To"]);

	$public = __DIR__ . "/certs/public/" . $to_mail . ".pem";

	if(file_exists($public))
		{
		$new_temp_message = [
			sprintf("%s: %s", "Content-Type", $head["Content-Type"]),
			sprintf("%s: %s", "MIME-Version", "1.0"),
			"",
			$mail["body"]
			];

		$new_temp_message = implode("\n", $new_temp_message);

		file_put_contents("/tmp/" . $file . ".dec", $new_temp_message);

		foreach(["Content-Type", "MIME-Version"] as $key)
			unset($head[$key]);

		$crt = file_get_contents($public);

		if(openssl_pkcs7_encrypt("/tmp/" . $file . ".dec", "/tmp/" . $file . ".enc", $crt, $head))
			$new_temp_message = file_get_contents("/tmp/" . $file . ".enc");
		else
			$new_temp_message = $mime;

		foreach(["dec", "enc", "ver"] as $extension)
			if(file_exists("/tmp/" . $file . "." . $extension))
				unlink("/tmp/" . $file . "." . $extension);
		}
	else
		$new_temp_message = $mime;

	return($new_temp_message);
	}

function active_sync_mail_body_smime_sign($mime) # almost copy of encode
	{
	$mail = active_sync_mail_split($mime);

	$head = iconv_mime_decode_headers($mail["head"]);

	list($from_name, $from_mail) = active_sync_mail_parse_address($head["From"]);

	$public = __DIR__ . "/certs/public/" . $to_mail . ".pem";
	$private = __DIR__ . "/certs/private/" . $to_mail . ".pem";

	if(file_exists($public) && file_exists($private))
		{
		$new_temp_message = [
			sprintf("%s: %s", "Content-Type", $head["Content-Type"]),
			sprintf("%s: %s", "MIME-Version", "1.0"),
			"",
			$mail["body"]
			];

		$new_temp_message = implode("\n", $new_temp_message);

		$file = active_sync_create_guid();

		file_put_contents("/tmp/" . $file . ".dec", $new_temp_message);

		foreach(["Content-Type", "MIME-Version"] as $key)
			unset($head[$key]);

		$crt = file_get_contents($public);
		$key = file_get_contents($private);

		if(openssl_pkcs7_sign("/tmp/" . $file . ".dec", "/tmp/" . $file . ".enc", $crt, $key, $head))
			$new_temp_message = file_get_contents("/tmp/" . $file . ".enc");
		else
			$new_temp_message = $mime;

		foreach(["dec", "enc", "ver"] as $extension)
			if(file_exists("/tmp/" . $file . "." . $extension))
				unlink("/tmp/" . $file . "." . $extension);
		}
	else
		$new_temp_message = $mime;

	return($new_temp_message);
	}

function active_sync_mail_convert_html_to_plain($subject)
	{
	$subject = str_replace("<br>", "\x0A", $subject); # before ltgt
	$subject = preg_replace("/<[^>]*>/", "", $subject); # before ltgt
	$subject = str_replace("&lt;", "\x3C", $subject); # after br
	$subject = str_replace("&gt;", "\x3E", $subject); # after br
	$subject = str_replace("&nbsp;", "\x20", $subject); # ...

	return($subject);
	}

function active_sync_mail_convert_plain_to_html($subject)
	{
	$table = [
#		"\x20" => "&nbsp;", # ...
		"\x0D" => "", # ...
		"\x3C" => "&lt;", # before br
		"\x3E" => "&gt;", # before br
		"\x0A" => "<br>" # after ltgt
		];

	foreach($table as $search => $replace)
		$subject = str_replace($search, $replace, $subject);

	return("<p>" . $subject . "</p>");
	}

function active_sync_mail_convert_plain_to_rtf($subject)
	{
	$subject = str_replace("\\", "\\\\", $subject);

	return("{\\rtf\\ansi\\pard " . $subject . " \\par}");
	}

function active_sync_mail_header_value_decode($value, $search = "")
	{
	$param = str_getcsv($value, ";");
	$value = array_shift($param);

	$data = [];

	while(count($param))
		{
		list($param_key, $param_value) = explode("=", trim(array_shift($param)), 2);

		$data[$param_key] = active_sync_mail_header_value_trim($param_value);
		}

	if(! strlen($search))
		return($value);

	if(isset($data[$search]))
		return($data[$search]);

	return(false);
	}

function active_sync_mail_header_value_trim($string)
	{
	if(strlen($string) < 2)
		return($string);

	# comment
	if(($string[0] == '(') && (substr($string, 0 - 1) == ')'))
		return(substr($string, 1, 0 - 1));

	# display-name
	if(($string[0] == '"') && (substr($string, 0 - 1) == '"'))
		return(substr($string, 1, 0 - 1));

	# mailbox
	if(($string[0] == '<') && (substr($string, 0 - 1) == '>'))
		return(substr($string, 1, 0 - 1));

	return($string);
	}

function active_sync_mail_is_forward($subject)
	{
	$table = [
		"da" => ["VS"],			# danish
		"de" => ["WG"],			# german
		"el" => ["ΠΡΘ"],		# greek
		"en" => ["FW", "FWD"],		# english
		"es" => ["RV"],			# spanish
		"fi" => ["VL"],			# finnish
		"fr" => ["TR"],			# french
		"he" => ["הועבר"],		# hebrew
		"is" => ["FS"],			# icelandic
		"it" => ["I"],			# italian
		"nl" => ["Doorst"],		# dutch
		"no" => ["VS"],			# norwegian
		"pl" => ["PD"],			# polish
		"pt" => ["ENC"],		# portuguese
		"ro" => ["Redirecţionat"],	# romanian
		"sv" => ["VB"],			# swedish
		"tr" => ["İLT"],		# turkish
		"zh" => ["转发"]		# chinese
		];

	foreach($table as $language => $abbreviations)
		foreach($abbreviations as $abbreviation)
			{
			$abbreviation .= ":";

			if(strtolower(substr($subject, 0, strlen($abbreviation))) == strtolower($abbreviation))
				return(true);
			}

	return(false);
	}

function active_sync_mail_is_reply($subject)
	{
	$table = [
		"da" => ["SV"],		# danish
		"de" => ["AW"],		# german
		"el" => ["ΑΠ", "ΣΧΕΤ"],	# greek
		"en" => ["RE"],		# english
		"es" => ["RE"],		# spanish
		"fi" => ["VS"],		# finnish
		"fr" => ["RE"],		# french
		"he" => ["תגובה"],	# hebrew
		"is" => ["SV"],		# icelandic
		"it" => ["R", "RIF"],	# italian
		"nl" => ["Antw"],	# dutch
		"no" => ["SV"],		# norwegian
		"pl" => ["Odp"],	# polish
		"pt" => ["RES"],	# portuguese
		"ro" => ["RE"],		# romanian
		"sv" => ["SV"],		# swedish
		"tr" => ["YNT"],	# turkish
		"zh" => ["回复"]	# chinese
		];

	foreach($table as $language => $abbreviations)
		foreach($abbreviations as $abbreviation)
			{
			$abbreviation .= ":";

			if(strtolower(substr($subject, 0, strlen($abbreviation))) == strtolower($abbreviation))
				return(true);
			}

	return(false);
	}

function active_sync_mail_parse($user, $collection_id, $server_id, $mime)
	{
	$data = [
#		"AirSyncBase" => [
#			"NativeBodyType" => 4
#			],
		"Email" => [
			"ContentClass" => "urn:content-classes:message",
			"Importance" => 1,
			"MessageClass" => "IPM.Note",
			"Read" => 0,
			"DateReceived" => date("Y-m-d\TH:i:s.000\Z")
			]
		];

	active_sync_mail_add_container_mime($data, $mime);

	$mail = active_sync_mail_split($mime);

	$head = iconv_mime_decode_headers($mail["head"]);

	foreach(["text/plain" => 1, "text/html" => 2, "application/rtf" => 3] as $content_type => $value)
		if(isset($head["Content-Type"]))
			if($head["Content-Type"] == $content_type)
				$data["AirSyncBase"]["NativeBodyType"] = $value;

	if(isset($head["Sender"]))
		$data["Email2"]["Sender"] = $head["Sender"];

	if(isset($head["Reply-To"]))
		$data["Email"]["ReplyTo"] = $head["Reply-To"];

	if(isset($head["From"]))
		$data["Email"]["From"] = $head["From"];

	if(isset($head["To"]))
		$data["Email"]["To"] = $head["To"];

	if(isset($head["Cc"]))
		$data["Email"]["Cc"] = $head["Cc"];

	if(isset($head["Bcc"]))
		$data["Email2"]["ReceivedAsBcc"] = 1;

	if(isset($head["Subject"]))
		$data["Email"]["Subject"] = $head["Subject"];

	if(isset($head["Date"]))
		$data["Email"]["DateReceived"] = date("Y-m-d\TH:i:s.000\Z", strtotime($head["Date"]));

	if(isset($head["Importance"]))
		$data["Email"]["Importance"] = strtr($head["Importance"], ["low" => 0, "normal" => 1, "high" => 2]);

	if(isset($head["X-Priority"]))
		$data["Email"]["Importance"] = strtr($head["X-Priority"], [5 => 0, 3 => 1, 1 => 2]);

#	$thread_topic = $data["Email"]["Subject"];

#	if(active_sync_mail_is_forward($thread_topic) == 1)
#		list($null, $thread_topic) = explode(":", $thread_topic, 2);

#	if(active_sync_mail_is_reply($thread_topic) == 1)
#		list($null, $thread_topic) = explode(":", $thread_topic, 2);

#	$data["Email"]["ThreadTopic"] = trim($thread_topic);

	active_sync_mail_parse_body($user, $collection_id, $server_id, $data, $head, $mail["body"]);

	return($data);
	}

function active_sync_mail_parse_address($data, $localhost = "localhost")
	{
	list($null, $name, $mailbox, $comment) = ["", "", "", ""];

	if(! strlen($data))
		return(false);

	# "name" [MOBILE:number]
	# this is a special active sync construction for sending sms
#	elseif(preg_match("/\"(.*)\" \[MOBILE:(.*)\]/", $data, $matches) == 1)
#		list($null, $name, $mailbox) = $matches;

	# "name" <mailbox>
	elseif(preg_match("/\"(.*)\" <(.*)>/", $data, $matches) == 1)
		list($null, $name, $mailbox) = $matches;

	# "name" <mailbox> (comment)
	elseif(preg_match("/\"(.*)\" <(.*)> \((.*)\)/", $data, $matches) == 1)
		list($null, $name, $mailbox, $comment) = $matches;

	# name <mailbox>
	elseif(preg_match("/(.*) <(.*)>/", $data, $matches) == 1)
		list($null, $name, $mailbox) = $matches;

	# name <mailbox> (comment)
	elseif(preg_match("/(.*) <(.*)> \((.*)\)/", $data, $matches) == 1)
		list($null, $name, $mailbox, $comment) = $matches;

	# <mailbox>
	elseif(preg_match("/<(.*)>/", $data, $matches) == 1)
		list($null, $mailbox) = $matches;

	# <mailbox> (comment)
	elseif(preg_match("/<(.*)> \((.*)\)/", $data, $matches) == 1)
		list($null, $mailbox, $comment) = $matches;

	# mailbox
	else # if(preg_match("/(.*)/", $data, $matches) == 1)
		list($null, $mailbox) = ["", ""];

	return([$name, $mailbox]);
	}

function active_sync_mail_parse_body($user, $collection_id, $server_id, & $data, $head, $body)
	{
	$content_transfer_encoding = "";

	if(isset($head["Content-Transfer-Encoding"]))
		$content_transfer_encoding = active_sync_mail_header_value_decode($head["Content-Transfer-Encoding"]);

	$content_disposition = "";

	if(isset($head["Content-Disposition"]))
		$content_disposition = active_sync_mail_header_value_decode($head["Content-Disposition"]);

	$content_type = "";
	$content_type_charset = "";
	$content_type_boundary = "";

	if(isset($head["Content-Type"]))
		{
		$content_type = active_sync_mail_header_value_decode($head["Content-Type"]);
		$content_type_charset = active_sync_mail_header_value_decode($head["Content-Type"], "charset");
		$content_type_boundary = active_sync_mail_header_value_decode($head["Content-Type"], "boundary");
		}

	if($content_transfer_encoding == "")
		$body = $body;
	elseif($content_transfer_encoding == "base64")
		$body = base64_decode($body);
	elseif($content_transfer_encoding == "7bit")
		$body = $body;
	elseif($content_transfer_encoding == "8bit")
		$body = $body;
	elseif($content_transfer_encoding == "quoted-printable")
		$body = quoted_printable_decode($body);

	if($content_type == "")
		{
		if(strtoupper($content_type_charset) != "UTF-8")
			$body = utf8_encode($body);

		$body_html = active_sync_mail_convert_plain_to_html($body);
		$body_plain = $body;

		active_sync_mail_add_container_plain($data, $body_plain);
		active_sync_mail_add_container_html($data, $body_html);
		}
	elseif($content_disposition == "attachment" || $content_disposition == "inline")
		active_sync_mail_parse_body_part($user, $collection_id, $server_id, $data, $head, $body);
	elseif($content_type == "multipart/alternative" || $content_type == "multipart/mixed" || $content_type == "multipart/related")
		{
		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index ++)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "multipart/report")
		{
		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index ++)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);

		# set this after multipart have been parsed
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "REPORT.IPM.Note.NDR";
		}
	elseif($content_type == "multipart/signed")
		{
		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index ++)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "application/pgp-signature")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME.MultipartSigned";
		}
	elseif($content_type == "application/pkcs7-mime" || $content_type == "application/x-pkcs7-mime")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME";
		}
	elseif($content_type == "application/pkcs7-signature" || $content_type == "application/x-pkcs7-signature")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME.MultipartSigned";
		}
	elseif($content_type == "application/rtf")
		active_sync_mail_add_container_rtf($data, $body);
	elseif($content_type == "text/calendar" || $content_type == "text/x-vCalendar")
		active_sync_mail_add_container_calendar($data, $body, $user);
	elseif($content_type == "text/html")
		{
		if($content_type_charset != "UTF-8")
			$body = utf8_encode($body);

		$body_html = $body;
		$body_plain = active_sync_mail_convert_html_to_plain($body);

		active_sync_mail_add_container_plain($data, $body_plain);
		active_sync_mail_add_container_html($data, $body_html);
		}
	elseif($content_type == "text/plain")
		{
		if($content_type_charset != "UTF-8")
			$body = utf8_encode($body);

		$body_html = active_sync_mail_convert_plain_to_html($body);
		$body_plain = $body;

		active_sync_mail_add_container_plain($data, $body_plain);
		active_sync_mail_add_container_html($data, $body_html);
		}
	else
		active_sync_mail_parse_body_part($user, $collection_id, $server_id, $data, $head, $body);
	}

function active_sync_mail_parse_body_multipart($body, $boundary)
	{
	list($index, $retval) = [0, [""]];

	$body = str_replace("\r", "", $body); # WIN

	while(strlen($body))
		{
		list($line, $body) = explode("\n", $body, 2);

		if($line == "--" . $boundary || $line == "--" . $boundary . "--")
			$retval[++ $index] = "";
		else
			$retval[$index] .= $line . "\n";
		}

	return($retval);
	}

function active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, & $data, $mime)
	{
	$mail = active_sync_mail_split($mime);

	$head = iconv_mime_decode_headers($mail["head"]);

	active_sync_mail_parse_body($user, $collection_id, $server_id, $data, $head, $mail["body"]);
	}

function active_sync_mail_parse_body_part($user, $collection_id, $server_id, & $data, $head, $body)
	{
	$content_description = "";

	if(isset($head["Content-Description"]))
		$content_description = active_sync_mail_header_value_decode($head["Content-Description"]);

	$content_disposition = "";

	if(isset($head["Content-Disposition"]))
		$content_disposition = active_sync_mail_header_value_decode($head["Content-Disposition"]);

	$content_id = "";

	if(isset($head["Content-ID"]))
		$content_id = active_sync_mail_header_value_decode($head["Content-ID"]);

	$content_type = "";
	$content_type_name = "";

	if(isset($head["Content-Type"]))
		{
		$content_type = active_sync_mail_header_value_decode($head["Content-Type"]);
		$content_type_name = active_sync_mail_header_value_decode($head["Content-Type"], "name");
		}

	if(! strlen($content_type_name))
		{
		foreach(range(0, 9) as $i)
			{
			$temp = active_sync_mail_header_value_decode($head["Content-Type"], "name*" . $i . "*");

			if(substr($temp, 0, 10) == "ISO-8859-1")
				$temp = utf8_encode(urldecode(substr($temp, 12)));

			$content_type_name .= $temp;
			}
		}

	if(! strlen($content_type))
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note";
		}
	elseif($content_type == "audio/wav")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.Microsoft.Voicemail";
		}
	elseif($content_type == "text/plain" || $content_type == "text/html")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note";
		}
	elseif($content_type == "text/calendar" || $content_type == "text/x-vCalendar")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
		$data["Email"]["MessageClass"] = "IPM.Notification.Meeting";
		}

	$reference = active_sync_create_guid();

	$data["Attachments"][] = [
		"AirSyncBase" => [
			"ContentId" => $content_id,
			"IsInline" => ($content_disposition == "inline" ? 1 : 0),
			"DisplayName" => ($content_description == "" ? "..." : $content_description),
			"EstimatedDataSize" => strlen($body),
			"FileReference" => $reference,
			"Method" => ($content_disposition == "inline" ? 6 : 1)
			]
		];

	$data["File"][$reference] = [
		"AirSyncBase" => [
			"ContentType" => $content_type
			],
		"ItemOperations" => [
			"Data" => base64_encode($body)
			]
		];
	}

function active_sync_mail_signature_save($data, $body)
	{
	list($name, $mail) = active_sync_mail_parse_address($data["Email"]["From"]);

	$crt = __DIR__ . "/certs/public/" . $mail . ".pem";

	if(! file_exists($crt))
		{
		$body = base64_encode($body);

		$body = chunk_split($body , 64);

		$body = ["-----BEGIN PKCS7-----", "\n", $body, "-----END PKCS7-----"];

		$body = implode("", $body);

		file_put_contents($crt, $body);

		exec("openssl pkcs7 -in " . $crt . " -out " . $crt . " -text -print_certs", $output, $return_var);

		$body = file_get_contents($crt);

		list($null, $body) = explode("-----BEGIN CERTIFICATE-----", 2);
		list($body, $null) = explode("-----END CERTIFICATE-----", 2);

		$body = ["-----BEGIN CERTIFICATE-----", $body, "-----END CERTIFICATE-----"];

		$body = implode("\n", $body);

		file_put_contents($crt, $body);
		}
	}

function active_sync_mail_split($mime)
	{
	$head = "";

	while(strlen($mime))
		{
		list($line, $mime) = explode("\n", $mime, 2);

		if($line == "" || $line == "\r")
			break;

		$head .= $line . "\n";
		}

	return(["head" => $head, "body" => $mime]);
	}
?>
