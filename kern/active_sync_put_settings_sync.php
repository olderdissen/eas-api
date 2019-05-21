<?
function active_sync_put_settings_sync($user, $collection_id, $device_id, $data)
	{
	# server will never save timestamps to any file.
	# timestamp will always be read from real files

	return(active_sync_put_settings(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $device_id . ".sync", $data));
	}
?>
