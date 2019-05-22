<?
function active_sync_get_calendar_by_uid($user, $uid)
	{
	$collection_id = active_sync_get_collection_id_by_type($user, 8); # Calendar ::= 8 | 14

	$settings_server = active_sync_get_settings_sync($user, $collection_id, "");

	foreach($settings_server["SyncDat"] as $server_id => $timestamp)
		{
		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		if(isset($data["Calendar"]["UID"]) === false)
			continue;

		if($data["Calendar"]["UID"] != $uid)
			continue;

		return($server_id);
		}

	return("");
	}
?>
