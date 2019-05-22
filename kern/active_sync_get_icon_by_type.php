<?
function active_sync_get_icon_by_type($type)
	{
	$ico = array();

	$ico[1] = "default";		# user-created folder (generic)

	$ico[2] = "mail-inbox";		# default inbox folder
	$ico[3] = "mail-drafts";	# default drafts folder
	$ico[4] = "mail-trash";		# default deleted items folder
	$ico[5] = "mail-sent";		# default sent items folder
	$ico[6] = "mail-outbox";	# default outbox folder
	$ico[7] = "tasks";		# default tasks folder
	$ico[8] = "calendar";		# default calendar folder
	$ico[9] = "contacts";		# default contacts folder
	$ico[10] = "notes";		# default notes folder
	$ico[11] = "journal";		# default journal folder

	$ico[12] = "mail-default";	# user-created mail folder
	$ico[13] = "calendar";		# user-created calendar folder
	$ico[14] = "contacts";		# user-created contacts folder
	$ico[15] = "tasks";		# user-created tasks folder
	$ico[16] = "journal";		# user-created journal folder
	$ico[17] = "notes";		# user-created notes folder

	$ico[18] = "default";		# unknown folder type

	$ico[19] = "ric";		# recipient information cache

	$type = (isset($ico[$type]) === false ? 1 : $type);

	return("folder-" . $ico[$type] . ".png");
	}
?>
