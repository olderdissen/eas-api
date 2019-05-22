<?
function active_sync_put_parent_id($user, $server_id, $parent_id)
	{
	$folders = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($folders["SyncDat"] as $id => $folder)
		{
		if($folder["ServerId"] != $server_id)
			continue;

		$folders["SyncDat"][$id]["ParentId"] = $parent_id;

		active_sync_put_settings(DAT_DIR . "/" . $user . ".sync", $folders);

		return(1);
		}

	return(0);
	}
?>
