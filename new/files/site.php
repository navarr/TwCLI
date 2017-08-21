<?php
	class Site
	{
		public $templates_dir;
		protected $variables;
		protected $our_variables;
		function __construct()
		{
			if(!session_id()) { session_start(); }
			if(isset($_SESSION["SITE_HANDLER"]["PUBLIC_VARIABLES"]))
			{
				foreach($_SESSION["SITE_HANDLER"]["PUBLIC_VARIABLES"] as $key => $val)
				{
					$this->variables[$key] = $val;
				}
			}
			if(isset($_SESSION["SITE_HANDLER"]["PRIVATE_VARIABLES"]))
			{
				foreach($_SESSION["SITE_HANDLER"]["PRIVATE_VARIABLES"] as $key => $val)
				{
					$this->our_variables[$key] = $val;
				}
			}
		}
		public function set($variable_name,$value)
		{
			$this->variables[$variable_name] = serialize($value);
			$_SESSION["SITE_HANDLER"]["PUBLIC_VARIABLES"][$variable_name] = $this->variables[$variable_name];
		}
		public function get($variable_name)
		{
			if(isset($this->variables[$variable_name]))
				{ return unserialize($this->variables[$variable_name]); }
			else
				{ return false; }
		}
		public function have($variable_name)
		{
			if(isset($this->variables[$variable_name])) { return true; }
			else { return false; }
		}
		
		protected function setVar($var,$val)
		{
			$this->our_variables[$var] = serialize($val);
			$_SESSION["SITE_HANDLER"]["PUBLIC_VARIABLES"][$var] = $this->our_variables[$var];
		}
		protected function getVar($var)
		{
			return unserialize($this->our_variables[$var]);
		}
		
		public function close()
		{
			session_write_close();
			session_regenerate_id();
		}
		
		public function redirect($uri,$code)
		{
			$this->close();
			header("Location: $uri",$code);
			die();
		}
		
		public function render($page)
		{
			$this->close();
			$page = $page;
			foreach($this->variables as $var => $val)
			{
				${$var} = unserialize($val);
			}
			require_once($this->templates_dir."/wrapper.ctp");
		}
	}