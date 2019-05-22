<?
function active_sync_get_domain()
	{
	$retval = active_sync_postfix_config("mydomain", "localhost");

#	$retval = active_sync_postfix_config("virtual_mailbox_domains", "localhost");
#	$retval = explode(", ", $retval);
#	$retval = $retval[0];

	return($retval);
	}
?>
