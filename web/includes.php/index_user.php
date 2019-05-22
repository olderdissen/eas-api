<?
if($Request["Cmd"] == "User")
	{
	$host = active_sync_get_domain();

	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	################################################################################

	print("<p style=\"font-weight: bold;\">");
		print("Benutzer");
	print("</p>");

	print("<div style=\"padding-left: 32px;\">");

		if(count($settings["login"]) > 0)
			{
			print("<table>");

				foreach($settings["login"] as $user_id => $user_data)
					{
					print("<tr>");
						print("<td>");
							print($user_data["IsAdmin"] == "T" ? "A" : "U");
						print("</td>");
						print("<td>");
							print($user_data["User"]);
						print("</td>");
						print("<td align=\"right\">");
							print("<small>");
								print(isset($user_data["DisplayName"]) ? "&quot;" . $user_data["DisplayName"] . "&quot;" : "");
							print("</small>");
						print("</td>");
						print("<td>");
							print("[");
								print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'UserEdit', user : '" . $user_data["User"] . "' });\">");
									print("Bearbeiten");
								print("</span>");
							print("]");
						print("</td>");
						print("<td>");
							print("[");

								if($user_data["User"] == $Request["AuthUser"])
									print("Löschen");

								if($user_data["User"] != $Request["AuthUser"])
									{
									print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'UserDeleteConfirm', user : '" . $user_data["User"] . "' });\">");
										print("Löschen");
									print("</span>");
									}

							print("]");
						print("</td>");
#						print("<td>");
#							print("[");
#								print("<a href=\"//" . $user_data["User"] . ":" . $user_data["Pass"] . "@olderdissen.ro/active-sync/web/index.php\">");
#									print("wechseln");
#								print("</a>");
#							print("]");
#						print("</td>");
					print("</tr>");
					}

				print("<tr>");
					print("<td colspan=\"4\">");
						print("&nbsp;");
					print("</td>");
					print("<td colspan=\"2\">");
						print("[");
							print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'UserEdit', user : '' });\">");
								print("Hinzufügen");
							print("</span>");
						print("]");
					print("</td>");
				print("</tr>");
			print("</table>");
			}
	print("</div>");

	if(isset($_GET["Status"]) === true)
		{
		$messages = array();
		$messages[10] = "Benutzer erfolgreich angelegt.";
		$messages[11] = "Benutzer konnte nicht angelegt werden.";
		$messages[12] = "Benutzer konnte nicht angelegt werden. Benutzer existiert bereits.";
		$messages[13] = "Benutzer konnte nicht angelegt werden. Maildir existiert bereits.";
		$messages[14] = "Benutzer konnte nicht angelegt werden. Vmailbox existiert bereits.";
		$messages[15] = "Benutzer konnte nicht angelegt werden. Virtual existiert bereits.";
		$messages[16] = "Benutzer konnte nicht angelegt werden. Benutzername und Kennwort fehlen.";
		$messages[17] = "Benutzer konnte nicht angelegt werden. Benutzername fehlt.";
		$messages[18] = "Benutzer konnte nicht angelegt werden. Kennwort fehlt.";
		$messages[20] = "Benutzer erfolgreich gelöscht.";
		$messages[22] = "Benutzer konnte nicht gelöscht werden. Benutzer existiert nicht.";
		$messages[23] = "Benutzer konnte nicht gelöscht werden. Maildir existiert nicht.";
		$messages[24] = "Benutzer konnte nicht gelöscht werden. Vmailbox existiert nicht.";
		$messages[27] = "Benutzer konnte nicht gelöscht werden. Benutzername fehlt.";
		$messages[30] = "Benutzer erfolgreich bearbeitet.";
		$messages[31] = "Benutzer konnte nicht bearbeitet werden.";
		$messages[32] = "Benutzer konnte nicht bearbeitet werden. Benutzer existiert nicht.";
		$messages[36] = "Benutzer konnte nicht bearbeitet werden. Benutzername und Kennwort fehlen.";
		$messages[37] = "Benutzer konnte nicht bearbeitet werden. Benutzername fehlt.";
		$messages[38] = "Benutzer konnte nicht bearbeitet werden. Kennwort fehlt.";

		$status = $_GET["Status"];

		print("<p style=\"color: rgb(192, 0, 0);\">");
			print(isset($messages[$status]) === false ? $status : $messages[$status]);
		print("</p>");
		}
	}

