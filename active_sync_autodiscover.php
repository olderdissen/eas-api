<?
define("ACTIVE_SYNC_AUTODISCOVER_REQUEST_OUTLOOK", "http://schemas.microsoft.com/exchange/autodiscover/outlook/requestschema/2006");
define("ACTIVE_SYNC_AUTODISCOVER_REQUEST_MOBILESYNC", "http://schemas.microsoft.com/exchange/autodiscover/mobilesync/requestschema/2006");

define("ACTIVE_SYNC_AUTODISCOVER_RESPONSE_DEFAULT", "http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006");
define("ACTIVE_SYNC_AUTODISCOVER_RESPONSE_OUTLOOK", "http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a");
define("ACTIVE_SYNC_AUTODISCOVER_RESPONSE_MOBILESYNC", "http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006");

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

	header("Content-Type: text/xml; charset=utf-8");
	header("Content-Length: " . strlen($autodiscover));

	print($autodiscover);

	active_sync_debug(print_r($_SERVER, true));
	if(isset($request["xml"]))
		active_sync_debug(print_r($request["xml"], true));
	active_sync_debug($autodiscover);
	}
?>
