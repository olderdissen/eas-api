<?
function active_sync_get_parent_id_by_collection_id($user, $server_id)
	{
	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($settings_server["SyncDat"] as $folder)
		{
		if($folder["ServerId"] != $server_id)
			continue;

		return($folder["ParentId"]);
		}

	return(0);
	}
?>
