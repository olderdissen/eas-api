<?
function active_sync_handle_get_item_estimate($request)
	{
	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("ItemEstimate");

	$response->x_open("GetItemEstimate");

		if(isset($xml->Collections) === true)
			{
			foreach($xml->Collections->Collection as $collection)
				{
				$sync_key	= strval($collection->SyncKey);
				$collection_id	= strval($collection->CollectionId);

				$settings_client = active_sync_get_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"]);

				$settings_server = active_sync_get_settings_sync($request["AuthUser"], $collection_id, "");

				$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

				if($sync_key != $settings_client["SyncKey"])
					$status = 4; # The synchonization key was invalid
				else
					$status = 1; # Success

				$response->x_open("Response");

					$response->x_open("Status");
						$response->x_print($status);
					$response->x_close("Status");

					if($status == 1)
						{
						$jobs = array();

						foreach($settings_server["SyncDat"] as $server_id => $null)
							{
							$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

							if(isset($data["AirSync"]["Class"]) === false)
								$data["AirSync"]["Class"] = $default_class;

							$class = $default_class;
							$filter_type = 0;
							$class_found = 0;

							if(isset($collection->Options) === true)
								{
								foreach($collection->Options as $options)
									{
									if(isset($options->Class) === false)
										$class = $default_class;
									else
										$class = strval($options->Class); # only occurs on email/sms

									if($data["AirSync"]["Class"] != $class)
										continue;

									if(isset($options->FilterType) === false)
										$filter_type = 0;
									else
										$filter_type = intval($options->FilterType); # only occurs on email/sms

									$class_found = 1;
									}
								}

							if($class_found == 0)
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

								$filter_type = 9; # :) no more filter_type between 0 (all), 1 - 7, 8 (incomplete)
								}

							if($filter_type == 0)
								{
								if(isset($settings_client["SyncDat"][$server_id]) === false)
									$jobs["Add"][] = $server_id;
								elseif($settings_client["SyncDat"][$server_id] == "*")
									$jobs["Add"][] = $server_id;
								elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
									$jobs["Change"][] = $server_id;

								$class_found = 1;
								}

							if(($filter_type > 0) && ($filter_type < 8))
								{
								$stat_filter = array("now", "- 1 day", "- 3 day", "- 1 week", "- 2 week", "- 1 month", "- 3 month", "- 6 month", "now");

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

								if(isset($settings_client["SyncDat"][$server_id]) === false)
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
								if(isset($settings_client["SyncDat"][$server_id]) === false)
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
							{
							if(isset($settings_server["SyncDat"][$server_id]) === true)
								continue;

							$jobs["Delete"][] = $server_id;
							}

						$estimate = 0;

						foreach(array("Add", "Change", "Delete", "SoftDelete") as $command)
							{
							if(isset($jobs[$command]) === false)
								continue;

							$estimate = $estimate + count($jobs[$command]);
							}

						$response->x_open("Collection");

							foreach(array("CollectionId" => $collection_id, "Estimate" => $estimate) as $token => $value)
								{
								$response->x_open($token);
									$response->x_print($value);
								$response->x_close($token);
								}

						$response->x_close("Collection");
						}

				$response->x_close("Response");
				}
			}

	$response->x_close("GetItemEstimate");

	return($response->response);
	}
?>
