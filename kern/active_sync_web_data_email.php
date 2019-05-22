<?
function active_sync_web_data_email($request)
	{
	$retval = array();

	foreach(glob(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/*.data") as $file)
		{
		$server_id = basename($file, ".data");

		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $server_id);

		if(isset($data["AirSync"]["Class"]) === false)
			continue;

		foreach(array("From" => "", "Importance" => 1, "Read" => 0, "Subject" => "", "To" => "", "MessageClass" => "IPM.Note") as $token => $value)
			$data["Email"][$token] = (isset($data["Email"][$token]) === false ? $value : $data["Email"][$token]);

		foreach(array("LastVerbExecuted" => 0) as $token => $value)
			$data["Email2"][$token] = (isset($data["Email2"][$token]) === false ? $value : $data["Email2"][$token]);

		$data["Email"]["DateReceived"] = (isset($data["Email"]["DateReceived"]) === false ? date("Y-m-d\TH:i:s\Z", filemtime(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $file)) : $data["Email"]["DateReceived"]);

		$add = 0;

		$body = "...";

		foreach($data["Body"] as $body)
			{
			if(isset($body["Type"]) === false)
				continue;

			if($body["Type"] != 1)
				continue;

			$body = $body["Data"];
			}

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

			$add = 1;
			}

		if($data["AirSync"]["Class"] == "SMS")
			{
			$class				= $data["AirSync"]["Class"];

			$from				= $data["Email"]["From"];
			$to				= $data["Email"]["To"];
			$date_received			= $data["Email"]["DateReceived"];
			$importance			= $data["Email"]["Importance"]; # how can sender determine the importance ???
			$read				= $data["Email"]["Read"];
			$subject			= utf8_encode(substr(utf8_decode($body) , 0, 80)); # doesn't matter if message is shorter than 80 chars

			$status				= (isset($data["Flag"]["Email"]["Status"]) ? $data["Flag"]["Email"]["Status"] : null);
			$attachments			= 0;

			$message_class			= null;
			$last_verb_executed		= $data["Email2"]["LastVerbExecuted"];

			$add = 1;
			}

		if($add == 1)
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
?>
