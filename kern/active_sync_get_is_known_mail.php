<?
function active_sync_get_is_known_mail($user, $collection_id, $email_address)
	{
	$retval = 0;

	foreach(glob(DAT_DIR . "/" . $user . "/" . $collection_id . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		foreach(array("Email1Address", "Email2Address", "Email3Address") as $token)
			{
			if(isset($data["Contacts"][$token]) === false)
				continue;

			list($data_name, $data_mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

			if(strtolower($data_mail) != strtolower($email_address))
				continue;

			$retval = 1;

			break(2); # exit foreach and foreach
			}
		}

	return($retval);
	}
?>
