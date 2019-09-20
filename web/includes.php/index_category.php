<?
if($Request["Cmd"] == "Category")
	{
	$user = $Request["AuthUser"];

	$collection_id	= active_sync_get_collection_id_by_type($user, 9); # Contacts

	$settings	= active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . ".sync");

	$categories_of_contacts = active_sync_get_categories_by_collection_id($user, $collection_id);

	if(isset($settings["Categories"]) === false)
		$categories_of_settings = array();
	else
		$categories_of_settings = $settings["Categories"];

	$list = array();

	foreach($categories_of_settings as $category => $enabled)
		{
		if(in_array($category, $list) === true)
			continue;

		$list[] = $category;
		}

	foreach($categories_of_contacts as $category => $count)
		{
		if(in_array($category, $list) === true)
			continue;

		$list[] = $category;
		}

	if(count($list) > 1)
		sort($list, SORT_LOCALE_STRING);

	print("<form style=\"height: 100%;\">");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"CategorySave\">");

		print("<table style=\"height: 100%; width: 100%;\">");
			print("<tr>");
				print("<td>");
					print("Die Kontakte die den markierten Gruppen zugeordnet sind werden angezeigt:");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
			print("</tr>");
			print("<tr style=\"height: 100%;\">");
				print("<td valign=\"top\">");
					print("<div style=\"height: 100%; width: 100%; position: relative;\">");
						print("<div style=\"background-color: #E0E0E0; border-style: solid; border-width: 1px; height: 100%; overflow-y: scroll; position: absolute;\">");
							print("<table style=\"width: 500px;\">");

								$c = 0;

								foreach($list as $category)
									{
									if($category == "*")
										continue;

									print("<tr onmouseover=\"this.className = 'list_hover';\" onmouseout=\"this.className = '" . ($c % 2 ? "list_even" : "list_odd") . "';\" class=\"" . ($c % 2 ? "list_even" : "list_odd") . "\">");
										print("<td>");
											print("<input type=\"checkbox\" name=\"_" . $category . "\" value=\"T\"" . (isset($categories_of_settings[$category]) === false ? " checked" : ($categories_of_settings[$category] == 0 ? "" : " checked")) . ">");
										print("</td>");
										print("<td style=\"width: 100%;\">");
											print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List', collection_id : '9009', category_id : '" . $category . "', class_id: 'Contacts' });\">");
												print($category);
											print("</span>");
											print(" ");
											print("<small>");
												print("(");
													print(isset($categories_of_contacts[$category]) === false ? 0 : $categories_of_contacts[$category]);
												print(")");
											print("</small>");
										print("</td>");
										print("<td>");
											print("[");
												print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'CategoryDeleteConfirm', item_id : '" . $category . "' });\">");
													print("Löschen");
												print("</span>");
											print("]");
										print("</td>");
										print("<td>");
											print("[");
												print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'CategoryEdit', item_id : '" . $category . "' });\">");
													print("Umbenennen");
												print("</span>");
											print("]");
										print("</td>");
									print("</tr>");

									$c = $c + 1;
									}

								print("<tr onmouseover=\"this.className = 'list_hover';\" onmouseout=\"this.className = '" . ($c % 2 ? "list_even" : "list_odd") . "';\" class=\"" . ($c % 2 ? "list_even" : "list_odd") . "\">");
									print("<td>");
										print("<input type=\"checkbox\" name=\"_*\" value=\"T\"" . (isset($categories_of_settings["*"]) === false ? " checked" : ($categories_of_settings["*"] == 0 ? "" : " checked")) . ">");
									print("</td>");
									print("<td>");
										print("Alle Anderen Kontakte"); # Toate celealte contacte
										print(" ");
										print("<small>");
											print("(");
												print($categories_of_contacts["*"]);
											print(")");
										print("</small>");
									print("</td>");
									print("<td colspan=\"2\">");
										print("&nbsp;");
									print("</td>");
								print("</tr>");
							print("</table>");
						print("</div>");
					print("</div>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("<small>");
						print(wordwrap("Hinweis für das Löschen: Beim löschen einer Gruppe werden die zugeordneten Kontakte aus der Gruppen gelöscht, nicht jedoch die Kontakte selbst.", 128, "<br>", false));
					print("</small>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'CategorySave' });\">");
							print("Speichern");
						print("</span>");
					print("]");
					print(" ");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'ResetForm' });\">");
							print("Zurücksetzen");
						print("</span>");
					print("]");
					print(" ");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'CategoryEdit', item_id : '' });\">");
							print("Hinzufügen");
						print("</span>");
					print("]");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}

if($Request["Cmd"] == "CategoryCreate")
	{
	$category = $Request["LongId"];

	if($category == "")
		$state = 2;
	else
		{
		$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

		$settings["Categories"][$category] = 1;

		active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"], $settings);

		$state = 3;
		}

	print($state);
	}

if($Request["Cmd"] == "CategoryDelete")
	{
	if($Request["ItemId"] == "")
		print(4);
	else
		{
		$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

		unset($settings["Categories"][$Request["ItemId"]]);

		active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync", $settings);

		foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . "/9009/*.data") as $file)
			{
			$server_id = basename($file, ".data");

			$data = active_sync_get_settings_data($Request["AuthUser"], "9009", $server_id);

			if(isset($data["Categories"]) === false)
				continue;

			foreach($data["Categories"] as $category_id => $category_data)
				{
				if($category_data != $Request["ItemId"])
					continue;

				unset($data["Categories"][$category_id]);

				active_sync_put_settings_data($Request["AuthUser"], "9009", $server_id, $data);

				break;
				}
			}

		print(5);
		}
	}

