<?
function active_sync_get_table_version()
	{
	$table = array();

#	$table[] = "1.0";
#	$table[] = "2.0";
#	$table[] = "2.1";
	$table[] = "2.5";	# LG-P920 depends on it
	$table[] = "12.0";
	$table[] = "12.1";
	$table[] = "14.0";	# allow SMS on Email
	$table[] = "14.1";	# allow SMS on Email
#	$table[] = "16.0";	# allow SMS on Email
#	$table[] = "16.1";	# allow SMS on Email, Find

	return($table);
	}
?>
