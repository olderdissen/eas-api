<?
function active_sync_handle_sync_save_email($xml, $user, $collection_id, $server_id)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	foreach(array("Class") as $token)
		{
		if(isset($xml->$token) === false)
			continue;

		$data["AirSync"][$token] = strval($xml->$token);
		}

	$codepage_table = array
		(
		"Email" => active_sync_get_default_email(),
		"Email2" => active_sync_get_default_email2()
		);

	foreach($codepage_table as $codepage => $token_table)
		foreach($token_table as $token => $value)
			{
			if(isset($xml->ApplicationData->$token) === false)
				continue;

			$data[$codepage][$token] = strval($xml->ApplicationData->$token);
			}

	if(isset($xml->ApplicationData->Body))
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

	if(isset($xml->ApplicationData->Categories))
		if(count($xml->ApplicationData->Categories->Category) > 0)
			foreach($xml->ApplicationData->Categories->Category as $category)
				$data["Categories"][] = strval($category);

#	$data["Email"]["Read"] = 1;

	# fixme: some fields are part of attachment !!!

	foreach(array("UmCallerID", "UmUserNotes") as $token)
		{
		if(isset($xml->ApplicationData->$token) === false)
			continue;

#		$data["Email2"][$token] = strval($xml->ApplicationData->$token);

#		$data["Attachments"][]["Email2"][$token] = $data["Email2"][$token];
		}

	if(isset($xml->ApplicationData->Flag))
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

	return(active_sync_put_settings_data($user, $collection_id, $server_id, $data) === false ? 16 : 1); # Ok. | Server error.
	}
?>
