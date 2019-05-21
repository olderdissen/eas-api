<?
function active_sync_wbxml_get_codepage_name_by_id($id)
	{
	$table = active_sync_wbxml_table_codepage();

	return(isset($table[$id & 0x1F]) === false ? "unknown" : $table[$id & 0x1F]);
	}
?>
