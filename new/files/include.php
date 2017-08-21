<?php
	error_reporting(E_ALL);
	if(!session_id()) { session_start(); }
	require_once(dirname(__FILE__)."/site.php");
	require_once(dirname(__FILE__)."/twitter/Twitter.lib.php");
	require_once(dirname(__FILE__)."/twitter/twit2html.func.php");
	require_once(dirname(__FILE__)."/settings.php");
	$t = new Twitter($key,$secret);
	$site = new Site();
	$site-> templates_dir = dirname(__FILE__)."/static/pages";
	
	// Theme Settings
		if(isset($_SESSION["theme"]) && file_exists("styles/".$_SESSION["theme"].".css"))
		{
			$style = $_SESSION["theme"];
		} else {
			if(file_exists("styles/user/uid_".$t->uid.".css"))
			{
				$style = "user/uid_".$t->uid;
				$_SESSION["theme"] = $style;
			} else {
				$style = "terminal";
			}
		}
		$thmurl = "styles/".$style.".css?time=".time();
		if(isset($_SESSION["theme_useurl"]))
		{
			$thmurl = $_SESSION["theme_url"];
		}
		$site->set("theme_url",$thmurl);