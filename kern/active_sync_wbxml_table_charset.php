<?
function active_sync_wbxml_table_charset()
	{
	$retval = array();

	$retval[0x0003] = "US-ASCII";
	$retval[0x0004] = "ISO-8859-1";
	$retval[0x0005] = "ISO-8859-2";
	$retval[0x0006] = "ISO-8859-3";
	$retval[0x0007] = "ISO-8859-4";
	$retval[0x0008] = "ISO-8859-5";
	$retval[0x0009] = "ISO-8859-6";
	$retval[0x000A] = "ISO-8859-7";
	$retval[0x000B] = "ISO-8859-8";
	$retval[0x000C] = "ISO-8859-9";
	$retval[0x000D] = "ISO-8859-10";

	$retval[0x006A] = "UTF-8";
	$retval[0x006D] = "ISO-8859-13";

	$retval[0x006E] = "ISO-8859-14";
	$retval[0x006F] = "ISO-8859-15";
	$retval[0x0070] = "ISO-8859-16";
	$retval[0x0071] = "GBK";
	$retval[0x0072] = "GB18030";
	$retval[0x0073] = "OSD_EBCDIC_DF04_15";
	$retval[0x0074] = "OSD_EBCDIC_DF03_IRV";
	$retval[0x0075] = "OSD_EBCDIC_DF04_1";
	$retval[0x0076] = "ISO-11548-1";
	$retval[0x0077] = "KZ-1048";

	$retval[0x03E8] = "ISO-10646-UCS-2";
	$retval[0x03E9] = "ISO-10646-UCS-4";

	$retval[0x03F4] = "UTF-7";
	$retval[0x03F5] = "UTF-16BE";
	$retval[0x03F6] = "UTF-16LE";
	$retval[0x03F7] = "UTF-16";
	$retval[0x03F8] = "CESU-8";
	$retval[0x03F9] = "UTF-32";
	$retval[0x03FA] = "UTF-32BE";
	$retval[0x03FB] = "UTF-32LE";
	$retval[0x03FC] = "BOCU-1";

	$retval[0x07D8] = "DEC-MCS";
	$retval[0x07D9] = "IBM850";
	$retval[0x07DA] = "IBM852";
	$retval[0x07DB] = "IBM437";

	$retval[0x07DD] = "IBM862";

	$retval[0x07E9] = "GB2312";
	$retval[0x07EA] = "BIG5";

	$retval[0x07EC] = "IBM037";
	$retval[0x07ED] = "IBM038";
	$retval[0x07EE] = "IBM273";
	$retval[0x07EF] = "IBM274";
	$retval[0x07F0] = "IBM275";
	$retval[0x07F1] = "IBM277";
	$retval[0x07F2] = "IBM278";
	$retval[0x07F3] = "IBM280";
	$retval[0x07F4] = "IBM281";
	$retval[0x07F5] = "IBM284";
	$retval[0x07F6] = "IBM285";
	$retval[0x07F7] = "IBM290";
	$retval[0x07F8] = "IBM297";
	$retval[0x07F9] = "IBM420";
	$retval[0x07FA] = "IBM423";
	$retval[0x07FB] = "IBM424";
	$retval[0x07FC] = "IBM500";
	$retval[0x07FD] = "IBM851";
	$retval[0x07FE] = "IBM855";
	$retval[0x07FF] = "IBM857";
	$retval[0x0800] = "IBM860";
	$retval[0x0801] = "IBM861";
	$retval[0x0802] = "IBM863";
	$retval[0x0803] = "IBM864";
	$retval[0x0804] = "IBM865";
	$retval[0x0805] = "IBM868";
	$retval[0x0806] = "IBM869";
	$retval[0x0807] = "IBM870";
	$retval[0x0808] = "IBM871";
	$retval[0x0809] = "IBM880";
	$retval[0x080A] = "IBM891";
	$retval[0x080B] = "IBM903";
	$retval[0x080C] = "IBM904";
	$retval[0x080D] = "IBM905";
	$retval[0x080E] = "IBM918";
	$retval[0x080F] = "IBM1026";
	$retval[0x0810] = "EBCDIC-AT-DE";
	$retval[0x0811] = "EBCDIC-AT-DE-A";
	$retval[0x0812] = "EBCDIC-CA-FR";
	$retval[0x0813] = "EBCDIC-DK-NO";
	$retval[0x0814] = "EBCDIC-DK-NO-A";
	$retval[0x0815] = "EBCDIC-FI-SE";
	$retval[0x0816] = "EBCDIC-FI-SE-A";
	$retval[0x0817] = "EBCDIC-FR";
	$retval[0x0818] = "EBCDIC-IT";
	$retval[0x0819] = "EBCDIC-PT";
	$retval[0x081A] = "EBCDIC-ES";
	$retval[0x081B] = "EBCDIC-ES-A";
	$retval[0x081C] = "EBCDIC-ES-S";
	$retval[0x081D] = "EBCDIC-UK";
	$retval[0x081E] = "EBCDIC-US";
	$retval[0x081F] = "UNKNOWN-8BIT";
	$retval[0x0820] = "MNEMONIC";
	$retval[0x0821] = "MNEM";
	$retval[0x0822] = "VISCII";
	$retval[0x0823] = "VIQR";
	$retval[0x0824] = "KOI8-R";
	$retval[0x0825] = "HZ-GB-2312";
	$retval[0x0826] = "IBM866";
	$retval[0x0827] = "IBM775";
	$retval[0x0828] = "KOI8-U";
	$retval[0x0829] = "IBM00858";
	$retval[0x082A] = "IBM00924";
	$retval[0x082B] = "IBM01140";
	$retval[0x082C] = "IBM01141";
	$retval[0x082D] = "IBM01142";
	$retval[0x082E] = "IBM01143";
	$retval[0x082F] = "IBM01144";
	$retval[0x0830] = "IBM01145";
	$retval[0x0831] = "IBM01146";
	$retval[0x0832] = "IBM01147";
	$retval[0x0833] = "IBM01148";
	$retval[0x0834] = "IBM01149";
	$retval[0x0835] = "BIG5-HKSCS";
	$retval[0x0836] = "IBM1047";
	$retval[0x0837] = "PTCP154";
	$retval[0x0838] = "AMIGA-1251";

	$retval[0x08D3] = "TIS-620";
	$retval[0x08D4] = "CP50220";

	return($retval);
	}
?>
