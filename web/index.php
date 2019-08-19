<?
chdir(__DIR__);

include_once("../active_sync_kern.php");

################################################################################
# ...
################################################################################

if(defined("WEB_DIR") === false)
	{
	die("WEB_DIR is not defined. have you included active_sync_kern.php before? WEB_DIR is needed to provide access to user data.");
	}
elseif(file_exists(WEB_DIR . "/.htaccess") === false)
	{
	$data = array();

	$data[] = "<IfModule mod_deflate.c>";
	$data[] = "\tAddOutputFilterByType DEFLATE application/json";
	$data[] = "\tAddOutputFilterByType DEFLATE image/png";
	$data[] = "\tAddOutputFilterByType DEFLATE text/event-stream";
	$data[] = "\tAddOutputFilterByType DEFLATE text/javascript";
	$data[] = "</IfModule>";

	file_put_contents(WEB_DIR . "/.htaccess", implode("\n", $data));
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
	################################################################################
	# set default value
	################################################################################

	$Request[$key] = $value;

	################################################################################
	# GET
	################################################################################

	if($_SERVER["REQUEST_METHOD"] == "GET")
		{
		$Request[$key] = (isset($_GET[$key]) === false ? $Request[$key] : $_GET[$key]);
		}

	################################################################################
	# POST
	################################################################################

	if($_SERVER["REQUEST_METHOD"] == "POST")
		{
		$Request[$key] = (isset($_POST[$key]) === false ? $Request[$key] : $_POST[$key]);
		}
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
elseif(file_exists(DAT_DIR . "/login.data") === false)
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
		if(is_dir(DAT_DIR) === false)
			mkdir(DAT_DIR, 0777, true);

		if(file_exists(DAT_DIR . "/login.data") === false)
			{
			active_sync_put_settings_login(array("login" => array(0 => array("User" => $Request["User"], "Pass" => $Request["Pass"], "IsAdmin" => "T"))));

			active_sync_folders_init($Request["User"]);
			}

		header("Location: index.php");
		}
	else
		header("Location: index.php");
	}
elseif(active_sync_get_is_identified($Request) == 0)
	{
	header("HTTP/1.1 401 Unauthorized");
	header("WWW-Authenticate: basic realm=\"ActiveSync\"");

	html_open();
		print("Zugriff nicht gestattet");
	html_close();
	}
elseif($Request["Cmd"] == "")
	{
	if(active_sync_get_is_identified($Request) == 0)
		{
		header("HTTP/1.1 401 Unauthorized");
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
	$includes = array();

	$includes[] = "calendar";
	$includes[] = "contact";
	$includes[] = "email";
	$includes[] = "notes";
	$includes[] = "tasks";

	$includes[] = "category";
	$includes[] = "device";
	$includes[] = "folder";
	$includes[] = "menu";
	$includes[] = "oof";
	$includes[] = "policy";
	$includes[] = "rights";
	$includes[] = "service";
	$includes[] = "settings";
	$includes[] = "user";

	foreach($includes as $section)
		{
		$file = WEB_DIR . "/includes.php/index_" . $section . ".php";

		if(file_exists($file))
			include_once($file);
		}

	active_sync_load_includes("includes");

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
				{
				print("<link rel=\"stylesheet\" type=\"text/css\" href=\"includes.css/index_" . $file . ".css\">");
				}

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

												if(file_exists(DAT_DIR . "/login.data") === false)
													{
													}
												elseif(active_sync_get_is_identified($Request) == 0)
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
?>
