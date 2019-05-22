<?
function active_sync_get_collection_id_by_type($user, $type)
	{
	$folders = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($folders["SyncDat"] as $folder)
		{
		if($folder["Type"] != $type)
			continue;

		return($folder["ServerId"]);
		}

	return(0);
	}
?>
