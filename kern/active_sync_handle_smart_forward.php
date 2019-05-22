<?
function active_sync_handle_smart_forward($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$mime = strval($xml->Mime);

	if(isset($xml->SaveInSentItems) === false)
		$save_in_sent_items = "F";
	else
		$save_in_sent_items = "T";

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
?>
