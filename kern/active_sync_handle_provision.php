<?
define("REMOTE_WIPE", 1);
define("ACCOUNT_ONLY_REMOTE_WIPE", 2);

function active_sync_handle_provision($request)
	{
	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Provision");

	$response->x_open("Provision");

		if(isset($xml->DeviceInformation) === true)
			{
			if(isset($xml->DeviceInformation->Set) === true)
				{
				$info = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

				foreach(active_sync_get_default_info() as $token)
					{
					if(isset($xml->DeviceInformation->Set->$token) === false)
						continue;

					$info["DeviceInformation"][$token] = strval($xml->DeviceInformation->Set->$token);
					}

				active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $info);

				$status = 1; # Success.
				}
			else
				$status = 2;

			$response->x_switch("Settings");

			$response->x_open("DeviceInformation");
				$response->x_open("Status");
					$response->x_print($status);
				$response->x_close("Status");
			$response->x_close("DeviceInformation");
			}

		if(active_sync_get_need_wipe($request) != 0)
			{
			}
		elseif(isset($xml->Policies) === true)
			{
			$device_id = $request["DeviceId"];

			$show_policy = 0;
			$show_empty = 0;
			$show_status = 1;

			active_sync_debug("PolicyKey: " . $request["PolicyKey"]);

			$settings_server = active_sync_get_settings(DAT_DIR . "/login.data");

			$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

			$settings_client["PolicyKey"] = $settings_server["Policy"]["PolicyKey"];

			active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings_client);

			if(isset($xml->Policies->Policy) === false)
				$status = 3; # Unknown PolicyType value.
			elseif(isset($xml->Policies->Policy->PolicyType) === false)
				$status = 3; # Unknown PolicyType value.
			elseif(strval($xml->Policies->Policy->PolicyType) != "MS-EAS-Provisioning-WBXML")
				$status = 3; # Unknown PolicyType value.
			elseif(isset($xml->Policies->Policy->Status) === false)
				{
				if(isset($settings_server["Policy"]["PolicyKey"]) === false)
					{
					$show_empty = 1;

					$status = 1; # There is no policy for this client.
					}
				elseif(isset($settings_server["Policy"]["Data"]) === false)
					{
					$show_empty = 1;

					$status = 1; # There is no policy for this client.
					}
				else
					{
					$show_policy = 1;

					$status = 1; # Success.
					}

				$show_status = 0;
				}
			elseif(intval($xml->Policies->Policy->Status) == 1)
				$status = 1; # Success.
			elseif(intval($xml->Policies->Policy->Status) == 2)
				$status = 1; # Success.
			elseif(isset($xml->Policies->Policy->PolicyKey) === false)
				$status = 5; # The client is acknowledging the wrong policy key.
			elseif(strval($xml->Policies->Policy->PolicyKey) != $settings_server["Policy"]["PolicyKey"])
				$status = 5; # The client is acknowledging the wrong policy key.
			else
				$status = 1; # Success.

			$response->x_switch("Provision");

			if($show_status == 1)
				{
				$response->x_open("Status");
					$response->x_print(1);
				$response->x_close("Status");
				}

			$response->x_open("Policies");
				$response->x_open("Policy");

					$response->x_open("PolicyType");
						$response->x_print("MS-EAS-Provisioning-WBXML");
					$response->x_close("PolicyType");

					$response->x_open("Status");
						$response->x_print($status);
					$response->x_close("Status");

					$response->x_open("PolicyKey");
						$response->x_print($settings_server["Policy"]["PolicyKey"]);
					$response->x_close("PolicyKey");

					if($show_policy == 1)
						{
						$response->x_open("Data");
							$response->x_open("EASProvisionDoc");

								foreach(array("ApprovedApplicationList" => "Hash", "UnapprovedInROMApplicationList" => "ApplicationName") as $k => $v)
									{
									if(isset($settings_server["Policy"]["Data"][$k]) === false)
										continue;

									$response->x_open($k);

										foreach(explode("\n", $settings_server["Policy"]["Data"][$k]) as $value)
											{
											$response->x_open($v);
												$response->x_print($value);
											$response->x_close($v);
											}

									$response->x_close($k);
									}

								foreach(active_sync_get_default_policy() as $token => $value)
									{
									if($token == "ApprovedApplicationList" || $token == "UnapprovedInROMApplicationList")
										continue;

									if(isset($settings_server["Policy"]["Data"][$token]) === false)
										continue;

									$response->x_open($token);
										$response->x_print($settings_server["Policy"]["Data"][$token]);
									$response->x_close($token);
									}

							$response->x_close("EASProvisionDoc");
						$response->x_close("Data");
						}

					if($show_empty == 1)
						{
						$response->x_open("Data");
							$response->x_open("EASProvisionDoc", false);
						$response->x_close("Data");
						}

				$response->x_close("Policy");
			$response->x_close("Policies");
			}

		if(active_sync_get_need_wipe($request) != 0)
			{
			$remote_wipe = 0;

			if(isset($xml->RemoteWipe) === false)
				$status = 1; # The client remote wipe was sucessful.
			elseif(isset($xml->RemoteWipe->Status) === false)
				{
				$remote_wipe = ACCOUNT_ONLY_REMOTE_WIPE;

				$status = 1; # The client remote wipe was sucessful.
				}
			elseif(intval($xml->RemoteWipe->Status) == 1) # The client remote wipe operation was successful.
				{
				active_sync_handle_provision_remote_wipe($request);

				$status = 1; # The client remote wipe was sucessful.
				}
			elseif(intval($xml->RemoteWipe->Status) == 2) # The remote wipe operation failed.
				$status = 1; # The client remote wipe was sucessful.
			else
				$status = 2; # Protocol error.

			$response->x_switch("Provision");

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($remote_wipe == 0)
				{
				}
			elseif($remote_wipe == REMOTE_WIPE)
				$response->x_open("RemoteWipe", false);
			elseif($remote_wipe == ACCOUNT_ONLY_REMOTE_WIPE)
				$response->x_open("AccountOnlyRemoteWipe", false);
			}

	$response->x_close("Provision");

	return($response->response);
	}

function active_sync_handle_provision_remote_wipe($request)
	{
	foreach(array("wipe") as $extension)
		{
		$file = DAT_DIR . "/" . $request["DeviceId"] . "." . $extension;

		if(file_exists($file) === false)
			continue;

		unlink($file);
		}

	return;

	$users = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($users as $user_id => $user_data)
		{
		if(is_dir(DAT_DIR . "/" . $user_data["User"]) === false)
			continue;

		$folders = active_sync_get_settings(DAT_DIR . "/" . $user_data["User"] . ".sync");

		foreach($folders as $folder_id => $folder_data)
			{
			if(is_dir(DAT_DIR . "/" . $user_data["User"] . "/" . $folder_data["ServerId"]) === true)
				continue;

			foreach(array("sync") as $extension)
				{
				$file = DAT_DIR . "/" . $user_data["User"] . "/" . $folder_data["ServerId"] . "/" . $request["DeviceId"] . "." . $extension;

				if(file_exists($file) === false)
					continue;

				unlink($file);
				}
			}

		foreach(array("info", "stat", "sync") as $extension)
			{
			$file = DAT_DIR . "/" . $user_data["User"] . "/" . $request["DeviceId"] . "." . $extension;

			if(file_exists($file) === false)
				continue;

			unlink($file);
			}
		}
	}
?>
