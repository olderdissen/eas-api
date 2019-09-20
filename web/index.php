<?
chdir(__DIR__);

include_once("../active_sync_kern.php");

################################################################################
# ...
################################################################################

if(! defined("ACTIVE_SYNC_WEB_DIR"))
	die("ACTIVE_SYNC_WEB_DIR is not defined. have you included active_sync_kern.php before? ACTIVE_SYNC_WEB_DIR is needed to provide access to user data.");
elseif(! file_exists(ACTIVE_SYNC_WEB_DIR . "/.htaccess"))
	{
	$data = array
		(
		"<IfModule mod_deflate.c>",
		"\tAddOutputFilterByType DEFLATE application/json",
		"\tAddOutputFilterByType DEFLATE image/png",
		"\tAddOutputFilterByType DEFLATE text/event-stream",
		"\tAddOutputFilterByType DEFLATE text/javascript",
		"</IfModule>"
		);

	file_put_contents(ACTIVE_SYNC_WEB_DIR . "/.htaccess", implode("\n", $data));
	}

################################################################################
# get some values
################################################################################

$filters = array("now", "- 1 day", "- 3 day", "- 1 week", "- 2 week", "- 1 month", "- 3 month", "- 6 month", "now");

################################################################################
# parse the request
################################################################################

$Request = active_sync_http_query_parse(); # still used for identifying user

foreach(array("Filter" => 6, "View" => 0, "Date" => time(), "Category" => "*", "AttachmentName" => "", "Cmd" => "", "CollectionId" => "", "DeviceId" => "", "DeviceType" => "", "ItemId" => "", "LongId" => "", "Occurence" => "", "SaveInSent" => "", "User" => "", "Pass" => "", "IsAdmin" => "F", "ServerId" => "", "ParentId" => "", "Type" => "", "DisplayName" => "", "Field" => "", "Search" => "", "StartTime" => "", "State" => "", "EndTime" => "", "RequestId" => "", "UserResponse" => "", "Status" => "", "Read" => "", "SrcFldId" => "", "DstFldId" => "", "SrcMsgId" => "", "DstMsgId" => "") as $key => $value)
	{
	$Request[$key] = $value;

	if($_SERVER["REQUEST_METHOD"] == "GET")
		$Request[$key] = (isset($_GET[$key]) ? $_GET[$key] : $Request[$key]);

	if($_SERVER["REQUEST_METHOD"] == "POST")
		$Request[$key] = (isset($_POST[$key]) ? $_POST[$key] : $Request[$key]);
	}

################################################################################
# show the site
################################################################################

if($Request["Cmd"] == "js")
	{
	header("Content-Type: text/javascript; charset=\"UTF-8\"");

	active_sync_load_includes("includes.js", "js");
	active_sync_load_includes("includes.js/rte", "js");
	}
elseif(! file_exists(ACTIVE_SYNC_DAT_DIR . "/login.data"))
	{
	if($Request["Cmd"] == "")
		{
		html_open();
			print("<p style=\"font-weight: bold;\">");
				print("Benutzer hinzufügen");
			print("</p>");

			print("<form>");
				print("<input type=\"hidden\" name=\"Cmd\" value=\"UserCreate\">");
				print("<div style=\"padding-left: 32px;\">");
					print("<table>");

						foreach(array("User" => "Benutzername", "Pass" => "Kennwort") as $key => $value)
							{
							print("<tr>");
								print("<td align=\"right\">");
									print($value);
								print("</td>");
								print("<td>");
									print(":");
								print("</td>");
								print("<td>");
									print("<input type=\"text\" name=\"" . $key . "\">");
								print("</td>");
							print("</tr>");
							}

						# additional fields are needed as in user dialog
					print("</table>");
				print("</div>");
			print("</form>");
			print("<p>");
				print("[");
					print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'UserSave' });\">");
						print("Speichern");
					print("</span>");
				print("]");
			print("</p>");
		html_close();
		}
	elseif($Request["Cmd"] == "UserCreate")
		{
		if(! is_dir(ACTIVE_SYNC_DAT_DIR))
			mkdir(ACTIVE_SYNC_DAT_DIR, 0777, true);

		if(! file_exists(ACTIVE_SYNC_DAT_DIR . "/login.data"))
			{
			active_sync_put_settings_login(array("login" => array(0 => array("User" => $Request["User"], "Pass" => $Request["Pass"], "IsAdmin" => "T"))));

			active_sync_folders_init($Request["User"]);
			}

		header("Location: index.php");
		}
	else
		header("Location: index.php");
	}
elseif(! active_sync_get_is_identified($Request))
	{
	header("WWW-Authenticate: basic realm=\"ActiveSync\"");

	html_open();
		print("Zugriff nicht gestattet");
	html_close();
	}
elseif($Request["Cmd"] == "")
	{
	if(! active_sync_get_is_identified($Request))
		{
		header("WWW-Authenticate: basic realm=\"ActiveSync\"");

		html_open();
			print("Zugriff nicht gestattet");
		html_close();
		}
	else
		{
		html_open();

		html_close();

#		header("Location: index.php?Cmd=Start"); # we have catched user login date, now jump back to start page ... make sure we have right credentials ... maybe site uses other auth too
		}
	}
else
	{
	$includes = array
		(
		"calendar",
		"contact",
		"email",
		"notes",
		"tasks",

		"category",
		"device",
		"folder",
		"menu",
		"oof",
		"policy",
		"rights",
		"service",
		"settings",
		"user"
		);

	foreach($includes as $section)
		{
		$file = ACTIVE_SYNC_WEB_DIR . "/includes.php/index_" . $section . ".php";

		if(file_exists($file))
			include_once($file);
		}

	active_sync_web($Request);
	}

function html_open()
	{
	global $Request;

	header("Content-Type: text/html; charset=\"UTF-8\"");
	header("Cache-Control: no-cache");

	print('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">');
	print("<html>");
		print("<head>");
			print("<title>" . active_sync_get_version() . "</title>");

			foreach(array("blocker", "calendar", "contacts", "email", "html", "list", "notes", "popup", "progress", "rte", "suggest", "touch") as $file)
				print("<link rel=\"stylesheet\" type=\"text/css\" href=\"includes.css/index_" . $file . ".css\">");

			print("<link rel=\"icon\" href=\"images/favicon.png\" type=\"image/png\">");
#			print("<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">");
#			print("<meta http-equiv=\"pragma\" content=\"no-cache\">");
#			print("<meta http-equiv=\"expires\" content=\"-1\">");
			print("<meta name=\"viewport\" content=\"width=device-width, minimum-scale=1.0, maximum-scale=1.0\">");
		print("</head>");

		print("<body>");
			print("<table style=\"height: 100%; width: 100%;\">");
				print("<tr>");
					print("<td style=\"height: 100%; padding: 10px;\">");
						print("<table style=\"height: 100%; width: 100%;\">");
							print("<tr>");
								print("<td>");
								print(active_sync_get_version() . " - ...");
								print("</td>");
							print("</tr>");
							print("<tr>");
								print("<td>");
									print("<div style=\"background-color: #FF0000; height: 1px;\">");
									print("</div>");
								print("</td>");
							print("</tr>");
							print("<tr>");
								print("<td style=\"height: 100%; vertical-align: top;\">");
									print("<table style=\"height: 100%; width: 100%;\">");
										print("<tr>");
											print("<td style=\"vertical-align: top; width: 175px;\">");

												print('<script type="text/javascript" src="index.php?Cmd=js">');
												print('</script>');

												if(file_exists(ACTIVE_SYNC_DAT_DIR . "/login.data"))
													if(active_sync_get_is_identified($Request) == 0)
														{
														print("<table style=\"width: 100%;\">");
															print("<tr>");
																print("<td style=\"vertical-align: top; width: 150px;\">");
																	print("<a href=\"index.php\">LogIn</a>");
																print("</td>");
															print("</tr>");
														print("</table>");
														}
													else
														{
														print("<span id=\"menu_content\">");
														print("</span>");

														################################################################################
														# don't let this be called in unidentifed state. popup_init will call Cmd=Menu,
														# wich needs authentication from there it will download user-dependent things

														print("<script type=\"text/javascript\">");
															print("handle_link({ cmd : 'MenuItems' });");
															print("handle_link({ cmd : 'EventContext' });");
															print("evt_drag_init();");
														print("</script>");
														################################################################################
														}

											print("</td>");
											print("<td style=\"width: 32px;\">");
												print("&nbsp;");
											print("</td>");
											print("<td style=\"height: 100%; vertical-align: top;\" id=\"body_content\">");
	}

function html_close()
	{
											print("</td>");
										print("</tr>");
									print("</table>");
								print("</td>");
							print("</tr>");
							print("<tr>");
								print("<td>");
									print("<div style=\"background-color: #FF0000; height: 1px;\">");
									print("</div>");
								print("</td>");
							print("</tr>");
							print("<tr>");
								print("<td>");
									print("Copyright &copy; 2012 by Markus Olderdissen. Alle Rechte vorbehalten. Keine unerlaubte Vervielfältigung.");
								print("</td>");
							print("</tr>");
						print("</table>");
					print("</td>");
				print("</tr>");
			print("</table>");
		print("</body>");
	print("</html>");
	}

function active_sync_compare_address($data, $expression)
	{
	$expression = strtolower($expression);

	foreach(array("BusinessAddress", "HomeAddress", "OtherAddress") as $token)
		foreach(array("Country", "State", "City", "PostalCode", "Street") as $key)
			if(isset($data["Contacts"][$token . $key]))
				if($data["Contacts"][$token . $key])
					if(substr(strtolower($data["Contacts"][$token . $key]), 0, strlen($expression)) == $expression)
						return(true);

	return(false);
	}

function active_sync_compare_name($data, $expression)
	{
	$expression = strtolower($expression);

	foreach(array("FirstName", "LastName", "MiddleName", "Email1Address", "Email2Address", "Email3Address", "JobTitle", "CompanyName") as $token)
		if(isset($data["Contacts"][$token]))
			if($data["Contacts"][$token])
				if(substr(strtolower($data["Contacts"][$token]), 0, strlen($expression)) == $expression)
					return(true);

	return(false);
	}

function active_sync_compare_other($data, $expression)
	{
	$expression = strtolower($expression);

	foreach(array("NickName", "CustomerId") as $token)
		if(isset($data["Contacts2"][$token]))
			if($data["Contacts2"][$token])
				if(substr(strtolower($data["Contacts2"][$token]), 0, strlen($expression)) == $expression)
					return(true);

	return(false);
	}

function active_sync_compare_phone($data, $expression)
	{
	$expression = strtolower($expression);

	$expression = active_sync_fix_phone($expression);

	$table = array
		(
		"AssistnamePhoneNumber",
		"CarPhoneNumber",
		"MobilePhoneNumber",
		"PagerNumber",
		"RadioPhoneNumber",
		"BusinessFaxNumber",
		"BusinessPhoneNumber",
		"Business2PhoneNumber",
		"HomeFaxNumber",
		"HomePhoneNumber",
		"Home2PhoneNumber"
		);

	foreach($table as $token)
		if(isset($data["Contacts"][$token]))
			if($data["Contacts"][$token])
				if(substr(strtolower(active_sync_fix_phone($data["Contacts"][$token])), 0, strlen($expression)) == $expression)
					return(true);

	return(false);
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

function active_sync_get_categories_by_collection_id($user_id, $collection_id)
	{
	$retval = array("*" => 0); # this is placeholder to count contacts without category

	foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $user_id . "/" . $collection_id . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($user_id, $collection_id, $server_id);

		if(! isset($data["Categories"]))
			$retval["*"] ++;
		elseif(! $data["Categories"])
			$retval["*"] ++;
		else
			foreach($data["Categories"] as $id => $category)
				if(isset($retval[$category]))
					$retval[$category] ++;
				else
					$retval[$category] = 1;
		}

	if($retval)
		ksort($retval, SORT_LOCALE_STRING);

	return($retval);
	}

