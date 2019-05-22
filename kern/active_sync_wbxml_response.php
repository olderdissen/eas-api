<?
class active_sync_wbxml_response
	{
	var $response = "\x03\x01\x6A\x00";
	var $codepage = 0xFF;

	function x_close($token = "")
		{
		$this->response = $this->response . chr(0x01);
		}

	function x_init()
		{
		$this->response = "";
		$this->response = $this->response . "\x03";
		$this->response = $this->response . "\x01";
		$this->response = $this->response . "\x6A";
		$this->response = $this->response . "\x00";

		$this->codepage = 0x00;
		}

	function x_print_multibyte_integer($integer)
		{
		$retval = "";
		$remain = 0x00;

		do
			{
			$retval = chr(($integer & 0x7F) | ($remain > 0x7F ? 0x80 : 0x00)) . $retval;

			$remain = $integer;

			$integer = $integer >> 7;
			}
		while($integer > 0x00);

		$this->response = $this->response . $retval;
		}

	function x_open($token, $contains_data = true, $has_attribute = false)
		{
		$data = active_sync_wbxml_get_token_id_by_name($this->codepage, $token);

		$data = $data | ($has_attribute === false ? 0x00 : 0x80);
		$data = $data | ($contains_data === false ? 0x00 : 0x40);

		$this->response = $this->response . chr($data);
		}

	function x_print($string)
		{
		if(strpos($string, "\x00") === false)
			{
			$this->response = $this->response . chr(0x03);
			$this->response = $this->response . $string;
			$this->response = $this->response . chr(0x00);
			}
		else
			{
			$this->x_print_bin($string);
			}
		}

	function x_print_bin($string)
		{
		$this->response = $this->response . chr(0xC3);

		$length = strlen($string);

		$this->x_print_multibyte_integer($length);

		$this->response = $this->response . $string;
		}

	function x_switch($codepage)
		{
		$codepage = (is_numeric($codepage) === false ? active_sync_wbxml_get_codepage_id_by_name($codepage) : $codepage);

		if($this->codepage != $codepage)
			{
			$this->codepage = $codepage;

			$this->response = $this->response . chr(0x00);
			$this->response = $this->response . chr($this->codepage);
			}
		}
	}
?>
