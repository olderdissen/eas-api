<?php
#function active_sync_handle_create_collection($equest)
#	{
#	}

#function active_sync_handle_delete_collection($request)
#	{
#	}

#function active_sync_handle_find($request)
#	{
#	}

function active_sync_handle_folder_create($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$sync_key	= strval($xml->SyncKey);
	$parent_id	= strval($xml->ParentId);
	$display_name	= strval($xml->DisplayName);
	$type		= strval($xml->Type);

	$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if($sync_key != $settings_client["SyncKey"])
		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
	else
		$status = active_sync_folder_create($request["AuthUser"], $parent_id, $display_name, $type);

	if($status == 1)
		{
		$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

		$settings_server = active_sync_get_settings_folder_server($request["AuthUser"]);

		$settings_client["SyncKey"] ++;
		$settings_client["SyncDat"] = $settings_server["SyncDat"];

		active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);

		$server_id = active_sync_get_collection_id_by_display_name($request["AuthUser"], $display_name);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderCreate");

		if($status == 1)
			$table = ["Status" => $status, "SyncKey" => $settings_client["SyncKey"], "ServerId" => $server_id];
		else
			$table = ["Status" => $status];

		foreach($table as $token => $value)
			$response->x_text($token, $value);

	$response->x_close("FolderCreate");

	return($response->response);
	}

function active_sync_handle_folder_delete($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$sync_key	= strval($xml->SyncKey);
	$server_id	= strval($xml->ServerId);

	$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if($sync_key != $settings_client["SyncKey"])
		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
	else
		$status = active_sync_folder_delete($request["AuthUser"], $server_id);

	if($status == 1)
		{
		$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

		$settings_server = active_sync_get_settings_folder_server($request["AuthUser"]);

		$settings_client["SyncKey"] ++;
		$settings_client["SyncDat"] = $settings_server["SyncDat"];

		active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderDelete");

		if($status == 1)
			$table = ["Status" => $status, "SyncKey" => $settings_client["SyncKey"]];
		else
			$table = ["Status" => $status];

		foreach($table as $token => $value)
			$response->x_text($token, $value);

	$response->x_close("FolderDelete");

	return($response->response);
	}

function active_sync_handle_folder_sync($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$sync_key = strval($xml->SyncKey);

	$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if($sync_key == 0)
		$status = 1; # Success.
	elseif($sync_key != $settings_client["SyncKey"])
		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
	else
		$status = 1; # Success.

	if(active_sync_get_need_wipe($request))
		$status = 140;

	if(active_sync_get_need_provision($request))
		$status = 142;

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderSync");

		if($status == 1)
			$settings_client["SyncKey"] ++;

		if($sync_key == 0)
			$settings_client["SyncDat"] = [];

		if($status == 142)
			$table = ["Status" => $status];
		else
			$table = ["Status" => $status, "SyncKey" => $settings_client["SyncKey"]];
		
		foreach($table as $token => $value)
			$response->x_text($token, $value);

		if($status == 1)
			{
			$jobs = [];

			$settings_server = active_sync_get_settings_folder_server($request["AuthUser"]);

			foreach($settings_server["SyncDat"] as $settings_server_id => $settings_server_data)
				{
				$known = false;

				foreach($settings_client["SyncDat"] as $settings_client_id => $settings_client_data)
					if($settings_server_data["ServerId"] != $settings_client_data["ServerId"])
						continue;
					elseif($settings_server_data["ParentId"] != $settings_client_data["ParentId"])
						$jobs["Update"][] = $settings_server_data;
					elseif($settings_server_data["DisplayName"] != $settings_client_data["DisplayName"])
						$jobs["Update"][] = $settings_server_data;
					elseif($settings_server_data["Type"] != $settings_client_data["Type"])
						$jobs["Update"][] = $settings_server_data;
					else
						$known = true;

				if(! $known)
					$jobs["Add"][] = $settings_server_data;
				}

			foreach($settings_client["SyncDat"] as $settings_client_id => $settings_client_data)
				{
				$known = false;

				foreach($settings_server["SyncDat"] as $settings_server_id => $settings_server_data)
					if($settings_client_data["ServerId"] != $settings_server_data["ServerId"])
						continue;
					else
						$known = true;

				if(! $known)
					$jobs["Delete"][] = $settings_client_data;
				}

			$actions = [
				"Add" => ["ServerId", "ParentId", "DisplayName", "Type"],
				"Delete" => ["ServerId"],
				"Update" => ["ServerId", "ParentId", "DisplayName", "Type"]
				];

			$count = 0;

			foreach($actions as $action => $fields)
				if(isset($jobs[$action]))
					$count += count($jobs[$action]);

			$response->x_open("Changes");

				$response->x_text("Count", $count);

				if($count > 0)
					foreach($actions as $action => $fields)
						if(isset($jobs[$action]))
							foreach($jobs[$action] as $job)
								{
								if($action == "Add")
									$settings_client["SyncDat"][] = $job;

								if($action == "Delete")
									foreach($settings_client["SyncDat"] as $settings_client_id => $settings_client_data)
										if($settings_client_data["ServerId"] == $job["ServerId"])
											unset($settings_client["SyncDat"][$settings_client_id]);

								if($action == "Update")
									foreach($settings_client["SyncDat"] as $settings_client_id => $settings_client_data)
										if($settings_client_data["ServerId"] == $job["ServerId"])
											$settings_client["SyncDat"][$settings_client_id] = $job;

								$response->x_open($action);

									# on Delete use ServerId only
									foreach($fields as $key)
										$response->x_text($key, $job[$key]);

								$response->x_close($action);
								}

			$response->x_close("Changes");
			}

	$response->x_close("FolderSync");

	active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);

	return($response->response);
	}

