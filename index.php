<?
chdir(__DIR__);

include_once("active_sync_kern.php");

$handle = [];

active_sync_http($handle);
?>
