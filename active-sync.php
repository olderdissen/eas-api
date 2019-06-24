<?
$timestamp_o = 1561414326;
$timestamp_n = $timestamp_o;

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
		if(sscanf($parameter, "--" . $key . "=%s", $value))
			$settings[$key] = $value;

	foreach(array("update-maildir", "update-version", "show", "help") as $key)
		if($parameter == "--" . $key)
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

if($settings["mode"] == "update-maildir")
	active_sync_maildir_sync();

if($settings["mode"] == "update-version")
	{
	foreach(array("kern", "web") as $dir)
		{
		$timestamp_x = filemtime(__DIR__ . "/" . $dir);
		$timestamp_n = max($timestamp_x, $timestamp_n);
		}

	foreach(array("images", "includes.css", "includes.js", "includes.php") as $dir)
		{
		$timestamp_x = filemtime(__DIR__ . "/web/" . $dir);
		$timestamp_n = max($timestamp_x, $timestamp_n);
		}

	if($timestamp_n > $timestamp_o)
		{
		$data = file(__DIR__ . "/kern/active_sync_get_version.php");
		eval($data[7]);
		$data[7] = "\t\$build\t\t= " . ($build + 1) . ";\n";
		file_put_contents(__DIR__ . "/kern/active_sync_get_version.php", implode("", $data));

		$data = file(__FILE__);
		$data[1] = "\$timestamp_o = " . (time() + 5) . ";\n";
		file_put_contents(__FILE__, implode("", $data));
		}
	}

if($settings["mode"] == "help")
	printf("Usage: %s [ <option> ]\n\n\t--update-maildir\tUpdates Maildir.\n\t--update-version\tUpdates Version.\n\n\t-?, --help\t\tPrint a help message and exit.\n", $settings["app-name"]);
?>
