<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
echo 'This script is going to run without debug, you can turn on debug by passing true as an argument.' . PHP_EOL;
$d = new NNTPdebug((isset($argv[1]) ? true : false));
$nntp = new NNTP();
$nntp->setLogger($d);
$connected = $nntp->doConnect();
if ($connected !== true) {exit();}
$n = PHP_EOL;
////////////////////////////////////////////////////////////////////////////////
//////////////////// Put your test code under here. ////////////////////////////
////////////////////////////////////////////////////////////////////////////////

echo 'This is the last post in the sharing group:' . $n.$n;
$x = $nntp->selectGroup('alt.binaries.zines');
$x = $nntp->get_Header($x['group'], $x['last']);
echo 'Subject: '.$x['Subject'].$n.'Poster: '.$x['From'].$n.'Time: '.$x['Date'].$n
.$n.'Now we will post an article to alt.test and see if it posted. The article will have this subject: I am testing posting articles to usenet, ignore'.$n;
$nntp->postArticle('alt.testing', 'I am testing posting articles to usenet, ignore', 'This is a test', '<testing@test.com>');
for($i=15;$i>=0;$i--) {echo "Sleeping $i so the article propagates.\r"; sleep(1);}
echo $n.$n;
$x = $nntp->selectGroup('alt.testing');
$x = $nntp->get_Header($x['group'], $x['last']);
echo 'Subject: '.$x['Subject'].$n.'Poster: '.$x['From'].$n.'Time: '.$x['Date'].$n;

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

/**
 * Class NNTPdebug
 */
class NNTPdebug
{
	/**
	 * Construct.
	 */
	public function __construct($debug = false)
	{

		define('PEAR_LOG_DEBUG', $debug);
		$this->color = new ColorCLI();
		/*if (defined('nZEDb_DEBUG')) {
			define('PEAR_LOG_DEBUG', nZEDb_DEBUG);
			$this->color = new ColorCLI();
		} else {
			define('PEAR_LOG_DEBUG', false);
		}*/
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
