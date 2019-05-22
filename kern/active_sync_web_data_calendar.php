<?
function active_sync_web_data_calendar($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	$settings["Settings"]["CalendarSync"] = (isset($settings["Settings"]["CalendarSync"]) === false ? 0 : $settings["Settings"]["CalendarSync"]);

	$calendar_sync = array("", "- 1 week", "- 1 month", "- 3 month", "- 6 month");

	$retval = array();

	if(strlen($request["StartTime"]) == 0)
		{
		# StartTime is missed
		}
	elseif(strlen($request["EndTime"]) == 0)
		{
		# EndTime is missed
		}
	else
		{
		foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
			{
			$server_id = basename($file, ".data");

			$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

			foreach(array("EndTime" => 0, "StartTime" => 0, "AllDayEvent" => 0, "Subject" => "", "Location" => "") as $token => $value)
				$data["Calendar"][$token] = (isset($data["Calendar"][$token]) === false ? $value : $data["Calendar"][$token]);

			foreach(array("Interval" => 1, "Occurrences" => 1) as $token => $value)
				$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) === false ? $value : $data["Recurrence"][$token]);

			$data["Calendar"]["StartTime"]	= strtotime($data["Calendar"]["StartTime"]);
			$data["Calendar"]["EndTime"]	= strtotime($data["Calendar"]["EndTime"]);

			foreach(range(1, $data["Recurrence"]["Occurrences"]) as $i)
				{
				$add = 0;

				if(($request["StartTime"] == "*") && ($request["EndTime"] == "*")) # request by agenda view
					{
					if($data["Calendar"]["StartTime"] >= strtotime($calendar_sync[$settings["Settings"]["CalendarSync"]])) # starts at selected day, ends at selected day
						{
						$add = 1;
						}
					}
				elseif($request["StartTime"] == "*")
					{
					}
				elseif($request["EndTime"] == "*")
					{
					}
				elseif($data["Calendar"]["EndTime"] <= $request["StartTime"])
					{
					}
				elseif($data["Calendar"]["StartTime"] >= $request["EndTime"])
					{
					}
				else
					{
					$add = 1;
					}

				if($add == 1)
					$retval[] = array($data["Calendar"]["StartTime"], $data["Calendar"]["EndTime"], $data["Calendar"]["AllDayEvent"], $server_id, $data["Calendar"]["Subject"], $data["Calendar"]["Location"]);

				$data["Calendar"]["StartTime"]	= $data["Calendar"]["StartTime"]  + ($data["Recurrence"]["Interval"] * 86400);
				$data["Calendar"]["EndTime"]	= $data["Calendar"]["EndTime"] + ($data["Recurrence"]["Interval"] * 86400);
				}
			}
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}
?>
