<?
function active_sync_wbxml_get_multibyte_integer($input, & $position = 0)
	{
	$multi_byte = 0;

	while(1)
		{
		$char = $input[$position ++];

		$byte = ord($char);

	  	$multi_byte = $multi_byte | ($byte & 0x7F);

	  	if(($byte & 0x80) != 0x80)
			break;

		$multi_byte = $multi_byte << 7;
		}

	return($multi_byte);
	}
?>
