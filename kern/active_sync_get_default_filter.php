<?
function active_sync_get_default_filter()
	{
	$retval = array();

	# 0 all
	# 1 1d
	# 2 3d
	# 3 1w
	# 4 2w
	# 5 1m
	# 6 3m
	# 7 6m
	# 8 incomplete

	$retval["Email"]	= array(0, 1, 2, 3, 4, 5);
	$retval["Calendar"]	= array(1, 4, 5, 6, 7);
	$retval["Tasks"]	= array(0, 8);

	return($retval);
	}
?>
