<?= '<?xml version="1.0" encoding="utf-8" ?>' ?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US">
	<head>
		<title><?php if(isset($title)) { echo($title." - "); } ?>TwCLI</title>
		<link rel="shorcut icon" href="/new/files/static/img/favicon.ico" />
		<link rel="stylesheet" type="text/css" href="/new/files/static/css/base.css" />
		<?php if(isset($theme_url)) { ?><link rel="stylesheet" type="text/css" href="/<?= $theme_url ?>" /><?php } ?>
		<script src="/new/files/static/js/gears_init.js" type="text/javascript"></script>
	</head>
	<body>
		<?php
			if(isset($page))
			{
				if(file_exists($page.".ctp"))
				{
					require_once($page.".ctp");
				}
				elseif(file_exists(dirname(__FILE__)."/".$page.".ctp"))
				{
					require_once(dirname(__FILE__)."/".$page.".ctp");
				}
				else
				{
					echo($page);
				}
			}
			else
			{
				echo("No Content");
			}
		?>	
	</body>
</html>