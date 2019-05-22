<?
function active_sync_get_class_by_type($type)
	{
	# AS-MSCMD - 2.2.3.170.2 - Type (FolderCreate)
	# AS-MSCMD - 2.2.3.170.3 - Type (FolderSync)

	$classes = array();

	$classes[1] = "";		# User-created folder (generic)
	$classes[2] = "Email";		# Default Inbox folder
	$classes[3] = "Email";		# Default Drafts folder
	$classes[4] = "Email";		# Default Deleted Items folder
	$classes[5] = "Email";		# Default Sent Items folder
	$classes[6] = "Email";		# Default Outbox folder
	$classes[7] = "Tasks";		# Default Tasks folder
	$classes[8] = "Calendar";	# Default Calendar folder
	$classes[9] = "Contacts";	# Default Contacts folder
	$classes[10] = "Notes";		# Default Notes folder
	$classes[11] = "Journal";	# Default Journal folder
	$classes[12] = "Email";		# User-created Mail folder
	$classes[13] = "Calendar";	# User-created Calendar folder
	$classes[14] = "Contacts";	# User-created Contacts folder
	$classes[15] = "Tasks";		# User-created Tasks folder
	$classes[16] = "Journal";	# User-created Journal folder
	$classes[17] = "Notes";		# User-created Notes folder
	$classes[18] = "";		# Unknown folder type
	$classes[19] = "";		# Recipient information cache

	$type = (($type < 1) || ($type > 19) ? 18 : $type);

	return($classes[$type]);
	}
?>
