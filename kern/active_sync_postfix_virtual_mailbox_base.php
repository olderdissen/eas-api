<?
function active_sync_postfix_virtual_mailbox_base()
	{
	$path = active_sync_postfix_config("virtual_mailbox_base", "/var/mail/virtual_mailbox_base");

	return($path);
	}
?>
