<?
if($Request["Cmd"] == "Device")
	{
	$devices = active_sync_get_devices_by_user($Request["AuthUser"]);

	if(count($devices) == 0)
		{
		print("<p>");
			print("Bisher wurden keine Geräte in Verbindung mit diesem Konto benutzt.");
		print("</p>");
		}
	else
		{
		print("<p>");
			print("Die nachfolgend aufgelisteten Geräte wurden bisher in Verbindung mit diesem Konto benutzt.");
		print("</p>");

		print("<table>");
			print("<tr class=\"list_row_title\">");
				print("<td class=\"list_title_text\" style=\"width: 150px;\">");
					print("DeviceID");
				print("</td>");
				print("<td class=\"list_title_text\" style=\"width: 150px;\">");
					print("FriendlyName");
				print("</td>");
				print("<td class=\"list_title_text\" style=\"width: 110px;\">");
					print("LastUsage");
				print("</td>");
				print("<td class=\"list_title_text\" style=\"width: 50px;\">");
					print("&nbsp;");
				print("</td>");
				print("<td class=\"list_title_text\" style=\"width: 50px;\">");
					print("&nbsp;");
				print("</td>");
			print("</tr>");

			$count = 0;

			foreach($devices as $DeviceId)
				{
				$data = active_sync_get_settings(DAT_DIR . "/" . $Request["AuthUser"] . "/" . $DeviceId . ".sync");

				foreach(active_sync_get_default_info() as $key => $value)
					$data["DeviceInformation"][$key] = (isset($data["DeviceInformation"][$key]) ? $data["DeviceInformation"][$key] : $value);

				print("<tr class=\"list_small " . ($count % 2 ? "list_even" : "list_odd") . "\">");
					print("<td>");
						print($DeviceId);
					print("</td>");
					print("<td>");
						print(isset($data["DeviceInformation"]["FriendlyName"]) ? $data["DeviceInformation"]["FriendlyName"] : "&nbsp;");
					print("</td>");
					print("<td>");
						print(date("Y-m-d-H-i-s", filemtime(DAT_DIR . "/" . $Request["AuthUser"]  . "/" . $DeviceId . ".sync")));
					print("</td>");
					print("<td>");
						print("[");

							if(count($data["DeviceInformation"]) == 0)
								print("Details");

							if(count($data["DeviceInformation"]) != 0)
								{
								print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'PopupDevice', device_id : '" . $DeviceId . "' });\">");
									print("Details");
								print("</a>");
								}

						print("]");
					print("</td>");
					print("<td>");
						print("[");
							print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeviceDeleteConfirm', device_id : '" . $DeviceId . "' });\">");
								print("Löschen");
							print("</a>");
						print("]");
					print("</td>");
				print("</tr>");

				$count = $count + 1;
				}

		print("</table>");
		}

	print("<p>");
		print("<small>");
			print(wordwrap("Hinweis für das Löschen: Das Löschen eines Geräts hat zur Folge, dass auf dem zu löschenden Gerät gespeicherten Daten dieses Kontos gelöscht und neu synchronisiert weren.", 128, "<br>", false));
		print("</small>");
	print("</p>");

	print("<script type=\"text/javascript\">");

		foreach($devices as $device_id)
			print("popup_device_state('" . $device_id . "', 2);");

	print("</script>");
	}

if($Request["Cmd"] == "DeviceDelete")
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

	foreach($settings["SyncDat"] as $folder_id => $folder_data)
		@ unlink(DAT_DIR . "/" . $Request["AuthUser"] . "/" . $folder_data["ServerId"] . "/" . $Request["DeviceId"] . ".sync");

	@ unlink(DAT_DIR . "/" . $Request["AuthUser"] . "/" . $Request["DeviceId"] . ".sync");

	print(1);
	}

if($Request["Cmd"] == "DeviceInfo")
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $Request["AuthUser"] . "/" . $Request["DeviceId"] . ".sync");

	foreach(active_sync_get_default_info() as $key => $value)
		$settings["DeviceInformation"][$key] = (isset($settings["DeviceInformation"][$key]) ? $settings["DeviceInformation"][$key] : $value);

	$data = json_encode($data["DeviceInformation"]);

#	header("Content-Type: application/json; charset=\"UTF-8\"");

	print($data);
	}
?>
