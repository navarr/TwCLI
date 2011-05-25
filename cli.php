<?php
	session_start();
	require_once("settings.php");
	require_once("fb.php");
	require_once("twitter/Twitter.lib.php");
	$t = new Twitter($key,$secret);
	$uri = NULL;
	if($_SESSION["facebook"])
	{
		//$uri = "http://apps.facebook.com/twitter_cli/cli.php";
	}
	$t-> require_login($uri);
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
	if(isset($_GET["q"]))
		{ $q = $_GET["q"]; }
	else { $q = ""; }
	if(isset($_GET["r"]))
		{ $return_to = $_GET["r"]; }
?>
<!DOCTYPE html>
<html><head>
	<title>TwCLI</title>
	<link rel="stylesheet" href="base.css" />
	<link rel="stylesheet" href="<?=$thmurl?>" />
	<link rel="shortcut icon" href="favicon.ico" />
	<meta http-equiv="content-type" value="text/html;charset=utf-8" />
</head><body onkeypress="document.getElementById('command').focus();">
<iframe src="post.php" id="frame" style="height:70%;min-height:500px;width:100%;">

</iframe>
<form onsubmit="return sendToPost();"><table style="border:0px;padding:0px;margin:0px;width:100%;" cellspacing="0" cellpadding="0">
<tr>
<td style="width:5em;"><tt><span class="thisUserContainer">[<span class="thisUser"><?= $t->screen_name ?></span>]$</span></tt></td>
<td><tt><input autocomplete="off" type="text" id="command" style="width:100%;" value="<?=$q?>" /></tt></td>
<td style="text-align:center;"><tt id="counter">0</tt></td>
</tr>
</table>
<script type="text/javascript">document.getElementById('command').focus();</script>
</form>
<script type="text/javascript">
	updateCounter();
	setInterval(updateCounter,5);
	String.prototype.trim = function() {
		return this.replace(/^\s+|\s+$/g,"");
	}
	function updateCounter()
	{
		var com = document.getElementById("command");
		var amt = com.value.split("[&]")[com.value.split("[&]").length-1].trim().length;
		document.getElementById("counter").innerText = amt;
	}
//	document.getElementById("command").onkeypress = function() { document.counterInt = setInterval(updateCounter,5); };
//	document.getElementById("command").onunfocus = function() { clearInterval(document.counterInt); }
</script>
<br />
<?php if($t->geo_enabled) { ?>
<input type="checkbox" id="useLocationCheck" onclick="toggleUsingLocation();"/><label for="useLocationCheck"><tt>Attach Location</tt></label><br />
<?php } ?>
<tt>Tip: type "help" for a list of commands.</tt><br />
<tt>Tip: <strong>follow TwCLI</strong> for program updates.</tt>
<br /><br />
<tt><a href="post.php?source" class="hyperlink">Source BY-NC-ND</a></tt><br />
<tt>a <a href="http://www.gtaero.net/" class="hyperlink">子猫ちゃん</a> project</tt>
<br /><br />
<tt><a href="privacy.php" class="hyperlink">Privacy Policy</a></tt><br />
<tt><a href="mailto:navarr@koneko-chan.net?subject=TwCLI%20Bug%20Report&body=Please%20describe%20the%20problem%20and%20how%20to%20recreate%20it:%0D%0A" class="email">Report Bugs</a></tt>
<!-- #### START JAVASCRIPT LOVE #### //-->

