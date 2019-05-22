<?
function active_sync_mail_header_decode_string($expression)
	{
	while(1)
		{
		if(strpos($expression, "=?") === false)
			break;

		list($a, $expression) = explode("=?", $expression, 2);

		if(strpos($expression, "?=") === false)
			break;

		list($expression, $b) = explode("?=", $expression, 2);

		if(strpos($expression, "?") === false)
			break;

		list($charset, $encoding, $string) = explode("?", $expression);

		$charset	= strtoupper($charset);
		$encoding	= strtoupper($encoding);

		if($encoding == "B")
			$string = base64_decode($string);

		if($encoding == "Q")
			{
			$string = quoted_printable_decode($string);

			$string = str_replace("_", " ", $string);
			}

		if($charset != "UTF-8")
			$string = utf8_encode($string);

		$expression = $a . $string . $b;
		}

	return($expression);
	}
?>
