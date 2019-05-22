<?
function active_sync_handle_move_items($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	$response = new active_sync_wbxml_response();

	$response->x_switch("Move");

	$response->x_open("MoveItems");

		if(isset($xml->Move) === true)
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
?>
