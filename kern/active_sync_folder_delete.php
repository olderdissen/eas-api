<?
function active_sync_folder_delete($user, $server_id)
	{
	if(active_sync_get_is_collection_id($user, $server_id) == 0)
		return(4);

	$type = active_sync_get_type_by_collection_id($user, $server_id);

	if(active_sync_get_is_special_folder($type) == 1)
		return(3);

	if(active_sync_get_is_user_folder($type) == 0)
		return(3);

	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync"); # observe order of parameters

	foreach(array("SyncDat" => array()) as $key => $value)
		$settings_server[$key] = (isset($settings_server[$key]) === false ? $value : $settings_server[$key]);

	active_sync_folder_delete_helper($settings_server, $user, $server_id);

	active_sync_put_settings(DAT_DIR . "/" . $user . ".sync", $settings_server); # observe order of parameters

	return(1);
	}
?>
