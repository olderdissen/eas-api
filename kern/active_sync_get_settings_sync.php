<?
function active_sync_get_settings_sync($user, $collection_id, $device_id = "")
	{
	$retval = array("SyncKey" => 0, "SyncDat" => array());

	if($device_id == "")
		{
		foreach(glob(DAT_DIR . "/" . $user . "/" . $collection_id . "/*.data") as $file)
			{
			$server_id = basename($file, ".data");

			$retval["SyncDat"][$server_id] = filemtime(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data");
			}
		}

	if($device_id != "")
		{
		$retval = active_sync_get_settings(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $device_id . ".sync");

		foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
			$retval[$key] = (isset($retval[$key]) === false ? $value : $retval[$key]);
		}

	return($retval);
	}
?>
