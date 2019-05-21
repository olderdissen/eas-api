<?
function active_sync_wbxml_get_charset_name_by_id($id)
	{
	$table = active_sync_wbxml_table_charset();

	return(isset($table[$id]) === false ? $id : $table[$id]);
	}
?>
