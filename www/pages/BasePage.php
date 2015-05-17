<?php
require_once SMARTY_DIR . 'Smarty.class.php';
require_once nZEDb_LIB . 'utility' . DS . 'SmartyUtils.php';

use nzedb\SABnzbd;
use nzedb\Users;
use nzedb\db\Settings;

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

	/**
	 * Current page the user is browsing. ie browse
	 * @var string
	 */
	public $page = '';

	public $page_template = '';

	/**
	 * User settings from the MySQL DB.
	 * @var array|bool
	 */
	public $userdata = array();

	/**
	 * URL of the server. ie http://localhost/
	 * @var string
	 */
	public $serverurl = '';

	/**
	 * Whether to trim white space before rendering the page or not.
	 * @var bool
	 */
	public $trimWhiteSpace = true;

	/**
	 * Is the current session HTTPS?
	 * @var bool
	 */
	public $https = false;

	/**
	 * Public access to Captcha object for error checking.
	 *
	 * @var \nzedb\Captcha
	 */
	public $captcha;

	/**
	 * Set up session / smarty / user variables.
	 */
	public function __construct()
	{
		$this->https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? true : false);

		session_set_cookie_params(0, '/', '', $this->https, true);
		@session_start();

		if (nZEDb_FLOOD_CHECK) {
			$this->floodCheck();
		}

		if ((function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) || ini_get('magic_quotes_sybase')) {
			$this->stripSlashes($_GET);
			$this->stripSlashes($_POST);
			$this->stripSlashes($_REQUEST);
			$this->stripSlashes($_COOKIE);
		}

		// Buffer settings/DB connection.
		$this->settings = new Settings();

		$this->smarty = new Smarty();

		$this->smarty->setTemplateDir(
			array(
				'user_frontend' => nZEDb_WWW . 'themes/' . $this->settings->getSetting('style') . '/templates/frontend',
				'frontend' => nZEDb_WWW . 'themes/Default/templates/frontend'
			)
		);
		$this->smarty->setCompileDir(SMARTY_DIR . 'templates_c/');
		$this->smarty->setConfigDir(SMARTY_DIR . 'configs/');
		$this->smarty->setCacheDir(SMARTY_DIR . 'cache/');
		$this->smarty->error_reporting = ((nZEDb_DEBUG ? E_ALL : E_ALL - E_NOTICE));

		if (isset($_SERVER['SERVER_NAME'])) {
			$this->serverurl = (
				($this->https === true ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] .
				(($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') ? ':' . $_SERVER['SERVER_PORT'] : '') .
				WWW_TOP . '/'
			);
			$this->smarty->assign('serverroot', $this->serverurl);
		}

		$this->page = (isset($_GET['page']) ? $_GET['page'] : 'content');

		$this->users = new Users(['Settings' => $this->settings]);
		if ($this->users->isLoggedIn()) {
			$this->userdata = $this->users->getById($this->users->currentUserId());
			$this->userdata['categoryexclusions'] = $this->users->getCategoryExclusion($this->users->currentUserId());

			// Change the theme to user's selected theme if they selected one, else use the admin one.
			if (isset($this->userdata['style']) && $this->userdata['style'] !== 'None') {
				$this->smarty->setTemplateDir(
					array(
						'user_frontend' => nZEDb_WWW . 'themes/' . $this->userdata['style'] . '/templates/frontend',
						'frontend'      => nZEDb_WWW . 'themes/Default/templates/frontend'
					)
				);
			}

			// Update last login every 15 mins.
			if ((strtotime($this->userdata['now']) - 900) > strtotime($this->userdata['lastlogin'])) {
				$this->users->updateSiteAccessed($this->userdata['id']);
			}

			$this->smarty->assign('userdata', $this->userdata);
			$this->smarty->assign('loggedin', 'true');

			$sab = new SABnzbd($this);
			$this->smarty->assign('sabintegrated', $sab->integratedBool);
			if ($sab->integratedBool !== false && $sab->url != '' && $sab->apikey != '') {
				$this->smarty->assign('sabapikeytype', $sab->apikeytype);
			}

			switch ((int)$this->userdata['role']) {
				case Users::ROLE_ADMIN:
					$this->smarty->assign('isadmin', 'true');
					break;
				case Users::ROLE_MODERATOR:
					$this->smarty->assign('ismod', 'true');
			}
		} else {
			$this->smarty->assign('isadmin', 'false');
			$this->smarty->assign('ismod', 'false');
			$this->smarty->assign('loggedin', 'false');
		}

		$this->smarty->assign('site', $this->settings);
		$this->smarty->assign('page', $this);
	}

	/**
	 * Unquotes quoted strings recursively in an array.
	 *
	 * @param $array
	 */
	private function stripSlashes(&$array)
	{
		foreach ($array as $key => $value) {
			$array[$key] = (is_array($value) ? array_map('stripslashes', $value) : stripslashes($value));
		}
	}

	/**
	 * Check if the user is flooding.
	 */
	public function floodCheck()
	{
		$waitTime = (nZEDb_FLOOD_WAIT_TIME < 1 ? 5 : nZEDb_FLOOD_WAIT_TIME);
		// Check if this is not from CLI.
		if (empty($argc)) {
			// If flood wait set, the user must wait x seconds until they can access a page.
			if (isset($_SESSION['flood_wait_until']) && $_SESSION['flood_wait_until'] > microtime(true)) {
				$this->showFloodWarning($waitTime);
			} else {
				// If user not an admin, they are allowed three requests in FLOOD_THREE_REQUESTS_WITHIN_X_SECONDS seconds.
				if (!isset($_SESSION['flood_check_hits'])) {
					$_SESSION['flood_check_hits'] = 1;
					$_SESSION['flood_check_time'] = microtime(true);
				} else {
					if ($_SESSION['flood_check_hits'] >= (nZEDb_FLOOD_MAX_REQUESTS_PER_SECOND < 1 ? 5 : nZEDb_FLOOD_MAX_REQUESTS_PER_SECOND)) {
						if ($_SESSION['flood_check_time'] + 1 > microtime(true)) {
							$_SESSION['flood_wait_until'] = microtime(true) + $waitTime;
							unset($_SESSION['flood_check_hits']);
							$this->showFloodWarning($waitTime);
						} else {
							$_SESSION['flood_check_hits'] = 1;
							$_SESSION['flood_check_time'] = microtime(true);
						}
					} else {
						$_SESSION['flood_check_hits']++;
					}
				}
			}
		}
	}

	/**
	 * Done in html here to reduce any smarty processing burden if a large flood is underway.
	 */
	public function showFloodWarning($seconds = 5)
	{
		header('Retry-After: ' . $seconds);
		$this->show503(
			sprintf(
				'Too many requests!</p><p>You must wait <b>%s seconds</b> before trying again.',
				$seconds
			)
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
	 * Show 404 page.
	 */
	public function show404()
	{
		header('HTTP/1.1 404 Not Found');
		exit(
		sprintf("
				<html>
					<head>
						<title>404 - File not found.</title>
					</head>
					<body>
						<h1>404 - File not found.</h1>
						<p>%s%s</p>
						<p>We could not find the above page on our servers.</p>
					</body>
				</html>",
			$this->serverurl,
			$this->page
		)
		);
	}

	/**
	 * Show 503 page.
	 *
	 * @param string $message Message to display.
	 */
	public function show503($message = 'Your maximum api or download limit has been reached for the day.')
	{
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		exit(
			sprintf("
				<html>
					<head>
						<title>Service Unavailable.</title>
					</head>
					<body>
						<h1>Service Unavailable.</h1>
						<p>%s</p>
					</body>
				</html>",
				$message
			)
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
