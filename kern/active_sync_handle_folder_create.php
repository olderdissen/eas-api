<?
function active_sync_handle_folder_create($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$sync_key	= strval($xml->SyncKey);
	$parent_id	= strval($xml->ParentId);
	$display_name	= strval($xml->DisplayName);
	$type		= strval($xml->Type);

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

		$status = active_sync_folder_create($request["AuthUser"], $parent_id, $display_name, $type);
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
		$settings_client["SyncDat"] = $settings_server["SyncDat"];

		active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings_client);

		$server_id = active_sync_get_collection_id_by_display_name($request["AuthUser"], $display_name);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderCreate");

		foreach(($status == 1 ? array("Status" => $status, "SyncKey" => $sync_key_new, "ServerId" => $server_id) : array("Status" => $status)) as $token => $value)
			{
			$response->x_open($token);
				$response->x_print($value);
			$response->x_close($token);
			}

	$response->x_close("FolderCreate");

	return($response->response);
	}
?>
