<?
function active_sync_web_data_tasks($request)
	{
	$retval = array();

	foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		foreach(array("DueDate", "Sensitivity", "StartDate", "Subject") as $key)
			$data["Tasks"][$key] = (isset($data["Tasks"][$key]) === false ? "" : $data["Tasks"][$key]);

		$due_date	= $data["Tasks"]["DueDate"];
		$start_date	= $data["Tasks"]["StartDate"];
		$subject	= $data["Tasks"]["Subject"];

		$due_date	= strtotime($due_date);
		$start_date	= strtotime($start_date);

		$retval[] = array($start_date, $due_date, $server_id, $subject);
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}
?>
