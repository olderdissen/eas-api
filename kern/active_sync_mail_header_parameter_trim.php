<?
function active_sync_mail_header_parameter_trim($string)
	{
	if(strlen($string) < 2)
		{
		}
	elseif((substr($string, 0, 1) == '(') && (substr($string, 0 - 1) == ')')) # comment
		$string = substr($string, 1, 0 - 1);
	elseif((substr($string, 0, 1) == '"') && (substr($string, 0 - 1) == '"')) # display-name
		$string = substr($string, 1, 0 - 1);
	elseif((substr($string, 0, 1) == '<') && (substr($string, 0 - 1) == '>')) # mailbox
		$string = substr($string, 1, 0 - 1);

	return($string);
	}
?>
