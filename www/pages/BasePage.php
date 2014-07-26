<?php
require_once SMARTY_DIR . 'Smarty.class.php';
require_once nZEDb_LIB . 'utility' . DS .'SmartyUtils.php';

class BasePage
{
	/**
	 * @var \nzedb\db\Settings
	 */
	public $settings = null;

	/**
	 * @var Users
	 */
	public $users = null;

	/**
	 * @var Smarty
	 */
	public $smarty = null;

	public $title = '';
	public $content = '';
	public $head = '';
	public $body = '';
	public $meta_keywords = '';
	public $meta_title = '';
	public $meta_description = '';
	public $page = '';
	public $page_template = '';
	public $userdata = array();
	public $serverurl = '';

	/**
	 * Whether to trim white space before rendering the page or not.
	 * @var bool
	 */
	public $trimWhiteSpace = true;

	const FLOOD_THREE_REQUESTS_WITHIN_X_SECONDS = 1.000;
	const FLOOD_PUNISHMENT_SECONDS = 3.0;

	/**
	 *
	 */
	public function __construct()
	{
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$secure_cookie = '1';
		} else {
			$secure_cookie = '0';
		}

		session_set_cookie_params(0, '/', '', $secure_cookie, 'true');
		@session_start();

		if ((function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) || ini_get('magic_quotes_sybase')) {
			foreach($_GET as $k => $v) $_GET[$k] = (is_array($v)) ? array_map("stripslashes", $v) : stripslashes($v);
			foreach($_POST as $k => $v) $_POST[$k] = (is_array($v)) ? array_map("stripslashes", $v) : stripslashes($v);
			foreach($_REQUEST as $k => $v) $_REQUEST[$k] = (is_array($v)) ? array_map("stripslashes", $v) : stripslashes($v);
			foreach($_COOKIE as $k => $v) $_COOKIE[$k] = (is_array($v)) ? array_map("stripslashes", $v) : stripslashes($v);
		}

		// Set settings variable.
		$this->settings = new \nzedb\db\Settings();

		$this->smarty = new Smarty();
		$this->smarty->setTemplateDir(
			array(
				'user_frontend' => nZEDb_WWW.'themes/' . $this->settings->getSetting('style') . '/templates/frontend',
				'frontend' => nZEDb_WWW . 'themes/Default/templates/frontend'
			)
		);

		$this->smarty->setCompileDir(SMARTY_DIR.'templates_c/');
		$this->smarty->setConfigDir(SMARTY_DIR.'configs/');
		$this->smarty->setCacheDir(SMARTY_DIR.'cache/');
		$this->smarty->error_reporting = (E_ALL - E_NOTICE);

		if (isset($_SERVER['SERVER_NAME'])) {
			if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
				$httpstart = 'https://';
			} else {
				$httpstart = 'http://';
			}

			$this->serverurl = (
				$httpstart . $_SERVER['SERVER_NAME'] .
				(($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') ? ':'.$_SERVER['SERVER_PORT'] : '') .
				WWW_TOP . '/'
			);
			$this->smarty->assign('serverroot', $this->serverurl);
		}

		$this->page = (isset($_GET['page']) ? $_GET['page'] : 'content');

		$this->users = new Users(['Settings' => $this->settings]);
		if ($this->users->isLoggedIn()) {
			$this->userdata = $this->users->getById($this->users->currentUserId());
			$this->userdata['categoryexclusions'] = $this->users->getCategoryExclusion($this->users->currentUserId());

			// Change the theme to user's selected theme.
			if (isset($this->userdata['style']) && $this->userdata['style'] !== 'None') {
				$this->smarty->setTemplateDir(
					array(
						'user_frontend' => nZEDb_WWW . 'themes/' . $this->userdata['style'] . '/templates/frontend',
						'frontend' => nZEDb_WWW . 'themes/Default/templates/frontend'
					)
				);
			}

			// Update lastlogin every 15 mins.
			if (strtotime($this->userdata['now'])-900 > strtotime($this->userdata['lastlogin'])) {
				$this->users->updateSiteAccessed($this->userdata['id']);
			}

			$this->smarty->assign('userdata',$this->userdata);
			$this->smarty->assign('loggedin', 'true');

			$sab = new SABnzbd($this);
			$integrated = false;
			switch ($sab->integrated) {
				case SABnzbd::INTEGRATION_TYPE_NONE:
					if ($this->userdata['queuetype'] == 2) {
						$integrated = true;
					}
					break;
				case SABnzbd::INTEGRATION_TYPE_SITEWIDE:
					$integrated = true;
					break;
				case SABnzbd::INTEGRATION_TYPE_USER:
					switch((int)$this->userdata['queuetype']) {
						case 1:
						case 2:
							$integrated = true;
							break;
						default:
							$integrated = false;
							break;
					}
					break;
				default:
					$integrated = false;
			}

			$this->smarty->assign('sabintegrated', $integrated);
			if ($integrated !== false && $sab->url != '' && $sab->apikey != '') {
				$this->smarty->assign('sabapikeytype', $sab->apikeytype);
			}

			if ($this->userdata['role'] == Users::ROLE_ADMIN) {
				$this->smarty->assign('isadmin', 'true');
			} else if ($this->userdata['role'] == Users::ROLE_MODERATOR) {
				$this->smarty->assign('ismod', 'true');
			}

			//$this->floodCheck(true, $this->userdata['role']);
		} else {
			$this->smarty->assign('isadmin', 'false');
			$this->smarty->assign('ismod', 'false');
			$this->smarty->assign('loggedin', 'false');

			//$this->floodCheck(false, '');
		}

		$this->smarty->assign('site', $this->settings);
		$this->smarty->assign('page', $this);
	}

