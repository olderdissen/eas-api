<?
function active_sync_mail_body_smime_sign($mime) # almost copy of encode
	{
	$mail_struct = active_sync_mail_split($mime);

	$head_parsed = active_sync_mail_parse_head($mail_struct["head"]);

	list($f_name, $f_mail) = active_sync_mail_parse_address($head_parsed["From"]);

	if((file_exists(CRT_DIR . "/certs/" . $f_mail . ".pem") === true) && (file_exists(CRT_DIR . "/private/" . $f_mail . ".pem") === true))
		{
		$new_temp_message = array();

		$new_temp_message[] = "Content-Type: " . $head_parsed["Content-Type"];
		$new_temp_message[] = "MIME-Version: 1.0";
		$new_temp_message[] = "";
		$new_temp_message[] = $mail_struct["body"];

		$new_temp_message = implode("\n", $new_temp_message);

		$file = active_sync_create_guid();

		file_put_contents("/tmp/" . $file . ".dec", $new_temp_message);

		foreach(array("Content-Type", "MIME-Version") as $key)
			unset($head_parsed[$key]);

		$crt = file_get_contents(CRT_DIR . "/certs/" . $f_mail . ".pem");
		$key = file_get_contents(CRT_DIR . "/private/" . $f_mail . ".pem");

		if(openssl_pkcs7_sign("/tmp/" . $file . ".dec", "/tmp/" . $file . ".enc", $crt, $key, $head_parsed) === false)
			$new_temp_message = $mime;
		else
			$new_temp_message = file_get_contents("/tmp/" . $file . ".enc");

		active_sync_mail_body_smime_cleanup();
		}
	else
		$new_temp_message = $mime;

	return($new_temp_message);
	}
?>
