<?
function active_sync_wbxml_get_token_name_by_id($codepage, $id)
	{
	$codepage = (is_numeric($codepage) === false ? active_sync_wbxml_get_codepage_id_by_name($codepage) : $codepage);

	$table = active_sync_wbxml_table_token();

	return(isset($table[$codepage][$id & 0x3F]) === false ? "unknown" : $table[$codepage][$id & 0x3F]);
	}
?>
