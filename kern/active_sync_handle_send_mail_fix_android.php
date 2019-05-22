<?
function active_sync_handle_send_mail_fix_android($request)
	{
	# Mime of SendMail makes problems here. stream of GT-S6802 is as follows:

	# 0000   03 01 6a 00 00 15 45 51 03 31 32 38 31 00 01 48  ..j...EQ.1281..H
	# 0010   03 00 01 50 c3 8c 44 61 74 65 3a 20 54 75 65 2c  ...P..Date: Tue,
	# ...
	# 05f0   6f 6d 2e 61 6e 64 72 6f 69 64 2e 65 6d 61 69 6c  om.android.email
	# 0600   5f 31 36 39 38 37 37 31 31 39 38 38 38 34 32 32  _169877119888422
	# 0610   2d 2d 0d 0a 0d 0a 01 01                          --......

	# 0x03			this is wbxml version
	# 0x01			this is wbxml publicid
	# 0x6A			this is wbxml charset
	# 0x00			this is wbxml strtbl
	# 0x00			SWITCH_PAGE
	# 0x15			ComposeMail
	# 0x45			SendMail
	# 0x51				ClientId
	# 0x03					STR_I (start)
	# 0x31 0x32 0x38 0x31				1281
	# 0x00					STR_I (end)
	# 0x01				END
	# 0x48				SaveInSentItems
	# 0x03					STR_I (start)
	# 0x00					STR_I (end)
	# 0x01				END
	# 0x50				Mime
	# 0xC3					OPAQUE
	# 0x8C 0x44					this is mb_int wich result in 1604 ... but 0x44 is first letter of string "Date: ..."
	# ...
	# 0x01				END
	# 0x01			END

	# wireshark states that data length of mime-data is 1604 bytes (0x8C 0x44)
	# after extracting mime-data by hand data length has 1536 (0x8C 0x00) bytes only

	# 0x8C - 1 000 1100
	# 0x44 - 0 100 0100
	# 0001100 1000100 -> 1604

	# 0x8C - 1 000 1100
	# 0x00 - 0 000 0000
	# 0001100 0000000 -> 1536

	# GT-S6802 removes this 0x00 wich result in a wrong calculation of length of the
	# upcoming OPAQUE data length

	# now we try to find this occurence
	# we check if "Date" follows after single byte (.?) of mb_int
	# so far this only works for mails up to 16256 bytes
	# 0xFF 0x00      -> 1 1111111 0 0000000           ->            0011 1111 1000 0000 -> 0x3F 0x80      -> 16256
	# 0xFF 0x00 0x00 -> 1 1111111 0 0000000 0 0000000 -> 00001 1111 1100 0000 0000 0000 -> 0x1F 0xC0 0x00 -> 2080768

	if($request["DeviceType"] != "SAMSUNGGTS6802")
		{
		}
	elseif(preg_match("/(.*\x50\xC3.?)(\x44\x61\x74\x65\x3A\x20.*)/", $request["wbxml"], $matches) == 1)
		$request["wbxml"] = $matches[1] . "\x00" . $matches[2];

	return($request);
	}
?>
