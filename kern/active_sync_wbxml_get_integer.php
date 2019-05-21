<?
function active_sync_wbxml_get_integer($input, & $position = 0)
	{
	$char = $input[$position ++];

	$byte = ord($char);

	return($byte);
	}
?>
