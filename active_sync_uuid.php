<?
function active_sync_create_guid($version = 4, $name = "localhost", $namespace = "{00000000-0000-0000-0000-000000000000}")
	{
	# be careful on non 64 bit machines
	# $namespace could be /etc/machine-id
	# $name could be /etc/hostname

	$time_low	= 0;
	$time_mid	= 0;
	$time_hi	= 0;
	$clock_seq_high	= 0;
	$clock_seq_low	= 0;
	$node		= 0;

	# time-based version
	if($version == 1)
		{
		$time = gettimeofday();
		$time = ($time["sec"] * 10 * 1000 * 1000) + ($time["usec"] * 10) + 0x01B21DD213814000;

		$time_low	= ((intval($time / 0x00000001) >>  0) & 0xffffffff);
		$time_mid	= ((intval($time / 0xffffffff) >>  0) & 0x0000ffff);
		$time_hi	= ((intval($time / 0xffffffff) >> 16) & 0x0000ffff);
		}

	# DCE Security version, with embedded POSIX UIDs
	if($version == 2)
		{
		}

	# name-based version that uses MD5 hashing
	if($version == 3)
		{
		$namespace = hex2bin(str_replace(["-", "{", "}"], "", $namespace));

		$hash = md5($namespace . $name);

		$time_low	= hexdec(substr($hash, 0, 8));
		$time_mid	= hexdec(substr($hash, 8, 4));
		$time_hi	= hexdec(substr($hash, 12, 4));
		$clock_seq_high	= hexdec(substr($hash, 16, 2));
		$clock_seq_low	= hexdec(substr($hash, 18, 2));
		$node		= hexdec(substr($hash, 20, 12));
		}

	# randomly or pseudo-randomly generated version
	if($version == 4)
		{
		$time_low	= mt_rand(0, 0xffffffff);
		$time_mid	= mt_rand(0, 0xffff);
		$time_hi	= mt_rand(0, 0xffff);
		$clock_seq_high	= mt_rand(0, 0xff);
		$clock_seq_low	= mt_rand(0, 0xff);
		$node		= mt_rand(0, 0xffffffffffff);
		}

	# name-based version that uses SHA-1 hashing
	if($version == 5)
		{
		$namespace = hex2bin(str_replace(["-", "{", "}"], "", $namespace));

		$hash = sha1($namespace . $name);

		$time_low	= hexdec(substr($hash, 0, 8));
		$time_mid	= hexdec(substr($hash, 8, 4));
		$time_hi	= hexdec(substr($hash, 12, 4));
		$clock_seq_high	= hexdec(substr($hash, 16, 2));
		$clock_seq_low	= hexdec(substr($hash, 18, 2));
		$node		= hexdec(substr($hash, 20, 12));
		}

	return(sprintf("%08x-%04x-%04x-%02x%02x-%012x", $time_low, $time_mid, ($version << 12) | ($time_hi & 0x0FFF), 0x80 | ($clock_seq_high & 0x3F), $clock_seq_low, $node));
	}
?>
