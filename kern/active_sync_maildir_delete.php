<?
function active_sync_maildir_delete($user)
	{
	$path = active_sync_postfix_virtual_mailbox_base();

	active_sync_maildir_delete_recursive($path . "/" . $user);
	}
?>
