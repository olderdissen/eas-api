<?
define("ACTIVE_SYNC_DAT_DIR", __DIR__ . "/data");
define("ACTIVE_SYNC_LOG_DIR", __DIR__ . "/logs");
define("ACTIVE_SYNC_WEB_DIR", __DIR__ . "/web");

define("ACTIVE_SYNC_FILTER_ALL", 0);
define("ACTIVE_SYNC_FILTER_INCOMPLETE", 8);

define("ACTIVE_SYNC_REMOTE_WIPE", 1);
define("ACTIVE_SYNC_REMOTE_WIPE_ACCOUNT_ONLY", 2);
define("ACTIVE_SYNC_SLEEP", 5);
define("ACTIVE_SYNC_PING_MAX_FOLDERS", 300);

################################################################################

# PHP 5 >= 5.1.0, PHP 7

setlocale(LC_ALL, "de_DE.UTF-8");

date_default_timezone_set("UTC"); # all stored datetime uses utc zone !!!

ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
ini_set("log_errors", "On");
ini_set("max_execution_time", 30);

if(defined("ACTIVE_SYNC_LOG_DIR"))
	ini_set("error_log", ACTIVE_SYNC_LOG_DIR . "/error-" . date("Y-m-d") . ".txt");

# find . -type d -exec chmod 0755 {} \;
# find . -type f -exec chmod 0644 {} \;
# chown www-data:www-data -R *

################################################################################

function active_sync_create_fullname_from_data($data, $style = 2)
	{
	$style = min($style, 2);
	$style = max($style, 0);

	$styles = [
		0 => [
			"FirstName" => "",
			"MiddleName" => " ",
			"LastName" => " ",
			"Suffix" => " "
			],
		1 => [
			"LastName" => "",
			"FirstName" => ", ",
			"MiddleName" => " ",
			"Suffix" => ", "
			],
		2 => [
			"FirstName" => "",
			"MiddleName" => " ",
			"LastName" => " "
			]
		];

	$retval = [];

	foreach($styles[$style] as $token => $prefix)
		{
		if(! isset($data["Contacts"][$token]))
			continue;

		if(! strlen($data["Contacts"][$token]))
			continue;

		if($retval)
			$retval[] = $prefix;

		$retval[] = $data["Contacts"][$token];
		}

	$helper = [];

	foreach(["YomiLastName" => "", "YomiFirstName" => " "] as $token => $prefix)
		{
		if(! isset($data["Contacts"][$token]))
			continue;

		if(! strlen($data["Contacts"][$token]))
			continue;

		if($retval)
			$helper[] = $prefix;

		$helper[] = $data["Contacts"][$token];
		}

	# add yomi for non email and if we already have some name data
	if($style != 2)
		if($retval)
			if($helper)
				$retval[] = " <small>" . implode("", $helper) . "</small>";

	# replace empty full name
	foreach(["Contacts2:NickName", "Contacts:CompanyName", "Contacts:JobTitle"] as $items)
		{
		if($retval)
			break;

		list($codepage, $token) = explode(":", $items, 2);

		if(isset($data[$codepage][$token]))
			if($data[$codepage][$token])
				$retval[] = $data[$codepage][$token];
		}

	if(! count($retval))
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

function active_sync_create_guid($version = 4, $name = "localhost", $namespace = "{00000000-0000-0000-0000-000000000000}")
	{
	# $namespace could be /etc/machine-id
	# $name could be /etc/hostname

	$time_low	= 0;
	$time_mid	= 0;
	$time_hi	= 0;
	$clock_seq_high	= 0;
	$clock_seq_low	= 0;
	$node		= 0;

	# time-based version
	if($version == 1)
		{
		$time = gettimeofday();
		$time = ($time["sec"] * 10 * 1000 * 1000) + ($time["usec"] * 10) + 0x01B21DD213814000;

		$time_low	= ((intval($time / 0x00000001) >>  0) & 0xffffffff);
		$time_mid	= ((intval($time / 0xffffffff) >>  0) & 0x0000ffff);
		$time_hi	= ((intval($time / 0xffffffff) >> 16) & 0x0000ffff);
		}

	# DCE Security version, with embedded POSIX UIDs
	if($version == 2)
		{
		}

	# name-based version that uses MD5 hashing
	if($version == 3)
		{
		$namespace = hex2bin(str_replace(["-", "{", "}"], "", $namespace));

		$hash = md5($namespace . $name);

		$time_low	= hexdec(substr($hash, 0, 8));
		$time_mid	= hexdec(substr($hash, 8, 4));
		$time_hi	= hexdec(substr($hash, 12, 4));
		$clock_seq_high	= hexdec(substr($hash, 16, 2));
		$clock_seq_low	= hexdec(substr($hash, 18, 2));
		$node		= hexdec(substr($hash, 20, 12));
		}

	# randomly or pseudo-randomly generated version
	if($version == 4)
		{
		$time_low	= rand(0, 0xffffffff);
		$time_mid	= rand(0, 0xffff);
		$time_hi	= rand(0, 0xffff);
		$clock_seq_high	= rand(0, 0xff);
		$clock_seq_low	= rand(0, 0xff);
		$node		= rand(0, 0xffffffffffff);
		}

	# name-based version that uses SHA-1 hashing
	if($version == 5)
		{
		$namespace = hex2bin(str_replace(["-", "{", "}"], "", $namespace));

		$hash = sha1($namespace . $name);

		$time_low	= hexdec(substr($hash, 0, 8));
		$time_mid	= hexdec(substr($hash, 8, 4));
		$time_hi	= hexdec(substr($hash, 12, 4));
		$clock_seq_high	= hexdec(substr($hash, 16, 2));
		$clock_seq_low	= hexdec(substr($hash, 18, 2));
		$node		= hexdec(substr($hash, 20, 12));
		}

	# glue and return value

	return(sprintf("%08x-%04x-%04x-%02x%02x-%012x", $time_low, $time_mid, ($time_hi & 0x0FFF) | ($version << 12), ($clock_seq_high & 0x3F) | 0x80, $clock_seq_low, $node));
	}

function active_sync_create_guid_filename($user, $collection_id)
	{
	$tries = 0;

	while($tries < 20)
		{
		$server_id = active_sync_create_guid();

		if(! file_exists(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data"))
			return($server_id);

		$tries ++;
		}

	active_sync_debug("unique filename for collection $collection_id of user $user not created.", "ERROR");

	return(false);
	}

function active_sync_debug($expression, $type = "DEBUG")
	{
	if(! defined("ACTIVE_SYNC_LOG_DIR"))
		die(__FUNCTION__ . ": ACTIVE_SYNC_LOG_DIR not defined.");

	if(! is_dir(ACTIVE_SYNC_LOG_DIR))
		mkdir(ACTIVE_SYNC_LOG_DIR, 0755, true);

	if(! defined("ACTIVE_SYNC_DEBUG_HANDLE"))
		{
		$filename = date("Y-m-d");

		if(isset($_GET["DeviceId"]))
			$filename = $_GET["DeviceId"];

		define("ACTIVE_SYNC_DEBUG_HANDLE", fopen(ACTIVE_SYNC_LOG_DIR . "/debug-" . $filename . ".txt", "a+"));
		}

	if(defined("ACTIVE_SYNC_DEBUG_HANDLE"))
		{
		$timestamp = "TIME: " . date("Y-m-d H:i:s");

		$remote_port = "-";

		if(isset($_SERVER["REMOTE_PORT"]))
			$remote_port = $_SERVER["REMOTE_PORT"];

		$command = "-";

		if(isset($_GET["Cmd"]))
			$command = $_GET["Cmd"];

		$e = (strlen($expression) ? $expression : "EMPTY");

		$e = (strpos($expression, PHP_EOL) === false ? " " : PHP_EOL) . $e;

		fwrite(ACTIVE_SYNC_DEBUG_HANDLE, implode(" ", [$timestamp, $remote_port, $type, implode("", [$command, $e, PHP_EOL])]));
		}

#	openlog("active-sync", LOG_PID | LOG_PERROR, LOG_SYSLOG);
#	syslog(LOG_NOTICE, $c);
#	closelog();

	return(true);
	}

function active_sync_folder_create($user, $parent_id, $display_name, $type)
	{
	if(! active_sync_get_is_collection_id($user, $parent_id))
		return(5);

	if(active_sync_get_is_display_name($user, $display_name))
		return(2);

	if(! active_sync_get_is_type($type))
		return(10);

	if(active_sync_get_is_special_folder($type))
		return(3);

	if(! active_sync_get_is_user_folder($type))
		return(3);

	$server_id = active_sync_get_folder_free($user);

	if($server_id == 0)
		return(6);

	if(! mkdir(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $server_id, 0755, true))
		return(6);

	$settings_server = active_sync_get_settings_folder_server($user);

	$settings_server["SyncDat"][] = [
		"ServerId" => $server_id,
		"ParentId" => $parent_id,
		"Type" => $type,
		"DisplayName" => $display_name
		];

	active_sync_put_settings_folder_server($user, $settings_server);

	return(1);
	}

function active_sync_folder_delete($user, $server_id)
	{
	if(! active_sync_get_is_collection_id($user, $server_id))
		return(4);

	$type = active_sync_get_type_by_collection_id($user, $server_id);

	if(active_sync_get_is_special_folder($type))
		return(3);

	if(! active_sync_get_is_user_folder($type))
		return(3);

	$settings_server = active_sync_get_settings_folder_server($user);

	active_sync_folder_delete_helper($settings_server, $user, $server_id);

	active_sync_put_settings_folder_server($user, $settings_server);

	return(1);
	}

function active_sync_folder_delete_helper(& $folders, $user, $server_id)
	{
	foreach($folders["SyncDat"] as $id => $folder)
		if($folder["ParentId"] == $server_id)
			active_sync_folder_delete_helper($folders, $user, $folder["ServerId"]);
		elseif($folder["ServerId"] == $server_id)
			unset($folders["SyncDat"][$id]);

	foreach(scandir(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $server_id) as $file)
		if(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $server_id . "/" . $file))
			unlink(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $server_id . "/" . $file);

	rmdir(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $server_id);
	}

function active_sync_folder_init($user)
	{
	if(! defined("ACTIVE_SYNC_DAT_DIR"))
		die("ACTIVE_SYNC_DAT_DIR is not defined. have you included active_sync_kern.php before? ACTIVE_SYNC_DAT_DIR is needed to store settings and user data.");

	if(! is_dir(ACTIVE_SYNC_DAT_DIR))
		mkdir(ACTIVE_SYNC_DAT_DIR, 0755, true);

	$file = "/etc/apache2/conf-available/active-sync.conf";

	if(! file_exists($file))
		{
		$data = [
			'<IfModule mod_alias.c>',
			'	Alias /Microsoft-Server-ActiveSync ' . __DIR__ . '/index.php',
			'	Alias /autodiscover/autodiscover.xml ' . __DIR__ . '/index.php',
			'	Alias /Autodiscover/Autodiscover.xml ' . __DIR__ . '/index.php',
			'</IfModule>'
			];

#		file_put_contents($file, implode(PHP_EOL, $data));

#		system("systemctl reload apache2");
		}

	$file = ACTIVE_SYNC_DAT_DIR . "/.htaccess";

	if(! file_exists($file))
		{
		$data = [
			'<Files "*">',
			'	Order allow,deny',
			'	Deny from all',
			'</Files>'
			];

		file_put_contents($file, implode(PHP_EOL, $data));
		}

	if(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $user))
		mkdir(ACTIVE_SYNC_DAT_DIR . "/" . $user, 0755, true);

	$settings = active_sync_get_settings_folder_server($user);

	if(! isset($settings["SyncDat"]))
		{
		$settings["SyncDat"] = active_sync_get_default_folder();

		active_sync_put_settings_folder_server($user, $settings);
		}

	foreach($settings["SyncDat"] as $folder)
		if(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $folder["ServerId"]))
			mkdir(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $folder["ServerId"], 0755, true);

	if(! defined("ACTIVE_SYNC_LOG_DIR"))
		return;

	if(! is_dir(ACTIVE_SYNC_LOG_DIR))
		mkdir(ACTIVE_SYNC_LOG_DIR, 0755, true);
	}

function active_sync_folder_update($user, $server_id, $parent_id, $display_name) # bogus ? cannot rename system folder
	{
	if(! active_sync_get_is_collection_id($user, $server_id))
		return(4);

	if(! active_sync_get_is_collection_id($user, $parent_id))
		return(5);

	if(active_sync_get_is_display_name($user, $display_name))
		return(2);

	$type = active_sync_get_type_by_collection_id($user, $server_id);

	if($type == 19)
		return(3);

	if(active_sync_get_is_special_folder($type))
		return(2);

	if(! active_sync_put_display_name($user, $server_id, $display_name))
		return(6);

	if(! active_sync_put_parent_id($user, $server_id, $parent_id))
		return(6);

	return(1);
	}

function active_sync_get_calendar_by_uid($user, $uid)
	{
	$collection_id = active_sync_get_collection_id_by_type($user, 8);

	$settings = active_sync_get_settings_files_server($user, $collection_id);

	if(isset($settings["SyncDat"]))
		foreach($settings["SyncDat"] as $server_id => $timestamp)
			{
			$data = active_sync_get_settings_data($user, $collection_id, $server_id);

			if(isset($data["Calendar"]["UID"]))
				if($data["Calendar"]["UID"] == $uid)
					return($server_id);
			}

	return(false);
	}

function active_sync_get_class_by_collection_id($user, $collection_id)
	{
	$type = active_sync_get_type_by_collection_id($user, $collection_id);

	$class = active_sync_get_class_by_type($type);

	return($class);
	}

function active_sync_get_class_by_type($type)
	{
	$table = [
		1 => "",		# User-created folder (generic)
		2 => "Email",		# Default Inbox folder
		3 => "Email",		# Default Drafts folder
		4 => "Email",		# Default Deleted Items folder
		5 => "Email",		# Default Sent Items folder
		6 => "Email",		# Default Outbox folder
		7 => "Tasks",		# Default Tasks folder
		8 => "Calendar",	# Default Calendar folder
		9 => "Contacts",	# Default Contacts folder
		10 => "Notes",		# Default Notes folder
		11 => "Journal",	# Default Journal folder
		12 => "Email",		# User-created Mail folder
		13 => "Calendar",	# User-created Calendar folder
		14 => "Contacts",	# User-created Contacts folder
		15 => "Tasks",		# User-created Tasks folder
		16 => "Journal",	# User-created Journal folder
		17 => "Notes",		# User-created Notes folder
		18 => "",		# Unknown folder type
		19 => ""		# Recipient information cache
		];

	$type = (($type < 1) || ($type > 19) ? 18 : $type);

	return($table[$type]);
	}

function active_sync_get_collection_id_by_display_name($user, $display_name)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(isset($settings["SyncDat"]))
		foreach($settings["SyncDat"] as $folder)
			if($folder["DisplayName"] == $display_name)
				return($folder["ServerId"]);

	active_sync_debug("collection with display name $display_name of user $user not found.", "ERROR");

	return(false);
	}

function active_sync_get_collection_id_by_type($user, $type)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(isset($settings["SyncDat"]))
		foreach($settings["SyncDat"] as $folder)
			if($folder["Type"] == $type)
				return($folder["ServerId"]);

	active_sync_debug("collection with type $type of user $user not found.", "ERROR");

	return(false);
	}

function active_sync_get_default_attachment()
	{
	$retval = [
		"AttMethod"		=> "",
		"AttName"		=> "",
		"AttOid"		=> "",
		"AttSize"		=> "",

		"ContentId"		=> "",
		"ContentLocation"	=> "",
		"DisplayName"		=> "",
		"EstimatedDataSize"	=> 0,
		"FileReference"		=> "",
		"IsInline"		=> 0,
		"Method"		=> 1,

		"UmAttDuration"		=> 0,
		"UmAttOrder"		=> 0,
		"UmCallerID"		=> 0,
		"UmUserNotes"		=> 0
		];

	return($retval);
	}

function active_sync_get_default_attendee()
	{
	$retval = [
		"AttendeeStatus"	=> 0,
		"AttendeeType"		=> 1,
		"Email"			=> "",
		"Name"			=> ""
		];

	return($retval);
	}

function active_sync_get_default_body()
	{
	$retval = [
		"Data"			=> "",
		"EstimatedDataSize"	=> 0,
		"Type"			=> 1
		];

	return($retval);
	}

function active_sync_get_default_calendar()
	{
	$retval = [
		"TimeZone"			=> "",
		"AllDayEvent"			=> 0,
		# Body
		"BodyTruncated"			=> 0,
		"BusyStatus"			=> 2,
		"OrganizerName"			=> "",
		"OrganizerEmail"		=> "",
		"DtStamp"			=> date("Ymd\THis\Z"),
		"EndTime"			=> date("Ymd\THis\Z"),
		"Location"			=> "",
		"Reminder"			=> 0,
		"Sensitivity"			=> 0,
		"Subject"			=> "",
		"StartTime"			=> date("Ymd\THis\Z"),
		"UID"				=> active_sync_create_guid(),
		"MeetingStatus"			=> 0,
		# Attendees
		# Categories
		# Recurrences
		# Exceptions
		"ResponseRequested"		=> 0,
		"AppointmentReplyTime"		=> "",
		"ResponseType"			=> 0,
		"DisallowNewTimeProposal"	=> 0,
		"OnlineMeetingConfLink"		=> "",
		"OnlineMeetingExternalLink"	=> ""
		];

	return($retval);
	}

function active_sync_get_default_contacts($Class = "Contact")
	{
	$retval = [];

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
		$retval["Alias"]		= "";
		$retval["FileAs"]		= "";
		$retval["WeightedRank"]		= "";
		$retval["Email1Address"]	= "";
		}

	return($retval);
	}

function active_sync_get_default_contacts2()
	{
	$retval = [
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
		];

	return($retval);
	}

function active_sync_get_default_email()
	{
	$retval = [
		"To"			=> "",
		"Cc"			=> "",
		"From"			=> "",
		"Subject"		=> "",
		"ReplyTo"		=> "",
		"DateReceived"		=> "",
		"DisplayTo"		=> "",
		"ThreadTopic"		=> "",
		"Importance"		=> "",
		"Read"			=> "",
		"MessageClass"		=> "IPM.Note",
		# MeetingRequest
		"InternetCPID"		=> "",
		# Flag
		"ContentClass"		=> "urn:content-classes:message",
		# Categories
		# Attachments
		# Body
		# BodySize
		# BodyTruncated
		"MIMEData"		=> "",
		"MIMESize"		=> 0,
		"MIMETruncated"		=> 0
		];

	return($retval);
	}

function active_sync_get_default_email2()
	{
	$retval = [
		# UmCallerID
		# UmUserNotes
		# UmAttDuration
		# UmAttOrder
		"ConversationId"		=> "",
		"ConversationIndex"		=> "",
		"LastVerbExecuted"		=> "",
		"LastVerbExecutionTime"		=> "",
		"ReceivedAsBcc"			=> 0,
		"Sender"			=> "",
		# CalendarType
		# IsLeapMonth
		"AccountId"			=> ""
		# MeetingMessageType
		# Bcc
		# IsDraft
		# Send
		];

	return($retval);
	}

function active_sync_get_default_exception()
	{
	$retval = [
		"Deleted"		=> 0,
		"ExceptionStartTime"	=> "",
		"EndTime"		=> "",
		"Location"		=> "",
		"Sensitivity"		=> "",
		"BusyStatus"		=> "",
		"AllDayEvent"		=> 0,
		"Reminder"		=> 1440,
		"DtStamp"		=> "",
		"MeetingStatus"		=> "",
		"AppointmentReplyTime"	=> "",
		"ResponseType"		=> ""
		];

	return($retval);
	}

function active_sync_get_default_filter()
	{
	$retval = [
		"Email"		=> [0, 1, 2, 3, 4, 5],
		"Calendar"	=> [1, 4, 5, 6, 7],
		"Tasks"		=> [0, 8]
		];

	return($retval);
	}

function active_sync_get_default_flag($class = "Tasks")
	{
	$retval = [];

	if($class == "Email")
		{
		$retval["CompleteTime"]		= "";
		$retval["FlagType"]		= "";
#		$retval["Status"]		= ""; # ???
		}

	if($class == "Tasks")
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
	$retval = [
			[
				"ServerId" => 9002,
				"ParentId" => 0,
				"Type" => 2,
				"DisplayName" => "Inbox"
			],
			[
				"ServerId" => 9003,
				"ParentId" => 0,
				"Type" => 3,
				"DisplayName" => "Drafts"
			],
			[
				"ServerId" => 9004,
				"ParentId" => 0,
				"Type" => 4,
				"DisplayName" => "Deleted Items"
			],
			[
				"ServerId" => 9005,
				"ParentId" => 0,
				"Type" => 5,
				"DisplayName" => "Sent Items"
			],
			[
				"ServerId" => 9006,
				"ParentId" => 0,
				"Type" => 6,
				"DisplayName" => "Outbox"
			],

			[
				"ServerId" => 9007,
				"ParentId" => 0,
				"Type" => 7,
				"DisplayName" => "Tasks"
			],
			[
				"ServerId" => 9008,
				"ParentId" => 0,
				"Type" => 8,
				"DisplayName" => "Calendar"
			],
			[
				"ServerId" => 9009,
				"ParentId" => 0,
				"Type" => 9,
				"DisplayName" => "Contacts"
			],
			[
				"ServerId" => 9010,
				"ParentId" => 0,
				"Type" => 10,
				"DisplayName" => "Notes"
			]
		];

	return($retval);
	}

function active_sync_get_default_info()
	{
	$retval = [
		"Model"			=> "",
		"Imei"			=> "",
		"FriendlyName"		=> "",
		"OS"			=> "",
		"OSLanguage"		=> "",
		"PhoneNumber"		=> "",
		"UserAgent"		=> "",
		"EnableOutboundSMS"	=> 1,
		"MobileOperator"	=> ""
		];

	return($retval);
	}

function active_sync_get_default_location()
	{
	$retval = [
		"Accuracy"		=> 0,
		"Altitude"		=> 0,
		"AltitudeAccuracy"	=> 0,
		"Annotation"		=> "...",
		"City"			=> "",
		"Country"		=> "",
		"DisplayName"		=> "...",
		"Latitude"		=> 0,
		"LocationUri"		=> "",
		"Longitude"		=> 0,
		"PostalCode"		=> "",
		"State"			=> "",
		"Street"		=> ""
		];

	return($retval);
	}

function active_sync_get_default_login()
	{
	$retval = [
		"User"		=> "",
		"Pass"		=> "",
		"IsAdmin"	=> "F",

		"DisplayName"	=> "",
		"FirstName"	=> "",
		"LastName"	=> ""
		];

	return($retval);
	}

function active_sync_get_default_meeting()
	{
	$retval = [
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
		];

	return($retval);
	}

function active_sync_get_default_notes()
	{
	$retval = [
		"Subject"		=> "",
		"MessageClass"		=> "IPM.StickyNote",
		"LastModifiedDate"	=> date("Y-m-d\TH:i:s\Z")
		# Categories
		];

	return($retval);
	}

function active_sync_get_default_policy()
	{
	$retval = [
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
		];

	return($retval);
	}

function active_sync_get_default_recurrence($Class = "Calendar")
	{
	$retval = [];

	if($Class == "Calendar")
		{
		$retval["Type"]			= 4;
		$retval["Occurrences"]		= 1;
		$retval["Interval"]		= 1;
		$retval["WeekOfMonth"]		= 1;
		$retval["DayOfWeek"]		= 0;
		$retval["MonthOfYear"]		= 1;
		$retval["Until"]		= date("Y-m-d\TH:i:s\Z", strtotime("+ 10 years"));
		$retval["DayOfMonth"]		= 1;
		$retval["CalendarType"]		= 0;
		$retval["IsLeapMonth"]		= 0;
		$retval["FirstDayOfWeek"]	= 1;
		}

	if($Class == "Email")
		{
		$retval["Type"]			= 4;
		$retval["Interval"]		= 1;
		$retval["Until"]		= date("Y-m-d\TH:i:s\Z", strtotime("+ 10 years"));
		$retval["Occurrences"]		= 1;
		$retval["WeekOfMonth"]		= 1;
		$retval["DayOfMonth"]		= 1;
		$retval["DayOfWeek"]		= 0;
		$retval["MonthOfYear"]		= 1;

		# email2 !!!
		$retval["CalendarType"]		= 0;
		$retval["IsLeapMonth"]		= 0;
		$retval["FirstDayOfWeek"]	= 1;
		}

	if($Class == "Tasks")
		{
		$retval["Type"]			= 4;
		$retval["Start"]		= date("Y-m-d\TH:i:s\Z");
		$retval["Until"]		= date("Y-m-d\TH:i:s\Z", strtotime("+ 10 years"));
		$retval["Occurrences"]		= 1;
		$retval["Interval"]		= 1;
		$retval["DayOfWeek"]		= 0;
		$retval["DayOfMonth"]		= 1;
		$retval["WeekOfMonth"]		= 1;
		$retval["MonthOfYear"]		= 1;
		$retval["Regenerate"]		= 0;
		$retval["DeadOccur"]		= 0;
		$retval["CalendarType"]		= 0;
		$retval["IsLeapMonth"]		= 0;
		$retval["FirstDayOfWeek"]	= 1;
		}

	return($retval);
	}

function active_sync_get_default_rights_management()
	{
	$retval = [
		"ContentExpiryDate"		=> date("Y-m-d\TH:i:s\Z"),
		"ContentOwner"			=> "",
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
		];

	return($retval);
	}

function active_sync_get_default_settings()
	{
	$retval = [
		"Language"		=> "en",	# en
		"TimeZone"		=> 28,		# de
		"PhoneOnly"		=> 0,		# PhoneOnly
		"SortBy"		=> 0,		# SortBy
		"ShowBy"		=> 0,		# DisplayBy
		"Reminder"		=> 1440,	# 1 day
		"FirstDayOfWeek"	=> 1,		# Monday
		"CalendarSync"		=> 0		# All
		];

	return($retval);
	}

