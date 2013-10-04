<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/backfill.php");
require_once(FS_ROOT."/../../../www/lib/nntp.php");
require_once(FS_ROOT."/../../../www/lib/ColorCLI.php");

/* This script will update the groups table to get the new article numbers for each group you have activated.
It will also truncate the parts, binaries, collections, and partsrepair tables.
*/
// TODO: Make this threaded so it goes faster.

$c = New ColorCLI;
$db = New DB();

if (!isset($argv[1]) || $argv[1] != 'true')
{
    printf($c->setColor('bold', 'yellow')."This script is used when you have switched UseNet Providers(USP) so you can pickup where you left off, rather than resetting all the groups.\nOnly use this script after you have updated your config.php file with your new USP info!!\nMake sure you ".$c->setColor('bold', 'red')."DO NOT".$c->setcolor('bold', 'yellow')." have any update or postprocess scripts running when running this script!\n\n".$c->setColor('norm', 'cyan')."Usage: php change_USP_provider true\n");
    exit();
}


$groups = $db->query("SELECT id, name, first_record_postdate, last_record_postdate FROM groups WHERE active = 1");
$numofgroups = count($groups);
$guesstime = $numofgroups * 2;
$totalstart = microtime(true);

echo "You have $numofgroups active, it takes about 2 minutes on average to processes each group.\n";
foreach ($groups as $group)
{
    $starttime = microtime(true);
    $nntp = new Nntp();
    if ($nntp->doConnect() === false)
        return;
    //printf("Updating group ".$group['name']."..\n");
    $bfdays = daysOldstr($group['first_record_postdate']);
    $currdays = daysOldstr($group['last_record_postdate']);
    $bfartnum = daytopost($nntp, $group['name'], $bfdays, true, true);
    echo "Our Current backfill postdate was: ".$c->setcolor('bold', 'yellow').date('r', strtotime($group['first_record_postdate'])).$c->rsetcolor()."\n";
    $currartnum = daytopost($nntp, $group['name'], $currdays, true, false);
    echo "Our Current current postdate was: ".$c->setcolor('bold', 'yellow').date('r', strtotime($group['last_record_postdate'])).$c->rsetcolor()."\n";
    $db->queryExec(sprintf("UPDATE groups SET first_record = %s, last_record = %s WHERE id = %d", $db->escapeString($bfartnum), $db->escapeString($currartnum), $group['id']));
    $endtime = microtime(true);
    echo $c->setColor('dim', 'gray')."This group took ".gmdate("H:i:s",$endtime-$starttime)." to process.\n";
    $numofgroups--;
    echo "There are ".$numofgroups." left to process.\n\n".$c->rsetcolor()."";
}

$totalend = microtime(true);
echo 'Total time to update all groups '.gmdate("H:i:s",$totalend-$totalstart)."\n";

// Truncate tables to complete the change to the new USP.
$arr = array("parts", "partrepair", "binaries", "collections");
	foreach ($arr as &$value)
    {
        $rel = $db->queryExec("TRUNCATE TABLE $value");
        if($rel !== false)
            printf("Truncating $value completed.\n");
    }
	unset($value);



function daysOldstr($timestamp)
{
    return round((time()-strtotime($timestamp))/86400, 5);
}

function daysOld($timestamp)
{
    return round((time()-$timestamp)/86400, 5);
}

// This function taken from lib/backfill.php, and modified to fit our needs.
function daytopost($nntp, $group, $days, $debug=true, $bfcheck=true)
{
    $c = New ColorCLI;
    $backfill = New Backfill();
    // DEBUG every postdate call?!?!
    $pddebug = $st = false;
    if ($debug && $bfcheck)
        echo 'Finding start and end articles for '.$group.".\n";

    if (!isset($nntp))
    {
        $nntp = new Nntp;
        if ($nntp->doConnectNC() === false)
            return;

        $st = true;
    }

    $data = $nntp->selectGroup($group);
    if (PEAR::isError($data))
    {
        $data = $nntp->dataError($nntp, $group, false);
        if ($data === false)
            return;
    }

    // Goal timestamp.
    $goaldate = date('U')-(86400*$days);
    $totalnumberofarticles = $data['last'] - $data['first'];
    $upperbound = $data['last'];
    $lowerbound = $data['first'];

    if ($debug && $bfcheck)
        echo 'Total Articles: '.number_format($totalnumberofarticles).' Newest: '.number_format($upperbound).' Oldest: '.number_format($lowerbound)."\n";

    if ($data['last'] == PHP_INT_MAX)
        exit("ERROR: Group data is coming back as php's max value. You should not see this since we use a patched Net_NNTP that fixes this bug.\n");

    $firstDate = $backfill->postdate($nntp, $data['first'], $pddebug, $group);
    $lastDate = $backfill->postdate($nntp, $data['last'], $pddebug, $group);

    if ($goaldate < $firstDate && $bfcheck)
    {
        if ($st === true)
            $nntp->doQuit();
        echo 'WARNING: The oldest post indexed from '.$days." day(s) ago is older than the first article stored on your news server.\nSetting to First available article of (".date('r', $firstDate).' or '.daysOld($firstDate)." days).\n";
        return $data['first'];
    }
    elseif ($goaldate > $lastDate && $bfcheck)
    {
        if ($st === true)
            $nntp->doQuit();
        echo 'ERROR: The oldest post indexed from '.$days." day(s) ago is newer than the last article stored on your news server.\nTo backfill this group you need to set Backfill Days to at least ".ceil(daysOld($lastDate)+1).' days ('.date('r', $lastDate-86400).").\n";
        return '';
    }

    if ($debug && $bfcheck)
        echo "Searching for postdates.\nGroup's Firstdate: ".$firstDate.' ('.((is_int($firstDate))?date('r', $firstDate):'n/a').").\nGroup's Lastdate: ".$lastDate.' ('.date('r', $lastDate).").\n";

    $interval = floor(($upperbound - $lowerbound) * 0.5);
    $dateofnextone = $templowered = '';

    $dateofnextone = $lastDate;
    // Match on days not timestamp to speed things up.
    while(daysOld($dateofnextone) < $days)
    {
        while(($tmpDate = $backfill->postdate($nntp,($upperbound-$interval),$pddebug,$group))>$goaldate)
        {
            $upperbound = $upperbound - $interval;
        }

        if(!$templowered)
        {
            $interval = ceil(($interval/2));
        }
        $dateofnextone = $backfill->postdate($nntp,($upperbound-1),$pddebug,$group);
        while(!$dateofnextone)
        {  $dateofnextone = $backfill->postdate($nntp,($upperbound-1),$pddebug,$group); }
    }
    if ($st === true)
        $nntp->doQuit();
    if ($bfcheck)
        echo "\nBackfill article determined to be ".$upperbound." ".$c->setcolor('bold', 'yellow')."(".date('r', $dateofnextone).")".$c->rsetcolor()."\n"; // which is '.daysOld($dateofnextone)." days old.\n";
    else
        echo 'Current article determined to be '.$upperbound." ".$c->setcolor('bold', 'yellow')."(".date('r', $dateofnextone).")".$c->rsetcolor()."\n"; // which is '.daysOld($dateofnextone)." days old.\n";
    return $upperbound;
}

