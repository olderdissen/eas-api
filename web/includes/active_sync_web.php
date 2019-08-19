<?
function active_sync_web($request)
	{
	$table = array
		(
		"Data"		=> "active_sync_web_data",
		"Delete"	=> "active_sync_web_delete",
		"Edit"		=> "active_sync_web_edit",
		"List"		=> "active_sync_web_list",
		"Meeting"	=> "active_sync_web_meeting",
		"Print"		=> "active_sync_web_print",
		"Save"		=> "active_sync_web_save"
		);

	$retval = null;

	foreach($table as $command => $function)
		{
		if($request["Cmd"] != $command)
			continue;

		if(function_exists($function) === false)
			continue;

		$retval = $function($request);
		}

	return($retval);
	}

function active_sync_web_data($request)
	{
	$table = array
		(
		"Calendar"	=> "active_sync_web_data_calendar",
		"Contacts"	=> "active_sync_web_data_contacts",
		"Email"		=> "active_sync_web_data_email",
		"Notes"		=> "active_sync_web_data_notes",
		"Tasks"		=> "active_sync_web_data_tasks"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		{
		if($default_class != $class)
			continue;

		if(function_exists($function) === false)
			continue;

		$retval = $function($request);
		}

	return($retval);
	}

function active_sync_web_data_calendar($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	$settings["Settings"]["CalendarSync"] = (isset($settings["Settings"]["CalendarSync"]) === false ? 0 : $settings["Settings"]["CalendarSync"]);

	$calendar_sync = array("", "- 1 week", "- 1 month", "- 3 month", "- 6 month");

	$retval = array();

	if(strlen($request["StartTime"]) == 0)
		{
		# StartTime is missed
		}
	elseif(strlen($request["EndTime"]) == 0)
		{
		# EndTime is missed
		}
	else
		{
		foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
			{
			$server_id = basename($file, ".data");

			$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

			foreach(array("EndTime" => 0, "StartTime" => 0, "AllDayEvent" => 0, "Subject" => "", "Location" => "") as $token => $value)
				$data["Calendar"][$token] = (isset($data["Calendar"][$token]) === false ? $value : $data["Calendar"][$token]);

			foreach(array("Interval" => 1, "Occurrences" => 1) as $token => $value)
				$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) === false ? $value : $data["Recurrence"][$token]);

			$data["Calendar"]["StartTime"]	= strtotime($data["Calendar"]["StartTime"]);
			$data["Calendar"]["EndTime"]	= strtotime($data["Calendar"]["EndTime"]);

			foreach(range(1, $data["Recurrence"]["Occurrences"]) as $i)
				{
				$add = 0;

				if(($request["StartTime"] == "*") && ($request["EndTime"] == "*")) # request by agenda view
					{
					if($data["Calendar"]["StartTime"] >= strtotime($calendar_sync[$settings["Settings"]["CalendarSync"]])) # starts at selected day, ends at selected day
						{
						$add = 1;
						}
					}
				elseif($request["StartTime"] == "*")
					{
					}
				elseif($request["EndTime"] == "*")
					{
					}
				elseif($data["Calendar"]["EndTime"] <= $request["StartTime"])
					{
					}
				elseif($data["Calendar"]["StartTime"] >= $request["EndTime"])
					{
					}
				else
					{
					$add = 1;
					}

				if($add == 1)
					$retval[] = array($data["Calendar"]["StartTime"], $data["Calendar"]["EndTime"], $data["Calendar"]["AllDayEvent"], $server_id, $data["Calendar"]["Subject"], $data["Calendar"]["Location"]);

				$data["Calendar"]["StartTime"]	= $data["Calendar"]["StartTime"]  + ($data["Recurrence"]["Interval"] * 86400);
				$data["Calendar"]["EndTime"]	= $data["Calendar"]["EndTime"] + ($data["Recurrence"]["Interval"] * 86400);
				}
			}
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}

function active_sync_web_data_contacts($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	foreach(array("ShowBy" => 1, "SortBy" => 1, "PhoneOnly" => 0) as $key => $value)
		$settings["Settings"][$key] = (isset($settings["Settings"][$key]) === false ? $value : $settings["Settings"][$key]);

	$retval = array();

	foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		$active = 0;

		if(isset($_GET["Category"]) === false) # ...
			{
			if(isset($data["Categories"]))
				if(count($data["Categories"]) > 0)
					$active = 1;
			}
		elseif($_GET["Category"] == "") # nicht zugewiesen
			{
			if(isset($data["Categories"]) === false) # no categories found
				$active = 1;
			elseif(count($data["Categories"]) == 0) # empty categories found
				$active = 1;
			}
		elseif($_GET["Category"] != "*") # alle
			{
			if(isset($data["Categories"]))
				if(count($data["Categories"]) > 0)
					if(in_array($_GET["Category"], $data["Categories"]))
						{
						$active = 1;
						}
			}
		elseif(isset($settings["Categories"]) === false) # no setup found
			$active = 1;
		elseif(count($settings["Categories"]) == 0) # empty setup found
			$active = 1;
		elseif(isset($data["Categories"]) === false) # no categories found
			{
			if(isset($settings["Categories"]["*"]))
				if($settings["Categories"]["*"] == 1)
					$active = 1;
			}
		elseif(count($data["Categories"]) == 0) # empty categories found
			{
			if(isset($settings["Categories"]["*"]))
				if($settings["Categories"]["*"] == 1)
					$active = 1;
			}
		else
			{
			foreach($data["Categories"] as $category)
				{
				if(isset($settings["Categories"][$category]) === false) # no setup found
					{
					$active = 1;

					break;
					}

				if($settings["Categories"][$category] == 1)
					{
					$active = 1;

					break;
					}
				}
			}

		if($active == 0)
			continue;

		$name_show = active_sync_create_fullname_from_data_for_contacts($data, $settings["Settings"]["ShowBy"]);
		$name_sort = active_sync_create_fullname_from_data_for_contacts($data, $settings["Settings"]["SortBy"]);

		$name_sort = str_replace(array("ä", "Ä", "ö", "Ö", "ü,", "Ü", "ß"), array("a", "A", "o", "O", "u", "U", "s"), $name_sort);
#		$name_sort = active_sync_normalize_chars($name_sort);
		$name_sort = strtolower($name_sort);

		$picture = (isset($data["Contacts"]["Picture"]) === false ? "/active-sync/web/images/contacts_default_image_small.png" : "data:image/unknown;base64," . $data["Contacts"]["Picture"]);

		$search_entry = array($name_sort, $name_show, $server_id, $picture);

		if($settings["Settings"]["PhoneOnly"] != 0)
			if(active_sync_get_is_phone_available($data) == 0)
				continue;

		$add = 0;

		if(isset($_GET["Search"]) === false)
			$add = 1;
		elseif($_GET["Search"] == "")
			$add = 1;

		if($add == 0)
			$add = active_sync_compare_phone($data, $_GET["Search"]);

		if($add == 0)
			$add = active_sync_compare_address($data, $_GET["Search"]);

		if($add == 0)
			$add = active_sync_compare_other($data, $_GET["Search"]);

		if($add == 0)
			$add = active_sync_compare_name($data, $_GET["Search"]);

		if($add == 1)
			$retval[] = $search_entry;
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}

