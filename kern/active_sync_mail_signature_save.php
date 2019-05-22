<?
function active_sync_mail_signature_save($data, $body)
	{
	list($name, $mail) = active_sync_mail_parse_address($data["Email"]["From"]);

	$crt = CRT_DIR . "/certs/" . $mail . ".pem";

	if(file_exists($crt) === false)
		{
		$body = base64_encode($body);

		$body = chunk_split($body , 64, "\n");

		$body = substr($body, 0, 0 - 1);

		$body = array("-----BEGIN PKCS7-----", $body, "-----END PKCS7-----");

		$body = implode("\n", $body);

		file_put_contents($crt, $body);

		exec("openssl pkcs7 -in " . $crt . " -out " . $crt . " -text -print_certs", $output, $return_var);

		$body = file_get_contents($crt);

		list($null, $body) = explode("-----BEGIN CERTIFICATE-----", 2);
		list($body, $null) = explode("-----END CERTIFICATE-----", 2);

		$body = array("-----BEGIN CERTIFICATE-----", $body, "-----END CERTIFICATE-----");

		$body = implode("\n", $body);

		file_put_contents($crt, $body);
		}
	}
?>
