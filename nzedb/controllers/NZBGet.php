<?php
use nzedb\utility;

/**
 * Class NZBGet
 *
 * Transfers data between an NZBGet server and an nZEDb website.
 *
 * @package nzedb
 */
class NZBGet
{
	/**
	 * NZBGet username.
	 * @var string
	 * @access public
	 */
	public $userName = '';

	/**
	 * NZBGet password.
	 * @var string
	 * @access public
	 */
	public $password = '';

	/**
	 * NZBGet URL.
	 * @var string
	 * @access public
	 */
	public $url = '';

	/**
	 * Full URL (containing password/username/etc).
	 * @var string|bool
	 * @access protected
	 */
	protected $fullURL = '';

	/**
	 * User ID.
	 * @var int
	 * @access protected
	 */
	protected $uid = 0;

	/**
	 * The users RSS token.
	 * @var string
	 * @access protected
	 */
	protected $rsstoken = '';

	/**
	 * URL to your nZEDb site.
	 * @var string
	 * @access protected
	 */
	protected $serverurl = '';

	/**
	 * @var Releases
	 * @access protected
	 */
	protected $Releases;

	/**
	 * @var NZB
	 * @access protected
	 */
	protected $NZB;

	/**
	 * Construct.
	 * Set up full URL.
	 *
	 * @var BasePage $page
	 *
	 * @access public
	 */
	public function __construct(&$page)
	{
		$this->serverurl = $page->serverurl;
		$this->uid = $page->userdata['id'];
		$this->rsstoken = $page->userdata['rsstoken'];

		if (!empty($page->userdata['nzbgeturl'])) {
			$this->url  = $page->userdata['nzbgeturl'];
			$this->userName = (empty($page->userdata['nzbgetusername']) ? '' : $page->userdata['nzbgetusername']);
			$this->password = (empty($page->userdata['nzbgetpassword']) ? '' : $page->userdata['nzbgetpassword']);
		}

		$this->fullURL = $this->verifyURL($this->url);
		$this->Releases = new \Releases(['Settings' => $page->settings]);
		$this->NZB = new \NZB($page->settings);
	}

	/**
	 * Send a NZB to NZBGet.
	 *
	 * @param string $guid Release identifier.
	 *
	 * @return bool|mixed
	 *
	 * @access public
	 */
	public function sendNZBToNZBGet($guid)
	{
		$relData = $this->Releases->getByGuid($guid);

		$string = nzedb\utility\Utility::unzipGzipFile($this->NZB->getNZBPath($guid));
		$string = ($string === false ? '' : $string);

		$header =
			'<?xml version="1.0"?>
			<methodCall>
				<methodName>append</methodName>
				<params>
					<param>
						<value><string>' . $relData['searchname'] . '</string></value>
					</param>
					<param>
						<value><string>' . $relData['category_name'] . '</string></value>
					</param>
					<param>
						<value><i4>0</i4></value>
					</param>
					<param>
						<value><boolean>>False</boolean></value>
					</param>
					<param>
						<value>
							<string>' .
								base64_encode($string) .
							'</string>
						</value>
					</param>
				</params>
			</methodCall>';
		nzedb\utility\Utility::getUrl(['url' => $this->fullURL . 'append', 'method' => 'post', 'postdata' => $header, 'verifycert' => false]);
	}

	/**
	 * Send a NZB URL to NZBGet.
	 *
	 * @param string $guid Release identifier.
	 *
	 * @return bool|mixed
	 *
	 * @access public
	 */
	public function sendURLToNZBGet($guid)
	{
		$reldata = $this->Releases->getByGuid($guid);

		$header =
			'<?xml version="1.0"?>
			<methodCall>
				<methodName>appendurl</methodName>
				<params>
					<param>
						<value><string>' . $reldata['searchname'] . '.nzb' . '</string></value>
					</param>
					<param>
						<value><string>' . $reldata['category_name'] . '</string></value>
					</param>
					<param>
						<value><i4>0</i4></value>
					</param>
					<param>
						<value><boolean>>False</boolean></value>
					</param>
					<param>
						<value>
							<string>' .
								$this->serverurl .
								'getnzb/' .
								$guid .
								'%26i%3D' .
								$this->uid .
								'%26r%3D' .
								$this->rsstoken
								.
							'</string>
						</value>
					</param>
				</params>
			</methodCall>';
		nzedb\utility\Utility::getUrl(['url' => $this->fullURL . 'appendurl', 'method' => 'post', 'postdata' => $header, 'verifycert' => false]);
	}

	/**
	 * Pause download queue on server. This method is equivalent for command "nzbget -P".
	 *
	 * @return void
	 *
	 * @access public
	 */
	public function pauseAll()
	{
		$header =
			'<?xml version="1.0"?>
			<methodCall>
				<methodName>pausedownload2</methodName>
				<params>
					<param>
						<value><boolean>1</boolean></value>
					</param>
				</params>
			</methodCall>';
		nzedb\utility\Utility::getUrl(['url' => $this->fullURL . 'pausedownload2', 'method' => 'post', 'postdata' => $header, 'verifycert' => false]);
	}

	/**
	 * Resume (previously paused) download queue on server. This method is equivalent for command "nzbget -U".
	 *
	 * @return void
	 *
	 * @access public
	 */
	public function resumeAll()
	{
		$header =
			'<?xml version="1.0"?>
			<methodCall>
				<methodName>resumedownload2</methodName>
				<params>
					<param>
						<value><boolean>1</boolean></value>
					</param>
				</params>
			</methodCall>';
		nzedb\utility\Utility::getUrl(['url' => $this->fullURL . 'resumedownload2', 'method' => 'post', 'postdata' => $header, 'verifycert' => false]);
	}

