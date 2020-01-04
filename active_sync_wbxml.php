<?php

define("WBXML_SWITCH", 0x00);
define("WBXML_END", 0x01);
define("WBXML_ENTITY", 0x02);
define("WBXML_STR_I", 0x03);
define("WBXML_LITERAL", 0x04);
define("WBXML_EXT_I_0", 0x40);
define("WBXML_EXT_I_1", 0x41);
define("WBXML_EXT_I_2", 0x42);
define("WBXML_PI", 0x43);
define("WBXML_LITERAL_C", 0x44);
define("WBXML_EXT_T_0", 0x80);
define("WBXML_EXT_T_1", 0x81);
define("WBXML_EXT_T_2", 0x82);
define("WBXML_STR_T", 0x83);
define("WBXML_LITERAL_A", 0x84);
define("WBXML_EXT_0", 0xC0);
define("WBXML_EXT_1", 0xC1);
define("WBXML_EXT_2", 0xC2);
define("WBXML_OPAQUE", 0xC3);
define("WBXML_LITERAL_AC", 0xC4);

define("WBXML_TERMSTR", 0x00);

function active_sync_wbxml_get_charset_by_name($expression)
	{
	$table = active_sync_wbxml_table_charset();

	foreach($table as $id => $name)
		if($name == $expression)
			return($id);

	return(false);
	}

function active_sync_wbxml_get_charset_by_id($id)
	{
	$table = active_sync_wbxml_table_charset();

	return(isset($table[$id]) ? $table[$id] : false);
	}

function active_sync_wbxml_get_something_by_something($table, $key, $value, $select)
	{
	foreach($table as $row)
		if($row[$key] == $value)
			return($row[$select]);

	return(false);
	}

function active_sync_wbxml_get_codepage_by_namespace($expression)
	{
	$table = active_sync_wbxml_table_namespace();

	return(active_sync_wbxml_get_something_by_something($table, "namespaceURI", $expression, "id"));
	}

function active_sync_wbxml_get_codepage_by_prefix($expression)
	{
	$table = active_sync_wbxml_table_namespace();

	return(active_sync_wbxml_get_something_by_something($table, "prefix", $expression, "id"));
	}

function active_sync_wbxml_get_codepage_namespace_by_id($expression)
	{
	$table = active_sync_wbxml_table_namespace();

	return(active_sync_wbxml_get_something_by_something($table, "id", $expression, "namespaceURI"));
	}

function active_sync_wbxml_get_codepage_prefix_by_id($expression)
	{
	$table = active_sync_wbxml_table_namespace();

	return(active_sync_wbxml_get_something_by_something($table, "id", $expression, "prefix"));
	}

function active_sync_wbxml_get_multibyte_integer($input, & $position = 0)
	{
	$multi_byte = 0;

	while(1)
		{
		$char = $input[$position ++];

		$byte = ord($char);

	  	$multi_byte |= ($byte & 0x7F);

	  	if(($byte & 0x80) != 0x80)
			break;

		$multi_byte <<= 7;
		}

	return($multi_byte);
	}

function active_sync_wbxml_get_public_identifier_by_name($expression)
	{
	$table = active_sync_wbxml_table_public_identifier();

	foreach($table as $id => $name)
		if($name == $expression)
			return($id);

	return(false);
	}

function active_sync_wbxml_get_public_identifier_by_id($id)
	{
	$table = active_sync_wbxml_table_public_identifier();

	return(isset($table[$id]) ? $table[$id] : $id);
	}

function active_sync_wbxml_get_string($input, & $position = 0)
	{
	$string = "";

	while(1)
		{
		$char = $input[$position ++];

		if($char == "\x00")
			break;

		$string .= $char;
		}

	return($string);
	}

function active_sync_wbxml_get_token_by_name($codepage, $expression)
	{
	if(! is_numeric($codepage))
		$codepage = active_sync_wbxml_get_codepage_by_namespace($codepage);

	$table = active_sync_wbxml_table_token();

	if(isset($table[$codepage]))
		foreach($table[$codepage] as $id => $name)
			if($name == $expression)
				return($id);

	return(false);
	}

function active_sync_wbxml_get_token_by_id($codepage, $id)
	{
	if(! is_numeric($codepage))
		$codepage = active_sync_wbxml_get_codepage_by_namespace($codepage);

	$table = active_sync_wbxml_table_token();

	return(isset($table[$codepage][$id & 0x3F]) ? $table[$codepage][$id & 0x3F] : false);
	}

function active_sync_wbxml_request_a($input, & $position = 0, $codepage = 0, $level = 0)
	{
	$buffer = [];

	if(! strlen($input))
		return(implode("", $buffer));

	if($position == 0)
		{
		$version = ord($input[$position ++]);

		$public_identifier = active_sync_wbxml_get_multibyte_integer($input, $position);

		$charset = active_sync_wbxml_get_multibyte_integer($input, $position);

		$string_table_length = active_sync_wbxml_get_multibyte_integer($input, $position);

		$string_table = "";

		while(strlen($string_table) < $string_table_length)
			$string_table .= $input[$position ++];

		$public_identifier = active_sync_wbxml_get_public_identifier_by_id($public_identifier);

		$charset = active_sync_wbxml_get_charset_by_id($charset);

		@ mb_internal_encoding($charset);

		$buffer[] = sprintf('<?xml version="1.0" encoding="%s"?>', $charset);
		}

	while($position < strlen($input))
		{
		$token = ord($input[$position ++]);

		switch($token)
			{
			case(0x00):
				$data = ord($input[$position]);
				$position ++;

				$buffer[] = sprintf("<!-- SWITCH 0x%02X %s -->", $data, active_sync_wbxml_get_codepage_namespace_by_id($data));
				$buffer[] = active_sync_wbxml_request_a($input, $position, $data, $level);

				$position --; # huuuh ... mysterious ... my secret

				break;
			case(0x01):
				return(implode("", $buffer));
			case(0x03):
				$data = active_sync_wbxml_get_string($input, $position);

				$buffer[] = sprintf("%s", $data);

				break;
			case(0xC3):
				$length = active_sync_wbxml_get_multibyte_integer($input, $position);

				$data = substr($input, $position, $length);
				$position += $length;

				$buffer[] = sprintf("<![CDATA[%s]]>", $data);

				break;
			case(0x02):
			case(0x04):
			case(0x40):
			case(0x41):
			case(0x42):
			case(0x43):
			case(0x44):
			case(0x80):
			case(0x81):
			case(0x82):
			case(0x83):
			case(0x84):
			case(0xC0):
			case(0xC1):
			case(0xC2):
			case(0xC4):
				break;
			default:
				$data = active_sync_wbxml_get_token_by_id($codepage, $token);

				if($token & 0x40)
					{
					$buffer[] = sprintf("<%s>", $data);

					$level ++;
					$buffer[] = active_sync_wbxml_request_a($input, $position, $codepage, $level);
					$level --;

					$buffer[] = sprintf("</%s>", $data);
					}
				else
					$buffer[] = sprintf("<%s/>", $data);

				break;
			}

		if($level == 0)
			break;
		}

	return(implode("", $buffer));
	}

