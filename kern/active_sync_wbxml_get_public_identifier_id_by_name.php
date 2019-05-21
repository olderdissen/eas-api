<?
function active_sync_wbxml_get_public_identifier_id_by_name($expression)
	{
	$table = active_sync_wbxml_table_public_identifier();

	foreach($table as $id => $name)
		{
		if($id != $expression)
			continue;

		return($id);
		}

	return(99);
	}
?>
