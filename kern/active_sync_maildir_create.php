<?
function active_sync_maildir_create($user = "root")
	{
	$path = active_sync_postfix_virtual_mailbox_base();

	foreach(array("/cur", "/new", "/tmp") as $dir)
		{
		if(is_dir($path . "/" . $user . $dir) === true)
			continue;

		mkdir($path . "/" . $user . $dir, 0777, true);

#		chown($path . "/" . $user . $dir, "mail");
#		chgrp($path . "/" . $user . $dir, "mail");
#		chmod($path . "/" . $user . $dir, octmode(777));
		}
	}
?>
