<?
function active_sync_postfix_config_a()
	{
	$f = "/etc/postfix/main.cf";

	$d = (file_exists($f) === false ? array() : file($f));

	$r = array();

	foreach($d as $i => $l)
		{
		list($l) = array(trim($l));

		list($l, $c) = (strpos($l, "#") === false ? array($l, "") : explode("#", $l, 2));

		list($l, $c) = array(trim($l), trim($c));

		list($k, $v) = (strpos($l, "=") === false ? array("", "") : explode("=", $l, 2));

		list($k, $v) = array(trim($k), trim($v));

		if($k == "")
			continue;

		if($v == "")
			continue;

		$r[$k] = $v;
		}

	return($r);
	}
?>
