<?
function active_sync_get_table_command()
	{
	$table = array();

	$table[0]	= "Sync";
	$table[1]	= "SendMail";
	$table[2]	= "SmartForward";
	$table[3]	= "SmartReply";
	$table[4]	= "GetAttachment";
#	$table[5]	= "GetHierarchy";	# DEPRECATED
#	$table[6]	= "CreateCollection";	# DEPRECATED
#	$table[7]	= "DeleteCollection";	# DEPRECATED
#	$table[8]	= "MoveCollection";	# DEPRECATED
	$table[9]	= "FolderSync";
	$table[10]	= "FolderCreate";
	$table[11]	= "FolderDelete";
	$table[12]	= "FolderUpdate";
	$table[13]	= "MoveItems";
	$table[14]	= "GetItemEstimate";
	$table[15]	= "MeetingResponse";
	$table[16]	= "Search";
	$table[17]	= "Settings";
	$table[18]	= "Ping";
	$table[19]	= "ItemOperations";
	$table[20]	= "Provision";
	$table[21]	= "ResolveRecipients";
	$table[22]	= "ValidateCert";
#	$table[23]	= "Find";

	return($table);
	}
?>
