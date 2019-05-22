<?
function active_sync_get_mac_address()
	{
	$retval = "00-00-00-00-00-00";

	$folder = "/sys/class/net";

	if(PHP_OS == "Linux")
		{
		foreach(scandir($folder) as $file)
			{
			if(($file == ".") || ($file == ".."))
				continue;

			if(preg_match("/(eth\d*)|(wlan\d*)/", $file, $matches) == 0)
				continue;

			if(file_exists($folder . "/" . $matches[1] . "/operstate") === false)
				continue;

			if(file_get_contents($folder . "/" . $matches[1] . "/operstate") != "up")
				continue;

			if(file_exists($folder . "/" . $matches[1] . "/carrier") === false)
				continue;

			if(file_get_contents($folder . "/" . $matches[1] . "/carrier") != 1)
				continue;

			if(file_exists($folder . "/" . $matches[1] . "/address") === false)
				continue;

			$retval = file_get_contents($folder . "/" . $matches[1] . "/address");

			break;
			}
		}

	$retval = str_replace(array("\n", "\r", "-", ":"), "", $retval);

	return($retval);
	}
?>