function active_sync_get_devices_by_user($user)
	{
	$retval = array();

	foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/*.sync") as $file)
		$retval[] = basename($file, ".sync");

	if($retval)
		sort($retval);

	return($retval);
	}

function active_sync_get_icon_by_type($type)
	{
	$table = array
		(
		1 => "default",		# user-created folder (generic)
		2 => "mail-inbox",	# default inbox folder
		3 => "mail-drafts",	# default drafts folder
		4 => "mail-trash",	# default deleted items folder
		5 => "mail-sent",	# default sent items folder
		6 => "mail-outbox",	# default outbox folder
		7 => "tasks",		# default tasks folder
		8 => "calendar",	# default calendar folder
		9 => "contacts",	# default contacts folder
		10 => "notes",		# default notes folder
		11 => "journal",	# default journal folder
		12 => "mail-default",	# user-created mail folder
		13 => "calendar",	# user-created calendar folder
		14 => "contacts",	# user-created contacts folder
		15 => "tasks",		# user-created tasks folder
		16 => "journal",	# user-created journal folder
		17 => "notes",		# user-created notes folder
		18 => "default",	# unknown folder type
		19 => "ric"		# recipient information cache
		);

	$type = (isset($table[$type]) ? $type : 1);

	return("folder-" . $table[$type] . ".png");
	}

function active_sync_get_is_phone($phone)
	{
	$table = array
		(
		"+40-7",
		"+49-15",
		"+49-16",
		"+49-17"
		);

	foreach($table as $prefix)
		{
		$prefix	= active_sync_fix_phone($prefix);
		$phone	= active_sync_fix_phone($phone);

		if(substr($phone, 0, strlen($prefix)) == $prefix)
			return(true);
		}

	return(false);
	}

function active_sync_get_is_phone_available($data)
	{
	$table = array
		(
		"AssistnamePhoneNumber",
		"CarPhoneNumber",
		"MobilePhoneNumber",
		"PagerNumber",
		"RadioPhoneNumber",
		"BusinessFaxNumber",
		"BusinessPhoneNumber",
		"Business2PhoneNumber",
		"HomeFaxNumber",
		"HomePhoneNumber",
		"Home2PhoneNumber"
		);

	foreach($table as $token)
		if(isset($data["Contacts"][$token]))
			if($data["Contacts"][$token])
				return(true);

	return(false);
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

function active_sync_mail_file_size($size)
	{
	$unit = 0;

	while($size > 999)
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

function active_sync_normalize_chars($string)
	{
	$table = array
		(
		"a" => array("à", "á", "â", "ã", "ä", "å", "ā", "ă"),
		"c" => array("ç", "ć", "ĉ", "ċ", "č", "ḉ"),
		"e" => array("è", "é", "ê", "ë", "ē", "ĕ", "ė", "ę", "ě", "ḕ", "ḗ", "ḙ", "ḛ", "ḝ"),
		"i" => array("ì", "í", "î"),
		"o" => array("ò", "ó", "ô", "ö"),
		"s" => array("ß", "ś", "ŝ", "ș", "š"),
		"t" => array("ț", "ţ"),
		"u" => array("ù", "ú", "û", "ü", "ũ", "ū", "ŭ", "ű"),
		"z" => array("ź", "ż", "ž"),
		);

	foreach($table as $char => $chars)
		foreach(array("strtolower", "strtoupper") as $action)
			$string = str_replace($action($chars), $action($char), $string);

	return($string);
	}

function active_sync_postfix_virtual_alias_maps_exists($user)
	{
	$host = system("postconf -h mydomain");

	$file = system("postconf -h virtual_alias_maps");

	list($type, $file) = explode(":", $file, 2);

	$data = (file_exists($file) ? file($file) : array());

	foreach($data as $id => $line)
		{
		if(strpos($line, " ") === false)
			$line .= " ";

		list($key, $val) = explode(" ", $line, 2);

		if(trim($key) == $user . "@" . $host)
			return(true);
		}

	return(false);
	}

function active_sync_postfix_virtual_mailbox_base_create($user = "root")
	{
	$file = system("postconf -h virtual_mailbox_base");

	foreach(array("/cur/", "/new/", "/tmp/") as $dir)
		if(! is_dir($file . $user . $dir))
			mkdir($file . $user . $dir, 0700, true);
	}

function active_sync_postfix_virtual_mailbox_base_delete($user)
	{
	$path = system("postconf -h virtual_mailbox_base");

	active_sync_maildir_delete_recursive($path . "/" . $user);
	}

function active_sync_postfix_virtual_mailbox_base_delete_recursive($folder)
	{
	foreach(scandir($folder) as $file)
		{
		if(($file == ".") || ($file == ".."))
			continue;

		if(is_dir($folder . "/" . $file))
			active_sync_postfix_virtual_mailbox_base_delete_recursive($folder . "/" . $file);
		else
			unlink($folder . "/" . $file);
		}

	rmdir($folder);
	}

function active_sync_postfix_virtual_mailbox_base_exists($user)
	{
	$file = system("postconf -h virtual_mailbox_base");

	return(is_dir($file . "/" . $user));
	}

function active_sync_postfix_virtual_mailbox_base_sync($auto_response_suppress = 0)
	{
	$host = system("postconf -h mydomain");

	$settings = active_sync_get_settings_server();

	foreach($settings["login"] as $login)
		{
		$user_id = $login["User"];

		if(file_exists(ACTIVE_SYNC_DAT_DIR . "/" . $user_id . ".mdl"))
			continue;

		touch(ACTIVE_SYNC_DAT_DIR . "/" . $user_id . ".mdl");

		$list = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user_id . ".mds");

		$oof = active_sync_get_settings_folder_server($user_id);

		$maildir = system("postconf -h virtual_mailbox_base") . "/" . $user_id . "/new";

		$collection_id = active_sync_get_collection_id_by_type($user_id, 2);

		foreach($list as $server_id => $timestamp)
			{
			if(! file_exists($maildir . "/" . $server_id))
				{
				if(file_exists(ACTIVE_SYNC_DAT_DIR . "/" . $user_id . "/" . $collection_id . "/" . $server_id . ".data") !== false)
					unlink(ACTIVE_SYNC_DAT_DIR . "/" . $user_id . "/" . $collection_id . "/" . $server_id . ".data");

				unset($list[$server_id]);
				}

			if(! file_exists(ACTIVE_SYNC_DAT_DIR . "/" . $user_id . "/" . $collection_id . "/" . $server_id . ".data"))
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

			if(file_exists(ACTIVE_SYNC_DAT_DIR . "/" . $user_id . "/" . $collection_id . "/" . $file . ".data"))
				continue;

			$mime = file_get_contents($maildir . "/" . $file);

			$data = active_sync_mail_parse($user_id, $collection_id, $file, $mime);

			active_sync_put_settings_data($user_id, $collection_id, $file, $data);

			$list[$file] = filemtime($maildir . "/" . $file);

			################################################################################

			$send_oof = false;

			if($oof["OOF"]["OofState"] == 1)
				$send_oof = true;

			if($oof["OOF"]["OofState"] == 2)
				if(time() > strtotime($oof["OOF"]["StartTime"]))
					if(time() < strtotime($oof["OOF"]["EndTime"]))
						$send_oof = true;
			
			if(! $send_oof)
				continue;

			################################################################################

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

			################################################################################

			$old_mime_message = "";

			foreach($data["Body"] as $body)
				if(isset($body["Type"]))
					if($body["Type"] == 4) # Mime
						$old_mime_message = $data["Body"][$id]["Data"];

			################################################################################

			list($head, $body) = active_sync_mail_split($old_mime_message);

			$head_parsed = iconv_mime_decode_headers($head);

			if(isset($head_parsed["X-Auto-Response-Suppress"]))
				$head_parsed["X-Auto-Response-Suppress"] = explode(",", "X-Auto-Response-Suppress");

			if(isset($head_parsed["X-Auto-Response-Suppress"]["OOF"]))
				continue;

			if(in_array($from_mail, array("", $to_mail)))
				continue;

			if(in_array($from_user, array("mailer-daemon", "no-reply", "root", "wwwrun", "www-run", "wwww-data", "www-user", "mail", "noreply", "postfix")))
				continue;

			################################################################################

			$collection_id = active_sync_get_collection_id_by_type($user_id, 9);

			################################################################################

			$reply_message = "";

			if($oof["OOF"]["AppliesToInternal"]["Enabled"] == 1)
				if($from_host == $to_host)
					$reply_message = $oof["OOF"]["OOF"]["AppliesToInternal"]["ReplyMessage"];

			if($oof["OOF"]["AppliesToExternalKnown"]["Enabled"] == 1)
				if($from_host != $to_host)
					if(active_sync_get_is_known_mail($user_id, $collection_id, $from_mail))
						$reply_message = $oof["OOF"]["AppliesToExternalKnown"]["ReplyMessage"];

			if($oof["OOF"]["AppliesToExternalUnknown"]["Enabled"] == 1)
				if($from_host != $to_host)
					if(! active_sync_get_is_known_mail($user_id, $collection_id, $from_mail))
						$reply_message = $oof["OOF"]["AppliesToExternalUnknown"]["ReplyMessage"];

			################################################################################

			if(! strlen($reply_message))
				continue;

			################################################################################

			$from_mail = implode("@", array($from_user, $from_host));
			$to_mail = implode("@", array($to_user, $to_host));

			################################################################################

			$auto_response_suppress_array = array();

			foreach(array(1 => "DR", 2 => "NDR", 3 => "RN", 4 => "NRN", 5 => "OOF", 6 => "AutoReply") as $key => $value)
				if($auto_response_suppress & (1 << $key))
					$auto_response_suppress_array[] = $value;

			$auto_response_suppress_string = implode(", ", $auto_response_suppress_array);

			################################################################################

			$new_mime_message = array
				(
				"From: " . ($to_name ? "\"" . $to_name . "\" <" . $to_mail . ">" : $to_mail),
				"To: " . ($from_name ? "\"" . $from_name . "\" <" . $from_mail . ">" : $from_mail),
				"Subject: OOF: " . $data["Email"]["Subject"],
				"Reply-To: " . $to_mail,
				"Auto-Submitted: auto-generated",
				"Message-ID: <" . active_sync_create_guid() . "@" . $host . ">",
				"X-Auto-Response-Suppress: " . $auto_response_suppress_string,
				"",
				$reply_message
				);

			$new_mime_message = implode("\n", $new_mime_message);

			################################################################################

			active_sync_send_mail($user_id, $new_mime_message);
			}

		active_sync_put_settings(ACTIVE_SYNC_DAT_DIR . "/" . $user_id . ".mds", $list);

		@ unlink(ACTIVE_SYNC_DAT_DIR . "/" . $user_id . ".mdl");
		}
	}

function active_sync_postfix_virtual_mailbox_maps_create($user)
	{
	$host = system("postconf -h mydomain");

	$file = system("postconf -h virtual_mailbox_maps");

	list($type, $file) = explode(":", $file, 2);

	$data = (file_exists($file) ? file($file) : array());

	$data[] = $user . "@" . $host . " " . $user . "/" . "\n";

	system("chmod 0666 " . $file);
	file_put_contents($file, implode("", $data));
	system("chmod 0644 " . $file);

	system("postmap " . $file);
	system("systemctl reload postfix");

	return(true);
	}

function active_sync_postfix_virtual_mailbox_maps_delete($user)
	{
	$host = system("postconf -h mydomain");

	$file = system("postconf -h virtual_mailbox_maps");

	list($type, $file) = explode(":", $file, 2);

	$data = (file_exists($file) ? file($file) : array());

	foreach($data as $id => $line)
		{
		if(strpos($line, " ") === false)
			$line .= " ";

		list($key, $val) = explode(" ", $line, 2);

		if(trim($key) != $user . "@" . $host)
			continue;

		unset($data[$id]);

		break;
		}

	system("chmod 0666 " . $file);
	file_put_contents($file, implode("", $data));
	system("chmod 0644 " . $file);

	system("postmap " . $file);
	system("systemctl reload postfix");
	}

function active_sync_postfix_virtual_mailbox_maps_exists($user)
	{
	$host = system("postconf -h mydomain");

	$file = system("postconf -h virtual_mailbox_maps");

	list($type, $file) = explode(":", $file, 2);

	$data = (file_exists($file) ? file($file) : array());

	foreach($data as $id => $line)
		{
		if(strpos($line, " ") === false)
			$line .= " ";

		list($key, $val) = explode(" ", $line, 2);

		if(trim($key) == $user . "@" . $host)
			return(true);
		}

	return(false);
	}

function active_sync_show_recurrence($data, $lang = "de")
	{
	# values defined by MS-AS are 1, 2, 3, 5, 6. value of 4 is not defined thus it is free to use for single occurrence

	active_sync_show_recurrence_type($data, $lang);
	active_sync_show_recurrence_occurrences($data, $lang);
	active_sync_show_recurrence_interval($data, $lang);
	active_sync_show_recurrence_week_of_month($data, $lang);
	active_sync_show_recurrence_day_of_week($data, $lang);
	active_sync_show_recurrence_month_of_year($data, $lang);
	active_sync_show_recurrence_day_of_month($data, $lang);
	active_sync_show_recurrence_until($data, $lang);

	print("<input type=\"hidden\" name=\"Recurrence:CalendarType\" value=\"" . $data["Recurrence"]["CalendarType"] . "\">");
	print("<input type=\"hidden\" name=\"Recurrence:IsLeapMonth\" value=\"" . $data["Recurrence"]["IsLeapMonth"] . "\">");
	print("<input type=\"hidden\" name=\"Recurrence:FirstDayOfWeek\" value=\"1\">");

	print("<script type=\"text/javascript\">");
		print("update_recurrence_init();");
		print("update_recurrence_type();");
		print("update_recurrence_month_of_year();");
		print("update_recurrence_day_of_week(" . $data["Recurrence"]["DayOfWeek"] . ");");

		foreach(array("Occurrences", "Interval", "DayOfMonth") as $token)
			if(isset($data["Recurrence"][$token]))
				print("document.forms[0]['Recurrence:" . $token . "'].selectedIndex = " . ($data["Recurrence"][$token] - 1) . ";");

	print("</script>");
	}

function active_sync_show_recurrence_day_of_month($data, $lang)
	{
	print("<span id=\"Recurrence:DayOfMonth\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Tag");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:DayOfMonth\" class=\"xs\">");
						foreach(range(0, 31) as $i)
							{
							print("<option value=\"" . $i . "\"" . ($data["Recurrence"]["DayOfMonth"] == $i ? " selected" : "") . ">");
								print($i);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_day_of_week($data, $lang)
	{
	print("<span id=\"Recurrence:DayOfWeek\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Wochentag");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:DayOfWeek\" class=\"xs\" onchange=\"handle_link({ cmd : 'UpdateRecurrenceDayOfWeek' });\">");
					print("</select>");

/*
					print("<table>");
						foreach(array(1 => "Sonntag", 2 => "Montag", 4 => "Dienstag", 8 => "Mittwoch", 16 => "Donnerstag", 32 => "Freitag", 64 => "Samstag", 127 => "letzter Tag des Monats") as $key => $value)
							{
							print("<tr>");
								print("<td>");
									print("<input type=\"checkbox\" name=\"Recurrence:DayOfWeek[]\" onchange=\"handle_link({ cmd : 'UpdateRecurrenceDayOfWeek' });\" value=\"" . $key . "\">");
								print("</td>");
								print("<td>");
									print($value);
								print("</td>");
							print("</tr>");
							}
					print("</table>");
*/

				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_interval($data, $lang)
	{
	print("<span id=\"Recurrence:Interval\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Interval");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:Interval\" class=\"xs\">");
						foreach(range(0, 999) as $i)
							{
							print("<option value=\"" . $i . "\"" . ($data["Recurrence"]["Interval"] == $i ? " selected" : "") . ">");
								print($i);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_month_of_year($data, $lang)
	{
	print("<span id=\"Recurrence:MonthOfYear\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Monat");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:MonthOfYear\" class=\"xs\" onchange=\"update_recurrence_month_of_year();\">");
						foreach(array(1 => "Januar", 2 => "Februar", 3 => "März", 4 => "April", 5 => "Mai", 6 => "Juni", 7 => "Juli", 8 => "August", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Dezember") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Recurrence"]["MonthOfYear"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_occurrences($data, $lang)
	{
	print("<span id=\"Recurrence:Occurrences\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Wiederholungen");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:Occurrences\" class=\"xs\">");
						foreach(range(1, 999) as $i)
							{
							print("<option value=\"" . $i . "\"" . ($data["Recurrence"]["Occurrences"] == $i ? " selected" : "") . ">");
								print($i);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_type($data, $lang = "de")
	{
	print("<table>");
		print("<tr>");
			print("<td class=\"field_label\">");
				print("Wiederholung");
			print("</td>");
			print("<td>");
				print(":");
			print("</td>");
			print("<td>");
				print("<select name=\"Recurrence:Type\" class=\"xs\" onchange=\"update_recurrence_type();\">");
					foreach(array(4 => "Einmaliges Ereignis", 0 => "Täglich", 1 => "Wöchentlich", 2 => "Monatlich", 3 => "Monatlich", 5 => "Jährlich", 6 => "Jährlich") as $key => $value)
						{
						print("<option value=\"" . $key . "\"" . ($data["Recurrence"]["Type"] == $key ? " selected" : "") . ">");
							print($value);
						print("</option>");
						}
				print("</select>");
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_show_recurrence_until($data, $lang)
	{
	print("<span id=\"Recurrence:Until\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Ablaufdatum");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<input type=\"text\" name=\"Recurrence:Until\" value=\"" . $data["Recurrence"]["Until"] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">"); # date or date + time ???
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_week_of_month($data, $lang)
	{
	print("<span id=\"Recurrence:WeekOfMonth\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Woche");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:WeekOfMonth\" class=\"xs\" onchange=\"update_recurrence_week_of_month();\">");
						foreach(array(1 => "ersten", 2 => "zweiten", 3 => "dritten", 4 => "vierten", 5 => "letzten") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Recurrence"]["Interval"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_web($request)
	{
	$table = array
		(
		"Data"		=> "active_sync_web_data",
		"Delete"	=> "active_sync_web_delete",
		"Edit"		=> "active_sync_web_edit",
		"List"		=> "active_sync_web_list",
		"Meeting"	=> "active_sync_web_meeting",
		"Print"		=> "active_sync_web_print",
		"Save"		=> "active_sync_web_save"
		);

	$retval = null;

	foreach($table as $command => $function)
		if($request["Cmd"] == $command)
			if(function_exists($function))
				$retval = $function($request);

	return($retval);
	}

function active_sync_web_data($request)
	{
	$table = array
		(
		"Calendar"	=> "active_sync_web_data_calendar",
		"Contacts"	=> "active_sync_web_data_contacts",
		"Email"		=> "active_sync_web_data_email",
		"Notes"		=> "active_sync_web_data_notes",
		"Tasks"		=> "active_sync_web_data_tasks"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		if($default_class == $class)
			if(function_exists($function))
				$retval = $function($request);

	return($retval);
	}

function active_sync_web_data_calendar($request)
	{
	$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	$settings["Settings"]["CalendarSync"] = (isset($settings["Settings"]["CalendarSync"]) ? $settings["Settings"]["CalendarSync"] : 0);

	$calendar_sync = array("", "- 1 week", "- 1 month", "- 3 month", "- 6 month");

	$retval = array();

	if(strlen($request["StartTime"]) == 0)
		{
		# StartTime is missed
		}
	elseif(strlen($request["EndTime"]) == 0)
		{
		# EndTime is missed
		}
	else
		{
		foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
			{
			$server_id = basename($file, ".data");

			$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

			foreach(array("EndTime" => 0, "StartTime" => 0, "AllDayEvent" => 0, "Subject" => "", "Location" => "") as $token => $value)
				$data["Calendar"][$token] = (isset($data["Calendar"][$token]) ? $data["Calendar"][$token] : $value);

			foreach(array("Interval" => 1, "Occurrences" => 1) as $token => $value)
				$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) ? $data["Recurrence"][$token] : $value);

			$data["Calendar"]["StartTime"]	= strtotime($data["Calendar"]["StartTime"]);
			$data["Calendar"]["EndTime"]	= strtotime($data["Calendar"]["EndTime"]);

			foreach(range(1, $data["Recurrence"]["Occurrences"]) as $i)
				{
				$add = 0;

				if(($request["StartTime"] == "*") && ($request["EndTime"] == "*")) # request by agenda view
					{
					if($data["Calendar"]["StartTime"] >= strtotime($calendar_sync[$settings["Settings"]["CalendarSync"]])) # starts at selected day, ends at selected day
						$add = 1;
					}
				elseif($request["StartTime"] == "*")
					{
					}
				elseif($request["EndTime"] == "*")
					{
					}
				elseif($data["Calendar"]["EndTime"] <= $request["StartTime"])
					{
					}
				elseif($data["Calendar"]["StartTime"] >= $request["EndTime"])
					{
					}
				else
					{
					$add = 1;
					}

				if($add == 1)
					$retval[] = array($data["Calendar"]["StartTime"], $data["Calendar"]["EndTime"], $data["Calendar"]["AllDayEvent"], $server_id, $data["Calendar"]["Subject"], $data["Calendar"]["Location"]);

				$data["Calendar"]["StartTime"]	+= ($data["Recurrence"]["Interval"] * 86400);
				$data["Calendar"]["EndTime"]	+= ($data["Recurrence"]["Interval"] * 86400);
				}
			}
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}

function active_sync_web_data_contacts($request)
	{
	$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	foreach(array("ShowBy" => 1, "SortBy" => 1, "PhoneOnly" => 0) as $key => $value)
		$settings["Settings"][$key] = (isset($settings["Settings"][$key]) ? $settings["Settings"][$key] : $value);

	$retval = array();

	foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		$active = false;

		if(! isset($_GET["Category"])) # ...
			{
			if(isset($data["Categories"]))
				if(count($data["Categories"]) > 0)
					$active = true;
			}
		elseif($_GET["Category"] == "") # nicht zugewiesen
			{
			if(! isset($data["Categories"])) # no categories found
				$active = true;
			elseif(count($data["Categories"]) == 0) # empty categories found
				$active = true;
			}
		elseif($_GET["Category"] != "*") # alle
			{
			if(isset($data["Categories"]))
				if(count($data["Categories"]) > 0)
					if(in_array($_GET["Category"], $data["Categories"]))
						$active = true;
			}
		elseif(! isset($settings["Categories"])) # no setup found
			$active = true;
		elseif(count($settings["Categories"]) == 0) # empty setup found
			$active = true;
		elseif(! isset($data["Categories"])) # no categories found
			{
			if(isset($settings["Categories"]["*"]))
				if($settings["Categories"]["*"] == 1)
					$active = true;
			}
		elseif(count($data["Categories"]) == 0) # empty categories found
			{
			if(isset($settings["Categories"]["*"]))
				if($settings["Categories"]["*"] == 1)
					$active = true;
			}
		else
			{
			foreach($data["Categories"] as $category)
				{
				if(! isset($settings["Categories"][$category])) # no setup found
					{
					$active = true;

					break;
					}

				if($settings["Categories"][$category] == 1)
					{
					$active = true;

					break;
					}
				}
			}

		if(! $active)
			continue;

		$name_show = active_sync_create_fullname_from_data_for_contacts($data, $settings["Settings"]["ShowBy"]);
		$name_sort = active_sync_create_fullname_from_data_for_contacts($data, $settings["Settings"]["SortBy"]);

		$name_sort = str_replace(array("ä", "Ä", "ö", "Ö", "ü,", "Ü", "ß"), array("a", "A", "o", "O", "u", "U", "s"), $name_sort);
#		$name_sort = active_sync_normalize_chars($name_sort);
		$name_sort = strtolower($name_sort);

		$picture = (isset($data["Contacts"]["Picture"]) ? "data:image/unknown;base64," . $data["Contacts"]["Picture"] : "/active-sync/web/images/contacts_default_image_small.png");

		$search_entry = array($name_sort, $name_show, $server_id, $picture);

		if($settings["Settings"]["PhoneOnly"] != 0)
			if(! active_sync_get_is_phone_available($data))
				continue;

		$add = false;

		if(! isset($_GET["Search"]))
			$add = true;
		elseif($_GET["Search"] == "")
			$add = true;

		if(! $add)
			$add = active_sync_compare_phone($data, $_GET["Search"]);

		if(! $add)
			$add = active_sync_compare_address($data, $_GET["Search"]);

		if(! $add)
			$add = active_sync_compare_other($data, $_GET["Search"]);

		if(! $add)
			$add = active_sync_compare_name($data, $_GET["Search"]);

		if(! $add)
			continue;

		$retval[] = $search_entry;
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}

function active_sync_web_data_email($request)
	{
	$retval = array();

	foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		if(! isset($data["AirSync"]["Class"]))
			continue;

		foreach(array("From" => "", "Importance" => 1, "Read" => 0, "Subject" => "", "To" => "", "MessageClass" => "IPM.Note") as $token => $value)
			$data["Email"][$token] = (isset($data["Email"][$token]) ? $data["Email"][$token] : $value);

		foreach(array("LastVerbExecuted" => 0) as $token => $value)
			$data["Email2"][$token] = (isset($data["Email2"][$token]) ? $data["Email2"][$token] : $value);

		$data["Email"]["DateReceived"] = (isset($data["Email"]["DateReceived"]) ? $data["Email"]["DateReceived"] : date("Y-m-d\TH:i:s\Z", filemtime(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $file)));

		$add = false;

		$mime = "...";

		foreach($data["Body"] as $body)
			if(isset($body["Type"]))
				if($body["Type"] == 1)
					$mime = $body["Data"];

		if($data["AirSync"]["Class"] == "Email")
			{
			$class				= $data["AirSync"]["Class"];

			$from				= $data["Email"]["From"];
			$to				= $data["Email"]["To"];
			$date_received			= $data["Email"]["DateReceived"];
			$importance			= $data["Email"]["Importance"];
			$read				= $data["Email"]["Read"];
			$subject			= $data["Email"]["Subject"];

			$status				= (isset($data["Flag"]["Email"]["Status"]) ? $data["Flag"]["Email"]["Status"] : 0);
			$attachments			= (isset($data["file"]) ? 1 : 0);

			$message_class			= $data["Email"]["MessageClass"];
			$last_verb_executed		= $data["Email2"]["LastVerbExecuted"];

			$add = true;
			}

		if($data["AirSync"]["Class"] == "SMS")
			{
			$class				= $data["AirSync"]["Class"];

			$from				= $data["Email"]["From"];
			$to				= $data["Email"]["To"];
			$date_received			= $data["Email"]["DateReceived"];
			$importance			= $data["Email"]["Importance"]; # how can sender determine the importance ???
			$read				= $data["Email"]["Read"];
			$subject			= utf8_encode(substr(utf8_decode($mime) , 0, 80)); # doesn't matter if message is shorter than 80 chars

			$status				= (isset($data["Flag"]["Email"]["Status"]) ? $data["Flag"]["Email"]["Status"] : null);
			$attachments			= 0;

			$message_class			= null;
			$last_verb_executed		= $data["Email2"]["LastVerbExecuted"];

			$add = true;
			}

		if($add)
			{
			// from and to must be changed on outbox
			// name and mail must already be split here
			$from		= str_replace(array("<", ">"), array("&lt;", "&gt;"), $from);
			$to		= str_replace(array("<", ">"), array("&lt;", "&gt;"), $to);

			$date_received	= strtotime($date_received);

			$retval[] = array($date_received, $from, $to, $subject, $read, $status, $server_id, $class, $importance, $attachments, $message_class, $last_verb_executed);
			}
		}

	if(count($retval) > 1)
		rsort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}

function active_sync_web_data_notes($request)
	{
	$retval = array();

	foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		foreach(array("Subject", "LastModifiedDate") as $token)
			$data["Notes"][$token] = (isset($data["Notes"][$token]) ? $data["Notes"][$token] : "...");

		$subject		= $data["Notes"]["Subject"];
		$last_modified_date	= $data["Notes"]["LastModifiedDate"];

		$last_modified_date	= strtotime($last_modified_date);

		$retval[] = array($subject, $server_id, $last_modified_date);
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}

function active_sync_web_data_tasks($request)
	{
	$retval = array();

	foreach(glob(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		foreach(array("DueDate", "Sensitivity", "StartDate", "Subject") as $key)
			$data["Tasks"][$key] = (isset($data["Tasks"][$key]) ? $data["Tasks"][$key] : "");

		$due_date	= $data["Tasks"]["DueDate"];
		$start_date	= $data["Tasks"]["StartDate"];
		$subject	= $data["Tasks"]["Subject"];

		$due_date	= strtotime($due_date);
		$start_date	= strtotime($start_date);

		$retval[] = array($start_date, $due_date, $server_id, $subject);
		}

	if(count($retval) > 1)
		sort($retval);

	$retval = json_encode($retval);

	header("Content-Type: application/json; charset=\"UTF-8\"");
	header("Content-Length: " . strlen($retval));

	print($retval);
	}

function active_sync_web_delete($request)
	{
	$table = array
		(
		"Calendar"	=> "active_sync_web_delete_calendar",
		"Contacts"	=> "active_sync_web_delete_contacts",
		"Email"		=> "active_sync_web_delete_email",
		"Notes"		=> "active_sync_web_delete_notes",
		"Tasks"		=> "active_sync_web_delete_tasks"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		if($default_class == $class)
			if(function_exists($function))
				$retval = $function($request);

	return($retval);
	}

function active_sync_web_delete_calendar($request)
	{
	$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]);

	if(isset($data["Attendees"]))
		if(count($data["Attendees"]) > 0)
			foreach($data["Attendees"] as $attendee_id => $attendee_data)
				{
				$boundary = active_sync_create_guid();

				$description = array
					(
					);

				if(isset($data["Calendar"]["StartTime"]))
					$description[] = "Wann: " . date("d.m.Y H:i:s", strtotime($data["Calendar"]["StartTime"]));

				if(isset($data["Calendar"]["Location"]))
					$description[] = "Wo: " . $data["Calendar"]["Location"];

				$description[] = "*~*~*~*~*~*~*~*~*~*";

				if(isset($data["Body"]))
					foreach($data["Body"] as $body)
						if(isset($body["Type"]))
							if($body["Type"] == 1)
								if(isset($body["Data"]))
									$description[] = $body["Data"];

				$description = implode("\n", $description);

				$mime = array
					(
					);

				if(isset($data["Calendar"]["OrganizerName"]))
					$mime[] = "From: \"" . $data["Calendar"]["OrganizerName"] . "\" <" . $data["Calendar"]["OrganizerEmail"] . ">";
				else
					$mime[] = "From: <" . $data["Calendar"]["OrganizerEmail"] . ">";

				if(isset($attendee_data["Name"]))
					$mime[] = "To: \"" . $attendee_data["Name"] . "\" <" . $attendee_data["Email"] . ">";
				else
					$mime[] = "To: <" . $attendee_data["Email"] . ">";

				if(isset($data["Calendar"]["Subject"]))
					$mime[] = "Subject: Abgesagt: " . $data["Calendar"]["Subject"];
				else
					$mime[] = "Subject: Abgesagt: ";

				$mime[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
				$mime[] = "";
				$mime[] = "--" . $boundary;
				$mime[] = "Content-Type: text/plain; charset=\"utf-8\"";
				$mime[] = "";
				$mime[] = $description;
				$mime[] = "";
				$mime[] = "--" . $boundary;
				$mime[] = "Content-Type: text/calendar; method=CANCEL; name=\"invite.ics\"";
				$mime[] = "";
				$mime[] = "BEGIN:VCALENDAR";
					$mime[] = "METHOD:CANCEL";
					$mime[] = "PRODID:" . active_sync_get_version();
					$mime[] = "VERSION:2.0";
					# VTIMEZONE
					$mime[] = "BEGIN:VEVENT";
						$mime[] = "UID:" . $data["Calendar"]["UID"];

						foreach(array("DTSTAMP" => "DtStamp", "DTSTART" => "StartTime", "DTEND" => "EndTime") as $key => $token)
							if(isset($data["Calendar"][$token]))
								$mime[] = $key . ":" . $data["Calendar"][$token];

						foreach(array("LOCATION" => "Location", "SUMMARY" => "Subject") as $key => $token)
							if(isset($data["Calendar"][$token]))
								$mime[] = $key . ":" . $data["Calendar"][$token];

						$mime[] = "DESCRIPTION:" . $description;

						foreach(array("FALSE" => 0, "TRUE" => 1) as $key => $value)
							if($data["Calendar"]["AllDayEvent"] == $value)
								$mime[] = "X-MICROSOFT-CDO-ALLDAYEVENT:" . $key;

						if(isset($attendee_data["Name"]))
							$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=\"" . $attendee_data["Name"] . "\":MAILTO:" . $attendee_data["Email"];
						else
							$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:MAILTO:" . $attendee_data["Email"];

						if(isset($data["Calendar"]["OrganizerName"]))
							$mime[] = "ORGANIZER;CN=\"" . $data["Calendar"]["OrganizerName"] . "\":MAILTO:" . $data["Calendar"]["OrganizerEmail"];
						else
							$mime[] = "ORGANIZER:MAILTO:" . $data["Calendar"]["OrganizerEmail"];

						$mime[] = "STATUS:CANCELED";
						$mime[] = "TRANSP:OPAQUE";
						$mime[] = "PRIORITY:5";
						$mime[] = "SEQUENCE:0";

					$mime[] = "END:VEVENT";
				$mime[] = "END:VCALENDAR";
				$mime[] = "";
				$mime[] = "--" . $boundary . "--";

				$mime = implode("\n", $mime);

				active_sync_send_mail($request["AuthUser"], $mime);
				}

	$file = ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data";

	if(! file_exists($file))
		$status = 8;
	elseif(! unlink($file))
		$status = 8;
	else
		$status = 1;

	print($status);
	}

function active_sync_web_delete_contacts($request)
	{
	$file = ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data";

	if(! file_exists($file))
		$status = 8;
	elseif(! unlink($file))
		$status = 8;
	else
		$status = 1;

	print($status);
	}

function active_sync_web_delete_email($request)
	{
	$file = ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data";

	if(! file_exists($file))
		$status = 8;
	elseif(! unlink($file))
		$status = 8;
	else
		$status = 1;

	print($status);
	}

function active_sync_web_delete_notes($request)
	{
	$file = ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data";

	if(! file_exists($file))
		$status = 8;
	elseif(! unlink($file))
		$status = 8;
	else
		$status = 1;

	print($status);
	}

function active_sync_web_delete_tasks($request)
	{
	$file = ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data";

	if(! file_exists($file))
		$status = 8;
	elseif(! unlink($file))
		$status = 8;
	else
		$status = 1;

	print($status);
	}

function active_sync_web_edit($request)
	{
	$table = array
		(
		"Calendar"	=> "active_sync_web_edit_calendar",
		"Contacts"	=> "active_sync_web_edit_contacts",
		"Email"		=> "active_sync_web_edit_email",
		"Notes"		=> "active_sync_web_edit_notes",
		"Tasks"		=> "active_sync_web_edit_tasks"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		if($default_class == $class)
			if(function_exists($function))
				$retval = $function($request);

	return($retval);
	}

function active_sync_web_edit_calendar($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_calendar() as $token => $value)
		$data["Calendar"][$token] = (isset($data["Calendar"][$token]) ? $data["Calendar"][$token] : $value);

	foreach(active_sync_get_default_recurrence() as $token => $value)
		$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) ? $data["Recurrence"][$token] : $value);

	foreach(array("Attendees") as $key)
		$data[$key] = (isset($data[$key]) ? $data[$key] : array());

	if(! isset($data["Body"]))
		$data["Body"][] = active_sync_get_default_body();

	foreach($data["Body"] as $body)
		if(isset($body["Type"]))
			if($body["Type"] == 1)
				foreach(active_sync_get_default_body() as $token => $value)
					$data["Body"][0][$token] = (isset($body[$token]) ? $body[$token] : $value);

	$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	$timezone_values = active_sync_get_table_timezone_information();

	$host = active_sync_get_domain();

#	$data["Calendar"]["Reminder"] = (isset($data["Calendar"]["Reminder"]) ? $data["Calendar"]["Reminder"] : ($request["ServerId"] == "" ? $settings["Settings"]["Reminder"] : 0));

#	foreach(array("FirstDayOfWeek" => $settings["Settings"]["FirstDayOfWeek"], "IsLeapMonth" => isset($data["Recurrence"]["MonthOfYear"]) ? $data["Recurrence"]["MonthOfYear"] == 2 ? 1 : 0 : 0) as $key => $value)
#		$data["Recurrence"][$key] = (isset($data["Recurrence"][$key]) ? $data["Recurrence"][$key] : $value);

	if($request["ServerId"] == "")
		{
		$login = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/login.data");

		foreach($login["login"] as $user)
			{
			if($user["User"] != $request["AuthUser"])
				continue;

			$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . ".sync");

			foreach(array("OrganizerName" => $user["DisplayName"], "OrganizerEmail" => $request["AuthUser"] . "@" . $host , "Reminder" => $settings["Settings"]["Reminder"]) as $token => $value)
				if($value != "")
					$data["Calendar"][$token] = $value;

			foreach(array("StartTime" => 0, "EndTime" => 3600) as $token => $value)
				{
				$cookie = (isset($_COOKIE["time_id"]) ? substr($_COOKIE["time_id"], 1) / 1000 : time());

				$data["Calendar"][$token] = date("Ymd\THis\Z", date("U", $cookie) + $value);
				}
			}
		}

	foreach(array("StartTime", "EndTime", "DtStamp") as $token)
		$data["Calendar"][$token] = date("d.m.Y H:i", strtotime($data["Calendar"][$token]) + 7200);

	print("<form>");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");
		print("<input type=\"hidden\" name=\"DtStamp\" value=\"" . $data["Calendar"]["DtStamp"] . "\">");
		print("<input type=\"hidden\" name=\"UID\" value=\"" . $data["Calendar"]["UID"] . "\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Was</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea name=\"Subject\" class=\"xt\" id=\"Subject\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
						print($data["Calendar"]["Subject"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Von</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"StartTime\" value=\"" . $data["Calendar"]["StartTime"] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : true });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Bis</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" id=\"EndTime\" name=\"EndTime\" value=\"" . $data["Calendar"]["EndTime"] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : true });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Zeitzone</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"TimeZone\" class=\"xs\">");
					foreach($timezone_values as $key => $value)
						{
						print("<option value=\"" . $key . "\"" . ($data["Calendar"]["TimeZone"] == $value[0] ? " selected" : "") . ">");
							print($value[1]);
						print("</option>");
						}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Ganzen Tag</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input style=\"border: solid 1px;\" type=\"checkbox\" onchange=\"handle_link({ cmd : 'UpdateAllDayEvent' });\" name=\"AllDayEvent\" value=\"1\" " . ($data["Calendar"]["AllDayEvent"] == 1 ? " checked" : "") . ">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Wo</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea name=\"Location\" name=\"Location\" class=\"xt\" id=\"Location\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
						print($data["Calendar"]["Location"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Beschreibung</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"hidden\" name=\"Body:Type\" value=\"1\">");
					print("<textarea name=\"Body:Data\" class=\"xt\" id=\"Body:Data\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
						print($data["Body"][0]["Data"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Teilnehmer</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"hidden\" name=\"MeetingStatus\" value=\"" . $data["Calendar"]["MeetingStatus"] . "\">");
					print("<input type=\"hidden\" name=\"OrganizerName\" value=\"" . $data["Calendar"]["OrganizerName"] . "\">");
					print("<input type=\"hidden\" name=\"OrganizerEmail\" value=\"" . $data["Calendar"]["OrganizerEmail"] . "\">");

					asort($data["Attendees"], SORT_LOCALE_STRING);

					# what about AttendeeStatus and AttendeeType if we change appointment ???

					print("<table>");
						print("<tr>");
							print("<td>");
								print("<select id=\"Attendees\" name=\"Attendees[]\" ondblclick=\"this.remove(this.selectedIndex);\" size=\"2\" class=\"xs\" style=\"height: 100px;\" multiple>");

									foreach($data["Attendees"] as $id => $attendee)
										{
										print("<option value=\"&quot;" . $attendee["Name"] . "&quot; &lt;" . $attendee["Email"] . "&gt;\">");
											print("&quot;");
												print($attendee["Name"]);
											print("&quot;");
											print(" ");
											print("&lt;");
												print($attendee["Email"]);
											print("&gt;");
										print("</option>");
										}

								print("</select>");
							print("</td>");
						print("</tr>");
						print("<tr>");
							print("<td>");
								print("<input type=\"text\" class=\"xi\" id=\"Attendee\" onfocus=\"options_handle_calendar('Attendee', 'Attendees');\">");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");
		print("</table>");

		active_sync_show_recurrence($data);

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Anzeigen als</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"BusyStatus\" class=\"xs\">");
						foreach(array(0 => "Verfügbar", 1 => "Vorläufig", 2 => "Besetzt", 3 => "Abwesend") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Calendar"]["BusyStatus"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Datenschutz</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Sensitivity\" class=\"xs\">");
						foreach(array(0 => "Standard", 1 => "Öffentlich", 2 => "Privat", 3 => "Vertraulich") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Calendar"]["Sensitivity"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Erinnerung</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Reminder\" class=\"xs\">");
					foreach(array(0 => "Keine", 1 => "1 Minute", 5 => "5 Minuten", 10 => "10 Minuten", 15 => "15 Minuten", 20 => "20 Minuten", 25 => "25 Minuten", 30 => "30 Minuten", 45 => "45 Minuten", 60 => "1 Stunde", 120 => "2 Stunden", 180 => "3 Stunden", 720 => "12 Stunden", 1440 => "24 Stunden", 2880 => "2 Tage", 10080 => "1 Woche") as $key => $value)
						{
						print("<option value=\"" . $key . "\"" . ($data["Calendar"]["Reminder"] == $key ? " selected" : "") . ">");
							print($value);
						print("</option>");
						}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<br>");

		print("<table>");
			print("<tr>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]</td>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

				if($request["ServerId"] != "")
					print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]</td>");

				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}

function active_sync_web_edit_contacts($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_contacts() as $token => $value)
		$data["Contacts"][$token] = (isset($data["Contacts"][$token]) ? $data["Contacts"][$token] : $value);

	foreach(active_sync_get_default_contacts2() as $token => $value)
		$data["Contacts2"][$token] = (isset($data["Contacts2"][$token]) ? $data["Contacts2"][$token] : $value);

	if(! isset($data["Body"]))
		$data["Body"][] = active_sync_get_default_body();

	foreach($data["Body"] as $body)
		if(isset($body["Type"]))
			if($body["Type"] == 1)
				foreach(active_sync_get_default_body() as $token => $value)
					$data["Body"][0][$token] = (isset($body[$token]) ? $body[$token] : $value);

	foreach(array("Categories", "Children") as $key)
		$data[$key] = (isset($data[$key]) ? $data[$key] : array());

	foreach(array("Anniversary", "Birthday") as $key)
		$data["Contacts"][$key] = ($data["Contacts"][$key] == "" ? "" : date("d.m.Y", strtotime($data["Contacts"][$key])));

	foreach(array("Email1Address", "Email2Address", "Email3Address") as $key)
		list($null, $data["Contacts"][$key]) = active_sync_mail_parse_address($data["Contacts"][$key]);

	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td style=\"width: 100%; height: 100%;\" valign=\"top\">");
				print("<form style=\"height: 100%;\">");
					print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
					print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
					print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");
					print("<table style=\"height: 100%;\">");
						print("<tr>");
							print("<td valign=\"top\">");
								print("<table>");
									print("<tr>");
										print("<td style=\"cursor: default;\" id=\"address_tab_b\">");
											# nothing to display yet
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td style=\"cursor: default;\" id=\"address_tab_h\">");
											# nothing to display yet
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td style=\"cursor: default;\" id=\"address_tab_o\">");
											# nothing to display yet
										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
						print("</tr>");
						print("<tr>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
						print("</tr>");
						print("<tr style=\"height: 200px; \">");
							print("<td style=\"vertical-align: top;\">");

								$weight_address = array(0, 0, 0); # Work, Home, Other

								$fields = array(array(0, "b", array("BusinessAddressStreet" => "Straße", "BusinessAddressCity" => "Stadt", "BusinessAddressState" => "Bundesland", "BusinessAddressPostalCode" => "Postleitzahl", "BusinessAddressCountry" => "Land", "BusinessPhoneNumber" => "Telefon", "Business2PhoneNumber" => "Telefon", "BusinessFaxNumber" => "Fax")), array(1, "h", array("HomeAddressStreet" => "Straße", "HomeAddressCity" => "Stadt", "HomeAddressState" => "Bundesland", "HomeAddressPostalCode" => "Postleitzahl", "HomeAddressCountry" => "Land", "HomePhoneNumber" => "Telefon", "Home2PhoneNumber" => "Telefon", "HomeFaxNumber" => "Fax")), array(2, "o", array("OtherAddressStreet" => "Straße", "OtherAddressCity" => "Stadt", "OtherAddressState" => "Bundesland", "OtherAddressPostalCode" => "Postleitzahl", "OtherAddressCountry" => "Land")));

								foreach($fields as $field_data)
									{
									list($weight_id, $page_id, $tokens) = $field_data;

									print("<span id=\"address_page_" . $page_id . "\" style=\"display: none;\">");

										foreach($tokens as $token => $value)
											{
											print("<table>");
												print("<tr>");
													print("<td class=\"field_label\">");
														print($value);
													print("</td>");
													print("<td>");
														print(":");
													print("</td>");
													print("<td>");
														print("<input");
														print(" ");
														print("type=\"text\"");
														print(" ");
														print("name=\"" . $token . "\"");
														print(" ");
														print("class=\"xi\"");
														print(" ");
														print("id=\"" . $token . "\"");

														if(strpos($token, "Address") !== false)
															{
															print(" onfocus=\"suggest_register(this.id, '" . $_GET["CollectionId"] . "', 0);\"");
															}

														if(strpos($token, "Phone") !== false)
															{
															print(" onfocus=\"suggest_register(this.id, '" . $_GET["CollectionId"] . "', 0);\"");
															}

														print(" value=\"" . $data["Contacts"][$token] . "\"");
														print(">");
													print("</td>");
												print("</tr>");
											print("</table>");

											$weight_address[$weight_id] += ($data["Contacts"][$token] != "" ? 1 : 0);
											}

										$weight_address[$weight_id] = 100 / count($tokens) * $weight_address[$weight_id];
									print("</span>");
									}

								natcasesort($weight_address);

								$weight_address = array_keys($weight_address);

								$weight_address = end($weight_address);

							print("</td>");
							print("<td style=\"width: 32px;\">");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("<input type=\"hidden\" id=\"img_data\" name=\"Picture\">");

								print("<table style=\"height: 100%; width: 100%;\">");
									print("<tr>");
										print("<td style=\"width: 165px;\">");
											print("&nbsp;");
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td style=\"height: 100%; text-align: center; border: none;\">");
											# image is stored with 69 x 69 pixels, but we have enough space, so display it in double size
											print("<img style=\"height: 108px;\" class=\"xl\" id=\"img_preview\" onclick=\"handle_link({ cmd : 'PictureLoad' });\" src=\"images/contacts_default_image_add.png\">");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td>");
											print("[");
												print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'PictureLoad' });\">");
													print("Hinzufügen");
												print("</span>");
											print("]");
											print(" ");
											print("[");
												print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'PictureDelete' });\">");
													print("Löschen");
												print("</span>");
											print("]");
										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
						print("</tr>");
						print("<tr>");
							print("<td>");
								print("<div style=\"background-color: #000000; height: 1px;\">");
								print("</div>");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("<div style=\"background-color: #000000; height: 1px;\">");
								print("</div>");
							print("</td>");
						print("</tr>");
						print("<tr style=\"height: 100%;\">");
							print("<td style=\"vertical-align: top;\">");

								foreach(array("Title" => "Namenspräfix", "FirstName" => "Vorname", "MiddleName" => "Zweiter Vorname", "LastName" => "Nachname", "Suffix" => "Namenssuffix", "YomiFirstName" => "Phonetischer Vorname", "YomiLastName" => "Phonetischer Nachname", "Anniversary" => "Jahrestag", "AssistantName" => "...", "AssistnamePhoneNumber" => "...", "Birthday" => "Geburtstag", "CompanyName" => "Firma", "Department" => "Abteilung", "FileAs" => "...", "JobTitle" => "Beruf", "CarPhoneNumber" => "...", "MobilePhoneNumber" => "Mobiltelefon", "OfficeLocation" => "Büro", "PagerNumber" => "Pager", "RadioPhoneNumber" => "Funk", "Spouse" => "Ehepartner", "WebPage" => "Webseite", "Alias" => "...", "WeightedRank" => "...") as $token => $value)
									{
									switch($token)
										{
										case("Anniversary"):
										case("Birthday"):

											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("Title"):
										case("FirstName"):
										case("MiddleName"):
										case("LastName"):
										case("Suffix"):
										case("YomiFirstName"):
										case("YomiLastName"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\" id=\"". $token . "\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\" onchange=\"handle_link({ cmd : 'UpdateFileAs' });\">");
														print("</td>");
														print("<td>");
															# nothing to display yet
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("Spouse"):
										case("CompanyName"):
										case("Department"):
										case("JobTitle"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\" id=\"". $token . "\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("FileAs"):
										case("WeightedRank"):
											print("<input type=\"hidden\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\">");

											break;
										case("RadioPhoneNumber"):
										case("CarPhoneNumber"):
										case("AssistnamePhoneNumber"):
											print("<input type=\"hidden\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\">");

											break;
										case("Alias"):
										case("AssistantName"):
										case("OfficeLocation"):
											print("<input type=\"hidden\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\">");

											break;
										case("MobilePhoneNumber"):
										case("PagerNumber"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("WebPage"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										default:
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										}
									}

								foreach(array("NickName" => "Spitzname", "CustomerId" => "Kundennummer", "GovernmentId" => "...", "ManagerName" => "...", "CompanyMainPhone" => "...", "AccountName" => "...", "MMS" => "...") as $token => $value)
									{
									switch($token)
										{
										case("CustomerId");
										case("NickName");
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts2"][$token] . "\" class=\"xi\" autocomplete=\"off\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("AccountName");
										case("CompanyMainPhone");
										case("GovernmentId");
										case("ManagerName");
										case("MMS");
											print("<input type=\"hidden\" name=\"". $token . "\" value=\"" . $data["Contacts2"][$token] . "\">");

											break;
										default:
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts2"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										}
									}

								print("<table>");
									print("<tr>");
										print("<td class=\"field_label\">");
											print("Memo");
										print("</td>");
										print("<td>:</td>");
										print("<td>");
											print("<input type=\"hidden\" name=\"Body:Type\" value=\"1\">");
											print("<input type=\"hidden\" name=\"Body:EstimatedDataSize\">"); # not stored by device, so ...
											print("<textarea name=\"Body:Data\" class=\"xt\">");
												print($data["Body"][0]["Data"]);
											print("</textarea>");
										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
							print("<td style=\"width: 32px;\">");
								print("&nbsp;");
							print("</td>");
							print("<td style=\"vertical-align: top;\">");
								print("<table style=\"height: 100%; width: 100%;\">");
									print("<tr>");
										print("<td>");
											print("<table>");
												print("<tr>");
													print("<td style=\"cursor: default;\" id=\"contact_tab_e\">");
														# nothing to display yet
													print("</td>");
													print("<td>");
														print("&nbsp;");
													print("</td>");
													print("<td style=\"cursor: default;\" id=\"contact_tab_i\">");
														# nothing to display yet
													print("</td>");
												print("</tr>");
											print("</table>");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");

											$weight_contact = array(0, 0); # email, im

											$fields = array(array(0, "e", "Contacts", array("Email1Address" => "E-Mail-Adresse", "Email2Address" => "E-Mail-Adresse", "Email3Address" => "E-Mail-Adresse")), array(1, "i", "Contacts2", array("IMAddress" => "Instant-Messenger", "IMAddress2" => "Instant-Messenger", "IMAddress3" => "Instant-Messenger")));

											foreach($fields as $field_data)
												{
												list($weight_id, $page_id, $codepage, $tokens) = $field_data;

												print("<span id=\"contact_page_" . $page_id . "\" style=\"display: none;\">");

													foreach($tokens as $token => $value)
														{
														print("<table>");
															print("<tr>");
																print("<td class=\"field_label\">");
																	print($value);
																print("</td>");
																print("<td>");
																	print(":");
																print("</td>");
																print("<td>");
																	print("<input type=\"text\" name=\"". $token . "\" class=\"xi\" value=\"" . $data[$codepage][$token] . "\">");
																print("</td>");
																print("<td>");

																	if($page_id == "e")
																		{
																		print("<img class=\"xl\" onclick=\"handle_link({ cmd : 'Edit' , collection_id : '9002', server_id : '', item_id : '" . $data[$codepage][$token] . "' });\" src=\"images/contacts_list_email_icon_small.png\">");
																		}

																	if($page_id == "i")
																		{
																		print("<img class=\"xl\" onclick=\"handle_link({ cmd : 'IM', item_id : '" . $data[$codepage][$token] . "' });\" src=\"images/contacts_list_im_icon_small.png\">");
																		}

																print("</td>");
															print("</tr>");
														print("</table>");

														$weight_contact[$weight_id] += ($data[$codepage][$token] != "" ? 1 : 0);
														}

													$weight_contact[$weight_id] = 100 / count($tokens) * $weight_contact[$weight_id];
												print("</span>");
												}

											natcasesort($weight_contact);

											$weight_contact = array_keys($weight_contact);

											$weight_contact = end($weight_contact);

										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");
											print("<div style=\"background-color: #000000; height: 1px;\">");
											print("</div>");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td style=\"height: 100%;\">");

											foreach(array(array("Category", "Categories", "Gruppen"), array("Child", "Children", "Kinder")) as $token)
												{
												print("<table style=\"height: 50%; width: 100%;\">");
													print("<tr>");
														print("<td class=\"field_label\" style=\"vertical-align: top;\">");
															print($token[2]);
														print("</td>");
														print("<td style=\"vertical-align: top;\">");
															print(":");
														print("</td>");
														print("<td style=\"height: 100%;\">");
															asort($data[$token[1]], SORT_LOCALE_STRING);

															print("<table style=\"height: 100%; width: 100%\">");
																print("<tr>");
																	print("<td style=\"height: 100%;\">");
																		print("<select id=\"" . $token[1] . "\" name=\"" . $token[1] . "[]\" ondblclick=\"this.remove(this.selectedIndex);\" size=\"2\" style=\"height: 100%; width: 250px;\" multiple>");

																			foreach($data[$token[1]] as $item)
																				{
																				print("<option value=\"" . $item . "\">");
																					print($item);
																				print("</option>");
																				}

																		print("</select>");
																	print("</td>");
																print("</tr>");
																print("<tr>");
																	print("<td>");
																		print("<input type=\"text\" class=\"xi\" id=\"" . $token[0] . "\" onfocus=\"options_handle_contacts('" . $token[0] . "', '" . $token[1] . "');\">");
																	print("</td>");
																print("</tr>");
															print("</table>");
														print("</td>");
													print("</tr>");
												print("</table>");
												}

										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</form>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("&nbsp;");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("<table>");
					print("<tr>");
						print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]</td>");
						print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

						if($request["ServerId"] != "")
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]</td>");

						print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
					print("</tr>");
				print("</table>");
			print("</td>");
		print("</tr>");
	print("</table>");

	print("<span id=\"buffer_address\" style=\"display: none;\">" . $weight_address . "</span>");
	print("<span id=\"buffer_contact\" style=\"display: none;\">" . $weight_contact . "</span>");
	print("<span id=\"buffer_picture\" style=\"display: none;\">" . $data["Contacts"]["Picture"] . "</span>");
	print("<script language=\"JavaScript\">");
	print("var general_data = " . json_encode($data) . ";");
	print("</script>");
	}

