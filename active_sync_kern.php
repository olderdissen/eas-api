<?php
################################################################################
# copyright 2008 - 2019 by Markus Olderdissen
# free for private use or inspiration. 
# public use need written permission.
################################################################################

define("ACTIVE_SYNC_DAT_DIR", __DIR__ . "/data");
define("ACTIVE_SYNC_LOG_DIR", __DIR__ . "/logs");
define("ACTIVE_SYNC_WEB_DIR", __DIR__ . "/web");

define("ACTIVE_SYNC_DEBUG_HEADERS", false);

define("ACTIVE_SYNC_FILTER_ALL", 0);
define("ACTIVE_SYNC_FILTER_INCOMPLETE", 8);

define("ACTIVE_SYNC_SLEEP", 5);
define("ACTIVE_SYNC_PING_MAX_FOLDERS", 300);

################################################################################

# PHP 5 >= 5.1.0, PHP 7

date_default_timezone_set("UTC"); # all stored datetime uses utc zone !!!

libxml_use_internal_errors(true);
libxml_disable_entity_loader(false);

setlocale(LC_ALL, "de_DE.UTF-8");

ini_set("display_errors", "On");
ini_set("error_reporting", E_ALL);
ini_set("log_errors", "On");
ini_set("max_execution_time", 30);

if(defined("ACTIVE_SYNC_LOG_DIR"))
	ini_set("error_log", ACTIVE_SYNC_LOG_DIR . "/error.system.log");

# find . -type d -exec chmod 0775 {} \;
# find . -type f -exec chmod 0664 {} \;
# find . -type d -exec chown www-data:www-data {} \;
# find . -type f -exec chown www-data:www-data {} \;

include("active_sync_autodiscover.php");
include("active_sync_cmd.php");
include("active_sync_http.php");
include("active_sync_ics.php");
include("active_sync_mail.php");
include("active_sync_uuid.php");
include("active_sync_wbxml.php");

################################################################################

function active_sync_create_fullname_from_data($data, $format = 2)
	{
	$retval = active_sync_create_fullname_from_data_by_format($data, $format);

	$yomi = active_sync_create_fullname_from_data_by_format($data, 9);

	if($format != 2 && count($retval) && count($yomi))
		$retval[] = " <small>" . implode("", $yomi) . "</small>";

	foreach(["Contacts2:NickName", "Contacts:CompanyName", "Contacts:JobTitle"] as $codepage_token)
		{
		if(count($retval))
			break;

		list($codepage, $token) = explode(":", $codepage_token, 2);

		if(! isset($data[$codepage][$token]))
			continue;

		if(! strlen($data[$codepage][$token]))
			continue;

		$retval[] = $data[$codepage][$token];
		}

	if(! count($retval))
		$retval[] = "(Unbekannt)";

	return(implode("", $retval));
	}

function active_sync_create_fullname_from_data_by_format($data, $format = 9)
	{
	# see https://docs.microsoft.com/de-de/openspecs/exchange_server_protocols/ms-oxocntc/5e78e5f9-2a0e-482a-90c2-9c48953d0f8f
	# - PidTagDisplayNamePrefix (section 2.2.1.1.3)
	# - PidTagGivenName (section 2.2.1.1.6)
	# - PidTagMiddleName (section 2.2.1.1.5)
	# - PidTagSurname (section 2.2.1.1.4)
	# - PidTagGeneration (section 2.2.1.1.2)

	$table = [
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
			],
		9 => [
			"YomiLastName" => "",
			"YomiFirstName" => " "
			]
		];

	$retval = [];

	foreach($table[$format] as $token => $delimiter)
		{
		if(! isset($data["Contacts"][$token]))
			continue;

		if(! strlen($data["Contacts"][$token]))
			continue;

		if(count($retval))
			$retval[] = $delimiter;

		$retval[] = $data["Contacts"][$token];
		}

	return($retval);
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
		mkdir(ACTIVE_SYNC_LOG_DIR, 0775, true);

	if(! defined("ACTIVE_SYNC_DEBUG_HANDLE"))
		{
		$filename = "system";
		
		if(isset($_GET["DeviceId"]))
			$filename = $_GET["DeviceId"];

		$filename = "debug." . $filename . ".log";

		define("ACTIVE_SYNC_DEBUG_HANDLE", fopen(ACTIVE_SYNC_LOG_DIR . "/" . $filename, "a+"));
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

		$e = (strpos($expression, "\n") === false ? " " : "\n") . $e;

		fwrite(ACTIVE_SYNC_DEBUG_HANDLE, implode(" ", [$timestamp, $remote_port, $type, implode("", [$command, $e, "\n"])]));
		}

