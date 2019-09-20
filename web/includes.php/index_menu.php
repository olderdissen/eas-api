<?
if($Request["Cmd"] == "Menu")
	{
	$retval = array();

	switch($Request["ItemId"])
		{
		case("Numbers");
			$data = active_sync_get_settings_data($Request["AuthUser"], $Request["CollectionId"], $Request["ServerId"]);

			foreach(array("HomePhone", "Home2Phone", "BusinessPhone", "Business2Phone", "CarPhone", "MobilePhone", "Pager", "RadioPhone") as $key)
				{
				$key = $key . "Number";

				if(isset($data["Contacts"][$key]))
					if($data["Contacts"][$key] != "")
						$retval[$key] = $data["Contacts"][$key];
				}

			break;
		case("Folders");
			$folders = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

			$default = active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["CollectionId"]);

			foreach($folders["SyncDat"] as $folder_id => $folder_data)
				{
				$class = active_sync_get_class_by_type($folder_data["Type"]);

				$special = active_sync_get_is_special_folder($folder_data["Type"]);

				if(($class == $default) && ($special == 0))
					$retval[$folder_data["ServerId"]] = $folder_data["DisplayName"];
				}

			break;
		}

	header("Content-Type: text/javascript; charset=\"UTF-8\"");

	print(json_encode($retval));
	}

if($Request["Cmd"] == "MenuItems")
	{
	html_item("blank.png", "<a href=\"index.php\">Startseite</a>");

	print("<hr>");

	$folders = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

	html_folders_menu($folders["SyncDat"]);

	print("<hr>");

	foreach(array("Gruppen" => "Category", "Geräte" => "Device", "Ordner" => "Folder", "OOF" => "Oof", "Einstellungen" => "Settings") as $menu => $command)
		html_item("blank.png", "<span class=\"span_link\" onclick=\"handle_link({ cmd : '" . $command . "' });\">" . $menu . "<span>");

	$admin = "F";
	
	$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/login.data");

	foreach($settings["login"] as $login)
		if($login["User"] == $_SERVER["PHP_AUTH_USER"])
			$admin = $login["IsAdmin"];

	if($admin == "T")
		{
		print("<hr>");

		foreach(array("Benutzer" => "User", "Richtlinien" => "Policy", "Rechte" => "Rights") as $menu => $command)
			html_item("blank.png", "<span class=\"span_link\" onclick=\"handle_link({ cmd : '" . $command . "' });\">" . $menu . "<span>");
		}
	}

function html_folders_menu($folders, $level = 0, $parent_id = 0)
	{
	global $Request;

	foreach($folders as $folder_id => $folder_data)
		{
		if($folder_data["ParentId"] != $parent_id)
			continue;

		print("<div style=\"padding-left: " . (($level * 16) + 2) . "px;\">");

			$symbol = active_sync_get_icon_by_type($folder_data["Type"]);

			$class = active_sync_get_class_by_type($folder_data["Type"]);

			$special = active_sync_get_is_special_folder($folder_data["Type"]);

			$text = "<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List', collection_id : '" . $folder_data["ServerId"] . "' });\">" . $folder_data["DisplayName"] . "</span>";

			if($class == "Email")
				{
				$read = 0;
				$unread = 0;

				foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . "/" . $folder_data["ServerId"] . "/*.data") as $file)
					{
					$server_id = basename($file, ".data");

					$data = active_sync_get_settings_data($Request["AuthUser"], $folder_data["ServerId"], $server_id);

					if(! isset($data["Email"]["Read"]))
						$unread ++;
					elseif($data["Email"]["Read"] == 0)
						$unread ++;
					elseif($data["Email"]["Read"] == 1)
						$read ++;
					else
						$read ++;
					}

				$text .= "<small id=\"collection:" . $folder_data["ServerId"] . "\">" . ($unread ? " (" . $unread . ")" : "") . "</small>";
				}

			html_item($symbol, $text);

		print("</div>");

		html_folders_menu($folders, $level + 1, $folder_data["ServerId"]);
		}
	}

function html_item($symbol, $text)
	{
	print("<div style=\"padding: 2px;\">");
		print("<div style=\"width: 16px; height: 16px; background-image: url(images/" . $symbol . "); float: left;\"></div>");
		print("<div style=\"padding-left: 21px;\">");
			print($text);
		print("</div>");
	print("</div>");
	}
?>
