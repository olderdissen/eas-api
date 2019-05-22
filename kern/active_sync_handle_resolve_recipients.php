<?
function active_sync_handle_resolve_recipients($request)
	{
	$host = active_sync_get_domain(); # needed for user@host

	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$recipients = array();

	if(isset($xml->To) === false)
		$status = 5;
	elseif(count($xml->To) > 100)
		$status = 5;
	else
		{
		$users = active_sync_get_settings(DAT_DIR . "/login.data");

		foreach($xml->To as $to)
			{
			$to = strval($to);

			foreach($users["login"] as $user)
				{
				foreach(glob(DAT_DIR . "/" . $user["User"] . "/9009/*.data") as $file) # contact
					{
					$server_id = basename($file, ".data");

					$data = active_sync_get_settings_data($user["User"], "9009", $server_id);

					foreach(array("Email1Address", "Email2Address", "Email3Address") as $token)
						{
						if(isset($data["Contacts"][$token]) === false)
							continue;

						if(strlen($data["Contacts"][$token]) == 0)
							continue;

						list($t_name, $t_mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

						if($t_mail != $to)
							continue;

						if($user["User"] != $request["AuthUser"])
							$recipients[$to][] = array("Type" => 1, "DisplayName" => $t_name, "EmailAddress" => $t_mail);

						if($user["User"] == $request["AuthUser"])
							$recipients[$to][] = array("Type" => 2, "DisplayName" => $t_name, "EmailAddress" => $t_mail);

						break(2); # foreach, while
						}
					}
				}
			}
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("ResolveRecipients");

	$response->x_open("ResolveRecipients");
		$response->x_open("Status");
			$response->x_print(1);
		$response->x_close("Status");

		foreach($xml->To as $to)
			{
			$to = strval($to);

			$response->x_open("Response");

				foreach(array("To" => $to, "Status" => (count($recipients[$to]) > 1 ? 2 : 1), "RecipientCount" => count($recipients[$to])) as $token => $value)
					{
					$response->x_open($token);
						$response->x_print($value);
					$response->x_close($token);
					}

				foreach($recipients[$to] as $id => $recipient)
					{
					$response->x_open("Recipient");

						foreach(array("Type", "DisplayName", "EmailAddress") as $field)
							{
							$response->x_open($field);
								$response->x_print($recipient[$field]);
							$response->x_close($field);
							}

						if(isset($xml->Options->Availability) === false)
							{
							}
						elseif(isset($xml->Options->Availability->StartTime) === false)
							{
							}
						elseif(isset($xml->Options->Availability->EndTime) === false)
							{
							}
						elseif(((strtotime($xml->Options->Availability->EndTime) - strtotime($xml->Options->Availability->StartTime)) / 1800) > 32768)
							{
							$response->x_open("Availability");
								$response->x_open("Status");
									$response->x_print(162);
								$response->x_close("Status");
							$response->x_close("Availability");
							}
						else
							{
							$start_time = strtotime($xml->Options->Availability->StartTime);
							$end_time = strtotime($xml->Options->Availability->EndTime);

							$merged_free_busy = array_fill(0, ($end_time - $start_time) / 1800, 4); # 4 = no data

							list($t_name, $t_mail) = active_sync_mail_parse_address($recipient["EmailAddress"]);
							list($t_user, $t_host) = explode("@", $t_mail);

							if($t_host == $host)
								{
								foreach(glob(DAT_DIR . "/" . $t_user . "/9008/*.data") as $file)
									{
									$server_id = basename($file, ".data");

									$data = active_sync_get_settings_data($t_user, "9008", $server_id);

									if(strtotime($data["Calendar"]["StartTime"]) > $end_time)
										continue;

									if(strtotime($data["Calendar"]["EndTime"]) < $start_time)
										continue;

									foreach(array("EndTime" => 0, "StartTime" => 0, "BusyStatus" => 0) as $token => $value)
										$data["Calendar"][$token] = (isset($data["Calendar"][$token]) === false ? $value : $data["Calendar"][$token]);

									foreach(array("EndTime" => 0, "StartTime" => 0) as $token => $value)
										$data["Calendar"][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data["Calendar"][$token]));

									for($x = $start_time; $x < $end_time; $x = $x + 1800)
										{
										if($x < strtotime($data["Calendar"]["StartTime"]))
											continue;

										if($x + 1800 > strtotime($data["Calendar"]["EndTime"]))
											continue;

										$merged_free_busy[($x - $start_time) / 1800] = $data["Calendar"]["BusyStatus"];
										}
									}
								}

							$response->x_open("Availability");

								foreach(array("Status" => 1, "MergedFreeBusy" => implode("", $merged_free_busy)) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

							$response->x_close("Availability");
							}

						if(isset($xml->Options->CertificateRetrieval) === false)
							{
							}
						elseif(intval($xml->Options->CertificateRetrieval) == 1) # Do not retrieve certificates for the recipient (default).
							{
							}
						elseif(file_exists(CRT_DIR . "/certs/" . $recipient["EmailAddress"] . ".pem") === false)
							{
							$response->x_open("Certificates");

								foreach(array("Status" => 7, "CertificateCount" => 0) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

							$response->x_close("Certificates");
							}
						elseif(intval($xml->Options->CertificateRetrieval) == 2) # Retrieve the full certificate for each resolved recipient.
							{
							$certificate = file_get_contents(CRT_DIR . "/certs/" . $recipient["EmailAddress"] . ".pem");

							list($null, $certificate) = explode("-----BEGIN CERTIFICATE-----", $certificate, 2);
							list($certificate, $null) = explode("-----END CERTIFICATE-----", $certificate, 2);

							$certificate = str_replace(array("\r", "\n"), "", $certificate);

							$response->x_open("Certificates");

								foreach(array("Status" => 1, "CertificateCount" => 1) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

								$response->x_open("Certificate");
									$response->x_print($certificate); # ... contains the X509 certificate ... encoded with base64 ...
								$response->x_close("Certificate");
							$response->x_close("Certificates");
							}
						elseif(intval($xml->Options->CertificateRetrieval) == 3) # Retrieve the mini certificate for each resolved recipient.
							{
							$certificate = file_get_contents(CRT_DIR . "/certs/" . $recipient["EmailAddress"] . ".pem");

							list($null, $certificate) = explode("-----BEGIN CERTIFICATE-----", $certificate, 2);
							list($certificate, $null) = explode("-----END CERTIFICATE-----", $certificate, 2);

							$certificate = str_replace(array("\r", "\n"), "", $certificate);

							$response->x_open("Certificates");

								foreach(array("Status" => 1, "CertificateCount" => 1) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

								$response->x_open("MiniCertificate");
									$response->x_print($certificate); # ... contains the mini-certificate ... encoded with base64 ...
								$response->x_close("MiniCertificate");
							$response->x_close("Certificates");
							}
						else
							{
							}

						if(isset($xml->Options->Picture) === false)
							{
							}
						elseif(isset($xml->Options->Picture->MaxPictures) === false)
							{
							}
						elseif(isset($xml->Options->Picture->MaxSize) === false)
							{
							}
						else
							{
#							$response->x_open("Picture");
#								$response->x_open("Status");
#									$response->x_print(1);
#								$response->x_close("Status");
#
#								$response->x_open("Data");
#									$response->x_print();
#								$response->x_close("Data");
#							$response->x_close("Picture");
							}

					$response->x_close("Recipient");
					}

			$response->x_close("Response");
			}

	$response->x_close("ResolveRecipients");

	return($response->response);
	}
?>