function active_sync_web_data_email($request)
	{
	$retval = array();

	foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		if(isset($data["AirSync"]["Class"]) === false)
			continue;

		foreach(array("From" => "", "Importance" => 1, "Read" => 0, "Subject" => "", "To" => "", "MessageClass" => "IPM.Note") as $token => $value)
			$data["Email"][$token] = (isset($data["Email"][$token]) === false ? $value : $data["Email"][$token]);

		foreach(array("LastVerbExecuted" => 0) as $token => $value)
			$data["Email2"][$token] = (isset($data["Email2"][$token]) === false ? $value : $data["Email2"][$token]);

		$data["Email"]["DateReceived"] = (isset($data["Email"]["DateReceived"]) === false ? date("Y-m-d\TH:i:s\Z", filemtime(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $file)) : $data["Email"]["DateReceived"]);

		$add = 0;

		$body = "...";

		foreach($data["Body"] as $body)
			{
			if(isset($body["Type"]) === false)
				continue;

			if($body["Type"] != 1)
				continue;

			$body = $body["Data"];
			}

		if($data["AirSync"]["Class"] == "Email")
			{
			$class				= $data["AirSync"]["Class"];

			$from				= $data["Email"]["From"];
			$to				= $data["Email"]["To"];
			$date_received			= $data["Email"]["DateReceived"];
			$importance			= $data["Email"]["Importance"];
			$read				= $data["Email"]["Read"];
			$subject			= $data["Email"]["Subject"];

			$status				= (isset($data["Flag"]["Email"]["Status"]) ? $data["Flag"]["Email"]["Status"] : 0);
			$attachments			= (isset($data["file"]) ? 1 : 0);

			$message_class			= $data["Email"]["MessageClass"];
			$last_verb_executed		= $data["Email2"]["LastVerbExecuted"];

			$add = 1;
			}

		if($data["AirSync"]["Class"] == "SMS")
			{
			$class				= $data["AirSync"]["Class"];

			$from				= $data["Email"]["From"];
			$to				= $data["Email"]["To"];
			$date_received			= $data["Email"]["DateReceived"];
			$importance			= $data["Email"]["Importance"]; # how can sender determine the importance ???
			$read				= $data["Email"]["Read"];
			$subject			= utf8_encode(substr(utf8_decode($body) , 0, 80)); # doesn't matter if message is shorter than 80 chars

			$status				= (isset($data["Flag"]["Email"]["Status"]) ? $data["Flag"]["Email"]["Status"] : null);
			$attachments			= 0;

			$message_class			= null;
			$last_verb_executed		= $data["Email2"]["LastVerbExecuted"];

			$add = 1;
			}

		if($add == 1)
			{
			// from and to must be changed on outbox
			// name and mail must already be split here
			$from		= str_replace(array("<", ">"), array("&lt;", "&gt;"), $from);
			$to		= str_replace(array("<", ">"), array("&lt;", "&gt;"), $to);

			$date_received	= strtotime($date_received);

			$retval[] = array($date_received, $from, $to, $subject, $read, $status, $server_id, $class, $importance, $attachments, $message_class, $last_verb_executed);
			}
		}

	if(count($retval) > 1)
		rsort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}

function active_sync_web_data_notes($request)
	{
	$retval = array();

	foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		foreach(array("Subject", "LastModifiedDate") as $token)
			$data["Notes"][$token] = (isset($data["Notes"][$token]) === false ? "..." : $data["Notes"][$token]);

		$subject		= $data["Notes"]["Subject"];
		$last_modified_date	= $data["Notes"]["LastModifiedDate"];

		$last_modified_date	= strtotime($last_modified_date);

		$retval[] = array($subject, $server_id, $last_modified_date);
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}

function active_sync_web_data_tasks($request)
	{
	$retval = array();

	foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		foreach(array("DueDate", "Sensitivity", "StartDate", "Subject") as $key)
			$data["Tasks"][$key] = (isset($data["Tasks"][$key]) === false ? "" : $data["Tasks"][$key]);

		$due_date	= $data["Tasks"]["DueDate"];
		$start_date	= $data["Tasks"]["StartDate"];
		$subject	= $data["Tasks"]["Subject"];

		$due_date	= strtotime($due_date);
		$start_date	= strtotime($start_date);

		$retval[] = array($start_date, $due_date, $server_id, $subject);
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}

function active_sync_web_delete($request)
	{
	$table = array
		(
		"Calendar"	=> "active_sync_web_delete_calendar",
		"Contacts"	=> "active_sync_web_delete_contacts",
		"Email"		=> "active_sync_web_delete_email",
		"Notes"		=> "active_sync_web_delete_notes",
		"Tasks"		=> "active_sync_web_delete_tasks"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		{
		if($default_class != $class)
			continue;

		if(function_exists($function) === false)
			continue;

		$retval = $function($request);
		}

	return($retval);
	}

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

function active_sync_web_delete_contacts($request)
	{
	$file = DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data";

	if(file_exists($file) === false)
		$status = 8;
	elseif(unlink($file) === false)
		$status = 8;
	else
		$status = 1;

	print($status);
	}

function active_sync_web_delete_email($request)
	{
	$file = DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data";

	if(file_exists($file) === false)
		$status = 8;
	elseif(unlink($file) === false)
		$status = 8;
	else
		$status = 1;

	print($status);
	}

function active_sync_web_delete_notes($request)
	{
	$file = DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data";

	if(file_exists($file) === false)
		$status = 8;
	elseif(unlink($file) === false)
		$status = 8;
	else
		$status = 1;

	print($status);
	}

function active_sync_web_delete_tasks($request)
	{
	$file = DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data";

	if(file_exists($file) === false)
		$status = 8;
	elseif(unlink($file) === false)
		$status = 8;
	else
		$status = 1;

	print($status);
	}

function active_sync_web_edit($request)
	{
	$table = array
		(
		"Calendar"	=> "active_sync_web_edit_calendar",
		"Contacts"	=> "active_sync_web_edit_contacts",
		"Email"		=> "active_sync_web_edit_email",
		"Notes"		=> "active_sync_web_edit_notes",
		"Tasks"		=> "active_sync_web_edit_tasks"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		{
		if($default_class != $class)
			continue;

		if(function_exists($function) === false)
			continue;

		$retval = $function($request);
		}

	return($retval);
	}

function active_sync_web_edit_calendar($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_calendar() as $token => $value)
		$data["Calendar"][$token] = (isset($data["Calendar"][$token]) === false ? $value : $data["Calendar"][$token]);

	foreach(active_sync_get_default_recurrence() as $token => $value)
		$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) === false ? $value : $data["Recurrence"][$token]);

	foreach(array("Attendees") as $key)
		$data[$key] = (isset($data[$key]) === false ? array() : $data[$key]);

	if(isset($data["Body"]) === false)
		$data["Body"][] = active_sync_get_default_body();

	foreach($data["Body"] as $body)
		{
		if(isset($body["Type"]) === false)
			continue;

		if($body["Type"] != 1)
			continue;

		foreach(active_sync_get_default_body() as $token => $value)
			$data["Body"][0][$token] = (isset($body[$token]) === false ? $value : $body[$token]);
		}

	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	$timezone_values = active_sync_get_table_timezone_information();

	$host = active_sync_get_domain();

#	$data["Calendar"]["Reminder"] = (isset($data["Calendar"]["Reminder"]) ? $data["Calendar"]["Reminder"] : ($request["ServerId"] == "" ? $settings["Settings"]["Reminder"] : 0));

