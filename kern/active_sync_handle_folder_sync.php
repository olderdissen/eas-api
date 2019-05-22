<?
function active_sync_handle_folder_sync($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$sync_key = strval($xml->SyncKey);

	$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
		$settings_client[$key] = (isset($settings_client[$key]) === false ? $value : $settings_client[$key]);

	if($sync_key == 0)
		{
		$sync_key_new = 1;

		$folders = array();

		$status = 1; # Success.
		}
	elseif($sync_key != $settings_client["SyncKey"])
		{
		$sync_key_new = 0;

		$folders = array();

		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
		}
	else
		{
		$sync_key_new = $settings_client["SyncKey"] + 1;

		$folders = $settings_client["SyncDat"];

		$status = 1; # Success.
		}

	if(active_sync_get_need_wipe($request) != 0)
		$status = 140;

	if(active_sync_get_need_provision($request) != 0)
		{
		$sync_key_new = $sync_key_new - 1;

		$status = 142;
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderSync");

		if($status == 142)
			{
			foreach(array("Status" => $status) as $token => $value)
				{
				$response->x_open($token);
					$response->x_print($value);
				$response->x_close($token);
				}
			}
		else
			{
			foreach(array("Status" => $status, "SyncKey" => $sync_key_new) as $token => $value)
				{
				$response->x_open($token);
					$response->x_print($value);
				$response->x_close($token);
				}
			}

		if($status == 1)
			{
			$jobs = array();

			$settings_server = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

			foreach(array("SyncDat" => array()) as $key => $value)
				$settings_server[$key] = (isset($settings_server[$key]) === false ? $value : $settings_server[$key]);

			foreach($settings_server["SyncDat"] as $settings_server_id => $settings_server_data)
				{
				$known = 0;

				foreach($folders as $folders_id => $folders_data)
					{
					if($settings_server_data["ServerId"] != $folders_data["ServerId"])
						{
						}
					elseif($settings_server_data["ParentId"] != $folders_data["ParentId"])
						{
						$jobs["Update"][] = $settings_server_data;

						$folders[$folders_id] = $settings_server_data;
						}
					elseif($settings_server_data["DisplayName"] != $folders_data["DisplayName"])
						{
						$jobs["Update"][] = $settings_server_data;

						$folders[$folders_id] = $settings_server_data;
						}
					elseif($settings_server_data["Type"] != $folders_data["Type"])
						{
						$jobs["Update"][] = $settings_server_data;

						$folders[$folders_id] = $settings_server_data;
						}

					if($settings_server_data["ServerId"] == $folders_data["ServerId"])
						$known = 1;
					}

				if($known == 0)
					{
					$jobs["Add"][] = $settings_server_data;

					$folders[] = $settings_server_data;
					}
				}

			foreach($folders as $folders_id => $folders_data)
				{
				$known = 0;

				foreach($settings_server["SyncDat"] as $settings_server_id => $settings_server_data)
					{
					if($folders_data["ServerId"] != $settings_server_data["ServerId"])
						continue;

					$known = 1;
					}

				if($known == 0)
					{
					$jobs["Delete"][] = $folders_data;

					unset($folders[$folders_id]);
					}
				}

			$actions = array("Update" => array("ServerId", "ParentId", "DisplayName", "Type"), "Delete" => array("ServerId"), "Add" => array("ServerId", "ParentId", "DisplayName", "Type"));

			$count = 0;

			foreach($actions as $action => $fields)
				$count = $count + (isset($jobs[$action]) === false ? 0 : count($jobs[$action]));

			$response->x_open("Changes");

				$response->x_open("Count");
					$response->x_print($count);
				$response->x_close("Count");

				if($count > 0)
					{
					foreach($actions as $action => $fields)
						{
						if(isset($jobs[$action]) === false)
							continue;

						foreach($jobs[$action] as $job)
							{
							$response->x_open($action);

								foreach($fields as $key)
									{
									$response->x_open($key);
										$response->x_print($job[$key]);
									$response->x_close($key);
									}

							$response->x_close($action);
							}
						}
					}

			$response->x_close("Changes");
			}

	$response->x_close("FolderSync");

	$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
		$settings_client[$key] = (isset($settings_client[$key]) === false ? $value : $settings_client[$key]);

	$settings_client["SyncKey"] = $sync_key_new;
	$settings_client["SyncDat"] = $folders;

	active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings_client);

	return($response->response);
	}
?>