# https://www.w3.org/1999/06/NOTE-wbxml-19990624/

function active_sync_wbxml_load($input)
	{
	if(! strlen($input))
		return("");

	$position = 0;

	$version = ord($input[$position ++]);

	$public_identifier = active_sync_wbxml_get_multibyte_integer($input, $position);

	if($public_identifier == 0x00)
		$tableref = active_sync_wbxml_get_multibyte_integer($input, $position);

	$charset = active_sync_wbxml_get_multibyte_integer($input, $position);

	$length = active_sync_wbxml_get_multibyte_integer($input, $position);

	$string_table = "";

	while(strlen($string_table) < $length)
		$string_table .= $input[$position ++];

	if($public_identifier == 0x00)
		$public_identifier = active_sync_wbxml_get_string($string_table, $tableref);
	elseif($public_identifier != 0x01)
		$public_identifier = active_sync_wbxml_get_public_identifier_by_id($public_identifier);
	else
		$public_identifier = "-//AIRSYNC//DTD AirSync//EN";

	$charset = active_sync_wbxml_get_charset_by_id($charset);

	@ mb_internal_encoding($charset);

	$implementation = new DOMImplementation();

	$doctype = $implementation->createDocumentType("AirSync", $public_identifier, "http://www.microsoft.com/");

	$document = new DOMDocument();

	$document->encoding = $charset;
	$document->formatOutput = true;
	$document->preserveWhiteSpace = false;
	$document->version = "1.0";
	$document->appendChild($doctype);

	$namespaceURI = "AirSync";
	$baseURI = null;
	$pageindex = 0;
	$root = null;

	while($position < strlen($input))
		{
		$token = ord($input[$position ++]);

		switch($token)
			{
			case(0x00): # 5.8.4.7.2. Code Page Switch Token
				$pageindex = ord($input[$position ++]);

				$namespaceURI = active_sync_wbxml_get_codepage_namespace_by_id($pageindex);

				$prefix = "xmlns:" . active_sync_wbxml_get_codepage_prefix_by_id($pageindex);

				if(! $baseURI)
					$baseURI = $namespaceURI;

				if($namespaceURI != $baseURI)
					$root->setAttributeNS("http://www.w3.org/2000/xmlns/", $prefix, $namespaceURI);

				break;
			case(0x01): # 5.8.4.7.1. END Token
				$child = $child->parentNode;

				break;
			case(0x03): # 5.8.4.1 Strings (I)
				$content = active_sync_wbxml_get_string($input, $position);

				$newnode = $document->createTextNode($content);

				$child->appendChild($newnode);

				break;
			case(0x83): # 5.8.4.1 Strings (T)
				$tableref = active_sync_wbxml_get_multibyte_integer($input, $position);

				$content = active_sync_wbxml_get_string($string_table, $tableref);

				$newnode = $document->createTextNode($content);

				$child->appendChild($newnode);

				break;
			case(0xC3): # 5.8.4.6. Opaque Data
				$length = active_sync_wbxml_get_multibyte_integer($input, $position);

				$data = substr($input, $position, $length);

				$data = base64_encode($data);

				$position += $length;

				$newnode = $document->createCDATASection($data);

				$child->appendChild($newnode);

				break;
			case(0x02): # 5.8.4.3. Character Entity (createEntityReference)
			case(0x04): # 5.8.4.5. Literal Tag or Attribute Name
			case(0x40): # 5.8.4.2. Global Extension Tokens
			case(0x41): # 5.8.4.2. Global Extension Tokens
			case(0x42): # 5.8.4.2. Global Extension Tokens
			case(0x43): # 5.8.4.4. Processing Instruction (createProcessingInstruction)
			case(0x44): # 5.8.4.5. Literal Tag or Attribute Name (C)
			case(0x80): # 5.8.4.2. Global Extension Tokens
			case(0x81): # 5.8.4.2. Global Extension Tokens
			case(0x82): # 5.8.4.2. Global Extension Tokens
			case(0x84): # 5.8.4.5. Literal Tag or Attribute Name (A)
			case(0xC0): # 5.8.4.2. Global Extension Tokens
			case(0xC1): # 5.8.4.2. Global Extension Tokens
			case(0xC2): # 5.8.4.2. Global Extension Tokens
			case(0xC4): # 5.8.4.5. Literal Tag or Attribute Name (AC)
				die("not implemented");
			default:
				$qualifiedName = active_sync_wbxml_get_token_by_id($pageindex, $token);

				$newnode = $document->createElementNS($namespaceURI, $qualifiedName);

				if(! $baseURI)
					$baseURI = $namespaceURI;

				if(! $root)
					$root = $child = $document->appendChild($newnode);
				elseif($token & 0x40)
					$child = $child->appendChild($newnode);
				else
					$child->appendChild($newnode);

				break;
			}
		}

	return($document->saveXML());
	}

function active_sync_wbxml_as_wbxml($input)
	{
	$xml = new DOMDocument();

	$xml->formatOutput = true;
	$xml->preserveWhiteSpace = false;
	$xml->loadXML($input);

	$codepage = "AirSync";

	$retval = "\x03\x01\x6A\x00" . active_sync_wbxml_inner_wbxml($xml, $codepage);

	return($retval);
	}

