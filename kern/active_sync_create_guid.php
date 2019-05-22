<?
function active_sync_create_guid($version = 4, $name = "", $namespace = "{00000000-0000-0000-0000-000000000000}")
	{
	# http://de.wikipedia.org/wiki/Universally_Unique_Identifier
	# http://de.wikipedia.org/wiki/Globally_Unique_Identifier
	# /proc/sys/kernel/random/uuid

	#  0	time_low			uint32_t	Zeitstempel, niederwertigste 32 Bits
	#  4	time_mid			uint16_t	Zeitstempel, mittlere 16 Bits
	#  6	time_hi_and_version		uint16_t	Oberste Bits des Zeitstempels in den unteren 12 Bits des Feldes, die oberen 4 Bits dienen als Versionsbezeichner
	#  8	clock_seq_high_and_reserved	uint8_t		Oberste 6 Bits der Clocksequenz (die obersten 2 Bits des Feldes sind in der hier beschriebenen UUID-Variante stets 1 0)
	#  9	clock_seq_low			uint8_t		Untere 8 Bits der Clocksequenz
	# 10	node				uint48_t	Eindeutige Node-Identifikationsnummer

	################################################################################

#		exec("uuid -v 1", $output);
#		exec("uuid -v 3 " . $namespace . " " . $name, $output);
#		exec("uuid -v 4", $output);
#		exec("uuid -v 5 " . $namespace . " " . $name, $output);

	################################################################################
	# set default values
	################################################################################

	$time_low			= "00000000";
	$time_mid			= "0000";
	$time_hi_and_version		= "0000";
	$clock_seq_high_and_reserved	= "80";
	$clock_seq_low			= "00";
	$node				= "000000000000";

	################################################################################
	# time-based version
	################################################################################

	if($version == 1)
		{
		$time = gettimeofday();
		$time = ($time["sec"] * 10 * 1000 * 1000) + ($time["usec"] * 10) + 0x01B21DD213814000;

		$time_low			= ((intval($time / 0x00000001) >>  0) & 0xFFFFFFFF);
		$time_mid			= ((intval($time / 0xFFFFFFFF) >>  0) & 0x0000FFFF);
		$time_hi_and_version		= ((intval($time / 0xFFFFFFFF) >> 16) & 0x00000FFF);

		$time_low			= sprintf("%08x", $time_low);
		$time_mid			= sprintf("%04x", $time_mid);
		$time_hi_and_version		= sprintf("%04x", $time_hi_and_version | ($version << 12));
		$clock_seq_high_and_reserved	= "80";
		$clock_seq_low			= "00";
		$node				= active_sync_get_mac_address();
		}

	################################################################################
	# DCE Security version, with embedded POSIX UIDs
	################################################################################

	if($version == 2)
		{
		$time_low			= "00000000";
		$time_mid			= "0000";
		$time_hi_and_version		= "2000";
		$clock_seq_high_and_reserved	= "80";
		$clock_seq_low			= "00";
		$node				= "000000000000";
		}

	################################################################################
	# name-based version that uses MD5 hashing
	################################################################################

	if($version == 3)
		{
		$namespace = active_sync_namespace_to_string($namespace);

		$hash = md5($namespace . $name);

		$time_low			= sprintf("%04x%04x", hexdec(substr($hash, 0, 4)), hexdec(substr($hash, 4, 4)));
		$time_mid			= sprintf("%04x", hexdec(substr($hash, 8, 4)));
		$time_hi_and_version		= sprintf("%04x", (hexdec(substr($hash, 12, 4)) & 0x0FFF) | ($version << 12));
		$clock_seq_high_and_reserved	= sprintf("%02x", (hexdec(substr($hash, 16, 2)) & 0x3F) | 0x80);
		$clock_seq_low			= sprintf("%02x", hexdec(substr($hash, 18, 2)));
		$node				= sprintf("%04x%04x%04x", hexdec(substr($hash, 20, 4)), hexdec(substr($hash, 24, 4)), hexdec(substr($hash, 28, 4)));
		}

	################################################################################
	# randomly or pseudo-randomly generated version
	################################################################################

	if($version == 4)
		{
		$time_low			= sprintf("%04x%04x", rand(0x0000, 0xFFFF), rand(0x0000, 0xFFFF));
		$time_mid			= sprintf("%04x", rand(0x0000, 0xFFFF));
		$time_hi_and_version		= sprintf("%04x", rand(0x0000, 0x0FFF) | ($version << 12));
		$clock_seq_high_and_reserved	= sprintf("%02x", rand(0x00, 0x3F) | 0x80);
		$clock_seq_low			= sprintf("%02x", rand(0x00, 0xFF));
		$node				= sprintf("%04x%04x%04x", rand(0x0000, 0xFFFF), rand(0x0000, 0xFFFF), rand(0x0000, 0xFFFF));
		}

	################################################################################
	# name-based version that uses SHA-1 hashing
	################################################################################

	if($version == 5)
		{
		$namespace = active_sync_namespace_to_string($namespace);

		$hash = sha1($namespace . $name);

		$time_low			= sprintf("%04x%04x", hexdec(substr($hash, 0, 4)), hexdec(substr($hash, 4, 4)));
		$time_mid			= sprintf("%04x", hexdec(substr($hash, 8, 4)));
		$time_hi_and_version		= sprintf("%04x", (hexdec(substr($hash, 12, 4)) & 0x0FFF) | ($version << 12));
		$clock_seq_high_and_reserved	= sprintf("%02x", (hexdec(substr($hash, 16, 2)) & 0x3F) | 0x80);
		$clock_seq_low			= sprintf("%02x", hexdec(substr($hash, 18, 2)));
		$node				= sprintf("%04x%04x%04x", hexdec(substr($hash, 20, 4)), hexdec(substr($hash, 24, 4)), hexdec(substr($hash, 28, 4)));
		}

	################################################################################
	# glue and return value
	################################################################################

	return(implode("-", array($time_low, $time_mid, $time_hi_and_version, $clock_seq_high_and_reserved . $clock_seq_low, $node)));
	}
?>