	/**
	 * Pause a single NZB from the queue.
	 *
	 * @param string $id
	 *
	 * @access public
	 */
	public function pauseFromQueue($id)
	{
		$header =
			'<?xml version="1.0"?>
			<methodCall>
				<methodName>editqueue</methodName>
				<params>
					<param>
						<value><string>GroupPause</string></value>
					</param>
					<param>
						<value><i4>0</i4></value>
					</param>
					<param>
						<value><string>""</string></value>
					</param>
					<param>
						<value>
							<array>
								<value><i4>' . $id . '</i4></value>
							</array>
						</value>
					</param>
				</params>
			</methodCall>';
		nzedb\utility\Utility::getUrl(['url' => $this->fullURL . 'editqueue', 'method' => 'post', 'postdata' => $header, 'verifycert' => false]);
	}

	/**
	 * Resume a single NZB from the queue.
	 *
	 * @param string $id
	 *
	 * @access public
	 */
	public function resumeFromQueue($id)
	{
		$header =
			'<?xml version="1.0"?>
			<methodCall>
				<methodName>editqueue</methodName>
				<params>
					<param>
						<value><string>GroupResume</string></value>
					</param>
					<param>
						<value><i4>0</i4></value>
					</param>
					<param>
						<value><string>""</string></value>
					</param>
					<param>
						<value>
							<array>
								<value><i4>' . $id . '</i4></value>
							</array>
						</value>
					</param>
				</params>
			</methodCall>';
		nzedb\utility\Utility::getUrl(['url' => $this->fullURL . 'editqueue', 'method' => 'post', 'postdata' => $header, 'verifycert' => false]);
	}

	/**
	 * Delete a single NZB from the queue.
	 *
	 * @param string $id
	 *
	 * @access public
	 */
	public function delFromQueue($id)
	{
		$header =
			'<?xml version="1.0"?>
			<methodCall>
				<methodName>editqueue</methodName>
				<params>
					<param>
						<value><string>GroupDelete</string></value>
					</param>
					<param>
						<value><i4>0</i4></value>
					</param>
					<param>
						<value><string>""</string></value>
					</param>
					<param>
						<value>
							<array>
								<value><i4>' . $id . '</i4></value>
							</array>
						</value>
					</param>
				</params>
			</methodCall>';
		nzedb\utility\Utility::getUrl(['url' => $this->fullURL . 'editqueue', 'method' => 'post', 'postdata' => $header, 'verifycert' => false]);
	}

	/**
	 * Set download speed limit. This method is equivalent for command "nzbget -R <Limit>".
	 *
	 * @param int $limit The speed to limit it to.
	 *
	 * @return bool
	 *
	 * @access public
	 */
	public function rate($limit)
	{
		$header =
			'<?xml version="1.0"?>
			<methodCall>
				<methodName>rate</methodName>
				<params>
					<param>
						<value><i4>' . $limit . '</i4></value>
					</param>
				</params>
			</methodCall>';
		nzedb\utility\Utility::getUrl(['url' => $this->fullURL . 'rate', 'method' => 'post', 'postdata' => $header, 'verifycert' => false]);
	}

	/**
	 * Get all items in download queue.
	 *
	 * @return array|bool
	 *
	 * @access public
	 */
	public function getQueue()
	{
		$data = nzedb\utility\Utility::getUrl(['url' => $this->fullURL . 'listgroups', 'verifycert' => false]);
		$retVal = false;
		if ($data) {
			$xml = simplexml_load_string($data);
			if ($xml) {
				$retVal = [];
				$i = 0;
				foreach($xml->params->param->value->array->data->value as $value) {
					foreach ($value->struct->member as $member) {
						$value = (array)$member->value;
						$value = array_shift($value);
						if (!is_object($value)) {
							$retVal[$i][(string)$member->name] = $value;
						}
					}
					$i++;
				}
			}
		}
		return $retVal;
	}

	/**
	 * Request for current status (summary) information. Parts of informations returned by this method can be printed by command "nzbget -L".
	 *
	 * @return array The status.
	 *
	 * @access public
	 */
	public function status()
	{
		$data = nzedb\utility\Utility::getUrl(['url' => $this->fullURL . 'status', 'verifycert' => false]);
		$retVal = false;
		if ($data) {
			$xml = simplexml_load_string($data);
			if ($xml) {
				foreach($xml->params->param->value->struct->member as $member) {
					$value = (array)$member->value;
					$value = array_shift($value);
					if (!is_object($value)) {
						$retVal[(string)$member->name] = $value;
					}

				}
			}
		}
		return $retVal;
	}

	/**
	 * Verify if the NZBGet URL is correct.
	 *
	 * @param string $url NZBGet URL to verify.
	 *
	 * @return bool|string
	 *
	 * @access public
	 */
	public function verifyURL ($url)
	{
		if (preg_match('/(?P<protocol>https?):\/\/(?P<url>.+?)(:(?P<port>\d+\/)|\/)$/i', $url, $matches)) {
			return
				$matches['protocol'] .
				'://' .
				$this->userName .
				':' .
				$this->password .
				'@' .
				$matches['url'] .
				(isset($matches['port']) ? ':' . $matches['port'] : (substr($matches['url'], -1) === '/' ? '' : '/')) .
				'xmlrpc/';
		} else {
			return false;
		}
	}
}
