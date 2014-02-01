<?php
require_once nZEDb_LIB . 'Util.php';

class SABnzbd
{
	public $url = '';
	public $apikey = '';
	public $priority = '';
	public $apikeytype = '';
	public $integrated = false;
	public $uid = '';
	public $rsstoken = '';
	public $serverurl = '';

	const INTEGRATION_TYPE_USER = 2;
	const INTEGRATION_TYPE_SITEWIDE = 1;
	const INTEGRATION_TYPE_NONE = 0;
	const API_TYPE_NZB = 1;
	const API_TYPE_FULL = 2;
	const PRIORITY_FORCE = 2;
	const PRIORITY_HIGH = 1;
	const PRIORITY_NORMAL = 0;
	const PRIORITY_LOW = -1;
	const PRIORITY_PAUSED = -2;

	function __construct(&$page)
	{
		$this->uid = $page->userdata['id'];
		$this->rsstoken = $page->userdata['rsstoken'];
		$this->serverurl = $page->serverurl;

		switch ($page->site->sabintegrationtype) {
			case self::INTEGRATION_TYPE_USER:
				if (!empty($_COOKIE['sabnzbd_' . $this->uid . '__apikey']) && !empty($_COOKIE['sabnzbd_' . $this->uid . '__host'])) {
					$this->url = $_COOKIE['sabnzbd_' . $this->uid . '__host'];
					$this->apikey = $_COOKIE['sabnzbd_' . $this->uid . '__apikey'];
					$this->priority = (isset($_COOKIE['sabnzbd_' . $this->uid . '__priority'])) ? $_COOKIE['sabnzbd_' . $this->uid . '__priority'] : 0;
					$this->apikeytype = (isset($_COOKIE['sabnzbd_' . $this->uid . '__apitype'])) ? $_COOKIE['sabnzbd_' . $this->uid . '__apitype'] : 1;
				} else if (!empty($page->userdata['sabapikey']) && !empty($page->userdata['saburl'])) {
					$this->url = $page->userdata['saburl'];
					$this->apikey = $page->userdata['sabapikey'];
					$this->priority = $page->userdata['sabpriority'];
					$this->apikeytype = $page->userdata['sabapikeytype'];
				}
				$this->integrated = self::INTEGRATION_TYPE_USER;
				break;
			case self::INTEGRATION_TYPE_SITEWIDE:
				if (!empty($page->site->sabapikey) && !empty($page->site->saburl)) {
					$this->url = $page->site->saburl;
					$this->apikey = $page->site->sabapikey;
					$this->priority = $page->site->sabpriority;
					$this->apikeytype = $page->site->sabapikeytype;
				}
				$this->integrated = self::INTEGRATION_TYPE_SITEWIDE;
				break;
		}
	}

	public function sendToSab($guid)
	{
		$addToSabUrl = $this->url . 'api?mode=addurl&priority=' . $this->priority . '&apikey=' . $this->apikey;
		$nzbUrl = $this->serverurl . 'getnzb/' . $guid . '&i=' . $this->uid . '&r=' . $this->rsstoken;
		$addToSabUrl = $addToSabUrl . '&name=' . urlencode($nzbUrl);
		return getUrl($addToSabUrl);
	}

	public function getQueue()
	{
		$queueUrl = $this->url . "api?mode=qstatus&output=json&apikey=" . $this->apikey;
		//$queueUrl = $this->url."api?mode=queue&start=START&limit=LIMIT&output=json&apikey=".$this->apikey;
		return getUrl($queueUrl);
	}

	public function getAdvQueue()
	{
		$queueUrl = $this->url . "api?mode=queue&start=START&limit=LIMIT&output=json&apikey=" . $this->apikey;
		//$queueUrl = $this->url."api?mode=queue&start=START&limit=LIMIT&output=json&apikey=".$this->apikey;
		return getUrl($queueUrl);
	}

	public function delFromQueue($id)
	{
		$delUrl = $this->url . "api?mode=queue&name=delete&value=" . $id . "&apikey=" . $this->apikey;
		return getUrl($delUrl);
	}

	public function pauseFromQueue($id)
	{
		$pauseUrl = $this->url . "api?mode=queue&name=pause&value=" . $id . "&apikey=" . $this->apikey;
		return getUrl($pauseUrl);
	}

	public function resumeFromQueue($id)
	{
		$resumeUrl = $this->url . "api?mode=queue&name=resume&value=" . $id . "&apikey=" . $this->apikey;
		return getUrl($resumeUrl);
	}

	public function pauseAll()
	{
		$pauseallUrl = $this->url . "api?mode=pause" . "&apikey=" . $this->apikey;
		return getUrl($pauseallUrl);
	}

	public function resumeAll()
	{
		$resumeallUrl = $this->url . "api?mode=resume" . "&apikey=" . $this->apikey;
		return getUrl($resumeallUrl);
	}

	public function checkCookie()
	{
		$res = false;
		if (isset($_COOKIE['sabnzbd_' . $this->uid . '__apikey'])) {
			$res = true;
		}
		if (isset($_COOKIE['sabnzbd_' . $this->uid . '__host'])) {
			$res = true;
		}
		if (isset($_COOKIE['sabnzbd_' . $this->uid . '__priority'])) {
			$res = true;
		}
		if (isset($_COOKIE['sabnzbd_' . $this->uid . '__apitype'])) {
			$res = true;
		}

		return $res;
	}

	public function setCookie($host, $apikey, $priority, $apitype)
	{
		setcookie('sabnzbd_' . $this->uid . '__host', $host, (time() + 2592000));
		setcookie('sabnzbd_' . $this->uid . '__apikey', $apikey, (time() + 2592000));
		setcookie('sabnzbd_' . $this->uid . '__priority', $priority, (time() + 2592000));
		setcookie('sabnzbd_' . $this->uid . '__apitype', $apitype, (time() + 2592000));
	}

	public function unsetCookie()
	{
		setcookie('sabnzbd_' . $this->uid . '__host', '', (time() - 2592000));
		setcookie('sabnzbd_' . $this->uid . '__apikey', '', (time() - 2592000));
		setcookie('sabnzbd_' . $this->uid . '__priority', '', (time() - 2592000));
		setcookie('sabnzbd_' . $this->uid . '__apitype', '', (time() - 2592000));
	}
}
?>
