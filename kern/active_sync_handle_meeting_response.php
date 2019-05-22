<?
function active_sync_handle_meeting_response($request)
	{
	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	if(isset($xml->Request) === true)
		{
		$user_response	= strval($xml->Request->UserResponse);
		$collection_id	= strval($xml->Request->CollectionId);	# inbox
		$request_id	= strval($xml->Request->RequestId);	# server_id
		$long_id	= strval($xml->Request->LongId);
		$instance_id	= strval($xml->Request->InstanceId);	# used if appointment is a recurring one

		$user = $request["AuthUser"];
		$host = active_sync_get_domain();

		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		unlink(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $request_id . ".data");

		$calendar_id = active_sync_get_calendar_by_uid($user, $data["Meeting"]["Email"]["UID"]);

		$collection_id = active_sync_get_collection_id_by_type($user, 8); # Calendar
		# this need to be changed, this function has to return a list of all kind of calendars

		if($calendar_id == "")
			{
			$calendar = array();

			$calendar["Calendar"] = $data["Meeting"]["Email"];

			unset($calendar["Calendar"]["Organizer"]);

			list($organizer_name, $organizer_mail) = active_sync_mail_parse_address($data["Meeting"]["Email"]["Organizer"]);

			foreach(array("OrganizerName" => $organizer_name, "OrganizerEmail" => $organizer_mail) as $token => $value)
				{
				if($value == "")
					continue;

				$calendar["Calendar"][$token] = $value;
				}

			$calendar["Calendar"]["MeetingStatus"] = 3;

			$calendar["Calendar"]["Subject"] = $data["Email"]["Subject"];

			if($user_response == 1)
				$calendar["Calendar"]["ResponseType"] = 3;

			if($user_response == 2)
				$calendar["Calendar"]["ResponseType"] = 2;

			if($user_response == 3)
				$calendar["Calendar"]["ResponseType"] = 4;

			if($user_response != 3)
				{
				$calendar_id = active_sync_create_guid_filename($user, $collection_id);

				active_sync_put_settings_data($user, $collection_id, $calendar_id, $calendar);
				}

			$boundary = active_sync_create_guid();

			$description = array();

			$description[] = "Wann: " . date("d.m.Y H:i:s", strtotime($data["Meeting"]["Email"]["StartTime"]));

			if(isset($data["Meeting"]["Email"]["Location"]) === true)
				$description[] = "Wo: " . $data["Meeting"]["Email"]["Location"];

			$description[] = "*~*~*~*~*~*~*~*~*~*";

			if(isset($data["Body"]))
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

			$mime = array();

			$mime[] = "From: " . $data["Email"]["To"];
			$mime[] = "To: " . $data["Email"]["From"];

			foreach(array("Accepted" => 1, "Tentative" => 2, "Declined" => 3) as $subject => $value)
				{
				if($user_response != $value)
					continue;

				$mime[] = "Subject: " . $subject . ": " . $data["Email"]["Subject"];
				}

			$mime[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/plain; charset=\"utf-8\"";
			$mime[] = "";
			$mime[] = implode("\n", $description);
			$mime[] = "";

			foreach(array("Accepted" => 1, "Tentative" => 2, "Declined" => 3) as $message => $value)
				{
				if($user_response != $value)
					continue;

				$mime[] = $message;
				}

			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/calendar; method=REPLY; name=\"invite.ics\"";
			$mime[] = "";
			$mime[] = "BEGIN:VCALENDAR";
				$mime[] = "METHOD:REPLY";
				$mime[] = "PRODID:" . active_sync_get_version();
				$mime[] = "VERSION:2.0";
				# VTIMEZONE
				$mime[] = "BEGIN:VEVENT";
					$mime[] = "UID:" . $data["Meeting"]["Email"]["UID"];

					foreach(array("DTSTAMP" => "DtStamp", "DTSTART" => "StartTime", "DTEND" => "EndTime") as $key => $token)
						$mime[] = $key . ":" . date("Y-m-d\TH:i:s\Z", strtotime($data["Meeting"]["Email"][$token]));

					if(isset($data["Meeting"]["Location"]) === true)
						$mime[] = "LOCATION: " . $data["Meeting"]["Email"]["Location"];

					if(isset($data["Email"]["Subject"]) === true)
						$mime[] = "SUMMARY: " . $data["Email"]["Subject"]; # take this from email subject

					$mime[] = "DESCRIPTION:" . implode("\\n", $description);

					foreach(array("FALSE" => 0, "TRUE" => 1) as $key => $value)
						{
						if($data["Meeting"]["Email"]["AllDayEvent"] != $value)
							continue;

						$mime[] = "X-MICROSOFT-CDO-ALLDAYEVENT:" . $key;
						}

					foreach(array("ACCEPTED" => 1, "TENTATIVE" => 2, "DECLINED" => 3) as $partstat => $value)
						{
						if($user_response != $value)
							continue;

						$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=" . $partstat . ";RSVP=TRUE:MAILTO:" . $user . "@" . $host;
						}

					list($organizer_name, $organizer_mail) = active_sync_mail_parse_address($data["Meeting"]["Email"]["Organizer"]);

					if($organizer_name == "")
						$mime[] = "ORGANIZER:MAILTO:" . $organizer_mail;
					else
						$mime[] = "ORGANIZER;CN=\"" . $organizer_name . "\":MAILTO:" . $organizer_mail;

					$mime[] = "STATUS:CONFIRMED";
					$mime[] = "TRANSP:OPAQUE";
					$mime[] = "PRIORITY:5";
					$mime[] = "SEQUENCE:0";

				$mime[] = "END:VEVENT";
			$mime[] = "END:VCALENDAR";
			$mime[] = "";
			$mime[] = "--" . $boundary . "--";

			$mime = implode("\n", $mime);

			active_sync_send_mail($user, $mime);
			}

		# http://msdn.microsoft.com/en-us/library/exchange/hh428684%28v=exchg.140%29.aspx
		# http://msdn.microsoft.com/en-us/library/exchange/hh428685%28v=exchg.140%29.aspx

		$response->x_switch("MeetingResponse");

		$response->x_open("MeetingResponse");

			$response->x_open("Result");

				foreach(array("Status" => 1, "RequestId" => $request_id, "CalendarId" => $calendar_id) as $token => $value)
					{
					$response->x_open($token);
						$response->x_print($value);
					$response->x_close($token);
					}

			$response->x_close("Result");

		$response->x_close("MeetingResponse");
		}

	return($response->response);
	}
?>