#	foreach(array("FirstDayOfWeek" => $settings["Settings"]["FirstDayOfWeek"], "IsLeapMonth" => isset($data["Recurrence"]["MonthOfYear"]) ? $data["Recurrence"]["MonthOfYear"] == 2 ? 1 : 0 : 0) as $key => $value)
#		$data["Recurrence"][$key] = (isset($data["Recurrence"][$key]) ? $data["Recurrence"][$key] : $value);

	if($request["ServerId"] == "")
		{
		$login = active_sync_get_settings(DAT_DIR . "/login.data");

		foreach($login["login"] as $user)
			{
			if($user["User"] != $request["AuthUser"])
				continue;

			$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

			foreach(array("OrganizerName" => $user["DisplayName"], "OrganizerEmail" => $request["AuthUser"] . "@" . $host , "Reminder" => $settings["Settings"]["Reminder"]) as $token => $value)
				{
				if($value == "")
					continue;

				$data["Calendar"][$token] = $value;
				}

			foreach(array("StartTime" => 0, "EndTime" => 3600) as $token => $value)
				{
				$cookie = (isset($_COOKIE["time_id"]) === false ? time() : substr($_COOKIE["time_id"], 1) / 1000);

				$data["Calendar"][$token] = date("Ymd\THis\Z", date("U", $cookie) + $value);
				}
			}
		}

	foreach(array("StartTime", "EndTime", "DtStamp") as $token)
		$data["Calendar"][$token] = date("d.m.Y H:i", strtotime($data["Calendar"][$token]) + 7200);

	print("<form>");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");
		print("<input type=\"hidden\" name=\"DtStamp\" value=\"" . $data["Calendar"]["DtStamp"] . "\">");
		print("<input type=\"hidden\" name=\"UID\" value=\"" . $data["Calendar"]["UID"] . "\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Was</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea name=\"Subject\" class=\"xt\" id=\"Subject\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
						print($data["Calendar"]["Subject"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Von</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"StartTime\" value=\"" . $data["Calendar"]["StartTime"] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : true });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Bis</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" id=\"EndTime\" name=\"EndTime\" value=\"" . $data["Calendar"]["EndTime"] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : true });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Zeitzone</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"TimeZone\" class=\"xs\">");
					foreach($timezone_values as $key => $value)
						{
						print("<option value=\"" . $key . "\"" . ($data["Calendar"]["TimeZone"] == $value[0] ? " selected" : "") . ">");
							print($value[1]);
						print("</option>");
						}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Ganzen Tag</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input style=\"border: solid 1px;\" type=\"checkbox\" onchange=\"handle_link({ cmd : 'UpdateAllDayEvent' });\" name=\"AllDayEvent\" value=\"1\" " . ($data["Calendar"]["AllDayEvent"] == 1 ? " checked" : "") . ">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Wo</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea name=\"Location\" name=\"Location\" class=\"xt\" id=\"Location\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
						print($data["Calendar"]["Location"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Beschreibung</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"hidden\" name=\"Body:Type\" value=\"1\">");
					print("<textarea name=\"Body:Data\" class=\"xt\" id=\"Body:Data\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
						print($data["Body"][0]["Data"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Teilnehmer</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"hidden\" name=\"MeetingStatus\" value=\"" . $data["Calendar"]["MeetingStatus"] . "\">");
					print("<input type=\"hidden\" name=\"OrganizerName\" value=\"" . $data["Calendar"]["OrganizerName"] . "\">");
					print("<input type=\"hidden\" name=\"OrganizerEmail\" value=\"" . $data["Calendar"]["OrganizerEmail"] . "\">");

					asort($data["Attendees"], SORT_LOCALE_STRING);

					# what about AttendeeStatus and AttendeeType if we change appointment ???

					print("<table>");
						print("<tr>");
							print("<td>");
								print("<select id=\"Attendees\" name=\"Attendees[]\" ondblclick=\"this.remove(this.selectedIndex);\" size=\"2\" class=\"xs\" style=\"height: 100px;\" multiple>");

									foreach($data["Attendees"] as $id => $attendee)
										{
										print("<option value=\"&quot;" . $attendee["Name"] . "&quot; &lt;" . $attendee["Email"] . "&gt;\">");
											print("&quot;");
												print($attendee["Name"]);
											print("&quot;");
											print(" ");
											print("&lt;");
												print($attendee["Email"]);
											print("&gt;");
										print("</option>");
										}

								print("</select>");
							print("</td>");
						print("</tr>");
						print("<tr>");
							print("<td>");
								print("<input type=\"text\" class=\"xi\" id=\"Attendee\" onfocus=\"options_handle_calendar('Attendee', 'Attendees');\">");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");
		print("</table>");

		active_sync_show_recurrence($data);

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Anzeigen als</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"BusyStatus\" class=\"xs\">");
						foreach(array(0 => "Verfügbar", 1 => "Vorläufig", 2 => "Besetzt", 3 => "Abwesend") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Calendar"]["BusyStatus"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Datenschutz</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Sensitivity\" class=\"xs\">");
						foreach(array(0 => "Standard", 1 => "Öffentlich", 2 => "Privat", 3 => "Vertraulich") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Calendar"]["Sensitivity"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Erinnerung</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Reminder\" class=\"xs\">");
					foreach(array(0 => "Keine", 1 => "1 Minute", 5 => "5 Minuten", 10 => "10 Minuten", 15 => "15 Minuten", 20 => "20 Minuten", 25 => "25 Minuten", 30 => "30 Minuten", 45 => "45 Minuten", 60 => "1 Stunde", 120 => "2 Stunden", 180 => "3 Stunden", 720 => "12 Stunden", 1440 => "24 Stunden", 2880 => "2 Tage", 10080 => "1 Woche") as $key => $value)
						{
						print("<option value=\"" . $key . "\"" . ($data["Calendar"]["Reminder"] == $key ? " selected" : "") . ">");
							print($value);
						print("</option>");
						}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<br>");

		print("<table>");
			print("<tr>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]</td>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

				if($request["ServerId"] != "")
					print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]</td>");

				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}

function active_sync_web_edit_contacts($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_contacts() as $token => $value)
		$data["Contacts"][$token] = (isset($data["Contacts"][$token]) === false ? $value : $data["Contacts"][$token]);

	foreach(active_sync_get_default_contacts2() as $token => $value)
		$data["Contacts2"][$token] = (isset($data["Contacts2"][$token]) === false ? $value : $data["Contacts2"][$token]);

	if(isset($data["Body"]) === false)
		$data["Body"][] = active_sync_get_default_body();

	foreach($data["Body"] as $body)
		{
		if(isset($body["Type"]) === false)
			continue;

		if($body["Type"] != 1)
			continue;

		foreach(active_sync_get_default_body() as $token => $value)
			$data["Body"][0][$token] = (isset($body[$token]) === false ? $value : $body[$token]);
		}

	foreach(array("Categories", "Children") as $key)
		$data[$key] = (isset($data[$key]) === false ? array() : $data[$key]);

	foreach(array("Anniversary", "Birthday") as $key)
		$data["Contacts"][$key] = ($data["Contacts"][$key] == "" ? "" : date("d.m.Y", strtotime($data["Contacts"][$key])));

	foreach(array("Email1Address", "Email2Address", "Email3Address") as $key)
		list($null, $data["Contacts"][$key]) = active_sync_mail_parse_address($data["Contacts"][$key]);

	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td style=\"width: 100%; height: 100%;\" valign=\"top\">");
				print("<form style=\"height: 100%;\">");
					print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
					print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
					print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");
					print("<table style=\"height: 100%;\">");
						print("<tr>");
							print("<td valign=\"top\">");
								print("<table>");
									print("<tr>");
										print("<td style=\"cursor: default;\" id=\"address_tab_b\">");
											# nothing to display yet
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td style=\"cursor: default;\" id=\"address_tab_h\">");
											# nothing to display yet
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td style=\"cursor: default;\" id=\"address_tab_o\">");
											# nothing to display yet
										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
						print("</tr>");
						print("<tr>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
						print("</tr>");
						print("<tr style=\"height: 200px; \">");
							print("<td style=\"vertical-align: top;\">");

								$weight_address = array(0, 0, 0); # Work, Home, Other

								$fields = array(array(0, "b", array("BusinessAddressStreet" => "Straße", "BusinessAddressCity" => "Stadt", "BusinessAddressState" => "Bundesland", "BusinessAddressPostalCode" => "Postleitzahl", "BusinessAddressCountry" => "Land", "BusinessPhoneNumber" => "Telefon", "Business2PhoneNumber" => "Telefon", "BusinessFaxNumber" => "Fax")), array(1, "h", array("HomeAddressStreet" => "Straße", "HomeAddressCity" => "Stadt", "HomeAddressState" => "Bundesland", "HomeAddressPostalCode" => "Postleitzahl", "HomeAddressCountry" => "Land", "HomePhoneNumber" => "Telefon", "Home2PhoneNumber" => "Telefon", "HomeFaxNumber" => "Fax")), array(2, "o", array("OtherAddressStreet" => "Straße", "OtherAddressCity" => "Stadt", "OtherAddressState" => "Bundesland", "OtherAddressPostalCode" => "Postleitzahl", "OtherAddressCountry" => "Land")));

								foreach($fields as $field_data)
									{
									list($weight_id, $page_id, $tokens) = $field_data;

									print("<span id=\"address_page_" . $page_id . "\" style=\"display: none;\">");

										foreach($tokens as $token => $value)
											{
											print("<table>");
												print("<tr>");
													print("<td class=\"field_label\">");
														print($value);
													print("</td>");
													print("<td>");
														print(":");
													print("</td>");
													print("<td>");
														print("<input");
														print(" ");
														print("type=\"text\"");
														print(" ");
														print("name=\"" . $token . "\"");
														print(" ");
														print("class=\"xi\"");
														print(" ");
														print("id=\"" . $token . "\"");

														if(strpos($token, "Address") !== false)
															{
															print(" onfocus=\"suggest_register(this.id, '" . $_GET["CollectionId"] . "', 0);\"");
															}

														if(strpos($token, "Phone") !== false)
															{
															print(" onfocus=\"suggest_register(this.id, '" . $_GET["CollectionId"] . "', 0);\"");
															}

														print(" value=\"" . $data["Contacts"][$token] . "\"");
														print(">");
													print("</td>");
												print("</tr>");
											print("</table>");

											$weight_address[$weight_id] = $weight_address[$weight_id] + ($data["Contacts"][$token] != "" ? 1 : 0);
											}

										$weight_address[$weight_id] = 100 / count($tokens) * $weight_address[$weight_id];
									print("</span>");
									}

								natcasesort($weight_address);

								$weight_address = array_keys($weight_address);

								$weight_address = end($weight_address);

							print("</td>");
							print("<td style=\"width: 32px;\">");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("<input type=\"hidden\" id=\"img_data\" name=\"Picture\">");

								print("<table style=\"height: 100%; width: 100%;\">");
									print("<tr>");
										print("<td style=\"width: 165px;\">");
											print("&nbsp;");
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td style=\"height: 100%; text-align: center; border: none;\">");
											# image is stored with 69 x 69 pixels, but we have enough space, so display it in double size
											print("<img style=\"height: 108px;\" class=\"xl\" id=\"img_preview\" onclick=\"handle_link({ cmd : 'PictureLoad' });\" src=\"images/contacts_default_image_add.png\">");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td>");
											print("[");
												print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'PictureLoad' });\">");
													print("Hinzufügen");
												print("</span>");
											print("]");
											print(" ");
											print("[");
												print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'PictureDelete' });\">");
													print("Löschen");
												print("</span>");
											print("]");
										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
						print("</tr>");
						print("<tr>");
							print("<td>");
								print("<div style=\"background-color: #000000; height: 1px;\">");
								print("</div>");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("<div style=\"background-color: #000000; height: 1px;\">");
								print("</div>");
							print("</td>");
						print("</tr>");
						print("<tr style=\"height: 100%;\">");
							print("<td style=\"vertical-align: top;\">");

								foreach(array("Title" => "Namenspräfix", "FirstName" => "Vorname", "MiddleName" => "Zweiter Vorname", "LastName" => "Nachname", "Suffix" => "Namenssuffix", "YomiFirstName" => "Phonetischer Vorname", "YomiLastName" => "Phonetischer Nachname", "Anniversary" => "Jahrestag", "AssistantName" => "...", "AssistnamePhoneNumber" => "...", "Birthday" => "Geburtstag", "CompanyName" => "Firma", "Department" => "Abteilung", "FileAs" => "...", "JobTitle" => "Beruf", "CarPhoneNumber" => "...", "MobilePhoneNumber" => "Mobiltelefon", "OfficeLocation" => "Büro", "PagerNumber" => "Pager", "RadioPhoneNumber" => "Funk", "Spouse" => "Ehepartner", "WebPage" => "Webseite", "Alias" => "...", "WeightedRank" => "...") as $token => $value)
									{
									switch($token)
										{
										case("Anniversary"):
										case("Birthday"):

											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("Title"):
										case("FirstName"):
										case("MiddleName"):
										case("LastName"):
										case("Suffix"):
										case("YomiFirstName"):
										case("YomiLastName"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\" id=\"". $token . "\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\" onchange=\"handle_link({ cmd : 'UpdateFileAs' });\">");
														print("</td>");
														print("<td>");
															# nothing to display yet
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("Spouse"):
										case("CompanyName"):
										case("Department"):
										case("JobTitle"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\" id=\"". $token . "\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("FileAs"):
										case("WeightedRank"):
											print("<input type=\"hidden\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\">");

											break;
										case("RadioPhoneNumber"):
										case("CarPhoneNumber"):
										case("AssistnamePhoneNumber"):
											print("<input type=\"hidden\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\">");

											break;
										case("Alias"):
										case("AssistantName"):
										case("OfficeLocation"):
											print("<input type=\"hidden\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\">");

											break;
										case("MobilePhoneNumber"):
										case("PagerNumber"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("WebPage"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										default:
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										}
									}

								foreach(array("NickName" => "Spitzname", "CustomerId" => "Kundennummer", "GovernmentId" => "...", "ManagerName" => "...", "CompanyMainPhone" => "...", "AccountName" => "...", "MMS" => "...") as $token => $value)
									{
									switch($token)
										{
										case("CustomerId");
										case("NickName");
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts2"][$token] . "\" class=\"xi\" autocomplete=\"off\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("AccountName");
										case("CompanyMainPhone");
										case("GovernmentId");
										case("ManagerName");
										case("MMS");
											print("<input type=\"hidden\" name=\"". $token . "\" value=\"" . $data["Contacts2"][$token] . "\">");

											break;
										default:
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts2"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										}
									}

								print("<table>");
									print("<tr>");
										print("<td class=\"field_label\">");
											print("Memo");
										print("</td>");
										print("<td>:</td>");
										print("<td>");
											print("<input type=\"hidden\" name=\"Body:Type\" value=\"1\">");
											print("<input type=\"hidden\" name=\"Body:EstimatedDataSize\">"); # not stored by device, so ...
											print("<textarea name=\"Body:Data\" class=\"xt\">");
												print($data["Body"][0]["Data"]);
											print("</textarea>");
										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
							print("<td style=\"width: 32px;\">");
								print("&nbsp;");
							print("</td>");
							print("<td style=\"vertical-align: top;\">");
								print("<table style=\"height: 100%; width: 100%;\">");
									print("<tr>");
										print("<td>");
											print("<table>");
												print("<tr>");
													print("<td style=\"cursor: default;\" id=\"contact_tab_e\">");
														# nothing to display yet
													print("</td>");
													print("<td>");
														print("&nbsp;");
													print("</td>");
													print("<td style=\"cursor: default;\" id=\"contact_tab_i\">");
														# nothing to display yet
													print("</td>");
												print("</tr>");
											print("</table>");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");

											$weight_contact = array(0, 0); # email, im

											$fields = array(array(0, "e", "Contacts", array("Email1Address" => "E-Mail-Adresse", "Email2Address" => "E-Mail-Adresse", "Email3Address" => "E-Mail-Adresse")), array(1, "i", "Contacts2", array("IMAddress" => "Instant-Messenger", "IMAddress2" => "Instant-Messenger", "IMAddress3" => "Instant-Messenger")));

											foreach($fields as $field_data)
												{
												list($weight_id, $page_id, $codepage, $tokens) = $field_data;

												print("<span id=\"contact_page_" . $page_id . "\" style=\"display: none;\">");

													foreach($tokens as $token => $value)
														{
														print("<table>");
															print("<tr>");
																print("<td class=\"field_label\">");
																	print($value);
																print("</td>");
																print("<td>");
																	print(":");
																print("</td>");
																print("<td>");
																	print("<input type=\"text\" name=\"". $token . "\" class=\"xi\" value=\"" . $data[$codepage][$token] . "\">");
																print("</td>");
																print("<td>");

																	if($page_id == "e")
																		{
																		print("<img class=\"xl\" onclick=\"handle_link({ cmd : 'Edit' , collection_id : '9002', server_id : '', item_id : '" . $data[$codepage][$token] . "' });\" src=\"images/contacts_list_email_icon_small.png\">");
																		}

																	if($page_id == "i")
																		{
																		print("<img class=\"xl\" onclick=\"handle_link({ cmd : 'IM', item_id : '" . $data[$codepage][$token] . "' });\" src=\"images/contacts_list_im_icon_small.png\">");
																		}

																print("</td>");
															print("</tr>");
														print("</table>");

														$weight_contact[$weight_id] = $weight_contact[$weight_id] + ($data[$codepage][$token] != "" ? 1 : 0);
														}

													$weight_contact[$weight_id] = 100 / count($tokens) * $weight_contact[$weight_id];
												print("</span>");
												}

											natcasesort($weight_contact);

											$weight_contact = array_keys($weight_contact);

											$weight_contact = end($weight_contact);

										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");
											print("<div style=\"background-color: #000000; height: 1px;\">");
											print("</div>");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td style=\"height: 100%;\">");

											foreach(array(array("Category", "Categories", "Gruppen"), array("Child", "Children", "Kinder")) as $token)
												{
												print("<table style=\"height: 50%; width: 100%;\">");
													print("<tr>");
														print("<td class=\"field_label\" style=\"vertical-align: top;\">");
															print($token[2]);
														print("</td>");
														print("<td style=\"vertical-align: top;\">");
															print(":");
														print("</td>");
														print("<td style=\"height: 100%;\">");
															asort($data[$token[1]], SORT_LOCALE_STRING);

															print("<table style=\"height: 100%; width: 100%\">");
																print("<tr>");
																	print("<td style=\"height: 100%;\">");
																		print("<select id=\"" . $token[1] . "\" name=\"" . $token[1] . "[]\" ondblclick=\"this.remove(this.selectedIndex);\" size=\"2\" style=\"height: 100%; width: 250px;\" multiple>");

																			foreach($data[$token[1]] as $item)
																				{
																				print("<option value=\"" . $item . "\">");
																					print($item);
																				print("</option>");
																				}

																		print("</select>");
																	print("</td>");
																print("</tr>");
																print("<tr>");
																	print("<td>");
																		print("<input type=\"text\" class=\"xi\" id=\"" . $token[0] . "\" onfocus=\"options_handle_contacts('" . $token[0] . "', '" . $token[1] . "');\">");
																	print("</td>");
																print("</tr>");
															print("</table>");
														print("</td>");
													print("</tr>");
												print("</table>");
												}

										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</form>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("&nbsp;");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("<table>");
					print("<tr>");
						print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]</td>");
						print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

						if($request["ServerId"] != "")
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]</td>");

						print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
					print("</tr>");
				print("</table>");
			print("</td>");
		print("</tr>");
	print("</table>");

	print("<span id=\"buffer_address\" style=\"display: none;\">" . $weight_address . "</span>");
	print("<span id=\"buffer_contact\" style=\"display: none;\">" . $weight_contact . "</span>");
	print("<span id=\"buffer_picture\" style=\"display: none;\">" . $data["Contacts"]["Picture"] . "</span>");
	print("<script language=\"JavaScript\">");
	print("var general_data = " . json_encode($data) . ";");
	print("</script>");
	}

