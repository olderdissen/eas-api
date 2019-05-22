<?
function active_sync_mail_split($mail)
	{
	$head = array();

	while(strlen($mail) > 0)
		{
		list($line, $mail) = (strpos($mail, "\n") === false ? array($mail, "") : explode("\n", $mail, 2));

		$line = str_replace("\r", "", $line);

		if(strlen($line) == 0)
			break;

		$head[] = $line;
		}

	$head = implode("\n", $head); # !!! we expect empty line later

	$head = str_replace(array("\x0D", "\x0A\x09", "\x0A\x20"), array("", "\x20", "\x20"), $head);

	return(array("head" => $head, "body" => $mail));
	}
?>
