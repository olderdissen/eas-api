<?
define("ACTIVE_SYNC_DATA_DECODE", 0 - 1);
define("ACTIVE_SYNC_DATA_ENCODE", 0 + 1);

function active_sync_data_code($string, $key, $direction)
	{
	$direction = ($direction < 0 - 1 ? 0 - 1 : $direction);
	$direction = ($direction > 0 + 1 ? 0 + 1 : $direction);

	for($position = 0; $position < strlen($string); $position = $position + 1)
		$string[$position] = chr((0x0100 + ord($string[$position]) + (ord($key[$position % strlen($key)]) * $direction)) % 0x0100);

	return($string);
	}
?>
