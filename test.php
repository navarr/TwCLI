<?php
	header("Content-type: text/plain");
	session_start();
	require_once("settings.php");
	require_once("twit2html.func.php");
	require_once("twitter/Twitter.lib.php");
	$t = new Twitter($key,$secret);
	$t-> require_login();
	print_r($t);
	print_r($t->api->statuses_homeTimeline());