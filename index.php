<?php
	require_once("fb.php");
        $style = "terminal";
        if(isset($_SESSION["theme"]))
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
        if($_SESSION["theme_useurl"])
        {
                $thmurl = $_SESSION["theme_url"];
        }
?>
<html>
<head>
	<title>TwCLI</title>
	<link rel="stylesheet" href="<?= $thmurl ?>" />
</head>
<body style="text-align:center;">
TwCLI will require you to authorize it using Twitter the first time you use it.
<br /><br />
<a href="cli.php?q=follow+twcli+[%26]+help" class="hyperlink" style="font-size:72pt;">Start</a>
<br /><br />
<a href="privacy.php" class="hyperlink">Privacy Policy</a>
</body>
</html>
