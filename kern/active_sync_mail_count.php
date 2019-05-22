<?
function active_sync_mail_count($user, $collection_id)
	{
	$retval = array(0, 0);

	foreach(glob(DAT_DIR . "/" . $user . "/" . $collection_id . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		if(isset($data["Email"]["Read"]) === false)
			$retval[0] = $retval[0] + 1;
		elseif($data["Email"]["Read"] == 0)
			$retval[0] = $retval[0] + 1;
		elseif($data["Email"]["Read"] == 1)
			$retval[1] = $retval[1] + 1;
		else
			$retval[1] = $retval[1] + 1;
		}

	return($retval);
	}
?>
