<?
function active_sync_web_save_email($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($settings["login"] as $user)
		{
		if($user["User"] != $request["AuthUser"])
			{
			continue;
			}

		break;
		}

	$from = $user["User"] . "@" . active_sync_get_domain();

	$from = ($user["DisplayName"] ? "\"" . $user["DisplayName"] . "\" <" . $from . ">" : $from);

	$to		= $_POST["To"];				# not available via Request
	$cc		= $_POST["Cc"];				# not available via Request
	$bcc		= $_POST["Bcc"];				# not available via Request
	$subject	= $_POST["Subject"];				# not available via Request
	$importance	= $_POST["Importance"];			# not available via Request

	$draft			= $_POST["Draft"];

	$body_p			= $_POST["inhalt"];				# not available via Request

	$body_p			= active_sync_mail_convert_html_to_plain($body_p);

	$body_h			= $_POST["inhalt"];				# not available via Request

	$importance_values	= array(0 => "Low", 1 => "Normal", 2 => "High");		# low number = low priority (0, 1, 2)
	$priority_values	= array(0 => "5 (Low)", 1 => "3 (Normal)", 2 => "1 (High)");	# low number = high priority (5, 3, 1)

	$boundary		= active_sync_create_guid();

	$body_m = array();

	$body_m[] = "From: " . $from;
	$body_m[] = "To: " . $to;

	if(strlen($cc) != 0)
		{
		$body_m[] = "Cc: " . $cc;
		}

	if(strlen($bcc) != 0)
		{
		$body_m[] = "Bcc: " . $bcc;
		}

	$body_m[] = "Date: " . date("r");
#		$body_m[] = "Content-Type: text/html; charset=\"UTF-8\"";
#		$body_m[] = "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"";
	$body_m[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
	$body_m[] = "Importance: " . $importance_values[$importance];
	$body_m[] = "MIME-Version: 1.0";
	$body_m[] = "X-Priority: " . $priority_values[$importance];
	$body_m[] = "X-Mailer: " . active_sync_get_version();
	$body_m[] = "";
	$body_m[] = "--" . $boundary;
	$body_m[] = "Content-Type: text/plain; charset=\"UTF-8\"";
	$body_m[] = "";
	$body_m[] = $body_p;
	$body_m[] = "--" . $boundary;
	$body_m[] = "Content-Type: text/html; charset=\"UTF-8\"";
	$body_m[] = "";
	$body_m[] = $body_h;

#		foreach($attachments as $id => $attachment)
#			{
#			$body_m[] = "--" . $boundary;
#			$body_m[] = "Content-Type: " . $attachment["ContentType"] . "; name=\"" . $attachment["DisplayName"] . "\"";
#			$body_m[] = "Content-Disposition: attachment; filename=\"" . $attachment["AirSyncBase"]["DisplayName"] . "\"";
#			$body_m[] = $attachment["AirSyncBase"]["Data"]
#			}

	$body_m[] = "--" . $boundary . "--";

	$body_m = implode("\n", $body_m);

	$data = array
		(
		"AirSync" => array
			(
			"Class" => "Email"
			),
		"Email" => array
			(
			"From" => $from,
			"To" => $to,
			"Cc" => $cc,
			"Importance" => $importance,
			"Subject" => $subject,
			"DateReceived" => date("Y-m-d\TH:i:s\Z", date("U")),
			"Read" => 1,
			"ContentClass" => "urn:content-classes:message",
			"MessageClass" => "IPM.Note"
			),
		"Body" => array
			(
			array
				(
				"Type" => 1,
				"EstimatedDataSize" => strlen($body_p),
				"Data" => $body_p
				),
			array
				(
				"Type" => 2,
				"EstimatedDataSize" => strlen($body_h),
				"Data" => $body_h
				),
			array
				(
				"Type" => 4,
				"EstimatedDataSize" => strlen($body_m),
				"Data" => $body_m
				)
			)
		);

	if($bcc != "")
		$data["Email2"]["ReceivedAsBcc"] = 1;

	if($draft == 0)
		{
		list($t_name, $t_mail) = active_sync_mail_parse_address($to);
		list($f_name, $f_mail) = active_sync_mail_parse_address($from);

		if(strlen($to) > 0)
			{
			$recipient_is_phone = active_sync_get_is_phone($t_mail);

			if($recipient_is_phone == 0)
				active_sync_send_mail($request["AuthUser"], $body_m);

			if($recipient_is_phone == 1)
				{
				$x_name = $f_name;
				$x_mail = $f_mail;

				$settings = active_sync_get_settings_user($request["AuthUser"]);

				if(isset($settings["DisplayName"]) === true)
					$x_name = $settings["DisplayName"];

				if(isset($settings["MobilePhone"]) === true)
					$x_mail = $settings["MobilePhone"];

				$devices = active_sync_get_devices_by_user($request["AuthUser"]);

				foreach($devices as $device)
					{
					$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" .  $device . ".sync");

					if(isset($settings["DeviceInformation"]["EnableOutboundSMS"]) === false)
						continue;

					if(isset($settings["DeviceInformation"]["PhoneNumber"]) === false)
						continue;

					$x_mail = $settings["DeviceInformation"]["PhoneNumber"];

					break;
					}

				$data = array
					(
					"AirSync" => array
						(
						"Class" => "SMS"
						),
					"Email" => array
						(
						"DateReceived" => date("Y-m-d\TH:i:s\Z", date("U")),
						"Read" => 1,
						"To" => ($t_name ? "\"" . $t_name . "\" " : "") . "[MOBILE: " . $t_mail . "]",
						"From" => ($x_name ? "\"" . $x_name . "\" " : "") . "[MOBILE: " . $x_mail . "]"
						),
					"Body" => array
						(
						array
							(
							"Type" => 1,
							"EstimatedDataSize" => strlen($body_p),
							"Data" => $body_p
							)
						)
					);

				$user = $request["AuthUser"];
				$collection_id = active_sync_get_collection_id_by_type($user, 6); # Outbox
				$server_id = active_sync_create_guid_filename($user, $collection_id);

				active_sync_put_settings_data($user, $collection_id, $server_id, $data);
				}
			}

		if($request["SaveInSent"] == "T")
			{
			$user = $request["AuthUser"];
			$collection_id = active_sync_get_collection_id_by_type($user, 5); # Sent Items
			$server_id = active_sync_create_guid_filename($user, $collection_id);

			active_sync_put_settings_data($user, $collection_id, $server_id, $data);
			}
		}

	if($draft == 1)
		{
		$user		= $request["AuthUser"];
		$collection_id	= active_sync_get_collection_id_by_type($user, 3); # Drafts
		$server_id	= ($request["ServerId"] ? $request["ServerId"] : active_sync_create_guid_filename($user, $collection_id));

		$reference = 0;

		foreach(scandir(DAT_DIR . "/../web/temp") as $file)
			{
			if(($file == ".") || ($file == ".."))
				continue;

			$body = file_get_contents(DAT_DIR . "/../web/temp/" . $file);

			unlink(DAT_DIR . "/../web/temp/" . $file);

			$data["Attachment"]["AirSyncBase"][$reference] = array(
				"DisplayName" => $file,
				"FileReference" => $server_id . ":" . $reference,
				"Method" => 1,
				"EstimatedDataSize" => strlen($body),
				"ContentId" => "xxx",
				"IsInline" => 0
				);

			$data["File"][$reference] = array(
				"ContentType" => "",
				"Data" => base64_encode($body)
				);

			$reference = $reference + 1;
			}

		active_sync_put_settings_data($user, $collection_id, $server_id, $data);
		}

	print(1);
	}
?>
