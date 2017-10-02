<?php

require_once nZEDb_LIB . 'utility' . DS . 'SmartyUtils.php';

use app\models\Settings;
use nzedb\SABnzbd;
use nzedb\Users;
use nzedb\db\DB;

class BasePage
{
	/**
	 * @var \nzedb\db\DB
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
	 *
	 * @var string
	 */
	public $page = '';

	public $page_template = '';

	/**
	 * User settings from the MySQL DB.
	 *
	 * @var array|bool
	 */
	public $userdata = [];

	/**
	 * URL of the server. ie http://localhost/
	 *
	 * @var string
	 */
	public $serverurl = '';

	/**
	 * Whether to trim white space before rendering the page or not.
	 *
	 * @var bool
	 */
	public $trimWhiteSpace = true;

	/**
	 * Is the current session HTTPS?
	 *
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
	 * User's theme
	 *
	 * @var string
	 */
	protected $theme = 'Default';

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

		// Buffer settings/DB connection.
		$this->settings = new DB();

		$this->smarty = new Smarty();

		$this->smarty->setCacheDir(nZEDb_SMARTY_CACHE);
		$this->smarty->setCompileDir(nZEDb_SMARTY_TEMPLATES);
		$this->smarty->setConfigDir(nZEDb_SMARTY_CONFIGS);
		$this->smarty->setPluginsDir([
				SMARTY_DIR . 'plugins/',
				nZEDb_WWW . 'plugins/',
		]);
		$this->smarty->error_reporting = ((nZEDb_DEBUG ? E_ALL : E_ALL - E_NOTICE));

		if (isset($_SERVER['SERVER_NAME'])) {
			$this->serverurl = (
				($this->https === true ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] .
				(($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') ?
					':' . $_SERVER['SERVER_PORT'] : '') .
				WWW_TOP . '/'
			);
			$this->smarty->assign('serverroot', $this->serverurl);
		}

		$this->page = (isset($_GET['page']) ? $_GET['page'] : 'content');

		$this->users = new Users(['Settings' => $this->settings]);
		if ($this->users->isLoggedIn()) {
			$this->setUserPreferences();
		} else {
			$this->theme = Settings::value('site.main.style');

			$this->smarty->assign('isadmin', 'false');
			$this->smarty->assign('ismod', 'false');
			$this->smarty->assign('loggedin', 'false');
		}

		$this->smarty->assign('theme', $this->theme);
		$this->smarty->assign('site', $this->settings);
		$this->smarty->assign('page', $this);
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
			if (isset($_SESSION['flood_wait_until']) &&
				$_SESSION['flood_wait_until'] > microtime(true)
			) {
				$this->showFloodWarning($waitTime);
			} else {
				// If user not an admin, they are allowed three requests in FLOOD_THREE_REQUESTS_WITHIN_X_SECONDS seconds.
				if (!isset($_SESSION['flood_check_hits'])) {
					$_SESSION['flood_check_hits'] = 1;
					$_SESSION['flood_check_time'] = microtime(true);
				} else {
					if ($_SESSION['flood_check_hits'] >=
						(nZEDb_FLOOD_MAX_REQUESTS_PER_SECOND < 1 ? 5 : nZEDb_FLOOD_MAX_REQUESTS_PER_SECOND)
					) {
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
	 * Allows to fetch a value from the settings table.
	 *
	 * This method is deprecated, as the column it uses to select the data is due to be removed
	 * from the table *soon*.
	 *
	 * @param $setting
	 *
	 * @return array|bool|mixed|null|string
	 */
	public function getSetting($setting)
	{
		if (strpos($setting, '.') === false) {
			trigger_error(
				'You should update your template to use the newer method "$page->getSettingValue()"" of fetching values from the "settings" table! This method *will* be removed in a future version.',
				E_USER_WARNING);
		} else {
			return $this->getSettingValue($setting);
		}

		return $this->settings->$setting;

	}

	public function getSettingValue($setting)
	{
		return Settings::value($setting);
	}

	/**
	 * Done in html here to reduce any smarty processing burden if a large flood is underway.
	 *
	 * @param int $seconds The number of seconds after which to retry operation
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
	 *
	 * @param string $reason The reason we 404'd
	 */
	public function show404($reason = '')
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
						<p>%s</p>
						<p>We could not find the above page on our servers.</p>
					</body>
				</html>",
				$this->serverurl,
				htmlspecialchars($this->page),
				(!empty($reason) ? 'Reason: ' . $reason : '')
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

	protected function setUserPreferences()
	{
		$this->userdata = $this->users->getById($this->users->currentUserId());
		$this->userdata['categoryexclusions'] = $this->users->getCategoryExclusion($this->users->currentUserId());
		$this->userdata['rolecategoryexclusions'] = $this->users->getRoleCategoryExclusion($this->userdata['role']);

		// Change to the user's selected theme, if they selected one, else use the admin set one.
		$this->theme = isset($this->userdata['style']) ? $this->userdata['style'] : 'None';

		if ($this->theme == 'None') {
			$this->theme = Settings::value('site.main.style');
		}

		if (lcfirst($this->theme) === $this->theme) {
			// TODO add redirect to error page telling the user their theme name is invalid (after SQL patch to update current users is added).
			$this->theme = ucfirst($this->theme);
		}

		// Update last login every 15 mins.
		if ((strtotime($this->userdata['now']) - 900) >
			strtotime($this->userdata['lastlogin'])
		) {
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
	}
}