function active_sync_web_edit_email($request)
	{
	if($request["ServerId"] != "")
		{
		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]);

		$from		= $data["Email"]["From"];
		$to		= $data["Email"]["To"];
		$date_received	= $data["Email"]["DateReceived"];
		$subject	= (isset($data["Email"]["Subject"]) === false ? "" : $data["Email"]["Subject"]);

		$from		= utf8_decode($from);
		$from		= htmlentities($from);

		$to		= utf8_decode($to);
		$to		= htmlentities($to);

		$subject	= utf8_decode($subject);
		$subject	= htmlentities($subject);

		$mime = "";

		if($request["CollectionId"] == 9003) # drafts what about Email:IsDraft
			{
			foreach($data["Body"] as $body)
				{
				if(isset($body["Type"]) === false)
					continue;

				if($body["Type"] == 1)
					{
					$quote = array();

					$body["Data"] = wordwrap($body["Data"], 72);

					foreach(explode("\n", $body["Data"]) as $line)
						$quote[] = "" . htmlentities(utf8_decode($line));

					$mime = implode("<br>", $quote);
					}
				}
			}

		if($request["CollectionId"] != 9003) # drafts
			{
			foreach($data["Body"] as $body)
				{
				if(isset($body["Type"]) === false)
					continue;

				if($body["Type"] == 1)
					{
					$quote = array();

					$quote[] = "";
					$quote[] = "Am Montag, den " . date("d.m.Y, H:i", strtotime($date_received)) . " +0000 schrieb " . $from . ":";

					$body["Data"] = wordwrap($body["Data"], 72);

					foreach(explode("\n", $body["Data"]) as $line)
						$quote[] = "&gt; " . htmlentities(utf8_decode($line));

					# "-------- Ursprüngliche Nachricht --------";
					# "Von: " . htmlentities($from);
					# "Datum: " . date("d.m.Y, H:i", strtotime($date_received)) . " (+0000)"
					# "An: " . htmlentities($to);
					# "Betreff: " . $subject;

					$mime = implode("<br>", $quote);
					}

				if($body["Type"] == 2)
					{
					$quote = array();

					$quote[] = "<br>";
					$quote[] = "<div name=\"quote\" style=\"margin: 10px 5px 5px 10px; padding: 10px 0px 10px 10px; border-left: 2px solid #4080C0; word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space;\">";
						$quote[] = "<div style=\"margin: 0px 0px 10px 0px;\">";
							$quote[] = "<b>Gesendet:</b> " . date("l, d. F Y \u\m H:i \U\h\\r", strtotime($date_received));
							$quote[] = "<br>";
							$quote[] = "<b>Von:</b> " . $from;
							$quote[] = "<br>";
							$quote[] = "<b>An:</b> " . $to;
							$quote[] = "<br>";
							$quote[] = "<b>Betreff:</b> " . $subject;
						$quote[] = "</div>";
						$quote[] = "<div name=\"quoted-content\">";
							$quote[] = $body["Data"];
						$quote[] = "</div>";
					$quote[] = "</div>";

					$mime = implode("", $quote);
					}
				}

			if($request["LongId"] != "F")
				{
				}
			elseif(active_sync_mail_is_forward($subject) == 0)
				{
				$to		= "";
				$subject	= "Fw: " . $subject;
				}
			else
				{
				$to		= "";
				}

			if($request["LongId"] != "R")
				{
				}
			elseif(active_sync_mail_is_reply($subject) == 0)
				{
				$to		= $from;
				$subject	= "Re: " . $subject;
				}
			else
				{
				$to		= $from;
				}
			}

		$body = str_replace(array("\r", "\n", "\""), array("", "", "\\\""), $mime);
		}

	if($request["ServerId"] == "")
		{
		$data = array();

		$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

		$settings["Settings"]["Signature"]	= (isset($settings["Settings"]["Signature"]) === false ? "Von " . active_sync_get_version() . " gesendet" : $settings["Settings"]["Signature"]);
		$settings["Settings"]["Append"]		= (isset($settings["Settings"]["Append"]) === false ? 1 : $settings["Settings"]["Append"]);

		$from		= "";
		$to		= "";
		$subject	= "";
		$body		= ($settings["Settings"]["Append"] ? "<br>---<br>" . $settings["Settings"]["Signature"] : ""); # signature ::= "--" <sp> <cr> <lf> ( * 4 (* 80 <char>))

		if($request["ItemId"] != "")
			{
			list($disposition_type, $disposition_data) = explode(":", $request["ItemId"], 2);

			if($disposition_type == "inline")
				{
				list($x_collection_id, $x_server_id, $x_data) = explode(":", $disposition_data, 3);

				$contact = active_sync_get_settings_data($request["AuthUser"], $x_collection_id, $x_server_id);

				$body = (isset($contact["Contacts"]["FileAs"]) === false ? "" : $contact["Contacts"]["FileAs"]) . "<br>" . (isset($contact["Contacts"][$x_data]) === false ? "" : $contact["Contacts"][$x_data]) . "<br>" . $body;
				}

			if($disposition_type == "attachment")
				{
				list($x_collection_id, $x_server_id) = explode(":", $disposition_data, 2);

				# ...
				}

			if($disposition_type == "mail")
				{
				$to = $disposition_data;
				}
			}
		}

	print("<form style=\"height:100%;\">");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");
		print("<input type=\"hidden\" name=\"Draft\" value=\"0\">");
		print("<table style=\"height: 100%; width: 100%;\">");
			print("<tr>");
				print("<td>");
					print("<table style=\"width: 100%;\">");

						foreach(array("To" => "An", "Cc" => "Kopie", "Bcc" => "Blindkopie") as $key => $value)
							{
							print("<tr>");
								print("<td style=\"text-align: right;\">");
									print($value);
								print("</td>");
								print("<td>");
									print(":");
								print("</td>");
								print("<td colspan=\"5\" style=\"text-align: right; width: 100%;\">");
									# search suggest taken from 9009 default contacts folder
									if($key == "To")
										{
										print("<input type=\"text\" name=\"" . $key . "\" id=\"" . $key . "\" onfocus=\"suggest_register(this.id, '9009', 1);\" style=\"width: 100%;\" value=\"" . $to . "\">");
										}
									else
										{
										print("<input type=\"text\" name=\"" . $key . "\" id=\"" . $key . "\" onfocus=\"suggest_register(this.id, '9009', 1);\" style=\"width: 100%;\">");
										}
								print("</td>");
							print("</tr>");
							}

						print("<tr>");
							print("<td style=\"text-align: right;\">");
								print("Betreff");
							print("</td>");
							print("<td>");
								print(":");
							print("</td>");
							print("<td style=\"width: 100%;\">");
								print("<input type=\"text\" name=\"Subject\" value=\"" . $subject . "\" style=\"width: 100%;\">");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("Priorität");
							print("</td>");
							print("<td>");
								print(":");
							print("</td>");
							print("<td>");
								print("<select name=\"Importance\">");

									foreach(array(0 => 0, 1 => 1, 2 => 0) as $importance => $selected)
										{
										print("<option value=\"" . $importance . "\"" . ($selected == 1 ? " selected" : "") . ">");
											print($importance);
										print("</option>");
										}

								print("<select>");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");

			print("<tr>");
				print("<td style=\"height: 100%;\" id=\"mail_content\">");
					print($body);
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("Dateianhänge");
					print(":");
					print(" ");
					print("<select name=\"attachments\" class=\"xs\">");
						if(isset($data["Attachments"]["AirSyncBase"]) === true)
							{
							foreach($data["Attachments"]["AirSyncBase"] as $attachment_id => $attachment_data)
								{
								print("<option value=\"" . $attachment_id . "\">");
									print($attachment_data["DisplayName"]);
								print("</option>");
								}
							}
					print("</select>");
					print(" ");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'AttachmentDelete' });\">");
							print("Löschen");
						print("</span>");
					print("]");
					print(" ");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'AttachmentUpload' });\">");
							print("Hinzufügen");
						print("</span>");
					print("]");

					print("<span id=\"pbe\" style=\"border: solid 1px; height: 18px; float: right; display: none; width: 200px;\">");
						print("<span id=\"pbc\" style=\"background-color: #4080C0; display: block; height: 18px; width: 0px;\">");
						print("</span>");
					print("</span>");

				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("<input type=\"checkbox\" name=\"SaveInSent\" value=\"T\" checked>");
					print(" Im Ordner <b>Gesendet</b> speichern");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("<table>");
						print("<tr>");
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Send' });\">Senden</span>]</td>");
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

							if($request["ServerId"] != "")
								{
								print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Verwerfen</span>]</td>");
								}

							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Draft' });\">Entwurf</span>]</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}

