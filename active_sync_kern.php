<?
define("ADM_DIR", __DIR__ . "/admin");
define("CRT_DIR", __DIR__ . "/cert");
define("DAT_DIR", __DIR__ . "/data");
define("INC_DIR", __DIR__ . "/kern");
define("LOG_DIR", __DIR__ . "/logs");
define("WEB_DIR", __DIR__ . "/web");
define("ATT_DIR", __DIR__ . "/files");
define("MAN_DIR", __DIR__ . "/man");
#define("DEB_DAT", "");

################################################################################

setlocale(LC_ALL, "de_DE.UTF-8");
date_default_timezone_set("UTC"); # all stored datetime uses utc zone !!!

################################################################################

ini_set("display_errors", "On");
ini_set("error_log", (defined("LOG_DIR") ? LOG_DIR . "/as-error-" . date("Y-m-d") . ".txt" : ini_get("error_log")));
ini_set("error_reporting", E_ALL);
ini_set("log_errors", "On");
ini_set("max_execution_time", 30);

register_shutdown_function("active_sync_timeout");

function active_sync_timeout()
	{
	if(error_get_last() == null)
		return;

	header("HTTP/1.1 200 OK");
	}

################################################################################

include_once(INC_DIR . "/active_sync_load_includes.php");

################################################################################

active_sync_load_includes(INC_DIR);

# sudo find . -type d -exec chmod 0755 {} \; && sudo find . -type f -exec chmod 0644 {} \; && sudo chown www-data:www-data -R *
?>
