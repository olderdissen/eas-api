<?
function active_sync_mail_parse_body_multipart($body, $boundary)
	{
	$retval = array();

	$index = 0;

	$retval[$index] = "";

	while(strlen($body) > 0)
		{
		list($line, $body) = (strpos($body, "\n") === false ? array($body, "") : explode("\n", $body, 2));

		$line = str_replace("\r", "", $line);

		if(($line == "--" . $boundary) || ($line == "--" . $boundary . "--"))
			{
			$index = $index + 1;

			$retval[$index] = "";

			continue;
			}

		$retval[$index] = $retval[$index] . $line . "\n";
		}

	return($retval);
	}
?>
