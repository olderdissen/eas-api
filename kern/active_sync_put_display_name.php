<?
function active_sync_put_display_name($user, $server_id, $display_name)
	{
	$folders = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($folders["SyncDat"] as $id => $folder)
		{
		if($folder["ServerId"] != $server_id)
			continue;

		$folders["SyncDat"][$id]["DisplayName"] = $display_name;

		active_sync_put_settings(DAT_DIR . "/" . $user . ".sync", $folders);

		return(1);
		}

	return(0);
	}
?>