function active_sync_get_default_sms()
	{
	$retval = active_sync_get_default_email();

	return($retval);
	}

function active_sync_get_default_tasks($class = "Tasks")
	{
	$retval = [];

	if($class == "Email")
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

	if($class == "Tasks")
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

function active_sync_get_display_name_by_collection_id($user, $server_id)
	{
	$folders = active_sync_get_settings_folder_server($user);

	foreach($folders as $folder)
		if($folder["ServerId"] == $server_id)
			return($folder["DisplayName"]);

	return(false);
	}

function active_sync_get_domain()
	{
	$settings = active_sync_get_settings_server();

	return(isset($settings["domain"]) ? $settings["domain"] : "localhost");
	}

function active_sync_get_folder_free($user_id)
	{
	foreach(range(1000, 8999) as $collection_id)
		if(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $user_id . "/" . $collection_id))
			return($collection_id);

	return(false);
	}

function active_sync_get_is_collection_id($user, $collection_id)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(isset($settings["SyncDat"]))
		foreach($settings["SyncDat"] as $folder)
			if($folder["ServerId"] == $collection_id)
				return(true);

	return($collection_id == 0);
	}

function active_sync_get_is_display_name($user, $display_name)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(isset($settings["SyncDat"]))
		foreach($settings["SyncDat"] as $folder)
			if($folder["DisplayName"] == $display_name)
				return(true);

	return(false);
	}

function active_sync_get_is_identified($request)
	{
	$settings = active_sync_get_settings_server();

	if(isset($settings["login"]))
		foreach($settings["login"] as $login)
			if($login["User"] == $request["AuthUser"])
				return($login["Pass"] == $request["AuthPass"]);

	return(false);
	}

function active_sync_get_is_known_mail($user, $collection_id, $email_address)
	{
	foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		foreach(["Email1Address", "Email2Address", "Email3Address"] as $token)
			{
			if(! isset($data["Contacts"][$token]))
				continue;

			list($data_name, $data_mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

			if(strtolower($data_mail) == strtolower($email_address))
				return(true);
			}
		}

	return(false);
	}

function active_sync_get_is_special_folder($type)
	{
	return(in_array($type, [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 19]));
	}

function active_sync_get_is_system_folder($type)
	{
	return(in_array($type, [2, 3, 4, 5, 6, 7, 8, 9, 10, 11]));
	}

function active_sync_get_is_type($type)
	{
	return(($type < 1) || ($type > 19) ? false : true);
	}

function active_sync_get_is_user_folder($type)
	{
	return(in_array($type, [1, 12, 13, 14, 15, 16, 17]));
	}

# doesn't work so far, but also not needed yet

function active_sync_get_message_id_by_server_id($message_id)
	{
	}

function active_sync_get_ms_global_obj_id_by_ms_uid($expression)
	{
	$time = gettimeofday();
	$time = ($time["sec"] * 10000000) + ($time["usec"] * 10) + 0x01B21DD213814000;

	$retval = [];

	if(strlen($expression) == 38) # VCALID
		{
		$retval["CLASSID"]	= pack("H*", str_replace(["{", "}", "-"], "", "{04000000-8200-E000-74C5-B7101A82E008}"));
		$retval["INSTDATE"]	= pack("CCCC", 0, 0, 0, 0);
		$retval["NOW"]		= pack("VV", (intval($time / 0x00000001) >>  0) & 0xFFFFFFFF, (intval($time / 0xFFFFFFFF) >>  0) & 0xFFFFFFFF);
		$retval["ZERO"]		= str_repeat(chr(0x00), 8);
		$retval["BYTECOUNT"]	= pack("V", 0);
		$retval["DATA"]		= "vCal-Uid" . pack("V", 1) . pack("H*", str_replace(["{", "}", "-"], "", $expression)) . "\x00";

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
	$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	$settings_server = active_sync_get_settings_folder_server($request["AuthUser"]);

	foreach($settings_server["SyncDat"] as $server_id => $server_data)
		{
		$known = false;

		foreach($settings_client["SyncDat"] as $client_id => $client_data)
			if($server_data["ServerId"] == $client_data["ServerId"])
				if($server_data["ParentId"] == $client_data["ParentId"])
					if($server_data["DisplayName"] == $client_data["DisplayName"])
						if($server_data["Type"] == $client_data["Type"])
							$known = true;

		if(! $known)
			return(true);
		}

	foreach($settings_client["SyncDat"] as $client_id => $client_data)
		{
		$known = false;

		foreach($settings_server["SyncDat"] as $server_id => $server_data)
			if($client_data["ServerId"] == $server_data["ServerId"])
				if($client_data["ParentId"] == $server_data["ParentId"])
					if($client_data["DisplayName"] == $server_data["DisplayName"])
						if($client_data["Type"] == $server_data["Type"])
							$known = true;

		if(! $known)
			return(true);
		}

	return(false);
	}

function active_sync_get_need_provision($request)
	{
	$settings_server = active_sync_get_settings_server();

	$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if(! isset($settings_server["Policy"]["PolicyKey"]))
		return(isset($settings_client["PolicyKey"]));

	if(! isset($settings_client["PolicyKey"]))
		return(true);

	if($settings_server["Policy"]["PolicyKey"] != $settings_client["PolicyKey"])
		return(true);

	if($request["PolicyKey"] != 0)
		return($request["PolicyKey"] != $settings_server["Policy"]["PolicyKey"]);

	return($request["Cmd"] != "Ping");
	}

function active_sync_get_need_wipe($request)
	{
	$settings = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	return(isset($settings["Wipe"]));
	}

function active_sync_get_parent_id_by_collection_id($user, $server_id)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(isset($settings["SyncDat"]))
		foreach($settings["SyncDat"] as $folder)
			if($folder["ServerId"] == $server_id)
				return($folder["ParentId"]);

	return(false);
	}

function active_sync_get_settings($file)
	{
	clearstatcache();

	if(file_exists($file))
		$retval = file_get_contents($file);
	else
		$retval = "";

	if(! strlen($retval))
		$retval = [];
	elseif(in_array($retval[0], ["a", "i", "s"]))
		$retval = unserialize($retval);
	elseif(in_array($retval[0], ["[", "{"]))
		$retval = json_decode($retval, true);
#	elseif(in_array($retval[0], ["<"]))
#		$retval = new SimpleXMLElement($retval)->asXML();
	else
		$retval = [];

	return($retval);
	}

function active_sync_get_settings_data($user, $collection_id, $server_id)
	{
	$retval = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data");

	return($retval);
	}

function active_sync_get_settings_server()
	{
	$retval = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/login.data");

	return($retval);
	}

function active_sync_get_settings_files_client($user, $collection_id, $device_id)
	{
	$retval = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $device_id . ".sync");

	foreach(["SyncKey" => 0, "SyncDat" => []] as $key => $value)
		if(! isset($retval[$key]))
			$retval[$key] = $value;

	return($retval);
	}

function active_sync_get_settings_files_server($user, $collection_id)
	{
	$retval = ["SyncDat" => []];

	foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$retval["SyncDat"][$server_id] = filemtime(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data");
		}

	return($retval);
	}

function active_sync_get_settings_folder_client($user, $device_id)
	{
	$retval = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $device_id . ".sync");

	foreach(["SyncKey" => 0, "SyncDat" => []] as $key => $value)
		if(! isset($retval[$key]))
			$retval[$key] = $value;

	return($retval);
	}

function active_sync_get_settings_folder_server($user)
	{
	$retval = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . ".sync");

	$data = active_sync_get_default_folder();

	foreach(["SyncKey" => 0, "SyncDat" => $data] as $key => $value)
		if(! isset($retval[$key]))
			$retval[$key] = $value;

	return($retval);
	}

function active_sync_get_supported_commands()
	{
	$retval = [];

	$handles = active_sync_get_table_handle();

	foreach($handles as $command => $function)
		if(function_exists($function))
			$retval[] = $command;

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
	$table = [
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
		];

	return($table);
	}

function active_sync_get_table_handle()
	{
	$table = [
		"Sync"			=> "active_sync_handle_sync",
		"SendMail"		=> "active_sync_handle_send_mail",
		"SmartForward"		=> "active_sync_handle_smart_forward",
		"SmartReply"		=> "active_sync_handle_smart_reply",
		"GetAttachment"		=> "active_sync_handle_get_attachment",
		"GetHierarchy"		=> "active_sync_handle_get_hierarchy",		# DEPRECATED
		"CreateCollection"	=> "active_sync_handle_create_collection",	# DEPRECATED
		"DeleteCollection"	=> "active_sync_handle_delete_collection",	# DEPRECATED
		"MoveCollection"	=> "active_sync_handle_move_collection",	# DEPRECATED
		"FolderSync"		=> "active_sync_handle_folder_sync",
		"FolderCreate"		=> "active_sync_handle_folder_create",
		"FolderDelete"		=> "active_sync_handle_folder_delete",
		"FolderUpdate"		=> "active_sync_handle_folder_update",
		"MoveItems"		=> "active_sync_handle_move_items",
		"GetItemEstimate"	=> "active_sync_handle_get_item_estimate",
		"MeetingResponse"	=> "active_sync_handle_meeting_response",
		"Search"		=> "active_sync_handle_search",
		"Settings"		=> "active_sync_handle_settings",
		"Ping"			=> "active_sync_handle_ping",
		"ItemOperations"	=> "active_sync_handle_item_operations",
		"Provision"		=> "active_sync_handle_provision",
		"ResolveRecipients"	=> "active_sync_handle_resolve_recipients",
		"ValidateCert"		=> "active_sync_handle_validate_cert"
		];

	return($table);
	}

function active_sync_get_table_method()
	{
	$table = [
		"GET"		=> "active_sync_http_method_get", # used by web interface
		"POST"		=> "active_sync_http_method_post",
#		"PUT"		=> "active_sync_http_method_put",
#		"PATCH"		=> "active_sync_http_method_patch",
#		"DELETE"	=> "active_sync_http_method_delete",
#		"HEAD"		=> "active_sync_http_method_read",
		"OPTIONS"	=> "active_sync_http_method_options",
#		"CONNECT"	=> "active_sync_http_method_connect",
#		"TRACE"		=> "active_sync_http_method_trace"
		];

	return($table);
	}

function active_sync_get_table_policy()
	{
	# type ::= C (checkbox) | L (textarea) | R (radio) | S (select) | T (text)

	$table = [
			[
			"Name" => "AllowBluetooth",
			"Type" => "S",
			"Values" => array
				(
				0 => "Disable Bluetooth.",
				1 => "Disable Bluetooth, but allow the configuration of hands-free profiles.",
				2 => "Allow Bluetooth."
				)
			],
			[
			"Name" => "AllowBrowser",
			"Type" => "S",
			"Values" => array
				(
				0 => "Do not allow the use of a web browser.",
				1 => "Allow the use of a web browser."
				)
			],
			[
			"Name" => "AllowCamera",
			"Type" => "S",
			"Values" => array
				(
				0 => "Use of the camera is not allowed.",
				1 => "Use of the camera is allowed."
				)
			],
			[
			"Name" => "AllowConsumerEmail",
			"Type" => "S",
			"Values" => array
				(
				0 => "Do not allow the user to configure a personal email account.",
				1 => "Allow the user to configure a personal email account."
				)
			],
			[
			"Name" => "AllowDesktopSync",
			"Type" => "S",
			"Values" => array
				(
				0 => "Do not allow Desktop ActiveSync.",
				1 => "Allow Desktop ActiveSync."
				)
			],
			[
			"Name" => "AllowHTMLEmail",
			"Type" => "S",
			"Values" => array
				(
				0 => "HTML-formatted email is not allowed.",
				1 => "HTML-formatted email is allowed."
				)
			],
			[
			"Name" => "AllowInternetSharing",
			"Type" => "S",
			"Values" => array
				(
				0 => "Do not allow the use of Internet Sharing.",
				1 => "Allow the use of Internet Sharing."
				)
			],
			[
			"Name" => "AllowIrDA",
			"Type" => "S",
			"Values" => array
				(
				0 => "Disable IrDA.",
				1 => "Allow IrDA."
				)
			],
			[
			"Name" => "AllowPOPIMAPEmail",
			"Type" => "S",
			"Values" => array
				(
				0 => "POP or IMAP email access is not allowed.",
				1 => "POP or IMAP email access is allowed."
				)
			],
			[
			"Name" => "AllowRemoteDesktop",
			"Type" => "S",
			"Values" => array
				(
				0 => "Do not allow the use of Remote Desktop.",
				1 => "Allow the use of Remote Desktop."
				)
			],
			[
			"Name" => "AllowSimpleDevicePassword",
			"Type" => "S",
			"Values" => array
				(
				0 => "Simple passwords are not allowed.",
				1 => "Simple passwords are allowed."
				)
			],
			[
			"Name" => "AllowSMIMEEncryptionAlgorithmNegotiation",
			"Type" => "S",
			"Values" => array
				(
				0 => "Do not negotiate.",
				1 => "Negotiate a strong algorithm.",
				2 => "Negotiate any algorithm."
				)
			],
			[
			"Name" => "AllowSMIMESoftCerts",
			"Type" => "S",
			"Values" => array
				(
				0 => "Soft certificates are not allowed.",
				1 => "Soft certificates are allowed."
				)
			],
			[
			"Name" => "AllowStorageCard",
			"Type" => "S",
			"Values" => array
				(
				0 => "SD card use is not allowed.",
				1 => "SD card use is allowed."
				)
			],
			[
			"Name" => "AllowTextMessaging",
			"Type" => "S",
			"Values" => array
				(
				0 => "SMS or text messaging is not allowed.",
				1 => "SMS or text messaging is allowed."
				)
			],
			[
			"Name" => "AllowUnsignedApplications",
			"Type" => "S",
			"Values" => array
				(
				0 => "Unsigned applications are not allowed to execute.",
				1 => "Unsigned applications are allowed to execute."
				)
			],
			[
			"Name" => "AllowUnsignedInstallationPackages",
			"Type" => "S",
			"Values" => array
				(
				0 => "Unsigned cabinet (.cab) files are not allowed to be installed.",
				1 => "Unsigned cabinet (.cab) files are allowed to be installed."
				)
			],
			[
			"Name" => "AllowWiFi",
			"Type" => "S",
			"Values" => array
				(
				0 => "The use of Wi-Fi connections is not allowed.",
				1 => "The use of Wi-Fi connections is allowed."
				)
			],
			[
			"Name" => "AlphanumericDevicePasswordRequired",
			"Type" => "S",
			"Values" => array
				(
				0 => "Alphanumeric device password is not required.",
				1 => "Alphanumeric device password is required."
				)
			],
			[
			"Name" => "ApprovedApplicationList",
			"Type" => "L",
			"Label" => "Hash"
			],
			[
			"Name" => "AttachmentsEnabled",
			"Type" => "S",
			"Values" => array
				(
				0 => "Attachments are not allowed to be downloaded.",
				1 => "Attachments are allowed to be downloaded."
				)
			],
			[
			"Name" => "DevicePasswordEnabled",
			"Type" => "S",
			"Values" => array
				(
				0 => "Device password is not required.",
				1 => "Device password is required."
				)
			],
			[
			"Name" => "DevicePasswordExpiration",
			"Type" => "T",
			"Length" => 4,
			"Label" => "day(s)"
			],
			[
			"Name" => "DevicePasswordHistory",
			"Type" => "T",
			"Length" => 4,
			"Label" => "entry(s)"
			],
			[
			"Name" => "MaxAttachmentSize",
			"Type" => "T",
			"Length" => 8,
			"Label" => "byte(s)",
			"Min" => 0,
			"Max" => 99999999
			],
			[
			"Name" => "MaxCalendarAgeFilter",
			"Type" => "S",
			"Values" => array
				(
				0 => "All days",
				4 => "2 weeks",
				5 => "1 month",
				6 => "3 months",
				7 => "6 month"
				)
			],
			[
			"Name" => "MaxDevicePasswordFailedAttempts",
			"Type" => "T",
			"Length" => 2,
			"Label" => "tries(s)",
			"Min" => 4,
			"Max" => 16
			],
			[
			"Name" => "MaxEmailAgeFilter",
			"Type" => "S",
			"Values" => array
				(
				0 => "Sync all",
				1 => "1 day",
				2 => "3 days",
				3 => "1 week",
				4 => "2 weeks",
				5 => "1 month"
				)
			],
			[
			"Name" => "MaxEmailBodyTruncationSize",
			"Type" => "T",
			"Length" => 8,
			"Label" => "byte(s)",
			"Min" => 0,
			"Max" => 99999999
			],
			[
			"Name" => "MaxEmailHTMLBodyTruncationSize",
			"Type" => "T",
			"Length" => 8,
			"Label" => "byte(s)",
			"Min" => 0,
			"Max" => 99999999
			],
			[
			"Name" => "MaxInactivityTimeDeviceLock",
			"Type" => "T",
			"Length" => 4,
			"Label" => "second(s)",
			"Min" => 0,
			"Max" => 9999
			],
			[
			"Name" => "MinDevicePasswordComplexCharacters",
			"Type" => "T",
			"Length" => 2,
			"Label" => "char(s)",
			"Min" => 1,
			"Max" => 4
			],
			[
			"Name" => "MinDevicePasswordLength",
			"Type" => "T",
			"Length" => 2,
			"Label" => "chars(s)",
			"Min" => 1,
			"Max" => 16
			],
			[
			"Name" => "PasswordRecoveryEnabled",
			"Type" => "S",
			"Values" => array
				(
				0 => "Password recovery is not enabled on the server.",
				1 => "Password recovery is enabled on the server."
				)
			],
			[
			"Name" => "RequireDeviceEncryption",
			"Type" => "S",
			"Values" => array
				(
				0 => "Encryption is not required.",
				1 => "Encryption is required."
				)
			],
			[
			"Name" => "RequireEncryptedSMIMEMessages",
			"Type" => "S",
			"Values" => array
				(
				0 => "Encrypted email messages are not required.",
				1 => "Email messages are required to be encrypted."
				)
			],
			[
			"Name" => "RequireEncryptionSMIMEAlgorithm",
			"Type" => "S",
			"Values" => array
				(
				0 => "TripleDES algorithm",
				1 => "DES algorithm",
				2 => "RC2 128bit",
				3 => "RC2 64bit",
				4 => "RC2 40bit"
				)
			],
			[
			"Name" => "RequireManualSyncWhenRoaming",
			"Type" => "S",
			"Values" => array
				(
				0 => "Do not require manual sync; allow direct push when roaming.",
				1 => "Require manual sync when roaming."
				)
			],
			[
			"Name" => "RequireSignedSMIMEAlgorithm",
			"Type" => "S",
			"Values" => array
				(
				0 => "Use SHA1.",
				1 => "Use MD5."
				)
			],
			[
			"Name" => "RequireSignedSMIMEMessages",
			"Type" => "S",
			"Values" => array
				(
				0 => "Signed S/MIME messages are not required.",
				1 => "Signed S/MIME messages are required."
				)
			],
			[
			"Name" => "RequireStorageCardEncryption",
			"Type" => "S",
			"Values" => array
				(
				0 => "Encryption of the device storage card is not required.",
				1 => "Encryption of the device storage card is required."
				)
			],
			[
			"Name" => "UnapprovedInROMApplicationList",
			"Type" => "L",
			"Label" => "ApplicationName"
			]
		];

	return($table);
	}

function active_sync_get_table_timezone_information()
	{
	$_00 = [0,  0,  0,  0,  0,  0,  0,  0];
	$_01 = [0, 11,  0,  1,  2,  0,  0,  0];
	$_02 = [0,  3,  0,  2,  2,  0,  0,  0];
	$_03 = [0, 10,  0,  4,  2,  0,  0,  0];
	$_04 = [0,  4,  0,  1,  2,  0,  0,  0];
	$_05 = [0,  3,  6,  2,  0,  0,  0,  0];
	$_06 = [0, 10,  6,  2,  0,  0,  0,  0];
	$_07 = [0, 11,  0,  1,  1,  0,  0,  0];
	$_08 = [0,  3,  0,  2,  1,  0,  0,  0];
	$_09 = [0, 10,  0,  4,  3,  0,  0,  0];
	$_10 = [0,  3,  0,  5,  4,  0,  0,  0];
	$_11 = [0,  2,  0,  3,  4,  0,  0,  0];
	$_12 = [0, 10,  0,  3,  6,  0,  0,  0];
	$_13 = [0, 10,  0,  1,  2,  0,  0,  0];
	$_14 = [0,  3,  0,  5,  1,  0,  0,  0];
	$_15 = [0, 10,  0,  5,  3,  0,  0,  0];
	$_16 = [0,  3,  0,  4,  2,  0,  0,  0];
	$_17 = [0,  3,  0,  5,  2,  0,  0,  0];
	$_18 = [0,  9,  0,  1,  2,  0,  0,  0];
	$_19 = [0, 10,  5,  5,  1,  0,  0,  0];
	$_20 = [0,  3,  4,  5,  0,  0,  0,  0];
	$_21 = [0, 10,  0,  4,  4,  0,  0,  0];
	$_22 = [0,  3,  0,  5,  3,  0,  0,  0];
	$_23 = [0, 10,  6,  4,  0,  0,  0,  0];
	$_24 = [0,  3,  6,  5,  0,  0,  0,  0];
	$_25 = [0,  9,  0,  2,  2,  0,  0,  0];
	$_26 = [0,  3,  5,  5,  2,  0,  0,  0];
	$_27 = [0,  9,  6,  3, 22, 30,  0,  0];
	$_28 = [0,  3,  4,  3, 22, 30,  0,  0];
	$_29 = [0, 10,  0,  4,  5,  0,  0,  0];
	$_30 = [0,  4,  0,  1,  3,  0,  0,  0];
	$_31 = [0,  9,  0,  5,  2,  0,  0,  0];

	$table = [
		[[ 660, "", $_00,  0, "", $_00,   0], "Midway-Inseln"],
		[[ 600, "", $_00,  0, "", $_00,   0], "Hawaii"],
		[[ 540, "", $_01,  0, "", $_02, -60], "Alaska"],
		[[ 480, "", $_01,  0, "", $_02, -60], "Pazifik"],
		[[ 480, "", $_01,  0, "", $_02, -60], "Tijuana"],
		[[ 420, "", $_00,  0, "", $_00,   0], "Arizona"],
		[[ 420, "", $_03,  0, "", $_04, -60], "Chihuahua"],
		[[ 420, "", $_01,  0, "", $_02, -60], "Mountain"],
		[[ 360, "", $_00,  0, "", $_00,   0], "Mittelamerika"],
		[[ 360, "", $_01,  0, "", $_02, -60], "Central"],
		[[ 360, "", $_03,  0, "", $_04, -60], "Mexiko-Stadt"],
		[[ 360, "", $_00,  0, "", $_00,   0], "Saskatchewan"],
		[[ 300, "", $_00,  0, "", $_00,   0], "Bogota"],
		[[ 300, "", $_01,  0, "", $_02, -60], "Eastern"],
		[[ 270, "", $_00,  0, "", $_00,   0], "Venezuela"],
		[[ 240, "", $_00,  0, "", $_00,   0], "Atlantik"],
		[[ 240, "", $_00,  0, "", $_00,   0], "Manaus"],
		[[ 240, "", $_05,  0, "", $_06, -60], "Santiago"],
		[[ 210, "", $_07,  0, "", $_08, -60], "Neufundland"],
		[[ 180, "", $_00,  0, "", $_00,   0], "Buenos Aires"],
		[[ 180, "", $_09,  0, "", $_10, -60], "Grönland"],
		[[ 180, "", $_11,  0, "", $_12, -60], "Brasilien"],
		[[ 180, "", $_02,  0, "", $_13, -60], "Montevideo"],
		[[ 120, "", $_00,  0, "", $_00,   0], "Mittelatlantik"],
		[[  60, "", $_09,  0, "", $_10, -60], "Azoren"],
		[[  60, "", $_00,  0, "", $_00,   0], "Kapverdische Inseln"],
		[[   0, "", $_00,  0, "", $_00,   0], "Casablanca"],
		[[   0, "", $_03,  0, "", $_14, -60], "London, Dublin"],
		[[- 60, "", $_15,  0, "", $_16, -60], "Amsterdam, Berlin"],
		[[- 60, "", $_09,  0, "", $_17, -60], "Belgrad"],
		[[- 60, "", $_09,  0, "", $_17, -60], "Brüssel"],
		[[- 60, "", $_09,  0, "", $_17, -60], "Sarajevo"],
		[[- 60, "", $_00,  0, "", $_00,   0], "W.-Afrika"],
		[[- 60, "", $_15,  0, "", $_17, -60], "Mitteleuropäische Zeit"],
		[[- 60, "", $_04,  0, "", $_18, -60], "Windhoek"],
		[[-120, "", $_19,  0, "", $_20, -60], "Amman, Jordan"],
		[[-120, "", $_21,  0, "", $_22, -60], "Athen, Istanbul"],
		[[-120, "", $_23,  0, "", $_24, -60], "Beirut, Libanon"],
		[[-120, "", $_00,  0, "", $_00,   0], "Kairo"],
		[[-120, "", $_21,  0, "", $_22, -60], "Helsinki"],
		[[-120, "", $_25,  0, "", $_26, -60], "Jerusalem"],
		[[-120, "", $_09,  0, "", $_17, -60], "Minsk"],
		[[-120, "", $_00,  0, "", $_00,   0], "Harare"],
		[[-180, "", $_00,  0, "", $_00,   0], "Baghdad"],
		[[-180, "", $_00,  0, "", $_00,   0], "Kuwait"],
		[[-180, "", $_00,  0, "", $_00,   0], "Nairobi"],
		[[-210, "", $_27,  0, "", $_28, -60], "Teheran"],
		[[-240, "", $_00,  0, "", $_00,   0], "Moskau"],
		[[-240, "", $_29,  0, "", $_10, -60], "Baku"],
		[[-240, "", $_00,  0, "", $_00,   0], "Tbilisi"],
		[[-240, "", $_09,  0, "", $_17, -60], "Yerevan"],
		[[-240, "", $_00,  0, "", $_00,   0], "Dubai"],
		[[-270, "", $_00,  0, "", $_00,   0], "Kabul"],
		[[-300, "", $_00,  0, "", $_00,   0], "Islamabad, Karatschi"],
		[[-300, "", $_00,  0, "", $_00,   0], "Uralsk"],
		[[-330, "", $_00,  0, "", $_00,   0], "Kolkata"],
		[[-330, "", $_00,  0, "", $_00,   0], "Sri Lanka"],
		[[-345, "", $_00,  0, "", $_00,   0], "Kathmandu"],
		[[-360, "", $_00,  0, "", $_00,   0], "Jekaterinburg"],
		[[-360, "", $_00,  0, "", $_00,   0], "Astana"],
		[[-390, "", $_00,  0, "", $_00,   0], "Yangon"],
		[[-420, "", $_00,  0, "", $_00,   0], "Bangkok"],
		[[-480, "", $_00,  0, "", $_00,   0], "Krasnojarsk"],
		[[-480, "", $_00,  0, "", $_00,   0], "Peking"],
		[[-480, "", $_00,  0, "", $_00,   0], "Hong Kong"],
		[[-480, "", $_00,  0, "", $_00,   0], "Kuala Lumpur"],
		[[-480, "", $_00,  0, "", $_00,   0], "Perth"],
		[[-480, "", $_00,  0, "", $_00,   0], "Taipeh"],
		[[-540, "", $_00,  0, "", $_00,   0], "Irkutsk"],
		[[-540, "", $_00,  0, "", $_00,   0], "Seoul"],
		[[-540, "", $_00,  0, "", $_00,   0], "Tokio, Osaka"],
		[[-570, "", $_00,  0, "", $_00,   0], "Darwin"],
		[[-570, "", $_30,  0, "", $_13, -60], "Adelaide"],
		[[-600, "", $_00,  0, "", $_00,   0], "Jakutsk"],
		[[-600, "", $_00,  0, "", $_00,   0], "Brisbane"],
		[[-600, "", $_00,  0, "", $_00,   0], "Guam"],
		[[-600, "", $_30,  0, "", $_13, -60], "Hobart"],
		[[-600, "", $_30,  0, "", $_13, -60], "Canberra, Sydney"],
		[[-660, "", $_00,  0, "", $_00,   0], "Wladiwostok"],
		[[-720, "", $_00,  0, "", $_00,   0], "Magadan"],
		[[-720, "", $_00,  0, "", $_00,   0], "Marshall-Inseln"],
		[[-720, "", $_00,  0, "", $_00,   0], "Fidchi"],
		[[-720, "", $_30,  0, "", $_31, -60], "Auckland"],
		[[-780, "", $_00,  0, "", $_00,   0], "Tonga"],
		];

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

		$table[$id] = [base64_encode($data), sprintf("GMT %s%02d%02d %s", $bias_p, $bias_h, $bias_m, $name)];
		}

	return($table);
	}

