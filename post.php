<?php
	if(isset($_GET["source"])) { highlight_file(__FILE__);die(); }
	session_start();
	require_once("settings.php");
	require_once("twit2html.func.php");
	require_once("twitter/Twitter.lib.php");
	$t = new Twitter($key,$secret);
	$uri = NULL;
	if($_REQUEST["fb_sig_in_iframe"])
		{ $uri = "http://apps.facebook.com/twitter_cli/cli.php"; }
	$t->require_login($uri);
	if(isset($_SESSION["theme"])) { $theme = $_SESSION["theme"]; } else { $theme = "terminal"; }
		$thmurl = "styles/".$theme.".css?time=".time();
	if($_SESSION["theme_useurl"] && isset($_SESSION["theme_url"]))
		{ $thmurl = $_SESSION["theme_url"]; }
	if(!isset($_SESSION["insert_array"]))
	{
		$_SESSION["commands"][] = "login <pre>".file_get_contents("motd.txt")."</pre>";
		$_SESSION["insert_array"] = array();
	}
	if(isset($_GET["geo"]) && count(explode(",",$_GET["geo"])) == 2)
	{
		$tgeo = explode(",",$_GET["geo"]);
		$latitude = $tgeo[0];
		$longitude = $tgeo[1];
	} else {
		$latitude = null;
		$longitude = null;
	}

	if(!isset($_SESSION["version"]))
	{
		$_SESSION["version"] = 1;
	}
?>
<html><head><title>CLI</title><link rel="stylesheet" href="<?= $thmurl ?>" /></head>
<body onload="window.scrollBy(0,9000000);" id="terminal" onkeypress="top.document.getElementById('command').focus();">
<script type="text/javascript">
	parent.unlockInput();
