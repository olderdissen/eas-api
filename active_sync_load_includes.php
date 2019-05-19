<?
function active_sync_load_includes($path, $type = "php", $recursive = false)
	{
	foreach(glob($path . "/*." . $type) as $file)
		{
		if($type == "js")
			print(file_get_contents($file));

		if($type == "php")
			include_once($file);
		}

	return(true);
	}
?>
