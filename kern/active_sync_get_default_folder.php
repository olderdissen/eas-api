<?
#  1 User-created folder (generic)
#  2 Default Inbox folder
#  3 Default Drafts folder
#  4 Default Deleted Items folder
#  5 Default Sent Items folder
#  6 Default Outbox folder
#  7 Default Tasks folder
#  8 Default Calendar folder
#  9 Default Contacts folder
# 10 Default Notes folder
# 11 Default Journal folder
# 12 User-created Mail folder
# 13 User-created Notes folder
# 14 User-created Calendar folder
# 15 User-created Contacts folder
# 16 User-created Tasks folder
# 17 User-created journal folder
# 18 Unknown folder type
# 19 Recipient information cache

function active_sync_get_default_folder()
	{
	$folders = array
		(
		array("ServerId" => 9002, "ParentId" => 0, "Type" => 2, "DisplayName" => "Inbox"),
		array("ServerId" => 9003, "ParentId" => 0, "Type" => 3, "DisplayName" => "Drafts"),
		array("ServerId" => 9004, "ParentId" => 0, "Type" => 4, "DisplayName" => "Deleted Items"),
		array("ServerId" => 9005, "ParentId" => 0, "Type" => 5, "DisplayName" => "Sent Items"),
		array("ServerId" => 9006, "ParentId" => 0, "Type" => 6, "DisplayName" => "Outbox"),

		array("ServerId" => 9007, "ParentId" => 0, "Type" => 7, "DisplayName" => "Tasks"),
		array("ServerId" => 9008, "ParentId" => 0, "Type" => 8, "DisplayName" => "Calendar"),
		array("ServerId" => 9009, "ParentId" => 0, "Type" => 9, "DisplayName" => "Contacts"),
		array("ServerId" => 9010, "ParentId" => 0, "Type" => 10, "DisplayName" => "Notes")

#		array("ServerId" => 9011, "ParentId" => 0, "Type" => 11, "DisplayName" => "Journal"),
#		array("ServerId" => 9019, "ParentId" => 0, "Type" => 19, "DisplayName" => "Recipient Information")
		);

	return($folders);
	}
?>
