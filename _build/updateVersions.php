<?php

require_once dirname(__FILE__) . '/../www/config.php';

$format = new ColorCLI();


$versions = @new SimpleXMLElement(nZEDb_VERSIONS, 0, true);
if ($versions === false) {
	die($format->error("Your versioning XML file ({nZEDb_VERSIONS}) is broken, try updating from git.\n"));
}

$s = new Sites();
$site = $s->get();
$changed = false;

if ($site->sqlpatch > $versions->nzedb->db) {
	echo $format->primary("Updating Db revision\n");
	$versions->nzedb->db = $site->sqlpatch;
	$changed = true;
}

exec('git log | grep "^commit" | wc -l', $output);
if ($output[0] > $versions->nzedb->commit) {
	echo $format->primary("Updating commit number\n");
	$versions->nzedb->commit = $output[0];
	$changed = true;
}

exec('git log --tags', $output);
$index = 0;
$count = count($output);
while (!preg_match('#v(\d+\.\d+\.\d+)#i', $output[$index], $match) && $count < $index ) {
	$index++;
}

if (!empty($match) && $match > $versions->nzedb->tag) {
	echo $format->primary("Updating tagged version\n");
	$versions->nzedb->tag = $match;
	$changed = true;
}


if ($changed == true) {
	//$versions->asXML(nZEDb_VERSIONS);
	echo "Saved updated XML file.\n";
} else {
	echo "Nothing updated, not saving file\n";
}


?>