function active_sync_web_edit_email($request)
	{
	if($request["ServerId"] != "")
		{
		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]);

		$from		= $data["Email"]["From"];
		$to		= $data["Email"]["To"];
		$date_received	= $data["Email"]["DateReceived"];
		$subject	= (isset($data["Email"]["Subject"]) ? $data["Email"]["Subject"] : "");

		$from		= utf8_decode($from);
		$from		= htmlentities($from);

		$to		= utf8_decode($to);
		$to		= htmlentities($to);

		$subject	= utf8_decode($subject);
		$subject	= htmlentities($subject);

		$mime = "";

		if($request["CollectionId"] == 9003) # drafts what about Email:IsDraft
			{
			foreach($data["Body"] as $body)
				{
				if(isset($body["Type"]))
					if($body["Type"] == 1)
						{
						$quote = array();

						$body["Data"] = wordwrap($body["Data"], 72);

						foreach(explode("\n", $body["Data"]) as $line)
							$quote[] = "" . htmlentities(utf8_decode($line));

						$mime = implode("<br>", $quote);
						}
				}
			}

		if($request["CollectionId"] != 9003) # drafts
			{
			foreach($data["Body"] as $body)
				{
				if(! isset($body["Type"]))
					continue;

				if($body["Type"] == 1)
					{
					$quote = array();

					$quote[] = "";
					$quote[] = "Am Montag, den " . date("d.m.Y, H:i", strtotime($date_received)) . " +0000 schrieb " . $from . ":";

					$body["Data"] = wordwrap($body["Data"], 72);

					foreach(explode("\n", $body["Data"]) as $line)
						$quote[] = "&gt; " . htmlentities(utf8_decode($line));

					# "-------- Ursprüngliche Nachricht --------";
					# "Von: " . htmlentities($from);
					# "Datum: " . date("d.m.Y, H:i", strtotime($date_received)) . " (+0000)"
					# "An: " . htmlentities($to);
					# "Betreff: " . $subject;

					$mime = implode("<br>", $quote);
					}

				if($body["Type"] == 2)
					{
					$quote = array();

					$quote[] = "<br>";
					$quote[] = "<div name=\"quote\" style=\"margin: 10px 5px 5px 10px; padding: 10px 0px 10px 10px; border-left: 2px solid #4080C0; word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space;\">";
						$quote[] = "<div style=\"margin: 0px 0px 10px 0px;\">";
							$quote[] = "<b>Gesendet:</b> " . date("l, d. F Y \u\m H:i \U\h\\r", strtotime($date_received));
							$quote[] = "<br>";
							$quote[] = "<b>Von:</b> " . $from;
							$quote[] = "<br>";
							$quote[] = "<b>An:</b> " . $to;
							$quote[] = "<br>";
							$quote[] = "<b>Betreff:</b> " . $subject;
						$quote[] = "</div>";
						$quote[] = "<div name=\"quoted-content\">";
							$quote[] = $body["Data"];
						$quote[] = "</div>";
					$quote[] = "</div>";

					$mime = implode("", $quote);
					}
				}

			if($request["LongId"] == "F")
				if(active_sync_mail_is_forward($subject))
					$to = "";
				else
					list($to, $subject) = array("", "Fw: " . $subject);

			if($request["LongId"] == "R")
				if(active_sync_mail_is_reply($subject))
					$to		= $from;
				else
					list($to, $subject) = array($from, "Re: " . $subject);
			}

		$body = str_replace(array("\r", "\n", "\""), array("", "", "\\\""), $mime);
		}

	if($request["ServerId"] == "")
		{
		$data = array();

		$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . ".sync");

		$settings["Settings"]["Signature"]	= (isset($settings["Settings"]["Signature"]) ? $settings["Settings"]["Signature"] : "Von " . active_sync_get_version() . " gesendet");
		$settings["Settings"]["Append"]		= (isset($settings["Settings"]["Append"]) ? $settings["Settings"]["Append"] : "");

		$from		= "";
		$to		= "";
		$subject	= "";
		$body		= ($settings["Settings"]["Append"] ? "<br>---<br>" . $settings["Settings"]["Signature"] : ""); # signature ::= "--" <sp> <cr> <lf> ( * 4 (* 80 <char>))

		if($request["ItemId"] != "")
			{
			list($disposition_type, $disposition_data) = explode(":", $request["ItemId"], 2);

			if($disposition_type == "inline")
				{
				list($x_collection_id, $x_server_id, $x_data) = explode(":", $disposition_data, 3);

				$contact = active_sync_get_settings_data($request["AuthUser"], $x_collection_id, $x_server_id);

				$body = (isset($contact["Contacts"]["FileAs"]) ? $contact["Contacts"]["FileAs"] : "") . "<br>" . (isset($contact["Contacts"][$x_data]) ? $contact["Contacts"][$x_data] : "") . "<br>" . $body;
				}

			if($disposition_type == "attachment")
				{
				list($x_collection_id, $x_server_id) = explode(":", $disposition_data, 2);

				# ...
				}

			if($disposition_type == "mail")
				{
				$to = $disposition_data;
				}
			}
		}

	print("<form style=\"height:100%;\">");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");
		print("<input type=\"hidden\" name=\"Draft\" value=\"0\">");
		print("<table style=\"height: 100%; width: 100%;\">");
			print("<tr>");
				print("<td>");
					print("<table style=\"width: 100%;\">");

						foreach(array("To" => "An", "Cc" => "Kopie", "Bcc" => "Blindkopie") as $key => $value)
							{
							print("<tr>");
								print("<td style=\"text-align: right;\">");
									print($value);
								print("</td>");
								print("<td>");
									print(":");
								print("</td>");
								print("<td colspan=\"5\" style=\"text-align: right; width: 100%;\">");
									# search suggest taken from 9009 default contacts folder
									if($key == "To")
										{
										print("<input type=\"text\" name=\"" . $key . "\" id=\"" . $key . "\" onfocus=\"suggest_register(this.id, '9009', 1);\" style=\"width: 100%;\" value=\"" . $to . "\">");
										}
									else
										{
										print("<input type=\"text\" name=\"" . $key . "\" id=\"" . $key . "\" onfocus=\"suggest_register(this.id, '9009', 1);\" style=\"width: 100%;\">");
										}
								print("</td>");
							print("</tr>");
							}

						print("<tr>");
							print("<td style=\"text-align: right;\">");
								print("Betreff");
							print("</td>");
							print("<td>");
								print(":");
							print("</td>");
							print("<td style=\"width: 100%;\">");
								print("<input type=\"text\" name=\"Subject\" value=\"" . $subject . "\" style=\"width: 100%;\">");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("Priorität");
							print("</td>");
							print("<td>");
								print(":");
							print("</td>");
							print("<td>");
								print("<select name=\"Importance\">");

									foreach(array(0 => 0, 1 => 1, 2 => 0) as $importance => $selected)
										{
										print("<option value=\"" . $importance . "\"" . ($selected == 1 ? " selected" : "") . ">");
											print($importance);
										print("</option>");
										}

								print("<select>");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");

			print("<tr>");
				print("<td style=\"height: 100%;\" id=\"mail_content\">");
					print($body);
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("Dateianhänge");
					print(":");
					print(" ");
					print("<select name=\"attachments\" class=\"xs\">");
						if(isset($data["Attachments"]["AirSyncBase"]))
							foreach($data["Attachments"]["AirSyncBase"] as $attachment_id => $attachment_data)
								{
								print("<option value=\"" . $attachment_id . "\">");
									print($attachment_data["DisplayName"]);
								print("</option>");
								}
					print("</select>");
					print(" ");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'AttachmentDelete' });\">");
							print("Löschen");
						print("</span>");
					print("]");
					print(" ");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'AttachmentUpload' });\">");
							print("Hinzufügen");
						print("</span>");
					print("]");

					print("<span id=\"pbe\" style=\"border: solid 1px; height: 18px; float: right; display: none; width: 200px;\">");
						print("<span id=\"pbc\" style=\"background-color: #4080C0; display: block; height: 18px; width: 0px;\">");
						print("</span>");
					print("</span>");

				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("<input type=\"checkbox\" name=\"SaveInSent\" value=\"T\" checked>");
					print(" Im Ordner <b>Gesendet</b> speichern");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("<table>");
						print("<tr>");
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Send' });\">Senden</span>]</td>");
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

							if($request["ServerId"] != "")
								print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Verwerfen</span>]</td>");

							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Draft' });\">Entwurf</span>]</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}

