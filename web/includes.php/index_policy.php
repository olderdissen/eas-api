<?
if($Request["Cmd"] == "Policy")
	{
	$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/login.data");

	foreach(active_sync_get_default_policy() as $token => $value)
		$settings["Policy"]["Data"][$token] = (isset($settings["Policy"]["Data"][$token]) ? $settings["Policy"]["Data"][$token] : $value);

	$restrictions = active_sync_get_table_policy();

	print("<form>");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"PolicySave\">");
		print("<table>");
			print("<tr>");
				print("<td>");
					print("<select id=\"policy_selection\" size=\"2\" style=\"height: 300px; width: 350px;\" onchange=\"handle_link({ cmd : 'TogglePolicy' });\">");
					print("</select>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("<span id=\"policy_options\">");
						foreach($restrictions as $restriction)
							{
							$name = $restriction["Name"];

							print("<span style=\"display: none;\" id=\"" . $name . "\">");
#								print("<p>" . $name . "</p>");

								switch($restriction["Type"])
									{
									case("C"):
										$checked = ($settings["Policy"]["Data"][$name] == 1 ? " checked" : "");

										print("<input type=\"checkbox\" name=\"" . $name . "\" value=\"1\"" . $checked . ">");

										break;
									case("L"):
										print("<textarea name=\"" . $name . "\" size=\"2\" style=\"height: 100px; width: 350px;\">");
										print($settings["Policy"]["Data"][$name]);
										print("</textarea>");
										print($restriction["Label"]);

										break;
									case("R"):
										foreach($restriction["Values"] as $value)
											{
											$checked = ($settings["Policy"]["Data"][$name] == $value ? " checked" : "");

											print("<input onchange=\"handle_link({ cmd : 'PolicyInit' });\" type=\"radio\" name=\"" . $name . "\" value=\"" . $value . "\"" . $checked . ">");
											print(" ");
											print($value);
											}

										break;
									case("S"):
										print("<select name=\"" . $name . "\" onchange=\"handle_link({ cmd : 'PolicyInit' });\" style=\"width: 350px;\">");
											$selected = (strval($settings["Policy"]["Data"][$name]) == "" ? " selected" : "");

											print("<option value=\"\"" . $selected . ">");
											print("</option>");

											foreach($restriction["Values"] as $value => $description)
												{
												$selected = (strval($settings["Policy"]["Data"][$name]) == strval($value) ? " selected" : "");

												print("<option value=\"" . $value . "\"" . $selected . ">");
													print("(" . $value . ") " . $description);
												print("</option>");
												}
										print("</select>");

										break;
									case("T"):
										print("<input " . (isset($restriction["Min"]) && isset($restriction["Max"]) ? "onkeypress=\"return numbersonly(this, event, " . $restriction["Min"] . ", " . $restriction["Max"] . ");\"" : "") . " type=\"text\" name=\"" . $name . "\" style=\"text-align: right;\" size=\"" . $restriction["Length"] . "\" maxlength=\"" . $restriction["Length"] . "\" value=\"" . (isset($settings["Policy"]["Data"][$name]) ? $settings["Policy"]["Data"][$name] : $defaults[$name]) . "\">");
										print(" ");
										print($restriction["Label"]);
										print(" ");
										print(isset($restriction["Min"]) && isset($restriction["Max"]) ? " (" . $restriction["Min"] . " ... " . $restriction["Max"] . ")" : "");

										break;
									}
							print("</span>");
							}
					print("</span>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>&nbsp;</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'PolicySave' });\">");
							print("Speichern");
						print("</span>");
					print("]");
					print(" ");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'ResetForm' });\">");
							print("Zurücksetzen");
						print("</span>");
					print("]");
					if(isset($settings["Policy"]["Data"]) === true)
						{
						print(" ");
						print("[");
							print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'PolicyDelete' });\">");
								print("Zurücksetzen");
							print("</span>");
						print("]");
						}
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}

if($Request["Cmd"] == "PolicyDelete")
	{
	$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/login.data");

	$settings["Policy"] = array
		(
		"PolicyKey" => time()
		);

	active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/login.data", $settings);

	print(1);
	}

if($Request["Cmd"] == "PolicySave")
	{
	$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/login.data");

	$settings["Policy"] = array
		(
		"PolicyKey" => time()
		);

	foreach(active_sync_get_default_policy() as $restriction_key => $restriction_data)
		{
		if(isset($_POST[$restriction_key]) === false)
			continue;

		if(strlen($_POST[$restriction_key]) == 0)
			continue;

		$settings["Policy"]["Data"][$restriction_key] = $_POST[$restriction_key];
		}

	# Ignored if the value of the DevicePasswordEnabled element is FALSE (0).
	if(isset($settings["Policy"]["Data"]["DevicePasswordEnabled"]) === false)
		foreach(active_sync_get_default_policy_password() as $key)
			unset($settings["Policy"]["Data"][$key]);

	active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/login.data", $settings);

	print(1);
	}

function active_sync_get_default_policy_password()
	{
	$table = array
		(
		"AllowSimpleDevicePassword",
		"AlphanumericDevicePasswordRequired",
		"DevicePasswordExpiration",
		"DevicePasswordHistory",
		"MinDevicePasswordComplexCharacters",
		"MaxDevicePasswordFailedAttempts",
		"MinDevicePasswordLength",
		"PasswordRecoveryEnabled"
		);

	return($table);
	}
?>
