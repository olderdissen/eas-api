<?
if($Request["Cmd"] == "Policy")
	{
	################################################################################
	# ...
	################################################################################

	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach(active_sync_get_default_policy() as $token => $value)
		{
		$settings["Policy"]["Data"][$token] = (isset($settings["Policy"]["Data"][$token]) === false ? $value : $settings["Policy"]["Data"][$token]);
		}

	################################################################################
	# ...
	################################################################################

	$restrictions = active_sync_get_table_policy();

	################################################################################
	# todo:
	# two lists
	# one with possible settings
	# second with settings done so far
	# if setting is done, remove it from first list

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
						foreach($restrictions as $policy_name => $policy_data)
							{
							print("<span style=\"display: none;\" id=\"" . $policy_name . "\">");
#									print("<p>" . $policy_name . "</p>");

								switch($policy_data["Type"])
									{
									case("C"):
										$checked = ($settings["Policy"]["Data"][$policy_name] == 1 ? " checked" : "");

										print("<input type=\"checkbox\" name=\"" . $policy_name . "\" value=\"1\"" . $checked . ">");

										break;
									case("L"):
										print("<textarea name=\"" . $policy_name . "\" size=\"2\" style=\"height: 100px; width: 350px;\">");
										print($settings["Policy"]["Data"][$policy_name]);
										print("</textarea>");
										print($policy_data["Label"]);

										break;
									case("R"):
										foreach($policy_data["Values"] as $value)
											{
											$checked = ($settings["Policy"]["Data"][$policy_name] == $value ? " checked" : "");

											print("<input onchange=\"handle_link({ cmd : 'PolicyInit' });\" type=\"radio\" name=\"" . $policy_name . "\" value=\"" . $value . "\"" . $checked . ">");
											print(" ");
											print($value);
											}

										break;
									case("S"):
										print("<select name=\"" . $policy_name . "\" onchange=\"handle_link({ cmd : 'PolicyInit' });\" style=\"width: 350px;\">");
											$selected = (strval($settings["Policy"]["Data"][$policy_name]) == "" ? " selected" : "");

											print("<option value=\"\"" . $selected . ">");
											print("</option>");

											foreach($policy_data["Values"] as $value => $description)
												{
												$selected = (strval($settings["Policy"]["Data"][$policy_name]) == strval($value) ? " selected" : "");

												print("<option value=\"" . $value . "\"" . $selected . ">");
													print("(" . $value . ") " . $description);
												print("</option>");
												}
										print("</select>");

										break;
									case("T"):
										print("<input " . (isset($policy_data["Min"]) && isset($policy_data["Max"]) ? "onkeypress=\"return numbersonly(this, event, " . $policy_data["Min"] . ", " . $policy_data["Max"] . ");\"" : "") . " type=\"text\" name=\"" . $policy_name . "\" style=\"text-align: right;\" size=\"" . $policy_data["Length"] . "\" maxlength=\"" . $policy_data["Length"] . "\" value=\"" . (isset($settings["Policy"]["Data"][$policy_name]) ? $settings["Policy"]["Data"][$policy_name] : $defaults[$policy_name]) . "\">");
										print(" ");
										print($policy_data["Label"]);
										print(" ");
										print(isset($policy_data["Min"]) && isset($policy_data["Max"]) ? " (" . $policy_data["Min"] . " ... " . $policy_data["Max"] . ")" : "");

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
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	$settings["Policy"] = array();

	$settings["Policy"]["PolicyKey"] = time();

	active_sync_put_settings(DAT_DIR . "/login.data", $settings);

	print(1);
	}

if($Request["Cmd"] == "PolicySave")
	{
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	$settings["Policy"] = array();

	$settings["Policy"]["PolicyKey"] = time();

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

	active_sync_put_settings(DAT_DIR . "/login.data", $settings);

	print(1);
	}

function active_sync_get_default_policy_password()
	{
	$table = array();

	$table[] = "AllowSimpleDevicePassword";
	$table[] = "AlphanumericDevicePasswordRequired";
	$table[] = "DevicePasswordExpiration";
	$table[] = "DevicePasswordHistory";
	$table[] = "MinDevicePasswordComplexCharacters";
	$table[] = "MaxDevicePasswordFailedAttempts";
	$table[] = "MinDevicePasswordLength";
	$table[] = "PasswordRecoveryEnabled";

	return($table);
	}
?>
