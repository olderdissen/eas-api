<?
function active_sync_openssl_get_key_list()
	{
	$settings = active_sync_get_settings_cert();

	$retval = array();

	foreach(glob(CRT_DIR . "/private/*.pem") as $file)
		{
		$name_key = basename($file, ".pem");

		$file_key = CRT_DIR . "/private/" . $name_key . ".pem";

		$pass = (isset($settings["private"][$name_key]) === false ? "" : $settings["private"][$name_key]);

		$key = file_get_contents($file_key);

		$data = openssl_pkey_get_private($key, $pass);

		$retval[$name_key] = openssl_pkey_get_details($data);
		}

	if(count($retval) > 1)
		ksort($retval);

	return($retval);
	}
?>