function active_sync_user_create($request)
	{
	if(isset($request["User"]) === false)
		return(0);

	if($request["User"] == "")
		return(0);

	if(isset($request["Pass"]) === false)
		return(0);

	if($request["Pass"] == "")
		return(0);
	}

if($Request["Cmd"] == "UserCreate")
	{
	if(($_POST["User"] != "") && ($_POST["Pass"] != ""))
		{
		$settings = active_sync_get_settings(DAT_DIR . "/login.data");

		$mark = 0;

		foreach($settings["login"] as $user_id => $user_data)
			{
			if($user_data["User"] != $_POST["User"])
				continue;

			$mark = 1;

			break;
			}

		if($mark == 1)
			print(12);
		elseif(active_sync_postfix_virtual_alias_maps_exists($_POST["User"]) === true)
			print(15);
		elseif(active_sync_postfix_virtual_mailbox_maps_exists($_POST["User"]) === true)
			print(14);
		elseif(active_sync_maildir_exists($_POST["User"]) === true)
			print(13);
		else
			{
			active_sync_maildir_create($_POST["User"]);

			active_sync_postfix_virtual_mailbox_maps_create($_POST["User"]);

			$user = array();

			foreach(active_sync_get_default_login() as $key => $value)
				$user[$key] = (isset($_POST[$key]) ? $_POST[$key] : $value);

			$settings["login"][]  = $user;

			active_sync_put_settings(DAT_DIR . "/login.data", $settings);

			active_sync_folders_init($Request["User"]);

			print(10);
			}
		}
	elseif(($_POST["User"] == "") && ($_POST["Pass"] == ""))
		print(16);
	elseif($_POST["User"] == "")
		print(17);
	elseif($_POST["Pass"] == "")
		print(18);
	else
		print(11);
	}

if($Request["Cmd"] == "UserDelete")
	{
	if($Request["User"] == "")
		print(27);
	elseif(active_sync_postfix_virtual_mailbox_maps_exists($Request["User"]) === false)
		print(24);
	elseif(active_sync_maildir_exists($Request["User"]) === false)
		print(23);
	else
		{
		$settings = active_sync_get_settings(DAT_DIR . "/login.data");

		$mark = 0;

		foreach($settings["login"] as $user_id => $user_data)
			{
			if($user_data["User"] == $Request["User"])
				{
				unset($settings["login"][$user_id]);

				active_sync_put_settings(DAT_DIR . "/login.data", $settings);

				$mark = 1;

				break;
				}
			}

		if($mark == 0)
			print(22);
		else
			{
			active_sync_folder_delete_recursive(DAT_DIR . "/" . $Request["User"]);

			foreach(array("sync") as $extension)
				{
				$file = DAT_DIR . "/" . $Request["User"] . "." . $extension;

				if(file_exists($file) === true)
					unlink($file);
				}

			active_sync_postfix_virtual_mailbox_maps_delete($Request["User"]);

			active_sync_maildir_delete($Request["User"]);

			print(20);
			}
		}
	}

