<?
function active_sync_mail_file_size($size)
	{
	$unit = 0;

	while(($size < 1000) === false)
		{
		$size = $size / 1024;
		$unit = $unit + 1;
		}

	if($unit > 11)
		$unit = 11;
	elseif($size < 10)
		$size = number_format($size, 2);
	elseif($size < 100)
		$size = number_format($size, 1);
	elseif($size < 1000)
		$size = number_format($size, 0);

	return($size . " " . implode(null, array_slice(array("Byte", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB", "NB", "DB", "??"), $unit, 1)));
	}
?>
