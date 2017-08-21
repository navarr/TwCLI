<?php
/*
 Twitter API Library
 Author: Navarr T. Barnier
 Version: 0.2.2
 Build 27
 More Info & Usage License: http://tech.gtaero.net/code/twitter-api-library
 */
class Twitter
{
	public $api;
	public $auth;
	public $noauth;
	protected $key;
	protected $secret;
	protected $vars;
	protected $forceauth;
	public $cache;
	// Quick Vars
		protected $uid = FALSE;
		protected $name;
		protected $screen_name;
		protected $first_name;
		protected $last_name;
		protected $geo_enabled;
		protected $rl_limit;
		protected $rl_remaining;
		protected $rl_reset;

	function __construct($key, $secret)
	{
		$this->key = $key;
		$this->secret = $secret;
		// Setup the Session
		$this->pullVars();
		
		// Setup the API
		$this->api = new TwitterAPI($this);
		
		// Setup NoAuth
		$this->noauth = new TwitterNoAuth();

		// Cache.
		$this->cache = new TwitterCache;	
	
		// Check for an already logged-in User
		if($this->hasVar("QUICKDATA"))
		{
			$this->setQuickVars();
		}
	}
	function __get($n) // For Read-Only Variables
	{
		return $this->$n;
	}
	protected function setVar($var, $val)
	{
		$this->vars[$var] = $val;
		$_SESSION["TWITTERAPI_vars"][$var] = $val;
	}
	protected function getVar($var)
	{
		if (! isset ($this->vars[$var]))
		{
			return NULL;
		}
		return $this->vars[$var];
	}
	protected function hasVar($var)
	{
		if (! isset ($this->vars[$var]))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	protected function pullVars()
	{
		if (session_id() == "")
		{
			session_start();
		}
		if (! isset ($_SESSION["TWITTERAPI_vars"]))
		{
			return false;
		}
		foreach ($_SESSION["TWITTERAPI_vars"] as $k=>$v)
		{
			$this->vars[$k] = $v;
		}
	}
	public function logout()
	{
		$_SESSION["TWITTERAPI_vars"] = array();
		$_SESSION["TWITTERCACHE"] = array();
		$this->vars = array();
		$this->uid = FALSE;
	}
	public function get_login()
	{
		return array ($this->getVar("OAUTH_ACCESS_TOKEN"), $this->getVar("OAUTH_ACCESS_TOKEN_SECRET"));
	}
	public function require_login($return_to = NULL)
	{
		// Get the State, I guess.
		if ($this->hasVar("OAUTH_TOKEN"))
		{
			$this->setVar("OAUTH_STATE", "RETURNED");
		}
		switch($this->getVar("OAUTH_STATE"))
		{
			case "FINISHED":
				// If we already have all the required information, just complete Authorization.
				$this->setAuth(new TwitterOAuth($this->key, $this->secret, $this->getVar("OAUTH_ACCESS_TOKEN"), $this->getVar("OAUTH_ACCESS_TOKEN_SECRET")));
				break;
			case "RETURNED":
				// If we are just now receiving the information, Complete Authorization.
				if ($this->getVar("OAUTH_ACCESS_TOKEN") === NULL && $this->getVar("OAUTH_ACCESS_TOKEN_SECRET") === NULL)
				{
					$this->setAuth(new TwitterOAuth($this->key, $this->secret, $this->getVar("OAUTH_REQUEST_TOKEN"), $this->getVar("OAUTH_REQUEST_TOKEN_SECRET")));
					$tok = $this->auth->getAccessToken();
					$this->setVar("OAUTH_ACCESS_TOKEN", $tok['oauth_token']);
					$this->setVar("OAUTH_ACCESS_TOKEN_SECRET", $tok['oauth_token_secret']);
				}
				$this->setAuth(new TwitterOAuth($this->key, $this->secret, $this->getVar("OAUTH_ACCESS_TOKEN"), $this->getVar("OAUTH_ACCESS_TOKEN_SECRET")));
				$this->setVar("OAUTH_STATE","FINISHED");
				break;
			default:
				// If we have not yet gotten the information required for login.
				$this->setAuth(new TwitterOAuth($this->key, $this->secret));
				$tok = $this->auth->getRequestToken();
				if(!$return_to)
				{
					if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]) { $scheme = "https"; } else { $scheme = "http"; }
					$this->setVar("RETURN_URI", $scheme."://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
				} else {
					$this->setVar("RETURN_URI", $return_to);
				}
				$this->setVar("OAUTH_REQUEST_TOKEN", $tok['oauth_token']);
				$this->setVar("OAUTH_REQUEST_TOKEN_SECRET", $tok['oauth_token_secret']);
				if (!$tok['oauth_token'])
					{ return FALSE; }
				$rql = $this->auth->getAuthorizeUrl($tok, TRUE);
				session_write_close();
				session_regenerate_id();
				header("Location: $rql");
				print('<a href="$rql">$rql</a>');
				die();
				break;
		}
		$this->completeAuth();
		return TRUE;
	}
	public function setup_login()
	{
		$this->setAuth(new TwitterOAuth($this->key, $this->secret));
		$tok = $this->auth->getRequestToken();
		$this->setVar("OAUTH_REQUEST_TOKEN", $tok["oauth_token"]);
		$this->setVar("OAUTH_REQUEST_TOKEN_SECRET", $tok["oauth_token_secret"]);
		$this->setVar("OAUTH_STATE", "START");
		$rql = $this->auth->getAuthorizeUrl($tok,TRUE);
		return $rql;
	}
	public function use_login($tokKey, $tokSecret)
	{
		$this->setAuth(new TwitterOAuth($this->key, $this->secret, $tokKey, $tokSecret));
		$this->completeAuth();
		$this->setVar("OAUTH_STATE","FINISHED");
		return TRUE;
	}
	public function pin_login($pin)
	{
		$this->setAuth(new TwitterOAuth($this->key, $this->secret));
		$tok = $this->auth->getAccessToken($pin);
		$this->setVar("OAUTH_ACCESS_TOKEN", $tok['oauth_token']);
		$this->setVar("OAUTH_ACCESS_TOKEN_SECRET", $tok['oauth_token_secret']);
		$this->setAuth(new TwitterOAuth($this->key, $this->secret, $this->getVar("OAUTH_ACCESS_TOKEN"), $this->getVar("OAUTH_ACCESS_TOKEN_SECRET")));
		$this->completeAuth();
		$this->setVar("OAUTH_STATE","FINISHED");
		return TRUE;
	}
	protected function completeAuth()
	{
		$this->auth->useragent = TAPI_IDENTIFIER;
		$this->auth->decode_json = FALSE;
		$this->setQuickVars();
	}
	protected function setAuth($newAuth)
	{
		unset($this->auth);
		unset($this->api->auth);
		$this->auth = $newAuth;
		$this->api->auth = $newAuth;
	}
	protected function setQuickVars($verify_credentials = FALSE)
	{
		if (!$this->hasVar("QUICKDATA") || $verify_credentials || ($data = $this->getVar("QUICKDATA") && $data["id"]))
		{
			$this->forceauth = TRUE;
			$data = $this->api->account_verifyCredentials();
			$this->setVar("QUICKDATA", $data);
			$this->api->account_rateLimitStatus();
			$this->forceauth = FALSE;
		}
		$data = $this->getVar("QUICKDATA");
		$this->uid = $data["id"];
		$this->name = $data["name"];
		$n2 = explode(" ", $this->name);
		$fn = $n2[0];
		$sn = $n2[count($n2)-1];
		$this->first_name = $fn;
		$this->last_name = $sn;
		$this->screen_name = $data["screen_name"];
		$this->geo_enabled = $data["geo_enabled"];
		$this->cache->user_store($data);
		if(isset($data["RATELIMIT"]))
		{
			$this->setRateLimit($data["RATELIMIT"]["LIMIT"],$data["RATELIMIT"]["REMAINING"],$data["RATELIMIT"]["RESET"]);
		}
	}
	public function setRateLimit($limit,$remaining,$reset)
	{
		$this->rl_limit = $limit;
		$this->rl_remaining = $remaining;
		$this->rl_reset = $reset;
		$data = $this->getVar("QUICKDATA");
		$data["RATELIMIT"]["LIMIT"] = $limit;
		$data["RATELIMIT"]["REMAINING"] = $remaining;
		$data["RATELIMIT"]["RESET"] = $reset;
		$this->setVar("QUICKDATA",$data);
	}
	
