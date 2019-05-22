<?
function active_sync_handle_sync_save($xml, $user, $collection_id, $server_id, $class)
	{
	if($class == "Email")
		$data = active_sync_get_settings_data($user, $collection_id, $server_id);
	else
		$data = array();

	$codepage_table = array();

	if($class == "Contact")
		{
		$codepage_table["Contacts"] = active_sync_get_default_contacts();
		$codepage_table["Contacts2"] = active_sync_get_default_contacts2();
		}

	if($class == "Calendar")
		$codepage_table["Calendar"] = active_sync_get_default_calendar();

	if($class == "Email")
		{
		$codepage_table["Email"] = active_sync_get_default_email();
		$codepage_table["Email2"] = active_sync_get_default_email2();
		}

	if($class == "Notes")
		$codepage_table["Notes"] = active_sync_get_default_notes();

	if($class == "Tasks")
		$codepage_table["Tasks"] = active_sync_get_default_tasks();

	foreach($codepage_table as $codepage => $null)
		foreach($codepage_table[$codepage] as $token => $value)
			{
			if(isset($xml->ApplicationData->$token) === false)
				continue;

			$data[$codepage][$token] = strval($xml->ApplicationData->$token);
			}

	if($class == "Contact")
		{
		if(isset($data["Contacts"]["Picture"]))
			if(strlen($data["Contacts"]["Picture"]) > (48 * 1024))
				return(6); # Error in client/server conversion.
		}

	if($class == "Email")
		{
		foreach(array("Class") as $token)
			{
			if(isset($xml->$token) === false)
				continue;

			$data["AirSync"][$token] = strval($xml->$token);
			}

		foreach(array("UmCallerID", "UmUserNotes") as $token)
			{
			if(isset($xml->ApplicationData->$token) === false)
				continue;

	#		$data["Email2"][$token] = strval($xml->ApplicationData->$token);

	#		$data["Attachments"][]["Email2"][$token] = $data["Email2"][$token];
			}

		if(isset($xml->ApplicationData->Flag) === true)
			{
			$data["Flag"] = array();

			foreach(array("Email", "Tasks") as $codepage)
				{
				foreach(active_sync_get_default_flag($codepage) as $token)
					{
					if(isset($xml->ApplicationData->Flag->$token) === false)
						continue;

					$data["Flag"][$codepage][$token] = strval($xml->ApplicationData->Flag->$token);
					}
				}
			}
		}

	if(isset($xml->ApplicationData->Attendees) === true)
		foreach($xml->ApplicationData->Attendees->Attendee as $attendee)
			{
			$a = array();

			foreach(active_sync_get_default_attendee() as $token => $value)
				{
				if(isset($attendee->$token) === false)
					continue;

				$a[$token] = strval($attendee->$token);
				}

			$data["Attendees"][] = $a;
			}

	if(isset($xml->ApplicationData->Recurrence) === true)
		foreach(active_sync_get_default_recurrence() as $token => $value)
			{
			if(isset($xml->ApplicationData->Recurrence->$token) === false)
				continue;

			$data["Recurrence"][$token] = strval($xml->ApplicationData->Recurrence->$token);
			}

	if(isset($xml->ApplicationData->Body) === true)
		foreach($xml->ApplicationData->Body as $body)
			{
			$b = array();

			foreach(active_sync_get_default_body() as $token => $value)
				{
				if(isset($body->$token) === false)
					continue;

				$b[$token] = strval($body->$token);
				}

			if(isset($b["Data"]) === false)
				continue;

			if(strlen($b["Data"]) == 0)
				continue;

			$data["Body"][] = $b;
			}

	if(isset($xml->ApplicationData->Children))
		if(count($xml->ApplicationData->Children->Child) > 0)
			foreach($xml->ApplicationData->Children->Child as $child)
				$data["Children"][] = strval($child);

	if(isset($xml->ApplicationData->Categories))
		if(count($xml->ApplicationData->Categories->Category) > 0)
			foreach($xml->ApplicationData->Categories->Category as $category)
				$data["Categories"][] = strval($category);

	return(active_sync_put_settings_data($user, $collection_id, $server_id, $data) ? 1 : 5);
	}
?>
