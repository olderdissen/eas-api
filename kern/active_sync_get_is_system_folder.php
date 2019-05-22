<?
function active_sync_get_is_system_folder($type)
	{
	return(in_array($type, array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11)) === false ? 0 : 1);
	}
?>
