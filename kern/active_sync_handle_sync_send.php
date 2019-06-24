<?
function active_sync_handle_sync_send(& $response, $user, $collection_id, $server_id, $collection)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	if(isset($data["AirSync"]) === true)
		{
		$response->x_switch("AirSync");

		foreach($data["AirSync"] as $token => $value)
			{
			if(strlen($data["AirSync"][$token]) == 0)
				{
				$response->open($token, false);

				continue;
				}

			$response->x_open($token);
				$response->x_print($data["AirSync"][$token]);
			$response->x_close($token);
			}
		}

	$codepage_table = array
		(
		"AirSyncBase" => array("NativeBodyType" => 0),
		"Calendar" => active_sync_get_default_calendar(),
		"Contacts" => active_sync_get_default_contacts(),
		"Contacts2" => active_sync_get_default_contacts2(),
		"Email" => active_sync_get_default_email(),
		"Email2" => active_sync_get_default_email2(),
		"Notes" => active_sync_get_default_notes(),
		"Tasks" => active_sync_get_default_tasks()
		);

	$response->x_switch("AirSync");

	$response->x_open("ApplicationData");

		foreach($codepage_table as $codepage => $null)
			{
			if(isset($data[$codepage]) === false)
				continue;

			$response->x_switch($codepage);

			foreach($codepage_table[$codepage] as $token => $null)
				{
				if(isset($data[$codepage][$token]) === false)
					continue;

				if(strlen($data[$codepage][$token]) == 0)
					{
					$response->x_open($token, false);

					continue;
					}

				# The ... element is defined as an element in the Calendar namespace.
				# The value of this element is a string data type, represented as a
				# Compact DateTime ([MS-ASDTYPE] section 2.7.2).

				if(in_array($token, array("DtStamp", "StartTime", "EndTime")) === true)
					$data[$codepage][$token] = date("Ymd\THis\Z", strtotime($data[$codepage][$token]));

				# The value of this element is a datetime data type in Coordinated Universal
				# Time (UTC) format, as specified in [MS-ASDTYPE] section 2.3.

				if(in_array($token, array("Aniversary", "Birthday")) === true)
					$data[$codepage][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data[$codepage][$token]));

				# The value of the * element is a string data type represented as a
				# Compact DateTime ([MS-ASDTYPE] section 2.7.2).

				if(in_array($token, array("DateCompleted", "DueDate", "OrdinalDate", "ReminderTime", "Start", "StartDate", "UtcDueDate", "UtcStartDate")) === true)
					$data[$codepage][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data[$codepage][$token]));

				$response->x_open($token);
					$response->x_print($data[$codepage][$token]);
				$response->x_close($token);
				}
			}

		if(isset($data["Attachments"]) === true)
			{
			$response->x_switch("AirSyncBase");

			$response->x_open("Attachments");

				foreach($data["Attachments"] as $id => $null)
					{
					$response->x_switch("AirSyncBase");

					$response->x_open("Attachment");

						foreach(array("Email") as $codepage)
							{
							if(isset($data["Attachments"][$id][$codepage]) === false)
								continue;

							$response->x_switch($codepage);

							foreach($data["Attachments"][$id][$codepage] as $token => $null)
								{
								if(strlen($data["Attachments"][$id][$codepage][$token]) == 0)
									{
									$response->x_open($token, false);

									continue;
									}

								$response->x_open($token);
									$response->x_print($data["Attachments"][$id][$codepage][$token]);
								$response->x_close($token);
								}
							}

					$response->x_close("Attachment");
					}

			$response->x_close("Attachments");
			}

		if(isset($data["Attendees"]) === true)
			{
			$response->x_switch($marker);

			$response->x_open("Attendees");

				foreach($data["Attendees"] as $attendee)
					{
					$response->x_open("Attendee");

						foreach(active_sync_get_default_attendee() as $token => $null)
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

		if(isset($data["Categories"]) === true)
			{
			$response->x_switch($marker);

			$response->x_open("Categories");

				foreach($data["Categories"] as $id => $null)
					{
					$response->x_open("Category");
						$response->x_print($data["Categories"][$id]);
					$response->x_close("Category");
					}

			$response->x_close("Categories");
			}

		if(isset($data["Children"]) === true)
			{
			$response->x_switch($marker);

			$response->x_open("Children");

				foreach($data["Children"] as $id => $null)
					{
					$response->x_open("Child");
						$response->x_print($data["Children"][$id]);
					$response->x_close("Child");
					}

			$response->x_close("Children");
			}

		if(isset($data["Flag"]))
			if(count($data["Flag"]) == 0)
				{
				$response->x_switch($marker);

				$response->x_open("Flag", false);
				}
			else
				{
				$response->x_switch($marker);

				$response->x_open("Flag");

					foreach(array("Email", "Tasks") as $codepage)
						{
						if(isset($data["Flag"][$codepage]) === false)
							continue;

						$response->x_switch($codepage);

						foreach($data["Flag"][$codepage] as $token => $null)
							{
							if(strlen($data["Flag"][$codepage][$token]) == 0)
								{
								$response->x_open($token, false);

								continue;
								}

							$response->x_open($token);
								$response->x_print($data["Flag"][$codepage][$token]);
							$response->x_close($token);
							}
						}

				$response->x_close("Flag");
				}

		if(isset($data["Meeting"]) === true)
			{
			$response->x_switch($marker);

			$response->x_open("MeetingRequest");

				foreach(array("Email", "Email2", "Calendar") as $codepage)
					{
					if(isset($data["Meeting"][$codepage]) === false)
						continue;

					$response->x_switch($codepage);

					foreach($data["Meeting"][$codepage] as $token => $null)
						{
						if(strlen($data["Meeting"][$codepage][$token]) == 0)
							{
							$response->x_open($token, false);

							continue;
							}

						$response->x_open($token);
							$response->x_print($data["Meeting"][$codepage][$token]);
						$response->x_close($token);
						}
					}

			$response->x_close("MeetingRequest");
			}

		if(isset($data["Recurrence"]) === true)
			{
			$response->x_switch($marker);

			$response->x_open("Recurrences");

				foreach($data["Recurrence"] as $id => $null)
					{
					$response->x_open("Recurrence");

						foreach(active_sync_get_default_recurrence() as $token => $null)
							{
							if(isset($data["Recurrence"][$id][$token]) === false)
								continue;

							if(strlen($data["Recurrence"][$id][$token]) == 0)
								{
								$response->x_open($token, false);

								continue;
								}

							$response->x_open($token);
								$response->x_print($data["Recurrence"][$id][$token]);
							$response->x_close($token);
							}

					$response->x_close("Recurrence");
					}

			$response->x_close("Recurrences");
			}

		if(isset($data["RightsManagement"]) === true)
			{
			$response->x_switch("RightsManagement");

			$response->x_open("RightsManagementLicense");

				# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

				foreach(active_sync_get_default_rights_management() as $token => $null)
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
							if(isset($data["RightsManagement"]) === true)
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
									if(isset($data["Body"][$random_body_id]["EstimatedDataSize"]))
										if(intval($preference->TruncationSize) < $data["Body"][$random_body_id]["EstimatedDataSize"])
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

	$response->x_close("ApplicationData");
	}
?>
