<?
function active_sync_debug($expression, $type = "DEBUG")
	{
#	return;

	if(defined("LOG_DIR") === false)
		return(false);

	if(is_dir(LOG_DIR) === false)
		mkdir(LOG_DIR, 0777, true);

	if(defined("DEB_DAT") === false)
		{
		$device_id = date("Y-m-d");
		$device_id = (isset($_GET["DeviceId"]) === false ? $device_id : $_GET["DeviceId"]);

		define("DEB_DAT", fopen(LOG_DIR . "/as-debug-" . $device_id . ".txt", "a+"));
		}

	if(defined("DEB_DAT") === true)
		{
		$d = "TIME: " . date("Y-m-d H:i:s");

		$p = (isset($_SERVER["REMOTE_PORT"]) === false ? "-" : $_SERVER["REMOTE_PORT"]);

		$c = (isset($_GET["Cmd"]) === false ? "-" : $_GET["Cmd"]);

		$e = (strlen($expression) == 0 ? "EMPTY" : $expression);
		$e = (strpos($expression, "\n") === false ? " " : "\n") . $e;

#		$t = print_r(debug_backtrace(), true);
		$t = "";

		# debug_backtrace()[1]['function'];

		fwrite(DEB_DAT, implode(" ", array($d, $p, $type, implode("", array($c, $e, $t, "\n")))));
		}

	if(defined("DEB_DAT") === true)
		{
		}

#	openlog("active-sync", LOG_PID | LOG_PERROR, LOG_SYSLOG);
#	syslog(LOG_NOTICE, $c);
#	closelog();

	return(true);
	}
?>
