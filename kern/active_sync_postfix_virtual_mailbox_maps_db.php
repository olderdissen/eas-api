<?
function active_sync_postfix_virtual_mailbox_maps_db()
	{
	$file = active_sync_postfix_config("virtual_mailbox_maps", "hash:/etc/postfix/virtual_mailbox_maps");

	list($type, $file) = explode(":", $file, 2);

	return($file);
	}
?>
