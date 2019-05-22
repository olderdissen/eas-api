<?
function active_sync_create_guid_filename($user, $collection_id)
	{
	$count = 0;

	while(1)
		{
		$server_id = active_sync_create_guid();

		if(file_exists(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data") === false)
			return($server_id);

		$count = $count + 1;

		if($count > 20)
			break;
		}

	active_sync_debug("failed to create a unique filename");

	return(0);
	}
?>
