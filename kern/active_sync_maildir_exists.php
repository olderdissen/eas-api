<?
function active_sync_maildir_exists($user)
	{
	$path = active_sync_postfix_virtual_mailbox_base();

	return(is_dir($path . "/" . $user) ? 1 : 0);
	}
?>