if($Request["Cmd"] == "UserEdit")
	{
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	$data = active_sync_get_default_user();

	foreach($settings["login"] as $user_id => $user_data)
		{
		if($user_data["User"] != $_GET["User"])
			continue;

		$data = $user_data;
		}

	print("<p style=\"font-weight: bold;\">");
		print($_GET["User"] ? "Benutzer bearbeiten" : "Benutzer hinzufügen");
	print("</p>");

	print("<form>");
		print("<div style=\"padding-left: 32px;\">");
			print("<table>");
				print("<tr>");
					print("<td valign=\"top\">");
						print("<table>");
							print("<tr>");
								print("<td align=\"right\">");
									print("Benutzername");
								print("</td>");
								print("<td>");
									print(":");
								print("</td>");

								if($Request["User"] == "")
									{
									print("<td>");
										print("<input type=\"text\" name=\"User\" value=\"" . $data["User"] . "\" class=\"xi\">");
										print(" ");
										print("<sup>*</sup>");
									print("</td>");
									}

								if($Request["User"] != "")
									{
									print("<td>");
										print("<input type=\"text\" value=\"" . $data["User"] . "\" readonly disabled class=\"xi\">");
										print(" ");
										print("<sup>");
											print("*");
										print("</sup>");
									print("</td>");
									print("<input type=\"hidden\" name=\"User\" value=\"" . $data["User"] . "\">");
									}

							print("</tr>");
							print("<tr>");
								print("<td align=\"right\">");
									print("Kennwort");
								print("</td>");
								print("<td>");
									print(":");
								print("</td>");
								print("<td>");
									print("<input type=\"text\" name=\"Pass\" value=\"" . $data["Pass"] . "\" class=\"xi\">");
									print(" ");
									print("<sup>");
										print("*");
									print("</sup>");
								print("</td>");
							print("</tr>");
							print("<tr>");
								print("<td align=\"right\">");
									print("Administrator");
								print("</td>");
								print("<td>");
									print(":");
								print("</td>");

								if($Request["User"] == $Request["AuthUser"])
									{
									print("<td>");
										print("<input type=\"checkbox\" " . ($data["IsAdmin"] == "T" ? " checked" : "") . " readonly>");
									print("</td>");
									print("<input type=\"hidden\" name=\"IsAdmin\" value=\"" . $data["IsAdmin"] . "\">");
									}

								if($Request["User"] != $Request["AuthUser"])
									{
									print("<td>");
										print("<input type=\"checkbox\" name=\"IsAdmin\" value=\"T\"" . ($data["IsAdmin"] == "T" ? " checked" : "") . ">");
									print("</td>");
									}

							print("</tr>");

							print("<tr>");
								print("<td colspan=\"3\">");
									print("&nbsp;");
								print("</td>");
							print("</tr>");

							foreach(array("FirstName", "LastName", "DisplayName") as $key)
								{
								switch($key)
									{
									case("FirstName");
									case("LastName");
										print("<tr>");
											print("<td align=\"right\">");
												print($key);
											print("</td>");
											print("<td>");
												print(":");
											print("</td>");
											print("<td>");
												print("<input type=\"text\" name=\"" . $key . "\" value=\"" . (isset($data[$key]) ? $data[$key] : "") . "\" class=\"xi\" id=\"" . $key . "\" onfocus=\"suggest_register(this.id, '9009', 0);\" onchange=\"handle_link({ cmd : 'UpdateDisplayName' });\">");
											print("</td>");
										print("</tr>");

										break;
									case("DisplayName");
										print("<input type=\"hidden\" name=\"" . $key . "\" value=\"" . (isset($data[$key]) ? $data[$key] : "") . "\">");

										break;
									default;
										print("<tr>");
											print("<td align=\"right\">");
												print($key);
											print("</td>");
											print("<td>");
												print(":");
											print("</td>");
											print("<td>");
												print("<input type=\"text\" name=\"" . $key . "\" value=\"" . (isset($data[$key]) ? $data[$key] : "") . "\" class=\"xi\">");
											print("</td>");
										print("</tr>");

										break;
									}
								}

						print("</table>");
					print("</td>");
				print("</tr>");
			print("</table>");
		print("</div>");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"" . ($_GET["User"] ? "UserUpdate" : "UserCreate") . "\">");
	print("</form>");

	print("<p>");
		print("[");
			print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'UserSave' });\">");
				print("Speichern");
			print("</span>");
		print("]");
		print(" ");
		print("[");
			print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'User' });\">");
				print("Zurück");
			print("</span>");
		print("]");
	print("</p>");
	}

if($Request["Cmd"] == "UserUpdate")
	{
	if(($_POST["User"] != "") && ($_POST["Pass"] != ""))
		{
		$settings = active_sync_get_settings(DAT_DIR . "/login.data");

		$mark = 0;

		foreach($settings["login"] as $user_id => $user_data)
			{
			if($user_data["User"] != $_POST["User"])
				continue;

			foreach(active_sync_get_default_login() as $key => $value)
				{
				if(isset($_POST[$key]) === false)
					$settings["login"][$user_id][$key] = $value;
				else
					$settings["login"][$user_id][$key] = $_POST[$key];
				}

			active_sync_put_settings(DAT_DIR . "/login.data", $settings);

			$mark = 1;

			break;
			}

		if($mark == 0)
			print(32);
		else
			print(30);
		}
	elseif(($_POST["User"] == "") && ($_POST["Pass"] == ""))
		print(36);
	elseif($_POST["User"] == "")
		print(37);
	elseif($_POST["Pass"] == "")
		print(38);
	else
		print(31);
	}
?>
