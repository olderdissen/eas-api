<?
function active_sync_get_folder_free($user_id)
	{
	foreach(range(1000, 8999) as $collection_id)
		{
		if(is_dir(DAT_DIR . "/" . $user_id . "/" . $collection_id) === true)
			continue;

		return($collection_id);
		}

	return(0);
	}
?>
