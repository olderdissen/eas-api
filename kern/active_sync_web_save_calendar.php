<?
function active_sync_web_save_calendar($request)
	{
	$data = array();

	foreach(active_sync_get_default_calendar() as $token => $default_value)
		{
		if(isset($_POST[$token]) === false)
			continue;

		if(strlen($_POST[$token]) == 0)
			continue;

		$data["Calendar"][$token] = $_POST[$token];
		}

	$body = array();

	foreach(active_sync_get_default_body() as $token => $value)
		{
		if(isset($_POST["Body:" . $token]) === false)
			continue;

		if(strlen($_POST["Body:" . $token]) == 0)
			continue;

		$body[$token] = $_POST["Body:" . $token];
		}

	if(isset($body["Type"]))
		if($body["Type"] == 1)
			if(isset($body["Data"]))
				if(strlen($body["Data"]) > 0)
					$body["Body"][] = $body;

	if(isset($_POST["Attendees"]) === false)
		$data["Calendar"]["MeetingStatus"] = 0;
	else
		{
		$data["Calendar"]["MeetingStatus"] = 1;

		foreach($_POST["Attendees"] as $attendee_id => $attendee_data)
			{
			list($attendee_name, $attendee_mail) = active_sync_mail_parse_address($attendee_data);

			$temp = array();

			if($attendee_name != "")
				$temp["Name"] = $attendee_name;

			if($attendee_mail != "")
				$temp["Email"] = $attendee_mail;

			if(count($temp) > 0)
				{
				$temp["AttendeeType"] = 1;

				$data["Attendees"][] = $temp;
				}
			}
		}

	$fields = array(0x02, 0x02, 0x18, 0x13, 0x00, 0x1C, 0x17, "WeekOfMonth", "DayOfWeek", "MonthOfYear", "DayOfMonth");

	for($i = 0; $i < 4; $i = $i + 1)
		{
		$field = $fields[$i + 7];

		if((($fields[$_POST["Recurrence:Type"]] >> $i) & 0x01) == 0x00)
			continue;

		if($_POST["Recurrence:" . $field] == "")
			continue;

		$data["Recurrence"][$field] = $_POST["Recurrence:" . $field];
		}

	if((($_POST["Recurrence:Type"] == 3) || ($_POST["Recurrence:Type"] == 6)) && ($_POST["Recurrence:DayOfWeek"] == 127))
		unset($data["Recurrence"]["WeekOfMonth"]);

	if($_POST["Recurrence:Type"] != 4)
		{
		foreach(array("Type", "Occurrences", "Interval", "Until", "CalendarType", "IsLeapMonth", "FirstDayOfWeek") as $token)
			{
			if($_POST["Recurrence:" . $token] == "")
				continue;

			$data["Recurrence"][$token] = $_POST["Recurrence:" . $token];
			}
		}

	foreach(array("StartTime", "EndTime", "DtStamp") as $token)
		$data["Calendar"][$token] = date("Ymd\THis\Z", strtotime($data["Calendar"][$token]) - 7200); # time of appointment - timezone of appointment - timezone of server

	if(isset($data["Calendar"]["TimeZone"]) === true)
		{
		$timezone_values = active_sync_get_table_timezone_information();

		$data["Calendar"]["TimeZone"] = $timezone_values[$data["Calendar"]["TimeZone"]][0];
		}

	if(isset($data["Attendees"]) === false)
		{
		}
	elseif(count($data["Attendees"]) > 0)
		{
		foreach($data["Attendees"] as $attendee_id => $attendee_data)
			{
			$boundary = active_sync_create_guid();

			$description = array();

			$description[] = "Wann: " . date("d.m.Y H:i:s", strtotime($data["Calendar"]["StartTime"]));

			if(isset($data["Calendar"]["Location"]) === true)
				$description[] = "Wo: " . $data["Calendar"]["Location"];

			$description[] = "*~*~*~*~*~*~*~*~*~*";

			if(isset($data["Body"]["Data"]) === true)
				$description[] = $data["Body"]["Data"];

			$mime = array();

			if(isset($data["Calendar"]["OrganizerName"]) === false)
				$mime[] = "From: \"" . $data["Calendar"]["OrganizerEmail"] . "\" <" . $data["Calendar"]["OrganizerEmail"] . ">";
			else
				$mime[] = "From: <" . $data["Calendar"]["OrganizerEmail"] . ">";

			if(isset($attendee_data["Name"]) === false)
				$mime[] = "From: \"" . $attendee_data["Name"] . "\" <" . $attendee_data["Email"] . ">";
			else
				$mime[] = "To: <" . $attendee_data["Email"] . ">";

			if($request["ServerId"] == "")
				{
				if(isset($data["Calendar"]["Subject"]) === true)
					$mime[] = "Subject: " . $data["Calendar"]["Subject"];
				}
			else
				{
				if(isset($data["Calendar"]["Subject"]) === false)
					$mime[] = "Subject: Aktualisiert: ";
				else
					$mime[] = "Subject: Aktualisiert: " . $data["Calendar"]["Subject"];
				}

			$mime[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/plain; charset=\"utf-8\"";
			$mime[] = "";
			$mime[] = implode("\n", $description);
			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/calendar; method=REQUEST; name=\"invite.ics\"";
			$mime[] = "";
			$mime[] = "BEGIN:VCALENDAR";
				$mime[] = "METHOD:REQUEST";
				$mime[] = "PRODID:" . active_sync_get_version();
				$mime[] = "VERSION:2.0";
				# VTIMEZONE
				$mime[] = "BEGIN:VEVENT";
					$mime[] = "UID:" . $data["Calendar"]["UID"];

					foreach(array("DTSTAMP" => "DtStamp", "DTSTART" => "StartTime", "DTEND" => "EndTime") as $key => $token)
						$mime[] = $key . ":" . date("Y-m-d\TH:i:s\Z", strtotime($data["Calendar"][$token]));

					foreach(array("LOCATION" => "Location", "SUMMARY" => "Subject") as $key => $token)
						{
						if(isset($data["Calendar"][$token]) === false)
							continue;

						$mime[] = $key . ": " . $data["Calendar"][$token];
						}

					$mime[] = "DESCRIPTION:" . implode("\\n", $description);

					foreach(array("FALSE" => 0, "TRUE" => 1) as $key => $value)
						{
						if($data["Calendar"]["AllDayEvent"] != $value)
							continue;

						$mime[] = "X-MICROSOFT-CDO-ALLDAYEVENT:" . $value;
						}

					if(isset($attendee_data["Name"]) === true)
						$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=\"" . $attendee_data["Name"] . "\":MAILTO:" . $attendee_data["Email"];
					else
						$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:MAILTO:" . $attendee_data["Email"];

					if(isset($data["Calendar"]["OrganizerName"]) === false)
						$mime[] = "ORGANIZER:MAILTO:" . $data["Calendar"]["OrganizerEmail"];
					else
						$mime[] = "ORGANIZER;CN=\"" . $data["Calendar"]["OrganizerName"] . "\":MAILTO:" . $data["Calendar"]["OrganizerEmail"];

					$mime[] = "STATUS:CONFIRMED";
					$mime[] = "TRANSP:OPAQUE";
					$mime[] = "PRIORITY:5";
					$mime[] = "SEQUENCE:0";

					if(isset($data["Calendar"]["Reminder"]) === true)
						{
						$mime[] = "BEGIN:VALARM";
							$mime[] = "ACTION:DISPLAY";
							$mime[] = "DESCRIPTION:REMINDER";
							$mime[] = "TRIGGER:-PT" . $data["Calendar"]["Reminder"] . "M";
						$mime[] = "END:VALARM";
						}

				$mime[] = "END:VEVENT";
			$mime[] = "END:VCALENDAR";
			$mime[] = "";
			$mime[] = "--" . $boundary . "--";

			$mime = implode("\n", $mime);

			active_sync_send_mail($request["AuthUser"], $mime);
			}
		}

	if($request["ServerId"] == "")
		$request["ServerId"] = active_sync_create_guid_filename($request["AuthUser"], $request["CollectionId"]);

	active_sync_put_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"], $data);

	print(1);
	}
?>
