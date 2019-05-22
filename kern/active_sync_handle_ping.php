<?
function active_sync_handle_ping($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	if($request["wbxml"] == null)
		$xml = simplexml_load_string("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<Ping xmlns=\"Ping\"/>", "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);
	else
		$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	if(isset($xml->HeartbeatInterval) === true)
		{
		unset($settings["HeartbeatInterval"]);

		$settings["HeartbeatInterval"] = intval($xml->HeartbeatInterval);
		}

	if(isset($xml->Folders) === true)
		{
		unset($settings["Ping"]);

		foreach($xml->Folders->Folder as $folder)
			{
			$z = array();

			$z["Id"] = strval($folder->Id);
			$z["Class"] = strval($folder->Class);

			$settings["Ping"][] = $z;
			}
		}

	if(isset($settings["HeartbeatInterval"]) === true)
		{
		unset($xml->HeartbeatInterval);

		$x = $xml->addChild("HeartbeatInterval", $settings["HeartbeatInterval"]);
		}

		{
		unset($xml->Folders);

		$x = $xml->addChild("Folders");

		foreach($settings["Ping"] as $folder)
			{
			$y = $x->addChild("Folder");

			$y->addChild("Id", $folder["Id"]);
			$y->addChild("Class", $folder["Class"]);
			}
		}

	$settings["Port"] = (isset($_SERVER["REMOTE_PORT"]) === false ? "i" : $_SERVER["REMOTE_PORT"]);

	active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings);

#	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$timeout = microtime(true);
	$max_folders = 300;

	$changed_folders = array();

	while(1)
		{
		if(active_sync_get_need_wipe($request) != 0)
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(active_sync_get_need_provision($request) != 0)
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(active_sync_get_need_folder_sync($request) != 0)
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(isset($xml->Folders) === false)
			{
			$status = 3; # The Ping command request omitted required parameters.

			break;
			}

		if(count($xml->Folders->Folder) > $max_folders)
			{
			$status = 6; # The Ping command request specified more than the allowed number of folders to monitor.

			break;
			}

		if(isset($xml->HeartbeatInterval) === false)
			{
			$status = 3; # The Ping command request omitted required parameters.

			break;
			}

		if(intval($xml->HeartbeatInterval) < 60)
			{
			$status = 5; # The specified heartbeat interval is outside the allowed range.

			$heartbeat_interval = 60;

			break;
			}

		if(intval($xml->HeartbeatInterval) > 3540)
			{
			$status = 5; # The specified heartbeat interval is outside the allowed range.

			$heartbeat_interval = 3540;

			break;
			}

		if(($timeout + intval($xml->HeartbeatInterval)) < microtime(true))
			{
			$status = 1; # The heartbeat interval expired before any changes occurred in the folders being monitored.

			break;
			}

		foreach($xml->Folders->Folder as $folder)
			{
			$changes_detected = 0;
			$collection_id = strval($folder->Id);

			$settings_client = active_sync_get_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"]);

			$settings_server = active_sync_get_settings_sync($request["AuthUser"], $collection_id, "");

			if($settings_client["SyncKey"] == 0)
				$changes_detected = 1;

			foreach($settings_server["SyncDat"] as $server_id => $null)
				{
				if($changes_detected == 1)
					continue;

				if(isset($settings_client["SyncDat"][$server_id]) === false)
					$changes_detected = 1;

				if($changes_detected == 1)
					break;

				if($settings_client["SyncDat"][$server_id] == "*")
					continue;

				if($changes_detected == 1)
					break;

				if($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
					$changes_detected = 1;

				if($changes_detected == 1)
					break;
				}

			foreach($settings_client["SyncDat"] as $server_id => $null)
				{
				if($changes_detected == 1)
					continue;

				if(isset($settings_server["SyncDat"][$server_id]) === true)
					continue;

				$changes_detected = 1;
				}

			if($changes_detected == 0)
				continue;

			$changed_folders[] = $collection_id;
			}

		if(count($changed_folders) > 0)
			{
			$status = 2; # Changes occured in at least one of the monitored folders. The response specifies the changed folders.

			break;
			}

		$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

		if((isset($settings["Port"]) === false ? "n" : $settings["Port"]) != (isset($_SERVER["REMOTE_PORT"]) === false ? "s" : $_SERVER["REMOTE_PORT"]))
			{
			$status = 8; # An error occurred on the server.

			active_sync_debug("DIED", "RESPONSE"); die();

			break;
			}

		sleep(10);

		clearstatcache();
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("Ping");

	$response->x_open("Ping");

		$response->x_open("Status");
			$response->x_print($status);
		$response->x_close("Status");

		if($status == 2) # Changes occured in at least one of the monitored folders. The response specifies the changed folders.
			{
			$response->x_open("Folders");

				foreach($changed_folders as $collection_id)
					{
					$response->x_open("Folder");
						$response->x_print($collection_id);
					$response->x_close("Folder");
					}

			$response->x_close("Folders");
			}

		if($status == 5) # The specified heartbeat interval is outside the allowed range.
			{
			$response->x_open("HeartbeatInterval");
				$response->x_print($heartbeat_interval);
			$response->x_close("HeartbeatInterval");
			}

		if($status == 6) # The Ping command request specified more than the allowed number of folders to monitor.
			{
			$response->x_open("MaxFolders");
				$response->x_print($max_folders);
			$response->x_close("MaxFolders");
			}

	$response->x_close("Ping");

	return($response->response);
	}
?>
