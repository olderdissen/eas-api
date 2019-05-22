<?
function active_sync_get_collection_id_by_display_name($user, $display_name)
	{
	$folders = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($folders["SyncDat"] as $folder)
		{
		if($folder["DisplayName"] != $display_name)
			continue;

		return($folder["ServerId"]);
		}

	return(0);
	}
?>
