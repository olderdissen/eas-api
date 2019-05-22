<?
function active_sync_openssl_get_name_by_serial($serial)
	{
	$retval = "";

	foreach(glob(CRT_DIR . "/certs/*.pem") as $file)
		{
		$name_key = basename($file, ".pem");

		$data = openssl_x509_parse(file_get_contents($file));

		if(bccomp($data["serialNumber"], $serial) == 0)
			{
			$retval = $name_key;

			break;
			}
		}

	return($retval);
	}
?>
