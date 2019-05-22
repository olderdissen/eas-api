<?
function active_sync_mail_add_container_c(& $data, $body, $user)
	{
	$host = active_sync_get_domain(); # needed for user@host

	$temp = $body;
	$vcalendar = active_sync_vcalendar_parse($body);
	$body = $temp;

	foreach(active_sync_get_default_meeting() as $token => $value)
		$data["Meeting"]["Email"][$token] = $value;

	$timezone_informations = active_sync_get_table_timezone_information();

	$data["Meeting"]["Email"]["TimeZone"] = $timezone_informations[28][0];

	$codepage_table = array();

	$codepage_table["Email"] = array("DTSTART" => "StartTime", "DTSTAMP" => "DtStamp", "DTEND" => "EndTime", "LOCATION" => "Location");
	$codepage_table["Calendar"] = array("UID" => "UID");

	foreach($codepage_table as $codepage => $null)
		{
		foreach($codepage_table[$codepage] as $key => $token)
			{
			if(isset($vcalendar["VCALENDAR"]["VEVENT"][$key]) === false)
				continue;

			$data["Meeting"][$codepage][$token] = $vcalendar["VCALENDAR"]["VEVENT"][$key];
			}
		}

	########################################################################
	# check MeetingStatus
	########################################################################
	# 0	The event is an appointment, which has no attendees.
	# 1	The event is a meeting and the user is the meeting organizer.
	# 3	This event is a meeting, and the user is not the meeting organizer; the meeting was received from someone else.
	# 5	The meeting has been canceled and the user was the meeting organizer.
	# 7	The meeting has been canceled. The user was not the meeting organizer; the meeting was received from someone else.
	########################################################################
	# 0x01 The event is a meeting
	# 0x02 The user is/was not the meeting organizer; the meeting was received from someone else.
	# 0x04 The meeting has been canceled.
	########################################################################

#	$data["Meeting"]["Email"]["MeetingStatus"] = 0;

	$organizer = (isset($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"][$user . "@" . $host]) === false ? 0 : 1);

	foreach(array("CANCEL" => array(7, 5), "REQUEST" => array(3, 1)) as $key => $value)
		{
		if($vcalendar["VCALENDAR"]["METHOD"] != $key)
			continue;

#		$data["Meeting"]["Email"]["MeetingStatus"] = $value[$organizer];
		}

	########################################################################
	# check MeetingMessageType
	########################################################################
	# 0	A silent update was performed, or the message type is unspecified.
	# 1	Initial meeting request.
	# 3	Informational update.
	########################################################################

	$data["Meeting"]["Email2"]["MeetingMessageType"] = 0;

	foreach(array("CANCEL" => 0, "REPLY" => 3, "REQUEST" => 1) as $key => $value)
		if($vcalendar["VCALENDAR"]["METHOD"] == $key)
			$data["Meeting"]["Email2"]["MeetingMessageType"] = $value;

	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["CLASS"]) === true)
		{
		foreach(array("DEFAULT" => 0, "PUBLIC" => 1, "PRIVATE" => 2, "CONFIDENTIAL" => 3) as $key => $value)
			if($vcalendar["VCALENDAR"]["VEVENT"]["CLASS"] == $key)
				$data["Meeting"]["Email"]["Sensitivity"] = $value;
		}

	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["X-MICROSOFT-CDO-ALLDAYEVENT"]) === true)
		{
#		$data["Meeting"]["Email"]["AllDayEvent"] = 0;

		foreach(array("FALSE" => 0, "TRUE" => 1) as $key => $value)
			if($vcalendar["VCALENDAR"]["VEVENT"]["X-MICROSOFT-CDO-ALLDAYEVENT"] == $key)
				$data["Meeting"]["Email"]["AllDayEvent"] = $value;
		}

	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"]) === true)
		{
#		$data["Meeting"]["Email"]["Organizer"] = $user . "@" . $host;

		foreach($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"] as $key => $null)
			$data["Meeting"]["Email"]["Organizer"] = $key;
		}

	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$user . "@" . $host]["RVSP"]) === true)
		{
		foreach(array("FALSE" => 0, "TRUE" => 1) as $key => $value)
			{
			$data["Meeting"]["Email"]["ResponseRequested"] = 0;

			if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"]["RVSP"] == $key)
				$data["Meeting"]["Email"]["ResponseRequested"] = $value;
			}
		}

	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["VALARM"]["TRIGGER"]) === true)
		$data["Meeting"]["Email"]["Reminder"] = substr($vcalendar["VCALENDAR"]["VEVENT"]["VALARM"]["TRIGGER"], 3, 0 - 1); # -PT*M