#	openlog("active-sync", LOG_PID | LOG_PERROR, LOG_SYSLOG);
#	syslog(LOG_NOTICE, $c);
#	closelog();

	return(true);
	}

# https://docs.microsoft.com/de-de/openspecs/exchange_server_protocols/ms-ascmd/ac5a4d82-d9b8-402a-9cab-315b5635d30d

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

	if(! $server_id)
		return(6);

	if(! mkdir(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $server_id, 0775, true))
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

# https://docs.microsoft.com/de-de/openspecs/exchange_server_protocols/ms-ascmd/9a96a4af-5c08-43cb-8308-afe4a5138cd3

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

	$folder_to_delete = ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $server_id;

	foreach(scandir($folder_to_delete) as $file)
		if(! is_dir($folder_to_delete . "/" . $file))
			unlink($folder_to_delete . "/" . $file);

	rmdir($folder_to_delete);
	}

function active_sync_folder_init($user)
	{
	if(! defined("ACTIVE_SYNC_DAT_DIR"))
		die("ACTIVE_SYNC_DAT_DIR is not defined.");

	if(! is_dir(ACTIVE_SYNC_DAT_DIR))
		mkdir(ACTIVE_SYNC_DAT_DIR, 0775, true);

	if(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $user))
		mkdir(ACTIVE_SYNC_DAT_DIR . "/" . $user, 0775, true);

	$settings = active_sync_get_settings_folder_server($user);

	if(! isset($settings["SyncDat"]))
		{
		$settings["SyncDat"] = active_sync_get_default_folder();

		active_sync_put_settings_folder_server($user, $settings);
		}

	foreach($settings["SyncDat"] as $folder)
		if(! is_dir(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $folder["ServerId"]))
			mkdir(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $folder["ServerId"], 0775, true);

	# active_sync_folder_init_apache();
	active_sync_folder_init_htaccess();
	}

function active_sync_folder_init_apache()
	{
	$filename = "/etc/apache2/conf-available/active-sync.conf";

	if(file_exists($filename))
		return;

	$data = [
		# separate service
		'<IfModule mod_alias.c>',
		'	Alias /Microsoft-Server-ActiveSync ' . __DIR__ . '/index.php',
		'</IfModule>',

		# separate service
		'<IfModule mod_alias.c>',
		'	Alias /autodiscover/autodiscover.xml ' . __DIR__ . '/index.php',
		'	Alias /Autodiscover/Autodiscover.xml ' . __DIR__ . '/index.php',
		'</IfModule>'
		];

#	file_put_contents($filename, implode("\n", $data));

#	system("systemctl reload apache2");
	}

function active_sync_folder_init_htaccess()
	{
	$filename = __DIR__ . "/.htaccess";

	if(file_exists($filename))
		return;

	$data = [
		'<Files "*">',
		'	Order allow,deny',
		'	Deny from all',
		'</Files>'
		];

	file_put_contents($filename, implode("\n", $data));
	}

function active_sync_folder_update($user, $server_id, $parent_id, $display_name)
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

	if(! isset($settings["SyncDat"]))
		return(false);

	foreach($settings["SyncDat"] as $server_id => $timestamp)
		{
		$data = active_sync_get_settings_data($user, $collection_id, $server_id);

		if(! isset($data["Calendar"]["UID"]))
			continue;
		
		if($data["Calendar"]["UID"] != $uid)
			continue;

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

	$type = ($type < 1 || $type > 19 ? 18 : $type);

	return($table[$type]);
	}

function active_sync_get_collection_id_by_display_name($user, $display_name)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(! isset($settings["SyncDat"]))
		return(false);

	foreach($settings["SyncDat"] as $folder)
		if($folder["DisplayName"] == $display_name)
			return($folder["ServerId"]);

	return(false);
	}

