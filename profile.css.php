<?php
	require_once("settings.php");
	require_once("twit2html.func.php");
	require_once("twitter/Twitter.lib.php");
	$t = new Twitter($key,$secret);
	header("Content-type: text/css");
	if(isset($_GET["user"])) { $r = $t->api->users_show($_GET["user"],TWITTER_SN); }
	else { $r = $t->api->account_verifyCredentials(); }
?>
body,input,tt
{
	font-family: "Lucida Console";
	font-size: 14px;
	color: #<?= $r["result"]["profile_text_color"] ?>;
}
input
{
	background-color: #<?= $r["result"]["profile_sidebar_fill_color"] ?>;
}
body
{
	background-color: #<?= $r["result"]["profile_background_color"] ?>;
<?php if($r["result"]["profile_background_image_url"]) {?>
	background-image: url('<?= $r["result"]["profile_background_image_url"] ?>');
<?php if($r["result"]["profile_background_tile"]) { ?>
	background-repeat: repeat;
<?php } else { ?>
	background-repeat: no-repeat;
<?php } ?>
<?php } ?>
}
body#terminal
{
	background: none !important;
}
iframe
{
	background-color: #FFFFFF;
	background-color: rgba(255,255,255,0.75);
	background-image: none;
}
a,a:visited,.otherUser,.mention,.hashtag,.retweet,.private,.markerDM
{
	color: #<?= $r["result"]["profile_link_color"]?>;
	text-decoration: none;
}
a:hover
{
	text-decoration: underline;
}