	/**
	 * Check if the user is flooding.
	 *
	 * @param bool $loggedIn
	 * @param int  $role
	 */
	public function floodCheck($loggedIn, $role)
	{
		// If flood wait set, the user must wait x seconds until they can access a page.
		if (empty($argc) && $role != Users::ROLE_ADMIN && isset($_SESSION['flood_wait_until']) && $_SESSION['flood_wait_until'] > microtime(true)) {
			$this->showFloodWarning();
		} else {
			// If user not an admin, they are allowed three requests in FLOOD_THREE_REQUESTS_WITHIN_X_SECONDS seconds.
			if(empty($argc) && $role != Users::ROLE_ADMIN) {
				if (!isset($_SESSION['flood_check'])) {
					$_SESSION['flood_check'] = '1_'.microtime(true);
				} else {
					$hit = substr($_SESSION['flood_check'], 0, strpos($_SESSION['flood_check'], '_', 0));
					if ($hit >= 3) {
						$onetime = substr($_SESSION['flood_check'], strpos($_SESSION['flood_check'], '_') + 1);
						if ($onetime + BasePage::FLOOD_THREE_REQUESTS_WITHIN_X_SECONDS > microtime(true)) {
							$_SESSION['flood_wait_until'] = microtime(true) + BasePage::FLOOD_PUNISHMENT_SECONDS;
							unset($_SESSION['flood_check']);
							$this->showFloodWarning();
						} else {
							$_SESSION['flood_check'] = '1_'.microtime(true);
						}
					} else {
						$hit++;
						$_SESSION['flood_check'] = $hit.substr($_SESSION['flood_check'], strpos($_SESSION['flood_check'], '_', 0));
					}
				}
			}
		}
	}

	/**
	 * Done in html here to reduce any smarty processing burden if a large flood is underway.
	 */
	public function showFloodWarning()
	{
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Retry-After: ' . BasePage::FLOOD_PUNISHMENT_SECONDS);
		exit("
			<html>
			<head>
				<title>Service Unavailable</title>
			</head>

			<body>
				<h1>Service Unavailable</h1>

				<p>Too many requests!</p>

				<p>You must <b>wait " . BasePage::FLOOD_PUNISHMENT_SECONDS . " seconds</b> before trying again.</p>

			</body>
			</html>"
		);
	}

	/**
	 * Inject content into the html head.
	 *
	 * @param string $headContent
	 */
	public function addToHead($headContent)
	{
		$this->head .= ("\n" . $headContent);
	}

	/**
	 * Inject js/attributes into the html body tag.
	 *
	 * @param string $attribute
	 */
	public function addToBody($attribute)
	{
		$this->body .= (' ' . $attribute);
	}

	/**
	 * @return bool
	 */
	public function isPostBack()
	{
		return (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST');
	}

	/**
	 * Show 404 page.
	 */
	public function show404()
	{
		header('HTTP/1.1 404 Not Found');
		exit();
	}

	/**
	 * Show 403 page.
	 *
	 * @param bool $from_admin
	 */
	public function show403($from_admin = false)
	{
		header(
			'Location: ' .
			($from_admin ? str_replace('/admin', '', WWW_TOP) : WWW_TOP) .
			'/login?redirect=' .
			urlencode($_SERVER['REQUEST_URI'])
		);
		exit();
	}

	/**
	 * Show 503 page.
	 */
	public function show503()
	{
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		exit("
			<html>
				<head>
					<title>Service Unavailable</title>
				</head>
				<body>
					<h1>Service Unavailable</h1>
					<p>Your maximum api or download limit has been reached for the day</p>
				</body>
			</html>"
		);
	}

	/**
	 * Renders a page.
	 */
	public function render()
	{
		if ($this->trimWhiteSpace) {
			$this->smarty->loadFilter('output', 'trimwhitespace');
		}
		$this->smarty->display($this->page_template);
	}
}