<?
function active_sync_handle_sync($request)
	{
	########################################################################
	# get settings
	########################################################################

	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	################################################################################
	# MS-ASCMD - 4.5.10 Empty Sync Request and Response
	################################################################################

	if($request["wbxml"] == null)
		$request["wbxml"] = base64_decode($settings["Sync"]);
	else
		$settings["Sync"] = base64_encode($request["wbxml"]);

	########################################################################
	# save settings
	########################################################################

	active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings);

	########################################################################
	# parse request
	# do not use it here anymore, check request earlier
	########################################################################

	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	################################################################################
	# check HeartbeatInterval
	################################################################################

	if(isset($xml->HeartbeatInterval) === false)
		{
		$heartbeat_interval = 0;
		$limit = 0;
		}
	elseif(intval($xml->HeartbeatInterval) < 60) # 1 minute
		{
		$heartbeat_interval = intval($xml->HeartbeatInterval);
		$limit = 60;
		}
	elseif(intval($xml->HeartbeatInterval) > 3540) # 59 minutes
		{
		$heartbeat_interval = intval($xml->HeartbeatInterval);
		$limit = 3540;
		}
	else
		{
		$heartbeat_interval = intval($xml->HeartbeatInterval);
		$limit = 0;
		}

	# S3 increase 470 by 180 until 3530 until reconnect

	################################################################################
	# check Wait
	################################################################################

	if(isset($xml->Wait) === false)
		{
		$wait = 0;
		$limit = 0;
		}
	elseif(intval($xml->Wait) < 1) # 1 minutes
		{
		$wait = intval($xml->Wait);
		$limit = 1;
		}
	elseif(intval($xml->Wait) > 59) # 59 minutes
		{
		$wait = intval($xml->Wait);
		$limit = 59;
		}
	else
		{
		$wait = intval($xml->Wait);
		$limit = 0;
		}

	################################################################################
	# check WindowSize (global)
	################################################################################

	if(isset($xml->WindowSize) === false)
		$window_size_global = 100;
	elseif(intval($xml->WindowSize) == 0)
		$window_size_global = 512;
	elseif(intval($xml->WindowSize) > 512)
		$window_size_global = 512;
	else
		$window_size_global = intval($xml->WindowSize);

	################################################################################
	# check if Collections exist
	################################################################################

	if(isset($xml->Collections) === false)
		$status = 4; # Protocol error.
	else
		$status = 1; # Success.

	################################################################################
	# check if HeartbeatInterval and Wait exist
	################################################################################

	if($status == 1)
		if((($wait * 60) != 0) && (($heartbeat_interval * 1) != 0))
			$status = 4; # Protocol error.

	################################################################################
	# check Wait
	################################################################################

	if($status == 1)
		if(($limit != 0) && (($wait * 60) != 0))
			$status = 14; # Invalid Wait or HeartbeatInterval value.

	################################################################################
	# check HeartbeatInterval
	################################################################################

	if($status == 1)
		if(($limit != 0) && (($heartbeat_interval * 1) != 0))
			$status = 14; # Invalid Wait or HeartbeatInterval value.

	################################################################################
	# check RemoteWipe
	################################################################################

	if($status == 1)
		if(active_sync_get_need_wipe($request) != 0)
			$status = 12; # The folder hierarchy has changed.

	################################################################################
	# check Provision
	################################################################################

	if($status == 1)
		if(active_sync_get_need_provision($request) != 0)
			$status = 12; # The folder hierarchy has changed.

	################################################################################
	# check FolderSync
	################################################################################

	if($status == 1)
		if(active_sync_get_need_folder_sync($request) != 0)
			$status = 12; # The folder hierarchy has changed.

	################################################################################
	# create response
	################################################################################

	$response = new active_sync_wbxml_response();

	$response->x_switch("AirSync");

	$response->x_open("Sync");

		################################################################################
		# return global Status
		################################################################################

		foreach(($status == 1 ? array() : ($status == 14 ? array("Status" => $status, "Limit" => $limit) : array("Status" => $status))) as $token => $value)
			{
			$response->x_open($token);
				$response->x_print($value);
			$response->x_close($token);
			}

		################################################################################
		# continue process if no error is found (global)
		################################################################################

		if($status == 1)
			{
			################################################################################
			# process can be continued (global)
			################################################################################

			$timeout = microtime(true);

			################################################################################
			# init marker for changed Collections
			################################################################################

			$changed_collections = array("*" => 0);
			$synckey_checked = array();

			foreach($xml->Collections->Collection as $collection)
				{
				$sync_key	= strval($collection->SyncKey);
				$collection_id	= strval($collection->CollectionId);

				$changed_collections[$collection_id] = 0;
				$synckey_checked[$collection_id] = 0;
				}

			################################################################################

			$response->x_open("Collections");

				while(1)
					{
					foreach($xml->Collections->Collection as $collection)
						{
						$sync_key	= strval($collection->SyncKey);
						$collection_id	= strval($collection->CollectionId);

						################################################################################
						# get SyncState of CollectionId
						################################################################################

						$settings_client = active_sync_get_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"]);

						################################################################################
						# get SyncState of CollectionId (SyncKey, SyncDat)
						################################################################################

						$settings_server = active_sync_get_settings_sync($request["AuthUser"], $collection_id, "");

						################################################################################
						# get default Class of CollectionId
						################################################################################

						$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

						################################################################################
						# check GetChanges
						################################################################################

						# MS-ASCMD - 2.2.3.79 GetChanges

						# !!! read info about GetChanges and SyncKey !!!

						# if SyncKey == 0 then absence of GetChanges == 0
						# if SyncKey != 0 then absence of GetChanges == 1
						# if GetChanges is empty then a value of 1 is assumed in any case

						if(isset($collection->GetChanges) === false)
							$get_changes = ($sync_key == 0 ? 0 : 1);
						elseif(strval($collection->GetChanges) == "")
							$get_changes = 1;
						else
							$get_changes = strval($collection->GetChanges);

						################################################################################
						# check WindowsSize (collection)
						################################################################################

						if(isset($collection->WindowSize) === false)
							$window_size = 100;
						elseif(intval($collection->WindowSize) == 0)
							$window_size = 512;
						elseif(intval($collection->WindowSize) > 512)
							$window_size = 512;
						else
							$window_size = intval($collection->WindowSize);

						################################################################################
						# check SyncKey
						################################################################################

						if($synckey_checked[$collection_id] == 1)
							{
							if($settings_client["SyncKey"] == 0)
								{
								$settings_client["SyncKey"] = 0;

								$status = 3; # Invalid synchronization key.
								}
							else
								{
								$settings_client["SyncKey"] = $settings_client["SyncKey"] + 1;

								$status = 1; # Success.
								}
							}
						else
							{
							if($sync_key == 0)
								{
								$settings_client["SyncKey"] = 1;
								$settings_client["SyncDat"] = array();

								$status = 1; # Success.
								}
							elseif($sync_key != $settings_client["SyncKey"])
								{
								$settings_client["SyncKey"] = 0;
								$settings_client["SyncDat"] = array();

								$status = 3; # Invalid synchronization key.
								}
							else
								{
								$settings_client["SyncKey"] = $settings_client["SyncKey"] + 1;

								$status = 1; # Success.
								}

							$synckey_checked[$collection_id] = 1;
							}

						################################################################################
						# continue process if no error is found (collection)
						################################################################################

						if($sync_key == 0)
							{
							################################################################################
							# process can not be continued (collection)
							################################################################################

							$response->x_switch("AirSync");

							$response->x_open("Collection");

								foreach(array("SyncKey" => $settings_client["SyncKey"], "CollectionId" => $collection_id, "Status" => $status) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

							$response->x_close("Collection");

							################################################################################
							# mark CollectionId as changed
							################################################################################

							$changed_collections[$collection_id] = 1;
							}
						elseif($status == 1)
							{
							################################################################################
							# check for elements sended by device
							################################################################################

							if(isset($collection->Commands) === true)
								{
								$response->x_switch("AirSync");

								$response->x_open("Collection");

									foreach(array("SyncKey" => $settings_client["SyncKey"], "CollectionId" => $collection_id, "Status" => $status) as $token => $value)
										{
										$response->x_open($token);
											$response->x_print($value);
										$response->x_close($token);
										}

									$response->x_switch("AirSync");

									$response->x_open("Responses");

										################################################################################
										# handle request for Add
										################################################################################

										foreach($collection->Commands->Add as $add)
											{
											$client_id = strval($add->ClientId);

											$server_id = active_sync_create_guid_filename($request["AuthUser"], $collection_id);

											$response->x_switch("AirSync");

											$response->x_open("Add");

												if($server_id == 0)
													$status = 5; # Server error.
												elseif($default_class == "")
													$status = 5; # Server error.
												elseif(function_exists("active_sync_handle_sync_save_" . strtolower($default_class)) === false)
													$status = 5; # Server error.

												else
													{
													$function = "active_sync_handle_sync_save_" . strtolower($default_class);

													$status = $function($add, $request["AuthUser"], $collection_id, $server_id);
													}

												if($status == 1)
													{
													$settings_client["SyncDat"][$server_id] = filemtime(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data");

													$response->x_open("ServerId");
														$response->x_print($server_id);
													$response->x_close("ServerId");
													}

												foreach(array("ClientId" => $client_id, "Status" => $status) as $token => $value)
													{
													$response->x_open($token);
														$response->x_print($value);
													$response->x_close($token);
													}

											$response->x_close("Add");
											}

										################################################################################
										# handle request for Change
										################################################################################

										foreach($collection->Commands->Change as $change)
											{
											$server_id = strval($change->ServerId);

											$response->x_switch("AirSync");

											$response->x_open("Change");

active_sync_debug("s: " . $settings_server["SyncDat"][$server_id]);
active_sync_debug("c: " . $settings_client["SyncDat"][$server_id]);
active_sync_debug("x: " . filemtime(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data"));

												if(isset($settings_client["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(isset($settings_server["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(function_exists("active_sync_handle_sync_save_" . strtolower($default_class)) === false)
													$status = 5; # Server error.
												else
													{
													$function = "active_sync_handle_sync_save_" . strtolower($default_class);

													$status = $function($change, $request["AuthUser"], $collection_id, $server_id);
													}

												if($status == 1)
													$settings_client["SyncDat"][$server_id] = filemtime(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data");

												foreach(array("ServerId" => $server_id, "Status" => $status) as $token => $value)
													{
													$response->x_open($token);
														$response->x_print($value);
													$response->x_close($token);
													}

active_sync_debug("s: " . $settings_server["SyncDat"][$server_id]);
active_sync_debug("c: " . $settings_client["SyncDat"][$server_id]);
active_sync_debug("x: " . filemtime(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data"));

											$response->x_close("Change");
											}

										################################################################################
										# handle request for Delete
										################################################################################

										foreach($collection->Commands->Delete as $delete)
											{
											$server_id = strval($delete->ServerId);

											$response->x_switch("AirSync");

											$response->x_open("Delete");

												if(isset($settings_client["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(isset($settings_server["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(file_exists(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data") === false)
													$status = 8; # Object not found.
												else
													{
													################################################################################
													# set status
													################################################################################
													
													$status = 1; # Success;

													################################################################################
													# get data from file
													################################################################################
													
													$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);
													
													################################################################################
													# check for Attachments
													################################################################################
													
													if(isset($data["Attachments"]) === true)
														{
														################################################################################
														# check each Attachment
														################################################################################
													
														foreach($data["Attachments"] as $attachment)
															{
															################################################################################
															# skip if FileReference do not exist
															################################################################################
													
															if(isset($attachment["AirSyncBase"]["FileReference"]) === false)
																{
																$status = 8; # Object not found.
													
																break;
																}
													
															################################################################################
															# skip if file given by FileReference do not exist
															################################################################################
													
															if(file_exists(ATT_DIR . "/" . $attachment["AirSyncBase"]["FileReference"]) === false)
																{
																$status = 8; # Object not found.
													
																break;
																}
													
															################################################################################
															# skip if file given by FileReference can not be deleted
															################################################################################
													
															if(unlink(ATT_DIR . "/" . $attachment["AirSyncBase"]["FileReference"]) === false)
																{
																$status = 5; # Server error.
													
																break;
																}
															}
														}
													
													################################################################################
													# skip if file given by CollectionId and ServerId can not be deleted
													################################################################################
													
													if(unlink(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data") === false)
														$status = 5; # Server error.
													}

												if($status == 1)
													{
													unset($settings_client["SyncDat"][$server_id]);
													}

												foreach(array("ServerId" => $server_id, "Status" => $status) as $token => $value)
													{
													$response->x_open($token);
														$response->x_print($value);
													$response->x_close($token);
													}

											$response->x_close("Delete");
											}

										################################################################################
										# handle request for Fetch
										################################################################################

										foreach($collection->Commands->Fetch as $fetch)
											{
											$server_id = strval($fetch->ServerId);

											$response->x_switch("AirSync");

											$response->x_open("Fetch");

												if(isset($settings_client["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(isset($settings_server["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(function_exists("active_sync_handle_sync_send_" . strtolower($default_class)) === false)
													$status = 5; # Server error.
												else
													{
													$status = 1; # Success.

													$function = "active_sync_handle_sync_send_" . strtolower($default_class);

													$function($response, $request["AuthUser"], $collection_id, $server_id, $collection);
													}

												# wrong order? correct order: ServerId, Status, ApplicationData
												# wrong order? correct order: ApplicationData, ServerId, Status

												foreach(array("ServerId" => $server_id, "Status" => $status) as $token => $value)
													{
													$response->x_open($token);
														$response->x_print($value);
													$response->x_close($token);
													}

											$response->x_close("Fetch");
											}

									$response->x_close("Responses");

								$response->x_close("Collection");

								################################################################################
								# mark CollectionId as changed
								################################################################################

								$changed_collections[$collection_id] = 1;
								} # if(isset($collection->Commands) === true)

							################################################################################
							# get the changes
							# !!! read info about GetChanges and SyncKey !!!
							################################################################################

							if($get_changes == 1)
								{
								################################################################################
								# init jobs
								################################################################################

								$jobs = array();

								################################################################################
								# get SyncState of CollectionId (SyncKey, SyncDat)
								# get it once again, maybe some data has been written
								################################################################################

								$settings_server = active_sync_get_settings_sync($request["AuthUser"], $collection_id, "");

								################################################################################
								# check each file the server got
								################################################################################

								foreach($settings_server["SyncDat"] as $server_id => $server_timestamp)
									{
									################################################################################
									# get content of file
									################################################################################

									$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

									################################################################################
									# check class of file
									################################################################################

									if(isset($data["AirSync"]["Class"]) === false)
										$data["AirSync"]["Class"] = $default_class;

									################################################################################
									# check options
									# inbox contains email and sms. FilterType can differ. find the right one
									################################################################################

									$option_class = $default_class;
									$option_filter_type = 0;
									$process_sms = 1; # imagine we have sms

									if(isset($collection->Options) === true)
										{
										foreach($collection->Options as $options)
											{
											################################################################################
											# check Class of Option
											################################################################################

											if(isset($options->Class) === false)
												$option_class = $default_class;
											else
												$option_class = strval($options->Class); # only occurs on email/sms

											################################################################################
											# skip if class of option do not match class of data
											################################################################################

											if($option_class != $data["AirSync"]["Class"])
												continue;

											################################################################################
											# check FilterType of Option
											################################################################################

											if(isset($options->FilterType) === false)
												$option_filter_type = 0;
											else
												$option_filter_type = intval($options->FilterType);

											################################################################################
											# mark Class as found in Option
											# SMS never got an option
											# what is going on here?
											################################################################################

											$process_sms = 0;
											}
										}

									################################################################################
									# sync SMS
									################################################################################

									if($process_sms == 1)
										{
										if(isset($settings_client["SyncDat"][$server_id]) === false)
											$settings_client["SyncDat"][$server_id] = "*";
										elseif($settings_client["SyncDat"][$server_id] == "*")
											{
											# file is known as SoftDelete
											}
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											$jobs["SoftDelete"][] = $server_id;
										else
											$jobs["SoftDelete"][] = $server_id;

										################################################################################
										# 0 (all), 1 - 7, 8 (incomplete), ... so ... LIE
										################################################################################

										$option_filter_type = 9;
										}

									################################################################################
									# sync all
									################################################################################

									if($option_filter_type == 0)
										{
										if(isset($settings_client["SyncDat"][$server_id]) === false)
											$jobs["Add"][] = $server_id;
										elseif($settings_client["SyncDat"][$server_id] == "*")
											$jobs["Add"][] = $server_id;
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											$jobs["Change"][] = $server_id;
										}

									################################################################################
									# sync ...
									################################################################################

									if(($option_filter_type > 0) && ($option_filter_type < 8))
										{
										$stat_filter = array("now", "- 1 day", "- 3 day", "- 1 week", "- 2 week", "- 1 month", "- 3 month", "- 6 month", "now");

										$stat_filter = strtotime($stat_filter[$option_filter_type]);

										###########################################################################################
										# does FilterType only occur on Email/SMS as DateReceived ?

										if($default_class == "Calendar")
											$data_timestamp = strtotime($data["Calendar"]["EndTime"]);

										if($default_class == "Email")
											$data_timestamp = strtotime($data["Email"]["DateReceived"]);

										if($default_class == "Notes")
											$data_timestamp = strtotime($data["Notes"]["LastModifiedDate"]);

										if($default_class == "Tasks")
											$data_timestamp = strtotime($data["Tasks"]["DateCompleted"]);

										###########################################################################################

										if(isset($settings_client["SyncDat"][$server_id]) === false)
											{
											# file was not sent to client before

											if($data_timestamp < $stat_filter)
												$settings_client["SyncDat"][$server_id] = "*";
											else
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] == "*")
											{
											# file is known as SoftDelete

											if($data_timestamp < $stat_filter)
												{
												#
												}
											else
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											{
											# file changed since last sync

											if($data_timestamp < $stat_filter)
												$jobs["SoftDelete"][] = $server_id;
											else
												$jobs["Change"][] = $server_id;
											}
										else
											{
											# file is up to date since last sync

											if($data_timestamp < $stat_filter)
												$jobs["SoftDelete"][] = $server_id;
											}
										}

									###########################################################################################
									# sync incomplete (tasks only)
									###########################################################################################

									if($option_filter_type == 8)
										{
										if(isset($settings_client["SyncDat"][$server_id]) === false)
											{
											# file was not sent to client before

											if($data["Tasks"]["Complete"] == 1)
												$settings_client["SyncDat"][$server_id] = "*";
											else
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] == "*")
											{
											# file is known as SoftDelete

											if($data["Tasks"]["Complete"] == 1)
												{
												#
												}
											else
												{
												$jobs["Add"][] = $server_id;
												}
											}
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											{
											# file changed since last sync

											if($data["Tasks"]["Complete"] == 1)
												$jobs["SoftDelete"][] = $server_id;
											else
												$jobs["Change"][] = $server_id;
											}
										else
											{
											# file is up to date since last sync
											}
										}
									}

								################################################################################
								# check for to Delete
								################################################################################

								foreach($settings_client["SyncDat"] as $server_id => $client_timestamp)
									{
									################################################################################
									# skip if ServerId exist
									################################################################################

									if(isset($settings_server["SyncDat"][$server_id]) === true)
										continue;

									################################################################################
									# add ServerId to list of deleted elements
									################################################################################

									$jobs["Delete"][] = $server_id;
									}

								################################################################################
								# check for elements sended by server
								################################################################################

								if(count($jobs) > 0)
									{
									$response->x_switch("AirSync");

									$response->x_open("Collection");

										foreach(array("SyncKey" => $settings_client["SyncKey"], "CollectionId" => $collection_id, "Status" => $status) as $token => $value)
											{
											$response->x_open($token);
												$response->x_print($value);
											$response->x_close($token);
											}

										################################################################################
										# create a response for changed elements
										################################################################################

										$response->x_switch("AirSync");

										$response->x_open("Commands");

											################################################################################
											# init counter of changed elements
											################################################################################

											$estimate = 0;

											################################################################################
											# output for Add/Change
											################################################################################

											foreach(array("Add", "Change") as $command)
												{
												################################################################################
												# skip if no ServerId in list
												################################################################################

												if(isset($jobs[$command]) === false)
													continue;

												################################################################################
												# list all elements
												################################################################################

												foreach($jobs[$command] as $server_id)
													{
													################################################################################
													# exit if WindowSize is reached
													# count($jobs[$command]) contains list of elements to change
													################################################################################

													if($estimate == $window_size)
														break;

													################################################################################
													# increase counter of added/changed elements
													################################################################################

													$estimate = $estimate + 1;

													################################################################################
													# update timestamp of ServerId in SyncState
													################################################################################

													$settings_client["SyncDat"][$server_id] = $settings_server["SyncDat"][$server_id];

													################################################################################
													# output of Added/Changed ServerId and ApplicationData
													################################################################################

													$response->x_switch("AirSync");

													$response->x_open($command);
														$response->x_open("ServerId");
															$response->x_print($server_id);
														$response->x_close("ServerId");

														if($default_class == "")
															{
															}
														elseif(function_exists("active_sync_handle_sync_send_" . strtolower($default_class)) === true)
															{
															$function = "active_sync_handle_sync_send_" . strtolower($default_class);

															$function($response, $request["AuthUser"], $collection_id, $server_id, $collection);
															}

													$response->x_close($command);
													}
												}

											################################################################################
											# output for Delete/SoftDelete
											################################################################################

											foreach(array("Delete", "SoftDelete") as $command)
												{
												################################################################################
												# skip if no ServerId in list
												################################################################################

												if(isset($jobs[$command]) === false)
													continue;

												################################################################################
												# list all elements
												################################################################################

												foreach($jobs[$command] as $server_id)
													{
													################################################################################
													# exit if ServerId is reached
													################################################################################

													if($estimate == $window_size)
														break;

													################################################################################
													# increase counter of changed elements
													################################################################################

													$estimate = $estimate + 1;

													################################################################################
													# remove element from SyncState
													################################################################################

													if($command == "Delete")
														{
														unset($settings_server["SyncDat"][$server_id]);
														unset($settings_client["SyncDat"][$server_id]);
														}

													################################################################################
													# mark element in SyncState as SoftDelete
													################################################################################

													if($command == "SoftDelete")
														{
														$settings_server["SyncDat"][$server_id] = "*";
														$settings_client["SyncDat"][$server_id] = "*";
														}

													################################################################################
													# output of Deleted/SoftDeleted ServerId
													################################################################################

													$response->x_switch("AirSync");

													$response->x_open($command);
														$response->x_open("ServerId");
															$response->x_print($server_id);
														$response->x_close("ServerId");
													$response->x_close($command);
													}
												}

										$response->x_close("Commands");

										################################################################################
										# init counter of changed elements
										################################################################################

										$estimate = 0;

										################################################################################
										# get total number of changed elements
										################################################################################

										foreach(array("Add", "Change", "Delete", "SoftDelete") as $command)
											{
											################################################################################
											# skip if no elements in list
											################################################################################

											if(isset($jobs[$command]) === false)
												continue;

											################################################################################
											# increase counter
											################################################################################

											$estimate = $estimate + count($jobs[$command]);
											}

										################################################################################
										# check if we got more changes than requested by WindowSize
										################################################################################

										if($estimate > $window_size)
											{
											$response->x_switch("AirSync");

											$response->x_open("MoreAvailable", false);
											}

									$response->x_close("Collection");

									################################################################################
									# mark CollectionId as changed
									################################################################################

									$changed_collections[$collection_id] = 1;
									} # if(count($jobs) > 0)
								} # if($get_changes == 0)
							} # elseif($status == 1)
						elseif($status == 3)
							{
							################################################################################
							# process can not be continued (collection)
							################################################################################

							$response->x_switch("AirSync");

							$response->x_open("Collection");

								foreach(array("SyncKey" => $settings_client["SyncKey"], "CollectionId" => $collection_id, "Status" => $status) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

							$response->x_close("Collection");

							################################################################################
							# mark CollectionId as changed
							################################################################################

							$changed_collections[$collection_id] = 1;
							}

						################################################################################
						# continue if no changes detected
						################################################################################

						if($changed_collections[$collection_id] == 0)
							continue;

						################################################################################
						# store SyncState for CollectionId
						################################################################################

						active_sync_put_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"], $settings_client);

						################################################################################
						# mark collections as changed
						# empty response impossible now
						################################################################################

						$changed_collections["*"] = 1;
						} # foreach($xml->Collections->Collection as $collection)

					################################################################################
					# exit if changes were detected
					################################################################################

					if($changed_collections["*"] != 0)
						break;

					if((($wait * 60) != 0) && (($heartbeat_interval * 1) != 0))
						break;

					if((($wait * 60) == 0) && (($heartbeat_interval * 1) == 0))
						break;

					if((($wait * 60) != 0) && ($timeout + ($wait * 60) < microtime(true)))
						break;

					if((($heartbeat_interval * 1) != 0) && ($timeout + ($heartbeat_interval * 1) < microtime(true)))
						break;

					sleep(10);

					clearstatcache();
					} # while(1)

				################################################################################
				# return empty response if no changes at all.
				# this will also prevent invalid sync key ... gotcha
				# this saves a lot debug data
				################################################################################

				if($changed_collections["*"] == 0)
					return("");

				foreach($xml->Collections->Collection as $collection)
					{
					$sync_key	= strval($collection->SyncKey);
					$collection_id	= strval($collection->CollectionId);

					if($changed_collections[$collection_id] != 0)
						continue;

					$settings = active_sync_get_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"]);

					$settings["SyncKey"] = $settings["SyncKey"] + 1;

					active_sync_put_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"], $settings);

					$response->x_switch("AirSync");

					$response->x_open("Collection");

						foreach(array("SyncKey" => $settings["SyncKey"], "CollectionId" => $collection_id, "Status" => 1) as $token => $value)
							{
							$response->x_open($token);
								$response->x_print($value);
							$response->x_close($token);
							}

					$response->x_close("Collection");
					}

			$response->x_close("Collections");
			} # if($status == 1)

	$response->x_close("Sync");

	return($response->response);
	}
?>
