<?
function active_sync_wbxml_request_b($input)
	{
	libxml_use_internal_errors(true); # or add LIBXML_NOWARNING | LIBXML_NOBLANKS

	$xml = new SimpleXMLElement("<active_sync />"); # xml container

	$root = $xml; # working element

	$namespaces = array(); # used codepages. do not add 0 as first codepage, maybe there will be a different one as the first used

	$stack = array(); # used parents

	$codepage = 0; # codepage at start

	$position = 0; # position on startup

	if(strlen($input) == 0)
		return("");

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
			$string_table = $string_table . $input[$position ++];
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

				$codepage = $data;

				$stack[] = $codepage;

				$namespaces[$codepage] = $codepage;

				break;
			case(0x01):
				# 0000 0001 - END

				array_pop($stack);

				$root = current($root->xpath(".."));

				break;
			case(0x02):
				# 0000 0010 - ENTITY


				active_sync_debug("ENTITY");

				break;
			case(0x03):
				# 0000 0011 - STR_I

				$data = active_sync_wbxml_get_string($input, $position);

				$root[] = $data;

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

				$root[] = $data;

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

					$stack[] = $codepage;

					$namespaces[$codepage] = $codepage;

					$name = active_sync_wbxml_get_codepage_name_by_id($codepage);

					$child = $root->addChild(($codepage == reset($namespaces) ? "xmlns:" : "xmlns:" . strtolower($name) . ":") . $data);
					}

				################################################################################
				# has content
				################################################################################

				if(($token & 0x40) == 0x40)
					{
					$data = active_sync_wbxml_get_token_name_by_id($codepage, $token);

					$stack[] = $codepage;

					$namespaces[$codepage] = $codepage;

					$name = active_sync_wbxml_get_codepage_name_by_id($codepage);

					$child = $root->addChild(($codepage == reset($namespaces) ? "xmlns:" : "xmlns:" . strtolower($name) . ":") . $data);

					$root = $child;
					}

				break;
			}
		}

	foreach($namespaces as $id => $codepage)
		{
		$name = active_sync_wbxml_get_codepage_name_by_id($codepage);

		$xml->children()->addAttribute(($codepage == reset($namespaces) ? "xmlns:xmlns" : "xmlns:xmlns:" . strtolower($name)), $name);
		}

	$xml = "<?" . "xml version=\"1.0\" encoding=\"" . $charset . "\"?" . ">" . $xml->children()->asXML();

	return($xml);
	}
?>
