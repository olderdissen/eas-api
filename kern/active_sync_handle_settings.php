<?
function active_sync_handle_settings($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Settings");

	$response->x_open("Settings");

		if(isset($xml->Oof) === true)
			{
			$status = 2; # Protocol error.

			if(isset($xml->Oof->Get) === true)
				$status = 1; # Success.

			if(isset($xml->Oof->Set) === true)
				$status = 1; # Success.

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($status == 1)
				{
				$status = 2; # Protocol error.

				$response->x_open("Oof");

					$response->x_open("Status");
						$response->x_print(1); # Success.
					$response->x_close("Status");

					if(isset($xml->Oof->Get) === true)
						{
						$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

						$body_type = strval($xml->Oof->Get->BodyType);

						$response->x_open("Get");

							if(isset($settings["OOF"]) === true)
								{
								foreach(array("OofState", "StartTime", "EndTime") as $token)
									{
									if(isset($settings["OOF"][$token]) === false)
										continue;

									$response->x_open($token);
										$response->x_print($settings["OOF"][$token]);
									$response->x_close($token);
									}
								}

							if(isset($settings["OOF"]["OofMessage"]) === true)
								{
								foreach($settings["OOF"]["OofMessage"] as $oof_message)
									{
									$response->x_open("OofMessage");

										foreach($oof_message as $token => $value)
											{
											if(strlen($value) == 0)
												{
												$response->x_open($token, false);

												continue;
												}

											$response->x_open($token);
												$response->x_print($value);
											$response->x_close($token);
											}

									$response->x_close("OofMessage");
									}
								}

						$response->x_close("Get");
						}

					if(isset($xml->Oof->Set) === true)
						{
						$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

						$settings["OOF"] = array();

						foreach(array("OofState", "StartTime", "EndTime") as $token)
							{
							if(isset($xml->Oof->Set->$token) === false)
								continue;

							$settings["OOF"][$token] = strval($xml->Oof->Set->$token);
							}

						if(isset($xml->Oof->Set->OofMessage) === true)
							{
							$settings["OOF"]["OofMessage"] = array();

							foreach($xml->Oof->Set->OofMessage as $oof_message)
								{
								$data = array();

								foreach(array("AppliesToInternal", "AppliesToExternalKnown", "AppliesToExternalUnknown", "Enabled", "ReplyMessage", "BodyType") as $token)
									{
									if(isset($oof_message->$token) === false)
										continue;

									$data[$token] = strval($oof_message->$token);
									}

								$settings["OOF"]["OofMessage"][] = $data;
								}
							}

						active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync", $settings);
						}

				$response->x_close("Oof");
				}
			}

		if(isset($xml->DevicePassword) === true)
			{
			if(isset($xml->DevicePassword->Set) === true)
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($status == 1)
				{
				if(isset($xml->DevicePassword->Set->Password) === false)
					$status = 2; # Protocol error.
				elseif(strlen(strval($xml->DevicePassword->Set->Password)) == 0)
					$status = 2; # Protocol error.
				else
					$status = 1; # Success.

				if($status == 1)
					{
					$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

					$settings["DevicePassword"] = strval($xml->DevicePassword->Set->Password);

					active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings);
					}

				$response->x_open("DevicePassword");
					$response->x_open("Status");
						$response->x_print($status);
					$response->x_close("Status");
				$response->x_close("DevicePassword");
				}
			}

		if(isset($xml->DeviceInformation) === true)
			{
			if(isset($xml->DeviceInformation->Set) === true)
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($status == 1)
				{
				$status = 2; # Protocol error.

				$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

				foreach(active_sync_get_default_info() as $token => $value)
					{
					if(isset($xml->DeviceInformation->Set->$token) === false)
						continue;

					$settings["DeviceInformation"][$token] = strval($xml->DeviceInformation->Set->$token);

					$status = 1; # Success.
					}

				if($status == 1)
					{
					active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings);
					}

				$response->x_open("DeviceInformation");
					$response->x_open("Status");
						$response->x_print($status);
					$response->x_close("Status");
				$response->x_close("DeviceInformation");
				}
			}

		if(isset($xml->UserInformation) === true)
			{
			if(isset($xml->UserInformation->Get) === true)
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($status == 1)
				{
				$status = 2; # Protocol error.

				$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

				$response->x_open("UserInformation");

					$response->x_open("Status");
						$response->x_print(1); # Success.
					$response->x_close("Status");

					$response->x_open("Get");
						$response->x_open("EmailAddresses");

							foreach(array("SmtpAddress" => "SmtpAddress") as $token => $value)
								{
								$response->x_open($token);
									$response->x_print($value);
								$response->x_close($token);
								}

						$response->x_close("EmailAddresses");
					$response->x_close("Get");

				$response->x_close("UserInformation");
				}
			}

		if(isset($xml->RightsManagementInformation) === true)
			{
			if(isset($xml->RightsManagementInformation->Get) === true)
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($status == 1)
				{
				$settings = active_sync_get_settings(DAT_DIR . "/login.data");

				if(isset($settings["RightsManagementTemplates"]) === false)
					$status = 168;
				else
					$status = 1; # Protocol error.

				$response->x_open("RightsManagementInformation");

					$response->x_open("Status");
						$response->x_print($status);
					$response->x_close("Status");

					$response->x_open("Get");

						$response->x_switch("RightsManagement");

						if($status == 1)
							{
							$response->x_open("RightsManagementTemplates");

								foreach($settings["RightsManagementTemplates"] as $template)
									{
									$response->x_open("RightsManagementTemplate");

										foreach(array("TemplateID", "TemplateName", "TemplateDescription") as $token)
											{
											$response->x_open($token);
												$response->x_print($template[$token]);
											$response->x_close($token);
											}

									$response->x_close("RightsManagementTemplate");
									}

             						$response->x_close("RightsManagementTemplates");
							}
						else
							{
							$response->x_open("RightsManagementTemplates", false);
							}

					$response->x_close("Get");

				$response->x_close("RightsManagementInformation");
				}
			}

	$response->x_close("Settings");

	return($response->response);
	}
?>
