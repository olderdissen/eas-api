<?
# this can be used to parse header form mail and it's multipart

function active_sync_mail_parse_head($head)
	{
	$retval = array();

	while(strlen($head) > 0)
		{
		list($line, $head) = (strpos($head, "\n") === false ? array($head, "") : explode("\n", $head, 2));

		if(strlen($line) == 0)
			break;

		if(strpos($line, ":") === false)
			continue;

		list($key, $val) = (strpos($line, ":") === false ? array($line, "") : explode(":", $line, 2));

		list($key, $val) = array(trim($key), trim($val));


		if(strtolower($key) == "received")
			{
			$retval[$key][] = $val;

			continue;
			}

		if(strtolower($key) == "subject")
			{
			$retval[$key] = active_sync_mail_header_decode_string($val);

			continue;
			}

		if(strtolower($key) == "x-auto-response-suppress")
			{
			$retval[$key] = explode(",", $val);

			continue;
			}

		$retval[$key] = $val;
		}

	return($retval);
	}
?>
