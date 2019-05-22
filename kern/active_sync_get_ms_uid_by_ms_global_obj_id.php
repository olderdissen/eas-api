<?
# doesn't work so far, but also not needed yet

function active_sync_get_ms_uid_by_ms_global_obj_id($expression)
	{
	$expression = base64_decode($expression);

	$a = unpack("H32CLASSID/NINSTDATE/H16NOW/H16ZERO/VBYTECOUNT", $expression);

	if($a["BYTECOUNT"] == 16) # OUTLOOKID
		{
		$b = unpack("H" . ($a["BYTECOUNT"] * 2) . "DATA", substr($expression, 40));

		for($i = 16; $i < 20; $i = $i + 1)
			{
			$expression[$i] = chr(0x00);
			}

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
?>