function active_sync_get_table_version()
	{
	$table = [
#		"1.0",
#		"2.0",
#		"2.1",
		"2.5",	# LG-P920 depends on it
		"12.0",
		"12.1",
		"14.0",	# allow SMS on Email
		"14.1",	# allow SMS on Email
#		"16.0",	# allow SMS on Email
#		"16.1",	# allow SMS on Email, Find
		];

	return($table);
	}

function active_sync_get_type_by_collection_id($user, $server_id)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(isset($settings["SyncDat"]))
		foreach($settings["SyncDat"] as $folder)
			if($folder["ServerId"] == $server_id)
				return($folder["Type"]);

	active_sync_debug("collection with server id $server_id of user $user not found.", "ERROR");

	return(false);
	}

function active_sync_get_version($type = 0)
	{
	$settings = active_sync_get_settings_server();

	$retval = [
		"name" => "AndSync",
		"major" => 0,
		"minor" => 0,
		"revision" => 0,
		"build" => 0,
		"extension" => "",
		"description" => ""
		];

	$changes = false;

	foreach($retval as $key => $value)
		if(! isset($settings["version"][$key]))
			$changes = true;

	foreach($retval as $key => $value)
		if(! isset($settings["version"][$key]))
			$settings["version"][$key] = $retval[$key];

	foreach($retval as $key => $value)
		if(isset($settings["version"][$key]))
			$retval[$key] = $settings["version"][$key];

	if($changes)
		active_sync_put_settings_server($settings);

	if($type == 0)
		return(sprintf("%s %d.%d.%d-%d %s %s", $retval["name"], $retval["major"], $retval["minor"], $retval["revision"], $retval["build"], $retval["extension"], $retval["description"]));

	if($type == 1)
		return($retval);
	}

function active_sync_handle_autodiscover($request)
	{
	$case = "";
	$case_framework = "default";
	$display_name = "";
	$email_address = "";
	$email_address_user = "";
	$email_address_host = "";
	$redirect = "";
	$acceptable_response_schema = "";

	################################################################################

#	if(! isset($_SERVER["PHP_AUTH_USER"]))
#		header("WWW-Authenticate: basic realm=\"ActiveSync\"");

	define("ACTIVE_SYNC_AUTODISCOVER_REQUEST_OUTLOOK", "http://schemas.microsoft.com/exchange/autodiscover/outlook/requestschema/2006");
	define("ACTIVE_SYNC_AUTODISCOVER_REQUEST_MOBILESYNC", "http://schemas.microsoft.com/exchange/autodiscover/mobilesync/requestschema/2006");

	define("ACTIVE_SYNC_AUTODISCOVER_RESPONSE_DEFAULT", "http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006");
	define("ACTIVE_SYNC_AUTODISCOVER_RESPONSE_OUTLOOK", "http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a");
	define("ACTIVE_SYNC_AUTODISCOVER_RESPONSE_MOBILESYNC", "http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006");


	if($_SERVER["REQUEST_METHOD"] == "POST")
		{
		$autodiscover = new SimpleXMLElement($request["xml"]);
		$namespace = $autodiscover["xmlns"];

		if(! isset($autodiscover->Request))
			$error_code = 600;

		if(isset($autodiscover->Request->AcceptableResponseSchema))
			$acceptable_response_schema = strval($autodiscover->Request->AcceptableResponseSchema);

		if(isset($autodiscover->Request->EMailAddress))
			$email_address = strval($autodiscover->Request->EMailAddress);
		}

	$autodiscover = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><Autodiscover />');
	$autodiscover["xmlns"] = "http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006";

	################################################################################

	if($email_address)
		if(strpos($email_address, "@") !== false)
			list($email_address_user, $email_address_host) = explode("@", $email_address);

	################################################################################

	$settings_server = active_sync_get_settings_server();

	if(isset($settings_server["login"]))
		foreach($settings_server["login"] as $login)
			if($login["User"] == $email_address_user)
				{
				$case = "settings";
				$display_name = $login["DisplayName"];
				}

	################################################################################

	if(! strlen($email_address))
		$error_code = 500;

	if($email_address == "test@olderdissen.ro")
		{
#		$case = "redirect";
#		$redirect = "nomatrix@olderdissen.ro";
		}

	################################################################################

	if($case == "error") # 4.2.2 Response - Case Error
		{
		$response = $autodiscover->addChild("Response");
		$response["xmlns"] = $acceptable_response_schema;

			$user = $response->addChild("User");
				$user->DisplayName = $display_name;
				$user->EMailAddress = $email_address;

			$action = $response->addChild("Action");
				$error = $action->addChild("Error");
					$error->Status = 2;
					$error->Message = "The directory service could not be reached";
					$error->DebugData = "MailUser";
		}
	elseif($case == "redirect") # 4.2.3 Response - Case Redirect
		{
		$response = $autodiscover->addChild("Response");
		$response["xmlns"] = $acceptable_response_schema;

			$response->Culture = "en:en";

			$user = $response->addChild("User");
				$user->DisplayName = $display_name;
				$user->EMailAddress = $email_address;

			if($acceptable_response_schema == "http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a")
				{
				$account = $response->addChild("Account");
					$account->AccountType = "email";
					$account->Action = "redirectAddr";

					$account->RedirectAddr = $redirect;
				}

			if($acceptable_response_schema == "http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006")
				{
				$action = $response->addChild("Action");
					$action->Redirect = $redirect;
				}
		}
	elseif($case == "settings") # 4.2.4 Response - Case Server Settings
		{
		$response = $autodiscover->addChild("Response");
		$response["xmlns"] = $acceptable_response_schema;

			$response->Culture = "en:en";

			$user = $response->addChild("User");
				$user->DisplayName = $display_name;
				$user->EMailAddress = $email_address;

			if($acceptable_response_schema == "http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a")
				{
				$account = $response->addChild("Account");
					$account->AccountType = "email";
					$account->Action = "settings";

					$protocol = $account->addChild("Protocol");
						$protocol->Type = "SMTP";
						$protocol->Server = "smtp.olderdissen.ro";
						$protocol->Port = 25;

					$protocol = $account->addChild("Protocol");
						$protocol->Type = "IMAP";
						$protocol->Server = "imap.olderdissen.ro";
						$protocol->Port = 143;

					$protocol = $account->addChild("Protocol");
						$protocol->Type = "EXCH";
						$protocol->Server = "mail.olderdissen.ro";
						$protocol->OABUrl = "https://olderdissen.ro/oab";
						$protocol->ASUrl = "https://olderdissen.ro/Microsoft-Server-ActiveSync";
				}

			if($acceptable_response_schema == "http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006")
				{
				$action = $response->addChild("Action");
					$settings = $action->addChild("Settings");

						$server = $settings->addChild("Server");
							$server->Type = "MobileSync";
							$server->Url = "https://mail.olderdissen.ro/Microsoft-Server-ActiveSync";
							$server->Name = "Microsoft-Server-ActiveSync (Default Web Site)";

						$server = $settings->addChild("Server");
							$server->Type = "CertEnroll";
							$server->Url = "https://olderdissen.ro/";
							$server->ServerData = "CertEnrollTemplate";
				}
		}
	elseif($case_framework == "error") # 4.2.5 Response - Case Framework Error
		{
		$response = $autodiscover->addChild("Response");
		$response["xmlns"] = $acceptable_response_schema;

#			$response->Culture = "en:en";

#			$user = $response->addChild("User");
#				$user->DisplayName = $display_name;
#				$user->EMailAddress = $email_address;

#			$account = $response->addChild("Account");

				$error = $response->addChild("Error");
				$error["Time"] = date("H:i:s");
				$error["Id"] = time();
					$error->ErrorCode = $error_code;
					$error->Message = "Invalid Request";
					$error->DebugData = "";
		}
	elseif($case_framework == "default") # 4.2.6 Response – Case Framework Default
		{
		$response = $autodiscover->addChild("Response");
		$response["xmlns"] = $acceptable_response_schema;

#			$response->Culture = "en:en";

#			$user = $response->addChild("User");
#				$user->DisplayName = $display_name;
#				$user->EMailAddress = $email_address;

			$account = $autodiscover->addChild("Account");
				$account->AccountType = "email";
				$account->Action = "settings";
#				$account->Image = "https://olderdissen.ro/images/logo_small_v2.gif";
#				$account->ServiceHome = "https://www.olderdissen.ro/";
#				$account->RedirectUrl = "https://olderdissen.ro/Microsoft-Server-ActiveSync";

				$protocol = $account->addChild("Protocol");
					$protocol->Type = "SMTP";
					$protocol->Server = "smtp.olderdissen.ro";
					$protocol->Port = 25;

				$protocol = $account->addChild("Protocol");
					$protocol->Type = "IMAP";
					$protocol->Server = "imap.olderdissen.ro";
					$protocol->Port = 143;

				$protocol = $account->addChild("Protocol");
					$protocol->Type = "EXCH";
					$protocol->Server = "mail.olderdissen.ro";
					$protocol->OABUrl = "https://olderdissen.ro/oab";
					$protocol->ASUrl = "https://mail.olderdissen.ro/Microsoft-Server-ActiveSync";
		}

	$autodiscover = $autodiscover->asXML();

	$autodiscover = active_sync_wbxml_pretty($autodiscover);

	header("Content-Type: text/xml; charset=utf-8");
	header("Content-Length: " . strlen($autodiscover));

	print($autodiscover);

	active_sync_debug(print_r($_SERVER, true));
	if(isset($request["xml"]))
		active_sync_debug(print_r($request["xml"], true));
	active_sync_debug($autodiscover);
	}

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
	$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$sync_key	= strval($xml->SyncKey);
	$parent_id	= strval($xml->ParentId);
	$display_name	= strval($xml->DisplayName);
	$type		= strval($xml->Type);

	$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if($sync_key != $settings_client["SyncKey"])
		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
	else
		$status = active_sync_folder_create($request["AuthUser"], $parent_id, $display_name, $type);

	if($status == 1)
		{
		$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

		$settings_server = active_sync_get_settings_folder_server($request["AuthUser"]);

		$settings_client["SyncKey"] ++;
		$settings_client["SyncDat"] = $settings_server["SyncDat"];

		active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);

		$server_id = active_sync_get_collection_id_by_display_name($request["AuthUser"], $display_name);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderCreate");

		if($status == 1)
			$table = ["Status" => $status, "SyncKey" => $settings_client["SyncKey"], "ServerId" => $server_id];
		else
			$table = ["Status" => $status];

		foreach($table as $token => $value)
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
	$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$sync_key	= strval($xml->SyncKey);
	$server_id	= strval($xml->ServerId);

	$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if($sync_key != $settings_client["SyncKey"])
		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
	else
		$status = active_sync_folder_delete($request["AuthUser"], $server_id);

	if($status == 1)
		{
		$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

		$settings_server = active_sync_get_settings_folder_server($request["AuthUser"]);

		$settings_client["SyncKey"] ++;
		$settings_client["SyncDat"] = $settings_server["SyncDat"];

		active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderDelete");

		if($status == 1)
			$table = ["Status" => $status, "SyncKey" => $settings_client["SyncKey"]];
		else
			$table = ["Status" => $status];

		foreach($table as $token => $value)
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
	$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$sync_key = strval($xml->SyncKey);

	$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if($sync_key == 0)
		$status = 1; # Success.
	elseif($sync_key != $settings_client["SyncKey"])
		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
	else
		$status = 1; # Success.

	if(active_sync_get_need_wipe($request))
		$status = 140;

	if(active_sync_get_need_provision($request))
		$status = 142;

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderSync");

		if($status == 1)
			$settings_client["SyncKey"] ++;

		if($sync_key == 0)
			$settings_client["SyncDat"] = [];

		if($status == 142)
			$table = ["Status" => $status];
		else
			$table = ["Status" => $status, "SyncKey" => $settings_client["SyncKey"]];
		
		foreach($table as $token => $value)
			{
			$response->x_open($token);
				$response->x_print($value);
			$response->x_close($token);
			}

		if($status == 1)
			{
			$jobs = [];

			$settings_server = active_sync_get_settings_folder_server($request["AuthUser"]);

			foreach($settings_server["SyncDat"] as $settings_server_id => $settings_server_data)
				{
				$known = false;

				foreach($settings_client["SyncDat"] as $settings_client_id => $settings_client_data)
					if($settings_server_data["ServerId"] != $settings_client_data["ServerId"])
						continue;
					elseif($settings_server_data["ParentId"] != $settings_client_data["ParentId"])
						$jobs["Update"][] = $settings_server_data;
					elseif($settings_server_data["DisplayName"] != $settings_client_data["DisplayName"])
						$jobs["Update"][] = $settings_server_data;
					elseif($settings_server_data["Type"] != $settings_client_data["Type"])
						$jobs["Update"][] = $settings_server_data;
					else
						$known = true;

				if(! $known)
					$jobs["Add"][] = $settings_server_data;
				}

			foreach($settings_client["SyncDat"] as $settings_client_id => $settings_client_data)
				{
				$known = false;

				foreach($settings_server["SyncDat"] as $settings_server_id => $settings_server_data)
					if($settings_client_data["ServerId"] != $settings_server_data["ServerId"])
						continue;
					else
						$known = true;

				if(! $known)
					$jobs["Delete"][] = $settings_client_data;
				}

			$actions = [
				"Add" => ["ServerId", "ParentId", "DisplayName", "Type"],
				"Delete" => ["ServerId"],
				"Update" => ["ServerId", "ParentId", "DisplayName", "Type"]
				];

			$count = 0;

			foreach($actions as $action => $fields)
				if(isset($jobs[$action]))
					$count += count($jobs[$action]);

			$response->x_open("Changes");

				$response->x_open("Count");
					$response->x_print($count);
				$response->x_close("Count");

				if($count > 0)
					foreach($actions as $action => $fields)
						if(isset($jobs[$action]))
							foreach($jobs[$action] as $job)
								{
								if($action == "Add")
									$settings_client["SyncDat"][] = $job;

								if($action == "Delete")
									foreach($settings_client["SyncDat"] as $settings_client_id => $settings_client_data)
										if($settings_client_data["ServerId"] == $job["ServerId"])
											unset($settings_client["SyncDat"][$settings_client_id]);

								if($action == "Update")
									foreach($settings_client["SyncDat"] as $settings_client_id => $settings_client_data)
										if($settings_client_data["ServerId"] == $job["ServerId"])
											$settings_client["SyncDat"][$settings_client_id] = $job;

								$response->x_open($action);

									foreach($fields as $key)
										{
										$response->x_open($key);
											$response->x_print($job[$key]);
										$response->x_close($key);
										}

								$response->x_close($action);
								}

			$response->x_close("Changes");
			}

	$response->x_close("FolderSync");

	active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);

	return($response->response);
	}

