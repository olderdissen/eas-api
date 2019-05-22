<?
if($Request["Cmd"] == "Oof")
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

	################################################################################

	foreach(array("StartTime" => 0, "EndTime" => 86400) as $token => $value)
		$settings["OOF"][$token] = (isset($settings["OOF"][$token]) === false ? date("d.m.Y H:00", date("U") + $value) : date("d.m.Y H:i", strtotime($settings["OOF"][$token])));

	foreach(array("OofState" => 0) as $token => $value)
		$settings["OOF"][$token] = (isset($settings["OOF"][$token]) === false ? $value : $settings["OOF"][$token]);

	$settings["OOF"]["AppliesToExternalKnown"]["Enabled"] = 0;
	$settings["OOF"]["AppliesToExternalKnown"]["ReplyMessage"] = "";

	$settings["OOF"]["AppliesToInternal"]["Enabled"] = 0;
	$settings["OOF"]["AppliesToInternal"]["ReplyMessage"] = "";

	################################################################################

	# OofState
	# 0 disabled
	# 1 global
	# 2 time-based

	print("<form>");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"OofSave\">");
		print("<p>");
			print("<input type=\"checkbox\" name=\"F1\" value=\"1\"" . ($settings["OOF"]["OofState"] == 0 ? "" : " checked") . " onchange=\"handle_link({ cmd : 'ToggleSettings' });\">");
			print(" ");
			print("Automatische Abwesenheitsnotiz senden");
		print("</p>");
		print("<div id=\"a\" style=\"display: none; padding-left: 32px;\">");
			print("<p>");
				print("<input type=\"checkbox\" name=\"F2\" value=\"1\" " . ($settings["OOF"]["OofState"] == 2 ? " checked" : "") . " onchange=\"handle_link({ cmd : 'ToggleTimes' });\">");
				print(" ");
				print("Während dieser Zeit automatische Abwesenheitsnotiz versenden");
				print(":");
			print("</p>");
			print("<div id=\"times\" style=\"padding-left: 32px;\">");
				print("<table cellspacing=\"0\">");
					print("<tr>");
						print("<td align=\"right\">");
							print("Startzeit");
						print("</td>");
						print("<td>");
							print(":");
						print("</td>");
						print("<td>");
							print("<input type=\"text\" name=\"StartTime\" value=\"" . $settings["OOF"]["StartTime"] . "\" maxlength=\"16\" onclick=\"popup_date({ target : this, cmd : 'init', time : true });\" class=\"xi\" style=\"width: 128px;\">");
						print("</td>");
					print("</tr>");
					print("<tr>");
						print("<td align=\"right\">");
							print("Endzeit");
						print("</td>");
						print("<td>");
							print(":");
						print("</td>");
						print("<td>");
							print("<input type=\"text\" name=\"EndTime\" value=\"" . $settings["OOF"]["EndTime"] . "\" maxlength=\"16\" onclick=\"popup_date({ target : this, cmd : 'init', time : true });\" class=\"xi\" style=\"width: 128px;\">");
						print("</td>");
					print("</tr>");
				print("</table>");
			print("</div>");
			print("<p>");
				print("Einstellung für interne Nachrichten:");
			print("</p>");
			print("<div style=\"padding-left: 32px;\">");
				print("<p>");
					print("<textarea name=\"F5\" class=\"xt\">");
						print($settings["OOF"]["AppliesToInternal"]["ReplyMessage"]);
					print("</textarea>");
				print("</p>");
			print("</div>");
			print("<p>");
				print("<input type=\"checkbox\" name=\"F6\" value=\"1\"" . ($settings["OOF"]["AppliesToExternalKnown"]["Enabled"] == 1 ? " checked" : "") . " onchange=\"handle_link({ cmd : 'ToggleExternal' });\">");
				print(" ");
				print("Automatische Abwesenheitsnotiz an externe Absender senden");
			print("</p>");
			print("<div id=\"external\" style=\"display: none; padding-left: 32px;\">");
				print("<p>");
					print("Einstellung für externe Nachrichten:");
				print("</p>");
				print("<div style=\"padding-left: 32px;\">");
					print("<table cellspacing=\"0\">");
						print("<tr>");
							print("<td>");
								print("<input type=\"radio\" name=\"F7\" value=\"0\"" . ($settings["OOF"]["AppliesToExternalKnown"]["Enabled"] == 0 ? "" : " checked") . ">");
							print("</td>");
							print("<td>");
								print("Automatische Abwesenheitsnotiz an alle ausserhalb des Unternehmens senden");
							print("</td>");
						print("</tr>");
						print("<tr>");
							print("<td>");
								print("<input type=\"radio\" name=\"F7\" value=\"1\"" . ($settings["OOF"]["AppliesToExternalKnown"]["Enabled"] == 0 ? " checked" : "") . ">");
							print("</td>");
							print("<td>");
								print("Automatische Abwesenheitsnotiz an Absender in meiner Kontaktliste senden");
							print("</td>");
						print("</tr>");
					print("</table>");
					print("<p>");
						print("<textarea name=\"F8\" class=\"xt\">");
							print($settings["OOF"]["AppliesToExternalKnown"]["ReplyMessage"]);
						print("</textarea>");
					print("</p>");
				print("</div>");
			print("</div>");
		print("</div>");
	print("</form>");

	print("<p>");
		print("[");
			print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'OofSave' });\">");
				print("Einstellen");
			print("</span>");
		print("]");
