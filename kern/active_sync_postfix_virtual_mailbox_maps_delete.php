<?
function active_sync_postfix_virtual_mailbox_maps_delete($user)
	{
	$host = active_sync_get_domain();

	$file = active_sync_postfix_virtual_mailbox_maps_db();

	$data = (file_exists($file) === false ? array() : file($file));

	foreach($data as $id => $line)
		{
		list($key, $val) = (strpos($line, " ") === false ? array($line, "") : explode(" ", $line, 2));

		if(trim($key) != $user . "@" . $host)
			continue;

		unset($data[$id]);

		break;
		}

	exec("sudo chmod 0666 " . $file);
	file_put_contents($file, implode("", $data));
	exec("sudo chmod 0644 " . $file);

	exec("sudo postmap " . $file);
	exec("sudo service postfix reload");
	}
?>
