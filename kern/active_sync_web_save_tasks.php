<?
function active_sync_web_save_tasks($request)
	{
	$data = array();

	foreach(active_sync_get_default_tasks() as $token => $default_value)
		{
		if(isset($_POST[$token]) === false)
			continue;

		if(strlen($_POST[$token]) == 0)
			continue;

		$data["Tasks"][$token] = $_POST[$token];
		}

	$body = array();

	foreach(active_sync_get_default_body() as $token => $value)
		{
		if(isset($_POST["Body:" . $token]) === false)
			continue;

		if(strlen($_POST["Body:" . $token]) == 0)
			continue;

		$body[$token] = $_POST["Body:" . $token];
		}

	if(isset($body["Type"]))
		if($body["Type"] == 1)
			if(isset($body["Data"]))
				if(strlen($body["Data"]) > 0)
					$body["Body"][] = $body;

	# 0x01 WeekOfMonth
	# 0x02 DayOfWeek
	# 0x04 MonthOfYear
	# 0x08 DayOfMonth

	$fields = array(0x02, 0x02, 0x18, 0x13, 0x00, 0x1C, 0x17, "WeekOfMonth", "DayOfWeek", "MonthOfYear", "DayOfMonth");

	for($i = 0; $i < 4; $i = $i + 1)
		{
		$field = $fields[$i + 7];

		if((($fields[$_POST["Recurrence:Type"]] >> $i) & 0x01) == 0x00)
			continue;

		if($_POST["Recurrence:" . $field] == "")
			continue;

		$data["Recurrence"][$field] = $_POST["Recurrence:" . $field];
		}

	if((($_POST["Recurrence:Type"] == 3) || ($_POST["Recurrence:Type"] == 6)) && ($_POST["Recurrence:DayOfWeek"] == 127))
		unset($data["Recurrence"]["WeekOfMonth"]);

	if($_POST["Recurrence:Type"] != 4)
		{
		foreach(array("Type", "Occurrences", "Interval", "Until", "CalendarType", "IsLeapMonth", "FirstDayOfWeek") as $key)
			{
			if($_POST["Recurrence:" . $key] == "")
				continue;

			$data["Recurrence"][$key] = $_POST["Recurrence:" . $key];
			}

		if(($data["Recurrence"]["Until"] != "") && ($data["Recurrence"]["Occurrences"] != ""))
			unset($data["Recurrence"]["Until"]);
		}

	if($request["ServerId"] == "")
		$request["ServerId"] = active_sync_create_guid_filename($request["AuthUser"], $request["CollectionId"]);

	active_sync_put_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"], $data);

	print(1);
	}
?>
