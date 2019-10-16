<?
chdir(__DIR__);

include_once("active_sync_kern.php");

#$data = file_get_contents("php://input");
#if(isset($_SERVER["CONTENT_TYPE"]))
#	if($_SERVER["CONTENT_TYPE"] == "application/vnd.ms-sync.wbxml")
#		$data = active_sync_wbxml_request_parse_b($data);
#file_put_contents("log.txt", print_r($_SERVER, true) . $data, FILE_APPEND);

active_sync_http();

# our web interface has nothing to do with active sync in general.
# remove its content from active_sync_http and place it into this file.
?>
