<?
function active_sync_get_is_collection_id($user_id, $collection_id)
	{
	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user_id . ".sync");

	foreach($settings_server["SyncDat"] as $folder)
		{
		if($folder["ServerId"] != $collection_id)
			continue;

		return(1);
		}

	return($collection_id == 0 ? 1 : 0);
	}
?>