function active_sync_web_edit_notes($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_notes() as $token => $value)
		$data["Notes"][$token] = (isset($data["Notes"][$token]) === false ? $value : $data["Notes"][$token]);

	if(isset($data["Body"]) === false)
		$data["Body"][] = active_sync_get_default_body();

	foreach($data["Body"] as $body)
		{
		if(isset($body["Type"]) === false)
			continue;

		if($body["Type"] != 1)
			continue;

		foreach(active_sync_get_default_body() as $token => $value)
			$data["Body"][0][$token] = (isset($body[$token]) === false ? $value : $body[$token]);
		}

	print("<form style=\"height:100%;\" onsubmit=\"return false;\">");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");

		print("<table style=\"height: 100%; width: 100%;\">");
			print("<tr>");
				print("<td>");
					print("<table style=\"width: 100%;\">");
						print("<tr>");
							print("<td>");
								print("Titel");
							print("</td>");
							print("<td>");
								print(":");
							print("</td>");
							print("<td style=\"width: 100%;\">");
								print("<input type=\"text\" name=\"Subject\" value=\"" . $data["Notes"]["Subject"] . "\" style=\"width: 100%;\">");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td style=\"height: 100%;\">");
					print("<input type=\"hidden\" name=\"Body:Type\" value=\"1\">");
					print("<textarea name=\"Body:Data\" style=\"webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box; padding: 8px; font-family: Courier New; font-size: 10pt; width: 100%; height: 100%;\">");
						print($data["Body"][0]["Data"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]");
					print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]");

					if($request["ServerId"] != "")
						print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]");

					print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}

