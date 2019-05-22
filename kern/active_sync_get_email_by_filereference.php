<?
function active_sync_get_email_by_filereference($user, $file_reference)
	{
	$collection_id = active_sync_get_collection_id_by_type($user, 2); # Email ::= 2

	$settings = active_sync_get_settings_sync($user, $collection_id, "");

	foreach($settings["SyncDat"] as $server_id => $timestamp)
		{
		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		if(isset($data["Files"][$file_reference]) === false)
			continue;

		return($server_id);
		}

	return("");
	}
?>
