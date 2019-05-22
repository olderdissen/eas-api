<?
function active_sync_get_is_user_folder($type)
	{
	return(in_array($type, array(1, 12, 13, 14, 15, 16, 17)) === false ? 0 : 1);
	}
?>
