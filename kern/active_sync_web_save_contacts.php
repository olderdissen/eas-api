<?
function active_sync_web_save_contacts($request)
	{
	$data = array();

	foreach(active_sync_get_default_contacts() as $token => $default_value)
		{
		if(isset($_POST[$token]) === false)
			continue;

		if(strlen($_POST[$token]) == 0)
			continue;

		$data["Contacts"][$token] = $_POST[$token];
		}

	foreach(active_sync_get_default_contacts2() as $token => $default_value)
		{
		if(isset($_POST[$token]) === false)
			continue;

		if(strlen($_POST[$token]) == 0)
			continue;

		$data["Contacts2"][$token] = $_POST[$token];
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

	foreach(array("Categories", "Children") as $token)
		{
		if(isset($_POST[$token]) === false)
			continue;

		$data[$token] = $_POST[$token]; # !!! ARRAY
		}

	foreach(array("Anniversary", "Birthday") as $token)
		{
		if(isset($data["Contacts"][$token]) === false)
			{
			continue;
			}

		$data["Contacts"][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data["Contacts"][$token]));
#		$data["Contacts"][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data["Contacts"][$token]) - date("Z", strtotime($data["Contacts"][$token])));
		}

	foreach(array("Email1Address", "Email2Address", "Email3Address") as $token)
		{
		if(isset($data["Contacts"]["FileAs"]) === false)
			continue;

		if(isset($data["Contacts"][$token]) === false)
			continue;

		$data["Contacts"][$token] = "\"" . $data["Contacts"]["FileAs"]  . "\" <" . $data["Contacts"][$token] . ">";
		}

	if($request["ServerId"] == "")
		$request["ServerId"] = active_sync_create_guid_filename($request["AuthUser"], $request["CollectionId"]);

	active_sync_put_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"], $data);

	print(1);
	}
?>
