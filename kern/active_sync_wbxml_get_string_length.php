<?
function active_sync_wbxml_get_string_length($input, & $position = 0, $length = 0)
	{
	$string = substr($input, $position, $length);

	$position = $position + $length;

	return($string);
	}
?>
