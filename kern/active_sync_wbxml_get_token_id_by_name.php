<?
function active_sync_wbxml_get_token_id_by_name($codepage, $token)
	{
	$codepage = (is_numeric($codepage) === false ? active_sync_wbxml_get_codepage_id_by_name($codepage) : $codepage);

	foreach(range(0x05, 0x3F) as $id)
		{
		if(active_sync_wbxml_get_token_name_by_id($codepage, $id) != $token)
			continue;

		return($id);
		}

	return(99);
	}
?>