<script type="text/javascript src="gears_init.js"></script>
<script type="text/javascript">
	<?php if($return_to) { ?>document.return_to = "<?= str_replace('"','',$return_to) ?>";<?php } ?>

	document.insertArray = new Array;
<?php if($_SESSION["insert_array"]) { foreach($_SESSION["insert_array"] as $k => $tc) { ?>
	document.insertArray[<?=$k?>] = "<?= str_replace('"','\"',$tc) ?>";
<?php }  } ?>
	document.urlExtra = "";
	document.geoTagging = 1;
	try {
		if(!google) { google = 0; }
	} catch(err) { google = 0; }
	// Start GeoLocation Query
	function isDefined(variable)
	{
		return (typeof(window[variable]) == "undefined")? false:true;
	}
	function retrieveLocation()
	{
		if(navigator.geolocation)
		{
			navigator.geolocation.getCurrentPosition(function(position)
			{
				setGeoLocation(position.coords.latitude,position.coords.longitude);
				setUsingLocation(true);
			});
		} else if(google && google.gears) {
			var siteName = 'TwCLI';
			var icon = '/img/logo.png';
			var msg = 'TwCLI uses Google Gears to attach Geolocation data to your twitter posts.';
//			var allowed = google.gears.factory.getPermission(siteName,icon,msg);
			var geloc = google.gears.factory.create('beta.geolocation');
			geloc.getPermission(siteName,icon,msg);
			geloc.getCurrentPosition(function(position)
			{
				setGeoLocation(position.latitude,position.longitude);
				setUsingLocation(true);
			},function(error) { });
		}
	}
//	retrieveLocation();

	function sendToPost()
	{
		clearInterval(document.counterInt);
		if(document.loggedOut)
		{
			alert("You logged out previously, we will now log you back in.");
			document.location = 'cli.php?q=' + encodeURIComponent(document.getElementById('command').value.replace("+","%2B"));
			return false;
		}
		var command = document.getElementById('command').value;
		var extra = "";
		if(document.usingLocation) { retrieveLocation();extra = document.urlExtra; }
		document.getElementById('frame').src = 'post.php?t=' + encodeURIComponent(document.getElementById('command').value.replace("+","%2B")) + extra;
		document.getElementById('command').value = "";
		var a = document.insertArray.length;
		if(a == 20) { document.insertArray.shift(); document.insertArray[19] = command; } else { document.insertArray[document.insertArray.length] = command; }
		document.lastUp = document.insertArray.length;
		disablePost();
		return false;
	}
	function setUsingLocation(bool)
	{
		document.usingLocation = bool;
		document.getElementById("useLocationCheck").checked = bool;
	}
	function toggleUsingLocation()
	{
		bool = document.getElementById("useLocationCheck").checked;
		setUsingLocation(bool);
		if(bool == true) { retrieveLocation(); }		
	}
	function inverseUsingLocation()
	{
		setUsingLocation(!(document.usingLocation));
	}
	function setGeoLocation(lat,long)
	{
		document.urlExtra = "&geo=" + lat + "," + long;
	}
	function unlockInput()
	{
		document.getElementById('command').disabled = false;
	}
	function insertText(text)
	{
		document.getElementById('command').value = text;
		document.getElementById('command').focus();
	}
	function disablePost()
	{
		document.getElementById('command').disabled = true;
	}
	function logOut()
	{
		document.loggedOut = true;
	}
	document.getElementById('command').onkeydown = function(e)
	{
		if(!document.lastUp && document.lastUp !== 0) { document.lastUp = document.insertArray.length; }
		var code;
		if(!e) var e = window.event;
		if(e.keyCode) code = e.keyCode;
		else if (e.which) code = e.which;
		var ele = document.getElementById('command');
		if(code == 38)
		{
			if(document.lastUp != 0)
			{
				document.lastUp--;
				document.getElementById('command').value = document.insertArray[document.lastUp];
				setCaretPosition(ele,ele.length);
			}
		}
		else if (code == 40)
		{
			if(document.lastUp != document.insertArray.length)
			{
				document.lastUp++;
				if(document.insertArray[document.lastUp])
					{ document.getElementById('command').value = document.insertArray[document.lastUp]; }
				else
					{ document.getElementById('command').value = ""; }

				setCaretPosition(ele,ele.length);
			}
		}
	} // */
	function setCaretPosition(elemId, caretPos) {
	    var elem = document.getElementById(elemId);
	
	    if(elem != null) {
	        if(elem.createTextRange) {
	            var range = elem.createTextRange();
	            range.move('character', caretPos);
	            range.select();
	        }
	        else {
	            if(elem.selectionStart) {
	                elem.focus();
	                elem.setSelectionRange(caretPos, caretPos);
	            }
	            else
	                elem.focus();
	        }
	    }
	}
</script>
</body></html>
