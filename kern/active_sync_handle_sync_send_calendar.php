<?
function active_sync_handle_sync_send_calendar(& $response, $user, $collection_id, $server_id, $collection)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	$response->x_switch("AirSync");

	$response->x_open("ApplicationData");

		$codepage_table = array(
			"Calendar" => active_sync_get_default_calendar()
			);

		foreach($codepage_table as $codepage => $null)
			{
			if(isset($data[$codepage]) === false)
				continue;

			$response->x_switch($codepage);

			foreach($codepage_table[$codepage] as $token => $value)
				{
				if(isset($data[$codepage][$token]) === false)
					continue;

				if(strlen($data[$codepage][$token]) == 0)
					{
					$response->x_open($token, false);

					continue;
					}

				if(in_array($token, array("DtStamp", "StartTime", "EndTime")) === true)
					$data[$codepage][$token] = date("Ymd\THis\Z", strtotime($data[$codepage][$token]));

				$response->x_open($token);
					$response->x_print($data[$codepage][$token]);
				$response->x_close($token);
				}
			}

		if(isset($data["Attendees"]) === true)
			{
			$response->x_switch("Calendar");

			$response->x_open("Attendees");

				foreach($data["Attendees"] as $attendee)
					{
					$response->x_open("Attendee");

						foreach(active_sync_get_default_attendee() as $token => $value)
							{
							if(isset($attendee[$token]) === false)
								continue;

							if(strlen($attendee[$token]) == 0)
								{
								$response->x_open($token, false);

								continue;
								}

							$response->x_open($token);
								$response->x_print($attendee[$token]);
							$response->x_close($token);
							}

					$response->x_close("Attendee");
					}

			$response->x_close("Attendees");
			}

		if(isset($data["Exceptions"]) === true)
			{
			$response->x_switch("Calendar");

			$response->x_open("Exceptions");

				foreach($data["Exceptions"] as $exception)
					{
					$response->x_switch("Calendar");

					$response->x_open("Exception");

						if(isset($exception["Calendar"]) === true)
							{
							$response->x_switch("Calendar");

							foreach(active_sync_get_default_exception() as $token => $value)
								{
								if(isset($exception["Calendar"][$token]) === false)
									continue;

								if(strlen($exception["Calendar"][$token]) == 0)
									{
									$response->x_open($token, false);

									continue;
									}

								$response->x_open($token);
									$response->x_print($exception["Calendar"][$token]);
								$response->x_close($token);
								}
							}

						if(isset($exception["Attendees"]) === true)
							{
							$response->x_switch("Calendar");

							$response->x_open("Attendees");

								foreach($exception["Attendees"] as $attendee)
									{
									$response->x_open("Attendee");

										foreach(active_sync_get_default_attendee() as $token => $value)
											{
											if(isset($attendee[$token]) === false)
												continue;

											if(strlen($attendee[$token]) == 0)
												{
												$response->x_open($token, false);

												continue;
												}

											$response->x_open($token);
												$response->x_print($attendee[$token]);
											$response->x_close($token);
											}

									$response->x_close("Attendee");
									}

							$response->x_close("Attendees");
							}

						if(isset($exception["Categories"]) === true)
							{
							$response->x_switch("Calendar");

							$response->x_open("Categories");

								foreach($exception["Categories"] as $value)
									{
									$response->x_open("Category");
										$response->x_print($value);
									$response->x_close("Category");
									}

							$response->x_close("Categories");
							}

						if(isset($exception["Body"]) === true)
							{
							foreach($exception["Body"] as $body)
								{
								$response->x_switch("AirSyncBase");

								$response->x_open("Body");

									foreach(active_sync_get_default_body() as $token => $value)
										{
										if(isset($body[$token]) === false)
											continue;

										if(strlen($body[$token]) == 0)
											{
											$response->x_open($token, false);

											continue;
											}

										$response->x_open($token);
											$response->x_print($body[$token]);
										$response->x_close($token);
										}

								$response->x_close("Body");
								}
							}

					$response->x_close("Exception");
					}

			$response->x_close("Exceptions");
			}

		if(isset($data["Recurrence"]) === true)
			{
			$response->x_switch("Calendar");

			$response->x_open("Recurrence");

				foreach(active_sync_get_default_recurrence() as $token => $value)
					{
					if(isset($data["Recurrence"][$token]) === false)
						continue;

					if(strlen($data["Recurrence"][$token]) == 0)
						{
						$response->x_open($token, false);

						continue;
						}

					$response->x_open($token);
						$response->x_print($data["Recurrence"][$token]);
					$response->x_close($token);
					}

			$response->x_close("Recurrence");
			}

		if(isset($data["Body"]) === true)
			{
			$default_class = active_sync_get_class_by_collection_id($user, $collection_id);

			if(isset($collection->Options) === true)
				{
				foreach($collection->Options as $options)
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

							if(isset($data["Body"][$random_body_id]["Data"]) === false)
								continue;

							$response->x_switch("AirSyncBase");

							$response->x_open("Body");

								if(isset($preference["Preview"]) === true)
									{
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
									}

								if(isset($preference->TruncationSize))
									if(intval($preference->TruncationSize) > 0)
										if(isset($data["Body"][$random_body_id]["EstimatedDataSize"]) === false)
											{
											$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
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

		if(isset($data["Categories"]) === true)
			{
			$response->x_switch("Calendar");

			$response->x_open("Categories");

				foreach($data["Categories"] as $value)
					{
					$response->x_open("Category");
						$response->x_print($value);
					$response->x_close("Category");
					}

			$response->x_close("Categories");
			}

	$response->x_close("ApplicationData");
	}
?>
