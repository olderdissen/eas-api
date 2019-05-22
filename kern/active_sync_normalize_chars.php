<?
function active_sync_normalize_chars($string)
	{
	$table = array();

	$table["a"] = "à á â ã ä å ā ă";
	$table["c"] = "ç ć ĉ ċ č ḉ";
	$table["e"] = "è é ê ë ē ĕ ė ę ě ḕ ḗ ḙ ḛ ḝ";
	$table["i"] = "ì í î";
	$table["o"] = "ò ó ô ö";
	$table["s"] = "ß ś ŝ ș š";
	$table["t"] = "ț ţ";
	$table["u"] = "ù ú û ü ũ ū ŭ ű";
	$table["z"] = "ź ż ž";

	foreach($table as $char => $chars)
		{
		$string = str_replace(explode(" ", strtolower($chars)), strtolower($char), $string);
		$string = str_replace(explode(" ", strtoupper($chars)), strtoupper($char), $string);
		}

	return($string);
	}
?>
