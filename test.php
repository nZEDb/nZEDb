<?php
require_once dirname(__FILE__) . '/www/config.php';
$pdo = new \nzedb\db\Settings();
$c = new ColorCLI();
//$newxxx = new XXX(['Settings' => $pdo, 'Echo' => true, 'ColorCLI' => $c]);
//$test = new Games();
//$searchterm = "Secret Sessions 2";
//$searchterm = "fdsafdasfdsa";
//$searchterm = "You Stole My Innocence";
//$searchterm = "Buttsluts 2";
//$searchterm = "Hot Mexican Pussy 6";
//$searchterm = "Pussy Harvest";
$cookie = nZEDb_TMP . 'xxx.cookie';

$test = new Greenlight();
//$searchterm = "Space Tanks"; # Steam Green Light
$searchterm = "WildLife Park 3"; #Green Light
//$searchterm = "Grand Theft Auto San Andreas"; # Steam
//$searchterm = "Victory At Sea";
$test->searchterm = $searchterm;
$test->cookie = $cookie;
if ($test->search() !==false){
print_r($test->_getall());
}
// In XXX.php if result === false.. start manual search on different sites. that IAFD doesn't use. However if a result is found.
// make function to get directlink and start parsing that class....

?>
