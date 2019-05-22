<?
function active_sync_handle_send_mail($request)
	{
	if($request["ContentType"] == "application/vnd.ms-sync.wbxml")
		{
		$request = active_sync_handle_send_mail_fix_android($request); # !!!!!!!!!!!!!!!

		$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

		$mime = strval($xml->Mime);

		if(isset($xml->SaveInSentItems) === false)
			$save_in_sent_items = "F";
		else
			$save_in_sent_items = "T";
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
?>
