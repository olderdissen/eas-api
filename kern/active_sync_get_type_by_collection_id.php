<?
function active_sync_get_type_by_collection_id($user, $server_id)
	{
	$folders = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($folders["SyncDat"] as $folder)
		{
		if($folder["ServerId"] != $server_id)
			continue;

		return($folder["Type"]);
		}

	return(0);
	}
?>