function active_sync_wbxml_inner_wbxml($xml, & $codepage)
	{
	$retval = "";

	foreach($xml->childNodes as $node)
		{
		if($node->nodeType == 0x01) # XML_ELEMENT_NODE
			{
			if($codepage != $node->namespaceURI)
				{
				$token = active_sync_wbxml_get_codepage_by_namespace($node->namespaceURI);

				$retval .= "\x00" . chr($token);

				$codepage = $node->namespaceURI;
				}

			$token = active_sync_wbxml_get_token_by_name($codepage, $node->localName);

			if($node->childNodes->length == 0)
				$retval .= chr(0x00 | $token);
			else
				$retval .= chr(0x40 | $token) . active_sync_wbxml_inner_wbxml($node, $codepage) . "\x01";
			}
		elseif($node->nodeType == 0x03) # XML_TEXT_NODE
			$retval .= "\x03" . $node->nodeValue . "\x00";
		elseif($node->nodeType == 0x04) # XML_CDATA_SECTION_NODE
			{
			$helper = "";
			$remain = 0x00;
			$integer = strlen($node->nodeValue);

			do
				{
				$helper = chr(($integer & 0x7F) | ($remain > 0x7F ? 0x80 : 0x00)) . $helper;

				$remain = $integer;

				$integer >>= 7;
				}
			while($integer > 0x00);

			$retval .= chr(0xC3) . $helper . $node->nodeValue;
			}
		elseif($node->nodeType == 0x0A) # XML_DOCUMENT_TYPE_NODE
			$codepage = $node->name;
		else
			die("found unknown node: " . $node->nodeType);
		}

	return($retval);
	}

class active_sync_wbxml_response
	{
	var $codepage = 0x00;
	var $response = "\x03\x01\x6A\x00";

	function x_close($token = "")
		{
		$this->response .= chr(0x01);
		}

	function x_init()
		{
		$this->codepage = 0x00;
		$this->response = "\x03\x01\x6A\x00";
		}

	function x_print_multibyte_integer($integer)
		{
		$retval = "";
		$remain = 0x00;

		do
			{
			$retval = chr(($integer & 0x7F) | ($remain > 0x7F ? 0x80 : 0x00)) . $retval;

			$remain = $integer;

			$integer >>= 7;
			}
		while($integer > 0x00);

		$this->response .= $retval;
		}

	function x_open($token, $contains_data = true, $has_attribute = false)
		{
		$data = active_sync_wbxml_get_token_by_name($this->codepage, $token);

		if($has_attribute)
			$data |= 0x80;

		if($contains_data)
			$data |= 0x40;

		$this->response .= chr($data);
		}

	function x_text($token, $string)
		{
		if(strlen($string))
			{
			$this->x_open($token, true);
				$this->x_print($string);
			$this->x_close($token);
			}
		else
			$this->x_open($token, false);
		}

	function x_print($string)
		{
		if(strpos($string, "\x00") === false)
			$this->response .= "\x03" . $string . "\x00";
		else
			$this->x_print_bin($string);
		}

	function x_print_bin($string)
		{
		$this->response .= chr(0xC3);

		$length = strlen($string);

		$this->x_print_multibyte_integer($length);

		$this->response .= $string;
		}

	function x_switch($codepage)
		{
		if(! is_numeric($codepage))
			$codepage = active_sync_wbxml_get_codepage_by_namespace($codepage);

		if($this->codepage == $codepage)
			return;

		$this->codepage = $codepage;
		$this->response .= "\x00" . chr($codepage);
		}
	}

function active_sync_wbxml_table_charset()
	{
#	$location = "https://www.iana.org/assignments/character-sets/character-sets.xml";
#	$user_agent = "Mozilla/5.0 (X11; Linux x86_64; rv:69.0) Gecko/20100101 Firefox/69.0";

# 	$filename = basename($location);
#	if(! file_exists($file))
#		{
#		$context = stream_context_create(["http" => ["method" => "GET", "header" => "User-Agent: " . $user_agent]]);
#		$data = file_get_contents(location, false, $context);
#		file_put_contents(filename, $data);
#		}
#	$data = file_get_contents(filename);
#	$xml = simplexml_load_string($data);
#	foreach($xml->registry->record as $record)
#		if($record->name == $expression);
#			return($record->value);

	$retval = [
		3 => "US-ASCII",
		4 => "ISO-8859-1",
		5 => "ISO-8859-2",
		6 => "ISO-8859-3",
		7 => "ISO-8859-4",
		8 => "ISO-8859-5",
		9 => "ISO-8859-6",
		10 => "ISO-8859-7",
		11 => "ISO-8859-8",
		12 => "ISO-8859-9",
		13 => "ISO-8859-10",
		106 => "UTF-8",
		109 => "ISO-8859-13",
		110 => "ISO-8859-14",
		111 => "ISO-8859-15",
		112 => "ISO-8859-16",
		113 => "GBK",
		114 => "GB18030",
		115 => "OSD_EBCDIC_DF04_15",
		116 => "OSD_EBCDIC_DF03_IRV",
		117 => "OSD_EBCDIC_DF04_1",
		118 => "ISO-11548-1",
		119 => "KZ-1048",
		1000 => "ISO-10646-UCS-2",
		1001 => "ISO-10646-UCS-4",
		1012 => "UTF-7",
		1013 => "UTF-16BE",
		1014 => "UTF-16LE",
		1015 => "UTF-16",
		1016 => "CESU-8",
		1017 => "UTF-32",
		1018 => "UTF-32BE",
		1019 => "UTF-32LE",
		1020 => "BOCU-1",
		2008 => "DEC-MCS",
		2009 => "IBM850",
		2010 => "IBM852",
		2011 => "IBM437",
		2013 => "IBM862",
		2025 => "GB2312",
		2026 => "BIG5",
		2028 => "IBM037",
		2029 => "IBM038",
		2030 => "IBM273",
		2031 => "IBM274",
		2032 => "IBM275",
		2033 => "IBM277",
		2034 => "IBM278",
		2035 => "IBM280",
		2036 => "IBM281",
		2037 => "IBM284",
		2038 => "IBM285",
		2039 => "IBM290",
		2040 => "IBM297",
		2041 => "IBM420",
		2042 => "IBM423",
		2043 => "IBM424",
		2044 => "IBM500",
		2045 => "IBM851",
		2046 => "IBM855",
		2047 => "IBM857",
		2048 => "IBM860",
		2049 => "IBM861",
		2050 => "IBM863",
		2051 => "IBM864",
		2052 => "IBM865",
		2053 => "IBM868",
		2054 => "IBM869",
		2055 => "IBM870",
		2056 => "IBM871",
		2057 => "IBM880",
		2058 => "IBM891",
		2059 => "IBM903",
		2060 => "IBM904",
		2061 => "IBM905",
		2062 => "IBM918",
		2063 => "IBM1026",
		2064 => "EBCDIC-AT-DE",
		2065 => "EBCDIC-AT-DE-A",
		2066 => "EBCDIC-CA-FR",
		2067 => "EBCDIC-DK-NO",
		2068 => "EBCDIC-DK-NO-A",
		2069 => "EBCDIC-FI-SE",
		2070 => "EBCDIC-FI-SE-A",
		2071 => "EBCDIC-FR",
		2072 => "EBCDIC-IT",
		2073 => "EBCDIC-PT",
		2074 => "EBCDIC-ES",
		2075 => "EBCDIC-ES-A",
		2076 => "EBCDIC-ES-S",
		2077 => "EBCDIC-UK",
		2078 => "EBCDIC-US",
		2079 => "UNKNOWN-8BIT",
		2080 => "MNEMONIC",
		2081 => "MNEM",
		2082 => "VISCII",
		2083 => "VIQR",
		2084 => "KOI8-R",
		2085 => "HZ-GB-2312",
		2086 => "IBM866",
		2087 => "IBM775",
		2087 => "KOI8-U",
		2089 => "IBM00858",
		2090 => "IBM00924",
		2091 => "IBM01140",
		2092 => "IBM01141",
		2093 => "IBM01142",
		2094 => "IBM01143",
		2095 => "IBM01144",
		2096 => "IBM01145",
		2097 => "IBM01146",
		2098 => "IBM01147",
		2099 => "IBM01148",
		2100 => "IBM01149",
		2101 => "BIG5-HKSCS",
		2102 => "IBM1047",
		2103 => "PTCP154",
		2104 => "AMIGA-1251",
		2259 => "TIS-620",
		2260 => "CP50220",
		];

	return($retval);
	}

