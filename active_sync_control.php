<?
chdir(__DIR__);

include_once("active_sync_kern.php");

if(isset($argc) === false)
	exit;

$settings = array
	(
	"mode" => "help",
	"user-id" => "",
	"collection-id" => "",
	"server-id" => "",
	"app-name" => ""
	);

foreach($argv as $parameter)
	{
	foreach(array("user-id", "collection-id", "server-id") as $key)
		if(sscanf($parameter, "--%s=%s", $key, $value) == 2)
			$settings[$key] = $value;

	foreach(array("update-maildir", "update-version", "show", "help") as $key)
		if(sscanf($parameter, "--%s", $key) == 1)
			$settings["mode"] = $key;
	}

foreach($argv as $k => $v)
	{
	if($k == 0)
		$settings["app-name"] = $v;
	elseif($v == "-\?")
		$settings["mode"] = "help";
	}

if($settings["mode"] == "show")
	{
	if($settings["user-id"] == "")
		foreach(glob(DAT_DIR . "/*.sync") as $file)
			print(basename($file, ".sync") . "\n");
	elseif($settings["collection-id"] == "")
		foreach(glob(DAT_DIR . "/" . $settings["user-id"] . "/*") as $file)
			print(basename($file, "") . "\n");
	elseif($settings["server-id"] == "index")
		foreach(glob(DAT_DIR . "/" . $settings["user-id"] . "/" . $settings["collection-id"] . "/*.data") as $file)
			print(basename($file, ".data") . "\n");
	else
		print(json_encode(active_sync_get_settings(DAT_DIR . "/" . $settings["user-id"] . "/" . $settings["collection-id"] . "/" . $settings["server-id"] . ".data"), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

#if($settings["mode"] == "update-maildir")
#	active_sync_maildir_sync();

#if($settings["mode"] == "update-version")

if($settings["mode"] == "help")
	printf("Usage: %s [ <option> ]\n\n\t--update-maildir\tUpdates Maildir.\n\t--update-version\tUpdates Version.\n\n\t-?, --help\t\tPrint a help message and exit.\n", $settings["app-name"]);
?>
