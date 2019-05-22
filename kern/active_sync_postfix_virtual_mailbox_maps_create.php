<?
function active_sync_postfix_virtual_mailbox_maps_create($user)
	{
	$host = active_sync_get_domain();

	$file = active_sync_postfix_virtual_mailbox_maps_db();

	$data = (file_exists($file) === false ? array() : file($file));

	$data[] = $user . "@" . $host . " " . $user . "/" . "\n";

	exec("sudo chmod 0666 " . $file);
	file_put_contents($file, implode("", $data));
	exec("sudo chmod 0644 " . $file);

	exec("sudo postmap " . $file);
	exec("sudo /etc/init.d/postfix reload");

	return(1);
	}
?>
