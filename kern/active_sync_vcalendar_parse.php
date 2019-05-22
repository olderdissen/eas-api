<?
function active_sync_vcalendar_parse(& $data)
	{
	$retval = array();

	$data = str_replace(array("\x0D", "\x0A\x09", "\x0A\x20"), array("", "", ""), $data);

	while(strlen($data) > 0)
		{
		list($line, $data) = (strpos($data, "\n") === false ? array($data, "") : explode("\n", $data, 2));

		if(strlen($line) == 0)
			continue;

		if(strpos($line, ":") === false)
			continue;

		list($key, $val) = explode(":", $line, 2);

		if($key == "BEGIN")
			{
			$retval[$val] = active_sync_vcalendar_parse($data);

			continue;
			}

		if($key == "END")
			break;

		$opt = array();

		while(strpos($key, ";"))
			{
			$par = substr($key, strrpos($key, ";") + 1);
			$key = substr($key, 0, strrpos($key, ";"));

			if(strlen($par) == 0)
				continue;

			if(strpos($par, "=") === false)
				{
				$opt[$par] = 1;

				continue;
				}

			list($par_key, $par_val) = explode("=", $par, 2);

			$opt[$par_key] = $par_val;
			}

		if($key == "ATTENDEE")
			{
			list($proto, $email) = explode(":", $val);

			$retval[$key][$email] = $opt;

			continue;
			}

		if($key == "ORGANIZER")
			{
			list($proto, $email) = explode(":", $val);

			$retval[$key][$email] = $opt;

			continue;
			}

		if($key == "RRULE")
			{
			foreach(explode(";", $val) as $par)
				{
				list($par_key, $par_val) = explode("=", $par);

				$retval[$key][$par_key] = $par_val;
				}

			continue;
			}

		if($key == "CATEGORIES")
			{
			$val = explode("\,", $val);
			}

		$retval[$key] = $val;
		}

	return($retval);
	}
?>
