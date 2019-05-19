<?
function active_sync_get_settings_data($user, $collection_id, $server_id)
	{
#	$retval = active_sync_get_settings(implode("/", array(DAT_DIR, $user, $collection_id, $server_id)) . ".data");
	$retval = active_sync_get_settings(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data");

	return($retval);
	}
?>
