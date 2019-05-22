<?
function active_sync_web_data_contacts($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	foreach(array("ShowBy" => 1, "SortBy" => 1, "PhoneOnly" => 0) as $key => $value)
		$settings["Settings"][$key] = (isset($settings["Settings"][$key]) === false ? $value : $settings["Settings"][$key]);

	$retval = array();

	foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		$active = 0;

		if(isset($_GET["Category"]) === false) # ...
			{
			if(isset($data["Categories"]))
				if(count($data["Categories"]) > 0)
					$active = 1;
			}
		elseif($_GET["Category"] == "") # nicht zugewiesen
			{
			if(isset($data["Categories"]) === false) # no categories found
				$active = 1;
			elseif(count($data["Categories"]) == 0) # empty categories found
				$active = 1;
			}
		elseif($_GET["Category"] != "*") # alle
			{
			if(isset($data["Categories"]))
				if(count($data["Categories"]) > 0)
					if(in_array($_GET["Category"], $data["Categories"]))
						{
						$active = 1;
						}
			}
		elseif(isset($settings["Categories"]) === false) # no setup found
			$active = 1;
		elseif(count($settings["Categories"]) == 0) # empty setup found
			$active = 1;
		elseif(isset($data["Categories"]) === false) # no categories found
			{
			if(isset($settings["Categories"]["*"]))
				if($settings["Categories"]["*"] == 1)
					$active = 1;
			}
		elseif(count($data["Categories"]) == 0) # empty categories found
			{
			if(isset($settings["Categories"]["*"]))
				if($settings["Categories"]["*"] == 1)
					$active = 1;
			}
		else
			{
			foreach($data["Categories"] as $category)
				{
				if(isset($settings["Categories"][$category]) === false) # no setup found
					{
					$active = 1;

					break;
					}

				if($settings["Categories"][$category] == 1)
					{
					$active = 1;

					break;
					}
				}
			}

		if($active == 0)
			continue;

		$name_show = active_sync_create_fullname_from_data_for_contacts($data, $settings["Settings"]["ShowBy"]);
		$name_sort = active_sync_create_fullname_from_data_for_contacts($data, $settings["Settings"]["SortBy"]);

		$name_sort = str_replace(array("ä", "Ä", "ö", "Ö", "ü,", "Ü", "ß"), array("a", "A", "o", "O", "u", "U", "s"), $name_sort);
#		$name_sort = active_sync_normalize_chars($name_sort);
		$name_sort = strtolower($name_sort);

		$picture = (isset($data["Contacts"]["Picture"]) === false ? "/active-sync/web/images/contacts_default_image_small.png" : "data:image/unknown;base64," . $data["Contacts"]["Picture"]);

		$search_entry = array($name_sort, $name_show, $server_id, $picture);

		if($settings["Settings"]["PhoneOnly"] != 0)
			if(active_sync_get_is_phone_available($data) == 0)
				continue;

		$add = 0;

		if(isset($_GET["Search"]) === false)
			$add = 1;
		elseif($_GET["Search"] == "")
			$add = 1;

		if($add == 0)
			$add = active_sync_compare_phone($data, $_GET["Search"]);

		if($add == 0)
			$add = active_sync_compare_address($data, $_GET["Search"]);

		if($add == 0)
			$add = active_sync_compare_other($data, $_GET["Search"]);

		if($add == 0)
			$add = active_sync_compare_name($data, $_GET["Search"]);

		if($add == 1)
			$retval[] = $search_entry;
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}
?>
