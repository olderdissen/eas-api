<?
function active_sync_get_settings($file)
	{
	if(file_exists($file) === false)
		$retval = "";
	else
		$retval = file_get_contents($file);

	if(strlen($retval) == 0)
		$retval = array();
	elseif(substr($retval, 0, 1) == "a")
		$retval = unserialize($retval);
	elseif(substr($retval, 0, 1) == "i")
		$retval = unserialize($retval);
	elseif(substr($retval, 0, 1) == "s")
		$retval = unserialize($retval);
	elseif(substr($retval, 0, 1) == "[")
		$retval = json_decode($retval, true);
	elseif(substr($retval, 0, 1) == "{")
		$retval = json_decode($retval, true);
	else
		$retval = array();

	return($retval);
	}
?>
