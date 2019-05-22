<?
function active_sync_get_default_policy()
	{
	$retval = array();

	$retval["AllowBluetooth"]				= 2;		# 0 | 1 | 2
	$retval["AllowBrowser"]					= 1;		# 0 | 1
	$retval["AllowCamera"]					= 1;		# 0 | 1
	$retval["AllowConsumerEmail"]				= 1;		# 0 | 1
	$retval["AllowDesktopSync"]				= 1;		# 0 | 1
	$retval["AllowHTMLEmail"]				= 1;		# 0 | 1
	$retval["AllowInternetSharing"]				= 1;		# 0 | 1
	$retval["AllowIrDA"]					= 1;		# 0 | 1
	$retval["AllowPOPIMAPEmail"]				= 1;		# 0 | 1
	$retval["AllowRemoteDesktop"]				= 1;		# 0 | 1
	$retval["AllowSimpleDevicePassword"] 			= 1;		# 0 | 1
	$retval["AllowSMIMEEncryptionAlgorithmNegotiation"]	= 2;		# 0 | 1 | 2
	$retval["AllowSMIMESoftCerts"]				= 1;		# 0 | 1
	$retval["AllowStorageCard"]				= 1;		# 0 | 1
	$retval["AllowTextMessaging"]				= 1;		# 0 | 1
	$retval["AllowUnsignedApplications"]			= 1;		# 0 | 1
	$retval["AllowUnsignedInstallationPackages"]		= 1;		# 0 | 1
	$retval["AllowWiFi"]					= 1;		# 0 | 1
	$retval["AlphanumericDevicePasswordRequired"]		= 0;		# 0 | 1
	$retval["ApprovedApplicationList"]			= "";		# Hash
	$retval["AttachmentsEnabled"]				= 1;		# 0 | 1
	$retval["DevicePasswordEnabled"]			= 0;		# 0 | 1
	$retval["DevicePasswordExpiration"]			= 7;		# 0 .. x
	$retval["DevicePasswordHistory"]			= 52;		# 0 .. x
	$retval["MaxAttachmentSize"]				= 0;		# 0 .. x
	$retval["MaxCalendarAgeFilter"]				= 0;		# 0 (all days) | 4 (two weeks) | 5 (one month) | 6 (three months) | 7 (six months)
	$retval["MaxDevicePasswordFailedAttempts"]		= 4;		# 4 .. 16
	$retval["MaxEmailAgeFilter"]				= 0;		# 0 (sync all) | 1 (one day) | 2 (three days) | 3 (one week) | 4 (two weeks) | 5 (one month)
	$retval["MaxEmailBodyTruncationSize"]			= 0;		# -1 (no truncation) | 0 (truncate only the header) | 1 .. x (truncate the e-mail body to the specified size)
	$retval["MaxEmailHTMLBodyTruncationSize"]		= 0;		# -1 (no truncation) | 0 (truncate only the header) | 1 .. x (truncate the e-mail body to the specified size)
	$retval["MaxInactivityTimeDeviceLock"]			= 30;		# 0 .. 9998 | 9999 (infinite)
	$retval["MinDevicePasswordComplexCharacters"]		= 1;		# 1 .. 4
	$retval["MinDevicePasswordLength"]			= 1;		# 1 (no limit) | 2 .. 16
	$retval["PasswordRecoveryEnabled"]			= 1;		# 0 | 1
	$retval["RequireDeviceEncryption"]			= 0;		# 0 | 1
	$retval["RequireEncryptedSMIMEMessages"]		= 0;		# 0 | 1
	$retval["RequireEncryptionSMIMEAlgorithm"]		= 0;		# 0 | 1
	$retval["RequireManualSyncWhenRoaming"]			= 0;		# 0 | 1
	$retval["RequireSignedSMIMEAlgorithm"]			= 0;		# 0 | 1
	$retval["RequireSignedSMIMEMessages"]			= 0;		# 0 | 1
	$retval["RequireStorageCardEncryption"]			= 0;		# 0 | 1
	$retval["UnapprovedInROMApplicationList"]		= "";		# ApplicationName

	return($retval);
	}
?>
