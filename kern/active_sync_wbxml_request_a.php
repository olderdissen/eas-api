<?
# this function returns data as string

function active_sync_wbxml_request_a($input, & $position = 0, $codepage = 0, $level = 0)
	{
	$buffer = array();

	if(strlen($input) == 0)
		return(implode("\n", $buffer));

	if($position == 0)
		{
		$version = active_sync_wbxml_get_integer($input, $position);

		$public_identifier = active_sync_wbxml_get_multibyte_integer($input, $position);

		$charset = active_sync_wbxml_get_multibyte_integer($input, $position);

		$string_table_length = active_sync_wbxml_get_multibyte_integer($input, $position);

#		$version = "1." . $version;

		$public_identifier = active_sync_wbxml_get_public_identifier_name_by_id($public_identifier);

		$charset = active_sync_wbxml_get_charset_name_by_id($charset);

		@ mb_internal_encoding($charset);

		$string_table = "";

		while(strlen($string_table) < $string_table_length)
			{
			$string_table = $string_table . $input[$position ++];
			}

#		$buffer[] = "<!DOCTYPE unknown PUBLIC \"" . $public_identifier . "\" \"wbxml.dtd\">");
		$buffer[] = "<" . "?xml version=\"1.0\" encoding=\"" . $charset . "\"?" . ">";
		}

	# "xmlns" [ ":" <CodepageName> ] "=" <quot> "http://eas.microsoft.com/" ( "AirSync" | [ <CodepageName> "/" ] ) <quot>

	while($position < strlen($input))
		{
		$token = ord($input[$position ++]);

		switch($token)
			{
			case(0x00):
				# 0000 0000 - SWITCH_PAGE

				$data = active_sync_wbxml_get_integer($input, $position);

				$buffer[] = str_repeat("\t", $level) . sprintf("<!-- SWITCH_PAGE_0x%02X %s -->", $data, active_sync_wbxml_get_codepage_name_by_id($data));
				$buffer[] = active_sync_wbxml_request_a($input, $position, $data, $level);

				$position --; # huuuh ... mysterious ... my secret

				break;
			case(0x01):
				# 0000 0001 - END

				return(implode("\n", $buffer));
			case(0x02):
				# 0000 0010 - ENTITY

				active_sync_debug("ENTITY");

				break;
			case(0x03):
				# 0000 0011 - STR_I

				$data = active_sync_wbxml_get_string($input, $position);

				$buffer[] = str_repeat("\t", $level) . sprintf("<![CDATA[%s]]>", $data);

				break;
			case(0x04):
				# 0000 0100 - LITERAL

				active_sync_debug("LITERAL");

				break;
			case(0x40):
				# 0100 0000 - EXT_I_0

				active_sync_debug("EXT_I_0");

				break;
			case(0x41):
				# 0100 0001 - EXT_I_1

				active_sync_debug("EXT_I_1");

				break;
			case(0x42):
				# 0100 0010 - EXT_I_2

				active_sync_debug("EXT_I_2");

				break;
			case(0x43):
				# 0100 0011 - PI

				active_sync_debug("PI");

				break;
			case(0x44):
				# 0100 0100 - LITERAL_C

				active_sync_debug("LITERAL_C");

				break;
			case(0x80):
				# 1000 0000 - EXT_T_0

				active_sync_debug("EXT_T_0");

				break;
			case(0x81):
				# 1000 0001 - EXT_T_1

				active_sync_debug("EXT_T_1");

				break;
			case(0x82):
				# 1000 0010 - EXT_T_2

				active_sync_debug("EXT_T_2");

				break;
			case(0x83):
				# 1000 0011 - STR_T

				active_sync_debug("STR_T");

				break;
			case(0x84):
				# 1000 0100 - LITERAL_A

				active_sync_debug("LITERAL_A");

				break;
			case(0xC0):
				# 1100 0000 - EXT_0

				active_sync_debug("EXT_0");

				break;
			case(0xC1):
				# 1100 0001 - EXT_1

				active_sync_debug("EXT_1");

				break;
			case(0xC2):
				# 1100 0010 - EXT_2

				active_sync_debug("EXT_2");

				break;
			case(0xC3):
				# 1100 0011 - OPAQUE

				$data = active_sync_wbxml_get_multibyte_integer($input, $position);

				$data = active_sync_wbxml_get_string_length($input, $position, $data);

				$buffer[] = str_repeat("\t", $level) . sprintf("<![CDATA[%s]]>", $data);

				break;
			case(0xC4):
				# 1100 0100 - LITERAL_AC

				active_sync_debug("LITERAL_AC");

				break;
			default:
				# 0x05 - 0x3F
				# 0x45 - 0x7F
				# 0x85 - 0xBF (unused)
				# 0xC5 - 0xFF (unused)

				################################################################################
				# has no attribute
				################################################################################

				if(($token & 0x80) == 0x00)
					{
					}

				################################################################################
				# has attribute
				################################################################################

				if(($token & 0x80) == 0x80)
					{
					}

				################################################################################
				# has no content
				################################################################################

				if(($token & 0x40) == 0x00)
					{
					$data = active_sync_wbxml_get_token_name_by_id($codepage, $token);

					$buffer[] = str_repeat("\t", $level) . sprintf("<%s />", $data);
					}

				################################################################################
				# has content
				################################################################################

				if(($token & 0x40) == 0x40)
					{
					$data = active_sync_wbxml_get_token_name_by_id($codepage, $token);

					$buffer[] = str_repeat("\t", $level) . sprintf("<%s>", $data);

					$level ++;
					$buffer[] = active_sync_wbxml_request_a($input, $position, $codepage, $level);
					$level --;

					$data = active_sync_wbxml_get_token_name_by_id($codepage, $token);

					$buffer[] = str_repeat("\t", $level) . sprintf("</%s>", $data);
					}

				break;
			}

		if($level == 0)
			break;
		}

	return(implode("\n", $buffer));
	}
?>