	public function get_sn_from_id($id)
	{
		if ($id == $this->uid)
		{
			return $this->screen_name;
		}
		else
		{
			if ($this->cache->user_available($id))
			{
				$t = $this->cache->user_get($id);
			}
			else
			{
				$t = $this->api->users_show($id, TWITTER_UID);
				$this->cache->user_store($t);
			}
			return $t["screen_name"];
		}
	}
	public function get_name_from_id($id)
	{
		if ($id == $this->uid)
		{
			return $this->name;
		}
		else
		{
			if ($this->cache->user_available($id))
			{
				$t = $this->cache->user_get($id);
			}
			else
			{
				$t = $this->api->users_show($id, TWITTER_UID);
				$this->cache->user_store($t);
			}
			return $t["name"];
		}
	}
	public function get_id_from_sn($screen_name)
	{
		if (strtoupper($screen_name) == $this->screen_name)
		{
			return $this->uid;
		}
		else
		{
			if ($this->cache->user_available($screen_name, TWITTER_SN))
			{
				$t = $this->cache->user_get($screen_name, TWITTER_SN);
			}
			else
			{
				$t = $this->api->users_show($screen_name, TWITTER_SN);
				$this->cache->user_store($t);
			}
			return $t["id"];
		}
	}
	
	public function disable_auth()
	{
		$this->api->noauth = TRUE;
	}
	public function enable_auth()
	{
		$this->api->noauth = FALSE;
	}
	public function temp_disable_auth($i = 1,$add_to = FALSE)
	{
		if($add_to)
			{ $this->api->t_noauth += $i; }
		else
			{ $this->api->t_noauth = $i; }

		return $this->api->t_noauth;
	}
}
class TwitterCache
{
	// Responsible ONLY for handling Cached Data
	protected $vars;
	public $cache_timeout = 60;
	function __construct()
	{
		if (!session_id())
		{
			session_start();
		}
		if ( isset ($_SESSION["TWITTERCACHE"]))
		{
			$this->vars = $_SESSION["TWITTERCACHE"];
		}
		else
		{
			$_SESSION["TWITTERCACHE"] = array ();
		}
	}
	private function setVar($var, $val)
	{
		if ($_SESSION["TWITTERCACHE"][$var] = $val)
		{
			$this->vars[$var] = $val;
			return $val;
		}
		else
		{
			return FALSE;
		}
	}
	private function hasVar($var)
	{
		if ( isset ($this->vars[$var]))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	private function getVar($var)
	{
		return $this->vars[$var];
	}
	public function user_store($data)
	{ // $data should be an array of the json_decoded api->users_show
		if ($this->hasVar("USERS"))
		{
			$cache = $this->getVar("USERS");
		}
		else
		{
			$cache = array ();
		}
		$cache["BY_ID"][$data["id"]] = $data;
		$cache["BY_SN"][strtoupper($data["screen_name"])] = $data["id"];
		$this->setVar("USERS", $cache);
	}
	public function user_available($user, $user_type = TWITTER_UID)
	{ // Use Types TWITTER_UID and TWITTER_SN
		if ($this->hasVar("USERS"))
		{
			$cache = $this->getVar("USERS");
		}
		else
		{
			return FALSE;
		}
		if ($user_type == TWITTER_UID)
		{
			if ( isset ($cache["BY_ID"][$user]["id"]))
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			if ( isset ($cache["BY_ID"][$cache["BY_SN"][strtoupper($user)]]["id"]))
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
	}
	public function user_get($user, $user_type = TWITTER_UID)
	{
		if (!$this->user_available($user, $user_type))
		{
			return FALSE;
		}
		$cache = $this->getVar("USERS");
		if ($user_type == TWITTER_UID)
		{
			return $cache["BY_ID"][$user];
		}
		else
		{
			return $cache["BY_ID"][$cache["BY_SN"][strtoupper($user)]];
		}
	}
}
class TwitterAPI
{
	public $auth;
	public $return = "json";
	protected $count;
	protected $t;
	public $t_noauth = FALSE;
	public $noauth = FALSE;

	function __construct($t = null)
	{
		$this->count = array
		(
		"cached"=>0,
		"unauthenticated"=>0,
		"authenticated"=>0
		);
		if ($t)
		{
			$this->t = $t;
		}
	}
	private function addUser($v, $u, $t)
	{
		if ($t == TWITTER_UID)
		{
			$v["user_id"] = $u;
		}
		elseif ($t == TWITTER_SN)
		{
			$v["screen_name"] = $u;
		}
		else
		{
			$v["id"] = $u;
		}
		return $v;
	}

	// Search
	public function search($q, $since_id = null, $lang = null, $locale = null, $geocode = null, $rpp = null, $page = null, $show_user = null)
	{
		$vars = array ();
		$vars["q"] = $q;
		if ($since_id)
		{
			$vars["since_id"] = $since_id;
		}
		if ($lang)
		{
			$vars["lang"] = $lang;
		}
		if ($locale)
		{
			$vars["locale"] = $locale;
		}
		if ($geocode)
		{
			$vars["geocode"] = $geocode;
		}
		if ($rpp)
		{
			$vars["rpp"] = $rpp;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}
		if ($show_user)
		{
			$vars["show_user"] = $show_user;
		}
		return $this->callMethod("search", $vars);
	}
	public function trends()
	{
		return $this->callMethod("trends", $vars);
	}
	public function trends_current($exclude_hashtags = FALSE)
	{
		$vars = array ();
		if ($exclude_hashtags)
		{
			$vars["exclude"] = "hashtags";
		}
		return $this->callMethod("trends/current", $vars);
	}
	public function trends_daily($date = NULL, $exclude_hashtags = FALSE)
	{
		$vars = array ();
		if ($date)
		{
			$vars["date"] = date("Y-m-d", strtotime($date));
		}
		if ($exclude_hashtags)
		{
			$vars["exclude"] = "hashtags";
		}
		return $this->callMethod("trends/daily", $vars);
	}
	public function trends_weekly($start_date = NULL, $exclude_hashtags = FALSE)
	{
		$vars = array ();
		if ($start_date)
		{
			$vars["date"] = date("Y-m-d", strtotime($date));
		}
		if ($exclude_hashtags)
		{
			$vars["exclude"] = "hashtags";
		}
		return $this->callMethod("trends/weekly", $vars);
	}

	// Timeline
	public function statuses_publicTimeline()
	{
		return $this->callMethod("statuses/public_timeline");
	}
	public function statuses_homeTimeline($since_id = null, $max_id = null, $count = null, $page = null)
	{
		$vars = array ();
		if ($since_id)
		{
			$vars["since_id"] = $since_id;
		}
		if ($max_id)
		{
			$vars["max_id"] = $max_id;
		}
		if ($count)
		{
			$vars["count"] = $count;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}

		return $this->callMethod("statuses/home_timeline");
	}
	public function statuses_friendsTimeline($since_id = null, $max_id = null, $count = null, $page = null)
	{
		$vars = array ();
		if ($since_id)
		{
			$vars["since_id"] = $since_id;
		}
		if ($max_id)
		{
			$vars["max_id"] = $max_id;
		}
		if ($count)
		{
			$vars["count"] = $count;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}

		return $this->callMethod("statuses/friends_timeline");
	}
	public function statuses_userTimeline($u, $type = TWITTER_ID, $since_id = null, $max_id = null, $count = null, $page = null)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		if ($since_id)
		{
			$vars["since_id"] = $since_id;
		}
		if ($max_id)
		{
			$vars["max_id"] = $max_id;
		}
		if ($count)
		{
			$vars["count"] = $count;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}
		return $this->callMethod("statuses/user_timeline", $vars);
	}
	public function statuses_mentions($since_id = null, $max_id = null, $count = null, $page = null)
	{
		$vars = array ();
		if ($since_id)
		{
			$vars["since_id"] = $since_id;
		}
		if ($max_id)
		{
			$vars["max_id"] = $max_id;
		}
		if ($count)
		{
			$vars["count"] = $count;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}
		return $this->callMethod("statuses/mentions", $vars);
	}
	public function statuses_retweetedByMe($since_id = null, $max_id = null, $count = null, $page = null)
	{
		$vars = array ();
		if ($since_id)
		{
			$vars["since_id"] = $since_id;
		}
		if ($max_id)
		{
			$vars["max_id"] = $max_id;
		}
		if ($count)
		{
			$vars["count"] = $count;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}
		return $this->callMethod("statuses/retweeted_by_me", $vars);
	}
	public function statuses_retweetedToMe($since_id = null, $max_id = null, $count = null, $page = null)
	{
		$vars = array ();
		if ($since_id)
		{
			$vars["since_id"] = $since_id;
		}
		if ($max_id)
		{
			$vars["max_id"] = $max_id;
		}
		if ($count)
		{
			$vars["count"] = $count;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}
		return $this->callMethod("statuses/retweeted_to_me", $vars);
	}
	public function statuses_retweetsOfMe($since_id = null, $max_id = null, $count = null, $page = null)
	{
		$vars = array ();
		if ($since_id)
		{
			$vars["since_id"] = $since_id;
		}
		if ($max_id)
		{
			$vars["max_id"] = $max_id;
		}
		if ($count)
		{
			$vars["count"] = $count;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}
		return $this->callMethod("statuses/retweets_of_me", $vars);
	}
	// Status Methods
	public function statuses_show($id)
	{
		return $this->callMethod("statuses/show", array ("id"=>$id));
	}
	public function statuses_update($status, $reply_to = null, $lat = null, $long = null)
	{
		$vars = array ();
		$vars["status"] = $status;
		if ($reply_to)
		{
			$vars["in_reply_to_status_id"] = $reply_to;
		}
		if ($lat)
		{
			$vars["lat"] = $lat;
		}
		if ($long)
		{
			$vars["long"] = $long;
		}
		return $this->callMethod("statuses/update", $vars, "POST");
	}
	public function statuses_destroy($id)
	{
		return $this->callMethod("statuses/destroy", array ("id"=>$id), "POST");
	}
	public function statuses_retweet($id)
	{
		return $this->callMethod("statuses/retweet/$id", null, "POST");
	}
	public function statuses_retweets($id, $count = null)
	{
		$vars = array ();
		$vars["id"] = $id;
		if ($count)
		{
			$vars["count"] = $count;
		}
		return $this->callmethod("statuses/retweets", $vars);
	}

	// Users
	public function users_show($u, $type = TWITTER_ID)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		return $this->callMethod("users/show", $vars);
	}
	public function users_search($q, $per_page = NULL, $page = NULL)
	{
		$vars = array ("q"=>$q);
		if ($per_page)
		{
			$vars["per_page"] = $per_page;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}
		return $this->callMethod("users/search", $vars);
	}
	public function users_friends($a = null, $b = null, $c = null, $d = null)
		{ return $this->statuses_friends($a, $b, $c, $d); }
	public function statuses_friends($u, $type = TWITTER_ID, $cursor = null)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		if ($cursor)
		{
			$vars["cursor"] = $cursor;
		}
		$json = $this->callMethod("statuses/friends", $vars);
		foreach($json as $userData)
		{
			$this->t->cache->user_store($userData);
		}
		return $json;
	}
	public function users_followers($a = null, $b = null, $c = null, $d = null)
		{ return $this->statuses_followers($a, $b, $c, $d); }
	public function statuses_followers($u, $type = TWITTER_ID, $cursor = null)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		if ($cursor)
		{
			$vars["cursor"] = $cursor;
		}
		return $this->callMethod("statuses/followers", $vars);
	}

	// Lists (Oh Fuck, They really complicated THIS >.>")
	public function lists_create($name, $description = NULL, $privacy = TWITTER_PRIVACY_PUBLIC)
	{
		$vars = array ();
		$vars["name"] = $name;
		if ($privacy)
		{
			$vars["mode"] = "public";
		}
		else
		{
			$vars["mode"] = "private";
		}
		if ($description)
		{
			$vars["description"] = $description;
		}
		return $this->callMethod($this->t->screen_name."/lists", $vars, "POST");
	}
	public function lists_update($old_name, $new_name = null, $description = NULL, $privacy = null)
	{
		$vars = array ();
		if ($new_name)
		{
			$vars["name"] = $new_name;
		}
		if ($privacy)
		{
			$vars["mode"] = "public";
		}
		elseif ($privacy !== null)
		{
			$vars["mode"] = "private";
		}
		if ($description)
		{
			$vars["description"] = $description;
		}
		return $this->callMethod($this->t->screen_name."/lists/".$old_name, $vars, "POST");
	}
	public function lists_get($user = null, $cursor = null)
	{
		if (!$user)
		{
			$user = $this->t->screen_name;
		}
		if ($cursor)
		{
			$vars = array ("cursor"=>$cursor);
		}
		else
		{
			$vars = array ();
		}
		return $this->callMethod($user."/lists");
	}
	public function lists_display($user, $list)
	{
		return $this->callMethod($user."/lists/".$list);
	}
	public function lists_statuses($user, $list, $since_id = null, $max_id = null, $count = null, $page = null)
	{
		return $this->lists_timeline($user, $list, $since_id, $max_id, $count, $page);
	}
	public function lists_timeline($user, $list, $since_id = null, $max_id = null, $count = null, $page = null)
	{
		$vars = array ();
		if ($since_id)
		{
			$vars["since_id"] = $since_id;
		}
		if ($max_id)
		{
			$vars["max_id"] = $max_id;
		}
		if ($count)
		{
			$vars["count"] = $count;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}
		return $this->callMethod($user."/lists/".$list."/statuses", $vars);
	}
	public function lists_memberships($user, $list, $cursor = null)
	{
		if ($cursor)
		{
			$vars = array ("cursor"=>$cursor);
		}
		else
		{
			$vars = array ();
		}
		return $this->callmethod($user."/lists/".$list."/memberships", $vars);
	}
	public function lists_subscriptions($user = null)
	{
		if (!$user)
		{
			$user = $this->t->screen_name;
		}
		return $this->callMethod($user."/lists/subscriptions");
	}
	public function lists_destroy($name)
	{
		return $this->callMethod($this->t->screen_name."/lists/".$name, array ("_method"=>"DELETE"), "POST");
	}

	// List Members Methods
	public function list_members($user, $list, $cursor = null)
	{
		if ($cursor)
		{
			$vars = array ("cursor"=>$cursor);
		}
		else
		{
			$vars = array ();
		}
		return $this->callMethod($user."/".$list."/members", $vars);
	}
	public function list_membersAdd($list, $user_id_to_add)
	{
		return $this->callMethod($this->t->screen_name."/".$list."/members", array ("id"=>$user_id_to_add), "POST");
	}
	public function list_membersDelete($list, $user_id_to_delete)
	{
		return $this->callMethod($this->t->screen_name."/".$list."/members", array ("id"=>$user_id_to_delete, "_method"=>"DELETE"), "POST");
	}
	public function list_isMember($user, $list, $other_user)
	{
		return $this->callMethod($this->t->screen_name."/".$list."/members/".$other_user);
	}

	// List Subscribers Methods
	public function list_subscribers($user, $list, $cursor = null)
	{
		if ($cursor)
		{
			$vars = array ("cursor"=>$cursor);
		}
		else
		{
			$vars = array ();
		}
		return $this->callMethod($user."/".$list."/subscribers", $vars);
	}
	public function list_subscribe($user, $list)
	{
		return $this->callMethod($user."/".$list."/subscribers", null, "POST");
	}
	public function list_unsubscribe($user, $list)
	{
		return $this->callMethod($user."/".$list."/subscribers", array ("_method"=>"DELETE"), "POST");
	}
	public function list_isSubscriber($user, $list, $other_user)
	{
		return $this->callMethod($user."/".$list."/subscribers/".$other_user);
	}

	// Direct Messages
	public function direct_messages($a = null, $b = null, $c = null, $d = null)
	{
		return $this->dms($a, $b, $c, $d);
	}
	public function dms($since_id = null, $max_id = null, $count = null, $page = null)
	{
		$vars = array ();
		if ($since_id)
		{
			$vars["since_id"] = $since_id;
		}
		if ($max_id)
		{
			$vars["max_id"] = $max_id;
		}
		if ($count)
		{
			$vars["count"] = $count;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}
		return $this->callMethod("direct_messages", $vars);
	}
	public function dms_sent($since_id = null, $max_id = null, $count = null, $page = null)
	{
		$vars = array ();
		if ($since_id)
		{
			$vars["since_id"] = $since_id;
		}
		if ($max_id)
		{
			$vars["max_id"] = $max_id;
		}
		if ($count)
		{
			$vars["count"] = $count;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}
		return $this->callMethod("direct_messages/sent", $vars);
	}
	public function dms_new($text, $u, $type = TWITTER_ID)
	{
		$vars = array ();
		$vars["text"] = $text;
		$vars = $this->addUser($vars, $u, $type);
		return $this->callMethod("direct_messages/new", $vars, "POST");
	}
	public function dms_destroy($id)
	{
		return $this->callMethod("direct_messages/destroy", array ("id"=>$id), "POST");
	}

	// Friendship Methods
	public function friendships_create($u, $type = TWITTER_ID, $follow = null)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		if ($follow)
		{
			$vars["follow"] = $follow;
		}
		return $this->callMethod("friendships/create", $vars, "POST");
	}
	public function friendships_destroy($u, $type = TWITTER_ID)
	{
		$vars = array ();
		$call = "";
		$vars = $this->addUser($vars, $u, $type);
		return $this->callMethod("friendships/destroy", $vars, "POST");
	}
	public function friendships_exists($user_a, $user_b)
	{
		return $this->callMethod("friendships/exists", array ("user_a"=>$user_a, "user_b"=>$user_b), "GET");
	}
	public function friendships_show($target_id = null, $target_sn = null, $source_id = null, $source_sn = null)
	{
		$vars = array ();
		if ($target_id)
		{
			$vars["target_id"] = $target_id;
		}
		elseif ($target_sn)
		{
			$vars["target_screen_name"] = $target_sn;
		}
		else
		{
			throw Exception("User ID or Screen Name Required.");
		}
		if ($source_id)
		{
			$vars["source_id"] = $source_id;
		}
		elseif ($source_sn)
		{
			$vars["source_screen_name"] = $source_sn;
		}
		return $this->callMethod("friendships/show", $vars);
	}

	// Social Graphs
	public function friends_ids($u, $type = TWITTER_ID, $cursor = null)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		if ($cursor)
		{
			$vars["cursor"] = $cursor;
		}
		return $this->callMethod("friends/ids", $vars);
	}
	public function followers_ids($u, $type = TWITTER_ID, $cusor = null)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		if ($cursor)
		{
			$vars["cursor"] = $cursor;
		}
		return $this->callMethod("followers/ids", $vars);
	}

	// Account
	public function account_verifyCredentials()
	{
		return $this->callMethod("account/verify_credentials");
	}
	public function account_rateLimitStatus()
	{
		$json = $this->callMethod("account/rate_limit_status");
		$this->t->setRateLimit($json["hourly_limit"],$json["remaining_hits"],$json["reset_time_in_seconds"]);
		return $json;
	}
	public function account_endSession()
	{
		return $this->callMethod("account/end_session", null, "POST");
	}
	public function account_updateDeliveryDevice($device = null)
	{
		if ($device === null)
		{
			throw Exception("Device Required.");
		}
		return $this->callMethod("account/update_delivery_device", array ("device"=>$device), "POST");
	}
	public function account_updateProfileColors($bgColor = null, $txtColor = null, $linkColor = null, $sidebarFill = null, $sidebarBorder = null)
	{
		$vars = array ();
		if ($bgColor)
		{
			$vars["profile_background_color"] = $bgColor;
		}
		if ($txtColor)
		{
			$vars["profile_text_color"] = $txtColor;
		}
		if ($linkColor)
		{
			$vars["profile_link_color"] = $linkColor;
		}
		if ($sidebarFill)
		{
			$vars["profile_sidebar_fill_color"] = $sidebarFill;
		}
		if ($sidebarBorder)
		{
			$vars["profile_sidebar_border_color"] = $sidebarBorder;
		}
		return $this->callMethod("account/update_profile_colors", $vars, "POST");
	}
	public function account_updateProfileImage($image = null)
	{
		if ($image === null)
		{
			throw Exception("Image Required");
		}
		return $this->callMethod("account/update_profile_image", array ("image"=>$image), "POST");
	}
	public function account_updateProfileBackgroundImage($image = null, $tile = null)
	{
		$vars = array ();
		if ($image === null)
		{
			throw Exception("Image Required");
		}
		else
		{
			$vars["@image"] = "@".$image;
		}
		if ($tile !== null)
		{
			$vars["tile"] = $tile;
		}
		return $this->callMethod("account/update_profile_background_image", $vars, "POST");
	}
	public function account_updateProfile($name = null, $url = null, $location = null, $description = null)
	{
		$vars = array ();
		if ($name)
		{
			$vars["name"] = $name;
		}
		if ($url)
		{
			$vars["url"] = $url;
		}
		if ($location)
		{
			$vars["location"] = $location;
		}
		if ($description)
		{
			$vars["description"] = $description;
		}

		return $this->callMethod("account/update_profile", $vars, "POST");
	}

	// Favorite
	public function favorites_view($id = null, $page = null)
	{
		return $this->favorites($id, $page);
	}
	public function favorites($id = null, $page = null)
	{
		$vars = array ();
		if ($id)
		{
			$vars["id"] = $id;
		}
		if ($page)
		{
			$vars["page"] = $page;
		}

		return $this->callMethod("favorites", $vars);
	}
	public function favorites_create($id)
	{
		return $this->callMethod("favorites/create/$id", null, "POST");
	}
	public function favorites_destroy($id)
	{
		return $this->callMethod("favorites/destroy/$id", null, "POST");
	}

	// Notifications
	public function notifications_follow($u, $type = TWITTER_ID)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		return $this->callMethod("notifications/follow", $vars, "POST");
	}
	public function notifications_leave($u, $type = TWITTER_ID)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		return $this->callMethod("notifications/leave", $vars, "POST");
	}

	// Blocks
	public function blocks_create($u, $type = TWITTER_ID)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		return $this->callMethod("blocks/create", $vars, "POST");
	}
	public function blocks_destroy($u, $type = TWITTER_ID)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		return $this->callMethod("blocks/destroy", $vars, "POST");
	}
	public function blocks_exist($u, $type = TWITTER_ID)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		return $this->callMethod("blocks/exists", $vars);
	}
	public function blocks_blocking($page = null)
	{
		$vars = array ();
		if ($page)
		{
			$vars["page"] = $page;
		}
		return $this->callMethod("blocks/blocking", $vars);
	}
	public function blocks_blockingIDs()
	{
		return $this->callMethod("blocks/blocking_ids");
	}

	// Spam
	public function report_spam($u, $type = TWITTER_ID)
	{
		$vars = array ();
		$vars = $this->addUser($vars, $u, $type);
		return $this->callMethod("report_spam", $vars, "POST");
	}

	// Saved Searches
	public function saved_searches()
	{
		return $this->searches_view();
	}
	public function searches_view()
	{
		return $this->callMethod("saved_searches");
	}
	public function searches_show($id)
	{
		return $this->callMethod("saved_searches/show/$id");
	}
	public function searches_create($query)
	{
		return $this->callMethod("saved_searches/create", array ("query"=>$query), "POST");
	}
	public function searches_destroy($id)
	{
		return $this->callMethod("saved_searches/destroy/$id", null, "POST");
	}

	// Local Trend Methods
	public function trends_available($lat = NULL, $long = NULL)
	{
		if ($lat || $long)
		{
			$vars = array ("lat"=>$lat, "long"=>$long);
		}
		return $this->callMethod("trends/available", $vars);
	}
	public function trends_location($woeid)
	{
		return $this->callMethod("trends/$woeid");
	}

	// Help
	public function test()
	{
		return $this->callMethod("help/test");
	}
	// Some of Ours

	public function call_amount($type = "total")
	{
		if ( isset ($this->count[$type]))
		{
			return $this->count[$type];
		}
		else
		{
			$total = 0;
			foreach ($this->count as $v)
			{
				$total += $v;
			}
		}
		return $v;
	}

	protected function callMethod($method, $vars = null, $type = null)
	{
		if ($type === null)
		{
			$type = "GET";
		}
		else
		{
			$type = strtoupper($type);
		}
		$uri = "https://api.twitter.com/1/".$method.".json";
		return $this->request($uri, $vars, $type);
	}
	protected function request($uri, $vars, $type)
	{
		if((!$this->t->uid || $this->t_noauth || $this->noauth) && !$this->t->forceauth)
		{
			$result = json_decode($this->t->noauth->noAuthRequest($uri,$type,$vars),TRUE);
			$this->count["unauthenticated"]++;
			$http_code = $this->t->noauth->http_code;
			if(isset($this->t->noauth->http_info["X-RATELIMIT-LIMIT"]))
				{ $this->t->setRateLimit($this->auth->http_info["X-RATELIMIT-LIMIT"],$this->t->noauth->http_info["X-RATELIMIT-REMAINING"],strtotime($this->t->noauth->http_info["X-RATELIMIT-RESET"])); }
			if($this->t_noauth)
				{ $this->t_noauth--; }
		}
		else
		{
			$result = json_decode($this->auth->OAuthRequest($uri, $type, $vars),TRUE);
			$this->count["authenticated"]++;
			$http_code = $this->auth->http_code;
			if(isset($this->auth->http_info["X-RATELIMIT-LIMIT"]))
				{ $this->t->setRateLimit($this->auth->http_info["X-RATELIMIT-LIMIT"],$this->auth->http_info["X-RATELIMIT-REMAINING"],strtotime($this->auth->http_info["X-RATELIMIT-RESET"])); }
		}
		$this->call_log[] = $uri;
		$result["TAPI"]["response_code"] = $http_code;
		return $result;
	}
	private function debugVar($var)
	{
		//        print("<pre>");
		//        var_dump($var);
		//        print("</pre>");
	}
}
class TwitterNoAuth
{
	public $useragent = TAPI_IDENTIFIER;
	public $ssl_verifypeer = FALSE;
	public $timeout = 30;
	public $connecttimeout = 30;
	public $host = "http://api.twitter.com/1/";
	function noAuthRequest($url,$type,$vars)
	{
		$this->http_info = array();
		$ci = curl_init();
		/* Curl Settings */
		curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
		curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
		curl_setopt($ci, CURLOPT_HEADER, FALSE);
		switch($type)
		{
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, TRUE);
				if(!empty($vars))
					{ curl_setopt($ci, CURLOPT_POSTFIELDS, $vars); }
				break;
			case 'DELETE':
				curl_setopt($ci, CURLOPT_CUSTOMREQUEST, "DELETE");
			default:
				if(!empty($vars))
				{
					$tvars = "?";
					foreach($vars as $k => $v)
					{
						$tvars .= $k."=".urlencode($v)."&";
					}
					$url = $url .= $tvars;
				}
		}
		curl_setopt($ci, CURLOPT_URL, $url);
		$response = curl_exec($ci);
		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
		$this->url = $url;
		curl_close($ci);
		return $response;
	}
	function getHeader($ch, $header)
	{
		$i = strpos($header,":");
		if(!empty($i))
		{
			$key = str_replace("-","_",strtolower(substr($header,0,$i)));
			$value = trim(substr($header,$i+2));
			$this->http_info[$key] = $value;
		}
		return strlen($header);
	}
}
class TWML
{
	protected $t;
	protected $turled = false;
	function __construct($instance)
	{
		$this->t = $instance;
	}
	public function useTurled($bool)
	{
		if ($bool === true || $bool === false)
		{
			$this->turled = $bool;
		}
		return $this->turled;
	}
	public function profile_pic($uid = NULL, $opts = NULL)
	{
		$data = array ();
		if ($uid === null)
		{
			$uid = $this->t->uid;
		}
		$sn = $this->t->get_sn_from_id($uid);
		$title = $this->t->get_name_from_id($uid);
		$title.= " (@".$sn.")";
		if (! isset ($opts))
		{
			$opts = array ();
		}
		if (! isset ($opts["linked"]))
		{
			$opts["linked"] = true;
		}
		if (! isset ($opts["size"]))
		{
			$opts["size"] = "thumb";
		}
		// Sizes
		// Small, Thumb, Large, Full
		if ($opts["size"] == "small" || $opts["size"] == "mini")
		{
			$img = "http://twivatar.org/$sn/mini";
			$w = 24;
			$h = 24;
		}
		elseif ($opts["size"] == "thumb" || $opts["size"] == "normal")
		{
			$img = "http://twivatar.org/$sn/normal";
			$w = 48;
			$h = 48;
		}
		elseif ($opts["size"] == "large" || $opts["size"] == "bigger")
		{
			$img = "http://twivatar.org/$sn/bigger";
			$w = 73;
			$h = 73;
		}
		elseif ($opts["size"] == "full" || $opts["size"] == "original")
		{
			$img = "http://twivatar.org/$sn/original";
		}
		if ( isset ($opts["height"]))
		{
			$h = $opts["height"];
		}
		if ( isset ($opts["width"]))
		{
			$w = $opts["width"];
		}
		$html = "";
		if ($opts["linked"])
		{
			$html .= "<a href=\"http://twitter.com/$sn\">";
		}
		$html .= "<img src=\"$img\" alt=\"\"";
		if ($w || $h)
		{
			$html .= " style=\"";
			if ($w)
			{
				$html .= "width:$w;";
			}
			if ($h)
			{
				$html .= "height:$h;";
			}
			$html .= "\"";
		}
		$html .= " title=\"$title\" />";
		if ($opts["linked"])
		{
			$html .= "</a>";
		}
		return $html;
	}
	public function name($uid = NULL, $opts = NULL)
	{
		// Grab UID First
		$data = array ();
		if (! isset ($opts))
		{
			$opts = array ();
		}
		if (! isset ($opts["linked"]))
		{
			$opts["linked"] = true;
		}
		if (! isset ($opts["useyou"]))
		{
			$opts["useyou"] = true;
		}
		if (! isset ($opts["firstnameonly"]))
		{
			$opts["firstnameonly"] = false;
		}
		if (! isset ($opts["lastnameonly"]))
		{
			$opts["lastnameonly"] = false;
		}
		if (! isset ($opts["screennameonly"]))
		{
			$opts["screennameonly"] = false;
		}
		if (! isset ($opts["possessive"]))
		{
			$opts["possessive"] = false;
		}
		if (! isset ($opts["reflexive"]))
		{
			$opts["reflexive"] = false;
		}
		if (! isset ($opts["capitalize"]))
		{
			$opts["capitalize"] = false;
		}
		if (! isset ($opts["subjectid"]))
		{
			$opts["subjectid"] = NULL;
		}

		if ($uid == NULL || $uid == $this->t->uid || strtolower($uid) == "loggedinuser")
		{
			$data["name"] = $this->t->name;
			$data["sn"] = $this->t->screen_name;
			$data["uid"] = $this->t->uid;
			$data["first_name"] = $this->t->first_name;
			$data["last_name"] = $this->t->last_name;
			$sn = $data["sn"];
			// Defaults, just in case.
			$display = "you";
		}
		else
		{
			$data["name"] = $this->t->get_name_from_id($uid);
			$data["sn"] = $this->t->get_sn_from_id($uid);
			$data["uid"] = $uid;
			$tdata = explode(" ", $data["name"]);
			$data["first_name"] = $tdata[0];
			$data["last_name"] = $tdata[count($tdata)-1];
			$sn = $data["sn"];
			// Defaults, just in case.
			$display = $data["name"];
		}

		if ($data["uid"] == $this->t->uid && $opts["useyou"])
		{
			if (($opts["reflexive"] || $opts["subjectid"] == $data["uid"]) && $opts["possessive"])
			{
				if ($opts["capitalize"])
				{
					$display = "Your own";
				}
				else
				{
					$display = "your own";
				}
			}
			elseif ($opts["reflexive"] || $opts["subjectid"] == $data["uid"])
			{
				if ($opts["capitalize"])
				{
					$display = "Yourself";
				}
				else
				{
					$display = "yourself";
				}
			}
			elseif ($opts["possessive"])
			{
				if ($opts["capitalize"])
				{
					$display = "Your";
				}
				else
				{
					$display = "your";
				}
			}
			else
			{
				if ($opts["capitalize"])
				{
					$display = "You";
				}
				else
				{
					$display = "you";
				}
			}
		}
		elseif ($opts["firstnameonly"])
		{
			$display = $data["first_name"];
		}
		elseif ($opts["lastnameonly"])
		{
			if ($data["last_name"])
			{
				$display = $data["last_name"];
			}
			else
			{
				$display = $data["name"];
			}
		}
		elseif ($opts["screennameonly"])
		{
			$display = $data["sn"];
		}
		else
		{
			$display = $data["name"];
		}

		$html = "";

		if ($opts["linked"])
		{
			$html .= "<a href=\"http://twitter.com/$sn\">";
		}

		$html .= $display;

		if ($opts["linked"])
		{
			$html .= "</a>";
		}

		return $html;
	}
	public function if_is_user($uids)
	{
		if (!is_array($uids))
		{
			$uids = explode(",", $uids);
		}
		foreach ($uids as $uid)
		{
			if ($uid == $this->t->uid)
			{
				return true;
			}
		}
		return false;
	}
	public function user_status($uid = NULL,$linked = true)
	{
		if($uid === NULL) { $uid = $this->t->uid; }
		if($this->t->cache->user_available($uid,TWITTER_UID))
			{ $user = $this->t->cache->user_get($uid,TWITTER_UID); }
		else
			{ $user = NULL; }
		if(strtotime($user["status"]["created_at"]) < time()-$this->t->cache->cache_timeout)
			{ $user = NULL; }
		if($user == NULL)
		{
			$user = $this->t->api->users_show($uid,TWITTER_UID);
			$this->t->cache->user_store($user);
		}
		$status = $user["status"]["text"];
		if($linked)
		{
			// URL Regex Needed
			$status = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@i', '<a href="$1" target="_blank">$1</a>',$status);
			$status = preg_replace("#\@([a-z0-9_]+)#i",'@<a href="http://twitter.com/$1" target="_blank">$1</a>',$status);
		//	$status = preg_replace("#([\s]+)\#([\w\d\p{L}_\-\+\.]+)([\.]+)#iu", // Hashtag Needed
		}
		return $status;
	}
}