function active_sync_wbxml_table_namespace()
	{
	$retval = [
		["id" => 0, "namespaceURI" => "AirSync", "prefix" => "airsync"],
		["id" => 1, "namespaceURI" => "Contacts", "prefix" => "contacts"],
		["id" => 2, "namespaceURI" => "Email", "prefix" => "email"],
		["id" => 3, "namespaceURI" => "AirNotify", "prefix" => "airnotify"],
		["id" => 4, "namespaceURI" => "Calendar", "prefix" => "calendar"],
		["id" => 5, "namespaceURI" => "Move", "prefix" => "move"],
		["id" => 6, "namespaceURI" => "ItemEstimate", "prefix" => "itemestimate"],
		["id" => 7, "namespaceURI" => "FolderHierarchy", "prefix" => "folderhierarchy"],
		["id" => 8, "namespaceURI" => "MeetingResponse", "prefix" => "meetingresponse"],
		["id" => 9, "namespaceURI" => "Tasks", "prefix" => "tasks"],
		["id" => 10, "namespaceURI" => "ResolveRecipients", "prefix" => "resolverecipients"],
		["id" => 11, "namespaceURI" => "ValidateCerts", "prefix" => "validatecerts"],
		["id" => 12, "namespaceURI" => "Contacts2", "prefix" => "contacts2"],
		["id" => 13, "namespaceURI" => "Ping", "prefix" => "ping"],
		["id" => 14, "namespaceURI" => "Provision", "prefix" => "provision"],
		["id" => 15, "namespaceURI" => "Search", "prefix" => "search"],
		["id" => 16, "namespaceURI" => "GAL", "prefix" => "gal"],
		["id" => 17, "namespaceURI" => "AirSyncBase", "prefix" => "airsyncbase"],
		["id" => 18, "namespaceURI" => "Settings", "prefix" => "settings"],
		["id" => 19, "namespaceURI" => "DocumentLibrary", "prefix" => "documentlibrary"],
		["id" => 20, "namespaceURI" => "ItemOperations", "prefix" => "itemoperations"],
		["id" => 21, "namespaceURI" => "ComposeMail", "prefix" => "composemail"],
		["id" => 22, "namespaceURI" => "Email2", "prefix" => "email2"],
		["id" => 23, "namespaceURI" => "Notes", "prefix" => "notes"],
		["id" => 24, "namespaceURI" => "RightsManagement", "prefix" => "rm"],
		["id" => 25, "namespaceURI" => "Find", "prefix" => "find"]
		];

	return($retval);
	}

function active_sync_wbxml_table_public_identifier()
	{
	$retval = [
		0x02 => "-//WAPFORUM//DTD WML 1.0//EN",
		0x03 => "-//WAPFORUM//DTD WTA 1.0//EN",
		0x04 => "-//WAPFORUM//DTD WML 1.1//EN",

		0x05 => "-//WAPFORUM//DTD SI 1.0//EN",
		0x06 => "-//WAPFORUM//DTD SL 1.0//EN",
		0x07 => "-//WAPFORUM//DTD CO 1.0//EN",
		0x08 => "-//WAPFORUM//DTD CHANNEL 1.1//EN",
		0x09 => "-//WAPFORUM//DTD WML 1.2//EN",
		0x0A => "-//WAPFORUM//DTD WML 1.3//EN",
		0x0B => "-//WAPFORUM//DTD PROV 1.0//EN",
		0x0C => "-//WAPFORUM//DTD WTA-WML 1.2//EN",
		0x0D => "-//WAPFORUM//DTD CHANNEL 1.2//EN"
		];

	return($retval);
	}

