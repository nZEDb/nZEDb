<?php
require_once dirname(__FILE__) . '/config.php';

$c = new ColorCLI();

/**
  Array with possible arguments for run and
  whether or not those methods of operation require NNTP
**/

$args = array(
	'all'        => true,
	'pre'        => true,
	'nfo'        => true,
	'movies'     => false,
	'music'      => false,
	'console'    => false,
	'games'      => false,
	'book'       => false,
	'anime'      => false,
	'tv'         => false,
	'xxx'        => false,
	'additional' => true,
	'sharing'    => true,
	'allinf'     => true
);

$bool = array(
	'true',
	'false'
);

if (!isset($argv[1]) || !in_array($argv[1], $args) || !isset($argv[2]) || !in_array($argv[2], $bool)) {
	exit(
		$c->error(
			"\nIncorrect arguments.\n"
				. "The second argument (true/false) determines wether to echo or not.\n\n"
				. "php postprocess.php all true         ...: Does all the types of post processing.\n"
				. "php postprocess.php pre true         ...: Processes all Predb sites.\n"
				. "php postprocess.php nfo true         ...: Processes NFO files.\n"
				. "php postprocess.php movies true      ...: Processes movies.\n"
				. "php postprocess.php music true       ...: Processes music.\n"
				. "php postprocess.php console true     ...: Processes console games.\n"
				. "php postprocess.php games true       ...: Processes games.\n"
				. "php postprocess.php book true        ...: Processes books.\n"
				. "php postprocess.php anime true       ...: Processes anime.\n"
				. "php postprocess.php tv true          ...: Processes tv.\n"
				. "php postprocess.php xxx true         ...: Processes xxx.\n"
				. "php postprocess.php additional true  ...: Processes previews/mediainfo/etc...\n"
				. "php postprocess.php sharing true     ...: Processes uploading/downloading comments.\n"
				. "php postprocess.php allinf true      ...: Does all the types of post processing on a loop, sleeping 15 seconds between.\n"
		)
	);
}

$pdo = new \nzedb\db\Settings();

$mode = $argv[1];
$conn = $args[$mode];
$show = $argv[2];

$proxy = $pdo->getSetting('nntpproxy');
// Remove folders from tmpunrar.
$tmpunrar = $pdo->getSetting('tmpunrarpath');
rmtree($tmpunrar);

if ($conn === true) {
	// Don't use alternate here, if a article fails in post proc it will use alternate on its own.
	$nntp = new NNTP();
	if (($pdo->getSetting('alternate_nntp') == '1' ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
		exit($c->error("Unable to connect to usenet."));
	}
	if ($proxy == "1") {
		usleep(500000);
	}
}

if ($show === 'true') {
	$postprocess = new PostProcess(true);
} else {
	$postprocess = new PostProcess();
}

switch ($mode) {

	case 'all':
		$postprocess->processAll($nntp);
		break;
	case 'allinf':
		$i = 1;
		while ($i = 1) {
			$postprocess->processAll($nntp);
			sleep(15);
		}
		break;
	case 'additional':
		$postprocess->processAdditional($nntp, (isset($argv[3]) && is_numeric($argv[3]) ? $argv[3] : ''));
		break;
	case 'anime':
		exit;
		//$postprocess->processAnime();
		//break;
	case 'book':
		$postprocess->processBooks();
		break;
	case 'consoles':
		$postprocess->processConsoles();
		break;
	case 'games':
		$postprocess->processGames();
		break;
	case 'nfo':
		$postprocess->processNfos($nntp, (isset($argv[3]) && is_numeric($argv[3]) ? $argv[3] : ''));
		break;
	case 'movies':
		$postprocess->processMovies();
		break;
	case 'music':
		$postprocess->processMusic();
		break;
	case 'pre':
		$postprocess->processPredb($nntp);
		break;
	case 'sharing':
		$postprocess->processSharing($nntp);
		break;
	case 'tv':
		$postprocess->processTV();
		break;
	case 'xxx':
		$postprocess->processXXX();
		break;
	default:
		exit;
}

if ($proxy != "1" && $conn === true) {
	$nntp->doQuit();
}

/**
 * Delete a file or directory recursively.
 *
 * @param string $path
 * found here, modded to only delete subfolders
 * https://gist.github.com/SteelPangolin/1407308
 */
function rmtree($path)
{
	if (is_dir($path)) {
		foreach (scandir($path) as $name) {
			if (in_array($name, array('.', '..'))) {
				continue;
			}

			$subpath = $path . DIRECTORY_SEPARATOR . $name;
			rmtree($subpath);
		}
	}
}
