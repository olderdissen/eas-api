<?
function active_sync_wbxml_get_string($input, & $position = 0)
	{
	$string = "";

	while(1)
		{
		$char = $input[$position ++];

		if($char == "\x00")
			break;

		$string = $string . $char;
		}

	return($string);
	}
?>
