<?
function active_sync_web_save_notes($request)
	{
	$data = array();

	foreach(active_sync_get_default_notes() as $token => $value)
		{
		if(isset($_POST[$token]) === false)
			continue;

		if(strlen($_POST[$token]) == 0)
			continue;

		$data["Notes"][$token] = $_POST[$token];
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

	$data["Notes"]["LastModifiedDate"] = date("Y-m-d\TH:i:s\Z");

	if($request["ServerId"] == "")
		$request["ServerId"] = active_sync_create_guid_filename($request["AuthUser"], $request["CollectionId"]);

	active_sync_put_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"], $data);

	print(1);
	}
?>