#	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["RRULE"]) === true)
#		foreach(array("FREQ" => "Type", "COUNT" => "Occurences", "INTERVAL" => "Interval") as $key => $token)
#			if(isset($vcalendar["VCALENDAR"]["VEVENT"]["RRULE"][$key]) === true)
#				$data["Meeting"]["reccurence"][0][$token] = $vcalendar["VCALENDAR"]["VEVENT"]["RRULE"][$key];

	if(active_sync_body_type_exist($data, 1) == 0)
		{
		$new_temp_message = array();

		$new_temp_message[] = "Wann: " . date("d.m.Y H:i", strtotime($vcalendar["VCALENDAR"]["VEVENT"]["DTSTART"]));

		if(isset($vcalendar["VCALENDAR"]["VEVENT"]["LOCATION"]) === true)
			$new_temp_message[] = "Wo: " . $vcalendar["VCALENDAR"]["VEVENT"]["LOCATION"];

		$new_temp_message[] = "*~*~*~*~*~*~*~*~*~*";

		if(isset($vcalendar["VCALENDAR"]["VEVENT"]["DESCRIPTION"]) === true)
			$new_temp_message[] = $vcalendar["VCALENDAR"]["VEVENT"]["DESCRIPTION"];


#		if(isset($vcalendar["VCALENDAR"]["VEVENT"]["SUMMARY"]) === true)
#			$new_temp_message[] = $vcalendar["VCALENDAR"]["VEVENT"]["SUMMARY"]; # this must be calendar:body:data, not calendar:subject, but calendar:body:data from calendar is not available

		$new_temp_message = implode("\n", $new_temp_message);

		active_sync_mail_add_container_p($data, $new_temp_message);
		}


	$data["Email"]["From"] = (isset($data["Email"]["From"]) === false ? $user . "@" . $host : $data["Email"]["From"]);

	list($f_name, $f_mail) = active_sync_mail_parse_address($data["Email"]["From"]);
	list($t_name, $t_mail) = active_sync_mail_parse_address($data["Email"]["To"]);

	########################################################################
	# just check
	# if we are an attendee and have to delete a meeting from calendar or
	# if we are an organizer and have to update an attendee status.
	# nothing else!
	########################################################################

	if(isset($vcalendar["VCALENDAR"]["METHOD"]))
		if($vcalendar["VCALENDAR"]["METHOD"] === "CANCEL")
			{
			if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"][$user . "@" . $host]) === false)
				if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$user . "@" . $host]) === true)
					{
					$server_id = active_sync_get_calendar_by_uid($user, $vcalendar["VCALENDAR"]["VEVENT"]["UID"]);

					if($server_id != "")
						unlink(DAT_DIR . "/" . $user . "/". active_sync_get_collection_id_by_type($user, 8) . "/" . $server_id . ".data");
					}

			$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
			$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Canceled";
			}
		elseif($vcalendar["VCALENDAR"]["METHOD"] === "PUBLISH")
			{
			}
		elseif($vcalendar["VCALENDAR"]["METHOD"] === "REPLY")
			{
			if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"][$user . "@" . $host]) === true)
				if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$f_mail]) === true)
					{
					$server_id = active_sync_get_calendar_by_uid($user, $vcalendar["VCALENDAR"]["VEVENT"]["UID"]);

					if($server_id != "")
						{
						if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$f_mail]["PARTSTAT"] == "DECLINED")
							{
							$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Neg";

							active_sync_put_attendee_status($user, $server_id, $f_mail, 4);
							}

						if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$f_mail]["PARTSTAT"] == "ACCEPTED")
							{
							$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Pos";

							active_sync_put_attendee_status($user, $server_id, $f_mail, 3);
							}

						if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$f_mail]["PARTSTAT"] == "TENTATIVE")
							{
							$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Tent";

							active_sync_put_attendee_status($user, $server_id, $f_mail, 2);
							}
						}
					}
			}
		elseif($vcalendar["VCALENDAR"]["METHOD"] === "REQUEST")
			{
			$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
			$data["Email"]["MessageClass"] = "IPM.Notification.Meeting";

			if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"][$user . "@" . $host]) === false)
				if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$user . "@" . $host]) === true)
					{
					if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$user . "@" . $host]["PARTSTAT"] == "NEEDS-ACTION")
						{
						$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
						$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Request";
						}

					if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$user . "@" . $host]["PARTSTAT"] != "NEEDS-ACTION")
						{
						$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
						$data["Email"]["MessageClass"] = "IPM.Notification.Meeting";
						}
					}
			}
	}
?>
