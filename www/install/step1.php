<?php
require_once('../lib/installpage.php');
require_once('../lib/install.php');

$page = new Installpage();
$page->title = "Preflight Checklist";

$cfg = new Install();

if (!$cfg->isInitialized())
{
	header("Location: index.php");
	die();
}

$cfg = $cfg->getSession();

// Start checks.
$cfg->sha1Check = function_exists('sha1');
if ($cfg->sha1Check === false)
	$cfg->error = true;

$cfg->PDOCheck = extension_loaded('PDO');
if ($cfg->PDOCheck === false)
	$cfg->error = true;

$cfg->gdCheck = function_exists('imagecreatetruecolor');

$cfg->curlCheck = function_exists('curl_init');

$cfg->cacheCheck = is_writable($cfg->SMARTY_DIR.'/templates_c');
if ($cfg->cacheCheck === false)
	$cfg->error = true;

$cfg->animeCoversCheck = is_writable($cfg->WWW_DIR.'/covers/anime');
if ($cfg->animeCoversCheck === false)
	$cfg->error = true;

$cfg->audioCoversCheck = is_writable($cfg->WWW_DIR.'/covers/audio');
if ($cfg->audioCoversCheck === false)
	$cfg->error = true;

$cfg->audiosampleCoversCheck = is_writable($cfg->WWW_DIR.'/covers/audiosample');
if ($cfg->audiosampleCoversCheck === false)
	$cfg->error = true;

$cfg->bookCoversCheck = is_writable($cfg->WWW_DIR.'/covers/book');
if ($cfg->bookCoversCheck === false)
	$cfg->error = true;

$cfg->consoleCoversCheck = is_writable($cfg->WWW_DIR.'/covers/console');
if ($cfg->consoleCoversCheck === false)
	$cfg->error = true;

$cfg->movieCoversCheck = is_writable($cfg->WWW_DIR.'/covers/movies');
if ($cfg->movieCoversCheck === false)
	$cfg->error = true;

$cfg->musicCoversCheck = is_writable($cfg->WWW_DIR.'/covers/music');
if ($cfg->musicCoversCheck === false)
	$cfg->error = true;

$cfg->previewCoversCheck = is_writable($cfg->WWW_DIR.'/covers/preview');
if ($cfg->previewCoversCheck === false)
	$cfg->error = true;

$cfg->sampleCoversCheck = is_writable($cfg->WWW_DIR.'/covers/sample');
if ($cfg->sampleCoversCheck === false)
	$cfg->error = true;

$cfg->videoCoversCheck = is_writable($cfg->WWW_DIR.'/covers/video');
if ($cfg->videoCoversCheck === false)
	$cfg->error = true;

$cfg->configCheck = is_writable($cfg->WWW_DIR.'/config.php');
if($cfg->configCheck === false)
{
	$cfg->configCheck = is_file($cfg->WWW_DIR.'/config.php');
	if($cfg->configCheck === true)
	{
		$cfg->configCheck = false;
		$cfg->error = true;
	}
	else
	{
		$cfg->configCheck = is_writable($cfg->WWW_DIR);
		if($cfg->configCheck === false)
			$cfg->error = true;
	}
}

$cfg->lockCheck = is_writable($cfg->INSTALL_DIR.'/install.lock');
if ($cfg->lockCheck === false)
{
	$cfg->lockCheck = is_file($cfg->INSTALL_DIR.'/install.lock');
	if($cfg->lockCheck === true)
	{
		$cfg->lockCheck = false;
		$cfg->error = true;
	}
	else
	{
		$cfg->lockCheck = is_writable($cfg->INSTALL_DIR);
		if($cfg->lockCheck === false)
			$cfg->error = true;
	}
}

$cfg->pearCheck = @include('System.php');
$cfg->pearCheck = class_exists('System');
if (!$cfg->pearCheck)
	$cfg->error = true;

$cfg->schemaCheck = false;
if (is_readable($cfg->DB_DIR.'/schema.mysql') && is_readable($cfg->DB_DIR.'/schema.pgsql'))
	$cfg->schemaCheck = true;
if ($cfg->schemaCheck === false) { $cfg->error = true; }

// Dont set error = true for these as we only want to display a warning.
$cfg->phpCheck = (version_compare(PHP_VERSION, '5.4.0', '>=')) ? true : false;
$cfg->timelimitCheck = (ini_get('max_execution_time') >= 120) ? true : false;
$cfg->memlimitCheck = (ini_get('memory_limit') >= 1024 || ini_get('memory_limit') == -1) ? true : false;
$cfg->opensslCheck = extension_loaded("openssl");
$cfg->exifCheck = extension_loaded("exif");
$cfg->timezoneCheck = (ini_get('date.timezone') != "");

$cfg->rewriteCheck = (function_exists("apache_get_modules") && in_array("mod_rewrite", apache_get_modules())) ? true : false;

// Load previous config.php.
if (file_exists($cfg->WWW_DIR.'/config.php') && is_readable($cfg->WWW_DIR.'/config.php'))
{
	$tmpCfg = file_get_contents($cfg->WWW_DIR.'/config.php');
	$cfg->setConfig($tmpCfg);
}

if (!$cfg->error)
	$cfg->setSession();

$page->smarty->assign('cfg', $cfg);

$page->smarty->assign('page', $page);

$page->content = $page->smarty->fetch('step1.tpl');
$page->render();

?>