function active_sync_get_collection_id_by_type($user, $type)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(! isset($settings["SyncDat"]))
		return(false);

	foreach($settings["SyncDat"] as $folder)
		if($folder["Type"] == $type)
			return($folder["ServerId"]);

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
#		"Location"			=> "",
		"Organizer"			=> "",
#		"RecurrenceId"			=> "",
		"Reminder"			=> "",
		"ResponseRequested"		=> 1,
		"Sensitivity"			=> 0,
		"BusyStatus"			=> 2,
		"TimeZone"			=> "",
#		"GlobalObjId"			=> "",
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
		"ApprovedApplicationList"			=> [],	# Hash
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
		"UnapprovedInROMApplicationList"		=> []	# ApplicationName
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
	$settings = active_sync_get_settings_folder_server($user);

	if(! isset($settings["SyncDat"]))
		return(false);

	foreach($settings["SyncDat"] as $folder)
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

	if(! isset($settings["SyncDat"]))
		return(false);

	foreach($settings["SyncDat"] as $folder)
		if($folder["ServerId"] == $collection_id)
			return(true);

	return($collection_id == 0);
	}

function active_sync_get_is_display_name($user, $display_name)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(! isset($settings["SyncDat"]))
		return(false);

	foreach($settings["SyncDat"] as $folder)
		if($folder["DisplayName"] == $display_name)
			return(true);

	return(false);
	}

# https://docs.microsoft.com/en-us/powershell/module/exchange/mailboxes/new-mailbox?view=exchange-ps

function active_sync_get_is_identified($request)
	{
	$settings = active_sync_get_settings_server();

	if(! isset($settings["login"]))
		return(false);

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

			if(strcasecmp($data_mail, $email_address))
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
	return($type < 1 || $type > 19 ? false : true);
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
			{
			if($server_data["ServerId"] != $client_data["ServerId"])
				continue;

			if($server_data["ParentId"] != $client_data["ParentId"])
				continue;

			if($server_data["DisplayName"] != $client_data["DisplayName"])
				continue;

			if($server_data["Type"] != $client_data["Type"])
				continue;

			$known = true;
			}

		if(! $known)
			return(true);
		}

	foreach($settings_client["SyncDat"] as $client_id => $client_data)
		{
		$known = false;

		foreach($settings_server["SyncDat"] as $server_id => $server_data)
			{
			if($client_data["ServerId"] != $server_data["ServerId"])
				continue;

			if($client_data["ParentId"] != $server_data["ParentId"])
				continue;

			if($client_data["DisplayName"] != $server_data["DisplayName"])
				continue;

			if($client_data["Type"] != $server_data["Type"])
				continue;

			$known = true;
			}

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

	return(isset($settings["RemoteWipe"]));
	}

