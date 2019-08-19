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

#include_once(INC_DIR . "/active_sync_load_includes.php");

################################################################################

#active_sync_load_includes(INC_DIR);

# sudo find . -type d -exec chmod 0755 {} \; && sudo find . -type f -exec chmod 0644 {} \; && sudo chown www-data:www-data -R *

function active_sync_body_type_exist($data, $type)
	{
	if(isset($data["Body"]))
		foreach($data["Body"] as $body)
			if(isset($body["Type"]))
				if($body["Type"] == $type)
					return(1);

	return(0);
	}

function active_sync_compare_address($data, $expression)
	{
	foreach(array("BusinessAddress", "HomeAddress", "OtherAddress") as $token)
		{
		foreach(array("Country", "State", "City", "PostalCode", "Street") as $key)
			{
			if(isset($data["Contacts"][$token . $key]) === false)
				continue;

			if(strlen($data["Contacts"][$token . $key]) == 0)
				continue;

			$x = $expression;
			$y = $data["Contacts"][$token . $key];

			$x = strtolower($x);
			$y = strtolower($y);

			if(substr($y, 0, strlen($x)) != $x)
				continue;

			return(1);
			}
		}

	return(0);
	}

function active_sync_compare_name($data, $expression)
	{
	# "von der Linden" matches search of "v, d, l"
	# "von Becker" matches search of "v, b"
	# "_briefksten@arcor.de" matches search of "b"

	foreach(array("FirstName", "LastName", "MiddleName", "Email1Address", "Email2Address", "Email3Address", "JobTitle", "CompanyName") as $token)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		if(strlen($data["Contacts"][$token]) == 0)
			continue;

		$x = $expression;
		$y = $data["Contacts"][$token];

		$x = strtolower($x);
		$y = strtolower($y);

		if(substr($y, 0, strlen($x)) != $x)
			continue;

		return(1);
		}

	return(0);
	}

function active_sync_compare_other($data, $expression)
	{
	foreach(array("NickName", "CustomerId") as $token)
		{
		if(isset($data["Contacts2"][$token]) === false)
			continue;

		if(strlen($data["Contacts2"][$token]) == 0)
			continue;

		$x = $expression;
		$y = $data["Contacts2"][$token];

		$x = strtolower($x);
		$y = strtolower($y);

		if(substr($y, 0, strlen($x)) != $x)
			continue;

		return(1);
		}

	return(0);
	}

function active_sync_compare_phone($data, $expression)
	{
	foreach(array("AssistnamePhoneNumber", "CarPhoneNumber", "MobilePhoneNumber", "PagerNumber", "RadioPhoneNumber", "BusinessFaxNumber", "BusinessPhoneNumber", "Business2PhoneNumber", "HomeFaxNumber", "HomePhoneNumber", "Home2PhoneNumber") as $token)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		if(strlen($data["Contacts"][$token]) == 0)
			continue;

		$x = $expression;
		$y = $data["Contacts"][$token];

		$x = active_sync_fix_phone($x);
		$y = active_sync_fix_phone($y);

		$x = strtolower($x);
		$y = strtolower($y);

		if(substr($y, 0, strlen($x)) != $x)
			continue;

		return(1);
		}

	return(0);
	}

function active_sync_create_fullname_from_data($data, $style = 2)
	{
	$style = min($style, 2);
	$style = max($style, 0);

	$styles = array();

	$styles[0] = array("FirstName" => "", "MiddleName" => " ", "LastName" => " ", "Suffix" => " ");
	$styles[1] = array("LastName" => "", "FirstName" => ", ", "MiddleName" => " ", "Suffix" => ", ");
	$styles[2] = array("FirstName" => "", "MiddleName" => " ", "LastName" => " ");

	$retval = array();

	foreach($styles[$style] as $token => $prefix)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		if(strlen($data["Contacts"][$token]) == 0)
			continue;

		if(count($retval) > 0)
			$retval[] = $prefix;

		$retval[] = $data["Contacts"][$token];
		}

	$helper = array();

	foreach(array("YomiLastName" => "", "YomiFirstName" => " ") as $token => $prefix)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		if(strlen($data["Contacts"][$token]) == 0)
			continue;

		if(count($retval) > 0)
			$helper[] = $prefix;

		$helper[] = $data["Contacts"][$token];
		}

	################################################################################
	# add yomi for non email and if we already have some name data
	################################################################################

	if($style != 2)
		if(count($retval) > 0)
			if(count($helper) > 0)
				$retval[] = " <small>" . implode("", $helper) . "</small>";

	################################################################################
	# replace empty full name
	################################################################################

	foreach(array("Contacts2:NickName", "Contacts:CompanyName", "Contacts:JobTitle") as $items)
		{
		if(count($retval) > 0)
			break;

		list($codepage, $token) = explode(":", $items, 2);

		if(isset($data[$codepage][$token]) === false)
			continue;

		if(strlen($data[$codepage][$token]) == 0)
			continue;

		$retval[] = $data[$codepage][$token];
		}

	if(count($retval) == 0)
		$retval[] = "(Unbekannt)";

	return(implode("", $retval));
	}

function active_sync_create_fullname_from_data_for_contacts($data, $style = 0)
	{
	return(active_sync_create_fullname_from_data($data, $style));
	}

function active_sync_create_fullname_from_data_for_email($data, $style = 2)
	{
	return(active_sync_create_fullname_from_data($data, $style));
	}

function active_sync_create_guid($version = 4, $name = "", $namespace = "{00000000-0000-0000-0000-000000000000}")
	{
	# http://de.wikipedia.org/wiki/Universally_Unique_Identifier
	# http://de.wikipedia.org/wiki/Globally_Unique_Identifier
	# /proc/sys/kernel/random/uuid

	#  0	time_low			uint32_t	Zeitstempel, niederwertigste 32 Bits
	#  4	time_mid			uint16_t	Zeitstempel, mittlere 16 Bits
	#  6	time_hi_and_version		uint16_t	Oberste Bits des Zeitstempels in den unteren 12 Bits des Feldes, die oberen 4 Bits dienen als Versionsbezeichner
	#  8	clock_seq_high_and_reserved	uint8_t		Oberste 6 Bits der Clocksequenz (die obersten 2 Bits des Feldes sind in der hier beschriebenen UUID-Variante stets 1 0)
	#  9	clock_seq_low			uint8_t		Untere 8 Bits der Clocksequenz
	# 10	node				uint48_t	Eindeutige Node-Identifikationsnummer

	################################################################################

#		exec("uuid -v 1", $output);
#		exec("uuid -v 3 " . $namespace . " " . $name, $output);
#		exec("uuid -v 4", $output);
#		exec("uuid -v 5 " . $namespace . " " . $name, $output);

	################################################################################
	# set default values
	################################################################################

	$time_low			= "00000000";
	$time_mid			= "0000";
	$time_hi_and_version		= "0000";
	$clock_seq_high_and_reserved	= "80";
	$clock_seq_low			= "00";
	$node				= "000000000000";

	################################################################################
	# time-based version
	################################################################################

	if($version == 1)
		{
		$time = gettimeofday();
		$time = ($time["sec"] * 10 * 1000 * 1000) + ($time["usec"] * 10) + 0x01B21DD213814000;

		$time_low			= ((intval($time / 0x00000001) >>  0) & 0xFFFFFFFF);
		$time_mid			= ((intval($time / 0xFFFFFFFF) >>  0) & 0x0000FFFF);
		$time_hi_and_version		= ((intval($time / 0xFFFFFFFF) >> 16) & 0x00000FFF);

		$time_low			= sprintf("%08x", $time_low);
		$time_mid			= sprintf("%04x", $time_mid);
		$time_hi_and_version		= sprintf("%04x", $time_hi_and_version | ($version << 12));
		$clock_seq_high_and_reserved	= "80";
		$clock_seq_low			= "00";
		$node				= "000000000000";
		}

	################################################################################
	# DCE Security version, with embedded POSIX UIDs
	################################################################################

	if($version == 2)
		{
		$time_low			= "00000000";
		$time_mid			= "0000";
		$time_hi_and_version		= "2000";
		$clock_seq_high_and_reserved	= "80";
		$clock_seq_low			= "00";
		$node				= "000000000000";
		}

	################################################################################
	# name-based version that uses MD5 hashing
	################################################################################

	if($version == 3)
		{
		$namespace = active_sync_namespace_to_string($namespace);

		$hash = md5($namespace . $name);

		$time_low			= sprintf("%04x%04x", hexdec(substr($hash, 0, 4)), hexdec(substr($hash, 4, 4)));
		$time_mid			= sprintf("%04x", hexdec(substr($hash, 8, 4)));
		$time_hi_and_version		= sprintf("%04x", (hexdec(substr($hash, 12, 4)) & 0x0FFF) | ($version << 12));
		$clock_seq_high_and_reserved	= sprintf("%02x", (hexdec(substr($hash, 16, 2)) & 0x3F) | 0x80);
		$clock_seq_low			= sprintf("%02x", hexdec(substr($hash, 18, 2)));
		$node				= sprintf("%04x%04x%04x", hexdec(substr($hash, 20, 4)), hexdec(substr($hash, 24, 4)), hexdec(substr($hash, 28, 4)));
		}

	################################################################################
	# randomly or pseudo-randomly generated version
	################################################################################

	if($version == 4)
		{
		$time_low			= sprintf("%04x%04x", rand(0x0000, 0xFFFF), rand(0x0000, 0xFFFF));
		$time_mid			= sprintf("%04x", rand(0x0000, 0xFFFF));
		$time_hi_and_version		= sprintf("%04x", rand(0x0000, 0x0FFF) | ($version << 12));
		$clock_seq_high_and_reserved	= sprintf("%02x", rand(0x00, 0x3F) | 0x80);
		$clock_seq_low			= sprintf("%02x", rand(0x00, 0xFF));
		$node				= sprintf("%04x%04x%04x", rand(0x0000, 0xFFFF), rand(0x0000, 0xFFFF), rand(0x0000, 0xFFFF));
		}

	################################################################################
	# name-based version that uses SHA-1 hashing
	################################################################################

	if($version == 5)
		{
		$namespace = active_sync_namespace_to_string($namespace);

		$hash = sha1($namespace . $name);

		$time_low			= sprintf("%04x%04x", hexdec(substr($hash, 0, 4)), hexdec(substr($hash, 4, 4)));
		$time_mid			= sprintf("%04x", hexdec(substr($hash, 8, 4)));
		$time_hi_and_version		= sprintf("%04x", (hexdec(substr($hash, 12, 4)) & 0x0FFF) | ($version << 12));
		$clock_seq_high_and_reserved	= sprintf("%02x", (hexdec(substr($hash, 16, 2)) & 0x3F) | 0x80);
		$clock_seq_low			= sprintf("%02x", hexdec(substr($hash, 18, 2)));
		$node				= sprintf("%04x%04x%04x", hexdec(substr($hash, 20, 4)), hexdec(substr($hash, 24, 4)), hexdec(substr($hash, 28, 4)));
		}

	################################################################################
	# glue and return value
	################################################################################

	return(implode("-", array($time_low, $time_mid, $time_hi_and_version, $clock_seq_high_and_reserved . $clock_seq_low, $node)));
	}

function active_sync_create_guid_filename($user, $collection_id)
	{
	$count = 0;

	while(1)
		{
		$server_id = active_sync_create_guid();

		if(file_exists(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data") === false)
			return($server_id);

		$count ++;

		if($count > 20)
			break;
		}

	active_sync_debug("failed to create a unique filename");

	return(0);
	}

define("ACTIVE_SYNC_DATA_DECODE", 0 - 1);
define("ACTIVE_SYNC_DATA_ENCODE", 0 + 1);

function active_sync_data_code($string, $key, $direction)
	{
	$direction = ($direction < 0 - 1 ? 0 - 1 : $direction);
	$direction = ($direction > 0 + 1 ? 0 + 1 : $direction);

	for($position = 0; $position < strlen($string); $position ++)
		$string[$position] = chr((0x0100 + ord($string[$position]) + (ord($key[$position % strlen($key)]) * $direction)) % 0x0100);

	return($string);
	}

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

	if(defined("DEB_DAT"))
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

	if(defined("DEB_DAT"))
		{
		}

#	openlog("active-sync", LOG_PID | LOG_PERROR, LOG_SYSLOG);
#	syslog(LOG_NOTICE, $c);
#	closelog();

	return(true);
	}

function active_sync_fix_phone($string)
	{
	$retval = array();

	for($position = 0; $position < strlen($string); $position ++)
		{
		if(strpos("+0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", strtoupper($string[$position])) === false)
			continue;

		$retval[] = $string[$position];
		}

	return(implode("", $retval));
	}

function active_sync_fix_street($string)
	{
	$words = explode(" ", $string);

	if(is_numeric(substr(end($words), 0, 1)))
		array_pop($words);

	return(implode(" ", $words));
	}

function active_sync_folder_create($user, $parent_id, $display_name, $type)
	{
	if(active_sync_get_is_collection_id($user, $parent_id) == 0)
		return(5);

	if(active_sync_get_is_display_name($user, $display_name) == 1)
		return(2);

	if(active_sync_get_is_type($type) == 0)
		return(10);

	if(active_sync_get_is_special_folder($type) == 1)
		return(3);

	if(active_sync_get_is_user_folder($type) == 0)
		return(3);

	$server_id = active_sync_get_folder_free($user);

	if($server_id == 0)
		return(6);

	if(mkdir(DAT_DIR . "/" . $user . "/" . $server_id, 0777, true) === false)
		return(6);

	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync"); # observe order of parameters

	foreach(array("SyncDat" => array()) as $key => $value)
		$settings_server[$key] = (isset($settings_server[$key]) ? $settings_server[$key] : $value);

	$settings_server["SyncDat"][] = array("ServerId" => $server_id, "ParentId" => $parent_id, "Type" => $type, "DisplayName" => $display_name);

	active_sync_put_settings(DAT_DIR . "/" . $user . ".sync", $settings_server); # observe order of parameters

	return(1);
	}

function active_sync_folder_delete($user, $server_id)
	{
	if(active_sync_get_is_collection_id($user, $server_id) == 0)
		return(4);

	$type = active_sync_get_type_by_collection_id($user, $server_id);

	if(active_sync_get_is_special_folder($type) == 1)
		return(3);

	if(active_sync_get_is_user_folder($type) == 0)
		return(3);

	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync"); # observe order of parameters

	foreach(array("SyncDat" => array()) as $key => $value)
		$settings_server[$key] = (isset($settings_server[$key]) ? $settings_server[$key] : $value);

	active_sync_folder_delete_helper($settings_server, $user, $server_id);

	active_sync_put_settings(DAT_DIR . "/" . $user . ".sync", $settings_server); # observe order of parameters

	return(1);
	}

function active_sync_folder_delete_helper(& $folders, $user, $server_id)
	{
	foreach($folders["SyncDat"] as $id => $folder)
		{
		if($folder["ParentId"] == $server_id)
			active_sync_folder_delete_helper($folders, $user, $folder["ServerId"]);

		if($folder["ServerId"] == $server_id)
			unset($folders["SyncDat"][$id]);
		}

	foreach(scandir(DAT_DIR . "/" . $user . "/" . $server_id) as $file)
		{
		if(is_dir(DAT_DIR . "/" . $user . "/" . $server_id . "/" . $file))
			continue;

		unlink(DAT_DIR . "/" . $user . "/" . $server_id . "/" . $file);
		}

	rmdir(DAT_DIR . "/" . $user . "/" . $server_id);
	}

function active_sync_folder_init($user)
	{
	if(defined("DAT_DIR") === false)
		die("DAT_DIR is not defined. have you included active_sync_kern.php before? DAT_DIR is needed to store settings and user data.");

	if(is_dir(DAT_DIR) === false)
		mkdir(DAT_DIR, 0777, true);

	if(file_exists(DAT_DIR . "/.htaccess") === false)
		{
		$data = array
			(
			"<Files \"*\">",
			"\tOrder allow,deny",
			"\tDeny from all",
			"</Files>"
			);

		file_put_contents(DAT_DIR . "/.htaccess", implode("\n", $data));
		}

	if(is_dir(DAT_DIR . "/" . $user) === false)
		mkdir(DAT_DIR . "/" . $user, 0777, true);

	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync"); # observe order of parameters

	foreach(array("SyncDat" => array()) as $key => $value)
		$settings_server[$key] = (isset($settings_server[$key]) ? $settings_server[$key] : $value);

	if(count($settings_server["SyncDat"]) == 0)
		{
		$settings_server["SyncDat"] = active_sync_get_default_folder();

		active_sync_put_settings(DAT_DIR . "/" . $user . ".sync", $settings_server); # observe order of parameters
		}

	foreach($settings_server["SyncDat"] as $id => $folder)
		{
		if(is_dir(DAT_DIR . "/" . $user . "/" . $folder["ServerId"]))
			continue;

		mkdir(DAT_DIR . "/" . $user . "/" . $folder["ServerId"], 0777, true);
		}

	if(defined("LOG_DIR") === false)
		return;

	if(is_dir(LOG_DIR) === false)
		mkdir(LOG_DIR, 0777, true);
	}

function active_sync_folder_update($user, $server_id, $parent_id, $display_name) # bogus ? cannot rename system folder
	{
	if(active_sync_get_is_collection_id($user, $server_id) == 0)
		return(4);

	if(active_sync_get_is_collection_id($user, $parent_id) == 0)
		return(5);

	if(active_sync_get_is_display_name($user, $display_name) == 1)
		return(2);

	$type = active_sync_get_type_by_collection_id($user, $server_id);

	if($type == 19)
		return(3);

	if(active_sync_get_is_special_folder($type) == 1)
		return(2);

	if(active_sync_put_display_name($user, $server_id, $display_name) == 0)
		return(6);

	if(active_sync_put_parent_id($user, $server_id, $parent_id) == 0)
		return(6);

	return(1);
	}

function active_sync_get_body_by_type($data, $type)
	{
	if(isset($data["Body"]))
		foreach($data["Body"] as $body)
			if(isset($body["Type"]))
				if($body["Type"] == $type)
					if(isset($body["Data"]))
						return($body);

	return(false);
	}

function active_sync_get_calendar_by_uid($user, $uid)
	{
	$collection_id = active_sync_get_collection_id_by_type($user, 8); # Calendar ::= 8 | 14

	$settings_server = active_sync_get_settings_sync($user, $collection_id, "");

	foreach($settings_server["SyncDat"] as $server_id => $timestamp)
		{
		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		if(isset($data["Calendar"]["UID"]))
			if($data["Calendar"]["UID"] == $uid)
				return($server_id);
		}

	return("");
	}

function active_sync_get_categories_by_collection_id($user_id, $collection_id)
	{
	$retval = array("*" => 0); # this is placeholder to count contacts without category

	foreach(glob(DAT_DIR . "/" . $user_id . "/" . $collection_id . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($user_id, $collection_id, $server_id);

		if(isset($data["Categories"]) === false)
			$retval["*"] ++;
		elseif(count($data["Categories"]) == 0)
			$retval["*"] ++;
		else
			foreach($data["Categories"] as $id => $category)
				if(isset($retval[$category]) === false)
					$retval[$category] = 1;
				else
					$retval[$category] ++;
		}

	if(count($retval) > 1)
		ksort($retval, SORT_LOCALE_STRING);

	return($retval);
	}

function active_sync_get_class_by_collection_id($user, $collection_id)
	{
	$type = active_sync_get_type_by_collection_id($user, $collection_id);

	$class = active_sync_get_class_by_type($type);

	return($class);
	}

function active_sync_get_class_by_type($type)
	{
	# AS-MSCMD - 2.2.3.170.2 - Type (FolderCreate)
	# AS-MSCMD - 2.2.3.170.3 - Type (FolderSync)

	$classes = array
		(
		);

	$classes[1] = "";		# User-created folder (generic)
	$classes[2] = "Email";		# Default Inbox folder
	$classes[3] = "Email";		# Default Drafts folder
	$classes[4] = "Email";		# Default Deleted Items folder
	$classes[5] = "Email";		# Default Sent Items folder
	$classes[6] = "Email";		# Default Outbox folder
	$classes[7] = "Tasks";		# Default Tasks folder
	$classes[8] = "Calendar";	# Default Calendar folder
	$classes[9] = "Contacts";	# Default Contacts folder
	$classes[10] = "Notes";		# Default Notes folder
	$classes[11] = "Journal";	# Default Journal folder
	$classes[12] = "Email";		# User-created Mail folder
	$classes[13] = "Calendar";	# User-created Calendar folder
	$classes[14] = "Contacts";	# User-created Contacts folder
	$classes[15] = "Tasks";		# User-created Tasks folder
	$classes[16] = "Journal";	# User-created Journal folder
	$classes[17] = "Notes";		# User-created Notes folder
	$classes[18] = "";		# Unknown folder type
	$classes[19] = "";		# Recipient information cache

	$type = (($type < 1) || ($type > 19) ? 18 : $type);

	return($classes[$type]);
	}

function active_sync_get_collection_id_by_display_name($user, $display_name)
	{
	$folders = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($folders["SyncDat"] as $folder)
		if($folder["DisplayName"] == $display_name)
			return($folder["ServerId"]);

	return(0);
	}

function active_sync_get_collection_id_by_type($user, $type)
	{
	$folders = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($folders["SyncDat"] as $folder)
		if($folder["Type"] == $type)
			return($folder["ServerId"]);

	return(0);
	}

function active_sync_get_default_attachment()
	{
	$retval = array
		(
		"AttMethod"		=> "",	# 2.5
		"AttName"		=> "",	# 2.5
		"AttOid"		=> "",	# 2.5
		"AttSize"		=> "",	# 2.5

		"ContentId"		=> "",	# 12.0, 12.1, 14.0, 14.1
		"ContentLocation"	=> "",	# 12.0, 12.1, 14.0, 14.1
		"DisplayName"		=> "",	# 12.0, 12.1, 14.0, 14.1
		"EstimatedDataSize"	=> 0,	# 12.0, 12.1, 14.0, 14.1
		"FileReference"		=> "",	# 12.0, 12.1, 14.0, 14.1
		"IsInline"		=> 0,	# 12.0, 12.1, 14.0, 14.1
		"Method"		=> 1,	# 12.0, 12.1, 14.0, 14.1

		"UmAttDuration"		=> 0,	# 14.0, 14.1
		"UmAttOrder"		=> 0,	# 14.0, 14.1
		"UmCallerID"		=> 0,	# 14.0, 14.1
		"UmUserNotes"		=> 0	# 14.0, 14.1
		);

	return($retval);
	}

function active_sync_get_default_attendee()
	{
	$retval = array
		(
		"AttendeeStatus"	=> 0, # 0 Response unknown | 2 tentative | 3 accept | 4 decline | 5 not responded
		"AttendeeType"		=> 1, # 1 required | 2 optional | 3 resource
		"Email"			=> "",
		"Name"			=> ""
		);


	return($retval);
	}

function active_sync_get_default_body()
	{
	$retval = array
		(
		"Data"			=> "",
		"EstimatedDataSize"	=> 0,
		"Type"			=> 1
		);

	return($retval);
	}

function active_sync_get_default_calendar()
	{
	$retval = array
		(
		"TimeZone"			=> "xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==",
		"AllDayEvent"			=> 0, # 0 is not an all day event | 1 is an all day event
		# Body
		"BodyTruncated"			=> 0,
		"BusyStatus"			=> 2, # 0 free | 1 tentative | 2 busy | out of office
		"OrganizerName"			=> "",
		"OrganizerEmail"		=> "",
		"DtStamp"			=> date("Y-m-d\TH:i:s\Z"),
		"EndTime"			=> date("Y-m-d\TH:i:s\Z"),
		"Location"			=> "",
		"Reminder"			=> 0,
		"Sensitivity"			=> 0, # 0 normal | 1 personal | 2 private | 3 confidential
		"Subject"			=> "",
		"StartTime"			=> date("Y-m-d\TH:i:s\Z"),
		"UID"				=> active_sync_create_guid(),
		"MeetingStatus"			=> 0, # 0 is not a meeting | 1 is a meeting | 3 meeting received | 5 meeting is canceled | 7 meeting is canceled and received | 9 => 1 | 11 => 3 | 13 => 5 | 15 => 7 ... as bitfield: 0x01 meeting, 0x02 received, 0x04 canceled
		# Attendees
		# Categories
		# Recurrences
		# Exceptions
		"ResponseRequested"		=> 0,
		"AppointmentReplyTime"		=> "",
		"ResponseType"			=> 0, # 0 none | 1 organizer | 2 tentative | 3 accepted | 4 declined | 5 not responded
		"DisallowNewTimeProposal"	=> 0,
		"OnlineMeetingConfLink"		=> "",
		"OnlineMeetingExternalLink"	=> ""
		);

	return($retval);
	}

function active_sync_get_default_contacts($Class = "Contact")
	{
	$retval = array();

	if($Class == "Contact")
		{
		$retval["Anniversary"]			= "";
		$retval["AssistantName"]		= "";
		$retval["AssistnamePhoneNumber"]	= "";
		$retval["Birthday"]			= "";
		# Body
		# BodySize
		# BodyTruncated
		$retval["Business2PhoneNumber"]		= "";
		$retval["BusinessAddressCity"]		= "";
		$retval["BusinessPhoneNumber"]		= "";
		$retval["WebPage"]			= "";
		$retval["BusinessAddressCountry"]	= "";
		$retval["Department"]			= "";
		$retval["Email1Address"]		= "";
		$retval["Email2Address"]		= "";
		$retval["Email3Address"]		= "";
		$retval["BusinessFaxNumber"]		= "";
		$retval["FileAs"]			= "";
		$retval["Alias"]			= "";
		$retval["WeightedRank"]			= "";
		$retval["FirstName"]			= "";
		$retval["MiddleName"]			= "";
		$retval["HomeAddressCity"]		= "";
		$retval["HomeAddressCountry"]		= "";
		$retval["HomeFaxNumber"]		= "";
		$retval["HomePhoneNumber"]		= "";
		$retval["Home2PhoneNumber"]		= "";
		$retval["HomeAddressPostalCode"]	= "";
		$retval["HomeAddressState"]		= "";
		$retval["HomeAddressStreet"]		= "";
		$retval["MobilePhoneNumber"]		= "";
		$retval["Suffix"]			= "";
		$retval["CompanyName"]			= "";
		$retval["OtherAddressCity"]		= "";
		$retval["OtherAddressCountry"]		= "";
		$retval["CarPhoneNumber"]		= "";
		$retval["OtherAddressPostalCode"]	= "";
		$retval["OtherAddressState"]		= "";
		$retval["OtherAddressStreet"]		= "";
		$retval["PagerNumber"]			= "";
		$retval["Title"]			= "";
		$retval["BusinessAddressPostalCode"]	= "";
		$retval["LastName"]			= "";
		$retval["Spouse"]			= "";
		$retval["BusinessAddressState"]		= "";
		$retval["BusinessAddressStreet"]	= "";
		$retval["JobTitle"]			= "";
		$retval["YomiFirstName"]		= "";
		$retval["YomiLastName"]			= "";
		$retval["YomiCompanyName"]		= "";
		$retval["OfficeLocation"]		= "";
		$retval["RadioPhoneNumber"]		= "";
		$retval["Picture"]			= "";
		# Categories
		# Children
		}

	if($Class == "RIC")
		{
		$retval["Alias"]			= "";
		$retval["FileAs"]			= "";
		$retval["WeightedRank"]			= "";
		$retval["Email1Address"]		= "";
		}

	return($retval);
	}

function active_sync_get_default_contacts2()
	{
	$retval = array
		(
		"CustomerId"		=> "",
		"GovernmentId"		=> "",
		"IMAddress"		=> "",
		"IMAddress2"		=> "",
		"IMAddress3"		=> "",
		"ManagerName"		=> "",
		"CompanyMainPhone"	=> "",
		"AccountName"		=> "",
		"NickName"		=> "",
		"MMS"			=> ""
		);

	return($retval);
	}

function active_sync_get_default_email()
	{
	$retval = array
		(
		"To"			=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"Cc"			=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"From"			=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"Subject"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"ReplyTo"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"DateReceived"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"DisplayTo"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"ThreadTopic"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"Importance"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"Read"			=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"MessageClass"		=> "IPM.Note", # 2.5, 12.0, 12.1, 14.0, 14.1
		# MeetingRequest
		"InternetCPID"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		# Flag
		"ContentClass"		=> "urn:content-classes:message", # 2.5, 12.0, 12.1, 14.0, 14.1
		# Categories
		# Attachments
		# Body
		# BodySize
		# BodyTruncated
		"MIMEData"		=> "", # 2.5
		"MIMESize"		=> 0, # 2.5
		"MIMETruncated"		=> 0 # 2.5
		);

	return($retval);
	}

function active_sync_get_default_email2()
	{
	$retval = array
		(
		# UmCallerID
		# UmUserNotes
		# UmAttDuration
		# UmAttOrder
		"ConversationId"		=> "", # 14.0, 14.1
		"ConversationIndex"		=> "", # 14.0, 14.1
		"LastVerbExecuted"		=> "", # 14.0, 14.1
		"LastVerbExecutionTime"		=> "", # 14.0, 14.1
		"ReceivedAsBcc"			=> 0, # 14.0, 14.1
		"Sender"			=> "", # 14.0, 14.1
		# CalendarType
		# IsLeapMonth
		"AccountId"			=> "" # 14.1
		# MeetingMessageType
		# Bcc
		# IsDraft
		# Send
		);

	return($retval);
	}

function active_sync_get_default_exception()
	{
	$retval = array
		(
		"Deleted"		=> 0,
		"ExceptionStartTime"	=> "",
		"EndTime"		=> "",
		"Location"		=> "",
		"Sensitivity"		=> "",
		"BusyStatus"		=> "",
		"AllDayEvent"		=> 0,
		"Reminder"		=> 1440,
		"DTStamp"		=> "",
		"MeetingStatus"		=> "",
		"AppointmentReplyTime"	=> "",
		"ResponseType"		=> ""
		);

	return($retval);
	}

function active_sync_get_default_filter()
	{
	$retval = array
		(
		"Email"		=> array(0, 1, 2, 3, 4, 5),
		"Calendar"	=> array(1, 4, 5, 6, 7),
		"Tasks"		=> array(0, 8)
		);

	# 0 all
	# 1 1d
	# 2 3d
	# 3 1w
	# 4 2w
	# 5 1m
	# 6 3m
	# 7 6m
	# 8 incomplete

	return($retval);
	}

function active_sync_get_default_flag($Class = "Tasks")
	{
	$retval = array();

	if($Class == "Email")
		{
		$retval["CompleteTime"]		= "";
		$retval["FlagType"]		= "";
		$retval["Status"]		= "";
		}

	if($Class == "Tasks")
		{
		$retval["DateCompleted"]	= "";
		$retval["DueDate"]		= "";
		$retval["OrdinalDate"]		= "";
		$retval["ReminderSet"]		= "";
		$retval["ReminderTime"]		= "";
		$retval["StartDate"]		= "";
		$retval["Subject"]		= "";
		$retval["SubOrdinalDate"]	= "";
		$retval["UtcDueDate"]		= "";
		$retval["UtcStartDate"]		= "";
		}

	return($retval);
	}

function active_sync_get_default_folder()
	{
	#  1 User-created folder (generic)
	#  2 Default Inbox folder
	#  3 Default Drafts folder
	#  4 Default Deleted Items folder
	#  5 Default Sent Items folder
	#  6 Default Outbox folder
	#  7 Default Tasks folder
	#  8 Default Calendar folder
	#  9 Default Contacts folder
	# 10 Default Notes folder
	# 11 Default Journal folder
	# 12 User-created Mail folder
	# 13 User-created Notes folder
	# 14 User-created Calendar folder
	# 15 User-created Contacts folder
	# 16 User-created Tasks folder
	# 17 User-created journal folder
	# 18 Unknown folder type
	# 19 Recipient information cache

	$retval = array
		(
		array
			(
			"ServerId" => 9002,
			"ParentId" => 0,
			"Type" => 2,
			"DisplayName" => "Inbox"
			),
		array
			(
			"ServerId" => 9003,
			"ParentId" => 0,
			"Type" => 3,
			"DisplayName" => "Drafts"
			),
		array
			(
			"ServerId" => 9004,
			"ParentId" => 0,
			"Type" => 4,
			"DisplayName" => "Deleted Items"
			),
		array
			(
			"ServerId" => 9005,
			"ParentId" => 0,
			"Type" => 5,
			"DisplayName" => "Sent Items"
			),
		array
			(
			"ServerId" => 9006,
			"ParentId" => 0,
			"Type" => 6,
			"DisplayName" => "Outbox"
			),

		array
			(
			"ServerId" => 9007,
			"ParentId" => 0,
			"Type" => 7,
			"DisplayName" => "Tasks"
			),
		array
			(
			"ServerId" => 9008,
			"ParentId" => 0,
			"Type" => 8,
			"DisplayName" => "Calendar"
			),
		array
			(
			"ServerId" => 9009,
			"ParentId" => 0,
			"Type" => 9,
			"DisplayName" => "Contacts"
			),
		array
			(
			"ServerId" => 9010,
			"ParentId" => 0,
			"Type" => 10,
			"DisplayName" => "Notes"
			),

#		array("ServerId" => 9011, "ParentId" => 0, "Type" => 11, "DisplayName" => "Journal"),
#		array("ServerId" => 9019, "ParentId" => 0, "Type" => 19, "DisplayName" => "Recipient Information")
		);

	return($retval);
	}

function active_sync_get_default_info()
	{
	$retval = array
		(
		"Model"			=> "",
		"Imei"			=> "",
		"FriendlyName"		=> "",
		"OS"			=> "",
		"OSLanguage"		=> "",
		"PhoneNumber"		=> "",
		"UserAgent"		=> "",
		"EnableOutboundSMS"	=> 1,
		"MobileOperator"	=> ""
		);

	return($retval);
	}

function active_sync_get_default_location()
	{
	$retval = array
		(
		"Accuracy"		=> 1.00,
		"Altitude"		=> 90.00,
		"AltitudeAccuracy"	=> 10.00,
		"Annotation"		=> "...",
		"City"			=> "Bielefeld",
		"Country"		=> "Deutschland",
		"DisplayName"		=> "...",
		"Latitude"		=> 52.02836,
		"LocationUri"		=> "https://geo.olderdissen.ro/?zoom=19&lat=52.0284&lon=8.6112",
		"Longitude"		=> 8.61102,
		"PostalCode"		=> "33719",
		"State"			=> "Nordrhein-Westfalen",
		"Street"		=> "Gustav-Bastert-Straße"
		);

	return($retval);
	}

function active_sync_get_default_login()
	{
	$retval = array
		(
		"User"		=> "",
		"Pass"		=> "",
		"IsAdmin"	=> "F",

		"DisplayName"	=> "",
		"FirstName"	=> "",
		"LastName"	=> ""
		);

	return($retval);
	}

function active_sync_get_default_meeting()
	{
	$retval = array
		(
		"AllDayEvent"			=> 0,
		"StartTime"			=> date("Y-m-d\TH:i:s\Z"),
		"DtStamp"			=> date("Y-m-d\TH:i:s\Z"),
		"EndTime"			=> date("Y-m-d\TH:i:s\Z"),
		"InstanceType"			=> 0,
		"Location"			=> "",
		"Organizer"			=> "",
		"RecurrenceId"			=> "",
		"Reminder"			=> "",
		"ResponseRequested"		=> 1,
		"Sensitivity"			=> 0,
		"BusyStatus"			=> 2,
		"TimeZone"			=> "",
		"GlobalObjId"			=> "",
		"DisallowNewTimeProposal"	=> 0,
#		"MeetingMessageType"		=> 0,
#		"MeetingStatus"			=> 0,
#		"Recurrences"			=> "", # is group of Recurrence*

#		"Calendar/UID"			=> "00000000-0000-0000-0000-000000000000"
		);

	return($retval);
	}

function active_sync_get_default_notes()
	{
	$retval = array
		(
		"Subject"		=> "",
		"MessageClass"		=> "IPM.StickyNote",
		"LastModifiedDate"	=> date("Y-m-d\TH:i:s\Z")
		# Categories
		);

	return($retval);
	}

function active_sync_get_default_policy()
	{
	$retval = array
		(
		"AllowBluetooth"				=> 2,	# 0 | 1 | 2
		"AllowBrowser"					=> 1,	# 0 | 1
		"AllowCamera"					=> 1,	# 0 | 1
		"AllowConsumerEmail"				=> 1,	# 0 | 1
		"AllowDesktopSync"				=> 1,	# 0 | 1
		"AllowHTMLEmail"				=> 1,	# 0 | 1
		"AllowInternetSharing"				=> 1,	# 0 | 1
		"AllowIrDA"					=> 1,	# 0 | 1
		"AllowPOPIMAPEmail"				=> 1,	# 0 | 1
		"AllowRemoteDesktop"				=> 1,	# 0 | 1
		"AllowSimpleDevicePassword" 			=> 1,	# 0 | 1
		"AllowSMIMEEncryptionAlgorithmNegotiation"	=> 2,	# 0 | 1 | 2
		"AllowSMIMESoftCerts"				=> 1,	# 0 | 1
		"AllowStorageCard"				=> 1,	# 0 | 1
		"AllowTextMessaging"				=> 1,	# 0 | 1
		"AllowUnsignedApplications"			=> 1,	# 0 | 1
		"AllowUnsignedInstallationPackages"		=> 1,	# 0 | 1
		"AllowWiFi"					=> 1,	# 0 | 1
		"AlphanumericDevicePasswordRequired"		=> 0,	# 0 | 1
		"ApprovedApplicationList"			=> "",	# Hash
		"AttachmentsEnabled"				=> 1,	# 0 | 1
		"DevicePasswordEnabled"				=> 0,	# 0 | 1
		"DevicePasswordExpiration"			=> 7,	# 0 .. x
		"DevicePasswordHistory"				=> 52,	# 0 .. x
		"MaxAttachmentSize"				=> 0,	# 0 .. x
		"MaxCalendarAgeFilter"				=> 0,	# 0 (all days) | 4 (two weeks) | 5 (one month) | 6 (three months) | 7 (six months)
		"MaxDevicePasswordFailedAttempts"		=> 4,	# 4 .. 16
		"MaxEmailAgeFilter"				=> 0,	# 0 (sync all) | 1 (one day) | 2 (three days) | 3 (one week) | 4 (two weeks) | 5 (one month)
		"MaxEmailBodyTruncationSize"			=> 0,	# -1 (no truncation) | 0 (truncate only the header) | 1 .. x (truncate the e-mail body to the specified size)
		"MaxEmailHTMLBodyTruncationSize"		=> 0,	# -1 (no truncation) | 0 (truncate only the header) | 1 .. x (truncate the e-mail body to the specified size)
		"MaxInactivityTimeDeviceLock"			=> 30,	# 0 .. 9998 | 9999 (infinite)
		"MinDevicePasswordComplexCharacters"		=> 1,	# 1 .. 4
		"MinDevicePasswordLength"			=> 1,	# 1 (no limit) | 2 .. 16
		"PasswordRecoveryEnabled"			=> 1,	# 0 | 1
		"RequireDeviceEncryption"			=> 0,	# 0 | 1
		"RequireEncryptedSMIMEMessages"			=> 0,	# 0 | 1
		"RequireEncryptionSMIMEAlgorithm"		=> 0,	# 0 | 1
		"RequireManualSyncWhenRoaming"			=> 0,	# 0 | 1
		"RequireSignedSMIMEAlgorithm"			=> 0,	# 0 | 1
		"RequireSignedSMIMEMessages"			=> 0,	# 0 | 1
		"RequireStorageCardEncryption"			=> 0,	# 0 | 1
		"UnapprovedInROMApplicationList"		=> ""	# ApplicationName
		);

	return($retval);
	}

function active_sync_get_default_recurrence($Class = "Calendar")
	{
	$retval = array
		(
		);

	if($Class == "Calendar")
		{
		$retval["Type"]			= 4;	# 0 .. 3 | 4 (none) | 5 .. 6
		$retval["Occurrences"]		= 1;	# 1 .. 999
		$retval["Interval"]		= 1;	# 1 .. 999
		$retval["WeekOfMonth"]		= 1;	# 1 (first) .. 4 (fourth) | 5 (last)
		$retval["DayOfWeek"]		= 0;	# 1 | 2 | 4 | 8 | 16 | 32 | 64  127
		$retval["MonthOfYear"]		= 1;	# 1 .. 12
		$retval["Until"]		= date("d.m.Y", strtotime("+ 10 years"));
		$retval["DayOfMonth"]		= 1;	# 1 .. 31
		$retval["CalendarType"]		= 0;	# default
		$retval["IsLeapMonth"]		= 0;	# 0 | 1
		$retval["FirstDayOfWeek"]	= 1;	# 0 (sunday) .. 6 (saturday)
		}

	if($Class == "Email")
		{
		$retval["Type"]			= 4;	# 0 .. 3 | 4 (none) | 5 .. 6
		$retval["Interval"]		= 1;	# 1 .. 999
		$retval["Until"]		= date("d.m.Y", strtotime("+ 10 years"));
		$retval["Occurrences"]		= 1;	# 1 .. 999
		$retval["WeekOfMonth"]		= 1;	# 1 (first) .. 4 (fourth) | 5 (last)
		$retval["DayOfMonth"]		= 1;	# 1 .. 31
		$retval["DayOfWeek"]		= 0;	# 1 | 2 | 4 | 8 | 16 | 32 | 64  127
		$retval["MonthOfYear"]		= 1;	# 1 .. 12

		# email2 !!!
		$retval["CalendarType"]		= 0;	# default
		$retval["IsLeapMonth"]		= 0;	# 0 | 1
		$retval["FirstDayOfWeek"]	= 1;	# 0 (sunday) .. 6 (saturday)
		}

	if($Class == "Tasks")
		{
		$retval["Type"]			= 4;	# 0 .. 3 | 4 (none) | 5 .. 6
		$retval["Start"]		= date("d.m.Y H:i");
		$retval["Until"]		= date("d.m.Y", strtotime("+ 10 years"));
		$retval["Occurrences"]		= 1;	# 1 .. 999
		$retval["Interval"]		= 1;	# 1 .. 999
		$retval["DayOfWeek"]		= 0;	# 1 | 2 | 4 | 8 | 16 | 32 | 64  127
		$retval["DayOfMonth"]		= 1;	# 1 .. 31
		$retval["WeekOfMonth"]		= 1;	# 1 (first) .. 4 (fourth) | 5 (last)
		$retval["MonthOfYear"]		= 1;	# 1 .. 12
		$retval["Regenerate"]		= 0;
		$retval["DeadOccur"]		= 0;
		$retval["CalendarType"]		= 0;	# default
		$retval["IsLeapMonth"]		= 0;	# 0 | 1
		$retval["FirstDayOfWeek"]	= 1;	# 0 (sunday) .. 6 (saturday)
		}

	return($retval);
	}

function active_sync_get_default_rights_management()
	{
	$retval = array
		(
		"ContentExpiryDate"		=> date("Y-m-d\TH:i:s\Z"),
		"ContentOwner"			=> "", # 320 chars
		"EditAllowed"			=> 0,
		"ExportAllowed"			=> 0,
		"ExtractAllowed"		=> 0,
		"ForwardAllowed"		=> 0,
		"ModifyRecipientsAllowed"	=> 0,
		"Owner"				=> 0,
		"PrintAllowed"			=> 0,
		"ProgrammaticAccessAllowed"	=> 0,
		"ReplyAllAllowed"		=> 0,
		"ReplyAllowed"			=> 0,

		"TemplateDescription"		=> "template description", # 10240 chars
		"TemplateID"			=> "00000000-0000-0000-0000-000000000000",
		"TemplateName"			=> "template name" # 256 chars
		);

	return($retval);
	}

function active_sync_get_default_services()
	{
	$retval = array
		(
		array
			(
			"Class" => "Contacts",
			"Name" => "Kontakte",
			"Enabled" => "T"
			),
		array
			(
			"Class" => "Calendar",
			"Name" => "Kalender",
			"Enabled" => "T"
			),
		array
			(
			"Class" => "Tasks",
			"Name" => "Aufgaben",
			"Enabled" => "T"),
		array
			(
			"Class" => "Notes",
			"Name" => "Notizen",
			"Enabled" => "T"
			),
		array
			(
			"Class" => "Email",
			"Name" => "EMail",
			"Enabled" => "T"
			)
		);

	return($retval);
	}

function active_sync_get_default_settings()
	{
	$retval = array
		(
		"Language"		=> "en",	# en
		"TimeZone"		=> 28,		# de
		"PhoneOnly"		=> 0,		# PhoneOnly
		"SortBy"		=> 0,		# SortBy
		"ShowBy"		=> 0,		# DisplayBy
		"Reminder"		=> 1440,	# 1 day
		"FirstDayOfWeek"	=> 1,		# Monday
		"CalendarSync"		=> 0		# All
		);


	return($retval);
	}

function active_sync_get_default_sms()
	{
	$retval = active_sync_get_default_email();

	return($retval);
	}

function active_sync_get_default_tasks($Class = "Tasks")
	{
	$retval = array
		(
		);

	if($Class == "Email")
		{
		$retval["UtcStartDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["StartDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["UtcDueDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["DueDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["DateCompleted"]	= date("Y-m-d\TH:i:s\Z");
		$retval["ReminderTime"]		= date("Y-m-d\TH:i:s\Z");
		$retval["ReminderSet"]		= 0;
		$retval["OrdinalDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["SubOrdinalDate"]	= date("Y-m-d\TH:i:s\Z");
		}

	if($Class == "Tasks")
		{
		$retval["Subject"]		= "";
		# Body
		# BodySize
		# BodyTruncated
		$retval["Importance"]		= 0;
		$retval["UtcStartDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["StartDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["UtcDueDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["DueDate"]		= date("Y-m-d\TH:i:s\Z");
		# Categories
		# Recurrences
		$retval["Complete"]		= 0;
		$retval["DateCompleted"]	= date("Y-m-d\TH:i:s\Z");
		$retval["Sensitivity"]		= 0;
		$retval["ReminderTime"]		= date("Y-m-d\TH:i:s\Z");
		$retval["ReminderSet"]		= 0;
		# OrdinalDate
		# SubOrdinalDate
		}

	return($retval);
	}

function active_sync_get_default_user()
	{
	$retval = active_sync_get_default_login();

	return($retval);
	}

function active_sync_get_devices_by_user($user)
	{
	$retval = array();

	foreach(glob(DAT_DIR . "/" . $user . "/*.sync") as $file)
		$retval[] = basename($file, ".sync");

	if(count($retval) > 1)
		sort($retval);

	return($retval);
	}

function active_sync_get_display_name_by_collection_id($user, $server_id)
	{
	$folders = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($folders as $folder)
		{
		if($folder["ServerId"] != $server_id)
			continue;

		return($folder["DisplayName"]);
		}

	return("");
	}

function active_sync_get_domain()
	{
	$retval = active_sync_postfix_config("mydomain", "localhost");

#	$retval = active_sync_postfix_config("virtual_mailbox_domains", "localhost");
#	$retval = explode(", ", $retval);
#	$retval = $retval[0];

	return($retval);
	}

function active_sync_get_email_by_filereference($user, $file_reference)
	{
	$collection_id = active_sync_get_collection_id_by_type($user, 2); # Email ::= 2

	$settings = active_sync_get_settings_sync($user, $collection_id, "");

	foreach($settings["SyncDat"] as $server_id => $timestamp)
		{
		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		if(isset($data["Files"][$file_reference]))
			return($server_id);
		}

	return("");
	}

function active_sync_get_folder_free($user_id)
	{
	foreach(range(1000, 8999) as $collection_id)
		{
		if(is_dir(DAT_DIR . "/" . $user_id . "/" . $collection_id))
			continue;

		return($collection_id);
		}

	return(0);
	}

function active_sync_get_freebusy($user_id, $start_time, $end_time, $busy_status = 4, $steps = 1800)
	{
	$retval = array_fill(0, ($end_time - $start_time) / $steps, $busy_status);

	$collection_id = active_sync_get_collection_id_by_type($user_id, 8);

	foreach(glob(DAT_DIR . "/" . $user_id . "/" . $collection_id . "/*.data") as $file) # search in users contacts
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($user_id, $collection_id, $server_id);

		foreach(array("EndTime" => 0, "StartTime" => 0, "BusyStatus" => 0) as $token => $value)
			$data["Calendar"][$token] = (isset($data["Calendar"][$token]) === false ? $value : $data["Calendar"][$token]);

		if(strtotime($data["Calendar"]["StartTime"]) > $end_time)
			continue;

		if(strtotime($data["Calendar"]["EndTime"]) < $start_time)
			continue;

		for($s = $start_time; $s < $end_time; $s = $s + $steps)
			{
			$e = $s + $steps;

			if($s < strtotime($data["Calendar"]["StartTime"]))
				continue;

			if($e > strtotime($data["Calendar"]["EndTime"]))
				continue;

			$k = intval(($s - $start_time) / $steps);

			$v = (isset($data["Calendar"]["BusyStatus"]) === false ? $busy_status : $data["Calendar"]["BusyStatus"]);

			$retval[$k] = $v;
			}
		}

	return(implode("", $retval));
	}

function active_sync_get_icon_by_type($type)
	{
	$ico = array
		(
		);

	$ico[1] = "default";		# user-created folder (generic)

	$ico[2] = "mail-inbox";		# default inbox folder
	$ico[3] = "mail-drafts";	# default drafts folder
	$ico[4] = "mail-trash";		# default deleted items folder
	$ico[5] = "mail-sent";		# default sent items folder
	$ico[6] = "mail-outbox";	# default outbox folder
	$ico[7] = "tasks";		# default tasks folder
	$ico[8] = "calendar";		# default calendar folder
	$ico[9] = "contacts";		# default contacts folder
	$ico[10] = "notes";		# default notes folder
	$ico[11] = "journal";		# default journal folder

	$ico[12] = "mail-default";	# user-created mail folder
	$ico[13] = "calendar";		# user-created calendar folder
	$ico[14] = "contacts";		# user-created contacts folder
	$ico[15] = "tasks";		# user-created tasks folder
	$ico[16] = "journal";		# user-created journal folder
	$ico[17] = "notes";		# user-created notes folder

	$ico[18] = "default";		# unknown folder type

	$ico[19] = "ric";		# recipient information cache

	$type = (isset($ico[$type]) ? $type : 1);

	return("folder-" . $ico[$type] . ".png");
	}

function active_sync_get_is_admin($user)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($settings["login"] as $login)
		if($login["User"] == $user)
			return($login["IsAdmin"]);

	return("F");
	}

function active_sync_get_is_collection_id($user_id, $collection_id)
	{
	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user_id . ".sync");

	foreach($settings_server["SyncDat"] as $folder)
		if($folder["ServerId"] == $collection_id)
			return(1);

	return($collection_id == 0 ? 1 : 0);
	}

function active_sync_get_is_display_name($user, $display_name)
	{
	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($settings_server["SyncDat"] as $folder)
		if($folder["DisplayName"] == $display_name)
			return(1);

	return(0);
	}

function active_sync_get_is_filter($class, $filter)
	{
	$filters = active_sync_get_default_filter();

	return(array_key_exists($class, $filters) ? (in_array($filter, $filters[$class]) ? 1 : 0) : 1);
	}

function active_sync_get_is_identified($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($settings["login"] as $login)
		if($login["User"] == $request["AuthUser"])
			return($login["Pass"] == $request["AuthPass"] ? 1 : 0);

	return(0);
	}

function active_sync_get_is_known_mail($user, $collection_id, $email_address)
	{
	$retval = 0;

	foreach(glob(DAT_DIR . "/" . $user . "/" . $collection_id . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		foreach(array("Email1Address", "Email2Address", "Email3Address") as $token)
			{
			if(isset($data["Contacts"][$token]) === false)
				continue;

			list($data_name, $data_mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

			if(strtolower($data_mail) != strtolower($email_address))
				continue;

			$retval = 1;

			break(2); # exit foreach and foreach
			}
		}

	return($retval);
	}

function active_sync_get_is_phone($phone)
	{
	$prefixes = array();

	$prefixes[] = "+40-7";
	$prefixes[] = "+49-15";
	$prefixes[] = "+49-16";
	$prefixes[] = "+49-17";

	foreach($prefixes as $prefix)
		{
		$prefix	= active_sync_fix_phone($prefix);
		$phone	= active_sync_fix_phone($phone);

		if(substr($phone, 0, strlen($prefix)) != $prefix)
			continue;

		return(1);
		}

	return(0);
	}

function active_sync_get_is_phone_available($data)
	{
	foreach(array("AssistnamePhoneNumber", "CarPhoneNumber", "MobilePhoneNumber", "PagerNumber", "RadioPhoneNumber", "BusinessFaxNumber", "BusinessPhoneNumber", "Business2PhoneNumber", "HomeFaxNumber", "HomePhoneNumber", "Home2PhoneNumber") as $token)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		if(strlen($data["Contacts"][$token]) == 0)
			continue;

		return(1);
		}

	return(0);
	}

function active_sync_get_is_special_folder($type)
	{
	return(in_array($type, array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 19)) ? 1 : 0);
	}

function active_sync_get_is_system_folder($type)
	{
	return(in_array($type, array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11)) ? 1 : 0);
	}

function active_sync_get_is_type($type)
	{
	################################################################################
	# allowed range for types is 1 .. 19
	################################################################################

	return(($type < 1) || ($type > 19) ? 0 : 1);

	# 2.2.3.162.2 FolderCreate -> 10 Malformed request
	# 2.2.3.162.3 FolderDelete -> 10 Incorrectly formatted request
	# 2.2.3.162.5 FolderUpdate -> 10 Incorrectly formatted request
	# 2.2.3.162.* Folder....te ->  1 Success
	}

function active_sync_get_is_user_folder($type)
	{
	return(in_array($type, array(1, 12, 13, 14, 15, 16, 17)) ? 1 : 0);
	}

# doesn't work so far, but also not needed yet

function active_sync_get_message_id_by_server_id($message_id)
	{
	}

function active_sync_get_ms_global_obj_id_by_ms_uid($expression)
	{
	$time = gettimeofday();
	$time = ($time["sec"] * 10000000) + ($time["usec"] * 10) + 0x01B21DD213814000;

	$retval = array();

	if(strlen($expression) == 38) # VCALID
		{
		$retval["CLASSID"]	= pack("H*", str_replace(array("{", "}", "-"), "", "{04000000-8200-E000-74C5-B7101A82E008}"));
		$retval["INSTDATE"]	= pack("CCCC", 0, 0, 0, 0);
		$retval["NOW"]		= pack("VV", (intval($time / 0x00000001) >>  0) & 0xFFFFFFFF, (intval($time / 0xFFFFFFFF) >>  0) & 0xFFFFFFFF);
		$retval["ZERO"]		= str_repeat(chr(0x00), 8);
		$retval["BYTECOUNT"]	= pack("V", 0);
		$retval["DATA"]		= "vCal-Uid" . pack("V", 1) . pack("H*", str_replace(array("{", "}", "-"), "", $expression)) . "\x00";

		$retval["BYTECOUNT"]	= pack("V", strlen($retval["DATA"]));
		}

	if(strlen($expression) == 112) # OUTLOOKID
		{
		$retval["CLASSID"]	= pack("H*", substr($expression,  0, 32));
		$retval["INSTDATE"]	= pack("H*", substr($expression, 32,  8));
		$retval["NOW"]		= pack("H*", substr($expression, 40,  8));
		$retval["ZERO"]		= pack("H*", substr($expression, 48, 16));
		$retval["BYTECOUNT"]	= pack("H*", substr($expression, 64,  8));
		$retval["DATA"]		= pack("H*", substr($expression, 72));
		}

	$retval = implode("", $retval);

	$retval = base64_encode($retval);

	return($retval);
	}

# doesn't work so far, but also not needed yet

function active_sync_get_ms_uid_by_ms_global_obj_id($expression)
	{
	$expression = base64_decode($expression);

	$a = unpack("H32CLASSID/NINSTDATE/H16NOW/H16ZERO/VBYTECOUNT", $expression);

	if($a["BYTECOUNT"] == 16) # OUTLOOKID
		{
		$b = unpack("H" . ($a["BYTECOUNT"] * 2) . "DATA", substr($expression, 40));

		for($i = 16; $i < 20; $i ++)
			$expression[$i] = chr(0x00);

		$c = unpack("H" . (strlen($expression) * 2) . "DATA", $expression);
		}

	if($a["BYTECOUNT"] == 51) # VCALID
		{
		$b = unpack("A8VCALSTRING/VVERSION/A" . ($a["BYTECOUNT"] - 13) . "UID", substr($expression, 40));

		$c["UID"] = $b["UID"];
		}

#	print("<pre>" . print_r($a, true) . "</pre>");
#	print("<pre>" . print_r($b, true) . "</pre>");
#	print("<pre>" . print_r($c, true) . "</pre>");

	return(strtoupper($c["UID"]));
	}

function active_sync_get_need_folder_sync($request)
	{
	$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
		$settings_client[$key] = (isset($settings_client[$key]) ? $settings_client[$key] : $value);

	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	foreach(array("SyncDat" => array()) as $key => $value)
		$settings_server[$key] = (isset($settings_server[$key]) ? $settings_server[$key] : $value);

	foreach($settings_server["SyncDat"] as $server_id => $server_data)
		{
		$known = 0;

		foreach($settings_client["SyncDat"] as $client_id => $client_data)
			{
			if($server_data["ServerId"] != $client_data["ServerId"])
				continue;

			if($server_data["ParentId"] != $client_data["ParentId"])
				return(1);

			if($server_data["DisplayName"] != $client_data["DisplayName"])
				return(1);

			if($server_data["Type"] != $client_data["Type"])
				return(1);

			if($server_data["ServerId"] == $client_data["ServerId"])
				{
				$known = 1;

				break;
				}
			}

		if($known == 0)
			return(1);
		}

	################################################################################
	# check if folders on client-side are also known on server-side
	################################################################################

	foreach($settings_client["SyncDat"] as $client_id => $client_data)
		{
		$known = 0;

		foreach($settings_server["SyncDat"] as $server_id => $server_data)
			{
			if($client_data["ServerId"] == $server_data["ServerId"])
				{
				$known = 1;

				break;
				}
			}

		if($known == 0)
			return(1);
		}

	return(0);
	}

function active_sync_get_need_provision($request)
	{
	$settings_server = active_sync_get_settings(DAT_DIR . "/login.data");

	$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	if(isset($settings_server["Policy"]["PolicyKey"]) === false)
		{
		if(isset($settings_client["PolicyKey"]) === false)
			return(0);

		if(isset($settings_client["PolicyKey"]))
			return(1);
		}

	if(isset($settings_server["Policy"]["PolicyKey"]))
		{
		if(isset($settings_client["PolicyKey"]) === false)
			return(1);

		if(isset($settings_client["PolicyKey"]))
			{
			if($settings_server["Policy"]["PolicyKey"] != $settings_client["PolicyKey"])
				return(1);

			if($settings_server["Policy"]["PolicyKey"] == $settings_client["PolicyKey"])
				{
				if($request["PolicyKey"] != 0)
					{
					if($request["PolicyKey"] != $settings_server["Policy"]["PolicyKey"])
						return(1);

					if($request["PolicyKey"] == $settings_server["Policy"]["PolicyKey"])
						return(0);
					}

				if($request["PolicyKey"] == 0)
					{
					if($request["Cmd"] != "Ping")
						return(1);

					if($request["Cmd"] == "Ping")
						return(0); # PolicyKey of Ping is always 0 ... policy was requested by device
					}
				}
			}
		}
	}

function active_sync_get_need_wipe($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	return(isset($settings["Wipe"]) ? 1 : 0);
	}

function active_sync_get_parent_id_by_collection_id($user, $server_id)
	{
	$settings_server = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($settings_server["SyncDat"] as $folder)
		{
		if($folder["ServerId"] != $server_id)
			continue;

		return($folder["ParentId"]);
		}

	return(0);
	}

function active_sync_get_settings($file)
	{
	if(file_exists($file))
		$retval = file_get_contents($file);
	else
		$retval = "";

	if(strlen($retval) == 0)
		$retval = array();
	elseif(substr($retval, 0, 1) == "a")
		$retval = unserialize($retval);
	elseif(substr($retval, 0, 1) == "i")
		$retval = unserialize($retval);
	elseif(substr($retval, 0, 1) == "s")
		$retval = unserialize($retval);
	elseif(substr($retval, 0, 1) == "[")
		$retval = json_decode($retval, true);
	elseif(substr($retval, 0, 1) == "{")
		$retval = json_decode($retval, true);
	else
		$retval = array();

	return($retval);
	}

function active_sync_get_settings_data($user, $collection_id, $server_id)
	{
#	$retval = active_sync_get_settings(implode("/", array(DAT_DIR, $user, $collection_id, $server_id)) . ".data");
	$retval = active_sync_get_settings(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data");

	return($retval);
	}

function active_sync_get_settings_sync($user, $collection_id, $device_id = "")
	{
	$retval = array("SyncKey" => 0, "SyncDat" => array());

	if($device_id == "")
		{
		foreach(glob(DAT_DIR . "/" . $user . "/" . $collection_id . "/*.data") as $file)
			{
			$server_id = basename($file, ".data");

			$retval["SyncDat"][$server_id] = filemtime(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data");
			}
		}

	if($device_id != "")
		{
		$retval = active_sync_get_settings(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $device_id . ".sync");

		foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
			$retval[$key] = (isset($retval[$key]) ? $retval[$key] : $value);
		}

	return($retval);
	}

function active_sync_get_supported_commands()
	{
	$retval = array();

	$handles = active_sync_get_table_handle();

	foreach($handles as $command => $function)
		{
		if(function_exists($function) === false)
			continue;

		$retval[] = $command;
		}

	return(implode(",", $retval));
	}

function active_sync_get_supported_versions()
	{
	$versions = active_sync_get_table_version();

	# return value should depend on supported commands

	return(implode(",", $versions));
	}

function active_sync_get_table_command()
	{
	$table = array
		(
		0 => "Sync",
		1 => "SendMail",
		2 => "SmartForward",
		3 => "SmartReply",
		4 => "GetAttachment",
#		5 => "GetHierarchy",		# DEPRECATED
#		6 => "CreateCollection",	# DEPRECATED
#		7 => "DeleteCollection",	# DEPRECATED
#		8 => "MoveCollection",		# DEPRECATED
		9 => "FolderSync",
		10 => "FolderCreate",
		11 => "FolderDelete",
		12 => "FolderUpdate",
		13 => "MoveItems",
		14 => "GetItemEstimate",
		15 => "MeetingResponse",
		16 => "Search",
		17 => "Settings",
		18 => "Ping",
		19 => "ItemOperations",
		20 => "Provision",
		21 => "ResolveRecipients",
		22 => "ValidateCert",
#		23 => "Find"
		);

	return($table);
	}

function active_sync_get_table_handle()
	{
	$table = array
		(
		"Sync" => "active_sync_handle_sync",
		"SendMail" => "active_sync_handle_send_mail",
		"SmartForward" => "active_sync_handle_smart_forward",
		"SmartReply" => "active_sync_handle_smart_reply",
		"GetAttachment" => "active_sync_handle_get_attachment",
		"GetHierarchy" => "active_sync_handle_get_hierarchy",		# DEPRECATED
		"CreateCollection" => "active_sync_handle_create_collection",	# DEPRECATED
		"DeleteCollection" => "active_sync_handle_delete_collection",	# DEPRECATED
		"MoveCollection" => "active_sync_handle_move_collection",	# DEPRECATED
		"FolderSync" => "active_sync_handle_folder_sync",
		"FolderCreate" => "active_sync_handle_folder_create",
		"FolderDelete" => "active_sync_handle_folder_delete",
		"FolderUpdate" => "active_sync_handle_folder_update",
		"MoveItems" => "active_sync_handle_move_items",
		"GetItemEstimate" => "active_sync_handle_get_item_estimate",
		"MeetingResponse" => "active_sync_handle_meeting_response",
		"Search" => "active_sync_handle_search",
		"Settings" => "active_sync_handle_settings",
		"Ping" => "active_sync_handle_ping",
		"ItemOperations" => "active_sync_handle_item_operations",
		"Provision" => "active_sync_handle_provision",
		"ResolveRecipients" => "active_sync_handle_resolve_recipients",
		"ValidateCert" => "active_sync_handle_validate_cert"
		);

	return($table);
	}

function active_sync_get_table_handle_settings()
	{
	$table = array
		(
		"Oof" => "active_sync_handle_setting_oof",
		"DevicePassword" => "active_sync_handle_setting_device_password",
		"DeviceInformation" => "active_sync_handle_setting_device_information",
		"UserInformation" => "active_sync_handle_setting_user_information",
		"RightsManagementInformation" => "active_sync_handle_setting_rights_management_information"
		);

	return($table);
	}

function active_sync_get_table_method()
	{
	$table = array
		(
		"GET" => "active_sync_http_method_get", # used by web interface
		"POST" => "active_sync_http_method_post",
#		"PUT" => "active_sync_http_method_put",
#		"PATCH" => "active_sync_http_method_patch",
#		"DELETE" => "active_sync_http_method_delete",
#		"HEAD" => "active_sync_http_method_read",
		"OPTIONS" => "active_sync_http_method_options",
#		"CONNECT" => "active_sync_http_method_connect",
#		"TRACE" => "active_sync_http_method_trace"
		);

	return($table);
	}

function active_sync_get_table_parameter()
	{
	$table = array
		(
		0 =>"AttachmentName",
		1 => "CollectionId",

		3 => "ItemId",
		4 => "LongId",

		6 => "Occurence",
		7 => "Options",
		8 => "User"
		);

	return($table);
	}

function active_sync_get_table_policy()
	{
	# type ::= C (checkbox) | L (textarea) | R (radio) | S (select) | T (text)

	$table = array(
		"AllowBluetooth"				=> array("Type" => "S", "Values" => array(0 => "Disable Bluetooth.", 1 => "Disable Bluetooth, but allow the configuration of hands-free profiles.", 2 => "Allow Bluetooth.")),
		"AllowBrowser"					=> array("Type" => "S", "Values" => array(0 => "Do not allow the use of a web browser.", 1 => "Allow the use of a web browser.")),
		"AllowCamera"					=> array("Type" => "S", "Values" => array(0 => "Use of the camera is not allowed.", 1 => "Use of the camera is allowed.")),
		"AllowConsumerEmail"				=> array("Type" => "S", "Values" => array(0 => "Do not allow the user to configure a personal email account.", 1 => "Allow the user to configure a personal email account.")),
		"AllowDesktopSync"				=> array("Type" => "S", "Values" => array(0 => "Do not allow Desktop ActiveSync.", 1 => "Allow Desktop ActiveSync.")),
		"AllowHTMLEmail"				=> array("Type" => "S", "Values" => array(0 => "HTML-formatted email is not allowed.", 1 => "HTML-formatted email is allowed.")),
		"AllowInternetSharing"				=> array("Type" => "S", "Values" => array(0 => "Do not allow the use of Internet Sharing.", 1 => "Allow the use of Internet Sharing.")),
		"AllowIrDA"					=> array("Type" => "S", "Values" => array(0 => "Disable IrDA.", 1 => "Allow IrDA.")),
		"AllowPOPIMAPEmail"				=> array("Type" => "S", "Values" => array(0 => "POP or IMAP email access is not allowed.", 1 => "POP or IMAP email access is allowed.")),
		"AllowRemoteDesktop"				=> array("Type" => "S", "Values" => array(0 => "Do not allow the use of Remote Desktop.", 1 => "Allow the use of Remote Desktop.")),
		"AllowSimpleDevicePassword"			=> array("Type" => "S", "Values" => array(0 => "Simple passwords are not allowed.", 1 => "Simple passwords are allowed.")),
		"AllowSMIMEEncryptionAlgorithmNegotiation"	=> array("Type" => "S", "Values" => array(0 => "Do not negotiate.", 1 => "Negotiate a strong algorithm.", 2 => "Negotiate any algorithm.")),
		"AllowSMIMESoftCerts"				=> array("Type" => "S", "Values" => array(0 => "Soft certificates are not allowed.", 1 => "Soft certificates are allowed.")),
		"AllowStorageCard"				=> array("Type" => "S", "Values" => array(0 => "SD card use is not allowed.", 1 => "SD card use is allowed.")),
		"AllowTextMessaging"				=> array("Type" => "S", "Values" => array(0 => "SMS or text messaging is not allowed.", 1 => "SMS or text messaging is allowed.")),
		"AllowUnsignedApplications"			=> array("Type" => "S", "Values" => array(0 => "Unsigned applications are not allowed to execute.", 1 => "Unsigned applications are allowed to execute.")),
		"AllowUnsignedInstallationPackages"		=> array("Type" => "S", "Values" => array(0 => "Unsigned cabinet (.cab) files are not allowed to be installed.", 1 => "Unsigned cabinet (.cab) files are allowed to be installed.")),
		"AllowWiFi"					=> array("Type" => "S", "Values" => array(0 => "The use of Wi-Fi connections is not allowed.", 1 => "The use of Wi-Fi connections is allowed.")),
		"AlphanumericDevicePasswordRequired"		=> array("Type" => "S", "Values" => array(0 => "Alphanumeric device password is not required.", 1 => "Alphanumeric device password is required.")),
		"ApprovedApplicationList"			=> array("Type" => "L", "Label" => "Hash"),
		"AttachmentsEnabled"				=> array("Type" => "S", "Values" => array(0 => "Attachments are not allowed to be downloaded.", 1 => "Attachments are allowed to be downloaded.")),
		"DevicePasswordEnabled"				=> array("Type" => "S", "Values" => array(0 => "Device password is not required.", 1 => "Device password is required.")),
		"DevicePasswordExpiration"			=> array("Type" => "T", "Length" => 4, "Label" => "day(s)"),
		"DevicePasswordHistory"				=> array("Type" => "T", "Length" => 4, "Label" => "entry(s)"),
		"MaxAttachmentSize"				=> array("Type" => "T", "Length" => 8, "Label" => "byte(s)", "Min" => 0, "Max" => 99999999),
		"MaxCalendarAgeFilter"				=> array("Type" => "S", "Values" => array(0 => "All days", 4 => "2 weeks", 5 => "1 month", 6 => "3 months", 7 => "6 month")),
		"MaxDevicePasswordFailedAttempts"		=> array("Type" => "T", "Length" => 2, "Label" => "tries(s)", "Min" => 4, "Max" => 16),
		"MaxEmailAgeFilter"				=> array("Type" => "S", "Values" => array(0 => "Sync all", 1 => "1 day", 2 => "3 days", 3 => "1 week", 4 => "2 weeks", 5 => "1 month")),
		"MaxEmailBodyTruncationSize"			=> array("Type" => "T", "Length" => 8, "Label" => "byte(s)", "Min" => 0, "Max" => 99999999),
		"MaxEmailHTMLBodyTruncationSize"		=> array("Type" => "T", "Length" => 8, "Label" => "byte(s)", "Min" => 0, "Max" => 99999999),
		"MaxInactivityTimeDeviceLock"			=> array("Type" => "T", "Length" => 4, "Label" => "second(s)", "Min" => 0, "Max" => 9999),
		"MinDevicePasswordComplexCharacters"		=> array("Type" => "T", "Length" => 2, "Label" => "char(s)", "Min" => 1, "Max" => 4),
		"MinDevicePasswordLength"			=> array("Type" => "T", "Length" => 2, "Label" => "chars(s)", "Min" => 1, "Max" => 16),
		"PasswordRecoveryEnabled"			=> array("Type" => "S", "Values" => array(0 => "Password recovery is not enabled on the server.", 1 => "Password recovery is enabled on the server.")),
		"RequireDeviceEncryption"			=> array("Type" => "S", "Values" => array(0 => "Encryption is not required.", 1 => "Encryption is required.")),
		"RequireEncryptedSMIMEMessages"			=> array("Type" => "S", "Values" => array(0 => "Encrypted email messages are not required.", 1 => "Email messages are required to be encrypted.")),
		"RequireEncryptionSMIMEAlgorithm"		=> array("Type" => "S", "Values" => array(0 => "TripleDES algorithm", 1 => "DES algorithm", 2 => "RC2 128bit", 3 => "RC2 64bit", 4 => "RC2 40bit")),
		"RequireManualSyncWhenRoaming"			=> array("Type" => "S", "Values" => array(0 => "Do not require manual sync; allow direct push when roaming.", 1 => "Require manual sync when roaming.")),
		"RequireSignedSMIMEAlgorithm"			=> array("Type" => "S", "Values" => array(0 => "Use SHA1.", 1 => "Use MD5.")),
		"RequireSignedSMIMEMessages"			=> array("Type" => "S", "Values" => array(0 => "Signed S/MIME messages are not required.", 1 => "Signed S/MIME messages are required.")),
		"RequireStorageCardEncryption"			=> array("Type" => "S", "Values" => array(0 => "Encryption of the device storage card is not required.", 1 => "Encryption of the device storage card is required.")),
		"UnapprovedInROMApplicationList"		=> array("Type" => "L", "Label" => "ApplicationName"),
		);

	return($table);
	}

function active_sync_get_table_timezone_information()
	{
	$table = array(
		array(array( 660, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Midway-Inseln"),
		array(array( 600, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Hawaii"),
		array(array( 540, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Alaska"),
		array(array( 480, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Pazifik"),
		array(array( 480, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Tijuana"),
		array(array( 420, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Arizona"),
		array(array( 420, "", array(0, 10,  0,  4,  2,  0,  0,  0),  0, "", array(0,  4,  0,  1,  2,  0,  0,  0), -60), "Chihuahua"),
		array(array( 420, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Mountain"),
		array(array( 360, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Mittelamerika"),
		array(array( 360, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Central"),
		array(array( 360, "", array(0, 10,  0,  4,  2,  0,  0,  0),  0, "", array(0,  4,  0,  1,  2,  0,  0,  0), -60), "Mexiko-Stadt"),
		array(array( 360, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Saskatchewan"),
		array(array( 300, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Bogota"),
		array(array( 300, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Eastern"),
		array(array( 270, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Venezuela"),
		array(array( 240, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Atlantik"),
		array(array( 240, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Manaus"),
		array(array( 240, "", array(0,  3,  6,  2,  0,  0,  0,  0),  0, "", array(0, 10,  6,  2,  0,  0,  0,  0), -60), "Santiago"),
		array(array( 210, "", array(0, 11,  0,  1,  1,  0,  0,  0),  0, "", array(0,  3,  0,  2,  1,  0,  0,  0), -60), "Neufundland"),
		array(array( 180, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Buenos Aires"),
		array(array( 180, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  4,  0,  0,  0), -60), "Grönland"),
		array(array( 180, "", array(0,  2,  0,  3,  4,  0,  0,  0),  0, "", array(0, 10,  0,  3,  6,  0,  0,  0), -60), "Brasilien"),
		array(array( 180, "", array(0,  3,  0,  2,  2,  0,  0,  0),  0, "", array(0, 10,  0,  1,  2,  0,  0,  0), -60), "Montevideo"),
		array(array( 120, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Mittelatlantik"),
		array(array(  60, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  4,  0,  0,  0), -60), "Azoren"),
		array(array(  60, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kapverdische Inseln"),
		array(array(   0, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Casablanca"),
		array(array(   0, "", array(0, 10,  0,  4,  2,  0,  0,  0),  0, "", array(0,  3,  0,  5,  1,  0,  0,  0), -60), "London, Dublin"),
		array(array(- 60, "", array(0, 10,  0,  5,  3,  0,  0,  0),  0, "", array(0,  3,  0,  4,  2,  0,  0,  0), -60), "Amsterdam, Berlin"),
		array(array(- 60, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Belgrad"),
		array(array(- 60, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Brüssel"),
		array(array(- 60, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Sarajevo"),
		array(array(- 60, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "W.-Afrika"),
		array(array(- 60, "", array(0, 10,  0,  5,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Mitteleuropäische Zeit"),
		array(array(- 60, "", array(0,  4,  0,  1,  2,  0,  0,  0),  0, "", array(0,  9,  0,  1,  2,  0,  0,  0), -60), "Windhoek"),
		array(array(-120, "", array(0, 10,  5,  5,  1,  0,  0,  0),  0, "", array(0,  3,  4,  5,  0,  0,  0,  0), -60), "Amman, Jordan"),
		array(array(-120, "", array(0, 10,  0,  4,  4,  0,  0,  0),  0, "", array(0,  3,  0,  5,  3,  0,  0,  0), -60), "Athen, Istanbul"),
		array(array(-120, "", array(0, 10,  6,  4,  0,  0,  0,  0),  0, "", array(0,  3,  6,  5,  0,  0,  0,  0), -60), "Beirut, Libanon"),
		array(array(-120, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kairo"),
		array(array(-120, "", array(0, 10,  0,  4,  4,  0,  0,  0),  0, "", array(0,  3,  0,  5,  3,  0,  0,  0), -60), "Helsinki"),
		array(array(-120, "", array(0,  9,  0,  2,  2,  0,  0,  0),  0, "", array(0,  3,  5,  5,  2,  0,  0,  0), -60), "Jerusalem"),
		array(array(-120, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Minsk"),
		array(array(-120, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Harare"),
		array(array(-180, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Baghdad"),
		array(array(-180, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kuwait"),
		array(array(-180, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Nairobi"),
		array(array(-210, "", array(0,  9,  6,  3, 22, 30,  0,  0),  0, "", array(0,  3,  4,  3, 22, 30,  0,  0), -60), "Teheran"),
		array(array(-240, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Moskau"),
		array(array(-240, "", array(0, 10,  0,  4,  5,  0,  0,  0),  0, "", array(0,  3,  0,  5,  4,  0,  0,  0), -60), "Baku"),
		array(array(-240, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Tbilisi"),
		array(array(-240, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Yerevan"),
		array(array(-240, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Dubai"),
		array(array(-270, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kabul"),
		array(array(-300, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Islamabad, Karatschi"),
		array(array(-300, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Uralsk"),
		array(array(-330, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kolkata"),
		array(array(-330, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Sri Lanka"),
		array(array(-345, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kathmandu"),
		array(array(-360, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Jekaterinburg"),
		array(array(-360, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Astana"),
		array(array(-390, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Yangon"),
		array(array(-420, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Bangkok"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Krasnojarsk"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Peking"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Hong Kong"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kuala Lumpur"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Perth"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Taipeh"),
		array(array(-540, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Irkutsk"),
		array(array(-540, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Seoul"),
		array(array(-540, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Tokio, Osaka"),
		array(array(-570, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Darwin"),
		array(array(-570, "", array(0,  4,  0,  1,  3,  0,  0,  0),  0, "", array(0, 10,  0,  1,  2,  0,  0,  0), -60), "Adelaide"),
		array(array(-600, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Jakutsk"),
		array(array(-600, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Brisbane"),
		array(array(-600, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Guam"),
		array(array(-600, "", array(0,  4,  0,  1,  3,  0,  0,  0),  0, "", array(0, 10,  0,  1,  2,  0,  0,  0), -60), "Hobart"),
		array(array(-600, "", array(0,  4,  0,  1,  3,  0,  0,  0),  0, "", array(0, 10,  0,  1,  2,  0,  0,  0), -60), "Canberra, Sydney"),
		array(array(-660, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Wladiwostok"),
		array(array(-720, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Magadan"),
		array(array(-720, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Marshall-Inseln"),
		array(array(-720, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Fidchi"),
		array(array(-720, "", array(0,  4,  0,  1,  3,  0,  0,  0),  0, "", array(0,  9,  0,  5,  2,  0,  0,  0), -60), "Auckland"),
		array(array(-780, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Tonga"),
		);

	foreach($table as $id => $entry)
		{
		list($data, $name) = $entry;

		list($bias, $standard_name, $standard_date, $standard_bias, $daylight_name, $daylight_date, $daylight_bias) = $data;

		list($year, $month, $day_of_week, $day, $hour, $minute, $second, $milliseconds) = $standard_date;

		$standard_date = active_sync_systemtime_encode($year, $month, $day_of_week, $day, $hour, $minute, $second, $milliseconds);

		list($year, $month, $day_of_week, $day, $hour, $minute, $second, $milliseconds) = $daylight_date;

		$daylight_date = active_sync_systemtime_encode($year, $month, $day_of_week, $day, $hour, $minute, $second, $milliseconds);

		$data = active_sync_time_zone_information_encode($bias, $standard_name, $standard_date, $standard_bias, $daylight_name, $daylight_date, $daylight_bias);

		$bias_p = ($bias > 0 ? "-" : "+");
		$bias_v = ($bias > 0 ? 0 + 1 : 0 - 1) * $bias;

		$bias_m = ($bias_v % 60);
		$bias_h = (($bias_v - $bias_m) / 60);

		$table[$id] = array(base64_encode($data), sprintf("GMT %s%02d%02d %s", $bias_p, $bias_h, $bias_m, $name));
		}

	return($table);
	}

function active_sync_get_table_version()
	{
	$table = array();

#	$table[] = "1.0";
#	$table[] = "2.0";
#	$table[] = "2.1";
	$table[] = "2.5";	# LG-P920 depends on it
	$table[] = "12.0";
	$table[] = "12.1";
	$table[] = "14.0";	# allow SMS on Email
	$table[] = "14.1";	# allow SMS on Email
#	$table[] = "16.0";	# allow SMS on Email
#	$table[] = "16.1";	# allow SMS on Email, Find

	return($table);
	}

function active_sync_get_type_by_collection_id($user, $server_id)
	{
	$folders = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($folders["SyncDat"] as $folder)
		if($folder["ServerId"] == $server_id)
			return($folder["Type"]);

	return(0);
	}

function active_sync_get_version($type = 0)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	$defaults = array
		(
		"name" => "AndSync",
		"major" => 0,
		"minor" => 0,
		"revision" => 0,
		"build" => 1,
		"extension" => "",
		"description" => ""
		);

	$changes = false;

	foreach($defaults as $key => $value)
		if(isset($settings["version"][$key]) === false)
			$changes = true;

	foreach($defaults as $key => $value)
		if(isset($settings["version"][$key]) === false)
			$settings["version"][$key] = $defaults[$key];

	foreach($defaults as $key => $value)
		$defaults[$key] = $settings["version"][$key];

	if($changes)
		active_sync_put_settings(DAT_DIR . "/login.data", $settings);

	if($type == 0)
		return(sprintf("%s %d.%d.%d-%d %s %s", $defaults["name"], $defaults["major"], $defaults["minor"], $defaults["revision"], $defaults["build"], $defaults["extension"], $defaults["description"]));

	if($type == 1)
		return($defaults);

	}

function active_sync_namespace_to_string($namespace)
	{
	$namespace = str_replace(array("-", "{", "}"), "", $namespace);

	$namespace = str_split($namespace, 2);

#	for($position = 0; $position < count($namespace); $position ++)
#		$namespace[$position] = chr(hexdec($namespace[$position]));

	foreach($namespace as $position => $char)
		$namespace[$position] = chr(hexdec($char));

	$namespace = implode("", $namespace);

	return($namespace);
	}

function active_sync_normalize_chars($string)
	{
	$table = array();

	$table["a"] = "à á â ã ä å ā ă";
	$table["c"] = "ç ć ĉ ċ č ḉ";
	$table["e"] = "è é ê ë ē ĕ ė ę ě ḕ ḗ ḙ ḛ ḝ";
	$table["i"] = "ì í î";
	$table["o"] = "ò ó ô ö";
	$table["s"] = "ß ś ŝ ș š";
	$table["t"] = "ț ţ";
	$table["u"] = "ù ú û ü ũ ū ŭ ű";
	$table["z"] = "ź ż ž";

	foreach($table as $char => $chars)
		{
		$string = str_replace(explode(" ", strtolower($chars)), strtolower($char), $string);
		$string = str_replace(explode(" ", strtoupper($chars)), strtoupper($char), $string);
		}

	return($string);
	}

#	<Autodiscover>
#		<Request>
#			<EMailAddress>...</EMailAddress>
#			<AcceptableResponseSchema>http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006</AcceptableResponseSchema>
#		</Request>
#	</Autodiscover>

#	<Autodiscover>
#		<Response>
#			<Culture>de:de</Culture>
#			<User>
#				<DisplayName>...</DisplayName>
#				<EMailAddress>...</EMailAddress>
#			</User>
#			<Action>
#				<Redirect>...</Redirect>
#				<Settings>
#					<Server>
#						<Type>...</Type>
#						<Url>...</Url>
#						<Name>...</Name>
#						<ServerData>...</ServerData>
#					</Server>
#				</Settings>
#				<Error>
#					<Status>...</Status>
#					<Message>...</Message>
#					<DebugData>...</DebugData>
#					<ErrorCode>...</ErrorCode>
#				</Error>
#			</Action>
#			<Error>
#				<Status>...</Status>
#				<Message>...</Message>
#				<DebugData>...</DebugData>
#				<ErrorCode>...</ErrorCode>
#			</Error>
#		</Response>
#	</Autodiscover>

#function active_sync_handle_autodiscover($request)
#	{
#	}

#function active_sync_handle_create_collection($equest)
#	{
#	}

#function active_sync_handle_delete_collection($request)
#	{
#	}

#function active_sync_handle_find($request)
#	{
#	}

function active_sync_handle_folder_create($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$sync_key	= strval($xml->SyncKey);
	$parent_id	= strval($xml->ParentId);
	$display_name	= strval($xml->DisplayName);
	$type		= strval($xml->Type);

	$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
		$settings_client[$key] = (isset($settings_client[$key]) ? $settings_client[$key] : $value);

	if($sync_key != $settings_client["SyncKey"])
		{
		$sync_key_new = 0;

		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
		}
	else
		{
		$sync_key_new = $settings_client["SyncKey"] + 1;

		$status = active_sync_folder_create($request["AuthUser"], $parent_id, $display_name, $type);
		}

	if($status == 1)
		{
		$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

		foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
			$settings_client[$key] = (isset($settings_client[$key]) ? $settings_client[$key] : $value);

		$settings_server = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

		foreach(array("SyncDat" => array()) as $key => $value)
			$settings_server[$key] = (isset($settings_server[$key]) ? $settings_server[$key] : $value);

		$settings_client["SyncKey"] = $sync_key_new;
		$settings_client["SyncDat"] = $settings_server["SyncDat"];

		active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings_client);

		$server_id = active_sync_get_collection_id_by_display_name($request["AuthUser"], $display_name);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderCreate");

		foreach(($status == 1 ? array("Status" => $status, "SyncKey" => $sync_key_new, "ServerId" => $server_id) : array("Status" => $status)) as $token => $value)
			{
			$response->x_open($token);
				$response->x_print($value);
			$response->x_close($token);
			}

	$response->x_close("FolderCreate");

	return($response->response);
	}

function active_sync_handle_folder_delete($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$sync_key	= strval($xml->SyncKey);
	$server_id	= strval($xml->ServerId);

	$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
		$settings_client[$key] = (isset($settings_client[$key]) ? $settings_client[$key] : $value);

	if($sync_key != $settings_client["SyncKey"])
		{
		$sync_key_new = 0;

		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
		}
	else
		{
		$sync_key_new = $settings_client["SyncKey"] + 1;

		$status = active_sync_folder_delete($request["AuthUser"], $server_id);
		}

	if($status == 1)
		{
		$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

		foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
			$settings_client[$key] = (isset($settings_client[$key]) ? $settings_client[$key] : $value);

		$settings_server = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

		foreach(array("SyncDat" => array()) as $key => $value)
			$settings_server[$key] = (isset($settings_server[$key]) ? $settings_server[$key] : $value);

		$settings_client["SyncKey"] = $sync_key_new;
		$settings_client["SyncDat"] = $settings_server["SyncDat"];

		active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings_client);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderDelete");

		foreach(($status == 1 ? array("Status" => $status, "SyncKey" => $sync_key_new) : array("Status" => $status)) as $token => $value)
			{
			$response->x_open($token);
				$response->x_print($value);
			$response->x_close($token);
			}

	$response->x_close("FolderDelete");

	return($response->response);
	}

function active_sync_handle_folder_sync($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$sync_key = strval($xml->SyncKey);

	$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
		$settings_client[$key] = (isset($settings_client[$key]) ? $settings_client[$key] : $value);

	if($sync_key == 0)
		{
		$sync_key_new = 1;

		$folders = array();

		$status = 1; # Success.
		}
	elseif($sync_key != $settings_client["SyncKey"])
		{
		$sync_key_new = 0;

		$folders = array();

		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
		}
	else
		{
		$sync_key_new = $settings_client["SyncKey"] + 1;

		$folders = $settings_client["SyncDat"];

		$status = 1; # Success.
		}

	if(active_sync_get_need_wipe($request) != 0)
		$status = 140;

	if(active_sync_get_need_provision($request) != 0)
		{
		$sync_key_new = $sync_key_new - 1;

		$status = 142;
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderSync");

		if($status == 142)
			{
			foreach(array("Status" => $status) as $token => $value)
				{
				$response->x_open($token);
					$response->x_print($value);
				$response->x_close($token);
				}
			}
		else
			{
			foreach(array("Status" => $status, "SyncKey" => $sync_key_new) as $token => $value)
				{
				$response->x_open($token);
					$response->x_print($value);
				$response->x_close($token);
				}
			}

		if($status == 1)
			{
			$jobs = array();

			$settings_server = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

			foreach(array("SyncDat" => array()) as $key => $value)
				$settings_server[$key] = (isset($settings_server[$key]) ? $settings_server[$key] : $value);

			foreach($settings_server["SyncDat"] as $settings_server_id => $settings_server_data)
				{
				$known = 0;

				foreach($folders as $folders_id => $folders_data)
					{
					if($settings_server_data["ServerId"] != $folders_data["ServerId"])
						{
						}
					elseif($settings_server_data["ParentId"] != $folders_data["ParentId"])
						{
						$jobs["Update"][] = $settings_server_data;

						$folders[$folders_id] = $settings_server_data;
						}
					elseif($settings_server_data["DisplayName"] != $folders_data["DisplayName"])
						{
						$jobs["Update"][] = $settings_server_data;

						$folders[$folders_id] = $settings_server_data;
						}
					elseif($settings_server_data["Type"] != $folders_data["Type"])
						{
						$jobs["Update"][] = $settings_server_data;

						$folders[$folders_id] = $settings_server_data;
						}

					if($settings_server_data["ServerId"] == $folders_data["ServerId"])
						$known = 1;
					}

				if($known == 0)
					{
					$jobs["Add"][] = $settings_server_data;

					$folders[] = $settings_server_data;
					}
				}

			foreach($folders as $folders_id => $folders_data)
				{
				$known = 0;

				foreach($settings_server["SyncDat"] as $settings_server_id => $settings_server_data)
					{
					if($folders_data["ServerId"] != $settings_server_data["ServerId"])
						continue;

					$known = 1;
					}

				if($known == 0)
					{
					$jobs["Delete"][] = $folders_data;

					unset($folders[$folders_id]);
					}
				}

			$actions = array("Update" => array("ServerId", "ParentId", "DisplayName", "Type"), "Delete" => array("ServerId"), "Add" => array("ServerId", "ParentId", "DisplayName", "Type"));

			$count = 0;

			foreach($actions as $action => $fields)
				$count = $count + (isset($jobs[$action]) ? count($jobs[$action]) : 0);

			$response->x_open("Changes");

				$response->x_open("Count");
					$response->x_print($count);
				$response->x_close("Count");

				if($count > 0)
					{
					foreach($actions as $action => $fields)
						{
						if(isset($jobs[$action]) === false)
							continue;

						foreach($jobs[$action] as $job)
							{
							$response->x_open($action);

								foreach($fields as $key)
									{
									$response->x_open($key);
										$response->x_print($job[$key]);
									$response->x_close($key);
									}

							$response->x_close($action);
							}
						}
					}

			$response->x_close("Changes");
			}

	$response->x_close("FolderSync");

	$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
		$settings_client[$key] = (isset($settings_client[$key]) ? $settings_client[$key] : $value);

	$settings_client["SyncKey"] = $sync_key_new;
	$settings_client["SyncDat"] = $folders;

	active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings_client);

	return($response->response);
	}

function active_sync_handle_folder_update($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$sync_key	= strval($xml->SyncKey);
	$server_id	= strval($xml->ServerId);
	$parent_id	= strval($xml->ParentId);
	$display_name	= strval($xml->DisplayName);

	$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
		$settings_client[$key] = (isset($settings_client[$key]) ? $settings_client[$key] : $value);

	if($sync_key != $settings_client["SyncKey"])
		{
		$sync_key_new = 0;

		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
		}
	else
		{
		$sync_key_new = $settings_client["SyncKey"] + 1;

		$status = active_sync_folder_update($request["AuthUser"], $server_id, $parent_id, $display_name);
		}

	if($status == 1)
		{
		$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

		foreach(array("SyncKey" => 0, "SyncDat" => array()) as $key => $value)
			$settings_client[$key] = (isset($settings_client[$key]) ? $settings_client[$key] : $value);

		$settings_server = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

		foreach(array("SyncDat" => array()) as $key => $value)
			$settings_server[$key] = (isset($settings_server[$key]) ? $settings_server[$key] : $value);

		$settings_client["SyncKey"] = $sync_key_new;
		$settings_client["SyncDat"] = $folders;

		active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings_client);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderUpdate");

		foreach(($status == 1 ? array("Status" => $status, "SyncKey" => $sync_key_new) : array("Status" => $status)) as $token => $value)
			{
			$response->x_open($token);
				$response->x_print($value);
			$response->x_close($token);
			}

	$response->x_close("FolderUpdate");

	return($response->response);
	}

function active_sync_handle_get_attachment($request)
	{
#	header("Content-Type: application/vnd.ms-sync.wbxml");
	header("Content-Length: 0");
	}

function active_sync_handle_get_hierarchy($request)
	{
	# request is always empty

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("Folders");

		$folders = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

		foreach($folders as $folder)
			{
			$response->x_open("Folder");

				foreach(array("ServerId", "ParentId", "DisplayName", "Type") as $token);
					{
					$response->x_open($token);
						$response->x_print($folder[$token]);
					$response->x_close($token);
					}

			$response->x_close("Folder");
			}

	$response->x_close("Folders");

	return($response->response);
	}

function active_sync_handle_get_item_estimate($request)
	{
	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("ItemEstimate");

	$response->x_open("GetItemEstimate");

		if(isset($xml->Collections))
			{
			foreach($xml->Collections->Collection as $collection)
				{
				$sync_key	= strval($collection->SyncKey);
				$collection_id	= strval($collection->CollectionId);

				$settings_client = active_sync_get_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"]);

				$settings_server = active_sync_get_settings_sync($request["AuthUser"], $collection_id, "");

				$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

				if($sync_key != $settings_client["SyncKey"])
					$status = 4; # The synchonization key was invalid
				else
					$status = 1; # Success

				$response->x_open("Response");

					$response->x_open("Status");
						$response->x_print($status);
					$response->x_close("Status");

					if($status == 1)
						{
						$jobs = array();

						foreach($settings_server["SyncDat"] as $server_id => $null)
							{
							$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

							if(isset($data["AirSync"]["Class"]) === false)
								$data["AirSync"]["Class"] = $default_class;

							$class = $default_class;
							$filter_type = 0;
							$class_found = 0;

							if(isset($collection->Options))
								{
								foreach($collection->Options as $options)
									{
									if(isset($options->Class) === false)
										$class = $default_class;
									else
										$class = strval($options->Class); # only occurs on email/sms

									if($data["AirSync"]["Class"] != $class)
										continue;

									if(isset($options->FilterType) === false)
										$filter_type = 0;
									else
										$filter_type = intval($options->FilterType); # only occurs on email/sms

									$class_found = 1;
									}
								}

							if($class_found == 0)
								{
								if(isset($settings_client["SyncDat"][$server_id]) === false)
									$settings_client["SyncDat"][$server_id] = "*";
								elseif($settings_client["SyncDat"][$server_id] == "*")
									{
									# file is known as SoftDelete
									}
								elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
									$jobs["SoftDelete"][] = $server_id;
								else
									$jobs["SoftDelete"][] = $server_id;

								$filter_type = 9; # :) no more filter_type between 0 (all), 1 - 7, 8 (incomplete)
								}

							if($filter_type == 0)
								{
								if(isset($settings_client["SyncDat"][$server_id]) === false)
									$jobs["Add"][] = $server_id;
								elseif($settings_client["SyncDat"][$server_id] == "*")
									$jobs["Add"][] = $server_id;
								elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
									$jobs["Change"][] = $server_id;

								$class_found = 1;
								}

							if(($filter_type > 0) && ($filter_type < 8))
								{
								$stat_filter = array("now", "- 1 day", "- 3 day", "- 1 week", "- 2 week", "- 1 month", "- 3 month", "- 6 month", "now");

								$stat_filter = strtotime($stat_filter[$filter_type]);

								if($default_class == "Calendar")
									$timestamp = strtotime($data["Calendar"]["EndTime"]);

								if($default_class == "Email")
									$timestamp = strtotime($data["Email"]["DateReceived"]);

								if($default_class == "Notes")
									$timestamp = strtotime($data["Notes"]["LastModifiedDate"]);

								if($default_class == "SMS")
									$timestamp = strtotime($data["Email"]["DateReceived"]);

								if($default_class == "Tasks")
									$timestamp = strtotime($data["Tasks"]["DateCompleted"]);

								if(isset($settings_client["SyncDat"][$server_id]) === false)
									{
									if($timestamp < $stat_filter)
										$jobs["SoftDelete"][] = $server_id;
									else
										$jobs["Add"][] = $server_id;
									}
								elseif($settings_client["SyncDat"][$server_id] == "*")
									{
									if($timestamp < $stat_filter)
										{
										}
									else
										$jobs["Add"][] = $server_id;
									}
								elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
									{
									if($timestamp < $stat_filter)
										$jobs["SoftDelete"][] = $server_id;
									else
										$jobs["Change"][] = $server_id;
									}
								else
									{
									if($timestamp < $stat_filter)
										$jobs["SoftDelete"][] = $server_id;
									}
								}

							if($filter_type == 8)
								{
								if(isset($settings_client["SyncDat"][$server_id]) === false)
									{
									if($data["Tasks"]["Complete"] == 1)
										$jobs["SoftDelete"][] = $server_id;
									else
										$jobs["Add"][] = $server_id;
									}
								elseif($settings_client["SyncDat"][$server_id] == "*")
									{
									if($data["Tasks"]["Complete"] == 1)
										$jobs["SoftDelete"][] = $server_id;
									else
										$jobs["Add"][] = $server_id;
									}
								elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
									{
									if($data["Tasks"]["Complete"] == 1)
										$jobs["SoftDelete"][] = $server_id;
									else
										$jobs["Change"][] = $server_id;
									}
								}
							}

						foreach($settings_client["SyncDat"] as $server_id => $timestamp)
							{
							if(isset($settings_server["SyncDat"][$server_id]))
								continue;

							$jobs["Delete"][] = $server_id;
							}

						$estimate = 0;

						foreach(array("Add", "Change", "Delete", "SoftDelete") as $command)
							{
							if(isset($jobs[$command]) === false)
								continue;

							$estimate = $estimate + count($jobs[$command]);
							}

						$response->x_open("Collection");

							foreach(array("CollectionId" => $collection_id, "Estimate" => $estimate) as $token => $value)
								{
								$response->x_open($token);
									$response->x_print($value);
								$response->x_close($token);
								}

						$response->x_close("Collection");
						}

				$response->x_close("Response");
				}
			}

	$response->x_close("GetItemEstimate");

	return($response->response);
	}

function active_sync_handle_item_operations($request)
	{
	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	if(isset($xml->EmptyFolderContents))
		{
		$collection_id = strval($xml->EmptyFolderContents->CollectionId);

		# $xml->EmptyFolderContents->Options->DeleteSubFolders

		foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/*.data") as $file)
			{
			$server_id = basename($file, ".data");

#			unlink(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id);
			}

		$response->x_switch("ItemOperations");

		$response->x_open("ItemOperations");
			$response->x_open("Status");
				$response->x_print(1);
			$response->x_close("Status");

			$response->x_open("Response");
				$response->x_open("EmptyFolderContents");

					$response->x_switch("ItemOperations");

					$response->x_open("Status");
						$response->x_print(1);
					$response->x_close("Status");

					$response->x_switch("AirSync");

					$response->x_open("CollectionId");
						$response->x_print($collection_id);
					$response->x_close("CollectionId");

				$response->x_close("EmptyFolderContents");
			$response->x_close("Response");
		$response->x_close("ItemOperations");
		}

	if(isset($xml->Fetch))
		{
		$store = strval($xml->Fetch->Store); # Mailbox

		if(isset($xml->Fetch->LongId))
			{
			$long_id = strval($xml->Fetch->LongId);

			# ...
			}

		if(isset($xml->Fetch->FileReference))
			{
			$file_reference = strval($xml->Fetch->FileReference);

			list($user_id, $collection_id, $server_id, $reference) = explode(":", $file_reference, 4); # user_id, collection_id, server_id, attachment_id

			$data = active_sync_get_settings_data($user_id, $collection_id, $server_id);

			$response->x_switch("ItemOperations");

			$response->x_open("ItemOperations");
				$response->x_open("Status");
					$response->x_print(1);
				$response->x_close("Status");

				$response->x_open("Response");
					$response->x_open("Fetch");

						if(isset($data["File"][$reference]) === false)
							$status = 15; # Attachment fetch provider - Attachment or attachment ID is invalid.
						else
							$status = 1;

						$response->x_switch("ItemOperations");

						$response->x_open("Status");
							$response->x_print($status);
						$response->x_close("Status");

						$response->x_switch("AirSyncBase");

						$response->x_open("FileReference");
							$response->x_print($file_reference);
						$response->x_close("FileReference");

						if($status == 1)
							{
							$response->x_switch("ItemOperations");

							$response->x_open("Properties");

								$response->x_switch("AirSyncBase");

								$response->x_open("ContentType");
									$response->x_print($data["File"][$reference]["AirSyncBase"]["ContentType"]);
								$response->x_close("ContentType");

								$response->x_switch("ItemOperations");

								$response->x_open("Data");
									$response->x_print($data["File"][$reference]["ItemOperations"]["Data"]);
								$response->x_close("Data");

								if(isset($xml->Fetch->Options->RightsManagementSupport))
									if(intval($xml->Fetch->Options->RightsManagementSupport) == 1)
										if(isset($data["RightsManagement"]))
											{
											$response->x_switch("RightsManagement");

											$response->x_open("RightsManagementLicense");

												# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

												foreach(active_sync_get_default_rights_management() as $token => $value)
													{
													if(isset($data["RightsManagement"][$token]) === false)
														continue;

													if(strlen($data["RightsManagement"][$token]) == 0)
														{
														$response->x_open($token, false);

														continue;
														}

													$response->x_open($token);
														$response->x_print($data["RightsManagement"][$token]);
													$response->x_close($token);
													}

											$response->x_close("RightsManagementLicense");
											}

							$response->x_close("Properties");
							}

					$response->x_close("Fetch");
				$response->x_close("Response");
			$response->x_close("ItemOperations");
			}

		if((isset($xml->Fetch->CollectionId)) && (isset($xml->Fetch->ServerId)))
			{
			$collection_id	= strval($xml->Fetch->CollectionId);
			$server_id	= strval($xml->Fetch->ServerId);
#			$irm	= isset($xml->Fetch->RemoveRightsManagementProtection);

			$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

			$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

			$response->x_switch("ItemOperations");

			$response->x_open("ItemOperations");

				$response->x_open("Status");
					$response->x_print(1);
				$response->x_close("Status");

				$response->x_open("Response");
					$response->x_open("Fetch");

						$response->x_open("Status");
							$response->x_print(1);
						$response->x_close("Status");

						$response->x_switch("AirSync");

						# what about calendar and contact and notes and things?

						foreach(array("CollectionId" => $collection_id, "ServerId" => $server_id) as $token => $value)
							{
							$response->x_open($token);
								$response->x_print($value);
							$response->x_close($token);
							}

						$response->x_switch("ItemOperations");

						$response->x_open("Properties");

							foreach(array("Email", "Email2") as $codepage)
								{
								if(isset($data[$codepage]) === false)
									continue;

								$response->x_switch($codepage);

								foreach($data[$codepage] as $token => $value)
									{
									if(strlen($value) == 0)
										{
										$response->x_open($token, false);

										continue;
										}

									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}
								}

							$response->x_switch("Email");

							if(isset($data["Flag"]))
								{
								$response->x_switch("Email");

								$response->x_open("Flag");

									foreach($data["Flag"] as $token => $value)
										{
										if(strlen($value) == 0)
											{
											$response->x_open($token, false);

											continue;
											}

										$response->x_open($token);
											$response->x_print($value);
										$response->x_close($token);
										}

								$response->x_close("Flag");
								}
							else
								$response->x_open("Flag", false);

							if(isset($data["Body"]) )
								{
								$default_class = active_sync_get_class_by_collection_id($user, $collection_id);

								if(isset($xml->Fetch->Options))
									{
									foreach($xml->Fetch->Options as $options)
										{
										if(isset($options->Class))
											if(isset($data["AirSync"]["Class"]))
												if(strval($options->Class) != $data["AirSync"]["Class"])
													continue;

										if(isset($options->RightsManagementSupport))
											if(intval($options->RightsManagementSupport) == 1)
												if(isset($data["RightsManagement"]))
													{
													$response->x_switch("RightsManagement");

													$response->x_open("RightsManagementLicense");

														# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

														foreach(active_sync_get_default_rights_management() as $token => $value)
															{
															if(isset($data["RightsManagement"][$token]) === false)
																continue;

															if(strlen($data["RightsManagement"][$token]) == 0)
																{
																$response->x_open($token, false);

																continue;
																}

															$response->x_open($token);
																$response->x_print($data["RightsManagement"][$token]);
															$response->x_close($token);
															}

													$response->x_close("RightsManagementLicense");
													}

										foreach($options->BodyPreference as $preference)
											{
											foreach($data["Body"] as $random_body_id => $null) # !!!
												{
												if(isset($data["Body"][$random_body_id]["Type"]) === false)
													continue;

												if($data["Body"][$random_body_id]["Type"] != intval($preference->Type))
													continue;

												$response->x_switch("AirSyncBase");

												$response->x_open("Body");

													if(isset($preference["Preview"]))
														foreach($data["Body"] as $random_preview_id => $null) # !!!
															{
															if(isset($data["Body"][$random_preview_id]["Type"]) === false)
																continue;

															if($data["Body"][$random_preview_id]["Type"] != 1)
																continue;

															$response->x_open("Preview");
																$response->x_print(substr($data["Body"][$random_preview_id]["Data"], 0, intval($preference->Preview)));
															$response->x_close("Preview");
															}

													if(isset($preference->TruncationSize))
														if(intval($preference->TruncationSize) != 0)
															if(isset($data["Body"][$random_body_id]["EstimatedDataSize"]) === false)
																{
																$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

																$response->x_open("Truncated");
																	$response->x_print(1);
																$response->x_close("Truncated");
																}
															elseif(intval($preference-Truncation-Size) > $data["Body"][$random_body_id]["EstimatedDataSize"])
																{
																}
															elseif(intval($preference->TruncationSize) < $data["Body"][$random_body_id]["EstimatedDataSize"])
																{
																$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

																$response->x_open("Truncated");
																	$response->x_print(1);
																$response->x_close("Truncated");
																}

													foreach($data["Body"][$random_body_id] as $token => $value)
														{
														if(strlen($data["Body"][$random_body_id][$token]) == 0)
															{
															$response->x_open($token, false);

															continue;
															}

														$response->x_open($token);
															$response->x_print($data["Body"][$random_body_id][$token]); # opaque data will fail :(
														$response->x_close($token);
														}

												$response->x_close("Body");
												}
											}
										}
									}
								}

							$response->x_switch("ItemOperations");

						$response->x_close("Properties");
					$response->x_close("Fetch");
				$response->x_close("Response");
			$response->x_close("ItemOperations");
			}
		}

	if(isset($xml->Move))
		{
		}

	return($response->response);
	}

function active_sync_handle_meeting_response($request)
	{
	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	if(isset($xml->Request))
		{
		$user_response	= strval($xml->Request->UserResponse);
		$collection_id	= strval($xml->Request->CollectionId);	# inbox
		$request_id	= strval($xml->Request->RequestId);	# server_id
		$long_id	= strval($xml->Request->LongId);
		$instance_id	= strval($xml->Request->InstanceId);	# used if appointment is a recurring one

		$user = $request["AuthUser"];
		$host = active_sync_get_domain();

		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		unlink(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $request_id . ".data");

		$calendar_id = active_sync_get_calendar_by_uid($user, $data["Meeting"]["Email"]["UID"]);

		$collection_id = active_sync_get_collection_id_by_type($user, 8); # Calendar
		# this need to be changed, this function has to return a list of all kind of calendars

		if($calendar_id == "")
			{
			$calendar = array();

			$calendar["Calendar"] = $data["Meeting"]["Email"];

			unset($calendar["Calendar"]["Organizer"]);

			list($organizer_name, $organizer_mail) = active_sync_mail_parse_address($data["Meeting"]["Email"]["Organizer"]);

			foreach(array("OrganizerName" => $organizer_name, "OrganizerEmail" => $organizer_mail) as $token => $value)
				{
				if($value == "")
					continue;

				$calendar["Calendar"][$token] = $value;
				}

			$calendar["Calendar"]["MeetingStatus"] = 3;

			$calendar["Calendar"]["Subject"] = $data["Email"]["Subject"];

			if($user_response == 1)
				$calendar["Calendar"]["ResponseType"] = 3;

			if($user_response == 2)
				$calendar["Calendar"]["ResponseType"] = 2;

			if($user_response == 3)
				$calendar["Calendar"]["ResponseType"] = 4;

			if($user_response != 3)
				{
				$calendar_id = active_sync_create_guid_filename($user, $collection_id);

				active_sync_put_settings_data($user, $collection_id, $calendar_id, $calendar);
				}

			$boundary = active_sync_create_guid();

			$description = array();

			$description[] = "Wann: " . date("d.m.Y H:i:s", strtotime($data["Meeting"]["Email"]["StartTime"]));

			if(isset($data["Meeting"]["Email"]["Location"]))
				$description[] = "Wo: " . $data["Meeting"]["Email"]["Location"];

			$description[] = "*~*~*~*~*~*~*~*~*~*";

			if(isset($data["Body"]))
				{
				foreach($data["Body"] as $body)
					{
					if(isset($body["Type"]) === false)
						continue;

					if($body["Type"] != 1)
						continue;

					if(isset($body["Data"]) === false)
						continue;

					$description[] = $body["Data"];
					}
				}

			$mime = array();

			$mime[] = "From: " . $data["Email"]["To"];
			$mime[] = "To: " . $data["Email"]["From"];

			foreach(array("Accepted" => 1, "Tentative" => 2, "Declined" => 3) as $subject => $value)
				{
				if($user_response != $value)
					continue;

				$mime[] = "Subject: " . $subject . ": " . $data["Email"]["Subject"];
				}

			$mime[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/plain; charset=\"utf-8\"";
			$mime[] = "";
			$mime[] = implode("\n", $description);
			$mime[] = "";

			foreach(array("Accepted" => 1, "Tentative" => 2, "Declined" => 3) as $message => $value)
				{
				if($user_response != $value)
					continue;

				$mime[] = $message;
				}

			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/calendar; method=REPLY; name=\"invite.ics\"";
			$mime[] = "";
			$mime[] = "BEGIN:VCALENDAR";
				$mime[] = "METHOD:REPLY";
				$mime[] = "PRODID:" . active_sync_get_version();
				$mime[] = "VERSION:2.0";
				# VTIMEZONE
				$mime[] = "BEGIN:VEVENT";
					$mime[] = "UID:" . $data["Meeting"]["Email"]["UID"];

					foreach(array("DTSTAMP" => "DtStamp", "DTSTART" => "StartTime", "DTEND" => "EndTime") as $key => $token)
						$mime[] = $key . ":" . date("Y-m-d\TH:i:s\Z", strtotime($data["Meeting"]["Email"][$token]));

					if(isset($data["Meeting"]["Location"]))
						$mime[] = "LOCATION: " . $data["Meeting"]["Email"]["Location"];

					if(isset($data["Email"]["Subject"]))
						$mime[] = "SUMMARY: " . $data["Email"]["Subject"]; # take this from email subject

					$mime[] = "DESCRIPTION:" . implode("\\n", $description);

					foreach(array("FALSE" => 0, "TRUE" => 1) as $key => $value)
						{
						if($data["Meeting"]["Email"]["AllDayEvent"] != $value)
							continue;

						$mime[] = "X-MICROSOFT-CDO-ALLDAYEVENT:" . $key;
						}

					foreach(array("ACCEPTED" => 1, "TENTATIVE" => 2, "DECLINED" => 3) as $partstat => $value)
						{
						if($user_response != $value)
							continue;

						$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=" . $partstat . ";RSVP=TRUE:MAILTO:" . $user . "@" . $host;
						}

					list($organizer_name, $organizer_mail) = active_sync_mail_parse_address($data["Meeting"]["Email"]["Organizer"]);

					if($organizer_name == "")
						$mime[] = "ORGANIZER:MAILTO:" . $organizer_mail;
					else
						$mime[] = "ORGANIZER;CN=\"" . $organizer_name . "\":MAILTO:" . $organizer_mail;

					$mime[] = "STATUS:CONFIRMED";
					$mime[] = "TRANSP:OPAQUE";
					$mime[] = "PRIORITY:5";
					$mime[] = "SEQUENCE:0";

				$mime[] = "END:VEVENT";
			$mime[] = "END:VCALENDAR";
			$mime[] = "";
			$mime[] = "--" . $boundary . "--";

			$mime = implode("\n", $mime);

			active_sync_send_mail($user, $mime);
			}

		# http://msdn.microsoft.com/en-us/library/exchange/hh428684%28v=exchg.140%29.aspx
		# http://msdn.microsoft.com/en-us/library/exchange/hh428685%28v=exchg.140%29.aspx

		$response->x_switch("MeetingResponse");

		$response->x_open("MeetingResponse");

			$response->x_open("Result");

				foreach(array("Status" => 1, "RequestId" => $request_id, "CalendarId" => $calendar_id) as $token => $value)
					{
					$response->x_open($token);
						$response->x_print($value);
					$response->x_close($token);
					}

			$response->x_close("Result");

		$response->x_close("MeetingResponse");
		}

	return($response->response);
	}

#function active_sync_handle_move_collection($request)
#	{
#	}

function active_sync_handle_move_items($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Move");

	$response->x_open("MoveItems");

		if(isset($xml->Move))
			{
			foreach($xml->Move as $move)
				{
				$src_msg_id = strval($move->SrcMsgId);
				$src_fld_id = strval($move->SrcFldId);
				$dst_fld_id = strval($move->DstFldId);

				if(is_dir(DAT_DIR . "/" . $request["AuthUser"] . "/" . $src_fld_id) === false)
					$status = 1; # Invalid source collection ID or invalid source Item ID.
				elseif(file_exists(DAT_DIR . "/" . $request["AuthUser"] . "/" . $src_fld_id . "/" . $src_msg_id . ".data") === false)
					$status = 1; # Invalid source collection ID or invalid source Item ID.
				elseif(count($move->DstFldId) > 1)
					$status = 5; # One of the following failures occurred: the item cannot be moved to more than one item at a time, or the source or destination item was locked.
				elseif(is_dir(DAT_DIR . "/" . $request["AuthUser"] . "/" . $dst_fld_id) === false)
					$status = 2; # Invalid destination collection ID.
				elseif($src_fld_id == $dst_fld_id)
					$status = 4; # Source and destination collection IDs are the same.
				else
					{
					$dst_msg_id = active_sync_create_guid_filename($request["AuthUser"], $dst_fld_id);

					$src = DAT_DIR . "/" . $request["AuthUser"] . "/" . $src_fld_id . "/" . $src_msg_id . ".data";
					$dst = DAT_DIR . "/" . $request["AuthUser"] . "/" . $dst_fld_id . "/" . $dst_msg_id . ".data";

					if(rename($src, $dst) === false)
						$status = 7; # Source or destination item was locked.
					else
						$status = 3; # Success.
					}

				$response->x_open("Response");

					foreach(($status == 3 ? array("Status" => $status, "SrcMsgId" => $src_msg_id, "DstMsgId" => $dst_msg_id) : array("Status" => $status, "SrcMsgId" => $src_msg_id)) as $token => $value)
						{
						$response->x_open($token);
							$response->x_print($value);
						$response->x_close($token);
						}

				$response->x_close("Response");
				}
			}

	$response->x_close("MoveItems");

	return($response->response);
	}

function active_sync_handle_ping($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	if($request["wbxml"] == null)
		$xml = simplexml_load_string("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<Ping xmlns=\"Ping\"/>", "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);
	else
		$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$test = $xml->asXML(); $test = active_sync_wbxml_pretty($test); file_put_contents(LOG_DIR . "/test-before.txt", $test);

	if(isset($xml->HeartbeatInterval))
		{
		unset($settings["HeartbeatInterval"]);

		$settings["HeartbeatInterval"] = intval($xml->HeartbeatInterval);
		}

	if(isset($xml->Folders))
		{
		unset($settings["Ping"]);

		foreach($xml->Folders->Folder as $folder)
			{
			$z = array();

			$z["Id"] = strval($folder->Id);
			$z["Class"] = strval($folder->Class);

			$settings["Ping"][] = $z;
			}
		}

	if(isset($settings["HeartbeatInterval"]))
		{
		unset($xml->HeartbeatInterval);

		$x = $xml->addChild("HeartbeatInterval", $settings["HeartbeatInterval"]);
		}
	else
		{
		unset($xml->Folders);

		$x = $xml->addChild("Folders");

		foreach($settings["Ping"] as $folder)
			{
			$y = $x->addChild("Folder");

			$y->addChild("Id", $folder["Id"]);
			$y->addChild("Class", $folder["Class"]);
			}
		}

	$test = $xml->asXML(); $test = active_sync_wbxml_pretty($test); file_put_contents(LOG_DIR . "/test-after.txt", $test);

	$settings["Port"] = (isset($_SERVER["REMOTE_PORT"]) ? $_SERVER["REMOTE_PORT"] : "i");

	active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings);

#	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$timeout = microtime(true);
	$max_folders = 300;

	$changed_folders = array();

	while(1)
		{
		if(active_sync_get_need_wipe($request) != 0)
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(active_sync_get_need_provision($request) != 0)
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(active_sync_get_need_folder_sync($request) != 0)
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(isset($xml->Folders) === false)
			{
			$status = 3; # The Ping command request omitted required parameters.

			break;
			}

		if(count($xml->Folders->Folder) > $max_folders)
			{
			$status = 6; # The Ping command request specified more than the allowed number of folders to monitor.

			break;
			}

		if(isset($xml->HeartbeatInterval) === false)
			{
			$status = 3; # The Ping command request omitted required parameters.

			break;
			}

		if(intval($xml->HeartbeatInterval) < 60)
			{
			$status = 5; # The specified heartbeat interval is outside the allowed range.

			$heartbeat_interval = 60;

			break;
			}

		if(intval($xml->HeartbeatInterval) > 3540)
			{
			$status = 5; # The specified heartbeat interval is outside the allowed range.

			$heartbeat_interval = 3540;

			break;
			}

		if(($timeout + intval($xml->HeartbeatInterval)) < microtime(true))
			{
			$status = 1; # The heartbeat interval expired before any changes occurred in the folders being monitored.

			break;
			}

		foreach($xml->Folders->Folder as $folder)
			{
			$changes_detected = 0;
			$collection_id = strval($folder->Id);

			$settings_client = active_sync_get_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"]);

			$settings_server = active_sync_get_settings_sync($request["AuthUser"], $collection_id, "");

			if($settings_client["SyncKey"] == 0)
				$changes_detected = 1;

			foreach($settings_server["SyncDat"] as $server_id => $null)
				{
				if($changes_detected == 1)
					continue;

				if(isset($settings_client["SyncDat"][$server_id]) === false)
					$changes_detected = 1;

				if($changes_detected == 1)
					break;

				if($settings_client["SyncDat"][$server_id] == "*")
					continue;

				if($changes_detected == 1)
					break;

				if($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
					$changes_detected = 1;

				if($changes_detected == 1)
					break;
				}

			foreach($settings_client["SyncDat"] as $server_id => $null)
				{
				if($changes_detected == 1)
					continue;

				if(isset($settings_server["SyncDat"][$server_id]))
					continue;

				$changes_detected = 1;
				}

			if($changes_detected == 0)
				continue;

			$changed_folders[] = $collection_id;
			}

		if(count($changed_folders) > 0)
			{
			$status = 2; # Changes occured in at least one of the monitored folders. The response specifies the changed folders.

			break;
			}

		$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

		if((isset($settings["Port"]) === false ? "n" : $settings["Port"]) != (isset($_SERVER["REMOTE_PORT"]) === false ? "s" : $_SERVER["REMOTE_PORT"]))
			{
			$status = 8; # An error occurred on the server.

			active_sync_debug("DIED", "RESPONSE"); die();

			break;
			}

		sleep(10);

		clearstatcache();
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("Ping");

	$response->x_open("Ping");

		$response->x_open("Status");
			$response->x_print($status);
		$response->x_close("Status");

		if($status == 2) # Changes occured in at least one of the monitored folders. The response specifies the changed folders.
			{
			$response->x_open("Folders");

				foreach($changed_folders as $collection_id)
					{
					$response->x_open("Folder");
						$response->x_print($collection_id);
					$response->x_close("Folder");
					}

			$response->x_close("Folders");
			}

		if($status == 5) # The specified heartbeat interval is outside the allowed range.
			{
			$response->x_open("HeartbeatInterval");
				$response->x_print($heartbeat_interval);
			$response->x_close("HeartbeatInterval");
			}

		if($status == 6) # The Ping command request specified more than the allowed number of folders to monitor.
			{
			$response->x_open("MaxFolders");
				$response->x_print($max_folders);
			$response->x_close("MaxFolders");
			}

	$response->x_close("Ping");

	return($response->response);
	}

define("REMOTE_WIPE", 1);
define("ACCOUNT_ONLY_REMOTE_WIPE", 2);

function active_sync_handle_provision($request)
	{
	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Provision");

	$response->x_open("Provision");

		if(isset($xml->DeviceInformation))
			{
			if(isset($xml->DeviceInformation->Set))
				{
				$info = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

				foreach(active_sync_get_default_info() as $token)
					{
					if(isset($xml->DeviceInformation->Set->$token) === false)
						continue;

					$info["DeviceInformation"][$token] = strval($xml->DeviceInformation->Set->$token);
					}

				active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $info);

				$status = 1; # Success.
				}
			else
				$status = 2;

			$response->x_switch("Settings");

			$response->x_open("DeviceInformation");
				$response->x_open("Status");
					$response->x_print($status);
				$response->x_close("Status");
			$response->x_close("DeviceInformation");
			}

		if(active_sync_get_need_wipe($request) != 0)
			{
			}
		elseif(isset($xml->Policies))
			{
			$device_id = $request["DeviceId"];

			$show_policy = 0;
			$show_empty = 0;
			$show_status = 1;

			active_sync_debug("PolicyKey: " . $request["PolicyKey"]);

			$settings_server = active_sync_get_settings(DAT_DIR . "/login.data");

			$settings_client = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

			$settings_client["PolicyKey"] = $settings_server["Policy"]["PolicyKey"];

			active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings_client);

			if(isset($xml->Policies->Policy) === false)
				$status = 3; # Unknown PolicyType value.
			elseif(isset($xml->Policies->Policy->PolicyType) === false)
				$status = 3; # Unknown PolicyType value.
			elseif(strval($xml->Policies->Policy->PolicyType) != "MS-EAS-Provisioning-WBXML")
				$status = 3; # Unknown PolicyType value.
			elseif(isset($xml->Policies->Policy->Status) === false)
				{
				if(isset($settings_server["Policy"]["PolicyKey"]) === false)
					{
					$show_empty = 1;

					$status = 1; # There is no policy for this client.
					}
				elseif(isset($settings_server["Policy"]["Data"]) === false)
					{
					$show_empty = 1;

					$status = 1; # There is no policy for this client.
					}
				else
					{
					$show_policy = 1;

					$status = 1; # Success.
					}

				$show_status = 0;
				}
			elseif(intval($xml->Policies->Policy->Status) == 1)
				$status = 1; # Success.
			elseif(intval($xml->Policies->Policy->Status) == 2)
				$status = 1; # Success.
			elseif(isset($xml->Policies->Policy->PolicyKey) === false)
				$status = 5; # The client is acknowledging the wrong policy key.
			elseif(strval($xml->Policies->Policy->PolicyKey) != $settings_server["Policy"]["PolicyKey"])
				$status = 5; # The client is acknowledging the wrong policy key.
			else
				$status = 1; # Success.

			$response->x_switch("Provision");

			if($show_status == 1)
				{
				$response->x_open("Status");
					$response->x_print(1);
				$response->x_close("Status");
				}

			$response->x_open("Policies");
				$response->x_open("Policy");

					$response->x_open("PolicyType");
						$response->x_print("MS-EAS-Provisioning-WBXML");
					$response->x_close("PolicyType");

					$response->x_open("Status");
						$response->x_print($status);
					$response->x_close("Status");

					$response->x_open("PolicyKey");
						$response->x_print($settings_server["Policy"]["PolicyKey"]);
					$response->x_close("PolicyKey");

					if($show_policy == 1)
						{
						$response->x_open("Data");
							$response->x_open("EASProvisionDoc");

								foreach(array("ApprovedApplicationList" => "Hash", "UnapprovedInROMApplicationList" => "ApplicationName") as $k => $v)
									{
									if(isset($settings_server["Policy"]["Data"][$k]) === false)
										continue;

									$response->x_open($k);

										foreach(explode("\n", $settings_server["Policy"]["Data"][$k]) as $value)
											{
											$response->x_open($v);
												$response->x_print($value);
											$response->x_close($v);
											}

									$response->x_close($k);
									}

								foreach(active_sync_get_default_policy() as $token => $value)
									{
									if($token == "ApprovedApplicationList" || $token == "UnapprovedInROMApplicationList")
										continue;

									if(isset($settings_server["Policy"]["Data"][$token]) === false)
										continue;

									$response->x_open($token);
										$response->x_print($settings_server["Policy"]["Data"][$token]);
									$response->x_close($token);
									}

							$response->x_close("EASProvisionDoc");
						$response->x_close("Data");
						}

					if($show_empty == 1)
						{
						$response->x_open("Data");
							$response->x_open("EASProvisionDoc", false);
						$response->x_close("Data");
						}

				$response->x_close("Policy");
			$response->x_close("Policies");
			}

		if(active_sync_get_need_wipe($request) != 0)
			{
			$remote_wipe = 0;

			if(isset($xml->RemoteWipe) === false)
				$status = 1; # The client remote wipe was sucessful.
			elseif(isset($xml->RemoteWipe->Status) === false)
				{
				$remote_wipe = ACCOUNT_ONLY_REMOTE_WIPE;

				$status = 1; # The client remote wipe was sucessful.
				}
			elseif(intval($xml->RemoteWipe->Status) == 1) # The client remote wipe operation was successful.
				{
				active_sync_handle_provision_remote_wipe($request);

				$status = 1; # The client remote wipe was sucessful.
				}
			elseif(intval($xml->RemoteWipe->Status) == 2) # The remote wipe operation failed.
				$status = 1; # The client remote wipe was sucessful.
			else
				$status = 2; # Protocol error.

			$response->x_switch("Provision");

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($remote_wipe == 0)
				{
				}
			elseif($remote_wipe == REMOTE_WIPE)
				$response->x_open("RemoteWipe", false);
			elseif($remote_wipe == ACCOUNT_ONLY_REMOTE_WIPE)
				$response->x_open("AccountOnlyRemoteWipe", false);
			}

	$response->x_close("Provision");

	return($response->response);
	}

function active_sync_handle_provision_remote_wipe($request)
	{
	foreach(array("wipe") as $extension)
		{
		$file = DAT_DIR . "/" . $request["DeviceId"] . "." . $extension;

		if(file_exists($file))
			unlink($file);
		}

	return;

	$users = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($users as $user_id => $user_data)
		{
		if(is_dir(DAT_DIR . "/" . $user_data["User"]) === false)
			continue;

		$folders = active_sync_get_settings(DAT_DIR . "/" . $user_data["User"] . ".sync");

		foreach($folders as $folder_id => $folder_data)
			{
			if(is_dir(DAT_DIR . "/" . $user_data["User"] . "/" . $folder_data["ServerId"]))
				continue;

			foreach(array("sync") as $extension)
				{
				$file = DAT_DIR . "/" . $user_data["User"] . "/" . $folder_data["ServerId"] . "/" . $request["DeviceId"] . "." . $extension;

				if(file_exists($file))
					unlink($file);
				}
			}

		foreach(array("info", "stat", "sync") as $extension)
			{
			$file = DAT_DIR . "/" . $user_data["User"] . "/" . $request["DeviceId"] . "." . $extension;

			if(file_exists($file))
				unlink($file);
			}
		}
	}

function active_sync_handle_resolve_recipients($request)
	{
	$host = active_sync_get_domain(); # needed for user@host

	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$recipients = array();

	if(isset($xml->To) === false)
		$status = 5;
	elseif(count($xml->To) > 100)
		$status = 5;
	else
		{
		$users = active_sync_get_settings(DAT_DIR . "/login.data");

		foreach($xml->To as $to)
			{
			$to = strval($to);

			foreach($users["login"] as $user)
				foreach(glob(DAT_DIR . "/" . $user["User"] . "/9009/*.data") as $file) # contact
					{
					$server_id = basename($file, ".data");

					$data = active_sync_get_settings_data($user["User"], "9009", $server_id);

					foreach(array("Email1Address", "Email2Address", "Email3Address") as $token)
						{
						if(isset($data["Contacts"][$token]) === false)
							continue;

						if(strlen($data["Contacts"][$token]) == 0)
							continue;

						list($t_name, $t_mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

						if($t_mail != $to)
							continue;

						if($user["User"] != $request["AuthUser"])
							$recipients[$to][] = array("Type" => 1, "DisplayName" => $t_name, "EmailAddress" => $t_mail);

						if($user["User"] == $request["AuthUser"])
							$recipients[$to][] = array("Type" => 2, "DisplayName" => $t_name, "EmailAddress" => $t_mail);

						break(2); # foreach, while
						}
					}
			}
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("ResolveRecipients");

	$response->x_open("ResolveRecipients");
		$response->x_open("Status");
			$response->x_print(1);
		$response->x_close("Status");

		foreach($xml->To as $to)
			{
			$to = strval($to);

			$response->x_open("Response");

				foreach(array("To" => $to, "Status" => (count($recipients[$to]) > 1 ? 2 : 1), "RecipientCount" => count($recipients[$to])) as $token => $value)
					{
					$response->x_open($token);
						$response->x_print($value);
					$response->x_close($token);
					}

				foreach($recipients[$to] as $id => $recipient)
					{
					$response->x_open("Recipient");

						foreach(array("Type", "DisplayName", "EmailAddress") as $field)
							{
							$response->x_open($field);
								$response->x_print($recipient[$field]);
							$response->x_close($field);
							}

						if(isset($xml->Options->Availability) === false)
							{
							}
						elseif(isset($xml->Options->Availability->StartTime) === false)
							{
							}
						elseif(isset($xml->Options->Availability->EndTime) === false)
							{
							}
						elseif(((strtotime($xml->Options->Availability->EndTime) - strtotime($xml->Options->Availability->StartTime)) / 1800) > 32768)
							{
							$response->x_open("Availability");
								$response->x_open("Status");
									$response->x_print(162);
								$response->x_close("Status");
							$response->x_close("Availability");
							}
						else
							{
							$start_time = strtotime($xml->Options->Availability->StartTime);
							$end_time = strtotime($xml->Options->Availability->EndTime);

							$merged_free_busy = array_fill(0, ($end_time - $start_time) / 1800, 4); # 4 = no data

							list($t_name, $t_mail) = active_sync_mail_parse_address($recipient["EmailAddress"]);
							list($t_user, $t_host) = explode("@", $t_mail);

							if($t_host == $host)
								{
								foreach(glob(DAT_DIR . "/" . $t_user . "/9008/*.data") as $file)
									{
									$server_id = basename($file, ".data");

									$data = active_sync_get_settings_data($t_user, "9008", $server_id);

									if(strtotime($data["Calendar"]["StartTime"]) > $end_time)
										continue;

									if(strtotime($data["Calendar"]["EndTime"]) < $start_time)
										continue;

									foreach(array("EndTime" => 0, "StartTime" => 0, "BusyStatus" => 0) as $token => $value)
										$data["Calendar"][$token] = (isset($data["Calendar"][$token]) === false ? $value : $data["Calendar"][$token]);

									foreach(array("EndTime" => 0, "StartTime" => 0) as $token => $value)
										$data["Calendar"][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data["Calendar"][$token]));

									for($x = $start_time; $x < $end_time; $x += 1800)
										{
										if($x < strtotime($data["Calendar"]["StartTime"]))
											continue;

										if($x + 1800 > strtotime($data["Calendar"]["EndTime"]))
											continue;

										$merged_free_busy[($x - $start_time) / 1800] = $data["Calendar"]["BusyStatus"];
										}
									}
								}

							$response->x_open("Availability");

								foreach(array("Status" => 1, "MergedFreeBusy" => implode("", $merged_free_busy)) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

							$response->x_close("Availability");
							}

						if(isset($xml->Options->CertificateRetrieval) === false)
							{
							}
						elseif(intval($xml->Options->CertificateRetrieval) == 1) # Do not retrieve certificates for the recipient (default).
							{
							}
						elseif(file_exists(CRT_DIR . "/certs/" . $recipient["EmailAddress"] . ".pem") === false)
							{
							$response->x_open("Certificates");

								foreach(array("Status" => 7, "CertificateCount" => 0) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

							$response->x_close("Certificates");
							}
						elseif(intval($xml->Options->CertificateRetrieval) == 2) # Retrieve the full certificate for each resolved recipient.
							{
							$certificate = file_get_contents(CRT_DIR . "/certs/" . $recipient["EmailAddress"] . ".pem");

							list($null, $certificate) = explode("-----BEGIN CERTIFICATE-----", $certificate, 2);
							list($certificate, $null) = explode("-----END CERTIFICATE-----", $certificate, 2);

							$certificate = str_replace(array("\r", "\n"), "", $certificate);

							$response->x_open("Certificates");

								foreach(array("Status" => 1, "CertificateCount" => 1) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

								$response->x_open("Certificate");
									$response->x_print($certificate); # ... contains the X509 certificate ... encoded with base64 ...
								$response->x_close("Certificate");
							$response->x_close("Certificates");
							}
						elseif(intval($xml->Options->CertificateRetrieval) == 3) # Retrieve the mini certificate for each resolved recipient.
							{
							$certificate = file_get_contents(CRT_DIR . "/certs/" . $recipient["EmailAddress"] . ".pem");

							list($null, $certificate) = explode("-----BEGIN CERTIFICATE-----", $certificate, 2);
							list($certificate, $null) = explode("-----END CERTIFICATE-----", $certificate, 2);

							$certificate = str_replace(array("\r", "\n"), "", $certificate);

							$response->x_open("Certificates");

								foreach(array("Status" => 1, "CertificateCount" => 1) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

								$response->x_open("MiniCertificate");
									$response->x_print($certificate); # ... contains the mini-certificate ... encoded with base64 ...
								$response->x_close("MiniCertificate");
							$response->x_close("Certificates");
							}

						if(isset($xml->Options->Picture))
							if(isset($xml->Options->Picture->MaxPictures))
								if(isset($xml->Options->Picture->MaxSize))
									{
#									$response->x_open("Picture");
#										$response->x_open("Status");
#											$response->x_print(1);
#										$response->x_close("Status");
#
#										$response->x_open("Data");
#											$response->x_print();
#										$response->x_close("Data");
#									$response->x_close("Picture");
									}

					$response->x_close("Recipient");
					}

			$response->x_close("Response");
			}

	$response->x_close("ResolveRecipients");

	return($response->response);
	}

function active_sync_handle_search($request)
	{
	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Search");

	$response->x_open("Search");

		if(isset($xml->Store) === false)
			$status = 3; # Server error.
		elseif(isset($xml->Store->Name) === false)
			$status = 3; # Server error.
		elseif(isset($xml->Store->Query) === false)
			$status = 3; # Server error.
		else
			$status = 1; # Server error.

		$response->x_open("Status");
			$response->x_print($status);
		$response->x_close("Status");

		$response->x_open("Response");
			$response->x_open("Store");

				if(isset($xml->Store) === false)
					$status = 3; # Server error.
				elseif(isset($xml->Store->Name) === false)
					$status = 3; # Server error.
				elseif(strval($xml->Store->Name) == "GAL")
					$status = 1; # Ok.
				elseif(strval($xml->Store->Name) == "Mailbox")
					$status = 1; # Ok.
				elseif(strval($xml->Store->Name) == "Document Library")
					$status = 3; # Server error.
				else
					$status = 1; # Server error.

				$response->x_open("Status");
					$response->x_print($status);
				$response->x_close("Status");

				if($status != 1)
					{
					}
				elseif(strval($xml->Store->Name) == "GAL")
					{
					$query = strval($xml->Store->Query);

					$retval = array();

					$settings = active_sync_get_settings(DAT_DIR . "/login.data");

					foreach($settings["login"] as $login_data)
						{
						if($login_data["User"] == $request["AuthUser"])
							continue;

						foreach(glob(DAT_DIR . "/" . $login_data["User"] . "/9009/*.data") as $file)
							{
							$server_id = basename($file, ".data");

							$data = active_sync_get_settings_data($login_data["User"], "9009", $server_id);

							$data["Contacts"]["FileAs"] = active_sync_create_fullname_from_data($data);

							foreach(array("Email1Address", "Email2Address", "Email3Address") as $token)
								{
								if(isset($data["Contacts"][$token]) === false)
									continue;

								if(strlen($data["Contacts"][$token]) == 0)
									continue;

								list($name, $mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

								$data["Contacts"][$token] = $mail;
								}

							foreach(array("Email1Address", "Email2Address", "Email3Address", "FirstName", "LastName", "MiddleName") as $token)
								{
								if(isset($data["Contacts"][$token]) === false)
									continue;

								if(strtolower(substr($data["Contacts"][$token], 0, strlen($query))) != strtolower($query))
									continue;

								$retval[] = $data["Contacts"];

								break;
								}
							}
						}

					usort($retval, function($a, $b){return($a["FileAs"] - $b["FileAs"]);});

					if(isset($xml->Store->Options->Range) === false)
						$range = "0-99"; # default is written to 100 results somewhere ... really
					else
						$range = strval($xml->Store->Options->Range);

					list($m, $n) = explode("-", $range);

					$p = 0;

					foreach($retval as $data)
						{
						if($m > $n)
							break;

						$m ++;

						$response->x_switch("Search");

						$response->x_open("Result");

							$response->x_open("Properties");

								$response->x_switch("GAL");

								foreach(array("DisplayName" => "FileAs", "Title" => "Title", "Company" => "CompanyName", "Alias" => "Alias", "FirstName" => "FirstName", "LastName" => "LastName", "MobilePhone" => "MobilePhoneNumber", "EmailAddress" => "Email1Address") as $token_gal => $token_contact)
									{
									if(isset($data[$token_contact]) === false)
										continue;

									if(strlen($data[$token_contact]) == 0)
										continue;

									$response->x_open($token_gal);
										$response->x_print($data[$token_contact]);
									$response->x_close($token_gal);
									}

								if(isset($data["Picture"]) === false)
									$status = 173;
								elseif(strlen($data["Picture"]) == 0)
									$status = 173;
								elseif(isset($xml->Store->Options->Picture->MaxSize) === false)
									$status = 1;
								elseif(intval($xml->Store->Options->Picture->MaxSize) < strlen($data["Picture"]))
									$status = 174;
								elseif(isset($xml->Store->Options->Picture->MaxPicture) === false)
									$status = 1;
								elseif(intval($xml->Store->Options->Picture->MaxPicture) < $p)
									$status = 175;
								else
									$status = 1;

								$response->x_open("Picture");

									$response->x_open("Status");
										$response->x_print($status);
									$response->x_close("Status");

									if($status == 1)
										{
										$response->x_open("Data");
											$response->x_print($data["Picture"]);
										$response->x_close("Data");

										$p ++;
										}

								$response->x_close("Picture");

								$response->x_switch("Search");

							$response->x_close("Properties");
						$response->x_close("Result");
						}

					$response->x_switch("Search");

					foreach(array("Range" => $range, "Total" => $m) as $token => $value)
						{
						$response->x_open($token);
							$response->x_print($value);
						$response->x_close($token);
						}
					}
				elseif(strval($xml->Store->Name) == "Mailbox")
					{
					################################################################################
					# init ...
					################################################################################

					$class		= strval($xml->Store->Query->And->Class);
					$collection_id	= strval($xml->Store->Query->And->CollectionId);
					$free_text	= strval($xml->Store->Query->And->FreeText);

					# are GreatherThan->DateReceived, LessThan->DateReceived, FreeText optional?

					$retval = array();

					foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/*.data") as $file)
						{
						if(isset($xml->Store->Query->And->GreaterThan) === false)
							continue;

						if(isset($xml->Store->Query->And->GreaterThan->DateReceived) === false) # empty but existing value
							continue;

						if(isset($xml->Store->Query->And->GreaterThan->Value) === false)
							continue;

						if(isset($xml->Store->Query->And->LessThan) === false)
							continue;

						if(isset($xml->Store->Query->And->LessThan->DateReceived) === false) # empty but existing value
							continue;

						if(isset($xml->Store->Query->And->LessThan->Value) === false)
							continue;

						$server_id = basename($file, ".data");

						$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

						if(isset($data["AirSync"]["Class"]) === false)
							{
							}
						elseif($data["AirSync"]["Class"] != $class)
							continue;

						if(strtotime($data["Email"]["DateReceived"]) < strtotime(strval($xml->Store->Query->And->GreaterThan->Value)))
							continue;

						if(strtotime($data["Email"]["DateReceived"]) > strtotime(strval($xml->Store->Query->And->LessThan->Value)))
							continue;

						if(strpos(strtolower($data["Body"][4]["Data"]), strtolower($free_text)) === false) # check mime ...
							continue;

						$retval[] = $data;
						}

					if(isset($xml->Store->Options->Range) === false)
						$range = "0-99";
					else
						$range = strval($xml->Store->Options->Range);

					list($m, $n) = explode("-", $range);

					foreach($retval as $retval_data)
						{
						if($m > $n)
							break;

						$m ++;

						$response->x_switch("Search");

						$response->x_open("Result");

							$response->x_switch("AirSync");

							foreach(array("Class" => $class, "CollectionId" => $collection_id) as $token => $value)
								{
								$response->x_open($token);
									$response->x_print($value);
								$response->x_close($token);
								}

							$response->x_switch("Search");

							$response->x_open("Properties");

								if(isset($retval_data["Email"]))
									{
									$response->x_switch("Email");

									foreach($retval_data["Email"] as $token => $value)
										{
										if(strlen($value) == 0)
											{
											$response->x_open($token, false);

											continue;
											}

										$response->x_open($token);
											$response->x_print($value);
										$response->x_close($token);
										}
									}

								if(isset($retval_data["Body"][4]))
									{
									$response->x_switch("AirSyncBase");

									$response->x_open("Body");

										foreach($retval_data["Body"][4] as $token => $value)
											{
											if(strlen($value) == 0)
												{
												$response->x_open($token, false);

												continue;
												}

											$response->x_open($token);
												$response->x_print($value);
											$response->x_close($token);
											}

									$response->x_close("Body");
									}

								$response->x_switch("Search");

							$response->x_close("Properties");
						$response->x_close("Result");
						}

					$response->x_switch("Search");

					foreach(array("Range" => $range, "Total" => $m) as $token => $value)
						{
						$response->x_open($token);
							$response->x_print($value);
						$response->x_close($token);
						}
					}

			$response->x_close("Store");
		$response->x_close("Response");
	$response->x_close("Search");

	return($response->response);
	}

function active_sync_handle_send_mail($request)
	{
	if($request["ContentType"] == "application/vnd.ms-sync.wbxml")
		{
		$request = active_sync_handle_send_mail_fix_android($request); # !!!!!!!!!!!!!!!

		$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

		$mime = strval($xml->Mime);

		if(isset($xml->SaveInSentItems))
			$save_in_sent_items = "T";
		else
			$save_in_sent_items = "F";
		}

	if($request["ContentType"] == "message/rfc822")
		{
		$save_in_sent_items = $request["SaveInSent"]; # name of element in request-line differs from what can be gotten from request-body

		$mime = strval($request["wbxml"]);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("ComposeMail");

	$response->x_open("SendMail");

		$response->x_open("Status");
			$response->x_print(1);
		$response->x_close("Status");

	$response->x_close("SendMail");

	if($save_in_sent_items == "T")
		{
		$collection_id = active_sync_get_collection_id_by_type($request["AuthUser"], 5);

		$server_id = active_sync_create_guid_filename($request["AuthUser"], $collection_id);

		$data = active_sync_mail_parse($request["AuthUser"], $collection_id, $server_id, $mime);

		$data["Email"]["Read"] = 1;

		active_sync_put_settings_data($request["AuthUser"], $collection_id, $server_id, $data);
		}

	active_sync_send_mail($request["AuthUser"], $mime);

	return("");
#	return($response->response);
	}

function active_sync_handle_send_mail_fix_android($request)
	{
	# Mime of SendMail makes problems here. stream of GT-S6802 is as follows:

	# 0000   03 01 6a 00 00 15 45 51 03 31 32 38 31 00 01 48  ..j...EQ.1281..H
	# 0010   03 00 01 50 c3 8c 44 61 74 65 3a 20 54 75 65 2c  ...P..Date: Tue,
	# ...
	# 05f0   6f 6d 2e 61 6e 64 72 6f 69 64 2e 65 6d 61 69 6c  om.android.email
	# 0600   5f 31 36 39 38 37 37 31 31 39 38 38 38 34 32 32  _169877119888422
	# 0610   2d 2d 0d 0a 0d 0a 01 01                          --......

	# 0x03			this is wbxml version
	# 0x01			this is wbxml publicid
	# 0x6A			this is wbxml charset
	# 0x00			this is wbxml strtbl
	# 0x00			SWITCH_PAGE
	# 0x15			ComposeMail
	# 0x45			SendMail
	# 0x51				ClientId
	# 0x03					STR_I (start)
	# 0x31 0x32 0x38 0x31				1281
	# 0x00					STR_I (end)
	# 0x01				END
	# 0x48				SaveInSentItems
	# 0x03					STR_I (start)
	# 0x00					STR_I (end)
	# 0x01				END
	# 0x50				Mime
	# 0xC3					OPAQUE
	# 0x8C 0x44					this is mb_int wich result in 1604 ... but 0x44 is first letter of string "Date: ..."
	# ...
	# 0x01				END
	# 0x01			END

	# wireshark states that data length of mime-data is 1604 bytes (0x8C 0x44)
	# after extracting mime-data by hand data length has 1536 (0x8C 0x00) bytes only

	# 0x8C - 1 000 1100
	# 0x44 - 0 100 0100
	# 0001100 1000100 -> 1604

	# 0x8C - 1 000 1100
	# 0x00 - 0 000 0000
	# 0001100 0000000 -> 1536

	# GT-S6802 removes this 0x00 wich result in a wrong calculation of length of the
	# upcoming OPAQUE data length

	# now we try to find this occurence
	# we check if "Date" follows after single byte (.?) of mb_int
	# so far this only works for mails up to 16256 bytes
	# 0xFF 0x00      -> 1 1111111 0 0000000           ->            0011 1111 1000 0000 -> 0x3F 0x80      -> 16256
	# 0xFF 0x00 0x00 -> 1 1111111 0 0000000 0 0000000 -> 00001 1111 1100 0000 0000 0000 -> 0x1F 0xC0 0x00 -> 2080768

	if($request["DeviceType"] != "SAMSUNGGTS6802")
		{
		}
	elseif(preg_match("/(.*\x50\xC3.?)(\x44\x61\x74\x65\x3A\x20.*)/", $request["wbxml"], $matches) == 1)
		$request["wbxml"] = $matches[1] . "\x00" . $matches[2];

	return($request);
	}

function active_sync_handle_settings($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Settings");

	$response->x_open("Settings");

		if(isset($xml->Oof))
			{
			$status = 2; # Protocol error.

			if(isset($xml->Oof->Get))
				$status = 1; # Success.

			if(isset($xml->Oof->Set))
				$status = 1; # Success.

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($status == 1)
				{
				$status = 2; # Protocol error.

				$response->x_open("Oof");

					$response->x_open("Status");
						$response->x_print(1); # Success.
					$response->x_close("Status");

					if(isset($xml->Oof->Get))
						{
						$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

						$body_type = strval($xml->Oof->Get->BodyType);

						$response->x_open("Get");

							if(isset($settings["OOF"]))
								{
								foreach(array("OofState", "StartTime", "EndTime") as $token)
									{
									if(isset($settings["OOF"][$token]) === false)
										continue;

									$response->x_open($token);
										$response->x_print($settings["OOF"][$token]);
									$response->x_close($token);
									}
								}

							if(isset($settings["OOF"]["OofMessage"]))
								{
								foreach($settings["OOF"]["OofMessage"] as $oof_message)
									{
									$response->x_open("OofMessage");

										foreach($oof_message as $token => $value)
											{
											if(strlen($value) == 0)
												{
												$response->x_open($token, false);

												continue;
												}

											$response->x_open($token);
												$response->x_print($value);
											$response->x_close($token);
											}

									$response->x_close("OofMessage");
									}
								}

						$response->x_close("Get");
						}

					if(isset($xml->Oof->Set))
						{
						$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

						$settings["OOF"] = array();

						foreach(array("OofState", "StartTime", "EndTime") as $token)
							{
							if(isset($xml->Oof->Set->$token) === false)
								continue;

							$settings["OOF"][$token] = strval($xml->Oof->Set->$token);
							}

						if(isset($xml->Oof->Set->OofMessage))
							{
							$settings["OOF"]["OofMessage"] = array();

							foreach($xml->Oof->Set->OofMessage as $oof_message)
								{
								$data = array();

								foreach(array("AppliesToInternal", "AppliesToExternalKnown", "AppliesToExternalUnknown", "Enabled", "ReplyMessage", "BodyType") as $token)
									{
									if(isset($oof_message->$token) === false)
										continue;

									$data[$token] = strval($oof_message->$token);
									}

								$settings["OOF"]["OofMessage"][] = $data;
								}
							}

						active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync", $settings);
						}

				$response->x_close("Oof");
				}
			}

		if(isset($xml->DevicePassword))
			{
			if(isset($xml->DevicePassword->Set))
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($status == 1)
				{
				if(isset($xml->DevicePassword->Set->Password) === false)
					$status = 2; # Protocol error.
				elseif(strlen(strval($xml->DevicePassword->Set->Password)) == 0)
					$status = 2; # Protocol error.
				else
					$status = 1; # Success.

				if($status == 1)
					{
					$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

					$settings["DevicePassword"] = strval($xml->DevicePassword->Set->Password);

					active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings);
					}

				$response->x_open("DevicePassword");
					$response->x_open("Status");
						$response->x_print($status);
					$response->x_close("Status");
				$response->x_close("DevicePassword");
				}
			}

		if(isset($xml->DeviceInformation))
			{
			if(isset($xml->DeviceInformation->Set))
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($status == 1)
				{
				$status = 2; # Protocol error.

				$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

				foreach(active_sync_get_default_info() as $token => $value)
					{
					if(isset($xml->DeviceInformation->Set->$token) === false)
						continue;

					$settings["DeviceInformation"][$token] = strval($xml->DeviceInformation->Set->$token);

					$status = 1; # Success.
					}

				if($status == 1)
					{
					active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings);
					}

				$response->x_open("DeviceInformation");
					$response->x_open("Status");
						$response->x_print($status);
					$response->x_close("Status");
				$response->x_close("DeviceInformation");
				}
			}

		if(isset($xml->UserInformation))
			{
			if(isset($xml->UserInformation->Get))
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($status == 1)
				{
				$status = 2; # Protocol error.

				$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

				$response->x_open("UserInformation");

					$response->x_open("Status");
						$response->x_print(1); # Success.
					$response->x_close("Status");

					$response->x_open("Get");
						$response->x_open("EmailAddresses");

							foreach(array("SmtpAddress" => "SmtpAddress") as $token => $value)
								{
								$response->x_open($token);
									$response->x_print($value);
								$response->x_close($token);
								}

						$response->x_close("EmailAddresses");
					$response->x_close("Get");

				$response->x_close("UserInformation");
				}
			}

		if(isset($xml->RightsManagementInformation))
			{
			if(isset($xml->RightsManagementInformation->Get))
				$status = 1; # Success.
			else
				$status = 2; # Protocol error.

			$response->x_open("Status");
				$response->x_print($status);
			$response->x_close("Status");

			if($status == 1)
				{
				$settings = active_sync_get_settings(DAT_DIR . "/login.data");

				if(isset($settings["RightsManagementTemplates"]) === false)
					$status = 168;
				else
					$status = 1; # Protocol error.

				$response->x_open("RightsManagementInformation");

					$response->x_open("Status");
						$response->x_print($status);
					$response->x_close("Status");

					$response->x_open("Get");

						$response->x_switch("RightsManagement");

						if($status == 1)
							{
							$response->x_open("RightsManagementTemplates");

								foreach($settings["RightsManagementTemplates"] as $template)
									{
									$response->x_open("RightsManagementTemplate");

										foreach(array("TemplateID", "TemplateName", "TemplateDescription") as $token)
											{
											$response->x_open($token);
												$response->x_print($template[$token]);
											$response->x_close($token);
											}

									$response->x_close("RightsManagementTemplate");
									}

             						$response->x_close("RightsManagementTemplates");
							}
						else
							{
							$response->x_open("RightsManagementTemplates", false);
							}

					$response->x_close("Get");

				$response->x_close("RightsManagementInformation");
				}
			}

	$response->x_close("Settings");

	return($response->response);
	}

function active_sync_handle_smart_forward($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$mime = strval($xml->Mime);

	if(isset($xml->SaveInSentItems))
		$save_in_sent_items = "T";
	else
		$save_in_sent_items = "F";

	$response = new active_sync_wbxml_response();

	$response->x_switch("ComposeMail");

	$response->x_open("SmartForward");

		$response->x_open("Status");
			$response->x_print(1);
		$response->x_close("Status");

	$response->x_close("SmartForward");

	if(isset($xml->Source->FolderId))
		if(isset($xml->Source->ItemId))
			{
			$collection_id =  strval($xml->Source->FolderId);
			$server_id = strval($xml->Source->ItemId);

			$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

			$data["Email2"]["LastVerbExecuted"] = 3; # 1 REPLYTOSENDER | 2 REPLYTOALL | 3 FORWARD
			$data["Email2"]["LastVerbExecutionTime"] = date("Y-m-d\TH:i:s\Z");

			active_sync_put_settings_data($request["AuthUser"], $collection_id, $server_id, $data);
			}

	if($save_in_sent_items == "T")
		{
		$collection_id = active_sync_get_collection_id_by_type($request["AuthUser"], 5);

		$server_id = active_sync_create_guid_filename($request["AuthUser"], $collection_id);

		$data = active_sync_mail_parse($request["AuthUser"], $collection_id, $server_id, $mime);

		$data["Email"]["Read"] = 1;

		active_sync_put_settings_data($request["AuthUser"], $collection_id, $server_id, $data);
		}

	active_sync_send_mail($request["AuthUser"], $mime);

	return("");
#	return($response->response);
	}

function active_sync_handle_smart_reply($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$mime = strval($xml->Mime);

	if(isset($xml->SaveInSentItems))
		$save_in_sent_items = "T";
	else
		$save_in_sent_items = "F";

	$response = new active_sync_wbxml_response();

	$response->x_switch("ComposeMail");

	$response->x_open("SmartReply");

		$response->x_open("Status");
			$response->x_print(1);
		$response->x_close("Status");

	$response->x_close("SmartReply");

	if(isset($xml->Source->FolderId))
		if(isset($xml->Source->ItemId))
			{
			$collection_id =  strval($xml->Source->FolderId);
			$server_id = strval($xml->Source->ItemId);

			$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

			$data["Email2"]["LastVerbExecuted"] = 1; # 1 REPLYTOSENDER | 2 REPLYTOALL | 3 FORWARD
			$data["Email2"]["LastVerbExecutionTime"] = date("Y-m-d\TH:i:s\Z");

			active_sync_put_settings_data($request["AuthUser"], $collection_id, $server_id, $data);
			}

	if($save_in_sent_items == "T")
		{
		$collection_id = active_sync_get_collection_id_by_type($request["AuthUser"], 5);

		$server_id = active_sync_create_guid_filename($request["AuthUser"], $collection_id);

		$data = active_sync_mail_parse($request["AuthUser"], $collection_id, $server_id, $mime);

		$data["Email"]["Read"] = 1;

		active_sync_put_settings_data($request["AuthUser"], $collection_id, $server_id, $data);
		}

	active_sync_send_mail($request["AuthUser"], $mime);

	return("");
#	return($response->response);
	}

function active_sync_handle_sync($request)
	{
	########################################################################
	# get settings
	########################################################################

	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	################################################################################
	# MS-ASCMD - 4.5.10 Empty Sync Request and Response
	################################################################################

	if($request["wbxml"] == null)
		$request["wbxml"] = base64_decode($settings["Sync"]);
	else
		$settings["Sync"] = base64_encode($request["wbxml"]);

	########################################################################
	# save settings
	########################################################################

	active_sync_put_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync", $settings);

	########################################################################
	# parse request
	# do not use it here anymore, check request earlier
	########################################################################

	$xml = active_sync_wbxml_request_parse_a($request["wbxml"]);

	################################################################################
	# check HeartbeatInterval
	################################################################################

	if(isset($xml->HeartbeatInterval) === false)
		{
		$heartbeat_interval = 0;
		$limit = 0;
		}
	elseif(intval($xml->HeartbeatInterval) < 60) # 1 minute
		{
		$heartbeat_interval = intval($xml->HeartbeatInterval);
		$limit = 60;
		}
	elseif(intval($xml->HeartbeatInterval) > 3540) # 59 minutes
		{
		$heartbeat_interval = intval($xml->HeartbeatInterval);
		$limit = 3540;
		}
	else
		{
		$heartbeat_interval = intval($xml->HeartbeatInterval);
		$limit = 0;
		}

	# S3 increase 470 by 180 until 3530 until reconnect

	################################################################################
	# check Wait
	################################################################################

	if(isset($xml->Wait) === false)
		{
		$wait = 0;
		$limit = 0;
		}
	elseif(intval($xml->Wait) < 1) # 1 minutes
		{
		$wait = intval($xml->Wait);
		$limit = 1;
		}
	elseif(intval($xml->Wait) > 59) # 59 minutes
		{
		$wait = intval($xml->Wait);
		$limit = 59;
		}
	else
		{
		$wait = intval($xml->Wait);
		$limit = 0;
		}

	################################################################################
	# check WindowSize (global)
	################################################################################

	if(isset($xml->WindowSize) === false)
		$window_size_global = 100;
	elseif(intval($xml->WindowSize) == 0)
		$window_size_global = 512;
	elseif(intval($xml->WindowSize) > 512)
		$window_size_global = 512;
	else
		$window_size_global = intval($xml->WindowSize);

	################################################################################
	# check if Collections exist
	################################################################################

	if(isset($xml->Collections) === false)
		$status = 4; # Protocol error.
	else
		$status = 1; # Success.

	################################################################################
	# check if HeartbeatInterval and Wait exist
	################################################################################

	if($status == 1)
		if((($wait * 60) != 0) && (($heartbeat_interval * 1) != 0))
			$status = 4; # Protocol error.

	################################################################################
	# check Wait
	################################################################################

	if($status == 1)
		if(($limit != 0) && (($wait * 60) != 0))
			$status = 14; # Invalid Wait or HeartbeatInterval value.

	################################################################################
	# check HeartbeatInterval
	################################################################################

	if($status == 1)
		if(($limit != 0) && (($heartbeat_interval * 1) != 0))
			$status = 14; # Invalid Wait or HeartbeatInterval value.

	################################################################################
	# check RemoteWipe
	################################################################################

	if($status == 1)
		if(active_sync_get_need_wipe($request) != 0)
			$status = 12; # The folder hierarchy has changed.

	################################################################################
	# check Provision
	################################################################################

	if($status == 1)
		if(active_sync_get_need_provision($request) != 0)
			$status = 12; # The folder hierarchy has changed.

	################################################################################
	# check FolderSync
	################################################################################

	if($status == 1)
		if(active_sync_get_need_folder_sync($request) != 0)
			$status = 12; # The folder hierarchy has changed.

	################################################################################
	# create response
	################################################################################

	$response = new active_sync_wbxml_response();

	$response->x_switch("AirSync");

	$response->x_open("Sync");

		################################################################################
		# return global Status
		################################################################################

		foreach(($status == 1 ? array() : ($status == 14 ? array("Status" => $status, "Limit" => $limit) : array("Status" => $status))) as $token => $value)
			{
			$response->x_open($token);
				$response->x_print($value);
			$response->x_close($token);
			}

		################################################################################
		# continue process if no error is found (global)
		################################################################################

		if($status == 1)
			{
			################################################################################
			# process can be continued (global)
			################################################################################

			$timeout = microtime(true);

			################################################################################
			# init marker for changed Collections
			################################################################################

			$changed_collections = array("*" => 0);
			$synckey_checked = array();

			foreach($xml->Collections->Collection as $collection)
				{
				$sync_key	= strval($collection->SyncKey);
				$collection_id	= strval($collection->CollectionId);

				$changed_collections[$collection_id] = 0;
				$synckey_checked[$collection_id] = 0;
				}

			################################################################################

			$response->x_open("Collections");

				while(1)
					{
					foreach($xml->Collections->Collection as $collection)
						{
						$sync_key	= strval($collection->SyncKey);
						$collection_id	= strval($collection->CollectionId);

						################################################################################
						# get SyncState of CollectionId
						################################################################################

						$settings_client = active_sync_get_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"]);

						################################################################################
						# get SyncState of CollectionId (SyncKey, SyncDat)
						################################################################################

						$settings_server = active_sync_get_settings_sync($request["AuthUser"], $collection_id, "");

						################################################################################
						# get default Class of CollectionId
						################################################################################

						$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

						################################################################################
						# check GetChanges
						################################################################################

						# MS-ASCMD - 2.2.3.79 GetChanges

						# !!! read info about GetChanges and SyncKey !!!

						# if SyncKey == 0 then absence of GetChanges == 0
						# if SyncKey != 0 then absence of GetChanges == 1
						# if GetChanges is empty then a value of 1 is assumed in any case

						if(isset($collection->GetChanges) === false)
							$get_changes = ($sync_key == 0 ? 0 : 1);
						elseif(strval($collection->GetChanges) == "")
							$get_changes = 1;
						else
							$get_changes = intval($collection->GetChanges);

						################################################################################
						# check WindowsSize (collection)
						################################################################################

						if(isset($collection->WindowSize) === false)
							$window_size = 100;
						elseif(intval($collection->WindowSize) == 0)
							$window_size = 512;
						elseif(intval($collection->WindowSize) > 512)
							$window_size = 512;
						else
							$window_size = intval($collection->WindowSize);

						################################################################################
						# check SyncKey
						################################################################################

						if($synckey_checked[$collection_id] == 1)
							{
							if($settings_client["SyncKey"] == 0)
								{
								$settings_client["SyncKey"] = 0;

								$status = 3; # Invalid synchronization key.
								}
							else
								{
								$settings_client["SyncKey"] ++;

								$status = 1; # Success.
								}
							}
						else
							{
							if($sync_key == 0)
								{
								$settings_client["SyncKey"] = 1;
								$settings_client["SyncDat"] = array();

								$status = 1; # Success.
								}
							elseif($sync_key != $settings_client["SyncKey"])
								{
								$settings_client["SyncKey"] = 0;
								$settings_client["SyncDat"] = array();

								$status = 3; # Invalid synchronization key.
								}
							else
								{
								$settings_client["SyncKey"] ++;

								$status = 1; # Success.
								}

							$synckey_checked[$collection_id] = 1;
							}

						################################################################################
						# continue process if no error is found (collection)
						################################################################################

						if($sync_key == 0)
							{
							################################################################################
							# process can not be continued (collection)
							################################################################################

							$response->x_switch("AirSync");

							$response->x_open("Collection");

								foreach(array("SyncKey" => $settings_client["SyncKey"], "CollectionId" => $collection_id, "Status" => $status) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

							$response->x_close("Collection");

							################################################################################
							# mark CollectionId as changed
							################################################################################

							$changed_collections[$collection_id] = 1;
							}
						elseif($status == 1)
							{
							################################################################################
							# check for elements sended by device
							################################################################################

							if(isset($collection->Commands))
								{
								$response->x_switch("AirSync");

								$response->x_open("Collection");

									foreach(array("SyncKey" => $settings_client["SyncKey"], "CollectionId" => $collection_id, "Status" => $status) as $token => $value)
										{
										$response->x_open($token);
											$response->x_print($value);
										$response->x_close($token);
										}

									$response->x_switch("AirSync");

									$response->x_open("Responses");

										################################################################################
										# handle request for Add
										################################################################################

										foreach($collection->Commands->Add as $add)
											{
											$client_id = strval($add->ClientId);

											$server_id = active_sync_create_guid_filename($request["AuthUser"], $collection_id);

											$response->x_switch("AirSync");

											$response->x_open("Add");

												if($server_id == 0)
													$status = 5; # Server error.
												elseif($default_class == "")
													$status = 5; # Server error.
												elseif(function_exists("active_sync_handle_sync_save_" . strtolower($default_class)) === false)
													$status = 5; # Server error.

												else
													{
													$function = "active_sync_handle_sync_save_" . strtolower($default_class);

													$status = $function($add, $request["AuthUser"], $collection_id, $server_id);
													}

												if($status == 1)
													{
													$settings_client["SyncDat"][$server_id] = filemtime(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data");

													$response->x_open("ServerId");
														$response->x_print($server_id);
													$response->x_close("ServerId");
													}

												foreach(array("ClientId" => $client_id, "Status" => $status) as $token => $value)
													{
													$response->x_open($token);
														$response->x_print($value);
													$response->x_close($token);
													}

											$response->x_close("Add");
											}

										################################################################################
										# handle request for Change
										################################################################################

										foreach($collection->Commands->Change as $change)
											{
											$server_id = strval($change->ServerId);

											$response->x_switch("AirSync");

											$response->x_open("Change");

active_sync_debug("s: " . $settings_server["SyncDat"][$server_id]);
active_sync_debug("c: " . $settings_client["SyncDat"][$server_id]);
active_sync_debug("x: " . filemtime(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data"));

												if(isset($settings_client["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(isset($settings_server["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(function_exists("active_sync_handle_sync_save_" . strtolower($default_class)) === false)
													$status = 5; # Server error.
												else
													{
													$function = "active_sync_handle_sync_save_" . strtolower($default_class);

													$status = $function($change, $request["AuthUser"], $collection_id, $server_id);
													}

												if($status == 1)
													$settings_client["SyncDat"][$server_id] = filemtime(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data");

												foreach(array("ServerId" => $server_id, "Status" => $status) as $token => $value)
													{
													$response->x_open($token);
														$response->x_print($value);
													$response->x_close($token);
													}

active_sync_debug("s: " . $settings_server["SyncDat"][$server_id]);
active_sync_debug("c: " . $settings_client["SyncDat"][$server_id]);
active_sync_debug("x: " . filemtime(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data"));

											$response->x_close("Change");
											}

										################################################################################
										# handle request for Delete
										################################################################################

										foreach($collection->Commands->Delete as $delete)
											{
											$server_id = strval($delete->ServerId);

											$response->x_switch("AirSync");

											$response->x_open("Delete");

												if(isset($settings_client["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(isset($settings_server["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(file_exists(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data") === false)
													$status = 8; # Object not found.
												else
													{
													################################################################################
													# set status
													################################################################################
													
													$status = 1; # Success;

													################################################################################
													# get data from file
													################################################################################
													
													$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);
													
													################################################################################
													# check for Attachments
													################################################################################
													
													if(isset($data["Attachments"]))
														{
														################################################################################
														# check each Attachment
														################################################################################
													
														foreach($data["Attachments"] as $attachment)
															{
															################################################################################
															# skip if FileReference do not exist
															################################################################################
													
															if(isset($attachment["AirSyncBase"]["FileReference"]) === false)
																{
																$status = 8; # Object not found.
													
																break;
																}
													
															################################################################################
															# skip if file given by FileReference do not exist
															################################################################################
													
															if(file_exists(ATT_DIR . "/" . $attachment["AirSyncBase"]["FileReference"]) === false)
																{
																$status = 8; # Object not found.
													
																break;
																}
													
															################################################################################
															# skip if file given by FileReference can not be deleted
															################################################################################
													
															if(unlink(ATT_DIR . "/" . $attachment["AirSyncBase"]["FileReference"]) === false)
																{
																$status = 5; # Server error.
													
																break;
																}
															}
														}
													
													################################################################################
													# skip if file given by CollectionId and ServerId can not be deleted
													################################################################################
													
													if(unlink(DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data") === false)
														$status = 5; # Server error.
													}

												if($status == 1)
													{
													unset($settings_client["SyncDat"][$server_id]);
													}

												foreach(array("ServerId" => $server_id, "Status" => $status) as $token => $value)
													{
													$response->x_open($token);
														$response->x_print($value);
													$response->x_close($token);
													}

											$response->x_close("Delete");
											}

										################################################################################
										# handle request for Fetch
										################################################################################

										foreach($collection->Commands->Fetch as $fetch)
											{
											$server_id = strval($fetch->ServerId);

											$response->x_switch("AirSync");

											$response->x_open("Fetch");

												if(isset($settings_client["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(isset($settings_server["SyncDat"][$server_id]) === false)
													$status = 8; # Object not found.
												elseif(function_exists("active_sync_handle_sync_send_" . strtolower($default_class)) === false)
													$status = 5; # Server error.
												else
													{
													$status = 1; # Success.

													$function = "active_sync_handle_sync_send_" . strtolower($default_class);

													$function($response, $request["AuthUser"], $collection_id, $server_id, $collection);
													}

												# wrong order? correct order: ServerId, Status, ApplicationData
												# wrong order? correct order: ApplicationData, ServerId, Status

												foreach(array("ServerId" => $server_id, "Status" => $status) as $token => $value)
													{
													$response->x_open($token);
														$response->x_print($value);
													$response->x_close($token);
													}

											$response->x_close("Fetch");
											}

									$response->x_close("Responses");

								$response->x_close("Collection");

								################################################################################
								# mark CollectionId as changed
								################################################################################

								$changed_collections[$collection_id] = 1;
								} # if(isset($collection->Commands))

							################################################################################
							# get the changes
							# !!! read info about GetChanges and SyncKey !!!
							################################################################################

							if($get_changes == 1)
								{
								################################################################################
								# init jobs
								################################################################################

								$jobs = array();

								################################################################################
								# get SyncState of CollectionId (SyncKey, SyncDat)
								# get it once again, maybe some data has been written
								################################################################################

								$settings_server = active_sync_get_settings_sync($request["AuthUser"], $collection_id, "");

								################################################################################
								# check each file the server got
								################################################################################

								foreach($settings_server["SyncDat"] as $server_id => $server_timestamp)
									{
									################################################################################
									# get content of file
									################################################################################

									$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

									################################################################################
									# check class of file
									################################################################################

									if(isset($data["AirSync"]["Class"]) === false)
										$data["AirSync"]["Class"] = $default_class;

									################################################################################
									# check options
									# inbox contains email and sms. FilterType can differ. find the right one
									################################################################################

									$option_class = $default_class;
									$option_filter_type = 0;
									$process_sms = 1; # imagine we have sms

									if(isset($collection->Options))
										{
										foreach($collection->Options as $options)
											{
											################################################################################
											# check Class of Option
											################################################################################

											if(isset($options->Class) === false)
												$option_class = $default_class;
											else
												$option_class = strval($options->Class); # only occurs on email/sms

											################################################################################
											# skip if class of option do not match class of data
											################################################################################

											if($option_class != $data["AirSync"]["Class"])
												continue;

											################################################################################
											# check FilterType of Option
											################################################################################

											if(isset($options->FilterType) === false)
												$option_filter_type = 0;
											else
												$option_filter_type = intval($options->FilterType);

											################################################################################
											# mark Class as found in Option
											# SMS never got an option
											# what is going on here?
											################################################################################

											$process_sms = 0;
											}
										}

									################################################################################
									# sync SMS
									################################################################################

									if($process_sms == 1)
										{
										if(isset($settings_client["SyncDat"][$server_id]) === false)
											$settings_client["SyncDat"][$server_id] = "*";
										elseif($settings_client["SyncDat"][$server_id] == "*")
											{
											# file is known as SoftDelete
											}
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											$jobs["SoftDelete"][] = $server_id;
										else
											$jobs["SoftDelete"][] = $server_id;

										################################################################################
										# 0 (all), 1 - 7, 8 (incomplete), ... so ... LIE
										################################################################################

										$option_filter_type = 9;
										}

									################################################################################
									# sync all
									################################################################################

									if($option_filter_type == 0)
										{
										if(isset($settings_client["SyncDat"][$server_id]) === false)
											$jobs["Add"][] = $server_id;
										elseif($settings_client["SyncDat"][$server_id] == "*")
											$jobs["Add"][] = $server_id;
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											$jobs["Change"][] = $server_id;
										}

									################################################################################
									# sync ...
									################################################################################

									if(($option_filter_type > 0) && ($option_filter_type < 8))
										{
										$stat_filter = array("now", "- 1 day", "- 3 day", "- 1 week", "- 2 week", "- 1 month", "- 3 month", "- 6 month", "now");

										$stat_filter = strtotime($stat_filter[$option_filter_type]);

										###########################################################################################
										# does FilterType only occur on Email/SMS as DateReceived ?

										if($default_class == "Calendar")
											$data_timestamp = strtotime($data["Calendar"]["EndTime"]);

										if($default_class == "Email")
											$data_timestamp = strtotime($data["Email"]["DateReceived"]);

										if($default_class == "Notes")
											$data_timestamp = strtotime($data["Notes"]["LastModifiedDate"]);

										if($default_class == "Tasks")
											$data_timestamp = strtotime($data["Tasks"]["DateCompleted"]);

										###########################################################################################

										if(isset($settings_client["SyncDat"][$server_id]) === false)
											{
											# file was not sent to client before

											if($data_timestamp < $stat_filter)
												$settings_client["SyncDat"][$server_id] = "*";
											else
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] == "*")
											{
											# file is known as SoftDelete

											if($data_timestamp < $stat_filter)
												{
												#
												}
											else
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											{
											# file changed since last sync

											if($data_timestamp < $stat_filter)
												$jobs["SoftDelete"][] = $server_id;
											else
												$jobs["Change"][] = $server_id;
											}
										else
											{
											# file is up to date since last sync

											if($data_timestamp < $stat_filter)
												$jobs["SoftDelete"][] = $server_id;
											}
										}

									###########################################################################################
									# sync incomplete (tasks only)
									###########################################################################################

									if($option_filter_type == 8)
										{
										if(isset($settings_client["SyncDat"][$server_id]) === false)
											{
											# file was not sent to client before

											if($data["Tasks"]["Complete"] == 1)
												$settings_client["SyncDat"][$server_id] = "*";
											else
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] == "*")
											{
											# file is known as SoftDelete

											if($data["Tasks"]["Complete"] == 1)
												{
												#
												}
											else
												{
												$jobs["Add"][] = $server_id;
												}
											}
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											{
											# file changed since last sync

											if($data["Tasks"]["Complete"] == 1)
												$jobs["SoftDelete"][] = $server_id;
											else
												$jobs["Change"][] = $server_id;
											}
										else
											{
											# file is up to date since last sync
											}
										}
									}

								################################################################################
								# check for to Delete
								################################################################################

								foreach($settings_client["SyncDat"] as $server_id => $client_timestamp)
									{
									################################################################################
									# skip if ServerId exist
									################################################################################

									if(isset($settings_server["SyncDat"][$server_id]))
										continue;

									################################################################################
									# add ServerId to list of deleted elements
									################################################################################

									$jobs["Delete"][] = $server_id;
									}

								################################################################################
								# check for elements sended by server
								################################################################################

								if(count($jobs) > 0)
									{
									$response->x_switch("AirSync");

									$response->x_open("Collection");

										foreach(array("SyncKey" => $settings_client["SyncKey"], "CollectionId" => $collection_id, "Status" => $status) as $token => $value)
											{
											$response->x_open($token);
												$response->x_print($value);
											$response->x_close($token);
											}

										################################################################################
										# create a response for changed elements
										################################################################################

										$response->x_switch("AirSync");

										$response->x_open("Commands");

											################################################################################
											# init counter of changed elements
											################################################################################

											$estimate = 0;

											################################################################################
											# output for Add/Change
											################################################################################

											foreach(array("Add", "Change") as $command)
												{
												################################################################################
												# skip if no ServerId in list
												################################################################################

												if(isset($jobs[$command]) === false)
													continue;

												################################################################################
												# list all elements
												################################################################################

												foreach($jobs[$command] as $server_id)
													{
													################################################################################
													# exit if WindowSize is reached
													# count($jobs[$command]) contains list of elements to change
													################################################################################

													if($estimate == $window_size)
														break;

													################################################################################
													# increase counter of added/changed elements
													################################################################################

													$estimate ++;

													################################################################################
													# update timestamp of ServerId in SyncState
													################################################################################

													$settings_client["SyncDat"][$server_id] = $settings_server["SyncDat"][$server_id];

													################################################################################
													# output of Added/Changed ServerId and ApplicationData
													################################################################################

													$response->x_switch("AirSync");

													$response->x_open($command);
														$response->x_open("ServerId");
															$response->x_print($server_id);
														$response->x_close("ServerId");

														if($default_class == "")
															{
															}
														elseif(function_exists("active_sync_handle_sync_send_" . strtolower($default_class)))
															{
															$function = "active_sync_handle_sync_send_" . strtolower($default_class);

															$function($response, $request["AuthUser"], $collection_id, $server_id, $collection);
															}

													$response->x_close($command);
													}
												}

											################################################################################
											# output for Delete/SoftDelete
											################################################################################

											foreach(array("Delete", "SoftDelete") as $command)
												{
												################################################################################
												# skip if no ServerId in list
												################################################################################

												if(isset($jobs[$command]) === false)
													continue;

												################################################################################
												# list all elements
												################################################################################

												foreach($jobs[$command] as $server_id)
													{
													################################################################################
													# exit if ServerId is reached
													################################################################################

													if($estimate == $window_size)
														break;

													################################################################################
													# increase counter of changed elements
													################################################################################

													$estimate ++;

													################################################################################
													# remove element from SyncState
													################################################################################

													if($command == "Delete")
														{
														unset($settings_server["SyncDat"][$server_id]);
														unset($settings_client["SyncDat"][$server_id]);
														}

													################################################################################
													# mark element in SyncState as SoftDelete
													################################################################################

													if($command == "SoftDelete")
														{
														$settings_server["SyncDat"][$server_id] = "*";
														$settings_client["SyncDat"][$server_id] = "*";
														}

													################################################################################
													# output of Deleted/SoftDeleted ServerId
													################################################################################

													$response->x_switch("AirSync");

													$response->x_open($command);
														$response->x_open("ServerId");
															$response->x_print($server_id);
														$response->x_close("ServerId");
													$response->x_close($command);
													}
												}

										$response->x_close("Commands");

										################################################################################
										# init counter of changed elements
										################################################################################

										$estimate = 0;

										################################################################################
										# get total number of changed elements
										################################################################################

										foreach(array("Add", "Change", "Delete", "SoftDelete") as $command)
											{
											################################################################################
											# skip if no elements in list
											################################################################################

											if(isset($jobs[$command]) === false)
												continue;

											################################################################################
											# increase counter
											################################################################################

											$estimate = $estimate + count($jobs[$command]);
											}

										################################################################################
										# check if we got more changes than requested by WindowSize
										################################################################################

										if($estimate > $window_size)
											{
											$response->x_switch("AirSync");

											$response->x_open("MoreAvailable", false);
											}

									$response->x_close("Collection");

									################################################################################
									# mark CollectionId as changed
									################################################################################

									$changed_collections[$collection_id] = 1;
									} # if(count($jobs) > 0)
								} # if($get_changes == 0)
							} # elseif($status == 1)
						elseif($status == 3)
							{
							################################################################################
							# process can not be continued (collection)
							################################################################################

							$response->x_switch("AirSync");

							$response->x_open("Collection");

								foreach(array("SyncKey" => $settings_client["SyncKey"], "CollectionId" => $collection_id, "Status" => $status) as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

							$response->x_close("Collection");

							################################################################################
							# mark CollectionId as changed
							################################################################################

							$changed_collections[$collection_id] = 1;
							}

						################################################################################
						# continue if no changes detected
						################################################################################

						if($changed_collections[$collection_id] == 0)
							continue;

						################################################################################
						# store SyncState for CollectionId
						################################################################################

						active_sync_put_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"], $settings_client);

						################################################################################
						# mark collections as changed
						# empty response impossible now
						################################################################################

						$changed_collections["*"] = 1;
						} # foreach($xml->Collections->Collection as $collection)

					################################################################################
					# exit if changes were detected
					################################################################################

					if($changed_collections["*"] != 0)
						break;

					if((($wait * 60) != 0) && (($heartbeat_interval * 1) != 0))
						break;

					if((($wait * 60) == 0) && (($heartbeat_interval * 1) == 0))
						break;

					if((($wait * 60) != 0) && ($timeout + ($wait * 60) < microtime(true)))
						break;

					if((($heartbeat_interval * 1) != 0) && ($timeout + ($heartbeat_interval * 1) < microtime(true)))
						break;

					sleep(10);

					clearstatcache();
					} # while(1)

				################################################################################
				# return empty response if no changes at all.
				# this will also prevent invalid sync key ... gotcha
				# this saves a lot debug data
				################################################################################

				if($changed_collections["*"] == 0)
					return("");

				foreach($xml->Collections->Collection as $collection)
					{
					$sync_key	= strval($collection->SyncKey);
					$collection_id	= strval($collection->CollectionId);

					if($changed_collections[$collection_id] != 0)
						continue;

					$settings = active_sync_get_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"]);

					$settings["SyncKey"] ++;

					active_sync_put_settings_sync($request["AuthUser"], $collection_id, $request["DeviceId"], $settings);

					$response->x_switch("AirSync");

					$response->x_open("Collection");

						foreach(array("SyncKey" => $settings["SyncKey"], "CollectionId" => $collection_id, "Status" => 1) as $token => $value)
							{
							$response->x_open($token);
								$response->x_print($value);
							$response->x_close($token);
							}

					$response->x_close("Collection");
					}

			$response->x_close("Collections");
			} # if($status == 1)

	$response->x_close("Sync");

	return($response->response);
	}

function active_sync_handle_sync_save($xml, $user, $collection_id, $server_id, $class)
	{
	if($class == "Email")
		$data = active_sync_get_settings_data($user, $collection_id, $server_id);
	else
		$data = array();

	$codepage_table = array();

	if($class == "Contact")
		{
		$codepage_table["Contacts"] = active_sync_get_default_contacts();
		$codepage_table["Contacts2"] = active_sync_get_default_contacts2();
		}

	if($class == "Calendar")
		$codepage_table["Calendar"] = active_sync_get_default_calendar();

	if($class == "Email")
		{
		$codepage_table["Email"] = active_sync_get_default_email();
		$codepage_table["Email2"] = active_sync_get_default_email2();
		}

	if($class == "Notes")
		$codepage_table["Notes"] = active_sync_get_default_notes();

	if($class == "Tasks")
		$codepage_table["Tasks"] = active_sync_get_default_tasks();

	foreach($codepage_table as $codepage => $null)
		foreach($codepage_table[$codepage] as $token => $value)
			{
			if(isset($xml->ApplicationData->$token) === false)
				continue;

			$data[$codepage][$token] = strval($xml->ApplicationData->$token);
			}

	if($class == "Contact")
		{
		if(isset($data["Contacts"]["Picture"]))
			if(strlen($data["Contacts"]["Picture"]) > (48 * 1024))
				return(6); # Error in client/server conversion.
		}

	if($class == "Email")
		{
		foreach(array("Class") as $token)
			{
			if(isset($xml->$token) === false)
				continue;

			$data["AirSync"][$token] = strval($xml->$token);
			}

		foreach(array("UmCallerID", "UmUserNotes") as $token)
			{
			if(isset($xml->ApplicationData->$token) === false)
				continue;

	#		$data["Email2"][$token] = strval($xml->ApplicationData->$token);

	#		$data["Attachments"][]["Email2"][$token] = $data["Email2"][$token];
			}

		if(isset($xml->ApplicationData->Flag))
			{
			$data["Flag"] = array();

			foreach(array("Email", "Tasks") as $codepage)
				{
				foreach(active_sync_get_default_flag($codepage) as $token)
					{
					if(isset($xml->ApplicationData->Flag->$token) === false)
						continue;

					$data["Flag"][$codepage][$token] = strval($xml->ApplicationData->Flag->$token);
					}
				}
			}
		}

	if(isset($xml->ApplicationData->Attendees))
		foreach($xml->ApplicationData->Attendees->Attendee as $attendee)
			{
			$a = array();

			foreach(active_sync_get_default_attendee() as $token => $value)
				{
				if(isset($attendee->$token) === false)
					continue;

				$a[$token] = strval($attendee->$token);
				}

			$data["Attendees"][] = $a;
			}

	if(isset($xml->ApplicationData->Recurrence))
		foreach(active_sync_get_default_recurrence() as $token => $value)
			{
			if(isset($xml->ApplicationData->Recurrence->$token) === false)
				continue;

			$data["Recurrence"][$token] = strval($xml->ApplicationData->Recurrence->$token);
			}

	if(isset($xml->ApplicationData->Body))
		foreach($xml->ApplicationData->Body as $body)
			{
			$b = array();

			foreach(active_sync_get_default_body() as $token => $value)
				{
				if(isset($body->$token) === false)
					continue;

				$b[$token] = strval($body->$token);
				}

			if(isset($b["Data"]) === false)
				continue;

			if(strlen($b["Data"]) == 0)
				continue;

			$data["Body"][] = $b;
			}

	if(isset($xml->ApplicationData->Children))
		if(count($xml->ApplicationData->Children->Child) > 0)
			foreach($xml->ApplicationData->Children->Child as $child)
				$data["Children"][] = strval($child);

	if(isset($xml->ApplicationData->Categories))
		if(count($xml->ApplicationData->Categories->Category) > 0)
			foreach($xml->ApplicationData->Categories->Category as $category)
				$data["Categories"][] = strval($category);

	return(active_sync_put_settings_data($user, $collection_id, $server_id, $data) ? 1 : 5);
	}

function active_sync_handle_sync_save_calendar($xml, $user, $collection_id, $server_id)
	{
	$data = array();

	$codepage_table = array
		(
		"Calendar" => active_sync_get_default_calendar()
		);

	foreach($codepage_table as $codepage => $null)
		foreach($codepage_table[$codepage] as $token => $value)
			{
			if(isset($xml->ApplicationData->$token) === false)
				continue;

			$data[$codepage][$token] = strval($xml->ApplicationData->$token);
			}

	if(isset($xml->ApplicationData->Body))
		foreach($xml->ApplicationData->Body as $body)
			{
			$b = array();

			foreach(active_sync_get_default_body() as $token => $value)
				{
				if(isset($body->$token) === false)
					continue;

				$b[$token] = strval($body->$token);
				}

			if(isset($b["Data"]) === false)
				continue;

			if(strlen($b["Data"]) == 0)
				continue;

			$data["Body"][] = $b;
			}

	if(isset($xml->ApplicationData->Categories))
		if(count($xml->ApplicationData->Categories->Category) > 0)
			foreach($xml->ApplicationData->Categories->Category as $category)
				$data["Categories"][] = strval($category);

	if(isset($xml->ApplicationData->Recurrence))
		foreach(active_sync_get_default_recurrence() as $token => $value)
			{
			if(isset($xml->ApplicationData->Recurrence->$token) === false)
				continue;

			$data["Recurrence"][$token] = strval($xml->ApplicationData->Recurrence->$token);
			}

	if(isset($xml->ApplicationData->Attendees))
		foreach($xml->ApplicationData->Attendees->Attendee as $attendee)
			{
			$a = array();

			foreach(active_sync_get_default_attendee() as $token => $value)
				{
				if(isset($attendee->$token) === false)
					continue;

				$a[$token] = strval($attendee->$token);
				}

			$data["Attendees"][] = $a;
			}

	return(active_sync_put_settings_data($user, $collection_id, $server_id, $data) === false ? 16 : 1);
	}

function active_sync_handle_sync_save_contacts($xml, $user, $collection_id, $server_id)
	{
	$data = array();

	$codepage_table = array
		(
		"Contacts" => active_sync_get_default_contacts(),
		"Contacts2" => active_sync_get_default_contacts2()
		);

	foreach($codepage_table as $codepage => $null)
		foreach($codepage_table[$codepage] as $token => $value)
			{
			if(isset($xml->ApplicationData->$token) === false)
				continue;

			$data[$codepage][$token] = strval($xml->ApplicationData->$token);
			}

	if(isset($xml->ApplicationData->Body))
		foreach($xml->ApplicationData->Body as $body)
			{
			$b = array();

			foreach(active_sync_get_default_body() as $token => $value)
				{
				if(isset($body->$token) === false)
					continue;

				$b[$token] = strval($body->$token);
				}

			if(isset($b["Data"]) === false)
				continue;

			if(strlen($b["Data"]) == 0)
				continue;

			$data["Body"][] = $b;
			}

	if(isset($xml->ApplicationData->Categories))
		if(count($xml->ApplicationData->Categories->Category) > 0)
			foreach($xml->ApplicationData->Categories->Category as $category)
				$data["Categories"][] = strval($category);


	if(isset($xml->ApplicationData->Children))
		if(count($xml->ApplicationData->Children->Child) > 0)
			foreach($xml->ApplicationData->Children->Child as $child)
				$data["Children"][] = strval($child);

	return(active_sync_put_settings_data($user, $collection_id, $server_id, $data) === false ? 16 : 1);
	}

function active_sync_handle_sync_save_email($xml, $user, $collection_id, $server_id)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	foreach(array("Class") as $token)
		{
		if(isset($xml->$token) === false)
			continue;

		$data["AirSync"][$token] = strval($xml->$token);
		}

	$codepage_table = array
		(
		"Email" => active_sync_get_default_email(),
		"Email2" => active_sync_get_default_email2()
		);

	foreach($codepage_table as $codepage => $token_table)
		foreach($token_table as $token => $value)
			{
			if(isset($xml->ApplicationData->$token) === false)
				continue;

			$data[$codepage][$token] = strval($xml->ApplicationData->$token);
			}

	if(isset($xml->ApplicationData->Body))
		foreach($xml->ApplicationData->Body as $body)
			{
			$b = array();

			foreach(active_sync_get_default_body() as $token => $value)
				{
				if(isset($body->$token) === false)
					continue;

				$b[$token] = strval($body->$token);
				}

			if(isset($b["Data"]) === false)
				continue;

			if(strlen($b["Data"]) == 0)
				continue;

			$data["Body"][] = $b;
			}

	if(isset($xml->ApplicationData->Categories))
		if(count($xml->ApplicationData->Categories->Category) > 0)
			foreach($xml->ApplicationData->Categories->Category as $category)
				$data["Categories"][] = strval($category);

#	$data["Email"]["Read"] = 1;

	# fixme: some fields are part of attachment !!!

	foreach(array("UmCallerID", "UmUserNotes") as $token)
		{
		if(isset($xml->ApplicationData->$token) === false)
			continue;

#		$data["Email2"][$token] = strval($xml->ApplicationData->$token);

#		$data["Attachments"][]["Email2"][$token] = $data["Email2"][$token];
		}

	if(isset($xml->ApplicationData->Flag))
		{
		$data["Flag"] = array();

		foreach(array("Email", "Tasks") as $codepage)
			{
			foreach(active_sync_get_default_flag($codepage) as $token)
				{
				if(isset($xml->ApplicationData->Flag->$token) === false)
					continue;

				$data["Flag"][$codepage][$token] = strval($xml->ApplicationData->Flag->$token);
				}
			}
		}

	return(active_sync_put_settings_data($user, $collection_id, $server_id, $data) === false ? 16 : 1); # Ok. | Server error.
	}

function active_sync_handle_sync_save_notes($xml, $user, $collection_id, $server_id)
	{
	$data = array();

	$codepage_table = array
		(
		"Notes" => active_sync_get_default_notes()
		);

	foreach($codepage_table as $codepage => $null)
		foreach($codepage_table[$codepage] as $token => $value)
			{
			if(isset($xml->ApplicationData->$token) === false)
				continue;

			$data[$codepage][$token] = strval($xml->ApplicationData->$token);
			}

	if(isset($xml->ApplicationData->Body))
		foreach($xml->ApplicationData->Body as $body)
			{
			$b = array();

			foreach(active_sync_get_default_body() as $token => $value)
				{
				if(isset($body->$token) === false)
					continue;

				$b[$token] = strval($body->$token);
				}

			if(isset($b["Data"]) === false)
				continue;

			if(strlen($b["Data"]) == 0)
				continue;

			$data["Body"][] = $b;
			}

	if(isset($xml->ApplicationData->Categories))
		if(count($xml->ApplicationData->Categories->Category) > 0)
			foreach($xml->ApplicationData->Categories->Category as $category)
				$data["Categories"][] = strval($category);

	return(active_sync_put_settings_data($user, $collection_id, $server_id, $data) === false ? 16 : 1);
	}

function active_sync_handle_sync_save_tasks($xml, $user, $collection_id, $server_id)
	{
	$data = array();

	$codepage_table = array
		(
		"Tasks" => active_sync_get_default_tasks()
		);

	foreach($codepage_table as $codepage => $null)
		foreach($codepage_table[$codepage] as $token => $value)
			{
			if(isset($xml->ApplicationData->$token) === false)
				continue;

			$data[$codepage][$token] = strval($xml->ApplicationData->$token);
			}

	if(isset($xml->ApplicationData->Body))
		foreach($xml->ApplicationData->Body as $body)
			{
			$b = array();

			foreach(active_sync_get_default_body() as $token => $value)
				{
				if(isset($body->$token) === false)
					continue;

				$b[$token] = strval($body->$token);
				}

			if(isset($b["Data"]) === false)
				continue;

			if(strlen($b["Data"]) == 0)
				continue;

			$data["Body"][] = $b;
			}

	if(isset($xml->ApplicationData->Categories))
		if(count($xml->ApplicationData->Categories->Category) > 0)
			foreach($xml->ApplicationData->Categories->Category as $category)
				$data["Categories"][] = strval($category);

	if(isset($xml->ApplicationData->Recurrence))
		foreach(active_sync_get_default_recurrence() as $token => $value)
			{
			if(isset($xml->ApplicationData->Recurrence->$token) === false)
				continue;

			$data["Recurrence"][$token] = strval($xml->ApplicationData->Recurrence->$token);
			}

	return(active_sync_put_settings_data($user, $collection_id, $server_id, $data) === false ? 16 : 1);
	}

function active_sync_handle_sync_send(& $response, $user, $collection_id, $server_id, $collection)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	if(isset($data["AirSync"]))
		{
		$response->x_switch("AirSync");

		foreach($data["AirSync"] as $token => $value)
			{
			if(strlen($data["AirSync"][$token]) == 0)
				{
				$response->open($token, false);

				continue;
				}

			$response->x_open($token);
				$response->x_print($data["AirSync"][$token]);
			$response->x_close($token);
			}
		}

	$codepage_table = array
		(
		"AirSyncBase" => array("NativeBodyType" => 0),
		"Calendar" => active_sync_get_default_calendar(),
		"Contacts" => active_sync_get_default_contacts(),
		"Contacts2" => active_sync_get_default_contacts2(),
		"Email" => active_sync_get_default_email(),
		"Email2" => active_sync_get_default_email2(),
		"Notes" => active_sync_get_default_notes(),
		"Tasks" => active_sync_get_default_tasks()
		);

	$response->x_switch("AirSync");

	$response->x_open("ApplicationData");

		foreach($codepage_table as $codepage => $null)
			{
			if(isset($data[$codepage]) === false)
				continue;

			$response->x_switch($codepage);

			foreach($codepage_table[$codepage] as $token => $null)
				{
				if(isset($data[$codepage][$token]) === false)
					continue;

				if(strlen($data[$codepage][$token]) == 0)
					{
					$response->x_open($token, false);

					continue;
					}

				# The ... element is defined as an element in the Calendar namespace.
				# The value of this element is a string data type, represented as a
				# Compact DateTime ([MS-ASDTYPE] section 2.7.2).

				if(in_array($token, array("DtStamp", "StartTime", "EndTime")))
					$data[$codepage][$token] = date("Ymd\THis\Z", strtotime($data[$codepage][$token]));

				# The value of this element is a datetime data type in Coordinated Universal
				# Time (UTC) format, as specified in [MS-ASDTYPE] section 2.3.

				if(in_array($token, array("Aniversary", "Birthday")))
					$data[$codepage][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data[$codepage][$token]));

				# The value of the * element is a string data type represented as a
				# Compact DateTime ([MS-ASDTYPE] section 2.7.2).

				if(in_array($token, array("DateCompleted", "DueDate", "OrdinalDate", "ReminderTime", "Start", "StartDate", "UtcDueDate", "UtcStartDate")))
					$data[$codepage][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data[$codepage][$token]));

				$response->x_open($token);
					$response->x_print($data[$codepage][$token]);
				$response->x_close($token);
				}
			}

		if(isset($data["Attachments"]))
			{
			$response->x_switch("AirSyncBase");

			$response->x_open("Attachments");

				foreach($data["Attachments"] as $id => $null)
					{
					$response->x_switch("AirSyncBase");

					$response->x_open("Attachment");

						foreach(array("Email") as $codepage)
							{
							if(isset($data["Attachments"][$id][$codepage]) === false)
								continue;

							$response->x_switch($codepage);

							foreach($data["Attachments"][$id][$codepage] as $token => $null)
								{
								if(strlen($data["Attachments"][$id][$codepage][$token]) == 0)
									{
									$response->x_open($token, false);

									continue;
									}

								$response->x_open($token);
									$response->x_print($data["Attachments"][$id][$codepage][$token]);
								$response->x_close($token);
								}
							}

					$response->x_close("Attachment");
					}

			$response->x_close("Attachments");
			}

		if(isset($data["Attendees"]))
			{
			$response->x_switch($marker);

			$response->x_open("Attendees");

				foreach($data["Attendees"] as $attendee)
					{
					$response->x_open("Attendee");

						foreach(active_sync_get_default_attendee() as $token => $null)
							{
							if(isset($attendee[$token]) === false)
								continue;

							if(strlen($attendee[$token]) == 0)
								{
								$response->x_open($token, false);

								continue;
								}

							$response->x_open($token);
								$response->x_print($attendee[$token]);
							$response->x_close($token);
							}

					$response->x_close("Attendee");
					}

			$response->x_close("Attendees");
			}

		if(isset($data["Categories"]))
			{
			$response->x_switch($marker);

			$response->x_open("Categories");

				foreach($data["Categories"] as $id => $null)
					{
					$response->x_open("Category");
						$response->x_print($data["Categories"][$id]);
					$response->x_close("Category");
					}

			$response->x_close("Categories");
			}

		if(isset($data["Children"]))
			{
			$response->x_switch($marker);

			$response->x_open("Children");

				foreach($data["Children"] as $id => $null)
					{
					$response->x_open("Child");
						$response->x_print($data["Children"][$id]);
					$response->x_close("Child");
					}

			$response->x_close("Children");
			}

		if(isset($data["Flag"]))
			if(count($data["Flag"]) == 0)
				{
				$response->x_switch($marker);

				$response->x_open("Flag", false);
				}
			else
				{
				$response->x_switch($marker);

				$response->x_open("Flag");

					foreach(array("Email", "Tasks") as $codepage)
						{
						if(isset($data["Flag"][$codepage]) === false)
							continue;

						$response->x_switch($codepage);

						foreach($data["Flag"][$codepage] as $token => $null)
							{
							if(strlen($data["Flag"][$codepage][$token]) == 0)
								{
								$response->x_open($token, false);

								continue;
								}

							$response->x_open($token);
								$response->x_print($data["Flag"][$codepage][$token]);
							$response->x_close($token);
							}
						}

				$response->x_close("Flag");
				}

		if(isset($data["Meeting"]))
			{
			$response->x_switch($marker);

			$response->x_open("MeetingRequest");

				foreach(array("Email", "Email2", "Calendar") as $codepage)
					{
					if(isset($data["Meeting"][$codepage]) === false)
						continue;

					$response->x_switch($codepage);

					foreach($data["Meeting"][$codepage] as $token => $null)
						{
						if(strlen($data["Meeting"][$codepage][$token]) == 0)
							{
							$response->x_open($token, false);

							continue;
							}

						$response->x_open($token);
							$response->x_print($data["Meeting"][$codepage][$token]);
						$response->x_close($token);
						}
					}

			$response->x_close("MeetingRequest");
			}

		if(isset($data["Recurrence"]))
			{
			$response->x_switch($marker);

			$response->x_open("Recurrences");

				foreach($data["Recurrence"] as $id => $null)
					{
					$response->x_open("Recurrence");

						foreach(active_sync_get_default_recurrence() as $token => $null)
							{
							if(isset($data["Recurrence"][$id][$token]) === false)
								continue;

							if(strlen($data["Recurrence"][$id][$token]) == 0)
								{
								$response->x_open($token, false);

								continue;
								}

							$response->x_open($token);
								$response->x_print($data["Recurrence"][$id][$token]);
							$response->x_close($token);
							}

					$response->x_close("Recurrence");
					}

			$response->x_close("Recurrences");
			}

		if(isset($data["RightsManagement"]))
			{
			$response->x_switch("RightsManagement");

			$response->x_open("RightsManagementLicense");

				# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

				foreach(active_sync_get_default_rights_management() as $token => $null)
					{
					if(isset($data["RightsManagement"][$token]) === false)
						continue;

					if(strlen($data["RightsManagement"][$token]) == 0)
						{
						$response->x_open($token, false);

						continue;
						}

					$response->x_open($token);
						$response->x_print($data["RightsManagement"][$token]);
					$response->x_close($token);
					}

			$response->x_close("RightsManagementLicense");
			}

		if(isset($data["Body"]))
			{
			$default_class = active_sync_get_class_by_collection_id($user, $collection_id);

			if(isset($collection->Options))
				{
				foreach($collection->Options as $options)
					{
					if(isset($options->Class))
						if(isset($data["AirSync"]["Class"]))
							if(strval($options->Class) != $data["AirSync"]["Class"])
								continue;

					if(isset($options->RightsManagementSupport))
						if(intval($options->RightsManagementSupport) == 1)
							if(isset($data["RightsManagement"]))
								{
								$response->x_switch("RightsManagement");

								$response->x_open("RightsManagementLicense");

									# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

									foreach(active_sync_get_default_rights_management() as $token => $value)
										{
										if(isset($data["RightsManagement"][$token]) === false)
											continue;

										if(strlen($data["RightsManagement"][$token]) == 0)
											{
											$response->x_open($token, false);

											continue;
											}

										$response->x_open($token);
											$response->x_print($data["RightsManagement"][$token]);
										$response->x_close($token);
										}

								$response->x_close("RightsManagementLicense");
								}

					foreach($options->BodyPreference as $preference)
						{
						foreach($data["Body"] as $random_body_id => $null) # !!!
							{
							if(isset($data["Body"][$random_body_id]["Type"]) === false)
								continue;

							if($data["Body"][$random_body_id]["Type"] != intval($preference->Type))
								continue;

							$response->x_switch("AirSyncBase");

							$response->x_open("Body");

								if(isset($preference["Preview"]))
									foreach($data["Body"] as $random_preview_id => $null) # !!!
										{
										if(isset($data["Body"][$random_preview_id]["Type"]) === false)
											continue;

										if($data["Body"][$random_preview_id]["Type"] != 1)
											continue;

										$response->x_open("Preview");
											$response->x_print(substr($data["Body"][$random_preview_id]["Data"], 0, intval($preference->Preview)));
										$response->x_close("Preview");
										}

								if(isset($preference->TruncationSize))
									if(isset($data["Body"][$random_body_id]["EstimatedDataSize"]))
										if(intval($preference->TruncationSize) < $data["Body"][$random_body_id]["EstimatedDataSize"])
											{
											$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
											}

								foreach($data["Body"][$random_body_id] as $token => $value)
									{
									if(strlen($data["Body"][$random_body_id][$token]) == 0)
										{
										$response->x_open($token, false);

										continue;
										}

									$response->x_open($token);
										$response->x_print($data["Body"][$random_body_id][$token]); # opaque data will fail :(
									$response->x_close($token);
									}

							$response->x_close("Body");
							}
						}
					}
				}
			}

	$response->x_close("ApplicationData");
	}

function active_sync_handle_sync_send_calendar(& $response, $user, $collection_id, $server_id, $collection)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	$response->x_switch("AirSync");

	$response->x_open("ApplicationData");

		$codepage_table = array
			(
			"Calendar" => active_sync_get_default_calendar()
			);

		foreach($codepage_table as $codepage => $null)
			{
			if(isset($data[$codepage]) === false)
				continue;

			$response->x_switch($codepage);

			foreach($codepage_table[$codepage] as $token => $value)
				{
				if(isset($data[$codepage][$token]) === false)
					continue;

				if(strlen($data[$codepage][$token]) == 0)
					{
					$response->x_open($token, false);

					continue;
					}

				if(in_array($token, array("DtStamp", "StartTime", "EndTime")))
					$data[$codepage][$token] = date("Ymd\THis\Z", strtotime($data[$codepage][$token]));

				$response->x_open($token);
					$response->x_print($data[$codepage][$token]);
				$response->x_close($token);
				}
			}

		if(isset($data["Attendees"]))
			{
			$response->x_switch("Calendar");

			$response->x_open("Attendees");

				foreach($data["Attendees"] as $attendee)
					{
					$response->x_open("Attendee");

						foreach(active_sync_get_default_attendee() as $token => $value)
							{
							if(isset($attendee[$token]) === false)
								continue;

							if(strlen($attendee[$token]) == 0)
								{
								$response->x_open($token, false);

								continue;
								}

							$response->x_open($token);
								$response->x_print($attendee[$token]);
							$response->x_close($token);
							}

					$response->x_close("Attendee");
					}

			$response->x_close("Attendees");
			}

		if(isset($data["Recurrence"]))
			{
			$response->x_switch("Calendar");

			$response->x_open("Recurrence");

				foreach(active_sync_get_default_recurrence() as $token => $value)
					{
					if(isset($data["Recurrence"][$token]) === false)
						continue;

					if(strlen($data["Recurrence"][$token]) == 0)
						{
						$response->x_open($token, false);

						continue;
						}

					$response->x_open($token);
						$response->x_print($data["Recurrence"][$token]);
					$response->x_close($token);
					}

			$response->x_close("Recurrence");
			}

		if(isset($data["Body"]))
			{
			$default_class = active_sync_get_class_by_collection_id($user, $collection_id);

			if(isset($collection->Options))
				{
				foreach($collection->Options as $options)
					{
					if(isset($options->Class))
						if(isset($data["AirSync"]["Class"]))
							if(strval($options->Class) != $data["AirSync"]["Class"])
								continue;

					if(isset($options->RightsManagementSupport))
						if(intval($options->RightsManagementSupport) == 1)
							if(isset($data["RightsManagement"]))
								{
								$response->x_switch("RightsManagement");

								$response->x_open("RightsManagementLicense");

									# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

									foreach(active_sync_get_default_rights_management() as $token => $value)
										{
										if(isset($data["RightsManagement"][$token]) === false)
											continue;

										if(strlen($data["RightsManagement"][$token]) == 0)
											{
											$response->x_open($token, false);

											continue;
											}

										$response->x_open($token);
											$response->x_print($data["RightsManagement"][$token]);
										$response->x_close($token);
										}

								$response->x_close("RightsManagementLicense");
								}

					foreach($options->BodyPreference as $preference)
						{
						foreach($data["Body"] as $random_body_id => $null) # !!!
							{
							if(isset($data["Body"][$random_body_id]["Type"]) === false)
								continue;

							if($data["Body"][$random_body_id]["Type"] != intval($preference->Type))
								continue;

							if(isset($data["Body"][$random_body_id]["Data"]) === false)
								continue;

							$response->x_switch("AirSyncBase");

							$response->x_open("Body");

								if(isset($preference["Preview"]))
									{
									foreach($data["Body"] as $random_preview_id => $null) # !!!
										{
										if(isset($data["Body"][$random_preview_id]["Type"]) === false)
											continue;

										if($data["Body"][$random_preview_id]["Type"] != 1)
											continue;

										$response->x_open("Preview");
											$response->x_print(substr($data["Body"][$random_preview_id]["Data"], 0, intval($preference->Preview)));
										$response->x_close("Preview");
										}
									}

								if(isset($preference->TruncationSize))
									if(intval($preference->TruncationSize) > 0)
										if(isset($data["Body"][$random_body_id]["EstimatedDataSize"]) === false)
											{
											$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
											}
										elseif(intval($preference->TruncationSize) < $data["Body"][$random_body_id]["EstimatedDataSize"])
											{
											$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
											}

								foreach($data["Body"][$random_body_id] as $token => $value)
									{
									if(strlen($data["Body"][$random_body_id][$token]) == 0)
										{
										$response->x_open($token, false);

										continue;
										}

									$response->x_open($token);
										$response->x_print($data["Body"][$random_body_id][$token]); # opaque data will fail :(
									$response->x_close($token);
									}

							$response->x_close("Body");
							}
						}
					}
				}
			}

		if(isset($data["Categories"]))
			{
			$response->x_switch("Calendar");

			$response->x_open("Categories");

				foreach($data["Categories"] as $value)
					{
					$response->x_open("Category");
						$response->x_print($value);
					$response->x_close("Category");
					}

			$response->x_close("Categories");
			}

	$response->x_close("ApplicationData");
	}

function active_sync_handle_sync_send_contacts(& $response, $user, $collection_id, $server_id, $collection)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	$response->x_switch("AirSync");

	$response->x_open("ApplicationData");

		$codepage_table = array
			(
			"Contacts" => active_sync_get_default_contacts(),
			"Contacts2" => active_sync_get_default_contacts2()
			);

		foreach($codepage_table as $codepage => $null)
			{
			if(isset($data[$codepage]) === false)
				continue;

			$response->x_switch($codepage);

			foreach($codepage_table[$codepage] as $token => $value)
				{
				if(isset($data[$codepage][$token]) === false)
					continue;

				if(strlen($data[$codepage][$token]) == 0)
					{
					$response->x_open($token, false);

					continue;
					}

				if(in_array($token, array("Aniversary", "Birthday")))
					$data[$codepage][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data[$codepage][$token]));

				$response->x_open($token);
					$response->x_print($data[$codepage][$token]);
				$response->x_close($token);
				}
			}

		if(isset($data["Children"]))
			{
			$response->x_switch("Contacts");

			$response->x_open("Children");

				foreach($data["Children"] as $value)
					{
					$response->x_open("Child");
						$response->x_print($value);
					$response->x_close("Child");
					}

			$response->x_close("Children");
			}

		if(isset($data["Body"]))
			{
			$default_class = active_sync_get_class_by_collection_id($user, $collection_id);

			if(isset($collection->Options))
				{
				foreach($collection->Options as $options)
					{
					if(isset($options->Class))
						if(isset($data["AirSync"]["Class"]))
							if(strval($options->Class) != $data["AirSync"]["Class"])
								continue;

					if(isset($options->RightsManagementSupport))
						if(intval($options->RightsManagementSupport) == 1)
							if(isset($data["RightsManagement"]))
								{
								$response->x_switch("RightsManagement");

								$response->x_open("RightsManagementLicense");

									# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

									foreach(active_sync_get_default_rights_management() as $token => $value)
										{
										if(isset($data["RightsManagement"][$token]) === false)
											continue;

										if(strlen($data["RightsManagement"][$token]) == 0)
											{
											$response->x_open($token, false);

											continue;
											}

										$response->x_open($token);
											$response->x_print($data["RightsManagement"][$token]);
										$response->x_close($token);
										}

								$response->x_close("RightsManagementLicense");
								}

					foreach($options->BodyPreference as $preference)
						{
						foreach($data["Body"] as $random_body_id => $null) # !!!
							{
							if(isset($data["Body"][$random_body_id]["Type"]) === false)
								continue;

							if($data["Body"][$random_body_id]["Type"] != intval($preference->Type))
								continue;

							if(isset($data["Body"][$random_body_id]["Data"]) === false)
								continue;

							$response->x_switch("AirSyncBase");

							$response->x_open("Body");

								if(isset($preference["Preview"]))
									{
									foreach($data["Body"] as $random_preview_id => $null) # !!!
										{
										if(isset($data["Body"][$random_preview_id]["Type"]) === false)
											continue;

										if($data["Body"][$random_preview_id]["Type"] != 1)
											continue;

										$response->x_open("Preview");
											$response->x_print(substr($data["Body"][$random_preview_id]["Data"], 0, intval($preference->Preview)));
										$response->x_close("Preview");
										}
									}

								if(isset($preference->TruncationSize))
									if(isset($data["Body"][$random_body_id]["EstimatedDataSize"]))
										if(intval($preference->TruncationSize) < $data["Body"][$random_body_id]["EstimatedDataSize"])
											{
											$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
											}

								foreach($data["Body"][$random_body_id] as $token => $value)
									{
									if(strlen($data["Body"][$random_body_id][$token]) == 0)
										{
										$response->x_open($token, false);

										continue;
										}

									$response->x_open($token);
										$response->x_print($data["Body"][$random_body_id][$token]); # opaque data will fail :(
									$response->x_close($token);
									}

							$response->x_close("Body");
							}
						}
					}
				}
			}

		if(isset($data["Categories"]))
			{
			$response->x_switch("Contacts");

			$response->x_open("Categories");

				foreach($data["Categories"] as $value)
					{
					$response->x_open("Category");
						$response->x_print($value);
					$response->x_close("Category");
					}

			$response->x_close("Categories");
			}

	$response->x_close("ApplicationData");
	}

function active_sync_handle_sync_send_email(& $response, $user, $collection_id, $server_id, $collection)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	if(isset($data["AirSync"]))
		{
		$response->x_switch("AirSync");

		foreach($data["AirSync"] as $token => $value)
			{
			if(strlen($data["AirSync"][$token]) == 0)
				{
				$response->open($token, false);

				continue;
				}

			$response->x_open($token);
				$response->x_print($data["AirSync"][$token]);
			$response->x_close($token);
			}
		}

	$response->x_switch("AirSync");

	$response->x_open("ApplicationData");

		$codepage_table = array
			(
			"Email" => active_sync_get_default_email(),
			"Email2" => active_sync_get_default_email2(),
			"AirSyncBase" => array("NativeBodyType" => 4)
			);

		foreach($codepage_table as $codepage => $token_table)
			{
			if(isset($data[$codepage]) === false)
				continue;

			$response->x_switch($codepage);

			foreach($codepage_table[$codepage] as $token => $value)
				{
				if(isset($data[$codepage][$token]) === false)
					continue;

				if(strlen($data[$codepage][$token]) == 0)
					{
					$response->x_open($token, false);

					continue;
					}

				$response->x_open($token);
					$response->x_print($data[$codepage][$token]);
				$response->x_close($token);
				}
			}

		if(isset($data["Attachments"]))
			{
			$response->x_switch("AirSyncBase");

			$response->x_open("Attachments");

				foreach($data["Attachments"] as $id => $attachment)
					{
					$response->x_switch("AirSyncBase");

					$response->x_open("Attachment");

						foreach(array("AirSyncBase", "Email2") as $codepage)
							{
							if(isset($data["Attachments"][$id][$codepage]) === false)
								continue;

							$response->x_switch($codepage);

							foreach($data["Attachments"][$id][$codepage] as $token => $value)
								{
								if(strlen($data["Attachments"][$id][$codepage][$token]) == 0)
									{
									$response->x_open($token, false);

									continue;
									}

								$response->x_open($token);
									$response->x_print($data["Attachments"][$id][$codepage][$token]);
								$response->x_close($token);
								}
							}

					$response->x_close("Attachment");
					}

			$response->x_close("Attachments");
			}

		if(isset($data["Flag"]))
			if(count($data["Flag"]) == 0)
				{
				$response->x_switch("Email"); # or Tasks ???

				$response->x_open("Flag", false);
				}
			else
				{
				$response->x_switch("Email"); # or Tasks ???

				$response->x_open("Flag");

					foreach(array("Email", "Tasks") as $codepage)
						{
						if(isset($data["Flag"][$codepage]) === false)
							continue;

						$response->x_switch($codepage);

						foreach($data["Flag"][$codepage] as $token => $value)
							{
							if(strlen($data["Flag"][$codepage][$token]) == 0)
								{
								$response->x_open($token, false);

								continue;
								}

							$response->x_open($token);
								$response->x_print($data["Flag"][$codepage][$token]);
							$response->x_close($token);
							}
						}

				$response->x_close("Flag");
				}

		if(isset($data["Meeting"]))
			{
			$response->x_switch("Email");

			$response->x_open("MeetingRequest");

				foreach(array("Email", "Email2", "Calendar") as $codepage)
					{
					if(isset($data["Meeting"][$codepage]) === false)
						continue;

					$response->x_switch($codepage);

					foreach($data["Meeting"][$codepage] as $token => $value)
						{
						if(strlen($data["Meeting"][$codepage][$token]) == 0)
							{
							$response->x_open($token, false);

							continue;
							}

						$response->x_open($token);
							$response->x_print($data["Meeting"][$codepage][$token]);
						$response->x_close($token);
						}
					}

			$response->x_close("MeetingRequest");
			}

		if(isset($data["Recurrence"]))
			{
			$response->x_switch("Email");

			$response->x_open("Recurrences");

				foreach($data["Recurrence"] as $id => $recurrence)
					{
					$response->x_open("Recurrence");

						foreach(active_sync_get_default_recurrence() as $token => $value)
							{
							if(isset($data["Recurrence"][$id][$token]) === false)
								continue;

							if(strlen($data["Recurrence"][$id][$token]) == 0)
								{
								$response->x_open($token, false);

								continue;
								}

							$response->x_open($token);
								$response->x_print($data["Recurrence"][$id][$token]);
							$response->x_close($token);
							}

					$response->x_close("Recurrence");
					}

			$response->x_close("Recurrences");
			}

		if(isset($data["Body"]))
			{
			$default_class = active_sync_get_class_by_collection_id($user, $collection_id);

			if(isset($collection->Options))
				{
				foreach($collection->Options as $options)
					{
					if(isset($options->Class))
						if(isset($data["AirSync"]["Class"]))
							if(strval($options->Class) != $data["AirSync"]["Class"])
								continue;

					if(isset($options->RightsManagementSupport))
						if(intval($options->RightsManagementSupport) == 1)
							if(isset($data["RightsManagement"]))
								{
								$response->x_switch("RightsManagement");

								$response->x_open("RightsManagementLicense");

									# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

									foreach(active_sync_get_default_rights_management() as $token => $value)
										{
										if(isset($data["RightsManagement"][$token]) === false)
											continue;

										if(strlen($data["RightsManagement"][$token]) == 0)
											{
											$response->x_open($token, false);

											continue;
											}

										$response->x_open($token);
											$response->x_print($data["RightsManagement"][$token]);
										$response->x_close($token);
										}

								$response->x_close("RightsManagementLicense");
								}

					foreach($options->BodyPreference as $preference)
						{
						foreach($data["Body"] as $random_body_id => $null) # !!!
							{
							if(isset($data["Body"][$random_body_id]["Type"]) === false)
								continue;

							if($data["Body"][$random_body_id]["Type"] != intval($preference->Type))
								continue;

							if(isset($data["Body"][$random_body_id]["Data"]) === false)
								continue;

							$response->x_switch("AirSyncBase");

							$response->x_open("Body");

								if(isset($preference["Preview"]))
									{
									foreach($data["Body"] as $random_preview_id => $null) # !!!
										{
										if(isset($data["Body"][$random_preview_id]["Type"]) === false)
											continue;

										if($data["Body"][$random_preview_id]["Type"] != 1)
											continue;

										$response->x_open("Preview");
											$response->x_print(substr($data["Body"][$random_preview_id]["Data"], 0, intval($preference->Preview)));
										$response->x_close("Preview");
										}
									}

								if(isset($preference->TruncationSize))
									if(intval($preference->TruncationSize) > 0)
										if(isset($data["Body"][$random_body_id]["EstimatedDataSize"]) === false)
											{
											$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
											}
										elseif(intval($preference->TruncationSize) < $data["Body"][$random_body_id]["EstimatedDataSize"])
											{
											$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
											}

								foreach($data["Body"][$random_body_id] as $token => $value)
									{
									if(strlen($data["Body"][$random_body_id][$token]) == 0)
										{
										$response->x_open($token, false);

										continue;
										}

									$response->x_open($token);
										$response->x_print($data["Body"][$random_body_id][$token]); # opaque data will fail :(
									$response->x_close($token);
									}

							$response->x_close("Body");
							}
						}
					}
				}
			}

		if(isset($data["Categories"]))
			{
			$response->x_switch("Email");

			$response->x_open("Categories");

				foreach($data["Categories"] as $id => $value)
					{
					$response->x_open("Category");
						$response->x_print($data["Categories"][$id]);
					$response->x_close("Category");
					}

			$response->x_close("Categories");
			}

	$response->x_close("ApplicationData");
	}

function active_sync_handle_sync_send_notes(& $response, $user, $collection_id, $server_id, $collection)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	$response->x_switch("AirSync");

	$response->x_open("ApplicationData");

		$codepage_table = array
			(
			"Notes" => active_sync_get_default_notes()
			);

		foreach($codepage_table as $codepage => $null)
			{
			if(isset($data[$codepage]) === false)
				continue;

			$response->x_switch($codepage);

			foreach($codepage_table[$codepage] as $token => $value)
				{
				if(isset($data[$codepage][$token]) === false)
					continue;

				if(strlen($data[$codepage][$token]) == 0)
					{
					$response->x_open($token, false);

					continue;
					}

				# The value of the * element is a string data type represented as a
				# Compact DateTime ([MS-ASDTYPE] section 2.7.2).

				if(in_array($token, array("LastModifiedDate")))
					$data[$codepage][$token] = date("Ymd\THis\Z", strtotime($data[$codepage][$token]));

				$response->x_open($token);
					$response->x_print($data[$codepage][$token]);
				$response->x_close($token);
				}
			}

		if(isset($data["Body"]))
			{
			$default_class = active_sync_get_class_by_collection_id($user, $collection_id);

			if(isset($collection->Options))
				{
				foreach($collection->Options as $options)
					{
					if(isset($options->Class))
						if(isset($data["AirSync"]["Class"]))
							if(strval($options->Class) != $data["AirSync"]["Class"])
								continue;

					if(isset($options->RightsManagementSupport))
						if(intval($options->RightsManagementSupport) == 1)
							if(isset($data["RightsManagement"]))
								{
								$response->x_switch("RightsManagement");

								$response->x_open("RightsManagementLicense");

									# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

									foreach(active_sync_get_default_rights_management() as $token => $value)
										{
										if(isset($data["RightsManagement"][$token]) === false)
											continue;

										if(strlen($data["RightsManagement"][$token]) == 0)
											{
											$response->x_open($token, false);

											continue;
											}

										$response->x_open($token);
											$response->x_print($data["RightsManagement"][$token]);
										$response->x_close($token);
										}

								$response->x_close("RightsManagementLicense");
								}

					foreach($options->BodyPreference as $preference)
						{
						foreach($data["Body"] as $random_body_id => $null) # !!!
							{
							if(isset($data["Body"][$random_body_id]["Type"]) === false)
								continue;

							if($data["Body"][$random_body_id]["Type"] != intval($preference->Type))
								continue;

							if(isset($data["Body"][$random_body_id]["Data"]) === false)
								continue;

							$response->x_switch("AirSyncBase");

							$response->x_open("Body");

								if(isset($preference["Preview"]))
									{
									foreach($data["Body"] as $random_preview_id => $null) # !!!
										{
										if(isset($data["Body"][$random_preview_id]["Type"]) === false)
											continue;

										if($data["Body"][$random_preview_id]["Type"] != 1)
											continue;

										$response->x_open("Preview");
											$response->x_print(substr($data["Body"][$random_preview_id]["Data"], 0, intval($preference->Preview)));
										$response->x_close("Preview");
										}
									}

								if(isset($preference->TruncationSize))
									if(intval($preference->TruncationSize) > 0)
										if(isset($data["Body"][$random_body_id]["EstimatedDataSize"]) === false)
											{
											$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
											}
										elseif(intval($preference->TruncationSize) < $data["Body"][$random_body_id]["EstimatedDataSize"])
											{
											$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
											}

								foreach($data["Body"][$random_body_id] as $token => $value)
									{
									if(strlen($data["Body"][$random_body_id][$token]) == 0)
										{
										$response->x_open($token, false);

										continue;
										}

									$response->x_open($token);
										$response->x_print($data["Body"][$random_body_id][$token]); # opaque data will fail :(
									$response->x_close($token);
									}

							$response->x_close("Body");
							}
						}
					}
				}
			}

		if(isset($data["Categories"]))
			{
			$response->x_switch("Notes");

			$response->x_open("Categories");

				foreach($data["Categories"] as $token => $value)
					{
					$response->x_open("Category");
						$response->x_print($value);
					$response->x_close("Category");
					}

			$response->x_close("Categories");
			}

	$response->x_close("ApplicationData");
	}

function active_sync_handle_sync_send_tasks(& $response, $user, $collection_id, $server_id, $collection)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	$response->x_switch("AirSync");

	$response->x_open("ApplicationData");

		$codepage_table = array
			(
			"Tasks" => active_sync_get_default_tasks()
			);

		foreach($codepage_table as $codepage => $null)
			{
			if(isset($data[$codepage]) === false)
				continue;

			$response->x_switch($codepage);

			foreach($codepage_table[$codepage] as $token => $value)
				{
				if(isset($data[$codepage][$token]) === false)
					continue;

				if(strlen($data[$codepage][$token]) == 0)
					{
					$response->x_open($token, false);

					continue;
					}

				# The value of the * element is a string data type represented as a
				# Compact DateTime ([MS-ASDTYPE] section 2.7.2).

#				if(in_array($token, array("DateCompleted", "DueDate", "OrdinalDate", "ReminderTime", "Start", "StartDate", "UtcDueDate", "UtcStartDate")))
#					$data[$codepage][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data[$codepage][$token]));

				$response->x_open($token);
					$response->x_print($data[$codepage][$token]);
				$response->x_close($token);
				}
			}

		if(isset($data["Recurrence"]))
			{
			$response->x_switch("Tasks");

			$response->x_open("Recurrence");

				foreach(active_sync_get_default_recurrence() as $token => $value)
					{
					if(isset($data["Recurrence"][$token]) === false)
						continue;

					if(strlen($data["Recurrence"][$token]) == 0)
						{
						$response->x_open($token, false);

						continue;
						}

					$response->x_open($token);
						$response->x_print($data["Recurrence"][$token]);
					$response->x_close($token);
					}

			$response->x_close("Recurrence");
			}

		if(isset($data["Body"]))
			{
			$default_class = active_sync_get_class_by_collection_id($user, $collection_id);

			if(isset($collection->Options))
				{
				foreach($collection->Options as $options)
					{
					if(isset($options->Class))
						if(isset($data["AirSync"]["Class"]))
							if(strval($options->Class) != $data["AirSync"]["Class"])
								continue;

					if(isset($options->RightsManagementSupport))
						if(intval($options->RightsManagementSupport) == 1)
							if(isset($data["RightsManagement"]))
								{
								$response->x_switch("RightsManagement");

								$response->x_open("RightsManagementLicense");

									# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

									foreach(active_sync_get_default_rights_management() as $token => $value)
										{
										if(isset($data["RightsManagement"][$token]) === false)
											continue;

										if(strlen($data["RightsManagement"][$token]) == 0)
											{
											$response->x_open($token, false);

											continue;
											}

										$response->x_open($token);
											$response->x_print($data["RightsManagement"][$token]);
										$response->x_close($token);
										}

								$response->x_close("RightsManagementLicense");
								}

					foreach($options->BodyPreference as $preference)
						{
						foreach($data["Body"] as $random_body_id => $null) # !!!
							{
							if(isset($data["Body"][$random_body_id]["Type"]) === false)
								continue;

							if($data["Body"][$random_body_id]["Type"] != intval($preference->Type))
								continue;

							if(isset($data["Body"][$random_body_id]["Data"]) === false)
								continue;

							$response->x_switch("AirSyncBase");

							$response->x_open("Body");

								if(isset($preference["Preview"]))
									{
									foreach($data["Body"] as $random_preview_id => $null) # !!!
										{
										if(isset($data["Body"][$random_preview_id]["Type"]) === false)
											continue;

										if($data["Body"][$random_preview_id]["Type"] != 1)
											continue;

										$response->x_open("Preview");
											$response->x_print(substr($data["Body"][$random_preview_id]["Data"], 0, intval($preference->Preview)));
										$response->x_close("Preview");
										}
									}

								if(isset($preference->TruncationSize))
									if(intval($preference->TruncationSize) > 0)
										if(isset($data["Body"][$random_body_id]["EstimatedDataSize"]) === false)
											{
											$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
											}
										elseif(intval($preference->TruncationSize) < $data["Body"][$random_body_id]["EstimatedDataSize"])
											{
											$data["Body"][$random_body_id]["Data"] = substr($data["Body"][$random_body_id]["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
											}

								foreach($data["Body"][$random_body_id] as $token => $value)
									{
									if(strlen($data["Body"][$random_body_id][$token]) == 0)
										{
										$response->x_open($token, false);

										continue;
										}

									$response->x_open($token);
										$response->x_print($data["Body"][$random_body_id][$token]); # opaque data will fail :(
									$response->x_close($token);
									}

							$response->x_close("Body");
							}
						}
					}
				}
			}


		if(isset($data["Categories"]))
			{
			$response->x_switch("Tasks");

			$response->x_open("Categories");

				foreach($data["Categories"] as $value)
					{
					$response->x_open("Category");
						$response->x_print($value);
					$response->x_close("Category");
					}

			$response->x_close("Categories");
			}

	$response->x_close("ApplicationData");
	}

function active_sync_handle_validate_cert($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	if(isset($xml->CheckCRL) === false)
		$CheckCRL = 0;
	else
		$CheckCRL = strval($xml->CheckCRL);

	$states = array();

	if(isset($xml->CertificateChain))
		{
		foreach($xml->CertificateChain->Certificate as $Certificate)
			{
			$state = 1; # Success.

			$states[] = $state;
			}
		}

	if(isset($xml->Certificates))
		{
		foreach($xml->Certificates->Certificate as $Certificate)
			{
			$cert = chunk_split($Certificate, 64);

			$cert = "-----BEGIN CERTIFICATE-----" . "\n" . $cert . "-----END CERTIFICATE-----";

			$data = openssl_x509_parse($cert);

			$state = 1; # Success.

			if(time() < $data["validFrom_time_t"])
				$state = 7; # The digital ID used to sign the message has expired or is not yet valid.

			if(time() > $data["validTo_time_t"])
				$state = 7; # The digital ID used to sign the message has expired or is not yet valid.

			foreach($data["purposes"] as $purpose)
				{
				if($purpose[2] != "smimesign")
					continue;

				if($purpose[0] == 1)
					continue;

				$state = 6;

				break;
				}

			if($CheckCRL == 0)
				{
				}
			elseif(isset($data["extensions"]["crlDistributionPoints"]) === false)
				$state = 14; # The validity of the digital ID cannot be determined because the server that provides this information cannot be contacted.
			else
				{
				exec("echo \"" . $cert . "\" | openssl x509 -serial -noout", $output, $var_return);

				$serial = str_replace("serial=", "", $output[0]);

				list($type, $address) = explode(":", $data["extensions"]["crlDistributionPoints"], 2);

				$address = trim($address);

				$data = file_get_contents($address);

				exec("echo \"" . $data . "\" | openssl crl -text -noout", $output, $var_return);

				foreach($output as $line)
					{
					if($line == "    Serial Number: " . $serial)
						{
						$state = 13; # The digital ID used to sign this message has been revoked. This can indicate that the issuer of the digital ID no longer trusts the sender, the digital ID was reported stolen, or the digital ID was compromised.

						break;
						}
					}
				}

			$states[] = $state;
			}
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("ValidateCerts");

	$response->x_open("ValidateCert");
		$response->x_open("Status");
			$response->x_print(1);
		$response->x_close("Status");

		foreach($states as $state)
			{
			$response->x_open("Certificate");
				$response->x_open("Status");
					$response->x_print($state);
				$response->x_close("Status");
			$response->x_close("Certificate");
			}

	$response->x_close("ValidateCert");

	return($response->response);
	}

function active_sync_http()
	{
	$request = active_sync_http_query_parse();

	$table = active_sync_get_table_method();

	$method = $request["Method"];

	if(isset($table[$method]) === false)
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
	elseif(strlen($table[$method]) == 0)
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
	elseif(function_exists($table[$method]) === false)
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
	else
		$table[$method]($request);
	}

function active_sync_http_method_get($request)
	{
	if(defined("WEB_DIR") === false)
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 204, "No Content")));
	elseif(is_dir(WEB_DIR) === false)
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 204, "No Content")));
	else
		{
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 307, "Permanent Redirect")));

		header(implode(": ", array("Location", "web")));
		}
	}

function active_sync_http_method_options($request)
	{
	header("MS-Server-ActiveSync: " . active_sync_get_version());
	header("MS-ASProtocolVersions: " . active_sync_get_supported_versions());
	# header("X-MS-RP: " . active_sync_get_supported_versions());
	header("MS-ASProtocolCommands: " . active_sync_get_supported_commands());
	header("Allow: OPTIONS,POST"); # implode(",", active_sync_get_table_method());
	header("Public: OPTIONS,POST"); # implode(",", active_sync_get_table_method());
	}

function active_sync_http_method_post($request)
	{
	$logging = $request["wbxml"];
	$logging = active_sync_wbxml_request_b($logging);
	$logging = active_sync_wbxml_pretty($logging);
	active_sync_debug($logging, "REQUEST");

	$response = array
		(
		"wbxml" => "",
		"xml" => ""
		);

	if(active_sync_get_is_identified($request) == 0)
		header("WWW-Authenticate: basic realm=\"ActiveSync\"");
	elseif($request["DeviceId"] != "validate")
		{
		active_sync_folder_init($request["AuthUser"]);

		$table = active_sync_get_table_handle();

		$cmd = $request["Cmd"];

		if(isset($table[$cmd]) === false)
			header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
		elseif(strlen($table[$cmd]) == 0)
			header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
		elseif(function_exists($table[$cmd]) === false)
			header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
		else
			$response["wbxml"] = $table[$cmd]($request);

		if(headers_sent() === false)
			{
			header("Content-Length: " . strlen($response["wbxml"]));

			if(strlen($response["wbxml"]) > 0)
				header("Content-Type: application/vnd.ms-sync.wbxml");

			header_remove("X-Powered-By");
			}

		print($response["wbxml"]);
		}

	$logging = $response["wbxml"];
	$logging = active_sync_wbxml_request_b($logging);
	$logging = active_sync_wbxml_pretty($logging);
	active_sync_debug($logging, "RESPONSE");

@	file_put_contents(LOG_DIR . "/in.txt", json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
@	file_put_contents(LOG_DIR . "/out.txt", json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

function active_sync_http_query_parse()
	{
	$retval = array
		(
		"AcceptMultiPart"	=> "F",
		"AttachmentName"	=> "",
		"Cmd"			=> "",
		"CollectionId"		=> "",
		"DeviceId"		=> "",
		"DeviceType"		=> "",
		"ItemId"		=> "",
		"Locale"		=> "",
		"LongId"		=> "",
		"Occurence"		=> "",
		"PolicyKey"		=> "",
		"ProtocolVersion"	=> "",
		"SaveInSent"		=> "F",
		"User"			=> "",

		"AuthDomain"		=> "",	# extra field, not specified
		"AuthPass"		=> "",	# extra field, not specified
		"AuthUser"		=> "",	# extra field, not specified
		"Domain"		=> "",	# extra field, not specified
		"ContentType"		=> "",	# extra field, not specified
		"Method"		=> "",	# extra field, not specified
		"UserAgent"		=> ""	# extra field, not specified
		);

	$query = (isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : "");

	if($query == "")
		{
		}
	elseif(preg_match("#^([A-Za-z0-9+/]{4})*([A-Za-z0-9+/]{4}|[A-Za-z0-9+/]{3}=|[A-Za-z0-9+\/]{2}==)?$#", $query) == 1)
		{
		$b = base64_decode($query);

		$commands = active_sync_get_table_command();

		$parameters = active_sync_get_table_parameter();

		$device_id_length = ord($b[4]);							# DeviceIdLength
		$policy_key_length = ord($b[5 + $device_id_length]);				# PolicyKeyLength
		$device_type_length = ord($b[6 + $device_id_length + $policy_key_length]);	# DeviceTypeLength

		$z = unpack("CProtocolVersion/CCommandCode/vLocale/CDeviceIdLength/H" . ($device_id_length * 2) . "DeviceId/CPolicyKeyLength" . ($policy_key_length == 4 ? "/VPolicyKey" : "") . "/CDeviceTypeLength/A" . ($device_type_length) . "DeviceType", $b);

		$b = substr($b, 7 + $device_id_length + $policy_key_length + $device_type_length);

		while(strlen($b) > 0)
			{
			$f = ord($b[1]);
			$g = unpack("CTag/CLength/A" . $f . "Value", $b);
			$b = substr($b, 2 + $f);

			if($g["Tag"] == 7) # options
				{
				$retval["SaveInSent"]		= (($g["Value"] & 0x01) == 0x01 ? "T" : "F");
				$retval["AcceptMultiPart"]	= (($g["Value"] & 0x02) == 0x02 ? "T" : "F");
				}
			elseif(isset($parameters[$g["Tag"]]))
				$retval[$parameters[$g["Tag"]]]	= $g["Value"];
			}

		if(isset($commands[$z["CommandCode"]]))
			$retval["Cmd"] = $commands[$z["CommandCode"]];

		foreach(array("DeviceId", "DeviceType", "Locale", "PolicyKey", "ProtocolVersion") as $key)
			$retval[$key] = (isset($z[$key]) ? $z[$key] : "");

		$retval["ProtocolVersion"] = $retval["ProtocolVersion"] / 10; # 141 -> 14.1
		}
	else
		{
		foreach(array("AcceptMultiPart" => "HTTP_MS_ASACCEPTMULTIPART", "PolicyKey" => "HTTP_X_MS_POLICYKEY", "ProtocolVersion" => "HTTP_MS_ASPROTOCOLVERSION") as $key_a => $key_b)
			$retval[$key_a] = (isset($_SERVER[$key_b]) ? $_SERVER[$key_b] : "");

		foreach(array("AttachmentName", "Cmd", "CollectionId", "DeviceId", "DeviceType", "ItemId", "LongId", "Occurence", "SaveInSent", "User") as $key)
			$retval[$key] = (isset($_GET[$key]) ? $_GET[$key] : "");
		}

	foreach(array("AuthPass" => "PHP_AUTH_PW", "AuthUser" => "PHP_AUTH_USER", "ContentType" => "CONTENT_TYPE", "Method" => "REQUEST_METHOD", "UserAgent" => "HTTP_USER_AGENT") as $key_a => $key_b)
		$retval[$key_a] = (isset($_SERVER[$key_b]) ? $_SERVER[$key_b] : "");

	$domain = "";

	foreach(array("", "Auth") as $key)
		{
		$retval[$key . "User"] = strtolower($retval[$key . "User"]); # take care about brain-disabled-users

		list($retval[$key . "Domain"], $retval[$key . "User"]) = (strpos($retval[$key . "User"], "\\") === false ? array($domain, $retval[$key . "User"]) : explode("\\", $retval[$key . "User"], 2));
		}

#	$data = (isset($GLOBALS["HTTP_RAW_POST_DATA"]) === false ? null : $GLOBALS["HTTP_RAW_POST_DATA"]);
	$data = file_get_contents("php://input");

	$retval["wbxml"] = $data;

	$retval["xml"] = ($retval["ContentType"] == "application/vnd.ms-sync.wbxml" ? active_sync_wbxml_request_a($data) : "");

	return($retval);
	}

function active_sync_load_includes($path, $type = "php", $recursive = false)
	{
	foreach(glob($path . "/*." . $type) as $file)
		{
		if($type == "js")
			print(file_get_contents($file));

		if($type == "php")
			include_once($file);
		}

	return(true);
	}

function active_sync_mail_add_container_c(& $data, $body, $user)
	{
	$host = active_sync_get_domain(); # needed for user@host

	$temp = $body;
	$vcalendar = active_sync_vcalendar_parse($body);
	$body = $temp;

	foreach(active_sync_get_default_meeting() as $token => $value)
		$data["Meeting"]["Email"][$token] = $value;

	$timezone_informations = active_sync_get_table_timezone_information();

	$data["Meeting"]["Email"]["TimeZone"] = $timezone_informations[28][0];

	$codepage_table = array();

	$codepage_table["Email"] = array("DTSTART" => "StartTime", "DTSTAMP" => "DtStamp", "DTEND" => "EndTime", "LOCATION" => "Location");
	$codepage_table["Calendar"] = array("UID" => "UID");

	foreach($codepage_table as $codepage => $null)
		{
		foreach($codepage_table[$codepage] as $key => $token)
			{
			if(isset($vcalendar["VCALENDAR"]["VEVENT"][$key]) === false)
				continue;

			$data["Meeting"][$codepage][$token] = $vcalendar["VCALENDAR"]["VEVENT"][$key];
			}
		}

	########################################################################
	# check MeetingStatus
	########################################################################
	# 0	The event is an appointment, which has no attendees.
	# 1	The event is a meeting and the user is the meeting organizer.
	# 3	This event is a meeting, and the user is not the meeting organizer; the meeting was received from someone else.
	# 5	The meeting has been canceled and the user was the meeting organizer.
	# 7	The meeting has been canceled. The user was not the meeting organizer; the meeting was received from someone else.
	########################################################################
	# 0x01 The event is a meeting
	# 0x02 The user is/was not the meeting organizer; the meeting was received from someone else.
	# 0x04 The meeting has been canceled.
	########################################################################

#	$data["Meeting"]["Email"]["MeetingStatus"] = 0;

	$organizer = (isset($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"][$user . "@" . $host]) === false ? 0 : 1);

	foreach(array("CANCEL" => array(7, 5), "REQUEST" => array(3, 1)) as $key => $value)
		{
		if($vcalendar["VCALENDAR"]["METHOD"] != $key)
			continue;

#		$data["Meeting"]["Email"]["MeetingStatus"] = $value[$organizer];
		}

	########################################################################
	# check MeetingMessageType
	########################################################################
	# 0	A silent update was performed, or the message type is unspecified.
	# 1	Initial meeting request.
	# 3	Informational update.
	########################################################################

	$data["Meeting"]["Email2"]["MeetingMessageType"] = 0;

	foreach(array("CANCEL" => 0, "REPLY" => 3, "REQUEST" => 1) as $key => $value)
		if($vcalendar["VCALENDAR"]["METHOD"] == $key)
			$data["Meeting"]["Email2"]["MeetingMessageType"] = $value;

	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["CLASS"]))
		{
		foreach(array("DEFAULT" => 0, "PUBLIC" => 1, "PRIVATE" => 2, "CONFIDENTIAL" => 3) as $key => $value)
			if($vcalendar["VCALENDAR"]["VEVENT"]["CLASS"] == $key)
				$data["Meeting"]["Email"]["Sensitivity"] = $value;
		}

	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["X-MICROSOFT-CDO-ALLDAYEVENT"]))
		{
#		$data["Meeting"]["Email"]["AllDayEvent"] = 0;

		foreach(array("FALSE" => 0, "TRUE" => 1) as $key => $value)
			if($vcalendar["VCALENDAR"]["VEVENT"]["X-MICROSOFT-CDO-ALLDAYEVENT"] == $key)
				$data["Meeting"]["Email"]["AllDayEvent"] = $value;
		}

	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"]))
		{
#		$data["Meeting"]["Email"]["Organizer"] = $user . "@" . $host;

		foreach($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"] as $key => $null)
			$data["Meeting"]["Email"]["Organizer"] = $key;
		}

	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$user . "@" . $host]["RVSP"]))
		{
		foreach(array("FALSE" => 0, "TRUE" => 1) as $key => $value)
			{
			$data["Meeting"]["Email"]["ResponseRequested"] = 0;

			if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"]["RVSP"] == $key)
				$data["Meeting"]["Email"]["ResponseRequested"] = $value;
			}
		}

	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["VALARM"]["TRIGGER"]))
		$data["Meeting"]["Email"]["Reminder"] = substr($vcalendar["VCALENDAR"]["VEVENT"]["VALARM"]["TRIGGER"], 3, 0 - 1); # -PT*M

#	if(isset($vcalendar["VCALENDAR"]["VEVENT"]["RRULE"]))
#		foreach(array("FREQ" => "Type", "COUNT" => "Occurences", "INTERVAL" => "Interval") as $key => $token)
#			if(isset($vcalendar["VCALENDAR"]["VEVENT"]["RRULE"][$key]))
#				$data["Meeting"]["reccurence"][0][$token] = $vcalendar["VCALENDAR"]["VEVENT"]["RRULE"][$key];

	if(active_sync_body_type_exist($data, 1) == 0)
		{
		$new_temp_message = array();

		$new_temp_message[] = "Wann: " . date("d.m.Y H:i", strtotime($vcalendar["VCALENDAR"]["VEVENT"]["DTSTART"]));

		if(isset($vcalendar["VCALENDAR"]["VEVENT"]["LOCATION"]))
			$new_temp_message[] = "Wo: " . $vcalendar["VCALENDAR"]["VEVENT"]["LOCATION"];

		$new_temp_message[] = "*~*~*~*~*~*~*~*~*~*";

		if(isset($vcalendar["VCALENDAR"]["VEVENT"]["DESCRIPTION"]))
			$new_temp_message[] = $vcalendar["VCALENDAR"]["VEVENT"]["DESCRIPTION"];


#		if(isset($vcalendar["VCALENDAR"]["VEVENT"]["SUMMARY"]))
#			$new_temp_message[] = $vcalendar["VCALENDAR"]["VEVENT"]["SUMMARY"]; # this must be calendar:body:data, not calendar:subject, but calendar:body:data from calendar is not available

		$new_temp_message = implode("\n", $new_temp_message);

		active_sync_mail_add_container_p($data, $new_temp_message);
		}


	$data["Email"]["From"] = (isset($data["Email"]["From"]) === false ? $user . "@" . $host : $data["Email"]["From"]);

	list($f_name, $f_mail) = active_sync_mail_parse_address($data["Email"]["From"]);
	list($t_name, $t_mail) = active_sync_mail_parse_address($data["Email"]["To"]);

	########################################################################
	# just check
	# if we are an attendee and have to delete a meeting from calendar or
	# if we are an organizer and have to update an attendee status.
	# nothing else!
	########################################################################

	if(isset($vcalendar["VCALENDAR"]["METHOD"]))
		if($vcalendar["VCALENDAR"]["METHOD"] === "CANCEL")
			{
			if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"][$user . "@" . $host]) === false)
				if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$user . "@" . $host]))
					{
					$server_id = active_sync_get_calendar_by_uid($user, $vcalendar["VCALENDAR"]["VEVENT"]["UID"]);

					if($server_id != "")
						unlink(DAT_DIR . "/" . $user . "/". active_sync_get_collection_id_by_type($user, 8) . "/" . $server_id . ".data");
					}

			$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
			$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Canceled";
			}
		elseif($vcalendar["VCALENDAR"]["METHOD"] === "PUBLISH")
			{
			}
		elseif($vcalendar["VCALENDAR"]["METHOD"] === "REPLY")
			{
			if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"][$user . "@" . $host]))
				if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$f_mail]))
					{
					$server_id = active_sync_get_calendar_by_uid($user, $vcalendar["VCALENDAR"]["VEVENT"]["UID"]);

					if($server_id != "")
						{
						if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$f_mail]["PARTSTAT"] == "DECLINED")
							{
							$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Neg";

							active_sync_put_attendee_status($user, $server_id, $f_mail, 4);
							}

						if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$f_mail]["PARTSTAT"] == "ACCEPTED")
							{
							$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Pos";

							active_sync_put_attendee_status($user, $server_id, $f_mail, 3);
							}

						if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$f_mail]["PARTSTAT"] == "TENTATIVE")
							{
							$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Tent";

							active_sync_put_attendee_status($user, $server_id, $f_mail, 2);
							}
						}
					}
			}
		elseif($vcalendar["VCALENDAR"]["METHOD"] === "REQUEST")
			{
			$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
			$data["Email"]["MessageClass"] = "IPM.Notification.Meeting";

			if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ORGANIZER"][$user . "@" . $host]) === false)
				if(isset($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$user . "@" . $host]))
					{
					if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$user . "@" . $host]["PARTSTAT"] == "NEEDS-ACTION")
						{
						$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
						$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Request";
						}

					if($vcalendar["VCALENDAR"]["VEVENT"]["ATTENDEE"][$user . "@" . $host]["PARTSTAT"] != "NEEDS-ACTION")
						{
						$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
						$data["Email"]["MessageClass"] = "IPM.Notification.Meeting";
						}
					}
			}
	}

function active_sync_mail_add_container_h(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = array
		(
		"Type" => 2,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		);
	}

function active_sync_mail_add_container_m(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = array
		(
		"Type" => 4,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		);
	}

function active_sync_mail_add_container_p(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = array
		(
		"Type" => 1,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		);
	}

function active_sync_mail_add_container_r(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = array
		(
		"Type" => 3,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		);
	}

function active_sync_mail_body_smime_cleanup()
	{
	foreach(array("dec", "enc", "ver") as $extension)
		{
		if(file_exists("/tmp/" . $file . "." . $extension) === false)
			continue;

		unlink("/tmp/" . $file . "." . $extension);
		}
	}

function active_sync_mail_body_smime_decode($mime)
	{
	$file = active_sync_create_guid();

	$mail_struct = active_sync_mail_split($mime);

	$head_parsed = active_sync_mail_parse_head($mail_struct["head"]);

	list($t_name, $t_mail) = active_sync_mail_parse_address($head_parsed["To"]);

	if((file_exists(CRT_DIR . "/certs/" . $t_mail . ".pem")) && (file_exists(CRT_DIR . "/private/" . $t_mail . ".pem")))
		{
		$crt = file_get_contents(CRT_DIR . "/certs/" . $t_mail . ".pem");
		$key = file_get_contents(CRT_DIR . "/private/" . $t_mail . ".pem");

		file_put_contents("/tmp/" . $file . ".enc", $mime);

		if(openssl_pkcs7_decrypt("/tmp/" . $file . ".enc", "/tmp/" . $file . ".dec", $crt, array($key, "")) === false)
			$new_temp_message = $mime;
		elseif(openssl_pkcs7_verify("/tmp/" . $file . ".dec", PKCS7_NOVERIFY, "/tmp/" . $file . ".ver") === false)
			$new_temp_message = $mime;
		elseif(openssl_pkcs7_verify("/tmp/" . $file . ".dec", PKCS7_NOVERIFY, "/tmp/" . $file . ".ver", array(), "/tmp/" . $file . ".ver", "/tmp/" . $file . ".dec") === false)
			$new_temp_message = $mime;
		else
			{
			foreach(array("Content-Description", "Content-Disposition", "Content-Transfer-Encoding", "Content-Type", "Received") as $key)
				unset($head_parsed[$key]);

			$new_temp_message = array();

			foreach($head_parsed as $key => $val)
				$new_temp_message[] = $key . ": " . $val;

			$new_temp_message[] = "";
			$new_temp_message = file_get_contents("/tmp/" . $file . ".dec");

			$new_temp_message = implode("\n", $new_temp_message);
			}

		active_sync_mail_body_smime_cleanup();
		}
	else
		$new_temp_message = $mime;

	return($new_temp_message);
	}

function active_sync_mail_body_smime_encode($mime) # almost copy of sign
	{
	$file = active_sync_create_guid();

	$mail_struct = active_sync_mail_split($mime);

	$head_parsed = active_sync_mail_parse_head($mail_struct["head"]);

	list($t_name, $t_mail) = active_sync_mail_parse_address($head_parsed["To"]);

	if(file_exists(CRT_DIR . "/certs/" . $t_mail . ".pem"))
		{
		$new_temp_message = array();

		$new_temp_message[] = "Content-Type: " . $head_parsed["Content-Type"];
		$new_temp_message[] = "MIME-Version: 1.0";
		$new_temp_message[] = "";
		$new_temp_message[] = $mail_struct["body"];

		$new_temp_message = implode("\n", $new_temp_message);

		file_put_contents("/tmp/" . $file . ".dec", $mime);

		foreach(array("Content-Type", "MIME-Version") as $key)
			unset($head_parsed[$key]);

		$crt = file_get_contents(CRT_DIR . "/certs/" . $t_mail . ".pem");

		if(openssl_pkcs7_encrypt("/tmp/" . $file . ".dec", "/tmp/" . $file . ".enc", $crt, $head_parsed) === false)
			$new_temp_message = $mime;
		else
			$new_temp_message = file_get_contents("/tmp/" . $file . ".enc");

		active_sync_mail_body_smime_cleanup();
		}
	else
		$new_temp_message = $mime;

	return($new_temp_message);
	}

function active_sync_mail_body_smime_sign($mime) # almost copy of encode
	{
	$mail_struct = active_sync_mail_split($mime);

	$head_parsed = active_sync_mail_parse_head($mail_struct["head"]);

	list($f_name, $f_mail) = active_sync_mail_parse_address($head_parsed["From"]);

	if((file_exists(CRT_DIR . "/certs/" . $f_mail . ".pem")) && (file_exists(CRT_DIR . "/private/" . $f_mail . ".pem")))
		{
		$new_temp_message = array();

		$new_temp_message[] = "Content-Type: " . $head_parsed["Content-Type"];
		$new_temp_message[] = "MIME-Version: 1.0";
		$new_temp_message[] = "";
		$new_temp_message[] = $mail_struct["body"];

		$new_temp_message = implode("\n", $new_temp_message);

		$file = active_sync_create_guid();

		file_put_contents("/tmp/" . $file . ".dec", $new_temp_message);

		foreach(array("Content-Type", "MIME-Version") as $key)
			unset($head_parsed[$key]);

		$crt = file_get_contents(CRT_DIR . "/certs/" . $f_mail . ".pem");
		$key = file_get_contents(CRT_DIR . "/private/" . $f_mail . ".pem");

		if(openssl_pkcs7_sign("/tmp/" . $file . ".dec", "/tmp/" . $file . ".enc", $crt, $key, $head_parsed) === false)
			$new_temp_message = $mime;
		else
			$new_temp_message = file_get_contents("/tmp/" . $file . ".enc");

		active_sync_mail_body_smime_cleanup();
		}
	else
		$new_temp_message = $mime;

	return($new_temp_message);
	}

function active_sync_mail_convert_plain_to_html($data)
	{
	$data = str_replace("<", "&lt;", $data);
	$data = str_replace(">", "&gt;", $data);
	$data = str_replace(" ", "&nbsp;", $data);
	$data = str_replace("\r", "", $data);
	$data = str_replace("\n", "<br>", $data);

	$data = "<p>" . $data . "</p>";

	return($data);
	}

function active_sync_mail_convert_html_to_plain($data)
	{
	$data = str_replace("<br>", "\n", $data);
	$data = preg_replace("/<[^>]*>/", "", $data);
	$data = str_replace("&lt;", "<", $data);
	$data = str_replace("&gt;", ">", $data);
	$data = str_replace("&nbsp;", " ", $data);

	return($data);
	}

function active_sync_mail_count($user, $collection_id)
	{
	$retval = array(0, 0);

	foreach(glob(DAT_DIR . "/" . $user . "/" . $collection_id . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		if(isset($data["Email"]["Read"]) === false)
			$retval[0] ++;
		elseif($data["Email"]["Read"] == 0)
			$retval[0] ++;
		elseif($data["Email"]["Read"] == 1)
			$retval[1] ++;
		else
			$retval[1] ++;
		}

	return($retval);
	}

function active_sync_mail_file_size($size)
	{
	$unit = 0;

	while(($size < 1000) === false)
		{
		$size = $size / 1024;
		$unit ++;
		}

	if($unit > 11)
		$unit = 11;
	elseif($size < 10)
		$size = number_format($size, 2);
	elseif($size < 100)
		$size = number_format($size, 1);
	elseif($size < 1000)
		$size = number_format($size, 0);

	return($size . " " . implode(null, array_slice(array("Byte", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB", "NB", "DB", "??"), $unit, 1)));
	}

function active_sync_mail_header_decode_string($expression)
	{
	while(1)
		{
		if(strpos($expression, "=?") === false)
			break;

		list($a, $expression) = explode("=?", $expression, 2);

		if(strpos($expression, "?=") === false)
			break;

		list($expression, $b) = explode("?=", $expression, 2);

		if(strpos($expression, "?") === false)
			break;

		list($charset, $encoding, $string) = explode("?", $expression);

		$charset	= strtoupper($charset);
		$encoding	= strtoupper($encoding);

		if($encoding == "B")
			$string = base64_decode($string);

		if($encoding == "Q")
			{
			$string = quoted_printable_decode($string);

			$string = str_replace("_", " ", $string);
			}

		if($charset != "UTF-8")
			$string = utf8_encode($string);

		$expression = $a . $string . $b;
		}

	return($expression);
	}

function active_sync_mail_header_parameter_decode($string, $search = "")
	{
	$data = array();

	while(strlen($string) > 0)
		{
		if(strpos($string, ";") === false)
			break;

		list($line, $string) = explode(";", strrev($string), 2);

		list($line, $string) = array(strrev($line), strrev($string));

		list($line, $string) = array(trim($line), trim($string));

		if(strlen($line) == 0)
			continue;

		if(strpos($line, "=") === false)
			{
			$data[$line] = 1;

			continue;
			}

		list($key, $val) = explode("=", $line, 2);

		list($key, $val) = array(trim($key), trim($val));

		$val = active_sync_mail_header_parameter_trim($val);

		$data[$key] = $val;
		}

	if($search == "")
		$retval = $string;
	elseif(isset($data[$search]) === false)
		$retval = "";
	elseif($search == "charset")
		$retval = strtoupper($data[$search]); # this can be trap !!! utf-8 | UTF-8
	else
		$retval = $data[$search];

	return($retval);
	}
function active_sync_mail_header_parameter_trim($string)
	{
	if(strlen($string) < 2)
		{
		}
	elseif((substr($string, 0, 1) == '(') && (substr($string, 0 - 1) == ')')) # comment
		$string = substr($string, 1, 0 - 1);
	elseif((substr($string, 0, 1) == '"') && (substr($string, 0 - 1) == '"')) # display-name
		$string = substr($string, 1, 0 - 1);
	elseif((substr($string, 0, 1) == '<') && (substr($string, 0 - 1) == '>')) # mailbox
		$string = substr($string, 1, 0 - 1);

	return($string);
	}

function active_sync_mail_is_forward($subject)
	{
	$table = array();

	$table["da"] = array("VS");		# danish
	$table["de"] = array("WG");		# german
	$table["el"] = array("ΠΡΘ");		# greek
	$table["en"] = array("FW", "FWD");	# english
	$table["es"] = array("RV");		# spanish
	$table["fi"] = array("VL");		# finnish
	$table["fr"] = array("TR");		# french
	$table["he"] = array("הועבר");		# hebrew
	$table["is"] = array("FS");		# icelandic
	$table["it"] = array("I");		# italian
	$table["nl"] = array("Doorst");		# dutch
	$table["no"] = array("VS");		# norwegian
	$table["pl"] = array("PD");		# polish
	$table["pt"] = array("ENC");		# portuguese
	$table["ro"] = array("Redirecţionat");	# romanian
	$table["sv"] = array("VB");		# swedish
	$table["tr"] = array("İLT");		# turkish
	$table["zh"] = array("转发");		# chinese

	foreach($table as $language => $abbreviations)
		{
		foreach($abbreviations as $abbreviation)
			{
			$abbreviation = $abbreviation . ":";

			if(strtolower(substr($subject, 0, strlen($abbreviation))) != strtolower($abbreviation))
				continue;

			return(1);
			}
		}

	return(0);
	}

function active_sync_mail_is_reply($subject)
	{
	$table = array();

	$table["da"] = array("SV");		# danish
	$table["de"] = array("AW");		# german
	$table["el"] = array("ΑΠ", "ΣΧΕΤ");	# greek
	$table["en"] = array("RE");		# english
	$table["es"] = array("RE");		# spanish
	$table["fi"] = array("VS");		# finnish
	$table["fr"] = array("RE");		# french
	$table["he"] = array("תגובה");		# hebrew
	$table["is"] = array("SV");		# icelandic
	$table["it"] = array("R", "RIF");	# italian
	$table["nl"] = array("Antw");		# dutch
	$table["no"] = array("SV");		# norwegian
	$table["pl"] = array("Odp");		# polish
	$table["pt"] = array("RES");		# portuguese
	$table["ro"] = array("RE");		# romanian
	$table["sv"] = array("SV");		# swedish
	$table["tr"] = array("YNT");		# turkish
	$table["zh"] = array("回复");		# chinese

	foreach($table as $language => $abbreviations)
		{
		foreach($abbreviations as $abbreviation)
			{
			$abbreviation = $abbreviation . ":";

			if(strtolower(substr($subject, 0, strlen($abbreviation))) == strtolower($abbreviation))
				continue;

			return(1);
			}
		}

	return(0);
	}

function active_sync_mail_parse($user, $collection_id, $server_id, $mime)
	{
	$data = array();

	$data["AirSyncBase"]["NativeBodyType"] = 4;

	active_sync_mail_add_container_m($data, $mime);

	$mail_struct = active_sync_mail_split($mime);

	$head_parsed = active_sync_mail_parse_head($mail_struct["head"]);

	foreach(array("text/plain" => 1, "text/html" => 2, "application/rtf" => 3) as $content_type => $value)
		{
		if(isset($head_parsed["Content-Type"]) === false)
			continue;

		if($head_parsed["Content-Type"] != $content_type)
			continue;

		$data["AirSyncBase"]["NativeBodyType"] = $value;

		break;
		}

	if(isset($head_parsed["Date"]) === false)
		$data["Email"]["DateReceived"] = date("Y-m-d\TH:i:s.000\Z");
	else
		$data["Email"]["DateReceived"] = date("Y-m-d\TH:i:s.000\Z", strtotime($head_parsed["Date"]));

	if(isset($data["Email"]["Subject"]) === false)
		$data["Email"]["Subject"] = "...";

	foreach(array("ContentClass" => "urn:content-classes:message", "Importance" => 1, "MessageClass" => "IPM.Note", "Read" => 0) as $token => $value)
		$data["Email"][$token] = $value;

	foreach(array("low" => 0, "normal" => 1, "high" => 2) as $test => $importance)
		{
		if(isset($head_parsed["Importance"]) === false)
			continue;

		if($head_parsed["Importance"] != $test)
			continue;

		$data["Email"]["Importance"] = $importance;
		}

	foreach(array(5 => 0, 3 => 1, 1 => 2) as $test => $importance)
		{
		if(isset($head_parsed["X-Priority"]) === false)
			continue;

		if($head_parsed["X-Priority"] != $test)
			continue;

		$data["Email"]["Importance"] = $importance;
		}

	$translation_table = array();

	$translation_table["Email"] = array("From" => "From", "To" => "To", "Cc" => "Cc", "Subject" => "Subject", "ReplyTo" => "Reply-To");
	$translation_table["Email2"] = array("ReceivedAsBcc" => "Bcc", "Sender" => "Sender");

	foreach($translation_table as $codepage => $token_translation)
		{
		foreach($token_translation as $token => $field)
			{
			if(isset($head_parsed[$field]) === false)
				continue;

			if(strlen($head_parsed[$field]) == 0)
				continue;

			$data[$codepage][$token] = $head_parsed[$field];
			}
		}

#	$thread_topic = $data["Email"]["Subject"];

#	if(active_sync_mail_is_forward($thread_topic) == 1)
#		list($null, $thread_topic) = explode(":", $thread_topic, 2);

#	if(active_sync_mail_is_reply($thread_topic) == 1)
#		list($null, $thread_topic) = explode(":", $thread_topic, 2);

#	$data["Email"]["ThreadTopic"] = trim($thread_topic);

	active_sync_mail_parse_body($user, $collection_id, $server_id, $data, $head_parsed, $mail_struct["body"]);

	return($data);
	}

function active_sync_mail_parse_address($data, $localhost = "localhost")
	{
	list($null, $name, $mailbox, $comment) = array("", "", "", "");

	if($data == "")
		{
		}
#	elseif(preg_match("/\"(.*)\" \[MOBILE: (.*)\]/", $data, $matches) == 1)	# "name" [MOBILE: number]		!!! this is a special active sync construction for sending sms !!!
#		list($null, $name, $mailbox) = $matches;
	elseif(preg_match("/\"(.*)\" <(.*)>/", $data, $matches) == 1)		# "name" <mailbox>
		list($null, $name, $mailbox) = $matches;
	elseif(preg_match("/\"(.*)\" <(.*)> \((.*)\)/", $data, $matches) == 1)	# "name" <mailbox> (comment)
		list($null, $name, $mailbox, $comment) = $matches;
	elseif(preg_match("/(.*) <(.*)>/", $data, $matches) == 1)		# name <mailbox>
		list($null, $name, $mailbox) = $matches;
	elseif(preg_match("/(.*) <(.*)> \((.*)\)/", $data, $matches) == 1)	# name <mailbox> (comment)
		list($null, $name, $mailbox, $comment) = $matches;
	elseif(preg_match("/<(.*)>/", $data, $matches) == 1)			# <mailbox>
		list($null, $mailbox) = $matches;
	elseif(preg_match("/<(.*)> \((.*)\)/", $data, $matches) == 1)		# <mailbox> (comment)
		list($null, $mailbox, $comment) = $matches;
	elseif(preg_match("/(.*)/", $data, $matches) == 1)			# mailbox
		list($null, $mailbox) = $matches;

	$retval = array($name, $mailbox);

	return($retval);
	}

function active_sync_mail_parse_body($user, $collection_id, $server_id, & $data, $head_parsed, $body)
	{
	$content_transfer_encoding = "";

	if(isset($head_parsed["Content-Transfer-Encoding"]))
		$content_transfer_encoding = active_sync_mail_header_parameter_decode($head_parsed["Content-Transfer-Encoding"], "");

	$content_disposition = "";

	if(isset($head_parsed["Content-Disposition"]))
		$content_disposition = active_sync_mail_header_parameter_decode($head_parsed["Content-Disposition"], "");

	$content_type = "";
	$content_type_charset = "";
	$content_type_boundary = "";

	if(isset($head_parsed["Content-Type"]))
		{
		$content_type = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "");
		$content_type_charset = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "charset");
		$content_type_boundary = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "boundary");
		}

	if($content_transfer_encoding == "")
		{
		}
	elseif($content_transfer_encoding == "base64")
		$body = base64_decode($body);
	elseif($content_transfer_encoding == "7bit")
		{
		}
	elseif($content_transfer_encoding == "quoted-printable")
		$body = quoted_printable_decode($body);

	if($content_type == "")
		{
		if($content_type_charset != "UTF-8")
			$body = utf8_encode($body);

		$body_h = active_sync_mail_convert_plain_to_html($body);
		$body_p = $body;

		active_sync_mail_add_container_p($data, $body_p);

		active_sync_mail_add_container_h($data, $body_h);
		}
	elseif($content_disposition == "attachment")
		active_sync_mail_parse_body_part($user, $collection_id, $server_id, $data, $head_parsed, $body);
	elseif($content_disposition == "inline")
		active_sync_mail_parse_body_part($user, $collection_id, $server_id, $data, $head_parsed, $body);
	elseif($content_type == "multipart/alternative")
		{
		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index ++)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "multipart/mixed")
		{
		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index ++)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "multipart/related")
		{
		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index ++)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "multipart/report")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "REPORT.IPM.Note.NDR";

		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index ++)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "multipart/signed")
		{
		$body_parsed = active_sync_mail_parse_body_multipart($body, $content_type_boundary);

		for($index = 1; $index < count($body_parsed) - 1; $index ++)
			active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, $data, $body_parsed[$index]);
		}
	elseif($content_type == "application/pgp-signature")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME.MultipartSigned";
		}
	elseif($content_type == "application/pkcs7-mime")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME";
		}
	elseif($content_type == "application/pkcs7-signature")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME.MultipartSigned";
		}
	elseif($content_type == "application/rtf")
		active_sync_mail_add_container_r($data, $body);
	elseif($content_type == "application/x-pkcs7-mime")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME";
		}
	elseif($content_type == "application/x-pkcs7-signature")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME.MultipartSigned";
		}
	elseif($content_type == "text/calendar")
		active_sync_mail_add_container_c($data, $body, $user);
	elseif($content_type == "text/html")
		{
		if($content_type_charset != "UTF-8")
			$body = utf8_encode($body);

		$body_h = $body;
		$body_p = active_sync_mail_convert_html_to_plain($body);

		active_sync_mail_add_container_p($data, $body_p);
		active_sync_mail_add_container_h($data, $body_h);
		}
	elseif($content_type == "text/plain")
		{
		if($content_type_charset != "UTF-8")
			$body = utf8_encode($body);

		$body_h = active_sync_mail_convert_plain_to_html($body);
		$body_p = $body;

		active_sync_mail_add_container_p($data, $body_p);
		active_sync_mail_add_container_h($data, $body_h);
		}
	elseif($content_type == "text/x-vCalendar")
		active_sync_mail_add_container_c($data, $body, $user);
	else
		active_sync_mail_parse_body_part($user, $collection_id, $server_id, $data, $head_parsed, $body);
	}

function active_sync_mail_parse_body_multipart($body, $boundary)
	{
	$retval = array();

	$index = 0;

	$retval[$index] = "";

	while(strlen($body) > 0)
		{
		list($line, $body) = (strpos($body, "\n") === false ? array($body, "") : explode("\n", $body, 2));

		$line = str_replace("\r", "", $line);

		if(($line == "--" . $boundary) || ($line == "--" . $boundary . "--"))
			{
			$index ++;

			$retval[$index] = "";

			continue;
			}

		$retval[$index] .= $line . "\n";
		}

	return($retval);
	}

function active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, & $data, $mail)
	{
	$mail_struct = active_sync_mail_split($mail);

	$head_parsed = active_sync_mail_parse_head($mail_struct["head"]);

	active_sync_mail_parse_body($user, $collection_id, $server_id, $data, $head_parsed, $mail_struct["body"]);
	}


function active_sync_mail_parse_body_part($user, $collection_id, $server_id, & $data, $head_parsed, $body)
	{
	$content_description = "";

	if(isset($head_parsed["Content-Description"]))
		$content_description = active_sync_mail_header_parameter_decode($head_parsed["Content-Description"], "");

	$content_disposition = "";

	if(isset($head_parsed["Content-Disposition"]))
		$content_disposition = active_sync_mail_header_parameter_decode($head_parsed["Content-Disposition"], "");

	$content_id = "";

	if(isset($head_parsed["Content-ID"]))
		$content_id = active_sync_mail_header_parameter_trim($head_parsed["Content-ID"]);

	$content_type = "";
	$content_type_name = "";

	if(isset($head_parsed["Content-Type"]))
		{
		$content_type = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "");
		$content_type_name = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "name");
		}

	if($content_type_name == "")
		{
		foreach(range(0, 9) as $i)
			{
			$temp = active_sync_mail_header_parameter_decode($head_parsed["Content-Type"], "name*" . $i . "*");

			$temp = (substr($temp, 0, 10) == "ISO-8859-1" ? utf8_encode(urldecode(substr($temp, 12))) : $temp);

			$content_type_name = $content_type_name . $temp;
			}
		}

	if($content_type == "")
		{
		$data["Email"]["ContentClass"]		= "urn:content-classes:message";
		$data["Email"]["MessageClass"]		= "IPM.Note";
		}
	elseif($content_type == "audio/wav")
		{
		$data["Email"]["ContentClass"]		= "urn:content-classes:message";
		$data["Email"]["MessageClass"]		= "IPM.Note.Microsoft.Voicemail";

#		$data["Attachments"][$reference]["Email2"]["UmAttDuration"]	= 1;
#		$data["Attachments"][$reference]["Email2"]["UmAttOrder"]	= 1;
#		$data["Attachments"][$reference]["Email2"]["UmCallerID"]	= 0;
#		$data["Attachments"][$reference]["Email2"]["UmUserNotes"]	= "...";
		}
	elseif($content_type == "text/plain")
		{
		$data["Email"]["ContentClass"]		= "urn:content-classes:message";
		$data["Email"]["MessageClass"]		= "IPM.Note";
		}
	elseif($content_type == "text/html")
		{
		$data["Email"]["ContentClass"]		= "urn:content-classes:message";
		$data["Email"]["MessageClass"]		= "IPM.Note";
		}
	elseif($content_type == "text/x-vCalendar")
		{
		$data["Email"]["ContentClass"]		= "urn:content-classes:calendarmessage";
		$data["Email"]["MessageClass"]		= "IPM.Notification.Meeting";
		}

	$reference = active_sync_create_guid();

	$data["Attachments"][] = array
		(
		"AirSyncBase" => array
			(
			"ContentId" => $content_id,
			"IsInline" => ($content_disposition == "inline" ? 1 : 0),
			"DisplayName" => ($content_description == "" ? "..." : $content_description),
			"EstimatedDataSize" => strlen($body),
			"FileReference" => $user . ":" . $collection_id . ":" . $server_id . ":" . $reference,
			"Method" => ($content_disposition == "inline" ? 6 : 1)
			)
		);

	$data["File"][$reference] = array
		(
		"AirSyncBase" => array
			(
			"ContentType" => $content_type
			),
		"ItemOperations" => array
			(
			"Data" => base64_encode($body)
			)
		);
	}

function active_sync_mail_parse_head($head)
	{
	$retval = array();

	while(strlen($head) > 0)
		{
		list($line, $head) = (strpos($head, "\n") === false ? array($head, "") : explode("\n", $head, 2));

		if(strlen($line) == 0)
			break;

		if(strpos($line, ":") === false)
			continue;

		list($key, $val) = (strpos($line, ":") === false ? array($line, "") : explode(":", $line, 2));

		list($key, $val) = array(trim($key), trim($val));


		if(strtolower($key) == "received")
			{
			$retval[$key][] = $val;

			continue;
			}

		if(strtolower($key) == "subject")
			{
			$retval[$key] = active_sync_mail_header_decode_string($val);

			continue;
			}

		if(strtolower($key) == "x-auto-response-suppress")
			{
			$retval[$key] = explode(",", $val);

			continue;
			}

		$retval[$key] = $val;
		}

	return($retval);
	}

function active_sync_mail_signature_save($data, $body)
	{
	list($name, $mail) = active_sync_mail_parse_address($data["Email"]["From"]);

	$crt = CRT_DIR . "/certs/" . $mail . ".pem";

	if(file_exists($crt) === false)
		{
		$body = base64_encode($body);

		$body = chunk_split($body , 64, "\n");

		$body = substr($body, 0, 0 - 1);

		$body = array("-----BEGIN PKCS7-----", $body, "-----END PKCS7-----");

		$body = implode("\n", $body);

		file_put_contents($crt, $body);

		exec("openssl pkcs7 -in " . $crt . " -out " . $crt . " -text -print_certs", $output, $return_var);

		$body = file_get_contents($crt);

		list($null, $body) = explode("-----BEGIN CERTIFICATE-----", 2);
		list($body, $null) = explode("-----END CERTIFICATE-----", 2);

		$body = array("-----BEGIN CERTIFICATE-----", $body, "-----END CERTIFICATE-----");

		$body = implode("\n", $body);

		file_put_contents($crt, $body);
		}
	}

function active_sync_mail_split($mail)
	{
	$head = array();

	while(strlen($mail) > 0)
		{
		list($line, $mail) = (strpos($mail, "\n") === false ? array($mail, "") : explode("\n", $mail, 2));

		$line = str_replace("\r", "", $line);

		if(strlen($line) == 0)
			break;

		$head[] = $line;
		}

	$head = implode("\n", $head); # !!! we expect empty line later

	$head = str_replace(array("\x0D", "\x0A\x09", "\x0A\x20"), array("", "\x20", "\x20"), $head);

	return(array("head" => $head, "body" => $mail));
	}

function active_sync_maildir_create($user = "root")
	{
	$path = active_sync_postfix_virtual_mailbox_base();

	foreach(array("/cur", "/new", "/tmp") as $dir)
		{
		if(is_dir($path . "/" . $user . $dir))
			continue;

		mkdir($path . "/" . $user . $dir, 0777, true);

#		chown($path . "/" . $user . $dir, "mail");
#		chgrp($path . "/" . $user . $dir, "mail");
#		chmod($path . "/" . $user . $dir, octmode(777));
		}
	}

function active_sync_maildir_delete($user)
	{
	$path = active_sync_postfix_virtual_mailbox_base();

	active_sync_maildir_delete_recursive($path . "/" . $user);
	}

function active_sync_maildir_delete_recursive($folder)
	{
	foreach(scandir($folder) as $file)
		{
		if(($file == ".") || ($file == ".."))
			continue;

		if(is_dir($folder . "/" . $file))
			active_sync_maildir_delete_recursive($folder . "/" . $file);
		else
			unlink($folder . "/" . $file);
		}

	rmdir($folder);
	}

function active_sync_maildir_exists($user)
	{
	$path = active_sync_postfix_virtual_mailbox_base();

	return(is_dir($path . "/" . $user) ? 1 : 0);
	}

function active_sync_maildir_sync()
	{
	$host = active_sync_get_domain();
	$version = active_sync_get_version();

	$users = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($users["login"] as $user_id => $null)
		{
		if(file_exists(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".mdl"))
			continue;

		touch(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".mdl");

		$list = active_sync_get_settings(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".mds");

		$oof = active_sync_get_settings(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".sync");

		$maildir = active_sync_postfix_virtual_mailbox_base() . "/" . $users["login"][$user_id]["User"] . "/new";
#		$maildir = exec("postconf -h virtual_mailbox_base") . "/" . $users["login"][$user_id]["User"] . "/new";

		foreach($list as $server_id => $null)
			{
			if(file_exists($maildir . "/" . $server_id) === false)
				{
				if(file_exists(DAT_DIR . "/" . $users["login"][$user_id]["User"] . "/" . active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2) . "/" . $server_id . ".data") !== false)
					unlink(DAT_DIR . "/" . $users["login"][$user_id]["User"] . "/" . active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2) . "/" . $server_id . ".data");

				unset($list[$server_id]);
				}

			if(file_exists(DAT_DIR . "/" . $users["login"][$user_id]["User"] . "/" . active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2) . "/" . $server_id . ".data") === false)
				{
				if(file_exists($maildir . "/" . $server_id) !== false)
					unlink($maildir . "/" . $server_id);

				unset($list[$server_id]);
				}
			}

		foreach(scandir($maildir) as $file)
			{
			if(is_dir($maildir . "/" . $file))
				continue;

			if(file_exists(DAT_DIR . "/" . $users["login"][$user_id]["User"] . "/" . active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2) . "/" . $file . ".data"))
				continue;

			$mime = file_get_contents($maildir . "/" . $file);

			$data = active_sync_mail_parse($users["login"][$user_id]["User"], active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2), $file, $mime);

			active_sync_put_settings_data($users["login"][$user_id]["User"], active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 2), $file, $data);

			if(($oof["OOF"]["OofState"] == 1) || (($oof["OOF"]["OofState"] == 2) && ((time() > strtotime($oof["OOF"]["StartTime"])) && (time() < strtotime($oof["OOF"]["EndTime"])))))
				{
				list($from_name, $from_mail) = active_sync_mail_parse_address($data["Email"]["From"]);
				list($from_user, $from_host) = explode("@", ($from_mail ? $from_mail : "@"), 2);

				$from_mail = strtolower($from_mail);
				$from_user = strtolower($from_user);
				$from_host = strtolower($from_host);

				list($to_name, $to_mail) = active_sync_mail_parse_address($data["Email"]["To"]);		# check for multiple recipients
				list($to_user, $to_host) = explode("@", ($to_mail ? $to_mail : "@"), 2);			# check for undisclosed recipient !!!

				$to_mail = strtolower($to_mail);
				$to_user = strtolower($to_user);
				$to_host = strtolower($to_host);

				$old_mime_message = "";

				foreach($data["Body"] as $id => $null)
					{
					if(isset($data["Body"][$id]["Type"]) === false)
						continue;

					if($data["Body"][$id]["Type"] != 4) # Mime
						continue;

					$old_mime_message = $data["Body"][$id]["Data"];
					}

				list($head, $body) = active_sync_mail_split($old_mime_message);

				$head_parsed = active_sync_mail_parse_head($head);

				$reply_message = "";

				if(isset($head_parsed["X-Auto-Response-Suppress"]["OOF"]))
					continue;

				if(in_array($from_mail, array("", $to_mail)))
					continue;

				if(in_array($from_user, array("mailer-daemon", "no-reply", "root", "wwwrun", "www-run", "wwww-data", "www-user", "mail", "noreply", "postfix")))
					continue;

				if(($oof["OOF"]["AppliesToInternal"]["Enabled"] == 1) && ($from_host == $to_host))
					$reply_message = $oof["OOF"]["OOF"]["AppliesToInternal"]["ReplyMessage"];
				elseif(($oof["OOF"]["AppliesToExternalKnown"]["Enabled"] == 1) && ($from_host != $to_host) && (active_sync_get_is_known_mail($users["login"][$user_id]["User"], active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 9), $from_mail) == 1))
					$reply_message = $oof["OOF"]["AppliesToExternalKnown"]["ReplyMessage"];
				elseif(($oof["OOF"]["AppliesToExternalUnknown"]["Enabled"] == 1) && ($from_host != $to_host) && (active_sync_get_is_known_mail($users["login"][$user_id]["User"], active_sync_get_collection_id_by_type($users["login"][$user_id]["User"], 9), $from_mail) == 0))
					$reply_message = $oof["OOF"]["AppliesToExternalUnknown"]["ReplyMessage"];

				if(strlen($reply_message) == 0)
					continue;

				################################################################################

				# 0x00000001	DR		Suppress delivery reports from transport.
				# 0x00000002	NDR		Suppress non-delivery reports from transport.
				# 0x00000004	RN		Suppress read notifications from receiving client.
				# 0x00000008	NRN		Suppress non-read notifications from receiving client.
				# 0x00000010	OOF		Suppress Out of Office (OOF) notifications.
				# 0x00000020	AutoReply	Suppress auto-reply messages other than OOF notifications.

				$new_mime_message = array
					(
					"From: " . ($to_name ? "\"" . $to_name . "\" <" . $to_user . "@" . $to_host . ">" : $to_user . "@" . $to_host),
					"To: " . ($from_name ? "\"" . $from_name . "\" <" . $from_user . "@" . $from_host . ">" : $from_user . "@" . $from_host),
					"Subject: OOF: " . $data["Email"]["Subject"],
					"Reply-To: " . $to_user . "@" . $to_host,
					"Auto-Submitted: auto-generated",
					"Message-ID: <" . active_sync_create_guid() . "@" . $host . ">",
					"X-Auto-Response-Suppress: " . implode(", ", array("DR", "NDR", "RN", "NRN", "OOF", "AutoReply")), # we do not want anything
					"X-Mailer: " . $version,
					"",
					$reply_message
					);

				$new_mime_message = implode("\n", $new_mime_message);

				active_sync_send_mail($users["login"][$user_id]["User"], $new_mime_message);
				}

			$list[$file] = filemtime($maildir . "/" . $file);
			}

		active_sync_put_settings(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".mds", $list);

		@ unlink(DAT_DIR . "/" . $users["login"][$user_id]["User"] . ".mdl");
		}
	}

function active_sync_openssl_get_key_list()
	{
	$settings = active_sync_get_settings_cert();

	$retval = array();

	foreach(glob(CRT_DIR . "/private/*.pem") as $file)
		{
		$name_key = basename($file, ".pem");

		$file_key = CRT_DIR . "/private/" . $name_key . ".pem";

		$pass = (isset($settings["private"][$name_key]) ? $settings["private"][$name_key] : "");

		$key = file_get_contents($file_key);

		$data = openssl_pkey_get_private($key, $pass);

		$retval[$name_key] = openssl_pkey_get_details($data);
		}

	if(count($retval) > 1)
		ksort($retval);

	return($retval);
	}

function active_sync_openssl_get_name_by_serial($serial)
	{
	$retval = "";

	foreach(glob(CRT_DIR . "/certs/*.pem") as $file)
		{
		$name_key = basename($file, ".pem");

		$data = openssl_x509_parse(file_get_contents($file));

		if(bccomp($data["serialNumber"], $serial) == 0)
			{
			$retval = $name_key;

			break;
			}
		}

	return($retval);
	}

function active_sync_openssl_is_key_in_use($expression)
	{
	$settings = active_sync_get_settings_cert();

	foreach(array("requests", "certs") as $type)
		if(isset($settings[$type]))
			foreach($settings[$type] as $name => $key)
				if($key == $expression)
					return(1);

	return(0);
	}

function active_sync_postfix_config($setting, $default = "")
	{
#	return(exec("sudo postconf -h " . $setting));

	$file = "/etc/postfix/main.cf";

	$data = (file_exists($file) ? file($file) : array());

	foreach($data as $id => $line)
		{
		$line = trim($line);

		list($line, $comment) = (strpos($line, "#") === false ? array($line, "") : explode("#", $line, 2));

		list($line, $comment) = array(trim($line), trim($comment));

		list($key, $value) = (strpos($line, "=") === false ? array("", "") : explode("=", $line, 2));

		list($key, $value) = array(trim($key), trim($value));

		if($key == $setting)
			return($value);
		}

	return($default);
	}

function active_sync_postfix_config_a()
	{
	$file = "/etc/postfix/main.cf";

	$data = (file_exists($file) ? file($file) : array());

	$r = array();

	foreach($data as $id => $line)
		{
		$line = trim($line);

		list($line, $comment) = (strpos($line, "#") === false ? array($line, "") : explode("#", $line, 2));

		list($line, $comment) = array(trim($line), trim($comment));

		list($key, $value) = (strpos($line, "=") === false ? array("", "") : explode("=", $line, 2));

		list($key, $value) = array(trim($key), trim($value));

		if($key == "")
			continue;

		if($value == "")
			continue;

		$r[$key] = $value;
		}

	return($r);
	}

function active_sync_postfix_virtual_alias_maps_db()
	{
	$file = active_sync_postfix_config("virtual_alias_maps", "hash:/etc/postfix/virtual_alias_maps");

	list($type, $file) = explode(":", $file, 2);

	return($file);
	}

function active_sync_postfix_virtual_alias_maps_exists($user)
	{
	$host = active_sync_get_domain();

	$file = active_sync_postfix_virtual_alias_maps_db();

	$data = (file_exists($file) ? file($file) : array());

	foreach($data as $id => $line)
		{
		list($key, $val) = (strpos($line, " ") === false ? array($line, "") : explode(" ", $line, 2));

		if(trim($key) == $user . "@" . $host)
			return(1);
		}

	return(0);
	}

function active_sync_postfix_virtual_mailbox_base()
	{
	$path = active_sync_postfix_config("virtual_mailbox_base", "/var/mail/virtual_mailbox_base");

	return($path);
	}

function active_sync_postfix_virtual_mailbox_maps_create($user)
	{
	$host = active_sync_get_domain();

	$file = active_sync_postfix_virtual_mailbox_maps_db();

	$data = (file_exists($file) ? file($file) : array());

	$data[] = $user . "@" . $host . " " . $user . "/" . "\n";

	exec("sudo chmod 0666 " . $file);
	file_put_contents($file, implode("", $data));
	exec("sudo chmod 0644 " . $file);

	exec("sudo postmap " . $file);
	exec("sudo /etc/init.d/postfix reload");

	return(1);
	}

function active_sync_postfix_virtual_mailbox_maps_db()
	{
	$file = active_sync_postfix_config("virtual_mailbox_maps", "hash:/etc/postfix/virtual_mailbox_maps");

	list($type, $file) = explode(":", $file, 2);

	return($file);
	}

function active_sync_postfix_virtual_mailbox_maps_delete($user)
	{
	$host = active_sync_get_domain();

	$file = active_sync_postfix_virtual_mailbox_maps_db();

	$data = (file_exists($file) ? file($file) : array());

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

function active_sync_postfix_virtual_mailbox_maps_exists($user)
	{
	$host = active_sync_get_domain();

	$file = active_sync_postfix_virtual_mailbox_maps_db();

	$data = (file_exists($file) ? file($file) : array());

	foreach($data as $id => $line)
		{
		list($key, $val) = (strpos($line, " ") === false ? array($line, "") : explode(" ", $line, 2));

		if(trim($key) == $user . "@" . $host)
			return(1);
		}

	return(0);
	}

function active_sync_put_attendee_status($user, $server_id, $email, $attendee_status)
	{
	$collection_id = active_sync_get_collection_id_by_type($user, 8); # Calendar

	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	if(isset($data["Attendees"]))
		{
		foreach($data["Attendees"] as $id => $attendee)
			{
			if(isset($attendee["Email"]) === false)
				continue;

			if($attendee["Email"] != $email)
				continue;

			$data["Attendees"][$id]["AttendeeStatus"] = $attendee_status;

			active_sync_put_settings_data($user, $collection_id, $server_id, $data);

			return(1);
			}
		}

	return(0);
	}

function active_sync_put_display_name($user, $server_id, $display_name)
	{
	$folders = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($folders["SyncDat"] as $id => $folder)
		{
		if($folder["ServerId"] != $server_id)
			continue;

		$folders["SyncDat"][$id]["DisplayName"] = $display_name;

		active_sync_put_settings(DAT_DIR . "/" . $user . ".sync", $folders);

		return(1);
		}

	return(0);
	}

function active_sync_put_parent_id($user, $server_id, $parent_id)
	{
	$folders = active_sync_get_settings(DAT_DIR . "/" . $user . ".sync");

	foreach($folders["SyncDat"] as $id => $folder)
		{
		if($folder["ServerId"] != $server_id)
			continue;

		$folders["SyncDat"][$id]["ParentId"] = $parent_id;

		active_sync_put_settings(DAT_DIR . "/" . $user . ".sync", $folders);

		return(1);
		}

	return(0);
	}

function active_sync_put_settings($file, $data)
	{
#	$data = serialize($data);
	$data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	return(file_put_contents($file, $data));
	}

function active_sync_put_settings_data($user, $collection_id, $server_id, $data)
	{
	return(active_sync_put_settings(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data", $data));
	}

function active_sync_put_settings_sync($user, $collection_id, $device_id, $data)
	{
	# server will never save timestamps to any file.
	# timestamp will always be read from real files

	return(active_sync_put_settings(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $device_id . ".sync", $data));
	}

function active_sync_send_mail($user, $mime)
	{
	$host = active_sync_get_domain(); # needed for user@host

#	$mime = active_sync_mail_body_smime_sign($mime);
#	$mime = active_sync_mail_body_smime_encode($mime);

	$mail_struct = active_sync_mail_split($mime); # head, body

	$head_parsed = active_sync_mail_parse_head($mail_struct["head"]);

	$additional_headers = array();

	foreach($head_parsed as $key => $val)
		{
		if(($key == "Received") || ($key == "Subject") || ($key == "To"))
			continue;

		$additional_headers[] = implode(": ", array($key, $val));
		}

	# don't we need a recipient here? by settting to null we got an empty field.

	mail($head_parsed["To"], (isset($head_parsed["Subject"]) === false ? "" : $head_parsed["Subject"]), $mail_struct["body"], implode("\n", $additional_headers), "-f no-reply@" . $host);
	}

function active_sync_send_sms($user, $mime)
	{
/*
	$output = array();

	exec("uuid -v4", $output);

	$data = array
		(
		"AirSync" => array
			(
			"Class" => "SMS"
			),
		"Email" => array
			(
			"DateReceived" => date("Y-m-d\TH:i:s.000\Z"),
			"Read" => 1,
			"From" => "[MOBILE: " . $number . "]",
			"To" => "[MOBILE: " . $number . "]"
			),
		"Body" => array
			(
			array
				(
				"Type" => 1,
				"EstimatedDataSize" => strlen($text),
				"Data" => $text
				)
			)
		);

	active_sync_put_settings_data($user, "9006", $output[0], $data);
*/
	}

function active_sync_systemtime_decode($expression)
	{
	# Year ::= 1601 .. 30827
	# Month ::= 1 .. 12
	# DayOfWeek ::= 0 .. 6

	$retval = unpack("SYear/SMonth/SDayOfWeek/SDay/SHour/SMinute/SSecond/SMilliseconds", $expression);

	return($retval);
	}

function active_sync_systemtime_encode($Year, $Month, $DayOfWeek, $Day, $Hour, $Minute, $Second, $Milliseconds)
	{
	# Year ::= 1601 .. 30827
	# Month ::= 1 .. 12
	# DayOfWeek ::= 0 .. 6

	return(pack("SSSSSSSS", $Year, $Month, $DayOfWeek, $Day, $Hour, $Minute, $Second, $Milliseconds));
	}

function active_sync_time_zone_information_decode($expression)
	{
	$retval = unpack("lBias/a64StandardName/A16StandardDate/lStandardBias/a64DaylightName/A16DaylightDate/lDaylightBias", $expression);

	return($retval);
	}

function active_sync_time_zone_information_encode($Bias, $StandardName, $StandardDate, $StandardBias, $DaylightName, $DaylightDate, $DaylightBias)
	{
	$retval = pack("la64A16la64A16l", $Bias, $StandardName, $StandardDate, $StandardBias, $DaylightName, $DaylightDate, $DaylightBias);

	return($retval);
	}

function active_sync_user_exist($expression)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($settings["login"] as $user)
		{
		if($user["User"] != $expression)
			continue;

		return(1);
		}

	return(0);
	}

function active_sync_vcalendar_parse(& $data)
	{
	$retval = array();

	$data = str_replace(array("\x0D", "\x0A\x09", "\x0A\x20"), array("", "", ""), $data);

	while(strlen($data) > 0)
		{
		list($line, $data) = (strpos($data, "\n") === false ? array($data, "") : explode("\n", $data, 2));

		if(strlen($line) == 0)
			continue;

		if(strpos($line, ":") === false)
			continue;

		list($key, $val) = explode(":", $line, 2);

		if($key == "BEGIN")
			{
			$retval[$val] = active_sync_vcalendar_parse($data);

			continue;
			}

		if($key == "END")
			break;

		$opt = array();

		while(strpos($key, ";"))
			{
			$par = substr($key, strrpos($key, ";") + 1);
			$key = substr($key, 0, strrpos($key, ";"));

			if(strlen($par) == 0)
				continue;

			if(strpos($par, "=") === false)
				{
				$opt[$par] = 1;

				continue;
				}

			list($par_key, $par_val) = explode("=", $par, 2);

			$opt[$par_key] = $par_val;
			}

		if($key == "ATTENDEE")
			{
			list($proto, $email) = explode(":", $val);

			$retval[$key][$email] = $opt;

			continue;
			}

		if($key == "ORGANIZER")
			{
			list($proto, $email) = explode(":", $val);

			$retval[$key][$email] = $opt;

			continue;
			}

		if($key == "RRULE")
			{
			foreach(explode(";", $val) as $par)
				{
				list($par_key, $par_val) = explode("=", $par);

				$retval[$key][$par_key] = $par_val;
				}

			continue;
			}

		if($key == "CATEGORIES")
			$val = explode("\,", $val);

		$retval[$key] = $val;
		}

	return($retval);
	}

function active_sync_vcard_from_data($user, $collection_id, $server_id, $version = 21)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	$version = ($version == 40 ? 40 : $version);
	$version = ($version == 30 ? 30 : $version);
	$version = ($version == 21 ? 21 : $version);

	$retval = array();

	$retval[] = implode(":", array("BEGIN", "VCARD"));
	$retval[] = implode(":", array("VERSION", number_format($version / 10, 1, ".", "")));
	$retval[] = implode(":", array("REV", date("Y-m-d\TH:i:s\Z", filemtime(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data"))));
	$retval[] = implode(":", array("UID", $server_id));

	foreach(array("FileAs" => "FN", "Email1Address" => "EMAIL", "Email2Address" => "EMAIL", "Email3Address" => "EMAIL", "JobTitle" => "ROLE", "WebPage" => "URL", "Birthday" => "BDAY", "ManagerName" => "MANAGER", "Spouse" => "SPOUSE", "AssistantName" => "ASSISTANT", "Anniversary" => "ANNIVERSARY") as $token => $key)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		$retval[] = implode(":", array($key, $data["Contacts"][$token]));
		}

	if($version == 21)
		$fields = array("BusinessFaxNumber" => "WORK;FAX", "HomeFaxNumber" => "HOME;FAX", "MobilePhoneNumber" => "CELL", "PagerNumber" => "PAGER", "HomePhoneNumber" => "HOME", "BusinessPhoneNumber" => "WORK", "CarPhoneNumber" => "CAR");

	if($version == 30)
		$fields = array("BusinessFaxNumber" => "TYPE=WORK,FAX", "HomeFaxNumber" => "TYPE=HOME,FAX", "MobilePhoneNumber" => "TYPE=CELL", "PagerNumber" => "TYPE=PAGER", "HomePhoneNumber" => "TYPE=HOME,VOICE", "BusinessPhoneNumber" => "TYPE=WORK,VOICE", "CarPhoneNumber" => "TYPE=CAR");

	if($version == 40)
		$fields = array("BusinessFaxNumber" => "TYPE=work,fax", "HomeFaxNumber" => "TYPE=home,fax", "MobilePhoneNumber" => "TYPE=cell", "PagerNumber" => "TYPE=pager", "HomePhoneNumber" => "TYPE=home,voice", "BusinessPhoneNumber" => "TYPE=work,voice", "CarPhoneNumber" => "TYPE=car");

	foreach($fields as $token => $key)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		$retval[] = implode(":", array("TEL;" . $key, $data["Contacts"][$token]));
		}

	$x = array();

	foreach(array("CompanyName", "Department", "OfficeLocation") as $token)
		$x[] = (isset($data["Contacts"][$token]) ? str_replace(";", "\;", $data["Contacts"][$token]) : "");

	if(strlen(implode("", $x)) > 0)
		$retval[] = implode(":", array("ORG", implode(";", $x)));

	$x = array();

	foreach(array("LastName", "FirstName", "MiddleName", "Title", "Suffix") as $token)
		$x[] = (isset($data["Contacts"][$token]) ? str_replace(";", "\;", $data["Contacts"][$token]) : "");

	if(strlen(implode("", $x)) > 0)
		$retval[] = implode(":", array("N", implode(";", $x)));

	if(isset($data["Contacts2"]["NickName"]))
		$retval[] = "NICKNAME" . ":" . $data["Contacts2"]["NickName"];

	foreach(array("Business" => "WORK", "Home" => "HOME", "Other" => "OTHER") as $token_prefix => $type)
		{
		$x = array("", "");

		foreach(array("Street", "City", "State", "PostalCode", "Country") as $token_suffix)
			$x[] = (isset($data["Contacts"][$token_prefix . "Address" . $token_suffix]) ? str_replace(";", "\;", $data["Contacts"][$token_prefix . "Address" . $token_suffix]) : "");

		if(strlen(implode("", $x)) == 0)
			continue;

		if($version == 21)
			$retval[] = implode(":", array("ADR" . ";" . strtoupper($type), implode(";", $x)));

		if($version == 30)
			$retval[] = implode(":", array("ADR" . ";" . "TYPE=" . strtoupper($type), implode(";", $x)));

		if($version == 40)
			$retval[] = implode(":", array("ADR" . ";" . "TYPE=" . strtolower($type), implode(";", $x)));
		}

	if(isset($data["Body"]["Data"]))
		$retval[] = implode(":", array("NOTE", str_replace(array("\r", "\n"), array("", "\\n"), $data["Body"]["Data"])));

	if(isset($data["Categories"]))
		{
		$x = array();

		foreach($data["Categories"] as $category)
			$x[] = str_replace(",", "\,", $category);

		if(strlen(implode(",", $x)) > 0)
			$retval[] = implode(":", array("CATEGORIES", implode(",", $x)));
		}

	foreach(array("IMAddress", "IMAddress2", "IMAddress3") as $token)
		{
		if(isset($data["Contacts2"][$token]) === false)
			continue;

		if(strpos($data["Contacts2"][$token], ":") === false)
			continue;

		list($proto, $address) = explode(":", $data["Contacts2"][$token], 2);

		$retval[] = implode(":", array("X-" . strtoupper($proto), $address));
		}

	if(isset($data["Contacts"]["Picture"]))
		{
		$magic = $data["Contacts"]["Picture"];
		$magic = substr($magic, 0, 12);
		$magic = base64_decode($magic);

		if(substr($magic, 0, 2) == "BM")
			$format = "BMP";
		elseif(substr($magic, 0, 3) == "GIF")
			$format = "GIF";
		elseif(substr($magic, 1, 3) == "PNG")
			$format = "PNG";
		elseif(substr($magic, 6, 4) == "JFIF")
			$format = "JPEG";
		else
			$format = "UNKNOWN";

		if($version == 21)
			$retval[] = implode(":", array(implode(";", array("PHOTO", strtoupper($format), "ENCODING=BASE64")), $data["Contacts"]["Picture"]));

		if($version == 30)
			$retval[] = implode(":", array(implode(";", array("PHOTO", "TYPE=" . strtoupper($format), "ENCODING=B")), $data["Contacts"]["Picture"]));

		if($version == 40)
			$retval[] = implode(":", array("PHOTO", implode(";", array("data:image/" . strtolower($format), "BASE64" . $data["Contacts"]["Picture"]))));
		}

#	$retval[] = implode(":", array("X-ANDROID-CUSTOM", implode(";", array("vnd.android.cursor.item/relation", $data["Contacts"]["Spouse"], 14, "", "", "" "", "", "", "", "", "", "", "", "", ""))));
#	$retval[] = implode(":", array("X-ANDROID-CUSTOM", implode(";", array("vnd.android.cursor.item/nickname", $data["Contacts2"]["NickName"], 1, "", "", "" "", "", "", "", "", "", "", "", "", ""))));
	$retval[] = implode(":", array("SOURCE", "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]));
	$retval[] = implode(":", array("END", "VCARD"));

	foreach($retval as $id => $line)
		{
		$retval[$id] = chunk_split($retval[$id], 74, "\n ");
		$retval[$id] = substr($retval[$id], 0, 0 - 2);
		}

	return(implode("\n", $retval));
	}

function active_sync_wbxml_get_charset_id_by_name($expression)
	{
	foreach(range(0x0000, 0xFFFF) as $id)
		if(active_sync_wbxml_get_charset_name_by_id($id) == $name)
			return($id);

	return(99);
	}

function active_sync_wbxml_get_charset_name_by_id($id)
	{
	$table = active_sync_wbxml_table_charset();

	return(isset($table[$id]) ? $table[$id] : $id);
	}

function active_sync_wbxml_get_codepage_id_by_name($name)
	{
	foreach(range(0x00, 0x1F) as $id)
		if(active_sync_wbxml_get_codepage_name_by_id($id) == $name)
			return($id);

	return(99);
	}

function active_sync_wbxml_get_codepage_name_by_id($id)
	{
	$table = active_sync_wbxml_table_codepage();

	return(isset($table[$id & 0x1F]) ? $table[$id & 0x1F] : "unknown");
	}

function active_sync_wbxml_get_integer($input, & $position = 0)
	{
	$char = $input[$position ++];

	$byte = ord($char);

	return($byte);
	}

function active_sync_wbxml_get_multibyte_integer($input, & $position = 0)
	{
	$multi_byte = 0;

	while(1)
		{
		$char = $input[$position ++];

		$byte = ord($char);

	  	$multi_byte = $multi_byte | ($byte & 0x7F);

	  	if(($byte & 0x80) != 0x80)
			break;

		$multi_byte = $multi_byte << 7;
		}

	return($multi_byte);
	}

function active_sync_wbxml_get_public_identifier_id_by_name($expression)
	{
	$table = active_sync_wbxml_table_public_identifier();

	foreach($table as $id => $name)
		if($id == $expression)
			return($id);

	return(99);
	}

function active_sync_wbxml_get_public_identifier_name_by_id($id)
	{
	$table = active_sync_wbxml_table_public_identifier();

	return(isset($table[$id]) ? $table[$id] : $id);
	}

function active_sync_wbxml_get_string($input, & $position = 0)
	{
	$string = "";

	while(1)
		{
		$char = $input[$position ++];

		if($char == "\x00")
			break;

		$string = $string . $char;
		}

	return($string);
	}

function active_sync_wbxml_get_string_length($input, & $position = 0, $length = 0)
	{
	$string = substr($input, $position, $length);

	$position = $position + $length;

	return($string);
	}

function active_sync_wbxml_get_token_id_by_name($codepage, $token)
	{
	$codepage = (is_numeric($codepage) ? $codepage : active_sync_wbxml_get_codepage_id_by_name($codepage));

	foreach(range(0x05, 0x3F) as $id)
		if(active_sync_wbxml_get_token_name_by_id($codepage, $id) == $token)
			return($id);

	return(99);
	}

function active_sync_wbxml_get_token_name_by_id($codepage, $id)
	{
	$codepage = (is_numeric($codepage) ? $codepage : active_sync_wbxml_get_codepage_id_by_name($codepage));

	$table = active_sync_wbxml_table_token();

	return(isset($table[$codepage][$id & 0x3F]) ? $table[$codepage][$id & 0x3F] : "unknown");
	}

function active_sync_wbxml_pretty($expression)
	{
	if(strlen($expression) == 0)
		return("");

	$expression = simplexml_load_string($expression, "SimpleXMLElement", LIBXML_NOBLANKS | LIBXML_NOWARNING);

	if(isset($expression->Response->Fetch->Properties->Data))
		$expression->Response->Fetch->Properties->Data = "[PRIVATE DATA]";

	if(isset($expression->Response->Store->Result->Properties->Picture->Data))
		$expression->Response->Store->Result->Properties->Picture->Data = "[PRIVATE DATA]";

	if(isset($expression->Collections->Collection))
		foreach($expression->Collections->Collection as $collection)
			foreach(array("Add", "Change") as $action)
				if(isset($collection->Commands->$action))
					foreach($collection->Commands->$action as $whatever)
						$whatever->ApplicationData = "[PRIVATE DATA]";

	if(isset($expression->Policies->Policy->Data->EASProvisionDoc))
		$expression->Policies->Policy->Data->EASProvisionDoc = "[PRIVATE DATA]";

	if(isset($expression->RightsManagementInformation->Get->RightsManagementTemplates))
		$expression->RightsManagementInformation->Get->RightsManagementTemplates = "[PRIVATE DATA]";

	$expression = dom_import_simplexml($expression);
	$expression = $expression->ownerDocument;
	$expression->formatOutput = true;

	$expression = $expression->saveXML();

#	list($a, $b) = explode(">\n<", $expression, 2);
#	$expression = implode(">\n<!DOCTYPE AirSync PUBLIC \"-//AIRSYNC//DTD AirSync//EN\" \"http://www.microsoft.com/\">\n<", array($a, $b));

	return($expression);
	}

# this function returns data as string

function active_sync_wbxml_request_a($input, & $position = 0, $codepage = 0, $level = 0)
	{
	$buffer = array();

	if(strlen($input) == 0)
		return(implode("\n", $buffer));

	if($position == 0)
		{
		$version = active_sync_wbxml_get_integer($input, $position);

		$public_identifier = active_sync_wbxml_get_multibyte_integer($input, $position);

		$charset = active_sync_wbxml_get_multibyte_integer($input, $position);

		$string_table_length = active_sync_wbxml_get_multibyte_integer($input, $position);

#		$version = "1." . $version;

		$public_identifier = active_sync_wbxml_get_public_identifier_name_by_id($public_identifier);

		$charset = active_sync_wbxml_get_charset_name_by_id($charset);

		@ mb_internal_encoding($charset);

		$string_table = "";

		while(strlen($string_table) < $string_table_length)
			{
			$string_table = $string_table . $input[$position ++];
			}

#		$buffer[] = "<!DOCTYPE unknown PUBLIC \"" . $public_identifier . "\" \"wbxml.dtd\">");
		$buffer[] = "<" . "?xml version=\"1.0\" encoding=\"" . $charset . "\"?" . ">";
		}

	# "xmlns" [ ":" <CodepageName> ] "=" <quot> "http://eas.microsoft.com/" ( "AirSync" | [ <CodepageName> "/" ] ) <quot>

	while($position < strlen($input))
		{
		$token = ord($input[$position ++]);

		switch($token)
			{
			case(0x00):
				# 0000 0000 - SWITCH_PAGE

				$data = active_sync_wbxml_get_integer($input, $position);

				$buffer[] = str_repeat("\t", $level) . sprintf("<!-- SWITCH_PAGE_0x%02X %s -->", $data, active_sync_wbxml_get_codepage_name_by_id($data));
				$buffer[] = active_sync_wbxml_request_a($input, $position, $data, $level);

				$position --; # huuuh ... mysterious ... my secret

				break;
			case(0x01):
				# 0000 0001 - END

				return(implode("\n", $buffer));
			case(0x02):
				# 0000 0010 - ENTITY

				active_sync_debug("ENTITY");

				break;
			case(0x03):
				# 0000 0011 - STR_I

				$data = active_sync_wbxml_get_string($input, $position);

				$buffer[] = str_repeat("\t", $level) . sprintf("<![CDATA[%s]]>", $data);

				break;
			case(0x04):
				# 0000 0100 - LITERAL

				active_sync_debug("LITERAL");

				break;
			case(0x40):
				# 0100 0000 - EXT_I_0

				active_sync_debug("EXT_I_0");

				break;
			case(0x41):
				# 0100 0001 - EXT_I_1

				active_sync_debug("EXT_I_1");

				break;
			case(0x42):
				# 0100 0010 - EXT_I_2

				active_sync_debug("EXT_I_2");

				break;
			case(0x43):
				# 0100 0011 - PI

				active_sync_debug("PI");

				break;
			case(0x44):
				# 0100 0100 - LITERAL_C

				active_sync_debug("LITERAL_C");

				break;
			case(0x80):
				# 1000 0000 - EXT_T_0

				active_sync_debug("EXT_T_0");

				break;
			case(0x81):
				# 1000 0001 - EXT_T_1

				active_sync_debug("EXT_T_1");

				break;
			case(0x82):
				# 1000 0010 - EXT_T_2

				active_sync_debug("EXT_T_2");

				break;
			case(0x83):
				# 1000 0011 - STR_T

				active_sync_debug("STR_T");

				break;
			case(0x84):
				# 1000 0100 - LITERAL_A

				active_sync_debug("LITERAL_A");

				break;
			case(0xC0):
				# 1100 0000 - EXT_0

				active_sync_debug("EXT_0");

				break;
			case(0xC1):
				# 1100 0001 - EXT_1

				active_sync_debug("EXT_1");

				break;
			case(0xC2):
				# 1100 0010 - EXT_2

				active_sync_debug("EXT_2");

				break;
			case(0xC3):
				# 1100 0011 - OPAQUE

				$data = active_sync_wbxml_get_multibyte_integer($input, $position);

				$data = active_sync_wbxml_get_string_length($input, $position, $data);

				$buffer[] = str_repeat("\t", $level) . sprintf("<![CDATA[%s]]>", $data);

				break;
			case(0xC4):
				# 1100 0100 - LITERAL_AC

				active_sync_debug("LITERAL_AC");

				break;
			default:
				# 0x05 - 0x3F
				# 0x45 - 0x7F
				# 0x85 - 0xBF (unused)
				# 0xC5 - 0xFF (unused)

				################################################################################
				# has no attribute
				################################################################################

				if(($token & 0x80) == 0x00)
					{
					}

				################################################################################
				# has attribute
				################################################################################

				if(($token & 0x80) == 0x80)
					{
					}

				################################################################################
				# has no content
				################################################################################

				if(($token & 0x40) == 0x00)
					{
					$data = active_sync_wbxml_get_token_name_by_id($codepage, $token);

					$buffer[] = str_repeat("\t", $level) . sprintf("<%s />", $data);
					}

				################################################################################
				# has content
				################################################################################

				if(($token & 0x40) == 0x40)
					{
					$data = active_sync_wbxml_get_token_name_by_id($codepage, $token);

					$buffer[] = str_repeat("\t", $level) . sprintf("<%s>", $data);

					$level ++;
					$buffer[] = active_sync_wbxml_request_a($input, $position, $codepage, $level);
					$level --;

					$data = active_sync_wbxml_get_token_name_by_id($codepage, $token);

					$buffer[] = str_repeat("\t", $level) . sprintf("</%s>", $data);
					}

				break;
			}

		if($level == 0)
			break;
		}

	return(implode("\n", $buffer));
	}

function active_sync_wbxml_request_b($input)
	{
	libxml_use_internal_errors(true); # or add LIBXML_NOWARNING | LIBXML_NOBLANKS

	$xml = new SimpleXMLElement("<active_sync />"); # xml container

	$root = $xml; # working element

	$namespaces = array(); # used codepages. do not add 0 as first codepage, maybe there will be a different one as the first used

	$stack = array(); # used parents

	$codepage = 0; # codepage at start

	$position = 0; # position on startup

	if(strlen($input) == 0)
		return("");

	if($position == 0)
		{
		$version = active_sync_wbxml_get_integer($input, $position);

		$public_identifier = active_sync_wbxml_get_multibyte_integer($input, $position);

		$charset = active_sync_wbxml_get_multibyte_integer($input, $position);

		$string_table_length = active_sync_wbxml_get_multibyte_integer($input, $position);

#		$version = "1." . $version;

		$public_identifier = active_sync_wbxml_get_public_identifier_name_by_id($public_identifier);

		$charset = active_sync_wbxml_get_charset_name_by_id($charset);

		@ mb_internal_encoding($charset);

		$string_table = "";

		while(strlen($string_table) < $string_table_length)
			$string_table = $string_table . $input[$position ++];
		}

	# "xmlns" [ ":" <CodepageName> ] "=" <quot> "http://eas.microsoft.com/" ( "AirSync" | [ <CodepageName> "/" ] ) <quot>

	while($position < strlen($input))
		{
		$token = ord($input[$position ++]);

		switch($token)
			{
			case(0x00):
				# 0000 0000 - SWITCH_PAGE

				$data = active_sync_wbxml_get_integer($input, $position);

				$codepage = $data;

				$stack[] = $codepage;

				$namespaces[$codepage] = $codepage;

				break;
			case(0x01):
				# 0000 0001 - END

				array_pop($stack);

				$root = current($root->xpath(".."));

				break;
			case(0x02):
				# 0000 0010 - ENTITY


				active_sync_debug("ENTITY");

				break;
			case(0x03):
				# 0000 0011 - STR_I

				$data = active_sync_wbxml_get_string($input, $position);

				$root[] = $data;

				break;
			case(0x04):
				# 0000 0100 - LITERAL

				active_sync_debug("LITERAL");

				break;
			case(0x40):
				# 0100 0000 - EXT_I_0

				active_sync_debug("EXT_I_0");

				break;
			case(0x41):
				# 0100 0001 - EXT_I_1

				active_sync_debug("EXT_I_1");

				break;
			case(0x42):
				# 0100 0010 - EXT_I_2

				active_sync_debug("EXT_I_2");

				break;
			case(0x43):
				# 0100 0011 - PI

				active_sync_debug("PI");

				break;
			case(0x44):
				# 0100 0100 - LITERAL_C

				active_sync_debug("LITERAL_C");

				break;
			case(0x80):
				# 1000 0000 - EXT_T_0

				active_sync_debug("EXT_T_0");

				break;
			case(0x81):
				# 1000 0001 - EXT_T_1

				active_sync_debug("EXT_T_1");

				break;
			case(0x82):
				# 1000 0010 - EXT_T_2

				active_sync_debug("EXT_T_2");

				break;
			case(0x83):
				# 1000 0011 - STR_T

				active_sync_debug("STR_T");

				break;
			case(0x84):
				# 1000 0100 - LITERAL_A

				active_sync_debug("LITERAL_A");

				break;
			case(0xC0):
				# 1100 0000 - EXT_0

				active_sync_debug("EXT_0");

				break;
			case(0xC1):
				# 1100 0001 - EXT_1

				active_sync_debug("EXT_1");

				break;
			case(0xC2):
				# 1100 0010 - EXT_2

				active_sync_debug("EXT_2");

				break;
			case(0xC3):
				# 1100 0011 - OPAQUE

				$data = active_sync_wbxml_get_multibyte_integer($input, $position);

				$data = active_sync_wbxml_get_string_length($input, $position, $data);

				$root[] = $data;

				break;
			case(0xC4):
				# 1100 0100 - LITERAL_AC

				active_sync_debug("LITERAL_AC");

				break;
			default:
				# 0x05 - 0x3F
				# 0x45 - 0x7F
				# 0x85 - 0xBF (unused)
				# 0xC5 - 0xFF (unused)

				################################################################################
				# has no attribute
				################################################################################

				if(($token & 0x80) == 0x00)
					{
					}

				################################################################################
				# has attribute
				################################################################################

				if(($token & 0x80) == 0x80)
					{
					}

				################################################################################
				# has no content
				################################################################################

				if(($token & 0x40) == 0x00)
					{
					$data = active_sync_wbxml_get_token_name_by_id($codepage, $token);

					$stack[] = $codepage;

					$namespaces[$codepage] = $codepage;

					$name = active_sync_wbxml_get_codepage_name_by_id($codepage);

					$child = $root->addChild(($codepage == reset($namespaces) ? "xmlns:" : "xmlns:" . strtolower($name) . ":") . $data);
					}

				################################################################################
				# has content
				################################################################################

				if(($token & 0x40) == 0x40)
					{
					$data = active_sync_wbxml_get_token_name_by_id($codepage, $token);

					$stack[] = $codepage;

					$namespaces[$codepage] = $codepage;

					$name = active_sync_wbxml_get_codepage_name_by_id($codepage);

					$child = $root->addChild(($codepage == reset($namespaces) ? "xmlns:" : "xmlns:" . strtolower($name) . ":") . $data);

					$root = $child;
					}

				break;
			}
		}

	foreach($namespaces as $id => $codepage)
		{
		$name = active_sync_wbxml_get_codepage_name_by_id($codepage);

		$xml->children()->addAttribute(($codepage == reset($namespaces) ? "xmlns:xmlns" : "xmlns:xmlns:" . strtolower($name)), $name);
		}

	$xml = "<?" . "xml version=\"1.0\" encoding=\"" . $charset . "\"?" . ">" . $xml->children()->asXML();

	return($xml);
	}

function active_sync_wbxml_request_c($input, & $position = 0, $codepage = 0, $level = 0, & $namespaces = array())
	{
	$buffer = array();

	if(strlen($input) == 0)
		return(implode("", $buffer));

	if($position == 0)
		{
		$version = active_sync_wbxml_get_integer($input, $position);

		$public_identifier = active_sync_wbxml_get_multibyte_integer($input, $position);

		$charset = active_sync_wbxml_get_multibyte_integer($input, $position);

		$string_table_length = active_sync_wbxml_get_multibyte_integer($input, $position);

#		$version = "1." . $version;

		$public_identifier = active_sync_wbxml_get_public_identifier_name_by_id($public_identifier);

		$charset = active_sync_wbxml_get_charset_name_by_id($charset);

		@ mb_internal_encoding($charset);

		$string_table = "";

		while(strlen($string_table) < $string_table_length)
			$string_table = $string_table . $input[$position ++];

#		$buffer[] = "<!DOCTYPE unknown PUBLIC \"" . $public_identifier . "\" \"wbxml.dtd\">");
		$buffer[] = "<" . "?xml version=\"1.0\" encoding=\"" . $charset . "\"?" . ">";
		}

	# "xmlns" [ ":" <CodepageName> ] "=" <quot> "http://eas.microsoft.com/" ( "AirSync" | [ <CodepageName> "/" ] ) <quot>

	while($position < strlen($input))
		{
		$token = ord($input[$position ++]);

		switch($token)
			{
			case(0x00):
				# 0000 0000 - SWITCH_PAGE

				$data = active_sync_wbxml_get_integer($input, $position);

				$buffer[] = active_sync_wbxml_request_c($input, $position, $data, $level, $namespaces);

				$position --;

				# end of token result in end of codepage too.
				# the binary 0x01 need to be executed twice.
				# codepage will be added to namespaces when needed.

				break;
			case(0x01):
				# 0000 0001 - END

				return(implode("", $buffer));

				# this can also be a break with an additional check
				# for ($level != 0) outside while($position < strlen($input)) and a glue of buffer
			case(0x02):
				# 0000 0010 - ENTITY

				active_sync_debug("ENTITY");

				break;
			case(0x03):
				# 0000 0011 - STR_I

				$data = active_sync_wbxml_get_string($input, $position);

				$buffer[] = $data;

				break;
			case(0x04):
				# 0000 0100 - LITERAL

				active_sync_debug("LITERAL");

				break;
			case(0x40):
				# 0100 0000 - EXT_I_0

				active_sync_debug("EXT_I_0");

				break;
			case(0x41):
				# 0100 0001 - EXT_I_1

				active_sync_debug("EXT_I_1");

				break;
			case(0x42):
				# 0100 0010 - EXT_I_2

				active_sync_debug("EXT_I_2");

				break;
			case(0x43):
				# 0100 0011 - PI

				active_sync_debug("PI");

				break;
			case(0x44):
				# 0100 0100 - LITERAL_C

				active_sync_debug("LITERAL_C");

				break;
			case(0x80):
				# 1000 0000 - EXT_T_0

				active_sync_debug("EXT_T_0");

				break;
			case(0x81):
				# 1000 0001 - EXT_T_1

				active_sync_debug("EXT_T_1");

				break;
			case(0x82):
				# 1000 0010 - EXT_T_2

				active_sync_debug("EXT_T_2");

				break;
			case(0x83):
				# 1000 0011 - STR_T

				active_sync_debug("STR_T");

				break;
			case(0x84):
				# 1000 0100 - LITERAL_A

				active_sync_debug("LITERAL_A");

				break;
			case(0xC0):
				# 1100 0000 - EXT_0

				active_sync_debug("EXT_0");

				break;
			case(0xC1):
				# 1100 0001 - EXT_1

				active_sync_debug("EXT_1");

				break;
			case(0xC2):
				# 1100 0010 - EXT_2

				active_sync_debug("EXT_2");

				break;
			case(0xC3):
				# 1100 0011 - OPAQUE

				$data = active_sync_wbxml_get_multibyte_integer($input, $position);

				$data = active_sync_wbxml_get_string_length($input, $position, $data);

				$buffer[] = $data;

				break;
			case(0xC4):
				# 1100 0100 - LITERAL_AC

				active_sync_debug("LITERAL_AC");

				break;
			default:
				# 0x05 - 0x3F
				# 0x45 - 0x7F
				# 0x85 - 0xBF (unused)
				# 0xC5 - 0xFF (unused)

				################################################################################
				# has no attribute
				################################################################################

				if(($token & 0x80) == 0x00)
					{
					}

				################################################################################
				# has attribute
				################################################################################

				if(($token & 0x80) == 0x80)
					{
					}

				################################################################################
				# has no content
				################################################################################

				if(($token & 0x40) == 0x00)
					{
					$data = active_sync_wbxml_get_token_name_by_id($codepage, $token);

					$namespaces[$codepage] = $codepage;

					$codepage_name = active_sync_wbxml_get_codepage_name_by_id($codepage);

					$buffer[] = "<" . strtolower($codepage == reset($namespaces) ? "" : $codepage_name . ":") . $data . "/>";
					}

				################################################################################
				# has content
				################################################################################

				if(($token & 0x40) == 0x40)
					{
					$data = active_sync_wbxml_get_token_name_by_id($codepage, $token);

					$namespaces[$codepage] = $codepage;

					$codepage_name = active_sync_wbxml_get_codepage_name_by_id($codepage);

					$buffer[] = "<" . strtolower($codepage == reset($namespaces) ? "" : $codepage_name . ":") . $data . ">";
					$buffer[] = active_sync_wbxml_request_c($input, $position, $codepage, $level, $namespaces);
					$buffer[] = "</" . strtolower($codepage == reset($namespaces) ? "" : $codepage_name . ":") . $data . ">";
					}

				break;
			}

		if($level == 0)
			break;
		} # while($position < strlen($input))

	################################################################################
	# add namespaces
	################################################################################

	foreach($namespaces as $codepage)
		{
		$codepage_name = active_sync_wbxml_get_codepage_name_by_id($codepage);

		$tag = substr($buffer[1], 1, strlen($buffer[1]) - 1);

		$key = "xmlns" . strtolower($codepage == reset($namespaces) ? "" : ":" . $codepage_name);
		$value = $codepage_name;

		$attribute = implode("=", array($key, "\"" . $value . "\""));

		$buffer[1] = "<" . implode(" ", array($tag, $attribute)) . ">";
		}

	return(implode("", $buffer));
	}

function active_sync_wbxml_request_parse_a($data)
	{
	$data = active_sync_wbxml_request_a($data);

	$data = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

#	$data = new SimpleXMLElement($data);

	return($data);
	}

function active_sync_wbxml_request_parse_b($data)
	{
	$data = active_sync_wbxml_request_b($data);

	$data = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

#	$data = new SimpleXMLElement($data);

	return($data);
	}

function active_sync_wbxml_request_parse_c($data)
	{
	$data = active_sync_wbxml_request_c($data);

	$data = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

#	$data = new SimpleXMLElement($data);

	return($data);
	}

class active_sync_wbxml_response
	{
	var $response = "\x03\x01\x6A\x00";
	var $codepage = 0xFF;

	function x_close($token = "")
		{
		$this->response = $this->response . chr(0x01);
		}

	function x_init()
		{
		$this->response = "";
		$this->response = $this->response . "\x03";
		$this->response = $this->response . "\x01";
		$this->response = $this->response . "\x6A";
		$this->response = $this->response . "\x00";

		$this->codepage = 0x00;
		}

	function x_print_multibyte_integer($integer)
		{
		$retval = "";
		$remain = 0x00;

		do
			{
			$retval = chr(($integer & 0x7F) | ($remain > 0x7F ? 0x80 : 0x00)) . $retval;

			$remain = $integer;

			$integer = $integer >> 7;
			}
		while($integer > 0x00);

		$this->response = $this->response . $retval;
		}

	function x_open($token, $contains_data = true, $has_attribute = false)
		{
		$data = active_sync_wbxml_get_token_id_by_name($this->codepage, $token);

		$data = $data | ($has_attribute === false ? 0x00 : 0x80);
		$data = $data | ($contains_data === false ? 0x00 : 0x40);

		$this->response = $this->response . chr($data);
		}

	function x_print($string)
		{
		if(strpos($string, "\x00") === false)
			{
			$this->response = $this->response . chr(0x03);
			$this->response = $this->response . $string;
			$this->response = $this->response . chr(0x00);
			}
		else
			$this->x_print_bin($string);
		}

	function x_print_bin($string)
		{
		$this->response = $this->response . chr(0xC3);

		$length = strlen($string);

		$this->x_print_multibyte_integer($length);

		$this->response = $this->response . $string;
		}

	function x_switch($codepage)
		{
		$codepage = (is_numeric($codepage) === false ? active_sync_wbxml_get_codepage_id_by_name($codepage) : $codepage);

		if($this->codepage != $codepage)
			{
			$this->codepage = $codepage;

			$this->response = $this->response . chr(0x00);
			$this->response = $this->response . chr($this->codepage);
			}
		}
	}


function active_sync_wbxml_table_charset()
	{
	$retval = array
		(
		0x0003 => "US-ASCII",
		0x0004 => "ISO-8859-1",
		0x0005 => "ISO-8859-2",
		0x0006 => "ISO-8859-3",
		0x0007 => "ISO-8859-4",
		0x0008 => "ISO-8859-5",
		0x0009 => "ISO-8859-6",
		0x000A => "ISO-8859-7",
		0x000B => "ISO-8859-8",
		0x000C => "ISO-8859-9",
		0x000D => "ISO-8859-10",

		0x006A => "UTF-8",
		0x006D => "ISO-8859-13",

		0x006E => "ISO-8859-14",
		0x006F => "ISO-8859-15",
		0x0070 => "ISO-8859-16",
		0x0071 => "GBK",
		0x0072 => "GB18030",
		0x0073 => "OSD_EBCDIC_DF04_15",
		0x0074 => "OSD_EBCDIC_DF03_IRV",
		0x0075 => "OSD_EBCDIC_DF04_1",
		0x0076 => "ISO-11548-1",
		0x0077 => "KZ-1048",

		0x03E8 => "ISO-10646-UCS-2",
		0x03E9 => "ISO-10646-UCS-4",

		0x03F4 => "UTF-7",
		0x03F5 => "UTF-16BE",
		0x03F6 => "UTF-16LE",
		0x03F7 => "UTF-16",
		0x03F8 => "CESU-8",
		0x03F9 => "UTF-32",
		0x03FA => "UTF-32BE",
		0x03FB => "UTF-32LE",
		0x03FC => "BOCU-1",

		0x07D8 => "DEC-MCS",
		0x07D9 => "IBM850",
		0x07DA => "IBM852",
		0x07DB => "IBM437",

		0x07DD => "IBM862",

		0x07E9 => "GB2312",
		0x07EA => "BIG5",

		0x07EC => "IBM037",
		0x07ED => "IBM038",
		0x07EE => "IBM273",
		0x07EF => "IBM274",
		0x07F0 => "IBM275",
		0x07F1 => "IBM277",
		0x07F2 => "IBM278",
		0x07F3 => "IBM280",
		0x07F4 => "IBM281",
		0x07F5 => "IBM284",
		0x07F6 => "IBM285",
		0x07F7 => "IBM290",
		0x07F8 => "IBM297",
		0x07F9 => "IBM420",
		0x07FA => "IBM423",
		0x07FB => "IBM424",
		0x07FC => "IBM500",
		0x07FD => "IBM851",
		0x07FE => "IBM855",
		0x07FF => "IBM857",
		0x0800 => "IBM860",
		0x0801 => "IBM861",
		0x0802 => "IBM863",
		0x0803 => "IBM864",
		0x0804 => "IBM865",
		0x0805 => "IBM868",
		0x0806 => "IBM869",
		0x0807 => "IBM870",
		0x0808 => "IBM871",
		0x0809 => "IBM880",
		0x080A => "IBM891",
		0x080B => "IBM903",
		0x080C => "IBM904",
		0x080D => "IBM905",
		0x080E => "IBM918",
		0x080F => "IBM1026",
		0x0810 => "EBCDIC-AT-DE",
		0x0811 => "EBCDIC-AT-DE-A",
		0x0812 => "EBCDIC-CA-FR",
		0x0813 => "EBCDIC-DK-NO",
		0x0814 => "EBCDIC-DK-NO-A",
		0x0815 => "EBCDIC-FI-SE",
		0x0816 => "EBCDIC-FI-SE-A",
		0x0817 => "EBCDIC-FR",
		0x0818 => "EBCDIC-IT",
		0x0819 => "EBCDIC-PT",
		0x081A => "EBCDIC-ES",
		0x081B => "EBCDIC-ES-A",
		0x081C => "EBCDIC-ES-S",
		0x081D => "EBCDIC-UK",
		0x081E => "EBCDIC-US",
		0x081F => "UNKNOWN-8BIT",
		0x0820 => "MNEMONIC",
		0x0821 => "MNEM",
		0x0822 => "VISCII",
		0x0823 => "VIQR",
		0x0824 => "KOI8-R",
		0x0825 => "HZ-GB-2312",
		0x0826 => "IBM866",
		0x0827 => "IBM775",
		0x0828 => "KOI8-U",
		0x0829 => "IBM00858",
		0x082A => "IBM00924",
		0x082B => "IBM01140",
		0x082C => "IBM01141",
		0x082D => "IBM01142",
		0x082E => "IBM01143",
		0x082F => "IBM01144",
		0x0830 => "IBM01145",
		0x0831 => "IBM01146",
		0x0832 => "IBM01147",
		0x0833 => "IBM01148",
		0x0834 => "IBM01149",
		0x0835 => "BIG5-HKSCS",
		0x0836 => "IBM1047",
		0x0837 => "PTCP154",
		0x0838 => "AMIGA-1251",

		0x08D3 => "TIS-620",
		0x08D4 => "CP50220",
		);

	return($retval);
	}

function active_sync_wbxml_table_codepage()
	{
	$retval = array
		(
		0 => "AirSync",
		1 => "Contacts",
		2 => "Email",
		3 => "AirNotify",
		4 => "Calendar",
		5 => "Move",
		6 => "ItemEstimate",
		7 => "FolderHierarchy",
		8 => "MeetingResponse",
		9 => "Tasks",
		10 => "ResolveRecipients",
		11 => "ValidateCerts",
		12 => "Contacts2",
		13 => "Ping",
		14 => "Provision",
		15 => "Search",
		16 => "GAL",
		17 => "AirSyncBase",
		18 => "Settings",
		19 => "DocumentLibrary",
		20 => "ItemOperations",
		21 => "ComposeMail",
		22 => "Email2",
		23 => "Notes",
		24 => "RightsManagement",
		25 => "Find",
		254 => "WindowsLive"
		);

	return($retval);
	}

function active_sync_wbxml_table_public_identifier()
	{
	$retval = array
		(
		0x02 => "-//WAPFORUM//DTD WML 1.0//EN",
		0x03 => "-//WAPFORUM//DTD WTA 1.0//EN",
		0x04 => "-//WAPFORUM//DTD WML 1.1//EN",
		0x05 => "-//WAPFORUM//DTD SI 1.0//EN",
		0x06 => "-//WAPFORUM//DTD SL 1.0//EN",
		0x07 => "-//WAPFORUM//DTD CO 1.0//EN",
		0x08 => "-//WAPFORUM//DTD CHANNEL 1.1//EN",
		0x09 => "-//WAPFORUM//DTD WML 1.2//EN",
		0x0A => "-//WAPFORUM//DTD WML 1.3//EN",
		0x0B => "-//WAPFORUM//DTD PROV 1.0//EN",
		0x0C => "-//WAPFORUM//DTD WTA-WML 1.2//EN",
		0x0D => "-//WAPFORUM//DTD CHANNEL 1.2//EN"
		);

	return($retval);
	}

function active_sync_wbxml_table_token()
	{
	################################################################################
	# AirSync
	################################################################################

	$code_page_0 = array
		(
		0x05 => "Sync",
		0x06 => "Responses",
		0x07 => "Add",
		0x08 => "Change",
		0x09 => "Delete",
		0x0A => "Fetch",
		0x0B => "SyncKey",
		0x0C => "ClientId",
		0x0D => "ServerId",
		0x0E => "Status",
		0x0F => "Collection",
		0x10 => "Class",

		0x12 => "CollectionId",
		0x13 => "GetChanges",
		0x14 => "MoreAvailable",
		0x15 => "WindowSize",
		0x16 => "Commands",
		0x17 => "Options",
		0x18 => "FilterType",
		0x19 => "Truncation",

		0x1B => "Conflict",
		0x1C => "Collections",
		0x1D => "ApplicationData",
		0x1E => "DeletesAsMoves",

		0x20 => "Supported",
		0x21 => "SoftDelete",
		0x22 => "MIMESupport",
		0x23 => "MIMETruncation",
		0x24 => "Wait",
		0x25 => "Limit",
		0x26 => "Partial",
		0x27 => "ConversationMode",
		0x28 => "MaxItems",
		0x29 => "HeartbeatInterval"
		);

	################################################################################
	# Contacts
	################################################################################

	$code_page_1 = array
		(
		0x05 => "Anniversary",
		0x06 => "AssistantName",
		0x07 => "AssistnamePhoneNumber",
		0x08 => "Birthday",
		0x09 => "Body",
		0x0A => "BodySize",
		0x0B => "BodyTruncated",
		0x0C => "Business2PhoneNumber",
		0x0D => "BusinessAddressCity",
		0x0E => "BusinessAddressCountry",
		0x0F => "BusinessAddressPostalCode",
		0x10 => "BusinessAddressState",
		0x11 => "BusinessAddressStreet",
		0x12 => "BusinessFaxNumber",
		0x13 => "BusinessPhoneNumber",
		0x14 => "CarPhoneNumber",
		0x15 => "Categories",
		0x16 => "Category",
		0x17 => "Children",
		0x18 => "Child",
		0x19 => "CompanyName",
		0x1A => "Department",
		0x1B => "Email1Address",
		0x1C => "Email2Address",
		0x1D => "Email3Address",
		0x1E => "FileAs",
		0x1F => "FirstName",
		0x20 => "Home2PhoneNumber",
		0x21 => "HomeAddressCity",
		0x22 => "HomeAddressCountry",
		0x23 => "HomeAddressPostalCode",
		0x24 => "HomeAddressState",
		0x25 => "HomeAddressStreet",
		0x26 => "HomeFaxNumber",
		0x27 => "HomePhoneNumber",
		0x28 => "JobTitle",
		0x29 => "LastName",
		0x2A => "MiddleName",
		0x2B => "MobilePhoneNumber",
		0x2C => "OfficeLocation",
		0x2D => "OtherAddressCity",
		0x2E => "OtherAddressCountry",
		0x2F => "OtherAddressPostalCode",
		0x30 => "OtherAddressState",
		0x31 => "OtherAddressStreet",
		0x32 => "PagerNumber",
		0x33 => "RadioPhoneNumber",
		0x34 => "Spouse",
		0x35 => "Suffix",
		0x36 => "Title",
		0x37 => "WebPage",
		0x38 => "YomiCompanyName",
		0x39 => "YomiFirstName",
		0x3A => "YomiLastName",
		0x3C => "Picture",
		0x3D => "Alias",
		0x3E => "WeightedRank"
		);

	################################################################################
	# Email
	################################################################################

	$code_page_2 = array
		(
		0x05 => "Attachment",
		0x06 => "Attachments",
		0x07 => "AttName",
		0x08 => "AttSize",
		0x09 => "Att0id",
		0x0A => "AttMethod",

		0x0C => "Body",
		0x0D => "BodySize",
		0x0E => "BodyTruncated",
		0x0F => "DateReceived",
		0x10 => "DisplayName",
		0x11 => "DisplayTo",
		0x12 => "Importance",
		0x13 => "MessageClass",
		0x14 => "Subject",
		0x15 => "Read",
		0x16 => "To",
		0x17 => "Cc",
		0x18 => "From",
		0x19 => "ReplyTo",
		0x1A => "AllDayEvent",
		0x1B => "Categories",
		0x1C => "Category",
		0x1D => "DtStamp",
		0x1E => "EndTime",
		0x1F => "InstanceType",
		0x20 => "BusyStatus",
		0x21 => "Location",
		0x22 => "MeetingRequest",
		0x23 => "Organizer",
		0x24 => "RecurrenceId",
		0x25 => "Reminder",
		0x26 => "ResponseRequested",
		0x27 => "Recurrences",
		0x28 => "Recurrence",
		0x29 => "Type",
		0x2A => "Until",
		0x2B => "Occurrences",
		0x2C => "Interval",
		0x2D => "DayOfWeek",
		0x2E => "DayOfMonth",
		0x2F => "WeekOfMonth",
		0x30 => "MonthOfYear",
		0x31 => "StartTime",
		0x32 => "Sensitivity",
		0x33 => "TimeZone",
		0x34 => "GlobalObjId",
		0x35 => "ThreadTopic",
		0x36 => "MIMEData",
		0x37 => "MIMETruncated",
		0x38 => "MIMESize",
		0x39 => "InternetCPID",
		0x3A => "Flag",
		0x3B => "Status",
		0x3C => "ContentClass",
		0x3D => "FlagType",
		0x3E => "CompleteTime",
		0x3F => "DisallowNewTimeProposal"
		);

	################################################################################
	# AirNotify
	################################################################################

	$code_page_3 = array
		(
		0x05 => "Notify",
		0x06 => "Notification",
		0x07 => "Version",
		0x08 => "Lifetime",
		0x09 => "DeviceInfo",
		0x0A => "Enable",
		0x0B => "Folder",
		0x0C => "ServerId",
		0x0D => "DeviceAddress",
		0x0E => "ValidCarrierProfiles",
		0x0F => "CarrierProfile",
		0x10 => "Status",
		0x11 => "Responses",
		0x12 => "Devices",
		0x13 => "Device",
		0x14 => "Id",
		0x15 => "Expiry",
		0x16 => "NotifyGUID"
		);

	################################################################################
	# Calendar
	################################################################################

	$code_page_4 = array
		(
		0x05 => "TimeZone",
		0x06 => "AllDayEvent",
		0x07 => "Attendees",
		0x08 => "Attendee",
		0x09 => "Email",
		0x0A => "Name",
		0x0B => "Body",
		0x0C => "BodyTruncated",
		0x0D => "BusyStatus",
		0x0E => "Categories",
		0x0F => "Category",
		0x11 => "DtStamp",
		0x12 => "EndTime",
		0x13 => "Exception",
		0x14 => "Exceptions",
		0x15 => "Deleted",
		0x16 => "ExceptionStartTime",
		0x17 => "Location",
		0x18 => "MeetingStatus",
		0x19 => "OrganizerEmail",
		0x1A => "OrganizerName",
		0x1B => "Recurrence",
		0x1C => "Type",
		0x1D => "Until",
		0x1E => "Occurrences",
		0x1F => "Interval",
		0x20 => "DayOfWeek",
		0x21 => "DayOfMonth",
		0x22 => "WeekOfMonth",
		0x23 => "MonthOfYear",
		0x24 => "Reminder",
		0x25 => "Sensitivity",
		0x26 => "Subject",
		0x27 => "StartTime",
		0x28 => "UID",
		0x29 => "AttendeeStatus",
		0x2A => "AttendeeType",







		0x33 => "DisallowNewTimeProposal",
		0x34 => "ResponseRequested",
		0x35 => "AppointmentReplyTime",
		0x36 => "ResponseType",
		0x37 => "CalendarType",
		0x38 => "IsLeapMonth",
		0x39 => "FirstDayOfWeek",
		0x3A => "OnlineMeetingConfLink",
		0x3B => "OnlineMeetingExternalLink",
		0x3C => "ClientUid"
		);

	################################################################################
	# Move
	################################################################################

	$code_page_5 = array
		(
		0x05 => "MoveItems",
		0x06 => "Move",
		0x07 => "SrcMsgId",
		0x08 => "SrcFldId",
		0x09 => "DstFldId",
		0x0A => "Response",
		0x0B => "Status",
		0x0C => "DstMsgId"
		);

	################################################################################
	# GetItemEstimate
	################################################################################

	$code_page_6 = array
		(
		0x05 => "GetItemEstimate",
		0x06 => "Version",
		0x07 => "Collections",
		0x08 => "Collection",
		0x09 => "Class",
		0x0A => "CollectionId",
		0x0B => "DateTime",
		0x0C => "Estimate",
		0x0D => "Response",
		0x0E => "Status"
		);

	################################################################################
	# FolderHierarchy
	################################################################################

	$code_page_7 = array
		(
		0x05 => "Folders",
		0x06 => "Folder",
		0x07 => "DisplayName",
		0x08 => "ServerId",
		0x09 => "ParentId",
		0x0A => "Type",

		0x0C => "Status",

		0x0E => "Changes",
		0x0F => "Add",
		0x10 => "Delete",
		0x11 => "Update",
		0x12 => "SyncKey",
		0x13 => "FolderCreate",
		0x14 => "FolderDelete",
		0x15 => "FolderUpdate",
		0x16 => "FolderSync",
		0x17 => "Count"
		);

	################################################################################
	# MeetingResponse
	################################################################################

	$code_page_8 = array
		(
		0x05 => "CalendarId",
		0x06 => "CollectionId",
		0x07 => "MeetingResponse",
		0x08 => "RequestId",
		0x09 => "Request",
		0x0A => "Result",
		0x0B => "Status",
		0x0C => "UserResponse",
		0x0E => "InstanceId",

		0x10 => "ProposedStartTime",
		0x11 => "ProposedEndTime",
		0x12 => "SendResponse"
		);

	################################################################################
	# Tasks
	################################################################################

	$code_page_9 = array
		(
		0x05 => "Body",
		0x06 => "BodySize",
		0x07 => "BodyTruncated",
		0x08 => "Categories",
		0x09 => "Category",
		0x0A => "Complete",
		0x0B => "DateCompleted",
		0x0C => "DueDate",
		0x0D => "UtcDueDate",
		0x0E => "Importance",
		0x0F => "Recurrence",
		0x10 => "Type",
		0x11 => "Start",
		0x12 => "Until",
		0x13 => "Occurrences",
		0x14 => "Interval",
		0x15 => "DayOfMonth",
		0x16 => "DayOfWeek",
		0x17 => "WeekOfMonth",
		0x18 => "MonthOfYear",
		0x19 => "Regenerate",
		0x1A => "DeadOccur",
		0x1B => "ReminderSet",
		0x1C => "ReminderTime",
		0x1D => "Sensitivity",
		0x1E => "StartDate",
		0x1F => "UtcStartDate",
		0x20 => "Subject",
		
		0x22 => "OrdinalDate",
		0x23 => "SubOrdinalDate",
		0x24 => "CalendarType",
		0x25 => "IsLeapMonth",
		0x26 => "FirstDayOfWeek"
		);

	################################################################################
	# ResolveRecipients
	################################################################################

	$code_page_10 = array
		(
		0x05 => "ResolveRecipients",
		0x06 => "Response",
		0x07 => "Status",
		0x08 => "Type",
		0x09 => "Recipient",
		0x0A => "DisplayName",
		0x0B => "EmailAddress",
		0x0C => "Certificates",
		0x0D => "Certificate",
		0x0E => "MiniCertificate",
		0x0F => "Options",
		0x10 => "To",
		0x11 => "CertificateRetrieval",
		0x12 => "RecipientCount",
		0x13 => "MaxCertificates",
		0x14 => "MaxAmbiguousRecipients",
		0x15 => "CertificateCount",
		0x16 => "Availability",
		0x17 => "StartTime",
		0x18 => "EndTime",
		0x19 => "MergedFreeBusy",
		0x1A => "Picture",
		0x1B => "MaxSize",
		0x1C => "Data",
		0x1D => "MaxPictures"
		);

	################################################################################
	# ValidateCerts
	################################################################################

	$code_page_11 = array
		(
		0x05 => "ValidateCert",
		0x06 => "Certificates",
		0x07 => "Certificate",
		0x08 => "CertificateChain",
		0x09 => "CheckCRL",
		0x0A => "Status"
		);

	################################################################################
	# Contacts2
	################################################################################

	$code_page_12 = array
		(
		0x05 => "CustomerId",
		0x06 => "GovernmentId",
		0x07 => "IMAddress",
		0x08 => "IMAddress2",
		0x09 => "IMAddress3",
		0x0A => "ManagerName",
		0x0B => "CompanyMainPhone",
		0x0C => "AccountName",
		0x0D => "NickName",
		0x0E => "MMS"
		);

	################################################################################
	# Ping
	################################################################################

	$code_page_13 = array
		(
		0x05 => "Ping",
		0x06 => "AutdStatus",
		0x07 => "Status",
		0x08 => "HeartbeatInterval",
		0x09 => "Folders",
		0x0A => "Folder",
		0x0B => "Id",
		0x0C => "Class",
		0x0D => "MaxFolders"
		);

	################################################################################
	# Provision
	################################################################################

	$code_page_14 = array
		(
		0x05 => "Provision",
		0x06 => "Policies",
		0x07 => "Policy",
		0x08 => "PolicyType",
		0x09 => "PolicyKey",
		0x0A => "Data",
		0x0B => "Status",
		0x0C => "RemoteWipe",
		0x0D => "EASProvisionDoc",
		0x0E => "DevicePasswordEnabled",
		0x0F => "AlphanumericDevicePasswordRequired",
		0x10 => "RequireStorageCardEncryption",
		0x11 => "PasswordRecoveryEnabled",
		0x13 => "AttachmentsEnabled",
		0x14 => "MinDevicePasswordLength",
		0x15 => "MaxInactivityTimeDeviceLock",
		0x16 => "MaxDevicePasswordFailedAttempts",
		0x17 => "MaxAttachmentSize",
		0x18 => "AllowSimpleDevicePassword",
		0x19 => "DevicePasswordExpiration",
		0x1A => "DevicePasswordHistory",
		0x1B => "AllowStorageCard",
		0x1C => "AllowCamera",
		0x1D => "RequireDeviceEncryption",
		0x1E => "AllowUnsignedApplications",
		0x1F => "AllowUnsignedInstallationPackages",
		0x20 => "MinDevicePasswordComplexCharacters",
		0x21 => "AllowWiFi",
		0x22 => "AllowTextMessaging",
		0x23 => "AllowPOPIMAPEmail",
		0x24 => "AllowBluetooth",
		0x25 => "AllowIrDA",
		0x26 => "RequireManualSyncWhenRoaming",
		0x27 => "AllowDesktopSync",
		0x28 => "MaxCalendarAgeFilter",
		0x29 => "AllowHTMLEmail",
		0x2A => "MaxEmailAgeFilter",
		0x2B => "MaxEmailBodyTruncationSize",
		0x2C => "MaxEmailHTMLBodyTruncationSize",
		0x2D => "RequireSignedSMIMEMessages",
		0x2E => "RequireEncryptedSMIMEMessages",
		0x2F => "RequireSignedSMIMEAlgorithm",
		0x30 => "RequireEncryptionSMIMEAlgorithm",
		0x31 => "AllowSMIMEEncryptionAlgorithmNegotiation",
		0x32 => "AllowSMIMESoftCerts",
		0x33 => "AllowBrowser",
		0x34 => "AllowConsumerEmail",
		0x35 => "AllowRemoteDesktop",
		0x36 => "AllowInternetSharing",
		0x37 => "UnapprovedInROMApplicationList",
		0x38 => "ApplicationName",
		0x39 => "ApprovedApplicationList",
		0x3A => "Hash",
		0x3B => "AccountOnlyRemoteWipe"
		);

	################################################################################
	# Search
	################################################################################

	$code_page_15 = array
		(
		0x05 => "Search",

		0x07 => "Store",
		0x08 => "Name",
		0x09 => "Query",
		0x0A => "Options",
		0x0B => "Range",
		0x0C => "Status",
		0x0D => "Response",
		0x0E => "Result",
		0x0F => "Properties",
		0x10 => "Total",
		0x11 => "EqualTo",
		0x12 => "Value",
		0x13 => "And",
		0x14 => "Or",
		0x15 => "FreeText",

		0x17 => "DeepTraversal",
		0x18 => "LongId",
		0x19 => "RebuildResults",
		0x1A => "LessThan",
		0x1B => "GreaterThan",


		0x1E => "UserName",
		0x1F => "Password",
		0x20 => "ConversionId",
		0x21 => "Picture",
		0x22 => "MaxSize",
		0x23 => "MaxPictures"
		);

	################################################################################
	# GAL
	################################################################################

	$code_page_16 = array
		(
		0x05 => "DisplayName",
		0x06 => "Phone",
		0x07 => "Office",
		0x08 => "Title",
		0x09 => "Company",
		0x0A => "Alias",
		0x0B => "FirstName",
		0x0C => "LastName",
		0x0D => "HomePhone",
		0x0E => "MobilePhone",
		0x0F => "EmailAddress",
		0x10 => "Picture",
		0x11 => "Status",
		0x12 => "Data"
		);

	################################################################################
	# AirSyncBase
	################################################################################

	$code_page_17 = array
		(
		0x05 => "BodyPreference",
		0x06 => "Type",
		0x07 => "TruncationSize",
		0x08 => "AllOrNone",

		0x0A => "Body",
		0x0B => "Data",
		0x0C => "EstimatedDataSize",
		0x0D => "Truncated",
		0x0E => "Attachments",
		0x0F => "Attachment",
		0x10 => "DisplayName",
		0x11 => "FileReference",
		0x12 => "Method",
		0x13 => "ContentId",
		0x14 => "ContentLocation",
		0x15 => "IsInline",
		0x16 => "NativeBodyType",
		0x17 => "ContentType",
		0x18 => "Preview",
		0x19 => "BodyPartReference",
		0x1A => "BodyPart",
		0x1B => "Status",
		0x1C => "Add",
		0x1D => "Delete",
		0x1E => "ClientId",
		0x1F => "Content",
		0x20 => "Location",
		0x21 => "Annotation",
		0x22 => "Street",
		0x23 => "City",
		0x24 => "State",
		0x25 => "Country",
		0x26 => "PostalCode",
		0x27 => "Latitude",
		0x28 => "Longitude",
		0x29 => "Accuracy",
		0x2A => "Altitude",
		0x2B => "AltitudeAccuracy",
		0x2C => "LocationUri",
		0x2D => "InstanceId"
		);

	################################################################################
	# Settings
	################################################################################

	$code_page_18 = array
		(
		0x05 => "Settings",
		0x06 => "Status",
		0x07 => "Get",
		0x08 => "Set",
		0x09 => "Oof",
		0x0A => "OofState",
		0x0B => "StartTime",
		0x0C => "EndTime",
		0x0D => "OofMessage",
		0x0E => "AppliesToInternal",
		0x0F => "AppliesToExternalKnown",
		0x10 => "AppliesToExternalUnknown",
		0x11 => "Enabled",
		0x12 => "ReplyMessage",
		0x13 => "BodyType",
		0x14 => "DevicePassword",
		0x15 => "Password",
		0x16 => "DeviceInformation",
		0x17 => "Model",
		0x18 => "Imei",
		0x19 => "FriendlyName",
		0x1A => "OS",
		0x1B => "OSLanguage",
		0x1C => "PhoneNumber",
		0x1D => "UserInformation",
		0x1E => "EmailAddress",
		0x1F => "SmtpAddress",
		0x20 => "UserAgent",
		0x21 => "EnableOutboundSMS",
		0x22 => "MobileOperator",
		0x23 => "PrimaryEmailAddress",
		0x24 => "Accounts",
		0x25 => "Account",
		0x26 => "AccountId",
		0x27 => "AccountName",
		0x28 => "UserDisplayName",
		0x29 => "SendDisabled",

		0x2B => "RightsManagementInformation"
		);

	################################################################################
	# DocumentLibrary
	################################################################################

	$code_page_19 = array
		(
		0x05 => "LinkId",
		0x06 => "DisplayName",
		0x07 => "IsFolder",
		0x08 => "CreationDate",
		0x09 => "LastModifiedDate",
		0x0A => "IsHidden",
		0x0B => "ContentLength",
		0x0C => "ContentType"
		);

	################################################################################
	# ItemOperations
	################################################################################

	$code_page_20 = array
		(
		0x05 => "ItemOperations",
		0x06 => "Fetch",
		0x07 => "Store",
		0x08 => "Options",
		0x09 => "Range",
		0x0A => "Total",
		0x0B => "Properties",
		0x0C => "Data",
		0x0D => "Status",
		0x0E => "Response",
		0x0F => "Version",
		0x10 => "Schema",
		0x11 => "Part",
		0x12 => "EmptyFolderContents",
		0x13 => "DeleteSubFolders",
		0x14 => "UserName",
		0x15 => "Password",
		0x16 => "Move",
		0x17 => "DstFldId",
		0x18 => "ConversationId",
		0x19 => "MoveAlways"
		);

	################################################################################
	# ComposeMail
	################################################################################

	$code_page_21 = array
		(
		0x05 => "SendMail",
		0x06 => "SmartForward",
		0x07 => "SmartReply",
		0x08 => "SaveInSentItems",
		0x09 => "ReplaceMime",

		0x0B => "Source",
		0x0C => "FolderId",
		0x0D => "ItemId",
		0x0E => "LongId",
		0x0F => "InstanceId",
		0x10 => "Mime",
		0x11 => "ClientId",
		0x12 => "Status",
		0x13 => "AccountId",

		0x15 => "Forwardees",
		0x16 => "Forwardee",
		0x17 => "ForwardeeName",
		0x18 => "ForwardeeEmail"
		);

	################################################################################
	# Email2
	################################################################################

	$code_page_22 = array
		(
		0x05 => "UmCallerID",
		0x06 => "UmUserNotes",
		0x07 => "UmAttDuration",
		0x08 => "UmAttOrder",
		0x09 => "ConversationId",
		0x0A => "ConversationIndex",
		0x0B => "LastVerbExecuted",
		0x0C => "LastVerbExecutionTime",
		0x0D => "ReceivedAsBcc",
		0x0E => "Sender",
		0x0F => "CalendarType",
		0x10 => "IsLeapMonth",
		0x11 => "AccountId",
		0x12 => "FirstDayOfWeek",
		0x13 => "MeetingMessageType",

		0x15 => "IsDraft",
		0x16 => "Bcc",
		0x17 => "Send"
		);

	################################################################################
	# Notes
	################################################################################

	$code_page_23 = array
		(
		0x05 => "Subject",
		0x06 => "MessageClass",
		0x07 => "LastModifiedDate",
		0x08 => "Categories",
		0x09 => "Category"
		);

	################################################################################
	# RightsManagement
	################################################################################

	$code_page_24 = array
		(
		0x05 => "RightsManagementSupport",
		0x06 => "RightsManagementTemplates",
		0x07 => "RightsManagementTemplate",
		0x08 => "RightsManagementLicense",
		0x09 => "EditAllowed",
		0x0A => "ReplyAllowed",
		0x0B => "ReplyAllAllowed",
		0x0C => "ForwardAllowed",
		0x0D => "ModifyRecipientsAllowed",
		0x0E => "ExtractAllowed",
		0x0F => "PrintAllowed",
		0x10 => "ExportAllowed",
		0x11 => "ProgrammaticAccessAllowed",
		0x12 => "Owner",
		0x13 => "ContentExpiryDate",
		0x14 => "TemplateID",
		0x15 => "TemplateName",
		0x16 => "TemplateDescription",
		0x17 => "ContentOwner",
		0x18 => "RemoveRightsManagementProtection"
		);

	################################################################################
	# Find
	################################################################################

	$code_page_25 = array
		(
		0x05 => "Find",
		0x06 => "SearchId",
		0x07 => "ExecuteSearch",
		0x08 => "MailboxSearchCriterion",
		0x09 => "Query",
		0x0A => "Status",
		0x0B => "FreeText",
		0x0C => "Options",
		0x0D => "Range",
		0x0E => "DeepTraversal",


		0x11 => "Response",
		0x12 => "Result",
		0x13 => "Properties",
		0x14 => "Preview",
		0x15 => "HasAttachments",
		0x16 => "Total",
		0x17 => "DisplayCc",
		0x18 => "DisplayBcc",
		0x19 => "GalSearchCriterion"
		);

	################################################################################
	# ...
	################################################################################

	$code_page_254 = array
		(
		0x05 => "Annotations",
		0x06 => "Annotation",
		0x07 => "Name",
		0x08 => "Value"
		);

	################################################################################
	# ...
	################################################################################

	$retval = array
		(
		0x00 => $code_page_0,
		0x01 => $code_page_1,
		0x02 => $code_page_2,
		0x04 => $code_page_4,
		0x05 => $code_page_5,
		0x06 => $code_page_6,
		0x07 => $code_page_7,
		0x08 => $code_page_8,
		0x09 => $code_page_9,
		0x0A => $code_page_10,
		0x0B => $code_page_11,
		0x0C => $code_page_12,
		0x0D => $code_page_13,
		0x0E => $code_page_14,
		0x0F => $code_page_15,
		0x10 => $code_page_16,
		0x11 => $code_page_17,
		0x12 => $code_page_18,
		0x13 => $code_page_19,
		0x14 => $code_page_20,
		0x15 => $code_page_21,
		0x16 => $code_page_22,
		0x17 => $code_page_23,
		0x18 => $code_page_24,
		0x19 => $code_page_25,
		0xFE => $code_page_254
		);

	################################################################################
	# ...
	################################################################################

	return($retval);
	}
?>
