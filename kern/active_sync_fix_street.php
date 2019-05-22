<?
function active_sync_fix_street($string)
	{
	$words = explode(" ", $string);

	if(is_numeric(substr(end($words), 0, 1)) === true)
		array_pop($words);

	return(implode(" ", $words));
	}
?>