function active_sync_handle_folder_update($request)
	{
	$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$sync_key	= strval($xml->SyncKey);
	$server_id	= strval($xml->ServerId);
	$parent_id	= strval($xml->ParentId);
	$display_name	= strval($xml->DisplayName);

	$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if($sync_key != $settings_client["SyncKey"])
		$status = 9; # Synchronization key mismatch or invalid synchronziation key.
	else
		$status = active_sync_folder_update($request["AuthUser"], $server_id, $parent_id, $display_name);

	if($status == 1)
		{
		$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

		$settings_server = active_sync_get_settings_folder_server($request["AuthUser"]);

		$settings_client["SyncKey"] ++;
		$settings_client["SyncDat"] = $settings_server["SyncDat"];

		active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("FolderUpdate");

		if($status == 1)
			$table = ["Status" => $status, "SyncKey" => $settings_client["SyncKey"]];
		else
			$table = ["Status" => $status];

		foreach($table as $token => $value)
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

		$settings_server = active_sync_get_settings_folder_server($request["AuthUser"]);

		foreach($settings_server["SyncDat"] as $folder)
			{
			$response->x_open("Folder");

				foreach(["ServerId", "ParentId", "DisplayName", "Type"] as $token);
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
	$request["xml"] = active_sync_wbxml_request_a($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$response = new active_sync_wbxml_response();

	$response->x_switch("ItemEstimate");

	$response->x_open("GetItemEstimate");

		if(isset($xml->Collections))
			{
			foreach($xml->Collections->Collection as $collection)
				{
				$sync_key	= strval($collection->SyncKey);
				$collection_id	= strval($collection->CollectionId);

				$settings_client = active_sync_get_settings_files_client($request["AuthUser"], $collection_id, $request["DeviceId"]);

				$settings_server = active_sync_get_settings_files_server($request["AuthUser"], $collection_id);

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
						$jobs = [];

						foreach($settings_server["SyncDat"] as $server_id => $null)
							{
							$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

							if(! isset($data["AirSync"]["Class"]))
								$data["AirSync"]["Class"] = $default_class;

							$class = $default_class;
							$filter_type = 0;
							$class_found = false;

							if(isset($collection->Options))
								foreach($collection->Options as $options)
									{
									if(isset($options->Class))
										$class = strval($options->Class); # only occurs on email/sms
									else
										$class = $default_class;

									if($data["AirSync"]["Class"] != $class)
										continue;

									if(isset($options->FilterType))
										$filter_type = intval($options->FilterType); # only occurs on email/sms
									else
										$filter_type = 0;

									$class_found = true;
									}

							if(! $class_found)
								{
								if(! isset($settings_client["SyncDat"][$server_id]))
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
								if(! isset($settings_client["SyncDat"][$server_id]))
									$jobs["Add"][] = $server_id;
								elseif($settings_client["SyncDat"][$server_id] == "*")
									$jobs["Add"][] = $server_id;
								elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
									$jobs["Change"][] = $server_id;

								$class_found = true;
								}

							if(($filter_type > 0) && ($filter_type < 8))
								{
								$stat_filter = ["now", "- 1 day", "- 3 day", "- 1 week", "- 2 week", "- 1 month", "- 3 month", "- 6 month", "now"];

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

								if(! isset($settings_client["SyncDat"][$server_id]))
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
								if(! isset($settings_client["SyncDat"][$server_id]))
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
							if(! isset($settings_server["SyncDat"][$server_id]))
								$jobs["Delete"][] = $server_id;

						$estimate = 0;

						foreach(["Add", "Change", "Delete", "SoftDelete"] as $command)
							if(isset($jobs[$command]))
								$estimate += count($jobs[$command]);

						$response->x_open("Collection");

							foreach(["CollectionId" => $collection_id, "Estimate" => $estimate] as $token => $value)
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
	$request["xml"] = active_sync_wbxml_request_a($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$response = new active_sync_wbxml_response();

	if(isset($xml->EmptyFolderContents))
		{
		$collection_id = strval($xml->EmptyFolderContents->CollectionId);

		# $xml->EmptyFolderContents->Options->DeleteSubFolders

		foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/*.data") as $file)
			{
			$server_id = basename($file, ".data");

#			unlink(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id);
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

			$file = __DIR__ . "/" . $request["AuthUser"] . "/.files/" . $file_reference;

			$response->x_switch("ItemOperations");

			$response->x_open("ItemOperations");
				$response->x_open("Status");
					$response->x_print(1);
				$response->x_close("Status");

				$response->x_open("Response");
					$response->x_open("Fetch");

						if(file_exists($file))
							$status = 1;
						else
							$status = 15; # Attachment fetch provider - Attachment or attachment ID is invalid.

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
									$response->x_print(mime_content_type($file));
								$response->x_close("ContentType");

								$response->x_switch("ItemOperations");

								$response->x_open("Data");
									$response->x_print(base64_encode(file_get_contents($file)));
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
													if(! isset($data["RightsManagement"][$token]))
														continue;

													if(! strlen($data["RightsManagement"][$token]))
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

						foreach(["CollectionId" => $collection_id, "ServerId" => $server_id] as $token => $value)
							{
							$response->x_open($token);
								$response->x_print($value);
							$response->x_close($token);
							}

						$response->x_switch("ItemOperations");

						$response->x_open("Properties");

							foreach(["Email", "Email2"] as $codepage)
								{
								if(! isset($data[$codepage]))
									continue;

								$response->x_switch($codepage);

								foreach($data[$codepage] as $token => $value)
									{
									if(! strlen($value))
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
										if(! strlen($value))
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

							$default_class = active_sync_get_class_by_collection_id($user, $collection_id);

							if(isset($data["Body"]) )
								if(isset($xml->Fetch->Options))
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
															if(! isset($data["RightsManagement"][$token]))
																continue;

															if(! strlen($data["RightsManagement"][$token]))
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
											foreach($data["Body"] as $body) # !!!
												{
												if(! isset($body["Type"]))
													continue;

												if($body["Type"] != intval($preference->Type))
													continue;

												$response->x_switch("AirSyncBase");

												$response->x_open("Body");

													if(isset($preference["Preview"]))
														foreach($data["Body"] as $preview) # !!!
															{
															if(! isset($preview["Type"]))
																continue;

															if($preview["Type"] != 1)
																continue;

															$response->x_open("Preview");
																$response->x_print(substr($preview["Data"], 0, intval($preference->Preview)));
															$response->x_close("Preview");
															}

													if(isset($preference->TruncationSize))
														if(intval($preference->TruncationSize) != 0)
															if(! isset($body["EstimatedDataSize"]))
																{
																$body["Data"] = substr($body["Data"], 0, intval($preference->TruncationSize));

																$response->x_open("Truncated");
																	$response->x_print(1);
																$response->x_close("Truncated");
																}
															elseif(intval($preference->TruncationSize) < $body["EstimatedDataSize"])
																{
																$body["Data"] = substr($body["Data"], 0, intval($preference->TruncationSize));

																$response->x_open("Truncated");
																	$response->x_print(1);
																$response->x_close("Truncated");
																}

													foreach($body as $token => $value)
														{
														if(! strlen($value))
															{
															$response->x_open($token, false);

															continue;
															}

														$response->x_open($token);
															$response->x_print($value); # opaque data will fail :(
														$response->x_close($token);
														}

												$response->x_close("Body");
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
	$request["xml"] = active_sync_wbxml_request_a($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

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

		unlink(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $request_id . ".data");

		$calendar_id = active_sync_get_calendar_by_uid($user, $data["Meeting"]["Email"]["UID"]);

		$collection_id = active_sync_get_collection_id_by_type($user, 8); # Calendar
		# this need to be changed, this function has to return a list of all kind of calendars

		if($calendar_id == "")
			{
			$calendar = [];

			$calendar["Calendar"] = $data["Meeting"]["Email"];

			unset($calendar["Calendar"]["Organizer"]);

			list($organizer_name, $organizer_mail) = active_sync_mail_parse_address($data["Meeting"]["Email"]["Organizer"]);

			foreach(["OrganizerName" => $organizer_name, "OrganizerEmail" => $organizer_mail] as $token => $value)
				if(strlen($value))
					$calendar["Calendar"][$token] = $value;

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

			$description = [
				"Wann: " . date("d.m.Y H:i:s", strtotime($data["Meeting"]["Email"]["StartTime"]))
				];

			if(isset($data["Meeting"]["Email"]["Location"]))
				$description[] = "Wo: " . $data["Meeting"]["Email"]["Location"];

			$description[] = "*~*~*~*~*~*~*~*~*~*";

			if(isset($data["Body"]))
				foreach($data["Body"] as $body)
					if(isset($body["Type"]))
						if($body["Type"] == 1)
							if(isset($body["Data"]))
								$description[] = $body["Data"];

			$description = implode(PHP_EOL, $description);

			$mime = [
				"From: " . $data["Email"]["To"],
				"To: " . $data["Email"]["From"]
				];

			foreach(["Accepted" => 1, "Tentative" => 2, "Declined" => 3] as $subject => $value)
				if($user_response == $value)
					$mime[] = "Subject: " . $subject . ": " . $data["Email"]["Subject"];

			$mime[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/plain; charset=\"utf-8\"";
			$mime[] = "";
			$mime[] = $description;
			$mime[] = "";

			foreach(["Accepted" => 1, "Tentative" => 2, "Declined" => 3] as $message => $value)
				if($user_response == $value)
					$mime[] = $message;

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

					foreach(["DTSTAMP" => "DtStamp", "DTSTART" => "StartTime", "DTEND" => "EndTime"] as $key => $token)
						if(isset($data["Meeting"]["Email"][$token]))
							$mime[] = $key . ":" . $data["Meeting"]["Email"][$token];

					if(isset($data["Meeting"]["Location"]))
						$mime[] = "LOCATION: " . $data["Meeting"]["Email"]["Location"];

					if(isset($data["Email"]["Subject"]))
						$mime[] = "SUMMARY: " . $data["Email"]["Subject"]; # take this from email subject

					$mime[] = "DESCRIPTION:" . $description;

					foreach(["FALSE" => 0, "TRUE" => 1] as $key => $value)
						if($data["Meeting"]["Email"]["AllDayEvent"] == $value)
							$mime[] = "X-MICROSOFT-CDO-ALLDAYEVENT:" . $key;

					foreach(["ACCEPTED" => 1, "TENTATIVE" => 2, "DECLINED" => 3] as $partstat => $value)
						if($user_response == $value)
							$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=" . $partstat . ";RSVP=TRUE:MAILTO:" . $user . "@" . $host;

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

			$mime = implode(PHP_EOL, $mime);

			active_sync_send_mail($user, $mime);
			}

		# http://msdn.microsoft.com/en-us/library/exchange/hh428684%28v=exchg.140%29.aspx
		# http://msdn.microsoft.com/en-us/library/exchange/hh428685%28v=exchg.140%29.aspx

		$response->x_switch("MeetingResponse");

		$response->x_open("MeetingResponse");

			$response->x_open("Result");

				foreach(["Status" => 1, "RequestId" => $request_id, "CalendarId" => $calendar_id] as $token => $value)
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
	$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

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

				if(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $src_fld_id))
					$status = 1; # Invalid source collection ID or invalid source Item ID.
				elseif(! file_exists(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $src_fld_id . "/" . $src_msg_id . ".data"))
					$status = 1; # Invalid source collection ID or invalid source Item ID.
				elseif(count($move->DstFldId) > 1)
					$status = 5; # One of the following failures occurred: the item cannot be moved to more than one item at a time, or the source or destination item was locked.
				elseif(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $dst_fld_id))
					$status = 2; # Invalid destination collection ID.
				elseif($src_fld_id == $dst_fld_id)
					$status = 4; # Source and destination collection IDs are the same.
				else
					{
					$dst_msg_id = active_sync_create_guid_filename($request["AuthUser"], $dst_fld_id);

					$src = ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $src_fld_id . "/" . $src_msg_id . ".data";
					$dst = ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $dst_fld_id . "/" . $dst_msg_id . ".data";

					if(rename($src, $dst))
						$status = 3; # Success.
					else
						$status = 7; # Source or destination item was locked.
					}

				$response->x_open("Response");

					foreach(($status == 3 ? ["Status" => $status, "SrcMsgId" => $src_msg_id, "DstMsgId" => $dst_msg_id] : ["Status" => $status, "SrcMsgId" => $src_msg_id]) as $token => $value)
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
	$settings = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if(isset($request["wbxml"]))
		$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);
	else
		$request["xml"] = '<?xml version="1.0" encoding="utf-8"?><Ping xmlns="Ping" />';

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	if(isset($xml->HeartbeatInterval))
		$settings["HeartbeatInterval"] = intval($xml->HeartbeatInterval);

	if(isset($xml->Folders))
		{
		unset($settings["Ping"]);

		foreach($xml->Folders->Folder as $folder)
			{
			$settings["Ping"][] = [
				"Id" => strval($folder->Id),
				"Class" => strval($folder->Class)
				];
			}
		}

	if(isset($settings["HeartbeatInterval"]))
		{
		unset($xml->HeartbeatInterval);

		$x = $xml->addChild("HeartbeatInterval", $settings["HeartbeatInterval"]);
		}

	if(isset($settings["Ping"]))
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

	if(isset($_SERVER["REMOTE_PORT"]))
		$settings["Port"] = intval($_SERVER["REMOTE_PORT"]);

	active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings);

#	$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

#	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$changed_folders = [];

	while(1)
		{
		if(active_sync_get_need_wipe($request))
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(active_sync_get_need_provision($request))
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(active_sync_get_need_folder_sync($request))
			{
			$status = 7; # Folder hierarchy sync required.

			break;
			}

		if(! isset($xml->Folders))
			{
			$status = 3; # The Ping command request omitted required parameters.

			break;
			}

		if(count($xml->Folders->Folder) > ACTIVE_SYNC_PING_MAX_FOLDERS)
			{
			$status = 6; # The Ping command request specified more than the allowed number of folders to monitor.

			break;
			}

		if(! isset($xml->HeartbeatInterval))
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

		if(($_SERVER["REQUEST_TIME"] + intval($xml->HeartbeatInterval)) < time())
			{
			$status = 1; # The heartbeat interval expired before any changes occurred in the folders being monitored.

			break;
			}

		foreach($xml->Folders->Folder as $folder)
			{
			$changes_detected = false;
			$collection_id = strval($folder->Id);

			$settings_client = active_sync_get_settings_files_client($request["AuthUser"], $collection_id, $request["DeviceId"]);

			$settings_server = active_sync_get_settings_files_server($request["AuthUser"], $collection_id);

			if($settings_client["SyncKey"] == 0)
				$changes_detected = true;

			foreach($settings_server["SyncDat"] as $server_id => $null)
				{
				if($changes_detected)
					continue;

				if(! isset($settings_client["SyncDat"][$server_id]))
					$changes_detected = true;

				if($changes_detected)
					break;

				if($settings_client["SyncDat"][$server_id] == "*")
					continue;

				if($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
					$changes_detected = true;

				if($changes_detected)
					break;
				}

			foreach($settings_client["SyncDat"] as $server_id => $null)
				{
				if($changes_detected)
					continue;

				if(isset($settings_server["SyncDat"][$server_id]))
					continue;

				$changes_detected = true;
				}

			if(! $changes_detected)
				continue;

			$changed_folders[] = $collection_id;
			}

		if(count($changed_folders) > 0)
			{
			$status = 2; # Changes occured in at least one of the monitored folders. The response specifies the changed folders.

			break;
			}

		$settings = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

		if(! isset($settings["Port"]))
			$settings["Port"] = "n";

		# check if stored port is different. if yes, there is already a newer connection
		if($settings["Port"] != (isset($_SERVER["REMOTE_PORT"]) ? $_SERVER["REMOTE_PORT"] : "s"))
			{
			$status = 8; # An error occurred on the server.

			active_sync_debug("KILLED | " . $settings["Port"] . " REQUEST Ping", "RESPONSE"); die();

			break;
			}

		sleep(ACTIVE_SYNC_SLEEP);
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
				$response->x_print(ACTIVE_SYNC_PING_MAX_FOLDERS);
			$response->x_close("MaxFolders");
			}

	$response->x_close("Ping");

	return($response->response);
	}

function active_sync_handle_provision($request)
	{
	$request["xml"] = active_sync_wbxml_request_a($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Provision");

	$response->x_open("Provision");

		if(isset($xml->DeviceInformation))
			{
			if(isset($xml->DeviceInformation->Set))
				{
				$info = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

				foreach(active_sync_get_default_info() as $token)
					if(isset($xml->DeviceInformation->Set->$token))
						$info["DeviceInformation"][$token] = strval($xml->DeviceInformation->Set->$token);

				active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $info);

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

			$settings_server = active_sync_get_settings_server();

			$settings_client = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

			$settings_client["PolicyKey"] = $settings_server["Policy"]["PolicyKey"];

			active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings_client);

			if(! isset($xml->Policies->Policy))
				$status = 3; # Unknown PolicyType value.
			elseif(! isset($xml->Policies->Policy->PolicyType))
				$status = 3; # Unknown PolicyType value.
			elseif(strval($xml->Policies->Policy->PolicyType) != "MS-EAS-Provisioning-WBXML")
				$status = 3; # Unknown PolicyType value.
			elseif(! isset($xml->Policies->Policy->Status))
				{
				if(! isset($settings_server["Policy"]["PolicyKey"]))
					{
					$show_empty = 1;

					$status = 1; # There is no policy for this client.
					}
				elseif(! isset($settings_server["Policy"]["Data"]))
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
			elseif(! isset($xml->Policies->Policy->PolicyKey))
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

					$table = [
						"PolicyType" => "MS-EAS-Provisioning-WBXML",
						"Status" => $status,
						"PolicyKey" => $settings_server["Policy"]["PolicyKey"]
						];

					foreach($table as $token => $value)
						{
						$response->x_open($token);
							$response->x_print($value);
						$response->x_close($token);
						}

					if($show_policy == 1)
						{
						$response->x_open("Data");
							$response->x_open("EASProvisionDoc");

								foreach(["ApprovedApplicationList" => "Hash", "UnapprovedInROMApplicationList" => "ApplicationName"] as $k => $v)
									{
									if(! isset($settings_server["Policy"]["Data"][$k]))
										continue;

									$response->x_open($k);

										foreach(explode(PHP_EOL, $settings_server["Policy"]["Data"][$k]) as $value)
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

									if(! isset($settings_server["Policy"]["Data"][$token]))
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

		if(active_sync_get_need_wipe($request))
			{
			$remote_wipe = 0;

			if(! isset($xml->RemoteWipe))
				$status = 1; # The client remote wipe was sucessful.
			elseif(! isset($xml->RemoteWipe->Status))
				{
				$remote_wipe = ACTIVE_SYNC_REMOTE_WIPE_ACCOUNT_ONLY;

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
			elseif($remote_wipe == ACTIVE_SYNC_REMOTE_WIPE)
				$response->x_open("RemoteWipe", false);
			elseif($remote_wipe == ACTIVE_SYNC_REMOTE_WIPE_ACCOUNT_ONLY)
				$response->x_open("AccountOnlyRemoteWipe", false);
			}

	$response->x_close("Provision");

	return($response->response);
	}

function active_sync_handle_provision_remote_wipe($request)
	{
	$file = ACTIVE_SYNC_DAT_DIR . "/" . $request["DeviceId"] . ".wipe";

	if(file_exists($file))
		unlink($file);

	return;

	$settings = active_sync_get_settings_server();

	foreach($settings["login"] as $login)
		{
		if(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $login["User"]))
			continue;

		$folders = active_sync_get_settings_folder_server($login["User"]);

		foreach($folders["SyncDat"] as $folder)
			{
			if(is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $login["User"] . "/" . $folder["ServerId"]))
				continue;

			$file = ACTIVE_SYNC_DAT_DIR . "/" . $login["User"] . "/" . $folder["ServerId"] . "/" . $request["DeviceId"] . ".sync";

			if(file_exists($file))
				unlink($file);
			}

		$file = ACTIVE_SYNC_DAT_DIR . "/" . $login_data["User"] . "/" . $request["DeviceId"] . ".sync";

		if(file_exists($file))
			unlink($file);
		}
	}

function active_sync_handle_resolve_recipients($request)
	{
	$host = active_sync_get_domain(); # needed for user@host
	$recipients = [];

	$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$response = new active_sync_wbxml_response();

	$response->x_switch("ResolveRecipients");

	$response->x_open("ResolveRecipients");

		if(! isset($xml->To))
			$status = 5;
		elseif(count($xml->To) > 20)
			$status = 161;
		else
			{
			$settings = active_sync_get_settings_server();

			foreach($xml->To as $to)
				{
				$to = strval($to);

				foreach($settings["login"] as $login)
					{
					$collection_id = active_sync_get_collection_id_by_type($login["User"], 9);

					foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $login["User"] . "/" . $collection_id . "/*.data") as $file) # contact
						{
						$server_id = basename($file, ".data");

						$data = active_sync_get_settings_data($login["User"], $collection_id, $server_id);

						foreach(["Email1Address", "Email2Address", "Email3Address"] as $token)
							{
							if(! isset($data["Contacts"][$token]))
								continue;

							if(! strlen($data["Contacts"][$token]))
								continue;

							list($to_name, $to_mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

							if($to_mail != $to)
								continue;

							if($login["User"] == $request["AuthUser"])
								$recipients[$to][] = ["Type" => 2, "DisplayName" => $to_name, "EmailAddress" => $to_mail];
							else
								$recipients[$to][] = ["Type" => 1, "DisplayName" => $to_name, "EmailAddress" => $to_mail];

							break(2); # foreach, while
							}
						}
					}
				}

			$status = 1;
			}

		$response->x_open("Status");
			$response->x_print($status);
		$response->x_close("Status");

		foreach($xml->To as $to)
			{
			$to = strval($to);

			$response->x_open("Response");

				$recipient_count = count($recipients[$to]);

				foreach(["To" => $to, "Status" => ($recipient_count > 1 ? 2 : 1), "RecipientCount" => $recipient_count] as $token => $value)
					{
					$response->x_open($token);
						$response->x_print($value);
					$response->x_close($token);
					}

				foreach($recipients[$to] as $recipient)
					{
					$response->x_open("Recipient");

						foreach(["Type", "DisplayName", "EmailAddress"] as $field)
							{
							$response->x_open($field);
								$response->x_print($recipient[$field]);
							$response->x_close($field);
							}

						if(isset($xml->Options->Availability))
							{
							$response->x_open("Availability");

								$status = 1;

								if($status == 1)
									if(! isset($xml->Options->Availability->StartTime))
										$status = 5;

								if($status == 1)
									if(! isset($xml->Options->Availability->EndTime))
										$status = 5;

								if($status == 1)
									if(((strtotime($xml->Options->Availability->EndTime) - strtotime($xml->Options->Availability->StartTime)) / 1800) > 32768)
										$status = 5;

								$response->x_open("Status");
									$response->x_print($status);
								$response->x_close("Status");

								# check host for different status

								if($status == 1)
									{
									$start_time = strtotime($xml->Options->Availability->StartTime);
									$end_time = strtotime($xml->Options->Availability->EndTime);

									$merged_free_busy = str_repeat(4, ($end_time - $start_time) / 1800); # 4 = no data

									list($to_name, $to_mail) = active_sync_mail_parse_address($recipient["EmailAddress"]);
									list($to_user, $to_host) = explode("@", $to_mail);

									if($to_host == $host)
										{
										$collection_id = active_sync_get_collection_id_by_type($to_user, 8);

										foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $to_user . "/" . $collection_id . "/*.data") as $file)
											{
											$server_id = basename($file, ".data");

											$data = active_sync_get_settings_data($to_user, $collection_id, $server_id);

											if(! isset($data["Calendar"]["BusyStatus"]))
												continue;

											if($end_time < strtotime($data["Calendar"]["StartTime"]))
												continue;

											if($start_time > strtotime($data["Calendar"]["EndTime"]))
												continue;

											for($test_time = $start_time; $test_time < $end_time; $test_time += 1800)
												if($test_time >= strtotime($data["Calendar"]["StartTime"]))
													if($test_time + 1800 <= strtotime($data["Calendar"]["EndTime"]))
														$merged_free_busy[($test_time - $start_time) / 1800] = $data["Calendar"]["BusyStatus"];
											}
										}

									$response->x_open("MergedFreeBusy");
										$response->x_print($merged_free_busy);
									$response->x_close("MergedFreeBusy");
									}

							$response->x_close("Availability");
							}

						if(isset($xml->Options->CertificateRetrieval))
							if(intval($xml->Options->CertificateRetrieval) != 1) # Do not retrieve certificates for the recipient (default).
								{
								$response->x_open("Certificates");

									$pem_file = __DIR__ . "/certs/" . $recipient["EmailAddress"] . ".pem";

									if(file_exists($pem_file))
										$status = 1;
									else
										$status = 7;

									if($status == 1)
										{
										foreach(["Status" => $status, "CertificateCount" => 1] as $token => $value)
											{
											$response->x_open($token);
												$response->x_print($value);
											$response->x_close($token);
											}

										$certificate = file_get_contents($pem_file);

										list($null, $certificate) = explode("-----BEGIN CERTIFICATE-----", $certificate, 2);
										list($certificate, $null) = explode("-----END CERTIFICATE-----", $certificate, 2);

										$certificate = str_replace(["\r", "\n"], "", $certificate);

										if(intval($xml->Options->CertificateRetrieval) == 2) # Retrieve the full certificate for each resolved recipient.
											{
											$response->x_open("Certificate");
												$response->x_print($certificate); # ... contains the X509 certificate ... encoded with base64 ...
											$response->x_close("Certificate");
											}
										elseif(intval($xml->Options->CertificateRetrieval) == 3) # Retrieve the mini certificate for each resolved recipient.
											{
											$response->x_open("MiniCertificate");
												$response->x_print($certificate); # ... contains the mini-certificate ... encoded with base64 ...
											$response->x_close("MiniCertificate");
											}
										}
									else
										{
										foreach(["Status" => $status, "CertificateCount" => 0] as $token => $value)
											{
											$response->x_open($token);
												$response->x_print($value);
											$response->x_close($token);
											}
										}

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
	$request["xml"] = active_sync_wbxml_request_a($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Search");

	$response->x_open("Search");

		if(! isset($xml->Store))
			$status = 3; # Server error.
		elseif(! isset($xml->Store->Name))
			$status = 1; # Ok.
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

		if($status == 1)
			{
			$response->x_open("Response");
				$response->x_open("Store");

					if(! isset($xml->Store->Query))
						$status = 3; # Server error.
					else
						$status = 1; # Server error.

					$response->x_open("Status");
						$response->x_print($status);
					$response->x_close("Status");

					if($status == 1)
						if(strval($xml->Store->Name) == "GAL")
							{
							$query = strval($xml->Store->Query);

							$retval = [];

							$settings = active_sync_get_settings_server();

							foreach($settings["login"] as $login)
								{
								if($login["User"] == $request["AuthUser"])
									continue;

								$collection_id = active_sync_get_collection_id_by_type($login["User"], 9);

								if(! $collection_id)
									continue;

								foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $login["User"] . "/" . $collection_id . "/*.data") as $file)
									{
									$server_id = basename($file, ".data");

									$data = active_sync_get_settings_data($login["User"], $collection_id, $server_id);

									$data["Contacts"]["FileAs"] = active_sync_create_fullname_from_data($data);

									foreach(["Email1Address", "Email2Address", "Email3Address"] as $token)
										{
										if(! isset($data["Contacts"][$token]))
											continue;

										if(! strlen($data["Contacts"][$token]))
											continue;

										list($name, $mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

										$data["Contacts"][$token] = $mail;
										}

									$table = [
										"Alias",
										"BusinessPhoneNumber",
										"Email1Address",
										"Email2Address",
										"Email3Address",
										"FileAs",
										"FirstName",
										"HomePhoneNumber",
										"LastName",
										"MiddleName",
										"MobilePhoneNumber"
										];

									foreach($table as $token)
										{
										if(! isset($data["Contacts"][$token]))
											continue;

										if(! strlen($data["Contacts"][$token]))
											continue;

										if(strpos(strtolower($data["Contacts"][$token]), strtolower($query)) === false)
											continue;

										$retval[] = $data["Contacts"];
										}
									}
								}

#							usort($retval, function($a, $b){return($a["FileAs"] - $b["FileAs"]);});

							if(isset($xml->Store->Options->Range))
								$range = strval($xml->Store->Options->Range);
							else
								$range = "0-99";

							list($m, $n) = explode("-", $range);

							$picture_xount = 0;

							foreach($retval as $data)
								{
								if($m > $n)
									break;

								$m ++;

								$response->x_switch("Search");

								$response->x_open("Result");

									$response->x_open("Properties");

										$response->x_switch("GAL");

										$table = [
											"Alias" => "Alias",
											"Company" => "CompanyName",
											"DisplayName" => "FileAs",
											"EmailAddress" => "Email1Address",
											"FirstName" => "FirstName",
											"HomePhone" => "HomePhoneNumber",
											"LastName" => "LastName",
											"MobilePhone" => "MobilePhoneNumber",
											"Office" => "OfficeLocation",
											"Phone" => "BusinessPhoneNumber",
											"Title" => "Title"
											];

										foreach($table as $token_gal => $token_contact)
											{
											if(! isset($data[$token_contact]))
												continue;

											if(! strlen($data[$token_contact]))
												continue;

											$response->x_open($token_gal);
												$response->x_print($data[$token_contact]);
											$response->x_close($token_gal);
											}

										$status = 1;

										if(! isset($data["Picture"]))
											$status = 173;
										elseif(! strlen($data["Picture"]))
											$status = 173;

										if(isset($data["Picture"]))
											if($data["Picture"])
												$picture_xount ++;

										if(isset($xml->Store->Options->Picture->MaxSize))
											if(isset($data["Picture"]))
												if(strlen($data["Picture"]) > intval($xml->Store->Options->Picture->MaxSize))
													$status = 174;

										if(isset($xml->Store->Options->Picture->MaxPicture))
											if($picture_xount > intval($xml->Store->Options->Picture->MaxPicture))
												$status = 175;

										$response->x_open("Picture");

											$response->x_open("Status");
												$response->x_print($status);
											$response->x_close("Status");

											if($status == 1)
												{
#												$response->x_open("Data");
#													$response->x_print_bin(base64_decode($data["Picture"])); # !!!
#												$response->x_close("Data");
												}

										$response->x_close("Picture");

										$response->x_switch("Search");

									$response->x_close("Properties");
								$response->x_close("Result");
								}

							$response->x_switch("Search");

							foreach(["Range" => $range, "Total" => $m] as $token => $value)
								{
								$response->x_open($token);
									$response->x_print($value);
								$response->x_close($token);
								}
							}
						elseif(strval($xml->Store->Name) == "Mailbox")
							{
							$class		= strval($xml->Store->Query->And->Class);
							$collection_id	= strval($xml->Store->Query->And->CollectionId);
							$free_text	= strval($xml->Store->Query->And->FreeText);

							$default_class	= active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

							$retval = [];

							foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/*.data") as $file)
								{
								if(! isset($xml->Store->Query->And->GreaterThan))
									continue;

								if(! isset($xml->Store->Query->And->GreaterThan->DateReceived)) # empty but existing value
									continue;

								if(! isset($xml->Store->Query->And->GreaterThan->Value))
									continue;

								if(! isset($xml->Store->Query->And->LessThan))
									continue;

								if(! isset($xml->Store->Query->And->LessThan->DateReceived)) # empty but existing value
									continue;

								if(! isset($xml->Store->Query->And->LessThan->Value))
									continue;

								$server_id = basename($file, ".data");

								$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

								if(! isset($data["AirSync"]["Class"]))
									$request["AuthUser"] = $default_class;

								if(isset($xml->Store->Query->And->Class))
									if($data["AirSync"]["Class"] != strval($xml->Store->Query->And->Class))
										continue;

								if(strtotime($data["Email"]["DateReceived"]) < strtotime(strval($xml->Store->Query->And->GreaterThan->Value)))
									continue;

								if(strtotime($data["Email"]["DateReceived"]) > strtotime(strval($xml->Store->Query->And->LessThan->Value)))
									continue;

								foreach($data["Body"] as $body)
									if(isset($body["Data"]))
										if(strpos(strtolower($body["Data"]), strtolower($free_text)) !== false) # check mime ...
											$retval[] = $data;
								}

							if(isset($xml->Store->Options->Range))
								$range = strval($xml->Store->Options->Range);
							else
								$range = "0-99";

							list($m, $n) = explode("-", $range);

							foreach($retval as $retval_data)
								{
								if($m > $n)
									break;

								$m ++;

								$response->x_switch("Search");

								$response->x_open("Result");

									$response->x_switch("AirSync");

									foreach(["Class" => $class, "CollectionId" => $collection_id] as $token => $value)
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
												if(! strlen($value))
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
													if(! strlen($value))
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

							foreach(["Range" => $range, "Total" => $m] as $token => $value)
								{
								$response->x_open($token);
									$response->x_print($value);
								$response->x_close($token);
								}
							}

				$response->x_close("Store");
			$response->x_close("Response");
			}

	$response->x_close("Search");

	return($response->response);
	}

function active_sync_handle_send_mail($request)
	{
	if(isset($_SERVER["CONTENT_TYPE"]))
		if($_SERVER["CONTENT_TYPE"] == "application/vnd.ms-sync.wbxml")
			{
			$request = active_sync_handle_send_mail_fix_android($request); # !!!!!!!!!!!!!!!

			$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

			$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

			$mime = strval($xml->Mime);

			if(isset($xml->SaveInSentItems))
				$save_in_sent_items = "T";
			else
				$save_in_sent_items = "F";
			}

	if(isset($_SERVER["CONTENT_TYPE"]))
		if($_SERVER["CONTENT_TYPE"] == "message/rfc822")
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

	if($request["DeviceType"] == "SAMSUNGGTS6802")
		if(preg_match("/(.*\x50\xC3.?)(\x44\x61\x74\x65\x3A\x20.*)/", $request["wbxml"], $matches) == 1)
			$request["wbxml"] = $matches[1] . "\x00" . $matches[2];

	return($request);
	}

function active_sync_handle_settings($request)
	{
	$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

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
						$settings = active_sync_get_settings_folder_server($request["AuthUser"]);

						$body_type = strval($xml->Oof->Get->BodyType);

						$response->x_open("Get");

							if(isset($settings["OOF"]))
								foreach(["OofState", "StartTime", "EndTime"] as $token)
									{
									if(! isset($settings["OOF"][$token]))
										continue;

									$response->x_open($token);
										$response->x_print($settings["OOF"][$token]);
									$response->x_close($token);
									}

							if(isset($settings["OOF"]["OofMessage"]))
								foreach($settings["OOF"]["OofMessage"] as $oof_message)
									{
									$response->x_open("OofMessage");

										foreach($oof_message as $token => $value)
											{
											if(! strlen($value))
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

						$response->x_close("Get");
						}

					if(isset($xml->Oof->Set))
						{
						$settings = active_sync_get_settings_folder_server($request["AuthUser"]);

						$settings["OOF"] = [];

						foreach(["OofState", "StartTime", "EndTime"] as $token)
							{
							if(! isset($xml->Oof->Set->$token))
								continue;

							$settings["OOF"][$token] = strval($xml->Oof->Set->$token);
							}

						if(isset($xml->Oof->Set->OofMessage))
							{
							$settings["OOF"]["OofMessage"] = [];

							foreach($xml->Oof->Set->OofMessage as $oof_message)
								{
								$data = [];

								foreach(["AppliesToInternal", "AppliesToExternalKnown", "AppliesToExternalUnknown", "Enabled", "ReplyMessage", "BodyType"] as $token)
									if(isset($oof_message->$token))
										$data[$token] = strval($oof_message->$token);

								$settings["OOF"]["OofMessage"][] = $data;
								}
							}

						active_sync_put_settings_folder_server($request["AuthUser"], $settings);
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
				if(! isset($xml->DevicePassword->Set->Password))
					$status = 2; # Protocol error.
				elseif(! strval($xml->DevicePassword->Set->Password))
					$status = 2; # Protocol error.
				else
					$status = 1; # Success.

				if($status == 1)
					{
					$settings = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

					$settings["DevicePassword"] = strval($xml->DevicePassword->Set->Password);

					active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings);
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

				$settings = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

				foreach(active_sync_get_default_info() as $token => $value)
					{
					if(! isset($xml->DeviceInformation->Set->$token))
						continue;

					$settings["DeviceInformation"][$token] = strval($xml->DeviceInformation->Set->$token);

					$status = 1; # Success.
					}

				if($status == 1)
					active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings);

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

				$settings = active_sync_get_settings_folder_server($request["AuthUser"]);

				$response->x_open("UserInformation");

					$response->x_open("Status");
						$response->x_print(1); # Success.
					$response->x_close("Status");

					$response->x_open("Get");
						$response->x_open("EmailAddresses");

							foreach(["SmtpAddress" => "SmtpAddress"] as $token => $value)
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
				$settings = active_sync_get_settings_server();

				if(! isset($settings["RightsManagementTemplates"]))
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

										foreach(["TemplateID", "TemplateName", "TemplateDescription"] as $token)
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
	$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

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
	$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

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
	$settings = active_sync_get_settings_folder_client($request["AuthUser"], $request["DeviceId"]);

	if($request["wbxml"] == null)
		$request["wbxml"] = base64_decode($settings["Sync"]);
	else
		$settings["Sync"] = base64_encode($request["wbxml"]);

	active_sync_put_settings_folder_client($request["AuthUser"], $request["DeviceId"], $settings);

	$request["xml"] = active_sync_wbxml_request_a($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	# S3 increase 470 by 180 until 3530 or reconnect

	$status = 1; # Success.

	# check collection
	if($status == 1)
		if(! isset($xml->Collections))
			$status = 4; # Protocol error.

	# check if HeartbeatInterval and Wait exist
	if($status == 1)
		if(isset($xml->Wait))
			if(isset($xml->HeartbeatInterval))
				$status = 4; # Protocol error.

	# check Wait
	if($status == 1)
		if(isset($xml->Wait))
			if(intval($xml->Wait) < 1) # 1 minute
				list($status, $limit) = [14, 1]; # Invalid Wait or HeartbeatInterval value.

	# check Wait
	if($status == 1)
		if(isset($xml->Wait))
			if(intval($xml->Wait) > 59) # 59 minutes
				list($status, $limit) = [14, 59]; # Invalid Wait or HeartbeatInterval value.

	# check HeartbeatInterval
	if($status == 1)
		if(isset($xml->HeartbeatInterval))
			if(intval($xml->HeartbeatInterval) < 60) # 1 minute
				list($status, $limit) = [14, 60]; # Invalid Wait or HeartbeatInterval value.

	# check HeartbeatInterval
	if($status == 1)
		if(isset($xml->HeartbeatInterval))
			if(intval($xml->HeartbeatInterval) > 3540) # 59 minutes
				list($status, $limit) = [14, 3540]; # Invalid Wait or HeartbeatInterval value.

	# check RemoteWipe
	if($status == 1)
		if(active_sync_get_need_wipe($request))
			{
			$status = 12; # The folder hierarchy has changed.

			active_sync_debug("NEED WIPE");
			}

	# check Provision
	if($status == 1)
		if(active_sync_get_need_provision($request))
			{
			$status = 12; # The folder hierarchy has changed.

			active_sync_debug("NEED PROVISION");
			}

	# check FolderSync
	if($status == 1)
		if(active_sync_get_need_folder_sync($request))
			{
			$status = 12; # The folder hierarchy has changed.

			active_sync_debug("NEED FOLDER SYNC");
			}

	# create response

	$response = new active_sync_wbxml_response();

	$response->x_switch("AirSync");

	$response->x_open("Sync");

		if($status == 14)
			$table = ["Status" => $status, "Limit" => $limit];
		else
			$table = ["Status" => $status];

		if($status != 1)
			foreach($table as $token => $value)
				{
				$response->x_open($token);
					$response->x_print($value);
				$response->x_close($token);
				}

		if($status == 1)
			{
			$changed_collections = ["*" => false];
			$synckey_checked = [];

			foreach($xml->Collections->Collection as $collection)
				{
				$sync_key	= strval($collection->SyncKey);
				$collection_id	= strval($collection->CollectionId);

				$changed_collections[$collection_id] = false;
				$synckey_checked[$collection_id] = false;
				}

			################################################################################

			$response->x_open("Collections");

				while(1)
					{
					foreach($xml->Collections->Collection as $collection)
						{
						$sync_key	= strval($collection->SyncKey);
						$collection_id	= strval($collection->CollectionId);

						$settings_client = active_sync_get_settings_files_client($request["AuthUser"], $collection_id, $request["DeviceId"]);

						$settings_server = active_sync_get_settings_files_server($request["AuthUser"], $collection_id);

						$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $collection_id);

						# check GetChanges
						# MS-ASCMD - 2.2.3.79 GetChanges
						# if SyncKey == 0 then absence of GetChanges == 0
						# if SyncKey != 0 then absence of GetChanges == 1
						# if GetChanges is empty then a value of 1 is assumed in any case

						if(! isset($collection->GetChanges))
							$get_changes = ($sync_key == 0 ? 0 : 1);
						elseif(strval($collection->GetChanges) == "")
							$get_changes = 1;
						else
							$get_changes = intval($collection->GetChanges);

						# check WindowsSize (collection)
						if(! isset($collection->WindowSize))
							$window_size = 100;
						elseif(intval($collection->WindowSize) == 0)
							$window_size = 512;
						elseif(intval($collection->WindowSize) > 512)
							$window_size = 512;
						else
							$window_size = intval($collection->WindowSize);

						################################################################################

/*
						$status = 1;

						if($status == 1)
							if(! isset($collection->SyncKey))
								$status = 4; # Protocol error.

						if($status == 1)
							if(! isset($settings_client["SyncKey"]))
								$status = 3; # Invalid synchronization key.

						if($status == 1)
							if(isset($settings_client["SyncKey"]))
								if(isset($collection->SyncKey))
									if($settings_client["SyncKey"] != intval($collection->SyncKey))
										$status = 3; # Invalid synchronization key.

						if($status == 1)
							if(isset($settings_client["SyncKey"]))
								if(! $synckey_checked[$collection_id])
									$settings_client["SyncKey"] ++;

						if($status == 1)
							if(isset($collection->SyncKey))
								if(intval($collection->SyncKey) == 0)
									$settings_client["SyncDat"] = [];

						if($status == 1)
							if(isset($collection->SyncKey))
								if(intval($collection->SyncKey) == 0)
									$changed_collections[$collection_id] = true;

						if($status == 3)
							$settings_client["SyncKey"] = 0;

						$synckey_checked[$collection_id] = true;
*/

						################################################################################
						# check SyncKey
						################################################################################

						if($synckey_checked[$collection_id])
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
								$settings_client["SyncDat"] = [];

								$status = 1; # Success.
								}
							elseif($sync_key != $settings_client["SyncKey"])
								{
								$settings_client["SyncKey"] = 0;
								$settings_client["SyncDat"] = [];

								$status = 3; # Invalid synchronization key.
								}
							else
								{
								$settings_client["SyncKey"] ++;

								$status = 1; # Success.
								}

							$synckey_checked[$collection_id] = true;
							}

						################################################################################

						$table = [
							"SyncKey" => $settings_client["SyncKey"],
							"CollectionId" => $collection_id,
							"Status" => $status
							];

						################################################################################

						if($sync_key == 0)
							{
							$changed_collections[$collection_id] = true;

							$response->x_switch("AirSync");

							$response->x_open("Collection");

								foreach($table as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

							$response->x_close("Collection");
							}
						elseif($status != 1)
							{
							$changed_collections[$collection_id] = true;

							$response->x_switch("AirSync");

							$response->x_open("Collection");

								foreach($table as $token => $value)
									{
									$response->x_open($token);
										$response->x_print($value);
									$response->x_close($token);
									}

							$response->x_close("Collection");
							}
						elseif($status == 1)
							{
							if(isset($collection->Commands))
								{
								$changed_collections[$collection_id] = true;

								$response->x_switch("AirSync");

								$response->x_open("Collection");

									foreach($table as $token => $value)
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

												if(! $server_id)
													$status = 16; # Server error.
												else
													$status = active_sync_handle_sync_save($add, $request["AuthUser"], $collection_id, $server_id);

												if($status == 1)
													{
													$settings_client["SyncDat"][$server_id] = filemtime(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data");

													$response->x_open("ServerId");
														$response->x_print($server_id);
													$response->x_close("ServerId");
													}

												foreach(["ClientId" => $client_id, "Status" => $status] as $token => $value)
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

												if(! isset($settings_client["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												elseif(! isset($settings_server["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												else
													$status = active_sync_handle_sync_save($change, $request["AuthUser"], $collection_id, $server_id);

												if($status == 1)
													$settings_client["SyncDat"][$server_id] = filemtime(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data");

												foreach(["ServerId" => $server_id, "Status" => $status] as $token => $value)
													{
													$response->x_open($token);
														$response->x_print($value);
													$response->x_close($token);
													}

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

												if(! isset($settings_client["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												elseif(! isset($settings_server["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												elseif(! file_exists(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data"))
													$status = 8; # Object not found.
												else
													{
													$status = 1; # Success;

													$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);
													
													if(isset($data["Attachments"]))
														foreach($data["Attachments"] as $attachment)
															{
#															$file = __DIR__ . "/" . $reuest["AuthUser"] . "/.files/" . $attachment["AirSyncBase"]["FileReference"];
#															if(isset($attachment["AirSyncBase"]["FileReference"]))
#																$status = 8; # Object not found.
#															elseif(! file_exists(ACTIVE_SYNC_ATT_DIR . "/" . $attachment["AirSyncBase"]["FileReference"]))
#																$status = 8; # Object not found.
#															elseif(!unlink(__DIR__ . "/" . $reuest["AuthUser"] . "/.files/" . $attachment["AirSyncBase"]["FileReference"]))
#																$status = 5; # Server error.

#															if($status != 1)
#																break;
															}
													
													if(! unlink(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $collection_id . "/" . $server_id . ".data"))
														$status = 5; # Server error.
													}

												if($status == 1)
													unset($settings_client["SyncDat"][$server_id]);

												foreach(["ServerId" => $server_id, "Status" => $status] as $token => $value)
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

												if(! isset($settings_client["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												elseif(! isset($settings_server["SyncDat"][$server_id]))
													$status = 8; # Object not found.
												else
													{
													$status = 1; # Success.

													active_sync_handle_sync_send($response, $request["AuthUser"], $collection_id, $server_id, $collection);
													}

												# wrong order? correct order: ServerId, Status, ApplicationData
												# wrong order? correct order: ApplicationData, ServerId, Status

												foreach(["ServerId" => $server_id, "Status" => $status] as $token => $value)
													{
													$response->x_open($token);
														$response->x_print($value);
													$response->x_close($token);
													}

											$response->x_close("Fetch");
											}

									$response->x_close("Responses");

								$response->x_close("Collection");
								} # if(isset($collection->Commands))

							################################################################################
							# get the changes
							# !!! read info about GetChanges and SyncKey !!!
							################################################################################

							if($get_changes == 1)
								{
								$settings_server = active_sync_get_settings_files_server($request["AuthUser"], $collection_id);

								$jobs = [];

								foreach($settings_server["SyncDat"] as $server_id => $server_timestamp)
									{
									$data = active_sync_get_settings_data($request["AuthUser"], $collection_id, $server_id);

									if(! isset($data["AirSync"]["Class"]))
										$data["AirSync"]["Class"] = $default_class;

									################################################################################
									# check options
									# inbox contains email and sms. FilterType can differ. find the right one
									################################################################################

									$option_filter_type = ACTIVE_SYNC_FILTER_ALL;
									$process_sms = true; # imagine we have sms

									if(isset($collection->Options))
										foreach($collection->Options as $options)
											{
											$option_class = $default_class;

											if(isset($options->Class))
												$option_class = strval($options->Class); # only occurs on email/sms

											if($option_class != $data["AirSync"]["Class"])
												continue;

											if(isset($options->FilterType))
												$option_filter_type = intval($options->FilterType);

											$process_sms = false;
											}

									################################################################################
									# sync SMS
									################################################################################

									if($process_sms)
										{
										if(! isset($settings_client["SyncDat"][$server_id]))
											$settings_client["SyncDat"][$server_id] = "*";
										elseif($settings_client["SyncDat"][$server_id] != "*")
											if($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
												$jobs["SoftDelete"][] = $server_id;
											else
												$jobs["SoftDelete"][] = $server_id;

										$option_filter_type = 9;
										}

									################################################################################
									# sync all
									################################################################################

									if($option_filter_type == ACTIVE_SYNC_FILTER_ALL)
										{
										if(! isset($settings_client["SyncDat"][$server_id]))
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
										$stat_filter = ["now", "- 1 day", "- 3 day", "- 1 week", "- 2 week", "- 1 month", "- 3 month", "- 6 month", "now"];

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

										if(! isset($settings_client["SyncDat"][$server_id]))
											{
											if($data_timestamp < $stat_filter)
												$settings_client["SyncDat"][$server_id] = "*";
											else
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] == "*")
											{
											if($data_timestamp < $stat_filter)
												{
												#
												}
											else
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											{
											if($data_timestamp < $stat_filter)
												$jobs["SoftDelete"][] = $server_id;
											else
												$jobs["Change"][] = $server_id;
											}
										else
											{
											if($data_timestamp < $stat_filter)
												$jobs["SoftDelete"][] = $server_id;
											}
										}

									###########################################################################################
									# sync incomplete (tasks only)
									###########################################################################################

									if($option_filter_type == ACTIVE_SYNC_FILTER_INCOMPLETE)
										{
										if(! isset($settings_client["SyncDat"][$server_id]))
											{
											if($data["Tasks"]["Complete"] != 1)
												$jobs["Add"][] = $server_id;
											else
												$settings_client["SyncDat"][$server_id] = "*";
											}
										elseif($settings_client["SyncDat"][$server_id] == "*")
											{
											if($data["Tasks"]["Complete"] != 1)
												$jobs["Add"][] = $server_id;
											}
										elseif($settings_client["SyncDat"][$server_id] != $settings_server["SyncDat"][$server_id])
											{
											if($data["Tasks"]["Complete"] != 1)
												$jobs["Change"][] = $server_id;
											else
												$jobs["SoftDelete"][] = $server_id;
											}
										}
									}

								################################################################################
								# check for to Delete
								################################################################################

								foreach($settings_client["SyncDat"] as $server_id => $client_timestamp)
									if(! isset($settings_server["SyncDat"][$server_id]))
										$jobs["Delete"][] = $server_id;

								################################################################################
								# check for elements sended by server
								################################################################################

								if(count($jobs) > 0)
									{
									$changed_collections[$collection_id] = true;

									$response->x_switch("AirSync");

									$response->x_open("Collection");

										foreach($table as $token => $value)
											{
											$response->x_open($token);
												$response->x_print($value);
											$response->x_close($token);
											}

										$response->x_switch("AirSync");

										$response->x_open("Commands");

											$estimate = 0;

											foreach(["Add", "Change"] as $command)
												if(isset($jobs[$command]))
													foreach($jobs[$command] as $server_id)
														{
														if($estimate == $window_size)
															break;

														$estimate ++;

														$settings_client["SyncDat"][$server_id] = $settings_server["SyncDat"][$server_id];

														$response->x_switch("AirSync");

														$response->x_open($command);
															$response->x_open("ServerId");
																$response->x_print($server_id);
															$response->x_close("ServerId");

															active_sync_handle_sync_send($response, $request["AuthUser"], $collection_id, $server_id, $collection);

														$response->x_close($command);
														}

											################################################################################
											# output for Delete/SoftDelete
											################################################################################

											foreach(["Delete", "SoftDelete"] as $command)
												if(isset($jobs[$command]))
													foreach($jobs[$command] as $server_id)
														{
														if($estimate == $window_size)
															break;

														$estimate ++;

														if($command == "Delete")
															{
															unset($settings_server["SyncDat"][$server_id]);
															unset($settings_client["SyncDat"][$server_id]);
															}

														if($command == "SoftDelete")
															{
															$settings_server["SyncDat"][$server_id] = "*";
															$settings_client["SyncDat"][$server_id] = "*";
															}

														$response->x_open($command);
															$response->x_open("ServerId");
																$response->x_print($server_id);
															$response->x_close("ServerId");
														$response->x_close($command);
														}

										$response->x_close("Commands");

										$estimate = 0;

										foreach(["Add", "Change", "Delete", "SoftDelete"] as $command)
											if(isset($jobs[$command]))
												$estimate += count($jobs[$command]);

										if($estimate > $window_size)
											{
											$response->x_switch("AirSync");

											$response->x_open("MoreAvailable", false);
											}

									$response->x_close("Collection");
									} # if(count($jobs) > 0)
								} # if($get_changes == 0)
							} # elseif($status == 1)

						################################################################################
						# continue if no changes detected
						################################################################################

						if(! $changed_collections[$collection_id])
							continue;

						active_sync_put_settings_sync_client($request["AuthUser"], $collection_id, $request["DeviceId"], $settings_client);

						$changed_collections["*"] = true;
						} # foreach($xml->Collections->Collection as $collection)

					################################################################################
					# exit if changes were detected
					################################################################################

					if($changed_collections["*"])
						break;

					if(! isset($xml->Wait))
						if(! isset($xml->HeartbeatInterval))
							break;

					if(isset($xml->Wait))
						if($_SERVER["REQUEST_TIME"] + (intval($xml->Wait) * 60) < time())
							break;

					if(isset($xml->HeartbeatInterval))
						if($_SERVER["REQUEST_TIME"] + $xml->HeartbeatInterval < time())
							break;

					sleep(ACTIVE_SYNC_SLEEP);
					} # while(1)

				################################################################################
				# return empty response if no changes at all.
				# this will also prevent invalid sync key ... gotcha
				# this saves a lot debug data
				################################################################################

				if(! $changed_collections["*"])
					return("");

				foreach($xml->Collections->Collection as $collection)
					{
					$sync_key	= strval($collection->SyncKey);
					$collection_id	= strval($collection->CollectionId);

					if($changed_collections[$collection_id])
						continue;

					$settings_client = active_sync_get_settings_files_client($request["AuthUser"], $collection_id, $request["DeviceId"]);

					$settings_client["SyncKey"] ++;

					active_sync_put_settings_sync_client($request["AuthUser"], $collection_id, $request["DeviceId"], $settings);

					$response->x_switch("AirSync");

					$response->x_open("Collection");

						$table = [
							"SyncKey" => $settings_client["SyncKey"],
							"CollectionId" => $collection_id,
							"Status" => 1
							];

						foreach($table as $token => $value)
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

function active_sync_handle_sync_save($xml, $user, $collection_id, $server_id)
	{
	$class = active_sync_get_class_by_collection_id($user, $collection_id);

	if($class == "Email")
		{
		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		foreach(["Class" => "SMS"] as $token => $value)
			if(isset($xml->$token))
				$data["AirSync"][$token] = strval($xml->$token);

#		foreach(["UmCallerID", "UmUserNotes"] as $token)
#			if(isset($xml->ApplicationData->$token))
#				$data["Email2"][$token] = strval($xml->ApplicationData->$token);
#				$data["Attachments"][]["Email2"][$token] = $data["Email2"][$token];
		}
	else
		$data = [];

	$table = [
		"Contacts" => [
			"Contacts" => active_sync_get_default_contacts(),
			"Contacts2" => active_sync_get_default_contacts2()
			],
		"Calendar" => [
			"Calendar" => active_sync_get_default_calendar()
			],
		"Email" => [
			"Email" => active_sync_get_default_email(),
			"Email2" => active_sync_get_default_email2()
			],
		"Notes" => [
			"Notes" => active_sync_get_default_notes()
			],
		"Tasks" => [
			"Tasks" => active_sync_get_default_tasks()
			]
		];

	foreach($table[$class] as $codepage => $fields)
		foreach($fields as $token => $value)
			if(isset($xml->ApplicationData->$token))
				$data[$codepage][$token] = strval($xml->ApplicationData->$token);

	if(isset($xml->ApplicationData->Attendees))
		foreach($xml->ApplicationData->Attendees->Attendee as $attendee)
			{
			$a = [];

			foreach(active_sync_get_default_attendee() as $token => $value)
				if(isset($attendee->$token))
					$a[$token] = strval($attendee->$token);

			$data["Attendees"][] = $a;
			}

	if(isset($xml->ApplicationData->Body))
		foreach($xml->ApplicationData->Body as $body)
			{
			$b = [];

			foreach(active_sync_get_default_body() as $token => $value)
				if(isset($body->$token))
					$b[$token] = strval($body->$token);

			if(isset($b["Data"]))
				if(strlen($b["Data"]))
					$data["Body"][] = $b;
			}

	if(isset($xml->ApplicationData->Categories))
		if($xml->ApplicationData->Categories->Category)
			foreach($xml->ApplicationData->Categories->Category as $category)
				$data["Categories"][] = strval($category);

	if(isset($xml->ApplicationData->Children))
		if($xml->ApplicationData->Children->Child)
			foreach($xml->ApplicationData->Children->Child as $child)
				$data["Children"][] = strval($child);

	if(isset($xml->ApplicationData->Flag))
		{
		$data["Flag"] = []; # force empty flag

		foreach(active_sync_get_default_flag($class) as $token => $value)
			if(isset($xml->ApplicationData->Flag->$token))
				$data["Flag"][$class][$token] = strval($xml->ApplicationData->Flag->$token);
		}

	if(isset($xml->ApplicationData->Recurrence))
		foreach(active_sync_get_default_recurrence() as $token => $value)
			if(isset($xml->ApplicationData->Recurrence->$token))
				$data["Recurrence"][$token] = strval($xml->ApplicationData->Recurrence->$token);

	return(active_sync_put_settings_data($user, $collection_id, $server_id, $data) ? 1 : 16);
	}

function active_sync_handle_sync_send(& $response, $user, $collection_id, $server_id, $collection)
	{
	$class = active_sync_get_class_by_collection_id($user, $collection_id);

	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	if(isset($data["AirSync"]))
		{
		$response->x_switch("AirSync");

		foreach($data["AirSync"] as $token => $value)
			{
			if(! strlen($value))
				{
				$response->open($token, false);

				continue;
				}

			$response->x_open($token);
				$response->x_print($value);
			$response->x_close($token);
			}
		}

	$table = [
#		"AirSyncBase" => [
#			"NativeBodyType" => 0
#			],
		"Calendar" => [
			"Calendar" => active_sync_get_default_calendar()
			],
		"Contacts" => [
			"Contacts" => active_sync_get_default_contacts(),
			"Contacts2" => active_sync_get_default_contacts2()
			],
		"Email" => [
			"Email" => active_sync_get_default_email(),
			"Email2" => active_sync_get_default_email2()
			],
		"Notes" => [
			"Notes" => active_sync_get_default_notes()
			],
		"Tasks" => [
			"Tasks" => active_sync_get_default_tasks()
			]
		];

	$response->x_switch("AirSync");

	$response->x_open("ApplicationData");

		foreach($table[$class] as $codepage => $fields)
			{
			if(! isset($data[$codepage]))
				continue;

			$response->x_switch($codepage);

			foreach($fields as $token => $null)
				{
				if(! isset($data[$codepage][$token]))
					continue;

				if(! strlen($data[$codepage][$token]))
					{
					$response->x_open($token, false);

					continue;
					}

				# The ... element is defined as an element in the Calendar namespace.
				# The value of this element is a string data type, represented as a
				# Compact DateTime ([MS-ASDTYPE] section 2.7.2).

				if(in_array($token, ["DtStamp", "StartTime", "EndTime"]))
					$data[$codepage][$token] = date("Ymd\THis\Z", strtotime($data[$codepage][$token]));

				# The value of this element is a datetime data type in Coordinated Universal
				# Time (UTC) format, as specified in [MS-ASDTYPE] section 2.3.

				if(in_array($token, ["Aniversary", "Birthday"]))
					$data[$codepage][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data[$codepage][$token]));

				# The value of the * element is a string data type represented as a
				# Compact DateTime ([MS-ASDTYPE] section 2.7.2).

				if(in_array($token, ["DateCompleted", "DueDate", "OrdinalDate", "ReminderTime", "Start", "StartDate", "UtcDueDate", "UtcStartDate"]))
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

				foreach($data["Attachments"] as $attachment)
					{
					$response->x_switch("AirSyncBase");

					$response->x_open("Attachment");

						foreach(["Email"] as $codepage)
							{
							if(! isset($attachment[$codepage]))
								continue;

							$response->x_switch($codepage);

							foreach($attachment[$codepage] as $token => $null)
								{
								if(! strlen($attachment[$codepage][$token]))
									{
									$response->x_open($token, false);

									continue;
									}

								$response->x_open($token);
									$response->x_print($attachment[$codepage][$token]);
								$response->x_close($token);
								}
							}

					$response->x_close("Attachment");
					}

			$response->x_close("Attachments");
			}

		if(isset($data["Attendees"]))
			{
			$response->x_switch($class);

			$response->x_open("Attendees");

				foreach($data["Attendees"] as $attendee)
					{
					$response->x_open("Attendee");

						foreach(active_sync_get_default_attendee() as $token => $null)
							{
							if(! isset($attendee[$token]))
								continue;

							if(! strlen($attendee[$token]))
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
			$response->x_switch($class);

			$response->x_open("Categories");

				foreach($data["Categories"] as $category)
					{
					$response->x_open("Category");
						$response->x_print($category);
					$response->x_close("Category");
					}

			$response->x_close("Categories");
			}

		if(isset($data["Children"]))
			{
			$response->x_switch($class);

			$response->x_open("Children");

				foreach($data["Children"] as $child)
					{
					$response->x_open("Child");
						$response->x_print($child);
					$response->x_close("Child");
					}

			$response->x_close("Children");
			}

		if(isset($data["Flag"]))
			if(count($data["Flag"]) == 0)
				{
				$response->x_switch($class);

				$response->x_open("Flag", false);
				}
			else
				{
				$response->x_switch($class);

				$response->x_open("Flag");

					if(isset($data["Flag"][$class]))
						{
						$response->x_switch($class);

						foreach($data["Flag"][$class] as $token => $value)
							{
							if(! strlen($value))
								{
								$response->x_open($token, false);

								continue;
								}

							$response->x_open($token);
								$response->x_print($value);
							$response->x_close($token);
							}
						}

				$response->x_close("Flag");
				}

		if(isset($data["Meeting"]))
			{
			$response->x_switch($class);

			$response->x_open("MeetingRequest");

				foreach(["Email", "Email2", "Calendar"] as $codepage)
					{
					if(! isset($data["Meeting"][$codepage]))
						continue;

					$response->x_switch($codepage);

					foreach($data["Meeting"][$codepage] as $token => $value)
						{
						if(! strlen($value))
							{
							$response->x_open($token, false);

							continue;
							}

						$response->x_open($token);
							$response->x_print($value);
						$response->x_close($token);
						}
					}

			$response->x_close("MeetingRequest");
			}

		if(isset($data["Recurrence"]))
			{
			$response->x_switch($class);

			$response->x_open("Recurrence");

				foreach(active_sync_get_default_recurrence() as $token => $null)
					{
					if(! isset($data["Recurrence"][$token]))
						continue;

					if(! strlen($data["Recurrence"][$token]))
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

		if(isset($data["RightsManagement"]))
			{
			$response->x_switch("RightsManagement");

			$response->x_open("RightsManagementLicense");

				# foreach($data["RightsManagement"] as $rights_management_id => $rights_management_data) # multiple licenses allowed on single message?

				foreach(active_sync_get_default_rights_management() as $token => $null)
					{
					if(! isset($data["RightsManagement"][$token]))
						continue;

					if(! strlen($data["RightsManagement"][$token]))
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
			if(isset($collection->Options))
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
										if(! isset($data["RightsManagement"][$token]))
											continue;

										if(! strlen($data["RightsManagement"][$token]))
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
						foreach($data["Body"] as $body) # !!!
							{
							if(! isset($body["Type"]))
								continue;

							if($body["Type"] != intval($preference->Type))
								continue;

							$response->x_switch("AirSyncBase");

							$response->x_open("Body");

								if(isset($preference["Preview"]))
									foreach($data["Body"] as $preview) # !!!
										{
										if(! isset($preview["Type"]))
											continue;

										if($preview["Type"] != 1)
											continue;

										$response->x_open("Preview");
											$response->x_print(substr($preview["Data"], 0, intval($preference->Preview)));
										$response->x_close("Preview");
										}

								if(isset($preference->TruncationSize))
									if(isset($body["EstimatedDataSize"]))
										if(intval($preference->TruncationSize) < $body["EstimatedDataSize"])
											{
											$body["Data"] = substr($body["Data"], 0, intval($preference->TruncationSize));

											$response->x_open("Truncated");
												$response->x_print(1);
											$response->x_close("Truncated");
											}

								foreach($body as $token => $value)
									{
									if(! strlen($value))
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
						}
					}

	$response->x_close("ApplicationData");
	}

function active_sync_handle_validate_cert($request)
	{
	$request["xml"] = active_sync_wbxml_request_b($request["wbxml"]);

	$xml = simplexml_load_string($request["xml"], "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

	if(isset($xml->CheckCRL))
		$CheckCRL = strval($xml->CheckCRL);
	else
		$CheckCRL = 0;

	$states = [];

	if(isset($xml->CertificateChain))
		foreach($xml->CertificateChain->Certificate as $Certificate)
			{
			$state = 1; # Success.

			$states[] = $state;
			}

	if(isset($xml->Certificates))
		foreach($xml->Certificates->Certificate as $Certificate)
			{
			$cert = chunk_split($Certificate, 64);

			$cert = "-----BEGIN CERTIFICATE-----" . PHP_EOL . $cert . "-----END CERTIFICATE-----";

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
			elseif(! isset($data["extensions"]["crlDistributionPoints"]))
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

define("ACTIVE_SYNC_HTTP_AUTHENTICATE_REALM", "T-ActiveSync-Realm");

function active_sync_http()
	{
	$request = active_sync_http_query_parse();

	if($_SERVER["PHP_SELF"] == "/active-sync/index.php")
		{
		if(! defined("ACTIVE_SYNC_WEB_DIR"))
			http_response_code(204);
		elseif(! is_dir(ACTIVE_SYNC_WEB_DIR))
			http_response_code(204);
		else
			header("Location: web");
		}

	if($_SERVER["PHP_SELF"] == "/Autodiscover/Autodiscover.xml")
		if(! isset($_SERVER["REQUEST_METHOD"]))
			http_response_code(501);
		elseif($_SERVER["REQUEST_METHOD"] == "GET")
			active_sync_handle_autodiscover($request);
		elseif($_SERVER["REQUEST_METHOD"] == "POST")
			active_sync_handle_autodiscover($request);
		else
			http_response_code(501);

	if($_SERVER["PHP_SELF"] == "/Microsoft-Server-ActiveSync")
		if(! isset($_SERVER["REQUEST_METHOD"]))
			http_response_code(501);
		elseif($_SERVER["REQUEST_METHOD"] == "OPTIONS")
			{
			header("MS-Server-ActiveSync: " . active_sync_get_version());
			header("MS-ASProtocolVersions: " . active_sync_get_supported_versions());
			# header("X-MS-RP: " . active_sync_get_supported_versions());
			header("MS-ASProtocolCommands: " . active_sync_get_supported_commands());
			header("Allow: OPTIONS,POST");
			header("Public: OPTIONS,POST");
			}
		elseif($_SERVER["REQUEST_METHOD"] == "POST")
			{
			if(isset($request["wbxml"]))
				{
				$data = $request["wbxml"];
				$data = active_sync_wbxml_request_b($data);
				$data = active_sync_wbxml_pretty($data);
				active_sync_debug($data, "REQUEST");
				}
			else
				active_sync_debug("", "REQUEST");

			$response = [];

			if(! active_sync_get_is_identified($request))
				header("WWW-Authenticate: basic realm=\"ActiveSync\"");
			elseif($request["DeviceId"] == "validate")
				http_response_code(501);
			else
				{
				active_sync_folder_init($request["AuthUser"]);

				$table = active_sync_get_table_handle();

				$cmd = $request["Cmd"];

				if(! isset($table[$cmd]))
					http_response_code(501);
				elseif(! strlen($table[$cmd]))
					http_response_code(501);
				elseif(! function_exists($table[$cmd]))
					http_response_code(501);
				else
					$response["wbxml"] = $table[$cmd]($request);

				if(! headers_sent())
					{
					header_remove("X-Powered-By");

					if(isset($response["wbxml"]))
						{
						header("Content-Type: application/vnd.ms-sync.wbxml");
						header("Content-Length: " . strlen($response["wbxml"]));
						}
					}

				if(isset($response["wbxml"]))
					print($response["wbxml"]);
				}

			if(isset($response["wbxml"]))
				{
				$data = $response["wbxml"];
				$data = active_sync_wbxml_request_b($data);
				$data = active_sync_wbxml_pretty($data);
				active_sync_debug($data, "RESPONSE");
				}
			else
				active_sync_debug("", "RESPONSE");
			}
		else
			http_response_code(501);
	}

function active_sync_http_query_parse()
	{
	$retval = [
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

		"AuthPass"		=> "",	# extra field, not specified
		"AuthUser"		=> "",	# extra field, not specified
		];

	$table = [
		"AcceptMultiPart" => "HTTP_MS_ASACCEPTMULTIPART",
		"PolicyKey" => "HTTP_X_MS_POLICYKEY",
		"ProtocolVersion" => "HTTP_MS_ASPROTOCOLVERSION",

		"AuthUser" => "PHP_AUTH_USER",
		"AuthPass" => "PHP_AUTH_PW"
		];

	foreach($table as $key => $trans)
		if(isset($_SERVER[$trans]))
			$retval[$key] = $_SERVER[$trans];

	if(isset($_SERVER["QUERY_STRING"]))
		if(strlen($_SERVER["QUERY_STRING"]))
			if(preg_match("#^([A-Za-z0-9+/]{4})*([A-Za-z0-9+/]{4}|[A-Za-z0-9+/]{3}=|[A-Za-z0-9+\/]{2}==)?$#", $_SERVER["QUERY_STRING"]))
				{
				$query = base64_decode($_SERVER["QUERY_STRING"]);

				$commands = active_sync_get_table_command();

				$parameters = [
					0 =>"AttachmentName",
					1 => "CollectionId",
					3 => "ItemId",
					4 => "LongId",
					6 => "Occurence",
					7 => "Options",
					8 => "User"
					];

				$device_id_length = ord($query[4]);						# DeviceIdLength
				$policy_key_length = ord($query[5 + $device_id_length]);			# PolicyKeyLength
				$device_type_length = ord($query[6 + $device_id_length + $policy_key_length]);	# DeviceTypeLength

				$table = [
					"CProtocolVersion",
					"CCommandCode",
					"vLocale",
					"CDeviceIdLength",
					"H" . strval($device_id_length * 2) . "DeviceId",
					"CPolicyKeyLength",
					"VPolicyKey",
					"CDeviceTypeLength",
					"A" . strval($device_type_length * 1) . "DeviceType"
					];

				if($policy_key_length != 4)
					unset($table[6]);

				$z = unpack($table, $query);

				$query = substr($query, 7 + $device_id_length + $policy_key_length + $device_type_length);

				while(strlen($query))
					{
					$tag = ord($query[0]);
					$length = ord($query[1]);
					$value = substr($query, 2, $length);
					$query = substr($query, 2 + $length);

					if($g["Tag"] == 7) # options
						{
						$retval["SaveInSent"] = (($value & 0x01) ? "T" : "F");
						$retval["AcceptMultiPart"] = (($value & 0x02) ? "T" : "F");
						}
					elseif(isset($parameters[$tag]))
						{
						$key = $parameters[$tag];

						$retval[$key] = $value;
						}
					}

				if(isset($commands[$z["CommandCode"]]))
					$retval["Cmd"] = $commands[$z["CommandCode"]];

				$table = [
					"DeviceId",
					"DeviceType",
					"Locale",
					"PolicyKey",
					"ProtocolVersion"
					];

				foreach($table as $key)
					if(isset($z[$key]))
						$retval[$key] = $z[$key];

				$retval["ProtocolVersion"] /= 10; # 141 -> 14.1
				}
			else
				{
				$table = [
					"AttachmentName",
					"Cmd",
					"CollectionId",
					"DeviceId",
					"DeviceType",
					"ItemId",
					"LongId",
					"Occurence",
					"SaveInSent",
					"User"
					];

				foreach($table as $key)
					if(isset($_GET[$key]))
						$retval[$key] = $_GET[$key];
				}

	# user in query can vary from user in authentication
	foreach(["AuthUser", "User"] as $key)
		{
		# take care about brain-disabled-users
		$retval[$key] = strtolower($retval[$key]);

		if(strpos($retval[$key], "\x5C") !== false) # \
			list($null, $retval[$key]) = explode("\x5C", $retval[$key]);

		if(strpos($retval[$key], "\x40") !== false) # @
			list($retval[$key], $null) = explode("\x40", $retval[$key]);
		}

#	if(isset($_SERVER["CONTENT_LENGTH"]))
#		if($_SERVER["CONTENT_LENGTH"] > 0)

	if(isset($_SERVER["CONTENT_TYPE"]))
		{
		$data = file_get_contents("php://input");

		if($_SERVER["CONTENT_TYPE"] == "application/vnd.ms-sync")
			{
			$retval["xml"] = active_sync_wbxml_request_a($data);
			$retval["wbxml"] = $data;
			}

		if($_SERVER["CONTENT_TYPE"] == "application/vnd.ms-sync.wbxml")
			{
			$retval["xml"] = active_sync_wbxml_request_a($data);
			$retval["wbxml"] = $data;
			}

		if($_SERVER["CONTENT_TYPE"] == "text/xml")
			$retval["xml"] = $data;
		}

	return($retval);
	}

function active_sync_mail_add_container_calendar(& $data, $body, $user)
	{
	$host = active_sync_get_domain(); # needed for user@host

	$temp = $body;
	$vcalendar = active_sync_vcalendar_parse($body);
	$body = $temp;


	$vcalendar = [];

	if(isset($vcalendar["VCALENDAR"]))
		$vcalendar = $vcalendar["VCALENDAR"];

	$vevent = [];

	if(isset($vcalendar["VEVENT"]))
		$vevent = $vcalendar["VEVENT"];

	foreach(active_sync_get_default_meeting() as $token => $value)
		$data["Meeting"]["Email"][$token] = $value;

	$timezone_informations = active_sync_get_table_timezone_information();

	$data["Meeting"]["Email"]["TimeZone"] = $timezone_informations[28][0];

	$codepage_table = [
		"Email" => [
			"DTSTART" => "StartTime",
			"DTSTAMP" => "DtStamp",
			"DTEND" => "EndTime",
			"LOCATION" => "Location"
			],
		"Calendar" => [
			"UID" => "UID"
			]
		];

	foreach($codepage_table as $codepage => $null)
		foreach($codepage_table[$codepage] as $key => $token)
			if(isset($vevent[$key]))
				$data["Meeting"][$codepage][$token] = $vevent[$key];

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

	$organizer = (isset($vevent["ORGANIZER"][$user . "@" . $host]) ? 1 : 0);

#	foreach(["CANCEL" => [7, 5], "REQUEST" => [3, 1]] as $key => $value)
#		if($vcalendar["METHOD"] == $key)
#			$data["Meeting"]["Email"]["MeetingStatus"] = $value[$organizer];

	########################################################################
	# check MeetingMessageType
	########################################################################
	# 0	A silent update was performed, or the message type is unspecified.
	# 1	Initial meeting request.
	# 3	Informational update.
	########################################################################

	$data["Meeting"]["Email2"]["MeetingMessageType"] = 0;

	foreach(["CANCEL" => 0, "REPLY" => 3, "REQUEST" => 1] as $key => $value)
		if($vcalendar["METHOD"] == $key)
			$data["Meeting"]["Email2"]["MeetingMessageType"] = $value;

	if(isset($vevent["CLASS"]))
		foreach(["DEFAULT" => 0, "PUBLIC" => 1, "PRIVATE" => 2, "CONFIDENTIAL" => 3] as $key => $value)
			if($vevent["CLASS"] == $key)
				$data["Meeting"]["Email"]["Sensitivity"] = $value;

#	$data["Meeting"]["Email"]["AllDayEvent"] = 0;

	if(isset($vevent["X-MICROSOFT-CDO-ALLDAYEVENT"]))
		foreach(["FALSE" => 0, "TRUE" => 1] as $key => $value)
			if($vevent["X-MICROSOFT-CDO-ALLDAYEVENT"] == $key)
				$data["Meeting"]["Email"]["AllDayEvent"] = $value;

#	$data["Meeting"]["Email"]["Organizer"] = $user . "@" . $host;

	if(isset($vevent["ORGANIZER"]))
		foreach($vevent["ORGANIZER"] as $key => $null)
			$data["Meeting"]["Email"]["Organizer"] = $key;

	if(isset($vevent["ATTENDEE"][$user . "@" . $host]["RVSP"]))
		foreach(["FALSE" => 0, "TRUE" => 1] as $key => $value)
			{
			$data["Meeting"]["Email"]["ResponseRequested"] = 0;

			if($vevent["ATTENDEE"]["RVSP"] == $key)
				$data["Meeting"]["Email"]["ResponseRequested"] = $value;
			}

	if(isset($vevent["VALARM"]["TRIGGER"]))
		$data["Meeting"]["Email"]["Reminder"] = substr($vevent["VALARM"]["TRIGGER"], 3, 0 - 1); # -PT*M

#	if(isset($vevent["RRULE"]))
#		foreach(["FREQ" => "Type", "COUNT" => "Occurences", "INTERVAL" => "Interval"] as $key => $token)
#			if(isset($vevent["RRULE"][$key]))
#				$data["Meeting"]["reccurence"][0][$token] = $vevent["RRULE"][$key];

	$no_text = true;

	if(isset($data["Body"]))
		foreach($data["Body"] as $body)
			if(isset($body["Type"]))
				if($body["Type"] == 1)
					$no_text = false;

	if($no_text)
		{
		$new_temp_message = [
			"Wann: " . date("d.m.Y H:i", strtotime($vevent["DTSTART"]))
			];

		if(isset($vevent["LOCATION"]))
			$new_temp_message[] = "Wo: " . $vevent["LOCATION"];

		$new_temp_message[] = "*~*~*~*~*~*~*~*~*~*";

		if(isset($vevent["DESCRIPTION"]))
			$new_temp_message[] = $vevent["DESCRIPTION"];

#		if(isset($vevent["SUMMARY"]))
#			$new_temp_message[] = $vevent["SUMMARY"]; # this must be calendar:body:data (text), not calendar:subject, but calendar:body:data (text) from calendar is not available

		$new_temp_message = implode(PHP_EOL, $new_temp_message);

		active_sync_mail_add_container_plain($data, $new_temp_message);
		}

	if(! isset($data["Email"]["From"]))
		$data["Email"]["From"] = $user . "@" . $host;

	list($from_name, $from_mail) = active_sync_mail_parse_address($data["Email"]["From"]);
	list($to_name, $to_mail) = active_sync_mail_parse_address($data["Email"]["To"]);

	########################################################################
	# just check
	# if we are an attendee and have to delete a meeting from calendar or
	# if we are an organizer and have to update an attendee status.
	# nothing else!
	########################################################################

	if(! isset($vcalendar["METHOD"]))
		return(false);

	if($vcalendar["METHOD"] == "CANCEL")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
		$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Canceled";

		if(! isset($vevent["ORGANIZER"][$from_mail]))
			if(isset($vevent["ATTENDEE"][$from_mail]))
				{
				$server_id = active_sync_get_calendar_by_uid($user, $vevent["UID"]);

				$collection_id = active_sync_get_collection_id_by_type($user, 8);

				if($server_id != "")
					unlink(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/". $collection_id . "/" . $server_id . ".data");
				}
		}

	if($vcalendar["METHOD"] == "PUBLISH")
		{
		}

	if($vcalendar["METHOD"] == "REPLY")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
		$data["Email"]["MessageClass"] = "IPM.Notification.Meeting.Resp";

		if(isset($vevent["ORGANIZER"][$from_mail]))
			if(isset($vevent["ATTENDEE"][$from_mail]))
				{
				$server_id = active_sync_get_calendar_by_uid($user, $vevent["UID"]);

				if($server_id != "")
					{
					switch($vevent["ATTENDEE"][$from_mail]["PARTSTAT"])
						{
						case("DECLINED"):
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Neg";

							active_sync_put_attendee_status($user, $server_id, $from_mail, 4);

							break;
						case("ACCEPTED"):
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Pos";

							active_sync_put_attendee_status($user, $server_id, $from_mail, 3);

							break;
						case("TENTATIVE"):
							$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Resp.Tent";

							active_sync_put_attendee_status($user, $server_id, $from_mail, 2);

							break;
						}
					}
				}
		}

	if($vcalendar["METHOD"] == "REQUEST")
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:calendarmessage";
		$data["Email"]["MessageClass"] = "IPM.Notification.Meeting.Request";

		if(! isset($vevent["ORGANIZER"][$from_mail]))
			if(isset($vevent["ATTENDEE"][$from_mail]))
				if($vevent["ATTENDEE"][$from_mail]["PARTSTAT"] == "NEEDS-ACTION")
					$data["Email"]["MessageClass"] = "IPM.Schedule.Meeting.Request";
				else
					$data["Email"]["MessageClass"] = "IPM.Notification.Meeting";
		}

	return(true);
	}

function active_sync_mail_add_container_html(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = [
		"Type" => 2,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		];
	}

function active_sync_mail_add_container_mime(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = [
		"Type" => 4,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		];
	}

function active_sync_mail_add_container_plain(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = [
		"Type" => 1,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		];
	}

function active_sync_mail_add_container_rtf(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = [
		"Type" => 3,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		];
	}

function active_sync_mail_body_smime_decode($mime)
	{
	$file = active_sync_create_guid();

	$mail_struct = active_sync_mail_split($mime);

	$head_parsed = iconv_mime_decode_headers($mail_struct["head"]);

	list($to_name, $to_mail) = active_sync_mail_parse_address($head_parsed["To"]);

	$public = __DIR__ . "/certs/" . $to_mail . ".pem";
	$private = __DIR__ . "/private/" . $to_mail . ".pem";

	if(file_exists($public) && file_exists($private))
		{
		$crt = file_get_contents($public);
		$key = file_get_contents($private);

		file_put_contents("/tmp/" . $file . ".enc", $mime);

		if(! openssl_pkcs7_decrypt("/tmp/" . $file . ".enc", "/tmp/" . $file . ".dec", $crt, [$key, ""]))
			$new_temp_message = $mime;
		elseif(! openssl_pkcs7_verify("/tmp/" . $file . ".dec", PKCS7_NOVERIFY, "/tmp/" . $file . ".ver"))
			$new_temp_message = $mime;
		elseif(! openssl_pkcs7_verify("/tmp/" . $file . ".dec", PKCS7_NOVERIFY, "/tmp/" . $file . ".ver", [], "/tmp/" . $file . ".ver", "/tmp/" . $file . ".dec"))
			$new_temp_message = $mime;
		else
			{
			foreach(["Content-Description", "Content-Disposition", "Content-Transfer-Encoding", "Content-Type", "Received"] as $key)
				unset($head_parsed[$key]);

			$new_temp_message = [];

			foreach($head_parsed as $key => $val)
				$new_temp_message[] = $key . ": " . $val;

			$new_temp_message[] = "";
			$new_temp_message = file_get_contents("/tmp/" . $file . ".dec");

			$new_temp_message = implode(PHP_EOL, $new_temp_message);
			}

		foreach(["dec", "enc", "ver"] as $extension)
			if(file_exists("/tmp/" . $file . "." . $extension))
				unlink("/tmp/" . $file . "." . $extension);
		}
	else
		$new_temp_message = $mime;

	return($new_temp_message);
	}

function active_sync_mail_body_smime_encode($mime) # almost copy of sign
	{
	$file = active_sync_create_guid();

	$mail_struct = active_sync_mail_split($mime);

	$head_parsed = iconv_mime_decode_headers($mail_struct["head"]);

	list($to_name, $to_mail) = active_sync_mail_parse_address($head_parsed["To"]);

	$public = __DIR__ . "/certs/" . $to_mail . ".pem";

	if(file_exists($public))
		{
		$new_temp_message = [
			"Content-Type: " . $head_parsed["Content-Type"],
			"MIME-Version: 1.0",
			"",
			$mail_struct["body"]
			];

		$new_temp_message = implode(PHP_EOL, $new_temp_message);

		file_put_contents("/tmp/" . $file . ".dec", $new_temp_message);

		foreach(["Content-Type", "MIME-Version"] as $key)
			unset($head_parsed[$key]);

		$crt = file_get_contents($public);

		if(openssl_pkcs7_encrypt("/tmp/" . $file . ".dec", "/tmp/" . $file . ".enc", $crt, $head_parsed))
			$new_temp_message = file_get_contents("/tmp/" . $file . ".enc");
		else
			$new_temp_message = $mime;

		foreach(["dec", "enc", "ver"] as $extension)
			if(file_exists("/tmp/" . $file . "." . $extension))
				unlink("/tmp/" . $file . "." . $extension);
		}
	else
		$new_temp_message = $mime;

	return($new_temp_message);
	}

function active_sync_mail_body_smime_sign($mime) # almost copy of encode
	{
	$mail_struct = active_sync_mail_split($mime);

	$head_parsed = iconv_mime_decode_headers($mail_struct["head"]);

	list($from_name, $from_mail) = active_sync_mail_parse_address($head_parsed["From"]);

	$public = __DIR__ . "/certs/" . $to_mail . ".pem";
	$private = __DIR__ . "/private/" . $to_mail . ".pem";

	if(file_exists($public) && file_exists($private))
		{
		$new_temp_message = [
			"Content-Type: " . $head_parsed["Content-Type"],
			"MIME-Version: 1.0",
			"",
			$mail_struct["body"]
			];

		$new_temp_message = implode(PHP_EOL, $new_temp_message);

		$file = active_sync_create_guid();

		file_put_contents("/tmp/" . $file . ".dec", $new_temp_message);

		foreach(["Content-Type", "MIME-Version"] as $key)
			unset($head_parsed[$key]);

		$crt = file_get_contents($public);
		$key = file_get_contents($private);

		if(openssl_pkcs7_sign("/tmp/" . $file . ".dec", "/tmp/" . $file . ".enc", $crt, $key, $head_parsed))
			$new_temp_message = file_get_contents("/tmp/" . $file . ".enc");
		else
			$new_temp_message = $mime;

		foreach(["dec", "enc", "ver"] as $extension)
			if(file_exists("/tmp/" . $file . "." . $extension))
				unlink("/tmp/" . $file . "." . $extension);
		}
	else
		$new_temp_message = $mime;

	return($new_temp_message);
	}

function active_sync_mail_convert_plain_to_html($subject)
	{
	$table = [
#		"\x20" => "&nbsp;", # ...
		"\x0D" => "", # ...
		"\x3C" => "&lt;", # before br
		"\x3E" => "&gt;", # before br
		"\x0A" => "<br>" # after ltgt
		];

	foreach($table as $search => $replace)
		$subject = str_replace($search, $replace, $subject);

	return("<p>" . $subject . "</p>");
	}

function active_sync_mail_convert_html_to_plain($subject)
	{
	$subject = str_replace("<br>", "\x0A", $subject); # before ltgt
	$subject = preg_replace("/<[^>]*>/", "", $subject); # before ltgt
	$subject = str_replace("&lt;", "\x3C", $subject); # after br
	$subject = str_replace("&gt;", "\x3E", $subject); # after br
	$subject = str_replace("&nbsp;", "\x20", $subject); # ...

	return($subject);
	}

function active_sync_mail_header_value_decode($value, $search = "")
	{
	$data = [];

	if(strpos($value, ";") === false)
		$value .= ";";

	list($value, $parameters) = explode(";", $value, 2);

	foreach(str_getcsv($parameters, ";") as $parameter)
		{
		$parameter = trim($parameter);

		if(strpos($parameter, "=") === false)
			$parameter .= "=1";

		list($parameter_key, $parameter_value) = explode("=", $parameter, 2);

		$data[$parameter_key] = active_sync_mail_header_value_trim($parameter_value);
		}

	if(! $search)
		$retval = $value;
	elseif(isset($data[$search]))
		$retval = $data[$search];
	else
		$retval = "";

	return($retval);
	}

function active_sync_mail_header_value_trim($string)
	{
	if(strlen($string) < 2)
		$retval = $string;
	elseif((substr($string, 0, 1) == '(') && (substr($string, 0 - 1) == ')')) # comment
		$retval = substr($string, 1, 0 - 1);
	elseif((substr($string, 0, 1) == '"') && (substr($string, 0 - 1) == '"')) # display-name
		$retval = substr($string, 1, 0 - 1);
	elseif((substr($string, 0, 1) == '<') && (substr($string, 0 - 1) == '>')) # mailbox
		$retval = substr($string, 1, 0 - 1);
	else
		$retval = $string;

	return($retval);
	}

function active_sync_mail_is_forward($subject)
	{
	$table = [
		"da" => array("VS"),		# danish
		"de" => array("WG"),		# german
		"el" => array("ΠΡΘ"),		# greek
		"en" => array("FW", "FWD"),	# english
		"es" => array("RV"),		# spanish
		"fi" => array("VL"),		# finnish
		"fr" => array("TR"),		# french
		"he" => array("הועבר"),		# hebrew
		"is" => array("FS"),		# icelandic
		"it" => array("I"),		# italian
		"nl" => array("Doorst"),	# dutch
		"no" => array("VS"),		# norwegian
		"pl" => array("PD"),		# polish
		"pt" => array("ENC"),		# portuguese
		"ro" => array("Redirecţionat"),	# romanian
		"sv" => array("VB"),		# swedish
		"tr" => array("İLT"),		# turkish
		"zh" => array("转发")		# chinese
		];

	foreach($table as $language => $abbreviations)
		foreach($abbreviations as $abbreviation)
			{
			$abbreviation .= ":";

			if(strtolower(substr($subject, 0, strlen($abbreviation))) == strtolower($abbreviation))
				return(true);
			}

	return(false);
	}

function active_sync_mail_is_reply($subject)
	{
	$table = [
		"da" => array("SV"),		# danish
		"de" => array("AW"),		# german
		"el" => array("ΑΠ", "ΣΧΕΤ"),	# greek
		"en" => array("RE"),		# english
		"es" => array("RE"),		# spanish
		"fi" => array("VS"),		# finnish
		"fr" => array("RE"),		# french
		"he" => array("תגובה"),		# hebrew
		"is" => array("SV"),		# icelandic
		"it" => array("R", "RIF"),	# italian
		"nl" => array("Antw"),		# dutch
		"no" => array("SV"),		# norwegian
		"pl" => array("Odp"),		# polish
		"pt" => array("RES"),		# portuguese
		"ro" => array("RE"),		# romanian
		"sv" => array("SV"),		# swedish
		"tr" => array("YNT"),		# turkish
		"zh" => array("回复")		# chinese
		];

	foreach($table as $language => $abbreviations)
		foreach($abbreviations as $abbreviation)
			{
			$abbreviation .= ":";

			if(strtolower(substr($subject, 0, strlen($abbreviation))) == strtolower($abbreviation))
				return(true);
			}

	return(false);
	}

function active_sync_mail_parse($user, $collection_id, $server_id, $mime)
	{
	$data = [
		"AirSyncBase" => [
			"NativeBodyType" => 4
			]
		];

	active_sync_mail_add_container_mime($data, $mime);

	$mail_struct = active_sync_mail_split($mime);

	$head_parsed = iconv_mime_decode_headers($mail_struct["head"]);

	foreach(["text/plain" => 1, "text/html" => 2, "application/rtf" => 3] as $content_type => $value)
		if(isset($head_parsed["Content-Type"]))
			if($head_parsed["Content-Type"] == $content_type)
				$data["AirSyncBase"]["NativeBodyType"] = $value;

	if(isset($head_parsed["Date"]))
		$data["Email"]["DateReceived"] = date("Y-m-d\TH:i:s.000\Z", strtotime($head_parsed["Date"]));
	else
		$data["Email"]["DateReceived"] = date("Y-m-d\TH:i:s.000\Z");

	if(! isset($data["Email"]["Subject"]))
		$data["Email"]["Subject"] = "...";

	foreach(["ContentClass" => "urn:content-classes:message", "Importance" => 1, "MessageClass" => "IPM.Note", "Read" => 0] as $token => $value)
		$data["Email"][$token] = $value;

	foreach(["low" => 0, "normal" => 1, "high" => 2] as $test => $importance)
		if(isset($head_parsed["Importance"]))
			if($head_parsed["Importance"] == $test)
				$data["Email"]["Importance"] = $importance;

	foreach([5 => 0, 3 => 1, 1 => 2] as $test => $importance)
		if(isset($head_parsed["X-Priority"]))
			if($head_parsed["X-Priority"] == $test)
				$data["Email"]["Importance"] = $importance;

	$translation_table = [
		"Email" => [
			"From" => "From",
			"To" => "To",
			"Cc" => "Cc",
			"Subject" => "Subject",
			"ReplyTo" => "Reply-To"
			],
		"Email2" => [
			"ReceivedAsBcc" => "Bcc",
			"Sender" => "Sender"
			]
		];

	foreach($translation_table as $codepage => $token_translation)
		foreach($token_translation as $token => $field)
			if(isset($head_parsed[$field]))
				if($head_parsed[$field])
					$data[$codepage][$token] = $head_parsed[$field];

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
	list($null, $name, $mailbox, $comment) = ["", "", "", ""];

	if(! strlen($data))
		return(false);
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

	return([$name, $mailbox]);
	}

function active_sync_mail_parse_body($user, $collection_id, $server_id, & $data, $head_parsed, $body)
	{
	$content_transfer_encoding = "";

	if(isset($head_parsed["Content-Transfer-Encoding"]))
		$content_transfer_encoding = active_sync_mail_header_value_decode($head_parsed["Content-Transfer-Encoding"], "");

	$content_disposition = "";

	if(isset($head_parsed["Content-Disposition"]))
		$content_disposition = active_sync_mail_header_value_decode($head_parsed["Content-Disposition"], "");

	$content_type = "";
	$content_type_charset = "";
	$content_type_boundary = "";

	if(isset($head_parsed["Content-Type"]))
		{
		$content_type = active_sync_mail_header_value_decode($head_parsed["Content-Type"], "");
		$content_type_charset = active_sync_mail_header_value_decode($head_parsed["Content-Type"], "charset");
		$content_type_boundary = active_sync_mail_header_value_decode($head_parsed["Content-Type"], "boundary");
		}

	if($content_transfer_encoding == "")
		$body = $body;
	elseif($content_transfer_encoding == "base64")
		$body = base64_decode($body);
	elseif($content_transfer_encoding == "7bit")
		$body = $body;
	elseif($content_transfer_encoding == "8bit")
		$body = $body;
	elseif($content_transfer_encoding == "quoted-printable")
		$body = quoted_printable_decode($body);

	if($content_type == "")
		{
		if(strtoupper($content_type_charset) != "UTF-8")
			$body = utf8_encode($body);

		$body_html = active_sync_mail_convert_plain_to_html($body);
		$body_plain = $body;

		active_sync_mail_add_container_plain($data, $body_plain);
		active_sync_mail_add_container_html($data, $body_html);
		}
	elseif($content_disposition == "attachment")
		active_sync_mail_parse_body_part($user, $collection_id, $server_id, $data, $head_parsed, $body);
	elseif($content_disposition == "inline")
		active_sync_mail_parse_body_part($user, $collection_id, $server_id, $data, $head_parsed, $body);
	elseif(($content_type == "multipart/alternative") || ($content_type == "multipart/mixed") || ($content_type == "multipart/related"))
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
	elseif(($content_type == "application/pkcs7-mime") || ($content_type == "application/x-pkcs7-mime"))
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME";
		}
	elseif(($content_type == "application/pkcs7-signature") || ($content_type == "application/x-pkcs7-signature"))
		{
		$data["Email"]["ContentClass"] = "urn:content-classes:message";
		$data["Email"]["MessageClass"] = "IPM.Note.SMIME.MultipartSigned";
		}
	elseif($content_type == "application/rtf")
		active_sync_mail_add_container_rtf($data, $body);
	elseif(($content_type == "text/calendar") || ($content_type == "text/x-vCalendar"))
		active_sync_mail_add_container_calendar($data, $body, $user);
	elseif($content_type == "text/html")
		{
		if($content_type_charset != "UTF-8")
			$body = utf8_encode($body);

		$body_html = $body;
		$body_plain = active_sync_mail_convert_html_to_plain($body);

		active_sync_mail_add_container_plain($data, $body_plain);
		active_sync_mail_add_container_html($data, $body_html);
		}
	elseif($content_type == "text/plain")
		{
		if($content_type_charset != "UTF-8")
			$body = utf8_encode($body);

		$body_html = active_sync_mail_convert_plain_to_html($body);
		$body_plain = $body;

		active_sync_mail_add_container_plain($data, $body_plain);
		active_sync_mail_add_container_html($data, $body_html);
		}
	else
		active_sync_mail_parse_body_part($user, $collection_id, $server_id, $data, $head_parsed, $body);
	}

function active_sync_mail_parse_body_multipart($body, $boundary)
	{
	$retval = [];

	$index = 0;

	$retval[$index] = "";

	while($body)
		{
		if(strpos($body, PHP_EOL) === false)
			$body .= PHP_EOL;

		list($line, $body) = explode(PHP_EOL, $body, 2);

		$line = str_replace("\r", "", $line);

		if(($line == "--" . $boundary) || ($line == "--" . $boundary . "--"))
			{
			$index ++;

			$retval[$index] = "";

			continue;
			}

		$retval[$index] .= $line . PHP_EOL;
		}

	return($retval);
	}

function active_sync_mail_parse_body_multipart_part($user, $collection_id, $server_id, & $data, $mail)
	{
	$mail_struct = active_sync_mail_split($mail);

	$head_parsed = iconv_mime_decode_headers($mail_struct["head"]);

	active_sync_mail_parse_body($user, $collection_id, $server_id, $data, $head_parsed, $mail_struct["body"]);
	}

function active_sync_mail_parse_body_part($user, $collection_id, $server_id, & $data, $head_parsed, $body)
	{
	$content_description = "";

	if(isset($head_parsed["Content-Description"]))
		$content_description = active_sync_mail_header_value_decode($head_parsed["Content-Description"], "");

	$content_disposition = "";

	if(isset($head_parsed["Content-Disposition"]))
		$content_disposition = active_sync_mail_header_value_decode($head_parsed["Content-Disposition"], "");

	$content_id = "";

	if(isset($head_parsed["Content-ID"]))
		$content_id = active_sync_mail_header_value_trim($head_parsed["Content-ID"]);

	$content_type = "";
	$content_type_name = "";

	if(isset($head_parsed["Content-Type"]))
		{
		$content_type = active_sync_mail_header_value_decode($head_parsed["Content-Type"], "");
		$content_type_name = active_sync_mail_header_value_decode($head_parsed["Content-Type"], "name");
		}

	if($content_type_name == "")
		{
		foreach(range(0, 9) as $i)
			{
			$temp = active_sync_mail_header_value_decode($head_parsed["Content-Type"], "name*" . $i . "*");

			if(substr($temp, 0, 10) == "ISO-8859-1")
				$temp = utf8_encode(urldecode(substr($temp, 12)));

			$content_type_name .= $temp;
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
		}
	elseif(($content_type == "text/plain") || ($content_type == "text/html"))
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

	$data["Attachments"][] = [
		"AirSyncBase" => [
			"ContentId" => $content_id,
			"IsInline" => ($content_disposition == "inline" ? 1 : 0),
			"DisplayName" => ($content_description == "" ? "..." : $content_description),
			"EstimatedDataSize" => strlen($body),
			"FileReference" => $reference,
			"Method" => ($content_disposition == "inline" ? 6 : 1)
			]
		];

	$data["File"][$reference] = [
		"AirSyncBase" => [
			"ContentType" => $content_type
			],
		"ItemOperations" => [
			"Data" => base64_encode($body)
			]
		];
	}

function active_sync_mail_signature_save($data, $body)
	{
	list($name, $mail) = active_sync_mail_parse_address($data["Email"]["From"]);

	$crt = __DIR__ . "/certs/" . $mail . ".pem";

	if(! file_exists($crt))
		{
		$body = base64_encode($body);

		$body = chunk_split($body , 64, PHP_EOL);

		$body = substr($body, 0, 0 - 1);

		$body = ["-----BEGIN PKCS7-----", $body, "-----END PKCS7-----"];

		$body = implode(PHP_EOL, $body);

		file_put_contents($crt, $body);

		exec("openssl pkcs7 -in " . $crt . " -out " . $crt . " -text -print_certs", $output, $return_var);

		$body = file_get_contents($crt);

		list($null, $body) = explode("-----BEGIN CERTIFICATE-----", 2);
		list($body, $null) = explode("-----END CERTIFICATE-----", 2);

		$body = ["-----BEGIN CERTIFICATE-----", $body, "-----END CERTIFICATE-----"];

		$body = implode(PHP_EOL, $body);

		file_put_contents($crt, $body);
		}
	}

function active_sync_mail_split($mail)
	{
	$head = [];

	while($mail)
		{
		if(strpos($mail, PHP_EOL) === false)
			$mail .= PHP_EOL;

		list($line, $mail) = explode(PHP_EOL, $mail, 2);

		$line = str_replace("\r", "", $line);

		if(! strlen($line))
			break;

		$head[] = $line;
		}

	$head = implode(PHP_EOL, $head); # !!! we expect empty line later

	$head = active_sync_mail_unfold($head);

	return(["head" => $head, "body" => $mail]);
	}

function active_sync_mail_unfold($subject)
	{
	$table = [
		"\x0D" => "",
		"\x0A\x09" => "\x20",
		"\x0A\x20" => "\x20"
		];

	foreach($table as $search => $replace)
		$subject = str_replace($search, $replace, $subject);

	return($subject);
	}

function active_sync_put_attendee_status($user, $server_id, $email, $attendee_status)
	{
	$collection_id = active_sync_get_collection_id_by_type($user, 8); # Calendar

	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	if(isset($data["Attendees"]))
		foreach($data["Attendees"] as $id => $attendee)
			{
			if(! isset($attendee["Email"]))
				continue;

			if($attendee["Email"] != $email)
				continue;

			$data["Attendees"][$id]["AttendeeStatus"] = $attendee_status;

			active_sync_put_settings_data($user, $collection_id, $server_id, $data);

			return(true);
			}

	return(false);
	}

function active_sync_put_display_name($user, $server_id, $display_name)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(isset($settings["SyncDat"]))
		foreach($settings["SyncDat"] as $id => $folder)
			{
			if($folder["ServerId"] != $server_id)
				continue;

			$settings["SyncDat"][$id]["DisplayName"] = $display_name;

			active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . ".sync", $settings);

			return(true);
			}

	return(false);
	}

function active_sync_put_parent_id($user, $server_id, $parent_id)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(isset($settings["SyncDat"]))
		foreach($settings["SyncDat"] as $id => $folder)
			{
			if($folder["ServerId"] != $server_id)
				continue;

			$settings["SyncDat"][$id]["ParentId"] = $parent_id;

			active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . ".sync", $settings);

			return(true);
			}

	return(false);
	}

function active_sync_put_settings($file, $data)
	{
#	$data = serialize($data);
	$data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	$retval = file_put_contents($file, $data);

	clearstatcache(); # useful

	return($retval);
	}

function active_sync_put_settings_data($user, $collection_id, $server_id, $data)
	{
	return(active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data", $data));
	}

function active_sync_put_settings_folder_client($user, $device_id, $data)
	{
	return(active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $device_id . ".sync", $data));
	}

function active_sync_put_settings_folder_server($user, $data)
	{
	return(active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . ".sync", $data));
	}

function active_sync_put_settings_sync_client($user, $collection_id, $device_id, $data)
	{
	return(active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $device_id . ".sync", $data));
	}

function active_sync_put_settings_sync_server($user, $collection_id, $data)
	{
	die(__FUNCTION__ . ": nothing to set for collection."); # maybe parent, displayname, class???
	}

function active_sync_put_settings_server($data)
	{
	return(active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/login.data", $data));
	}

function active_sync_send_mail($user, $mime)
	{
	$host = active_sync_get_domain(); # needed for user@host

#	$mime = active_sync_mail_body_smime_sign($mime);
#	$mime = active_sync_mail_body_smime_encode($mime);

	$mail_struct = active_sync_mail_split($mime); # head, body

	$head_parsed = iconv_mime_decode_headers($mail_struct["head"]);

	$additional_headers = [];

	foreach($head_parsed as $key => $val)
		{
		if(($key == "Received") || ($key == "Subject") || ($key == "To"))
			continue;

		$additional_headers[] = implode(": ", [$key, $val]);
		}

	# don't we need a recipient here? by settting to null we got an empty field.

	mail($head_parsed["To"], (isset($head_parsed["Subject"]) ? $head_parsed["Subject"] : ""), $mail_struct["body"], implode(PHP_EOL, $additional_headers), "-f no-reply@" . $host);
	}

function active_sync_send_sms($user, $mime)
	{
/*
	$data = [
		"AirSync" => [
			"Class" => "SMS"
			],
		"Email" => [
			"DateReceived" => date("Y-m-d\TH:i:s.000\Z"),
			"Read" => 1,
			"From" => "[MOBILE: " . $number . "]",
			"To" => "[MOBILE: " . $number . "]"
			],
		"Body" => [
				[
				"Type" => 1,
				"EstimatedDataSize" => strlen($text),
				"Data" => $text
				]
			]
		];

	$server_id = active_sync_create_guid();
	$collection_id = active_sync_get_collection_id_by_type($user, 6);

	active_sync_put_settings_data($user, $collection_id, $server_id, $data);
*/
	}

function active_sync_systemtime_decode($expression)
	{
	$retval = unpack("SYear/SMonth/SDayOfWeek/SDay/SHour/SMinute/SSecond/SMilliseconds", $expression);

	return($retval);
	}

function active_sync_systemtime_encode($Year, $Month, $DayOfWeek, $Day, $Hour, $Minute, $Second, $Milliseconds)
	{
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

function active_sync_vcalendar_unfold($subject)
	{
	$table = [
		"\x0D" => "",
		"\x0A\x09" => "",
		"\x0A\x20" => ""
		];

	foreach($table as $search => $replace)
		$subject = str_replace($search, $replace, $subject);

	return($subject);
	}

function active_sync_vcalendar_parse(& $data)
	{
	$retval = [];

	$data = active_sync_vcalendar_unfold($data);

	while($data)
		{
		if(strpos($data, PHP_EOL) === false)
			$data .= PHP_EOL;

		list($line, $data) = explode(PHP_EOL, $data, 2);

		if(! strlen($line))
			continue;

		if(strpos($line, ":") === false)
			continue;

		list($key, $value) = explode(":", $line, 2);

		if(strpos($key, ";") === false)
			$key .= ";";

		list($key, $key_parameters) = explode(";", $key, 2);

		if($key == "BEGIN")
			$retval[$value] = active_sync_vcalendar_parse($data);
		elseif($key == "END")
			break;
		elseif($key == "ATTENDEE" || $key == "ORGANIZER")
			{
			list($proto, $email) = explode(":", $value, 2);

			$retval[$key][$email] = active_sync_vcard_parameter($key_parameters);
			}
		elseif($key == "RRULE")
			foreach(explode(";", $value) as $parameter)
				{
				list($parameter_key, $parameter_value) = explode("=", $parameter, 2);

				$retval[$key][$parameter_key] = $parameter_value;
				}
		elseif($key == "CATEGORIES")
			$value = explode("\,", $value);
		else
			$retval[$key] = $value;
		}

	return($retval);
	}

function active_sync_vcard_parameter($parameters)
	{
	$retval = [];

	foreach(str_getcsv($parameters, ";") as $parameter)
		{
		$parameter = trim($parameter);

		if(strpos($parameter, "=") === false)
			$parameter .= "=1";

		list($key, $value) = explode("=", $parameter, 2);

		$retval[$key] = $value;
		}

	return($retval);
	}

function active_sync_vcard_escape($subject)
	{
	$table = [
		"\x3B" => "\\;", # must be replaced first
		"\x2C" => "\\,",
		"\x0D" => "",
		"\x0A" => "\\n",
		"\x09" => "\\t"
		];

	foreach($table as $search => $replace)
		$subject = str_replace($search, $replace, $subject);

	return($subject);
	}

function active_sync_vcard_from_data($user, $collection_id, $server_id, $version = 21)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	if(! in_array($version, [21, 30, 40]))
		return("");

	$retval = [
		sprintf("BEGIN:VCARD"),
		sprintf("VERSION:%s", number_format($version / 10, 1, ".", "")),
		sprintf("REV:%s", date("Y-m-d\TH:i:s\Z", filemtime(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data"))),
		sprintf("UID:%s", $server_id)
		];

	$fields = [
		"FileAs" => "FN",
		"Email1Address" => "EMAIL",
		"Email2Address" => "EMAIL",
		"Email3Address" => "EMAIL",
		"JobTitle" => "ROLE",
		"WebPage" => "URL",
		"Birthday" => "BDAY",
		"ManagerName" => "MANAGER",
		"Spouse" => "SPOUSE",
		"AssistantName" => "ASSISTANT",
		"Anniversary" => "ANNIVERSARY"
		];

	foreach($fields as $token => $key)
		if(isset($data["Contacts"][$token]))
			$retval[] = sprintf("%s:%s", $key, $data["Contacts"][$token]);

	if($version == 21)
		$fields = [
			"BusinessFaxNumber" => "TEL;WORK;FAX",
			"HomeFaxNumber" => "TEL;HOME;FAX",
			"MobilePhoneNumber" => "TEL;CELL",
			"PagerNumber" => "TEL;PAGER",
			"HomePhoneNumber" => "TEL;HOME",
			"BusinessPhoneNumber" => "TEL;WORK",
			"CarPhoneNumber" => "TEL;CAR"
			];

	if($version == 30)
		$fields = [
			"BusinessFaxNumber" => "TEL;TYPE=WORK,FAX",
			"HomeFaxNumber" => "TEL;TYPE=HOME,FAX",
			"MobilePhoneNumber" => "TEL;TYPE=CELL",
			"PagerNumber" => "TEL;TYPE=PAGER",
			"HomePhoneNumber" => "TEL;TYPE=HOME,VOICE",
			"BusinessPhoneNumber" => "TEL;TYPE=WORK,VOICE",
			"CarPhoneNumber" => "TEL;TYPE=CAR"
			];

	if($version == 40)
		$fields = [
			"BusinessFaxNumber" => "TEL;TYPE=work,fax",
			"HomeFaxNumber" => "TEL;TYPE=home,fax",
			"MobilePhoneNumber" => "TEL;TYPE=cell",
			"PagerNumber" => "TEL;TYPE=pager",
			"HomePhoneNumber" => "TEL;TYPE=home,voice",
			"BusinessPhoneNumber" => "TEL;TYPE=work,voice",
			"CarPhoneNumber" => "TEL;TYPE=car"
			];

	foreach($fields as $token => $key)
		if(isset($data["Contacts"][$token]))
			$retval[] = sprintf("%s:%s", $key, $data["Contacts"][$token]);

	$x = [];

	foreach(["CompanyName", "Department", "OfficeLocation"] as $token)
		$x[] = (isset($data["Contacts"][$token]) ? active_sync_vcard_escape($data["Contacts"][$token]) : "");

	if(implode("", $x))
		$retval[] = sprintf("ORG:%s", implode(";", $x));

	$x = [];

	foreach(["LastName", "FirstName", "MiddleName", "Title", "Suffix"] as $token)
		$x[] = (isset($data["Contacts"][$token]) ? active_sync_vcard_escape($data["Contacts"][$token]) : "");

	if(implode("", $x))
		$retval[] = sprintf("N:%s", implode(";", $x));

	if(isset($data["Contacts2"]["NickName"]))
		$retval[] = sprintf("NICKNAME:%s", $data["Contacts2"]["NickName"]);

	foreach(["Business" => "WORK", "Home" => "HOME", "Other" => "OTHER"] as $token_prefix => $type)
		{
		$x = ["", ""];

		foreach(["Street", "City", "State", "PostalCode", "Country"] as $token_suffix)
			$x[] = (isset($data["Contacts"][$token_prefix . "Address" . $token_suffix]) ? active_sync_vcard_escape($data["Contacts"][$token_prefix . "Address" . $token_suffix]) : "");

		if(! implode("", $x))
			continue;

		if($version == 21)
			$retval[] = sprintf("ADR;%s:%s", strtoupper($type), implode(";", $x));

		if($version == 30)
			$retval[] = sprintf("ADR;TYPE=%s:%s", strtoupper($type), implode(";", $x));

		if($version == 40)
			$retval[] = sprintf("ADR;TYPE=%s:%s", strtolower($type), implode(";", $x));
		}

	if(isset($data["Body"]))
		foreach($data["Body"] as $body)
			if(isset($body["Type"]))
				if($body["Type"] == 1) # Text
					$retval[] = sprintf("NOTE:%s", active_sync_vcard_escape($body["Data"]));

	if(isset($data["Categories"]))
		{
		$x = [];

		foreach($data["Categories"] as $category)
			$x[] = active_sync_vcard_escape($category);

		if(implode("", $x))
			$retval[] = sprintf("CATEGORIES:%s", implode(",", $x));
		}

	foreach(["IMAddress", "IMAddress2", "IMAddress3"] as $token)
		{
		if(! isset($data["Contacts2"][$token]))
			continue;

		if(strpos($data["Contacts2"][$token], ":") === false)
			continue;

		list($proto, $address) = explode(":", $data["Contacts2"][$token], 2);

		$retval[] = sprintf("X-%s:%s", strtoupper($proto), $address);
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
			$retval[] = sprintf("PHOTO;%s;ENCODING=BASE64:%s", strtoupper($format), $data["Contacts"]["Picture"]);

		if($version == 30)
			$retval[] = sprintf("PHOTO;TYPE=%s;ENCODING=B:%s", strtoupper($format), $data["Contacts"]["Picture"]);

		if($version == 40)
			$retval[] = sprintf("PHOTO:data:image/%s;BASE64%s", strtolower($format), $data["Contacts"]["Picture"]);
		}

	$retval[] = sprintf("SOURCE:http://%s%s", $_SERVER["HTTP_HOST"], $_SERVER["REQUEST_URI"]);
	$retval[] = sprintf("END:VCARD");

	foreach($retval as $id => $line)
		{
		$retval[$id] = chunk_split($retval[$id], 74, "\n ");
		$retval[$id] = substr($retval[$id], 0, 0 - 2);
		}

	return(implode(PHP_EOL, $retval));
	}

define("WBXML_SWITCH", 0x00);
define("WBXML_END", 0x01);
define("WBXML_ENTITY", 0x02);
define("WBXML_STR_I", 0x03);
define("WBXML_LITERAL", 0x04);
define("WBXML_EXT_I_0", 0x40);
define("WBXML_EXT_I_1", 0x41);
define("WBXML_EXT_I_2", 0x42);
define("WBXML_PI", 0x43);
define("WBXML_LITERAL_C", 0x44);
define("WBXML_EXT_T_0", 0x80);
define("WBXML_EXT_T_1", 0x81);
define("WBXML_EXT_T_2", 0x82);
define("WBXML_STR_T", 0x83);
define("WBXML_LITERAL_A", 0x84);
define("WBXML_EXT_0", 0xC0);
define("WBXML_EXT_1", 0xC1);
define("WBXML_EXT_2", 0xC2);
define("WBXML_OPAQUE", 0xC3);
define("WBXML_LITERAL_AC", 0xC4);

define("WBXML_TERMSTR", 0x00);

function active_sync_wbxml_get_charset_by_name($expression)
	{
#	$data = file_get_contents("character-sets.xml");

#	$xml = simplexml_load_string($data);

#	foreach($xml->registry->record as $record)
#		if($record->name == $expression);
#			return($record->value);

	$table = active_sync_wbxml_table_charset();

	foreach($table as $id => $name)
		if($name == $expression)
			return($id);

	return(false);
	}

function active_sync_wbxml_get_charset_by_id($id)
	{
#	$data = file_get_contents("character-sets.xml");

#	$xml = simplexml_load_string($data);

#	foreach($xml->registry->record as $record)
#		if($record->value == $expression);
#			return($record->name);

	$table = active_sync_wbxml_table_charset();

	return(isset($table[$id]) ? $table[$id] : false);
	}

function active_sync_wbxml_get_codepage_by_namespace($expression)
	{
	$table = active_sync_wbxml_table_namespace();

	foreach($table as $id => $data)
		if($data["namespaceURI"] == $expression)
			return($id);

	return(false);
	}

function active_sync_wbxml_get_codepage_by_prefix($expression)
	{
	$table = active_sync_wbxml_table_namespace();

	foreach($table as $id => $data)
		if($data["prefix"] == $expression)
			return($id);

	return(false);
	}

function active_sync_wbxml_get_codepage_namespace_by_id($id)
	{
	$table = active_sync_wbxml_table_namespace();

	return(isset($table[$id]["namespaceURI"]) ? $table[$id]["namespaceURI"] : false);
	}

function active_sync_wbxml_get_codepage_prefix_by_id($id)
	{
	$table = active_sync_wbxml_table_namespace();

	return(isset($table[$id]["prefix"]) ? $table[$id]["prefix"] : false);
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

	  	$multi_byte |= ($byte & 0x7F);

	  	if(($byte & 0x80) != 0x80)
			break;

		$multi_byte <<= 7;
		}

	return($multi_byte);
	}

function active_sync_wbxml_get_public_identifier_by_name($expression)
	{
	$table = active_sync_wbxml_table_public_identifier();

	foreach($table as $id => $name)
		if($name == $expression)
			return($id);

	return(false);
	}

function active_sync_wbxml_get_public_identifier_by_id($id)
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

		$string .= $char;
		}

	return($string);
	}

function active_sync_wbxml_get_string_length($input, & $position = 0, $length = 0)
	{
	$string = substr($input, $position, $length);

	$position += $length;

	return($string);
	}

function active_sync_wbxml_get_token_by_name($codepage, $expression)
	{
	if(! is_numeric($codepage))
		$codepage = active_sync_wbxml_get_codepage_by_namespace($codepage);

	$table = active_sync_wbxml_table_token();

	if(isset($table[$codepage]))
		foreach($table[$codepage] as $id => $name)
			if($name == $expression)
				return($id);

	return(false);
	}

function active_sync_wbxml_get_token_by_id($codepage, $id)
	{
	if(! is_numeric($codepage))
		$codepage = active_sync_wbxml_get_codepage_by_namespace($codepage);

	$table = active_sync_wbxml_table_token();

	return(isset($table[$codepage][$id & 0x3F]) ? $table[$codepage][$id & 0x3F] : false);
	}

function active_sync_wbxml_pretty($expression)
	{
	if(! strlen($expression))
		return("");

	$expression = simplexml_load_string($expression, "SimpleXMLElement", LIBXML_NOBLANKS | LIBXML_NOWARNING);

	if(isset($expression->Response->Fetch->Properties->Data))
		$expression->Response->Fetch->Properties->Data = "[PRIVATE DATA]";

	if(isset($expression->Response->Store->Result->Properties->Picture->Data))
		$expression->Response->Store->Result->Properties->Picture->Data = "[PRIVATE DATA]";

	if(isset($expression->Collections->Collection))
		foreach($expression->Collections->Collection as $collection)
			foreach(["Add", "Change"] as $action)
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

	return($expression);
	}

function active_sync_wbxml_request_a($input, & $position = 0, $codepage = 0, $level = 0)
	{
	$buffer = [];

	if(! strlen($input))
		return(implode(PHP_EOL, $buffer));

	if($position == 0)
		{
		$version = active_sync_wbxml_get_integer($input, $position);
		$public_identifier = active_sync_wbxml_get_multibyte_integer($input, $position);
		$charset = active_sync_wbxml_get_multibyte_integer($input, $position);
		$string_table_length = active_sync_wbxml_get_multibyte_integer($input, $position);

		$public_identifier = active_sync_wbxml_get_public_identifier_by_id($public_identifier);
		$charset = active_sync_wbxml_get_charset_by_id($charset);

		@ mb_internal_encoding($charset);

		$string_table = "";

		while(strlen($string_table) < $string_table_length)
			$string_table .= $input[$position ++];

		$buffer[] = sprintf('<?xml version="1.0" encoding="%s"?>', $charset);
		}

	$tabs = str_repeat("\t", $level);

	while($position < strlen($input))
		{
		$token = ord($input[$position ++]);

		switch($token)
			{
			case(0x00):
				$data = active_sync_wbxml_get_integer($input, $position);

				$buffer[] = sprintf("%s<!-- SWITCH 0x%02X %s -->", $tabs, $data, active_sync_wbxml_get_codepage_namespace_by_id($data));
				$buffer[] = active_sync_wbxml_request_a($input, $position, $data, $level);

				$position --; # huuuh ... mysterious ... my secret

				break;
			case(0x01):
				return(implode(PHP_EOL, $buffer));
			case(0x03):
				$data = active_sync_wbxml_get_string($input, $position);

				$buffer[] = sprintf("%s<![CDATA[%s]]>", $tabs, $data);

				break;
			case(0xC3):
				$data = active_sync_wbxml_get_multibyte_integer($input, $position);

				$data = active_sync_wbxml_get_string_length($input, $position, $data);

				$buffer[] = sprintf("%s<![CDATA[%s]]>", $tabs, $data);

				break;
			case(0x02):
			case(0x04):
			case(0x40):
			case(0x41):
			case(0x42):
			case(0x43):
			case(0x44):
			case(0x80):
			case(0x81):
			case(0x82):
			case(0x83):
			case(0x84):
			case(0xC0):
			case(0xC1):
			case(0xC2):
			case(0xC4):
				break;
			default:
				$data = active_sync_wbxml_get_token_by_id($codepage, $token);

				if($token & 0x40)
					{
					$buffer[] = sprintf("%s<%s>", $tabs, $data);

					$level ++;
					$buffer[] = active_sync_wbxml_request_a($input, $position, $codepage, $level);
					$level --;

					$buffer[] = sprintf("%s</%s>", $tabs, $data);
					}
				else
					$buffer[] = sprintf("%s<%s />", $tabs, $data);

				break;
			}

		if($level == 0)
			break;
		}

	return(implode(PHP_EOL, $buffer));
	}

function active_sync_wbxml_request_b($input, $baseURI = "AirSync")
	{
	$position = 0;
	$namespaceURI = $baseURI;
	$pageindex = 0; # based on $baseURI

	if(! strlen($input))
		return("");

	$version = active_sync_wbxml_get_integer($input, $position);
	$public_identifier = active_sync_wbxml_get_multibyte_integer($input, $position);
	$charset = active_sync_wbxml_get_multibyte_integer($input, $position);
	$string_table_length = active_sync_wbxml_get_multibyte_integer($input, $position);

	$publicId = active_sync_wbxml_get_public_identifier_by_id($public_identifier);
	$encoding = active_sync_wbxml_get_charset_by_id($charset);

	$xml = new DOMDocument();

	$xml->encoding = $encoding;
	$xml->formatOutput = true;
	$xml->preserveWhiteSpace = false;
	$xml->version = "1.0";

	$imp = new DOMImplementation();

	$doctype = $imp->createDocumentType("AirSync", $publicId, "http://www.microsoft.com/");

#	$xml->appendChild($doctype);

	@ mb_internal_encoding($charset);

	$string_table = "";

	while(strlen($string_table) < $string_table_length)
		$string_table .= $input[$position ++];

	while($position < strlen($input))
		{
		$token = ord($input[$position ++]);

		switch($token)
			{
			case(0x00): # 5.8.4.7.2. Code Page Switch Token
				$pageindex = active_sync_wbxml_get_integer($input, $position);

				$namespaceURI = active_sync_wbxml_get_codepage_namespace_by_id($pageindex);
				$prefix = "xmlns:" . active_sync_wbxml_get_codepage_prefix_by_id($pageindex);

				# if root exist, apply new namespace there
				if(isset($root))
					if($namespaceURI != $baseURI)
						$root->setAttributeNS("http://www.w3.org/2000/xmlns/", $prefix, $namespaceURI);

				break;
			case(0x01): # 5.8.4.7.1. END Token
				$child = $child->parentNode;

				break;
			case(0x03): # 5.8.4.1 Strings
				$content = active_sync_wbxml_get_string($input, $position);

				$newnode = $xml->createTextNode($content);

				$child->appendChild($newnode);

				break;
			case(0x83): # 5.8.4.1 Strings
				$tableref = active_sync_wbxml_get_multibyte_integer($input, $position);

				$content = active_sync_wbxml_get_string($string_table, $tableref);

				$newnode = $xml->createTextNode($content);

				$child->appendChild($newnode);

				break;
			case(0xC3): # 5.8.4.6. Opaque Data
				$length = active_sync_wbxml_get_multibyte_integer($input, $position);

				$data = active_sync_wbxml_get_string_length($input, $position, $length);

				$newnode = $xml->createCDATASection($data);

				$child->appendChild($newnode);

				break;
			case(0x02): # 5.8.4.3. Character Entity
			case(0x04): # 5.8.4.5. Literal Tag or Attribute Name
			case(0x40): # 5.8.4.2. Global Extension Tokens
			case(0x41): # 5.8.4.2. Global Extension Tokens
			case(0x42): # 5.8.4.2. Global Extension Tokens
			case(0x43): # 5.8.4.4. Processing Instruction
			case(0x44): # 5.8.4.5. Literal Tag or Attribute Name
			case(0x80): # 5.8.4.2. Global Extension Tokens
			case(0x81): # 5.8.4.2. Global Extension Tokens
			case(0x82): # 5.8.4.2. Global Extension Tokens
			case(0x84): # 5.8.4.5. Literal Tag or Attribute Name
			case(0xC0): # 5.8.4.2. Global Extension Tokens
			case(0xC1): # 5.8.4.2. Global Extension Tokens
			case(0xC2): # 5.8.4.2. Global Extension Tokens
			case(0xC4): # 5.8.4.5. Literal Tag or Attribute Name
				die("not implemented");
			default:
				$qualifiedName = active_sync_wbxml_get_token_by_id($pageindex, $token);

				# if there is no root, a new namespace will be applied wherever we are.
				$newnode = $xml->createElementNS($namespaceURI, $qualifiedName);

				if(isset($root))
					if($token & 0x40)
						$child = $child->appendChild($newnode);
					else
						$child->appendChild($newnode);
				else
					$root = $child = $xml->appendChild($newnode);

				break;
			}
		}

	return($xml->saveXML());
	}

class active_sync_wbxml_response
	{
	var $response = "\x03\x01\x6A\x00";
	var $codepage = 0xFF;

	function x_close($token = "")
		{
		$this->response .= chr(0x01);
		}

	function x_init()
		{
		$this->codepage = 0x00;
		$this->response = "\x03\x01\x6A\x00";
		}

	function x_print_multibyte_integer($integer)
		{
		$retval = "";
		$remain = 0x00;

		do
			{
			$retval = chr(($integer & 0x7F) | ($remain > 0x7F ? 0x80 : 0x00)) . $retval;

			$remain = $integer;

			$integer >>= 7;
			}
		while($integer > 0x00);

		$this->response .= $retval;
		}

	function x_open($token, $contains_data = true, $has_attribute = false)
		{
		$data = active_sync_wbxml_get_token_by_name($this->codepage, $token);

		if($has_attribute)
			$data |= 0x80;

		if($contains_data)
			$data |= 0x40;

		$this->response .= chr($data);
		}

	function x_print($string)
		{
		if(strpos($string, "\x00") === false)
			$this->response .= "\x03" . $string . "\x00";
		else
			$this->x_print_bin($string);
		}

	function x_print_bin($string)
		{
		$this->response .= chr(0xC3);

		$length = strlen($string);

		$this->x_print_multibyte_integer($length);

		$this->response .= $string;
		}

	function x_switch($codepage)
		{
		if(! is_numeric($codepage))
			$codepage = active_sync_wbxml_get_codepage_by_namespace($codepage);

		if($this->codepage == $codepage)
			return;

		$this->codepage = $codepage;
		$this->response .= "\x00" . chr($codepage);
		}
	}

function active_sync_wbxml_table_charset()
	{
	# https://www.iana.org/assignments/character-sets/character-sets.xml

	$retval = [
		3 => "US-ASCII",
		4 => "ISO-8859-1",
		5 => "ISO-8859-2",
		6 => "ISO-8859-3",
		7 => "ISO-8859-4",
		8 => "ISO-8859-5",
		9 => "ISO-8859-6",
		10 => "ISO-8859-7",
		11 => "ISO-8859-8",
		12 => "ISO-8859-9",
		13 => "ISO-8859-10",
		106 => "UTF-8",
		109 => "ISO-8859-13",
		110 => "ISO-8859-14",
		111 => "ISO-8859-15",
		112 => "ISO-8859-16",
		113 => "GBK",
		114 => "GB18030",
		115 => "OSD_EBCDIC_DF04_15",
		116 => "OSD_EBCDIC_DF03_IRV",
		117 => "OSD_EBCDIC_DF04_1",
		118 => "ISO-11548-1",
		119 => "KZ-1048",
		1000 => "ISO-10646-UCS-2",
		1001 => "ISO-10646-UCS-4",
		1012 => "UTF-7",
		1013 => "UTF-16BE",
		1014 => "UTF-16LE",
		1015 => "UTF-16",
		1016 => "CESU-8",
		1017 => "UTF-32",
		1018 => "UTF-32BE",
		1019 => "UTF-32LE",
		1020 => "BOCU-1",
		2008 => "DEC-MCS",
		2009 => "IBM850",
		2010 => "IBM852",
		2011 => "IBM437",
		2013 => "IBM862",
		2025 => "GB2312",
		2026 => "BIG5",
		2028 => "IBM037",
		2029 => "IBM038",
		2030 => "IBM273",
		2031 => "IBM274",
		2032 => "IBM275",
		2033 => "IBM277",
		2034 => "IBM278",
		2035 => "IBM280",
		2036 => "IBM281",
		2037 => "IBM284",
		2038 => "IBM285",
		2039 => "IBM290",
		2040 => "IBM297",
		2041 => "IBM420",
		2042 => "IBM423",
		2043 => "IBM424",
		2044 => "IBM500",
		2045 => "IBM851",
		2046 => "IBM855",
		2047 => "IBM857",
		2048 => "IBM860",
		2049 => "IBM861",
		2050 => "IBM863",
		2051 => "IBM864",
		2052 => "IBM865",
		2053 => "IBM868",
		2054 => "IBM869",
		2055 => "IBM870",
		2056 => "IBM871",
		2057 => "IBM880",
		2058 => "IBM891",
		2059 => "IBM903",
		2060 => "IBM904",
		2061 => "IBM905",
		2062 => "IBM918",
		2063 => "IBM1026",
		2064 => "EBCDIC-AT-DE",
		2065 => "EBCDIC-AT-DE-A",
		2066 => "EBCDIC-CA-FR",
		2067 => "EBCDIC-DK-NO",
		2068 => "EBCDIC-DK-NO-A",
		2069 => "EBCDIC-FI-SE",
		2070 => "EBCDIC-FI-SE-A",
		2071 => "EBCDIC-FR",
		2072 => "EBCDIC-IT",
		2073 => "EBCDIC-PT",
		2074 => "EBCDIC-ES",
		2075 => "EBCDIC-ES-A",
		2076 => "EBCDIC-ES-S",
		2077 => "EBCDIC-UK",
		2078 => "EBCDIC-US",
		2079 => "UNKNOWN-8BIT",
		2080 => "MNEMONIC",
		2081 => "MNEM",
		2082 => "VISCII",
		2083 => "VIQR",
		2084 => "KOI8-R",
		2085 => "HZ-GB-2312",
		2086 => "IBM866",
		2087 => "IBM775",
		2087 => "KOI8-U",
		2089 => "IBM00858",
		2090 => "IBM00924",
		2091 => "IBM01140",
		2092 => "IBM01141",
		2093 => "IBM01142",
		2094 => "IBM01143",
		2095 => "IBM01144",
		2096 => "IBM01145",
		2097 => "IBM01146",
		2098 => "IBM01147",
		2099 => "IBM01148",
		2100 => "IBM01149",
		2101 => "BIG5-HKSCS",
		2102 => "IBM1047",
		2103 => "PTCP154",
		2104 => "AMIGA-1251",
		2259 => "TIS-620",
		2260 => "CP50220",
		];

	return($retval);
	}

function active_sync_wbxml_table_namespace()
	{
	$retval = [
		0 => array
			(
			"prefix" => "airsync",
			"namespaceURI" => "AirSync"
			),
		1 => array
			(
			"prefix" => "contacts",
			"namespaceURI" => "Contacts"
			),
		2 => array
			(
			"prefix" => "email",
			"namespaceURI" => "Email"
			),
		3 => array
			(
			"prefix" => "airnotify",
			"namespaceURI" => "AirNotify"
			),
		4 => array
			(
			"prefix" => "calendar",
			"namespaceURI" => "Calendar"
			),
		5 => array
			(
			"prefix" => "move",
			"namespaceURI" => "Move"
			),
		6 => array
			(
			"prefix" => "itemestimate",
			"namespaceURI" => "ItemEstimate"
			),
		7 => array
			(
			"prefix" => "folderhierarchy",
			"namespaceURI" => "FolderHierarchy"
			),
		8 => array
			(
			"prefix" => "meetingresponse",
			"namespaceURI" => "MeetingResponse"
			),
		9 => array
			(
			"prefix" => "tasks",
			"namespaceURI" => "Tasks"
			),
		10 => array
			(
			"prefix" => "resolverecipients",
			"namespaceURI" => "ResolveRecipients"
			),
		11 => array
			(
			"prefix" => "validatecerts",
			"namespaceURI" => "ValidateCerts"
			),
		12 => array
			(
			"prefix" => "contacts2",
			"namespaceURI" => "Contacts2"
			),
		13 => array
			(
			"prefix" => "ping",
			"namespaceURI" => "Ping"
			),
		14 => array
			(
			"prefix" => "provision",
			"namespaceURI" => "Provision"
			),
		15 => array
			(
			"prefix" => "search",
			"namespaceURI" => "Search"
			),
		16 => array
			(
			"prefix" => "gal",
			"namespaceURI" => "GAL"
			),
		17 => array
			(
			"prefix" => "airsyncbase",
			"namespaceURI" => "AirSyncBase"
			),
		18 => array
			(
			"prefix" => "settings",
			"namespaceURI" => "Settings"
			),
		19 => array
			(
			"prefix" => "documentlibrary",
			"namespaceURI" => "DocumentLibrary"
			),
		20 => array
			(
			"prefix" => "itemoperations",
			"namespaceURI" => "ItemOperations"
			),
		21 => array
			(
			"prefix" => "composemail",
			"namespaceURI" => "ComposeMail"
			),
		22 => array
			(
			"prefix" => "email2",
			"namespaceURI" => "Email2"
			),
		23 => array
			(
			"prefix" => "notes",
			"namespaceURI" => "Notes"
			),
		24 => array
			(
			"prefix" => "rm",
			"namespaceURI" => "RightsManagement"
			),
		25 => array
			(
			"prefix" => "find",
			"namespaceURI" => "Find"
			)
		];

	return($retval);
	}

function active_sync_wbxml_table_public_identifier()
	{
	$retval = [
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
		];

	return($retval);
	}

function active_sync_wbxml_table_token()
	{
	# AirSync
	$_0 = array
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

	# Contacts
	$_1 = array
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

	# Email
	$_2 = array
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

	# AirNotify
	$_3 = array
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

	# Calendar
	$_4 = array
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

	# Move
	$_5 = array
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

	# GetItemEstimate
	$_6 = array
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

	# FolderHierarchy
	$_7 = array
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

	# MeetingResponse
	$_8 = array
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

	# Tasks
	$_9 = array
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

	# ResolveRecipients
	$_10 = array
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

	# ValidateCerts
	$_11 = array
		(
		0x05 => "ValidateCert",
		0x06 => "Certificates",
		0x07 => "Certificate",
		0x08 => "CertificateChain",
		0x09 => "CheckCRL",
		0x0A => "Status"
		);

	# Contacts2
	$_12 = array
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

	# Ping
	$_13 = array
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

	# Provision
	$_14 = array
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

	# Search
	$_15 = array
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

	# GAL
	$_16 = array
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

	# AirSyncBase
	$_17 = array
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

	# Settings
	$_18 = array
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

	# DocumentLibrary
	$_19 = array
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

	# ItemOperations
	$_20 = array
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

	# ComposeMail
	$_21 = array
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

	# Email2
	$_22 = array
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

	# Notes
	$_23 = array
		(
		0x05 => "Subject",
		0x06 => "MessageClass",
		0x07 => "LastModifiedDate",
		0x08 => "Categories",
		0x09 => "Category"
		);

	# RightsManagement
	$_24 = array
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

	# Find
	$_25 = array
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
		0x19 => "GalSearchCriterion",
		0x20 => "MaxPictures",
		0x21 => "MaxSize",
		0x22 => "Picture"
		);

	$retval = array
		(
		0x00 => $_0,
		0x01 => $_1,
		0x02 => $_2,
		0x04 => $_4,
		0x05 => $_5,
		0x06 => $_6,
		0x07 => $_7,
		0x08 => $_8,
		0x09 => $_9,
		0x0A => $_10,
		0x0B => $_11,
		0x0C => $_12,
		0x0D => $_13,
		0x0E => $_14,
		0x0F => $_15,
		0x10 => $_16,
		0x11 => $_17,
		0x12 => $_18,
		0x13 => $_19,
		0x14 => $_20,
		0x15 => $_21,
		0x16 => $_22,
		0x17 => $_23,
		0x18 => $_24,
		0x19 => $_25
		);

	return($retval);
	}
?>
