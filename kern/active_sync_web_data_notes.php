<?
function active_sync_web_data_notes($request)
	{
	$retval = array();

	foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		foreach(array("Subject", "LastModifiedDate") as $token)
			$data["Notes"][$token] = (isset($data["Notes"][$token]) === false ? "..." : $data["Notes"][$token]);

		$subject		= $data["Notes"]["Subject"];
		$last_modified_date	= $data["Notes"]["LastModifiedDate"];

		$last_modified_date	= strtotime($last_modified_date);

		$retval[] = array($subject, $server_id, $last_modified_date);
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}
?>