function active_sync_handle_folder_update($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$sync_key	= strval($xml->SyncKey);
	$server_id	= strval($xml->ServerId);
	$parent_id	= strval($xml->ParentId);
	$display_name	= strval($xml->DisplayName);

	$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if($sync_key != $settings_client["SyncKey"])
		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
	else
		$status = active_sync_folder_update($request["AuthUser"], $server_id, $parent_id, $display_name);

	if($status == 1)
		{
		$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

		$settings_server = active_sync_get_settings_folder_server($request["AuthUser"]);

		$settings_client["SyncKey"] ++;
		$settings_client["SyncDat"] = $settings_server["SyncDat"];

		active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderUpdate");

		if($status == 1)
			$table = ["Status" => $status, "SyncKey" => $settings_client["SyncKey"]];
		else
			$table = ["Status" => $status];

		foreach($table as $token => $value)
			$response->x_text($token, $value);

	$response->x_close("FolderUpdate");

	return($response->response);
	}

function active_sync_handle_get_attachment($request)
	{
#	header("Content-Type: application/vnd.ms-sync.wbxml");
	header("Content-Length: 0");
	}

function active_sync_handle_get_hierarchy($request)
	{
	# request is always empty

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("Folders");

		$settings_server = active_sync_get_settings_folder_server($request["AuthUser"]);

		foreach($settings_server["SyncDat"] as $folder)
			{
			$response->x_open("Folder");

				foreach(["ServerId", "ParentId", "DisplayName", "Type"] as $token);
					$response->x_text($token, $folder[$token]);

			$response->x_close("Folder");
			}

	$response->x_close("Folders");

	return($response->response);
	}

function active_sync_handle_get_item_estimate($request)
	{
	$request["xml"] = active_sync_wbxml_request_a($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$response = new active_sync_wbxml_response();

	$response->x_switch("ItemEstimate");

	$response->x_open("GetItemEstimate");

		if(isset($xml->Collections))
			{
			foreach($xml->Collections->Collection as $collection)
				{
				$sync_key	= strval($collection->SyncKey);
				$collection_id	= strval($collection->CollectionId);

				$settings_client = active_sync_get_settings_files_client($request["AuthUser"], $collection_id, $request["DeviceId"]);

				$settings_server = active_sync_get_settings_files_server($request["AuthUser"], $collection_id);

				$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

				if($sync_key != $settings_client["SyncKey"])
					$status = 4; # The synchonization key was invalid
				else
					$status = 1; # Success

				$response->x_open("Response");

					$response->x_text("Status", $status);

					if($status == 1)
						{
						$jobs = [];

						foreach($settings_server["SyncDat"] as $server_id => $null)
							{
							$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

							if(! isset($data["AirSync"]["Class"]))
								$data["AirSync"]["Class"] = $default_class;

							$class = $default_class;
							$filter_type = 0;
							$class_found = false;

							if(isset($collection->Options))
								foreach($collection->Options as $options)
									{
									if(isset($options->Class))
										$class = strval($options->Class); # only occurs on email/sms
									else
										$class = $default_class;

									if($data["AirSync"]["Class"] != $class)
										continue;

									if(isset($options->FilterType))
										$filter_type = intval($options->FilterType); # only occurs on email/sms
									else
										$filter_type = 0;

									$class_found = true;
									}

							if(! $class_found)
								{
								if(! isset($settings_client["SyncDat"][$server_id]))
									$settings_client["SyncDat"][$server_id] = "*";
								elseif($settings_client["SyncDat"][$server_id] == "*")
									{
									# file is known as SoftDelete
									}
								elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
									$jobs["SoftDelete"][] = $server_id;
								else
									$jobs["SoftDelete"][] = $server_id;

								$filter_type = 9; # :) no more filter_type between 0 (all), 1 - 7, 8 (incomplete)
								}

							if($filter_type == 0)
								{
								if(! isset($settings_client["SyncDat"][$server_id]))
									$jobs["Add"][] = $server_id;
								elseif($settings_client["SyncDat"][$server_id] == "*")
									$jobs["Add"][] = $server_id;
								elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
									$jobs["Change"][] = $server_id;

								$class_found = true;
								}

							if(($filter_type > 0) && ($filter_type < 8))
								{
								$stat_filter = ["now", "- 1 day", "- 3 day", "- 1 week", "- 2 week", "- 1 month", "- 3 month", "- 6 month", "now"];

								$stat_filter = strtotime($stat_filter[$filter_type]);

								if($default_class == "Calendar")
									$timestamp = strtotime($data["Calendar"]["EndTime"]);

								if($default_class == "Email")
									$timestamp = strtotime($data["Email"]["DateReceived"]);

								if($default_class == "Notes")
									$timestamp = strtotime($data["Notes"]["LastModifiedDate"]);

								if($default_class == "SMS")
									$timestamp = strtotime($data["Email"]["DateReceived"]);

								if($default_class == "Tasks")
									$timestamp = strtotime($data["Tasks"]["DateCompleted"]);

								if(! isset($settings_client["SyncDat"][$server_id]))
									{
									if($timestamp < $stat_filter)
										$jobs["SoftDelete"][] = $server_id;
									else
										$jobs["Add"][] = $server_id;
									}
								elseif($settings_client["SyncDat"][$server_id] == "*")
									{
									if($timestamp < $stat_filter)
										{
										}
									else
										$jobs["Add"][] = $server_id;
									}
								elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
									{
									if($timestamp < $stat_filter)
										$jobs["SoftDelete"][] = $server_id;
									else
										$jobs["Change"][] = $server_id;
									}
								else
									{
									if($timestamp < $stat_filter)
										$jobs["SoftDelete"][] = $server_id;
									}
								}

							if($filter_type == 8)
								{
								if(! isset($settings_client["SyncDat"][$server_id]))
									{
									if($data["Tasks"]["Complete"] == 1)
										$jobs["SoftDelete"][] = $server_id;
									else
										$jobs["Add"][] = $server_id;
									}
								elseif($settings_client["SyncDat"][$server_id] == "*")
									{
									if($data["Tasks"]["Complete"] == 1)
										$jobs["SoftDelete"][] = $server_id;
									else
										$jobs["Add"][] = $server_id;
									}
								elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
									{
									if($data["Tasks"]["Complete"] == 1)
										$jobs["SoftDelete"][] = $server_id;
									else
										$jobs["Change"][] = $server_id;
									}
								}
							}

						foreach($settings_client["SyncDat"] as $server_id => $timestamp)
							if(! isset($settings_server["SyncDat"][$server_id]))
								$jobs["Delete"][] = $server_id;

						$estimate = 0;

						foreach(["Add", "Change", "Delete", "SoftDelete"] as $command)
							if(isset($jobs[$command]))
								$estimate += count($jobs[$command]);

						$response->x_open("Collection");

							foreach(["CollectionId" => $collection_id, "Estimate" => $estimate] as $token => $value)
								$response->x_text($token, $value);

						$response->x_close("Collection");
						}

				$response->x_close("Response");
				}
			}

	$response->x_close("GetItemEstimate");

	return($response->response);
	}

function active_sync_handle_item_operations($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$response = new active_sync_wbxml_response();

	if(isset($xml->EmptyFolderContents))
		{
		$collection_id = strval($xml->EmptyFolderContents->children("AirSync")->CollectionId);

		# $xml->EmptyFolderContents->Options->DeleteSubFolders

#		foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/*.data") as $file)
#			unlink($file);

		$response->x_switch("ItemOperations");

		$response->x_open("ItemOperations");
			$response->x_text("Status", 1);

			$response->x_open("Response");
				$response->x_open("EmptyFolderContents");

					$response->x_switch("ItemOperations");
					$response->x_text("Status", 1);

					$response->x_switch("AirSync");
					$response->x_text("CollectionId", $collection_id);

				$response->x_close("EmptyFolderContents");
			$response->x_close("Response");
		$response->x_close("ItemOperations");
		}

	if(isset($xml->Fetch))
		{
		$store = strval($xml->Fetch->Store); # Mailbox

		if(isset($xml->Fetch->LongId))
			{
			$long_id = strval($xml->Fetch->LongId);

			# ...
			}

		if(isset($xml->Fetch->children("AirSyncBase")->FileReference))
			{
			$file_reference = strval($xml->Fetch->children("AirSyncBase")->FileReference);

			$file = __DIR__ . "/" . $request["AuthUser"] . "/.files/" . $file_reference;

			list($u, $c, $s, $r) = explode(":", $file_reference);

			$data = active_sync_get_settings_data($u, $c, $s);

			$response->x_switch("ItemOperations");

			$response->x_open("ItemOperations");
				$response->x_text("Status", 1);

				$response->x_open("Response");
					$response->x_open("Fetch");

#						if(file_exists($file))
							$status = 1;
#						else
#							$status = 15; # Attachment fetch provider - Attachment or attachment ID is invalid.

						$response->x_switch("ItemOperations");
						$response->x_text("Status", $status);

						$response->x_switch("AirSyncBase");
						$response->x_text("FileReference", $file_reference);

						if($status == 1)
							{
							$response->x_switch("ItemOperations");

							$response->x_open("Properties");

								$response->x_switch("AirSyncBase");
#								$response->x_text("ContentType", mime_content_type($file));
								$response->x_text("ContentType", $data["File"][$r]["AirSyncBase"]["ContentType"]);

								$response->x_switch("ItemOperations");
#								$response->x_text("Data", base64_encode(file_get_contents($file)));
								$response->x_text("Data", $data["File"][$r]["ItemOperations"]["Data"]);

								if(isset($xml->Fetch->Options->RightsManagementSupport))
									if(intval($xml->Fetch->Options->RightsManagementSupport) == 1)
										if(isset($data["RightsManagement"]))
											{
											$response->x_switch("RightsManagement");

											$response->x_open("RightsManagementLicense");

												# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

												foreach(active_sync_get_default_rights_management() as $token => $value)
													{
													if(! isset($data["RightsManagement"][$token]))
														continue;

													$response->x_text($token, $data["RightsManagement"][$token]);
													}

											$response->x_close("RightsManagementLicense");
											}

							$response->x_close("Properties");
							}

					$response->x_close("Fetch");
				$response->x_close("Response");
			$response->x_close("ItemOperations");
			}

		if(isset($xml->Fetch->children("AirSync")->CollectionId) && isset($xml->Fetch->children("AirSync")->ServerId))
			{
			$collection_id	= strval($xml->Fetch->children("AirSync")->CollectionId);
			$server_id	= strval($xml->Fetch->children("AirSync")->ServerId);
#			$irm		= isset($xml->Fetch->children("RightsManagement")->RemoveRightsManagementProtection);

			$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

			$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

			$response->x_switch("ItemOperations");

			$response->x_open("ItemOperations");

				$response->x_text("Status", 1);

				$response->x_open("Response");
					$response->x_open("Fetch");

						$response->x_text("Status", 1);

						$response->x_switch("AirSync");

						# what about calendar and contact and notes and things?

						foreach(["CollectionId" => $collection_id, "ServerId" => $server_id] as $token => $value)
							$response->x_text($token, $value);

						$response->x_switch("ItemOperations");

						$response->x_open("Properties");

							foreach(["Email", "Email2"] as $codepage)
								{
								if(! isset($data[$codepage]))
									continue;

								$response->x_switch($codepage);

								foreach($data[$codepage] as $token => $value)
									$response->x_text($token, $value);
								}

							$response->x_switch("Email");

							if(isset($data["Flag"]))
								{
								$response->x_switch("Email");

								$response->x_open("Flag");

									foreach($data["Flag"] as $token => $value)
										$response->x_text($token, $value);

								$response->x_close("Flag");
								}
							else
								$response->x_open("Flag", false);

							$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

							if(isset($data["Body"]) )
								if(isset($xml->Fetch->Options))
									foreach($xml->Fetch->Options as $options)
										{
										if(isset($options->Class))
											if(isset($data["AirSync"]["Class"]))
												if(strval($options->Class) != $data["AirSync"]["Class"])
													continue;

										if(isset($options->children("RightsManagement")->RightsManagementSupport))
											if(intval($options->children("RightsManagement")->RightsManagementSupport) == 1)
												if(isset($data["RightsManagement"]))
													{
													$response->x_switch("RightsManagement");

													$response->x_open("RightsManagementLicense");

														# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

														foreach(active_sync_get_default_rights_management() as $token => $value)
															{
															if(! isset($data["RightsManagement"][$token]))
																continue;

															$response->x_text($token, $data["RightsManagement"][$token]);
															}

													$response->x_close("RightsManagementLicense");
													}

										foreach($options->children("AirSyncBase")->BodyPreference as $preference)
											foreach($data["Body"] as $body) # !!!
												{
												if(! isset($body["Type"]))
													continue;

												if($body["Type"] != intval($preference->Type))
													continue;

												$response->x_switch("AirSyncBase");

												$response->x_open("Body");

													if(isset($preference["Preview"]))
														foreach($data["Body"] as $preview) # !!!
															{
															if(! isset($preview["Type"]))
																continue;

															if($preview["Type"] != 1)
																continue;

															$response->x_text("Preview", substr($preview["Data"], 0, intval($preference->Preview)));
															}

													if(isset($preference->TruncationSize))
														if(intval($preference->TruncationSize) != 0)
															if(! isset($body["EstimatedDataSize"]))
																{
																$body["Data"] = substr($body["Data"], 0, intval($preference->TruncationSize));

																$response->x_text("Truncated", 1);
																}
															elseif(intval($preference->TruncationSize) < $body["EstimatedDataSize"])
																{
																$body["Data"] = substr($body["Data"], 0, intval($preference->TruncationSize));

																$response->x_text("Truncated", 1);
																}

													foreach($body as $token => $value)
														$response->x_text($token, $value);

												$response->x_close("Body");
												}
										}

							$response->x_switch("ItemOperations");

						$response->x_close("Properties");
					$response->x_close("Fetch");
				$response->x_close("Response");
			$response->x_close("ItemOperations");
			}
		}

	if(isset($xml->Move))
		{
		}

	return($response->response);
	}

function active_sync_handle_meeting_response($request)
	{
	$request["xml"] = active_sync_wbxml_request_a($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$response = new active_sync_wbxml_response();

	if(isset($xml->Request))
		{
		$user_response	= strval($xml->Request->UserResponse);
		$collection_id	= strval($xml->Request->CollectionId);	# inbox
		$request_id	= strval($xml->Request->RequestId);	# server_id
		$long_id	= strval($xml->Request->LongId);
		$instance_id	= strval($xml->Request->InstanceId);	# used if appointment is a recurring one

		$user = $request["AuthUser"];
		$host = active_sync_get_domain();

		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		unlink(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $request_id . ".data");

		$calendar_id = active_sync_get_calendar_by_uid($user, $data["Meeting"]["Email"]["UID"]);

		$collection_id = active_sync_get_collection_id_by_type($user, 8); # Calendar
		# this need to be changed, this function has to return a list of all kind of calendars

		if($calendar_id == "")
			{
			$calendar = [];

			$calendar["Calendar"] = $data["Meeting"]["Email"];

			unset($calendar["Calendar"]["Organizer"]);

			list($organizer_name, $organizer_mail) = active_sync_mail_parse_address($data["Meeting"]["Email"]["Organizer"]);

			foreach(["OrganizerName" => $organizer_name, "OrganizerEmail" => $organizer_mail] as $token => $value)
				if(strlen($value))
					$calendar["Calendar"][$token] = $value;

			$calendar["Calendar"]["MeetingStatus"] = 3;

			$calendar["Calendar"]["Subject"] = $data["Email"]["Subject"];

			if($user_response == 1)
				$calendar["Calendar"]["ResponseType"] = 3;

			if($user_response == 2)
				$calendar["Calendar"]["ResponseType"] = 2;

			if($user_response == 3)
				$calendar["Calendar"]["ResponseType"] = 4;

			if($user_response != 3)
				{
				$calendar_id = active_sync_create_guid_filename($user, $collection_id);

				active_sync_put_settings_data($user, $collection_id, $calendar_id, $calendar);
				}

			$boundary = active_sync_create_guid();

			$description = [
				"Wann: " . date("d.m.Y H:i:s", strtotime($data["Meeting"]["Email"]["StartTime"]))
				];

			if(isset($data["Meeting"]["Email"]["Location"]))
				$description[] = "Wo: " . $data["Meeting"]["Email"]["Location"];

			$description[] = "*~*~*~*~*~*~*~*~*~*";

			if(isset($data["Body"]))
				foreach($data["Body"] as $body)
					if(isset($body["Type"]))
						if($body["Type"] == 1)
							if(isset($body["Data"]))
								$description[] = $body["Data"];

			$description = implode("\n", $description);

			$mime = [
				"From: " . $data["Email"]["To"],
				"To: " . $data["Email"]["From"]
				];

			foreach(["Accepted" => 1, "Tentative" => 2, "Declined" => 3] as $subject => $value)
				if($user_response == $value)
					$mime[] = "Subject: " . $subject . ": " . $data["Email"]["Subject"];

			$mime[] = "MIME-Version: 1.0";
			$mime[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
			$mime[] = "";
			$mime[] = "This is a multi-part message in MIME format.";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/plain; charset=\"utf-8\"";
			$mime[] = "";
			$mime[] = $description;
			$mime[] = "";

			foreach(["Accepted" => 1, "Tentative" => 2, "Declined" => 3] as $message => $value)
				if($user_response == $value)
					$mime[] = $message;

			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/calendar; method=REPLY; name=\"invite.ics\"";
			$mime[] = "";
			# use ics
			$mime[] = "BEGIN:VCALENDAR";
				$mime[] = "PRODID:" . active_sync_get_version();
				$mime[] = "VERSION:2.0";
				$mime[] = "METHOD:REPLY";
				# VTIMEZONE
				$mime[] = "BEGIN:VEVENT";
					$mime[] = "UID:" . $data["Meeting"]["Email"]["UID"];

					foreach(["DTSTAMP" => "DtStamp", "DTSTART" => "StartTime", "DTEND" => "EndTime"] as $key => $token)
						if(isset($data["Meeting"]["Email"][$token]))
							$mime[] = $key . ":" . $data["Meeting"]["Email"][$token];

					if(isset($data["Meeting"]["Location"]))
						$mime[] = "LOCATION: " . $data["Meeting"]["Email"]["Location"];

					if(isset($data["Email"]["Subject"]))
						$mime[] = "SUMMARY: " . $data["Email"]["Subject"]; # take this from email subject

					$mime[] = "DESCRIPTION:" . $description;

					if(isset($data["Meeting"]["Email"]["AllDayEvent"]))
						foreach(["FALSE" => 0, "TRUE" => 1] as $key => $value)
							if($data["Meeting"]["Email"]["AllDayEvent"] == $value)
								$mime[] = "X-MICROSOFT-CDO-ALLDAYEVENT:" . $key;

					foreach(["ACCEPTED" => 1, "TENTATIVE" => 2, "DECLINED" => 3] as $partstat => $value)
						if($user_response == $value)
							$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=" . $partstat . ";RSVP=TRUE:mailto:" . $user . "@" . $host;

					list($organizer_name, $organizer_mail) = active_sync_mail_parse_address($data["Meeting"]["Email"]["Organizer"]);

					if(strlen($organizer_name))
						$mime[] = "ORGANIZER;CN=\"" . $organizer_name . "\":mailto:" . $organizer_mail;
					else
						$mime[] = "ORGANIZER:mailto:" . $organizer_mail;

					$mime[] = "STATUS:CONFIRMED";
					$mime[] = "TRANSP:OPAQUE";
					$mime[] = "PRIORITY:5";
					$mime[] = "SEQUENCE:0";

				$mime[] = "END:VEVENT";
			$mime[] = "END:VCALENDAR";
			$mime[] = "--" . $boundary . "--";

			$mime = implode("\n", $mime);

			active_sync_send_mail($user, $mime);
			}

		# http://msdn.microsoft.com/en-us/library/exchange/hh428684%28v=exchg.140%29.aspx
		# http://msdn.microsoft.com/en-us/library/exchange/hh428685%28v=exchg.140%29.aspx

		$response->x_switch("MeetingResponse");

		$response->x_open("MeetingResponse");

			$response->x_open("Result");

				foreach(["Status" => 1, "RequestId" => $request_id, "CalendarId" => $calendar_id] as $token => $value)
					$response->x_text($token, $value);

			$response->x_close("Result");

		$response->x_close("MeetingResponse");
		}

	return($response->response);
	}

#function active_sync_handle_move_collection($request)
#	{
#	}

function active_sync_handle_move_items($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Move");

	$response->x_open("MoveItems");

		if(isset($xml->Move))
			{
			foreach($xml->Move as $move)
				{
				$src_msg_id = strval($move->SrcMsgId);
				$src_fld_id = strval($move->SrcFldId);
				$dst_fld_id = strval($move->DstFldId);

				if(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $src_fld_id))
					$status = 1; # Invalid source collection ID or invalid source Item ID.
				elseif(! file_exists(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $src_fld_id . "/" . $src_msg_id . ".data"))
					$status = 1; # Invalid source collection ID or invalid source Item ID.
				elseif(count($move->DstFldId) > 1)
					$status = 5; # One of the following failures occurred: the item cannot be moved to more than one item at a time, or the source or destination item was locked.
				elseif(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $dst_fld_id))
					$status = 2; # Invalid destination collection ID.
				elseif($src_fld_id == $dst_fld_id)
					$status = 4; # Source and destination collection IDs are the same.
				else
					{
					$dst_msg_id = active_sync_create_guid_filename($request["AuthUser"], $dst_fld_id);

					$src = ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $src_fld_id . "/" . $src_msg_id . ".data";
					$dst = ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $dst_fld_id . "/" . $dst_msg_id . ".data";

					if(rename($src, $dst))
						$status = 3; # Success.
					else
						$status = 7; # Source or destination item was locked.
					}

				$response->x_open("Response");

					foreach(($status == 3 ? ["Status" => $status, "SrcMsgId" => $src_msg_id, "DstMsgId" => $dst_msg_id] : ["Status" => $status, "SrcMsgId" => $src_msg_id]) as $token => $value)
						$response->x_text($token, $value);

				$response->x_close("Response");
				}
			}

	$response->x_close("MoveItems");

	return($response->response);
	}

function active_sync_handle_ping($request)
	{
	$settings = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if(isset($request["wbxml"]))
		$request["xml"] = active_sync_wbxml_load($request["wbxml"]);
	else
		$request["xml"] = '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/"><Ping xmlns="Ping" />';

	$xml = simplexml_load_string($request["xml"]);

	if(isset($xml->HeartbeatInterval))
		$settings["HeartbeatInterval"] = intval($xml->HeartbeatInterval);

	if(isset($xml->Folders))
		{
		unset($settings["Ping"]);

		foreach($xml->Folders->Folder as $folder)
			{
			$settings["Ping"][] = [
				"Id" => strval($folder->Id),
				"Class" => strval($folder->Class)
				];
			}
		}

	if(isset($settings["HeartbeatInterval"]))
		{
		unset($xml->HeartbeatInterval);

		$x = $xml->addChild("HeartbeatInterval", $settings["HeartbeatInterval"]);
		}

	if(isset($settings["Ping"]))
		{
		unset($xml->Folders);

		$x = $xml->addChild("Folders");

		foreach($settings["Ping"] as $folder)
			{
			$y = $x->addChild("Folder");

			$y->addChild("Id", $folder["Id"]);
			$y->addChild("Class", $folder["Class"]);
			}
		}

	if(isset($_SERVER["REMOTE_PORT"]))
		$settings["Port"] = intval($_SERVER["REMOTE_PORT"]);

	active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings);

#	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

#	$xml = simplexml_load_string($request["xml"]);

	$changed_folders = [];

	while(1)
		{
		if(active_sync_get_need_wipe($request))
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(active_sync_get_need_provision($request))
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(active_sync_get_need_folder_sync($request))
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(! isset($xml->Folders))
			{
			$status = 3; # The Ping command request omitted required parameters.

			break;
			}

		if(count($xml->Folders->Folder) > ACTIVE_SYNC_PING_MAX_FOLDERS)
			{
			$status = 6; # The Ping command request specified more than the allowed number of folders to monitor.

			break;
			}

		if(! isset($xml->HeartbeatInterval))
			{
			$status = 3; # The Ping command request omitted required parameters.

			break;
			}

		if(intval($xml->HeartbeatInterval) < 60)
			{
			$status = 5; # The specified heartbeat interval is outside the allowed range.

			$heartbeat_interval = 60;

			break;
			}

		if(intval($xml->HeartbeatInterval) > 3540)
			{
			$status = 5; # The specified heartbeat interval is outside the allowed range.

			$heartbeat_interval = 3540;

			break;
			}

		if(($_SERVER["REQUEST_TIME"] + intval($xml->HeartbeatInterval)) < time())
			{
			$status = 1; # The heartbeat interval expired before any changes occurred in the folders being monitored.

			break;
			}

		foreach($xml->Folders->Folder as $folder)
			{
			$changes_detected = false;
			$collection_id = strval($folder->Id);

			$settings_client = active_sync_get_settings_files_client($request["AuthUser"], $collection_id, $request["DeviceId"]);

			$settings_server = active_sync_get_settings_files_server($request["AuthUser"], $collection_id);

			if($settings_client["SyncKey"] == 0)
				$changes_detected = true;

			foreach($settings_server["SyncDat"] as $server_id => $null)
				{
				if($changes_detected)
					continue;

				if(! isset($settings_client["SyncDat"][$server_id]))
					$changes_detected = true;

				if($changes_detected)
					break;

				if($settings_client["SyncDat"][$server_id] == "*")
					continue;

				if($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
					$changes_detected = true;

				if($changes_detected)
					break;
				}

			foreach($settings_client["SyncDat"] as $server_id => $null)
				{
				if($changes_detected)
					continue;

				if(isset($settings_server["SyncDat"][$server_id]))
					continue;

				$changes_detected = true;
				}

			if(! $changes_detected)
				continue;

			$changed_folders[] = $collection_id;
			}

		if(count($changed_folders) > 0)
			{
			$status = 2; # Changes occured in at least one of the monitored folders. The response specifies the changed folders.

			break;
			}

		$settings = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

		if(! isset($settings["Port"]))
			$settings["Port"] = "n";

		# check if stored port is different. if yes, there is already a newer connection
		if($settings["Port"] != (isset($_SERVER["REMOTE_PORT"]) ? $_SERVER["REMOTE_PORT"] : "s"))
			{
			$status = 8; # An error occurred on the server.

			active_sync_debug("KILLED | " . $settings["Port"] . " REQUEST Ping", "RESPONSE"); die();

			break;
			}

		sleep(ACTIVE_SYNC_SLEEP);
		}


	$response = new active_sync_wbxml_response();

	$response->x_switch("Ping");

	$response->x_open("Ping");

		$response->x_text("Status", $status);

		if($status == 2) # Changes occured in at least one of the monitored folders. The response specifies the changed folders.
			{
			$response->x_open("Folders");

				foreach($changed_folders as $collection_id)
					$response->x_text("Folder", $collection_id);

			$response->x_close("Folders");
			}

		if($status == 5) # The specified heartbeat interval is outside the allowed range.
			$response->x_text("HeartbeatInterval", $heartbeat_interval);

		if($status == 6) # The Ping command request specified more than the allowed number of folders to monitor.
			$response->x_text("MaxFolders", ACTIVE_SYNC_PING_MAX_FOLDERS);

	$response->x_close("Ping");

	return($response->response);
	}

function active_sync_handle_provision($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$settings_server = active_sync_get_settings_server();

	$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Provision");

	$response->x_open("Provision");

		$response->x_text("Status", 1);

		if(isset($xml->children("Settings")->DeviceInformation))
			{
			if(isset($xml->children("Settings")->DeviceInformation->children("Settings")->Set))
				{
				foreach($xml->children("Settings")->DeviceInformation->children("Settings")->Set->children("Settings") as $token => $value)
					$settings_client["DeviceInformation"][$token] = strval($value);

				active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);

				$status = 1; # Success.
				}
			else
				$status = 2;

			$response->x_switch("Settings");

			$response->x_open("DeviceInformation");
				$response->x_text("Status", $status);
			$response->x_close("DeviceInformation");
			}

		if(isset($xml->Policies))
			{
			# update PolicyKey
			$settings_client["PolicyKey"] = $settings_server["Policy"]["PolicyKey"];

			# store settings of users device.
			active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);

			# everythings is ok in the beginning.
			$status = 1; # Success.

			if(isset($xml->Policies->Policy->PolicyType))
				if(strval($xml->Policies->Policy->PolicyType) != "MS-EAS-Provisioning-WBXML")
					$status = 3; # Unknown PolicyType value.
	
			if(isset($xml->Policies->Policy->Status))
				if(isset($xml->Policies->Policy->PolicyKey))
					if(strval($xml->Policies->Policy->PolicyKey) != $settings_server["Policy"]["PolicyKey"])
						$status = 5; # The client is acknowledging the wrong policy key.

#			if(! isset($settings_server["Policy"]["PolicyKey"]))
#				$status = 2; # There is no policy for this client.

			if(! isset($settings_server["Policy"]["Data"]))
				$status = 2; # There is no policy for this client.

			$table = [
				"PolicyType" => "MS-EAS-Provisioning-WBXML",
				"PolicyKey" => $settings_server["Policy"]["PolicyKey"],
				"Status" => $status
				];

			$response->x_switch("Provision");

			$response->x_open("Policies");
				$response->x_open("Policy");

					foreach($table as $token => $value)
						$response->x_text($token, $value);

					if(! isset($xml->Policies->Policy->Status))
						if(! isset($xml->Policies->Policy->PolicyKey))
							{
							$response->x_open("Data");

								if(! isset($settings_server["Policy"]["Data"]))
									$response->x_open("EASProvisionDoc", false);
								else
									{
									$response->x_open("EASProvisionDoc");

										foreach(active_sync_get_default_policy() as $token => $value)
											if(! isset($settings_server["Policy"]["Data"][$token]))
												continue;
											elseif($token == "ApprovedApplicationList" || $token == "UnapprovedInROMApplicationList")
												{
												if(isset($settings_server["Policy"]["Data"][$token]))
													{
													$t = strtr($token, ["ApprovedApplicationList" => "Hash", "UnapprovedInROMApplicationList" => "ApplicationName"]);

													$response->x_open($token);

														foreach(explode("\n", $settings_server["Policy"]["Data"][$token]) as $v)
															$response->x_text($t, $v);

													$response->x_close($token);
													}
												}
											else
												$response->x_text($token, $settings_server["Policy"]["Data"][$token]);

									$response->x_close("EASProvisionDoc");
									}

							$response->x_close("Data");
							}

				$response->x_close("Policy");
			$response->x_close("Policies");
			}

		# check for RemoteWipe marker
		if(isset($settings_client["RemoteWipe"]))
			{
			if($settings_client["RemoteWipe"] == "RemoteWipe")
				$response->x_open("RemoteWipe", false);

			if($settings_client["RemoteWipe"] == "AccountOnlyRemoteWipe")
				$response->x_open("AccountOnlyRemoteWipe", false);
			}

		# remove RemoteWipe marker
		unset($settings_client["RemoteWipe"]);

		# store settings of users device.
		active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);

	$response->x_close("Provision");

	return($response->response);
	}

