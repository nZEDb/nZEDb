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
	 */
	public $userName = '';

	/**
	 * NZBGet password.
	 * @var string
	 */
	public $password = '';

	/**
	 * NZBGet URL.
	 * @var string
	 */
	public $url = '';

	/**
	 * Full URL (containing password/username/etc).
	 * @var string
	 */
	protected $fullURL = '';

	/**
	 * User ID.
	 * @var int
	 */
	protected $uid = 0;

	/**
	 * The users RSS token.
	 * @var string
	 */
	protected $rsstoken = '';

	/**
	 * URL to your nZEDb site.
	 * @var string
	 */
	protected $serverurl = '';

	/**
	 * @var Releases
	 */
	protected $Releases;

	/**
	 * @var NZB
	 */
	protected $NZB;

	/**
	 * Construct.
	 * Set up full URL.
	 *
	 * @var BasePage $page
	 */
	public function __construct(&$page)
	{
		$this->serverurl = $page->serverurl;
		$this->uid = $page->userdata['id'];
		$this->rsstoken = $page->userdata['rsstoken'];

		if (!empty($page->userdata['nzbgeturl']) && !empty($page->userdata['nzbgetusername']) && !empty($page->userdata['nzbgetpassword'])) {
			$this->url  = $page->userdata['nzbgeturl'];
			$this->userName = $page->userdata['nzbgetusername'];
			$this->password = $page->userdata['nzbgetpassword'];
		}

		$this->fullURL = $this->verifyURL($this->url);
		$this->Releases = new Releases();
		$this->NZB = new NZB();
	}

	/**
	 * Send a NZB to NZBGet.
	 *
	 * @param string $guid Release identifier.
	 *
	 * @return bool|mixed
	 */
	public function sendNZBToNZBGet($guid)
	{
		$reldata = $this->Releases->getByGuid($guid);
		$nzbpath = $this->NZB->getNZBPath($guid);

		$string = '';
		$nzb = @gzopen($nzbpath, 'rb', 0);
		if ($nzb) {
			while (!gzeof($nzb)) {
				$string .= gzread($nzb, 1024);
			}
			gzclose($nzb);
		}

		$header =
			'<?xml version="1.0"?>
			<methodCall>
				<methodName>append</methodName>
				<params>
					<param>
						<value><string>' . $reldata['searchname'] . '</string></value>
					</param>
					<param>
						<value><string>' . $reldata['category_name'] . '</string></value>
					</param>
					<param>
						<value><i4>0</i4></value>
					</param>
					<param>
						<boolean>>False</boolean></value>
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
		nzedb\utility\getUrl($this->fullURL . 'append', 'post', $header);
	}

	/**
	 * Send a NZB URL to NZBGet.
	 *
	 * @param string $guid Release identifier.
	 *
	 * @return bool|mixed
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
						<boolean>>False</boolean></value>
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
		nzedb\utility\getUrl($this->fullURL . 'appendurl', 'post', $header);
	}

	/**
	 * Pause download queue on server. This method is equivalent for command "nzbget -P".
	 *
	 * @return void
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
		nzedb\utility\getUrl($this->fullURL . 'pausedownload2', 'post', $header);
	}

	/**
	 * Resume (previously paused) download queue on server. This method is equivalent for command "nzbget -U".
	 *
	 * @return void
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
		nzedb\utility\getUrl($this->fullURL . 'resumedownload2', 'post', $header);
	}

	/**
	 * Pause a single NZB from the queue.
	 *
	 * @param string $id
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
		nzedb\utility\getUrl($this->fullURL . 'editqueue', 'post', $header);
	}

	/**
	 * Resume a single NZB from the queue.
	 *
	 * @param string $id
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
		nzedb\utility\getUrl($this->fullURL . 'editqueue', 'post', $header);
	}

	/**
	 * Delete a single NZB from the queue.
	 *
	 * @param string $id
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
		nzedb\utility\getUrl($this->fullURL . 'editqueue', 'post', $header);
	}

	/**
	 * Set download speed limit. This method is equivalent for command "nzbget -R <Limit>".
	 *
	 * @param int $limit The speed to limit it to.
	 *
	 * @return bool
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
		nzedb\utility\getUrl($this->fullURL . 'rate', 'post', $header);
	}

	/**
	 * Get all items in download queue.
	 *
	 * @return array|bool
	 */
	public function getQueue()
	{
		$data = nzedb\utility\getUrl($this->fullURL . 'listgroups');
		$retVal = false;
		if ($data) {
			$xml = simplexml_load_string($data);
			if ($xml) {
				$retVal = array();
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
	 */
	public function status()
	{
		$data = nzedb\utility\getUrl($this->fullURL . 'status');
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
				(isset($matches['port']) ? ':' . $matches['port'] : '') .
				'xmlrpc/';
		} else {
			return false;
		}
	}
}