<?
function active_sync_handle_validate_cert($request)
	{
	$xml = active_sync_wbxml_request_parse_b($request["wbxml"]);

	if(isset($xml->CheckCRL) === false)
		$CheckCRL = 0;
	else
		$CheckCRL = strval($xml->CheckCRL);

	$states = array();

	if(isset($xml->CertificateChain) === true)
		{
		foreach($xml->CertificateChain->Certificate as $Certificate)
			{
			$state = 1; # Success.

			$states[] = $state;
			}
		}

	if(isset($xml->Certificates) === true)
		{
		foreach($xml->Certificates->Certificate as $Certificate)
			{
			$cert = chunk_split($Certificate, 64);

			$cert = "-----BEGIN CERTIFICATE-----" . "\n" . $cert . "-----END CERTIFICATE-----";

			$data = openssl_x509_parse($cert);

			$state = 1; # Success.

			if(time() < $data["validFrom_time_t"])
				$state = 7; # The digital ID used to sign the message has expired or is not yet valid.

			if(time() > $data["validTo_time_t"])
				$state = 7; # The digital ID used to sign the message has expired or is not yet valid.

			foreach($data["purposes"] as $purpose)
				{
				if($purpose[2] != "smimesign")
					continue;

				if($purpose[0] == 1)
					continue;

				$state = 6;

				break;
				}

			if($CheckCRL == 0)
				{
				}
			elseif(isset($data["extensions"]["crlDistributionPoints"]) === false)
				$state = 14; # The validity of the digital ID cannot be determined because the server that provides this information cannot be contacted.
			else
				{
				exec("echo \"" . $cert . "\" | openssl x509 -serial -noout", $output, $var_return);

				$serial = str_replace("serial=", "", $output[0]);

				list($type, $address) = explode(":", $data["extensions"]["crlDistributionPoints"], 2);

				$address = trim($address);

				$data = file_get_contents($address);

				exec("echo \"" . $data . "\" | openssl crl -text -noout", $output, $var_return);

				foreach($output as $line)
					{
					if($line == "    Serial Number: " . $serial)
						{
						$state = 13; # The digital ID used to sign this message has been revoked. This can indicate that the issuer of the digital ID no longer trusts the sender, the digital ID was reported stolen, or the digital ID was compromised.

						break;
						}
					}
				}

			$states[] = $state;
			}
		}

	$response = new active_sync_wbxml_response();

	$response->x_switch("ValidateCerts");

	$response->x_open("ValidateCert");
		$response->x_open("Status");
			$response->x_print(1);
		$response->x_close("Status");

		foreach($states as $state)
			{
			$response->x_open("Certificate");
				$response->x_open("Status");
					$response->x_print($state);
				$response->x_close("Status");
			$response->x_close("Certificate");
			}

	$response->x_close("ValidateCert");

	return($response->response);
	}
?>
