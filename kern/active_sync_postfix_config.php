<?
function active_sync_postfix_config($setting, $default = "")
	{
#	return(exec("sudo postconf -h " . $setting));

	$f = "/etc/postfix/main.cf";

	$d = (file_exists($f) === false ? array() : file($f));

	foreach($d as $i => $l)
		{
		$l = trim($l);

		list($l, $c) = (strpos($l, "#") === false ? array($l, "") : explode("#", $l, 2));

		list($l, $c) = array(trim($l), trim($c));

		list($k, $v) = (strpos($l, "=") === false ? array("", "") : explode("=", $l, 2));

		list($k, $v) = array(trim($k), trim($v));

		if($k == $setting)
			return($v);
		}

	return($default);
	}
?>
