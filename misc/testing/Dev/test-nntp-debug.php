<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////// Put your test code at the bottom of this file. ////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
require_once dirname(__FILE__) . '/../../../www/config.php';
echo 'This script is going to run without debug, you can turn on debug by passing true as an argument.' . PHP_EOL;

class NNTPTest extends NNTP
{
	/**
	 * @param NNTPdebug $logger
	 */
	public function __construct($logger)
	{
		parent::__construct();
		$this->setLogger($logger);
	}
	public function __destruct()
	{
		parent::__destruct();
	}
}

/**
 * Class NNTPDebug
 */
class NNTPDebug
{
	/**
	 * @var ColorCLI
	 */
	public $color;

	/**
	 * Construct.
	 */
	public function __construct($debug = false)
	{
		define('PEAR_LOG_DEBUG', $debug);
		$this->color = new \ColorCLI();
	}

	/**
	 * @param bool|null $value
	 *
	 * @return bool
	 */
	public function _isMasked($value)
	{
		if (is_bool($value)) {
			return $value;
		} else {
			return false;
		}
	}

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function notice($message)
	{
		if (PEAR_LOG_DEBUG) {
			echo $this->color->notice($message);
		}
	}

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function debug($message)
	{
		if (PEAR_LOG_DEBUG) {
			echo $this->color->debug($message);
		}
	}

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function warning($message)
	{
		if (PEAR_LOG_DEBUG) {
			echo $this->color->warning($message);
		}
	}

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function info($message)
	{
		if (PEAR_LOG_DEBUG) {
			echo $this->color->info($message);
		}
	}
}

$nntp = new \NNTPTest(new \NNTPDebug((isset($argv[1]) ? true : false)));
if ($nntp->doConnect() !== true) {exit('Error connecting to usenet!' . PHP_EOL);}
$n = PHP_EOL;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////// Put your test code under here. ////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$db = new \nzedb\db\DB();
$groups = $db->query('SELECT name FROM groups WHERE name NOT like \'alt.binaries.%\' AND active = 1');

$groupList = array();
foreach ($groups as $group) {
	$groupList += $nntp->getGroups($group['name']);
}
$groupList += $nntp->getGroups('alt.binaries.*');

$groups = $db->queryDirect('SELECT name FROM groups WHERE active = 1');

$activeGroups = array();

if ($groups instanceof \Traversable) {
	foreach($groups as $group) {
		if (isset($groupList[$group['name']])) {
			$activeGroups[$group['name']] = $groupList[$group['name']];
		}
	}
}

var_dump($activeGroups);
