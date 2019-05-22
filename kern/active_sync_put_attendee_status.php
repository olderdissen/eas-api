<?
function active_sync_put_attendee_status($user, $server_id, $email, $attendee_status)
	{
	$collection_id = active_sync_get_collection_id_by_type($user, 8); # Calendar

	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	if(isset($data["Attendees"]) === true)
		{
		foreach($data["Attendees"] as $id => $attendee)
			{
			if(isset($attendee["Email"]) === false)
				continue;

			if($attendee["Email"] != $email)
				continue;

			$data["Attendees"][$id]["AttendeeStatus"] = $attendee_status;

			active_sync_put_settings_data($user, $collection_id, $server_id, $data);

			return(1);
			}
		}

	return(0);
	}
?>