#		print(" ");
#		print("[");
#			print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'ResetForm' });\">");
#				print("Zurücksetzen"");
#			print("</span>");
#		print("]");
	print("</p>");
	}

if($Request["Cmd"] == "OofSave")
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

	foreach(array("F1", "F2", "StartTime", "EndTime", "F5", "F6", "F7", "F8") as $field)
		$retval[$field] = (isset($_POST[$field])  === false ? "" : $_POST[$field]);

	$data["OofState"] = 0;

	if($data["OofState"] != 0)
		{
		}
	elseif($retval["F1"] != 1)
		{
		}
	elseif($retval["F5"] != "")
		{
		$data["OofState"] = 1;

		$data["OofMessage"][0]["AppliesToInternal"]		= "";
		$data["OofMessage"][0]["Enabled"]			= 1;
		$data["OofMessage"][0]["ReplyMessage"]			= $retval["F5"];
		$data["OofMessage"][0]["BodyType"]			= "text";

		$data["OofMessage"][1]["AppliesToExternalKnown"]	= "";
		$data["OofMessage"][1]["Enabled"]			= 0;

		$data["OofMessage"][2]["AppliesToExternalUnknown"]	= "";
		$data["OofMessage"][2]["Enabled"]			= 0;
		}

	if($data["OofState"] != 1)
		{
		}
	elseif($retval["F6"] != 1)
		{
		}
	elseif($retval["F8"] == "")
		{
		}
	elseif($retval["F7"] == 0)
		{
		$data["OofMessage"][1]["AppliesToExternalKnown"]	= "";
		$data["OofMessage"][1]["Enabled"]			= 1;
		$data["OofMessage"][1]["ReplyMessage"]			= $retval["F8"];
		$data["OofMessage"][1]["BodyType"]			= "text";

		$data["OofMessage"][2]["AppliesToExternalUnknown"]	= "";
		$data["OofMessage"][2]["Enabled"]			= 0;
		$data["OofMessage"][2]["ReplyMessage"]			= $retval["F8"];
		$data["OofMessage"][2]["BodyType"]			= "text";
		}
	elseif($retval["F7"] == 1)
		{
		$data["OofMessage"][1]["AppliesToExternalKnown"]	= "";
		$data["OofMessage"][1]["Enabled"]			= 1;
		$data["OofMessage"][1]["ReplyMessage"]			= $retval["F8"];
		$data["OofMessage"][1]["BodyType"]			= "text";

		$data["OofMessage"][2]["AppliesToExternalUnknown"]	= "";
		$data["OofMessage"][2]["Enabled"]			= 1;
		$data["OofMessage"][2]["ReplyMessage"]			= $retval["F8"];
		$data["OofMessage"][2]["BodyType"]			= "text";
		}

	if($data["OofState"] == 0)
		{
		}
	elseif($retval["F2"] != 1)
		{
		}
	elseif($retval["StartTime"] == "")
		{
		}
	elseif($retval["EndTime"] == "")
		{
		}
	else
		{
		$a = strtotime($retval["StartTime"]);
		$b = strtotime($retval["EndTime"]);

		$data["OofState"]					= 2;
		$data["StartTime"]					= date("Y-m-d\TH:i:s.000\Z", $a - date("Z", $a));
		$data["EndTime"]					= date("Y-m-d\TH:i:s.000\Z", $b - date("Z", $b));
		}

	$settings["OOF"] = $data;

	active_sync_put_settings(DAT_DIR . "/" . $Request["AuthUser"] . ".sync", $settings);

	print(1);
	}
?>
