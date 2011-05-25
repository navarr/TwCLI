<?php
	function twit2html($txt,$beta = FALSE)
	{
		$txt = " ".$txt." ";
		// Already Encoded
		//	$txt = preg_replace("~&#([0-9]+);~","%%rep%$1%%",$txt);
			$txt = html_entity_decode($txt);
		// Fix < >
			$txt = str_replace(array("<",">"),array("&lt;","&gt;"),$txt);
		// Italics
		//	$txt = preg_replace("#([^\w]+)\_([^\_]+)\_([^\w]+)#",'$1<em>$2</em>$3',$txt);
		// Bold
		//	$txt = preg_replace("#([^\w]+)\*([^\*]+)\*([^\w]+)#",'$1<strong>$2</strong>$3',$txt);
		// Strikeout
		//	$txt = preg_replace("#([^\w]*)\~\~([^\~]+)\~\~([^\w]*)#",'$1<strike>$2</strike>$3',$txt);
		// URIs
			$txt = preg_replace("^(http|https|ftp|irc)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|localhost|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&amp;%\$#\=~_\-\/]*))*^",'<a href="$0" title="Link to $0" target="_blank" class="hyperlink">$0</a>',$txt);
		// @replies
			$txt = preg_replace("#([^\w]+)@([a-zA-Z0-9_/-]+)#i",'$1<span class="mentionContainer">@<span class="mention">$2</span></span>',$txt);
		// Emails
			// /^[A-z0-9_\-]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{2,4}$/
			$txt = preg_replace("#[A-z0-9_\-\+]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{2,4}#i",'<a href="mailto:$0" title="Email: $0" class="email">$0</a> ',$txt);
		// 3rd Layer Hashtags + .
			$txt = preg_replace("# \#([\w\d\p{L}_\-\+\.]+)\:([\w\d\p{L}_\-\+\.]+)\=([\w\d\p{L}_\-\+\.]+)([\.]+) #iu",' #<a href="http://search.twitter.com/search?q=%23$1" target="pageFrame">$1</a>:<a href="http://search.twitter.com/search?q=%23$1%3A$2" target="pageFrame">$2</a>=<a href="http://search.twitter.com/search?q=%23$1%3A$2%3D$3" target="pageFrame">$3</a>$4 ',$txt);
		// 2nd Layer Hashtags + .
			$txt = preg_replace("# \#([\w\d\p{L}_\-\+\.]+)\:([\w\d\p{L}_\-\+\.]+)([\.]+) #iu",' #<a href="http://search.twitter.com/search?q=%23$1" target="pageFrame">$1</a>:<a href="http://search.twitter.com/search?q=%23$1%3A$2" target="pageFrame">$2</a>$3 ',$txt);
		// 1st Layer Hashtags + .
			$txt = preg_replace("# \#([\w\d\p{L}_\-\+\.]+)([\.]+) #iu",' <a href="http://search.twitter.com/search?q=%23$1" target="_blank" class="hashtag">$1</a>$2 ',$txt);
		// 3rd Layer Hashtags
			$txt = preg_replace("# \#([\w\d\p{L}_\-\+\.]+)\:([\w\d\p{L}_\-\+\.]+)\=([\w\d\p{L}_\-\+\.]+)#iu",' #<a href="http://search.twitter.com/search?q=%23$1" target="pageFrame">$1</a>:<a href="http://search.twitter.com/search?q=%23$1%3A$2" target="pageFrame">$2</a>=<a href="http://search.twitter.com/search?q=%23$1%3A$2%3D$3" target="pageFrame">$3</a>',$txt);
		// 2nd Layer Hashtags
			$txt = preg_replace("# \#([\w\d\p{L}_\-\+\.]+)\:([\w\d\p{L}_\-\+\.]+)#iu",' #<a href="http://search.twitter.com/search?q=%23$1" target="pageFrame">$1</a>:<a href="http://search.twitter.com/search?q=%23$1%3A$2" target="pageFrame">$2</a>',$txt);
		// 1st Layer Hashtags
			$txt = preg_replace("# \#([\w\d\p{L}_\-\+\.]+)#iu",' <span class="hashtagContainer">#<a href="http://search.twitter.com/search?q=%23$1" target="_blank" class="hashtag">$1</a></span>',$txt);
		// Status IDs
			$txt = preg_replace("# \^([0-9a-fx]+)#iu", ' <abbr class="retweetContainer" title="Status ID">^<span class="retweet">$1</span></abbr>',$txt);
		// Stock Twits
		//	$txt = preg_replace("#([^\w]*)\$([a-zA-Z0-9_\-]+)#i",' $<a href="http://stream.stocktwits.com/tagged/$1" target="pageFrame">$1</a>',$txt);
		// Fix Encoding Back
		//	$txt = preg_replace("~%%rep%([0-9]*)%%~","&#$1;",$txt);
		//	$txt = htmlentities($txt);
			
		$txt = substr($txt,1,strlen($txt)-2);
	//	$txt = smiley_replace($txt);
		return $txt;
	}
	function smiley_replace($txt)
	{
		$txt = " ".$txt." ";
		$ra1 = array
		(
			array(" :) "," :-) "," =) "," =-) "),
			array(" :D "," :-D "," =D "," =-D "),
			array(" ;) "," ;-) "," ^.~ "),
			array(" ^_^ "," ^^ "),
			array(" >:o "),
			array(" :3 "," =^.^= "),
			array(" >:( "," >:-( "," >:-< "," >=( "," >=-( "),
			array(" :( "," :-( "," =( "," =-( "," D= "),
			array(" ;( "," :'( "," T.T "," TT.TT "," ='( "),
			array(" :o "," :-o "," =o "," =-o "),
			array(" 8) "," 8-) "," 8| "," 8-| "),
			array(" :p "," :-p "," =p "," =-p "),
			array(" o.o "),
			array(" -.- "," -_- "),
			array(" :\ "," :/ "," =\ "," =/ "),
			array(" 3:) "," 3=) "),
			array(" o:) "," o=) "),
			array(" :-* "," :* "," =* "," =-* "),
			array(" <3 "),
			array(" :v "," =v "," v: "," v= "),
			array(" :|] "," =|] ")
		);
		$ra2 = array
		(
			" <img src=\"img/emoticon/happy.jpg\" alt=\":)\" /> ",
			" <img src=\"img/emoticon/bighappy.jpg\" alt=\":D\" /> ",
			" <img src=\"img/emoticon/wink.jpg\" alt=\";)\" /> ",
			" <img src=\"img/emoticon/anime.gif\" alt=\"^_^\" /> ",
			" <img src=\"img/emoticon/laughing.gif\" alt=\">:o\" /> ",
			" <img src=\"img/emoticon/cat.jpg\" alt=\":3\" /> ",
			array(" >:( "," >:-( "," >:-< "," >=( "," >=-( "),
			array(" :( "," :-( "," =( "," =-( "," D= "),
			array(" ;( "," :'( "," T.T "," TT.TT "," ='( "),
			array(" :o "," :-o "," =o "," =-o "),
			array(" 8) "," 8-) "," 8| "," 8-| "),
			array(" :p "," :-p "," =p "," =-p "),
			array(" o.o "),
			array(" -.- "," -_- "),
			array(" :\ "," :/ "," =\ "," =/ "),
			array(" 3:) "," 3=) "),
			array(" o:) "," o=) "),
			array(" :-* "," :* "," =* "," =-* "),
			array(" <3 "),
			array(" :v "," =v "," v: "," v= "),
			array(" :|] "," =|] ")
		);
		$txt = str_ireplace($ra1,$ra2,$txt);
		$txt = substr($txt,1,strlen($txt)-1);
		return $txt;
	}
?>