function active_sync_web_edit_notes($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_notes() as $token => $value)
		$data["Notes"][$token] = (isset($data["Notes"][$token]) ? $data["Notes"][$token] : $value);

	if(! isset($data["Body"]))
		$data["Body"][] = active_sync_get_default_body();

	foreach($data["Body"] as $body)
		if(isset($body["Type"]))
			if($body["Type"] == 1)
				foreach(active_sync_get_default_body() as $token => $value)
					$data["Body"][0][$token] = (isset($body[$token]) ? $body[$token] : $value);

	print("<form style=\"height:100%;\" onsubmit=\"return false;\">");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");

		print("<table style=\"height: 100%; width: 100%;\">");
			print("<tr>");
				print("<td>");
					print("<table style=\"width: 100%;\">");
						print("<tr>");
							print("<td>");
								print("Titel");
							print("</td>");
							print("<td>");
								print(":");
							print("</td>");
							print("<td style=\"width: 100%;\">");
								print("<input type=\"text\" name=\"Subject\" value=\"" . $data["Notes"]["Subject"] . "\" style=\"width: 100%;\">");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td style=\"height: 100%;\">");
					print("<input type=\"hidden\" name=\"Body:Type\" value=\"1\">");
					print("<textarea name=\"Body:Data\" style=\"webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box; padding: 8px; font-family: Courier New; font-size: 10pt; width: 100%; height: 100%;\">");
						print($data["Body"][0]["Data"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]");
					print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]");

					if($request["ServerId"] != "")
						print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]");

					print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}