function active_sync_wbxml_table_token()
	{
	# AirSync
	$_0 = array
		(
		0x05 => "Sync",
		0x06 => "Responses",
		0x07 => "Add",
		0x08 => "Change",
		0x09 => "Delete",
		0x0A => "Fetch",
		0x0B => "SyncKey",
		0x0C => "ClientId",
		0x0D => "ServerId",
		0x0E => "Status",
		0x0F => "Collection",
		0x10 => "Class",
		0x12 => "CollectionId",
		0x13 => "GetChanges",
		0x14 => "MoreAvailable",
		0x15 => "WindowSize",
		0x16 => "Commands",
		0x17 => "Options",
		0x18 => "FilterType",
		0x19 => "Truncation",
		0x1B => "Conflict",
		0x1C => "Collections",
		0x1D => "ApplicationData",
		0x1E => "DeletesAsMoves",
		0x20 => "Supported",
		0x21 => "SoftDelete",
		0x22 => "MIMESupport",
		0x23 => "MIMETruncation",
		0x24 => "Wait",
		0x25 => "Limit",
		0x26 => "Partial",
		0x27 => "ConversationMode",
		0x28 => "MaxItems",
		0x29 => "HeartbeatInterval"
		);

	# Contacts
	$_1 = array
		(
		0x05 => "Anniversary",
		0x06 => "AssistantName",
		0x07 => "AssistnamePhoneNumber",
		0x08 => "Birthday",
		0x09 => "Body",
		0x0A => "BodySize",
		0x0B => "BodyTruncated",
		0x0C => "Business2PhoneNumber",
		0x0D => "BusinessAddressCity",
		0x0E => "BusinessAddressCountry",
		0x0F => "BusinessAddressPostalCode",
		0x10 => "BusinessAddressState",
		0x11 => "BusinessAddressStreet",
		0x12 => "BusinessFaxNumber",
		0x13 => "BusinessPhoneNumber",
		0x14 => "CarPhoneNumber",
		0x15 => "Categories",
		0x16 => "Category",
		0x17 => "Children",
		0x18 => "Child",
		0x19 => "CompanyName",
		0x1A => "Department",
		0x1B => "Email1Address",
		0x1C => "Email2Address",
		0x1D => "Email3Address",
		0x1E => "FileAs",
		0x1F => "FirstName",
		0x20 => "Home2PhoneNumber",
		0x21 => "HomeAddressCity",
		0x22 => "HomeAddressCountry",
		0x23 => "HomeAddressPostalCode",
		0x24 => "HomeAddressState",
		0x25 => "HomeAddressStreet",
		0x26 => "HomeFaxNumber",
		0x27 => "HomePhoneNumber",
		0x28 => "JobTitle",
		0x29 => "LastName",
		0x2A => "MiddleName",
		0x2B => "MobilePhoneNumber",
		0x2C => "OfficeLocation",
		0x2D => "OtherAddressCity",
		0x2E => "OtherAddressCountry",
		0x2F => "OtherAddressPostalCode",
		0x30 => "OtherAddressState",
		0x31 => "OtherAddressStreet",
		0x32 => "PagerNumber",
		0x33 => "RadioPhoneNumber",
		0x34 => "Spouse",
		0x35 => "Suffix",
		0x36 => "Title",
		0x37 => "WebPage",
		0x38 => "YomiCompanyName",
		0x39 => "YomiFirstName",
		0x3A => "YomiLastName",
		0x3C => "Picture",
		0x3D => "Alias",
		0x3E => "WeightedRank"
		);

	# Email
	$_2 = array
		(
		0x05 => "Attachment",
		0x06 => "Attachments",
		0x07 => "AttName",
		0x08 => "AttSize",
		0x09 => "Att0id",
		0x0A => "AttMethod",
		0x0C => "Body",
		0x0D => "BodySize",
		0x0E => "BodyTruncated",
		0x0F => "DateReceived",
		0x10 => "DisplayName",
		0x11 => "DisplayTo",
		0x12 => "Importance",
		0x13 => "MessageClass",
		0x14 => "Subject",
		0x15 => "Read",
		0x16 => "To",
		0x17 => "Cc",
		0x18 => "From",
		0x19 => "ReplyTo",
		0x1A => "AllDayEvent",
		0x1B => "Categories",
		0x1C => "Category",
		0x1D => "DtStamp",
		0x1E => "EndTime",
		0x1F => "InstanceType",
		0x20 => "BusyStatus",
		0x21 => "Location",
		0x22 => "MeetingRequest",
		0x23 => "Organizer",
		0x24 => "RecurrenceId",
		0x25 => "Reminder",
		0x26 => "ResponseRequested",
		0x27 => "Recurrences",
		0x28 => "Recurrence",
		0x29 => "Type",
		0x2A => "Until",
		0x2B => "Occurrences",
		0x2C => "Interval",
		0x2D => "DayOfWeek",
		0x2E => "DayOfMonth",
		0x2F => "WeekOfMonth",
		0x30 => "MonthOfYear",
		0x31 => "StartTime",
		0x32 => "Sensitivity",
		0x33 => "TimeZone",
		0x34 => "GlobalObjId",
		0x35 => "ThreadTopic",
		0x36 => "MIMEData",
		0x37 => "MIMETruncated",
		0x38 => "MIMESize",
		0x39 => "InternetCPID",
		0x3A => "Flag",
		0x3B => "Status",
		0x3C => "ContentClass",
		0x3D => "FlagType",
		0x3E => "CompleteTime",
		0x3F => "DisallowNewTimeProposal"
		);

	# AirNotify
	$_3 = array
		(
		0x05 => "Notify",
		0x06 => "Notification",
		0x07 => "Version",
		0x08 => "Lifetime",
		0x09 => "DeviceInfo",
		0x0A => "Enable",
		0x0B => "Folder",
		0x0C => "ServerId",
		0x0D => "DeviceAddress",
		0x0E => "ValidCarrierProfiles",
		0x0F => "CarrierProfile",
		0x10 => "Status",
		0x11 => "Responses",
		0x12 => "Devices",
		0x13 => "Device",
		0x14 => "Id",
		0x15 => "Expiry",
		0x16 => "NotifyGUID"
		);

	# Calendar
	$_4 = array
		(
		0x05 => "TimeZone",
		0x06 => "AllDayEvent",
		0x07 => "Attendees",
		0x08 => "Attendee",
		0x09 => "Email",
		0x0A => "Name",
		0x0B => "Body",
		0x0C => "BodyTruncated",
		0x0D => "BusyStatus",
		0x0E => "Categories",
		0x0F => "Category",
		0x11 => "DtStamp",
		0x12 => "EndTime",
		0x13 => "Exception",
		0x14 => "Exceptions",
		0x15 => "Deleted",
		0x16 => "ExceptionStartTime",
		0x17 => "Location",
		0x18 => "MeetingStatus",
		0x19 => "OrganizerEmail",
		0x1A => "OrganizerName",
		0x1B => "Recurrence",
		0x1C => "Type",
		0x1D => "Until",
		0x1E => "Occurrences",
		0x1F => "Interval",
		0x20 => "DayOfWeek",
		0x21 => "DayOfMonth",
		0x22 => "WeekOfMonth",
		0x23 => "MonthOfYear",
		0x24 => "Reminder",
		0x25 => "Sensitivity",
		0x26 => "Subject",
		0x27 => "StartTime",
		0x28 => "UID",
		0x29 => "AttendeeStatus",
		0x2A => "AttendeeType",
		0x33 => "DisallowNewTimeProposal",
		0x34 => "ResponseRequested",
		0x35 => "AppointmentReplyTime",
		0x36 => "ResponseType",
		0x37 => "CalendarType",
		0x38 => "IsLeapMonth",
		0x39 => "FirstDayOfWeek",
		0x3A => "OnlineMeetingConfLink",
		0x3B => "OnlineMeetingExternalLink",
		0x3C => "ClientUid"
		);

	# Move
	$_5 = array
		(
		0x05 => "MoveItems",
		0x06 => "Move",
		0x07 => "SrcMsgId",
		0x08 => "SrcFldId",
		0x09 => "DstFldId",
		0x0A => "Response",
		0x0B => "Status",
		0x0C => "DstMsgId"
		);

	# GetItemEstimate
	$_6 = array
		(
		0x05 => "GetItemEstimate",
		0x06 => "Version",
		0x07 => "Collections",
		0x08 => "Collection",
		0x09 => "Class",
		0x0A => "CollectionId",
		0x0B => "DateTime",
		0x0C => "Estimate",
		0x0D => "Response",
		0x0E => "Status"
		);

	# FolderHierarchy
	$_7 = array
		(
		0x05 => "Folders",
		0x06 => "Folder",
		0x07 => "DisplayName",
		0x08 => "ServerId",
		0x09 => "ParentId",
		0x0A => "Type",
		0x0C => "Status",
		0x0E => "Changes",
		0x0F => "Add",
		0x10 => "Delete",
		0x11 => "Update",
		0x12 => "SyncKey",
		0x13 => "FolderCreate",
		0x14 => "FolderDelete",
		0x15 => "FolderUpdate",
		0x16 => "FolderSync",
		0x17 => "Count"
		);

	# MeetingResponse
	$_8 = array
		(
		0x05 => "CalendarId",
		0x06 => "CollectionId",
		0x07 => "MeetingResponse",
		0x08 => "RequestId",
		0x09 => "Request",
		0x0A => "Result",
		0x0B => "Status",
		0x0C => "UserResponse",
		0x0E => "InstanceId",
		0x10 => "ProposedStartTime",
		0x11 => "ProposedEndTime",
		0x12 => "SendResponse"
		);

	# Tasks
	$_9 = array
		(
		0x05 => "Body",
		0x06 => "BodySize",
		0x07 => "BodyTruncated",
		0x08 => "Categories",
		0x09 => "Category",
		0x0A => "Complete",
		0x0B => "DateCompleted",
		0x0C => "DueDate",
		0x0D => "UtcDueDate",
		0x0E => "Importance",
		0x0F => "Recurrence",
		0x10 => "Type",
		0x11 => "Start",
		0x12 => "Until",
		0x13 => "Occurrences",
		0x14 => "Interval",
		0x15 => "DayOfMonth",
		0x16 => "DayOfWeek",
		0x17 => "WeekOfMonth",
		0x18 => "MonthOfYear",
		0x19 => "Regenerate",
		0x1A => "DeadOccur",
		0x1B => "ReminderSet",
		0x1C => "ReminderTime",
		0x1D => "Sensitivity",
		0x1E => "StartDate",
		0x1F => "UtcStartDate",
		0x20 => "Subject",
		0x22 => "OrdinalDate",
		0x23 => "SubOrdinalDate",
		0x24 => "CalendarType",
		0x25 => "IsLeapMonth",
		0x26 => "FirstDayOfWeek"
		);

	# ResolveRecipients
	$_10 = array
		(
		0x05 => "ResolveRecipients",
		0x06 => "Response",
		0x07 => "Status",
		0x08 => "Type",
		0x09 => "Recipient",
		0x0A => "DisplayName",
		0x0B => "EmailAddress",
		0x0C => "Certificates",
		0x0D => "Certificate",
		0x0E => "MiniCertificate",
		0x0F => "Options",
		0x10 => "To",
		0x11 => "CertificateRetrieval",
		0x12 => "RecipientCount",
		0x13 => "MaxCertificates",
		0x14 => "MaxAmbiguousRecipients",
		0x15 => "CertificateCount",
		0x16 => "Availability",
		0x17 => "StartTime",
		0x18 => "EndTime",
		0x19 => "MergedFreeBusy",
		0x1A => "Picture",
		0x1B => "MaxSize",
		0x1C => "Data",
		0x1D => "MaxPictures"
		);

	# ValidateCerts
	$_11 = array
		(
		0x05 => "ValidateCert",
		0x06 => "Certificates",
		0x07 => "Certificate",
		0x08 => "CertificateChain",
		0x09 => "CheckCRL",
		0x0A => "Status"
		);

	# Contacts2
	$_12 = array
		(
		0x05 => "CustomerId",
		0x06 => "GovernmentId",
		0x07 => "IMAddress",
		0x08 => "IMAddress2",
		0x09 => "IMAddress3",
		0x0A => "ManagerName",
		0x0B => "CompanyMainPhone",
		0x0C => "AccountName",
		0x0D => "NickName",
		0x0E => "MMS"
		);

	# Ping
	$_13 = array
		(
		0x05 => "Ping",
		0x06 => "AutdStatus",
		0x07 => "Status",
		0x08 => "HeartbeatInterval",
		0x09 => "Folders",
		0x0A => "Folder",
		0x0B => "Id",
		0x0C => "Class",
		0x0D => "MaxFolders"
		);

	# Provision
	$_14 = array
		(
		0x05 => "Provision",
		0x06 => "Policies",
		0x07 => "Policy",
		0x08 => "PolicyType",
		0x09 => "PolicyKey",
		0x0A => "Data",
		0x0B => "Status",
		0x0C => "RemoteWipe",
		0x0D => "EASProvisionDoc",
		0x0E => "DevicePasswordEnabled",
		0x0F => "AlphanumericDevicePasswordRequired",
		0x10 => "RequireStorageCardEncryption",
		0x11 => "PasswordRecoveryEnabled",
		0x13 => "AttachmentsEnabled",
		0x14 => "MinDevicePasswordLength",
		0x15 => "MaxInactivityTimeDeviceLock",
		0x16 => "MaxDevicePasswordFailedAttempts",
		0x17 => "MaxAttachmentSize",
		0x18 => "AllowSimpleDevicePassword",
		0x19 => "DevicePasswordExpiration",
		0x1A => "DevicePasswordHistory",
		0x1B => "AllowStorageCard",
		0x1C => "AllowCamera",
		0x1D => "RequireDeviceEncryption",
		0x1E => "AllowUnsignedApplications",
		0x1F => "AllowUnsignedInstallationPackages",
		0x20 => "MinDevicePasswordComplexCharacters",
		0x21 => "AllowWiFi",
		0x22 => "AllowTextMessaging",
		0x23 => "AllowPOPIMAPEmail",
		0x24 => "AllowBluetooth",
		0x25 => "AllowIrDA",
		0x26 => "RequireManualSyncWhenRoaming",
		0x27 => "AllowDesktopSync",
		0x28 => "MaxCalendarAgeFilter",
		0x29 => "AllowHTMLEmail",
		0x2A => "MaxEmailAgeFilter",
		0x2B => "MaxEmailBodyTruncationSize",
		0x2C => "MaxEmailHTMLBodyTruncationSize",
		0x2D => "RequireSignedSMIMEMessages",
		0x2E => "RequireEncryptedSMIMEMessages",
		0x2F => "RequireSignedSMIMEAlgorithm",
		0x30 => "RequireEncryptionSMIMEAlgorithm",
		0x31 => "AllowSMIMEEncryptionAlgorithmNegotiation",
		0x32 => "AllowSMIMESoftCerts",
		0x33 => "AllowBrowser",
		0x34 => "AllowConsumerEmail",
		0x35 => "AllowRemoteDesktop",
		0x36 => "AllowInternetSharing",
		0x37 => "UnapprovedInROMApplicationList",
		0x38 => "ApplicationName",
		0x39 => "ApprovedApplicationList",
		0x3A => "Hash",
		0x3B => "AccountOnlyRemoteWipe"
		);

	# Search
	$_15 = array
		(
		0x05 => "Search",
		0x07 => "Store",
		0x08 => "Name",
		0x09 => "Query",
		0x0A => "Options",
		0x0B => "Range",
		0x0C => "Status",
		0x0D => "Response",
		0x0E => "Result",
		0x0F => "Properties",
		0x10 => "Total",
		0x11 => "EqualTo",
		0x12 => "Value",
		0x13 => "And",
		0x14 => "Or",
		0x15 => "FreeText",
		0x17 => "DeepTraversal",
		0x18 => "LongId",
		0x19 => "RebuildResults",
		0x1A => "LessThan",
		0x1B => "GreaterThan",
		0x1E => "UserName",
		0x1F => "Password",
		0x20 => "ConversionId",
		0x21 => "Picture",
		0x22 => "MaxSize",
		0x23 => "MaxPictures"
		);

	# GAL
	$_16 = array
		(
		0x05 => "DisplayName",
		0x06 => "Phone",
		0x07 => "Office",
		0x08 => "Title",
		0x09 => "Company",
		0x0A => "Alias",
		0x0B => "FirstName",
		0x0C => "LastName",
		0x0D => "HomePhone",
		0x0E => "MobilePhone",
		0x0F => "EmailAddress",
		0x10 => "Picture",
		0x11 => "Status",
		0x12 => "Data"
		);

	# AirSyncBase
	$_17 = array
		(
		0x05 => "BodyPreference",
		0x06 => "Type",
		0x07 => "TruncationSize",
		0x08 => "AllOrNone",
		0x0A => "Body",
		0x0B => "Data",
		0x0C => "EstimatedDataSize",
		0x0D => "Truncated",
		0x0E => "Attachments",
		0x0F => "Attachment",
		0x10 => "DisplayName",
		0x11 => "FileReference",
		0x12 => "Method",
		0x13 => "ContentId",
		0x14 => "ContentLocation",
		0x15 => "IsInline",
		0x16 => "NativeBodyType",
		0x17 => "ContentType",
		0x18 => "Preview",
		0x19 => "BodyPartReference",
		0x1A => "BodyPart",
		0x1B => "Status",
		0x1C => "Add",
		0x1D => "Delete",
		0x1E => "ClientId",
		0x1F => "Content",
		0x20 => "Location",
		0x21 => "Annotation",
		0x22 => "Street",
		0x23 => "City",
		0x24 => "State",
		0x25 => "Country",
		0x26 => "PostalCode",
		0x27 => "Latitude",
		0x28 => "Longitude",
		0x29 => "Accuracy",
		0x2A => "Altitude",
		0x2B => "AltitudeAccuracy",
		0x2C => "LocationUri",
		0x2D => "InstanceId"
		);

	# Settings
	$_18 = array
		(
		0x05 => "Settings",
		0x06 => "Status",
		0x07 => "Get",
		0x08 => "Set",
		0x09 => "Oof",
		0x0A => "OofState",
		0x0B => "StartTime",
		0x0C => "EndTime",
		0x0D => "OofMessage",
		0x0E => "AppliesToInternal",
		0x0F => "AppliesToExternalKnown",
		0x10 => "AppliesToExternalUnknown",
		0x11 => "Enabled",
		0x12 => "ReplyMessage",
		0x13 => "BodyType",
		0x14 => "DevicePassword",
		0x15 => "Password",
		0x16 => "DeviceInformation",
		0x17 => "Model",
		0x18 => "Imei",
		0x19 => "FriendlyName",
		0x1A => "OS",
		0x1B => "OSLanguage",
		0x1C => "PhoneNumber",
		0x1D => "UserInformation",
		0x1E => "EmailAddress",
		0x1F => "SmtpAddress",
		0x20 => "UserAgent",
		0x21 => "EnableOutboundSMS",
		0x22 => "MobileOperator",
		0x23 => "PrimaryEmailAddress",
		0x24 => "Accounts",
		0x25 => "Account",
		0x26 => "AccountId",
		0x27 => "AccountName",
		0x28 => "UserDisplayName",
		0x29 => "SendDisabled",
		0x2B => "RightsManagementInformation"
		);

	# DocumentLibrary
	$_19 = array
		(
		0x05 => "LinkId",
		0x06 => "DisplayName",
		0x07 => "IsFolder",
		0x08 => "CreationDate",
		0x09 => "LastModifiedDate",
		0x0A => "IsHidden",
		0x0B => "ContentLength",
		0x0C => "ContentType"
		);

	# ItemOperations
	$_20 = array
		(
		0x05 => "ItemOperations",
		0x06 => "Fetch",
		0x07 => "Store",
		0x08 => "Options",
		0x09 => "Range",
		0x0A => "Total",
		0x0B => "Properties",
		0x0C => "Data",
		0x0D => "Status",
		0x0E => "Response",
		0x0F => "Version",
		0x10 => "Schema",
		0x11 => "Part",
		0x12 => "EmptyFolderContents",
		0x13 => "DeleteSubFolders",
		0x14 => "UserName",
		0x15 => "Password",
		0x16 => "Move",
		0x17 => "DstFldId",
		0x18 => "ConversationId",
		0x19 => "MoveAlways"
		);

	# ComposeMail
	$_21 = array
		(
		0x05 => "SendMail",
		0x06 => "SmartForward",
		0x07 => "SmartReply",
		0x08 => "SaveInSentItems",
		0x09 => "ReplaceMime",
		0x0B => "Source",
		0x0C => "FolderId",
		0x0D => "ItemId",
		0x0E => "LongId",
		0x0F => "InstanceId",
		0x10 => "Mime",
		0x11 => "ClientId",
		0x12 => "Status",
		0x13 => "AccountId",
		0x15 => "Forwardees",
		0x16 => "Forwardee",
		0x17 => "ForwardeeName",
		0x18 => "ForwardeeEmail"
		);

	# Email2
	$_22 = array
		(
		0x05 => "UmCallerID",
		0x06 => "UmUserNotes",
		0x07 => "UmAttDuration",
		0x08 => "UmAttOrder",
		0x09 => "ConversationId",
		0x0A => "ConversationIndex",
		0x0B => "LastVerbExecuted",
		0x0C => "LastVerbExecutionTime",
		0x0D => "ReceivedAsBcc",
		0x0E => "Sender",
		0x0F => "CalendarType",
		0x10 => "IsLeapMonth",
		0x11 => "AccountId",
		0x12 => "FirstDayOfWeek",
		0x13 => "MeetingMessageType",
		0x15 => "IsDraft",
		0x16 => "Bcc",
		0x17 => "Send"
		);

	# Notes
	$_23 = array
		(
		0x05 => "Subject",
		0x06 => "MessageClass",
		0x07 => "LastModifiedDate",
		0x08 => "Categories",
		0x09 => "Category"
		);

	# RightsManagement
	$_24 = array
		(
		0x05 => "RightsManagementSupport",
		0x06 => "RightsManagementTemplates",
		0x07 => "RightsManagementTemplate",
		0x08 => "RightsManagementLicense",
		0x09 => "EditAllowed",
		0x0A => "ReplyAllowed",
		0x0B => "ReplyAllAllowed",
		0x0C => "ForwardAllowed",
		0x0D => "ModifyRecipientsAllowed",
		0x0E => "ExtractAllowed",
		0x0F => "PrintAllowed",
		0x10 => "ExportAllowed",
		0x11 => "ProgrammaticAccessAllowed",
		0x12 => "Owner",
		0x13 => "ContentExpiryDate",
		0x14 => "TemplateID",
		0x15 => "TemplateName",
		0x16 => "TemplateDescription",
		0x17 => "ContentOwner",
		0x18 => "RemoveRightsManagementProtection"
		);

	# Find
	$_25 = array
		(
		0x05 => "Find",
		0x06 => "SearchId",
		0x07 => "ExecuteSearch",
		0x08 => "MailboxSearchCriterion",
		0x09 => "Query",
		0x0A => "Status",
		0x0B => "FreeText",
		0x0C => "Options",
		0x0D => "Range",
		0x0E => "DeepTraversal",
		0x11 => "Response",
		0x12 => "Result",
		0x13 => "Properties",
		0x14 => "Preview",
		0x15 => "HasAttachments",
		0x16 => "Total",
		0x17 => "DisplayCc",
		0x18 => "DisplayBcc",
		0x19 => "GalSearchCriterion",
		0x20 => "MaxPictures",
		0x21 => "MaxSize",
		0x22 => "Picture"
		);

	$retval = array
		(
		0x00 => $_0,
		0x01 => $_1,
		0x02 => $_2,
		0x04 => $_4,
		0x05 => $_5,
		0x06 => $_6,
		0x07 => $_7,
		0x08 => $_8,
		0x09 => $_9,
		0x0A => $_10,
		0x0B => $_11,
		0x0C => $_12,
		0x0D => $_13,
		0x0E => $_14,
		0x0F => $_15,
		0x10 => $_16,
		0x11 => $_17,
		0x12 => $_18,
		0x13 => $_19,
		0x14 => $_20,
		0x15 => $_21,
		0x16 => $_22,
		0x17 => $_23,
		0x18 => $_24,
		0x19 => $_25
		);

	return($retval);
	}

