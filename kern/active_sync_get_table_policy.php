<?
function active_sync_get_table_policy()
	{
	# type ::= C (checkbox) | L (textarea) | R (radio) | S (select) | T (text)

	$table = array(
		"AllowBluetooth"				=> array("Type" => "S", "Values" => array(0 => "Disable Bluetooth.", 1 => "Disable Bluetooth, but allow the configuration of hands-free profiles.", 2 => "Allow Bluetooth.")),
		"AllowBrowser"					=> array("Type" => "S", "Values" => array(0 => "Do not allow the use of a web browser.", 1 => "Allow the use of a web browser.")),
		"AllowCamera"					=> array("Type" => "S", "Values" => array(0 => "Use of the camera is not allowed.", 1 => "Use of the camera is allowed.")),
		"AllowConsumerEmail"				=> array("Type" => "S", "Values" => array(0 => "Do not allow the user to configure a personal email account.", 1 => "Allow the user to configure a personal email account.")),
		"AllowDesktopSync"				=> array("Type" => "S", "Values" => array(0 => "Do not allow Desktop ActiveSync.", 1 => "Allow Desktop ActiveSync.")),
		"AllowHTMLEmail"				=> array("Type" => "S", "Values" => array(0 => "HTML-formatted email is not allowed.", 1 => "HTML-formatted email is allowed.")),
		"AllowInternetSharing"				=> array("Type" => "S", "Values" => array(0 => "Do not allow the use of Internet Sharing.", 1 => "Allow the use of Internet Sharing.")),
		"AllowIrDA"					=> array("Type" => "S", "Values" => array(0 => "Disable IrDA.", 1 => "Allow IrDA.")),
		"AllowPOPIMAPEmail"				=> array("Type" => "S", "Values" => array(0 => "POP or IMAP email access is not allowed.", 1 => "POP or IMAP email access is allowed.")),
		"AllowRemoteDesktop"				=> array("Type" => "S", "Values" => array(0 => "Do not allow the use of Remote Desktop.", 1 => "Allow the use of Remote Desktop.")),
		"AllowSimpleDevicePassword"			=> array("Type" => "S", "Values" => array(0 => "Simple passwords are not allowed.", 1 => "Simple passwords are allowed.")),
		"AllowSMIMEEncryptionAlgorithmNegotiation"	=> array("Type" => "S", "Values" => array(0 => "Do not negotiate.", 1 => "Negotiate a strong algorithm.", 2 => "Negotiate any algorithm.")),
		"AllowSMIMESoftCerts"				=> array("Type" => "S", "Values" => array(0 => "Soft certificates are not allowed.", 1 => "Soft certificates are allowed.")),
		"AllowStorageCard"				=> array("Type" => "S", "Values" => array(0 => "SD card use is not allowed.", 1 => "SD card use is allowed.")),
		"AllowTextMessaging"				=> array("Type" => "S", "Values" => array(0 => "SMS or text messaging is not allowed.", 1 => "SMS or text messaging is allowed.")),
		"AllowUnsignedApplications"			=> array("Type" => "S", "Values" => array(0 => "Unsigned applications are not allowed to execute.", 1 => "Unsigned applications are allowed to execute.")),
		"AllowUnsignedInstallationPackages"		=> array("Type" => "S", "Values" => array(0 => "Unsigned cabinet (.cab) files are not allowed to be installed.", 1 => "Unsigned cabinet (.cab) files are allowed to be installed.")),
		"AllowWiFi"					=> array("Type" => "S", "Values" => array(0 => "The use of Wi-Fi connections is not allowed.", 1 => "The use of Wi-Fi connections is allowed.")),
		"AlphanumericDevicePasswordRequired"		=> array("Type" => "S", "Values" => array(0 => "Alphanumeric device password is not required.", 1 => "Alphanumeric device password is required.")),
		"ApprovedApplicationList"			=> array("Type" => "L", "Label" => "Hash"),
		"AttachmentsEnabled"				=> array("Type" => "S", "Values" => array(0 => "Attachments are not allowed to be downloaded.", 1 => "Attachments are allowed to be downloaded.")),
		"DevicePasswordEnabled"				=> array("Type" => "S", "Values" => array(0 => "Device password is not required.", 1 => "Device password is required.")),
		"DevicePasswordExpiration"			=> array("Type" => "T", "Length" => 4, "Label" => "day(s)"),
		"DevicePasswordHistory"				=> array("Type" => "T", "Length" => 4, "Label" => "entry(s)"),
		"MaxAttachmentSize"				=> array("Type" => "T", "Length" => 8, "Label" => "byte(s)", "Min" => 0, "Max" => 99999999),
		"MaxCalendarAgeFilter"				=> array("Type" => "S", "Values" => array(0 => "All days", 4 => "2 weeks", 5 => "1 month", 6 => "3 months", 7 => "6 month")),
		"MaxDevicePasswordFailedAttempts"		=> array("Type" => "T", "Length" => 2, "Label" => "tries(s)", "Min" => 4, "Max" => 16),
		"MaxEmailAgeFilter"				=> array("Type" => "S", "Values" => array(0 => "Sync all", 1 => "1 day", 2 => "3 days", 3 => "1 week", 4 => "2 weeks", 5 => "1 month")),
		"MaxEmailBodyTruncationSize"			=> array("Type" => "T", "Length" => 8, "Label" => "byte(s)", "Min" => 0, "Max" => 99999999),
		"MaxEmailHTMLBodyTruncationSize"		=> array("Type" => "T", "Length" => 8, "Label" => "byte(s)", "Min" => 0, "Max" => 99999999),
		"MaxInactivityTimeDeviceLock"			=> array("Type" => "T", "Length" => 4, "Label" => "second(s)", "Min" => 0, "Max" => 9999),
		"MinDevicePasswordComplexCharacters"		=> array("Type" => "T", "Length" => 2, "Label" => "char(s)", "Min" => 1, "Max" => 4),
		"MinDevicePasswordLength"			=> array("Type" => "T", "Length" => 2, "Label" => "chars(s)", "Min" => 1, "Max" => 16),
		"PasswordRecoveryEnabled"			=> array("Type" => "S", "Values" => array(0 => "Password recovery is not enabled on the server.", 1 => "Password recovery is enabled on the server.")),
		"RequireDeviceEncryption"			=> array("Type" => "S", "Values" => array(0 => "Encryption is not required.", 1 => "Encryption is required.")),
		"RequireEncryptedSMIMEMessages"			=> array("Type" => "S", "Values" => array(0 => "Encrypted email messages are not required.", 1 => "Email messages are required to be encrypted.")),
		"RequireEncryptionSMIMEAlgorithm"		=> array("Type" => "S", "Values" => array(0 => "TripleDES algorithm", 1 => "DES algorithm", 2 => "RC2 128bit", 3 => "RC2 64bit", 4 => "RC2 40bit")),
		"RequireManualSyncWhenRoaming"			=> array("Type" => "S", "Values" => array(0 => "Do not require manual sync; allow direct push when roaming.", 1 => "Require manual sync when roaming.")),
		"RequireSignedSMIMEAlgorithm"			=> array("Type" => "S", "Values" => array(0 => "Use SHA1.", 1 => "Use MD5.")),
		"RequireSignedSMIMEMessages"			=> array("Type" => "S", "Values" => array(0 => "Signed S/MIME messages are not required.", 1 => "Signed S/MIME messages are required.")),
		"RequireStorageCardEncryption"			=> array("Type" => "S", "Values" => array(0 => "Encryption of the device storage card is not required.", 1 => "Encryption of the device storage card is required.")),
		"UnapprovedInROMApplicationList"		=> array("Type" => "L", "Label" => "ApplicationName"),
		);

	return($table);
	}
?>
