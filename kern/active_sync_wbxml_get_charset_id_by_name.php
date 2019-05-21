<?
function active_sync_wbxml_get_charset_id_by_name($expression)
	{
	foreach(range(0x0000, 0xFFFF) as $id)
		{
		if(active_sync_wbxml_get_charset_name_by_id($id) != $name)
			continue;

		return($id);
		}

	return(99);
	}
?>