function active_sync_xml_pretty($expression)
	{
	$expression = dom_import_simplexml($expression);

	$expression = $expression->ownerDocument;

	$expression->formatOutput = true;

	$expression = $expression->saveXML();

	return($expression);
	}

function active_sync_xml_privacy($expression)
	{
	if(! $expression)
		return(false);

	$expression = simplexml_load_string($expression);

	if(isset($expression->Response->Fetch->Properties->Data))
		$expression->Response->Fetch->Properties->Data = "[PRIVATE DATA]";

	if(isset($expression->Response->Store->Result))
		foreach($expression->Response->Store->Result as $result)
			$result->Properties = "[PRIVATE DATA]";

	if(isset($expression->Collections->Collection))
		foreach($expression->Collections->Collection as $collection)
			foreach(["Add", "Change"] as $action)
				if(isset($collection->Commands->$action))
					foreach($collection->Commands->$action as $whatever)
						$whatever->ApplicationData = "[PRIVATE DATA]";

	if(isset($expression->Policies->Policy->Data->EASProvisionDoc))
		$expression->Policies->Policy->Data->EASProvisionDoc = "[PRIVATE DATA]";

	if(isset($expression->RightsManagementInformation->Get->RightsManagementTemplates))
		$expression->RightsManagementInformation->Get->RightsManagementTemplates = "[PRIVATE DATA]";

	$expression = $expression->asXML(); # simplexml_load_string(string)->asXML()

	return($expression);
	}
?>
