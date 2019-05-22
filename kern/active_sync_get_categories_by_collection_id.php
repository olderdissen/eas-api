<?
function active_sync_get_categories_by_collection_id($user_id, $collection_id)
	{
	$retval = array("*" => 0); # this is placeholder to count contacts without category

	foreach(glob(DAT_DIR . "/" . $user_id . "/" . $collection_id . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($user_id, $collection_id, $server_id);

		if(isset($data["Categories"]) === false)
			{
			$retval["*"] = $retval["*"] + 1;

			continue;
			}

		if(count($data["Categories"]) == 0)
			{
			$retval["*"] = $retval["*"] + 1;

			continue;
			}

		foreach($data["Categories"] as $id => $category)
			{
			if(isset($retval[$category]) === false)
				$retval[$category] = 1;
			else
				$retval[$category] = $retval[$category] + 1;
			}
		}

	if(count($retval) > 1)
		ksort($retval, SORT_LOCALE_STRING);

	return($retval);
	}
?>
