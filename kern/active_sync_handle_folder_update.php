<?
function active_sync_handle_folder_update($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$sync_key	= strval($xml->SyncKey);
	$server_id	= strval($xml->ServerId);
	$parent_id	= strval($xml->ParentId);
	$display_name	= strval($xml->DisplayName);

	$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
		$settings_client[$key] = (isset($settings_client[$key]) === false ? $value : $settings_client[$key]);

	if($sync_key != $settings_client["SyncKey"])
		{
		$sync_key_new = 0;

		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
		}
	else
		{
		$sync_key_new = $settings_client["SyncKey"] + 1;

		$status = active_sync_folder_update($request["AuthUser"], $server_id, $parent_id, $display_name);
		}

	if($status == 1)
		{
		$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

		foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
			$settings_client[$key] = (isset($settings_client[$key]) === false ? $value : $settings_client[$key]);

		$settings_server = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

		foreach(array("SyncDat" => array()) as $key => $value)
			$settings_server[$key] = (isset($settings_server[$key]) === false ? $value : $settings_server[$key]);

		$settings_client["SyncKey"] = $sync_key_new;
		$settings_client["SyncDat"] = $folders;

		active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings_client);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderUpdate");

		foreach(($status == 1 ? array("Status" => $status, "SyncKey" => $sync_key_new) : array("Status" => $status)) as $token => $value)
			{
			$response->x_open($token);
				$response->x_print($value);
			$response->x_close($token);
			}

	$response->x_close("FolderUpdate");

	return($response->response);
	}
?>
