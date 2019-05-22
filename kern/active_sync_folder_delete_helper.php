<?
function active_sync_folder_delete_helper(& $folders, $user, $server_id)
	{
	foreach($folders["SyncDat"] as $id => $folder)
		{
		if($folder["ParentId"] == $server_id)
			active_sync_folder_delete_helper($folders, $user, $folder["ServerId"]);

		if($folder["ServerId"] == $server_id)
			unset($folders["SyncDat"][$id]);
		}

	foreach(scandir(DAT_DIR . "/" . $user . "/" . $server_id) as $file)
		{
		if(is_dir(DAT_DIR . "/" . $user . "/" . $server_id . "/" . $file) === true)
			continue;

		unlink(DAT_DIR . "/" . $user . "/" . $server_id . "/" . $file);
		}

	rmdir(DAT_DIR . "/" . $user . "/" . $server_id);
	}
?>