function active_sync_get_parent_id_by_collection_id($user, $server_id)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(! isset($settings["SyncDat"]))
		return(false);

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

		$retval["SyncDat"][$server_id] = filemtime($file);
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
		"Values" => [
			0 => "Disable Bluetooth.",
			1 => "Disable Bluetooth, but allow the configuration of hands-free profiles.",
			2 => "Allow Bluetooth."
			]
		],
		[
		"Name" => "AllowBrowser",
		"Type" => "S",
		"Values" => [
			0 => "Do not allow the use of a web browser.",
			1 => "Allow the use of a web browser."
			]
		],
		[
		"Name" => "AllowCamera",
		"Type" => "S",
		"Values" => [
			0 => "Use of the camera is not allowed.",
			1 => "Use of the camera is allowed."
			]
		],
		[
		"Name" => "AllowConsumerEmail",
		"Type" => "S",
		"Values" => [
			0 => "Do not allow the user to configure a personal email account.",
			1 => "Allow the user to configure a personal email account."
			]
		],
		[
		"Name" => "AllowDesktopSync",
		"Type" => "S",
		"Values" => [
			0 => "Do not allow Desktop ActiveSync.",
			1 => "Allow Desktop ActiveSync."
			]
		],
		[
		"Name" => "AllowHTMLEmail",
		"Type" => "S",
		"Values" => [
			0 => "HTML-formatted email is not allowed.",
			1 => "HTML-formatted email is allowed."
			]
		],
		[
		"Name" => "AllowInternetSharing",
		"Type" => "S",
		"Values" => [
			0 => "Do not allow the use of Internet Sharing.",
			1 => "Allow the use of Internet Sharing."
			]
		],
		[
		"Name" => "AllowIrDA",
		"Type" => "S",
		"Values" => [
			0 => "Disable IrDA.",
			1 => "Allow IrDA."
			]
		],
		[
		"Name" => "AllowPOPIMAPEmail",
		"Type" => "S",
		"Values" => [
			0 => "POP or IMAP email access is not allowed.",
			1 => "POP or IMAP email access is allowed."
			]
		],
		[
		"Name" => "AllowRemoteDesktop",
		"Type" => "S",
		"Values" => [
			0 => "Do not allow the use of Remote Desktop.",
			1 => "Allow the use of Remote Desktop."
			]
		],
		[
		"Name" => "AllowSimpleDevicePassword",
		"Type" => "S",
		"Values" => [
			0 => "Simple passwords are not allowed.",
			1 => "Simple passwords are allowed."
			]
		],
		[
		"Name" => "AllowSMIMEEncryptionAlgorithmNegotiation",
		"Type" => "S",
		"Values" => [
			0 => "Do not negotiate.",
			1 => "Negotiate a strong algorithm.",
			2 => "Negotiate any algorithm."
			]
		],
		[
		"Name" => "AllowSMIMESoftCerts",
		"Type" => "S",
		"Values" => [
			0 => "Soft certificates are not allowed.",
			1 => "Soft certificates are allowed."
			]
		],
		[
		"Name" => "AllowStorageCard",
		"Type" => "S",
		"Values" => [
			0 => "SD card use is not allowed.",
			1 => "SD card use is allowed."
			]
		],
		[
		"Name" => "AllowTextMessaging",
		"Type" => "S",
		"Values" => [
			0 => "SMS or text messaging is not allowed.",
			1 => "SMS or text messaging is allowed."
			]
		],
		[
		"Name" => "AllowUnsignedApplications",
		"Type" => "S",
		"Values" => [
			0 => "Unsigned applications are not allowed to execute.",
			1 => "Unsigned applications are allowed to execute."
			]
		],
		[
		"Name" => "AllowUnsignedInstallationPackages",
		"Type" => "S",
		"Values" => [
			0 => "Unsigned cabinet (.cab) files are not allowed to be installed.",
			1 => "Unsigned cabinet (.cab) files are allowed to be installed."
			]
		],
		[
		"Name" => "AllowWiFi",
		"Type" => "S",
		"Values" => [
			0 => "The use of Wi-Fi connections is not allowed.",
			1 => "The use of Wi-Fi connections is allowed."
			]
		],
		[
		"Name" => "AlphanumericDevicePasswordRequired",
		"Type" => "S",
		"Values" => [
			0 => "Alphanumeric device password is not required.",
			1 => "Alphanumeric device password is required."
			]
		],
		[
		"Name" => "ApprovedApplicationList",
		"Type" => "L",
		"Label" => "Hash"
		],
		[
		"Name" => "AttachmentsEnabled",
		"Type" => "S",
		"Values" => [
			0 => "Attachments are not allowed to be downloaded.",
			1 => "Attachments are allowed to be downloaded."
			]
		],
		[
		"Name" => "DevicePasswordEnabled",
		"Type" => "S",
		"Values" => [
			0 => "Device password is not required.",
			1 => "Device password is required."
			]
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
		"Values" => [
			0 => "All days",
			4 => "2 weeks",
			5 => "1 month",
			6 => "3 months",
			7 => "6 month"
			]
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
		"Values" => [
			0 => "Sync all",
			1 => "1 day",
			2 => "3 days",
			3 => "1 week",
			4 => "2 weeks",
			5 => "1 month"
			]
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
		"Values" => [
			0 => "Password recovery is not enabled on the server.",
			1 => "Password recovery is enabled on the server."
			]
		],
		[
		"Name" => "RequireDeviceEncryption",
		"Type" => "S",
		"Values" => [
			0 => "Encryption is not required.",
			1 => "Encryption is required."
			]
		],
		[
		"Name" => "RequireEncryptedSMIMEMessages",
		"Type" => "S",
		"Values" => [
			0 => "Encrypted email messages are not required.",
			1 => "Email messages are required to be encrypted."
			]
		],
		[
		"Name" => "RequireEncryptionSMIMEAlgorithm",
		"Type" => "S",
		"Values" => [
			0 => "TripleDES algorithm",
			1 => "DES algorithm",
			2 => "RC2 128bit",
			3 => "RC2 64bit",
			4 => "RC2 40bit"
			]
		],
		[
		"Name" => "RequireManualSyncWhenRoaming",
		"Type" => "S",
		"Values" => [
			0 => "Do not require manual sync; allow direct push when roaming.",
			1 => "Require manual sync when roaming."
			]
		],
		[
		"Name" => "RequireSignedSMIMEAlgorithm",
		"Type" => "S",
		"Values" => [
			0 => "Use SHA1.",
			1 => "Use MD5."
			]
		],
		[
		"Name" => "RequireSignedSMIMEMessages",
		"Type" => "S",
		"Values" => [
			0 => "Signed S/MIME messages are not required.",
			1 => "Signed S/MIME messages are required."
			]
		],
		[
		"Name" => "RequireStorageCardEncryption",
		"Type" => "S",
		"Values" => [
			0 => "Encryption of the device storage card is not required.",
			1 => "Encryption of the device storage card is required."
			]
		],
		[
		"Name" => "UnapprovedInROMApplicationList",
		"Type" => "L",
		"Label" => "ApplicationName"
		]
		];

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
		"16.0",	# allow SMS on Email
		"16.1",	# allow SMS on Email, Find
		];

	return($table);
	}

