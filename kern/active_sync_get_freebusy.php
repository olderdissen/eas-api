<?
function active_sync_get_freebusy($user_id, $start_time, $end_time, $busy_status = 4, $steps = 1800)
	{
	$retval = array_fill(0, ($end_time - $start_time) / $steps, $busy_status);

	$collection_id = active_sync_get_collection_id_by_type($user_id, 8);

	foreach(glob(DAT_DIR . "/" . $user_id . "/" . $collection_id . "/*.data") as $file) # search in users contacts
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($user_id, $collection_id, $server_id);

		foreach(array("EndTime" => 0, "StartTime" => 0, "BusyStatus" => 0) as $token => $value)
			$data["Calendar"][$token] = (isset($data["Calendar"][$token]) === false ? $value : $data["Calendar"][$token]);

		if(strtotime($data["Calendar"]["StartTime"]) > $end_time)
			continue;

		if(strtotime($data["Calendar"]["EndTime"]) < $start_time)
			continue;

		for($s = $start_time; $s < $end_time; $s = $s + $steps)
			{
			$e = $s + $steps;

			if($s < strtotime($data["Calendar"]["StartTime"]))
				continue;

			if($e > strtotime($data["Calendar"]["EndTime"]))
				continue;

			$k = intval(($s - $start_time) / $steps);

			$v = (isset($data["Calendar"]["BusyStatus"]) === false ? $busy_status : $data["Calendar"]["BusyStatus"]);

			$retval[$k] = $v;
			}
		}

	return(implode("", $retval));
	}
?>
