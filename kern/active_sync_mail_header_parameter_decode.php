<?
function active_sync_mail_header_parameter_decode($string, $search = "")
	{
	$data = array();

	while(strlen($string) > 0)
		{
		if(strpos($string, ";") === false)
			break;

		list($line, $string) = explode(";", strrev($string), 2);

		list($line, $string) = array(strrev($line), strrev($string));

		list($line, $string) = array(trim($line), trim($string));

		if(strlen($line) == 0)
			continue;

		if(strpos($line, "=") === false)
			{
			$data[$line] = 1;

			continue;
			}

		list($key, $val) = explode("=", $line, 2);

		list($key, $val) = array(trim($key), trim($val));

		$val = active_sync_mail_header_parameter_trim($val);

		$data[$key] = $val;
		}

	if($search == "")
		$retval = $string;
	elseif(isset($data[$search]) === false)
		$retval = "";
	elseif($search == "charset")
		$retval = strtoupper($data[$search]); # this can be trap !!! utf-8 | UTF-8
	else
		$retval = $data[$search];

	return($retval);
	}
?>