</script>
<?php
	function debug_start() { print("<!-- "); }
	function debug_end() { print(" -->"); }
	function get_str($command,$amt)
	{
		$tokens = explode(" ",$command);
		$count = 0;
		for($i = 0; $i < $amt ; $i++)
		{
			$count += strlen($tokens[$i])+1;
		}
		return substr($command,$count);
	}
	function htmlclean($text) { return str_replace(array("<",">"),array("&lt;","&gt;"),$text); }
	$pageend = "";
	if(isset($_GET["t"]))
	{
		$get = $_GET['t'];
		if(count($_SESSION["insert_array"]) == 20) { array_shift($_SESSION["insert_array"]);$_SESSION["insert_array"][] = $get; } else { $_SESSION["insert_array"][] = $get; }
		$expld = explode("[&]",$get);
	foreach($expld as $txt) {
		$txt = trim($txt);
	if($txt != "") {
		$command = urldecode($txt);
		$toks = explode(" ",$command);
		$comm = strtolower($toks[0]);
		if(substr($comm,0,1) == "/") { $comm = substr($comm,1); }

		$data = htmlclean($command)."<br />";

		if($comm == "clear" || $comm == "cls")
		{
			$data = "";
			unset($_SESSION["commands"]);
		}
		elseif($comm == "help" || $comm == "commands" || $comm == "command" || $comm == "man")
		{
			$toks[1] = strtolower($toks[1]);

			if($toks[1] == "post" || $toks[1] == "tweet" || $toks[1] == "twack" || $toks[1] == "update" || $toks[1] == "wall")
				{ $data .= "Syntax: post Message<br />Posts a new message to twitter.<br />Aliases: update, tweet, twack, wall<br />See also: reply"; }

			elseif($toks[1] == "reply")
				{ $data .= "Syntax: reply @User Message<br />Replies to User's last viewed tweet.<br />Basically an alias of post, but actually replies."; }
			
			elseif($toks[1] == "poke" || $toks[1] == "retaliate")
				{ $data .= "Syntax: poke @User<br />Opens a Window to Poke the specified user.<br />Aliases: retaliate"; }

			elseif($toks[1] == "delete" || $toks[1] == "del" || $toks[1] == "deletelast")
				{ $data .= "Syntax: delete [^id]<br />Deletes the specified (or last from TwCLI if not specified) tweet.<br />Aliases: del, deletelast"; }

			elseif($toks[1] == "rt" || $toks[1] == "retweet" || $toks[1] == "retwack")

				{ $data .= "Syntax: rt Username or ^ID<br />Retweets the last message posted by Username, or the message with the specified ID<br />Aliases: retweet"; }

			elseif($toks[1] == "whois" || $toks[1] == "lookup" || $toks[1] == "info" || $toks[1] == "finger")
				{ $data .= "Syntax: whois Username<br />Looks up the user's profile.<br />Aliases: lookup, info, finger, profile"; }

			elseif($toks[1] == "follow" || $toks[1] == "ln")
				{ $data .= "Syntax: follow Username<br />Follows Username.<br />Aliases: ln"; }

			elseif($toks[1] == "unfollow" || $toks[1] == "leave" || $toks[1] == "unlink")
				{ $data .= "Syntax: unfollow Username<br />Stop Following Username.<br />Aliases: leave, unlink"; }

			elseif($toks[1] == "timeline" || $toks[1] == "t")
				{ $data .= "Syntax: timeline [page]<br />Displays your timeline.<br />Aliases: friends, t, ls"; }

			elseif($toks[1] == "public")
				{ $data .= "Syntax: public<br />Displays the public timeline."; }

			elseif($toks[1] == "mentions" || $toks[1] == "replies")
				{ $data .= "Syntax: mentions [page]<br />Displays posts that mention you.<br />Aliases: replies, @"; }

			elseif($toks[1] == "view" || $toks[1] == "^")
				{ $data .= "Syntax: view ID<br />Displays the tweet corresponding to the posted ID.<br />Aliases: ^"; }
				
			elseif($toks[1] == "dms" || $toks[1] == "directs")
				{ $data .= "Syntax: dms [page]<br />Displays direct messages sent to you.<br />Aliases: directs"; }
				
			elseif($toks[1] == "sent")
				{ $data .= "Syntax: sent [page]<br />Displays direct messages you sent to others."; }
				
			elseif($toks[1] == "dm" || $toks[1] == "d" || $toks[1] == "direct")
				{ $data .= "Syntax: dm User Message<br />Sends the user a message.<br />Aliases: d direct"; }

			elseif($toks[1] == "fromuser" || $toks[1] == "from")
				{ $data .= "Syntax: from User [page]<br />Displays the specified user's latest posts.<br />Aliases: fromuser, user, u, ls"; }

			elseif($toks[1] == "ls")
				{ $data .= "Syntax: from [user] [page]<br />Displays the latest posts from the timeline (or user if specified).<br />Aliases (timeline): timeline, t, friends<br />Aliases: (user): from, fromuser"; }

			elseif($toks[1] == "listcreate" || $toks[1] == "createlist")
				{ $data .= "Syntax: listcreate ListName [privacy] [description]<br />Creates a list.  The Privacy parameter should be either \"Public\" or \"Private\" and is not required.<br />Aliases: createlist"; }

			elseif($toks[1] == "listdelete" || $toks[1] == "listdel" || $toks[1] == "dellist" || $toks[1] == "deletelist")
				{ $data .= "Syntax: listdelete ListName<br />Deletes a list.<br />Aliases: listdel, dellist, deletelist"; }

			elseif($toks[1] == "listadd" || $toks[1] == "addlist" || $toks[1] == "addtolist" || $toks[1] == "add2list")
				{ $data .= "Syntax: listadd list[, list2, list<i>i</i>, ...] user[, user2, user<i>i</i>, ...]<br />Adds User(s) to the specified List(s).<br />Aliases: addlist, addtolist, add2list"; }

			elseif($toks[1] == "listrename" || $toks[1] == "renamelist")
				{ $data .= "Syntax: listrename OldName NewName<br />Renames a list.<br />Aliases: renamelist"; }

			elseif($toks[1] == "listdescription" || $toks[1] == "listdesc" || $toks[1] == "desclist")
				{ $data .= "Syntax: listdesc Name Description.<br />Adds or Changes the Description of a List.<br />Aliases: listdescription, desclist"; }

			elseif($toks[1] == "list")
				{ $data .= "Syntax: list User List<br />Displays the list User/List."; }

			elseif($toks[1] == "lists")
				{ $data .= "Syntax: lists [user]<br />Displays the lists made by the specified user.  If user is not specified, displays the lists you're subscribed to."; }

			elseif($toks[1] == "mylists")
				{ $data .= "Syntax: mylists<br />Displays the lists you've created.  Basically an alias for lists ".$t->screen_name; }

			elseif($toks[1] == "clear" || $toks[1] == "cls")
				{ $data .= "Syntax: clear<br />Clears the window of all text.<br />Aliases: cls"; }

			elseif($toks[1] == "logout" || $toks[1] == "end" || $toks[1] == "quit" || $toks[1] == "exit")
				{ $data .= "Syntax: logout<br />Logs you out and redirects you to the main page.<br />Aliases: end, quit, exit"; }

			elseif($toks[1] == "help" || $toks[1] == "commands" || $toks[1] == "command" || $toks[1] == "man")
				{ $data .= "Syntax: help [command]<br />Displays help text on the command.<br />Aliases: commands, command, man"; }

			elseif($toks[1] == "&")
				{ $data .= "Syntax: command1 [&] command2<br />Seperates commands so two or more may be entered at once."; }

			elseif($toks[1] == "postit")
				{ $data .= "Syntax: postit<br />Posts the last command ran by TwCLI.  Useful if you accidentally wrote a post without using the command."; }

			elseif($toks[1] == "edit")
				{ $data .= "Syntax: edit<br />Copies your last command back to the command line.  Useful if you make a typo."; }

			elseif($toks[1] == "oh")
				{ $data .= "Syntax: oh Message<br />Alias for post OH: Message"; }

			elseif($toks[1] == "promote")
				{ $data .= "Syntax: promote<br />Sets your status that you're using TwCLI."; }

			elseif($toks[1] == "themelist")
				{ $data .= "Syntax: themelist<br />Lists all themes."; }

			elseif($toks[1] == "themeset")
				{ $data .= "Syntax: themeset Theme<br />Sets Theme.<br />Aliases: settheme"; }
		
			elseif($toks[1] == "themecss")
				{ $data .= "Syntax: themecss URL<br />Loads a theme from a custom URL."; }

			elseif($toks[1] == "search")
				{ $data .= "Syntax: search Query<br />Launches a Twitter Search"; }

			elseif($toks[1] == "hashtag")
				{ $data .= "Syntax: hashtag #tag<br />Launches a Twitter Search for the specified hashtag.  Basically an alias for search #tag"; }

			elseif($toks[1] == "google")
				{ $data .= "Syntax: google Search<br />Launches a Google Search"; }
				
			elseif($toks[1] == "setloc")
				{ $data .= "Syntax: setloc [New Location (or Geo-Enabled)]<br />Sets the Location on your Twitter Profile"; }
				
			elseif($toks[1] == "originof")
				{ $data .= "Syntax: originof ^tweet<br />Displays the message the specified tweet was in reply to."; }

			elseif(!$toks[1])
			{
				$data.= "List of Commands:<br />";
				$data.= "post delete reply originof poke rt oh whois follow unfollow timeline public mentions dms sent dm from list lists mylists listcreate listadd listrename clear logout help & postit edit promote themelist themeset themecss search hashtag google setloc<br /><br />";
				$data.= "type \"help command\" for more information on the command.<br /><br />";
				$data.= "Note: [var] denotes an optional var.  The brackets are not part of the command. (Excluding [&]).";
			}
			else
			{
				$data .= "No help available, yet.";
			}
		}
		elseif($comm == "motd")
		{
			$data .= "<pre>".file_get_contents("motd.txt")."</pre>";
		}
		elseif($comm == "del" || $comm == "delete" || $comm == "deletelast")
		{
			if(is_array($_SESSION["last_post"]) || ($toks[1] && substr($toks[1],0,1) == "^"))
			{
				if(substr($toks[1],0,1) == "^")
				{
					if(substr($toks[1],0,3) == "^0x") { $id = hexdec(substr($toks[1],3)); }
					else { $id = substr($toks[1],1); }
				} else { $id = $_SESSION["last_post"]["id"]; }
				$r = $t->api->statuses_destroy($id);
				if(!$r["result"]["error"])
					{ $data .= "Deleted Post \"".twit2html($r["result"]["text"])."\""; }
				else
					{ $data .= $r["result"]["error"]; }
			}
			else
				{ $data .= "No Post to Delete."; }
		}
		elseif($comm == "post" || $comm == "update" || $comm == "postit" || $comm == "promote" || $comm == "tweet" || $comm == "oh" || $comm == "reply" || (substr($comm,0,1) == "@" && $comm != "@") || $comm == "twack" || $comm == "p" || $comm == "wall")
		{
			$str = get_str($command,1);
			$place = null;
			if(isset($_SESSION["place"])) { $place = $_SESSION["place"]; }
			if($comm == "postit") { $str = $_SESSION["last_command"]; }
			if($comm == "promote") { $str = "I'm using TwCLI!  http://twcli.koneko-chan.net"; }
			if($comm == "oh") { $str = "OH: ".$str; }
			$reply_to = null;
			if($comm == "reply" || substr($comm,0,1) == "@")
			{
				if(substr($toks[1],0,1) == "^")
				{
					$str = get_str($command,2);
					$id = substr($toks[1],1);
					if(substr($id,0,2) == "0x") { $id = hexdec(substr($id,2)); }
					$a = $t->api->statuses_show($id);
					$user = $a["result"]["user"]["screen_name"];
					$reply_to = $id;
					$str = "@{$user} {$str}";
				} else {
					if(substr($comm,0,1) == "@") { $str = $command; }
					if(substr($str,0,1) != "@") { $str = "@".$str; }
					$user = explode(" ",$str);
					$user = substr($user[0],1);
					if(substr($user,-1) == ":") { $user = substr($user,0,strlen($user)-1); }
					if(isset($_SESSION["last_users"][strtoupper($user)]))
						{ $reply_to = $_SESSION["last_users"][strtoupper($user)]["id"]; }
				}
			}
			$r = $t->api->statuses_update($str,$reply_to,$latitude,$longitude,$place,true);
			if(mb_strlen($str,"utf8") > 140 && $r["result"]["id"] == $_SESSION["last_post"]["id"])
			{
				$data .= "Post is too long by ".(strlen($str)-140)." characters.";
				print("<script type=\"text/javascript\">parent.insertText(\"".$comm." ".str_replace('"','\"',$str)."\");</script>");
			}
			elseif(!$r["result"]["error"])
			{
				$data .= "Posted \"".twit2html($r["result"]["text"])."\"";
				$_SESSION["last_post"]["id"] = $r["result"]["id"];
				$_SESSION["last_post"]["text"] = $r["result"]["text"];
			}
			else
				{ $data .= "Error: ".$r["result"]["error"]; }
		}
		elseif($comm == "edit")
		{
			$data .= "Copied your previous command.";
			print("<script type=\"text/javascript\">parent.insertText(\"".str_replace('"','\"',$_SESSION["last_command"])."\");</script>");			
		}
		elseif($comm == "rt" || $comm == "retweet" || $comm == "retwack")
		{
			$user = $toks[1];
			if(substr($user,0,1) == "@") { $user = substr($user,1); }
			if(!$user)
			{
				$data .= "Error: Requires one parameter, username or tweet id to retweet.";
			}
			elseif(isset($_SESSION["last_users"][strtoupper($user)]) || substr($user,0,1) == "^")
			{
				if(substr($user,0,1) == "^")
				{
					if(substr($user,0,3) == "^0x") { $retweet_id = hexdec(substr($user,3)); }
					else { $retweet_id = substr($user,1); }
				} else {
					$retweet_id = $_SESSION["last_users"][strtoupper($user)]["id"];
				}
				$r = $t->api->statuses_retweet($retweet_id);
				if(!$r["result"]["error"])
					{ $data .= "Retweeted @<span class=\"mention\">{$r["result"]["retweeted_status"]["user"]["screen_name"]}</span>'s post: \"".twit2html($r["result"]["retweeted_status"]["text"])."\""; }
				else
				{
					$user = $user;
					if(substr($text,0,2) != "RT")
					{
						if(isset($_SESSION["last_users"][strtoupper($user)]["sn"])) { $user = $_SESSION["last_users"][strtoupper($user)]["sn"]; }
						if(strlen($text) > (140 - (5 + strlen($user)))) { $post = substr($text,0,strlen($text)-13)."..."; } else { $post = $text; }
						$post = "RT @$user $post";
					} else { $post = $text; }
					print("<script type=\"text/javascript\">parent.insertText(\"post ".str_replace('"','\"',$post)."\");</script>");
					$data .= "Automatic Retweet Failed, Manual Post in command bar.";					/*
					$r = $t->api->statuses_update($post);
					if(!$r["error"])
					{
						$data .= "Posted: \"".twit2html($r[text])."\"";
						$_SESSION["last_post"]["id"] = $r["id"];
						$_SESSION["last_post"]["text"] = $r["text"];
					}
					else
						{ $data .= $r["error"]; }
					*/
				}
			}
			else
				{ $data .= "Error: Could not find post to retweet."; }
		}
		elseif($comm == "echo" || $comm == "print")
		{
			$text = substr($command,strlen($comm)+1);
			if(strtolower($t->screen_name) != "navarr") { $text = htmlclean($text); }
			$data .= $text;
		}
		elseif($comm == "follow" || $comm == "ln")
		{
			$r = $t->api->friendships_create($toks[1],TWITTER_SN);
			if(!$r["error"])
				{ $data .= "Now Following $toks[1]."; }
			else
				{ $data .= "Error: {$r["result"]["error"]}"; }
		}
		elseif($comm == "unfollow" || $comm == "leave" || $comm == "unlink")
		{
			$r = $t->api->friendships_destroy($toks[1],TWITTER_SN);
			if(!$r["error"])
				{ $data .= "No Longer Following $toks[1]."; }
			else
				{ $data .= "Error: {$r["result"]["error"]}"; }
		}
		elseif($comm == "whois" || $comm == "lookup" || $comm == "info" || $comm == "finger" || $comm == "profile")
		{
			$user = $toks[1];
			if(substr($user,0,1) == "@") { $user = substr($user,1); }
			$r = $t->api->users_show($user,TWITTER_SN);
			if(!$r["result"]["error"])
			{
				$data .= "<br />Profile For: {$r["result"]["screen_name"]} <br />";
				if($r["result"]["name"])
				{
					$data .= "Name: ".htmlclean($r["result"]["name"])." ";
					if($r["result"]["verified"]) { $data .= "(Verified)"; }
					$data .= "<br />";
				}
				if($r["result"]["description"])
					{ $data .= "Bio: ".twit2html($r["result"]["description"])."<br />"; }
				if($r["result"]["url"])
					{ $data .= "Website: ".twit2html($r["result"]["url"])."<br />"; }
				if($r["result"]["location"])
					{ $data .= "Location: ".htmlclean($r["result"]["location"])." <br />"; }
				if($r["result"]["time_zone"])
					{ $data .= "Timezone: ".htmlclean($r["result"]["time_zone"])." <br />"; }
				$data .= "User Since: {$r["result"]["created_at"]} <br />";
				$data .= "Following: {$r["result"]["friends_count"]} - Followers: {$r["result"]["followers_count"]} <br />";
				$data .= "{$r["result"]["screen_name"]} has posted {$r["result"]["statuses_count"]} ";
					if($r["result"]["statuses_count"] == 1) { $data .= "time"; } else { $data .= "times"; }
					$data .= "<br />";
				if($r["result"]["protected"]) { $data .= "Account is protected.<br />";$subrq = "request to "; } else { $subrq = ""; }
				$data .= "<br />";
				if($r["result"]["status"])
				{
					$data .= "<span class=\"otherUserContainer\">&lt;<span class=\"otherUser\">{$r["result"]["screen_name"]}</span>&gt;</span> ".twit2html($r["result"]["status"]["text"])."<br /><br />";
					$_SESSION["last_users"][strtoupper($r["result"]["screen_name"])] = array
					(
						"id" => $r["result"]["status"]["id"],
						"uid" => $r["result"]["id"],
						"text" => $r["result"]["status"]["text"],
						"sn" => $r["result"]["screen_name"]
					);
				}
				if(strtolower($r["result"]["screen_name"]) == strtolower($t->screen_name))
					{ /* do nothing */ }
				elseif(!$r["result"]["following"])
				{
					$data .= "Type FOLLOW {$r["result"]["screen_name"]} to ".$subrq."start following.<br />";
				} else {
					$data .= "Type LEAVE {$r["result"]["screen_name"]} to stop following.<br />";
				}
				$data .= "Type FROM {$r["result"]["screen_name"]} to read {$r["result"]["screen_name"]}'s latest posts.<br />";
			//	$data .= "<br />";
			//	$r2 = $t->api->account_rateLimitStatus();
			//	$data .= "{$r2["result"]["remaining_hits"]} queries left available this hour.";
			}
			else
				{ $data .= "Error {$r["result"]["error"]}"; }
		}
		elseif($comm == "timeline" || $comm == "mentions" || $comm == "replies" || $comm == "fromuser" || $comm == "from" || $comm == "list" || $comm == "user" || $comm == "u" || $comm == "friends" || $comm == "@" || $comm == "public" || $comm == "t" || $comm == "ls" || $comm == "view" || substr($comm,0,1) == "^" || $comm == "originof" || $comm=="thread")
		{
			$tError = 0;
			$tErrorMsg = "";
			if($comm == "fromuser" || ($comm == "ls" && $tok[1])) { $pi = 2; } else { $pi = 1; }
			if((int)$toks[$pi] > 1) { $page = $toks[$pi]; } else { $page = 1; }
			if($comm == "timeline" || $comm == "friends" || $comm == "t" || ($comm == "ls" && (!$toks[1] || intval($toks[1]) > 0))) { $r = $t->api->statuses_homeTimeline(null,null,20,$page); }
			if($comm == "mentions" || $comm == "replies" || $comm == "@") { $r = $t->api->statuses_mentions(null,null,20,$page); }
			if($comm == "view" || substr($comm,0,1) == "^")
			{
				if($comm != "^" && substr($comm,0,1) == "^") { $toks[1] = substr($comm,1); }
				if(substr($toks[1],0,2) == "0x") { $toks[1] = hexdec(substr($toks[1],2)); }
				if($toks[1])
					{ $a = $t->api->statuses_show($toks[1]);$r = array();$r["result"][0] = $a["result"]; }
				else
				{
					$tError = 1;
					$tErrorMsg = "Must specify a status to show.";
				}
			}
			if($comm == "originof")
			{
				if(substr($toks[1],0,1) == "^") { $toks[1] = substr($toks[1],1); }
				if(substr($toks[1],0,2) == "0x") { $toks[1] = hexdec(substr($toks[1],2)); }
				if($toks[1])
				{
						$a = $t->api->statuses_show($toks[1]);
						if($a["result"]["in_reply_to_status_id"] > 0)
						{
							$b = $t->api->statuses_show($a["result"]["in_reply_to_status_id"]);
							if($b["TAPI"]["response_code"] == 403)
							{
								$tError = 1;
								$tErrorMsg = "The origin status is marked as private.";
							}
							elseif($b["TAPI"]["response_code"] == 404)
							{
								$tError = 1;
								$tErrorMsg = "The origin status no longer exists.";								
							}
							else
							{
								$r["result"][0] = $b["result"];
							}						
						}
						else
						{
							if($a["TAPI"]["response_code"] == 404)
							{
								$tError = 1;
								$tErrorMsg = "This status no longer exists.";
							}
							else
							{
								$tError = 1;
								$tErrorMsg = "This status was not in reply to another.";
							}
						}
				}
				else
				{
					$tError = 1;
					$tErrorMsg = "Must specify a status to pull the origin from.";
				}
			}
			if($comm == "thread")
			{
				if(substr($toks[1],0,1) == "^") { $toks[1] = substr($toks[1],1); }
				if(substr($toks[1],0,2) == "0x") { $toks[1] = hexdec(substr($toks[1],2)); }
				if($toks[1])
				{
					$r = array("result" => array());
					$a = array("result" => array("in_reply_to_status_id" => $toks[1]));
					while($a["result"]["in_reply_to_status_id"] > 0)
					{
						$a = $t->api->statuses_show($a["result"]["in_reply_to_status_id"]);
						if($a["TAPI"]["response_code"] == 200)
							{ $r["result"][] = $a["result"]; }
					}
				//	krsort($r["result"]);
				}
				else
				{
					$tError = 1;
					$tErrorMsg = "Must specify a status to pull the origin from.";
				}
			}
			if($comm == "from" || $comm == "fromuser" || $comm == "user" || $comm == "u" || ($comm == "ls" && $toks[1] && ($toks[2] || intval($toks[1]) <= 0)))
			{
				if($toks[1])
					{ $r = $t->api->statuses_userTimeline($toks[1],TWITTER_SN,null,null,20,$page); }
				else
				{
					$tError = 1;
					$tErrorMsg = "Must specify a user to look up.";
				}
			}
			if($comm == "list")
			{
				$user = "";
				$list = "";
				if($toks[2])
					{ $user = $toks[1]; $list = $toks[2]; }
				elseif(strpos($toks[1],"/"))
				{
					$listex = explode("/",$toks[1]);
					$user = $listex[0];
					$list = $listex[1];
				}
				if(substr($user,0,1) == "@") { $user = substr($user,1); }
				if($user && $list)
				{
					$r = $t->api->lists_timeline($user,$list);
				} else {
					$tError = 1;
					$tErrorMsg = "Must specify a user and a list.";
				}
			}
			if($comm == "public")
			{
				$r = $t->api->statuses_publicTimeline();
			}
			if($r["result"]["error"])
			{

				$tError = 1;
				$tErrorMsg = "Error {$r["result"]["error"]}";
			}
			if(!$tError && $r)
			{
			//	$r2 = $t->api->account_rateLimitStatus();
			//	$left = $r2["result"]["remaining_hits"];
				$append = "";
				$lastusers = array();
				$i = 0;
				foreach($r["result"] as $v)
				{
					$i++;
					$tappend = "";
					$symbol = "";
					if($v["retweeted_status"])
					{
						if($v["retweeted_status"]["user"]["protected"]) { $symbol .= "<span class=\"privateContainer\">[<abbr title=\"Private\" class=\"private\">L</abbr>]</span> "; }
						$tappend .= "<span class=\"otherUserContainer\">[<span class=\"otherUser\">".$v["user"]["screen_name"]."</span>]</span> ";
						$msg_user = $v["retweeted_status"]["user"]["screen_name"];
						if(count($v["contributors"]))
						{
							$tappend .= "<span class=\"otherUserContainer\">[<span class=\"otherUser\">".$t->get_sn_from_id($v["contributors"][0])."</span>]</span> ";
						}
						$tappend .= "<span class=\"retweetContainer\">[<abbr title=\"ReTweet\" class=\"retweet\">RT</abbr>]</span> ";
						$tappend .= "<span class=\"otherUserContainer\">&lt;<span class=\"otherUser\">".$msg_user."</span>&gt;</span> $symbol".twit2html($v["retweeted_status"]["text"]);
						$tappend .= " {<abbr class=\"retweetContainer\" title=\"Status ID\">^<span class=\"retweet\">0x".dechex($v["retweeted_status"]["id"])."</span></abbr>}";
						if(!isset($lastusers[strtoupper($v["retweeted_status"]["user"]["screen_name"])])) {
							$lastusers[strtoupper($v["retweeted_status"]["user"]["screen_name"])] = array();
							$lastusers[strtoupper($v["retweeted_status"]["user"]["screen_name"])]["uid"] = $v["retweeted_status"]["user"]["id"];
							$lastusers[strtoupper($v["retweeted_status"]["user"]["screen_name"])]["id"] = $v["retweeted_status"]["id"];
							$lastusers[strtoupper($v["retweeted_status"]["user"]["screen_name"])]["text"] = $v["retweeted_status"]["text"];
							$lastusers[strtoupper($v["retweeted_status"]["user"]["screen_name"])]["sn"] = $v["retweeted_status"]["user"]["screen_name"];
						}
					}
					else
					{
						if($v["user"]["protected"]) { $symbol .= "<span class=\"privateContainer\">[<abbr title=\"Private\" class=\"private\">L</abbr>]</span> "; }
						$msg_user = $v["user"]["screen_name"];
						if(count($v["contributors"]))
						{
							$tappend .= "<span class=\"otherUserContainer\">[<span class=\"otherUser\">".$msg_user."</span>]</span> ";
							$msg_user = $t->get_sn_from_id($v["contributors"][0]);
						}
						$tappend .= "<span class=\"otherUserContainer\">&lt;<span class=\"otherUser\">".$msg_user."</span>&gt;</span> $symbol".twit2html($v["text"]);
						$tappend .= " {<abbr class=\"retweetContainer\" title=\"Status ID\">^<span class=\"retweet\">0x".dechex($v["id"])."</span></abbr>}";
						if(!isset($lastusers[strtoupper($v["user"]["screen_name"])])) {
							$lastusers[strtoupper($v["user"]["screen_name"])] = array();
							$lastusers[strtoupper($v["user"]["screen_name"])]["uid"] = $v["user"]["id"];
							$lastusers[strtoupper($v["user"]["screen_name"])]["id"] = $v["id"];
							$lastusers[strtoupper($v["user"]["screen_name"])]["text"] = $v["text"];
							$lastusers[strtoupper($v["user"]["screen_name"])]["sn"] = $v["user"]["screen_name"];
						}
					}
					$tappend .= "<br /><br />";
					$append = $tappend.$append;
				}
				$data .= $append;
		//		$data .= "Page: $page<br />";
		//		$data .= "$left queries left available this hour.";
				if(is_array($_SESSION["last_users"])) {
					$_SESSION["last_users"] = array_merge($_SESSION["last_users"],$lastusers);
				} else {
					$_SESSION["last_users"] = $lastusers;
				}
			} else {
				$data .= $tErrorMsg;
			}
		}
		elseif($comm == "dms" || $comm == "directs")
		{
			if(isset($toks[1]) && (int)$toks[1] > 1) { $page = (int)$toks[1]; } else { $page = 1; }
			$r = $t->api->direct_messages(null,null,20,$page);
		//	$r2 = $t->api->account_rateLimitStatus();
		//	$left = $r2["result"]["remaining_hits"];
			$append = "";
			$i = 0;
			foreach($r["result"] as $v)
			{
				$i++;
				$tappend = "";
				$tappend .= "<span class=\"markerDM\">DM</span> <span class=\"otherUserContainer\">&lt;<span class=\"otherUser\">".$v["sender_screen_name"]."</span>&gt;</span> ".twit2html($v["text"]);
				$tappend .= "<br /><br />";
				$append = $tappend.$append;
			}
			$data .= $append;
		//	$data .= "Page: $page<br />";
		//	$data .= "$left queries left available this hour.";
		}
		elseif($comm == "sent")
		{
			if(isset($toks[1]) && (int)$toks[1] > 1) { $page = (int)$toks[1]; } else { $page = 1; }
			$r = $t->api->dms_sent(null,null,20,$page);
		//	$r2 = $t->api->account_rateLimitStatus();
		//	$left = $r2["result"]["remaining_hits"];
			$append = "";
			$i = 0;
			foreach($r["result"] as $v)
			{
				$i++;
				$tappend = "";
				$tappend .= "<span class=\"markerDM\">DM</span> <span class=\"otherUserContainer\">&lt;<span class=\"otherUser\">{$v[sender_screen_name]}</span>&gt;</span> <span class=\"mentionContainer\">@<span class=\"mention\">{$v[recipient_screen_name]}</span></span> ".twit2html($v["text"]);
				$tappend .= "<br /><br />";
				$append = $tappend.$append;
			}
			$data .= $append;
		//	$data .= "Page: $page<br />";
		//	$data .= "$left queries left available this hour.";
		}
		elseif($comm == "dm" || $comm == "d" || $comm == "direct")
		{
			$user = $toks[1];
			if(!$user || !$toks[2])
			{
				$data .= "Missing Parameters.<br />Usage: dm user message";
			} else {
				$str = get_str($command,2);
				$r = $t->api->dms_new($str,$user,TWITTER_SN);
				if(!$r["result"]["error"])
				{
					$data .= "Direct Message Succesfully Sent to <span class=\"mentionContainer\">@<span class=\"mention\">{$r[result][recipient][screen_name]}</span></span>.";
				} else {
					$data .= "Error: ".$r["result"]["error"];
				}
			}
		}
		elseif($comm == "lists" || $comm == "mylists")
		{
			if($comm == "mylists") { $user = $t->screen_name;$r = $t->api->lists_get($user); }
			elseif($toks[1]) { $user = $toks[1];$r = $t->api->lists_get($user); } else { $user = $t->screen_name;$r = $t->api->lists_subscriptions($user); }
			if(!$r["result"]["error"])
			{
				foreach($r["result"]["lists"] as $list)
				{
					$data .= $list["full_name"]." ($list[member_count] members)<br />";
				}
			} else {
				$data .= "Error: ".$r["result"]["error"];
			}
		//	$r2 = $t->api->account_rateLimitStatus();
		//	$data .= "<br />{$r2[result][remaining_hits]} queries left available this hour.";
		}
		elseif($comm == "createlist" || $comm == "listcreate")
		{
			$list = $toks[1];
			if($list)
			{
				$tpriv = strtolower($toks[2]);
				if($tpriv == "public")
				{
					$privacy = TWITTER_PRIVACY_PUBLIC;
					$desc = get_str($command,3);
				}
				elseif($tpriv == "private")
				{
					$privacy = TWITTER_PRIVACY_PRIVATE;
					$desc = get_str($command,3);
				}
				else
				{
					$privacy = TWITTER_PRIVACY_PUBLIC;
					$desc = get_str($command,2);
				}
	
				$r = $t->api->lists_create($list,$desc,$privacy);
				$list = $r["result"]["slug"];
				if(!$r["result"]["error"])
					{ $data = $command."<br />List \"$list\" Created."; }
				else
					{ $data .= "Error: ".$r["result"]["error"]; }
			} else {
				$data .= "Error: You must specify at least the name of the list to create.";
			}
		}
		elseif($comm == "listdesc" || $comm == "listdescription" || $comm == "desclist")
		{
			$list = $toks[1];
			$desc = get_str($command,2);
			if($list && $desc)
			{
				$r = $t->api->lists_update($list,NULL,$desc);
				if(!$r["result"]["error"])
					{ $data .= "<br />List \"$list\" Updated."; }
				else
					{ $data .= "Error: ".$r["result"]["error"]; }
			} else
				{ $data .= "Error: You must specify both a list and a description for that list."; }
		}
		elseif(($comm == "listadd" || $comm == "addlist" || $comm == "addtolist" || $comm == "add2list"))
		{
			$str = get_str($command,1);
			$cm_toks = explode(",",$str);
			$ttype = 1;
			$users = array();$lists = array();$user_ids = array();
			foreach($cm_toks as $ttok)
			{
				$ttok = trim($ttok);
				if(strpos($ttok," "))
				{
					$ttt = explode(" ",$ttok);
					if($ttype == 1)
					{
						$ttype = 2;
						$lists[] = $ttt[0];
						for($ti = 1;$ti < count($ttt);$ti++)
						{
							if(str_replace(" ","",$ttt[$ti]) != "")
								{ $users[] = trim($ttt[$ti]); }
						}
					} else {
						foreach($ttt as $tuser)
						{
							if(str_replace(" ","",$tuser) != "")
								{ $users[] = trim($tuser); }
						}
					}						
				}
				elseif($ttype == 1)
				{
					$lists[] = $ttok;
				}
				elseif($ttype == 2)
				{
					$users[] = $ttok;
				}
			}
			foreach($users as $tuser)
			{
				if(isset($_SESSION["last_users"][strtoupper($tuser)]["uid"]))
					{ $user_id = (int)$_SESSION["last_users"][strtoupper($tuser)]["uid"]; }
				else
					{
					$tr = $t->api->users_show($tuser,TWITTER_SN);
					$_SESSION["last_users"][strtoupper($tuser)] = array
					(
						"id" => $tr["result"]["status"]["id"],
						"uid" => $tr["result"]["id"],
						"text" => $tr["result"]["status"]["text"],
						"sn" => $tr["result"]["screen_name"]
					);
					$user_id = (int)$tr["result"]["id"];
				}
				$user_ids[] = $user_id;

			}
			if(count($users) > 0 && count($lists) > 0)
			{
				foreach($lists as $tlist)
				{
					foreach($user_ids as $k => $user_id)
					{
						$r = $t->api->list_membersAdd($tlist,$user_id);
						if(!$r["result"]["error"] && $r)
						{
							$data .= "Added ".$users[$k]." to $tlist<br />";
						}
						else
						{
							$data .= "Error: Could not add $tuser to $tlist";
							if($r["error"])
								{ $data .= " for reason: {$r[result][error]}"; }
							elseif($user_id == 0)
								{ $data .= " (No Such User)"; }
							$data .= "<br />";
						}
					}
				}
			//	$r2 = $t->api->account_rateLimitStatus();
			//	$left = $r2["result"]["remaining_hits"];
			//	$data .= "<br />$left queries available this hour.";
			} else {
				$data .= "You must specify at least one list and one user.";
			}
			
		}
		elseif($comm == "dellist" || $comm == "listdel" || $comm == "listdelete")
		{
			$list = $toks[1];
			$r = $t->api->lists_destroy($list);
			if(!$r["result"]["error"])
				{ $data .= "List \"$list\" Deleted."; }
			else
				{ $data .= "Error: ".$r["result"]["error"]; }
		}
		elseif($comm == "renamelist" || $comm == "listrename")
		{
			if($toks[1] && $toks[2])
			{
				$r = $t->api->lists_update($toks[1],$toks[2]);
				if(!$r["result"]["error"])
					{ $data .= "List \"$toks[1]\" renamed to \"$toks[2]\""; }
				else
					{ $data .= "Error: ".$r["result"]["error"]; }
			}
		}
		elseif($comm == "logout" || $comm == "quit" || $comm == "end" || $comm == "exit" || $comm == "close")
		{
			print("<tt>[".$t->screen_name."]$ $command <br />Logged Out.</tt>");
			session_destroy();
			unset($_SESSION);
			print("<script type=\"text/javascript\">parent.logOut();</script>");
			if($comm == "exit" || $comm == "close")
			{
				print("<script type=\"text/javascript\">parent.window.close();</script>");
			}
			die();
		}
		elseif($comm == "themeset" || $comm == "settheme")
		{
			$thm = strtolower($toks[1]);
			if(file_exists("styles/".$thm.".css"))
			{
				$url = "http://twcli.koneko-chan.net/styles/".$toks[1].".css";
				
				$text = "@import url(\"".str_replace('"','',$url)."\");";
				$f = fopen("styles/user/uid_".$t->uid.".css","w");
				$t = fwrite($f,$text);
				fclose($f);
				copy("styles/user/uid_".$t->uid.".css","styles/user/".strtolower($t->screen_name).".css");
			
				$data .= "Theme set to $toks[1]";
				print("<script type=\"text/javascript\">parent.document.location='cli.php';</script>");
			} else {
				$data .= "Theme \"$thm\" does not exist.";
			}
		}
		elseif($comm == "themecss")
		{
			if($toks[1])
			{
				$url = $toks[1];

				$text = "@import url(\"".str_replace('"','',$url)."\");";
				$f = fopen("styles/user/uid_".$t->uid.".css","w");
				$t = fwrite($f,$text);
				fclose($f);
				copy("styles/user/uid_".$t->uid.".css","styles/user/".strtolower($t->screen_name).".css");
			
				$data .= "Theme set to $url";
				print("<script type=\"text/javascript\">parent.document.location='cli.php';</script>");
			} else {
				$data .= "You must specify a URL to a CSS stylesheet.";
			}
			
		}
		elseif($comm == "themelist")
		{
			$d = opendir("styles");
			$styles = "";
			while(($f = readdir($d)) !== false)
			{
				if(!is_dir("styles/".$f)) { $temp = explode(".",$f);$styles .= $temp[0]." "; }
			}
			$data .= $styles;
		}
		elseif($comm == "setloc")
		{
			if(isset($toks[1])) { $str = get_str($command,1); }
			else { $str = "TwCLI: ".$latitude.",".$longitude; }
			if(!$toks[1] && (!$latitude || !$longitude))
			{
				$data .= "Error: You must either have Geo-Location Enabled, or specify a location manually.";
			} else {
				$r = $t->api->account_updateProfile(null,null,$str);
				if(!$r["result"]["error"])
					{ $data .= "Location Updated to {$r[result][location]}."; }
				else
					{ $data .= "Error: ".$r["result"]["error"]; }
			}
		}
		elseif($comm == "block")
		{
			if(isset($toks[1]))
			{
				$user = $toks[1];
				$r = $t->api->blocks_create($user,TWITTER_SN);
				if(!$r["result"]["error"])
					{ $data .= "Succesfully blocked @{$r["result"]["screen_name"]}"; }
				else
					{ $data .= "Error: {$r["result"]["error"]}"; }
			} else {
				$data .= "Error: You must specify a username to block.";
			}
		}
		elseif($comm == "poke" || $comm == "retaliate")
		{
			if($toks[1])
			{
				$user = $toks[1];
				if(substr($user,0,1) == "@") { $user = substr($user,1); }
				if(isset($_SESSION["last_users"][strtoupper($user)]))
					{ $reply_to = $_SESSION["last_users"][strtoupper($user)]["id"]; } else { $reply_to = ""; }
				$data .= "Poke: <span class=\"mentionContainer\">@<a target=\"_blank\" rel=\"noreferrer\" href=\"http://twitter.koneko-chan.net/superpoke/poke.php?rsn={$user}&reply={$reply_to}\" class=\"mention hyperlink\">{$user}</a></span>";
				$pageend .= "<form id=\"pokeLink\" method=\"get\" action=\"http://twitter.koneko-chan.net/superpoke/poke.php\" target=\"_blank\" rel=\"noreferrer\"><input name=\"rsn\" type=\"hidden\" value=\"{$user}\" /><input name=\"reply\" type=\"hidden\" value=\"{$reply_to}\" /></form>";
				$pageend .= "<script type=\"text/javascript\">document.getElementById('pokeLink').submit();</script>";			
			} else {
				$data .= "Error: You must specify a user to poke!";
			}
		}
		elseif($comm == "google")
		{
			$data .= "Google: <a target=\"_blank\" rel=\"noreferrer\" href=\"http://www.google.com/search?q=".urlencode(get_str($command,1))."\" class=\"hyperlink\">".htmlclean(get_str($command,1))."</a>";
			$pageend .= "<form id='newGoogleLink' method='get' action='http://www.google.com/search' target='_blank' rel='noreferrer'><input name=\"q\" type=\"hidden\" value=\"".str_replace('"','',get_str($command,1))."\" /></form>";
			$pageend .= "<script type=\"text/javascript\">document.getElementById('newGoogleLink').submit();</script>";
		}
		elseif($comm == "hashtag")
		{
			$tag = $toks[1];
			if(substr($tag,0,1) == "#") { $tag = substr($tag,1); }
			$data .= "Hashtag: <span class=\"hashtagContainer\">#<a href=\"http://search.twitter.com/search?q=%23".urlencode($tag)."\" rel=\"noreferrer\" class=\"hashtag\" target=\"blank\">$tag</a></span>";
			$pageend .= "<form id='newHashLink' method='get' action='http://search.twitter.com/search' target='_blank' rel='noreferrer'><input name=\"q\" type=\"hidden\" value=\"#".str_replace('"','',$tag)."\" /></form>";
			$pageend .= "<script type=\"text/javascript\">document.getElementById('newHashLink').submit();</script>";						
		}
		elseif($comm == "search")
		{
			$search = get_str($command,1);
			$data .= "Twitter Search: <a href=\"http://search.twitter.com/search?q=".urlencode($search)."\" class=\"hyperlink\" rel=\"noreferrer\" target=\"blank\">$search</a>";
			$pageend .= "<form id='newSearchLink' method='get' action='http://search.twitter.com/search' target='_blank' rel='noreferrer'><input name=\"q\" type=\"hidden\" value=\"".str_replace('"','',$search)."\" /></form>";
			$pageend .= "<script type=\"text/javascript\">document.getElementById('newSearchLink').submit();</script>";						

		}
		elseif($comm == "wiki" && $t->screen_name == "Navarr")
		{
			$data .= "<a target=\"_blank\" href=\"http://en.wikipedia.org/search-redirect.php?language=en&search=".urlencode(get_str($command,1))."\" class=\"hyperlink\">".htmlclean(get_str($command,1))."</a>";
			$pageend .= "<form id='newWikiLink' method='get' action='http://www.wikipedia.org/search-redirect.php?language=en' target='_blank'><input name=\"search\" type=\"hidden\" value=\"".str_replace('"','',get_str($command,1))."\" /></form>";
			$pageend .= "<script type=\"text/javascript\">document.getElementById('newWikiLink').submit();</script>";			
		}
		
		elseif($comm == "walkthrough" || $comm == "introduction")
		{
			$data .= "Welcome to TwCLI - The Command Line Twitter Client<br /><br />";
			$data .= "There are probably <strong>four</strong> commands you'll use most often..<br />";
			$data .= " - timeline (or t)<br />";
			$data .= " - post (or p)<br />";
			$data .= " - mentions (or @) <em>and</em><br />";
			$data .= " - reply (or @)<br /><br />";
		}
		elseif($comm == "myloc" || $comm == "mylocation")
		{
			if(!isset($toks[1]))
			{
				$r = $t->api->geo_reverseGeocode($latitude,$longitude);
				foreach($r["result"]["result"]["places"] as $place)
				{
					$data .= "{$place["id"]} {$place["full_name"]} <br />";
				}
			} else {
				$_SESSION["place"] = $toks[1];
				$data .= "Place ID Set to {$toks[1]}";
			}
		}

		// NAVARR ONLY
			elseif($comm == "usetest" && $t->screen_name == "Navarr")
			{
				setcookie("usetest",true);
			}	
			elseif($comm == "lastusers" && $t->screen_name == "Navarr")
			{
				print_r($_SESSION["last_users"]);
			}
			elseif($comm == "stream" && $t->screen_name == "Navarr")
			{
				$data .= "Begin Streaming...";
				$pageend .= "<script type=\"text/javascript\">alert('Streaming Not Yet Available');</script>";
			}

		// EVERYTHING ELSE

		else
		{
			$data = $command."<br />Not Supported.<br />Did you want to post that? If so, use command POSTIT to post your last command. If not, use command EDIT to copy it back into the command line.";
		}
		$_SESSION["last_command"] = $command;
		$_SESSION["commands"][] = $data;
	} } }
	print("<tt>");
	for($i = count($_SESSION["commands"])-10;$i<count($_SESSION["commands"]);$i++)
	{
		$com = $_SESSION["commands"][$i];
		if($com) {
		?><span class="thisUserContainer">[<span class="thisUser"><?= $t->screen_name ?></span>]$</span> <span class="command"><?= $com ?></span><br /><br /><?php
		}
	}
	print("</tt>");
	print($pageend);
?>
<script type="text/javascript">window.scrollBy(0,9000000);</script>
</body></html>
