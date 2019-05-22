<?
function active_sync_folders_init($user)
	{
	if(defined("DAT_DIR") === false)
		die("DAT_DIR is not defined. have you included active_sync_kern.php before? DAT_DIR is needed to store settings and user data.");

	if(is_dir(DAT_DIR) === false)
		mkdir(DAT_DIR, 0777, true);

	if(file_exists(DAT_DIR . "/.htaccess") === false)
		{
		$data = array();

		$data[] = "<Files \"*\">";
		$data[] = "\tOrder allow,deny";
		$data[] = "\tDeny from all";
		$data[] = "</Files>";

		file_put_contents(DAT_DIR . "/.htaccess", implode("\n", $data));
		}

	if(is_dir(DAT_DIR . "/" . $user) === false)
		mkdir(DAT_DIR . "/" . $user, 0777, true);

	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync"); # observe order of parameters

	foreach(array("SyncDat" => array()) as $key => $value)
		$settings_server[$key] = (isset($settings_server[$key]) === false ? $value : $settings_server[$key]);

	if(count($settings_server["SyncDat"]) == 0)
		{
		$settings_server["SyncDat"] = active_sync_get_default_folder();

		active_sync_put_settings(DAT_DIR . "/" . $user . ".sync", $settings_server); # observe order of parameters
		}

	foreach($settings_server["SyncDat"] as $id => $folder)
		{
		if(is_dir(DAT_DIR . "/" . $user . "/" . $folder["ServerId"]) === true)
			continue;

		mkdir(DAT_DIR . "/" . $user . "/" . $folder["ServerId"], 0777, true);
		}

	if(defined("LOG_DIR") === false)
		return;

	if(is_dir(LOG_DIR) === false)
		mkdir(LOG_DIR, 0777, true);
	}
?>
