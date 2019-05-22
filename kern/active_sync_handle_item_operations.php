<?
function active_sync_handle_item_operations($request)
	{
	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	if(isset($xml->EmptyFolderContents) === true)
		{
		$collection_id = strval($xml->EmptyFolderContents->CollectionId);

		# $xml->EmptyFolderContents->Options->DeleteSubFolders

		foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/*.data") as $file)
			{
			$server_id = basename($file, ".data");

#			unlink(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id);
			}

		$response->x_switch("ItemOperations");

		$response->x_open("ItemOperations");
			$response->x_open("Status");
				$response->x_print(1);
			$response->x_close("Status");

			$response->x_open("Response");
				$response->x_open("EmptyFolderContents");

					$response->x_switch("ItemOperations");

					$response->x_open("Status");
						$response->x_print(1);
					$response->x_close("Status");

					$response->x_switch("AirSync");

					$response->x_open("CollectionId");
						$response->x_print($collection_id);
					$response->x_close("CollectionId");

				$response->x_close("EmptyFolderContents");
			$response->x_close("Response");
		$response->x_close("ItemOperations");
		}

	if(isset($xml->Fetch) === true)
		{
		$store = strval($xml->Fetch->Store); # Mailbox

		if(isset($xml->Fetch->LongId) === true)
			{
			$long_id = strval($xml->Fetch->LongId);

			# ...
			}

		if(isset($xml->Fetch->FileReference) === true)
			{
			$file_reference = strval($xml->Fetch->FileReference);

			list($user_id, $collection_id, $server_id, $reference) = explode(":", $file_reference, 4); # user_id, collection_id, server_id, attachment_id

			$data = active_sync_get_settings_data($user_id, $collection_id, $server_id);

			$response->x_switch("ItemOperations");

			$response->x_open("ItemOperations");
				$response->x_open("Status");
					$response->x_print(1);
				$response->x_close("Status");

				$response->x_open("Response");
					$response->x_open("Fetch");

						if(isset($data["File"][$reference]) === false)
							$status = 15; # Attachment fetch provider - Attachment or attachment ID is invalid.
						else
							$status = 1;

						$response->x_switch("ItemOperations");

						$response->x_open("Status");
							$response->x_print($status);
						$response->x_close("Status");

						$response->x_switch("AirSyncBase");

						$response->x_open("FileReference");
							$response->x_print($file_reference);
						$response->x_close("FileReference");

						if($status == 1)
							{
							$response->x_switch("ItemOperations");

							$response->x_open("Properties");

								$response->x_switch("AirSyncBase");

								$response->x_open("ContentType");
									$response->x_print($data["File"][$reference]["AirSyncBase"]["ContentType"]);
								$response->x_close("ContentType");

								$response->x_switch("ItemOperations");

								$response->x_open("Data");
									$response->x_print($data["File"][$reference]["ItemOperations"]["Data"]);
								$response->x_close("Data");

								if(isset($xml->Fetch->Options->RightsManagementSupport))
									if(intval($xml->Fetch->Options->RightsManagementSupport) == 1)
										if(isset($data["RightsManagement"]))
											{
											$response->x_switch("RightsManagement");

											$response->x_open("RightsManagementLicense");

												# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

												foreach(active_sync_get_default_rights_management() as $token => $value)
													{
													if(isset($data["RightsManagement"][$token]) === false)
														continue;

													if(strlen($data["RightsManagement"][$token]) == 0)
														{
														$response->x_open($token, false);

														continue;
														}

													$response->x_open($token);
														$response->x_print($data["RightsManagement"][$token]);
													$response->x_close($token);
													}

											$response->x_close("RightsManagementLicense");
											}

							$response->x_close("Properties");
							}

					$response->x_close("Fetch");
				$response->x_close("Response");
			$response->x_close("ItemOperations");
			}

		if((isset($xml->Fetch->CollectionId) === true) && (isset($xml->Fetch->ServerId) === true))
			{
			$collection_id	= strval($xml->Fetch->CollectionId);
			$server_id	= strval($xml->Fetch->ServerId);
#			$irm	= isset($xml->Fetch->RemoveRightsManagementProtection);

			$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

			$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

			$response->x_switch("ItemOperations");

			$response->x_open("ItemOperations");

				$response->x_open("Status");
					$response->x_print(1);
				$response->x_close("Status");

				$response->x_open("Response");
					$response->x_open("Fetch");

						$response->x_open("Status");
							$response->x_print(1);
						$response->x_close("Status");

						$response->x_switch("AirSync");

						# what about calendar and contact and notes and things?

						foreach(array("CollectionId" => $collection_id, "ServerId" => $server_id) as $token => $value)
							{
							$response->x_open($token);
								$response->x_print($value);
							$response->x_close($token);
							}

						$response->x_switch("ItemOperations");

						$response->x_open("Properties");

							foreach(array("Email", "Email2") as $codepage)
								{
								if(isset($data[$codepage]) === false)
									continue;

								$response->x_switch($codepage);

								foreach($data[$codepage] as $token => $value)
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

							$response->x_switch("Email");

							if(isset($data["Flag"]) === true)
								{
								$response->x_switch("Email");

								$response->x_open("Flag");

									foreach($data["Flag"] as $token => $value)
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

								$response->x_close("Flag");
								}
							else
								$response->x_open("Flag", false);

							if(isset($data["Body"]) === true)
								{
								$default_class = active_sync_get_class_by_collection_id($user, $collection_id);

								if(isset($xml->Fetch->Options) === true)
									{
									foreach($xml->Fetch->Options as $options)
										{
										if(isset($options->Class))
											if(isset($data["AirSync"]["Class"]))
												if(strval($options->Class) != $data["AirSync"]["Class"])
													continue;

										if(isset($options->RightsManagementSupport))
											if(intval($options->RightsManagementSupport) == 1)
												if(isset($data["RightsManagement"]))
													{
													$response->x_switch("RightsManagement");

													$response->x_open("RightsManagementLicense");

														# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

														foreach(active_sync_get_default_rights_management() as $token => $value)
															{
															if(isset($data["RightsManagement"][$token]) === false)
																continue;

															if(strlen($data["RightsManagement"][$token]) == 0)
																{
																$response->x_open($token, false);

																continue;
																}

															$response->x_open($token);
																$response->x_print($data["RightsManagement"][$token]);
															$response->x_close($token);
															}

													$response->x_close("RightsManagementLicense");
													}

										foreach($options->BodyPreference as $preference)
											{
											foreach($data["Body"] as $random_body_id => $null) # !!!
												{
												if(isset($data["Body"][$random_body_id]["Type"]) === false)
													continue;

												if($data["Body"][$random_body_id]["Type"] != intval($preference->Type))
													continue;

												$response->x_switch("AirSyncBase");

												$response->x_open("Body");

													if(isset($preference["Preview"]))
														foreach($data["Body"] as $random_preview_id => $null) # !!!
															{
															if(isset($data["Body"][$random_preview_id]["Type"]) === false)
																continue;

															if($data["Body"][$random_preview_id]["Type"] != 1)
																continue;

															$response->x_open("Preview");
																$response->x_print(substr($data["Body"][$random_preview_id]["Data"], 0, intval($preference->Preview)));
															$response->x_close("Preview");
															}

													if(isset($preference->TruncationSize))
														if(intval($preference->TruncationSize) != 0)
															if(isset($data["Body"][$random_body_id]["EstimatedDataSize"]) === false)
																{
																$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

																$response->x_open("Truncated");
																	$response->x_print(1);
																$response->x_close("Truncated");
																}
															elseif(intval($preference-Truncation-Size) > $data["Body"][$random_body_id]["EstimatedDataSize"])
																{
																}
															elseif(intval($preference->TruncationSize) < $data["Body"][$random_body_id]["EstimatedDataSize"])
																{
																$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

																$response->x_open("Truncated");
																	$response->x_print(1);
																$response->x_close("Truncated");
																}

													foreach($data["Body"][$random_body_id] as $token => $value)
														{
														if(strlen($data["Body"][$random_body_id][$token]) == 0)
															{
															$response->x_open($token, false);

															continue;
															}

														$response->x_open($token);
															$response->x_print($data["Body"][$random_body_id][$token]); # opaque data will fail :(
														$response->x_close($token);
														}

												$response->x_close("Body");
												}
											}
										}
									}
								}

							$response->x_switch("ItemOperations");

						$response->x_close("Properties");
					$response->x_close("Fetch");
				$response->x_close("Response");
			$response->x_close("ItemOperations");
			}
		}

	if(isset($xml->Move) === true)
		{
		}

	return($response->response);
	}
?>
