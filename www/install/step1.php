<?php
require_once realpath(__DIR__ . '/../automated.config.php');

$page = new InstallPage();
$page->title = "Preflight Checklist";

$cfg = new Install();

if (!$cfg->isInitialized()) {
	header("Location: index.php");
	die();
}

$cfg->setSession();
$cfg = $cfg->getSession();

// Start checks.
$cfg->cryptCheck = function_exists('crypt');
if ($cfg->cryptCheck === false) {
	$cfg->error = true;
}

$cfg->sha1Check = function_exists('sha1');
if ($cfg->sha1Check === false) {
	$cfg->error = true;
}

$cfg->PDOCheck = extension_loaded('PDO');
if ($cfg->PDOCheck === false) {
	$cfg->error = true;
}

$cfg->jsonCheck = extension_loaded('json');
if ($cfg->jsonCheck === false) {
	$cfg->error = true;
}

$cfg->gdCheck = function_exists('imagecreatetruecolor');

$cfg->curlCheck = function_exists('curl_init');

if (extension_loaded('posix') && strtolower(substr(PHP_OS, 0, 3)) !== 'win') {
	$group = posix_getgrgid(posix_getgid());
	$fixString = '<br /><br />Another solution is to run:<br />chown -R YourUnixUserName:' . $group['name']  . ' ' . nZEDb_ROOT .
		'<br />Then give your user access to the group:<br />usermod -a -G ' . $group['name'] . ' YourUnixUserName' .
		'<br />Finally give read/write access to your user/group:<br />chmod -R 774 ' . nZEDb_ROOT;
	$page->smarty->assign('fixString', $fixString);
	$page->smarty->assign('unixGroup', $group['name']);
	$page->smarty->assign('rootPath', nZEDb_ROOT);
} else {
	$page->smarty->assign('fixString', false);
}

$cfg->cacheCheck = is_writable(SMARTY_DIR . 'templates_c');
if ($cfg->cacheCheck === false) {
	$cfg->error = true;
}

$cfg->animeCoversCheck = is_writable($cfg->COVERS_PATH . 'anime');
if ($cfg->animeCoversCheck === false) {
	$cfg->error = true;
}

$cfg->audioCoversCheck = is_writable($cfg->COVERS_PATH . 'audio');
if ($cfg->audioCoversCheck === false) {
	$cfg->error = true;
}

$cfg->audiosampleCoversCheck = is_writable($cfg->COVERS_PATH . 'audiosample');
if ($cfg->audiosampleCoversCheck === false) {
	$cfg->error = true;
}

$cfg->bookCoversCheck = is_writable($cfg->COVERS_PATH . 'book');
if ($cfg->bookCoversCheck === false) {
	$cfg->error = true;
}

$cfg->consoleCoversCheck = is_writable($cfg->COVERS_PATH . 'console');
if ($cfg->consoleCoversCheck === false) {
	$cfg->error = true;
}

$cfg->movieCoversCheck = is_writable($cfg->COVERS_PATH . 'movies');
if ($cfg->movieCoversCheck === false) {
	$cfg->error = true;
}

$cfg->musicCoversCheck = is_writable($cfg->COVERS_PATH . 'music');
if ($cfg->musicCoversCheck === false) {
	$cfg->error = true;
}

$cfg->previewCoversCheck = is_writable($cfg->COVERS_PATH . 'preview');
if ($cfg->previewCoversCheck === false) {
	$cfg->error = true;
}

$cfg->sampleCoversCheck = is_writable($cfg->COVERS_PATH . 'sample');
if ($cfg->sampleCoversCheck === false) {
	$cfg->error = true;
}

$cfg->videoCoversCheck = is_writable($cfg->COVERS_PATH . 'video');
if ($cfg->videoCoversCheck === false) {
	$cfg->error = true;
}

$cfg->configCheck = is_writable(nZEDb_WWW);
if ($cfg->configCheck === false) {
	$cfg->configCheck = is_file(nZEDb_WWW);
	if ($cfg->configCheck === true) {
		$cfg->configCheck = false;
		$cfg->error = true;
	} else {
		$cfg->configCheck = is_writable(nZEDb_WWW);
		if ($cfg->configCheck === false) {
			$cfg->error = true;
		}
	}
}

$cfg->lockCheck = is_writable($cfg->INSTALL_DIR . 'install.lock');
if ($cfg->lockCheck === false) {
	$cfg->lockCheck = is_file($cfg->INSTALL_DIR . 'install.lock');
	if ($cfg->lockCheck === true) {
		$cfg->lockCheck = false;
		$cfg->error = true;
	} else {
		$cfg->lockCheck = is_writable($cfg->INSTALL_DIR);
		if ($cfg->lockCheck === false) {
			$cfg->error = true;
		}
	}
}

$cfg->pearCheck = @include 'System.php';
$cfg->pearCheck = class_exists('System');
if (!$cfg->pearCheck) {
	$cfg->error = true;
}

$cfg->schemaCheck = false;
if (is_readable($cfg->DB_DIR . 'mysql-ddl.sql')) {
	$cfg->schemaCheck = true;
}
if ($cfg->schemaCheck === false) {
	$cfg->error = true;
}

// Don't set error = true for these as we only want to display a warning.
$cfg->phpCheck = (version_compare(PHP_VERSION, nZEDb_MINIMUM_PHP_VERSION, '>=')) ? true : false;
$cfg->timelimitCheck = (ini_get('max_execution_time') >= 120) ? true : false;
$cfg->memlimitCheck = (ini_get('memory_limit') >= 1024 || ini_get('memory_limit') == -1) ? true : false;
$cfg->opensslCheck = extension_loaded("openssl");
$cfg->exifCheck = extension_loaded("exif");
$cfg->timezoneCheck = (ini_get('date.timezone') != "");

if (preg_match('/apache/i', $_SERVER['SERVER_SOFTWARE'])) {
	$cfg->rewriteCheck = (function_exists("apache_get_modules") && in_array("mod_rewrite", apache_get_modules())) ? true : false;
} else {
	$cfg->rewriteCheck = true;
}

// Load previous config.php.
if (file_exists(nZEDb_WWW . 'config.php') && is_readable(nZEDb_WWW . 'config.php')) {
	$tmpCfg = file_get_contents(nZEDb_WWW . 'config.php');
	$cfg->setConfig($tmpCfg);
}

if (!$cfg->error) {
	$cfg->setSession();
}

$page->smarty->assign('cfg', $cfg);

$page->smarty->assign('page', $page);

$page->content = $page->smarty->fetch('step1.tpl');
$page->render();