function active_sync_get_type_by_collection_id($user, $server_id)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(! isset($settings["SyncDat"]))
		return(false);

	foreach($settings["SyncDat"] as $folder)
		if($folder["ServerId"] == $server_id)
			return($folder["Type"]);

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

function active_sync_put_attendee_status($user, $server_id, $email, $attendee_status)
	{
	$collection_id = active_sync_get_collection_id_by_type($user, 8); # Calendar

	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	if(! isset($data["Attendees"]))
		return(false);

	foreach($data["Attendees"] as $id => $attendee)
		{
		if(! isset($attendee["Email"]))
			continue;

		if($attendee["Email"] != $email)
			continue;

		if($data["Attendees"][$id]["AttendeeStatus"] == $attendee_status)
			return(true);

		$data["Attendees"][$id]["AttendeeStatus"] = $attendee_status;

		active_sync_put_settings_data($user, $collection_id, $server_id, $data);

		return(true);
		}

	return(false);
	}

function active_sync_put_display_name($user, $server_id, $display_name)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(! isset($settings["SyncDat"]))
		return(false);

	foreach($settings["SyncDat"] as $id => $folder)
		{
		if($folder["ServerId"] != $server_id)
			continue;

		if($settings["SyncDat"][$id]["DisplayName"] == $display_name)
			return(true);

		$settings["SyncDat"][$id]["DisplayName"] = $display_name;

		active_sync_put_settings_folder_server($user, $settings);

		return(true);
		}

	return(false);
	}

function active_sync_put_parent_id($user, $server_id, $parent_id)
	{
	$settings = active_sync_get_settings_folder_server($user);

	if(! isset($settings["SyncDat"]))
		return(false);

	foreach($settings["SyncDat"] as $id => $folder)
		{
		if($folder["ServerId"] != $server_id)
			continue;

		if($settings["SyncDat"][$id]["ParentId"] == $parent_id)
			return(true);

		$settings["SyncDat"][$id]["ParentId"] = $parent_id;

		active_sync_put_settings_folder_server($user, $settings);

		return(true);
		}

	return(false);
	}

function active_sync_put_settings($file, $data)
	{
#	$data = serialize($data);
	$data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	$retval = file_put_contents($file, $data);

	chmod($file, 0666); # read (4) and write (2) access to group, user, world

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

	$mail = active_sync_mail_split($mime);

	$head = iconv_mime_decode_headers($mail["head"]);

	$additional_headers = [];

	foreach($head as $key => $val)
		{
		if($key == "Received" || $key == "Subject" || $key == "To")
			continue;

		$additional_headers[] = implode(": ", [$key, $val]);
		}

	# don't we need a recipient here? by settting to null we got an empty field.

	# mail($head["To"], (isset($head["Subject"]) ? $head["Subject"] : ""), $mail["body"], implode("\n", $additional_headers), "-f no-reply@" . $host);
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
?>
