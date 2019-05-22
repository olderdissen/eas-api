<?
function active_sync_mail_parse_body_part($user, $collection_id, $server_id, & $data, $head_parsed, $body)
	{
	$content_description = "";

	if(isset($head_parsed["Content-Description"]) === true)
		$content_description = active_sync_mail_header_parameter_decode($head_parsed["Content-Description"], "");

	$content_disposition = "";

	if(isset($head_parsed["Content-Disposition"]) === true)
		$content_disposition = active_sync_mail_header_parameter_decode($head_parsed["Content-Disposition"], "");

	$content_id = "";

	if(isset($head_parsed["Content-ID"]) === true)
		$content_id = active_sync_mail_header_parameter_trim($head_parsed["Content-ID"]);

	$content_type = "";
	$content_type_name = "";

	if(isset($head_parsed["Content-Type"]) === true)
		{
		$content_type = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "");
		$content_type_name = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "name");
		}

	if($content_type_name == "")
		{
		foreach(range(0, 9) as $i)
			{
			$temp = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "name*" . $i . "*");

			$temp = (substr($temp, 0, 10) == "ISO-8859-1" ? utf8_encode(urldecode(substr($temp, 12))) : $temp);

			$content_type_name = $content_type_name . $temp;
			}
		}

	if($content_type == "")
		{
		$data["Email"]["ContentClass"]		= "urn:content-classes:message";
		$data["Email"]["MessageClass"]		= "IPM.Note";
		}
	elseif($content_type == "audio/wav")
		{
		$data["Email"]["ContentClass"]		= "urn:content-classes:message";
		$data["Email"]["MessageClass"]		= "IPM.Note.Microsoft.Voicemail";

#		$data["Attachments"][$reference]["Email2"]["UmAttDuration"]	= 1;
#		$data["Attachments"][$reference]["Email2"]["UmAttOrder"]	= 1;
#		$data["Attachments"][$reference]["Email2"]["UmCallerID"]	= 0;
#		$data["Attachments"][$reference]["Email2"]["UmUserNotes"]	= "...";
		}
	elseif($content_type == "text/plain")
		{
		$data["Email"]["ContentClass"]		= "urn:content-classes:message";
		$data["Email"]["MessageClass"]		= "IPM.Note";
		}
	elseif($content_type == "text/html")
		{
		$data["Email"]["ContentClass"]		= "urn:content-classes:message";
		$data["Email"]["MessageClass"]		= "IPM.Note";
		}
	elseif($content_type == "text/x-vCalendar")
		{
		$data["Email"]["ContentClass"]		= "urn:content-classes:calendarmessage";
		$data["Email"]["MessageClass"]		= "IPM.Notification.Meeting";
		}

	$reference = active_sync_create_guid();

	$data["Attachments"][] = array
		(
		"AirSyncBase" => array
			(
			"ContentId" => $content_id,
			"IsInline" => ($content_disposition == "inline" ? 1 : 0),
			"DisplayName" => ($content_description == "" ? "..." : $content_description),
			"EstimatedDataSize" => strlen($body),
			"FileReference" => $user . ":" . $collection_id . ":" . $server_id . ":" . $reference,
			"Method" => ($content_disposition == "inline" ? 6 : 1)
			)
		);

	$data["File"][$reference] = array
		(
		"AirSyncBase" => array
			(
			"ContentType" => $content_type
			),
		"ItemOperations" => array
			(
			"Data" => base64_encode($body)
			)
		);
	}
?>
