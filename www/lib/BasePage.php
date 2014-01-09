<?php
require_once SMARTY_DIR . 'Smarty.class.php';
//require_once nZEDb_LIB . 'users.php';
//require_once nZEDb_LIB . 'site.php';
//require_once nZEDb_LIB . 'sabnzbd.php';
//require_once nZEDb_LIB . 'framework/db.php';

class BasePage
{
	public $title = '';
	public $content = '';
	public $head = '';
	public $body = '';
	public $meta_keywords = '';
	public $meta_title = '';
	public $meta_description = '';
	public $page = '';
	public $page_template = '';
	public $smarty = '';
	public $userdata = array();
	public $serverurl = '';
	public $site = '';

	const FLOOD_THREE_REQUESTS_WITHIN_X_SECONDS = 1.000;
	const FLOOD_PUNISHMENT_SECONDS = 3.0;

	function BasePage()
	{
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
			$secure_cookie = '1';
		else
			$secure_cookie = '0';

		session_set_cookie_params(0, '/', '', $secure_cookie, 'true');
		@session_start();

		if ((function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) || ini_get('magic_quotes_sybase'))
		{
			foreach($_GET as $k => $v) $_GET[$k] = (is_array($v)) ? array_map("stripslashes", $v) : stripslashes($v);
			foreach($_POST as $k => $v) $_POST[$k] = (is_array($v)) ? array_map("stripslashes", $v) : stripslashes($v);
			foreach($_REQUEST as $k => $v) $_REQUEST[$k] = (is_array($v)) ? array_map("stripslashes", $v) : stripslashes($v);
			foreach($_COOKIE as $k => $v) $_COOKIE[$k] = (is_array($v)) ? array_map("stripslashes", $v) : stripslashes($v);
		}

		// Set site variable.
		$s = new Sites();
		$this->site = $s->get();

		$this->smarty = new Smarty();
		$this->smarty->setTemplateDir(array('user_frontend' => nZEDb_WWW.'themes/'.$this->site->style.'/templates/frontend', 'frontend' => nZEDb_WWW.'themes/Default/templates/frontend'));

		$this->smarty->setCompileDir(SMARTY_DIR.'templates_c/');
		$this->smarty->setConfigDir(SMARTY_DIR.'configs/');
		$this->smarty->setCacheDir(SMARTY_DIR.'cache/');
		$this->smarty->error_reporting = (E_ALL - E_NOTICE);

		if (isset($_SERVER['SERVER_NAME']))
		{
			if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
				$httpstart = 'https://';
			else
				$httpstart = 'http://';

			$this->serverurl = $httpstart.$_SERVER['SERVER_NAME'].(($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') ? ':'.$_SERVER['SERVER_PORT'] : '').WWW_TOP.'/';
			$this->smarty->assign('serverroot', $this->serverurl);
		}

		$this->page = (isset($_GET['page'])) ? $_GET['page'] : 'content';

		$users = new Users();
		if ($users->isLoggedIn())
		{
			$this->userdata = $users->getById($users->currentUserId());
			$this->userdata['categoryexclusions'] = $users->getCategoryExclusion($users->currentUserId());

			// Update lastlogin every 15 mins.
			if (strtotime($this->userdata['now'])-900 > strtotime($this->userdata['lastlogin']))
				$users->updateSiteAccessed($this->userdata['id']);

			$this->smarty->assign('userdata',$this->userdata);
			$this->smarty->assign('loggedin', 'true');

			$sab = new SABnzbd($this);
			if ($sab->integrated !== false && $sab->url != '' && $sab->apikey != '')
			{
				$this->smarty->assign('sabintegrated', $sab->integrated);
				$this->smarty->assign('sabapikeytype', $sab->apikeytype);
			}
			if ($this->userdata['role'] == Users::ROLE_ADMIN)
				$this->smarty->assign('isadmin', 'true');
			else if ($this->userdata['role'] == Users::ROLE_MODERATOR)
				$this->smarty->assign('ismod', 'true');

			//$this->floodCheck(true, $this->userdata['role']);
		}
		else
		{
			$this->smarty->assign('isadmin', 'false');
			$this->smarty->assign('ismod', 'false');
			$this->smarty->assign('loggedin', 'false');

			//$this->floodCheck(false, '');
		}

	$this->smarty->assign('site', $this->site);
	$this->smarty->assign('page', $this);
	}

	public function floodCheck($loggedin, $role)
	{
		// If flood wait set, the user must wait x seconds until they can access a page.
		if (empty($argc) && $role != Users::ROLE_ADMIN && isset($_SESSION['flood_wait_until']) && $_SESSION['flood_wait_until'] > microtime(true))
			$this->showFloodWarning();
		else
		{
			// If user not an admin, they are allowed three requests in FLOOD_THREE_REQUESTS_WITHIN_X_SECONDS seconds.
			if(empty($argc) && $role != Users::ROLE_ADMIN)
			{
				if (!isset($_SESSION['flood_check']))
					$_SESSION['flood_check'] = '1_'.microtime(true);
				else
				{
					$hit = substr($_SESSION['flood_check'], 0, strpos($_SESSION['flood_check'], '_', 0));
					if ($hit >= 3)
					{
						$onetime = substr($_SESSION['flood_check'], strpos($_SESSION['flood_check'], '_') + 1);
						if ($onetime + BasePage::FLOOD_THREE_REQUESTS_WITHIN_X_SECONDS > microtime(true))
						{
							$_SESSION['flood_wait_until'] = microtime(true) + BasePage::FLOOD_PUNISHMENT_SECONDS;
							unset($_SESSION['flood_check']);
							$this->showFloodWarning();
						}
						else
							$_SESSION['flood_check'] = '1_'.microtime(true);
					}
					else
					{
						$hit++;
						$_SESSION['flood_check'] = $hit.substr($_SESSION['flood_check'], strpos($_SESSION['flood_check'], '_', 0));
					}
				}
			}
		}
	}

	// Done in html here to reduce any smarty processing burden if a large flood is underway.
	public function showFloodWarning()
	{
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Retry-After: '.BasePage::FLOOD_PUNISHMENT_SECONDS);
		echo "
			<html>
			<head>
				<title>Service Unavailable</title>
			</head>

			<body>
				<h1>Service Unavailable</h1>

				<p>Too many requests!</p>

				<p>You must <b>wait ".BasePage::FLOOD_PUNISHMENT_SECONDS." seconds</b> before trying again.</p>

			</body>
			</html>";
		die();
	}

	// Inject content into the html head.
	public function addToHead($headcontent)
	{
		$this->head = $this->head."\n".$headcontent;
	}

	// Inject js/attributes into the html body tag.
	public function addToBody($attr)
	{
		$this->body = $this->body.' '.$attr;
	}

	public function isPostBack()
	{
		return (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST');
	}

	public function show404()
	{
		header('HTTP/1.1 404 Not Found');
		die();
	}

	public function show403($from_admin = false)
	{
		$redirect_path = ($from_admin) ? str_replace('/admin', '', WWW_TOP) : WWW_TOP;
		header('Location: '.$redirect_path.'/login?redirect='.urlencode($_SERVER['REQUEST_URI']));
		die();
	}

	public function show503()
	{
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		echo "
			<html>
			<head>
				<title>Service Unavailable</title>
			</head>

			<body>
				<h1>Service Unavailable</h1>

				<p>Your maximum api or download limit has been reached for the day</p>

			</body>
			</html>";
		die();
	}

	public function render()
	{
		$this->smarty->display($this->page_template);
	}
}
