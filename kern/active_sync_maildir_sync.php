<?
function active_sync_maildir_sync()
	{
	$host = active_sync_get_domain();
	$version = active_sync_get_version();

	$users = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($users["login"] as $user_id => $null)
		{
		if(file_exists(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".mdl") === true)
			continue;

		touch(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".mdl");

		$list = active_sync_get_settings(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".mds");

		$oof = active_sync_get_settings(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".sync");

		$maildir = active_sync_postfix_virtual_mailbox_base() . "/" . $users["login"][$user_id]["User"] . "/new";
#		$maildir = exec("postconf -h virtual_mailbox_base") . "/" . $users["login"][$user_id]["User"] . "/new";

		foreach($list as $server_id => $null)
			{
			if(file_exists($maildir . "/" . $server_id) === false)
				{
				if(file_exists(DAT_DIR . "/" . $users["login"][$user_id]["User"] . "/" . active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2) . "/" . $server_id . ".data") !== false)
					unlink(DAT_DIR . "/" . $users["login"][$user_id]["User"] . "/" . active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2) . "/" . $server_id . ".data");

				unset($list[$server_id]);
				}

			if(file_exists(DAT_DIR . "/" . $users["login"][$user_id]["User"] . "/" . active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2) . "/" . $server_id . ".data") === false)
				{
				if(file_exists($maildir . "/" . $server_id) !== false)
					unlink($maildir . "/" . $server_id);

				unset($list[$server_id]);
				}
			}

		foreach(scandir($maildir) as $file)
			{
			if(is_dir($maildir . "/" . $file) === true)
				continue;

			if(file_exists(DAT_DIR . "/" . $users["login"][$user_id]["User"] . "/" . active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2) . "/" . $file . ".data") === true)
				continue;

			$mime = file_get_contents($maildir . "/" . $file);

			$data = active_sync_mail_parse($users["login"][$user_id]["User"], active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2), $file, $mime);

			active_sync_put_settings_data($users["login"][$user_id]["User"], active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2), $file, $data);

			if(($oof["OOF"]["OofState"] == 1) || (($oof["OOF"]["OofState"] == 2) && ((time() > strtotime($oof["OOF"]["StartTime"])) && (time() < strtotime($oof["OOF"]["EndTime"])))))
				{
				list($from_name, $from_mail) = active_sync_mail_parse_address($data["Email"]["From"]);
				list($from_user, $from_host) = explode("@", ($from_mail ? $from_mail : "@"), 2);

				$from_mail = strtolower($from_mail);
				$from_user = strtolower($from_user);
				$from_host = strtolower($from_host);

				list($to_name, $to_mail) = active_sync_mail_parse_address($data["Email"]["To"]);		# check for multiple recipients
				list($to_user, $to_host) = explode("@", ($to_mail ? $to_mail : "@"), 2);			# check for undisclosed recipient !!!

				$to_mail = strtolower($to_mail);
				$to_user = strtolower($to_user);
				$to_host = strtolower($to_host);

				$old_mime_message = "";

				foreach($data["Body"] as $id => $null)
					{
					if(isset($data["Body"][$id]["Type"]) === false)
						continue;

					if($data["Body"][$id]["Type"] != 4) # Mime
						continue;

					$old_mime_message = $data["Body"][$id]["Data"];
					}

				list($head, $body) = active_sync_mail_split($old_mime_message);

				$head_parsed = active_sync_mail_parse_head($head);

				$reply_message = "";

				if(isset($head_parsed["X-Auto-Response-Suppress"]["OOF"]) === false)
					if(in_array($from_mail, array("", $to_mail)) === false)
						if(in_array($from_user, array("mailer-daemon", "no-reply", "root", "wwwrun", "www-run", "wwww-data", "www-user", "mail", "noreply", "postfix")) === false)
							if(($oof["OOF"]["AppliesToInternal"]["Enabled"] == 1) && ($from_host == $to_host))
								$reply_message = $oof["OOF"]["OOF"]["AppliesToInternal"]["ReplyMessage"];
							elseif(($oof["OOF"]["AppliesToExternalKnown"]["Enabled"] == 1) && ($from_host != $to_host) && (active_sync_get_is_known_mail($users["login"][$user_id]["User"], active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 9), $from_mail) == 1))
								$reply_message = $oof["OOF"]["AppliesToExternalKnown"]["ReplyMessage"];
							elseif(($oof["OOF"]["AppliesToExternalUnknown"]["Enabled"] == 1) && ($from_host != $to_host) && (active_sync_get_is_known_mail($users["login"][$user_id]["User"], active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 9), $from_mail) == 0))
								$reply_message = $oof["OOF"]["AppliesToExternalUnknown"]["ReplyMessage"];

				if(strlen($reply_message) == 0)
					continue;

				################################################################################

				# 0x00000001	DR		Suppress delivery reports from transport.
				# 0x00000002	NDR		Suppress non-delivery reports from transport.
				# 0x00000004	RN		Suppress read notifications from receiving client.
				# 0x00000008	NRN		Suppress non-read notifications from receiving client.
				# 0x00000010	OOF		Suppress Out of Office (OOF) notifications.
				# 0x00000020	AutoReply	Suppress auto-reply messages other than OOF notifications.

				$new_mime_message = array();

				$new_mime_message[] = "From: " . ($to_name ? "\"" . $to_name . "\" <" . $to_user . "@" . $to_host . ">" : $to_user . "@" . $to_host);
				$new_mime_message[] = "To: " . ($from_name ? "\"" . $from_name . "\" <" . $from_user . "@" . $from_host . ">" : $from_user . "@" . $from_host);
				$new_mime_message[] = "Subject: OOF: " . $data["Email"]["Subject"];
				$new_mime_message[] = "Reply-To: " . $to_user . "@" . $to_host;
				$new_mime_message[] = "Auto-Submitted: auto-generated";
				$new_mime_message[] = "Message-ID: <" . active_sync_create_guid() . "@" . $host . ">";
				$new_mime_message[] = "X-Auto-Response-Suppress: " . implode(", ", array("DR", "NDR", "RN", "NRN", "OOF", "AutoReply")); # we do not want anything
				$new_mime_message[] = "X-Mailer: " . $version;
				$new_mime_message[] = "";
				$new_mime_message[] = $reply_message;

				$new_mime_message = implode("\n", $new_mime_message);

				active_sync_send_mail($users["login"][$user_id]["User"], $new_mime_message);
				}

			$list[$file] = filemtime($maildir . "/" . $file);
			}

		active_sync_put_settings(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".mds", $list);

		@ unlink(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".mdl");
		}
	}
?>
