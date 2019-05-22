<?
function active_sync_mail_body_smime_decode($mime)
	{
	$file = active_sync_create_guid();

	$mail_struct = active_sync_mail_split($mime);

	$head_parsed = active_sync_mail_parse_head($mail_struct["head"]);

	list($t_name, $t_mail) = active_sync_mail_parse_address($head_parsed["To"]);

	if((file_exists(CRT_DIR . "/certs/" . $t_mail . ".pem") === true) && (file_exists(CRT_DIR . "/private/" . $t_mail . ".pem") === true))
		{
		$crt = file_get_contents(CRT_DIR . "/certs/" . $t_mail . ".pem");
		$key = file_get_contents(CRT_DIR . "/private/" . $t_mail . ".pem");

		file_put_contents("/tmp/" . $file . ".enc", $mime);

		if(openssl_pkcs7_decrypt("/tmp/" . $file . ".enc", "/tmp/" . $file . ".dec", $crt, array($key, "")) === false)
			$new_temp_message = $mime;
		elseif(openssl_pkcs7_verify("/tmp/" . $file . ".dec", PKCS7_NOVERIFY, "/tmp/" . $file . ".ver") === false)
			$new_temp_message = $mime;
		elseif(openssl_pkcs7_verify("/tmp/" . $file . ".dec", PKCS7_NOVERIFY, "/tmp/" . $file . ".ver", array(), "/tmp/" . $file . ".ver", "/tmp/" . $file . ".dec") === false)
			$new_temp_message = $mime;
		else
			{
			foreach(array("Content-Description", "Content-Disposition", "Content-Transfer-Encoding", "Content-Type", "Received") as $key)
				unset($head_parsed[$key]);

			$new_temp_message = array();

			foreach($head_parsed as $key => $val)
				$new_temp_message[] = $key . ": " . $val;

			$new_temp_message[] = "";
			$new_temp_message = file_get_contents("/tmp/" . $file . ".dec");

			$new_temp_message = implode("\n", $new_temp_message);
			}

		active_sync_mail_body_smime_cleanup();
		}
	else
		$new_temp_message = $mime;

	return($new_temp_message);
	}
?>
