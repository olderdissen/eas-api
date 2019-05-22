<?
# doesn't work so far, but also not needed yet

function active_sync_get_ms_global_obj_id_by_ms_uid($expression)
	{
	$time = gettimeofday();
	$time = ($time["sec"] * 10000000) + ($time["usec"] * 10) + 0x01B21DD213814000;

	$retval = array();

	if(strlen($expression) == 38) # VCALID
		{
		$retval["CLASSID"]	= pack("H*", str_replace(array("{", "}", "-"), "", "{04000000-8200-E000-74C5-B7101A82E008}"));
		$retval["INSTDATE"]	= pack("CCCC", 0, 0, 0, 0);
		$retval["NOW"]		= pack("VV", (intval($time / 0x00000001) >>  0) & 0xFFFFFFFF, (intval($time / 0xFFFFFFFF) >>  0) & 0xFFFFFFFF);
		$retval["ZERO"]		= str_repeat(chr(0x00), 8);
		$retval["BYTECOUNT"]	= pack("V", 0);
		$retval["DATA"]		= "vCal-Uid" . pack("V", 1) . pack("H*", str_replace(array("{", "}", "-"), "", $expression)) . "\x00";

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
?>
