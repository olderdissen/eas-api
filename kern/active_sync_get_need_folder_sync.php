<?
function active_sync_get_need_folder_sync($request)
	{
	$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
		{
		$settings_client[$key] = (isset($settings_client[$key]) === false ? $value : $settings_client[$key]);
		}

	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	foreach(array("SyncDat" => array()) as $key => $value)
		{
		$settings_server[$key] = (isset($settings_server[$key]) === false ? $value : $settings_server[$key]);
		}

	foreach($settings_server["SyncDat"] as $server_id => $server_data)
		{
		$known = 0;

		foreach($settings_client["SyncDat"] as $client_id => $client_data)
			{
			if($server_data["ServerId"] != $client_data["ServerId"])
				continue;

			if($server_data["ParentId"] != $client_data["ParentId"])
				return(1);

			if($server_data["DisplayName"] != $client_data["DisplayName"])
				return(1);

			if($server_data["Type"] != $client_data["Type"])
				return(1);

			if($server_data["ServerId"] == $client_data["ServerId"])
				{
				$known = 1;

				break;
				}
			}

		if($known == 0)
			return(1);
		}

	################################################################################
	# check if folders on client-side are also known on server-side
	################################################################################

	foreach($settings_client["SyncDat"] as $client_id => $client_data)
		{
		$known = 0;

		foreach($settings_server["SyncDat"] as $server_id => $server_data)
			{
			if($client_data["ServerId"] == $server_data["ServerId"])
				{
				$known = 1;

				break;
				}
			}

		if($known == 0)
			return(1);
		}

	return(0);
	}
?>
