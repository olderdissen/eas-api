<?
function active_sync_folder_create($user, $parent_id, $display_name, $type)
	{
	if(active_sync_get_is_collection_id($user, $parent_id) == 0)
		return(5);

	if(active_sync_get_is_display_name($user, $display_name) == 1)
		return(2);

	if(active_sync_get_is_type($type) == 0)
		return(10);

	if(active_sync_get_is_special_folder($type) == 1)
		return(3);

	if(active_sync_get_is_user_folder($type) == 0)
		return(3);

	$server_id = active_sync_get_folder_free($user);

	if($server_id == 0)
		return(6);

	if(mkdir(DAT_DIR . "/" . $user . "/" . $server_id, 0777, true) === false)
		return(6);

	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync"); # observe order of parameters

	foreach(array("SyncDat" => array()) as $key => $value)
		$settings_server[$key] = (isset($settings_server[$key]) === false ? $value : $settings_server[$key]);

	$settings_server["SyncDat"][] = array("ServerId" => $server_id, "ParentId" => $parent_id, "Type" => $type, "DisplayName" => $display_name);

	active_sync_put_settings(DAT_DIR . "/" . $user . ".sync", $settings_server); # observe order of parameters

	return(1);
	}
?>
