<?
function active_sync_handle_search($request)
	{
	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Search");

	$response->x_open("Search");

		if(isset($xml->Store) === false)
			$status = 3; # Server error.
		elseif(isset($xml->Store->Name) === false)
			$status = 3; # Server error.
		elseif(isset($xml->Store->Query) === false)
			$status = 3; # Server error.
		else
			$status = 1; # Server error.

		$response->x_open("Status");
			$response->x_print($status);
		$response->x_close("Status");

		$response->x_open("Response");
			$response->x_open("Store");

				if(isset($xml->Store) === false)
					$status = 3; # Server error.
				elseif(isset($xml->Store->Name) === false)
					$status = 3; # Server error.
				elseif(strval($xml->Store->Name) == "GAL")
					$status = 1; # Ok.
				elseif(strval($xml->Store->Name) == "Mailbox")
					$status = 1; # Ok.
				elseif(strval($xml->Store->Name) == "Document Library")
					$status = 3; # Server error.
				else
					$status = 1; # Server error.

				$response->x_open("Status");
					$response->x_print($status);
				$response->x_close("Status");

				if($status != 1)
					{
					}
				elseif(strval($xml->Store->Name) == "GAL")
					{
					$query = strval($xml->Store->Query);

					$retval = array();

					$settings = active_sync_get_settings(DAT_DIR . "/login.data");

					foreach($settings["login"] as $login_data)
						{
						if($login_data["User"] == $request["AuthUser"])
							continue;

						foreach(glob(DAT_DIR . "/" . $login_data["User"] . "/9009/*.data") as $file)
							{
							$server_id = basename($file, ".data");

							$data = active_sync_get_settings_data($login_data["User"], "9009", $server_id);

							$data["Contacts"]["FileAs"] = active_sync_create_fullname_from_data($data);

							foreach(array("Email1Address", "Email2Address", "Email3Address") as $token)
								{
								if(isset($data["Contacts"][$token]) === false)
									continue;

								if(strlen($data["Contacts"][$token]) == 0)
									continue;

								list($name, $mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

								$data["Contacts"][$token] = $mail;
								}

							foreach(array("Email1Address", "Email2Address", "Email3Address", "FirstName", "LastName", "MiddleName") as $token)
								{
								if(isset($data["Contacts"][$token]) === false)
									continue;

								if(strtolower(substr($data["Contacts"][$token], 0, strlen($query))) != strtolower($query))
									continue;

								$retval[] = $data["Contacts"];

								break;
								}
							}
						}

					usort($retval, function($a, $b){return($a["FileAs"] - $b["FileAs"]);});

					if(isset($xml->Store->Options->Range) === false)
						$range = "0-99"; # default is written to 100 results somewhere ... really
					else
						$range = strval($xml->Store->Options->Range);

					list($m, $n) = explode("-", $range);

					$p = 0;

					foreach($retval as $data)
						{
						if($m > $n)
							break;

						$m = $m + 1;

						$response->x_switch("Search");

						$response->x_open("Result");

							$response->x_open("Properties");

								$response->x_switch("GAL");

								foreach(array("DisplayName" => "FileAs", "Title" => "Title", "Company" => "CompanyName", "Alias" => "Alias", "FirstName" => "FirstName", "LastName" => "LastName", "MobilePhone" => "MobilePhoneNumber", "EmailAddress" => "Email1Address") as $token_gal => $token_contact)
									{
									if(isset($data[$token_contact]) === false)
										continue;

									if(strlen($data[$token_contact]) == 0)
										continue;

									$response->x_open($token_gal);
										$response->x_print($data[$token_contact]);
									$response->x_close($token_gal);
									}

								if(isset($data["Picture"]) === false)
									$status = 173;
								elseif(strlen($data["Picture"]) == 0)
									$status = 173;
								elseif(isset($xml->Store->Options->Picture->MaxSize) === false)
									$status = 1;
								elseif(intval($xml->Store->Options->Picture->MaxSize) < strlen($data["Picture"]))
									$status = 174;
								elseif(isset($xml->Store->Options->Picture->MaxPicture) === false)
									$status = 1;
								elseif(intval($xml->Store->Options->Picture->MaxPicture) < $p)
									$status = 175;
								else
									$status = 1;

								$response->x_open("Picture");

									$response->x_open("Status");
										$response->x_print($status);
									$response->x_close("Status");

									if($status == 1)
										{
										$response->x_open("Data");
											$response->x_print($data["Picture"]);
										$response->x_close("Data");

										$p = $p + 1;
										}

								$response->x_close("Picture");

								$response->x_switch("Search");

							$response->x_close("Properties");
						$response->x_close("Result");
						}

					$response->x_switch("Search");

					foreach(array("Range" => $range, "Total" => $m) as $token => $value)
						{
						$response->x_open($token);
							$response->x_print($value);
						$response->x_close($token);
						}
					}
				elseif(strval($xml->Store->Name) == "Mailbox")
					{
					################################################################################
					# init ...
					################################################################################

					$class		= strval($xml->Store->Query->And->Class);
					$collection_id	= strval($xml->Store->Query->And->CollectionId);
					$free_text	= strval($xml->Store->Query->And->FreeText);

					# are GreatherThan->DateReceived, LessThan->DateReceived, FreeText optional?

					$retval = array();

					foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/*.data") as $file)
						{
						if(isset($xml->Store->Query->And->GreaterThan) === false)
							continue;

						if(isset($xml->Store->Query->And->GreaterThan->DateReceived) === false) # empty but existing value
							continue;

						if(isset($xml->Store->Query->And->GreaterThan->Value) === false)
							continue;

						if(isset($xml->Store->Query->And->LessThan) === false)
							continue;

						if(isset($xml->Store->Query->And->LessThan->DateReceived) === false) # empty but existing value
							continue;

						if(isset($xml->Store->Query->And->LessThan->Value) === false)
							continue;

						$server_id = basename($file, ".data");

						$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

						if(isset($data["AirSync"]["Class"]) === false)
							{
							}
						elseif($data["AirSync"]["Class"] != $class)
							continue;

						if(strtotime($data["Email"]["DateReceived"]) < strtotime(strval($xml->Store->Query->And->GreaterThan->Value)))
							continue;

						if(strtotime($data["Email"]["DateReceived"]) > strtotime(strval($xml->Store->Query->And->LessThan->Value)))
							continue;

						if(strpos(strtolower($data["Body"][4]["Data"]), strtolower($free_text)) === false) # check mime ...
							continue;

						$retval[] = $data;
						}

					if(isset($xml->Store->Options->Range) === false)
						$range = "0-99";
					else
						$range = strval($xml->Store->Options->Range);

					list($m, $n) = explode("-", $range);

					foreach($retval as $retval_data)
						{
						if($m > $n)
							break;

						$m = $m + 1;

						$response->x_switch("Search");

						$response->x_open("Result");

							$response->x_switch("AirSync");

							foreach(array("Class" => $class, "CollectionId" => $collection_id) as $token => $value)
								{
								$response->x_open($token);
									$response->x_print($value);
								$response->x_close($token);
								}

							$response->x_switch("Search");

							$response->x_open("Properties");

								if(isset($retval_data["Email"]) === true)
									{
									$response->x_switch("Email");

									foreach($retval_data["Email"] as $token => $value)
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
									}

								if(isset($retval_data["Body"][4]) === true)
									{
									$response->x_switch("AirSyncBase");

									$response->x_open("Body");

										foreach($retval_data["Body"][4] as $token => $value)
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

									$response->x_close("Body");
									}

								$response->x_switch("Search");

							$response->x_close("Properties");
						$response->x_close("Result");
						}

					$response->x_switch("Search");

					foreach(array("Range" => $range, "Total" => $m) as $token => $value)
						{
						$response->x_open($token);
							$response->x_print($value);
						$response->x_close($token);
						}
					}

			$response->x_close("Store");
		$response->x_close("Response");
	$response->x_close("Search");

	return($response->response);
	}
?>
