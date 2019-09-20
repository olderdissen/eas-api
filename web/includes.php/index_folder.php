<?
if($Request["Cmd"] == "Folder")
	{
	$folders = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

	print("<table>");
		folders_list($folders);

		print("<tr>");
			print("<td>");
				print("&nbsp;");
			print("</td>");
			print("<td colspan=\"2\">");
				print("[");
					print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'FolderEdit', server_id : '' });\">");
						print("Hinzufügen");
					print("</span>");
				print("]");
			print("</td>");
		print("</tr>");
	print("</table>");

	print("<script type=\"text/javascript\">");
		print("var status = " . (isset($_GET["Status"]) === false ? 1 : $_GET["Status"]) . ";");

		print("if(status != 1)");
			print("{");
			print("popup_folder_error(status);");
			print("}");
	print("</script>");
	}

function folders_edit($folders, $selected_id, $level = 0, $parent_id = 0)
	{
	foreach($folders["SyncDat"] as $folder_id => $folder_data)
		{
		if($folder_data["ParentId"] == $selected_id)
			{
			# folder can not be moved to a subfolder of itself
			}
		elseif($folder_data["ParentId"] == $parent_id)
			{
			$txt = (active_sync_get_is_special_folder($folder_data["Type"]) == 1 ? $folder_data["DisplayName"] : $folder_data["DisplayName"]);

			print("<option style=\"padding-left: " . ($level * 16) . "px;\" value=\"" . $folder_data["ServerId"] . "\"" . ($selected_id == $folder_data["ServerId"] ? " selected" : "") . ">");
				print($txt);
			print("</option>");

			folders_edit($folders, $selected_id, $level + 1, $folder_data["ServerId"]);
			}
		}
	}

function folders_list($folders, $level = 0, $parent_id = 0)
	{
	foreach($folders["SyncDat"] as $folder_id => $folder_data)
		{
		if($folder_data["ParentId"] != $parent_id)
			{
			continue;
			}

		print("<tr>");
			print("<td style=\"padding-left: " . ($level * 16) . "px;\">");
				print("<table>");
					print("<tr>");
						print("<td>");
							print("<img width=\"16\" height=\"16\" src=\"images/" . active_sync_get_icon_by_type($folder_data["Type"]) . "\">");
						print("</td>");
						print("<td style=\"width: 100%;\">");
							print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List', collection_id : '" . $folder_data["ServerId"] . "' });\">");
								print($folder_data["DisplayName"]);
							print("</span>");
						print("</td>");
					print("</tr>");
				print("</table>");
			print("</td>");

			$special = active_sync_get_is_special_folder($folder_data["Type"]);

			if($special == 1)
				{
				print("<td>");
					print("[");
						print("Löschen");
					print("]");
				print("</td>");
				print("<td>");
					print("[");
						print("Bearbeiten");
					print("]");
				print("</td>");
				}
			else
				{
				print("<td>");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'FolderDeleteConfirm', server_id : '" . $folder_data["ServerId"] . "' });\">");
							print("Löschen");
						print("</span>");
					print("]");
				print("</td>");
				print("<td>");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'FolderEdit', server_id : '" . $folder_data["ServerId"] . "' });\">");
							print("Bearbeiten");
						print("</span>");
					print("]");
				print("</td>");
				}

		print("</tr>");

		folders_list($folders, $level + 1, $folder_data["ServerId"]);
		}
	}

if($Request["Cmd"] == "FolderCreate")
	{
	$Request["DisplayName"] = $Request["DisplayName"] ? $Request["DisplayName"] : "(Unbenannt)";

	$status = active_sync_folder_create($Request["AuthUser"], $Request["ParentId"], $Request["DisplayName"], $Request["Type"]);

	print(1);
	}

if($Request["Cmd"] == "FolderDelete")
	{
	$status = active_sync_folder_delete($Request["AuthUser"], $Request["ServerId"]);

	print(1);
	}

if($Request["Cmd"] == "FolderEdit")
	{
	$folders = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

	print("<p>");
		print("Vom Benutzer hinzugefügte Ordner werden möglicherweise nicht auf einem mobilen Endgerät dargestellt.");
	print("</p>");

	print("<form onsubmit=\"return false;\">");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"" . ($Request["ServerId"] ? "FolderUpdate" : "FolderCreate") . "\">");

		print($Request["AuthUser"] == "" ? "" : "<input type=\"hidden\" name=\"User\" value=\"" . $Request["AuthUser"] . "\">");

		print("<table>");
			$parent_id = "9002"; # inital value

			if($Request["ServerId"] != "")
				{
				foreach($folders["SyncDat"] as $folder_id => $folder_data)
					{
					if($folder_data["ServerId"] == $Request["ServerId"])
						{
						$parent_id = $folder_data["ParentId"];

						break;
						}
					}

				print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $Request["ServerId"] . "\">");
				}

			print("<tr>");
				print("<td align=\"right\">");
					print("ParentId");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select class=\"xs\" name=\"ParentId\">");
						print("<option value=\"0\">");
							print("&nbsp;");
						print("</option>");
						folders_edit($folders, $parent_id);
					print("</select>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td align=\"right\">");
					print("DisplayName");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				if($Request["ServerId"] != "")
					{
					foreach($folders["SyncDat"] as $folder_id => $folder_data)
						{
						if($folder_data["ServerId"] == $Request["ServerId"])
							{
							$display_name = $folder_data["DisplayName"];

							break;
							}
						}

					print("<td>");
						print("<input class=\"xt\" type=\"text\" name=\"DisplayName\" value=\"" . $display_name . "\">");
					print("</td>");
					}
				else
					{
					print("<td>");
						print("<input class=\"xt\" type=\"text\" name=\"DisplayName\">");
					print("</td>");
					}
			print("</tr>");
			if($Request["ServerId"] == "")
				{
				print("<tr>");
					print("<td align=\"right\">");
						print("Type");
					print("</td>");
					print("<td>");
						print(":");
					print("</td>");
					print("<td>");
						print("<select class=\"xs\" name=\"Type\">");
							foreach(array(12 => "Email", 13 => "Calendar", 14 => "Contacts", 15 => "Tasks", 17 => "Notes", 1 => "Folder") as $type => $class)
								{
								print("<option value=\"" . $type . "\">");
									print($class);
								print("</option>"); # do not show types of disabled services
								}
						print("</select>");
					print("</td>");
				print("</tr>");
				}
		print("</table>");
	print("</form>");

	print("<p>");
		print("[");
			print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'FolderSave' });\">");
				print("Speichern");
			print("</span>");
		print("]");
		print(" ");
		print("[");
			print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Folder' });\">");
				print("Zurück");
			print("</span>");
		print("]");
	print("</p>");
	}

if($Request["Cmd"] == "FolderUpdate")
	{
	$status = active_sync_folder_update($Request["AuthUser"], $Request["ServerId"], $Request["ParentId"], $Request["DisplayName"]);

	print($status);
	}
?>
