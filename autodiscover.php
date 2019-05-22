<?
chdir(__DIR__);

################################################################################
# parse input
################################################################################

# <?xml version='1.0' encoding='UTF-8' standalone='no' ? >
# <Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/mobilesync/requestschema/2006">
#  <Request>
#   <EMailAddress>test@olderdissen.ro</EMailAddress>
#   <AcceptableResponseSchema>http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006</AcceptableResponseSchema>
#  </Request>
# </Autodiscover>

if($data = file_get_contents("php://input"))
	{
	file_put_contents("autodiscover.log", $data);

	$data = new SimpleXMLElement($data);

	$email_address = $data->Request->EMailAddress;
	$acceptable_response_schema = $data->Request->AcceptableResponseSchema;
	}
else
	{
	$email_address = "user@olderdissen.ro";
	$acceptable_response_schema = "http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006";
	}

################################################################################
# ...
################################################################################

list($user, $host) = (strpos($email_address, "@") === false ? array("", "") : explode("@", $email_address));

################################################################################
# get list of users
################################################################################

$logins = json_decode(file_get_contents(__DIR__ . "/data/login.data"), true);

################################################################################
# check if user is known
################################################################################

$display_name = "John Doe";
$framework_case = "default";

foreach($logins["login"] as $login)
	{
	################################################################################
	# check if user is a different one
	################################################################################

	if($login["User"] != $user)
		continue;

	################################################################################
	# return if right password is given
	################################################################################

	$framework_case = "";
	$display_name = $login["DisplayName"];
	}

################################################################################
# ...
################################################################################

$framework_action = "settings";

$autodiscover = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' standalone='no' ?><AutoDiscover xmlns=\"http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006\" />");

if($framework_case == "")
	{
	$response = $autodiscover->addChild("Response");

		$response->addChild("Culture", "en:en");

		$user = $response->addChild("User");

			$user->addChild("DisplayName", $display_name);
			$user->addChild("EMailAddress", $email_address);

		$action = $response->addChild("Action");

			if($framework_action == "error") # 4.2.2 Response - Case Error
				{
				$error = $action->addChild("Error");

					$error->addChild("ErrorCode", 2);
					$error->addChild("Message", "The directory service could not be reached");
					$error->addChild("DebugData", "MailUser");
				}

			if($framework_action == "redirect") # 4.2.3 Response - Case Redirect
				{
				$action->addChild("Redirect", $email_address);
				}

			if($framework_action == "settings") # 4.2.4 Response - Case Server Settings
				{
				$settings = $action->addChild("Settings");

					$server = $settings->addChild("Server");

						$server->addChild("Type", "MobileSync");
						$server->addChild("Url", "https://olderdissen.ro/Microsoft-Server-ActiveSync");
						$server->addChild("Name", "Microsoft Exchange ActiveSync");

#					$server = $settings->addChild("Server");

#						$server->addChild("Type", "CertEnroll");
#						$server->addChild("Url", "https://" . $host . "/CertEnroll");
#						$server->addChild("Name", "");
#						$server->addChild("ServerData", "CertEnrollTemplate");
				}
	}

if($framework_case == "default") # 4.2.6 Response – Case Framework Default
	{
	$account = $autodiscover->addChild("Account");

		$account->addChild("AccountType", "email"); # email | nntp

		if($framework_action == "redirect-url")
			{
			$account->addChild("Action", "redirectUrl");
			$account->addChild("RedirectUrl", "https://autodiscover.olderdissen.ro/autodiscover/autodiscover.xml");
			}

		if($framework_action == "redirect-addr")
			{
			$account->addChild("Action", "redirectAddr");
			$account->addChild("RedirectAddr", $email_address);
			}

		if($framework_action == "settings")
			{
			$account->addChild("Action", "settings");
			$account->addChild("Image", "https://olderdissen.ro/images/logo_small_v2.gif");
			$account->addChild("ServiceHome", "https://olderdissen.ro/Microsoft-Server-ActiveSync");

			$protocol = $account->addChild("Protocol");

				$protocol->addChild("Type", "smtp");
				$protocol->addChild("Server", "olderdissen.ro");
				$protocol->addChild("Port", 25);

			$protocol = $account->addChild("Protocol");

				$protocol->addChild("Type", "imap2");
				$protocol->addChild("Server", "olderdissen.ro");
				$protocol->addChild("Port", 143);

			$protocol = $account->addChild("Protocol");

				$protocol->addChild("Type", "urd");
				$protocol->addChild("Server", "olderdissen.ro");
				$protocol->addChild("Port", 465);

			$protocol = $account->addChild("Protocol");

				$protocol->addChild("Type", "submission");
				$protocol->addChild("Server", "olderdissen.ro");
				$protocol->addChild("Port", 587);

			$protocol = $account->addChild("Protocol");

				$protocol->addChild("Type", "imaps");
				$protocol->addChild("Server", "olderdissen.ro");
				$protocol->addChild("Port", 993);
			}
	}

if($framework_case == "error") # 4.2.5 Response - Case Framework Error
	{
	$response = $autodiscover->addChild("Response");

		$error = $response->addChild("Error");

			$error->addAttribute("Id", date("U"));
			$error->addAttribute("Time", date("H:i:s"));

			$error->addChild("ErrorCode", 600);
			$error->addChild("Message", "Invalid Request");
			$error->addChild("DebugData", "MailUser");
	}

$autodiscover = $autodiscover->asXML();

header("Content-Type: text/xml; charset=utf-8");
header("Content-Length: " . strlen($autodiscover));

print($autodiscover);
?>