function active_sync_handle_provision_remote_wipe($request)
	{
	return;

	$settings = active_sync_get_settings_server();

	foreach($settings["login"] as $login)
		{
		if(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $login["User"]))
			continue;

		$folders = active_sync_get_settings_folder_server($login["User"]);

		foreach($folders["SyncDat"] as $folder)
			{
			if(is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $login["User"] . "/" . $folder["ServerId"]))
				continue;

			$file = ACTIVE_SYNC_DAT_DIR . "/" . $login["User"] . "/" . $folder["ServerId"] . "/" . $request["DeviceId"] . ".sync";

			if(file_exists($file))
				unlink($file);
			}

		$file = ACTIVE_SYNC_DAT_DIR . "/" . $login_data["User"] . "/" . $request["DeviceId"] . ".sync";

		if(file_exists($file))
			unlink($file);
		}
	}

function active_sync_handle_resolve_recipients($request)
	{
	$host = active_sync_get_domain(); # needed for user@host
	$recipients = [];

	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("ResolveRecipients");

	$response->x_open("ResolveRecipients");

		if(! isset($xml->To))
			$status = 5;
		elseif(count($xml->To) > 20)
			$status = 161;
		else
			{
			$settings = active_sync_get_settings_server();

			foreach($xml->To as $to)
				{
				$to = strval($to);

				foreach($settings["login"] as $login)
					{
					$collection_id = active_sync_get_collection_id_by_type($login["User"], 9);

					foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $login["User"] . "/" . $collection_id . "/*.data") as $file) # contact
						{
						$server_id = basename($file, ".data");

						$data = active_sync_get_settings_data($login["User"], $collection_id, $server_id);

						foreach(["Email1Address", "Email2Address", "Email3Address"] as $token)
							{
							if(! isset($data["Contacts"][$token]))
								continue;

							list($to_name, $to_mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

							if($to_mail != $to)
								continue;

							if($login["User"] == $request["AuthUser"])
								$recipients[$to][] = ["Type" => 2, "DisplayName" => $to_name, "EmailAddress" => $to_mail];
							else
								$recipients[$to][] = ["Type" => 1, "DisplayName" => $to_name, "EmailAddress" => $to_mail];

							break(2); # foreach, while
							}
						}
					}
				}

			$status = 1;
			}

		$response->x_text("Status", $status);

		foreach($xml->To as $to)
			{
			$to = strval($to);

			$response->x_open("Response");

				$recipient_count = count($recipients[$to]);

				foreach(["To" => $to, "Status" => ($recipient_count > 1 ? 2 : 1), "RecipientCount" => $recipient_count] as $token => $value)
					$response->x_text($token, $value);

				foreach($recipients[$to] as $recipient)
					{
					$response->x_open("Recipient");

						foreach(["Type", "DisplayName", "EmailAddress"] as $field)
							$response->x_text($field, $recipient[$field]);

						if(isset($xml->Options->Availability))
							{
							$response->x_open("Availability");

								$status = 1;

								if($status == 1)
									if(! isset($xml->Options->Availability->StartTime))
										$status = 5;

								if($status == 1)
									if(! isset($xml->Options->Availability->EndTime))
										$status = 5;

								if($status == 1)
									if(((strtotime($xml->Options->Availability->EndTime) - strtotime($xml->Options->Availability->StartTime)) / 1800) > 32768)
										$status = 5;

								$response->x_text("Status", $status);

								# check host for different status

								if($status == 1)
									{
									$start_time = strtotime($xml->Options->Availability->StartTime);
									$end_time = strtotime($xml->Options->Availability->EndTime);

									$merged_free_busy = str_repeat(4, ($end_time - $start_time) / 1800); # 4 = no data

									list($to_name, $to_mail) = active_sync_mail_parse_address($recipient["EmailAddress"]);
									list($to_user, $to_host) = explode("@", $to_mail);

									if($to_host == $host)
										{
										$collection_id = active_sync_get_collection_id_by_type($to_user, 8);

										foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $to_user . "/" . $collection_id . "/*.data") as $file)
											{
											$server_id = basename($file, ".data");

											$data = active_sync_get_settings_data($to_user, $collection_id, $server_id);

											if(! isset($data["Calendar"]["BusyStatus"]))
												continue;

											if($end_time < strtotime($data["Calendar"]["StartTime"]))
												continue;

											if($start_time > strtotime($data["Calendar"]["EndTime"]))
												continue;

											for($test_time = $start_time; $test_time < $end_time; $test_time += 1800)
												if($test_time >= strtotime($data["Calendar"]["StartTime"]))
													if($test_time + 1800 <= strtotime($data["Calendar"]["EndTime"]))
														$merged_free_busy[($test_time - $start_time) / 1800] = $data["Calendar"]["BusyStatus"];
											}
										}

									$response->x_open("MergedFreeBusy");
										$response->x_print($merged_free_busy);
									$response->x_close("MergedFreeBusy");
									}

							$response->x_close("Availability");
							}

						if(isset($xml->Options->CertificateRetrieval))
							if(intval($xml->Options->CertificateRetrieval) != 1) # Do not retrieve certificates for the recipient (default).
								{
								$response->x_open("Certificates");

									$pem_file = __DIR__ . "/certs/public/" . $recipient["EmailAddress"] . ".pem";

									if(file_exists($pem_file))
										$status = 1;
									else
										$status = 7;

									if($status == 1)
										{
										foreach(["Status" => $status, "CertificateCount" => 1] as $token => $value)
											$response->x_text($token, $value);

										$certificate = file_get_contents($pem_file);

										list($null, $certificate) = explode("-----BEGIN CERTIFICATE-----", $certificate, 2);
										list($certificate, $null) = explode("-----END CERTIFICATE-----", $certificate, 2);

										$certificate = str_replace(["\r", "\n"], "", $certificate);

										if(intval($xml->Options->CertificateRetrieval) == 2) # Retrieve the full certificate for each resolved recipient.
											$response->x_text("Certificate", $certificate); # ... contains the X509 certificate ... encoded with base64 ...
										elseif(intval($xml->Options->CertificateRetrieval) == 3) # Retrieve the mini certificate for each resolved recipient.
											$response->x_text("MiniCertificate", $certificate); # ... contains the mini-certificate ... encoded with base64 ...
										}
									else
										{
										foreach(["Status" => $status, "CertificateCount" => 0] as $token => $value)
											$response->x_text($token, $value);
										}

								$response->x_close("Certificates");
								}

						if(isset($xml->Options->Picture))
							if(isset($xml->Options->Picture->MaxPictures))
								if(isset($xml->Options->Picture->MaxSize))
									{
#									$response->x_open("Picture");
#										$response->x_text("Status", 1);
#										$response->x_text("Data", "");
#									$response->x_close("Picture");
									}

					$response->x_close("Recipient");
					}

			$response->x_close("Response");
			}

	$response->x_close("ResolveRecipients");

	return($response->response);
	}

