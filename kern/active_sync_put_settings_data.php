<?
function active_sync_put_settings_data($user, $collection_id, $server_id, $data)
	{
	return(active_sync_put_settings(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data", $data));
	}
?>