function active_sync_web_edit_tasks($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_tasks() as $token => $value)
		$data["Tasks"][$token] = (isset($data["Tasks"][$token]) ? $data["Tasks"][$token] : $value);

	foreach(active_sync_get_default_recurrence() as $token => $value)
		$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) ? $data["Recurrence"][$token] : $value);

	if(! isset($data["Body"]))
		$data["Body"][] = active_sync_get_default_body();

	foreach($data["Body"] as $body)
		if(isset($body["Type"]))
			if($body["Type"] == 1)
				foreach(active_sync_get_default_body() as $token => $value)
					$data["Body"][0][$token] = (isset($body[$token]) ? $body[$token] : $value);

	$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	foreach(active_sync_get_default_settings() as $key => $val)
		$settings["Settings"][$key] = (isset($_POST[$key]) ? $_POST[$key] : "");

	foreach(array("FirstDayOfWeek" => $settings["Settings"]["FirstDayOfWeek"], "IsLeapMonth" => isset($data["Recurrence"]["MonthOfYear"]) ? $data["Recurrence"]["MonthOfYear"] == 2 ? 1 : 0 : 0) as $token => $value)
		$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) ? $data["Recurrence"][$token] : $value);

	foreach(array("StartDate", "DueDate", "DateCompleted") as $token)
		$data["Tasks"][$token] = date("d.m.Y", strtotime($data["Tasks"][$token]));

	foreach(array("ReminderTime") as $token)
		$data["Tasks"][$token] = date("d.m.Y H:i", strtotime($data["Tasks"][$token]));

	print("<form>");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Was</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea name=\"Subject\" class=\"xt\">");
						print($data["Tasks"]["Subject"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Von</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"StartDate\" value=\"" . $data["Tasks"]["StartDate"] . "\" class=\"xi\" maxlength=\"10\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Bis</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"DueDate\" value=\"" . $data["Tasks"]["DueDate"] . "\" class=\"xi\" maxlength=\"10\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Erledigt");
				print("</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"DateCompleted\" value=\"" . $data["Tasks"]["DateCompleted"] . "\" class=\"xi\" maxlength=\"10\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
				print("</td>");
				print("<td>");
					print("<input onchange=\"handle_link({ cmd : 'ToggleComplete' });\" type=\"checkbox\" name=\"Complete\" value=\"1\" " . ($data["Tasks"]["Complete"] == 1 ? " checked" : "") . ">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Beschreibung</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea class=\"xt\" name=\"Body:Data\">");
						print($data["Body"][0]["Data"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		active_sync_show_recurrence($data);

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Datenschutz</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Sensitivity\" class=\"xs\">");
						foreach(array(0 => "Standard", 1 => "Öffentlich", 2 => "Privat", 3 => "Vertraulich") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Tasks"]["Sensitivity"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Priorität</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Importance\" class=\"xs\">");
						foreach(array(0 => "Niedrig", 1 => "Mittel", 2 => "Hoch") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Tasks"]["Importance"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Erinnerung</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"ReminderTime\" value=\"" . $data["Tasks"]["ReminderTime"] . "\" class=\"xi\" maxlength=\"19\" onclick=\"popup_date({ target : this, cmd : 'init', time : true });\">");
				print("</td>");
				print("<td>");
					print("<input onchange=\"handle_link({ cmd : 'ToggleReminderSet' });\" style=\"border: solid 1px;\" type=\"checkbox\" name=\"ReminderSet\" value=\"1\" " . ($data["Tasks"]["ReminderSet"] == 1 ? " checked" : "") . ">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<br>");

		print("<table>");
			print("<tr>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]</td>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

				if($request["ServerId"] != "")
					{
					print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]</td>");
					}

				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}

function active_sync_web_list($request)
	{
	$table = array
		(
		"Calendar"	=> "active_sync_web_list_calendar",
		"Contacts"	=> "active_sync_web_list_contacts",
		"Email"		=> "active_sync_web_list_email",
		"Notes"		=> "active_sync_web_list_notes",
		"Tasks"		=> "active_sync_web_list_tasks"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		if($default_class == $class)
			if(function_exists($function))
				$retval = $function($request);

	return($retval);
	}

function active_sync_web_list_calendar($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td>");
				print("<table>");
					print("<tr>");
						print("<td>Ansicht</td>");
						print("<td>:</td>");
						print("<td>");
							print("<select id=\"view\" onchange=\"handle_link({ cmd : 'CalendarSelect' });\">");

							foreach(array("a" => "Agenda", "d" => "Tag", "w" => "Woche", "m" => "Monat", "y" => "Jahr") as $key => $value)
								printf("<option value=\"%s\">%s</option>", $key, $value);
							print("</select>");
						print("</td>");
						print("<td>&nbsp;</td>");
						print("<td><input type=\"button\" onclick=\"handle_link({ cmd : 'CalendarJumpToNow' });\" value=\"Heute\"></td>");
					print("</tr>");
				print("</table>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>&nbsp;</td>");
		print("</tr>");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<span id=\"search_result\"></span>");
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_web_list_contacts($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td>");
				print("<table>");
					print("<tr>");
						print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Edit', server_id : '' });\">Hinzufügen</span>]</td>");
						print("<td style=\"width: 50px;\">&nbsp;</td>");
						print("<td>Suche nach</td>");
						print("<td>:</td>");
						print("<td><input style=\"width: 150px;\" id=\"search_name\" type=\"text\"\"></td>");
						print("<td style=\"width: 50px;\">&nbsp;</td>");
						print("<td><span class=\"span_link\" onclick=\"handle_link({ cmd : 'Category' });\">Gruppe</span></td>");
						print("<td>:</td>");
						print("<td>");
							print("<select id=\"search_category\" style=\"width: 150px;\"\">");

								foreach(array("Alle" => "*", "Nicht zugewiesen" => "") as $key => $value)
									printf("<option value=\"%s\">%s</option>", $value, $key);

								$categories = active_sync_get_categories_by_collection_id($request["AuthUser"], $request["CollectionId"]);

								foreach($categories as $category => $count)
									{
									if($category == "*")
										continue;

									printf("<option value=\"%s\">%s</option>", $category, $category);
									}
							print("</select>");
						print("</td>");
					print("</tr>");
				print("</table>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>&nbsp;</td>");
		print("</tr>");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<table style=\"height: 100%; width: 100%;\">");
					print("<tr>");
						print("<td style=\"height: 100%; width: 32px;\">");
							print("<table style=\"height: 100%; width: 32px;\">");
								$m = "#ABCDEFGHIJKLMNOPQRSTUVWXYZ";

								for($i = 0; $i < strlen($m); $i ++)
									printf("<tr><td class=\"span_link\" style=\"border: solid 1px; border: solid 1px; text-align: center;\" onclick=\"contact_scroll_to('LETTER_%s', 'touchscroll_div');\">%s</td></tr>", $m[$i], $m[$i]);
							print("</table>");
						print("</td>");
						print("<td style=\"height: 100%;\">");
							print("<div class=\"touchscroll_outer\">");
								print("<div class=\"touchscroll_inner\" id=\"touchscroll_div\">");
									print("<span id=\"search_result\"></span>");
								print("</div>");
							print("</div>");
						print("</td>");
					print("</tr>");
				print("</table>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>&nbsp;</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . ".sync");

				print("<span id=\"search_count\">0</span>");
				print(" ");
				print("Kontakte" . (isset($settings["Settings"]["PhoneOnly"]) ? " mit Telefonnummern " : " ") . "werden angezeigt."); # cu numere de telefon
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_web_list_email($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<div class=\"touchscroll_outer\">");
					print("<div class=\"touchscroll_inner\" id=\"touchscroll_div\">");
						print("<span id=\"search_result\"></span>");
					print("</div>");
				print("</div>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>&nbsp;</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Edit', server_id : '', item_id : '', long_id : '' });\">Verfassen</span>]");
				print(" ");
				print("<span id=\"delete_selected\" style=\"display: none;\">");
					print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteMultipleConfirm' });\">Löschen</span>]");
				print("</span>");
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_web_list_notes($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<div class=\"touchscroll_outer\">");
					print("<div class=\"touchscroll_inner\" id=\"touchscroll_div\">");
						print("<span id=\"search_result\">");
						print("</span>");
					print("</div>");
				print("</div>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("&nbsp;");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("[");
					print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Edit', server_id : '' });\">");
						print("Hinzufügen");
					print("</span>");
				print("]");
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_web_list_tasks($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<div class=\"touchscroll_outer\">");
					print("<div class=\"touchscroll_inner\" id=\"touchscroll_div\">");
						print("<span id=\"search_result\"></span>");
					print("</div>");
				print("</div>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>&nbsp;</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Edit', server_id : '' });\">Hinzufügen</span>]");
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_web_print($request)
	{
	$table = array
		(
		"Email" => "active_sync_web_print_email"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		if($default_class == $class)
			if(function_exists($function))
				$retval = $function($request);

	return($retval);
	}

function active_sync_web_print_email($request)
	{
	$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]);

	$retval = "";

	if(isset($data["Body"]))
		foreach($data["Body"] as $body)
			if(! isset($body["Data"]))
				$retval = "";
			elseif(! isset($body["Type"]))
				$retval = "";
			elseif($body["Type"] == 1) # text
				$retval = $body["Data"];
			elseif($body["Type"] == 2) # html
				$retval = $body["Data"];
			else
				$retval = "";

	$file = "/tmp/" . active_sync_create_guid();

	file_put_contents($file, $retval);

	exec("lpr " . $file);

	unlink($file);

	print(1);
	}

function active_sync_web_save($request)
	{
	$table = array
		(
		"Calendar"	=> "active_sync_web_save_calendar",
		"Contacts"	=> "active_sync_web_save_contacts",
		"Email"		=> "active_sync_web_save_email",
		"Notes"		=> "active_sync_web_save_notes",
		"Tasks"		=> "active_sync_web_save_tasks"
		);

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		if($default_class == $class)
			if(function_exists($function))
				$retval = $function($request);

	return($retval);
	}

function active_sync_web_save_calendar($request)
	{
	$data = array();

	foreach(active_sync_get_default_calendar() as $token => $default_value)
		if(isset($_POST[$token]))
			if(strlen($_POST[$token]) > 0)
				$data["Calendar"][$token] = $_POST[$token];

	$body = array();

	foreach(active_sync_get_default_body() as $token => $value)
		if(isset($_POST["Body:" . $token]))
			if(strlen($_POST["Body:" . $token]) > 0)
				$body[$token] = $_POST["Body:" . $token];

	if(isset($body["Type"]))
		if($body["Type"] == 1)
			if(isset($body["Data"]))
				if(strlen($body["Data"]) > 0)
					$body["Body"][] = $body;

	if(! isset($_POST["Attendees"]))
		$data["Calendar"]["MeetingStatus"] = 0;
	else
		{
		$data["Calendar"]["MeetingStatus"] = 1;

		foreach($_POST["Attendees"] as $attendee_id => $attendee_data)
			{
			list($attendee_name, $attendee_mail) = active_sync_mail_parse_address($attendee_data);

			$temp = array();

			if($attendee_name != "")
				$temp["Name"] = $attendee_name;

			if($attendee_mail != "")
				$temp["Email"] = $attendee_mail;

			if(count($temp) > 0)
				{
				$temp["AttendeeType"] = 1;

				$data["Attendees"][] = $temp;
				}
			}
		}

	$fields = array(0x02, 0x02, 0x18, 0x13, 0x00, 0x1C, 0x17, "WeekOfMonth", "DayOfWeek", "MonthOfYear", "DayOfMonth");

	for($i = 0; $i < 4; $i ++)
		{
		$field = $fields[$i + 7];

		if((($fields[$_POST["Recurrence:Type"]] >> $i) & 0x01) == 0x00)
			continue;

		if($_POST["Recurrence:" . $field] == "")
			continue;

		$data["Recurrence"][$field] = $_POST["Recurrence:" . $field];
		}

	if((($_POST["Recurrence:Type"] == 3) || ($_POST["Recurrence:Type"] == 6)) && ($_POST["Recurrence:DayOfWeek"] == 127))
		unset($data["Recurrence"]["WeekOfMonth"]);

	if($_POST["Recurrence:Type"] != 4)
		foreach(array("Type", "Occurrences", "Interval", "Until", "CalendarType", "IsLeapMonth", "FirstDayOfWeek") as $token)
			if($_POST["Recurrence:" . $token] != "")
				$data["Recurrence"][$token] = $_POST["Recurrence:" . $token];

	foreach(array("StartTime", "EndTime", "DtStamp") as $token)
		$data["Calendar"][$token] = date("Ymd\THis\Z", strtotime($data["Calendar"][$token]) - 7200); # time of appointment - timezone of appointment - timezone of server

	if(isset($data["Calendar"]["TimeZone"]))
		{
		$timezone_values = active_sync_get_table_timezone_information();

		$data["Calendar"]["TimeZone"] = $timezone_values[$data["Calendar"]["TimeZone"]][0];
		}

	if(isset($data["Attendees"]))
		foreach($data["Attendees"] as $attendee_id => $attendee_data)
			{
			$boundary = active_sync_create_guid();

			$description = array();

			$description[] = "Wann: " . date("d.m.Y H:i:s", strtotime($data["Calendar"]["StartTime"]));

			if(isset($data["Calendar"]["Location"]))
				$description[] = "Wo: " . $data["Calendar"]["Location"];

			$description[] = "*~*~*~*~*~*~*~*~*~*";

			if(isset($data["Body"]["Data"]))
				$description[] = $data["Body"]["Data"];

			$mime = array();

			if(isset($data["Calendar"]["OrganizerName"]))
				$mime[] = "From: <" . $data["Calendar"]["OrganizerEmail"] . ">";
			else
				$mime[] = "From: \"" . $data["Calendar"]["OrganizerEmail"] . "\" <" . $data["Calendar"]["OrganizerEmail"] . ">";

			if(isset($attendee_data["Name"]))
				$mime[] = "To: <" . $attendee_data["Email"] . ">";
			else
				$mime[] = "From: \"" . $attendee_data["Name"] . "\" <" . $attendee_data["Email"] . ">";

			if($request["ServerId"] == "")
				{
				if(isset($data["Calendar"]["Subject"]))
					$mime[] = "Subject: " . $data["Calendar"]["Subject"];
				}
			else
				{
				if(isset($data["Calendar"]["Subject"]))
					$mime[] = "Subject: Aktualisiert: " . $data["Calendar"]["Subject"];
				else
					$mime[] = "Subject: Aktualisiert: ";
				}

			$mime[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/plain; charset=\"utf-8\"";
			$mime[] = "";
			$mime[] = implode("\n", $description);
			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/calendar; method=REQUEST; name=\"invite.ics\"";
			$mime[] = "";
			$mime[] = "BEGIN:VCALENDAR";
				$mime[] = "METHOD:REQUEST";
				$mime[] = "PRODID:" . active_sync_get_version();
				$mime[] = "VERSION:2.0";
				# VTIMEZONE
				$mime[] = "BEGIN:VEVENT";
					$mime[] = "UID:" . $data["Calendar"]["UID"];

					foreach(array("DTSTAMP" => "DtStamp", "DTSTART" => "StartTime", "DTEND" => "EndTime") as $key => $token)
						$mime[] = $key . ":" . date("Y-m-d\TH:i:s\Z", strtotime($data["Calendar"][$token]));

					foreach(array("LOCATION" => "Location", "SUMMARY" => "Subject") as $key => $token)
						{
						if(isset($data["Calendar"][$token]))
							continue;

						$mime[] = $key . ": " . $data["Calendar"][$token];
						}

					$mime[] = "DESCRIPTION:" . implode("\\n", $description);

					foreach(array("FALSE" => 0, "TRUE" => 1) as $key => $value)
						{
						if($data["Calendar"]["AllDayEvent"] != $value)
							continue;

						$mime[] = "X-MICROSOFT-CDO-ALLDAYEVENT:" . $value;
						}

					if(isset($attendee_data["Name"]))
						$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=\"" . $attendee_data["Name"] . "\":MAILTO:" . $attendee_data["Email"];
					else
						$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:MAILTO:" . $attendee_data["Email"];

					if(isset($data["Calendar"]["OrganizerName"]))
						$mime[] = "ORGANIZER;CN=\"" . $data["Calendar"]["OrganizerName"] . "\":MAILTO:" . $data["Calendar"]["OrganizerEmail"];
					else
						$mime[] = "ORGANIZER:MAILTO:" . $data["Calendar"]["OrganizerEmail"];

					$mime[] = "STATUS:CONFIRMED";
					$mime[] = "TRANSP:OPAQUE";
					$mime[] = "PRIORITY:5";
					$mime[] = "SEQUENCE:0";

					if(isset($data["Calendar"]["Reminder"]))
						{
						$mime[] = "BEGIN:VALARM";
							$mime[] = "ACTION:DISPLAY";
							$mime[] = "DESCRIPTION:REMINDER";
							$mime[] = "TRIGGER:-PT" . $data["Calendar"]["Reminder"] . "M";
						$mime[] = "END:VALARM";
						}

				$mime[] = "END:VEVENT";
			$mime[] = "END:VCALENDAR";
			$mime[] = "";
			$mime[] = "--" . $boundary . "--";

			$mime = implode("\n", $mime);

			active_sync_send_mail($request["AuthUser"], $mime);
			}

	if($request["ServerId"] == "")
		$request["ServerId"] = active_sync_create_guid_filename($request["AuthUser"], $request["CollectionId"]);

	active_sync_put_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"], $data);

	print(1);
	}

function active_sync_web_save_contacts($request)
	{
	$data = array();

	foreach(active_sync_get_default_contacts() as $token => $default_value)
		if(isset($_POST[$token]))
			if(strlen($_POST[$token]) > 0)
				$data["Contacts"][$token] = $_POST[$token];

	foreach(active_sync_get_default_contacts2() as $token => $default_value)
		if(isset($_POST[$token]))
			if(strlen($_POST[$token]) > 0)
				$data["Contacts2"][$token] = $_POST[$token];

	$body = array();

	foreach(active_sync_get_default_body() as $token => $value)
		if(isset($_POST["Body:" . $token]))
			if(strlen($_POST["Body:" . $token]) > 0)
				$body[$token] = $_POST["Body:" . $token];

	if(isset($body["Type"]))
		if($body["Type"] == 1)
			if(isset($body["Data"]))
				if(strlen($body["Data"]) > 0)
					$data["Body"][] = $body;

	foreach(array("Categories", "Children") as $token)
		if(isset($_POST[$token]))
			$data[$token] = $_POST[$token]; # !!! ARRAY

	foreach(array("Anniversary", "Birthday") as $token)
		if(isset($data["Contacts"][$token]))
			$data["Contacts"][$token] = date("Y-m-d\TH:i:s\Z", strtotime($data["Contacts"][$token]));

	foreach(array("Email1Address", "Email2Address", "Email3Address") as $token)
		if(isset($data["Contacts"]["FileAs"]))
			if(isset($data["Contacts"][$token]))
				$data["Contacts"][$token] = "\"" . $data["Contacts"]["FileAs"]  . "\" <" . $data["Contacts"][$token] . ">";

	if($request["ServerId"] == "")
		$request["ServerId"] = active_sync_create_guid_filename($request["AuthUser"], $request["CollectionId"]);

	active_sync_put_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"], $data);

	print(1);
	}

function active_sync_web_save_email($request)
	{
	$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/login.data");

	foreach($settings["login"] as $user)
		{
		if($user["User"] != $request["AuthUser"])
			continue;

		break;
		}

	$from = $user["User"] . "@" . active_sync_get_domain();

	$from = ($user["DisplayName"] ? "\"" . $user["DisplayName"] . "\" <" . $from . ">" : $from);

	$to		= $_POST["To"];				# not available via Request
	$cc		= $_POST["Cc"];				# not available via Request
	$bcc		= $_POST["Bcc"];				# not available via Request
	$subject	= $_POST["Subject"];				# not available via Request
	$importance	= $_POST["Importance"];			# not available via Request

	$draft			= $_POST["Draft"];

	$body_p			= $_POST["inhalt"];				# not available via Request

	$body_p			= active_sync_mail_convert_html_to_plain($body_p);

	$body_h			= $_POST["inhalt"];				# not available via Request

	$importance_values	= array(0 => "Low", 1 => "Normal", 2 => "High");		# low number = low priority (0, 1, 2)
	$priority_values	= array(0 => "5 (Low)", 1 => "3 (Normal)", 2 => "1 (High)");	# low number = high priority (5, 3, 1)

	$boundary		= active_sync_create_guid();

	$body_m = array();

	$body_m[] = "From: " . $from;
	$body_m[] = "To: " . $to;

	if(strlen($cc) != 0)
		$body_m[] = "Cc: " . $cc;

	if(strlen($bcc) != 0)
		$body_m[] = "Bcc: " . $bcc;

	$body_m[] = "Date: " . date("r");
#		$body_m[] = "Content-Type: text/html; charset=\"UTF-8\"";
#		$body_m[] = "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"";
	$body_m[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
	$body_m[] = "Importance: " . $importance_values[$importance];
	$body_m[] = "MIME-Version: 1.0";
	$body_m[] = "X-Priority: " . $priority_values[$importance];
	$body_m[] = "X-Mailer: " . active_sync_get_version();
	$body_m[] = "";
	$body_m[] = "--" . $boundary;
	$body_m[] = "Content-Type: text/plain; charset=\"UTF-8\"";
	$body_m[] = "";
	$body_m[] = $body_p;
	$body_m[] = "--" . $boundary;
	$body_m[] = "Content-Type: text/html; charset=\"UTF-8\"";
	$body_m[] = "";
	$body_m[] = $body_h;

#		foreach($attachments as $id => $attachment)
#			{
#			$body_m[] = "--" . $boundary;
#			$body_m[] = "Content-Type: " . $attachment["ContentType"] . "; name=\"" . $attachment["DisplayName"] . "\"";
#			$body_m[] = "Content-Disposition: attachment; filename=\"" . $attachment["AirSyncBase"]["DisplayName"] . "\"";
#			$body_m[] = $attachment["AirSyncBase"]["Data"]
#			}

	$body_m[] = "--" . $boundary . "--";

	$body_m = implode("\n", $body_m);

	$data = array
		(
		"AirSync" => array
			(
			"Class" => "Email"
			),
		"Email" => array
			(
			"From" => $from,
			"To" => $to,
			"Cc" => $cc,
			"Importance" => $importance,
			"Subject" => $subject,
			"DateReceived" => date("Y-m-d\TH:i:s\Z", date("U")),
			"Read" => 1,
			"ContentClass" => "urn:content-classes:message",
			"MessageClass" => "IPM.Note"
			),
		"Body" => array
			(
			array
				(
				"Type" => 1,
				"EstimatedDataSize" => strlen($body_p),
				"Data" => $body_p
				),
			array
				(
				"Type" => 2,
				"EstimatedDataSize" => strlen($body_h),
				"Data" => $body_h
				),
			array
				(
				"Type" => 4,
				"EstimatedDataSize" => strlen($body_m),
				"Data" => $body_m
				)
			)
		);

	if($bcc != "")
		$data["Email2"]["ReceivedAsBcc"] = 1;

	if($draft == 0)
		{
		list($t_name, $t_mail) = active_sync_mail_parse_address($to);
		list($f_name, $f_mail) = active_sync_mail_parse_address($from);

		if(strlen($to) > 0)
			{
			$recipient_is_phone = active_sync_get_is_phone($t_mail);

			if($recipient_is_phone == 0)
				active_sync_send_mail($request["AuthUser"], $body_m);

			if($recipient_is_phone == 1)
				{
				$x_name = $f_name;
				$x_mail = $f_mail;

				$settings = active_sync_get_settings_user($request["AuthUser"]);

				if(isset($settings["DisplayName"]))
					$x_name = $settings["DisplayName"];

				if(isset($settings["MobilePhone"]))
					$x_mail = $settings["MobilePhone"];

				$devices = active_sync_get_devices_by_user($request["AuthUser"]);

				foreach($devices as $device)
					{
					$settings = active_sync_get_settings(ACTIVE_SYNC_DAT_DIR . "/" . $request["AuthUser"] . "/" .  $device . ".sync");

					if(! isset($settings["DeviceInformation"]["EnableOutboundSMS"]))
						continue;

					if(! isset($settings["DeviceInformation"]["PhoneNumber"]))
						continue;

					$x_mail = $settings["DeviceInformation"]["PhoneNumber"];

					break;
					}

				$data = array
					(
					"AirSync" => array
						(
						"Class" => "SMS"
						),
					"Email" => array
						(
						"DateReceived" => date("Y-m-d\TH:i:s\Z", date("U")),
						"Read" => 1,
						"To" => ($t_name ? "\"" . $t_name . "\" " : "") . "[MOBILE: " . $t_mail . "]",
						"From" => ($x_name ? "\"" . $x_name . "\" " : "") . "[MOBILE: " . $x_mail . "]"
						),
					"Body" => array
						(
						array
							(
							"Type" => 1,
							"EstimatedDataSize" => strlen($body_p),
							"Data" => $body_p
							)
						)
					);

				$user = $request["AuthUser"];
				$collection_id = active_sync_get_collection_id_by_type($user, 6); # Outbox
				$server_id = active_sync_create_guid_filename($user, $collection_id);

				active_sync_put_settings_data($user, $collection_id, $server_id, $data);
				}
			}

		if($request["SaveInSent"] == "T")
			{
			$user = $request["AuthUser"];
			$collection_id = active_sync_get_collection_id_by_type($user, 5); # Sent Items
			$server_id = active_sync_create_guid_filename($user, $collection_id);

			active_sync_put_settings_data($user, $collection_id, $server_id, $data);
			}
		}

	if($draft == 1)
		{
		$user		= $request["AuthUser"];
		$collection_id	= active_sync_get_collection_id_by_type($user, 3); # Drafts
		$server_id	= ($request["ServerId"] ? $request["ServerId"] : active_sync_create_guid_filename($user, $collection_id));

		$reference = 0;

		foreach(scandir(ACTIVE_SYNC_DAT_DIR . "/../web/temp") as $file)
			{
			if(($file == ".") || ($file == ".."))
				continue;

			$body = file_get_contents(ACTIVE_SYNC_DAT_DIR . "/../web/temp/" . $file);

			unlink(ACTIVE_SYNC_DAT_DIR . "/../web/temp/" . $file);

			$data["Attachment"]["AirSyncBase"][$reference] = array
				(
				"DisplayName" => $file,
				"FileReference" => $server_id . ":" . $reference,
				"Method" => 1,
				"EstimatedDataSize" => strlen($body),
				"ContentId" => "xxx",
				"IsInline" => 0
				);

			$data["File"][$reference] = array
				(
				"ContentType" => "",
				"Data" => base64_encode($body)
				);

			$reference ++;
			}

		active_sync_put_settings_data($user, $collection_id, $server_id, $data);
		}

	print(1);
	}

function active_sync_web_save_notes($request)
	{
	$data = array();

	foreach(active_sync_get_default_notes() as $token => $value)
		if(isset($_POST[$token]))
			if(strlen($_POST[$token]) > 0)
				$data["Notes"][$token] = $_POST[$token];

	$body = array();

	foreach(active_sync_get_default_body() as $token => $value)
		if(isset($_POST["Body:" . $token]))
			if(strlen($_POST["Body:" . $token]) > 0)
				$body[$token] = $_POST["Body:" . $token];

	if(isset($body["Type"]))
		if($body["Type"] == 1)
			if(isset($body["Data"]))
				if(strlen($body["Data"]) > 0)
					$body["Body"][] = $body;

	$data["Notes"]["LastModifiedDate"] = date("Ymd\THis\Z");

	if($request["ServerId"] == "")
		$request["ServerId"] = active_sync_create_guid_filename($request["AuthUser"], $request["CollectionId"]);

	active_sync_put_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"], $data);

	print(1);
	}

function active_sync_web_save_tasks($request)
	{
	$data = array();

	foreach(active_sync_get_default_tasks() as $token => $default_value)
		if(isset($_POST[$token]))
			if(strlen($_POST[$token]) > 0)
				$data["Tasks"][$token] = $_POST[$token];

	$body = array();

	foreach(active_sync_get_default_body() as $token => $value)
		if(isset($_POST["Body:" . $token]))
			if(strlen($_POST["Body:" . $token]) > 0)
				$body[$token] = $_POST["Body:" . $token];

	if(isset($body["Type"]))
		if($body["Type"] == 1)
			if(isset($body["Data"]))
				if(strlen($body["Data"]) > 0)
					$body["Body"][] = $body;

	# 0x01 WeekOfMonth
	# 0x02 DayOfWeek
	# 0x04 MonthOfYear
	# 0x08 DayOfMonth

	$fields = array(0x02, 0x02, 0x18, 0x13, 0x00, 0x1C, 0x17, "WeekOfMonth", "DayOfWeek", "MonthOfYear", "DayOfMonth");

	for($i = 0; $i < 4; $i ++)
		{
		$field = $fields[$i + 7];

		if((($fields[$_POST["Recurrence:Type"]] >> $i) & 0x01) == 0x00)
			continue;

		if($_POST["Recurrence:" . $field] == "")
			continue;

		$data["Recurrence"][$field] = $_POST["Recurrence:" . $field];
		}

	if((($_POST["Recurrence:Type"] == 3) || ($_POST["Recurrence:Type"] == 6)) && ($_POST["Recurrence:DayOfWeek"] == 127))
		unset($data["Recurrence"]["WeekOfMonth"]);

	if($_POST["Recurrence:Type"] != 4)
		{
		foreach(array("Type", "Occurrences", "Interval", "Until", "CalendarType", "IsLeapMonth", "FirstDayOfWeek") as $key)
			{
			if($_POST["Recurrence:" . $key] == "")
				continue;

			$data["Recurrence"][$key] = $_POST["Recurrence:" . $key];
			}

		if(($data["Recurrence"]["Until"] != "") && ($data["Recurrence"]["Occurrences"] != ""))
			unset($data["Recurrence"]["Until"]);
		}

	if($request["ServerId"] == "")
		$request["ServerId"] = active_sync_create_guid_filename($request["AuthUser"], $request["CollectionId"]);

	active_sync_put_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"], $data);

	print(1);
	}
?>
