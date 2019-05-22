<?
function active_sync_postfix_virtual_alias_maps_db()
	{
	$file = active_sync_postfix_config("virtual_alias_maps", "hash:/etc/postfix/virtual_alias_maps");

	list($type, $file) = explode(":", $file, 2);

	return($file);
	}
?>
