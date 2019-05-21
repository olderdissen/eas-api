<?
function active_sync_wbxml_get_public_identifier_name_by_id($id)
	{
	$table = active_sync_wbxml_table_public_identifier();

	return(isset($table[$id]) === false ? $id : $table[$id]);
	}
?>
