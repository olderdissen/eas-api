<?
function active_sync_wbxml_get_codepage_id_by_name($name)
	{
	foreach(range(0x00, 0x1F) as $id)
		{
		if(active_sync_wbxml_get_codepage_name_by_id($id) != $name)
			continue;

		return($id);
		}

	return(99);
	}
?>