// Definitions
	define("TWITTER_ID", 1);
	define("TWITTER_UID", 2);
	define("TWITTER_SN", 3);
	define("TWITTER_PRIVACY_PRIVATE", 0);
	define("TWITTER_PRIVACY_PUBLIC", 1);
	define("TAPI_IDENTIFIER", "simpleTAPI v0.2.1");

// Crazy Ass Twitter OAuth Non-Callback Workaround
	if (!session_id())
	{
		session_start();
	}
	header("X-Twitter-API: ".TAPI_IDENTIFIER);
	if ( isset ($_SESSION["TWITTERAPI_vars"]["RETURN_URI"]))
	{
		if ( isset ($_REQUEST['oauth_token']))
		{
			$_SESSION["TWITTERAPI_vars"]["OAUTH_TOKEN"] = $_REQUEST["oauth_token"];
		}
		$url = $_SESSION["TWITTERAPI_vars"]["RETURN_URI"];
		if ($_SESSION["TWITTERAPI_vars"]["RETURN_VARS"])
		{
			$url .= "?".$_SESSION["TWITTERAPI_vars"]["RETURN_VARS"];
		}
		unset ($_SESSION["TWITTERAPI_vars"]["RETURN_URI"]);
		session_write_close();
		session_regenerate_id();
		header("Location: $url");
		print('<a href="$url">$url</a>');
		die();
	}

// File Loader
	if (!class_exists("TwitterOAuth"))
	{
		require_once (dirname( __FILE__ )."/TwitterOAuth.php");
	}
	if (!class_exists("OAuthConsumer"))
	{
		require_once (dirname( __FILE__ )."/OAuth.php");
	}
