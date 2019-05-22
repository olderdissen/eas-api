<?
function active_sync_maildir_delete_recursive($folder)
	{
	foreach(scandir($folder) as $file)
		{
		if(($file == ".") || ($file == ".."))
			continue;

		if(is_dir($folder . "/" . $file) === true)
			active_sync_maildir_delete_recursive($folder . "/" . $file);
		else
			unlink($folder . "/" . $file);
		}

	rmdir($folder);
	}
?>
