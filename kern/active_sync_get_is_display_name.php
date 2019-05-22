<?
function active_sync_get_is_display_name($user, $display_name)
	{
	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($settings_server["SyncDat"] as $folder)
		{
		if($folder["DisplayName"] != $display_name)
			continue;

		return(1);
		}

	return(0);
	}
?>
