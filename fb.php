<?php
	session_start();
        if($_REQUEST["fb_sig_in_iframe"])
        {
		$_SESSION["facebook"] = 1;
		if(!isset($_SESSION["theme"]))
		{
			$_SESSION["theme"] = "facebook";
		}
        }
?>