function active_sync_web_edit_tasks($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_tasks() as $token => $value)
		$data["Tasks"][$token] = (isset($data["Tasks"][$token]) === false ? $value : $data["Tasks"][$token]);

	foreach(active_sync_get_default_recurrence() as $token => $value)
		$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) === false ? $value : $data["Recurrence"][$token]);

	if(isset($data["Body"]) === false)
		$data["Body"][] = active_sync_get_default_body();

	foreach($data["Body"] as $body)
		{
		if(isset($body["Type"]) === false)
			continue;

		if($body["Type"] != 1)
			continue;

		foreach(active_sync_get_default_body() as $token => $value)
			$data["Body"][0][$token] = (isset($body[$token]) === false ? $value : $body[$token]);
		}

	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	foreach(active_sync_get_default_settings() as $key => $val)
		$settings["Settings"][$key] = (isset($_POST[$key]) === false ? "" : $_POST[$key]);

	foreach(array("FirstDayOfWeek" => $settings["Settings"]["FirstDayOfWeek"], "IsLeapMonth" => isset($data["Recurrence"]["MonthOfYear"]) ? $data["Recurrence"]["MonthOfYear"] == 2 ? 1 : 0 : 0) as $token => $value)
		$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) ? $data["Recurrence"][$token] : $value);

	foreach(array("StartDate", "DueDate", "DateCompleted") as $token)
		$data["Tasks"][$token] = date("d.m.Y", strtotime($data["Tasks"][$token]));

	foreach(array("ReminderTime") as $token)
		$data["Tasks"][$token] = date("d.m.Y H:i", strtotime($data["Tasks"][$token]));

	print("<form>");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Was</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea name=\"Subject\" class=\"xt\">");
						print($data["Tasks"]["Subject"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Von</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"StartDate\" value=\"" . $data["Tasks"]["StartDate"] . "\" class=\"xi\" maxlength=\"10\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Bis</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"DueDate\" value=\"" . $data["Tasks"]["DueDate"] . "\" class=\"xi\" maxlength=\"10\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Erledigt");
				print("</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"DateCompleted\" value=\"" . $data["Tasks"]["DateCompleted"] . "\" class=\"xi\" maxlength=\"10\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
				print("</td>");
				print("<td>");
					print("<input onchange=\"handle_link({ cmd : 'ToggleComplete' });\" type=\"checkbox\" name=\"Complete\" value=\"1\" " . ($data["Tasks"]["Complete"] == 1 ? " checked" : "") . ">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Beschreibung</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea class=\"xt\" name=\"Body:Data\">");
						print($data["Body"][0]["Data"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		active_sync_show_recurrence($data);

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Datenschutz</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Sensitivity\" class=\"xs\">");
						foreach(array(0 => "Standard", 1 => "Öffentlich", 2 => "Privat", 3 => "Vertraulich") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Tasks"]["Sensitivity"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Priorität</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Importance\" class=\"xs\">");
						foreach(array(0 => "Niedrig", 1 => "Mittel", 2 => "Hoch") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Tasks"]["Importance"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Erinnerung</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"ReminderTime\" value=\"" . $data["Tasks"]["ReminderTime"] . "\" class=\"xi\" maxlength=\"19\" onclick=\"popup_date({ target : this, cmd : 'init', time : true });\">");
				print("</td>");
				print("<td>");
					print("<input onchange=\"handle_link({ cmd : 'ToggleReminderSet' });\" style=\"border: solid 1px;\" type=\"checkbox\" name=\"ReminderSet\" value=\"1\" " . ($data["Tasks"]["ReminderSet"] == 1 ? " checked" : "") . ">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<br>");

		print("<table>");
			print("<tr>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]</td>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

				if($request["ServerId"] != "")
					{
					print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]</td>");
					}

				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}

function active_sync_web_list($request)
	{
	$table = array
		(
		"Calendar"	=> "active_sync_web_list_calendar",
		"Contacts"	=> "active_sync_web_list_contacts",
		"Email"		=> "active_sync_web_list_email",
		"Notes"		=> "active_sync_web_list_notes",
		"Tasks"		=> "active_sync_web_list_tasks"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		{
		if($default_class != $class)
			continue;

		if(function_exists($function) === false)
			continue;

		$retval = $function($request);
		}

	return($retval);
	}

function active_sync_web_list_calendar($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td>");
				print("<table>");
					print("<tr>");
						print("<td>Ansicht</td>");
						print("<td>:</td>");
						print("<td>");
							print("<select id=\"view\" onchange=\"handle_link({ cmd : 'CalendarSelect' });\">");

							foreach(array("a" => "Agenda", "d" => "Tag", "w" => "Woche", "m" => "Monat", "y" => "Jahr") as $key => $value)
								printf("<option value=\"%s\">%s</option>", $key, $value);
							print("</select>");
						print("</td>");
						print("<td>&nbsp;</td>");
						print("<td><input type=\"button\" onclick=\"handle_link({ cmd : 'CalendarJumpToNow' });\" value=\"Heute\"></td>");
					print("</tr>");
				print("</table>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>&nbsp;</td>");
		print("</tr>");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<span id=\"search_result\"></span>");
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_web_list_contacts($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td>");
				print("<table>");
					print("<tr>");
						print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Edit', server_id : '' });\">Hinzufügen</span>]</td>");
						print("<td style=\"width: 50px;\">&nbsp;</td>");
						print("<td>Suche nach</td>");
						print("<td>:</td>");
						print("<td><input style=\"width: 150px;\" id=\"search_name\" type=\"text\"\"></td>");
						print("<td style=\"width: 50px;\">&nbsp;</td>");
						print("<td><span class=\"span_link\" onclick=\"handle_link({ cmd : 'Category' });\">Gruppe</span></td>");
						print("<td>:</td>");
						print("<td>");
							print("<select id=\"search_category\" style=\"width: 150px;\"\">");

								foreach(array("Alle" => "*", "Nicht zugewiesen" => "") as $key => $value)
									printf("<option value=\"%s\">%s</option>", $value, $key);

								$categories = active_sync_get_categories_by_collection_id($request["AuthUser"], $request["CollectionId"]);

								foreach($categories as $category => $count)
									{
									if($category == "*")
										continue;

									printf("<option value=\"%s\">%s</option>", $category, $category);
									}
							print("</select>");
						print("</td>");
					print("</tr>");
				print("</table>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>&nbsp;</td>");
		print("</tr>");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<table style=\"height: 100%; width: 100%;\">");
					print("<tr>");
						print("<td style=\"height: 100%; width: 32px;\">");
							print("<table style=\"height: 100%; width: 32px;\">");
								$m = "#ABCDEFGHIJKLMNOPQRSTUVWXYZ";

								for($i = 0; $i < strlen($m); $i ++)
									printf("<tr><td class=\"span_link\" style=\"border: solid 1px; border: solid 1px; text-align: center;\" onclick=\"contact_scroll_to('LETTER_%s', 'touchscroll_div');\">%s</td></tr>", $m[$i], $m[$i]);
							print("</table>");
						print("</td>");
						print("<td style=\"height: 100%;\">");
							print("<div class=\"touchscroll_outer\">");
								print("<div class=\"touchscroll_inner\" id=\"touchscroll_div\">");
									print("<span id=\"search_result\"></span>");
								print("</div>");
							print("</div>");
						print("</td>");
					print("</tr>");
				print("</table>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>&nbsp;</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

				print("<span id=\"search_count\">0</span>");
				print(" ");
				print("Kontakte" . (isset($settings["Settings"]["PhoneOnly"]) ? " mit Telefonnummern " : " ") . "werden angezeigt."); # cu numere de telefon
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_web_list_email($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<div class=\"touchscroll_outer\">");
					print("<div class=\"touchscroll_inner\" id=\"touchscroll_div\">");
						print("<span id=\"search_result\"></span>");
					print("</div>");
				print("</div>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>&nbsp;</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Edit', server_id : '', item_id : '', long_id : '' });\">Verfassen</span>]");
				print(" ");
				print("<span id=\"delete_selected\" style=\"display: none;\">");
					print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteMultipleConfirm' });\">Löschen</span>]");
				print("</span>");
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_web_list_notes($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<div class=\"touchscroll_outer\">");
					print("<div class=\"touchscroll_inner\" id=\"touchscroll_div\">");
						print("<span id=\"search_result\">");
						print("</span>");
					print("</div>");
				print("</div>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("&nbsp;");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("[");
					print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Edit', server_id : '' });\">");
						print("Hinzufügen");
					print("</span>");
				print("]");
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_web_list_tasks($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<div class=\"touchscroll_outer\">");
					print("<div class=\"touchscroll_inner\" id=\"touchscroll_div\">");
						print("<span id=\"search_result\"></span>");
					print("</div>");
				print("</div>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>&nbsp;</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Edit', server_id : '' });\">Hinzufügen</span>]");
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_web_print($request)
	{
	$table = array
		(
		"Email" => "active_sync_web_print_email"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		{
		if($default_class != $class)
			continue;

		if(function_exists($function) === false)
			continue;

		$retval = $function($request);
		}

	return($retval);
	}

function active_sync_web_print_email($request)
	{
	$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]);

	if(isset($data["Body"]["Type"]) === false)
		$data = "";
	elseif($data["Body"]["Type"] == 1) # text
		$data = $data["Body"]["Data"];
	elseif($data["Body"]["Type"] == 2) # html
		$data = $data["Body"]["Data"];
	else
		$data = "";

	$file = "/tmp/" . active_sync_create_guid();

	file_put_contents($file, $data);

	exec("lpr " . $file);

	unlink($file);

	print(1);
	}

function active_sync_web_save($request)
	{
	$table = array
		(
		"Calendar"	=> "active_sync_web_save_calendar",
		"Contacts"	=> "active_sync_web_save_contacts",
		"Email"		=> "active_sync_web_save_email",
		"Notes"		=> "active_sync_web_save_notes",
		"Tasks"		=> "active_sync_web_save_tasks"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		{
		if($default_class != $class)
			continue;

		if(function_exists($function) === false)
			continue;

		$retval = $function($request);
		}

	return($retval);
	}

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

	for($i = 0; $i < 4; $i ++)
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

function active_sync_web_save_contacts($request)
	{
	$data = array();

	foreach(active_sync_get_default_contacts() as $token => $default_value)
		{
		if(isset($_POST[$token]) === false)
			continue;

		if(strlen($_POST[$token]) == 0)
			continue;

		$data["Contacts"][$token] = $_POST[$token];
		}

	foreach(active_sync_get_default_contacts2() as $token => $default_value)
		{
		if(isset($_POST[$token]) === false)
			continue;

		if(strlen($_POST[$token]) == 0)
			continue;

		$data["Contacts2"][$token] = $_POST[$token];
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
					$data["Body"][] = $body;

	foreach(array("Categories", "Children") as $token)
		{
		if(isset($_POST[$token]) === false)
			continue;

		$data[$token] = $_POST[$token]; # !!! ARRAY
		}

	foreach(array("Anniversary", "Birthday") as $token)
		{
		if(isset($data["Contacts"][$token]) === false)
			{
			continue;
			}

		$data["Contacts"][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data["Contacts"][$token]));
#		$data["Contacts"][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data["Contacts"][$token]) - date("Z", strtotime($data["Contacts"][$token])));
		}

	foreach(array("Email1Address", "Email2Address", "Email3Address") as $token)
		{
		if(isset($data["Contacts"]["FileAs"]) === false)
			continue;

		if(isset($data["Contacts"][$token]) === false)
			continue;

		$data["Contacts"][$token] = "\"" . $data["Contacts"]["FileAs"]  . "\" <" . $data["Contacts"][$token] . ">";
		}

	if($request["ServerId"] == "")
		$request["ServerId"] = active_sync_create_guid_filename($request["AuthUser"], $request["CollectionId"]);

	active_sync_put_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"], $data);

	print(1);
	}

function active_sync_web_save_email($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($settings["login"] as $user)
		{
		if($user["User"] != $request["AuthUser"])
			{
			continue;
			}

		break;
		}

	$from = $user["User"] . "@" . active_sync_get_domain();

	$from = ($user["DisplayName"] ? "\"" . $user["DisplayName"] . "\" <" . $from . ">" : $from);

	$to		= $_POST["To"];				# not available via Request
	$cc		= $_POST["Cc"];				# not available via Request
	$bcc		= $_POST["Bcc"];				# not available via Request
	$subject	= $_POST["Subject"];				# not available via Request
	$importance	= $_POST["Importance"];			# not available via Request

	$draft			= $_POST["Draft"];

	$body_p			= $_POST["inhalt"];				# not available via Request

	$body_p			= active_sync_mail_convert_html_to_plain($body_p);

	$body_h			= $_POST["inhalt"];				# not available via Request

	$importance_values	= array(0 => "Low", 1 => "Normal", 2 => "High");		# low number = low priority (0, 1, 2)
	$priority_values	= array(0 => "5 (Low)", 1 => "3 (Normal)", 2 => "1 (High)");	# low number = high priority (5, 3, 1)

	$boundary		= active_sync_create_guid();

	$body_m = array();

	$body_m[] = "From: " . $from;
	$body_m[] = "To: " . $to;

	if(strlen($cc) != 0)
		{
		$body_m[] = "Cc: " . $cc;
		}

	if(strlen($bcc) != 0)
		{
		$body_m[] = "Bcc: " . $bcc;
		}

	$body_m[] = "Date: " . date("r");
#		$body_m[] = "Content-Type: text/html; charset=\"UTF-8\"";
#		$body_m[] = "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"";
	$body_m[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
	$body_m[] = "Importance: " . $importance_values[$importance];
	$body_m[] = "MIME-Version: 1.0";
	$body_m[] = "X-Priority: " . $priority_values[$importance];
	$body_m[] = "X-Mailer: " . active_sync_get_version();
	$body_m[] = "";
	$body_m[] = "--" . $boundary;
	$body_m[] = "Content-Type: text/plain; charset=\"UTF-8\"";
	$body_m[] = "";
	$body_m[] = $body_p;
	$body_m[] = "--" . $boundary;
	$body_m[] = "Content-Type: text/html; charset=\"UTF-8\"";
	$body_m[] = "";
	$body_m[] = $body_h;

#		foreach($attachments as $id => $attachment)
#			{
#			$body_m[] = "--" . $boundary;
#			$body_m[] = "Content-Type: " . $attachment["ContentType"] . "; name=\"" . $attachment["DisplayName"] . "\"";
#			$body_m[] = "Content-Disposition: attachment; filename=\"" . $attachment["AirSyncBase"]["DisplayName"] . "\"";
#			$body_m[] = $attachment["AirSyncBase"]["Data"]
#			}

	$body_m[] = "--" . $boundary . "--";

	$body_m = implode("\n", $body_m);

	$data = array
		(
		"AirSync" => array
			(
			"Class" => "Email"
			),
		"Email" => array
			(
			"From" => $from,
			"To" => $to,
			"Cc" => $cc,
			"Importance" => $importance,
			"Subject" => $subject,
			"DateReceived" => date("Y-m-d\TH:i:s\Z", date("U")),
			"Read" => 1,
			"ContentClass" => "urn:content-classes:message",
			"MessageClass" => "IPM.Note"
			),
		"Body" => array
			(
			array
				(
				"Type" => 1,
				"EstimatedDataSize" => strlen($body_p),
				"Data" => $body_p
				),
			array
				(
				"Type" => 2,
				"EstimatedDataSize" => strlen($body_h),
				"Data" => $body_h
				),
			array
				(
				"Type" => 4,
				"EstimatedDataSize" => strlen($body_m),
				"Data" => $body_m
				)
			)
		);

	if($bcc != "")
		$data["Email2"]["ReceivedAsBcc"] = 1;

	if($draft == 0)
		{
		list($t_name, $t_mail) = active_sync_mail_parse_address($to);
		list($f_name, $f_mail) = active_sync_mail_parse_address($from);

		if(strlen($to) > 0)
			{
			$recipient_is_phone = active_sync_get_is_phone($t_mail);

			if($recipient_is_phone == 0)
				active_sync_send_mail($request["AuthUser"], $body_m);

			if($recipient_is_phone == 1)
				{
				$x_name = $f_name;
				$x_mail = $f_mail;

				$settings = active_sync_get_settings_user($request["AuthUser"]);

				if(isset($settings["DisplayName"]) === true)
					$x_name = $settings["DisplayName"];

				if(isset($settings["MobilePhone"]) === true)
					$x_mail = $settings["MobilePhone"];

				$devices = active_sync_get_devices_by_user($request["AuthUser"]);

				foreach($devices as $device)
					{
					$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" .  $device . ".sync");

					if(isset($settings["DeviceInformation"]["EnableOutboundSMS"]) === false)
						continue;

					if(isset($settings["DeviceInformation"]["PhoneNumber"]) === false)
						continue;

					$x_mail = $settings["DeviceInformation"]["PhoneNumber"];

					break;
					}

				$data = array
					(
					"AirSync" => array
						(
						"Class" => "SMS"
						),
					"Email" => array
						(
						"DateReceived" => date("Y-m-d\TH:i:s\Z", date("U")),
						"Read" => 1,
						"To" => ($t_name ? "\"" . $t_name . "\" " : "") . "[MOBILE: " . $t_mail . "]",
						"From" => ($x_name ? "\"" . $x_name . "\" " : "") . "[MOBILE: " . $x_mail . "]"
						),
					"Body" => array
						(
						array
							(
							"Type" => 1,
							"EstimatedDataSize" => strlen($body_p),
							"Data" => $body_p
							)
						)
					);

				$user = $request["AuthUser"];
				$collection_id = active_sync_get_collection_id_by_type($user, 6); # Outbox
				$server_id = active_sync_create_guid_filename($user, $collection_id);

				active_sync_put_settings_data($user, $collection_id, $server_id, $data);
				}
			}

		if($request["SaveInSent"] == "T")
			{
			$user = $request["AuthUser"];
			$collection_id = active_sync_get_collection_id_by_type($user, 5); # Sent Items
			$server_id = active_sync_create_guid_filename($user, $collection_id);

			active_sync_put_settings_data($user, $collection_id, $server_id, $data);
			}
		}

	if($draft == 1)
		{
		$user		= $request["AuthUser"];
		$collection_id	= active_sync_get_collection_id_by_type($user, 3); # Drafts
		$server_id	= ($request["ServerId"] ? $request["ServerId"] : active_sync_create_guid_filename($user, $collection_id));

		$reference = 0;

		foreach(scandir(DAT_DIR . "/../web/temp") as $file)
			{
			if(($file == ".") || ($file == ".."))
				continue;

			$body = file_get_contents(DAT_DIR . "/../web/temp/" . $file);

			unlink(DAT_DIR . "/../web/temp/" . $file);

			$data["Attachment"]["AirSyncBase"][$reference] = array(
				"DisplayName" => $file,
				"FileReference" => $server_id . ":" . $reference,
				"Method" => 1,
				"EstimatedDataSize" => strlen($body),
				"ContentId" => "xxx",
				"IsInline" => 0
				);

			$data["File"][$reference] = array(
				"ContentType" => "",
				"Data" => base64_encode($body)
				);

			$reference ++;
			}

		active_sync_put_settings_data($user, $collection_id, $server_id, $data);
		}

	print(1);
	}

function active_sync_web_save_notes($request)
	{
	$data = array();

	foreach(active_sync_get_default_notes() as $token => $value)
		{
		if(isset($_POST[$token]) === false)
			continue;

		if(strlen($_POST[$token]) == 0)
			continue;

		$data["Notes"][$token] = $_POST[$token];
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

	$data["Notes"]["LastModifiedDate"] = date("Y-m-d\TH:i:s\Z");

	if($request["ServerId"] == "")
		$request["ServerId"] = active_sync_create_guid_filename($request["AuthUser"], $request["CollectionId"]);

	active_sync_put_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"], $data);

	print(1);
	}

function active_sync_web_save_tasks($request)
	{
	$data = array();

	foreach(active_sync_get_default_tasks() as $token => $default_value)
		{
		if(isset($_POST[$token]) === false)
			continue;

		if(strlen($_POST[$token]) == 0)
			continue;

		$data["Tasks"][$token] = $_POST[$token];
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

	# 0x01 WeekOfMonth
	# 0x02 DayOfWeek
	# 0x04 MonthOfYear
	# 0x08 DayOfMonth

	$fields = array(0x02, 0x02, 0x18, 0x13, 0x00, 0x1C, 0x17, "WeekOfMonth", "DayOfWeek", "MonthOfYear", "DayOfMonth");

	for($i = 0; $i < 4; $i ++)
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
		foreach(array("Type", "Occurrences", "Interval", "Until", "CalendarType", "IsLeapMonth", "FirstDayOfWeek") as $key)
			{
			if($_POST["Recurrence:" . $key] == "")
				continue;

			$data["Recurrence"][$key] = $_POST["Recurrence:" . $key];
			}

		if(($data["Recurrence"]["Until"] != "") && ($data["Recurrence"]["Occurrences"] != ""))
			unset($data["Recurrence"]["Until"]);
		}

	if($request["ServerId"] == "")
		$request["ServerId"] = active_sync_create_guid_filename($request["AuthUser"], $request["CollectionId"]);

	active_sync_put_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"], $data);

	print(1);
	}
?>