function active_sync_handle_search($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Search");

	$response->x_open("Search");

		if(! isset($xml->Store))
			$status = 3; # Server error.
		elseif(! isset($xml->Store->Name))
			$status = 1; # Ok.
		elseif(strval($xml->Store->Name) == "GAL")
			$status = 1; # Ok.
		elseif(strval($xml->Store->Name) == "Mailbox")
			$status = 1; # Ok.
		elseif(strval($xml->Store->Name) == "Document Library")
			$status = 3; # Server error.
		else
			$status = 1; # Ok.

		$response->x_text("Status", $status);

		if($status == 1)
			{
			$response->x_open("Response");
				$response->x_open("Store");

					if(! isset($xml->Store->Query))
						$status = 3; # Server error.
					else
						$status = 1; # Ok.

					$response->x_text("Status", $status);

					if($status == 1)
						if(strval($xml->Store->Name) == "GAL")
							{
							$query = strval($xml->Store->Query);

							$retval = [];

							$settings = active_sync_get_settings_server();

							foreach($settings["login"] as $login)
								{
								if($login["User"] == $request["AuthUser"])
									continue;

								$collection_id = active_sync_get_collection_id_by_type($login["User"], 9);

								if(! $collection_id)
									continue;

								foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $login["User"] . "/" . $collection_id . "/*.data") as $file)
									{
									$server_id = basename($file, ".data");

									$data = active_sync_get_settings_data($login["User"], $collection_id, $server_id);

									$data["Contacts"]["FileAs"] = active_sync_create_fullname_from_data($data, 2);

									foreach(["Email1Address", "Email2Address", "Email3Address"] as $token)
										{
										if(! isset($data["Contacts"][$token]))
											continue;

										list($name, $mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

										$data["Contacts"][$token] = $mail;
										}

									$table = [
										"Alias",
										"BusinessPhoneNumber",
										"Email1Address",
										"Email2Address",
										"Email3Address",
										"FileAs",
										"FirstName",
										"HomePhoneNumber",
										"LastName",
										"MiddleName",
										"MobilePhoneNumber"
										];

									foreach($table as $token)
										{
										if(! isset($data["Contacts"][$token]))
											continue;

										if(stripos($data["Contacts"][$token], $query) === false)
											continue;

										$retval[$server_id] = $data["Contacts"];
										}
									}
								}

#							usort($retval, function($a, $b){return($a["FileAs"] - $b["FileAs"]);});

							if(isset($xml->Store->Options->Range))
								$range = strval($xml->Store->Options->Range);
							else
								$range = "0-99";

							list($m, $n) = explode("-", $range);

							$picture_count = 0;

							foreach($retval as $data)
								{
								if($m > $n) # m ++ ???
									break;

								$m ++;

								$response->x_switch("Search");

								$response->x_open("Result");
									$response->x_open("Properties");

										$response->x_switch("GAL");

										$table = [
											"Alias" => "Alias",
											"Company" => "CompanyName",
											"DisplayName" => "FileAs",
											"EmailAddress" => "Email1Address",
											"FirstName" => "FirstName",
											"HomePhone" => "HomePhoneNumber",
											"LastName" => "LastName",
											"MobilePhone" => "MobilePhoneNumber",
											"Office" => "OfficeLocation",
											"Phone" => "BusinessPhoneNumber",
											"Title" => "Title"
											];

										foreach($table as $token_gal => $token_contact)
											{
											if(! isset($data[$token_contact]))
												continue;

											if(! strlen($data[$token_contact]))
												continue;

											$response->x_text($token_gal, $data[$token_contact]);
											}

										$status = 1;

										if(! isset($data["Picture"]))
											$status = 173;
										elseif(! strlen($data["Picture"]))
											$status = 173;
										else
											$picture_count ++;

										if(isset($xml->Store->Options->Picture->MaxSize))
											if(isset($data["Picture"]))
												if(strlen($data["Picture"]) > intval($xml->Store->Options->Picture->MaxSize))
													$status = 174;

										if(isset($xml->Store->Options->Picture->MaxPicture))
											if($picture_count > intval($xml->Store->Options->Picture->MaxPicture))
												$status = 175;

										$response->x_open("Picture");

											$response->x_text("Status", $status);

											if($status == 1)
												{
												$data["Picture"] = base64_decode($data["Picture"]);

												$response->x_open("Data");
													$response->x_print($data["Picture"]);
												$response->x_close("Data");
												}

										$response->x_close("Picture");

										$response->x_switch("Search");

									$response->x_close("Properties");
								$response->x_close("Result");
								}

							$response->x_switch("Search");

							foreach(["Range" => $range, "Total" => $m] as $token => $value)
								$response->x_text($token, $value);
							}
						elseif(strval($xml->Store->Name) == "Mailbox")
							{
							$class		= strval($xml->Store->Query->And->Class);
							$collection_id	= strval($xml->Store->Query->And->CollectionId);
							$free_text	= strval($xml->Store->Query->And->FreeText);

							$default_class	= active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

							$retval = [];

							foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/*.data") as $file)
								{
								if(! isset($xml->Store->Query->And->GreaterThan))
									continue;

								if(! isset($xml->Store->Query->And->GreaterThan->DateReceived)) # empty but existing value
									continue;

								if(! isset($xml->Store->Query->And->GreaterThan->Value))
									continue;

								if(! isset($xml->Store->Query->And->LessThan))
									continue;

								if(! isset($xml->Store->Query->And->LessThan->DateReceived)) # empty but existing value
									continue;

								if(! isset($xml->Store->Query->And->LessThan->Value))
									continue;

								$server_id = basename($file, ".data");

								$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

								if(! isset($data["AirSync"]["Class"]))
									$request["AuthUser"] = $default_class;

								if(isset($xml->Store->Query->And->Class))
									if($data["AirSync"]["Class"] != strval($xml->Store->Query->And->Class))
										continue;

								if(strtotime($data["Email"]["DateReceived"]) < strtotime(strval($xml->Store->Query->And->GreaterThan->Value)))
									continue;

								if(strtotime($data["Email"]["DateReceived"]) > strtotime(strval($xml->Store->Query->And->LessThan->Value)))
									continue;

								foreach($data["Body"] as $body)
									{
									if(!isset($body["Data"]))
										continue;
									
									if(stripos($body["Data"], $free_text) === false) # check mime ...
										continue;

									$retval[] = $data;
									}
								}

							if(isset($xml->Store->Options->Range))
								$range = strval($xml->Store->Options->Range);
							else
								$range = "0-99";

							list($m, $n) = explode("-", $range);

							foreach($retval as $retval_data)
								{
								if($m > $n)
									break;

								$m ++;

								$response->x_switch("Search");

								$response->x_open("Result");

									$response->x_switch("AirSync");

									foreach(["Class" => $class, "CollectionId" => $collection_id] as $token => $value)
										$response->x_text($token, $value);

									$response->x_switch("Search");

									$response->x_open("Properties");

										if(isset($retval_data["Email"]))
											{
											$response->x_switch("Email");

											foreach($retval_data["Email"] as $token => $value)
												$response->x_text($token, $value);
											}

										foreach($retval_data["Body"] as $body)
											{
											$response->x_switch("AirSyncBase");

											$response->x_open("Body");

												foreach($body as $token => $value)
													$response->x_text($token, $value);

											$response->x_close("Body");
											}

										$response->x_switch("Search");

									$response->x_close("Properties");
								$response->x_close("Result");
								}

							$response->x_switch("Search");

							foreach(["Range" => $range, "Total" => $m] as $token => $value)
								$response->x_text($token, $value);
							}

				$response->x_close("Store");
			$response->x_close("Response");
			}

	$response->x_close("Search");

	return($response->response);
	}

function active_sync_handle_send_mail($request)
	{
	if(isset($_SERVER["CONTENT_TYPE"]))
		if($_SERVER["CONTENT_TYPE"] == "application/vnd.ms-sync.wbxml")
			{
			$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

			$xml = simplexml_load_string($request["xml"]);

			$mime = strval($xml->Mime);

			if(isset($xml->SaveInSentItems))
				$save_in_sent_items = "T";
			else
				$save_in_sent_items = "F";
			}

	if(isset($_SERVER["CONTENT_TYPE"]))
		if($_SERVER["CONTENT_TYPE"] == "message/rfc822")
			{
			$save_in_sent_items = $request["SaveInSent"]; # name of element in request-line differs from what can be gotten from request-body

			$mime = strval($request["wbxml"]);
			}

	$response = new active_sync_wbxml_response();

	$response->x_switch("ComposeMail");

	$response->x_open("SendMail");
		$response->x_text("Status", 1);
	$response->x_close("SendMail");

	if($save_in_sent_items == "T")
		{
		$collection_id = active_sync_get_collection_id_by_type($request["AuthUser"], 5);

		$server_id = active_sync_create_guid_filename($request["AuthUser"], $collection_id);

		$data = active_sync_mail_parse($request["AuthUser"], $collection_id, $server_id, $mime);

		$data["Email"]["Read"] = 1;

		active_sync_put_settings_data($request["AuthUser"], $collection_id, $server_id, $data);
		}

	active_sync_send_mail($request["AuthUser"], $mime);

	return("");
#	return($response->response);
	}

function active_sync_handle_settings($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Settings");

	$response->x_open("Settings");

		if(isset($xml->Oof))
			{
			$status = 2; # Protocol error.

			if(isset($xml->Oof->Get))
				$status = 1; # Success.

			if(isset($xml->Oof->Set))
				$status = 1; # Success.

			$response->x_text("Status", $status);

			if($status == 1)
				{
				$status = 2; # Protocol error.

				$response->x_open("Oof");

					$response->x_text("Status", 1);

					if(isset($xml->Oof->Get))
						{
						$settings = active_sync_get_settings_folder_server($request["AuthUser"]);

						$body_type = strval($xml->Oof->Get->BodyType);

						$response->x_open("Get");

							if(isset($settings["OOF"]))
								foreach(["OofState", "StartTime", "EndTime"] as $token)
									if(isset($settings["OOF"][$token]))
										$response->x_text($token, $settings["OOF"][$token]);

							if(isset($settings["OOF"]["OofMessage"]))
								foreach($settings["OOF"]["OofMessage"] as $oof_message)
									{
									$response->x_open("OofMessage");

										foreach($oof_message as $token => $value)
											$response->x_text($token, $value);

									$response->x_close("OofMessage");
									}

						$response->x_close("Get");
						}

					if(isset($xml->Oof->Set))
						{
						$settings = active_sync_get_settings_folder_server($request["AuthUser"]);

						$settings["OOF"] = [];

						foreach(["OofState", "StartTime", "EndTime"] as $token)
							if(isset($xml->Oof->Set->$token))
								$settings["OOF"][$token] = strval($xml->Oof->Set->$token);

						if(isset($xml->Oof->Set->OofMessage))
							{
							$settings["OOF"]["OofMessage"] = [];

							foreach($xml->Oof->Set->OofMessage as $oof_message)
								{
								$data = [];

								foreach(["AppliesToInternal", "AppliesToExternalKnown", "AppliesToExternalUnknown", "Enabled", "ReplyMessage", "BodyType"] as $token)
									if(isset($oof_message->$token))
										$data[$token] = strval($oof_message->$token);

								$settings["OOF"]["OofMessage"][] = $data;
								}
							}

						active_sync_put_settings_folder_server($request["AuthUser"], $settings);
						}

				$response->x_close("Oof");
				}
			}

		if(isset($xml->DevicePassword))
			{
			if(isset($xml->DevicePassword->Set))
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_text("Status", $status);

			if($status == 1)
				{
				if(! isset($xml->DevicePassword->Set->Password))
					$status = 2; # Protocol error.
				elseif(! strval($xml->DevicePassword->Set->Password))
					$status = 2; # Protocol error.
				else
					$status = 1; # Success.

				if($status == 1)
					{
					$settings = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

					$settings["DevicePassword"] = strval($xml->DevicePassword->Set->Password);

					active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings);
					}

				$response->x_open("DevicePassword");
					$response->x_text("Status", $status);
				$response->x_close("DevicePassword");
				}
			}

		if(isset($xml->DeviceInformation))
			{
			if(isset($xml->DeviceInformation->Set))
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_text("Status", $status);

			if($status == 1)
				{
				$status = 2; # Protocol error.

				$settings = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

				foreach(active_sync_get_default_info() as $token => $value)
					{
					if(! isset($xml->DeviceInformation->Set->$token))
						continue;

					$settings["DeviceInformation"][$token] = strval($xml->DeviceInformation->Set->$token);

					$status = 1; # Success.
					}

				if($status == 1)
					active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings);

				$response->x_open("DeviceInformation");
					$response->x_text("Status", $status);
				$response->x_close("DeviceInformation");
				}
			}

		if(isset($xml->UserInformation))
			{
			if(isset($xml->UserInformation->Get))
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_text("Status", $status);

			if($status == 1)
				{
				$status = 2; # Protocol error.

				$settings = active_sync_get_settings_folder_server($request["AuthUser"]);

				$response->x_open("UserInformation");

					$response->x_text("Status", 1);

					$response->x_open("Get");
						$response->x_open("EmailAddresses");

							foreach(["SmtpAddress" => "SmtpAddress"] as $token => $value)
								$response->x_text($token, $value);

						$response->x_close("EmailAddresses");
					$response->x_close("Get");

				$response->x_close("UserInformation");
				}
			}

		if(isset($xml->RightsManagementInformation))
			{
			if(isset($xml->RightsManagementInformation->Get))
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_text("Status", $status);

			if($status == 1)
				{
				$settings = active_sync_get_settings_server();

				if(! isset($settings["RightsManagementTemplates"]))
					$status = 168;
				else
					$status = 1; # Protocol error.

				$response->x_open("RightsManagementInformation");

					$response->x_text("Status", $status);

					$response->x_open("Get");

						$response->x_switch("RightsManagement");

						if($status == 1)
							{
							$response->x_open("RightsManagementTemplates");

								foreach($settings["RightsManagementTemplates"] as $template)
									{
									$response->x_open("RightsManagementTemplate");

										foreach(["TemplateID", "TemplateName", "TemplateDescription"] as $token)
											$response->x_text($token, $template[$token]);

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

function active_sync_handle_smart_forward($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$mime = strval($xml->Mime);

	if(isset($xml->SaveInSentItems))
		$save_in_sent_items = "T";
	else
		$save_in_sent_items = "F";

	$response = new active_sync_wbxml_response();

	$response->x_switch("ComposeMail");

	$response->x_open("SmartForward");

		$response->x_text("Status", 1);

	$response->x_close("SmartForward");

	if(isset($xml->Source->FolderId))
		if(isset($xml->Source->ItemId))
			{
			$collection_id =  strval($xml->Source->FolderId);
			$server_id = strval($xml->Source->ItemId);

			$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

			$data["Email2"]["LastVerbExecuted"] = 3; # 1 REPLYTOSENDER | 2 REPLYTOALL | 3 FORWARD
			$data["Email2"]["LastVerbExecutionTime"] = date("Y-m-d\TH:i:s\Z");

			active_sync_put_settings_data($request["AuthUser"], $collection_id, $server_id, $data);
			}

	if($save_in_sent_items == "T")
		{
		$collection_id = active_sync_get_collection_id_by_type($request["AuthUser"], 5);

		$server_id = active_sync_create_guid_filename($request["AuthUser"], $collection_id);

		$data = active_sync_mail_parse($request["AuthUser"], $collection_id, $server_id, $mime);

		$data["Email"]["Read"] = 1;

		active_sync_put_settings_data($request["AuthUser"], $collection_id, $server_id, $data);
		}

	active_sync_send_mail($request["AuthUser"], $mime);

	return("");
#	return($response->response);
	}

function active_sync_handle_smart_reply($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	$mime = strval($xml->Mime);

	if(isset($xml->SaveInSentItems))
		$save_in_sent_items = "T";
	else
		$save_in_sent_items = "F";

	$response = new active_sync_wbxml_response();

	$response->x_switch("ComposeMail");

	$response->x_open("SmartReply");

		$response->x_text("Status", 1);

	$response->x_close("SmartReply");

	if(isset($xml->Source->FolderId))
		if(isset($xml->Source->ItemId))
			{
			$collection_id =  strval($xml->Source->FolderId);
			$server_id = strval($xml->Source->ItemId);

			$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

			$data["Email2"]["LastVerbExecuted"] = 1; # 1 REPLYTOSENDER | 2 REPLYTOALL | 3 FORWARD
			$data["Email2"]["LastVerbExecutionTime"] = date("Y-m-d\TH:i:s\Z");

			active_sync_put_settings_data($request["AuthUser"], $collection_id, $server_id, $data);
			}

	if($save_in_sent_items == "T")
		{
		$collection_id = active_sync_get_collection_id_by_type($request["AuthUser"], 5);

		$server_id = active_sync_create_guid_filename($request["AuthUser"], $collection_id);

		$data = active_sync_mail_parse($request["AuthUser"], $collection_id, $server_id, $mime);

		$data["Email"]["Read"] = 1;

		active_sync_put_settings_data($request["AuthUser"], $collection_id, $server_id, $data);
		}

	active_sync_send_mail($request["AuthUser"], $mime);

	return("");
#	return($response->response);
	}

function active_sync_handle_sync($request)
	{
	$settings = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if($request["wbxml"] == null)
		$request["wbxml"] = base64_decode($settings["Sync"]);
	else
		$settings["Sync"] = base64_encode($request["wbxml"]);

	active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings);



#	$request["myxml"] = active_sync_wbxml_load($request["wbxml"]);

#	$myxml = simplexml_load_string($request["myxml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

#	$myxml->Collections->Collection[0]->Options[0]->children("AirSyncBase")->BodyPreference->Type = 99;

#	active_sync_debug($myxml->asXML());



	$request["xml"] = active_sync_wbxml_request_a($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	# S3 increase 470 by 180 until 3530 or reconnect

	$status = 1; # Success.

	# check collection
	if($status == 1)
		if(! isset($xml->Collections))
			$status = 4; # Protocol error.

	# check if HeartbeatInterval and Wait exist
	if($status == 1)
		if(isset($xml->Wait))
			if(isset($xml->HeartbeatInterval))
				$status = 4; # Protocol error.

	# check Wait
	if($status == 1)
		if(isset($xml->Wait))
			if(intval($xml->Wait) < 1) # 1 minute
				list($status, $limit) = [14, 1]; # Invalid Wait or HeartbeatInterval value.

	# check Wait
	if($status == 1)
		if(isset($xml->Wait))
			if(intval($xml->Wait) > 59) # 59 minutes
				list($status, $limit) = [14, 59]; # Invalid Wait or HeartbeatInterval value.

	# check HeartbeatInterval
	if($status == 1)
		if(isset($xml->HeartbeatInterval))
			if(intval($xml->HeartbeatInterval) < 60) # 1 minute
				list($status, $limit) = [14, 60]; # Invalid Wait or HeartbeatInterval value.

	# check HeartbeatInterval
	if($status == 1)
		if(isset($xml->HeartbeatInterval))
			if(intval($xml->HeartbeatInterval) > 3540) # 59 minutes
				list($status, $limit) = [14, 3540]; # Invalid Wait or HeartbeatInterval value.

	# check RemoteWipe
	if($status == 1)
		if(active_sync_get_need_wipe($request))
			{
			$status = 12; # The folder hierarchy has changed.

			active_sync_debug("NEED WIPE");
			}

	# check Provision
	if($status == 1)
		if(active_sync_get_need_provision($request))
			{
			$status = 12; # The folder hierarchy has changed.

			active_sync_debug("NEED PROVISION");
			}

	# check FolderSync
	if($status == 1)
		if(active_sync_get_need_folder_sync($request))
			{
			$status = 12; # The folder hierarchy has changed.

			active_sync_debug("NEED FOLDER SYNC");
			}

	# create response

	$response = new active_sync_wbxml_response();

	$response->x_switch("AirSync");

	$response->x_open("Sync");

		if($status == 14)
			$table = ["Status" => $status, "Limit" => $limit];
		else
			$table = ["Status" => $status];

		if($status != 1)
			foreach($table as $token => $value)
				$response->x_text($token, $value);

		if($status == 1)
			{
			$changed_collections = ["*" => false];
			$synckey_checked = [];

			foreach($xml->Collections->Collection as $collection)
				{
				$sync_key	= strval($collection->SyncKey);
				$collection_id	= strval($collection->CollectionId);

				$changed_collections[$collection_id] = false;
				$synckey_checked[$collection_id] = false;
				}

			################################################################################

			$response->x_open("Collections");

				while(1)
					{
					foreach($xml->Collections->Collection as $collection)
						{
						$sync_key	= strval($collection->SyncKey);
						$collection_id	= strval($collection->CollectionId);

						$settings_client = active_sync_get_settings_files_client($request["AuthUser"], $collection_id, $request["DeviceId"]);

						$settings_server = active_sync_get_settings_files_server($request["AuthUser"], $collection_id);

						$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

						# check GetChanges
						# MS-ASCMD - 2.2.3.79 GetChanges
						# if SyncKey == 0 then absence of GetChanges == 0
						# if SyncKey != 0 then absence of GetChanges == 1
						# if GetChanges is empty then a value of 1 is assumed in any case

						if(! isset($collection->GetChanges))
							$get_changes = ($sync_key == 0 ? 0 : 1);
						elseif(strval($collection->GetChanges) == "")
							$get_changes = 1;
						else
							$get_changes = intval($collection->GetChanges);

						# check WindowsSize (collection)
						if(! isset($collection->WindowSize))
							$window_size = 100;
						elseif(intval($collection->WindowSize) == 0)
							$window_size = 512;
						elseif(intval($collection->WindowSize) > 512)
							$window_size = 512;
						else
							$window_size = intval($collection->WindowSize);

						################################################################################

/*
						$status = 1;

						if($status == 1)
							if(! isset($collection->SyncKey))
								$status = 4; # Protocol error.

						if($status == 1)
							if(! isset($settings_client["SyncKey"]))
								$status = 3; # Invalid synchronization key.

						if($status == 1)
							if(isset($settings_client["SyncKey"]))
								if(isset($collection->SyncKey))
									if($settings_client["SyncKey"] != intval($collection->SyncKey))
										$status = 3; # Invalid synchronization key.

						if($status == 1)
							if(isset($settings_client["SyncKey"]))
								if(! $synckey_checked[$collection_id])
									$settings_client["SyncKey"] ++;

						if($status == 1)
							if(isset($collection->SyncKey))
								if(intval($collection->SyncKey) == 0)
									$settings_client["SyncDat"] = [];

						if($status == 1)
							if(isset($collection->SyncKey))
								if(intval($collection->SyncKey) == 0)
									$changed_collections[$collection_id] = true;

						if($status == 3)
							$settings_client["SyncKey"] = 0;

						$synckey_checked[$collection_id] = true;
*/

						# check SyncKey
						if($synckey_checked[$collection_id])
							{
							if($settings_client["SyncKey"] == 0)
								{
								$settings_client["SyncKey"] = 0;

								$status = 3; # Invalid synchronization key.
								}
							else
								{
								$settings_client["SyncKey"] ++;

								$status = 1; # Success.
								}
							}
						else
							{
							if($sync_key == 0)
								{
								$settings_client["SyncKey"] = 1;
								$settings_client["SyncDat"] = [];

								$status = 1; # Success.
								}
							elseif($sync_key != $settings_client["SyncKey"])
								{
								$settings_client["SyncKey"] = 0;
								$settings_client["SyncDat"] = [];

								$status = 3; # Invalid synchronization key.
								}
							else
								{
								$settings_client["SyncKey"] ++;

								$status = 1; # Success.
								}

							$synckey_checked[$collection_id] = true;
							}

						################################################################################

						$table = [
							"SyncKey" => $settings_client["SyncKey"],
							"CollectionId" => $collection_id,
							"Status" => $status
							];

						################################################################################

						if($sync_key == 0)
							{
							$changed_collections[$collection_id] = true;

							$response->x_switch("AirSync");

							$response->x_open("Collection");

								foreach($table as $token => $value)
									$response->x_text($token, $value);

							$response->x_close("Collection");
							}
						elseif($status != 1)
							{
							$changed_collections[$collection_id] = true;

							$response->x_switch("AirSync");

							$response->x_open("Collection");

								foreach($table as $token => $value)
									$response->x_text($token, $value);

							$response->x_close("Collection");
							}
						elseif($status == 1)
							{
							if(isset($collection->Commands))
								{
								$changed_collections[$collection_id] = true;

								$response->x_switch("AirSync");

								$response->x_open("Collection");

									foreach($table as $token => $value)
										$response->x_text($token, $value);

									$response->x_switch("AirSync");

									$response->x_open("Responses");

										# handle request for Add
										foreach($collection->Commands->Add as $add)
											{
											$client_id = strval($add->ClientId);

											$server_id = active_sync_create_guid_filename($request["AuthUser"], $collection_id);

											$response->x_switch("AirSync");

											$response->x_open("Add");

												if(! $server_id)
													$status = 16; # Server error.
												else
													$status = active_sync_handle_sync_save($add, $request["AuthUser"], $collection_id, $server_id);

												if($status == 1)
													{
													$settings_client["SyncDat"][$server_id] = filemtime(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data");

													$response->x_text("ServerId", $server_id);
													}

												foreach(["ClientId" => $client_id, "Status" => $status] as $token => $value)
													$response->x_text($token, $value);

											$response->x_close("Add");
											}

										# handle request for Change
										foreach($collection->Commands->Change as $change)
											{
											$server_id = strval($change->ServerId);

											$response->x_switch("AirSync");

											$response->x_open("Change");

												if(! isset($settings_client["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												elseif(! isset($settings_server["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												else
													$status = active_sync_handle_sync_save($change, $request["AuthUser"], $collection_id, $server_id);

												if($status == 1)
													$settings_client["SyncDat"][$server_id] = filemtime(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data");

												foreach(["ServerId" => $server_id, "Status" => $status] as $token => $value)
													$response->x_text($token, $value);

											$response->x_close("Change");
											}

										# handle request for Delete
										foreach($collection->Commands->Delete as $delete)
											{
											$server_id = strval($delete->ServerId);

											$response->x_switch("AirSync");

											$response->x_open("Delete");

												if(! isset($settings_client["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												elseif(! isset($settings_server["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												elseif(! file_exists(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data"))
													$status = 8; # Object not found.
												else
													{
													$status = 1; # Success;

													$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);
													
													if(isset($data["Attachments"]))
														foreach($data["Attachments"] as $attachment)
															{
#															$file = __DIR__ . "/" . $reuest["AuthUser"] . "/.files/" . $attachment["AirSyncBase"]["FileReference"];

#															if(isset($attachment["AirSyncBase"]["FileReference"]))
#																$status = 8; # Object not found.
#															elseif(! file_exists(ACTIVE_SYNC_ATT_DIR . "/" . $attachment["AirSyncBase"]["FileReference"]))
#																$status = 8; # Object not found.
#															elseif(!unlink(__DIR__ . "/" . $reuest["AuthUser"] . "/.files/" . $attachment["AirSyncBase"]["FileReference"]))
#																$status = 5; # Server error.

#															if($status != 1)
#																break;
															}
													
													if(! unlink(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data"))
														$status = 5; # Server error.
													}

												if($status == 1)
													unset($settings_client["SyncDat"][$server_id]);

												foreach(["ServerId" => $server_id, "Status" => $status] as $token => $value)
													$response->x_text($token, $value);

											$response->x_close("Delete");
											}

										# handle request for Fetch
										foreach($collection->Commands->Fetch as $fetch)
											{
											$server_id = strval($fetch->ServerId);

											$response->x_switch("AirSync");

											$response->x_open("Fetch");

												if(! isset($settings_client["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												elseif(! isset($settings_server["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												else
													{
													$status = 1; # Success.

													active_sync_handle_sync_send($response, $request["AuthUser"], $collection_id, $server_id, $collection);
													}

												# wrong order? correct order: ServerId, Status, ApplicationData
												# wrong order? correct order: ApplicationData, ServerId, Status

												foreach(["ServerId" => $server_id, "Status" => $status] as $token => $value)
													$response->x_text($token, $value);

											$response->x_close("Fetch");
											}

									$response->x_close("Responses");

								$response->x_close("Collection");
								} # if(isset($collection->Commands))

							# get the changes
							if($get_changes == 1)
								{
								$settings_server = active_sync_get_settings_files_server($request["AuthUser"], $collection_id);

								$jobs = [];

								foreach($settings_server["SyncDat"] as $server_id => $server_timestamp)
									{
									$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

									if(! isset($data["AirSync"]["Class"]))
										$data["AirSync"]["Class"] = $default_class;

									# check options
									$option_filter_type = ACTIVE_SYNC_FILTER_ALL;
									$process_sms = true; # imagine we have sms

									if(isset($collection->Options))
										foreach($collection->Options as $options)
											{
											$option_class = $default_class;

											if(isset($options->Class))
												$option_class = strval($options->Class); # only occurs on email/sms

											if($option_class != $data["AirSync"]["Class"])
												continue;

											if(isset($options->FilterType))
												$option_filter_type = intval($options->FilterType);

											$process_sms = false;
											}

									# sync SMS
									if($process_sms)
										{
										if(! isset($settings_client["SyncDat"][$server_id]))
											$settings_client["SyncDat"][$server_id] = "*";
										elseif($settings_client["SyncDat"][$server_id] != "*")
											if($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
												$jobs["SoftDelete"][] = $server_id;
											else
												$jobs["SoftDelete"][] = $server_id;

										$option_filter_type = 9;
										}

									# sync all
									if($option_filter_type == ACTIVE_SYNC_FILTER_ALL)
										{
										if(! isset($settings_client["SyncDat"][$server_id]))
											$jobs["Add"][] = $server_id;
										elseif($settings_client["SyncDat"][$server_id] == "*")
											$jobs["Add"][] = $server_id;
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											$jobs["Change"][] = $server_id;
										}

									# sync ...
									if(($option_filter_type > 0) && ($option_filter_type < 8))
										{
										$stat_filter = ["now", "- 1 day", "- 3 day", "- 1 week", "- 2 week", "- 1 month", "- 3 month", "- 6 month", "now"];

										$stat_filter = strtotime($stat_filter[$option_filter_type]);

										# does FilterType only occur on Email/SMS as DateReceived ?

										if($default_class == "Calendar")
											$data_timestamp = strtotime($data["Calendar"]["EndTime"]);

										if($default_class == "Email")
											$data_timestamp = strtotime($data["Email"]["DateReceived"]);

										if($default_class == "Notes")
											$data_timestamp = strtotime($data["Notes"]["LastModifiedDate"]);

										if($default_class == "Tasks")
											$data_timestamp = strtotime($data["Tasks"]["DateCompleted"]);


										if(! isset($settings_client["SyncDat"][$server_id]))
											{
											if($data_timestamp < $stat_filter)
												$settings_client["SyncDat"][$server_id] = "*";
											else
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] == "*")
											{
											if($data_timestamp < $stat_filter)
												{
												#
												}
											else
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											{
											if($data_timestamp < $stat_filter)
												$jobs["SoftDelete"][] = $server_id;
											else
												$jobs["Change"][] = $server_id;
											}
										else
											{
											if($data_timestamp < $stat_filter)
												$jobs["SoftDelete"][] = $server_id;
											}
										}

									# sync incomplete (tasks only)
									if($option_filter_type == ACTIVE_SYNC_FILTER_INCOMPLETE)
										{
										if(! isset($settings_client["SyncDat"][$server_id]))
											{
											if($data["Tasks"]["Complete"] != 1)
												$jobs["Add"][] = $server_id;
											else
												$settings_client["SyncDat"][$server_id] = "*";
											}
										elseif($settings_client["SyncDat"][$server_id] == "*")
											{
											if($data["Tasks"]["Complete"] != 1)
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											{
											if($data["Tasks"]["Complete"] != 1)
												$jobs["Change"][] = $server_id;
											else
												$jobs["SoftDelete"][] = $server_id;
											}
										}
									}

								# check for to Delete
								foreach($settings_client["SyncDat"] as $server_id => $client_timestamp)
									if(! isset($settings_server["SyncDat"][$server_id]))
										$jobs["Delete"][] = $server_id;

								# check for elements sended by server
								if(count($jobs) > 0)
									{
									$changed_collections[$collection_id] = true;

									$response->x_switch("AirSync");

									$response->x_open("Collection");

										foreach($table as $token => $value)
											$response->x_text($token, $value);

										$response->x_switch("AirSync");

										$response->x_open("Commands");

											$estimate = 0;

											foreach(["Add", "Change", "Delete", "SoftDelete"] as $command)
												if(isset($jobs[$command]))
													foreach($jobs[$command] as $server_id)
														{
														if($estimate == $window_size)
															break;

														$estimate ++;

														$response->x_switch("AirSync");

														$response->x_open($command);
															$response->x_text("ServerId", $server_id);

															if($command == "Add" || $command == "Change")
																{
																$settings_client["SyncDat"][$server_id] = $settings_server["SyncDat"][$server_id];

																active_sync_handle_sync_send($response, $request["AuthUser"], $collection_id, $server_id, $collection);
																}

															if($command == "Delete")
																{
																unset($settings_server["SyncDat"][$server_id]);
																unset($settings_client["SyncDat"][$server_id]);
																}

															if($command == "SoftDelete")
																{
																$settings_server["SyncDat"][$server_id] = "*";
																$settings_client["SyncDat"][$server_id] = "*";
																}

														$response->x_close($command);
														}

										$response->x_close("Commands");

										$estimate = 0;

										foreach(["Add", "Change", "Delete", "SoftDelete"] as $command)
											if(isset($jobs[$command]))
												$estimate += count($jobs[$command]);

										if($estimate > $window_size)
											{
											$response->x_switch("AirSync");

											$response->x_open("MoreAvailable", false);
											}

									$response->x_close("Collection");
									} # if(count($jobs) > 0)
								} # if($get_changes == 0)
							} # elseif($status == 1)

						# continue if no changes detected
						if(! $changed_collections[$collection_id])
							continue;

						active_sync_put_settings_sync_client($request["AuthUser"], $collection_id, $request["DeviceId"], $settings_client);

						$changed_collections["*"] = true;
						} # foreach($xml->Collections->Collection as $collection)

					# exit if changes were detected
					if($changed_collections["*"])
						break;

					if(! isset($xml->Wait))
						if(! isset($xml->HeartbeatInterval))
							break;

					if(isset($xml->Wait))
						if($_SERVER["REQUEST_TIME"] + (intval($xml->Wait) * 60) < time())
							break;

					if(isset($xml->HeartbeatInterval))
						if($_SERVER["REQUEST_TIME"] + $xml->HeartbeatInterval < time())
							break;

					sleep(ACTIVE_SYNC_SLEEP);
					} # while(1)

				# return empty response if no changes at all.
				if(! $changed_collections["*"])
					return("");

				foreach($xml->Collections->Collection as $collection)
					{
					$sync_key	= strval($collection->SyncKey);
					$collection_id	= strval($collection->CollectionId);

					if($changed_collections[$collection_id])
						continue;

					$settings_client = active_sync_get_settings_files_client($request["AuthUser"], $collection_id, $request["DeviceId"]);

					$settings_client["SyncKey"] ++;

					active_sync_put_settings_sync_client($request["AuthUser"], $collection_id, $request["DeviceId"], $settings);

					$response->x_switch("AirSync");

					$response->x_open("Collection");

						$table = [
							"SyncKey" => $settings_client["SyncKey"],
							"CollectionId" => $collection_id,
							"Status" => 1
							];

						foreach($table as $token => $value)
							$response->x_text($token, $value);

					$response->x_close("Collection");
					}

			$response->x_close("Collections");
			} # if($status == 1)

	$response->x_close("Sync");

	return($response->response);
	}

function active_sync_handle_sync_save($xml, $user, $collection_id, $server_id)
	{
	$class = active_sync_get_class_by_collection_id($user, $collection_id);

	if($class == "Email")
		{
		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		foreach(["Class" => "SMS"] as $token => $value)
			if(isset($xml->$token))
				$data["AirSync"][$token] = strval($xml->$token);

#		foreach(["UmCallerID", "UmUserNotes"] as $token)
#			if(isset($xml->ApplicationData->$token))
#				$data["Email2"][$token] = strval($xml->ApplicationData->$token);
#				$data["Attachments"][]["Email2"][$token] = $data["Email2"][$token];
		}
	else
		$data = [];

	$table = [
		"Contacts" => [
			"Contacts" => active_sync_get_default_contacts(),
			"Contacts2" => active_sync_get_default_contacts2()
			],
		"Calendar" => [
			"Calendar" => active_sync_get_default_calendar()
			],
		"Email" => [
			"Email" => active_sync_get_default_email(),
			"Email2" => active_sync_get_default_email2()
			],
		"Notes" => [
			"Notes" => active_sync_get_default_notes()
			],
		"Tasks" => [
			"Tasks" => active_sync_get_default_tasks()
			]
		];

	foreach($table[$class] as $codepage => $fields)
		foreach($fields as $token => $value)
			if(isset($xml->ApplicationData->$token))
				$data[$codepage][$token] = strval($xml->ApplicationData->$token);

	if(isset($xml->ApplicationData->Attendees))
		foreach($xml->ApplicationData->Attendees->Attendee as $attendee)
			{
			$a = [];

			foreach(active_sync_get_default_attendee() as $token => $value)
				if(isset($attendee->$token))
					$a[$token] = strval($attendee->$token);

			$data["Attendees"][] = $a;
			}

	if(isset($xml->ApplicationData->Body))
		foreach($xml->ApplicationData->Body as $body)
			{
			$b = [];

			foreach(active_sync_get_default_body() as $token => $value)
				if(isset($body->$token))
					$b[$token] = strval($body->$token);

			if(isset($b["Data"]))
				if(strlen($b["Data"]))
					$data["Body"][] = $b;
			}

	if(isset($xml->ApplicationData->Categories))
		if($xml->ApplicationData->Categories->Category)
			foreach($xml->ApplicationData->Categories->Category as $category)
				$data["Categories"][] = strval($category);

	if(isset($xml->ApplicationData->Children))
		if($xml->ApplicationData->Children->Child)
			foreach($xml->ApplicationData->Children->Child as $child)
				$data["Children"][] = strval($child);

	if(isset($xml->ApplicationData->Flag))
		{
		$data["Flag"] = []; # force empty flag

		foreach(active_sync_get_default_flag($class) as $token => $value)
			if(isset($xml->ApplicationData->Flag->$token))
				$data["Flag"][$class][$token] = strval($xml->ApplicationData->Flag->$token);
		}

	if(isset($xml->ApplicationData->Recurrence))
		foreach(active_sync_get_default_recurrence() as $token => $value)
			if(isset($xml->ApplicationData->Recurrence->$token))
				$data["Recurrence"][$token] = strval($xml->ApplicationData->Recurrence->$token);

#	if(isset($data["AirSync"]))
#		if(isset($data["AirSync"]["Class"]))
#			if($data["AirSync"]["Class"] == "SMS")
#				if(strval($xml->ApplicationData->Flag->Read) == 1)
#					$data["Flag"]["Email"]["Read"] = 1;

	return(active_sync_put_settings_data($user, $collection_id, $server_id, $data) ? 1 : 16);
	}

function active_sync_handle_sync_send(& $response, $user, $collection_id, $server_id, $collection)
	{
	$class = active_sync_get_class_by_collection_id($user, $collection_id);

	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	if(isset($data["AirSync"]))
		{
		$response->x_switch("AirSync");

		foreach($data["AirSync"] as $token => $value)
			$response->x_text($token, $value);
		}

	$table = [
#		"AirSyncBase" => [
#			"NativeBodyType" => 0
#			],
		"Calendar" => [
			"Calendar" => active_sync_get_default_calendar()
			],
		"Contacts" => [
			"Contacts" => active_sync_get_default_contacts(),
			"Contacts2" => active_sync_get_default_contacts2()
			],
		"Email" => [
			"Email" => active_sync_get_default_email(),
			"Email2" => active_sync_get_default_email2()
			],
		"Notes" => [
			"Notes" => active_sync_get_default_notes()
			],
		"Tasks" => [
			"Tasks" => active_sync_get_default_tasks()
			]
		];

	$response->x_switch("AirSync");

	$response->x_open("ApplicationData");

		foreach($table[$class] as $codepage => $fields)
			{
			if(! isset($data[$codepage]))
				continue;

			$response->x_switch($codepage);

			foreach($fields as $token => $null)
				{
				if(! isset($data[$codepage][$token]))
					continue;

				if($codepage == "Calendar" && $token == "AllDayEvent")
					{
					$response->x_open($token, false);

					continue;
					}

				if(! strlen($data[$codepage][$token]))
					{
					$response->x_open($token, false);

					continue;
					}

				# The ... element is defined as an element in the Calendar namespace.
				# The value of this element is a string data type, represented as a
				# Compact DateTime ([MS-ASDTYPE] section 2.7.2).

				if(in_array($token, ["DtStamp", "StartTime", "EndTime"]))
					$data[$codepage][$token] = date("Ymd\THis\Z", strtotime($data[$codepage][$token]));

				# The value of this element is a datetime data type in Coordinated Universal
				# Time (UTC) format, as specified in [MS-ASDTYPE] section 2.3.

				if(in_array($token, ["Aniversary", "Birthday"]))
					$data[$codepage][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data[$codepage][$token]));

				# The value of the * element is a string data type represented as a
				# Compact DateTime ([MS-ASDTYPE] section 2.7.2).

				if(in_array($token, ["DateCompleted", "DueDate", "OrdinalDate", "ReminderTime", "Start", "StartDate", "UtcDueDate", "UtcStartDate"]))
					$data[$codepage][$token] = date("Y-m-d\TH:i:s.000\Z", strtotime($data[$codepage][$token]));

				$response->x_open($token);
					$response->x_print($data[$codepage][$token]);
				$response->x_close($token);
				}
			}

		if(isset($data["Attachments"]))
			{
			$response->x_switch("AirSyncBase");

			$response->x_open("Attachments");

				foreach($data["Attachments"] as $attachment)
					{
					$response->x_switch("AirSyncBase");

					$response->x_open("Attachment");

						foreach(["AirSyncBase", "Email2"] as $codepage)
							{
							if(! isset($attachment[$codepage]))
								continue;

							$response->x_switch($codepage);

							foreach($attachment[$codepage] as $token => $null)
								$response->x_text($token, $attachment[$codepage][$token]);
							}

					$response->x_close("Attachment");
					}

			$response->x_close("Attachments");
			}

		if(isset($data["Attendees"]))
			{
			$response->x_switch($class);

			$response->x_open("Attendees");

				foreach($data["Attendees"] as $attendee)
					{
					$response->x_open("Attendee");

						foreach(active_sync_get_default_attendee() as $token => $null)
							if(isset($attendee[$token]))
								$response->x_text($token, $attendee[$token]);

					$response->x_close("Attendee");
					}

			$response->x_close("Attendees");
			}

		if(isset($data["Categories"]))
			{
			$response->x_switch($class);

			$response->x_open("Categories");

				foreach($data["Categories"] as $category)
					$response->x_text("Category", $category);

			$response->x_close("Categories");
			}

		if(isset($data["Children"]))
			{
			$response->x_switch($class);

			$response->x_open("Children");

				foreach($data["Children"] as $child)
					$response->x_text("Child", $child);

			$response->x_close("Children");
			}

		if(isset($data["Flag"]))
			if(count($data["Flag"]) == 0)
				{
				$response->x_switch($class);

				$response->x_open("Flag", false);
				}
			else
				{
				$response->x_switch($class);

				$response->x_open("Flag");

					if(isset($data["Flag"][$class]))
						{
						$response->x_switch($class);

						foreach($data["Flag"][$class] as $token => $value)
							$response->x_text($token, $value);
						}

				$response->x_close("Flag");
				}

		if(isset($data["Meeting"]))
			{
			$response->x_switch($class);

			$response->x_open("MeetingRequest");

				foreach(["Email", "Email2", "Calendar"] as $codepage)
					{
					if(! isset($data["Meeting"][$codepage]))
						continue;

					$response->x_switch($codepage);

					foreach($data["Meeting"][$codepage] as $token => $value)
						$response->x_text($token, $value);
					}

			$response->x_close("MeetingRequest");
			}

		if(isset($data["Recurrence"]))
			{
			$response->x_switch($class);

			$response->x_open("Recurrence");

				foreach(active_sync_get_default_recurrence() as $token => $null)
					if(isset($data["Recurrence"][$token]))
						$response->x_text($token, $data["Recurrence"][$token]);

			$response->x_close("Recurrence");
			}

		if(isset($data["RightsManagement"]))
			{
			$response->x_switch("RightsManagement");

			$response->x_open("RightsManagementLicense");

				foreach(active_sync_get_default_rights_management() as $token => $null)
					if(isset($data["RightsManagement"][$token]))
						$response->x_text($token, $data["RightsManagement"][$token]);

			$response->x_close("RightsManagementLicense");
			}

		if(isset($data["Body"]))
			if(isset($collection->Options))
				foreach($collection->Options as $options)
					{
					if(isset($options->Class))
						if(isset($data["AirSync"]["Class"]))
							if(strval($options->Class) != $data["AirSync"]["Class"])
								continue;

					foreach($options->BodyPreference as $preference)
						{
						foreach($data["Body"] as $body) # !!!
							{
							if(! isset($body["Type"]))
								continue;

							if($body["Type"] != intval($preference->Type))
								continue;

							$response->x_switch("AirSyncBase");

							$response->x_open("Body");

								if(isset($preference["Preview"]))
									foreach($data["Body"] as $preview) # !!!
										{
										if(! isset($preview["Type"]))
											continue;

										if($preview["Type"] != 1)
											continue;

										$response->x_open("Preview");
											$response->x_print(substr($preview["Data"], 0, intval($preference->Preview)));
										$response->x_close("Preview");
										}

								if(isset($preference->TruncationSize))
									if(isset($body["EstimatedDataSize"]))
										if(intval($preference->TruncationSize) < $body["EstimatedDataSize"])
											{
											$body["Data"] = substr($body["Data"], 0, intval($preference->TruncationSize));

											$response->x_text("Truncated", 1);
											}

								foreach($body as $token => $value)
									$response->x_text($token, $value);

							$response->x_close("Body");
							}
						}
					}

	$response->x_close("ApplicationData");
	}

function active_sync_handle_validate_cert($request)
	{
	$request["xml"] = active_sync_wbxml_load($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"]);

	if(isset($xml->CheckCRL))
		$CheckCRL = strval($xml->CheckCRL);
	else
		$CheckCRL = 0;

	$states = [];

	if(isset($xml->CertificateChain))
		foreach($xml->CertificateChain->Certificate as $Certificate)
			{
			$state = 1; # Success.

			$states[] = $state;
			}

	if(isset($xml->Certificates))
		foreach($xml->Certificates->Certificate as $Certificate)
			{
			$cert = chunk_split($Certificate, 64);

			$cert = ["-----BEGIN CERTIFICATE-----", "\n", $cert, "-----END CERTIFICATE-----"];

			$cert = implode("", $cert);

			$data = openssl_x509_parse($cert);

			$state = 1; # Success.

			if(time() < $data["validFrom_time_t"])
				$state = 7; # The digital ID used to sign the message has expired or is not yet valid.

			if(time() > $data["validTo_time_t"])
				$state = 7; # The digital ID used to sign the message has expired or is not yet valid.

			foreach($data["purposes"] as $purpose)
				{
				if($purpose[2] != "smimesign")
					continue;

				if($purpose[0] == 1)
					continue;

				$state = 6;

				break;
				}

			if($CheckCRL == 0)
				{
				}
			elseif(! isset($data["extensions"]["crlDistributionPoints"]))
				$state = 14; # The validity of the digital ID cannot be determined because the server that provides this information cannot be contacted.
			else
				{
				exec("echo \"" . $cert . "\" | openssl x509 -serial -noout", $output, $var_return);

				$serial = str_replace("serial=", "", $output[0]);

				list($type, $address) = explode(":", $data["extensions"]["crlDistributionPoints"], 2);

				$address = trim($address);

				$data = file_get_contents($address);

				exec("echo \"" . $data . "\" | openssl crl -text -noout", $output, $var_return);

				foreach($output as $line)
					{
					if($line == "    Serial Number: " . $serial)
						{
						$state = 13; # The digital ID used to sign this message has been revoked. This can indicate that the issuer of the digital ID no longer trusts the sender, the digital ID was reported stolen, or the digital ID was compromised.

						break;
						}
					}
				}

			$states[] = $state;
			}

	$response = new active_sync_wbxml_response();

	$response->x_switch("ValidateCerts");

	$response->x_open("ValidateCert");
		$response->x_text("Status", 1);

		foreach($states as $state)
			{
			$response->x_open("Certificate");
				$response->x_text("Status", $state);
			$response->x_close("Certificate");
			}

	$response->x_close("ValidateCert");

	return($response->response);
	}
?>
