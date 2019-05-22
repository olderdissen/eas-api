<?
function active_sync_web_delete_calendar($request)
	{
	$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]);

	if(isset($data["Attendees"]) === false)
		{
		}
	elseif(count($data["Attendees"]) > 0)
		{
		foreach($data["Attendees"] as $attendee_id => $attendee_data)
			{
			$boundary = active_sync_create_guid();

			$description = array();

			if(isset($data["Calendar"]["StartTime"]) === true)
				$description[] = "Wann: " . date("d.m.Y H:i:s", strtotime($data["Calendar"]["StartTime"]));

			if(isset($data["Calendar"]["Location"]) === true)
				$description[] = "Wo: " . $data["Calendar"]["Location"];

			$description[] = "*~*~*~*~*~*~*~*~*~*";

			if(isset($data["Body"]) === true)
				{
				foreach($data["Body"] as $body)
					{
					if(isset($body["Type"]) === false)
						continue;
					
					if($body["Type"] != 1)
						continue;

					if(isset($body["Data"]) === false)
						continue;

					$description[] = $body["Data"];
					}
				}

			$description = implode("\n", $description);

			$mime = array();

			if(isset($data["Calendar"]["OrganizerName"]) === false)
				$mime[] = "From: \"" . $data["Calendar"]["OrganizerEmail"] . "\" <" . $data["Calendar"]["OrganizerEmail"] . ">";
			else
				$mime[] = "From: <" . $data["Calendar"]["OrganizerEmail"] . ">";

			if(isset($attendee_data["Name"]) === false)
				$mime[] = "From: \"" . $attendee_data["Name"] . "\" <" . $attendee_data["Email"] . ">";
			else
				$mime[] = "To: <" . $attendee_data["Email"] . ">";

			if(isset($data["Calendar"]["Subject"]) === false)
				$mime[] = "Subject: Abgesagt: ";
			else
				$mime[] = "Subject: Abgesagt: " . $data["Calendar"]["Subject"];

			$mime[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/plain; charset=\"utf-8\"";
			$mime[] = "";
			$mime[] = $description;
			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/calendar; method=CANCEL; name=\"invite.ics\"";
			$mime[] = "";
			$mime[] = "BEGIN:VCALENDAR";
				$mime[] = "METHOD:CANCEL";
				$mime[] = "PRODID:" . active_sync_get_version();
				$mime[] = "VERSION:2.0";
				# VTIMEZONE
				$mime[] = "BEGIN:VEVENT";
					$mime[] = "UID:" . $data["Calendar"]["UID"];

					foreach(array("DTSTAMP" => "DtStamp", "DTSTART" => "StartTime", "DTEND" => "EndTime") as $key => $token)
						$mime[] = $key . ":" . date("Ymd\THis\Z", strtotime($data["Calendar"][$token]));

					foreach(array("LOCATION" => "Location", "SUMMARY" => "Subject") as $key => $token)
						if(isset($data["Calendar"][$token]) === true)
							$mime[] = $key . ": " . $data["Calendar"][$token];

					$mime[] = "DESCRIPTION:" . implode("\\n", $description);

					foreach(array("FALSE" => 0, "TRUE" => 1) as $key => $value)
						if($data["Calendar"]["AllDayEvent"] == $value)
							$mime[] = "X-MICROSOFT-CDO-ALLDAYEVENT:" . $key;

					if(isset($attendee_data["Name"]) === true)
						$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=\"" . $attendee_data["Name"] . "\":MAILTO:" . $attendee_data["Email"];
					else
						$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:MAILTO:" . $attendee_data["Email"];

					if(isset($data["Calendar"]["OrganizerName"]) === false)
						$mime[] = "ORGANIZER:MAILTO:" . $data["Calendar"]["OrganizerEmail"];
					else
						$mime[] = "ORGANIZER;CN=\"" . $data["Calendar"]["OrganizerName"] . "\":MAILTO:" . $data["Calendar"]["OrganizerEmail"];

					$mime[] = "STATUS:CANCELLED";
					$mime[] = "TRANSP:OPAQUE";
					$mime[] = "PRIORITY:5";
					$mime[] = "SEQUENCE:0";

				$mime[] = "END:VEVENT";
			$mime[] = "END:VCALENDAR";
			$mime[] = "";
			$mime[] = "--" . $boundary . "--";

			$mime = implode("\n", $mime);

			active_sync_send_mail($request["AuthUser"], $mime);
			}
		}

	$file = DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data";

	if(file_exists($file) === false)
		$status = 8;
	elseif(unlink($file) === false)
		$status = 8;
	else
		$status = 1;

	print($status);
	}
?>
