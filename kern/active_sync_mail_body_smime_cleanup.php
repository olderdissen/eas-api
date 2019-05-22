<?
function active_sync_mail_body_smime_cleanup()
	{
	foreach(array("dec", "enc", "ver") as $extension)
		{
		if(file_exists("/tmp/" . $file . "." . $extension) === false)
			continue;

		unlink("/tmp/" . $file . "." . $extension);
		}
	}
?>
