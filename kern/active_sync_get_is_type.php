<?
function active_sync_get_is_type($type)
	{
	################################################################################
	# allowed range for types is 1 .. 19
	################################################################################

	return(($type < 1) || ($type > 19) ? 0 : 1);

	# 2.2.3.162.2 FolderCreate -> 10 Malformed request
	# 2.2.3.162.3 FolderDelete -> 10 Incorrectly formatted request
	# 2.2.3.162.5 FolderUpdate -> 10 Incorrectly formatted request
	# 2.2.3.162.* Folder....te ->  1 Success
	}
?>