if($Request["Cmd"] == "CategoryEdit")
	{
	print("<form onsubmit=\"return false;\">");

		if($Request["ItemId"] == "")
			{
			print("<input type=\"hidden\" name=\"Cmd\" value=\"CategoryCreate\">");
			print("<p>");
				print("Anlegen einer neuen Gruppe");
				print(".");
			print("</p>");
			}

		if($Request["ItemId"] != "")
			{
			print("<input type=\"hidden\" name=\"Cmd\" value=\"CategoryUpdate\">");
			print("<input type=\"hidden\" name=\"ItemId\" value=\"" . $Request["ItemId"] . "\">");
			print("<p>");
				print("Umbenennen der Gruppe");
				print(" ");
				print("<b>");
					print($Request["ItemId"]);
				print("</b>");
				print(".");
			print("</p>");
			}

		print("<table>");
			print("<tr>");
				print("<td style=\"padding-left: 30px;\" align=\"right\">");
					print("Neuer Name");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<input type=\"text\" name=\"LongId\" value=\"" . $Request["ItemId"] . "\">");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</form>");

	print("<p>");
		print("[");

			if($Request["ItemId"] == "")
				{
				print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'CategoryCreate' });\">");
					print("Speichern");
				print("</span>");
				}

			if($Request["ItemId"] != "")
				{
				print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'CategoryUpdate' });\">");
					print("Speichern");
				print("</span>");
				}

		print("]");
		print(" ");
		print("[");
			print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Category' });\">");
				print("Zurück");
			print("</span>");
		print("]");
	print("</p>");
	}

if($Request["Cmd"] == "CategoryList")
	{
	$user = $Request["AuthUser"];

	$collection_id	= active_sync_get_collection_id_by_type($user, 9); # Contacts

	$settings	= active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . ".sync");

	$categories_of_contacts = active_sync_get_categories_by_collection_id($user, $collection_id);

	if(isset($settings["Categories"]) === false)
		$categories_of_settings = array();
	else
		$categories_of_settings = $settings["Categories"];

	$list = array();

	foreach($categories_of_settings as $category => $enabled)
		{
		if(in_array($category, $list) === true)
			continue;

		$list[] = $category;
		}

	foreach($categories_of_contacts as $category => $count)
		{
		if(in_array($category, $list) === true)
			continue;

		$list[] = $category;
		}

	if(count($list) > 1)
		sort($list, SORT_LOCALE_STRING);

	$retval = array();

	foreach($list as $item)
		$retval[$item] = array((isset($categories_of_settings[$item]) === false ? 0 : $categories_of_settings[$item]), (isset($categories_of_contacts[$item]) === false ? 0 : $categories_of_contacts[$item]));

	header("Content-Type: application/json; charset=\"UTF-8\"");

	print(json_encode($retval));
	}

if($Request["Cmd"] == "CategorySave")
	{
	$collection_id	= active_sync_get_collection_id_by_type($Request["AuthUser"], 9); # Contacts

	$categories	= active_sync_get_categories_by_collection_id($Request["AuthUser"], $collection_id);

	$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

	foreach($categories as $category => $count)
		$settings["Categories"][$category] = (isset($_POST["_" . $category]) ? 1 : 0);

	active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync", $settings);

	print(1);
	}

if($Request["Cmd"] == "CategoryState")
	{
	$retval = array();

	$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

	if(isset($settings["Categories"]) === true)
		$retval = $settings["Categories"];
	else
		$retval["*"] = 1;

	if(count($retval) > 1)
		ksort($retval, SORT_LOCALE_STRING);

	header("Content-Type: application/json; charset=\"UTF-8\"");

	print(json_encode($retval));
	}

if($Request["Cmd"] == "CategoryUpdate")
	{
	if($Request["ItemId"] == "") # old name
		print(9);
	elseif($Request["LongId"] == "") # new name
		print(8);
	elseif($Request["ItemId"] == $Request["LongId"])
		print(6);
	else
		{
		$o = $Request["ItemId"];
		$n = $Request["LongId"];

		$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

		if(isset($settings["Categories"][$o]) === true) # if setting for this category is available
			{
			$settings["Categories"][$n] = $settings["Categories"][$o];

			unset($settings["Categories"][$o]);
			}

		active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync", $settings);

		foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . "/9009/*.data") as $file)
			{
			$server_id = basename($file, ".data");

			$data = active_sync_get_settings_data($Request["AuthUser"], "9009", $server_id);

			if(isset($data["Categories"]) === false)
				continue;

			foreach($data["Categories"] as $category_id => $category_data)
				{
				if($category_data != $Request["ItemId"])
					continue;

				$data["Categories"][$category_id] = $Request["LongId"]; # here we can also unset category and add the new one. but we want to keep the sorting so we just rename it.

				active_sync_put_settings_data($Request["AuthUser"], "9009", $server_id, $data);

				break;
				}
			}

		print(7);
		}
	}
?>
