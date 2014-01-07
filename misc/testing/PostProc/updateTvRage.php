<?php
//This script will update all records in the tvrage table

require_once dirname(__FILE__) . '/../../../www/config.php';
//require_once nZEDb_LIB . 'framework/db.php';
//require_once nZEDb_LIB . 'tvrage.php';

$tvrage = new TvRage(true);
$db = new Db();

$shows = $db->queryDirect("SELECT rageid FROM tvrage WHERE imgdata IS NULL ORDER BY rageid DESC");
if ($shows->rowCount() > 0)
    echo "Updating ".$shows->rowCount()." tv shows.\n";

$loop = 0;
foreach ($shows as $show)
{
    $starttime = microtime(true);
    $rageid = $show['rageid'];
    $tvrShow = $tvrage->getRageInfoFromService($rageid);
    $genre = '';
    if (isset($tvrShow['genres']) && is_array($tvrShow['genres']) && !empty($tvrShow['genres']))
    {
        if (is_array($tvrShow['genres']['genre']))
            $genre = @implode('|', $tvrShow['genres']['genre']);
        else
            $genre = $tvrShow['genres']['genre'];
    }
    $country = '';
    if (isset($tvrShow['country']) && !empty($tvrShow['country']))
        $country = $tvrage->countryCode($tvrShow['country']);

    $rInfo = $tvrage->getRageInfoFromPage($rageid);
    $desc = '';
    if (isset($rInfo['desc']) && !empty($rInfo['desc']))
        $desc = $rInfo['desc'];

    $imgbytes = '';
    if (isset($rInfo['imgurl']) && !empty($rInfo['imgurl']))
    {
        $img = getUrl($rInfo['imgurl']);
        if ($img !== false)
        {
            $im = @imagecreatefromstring($img);
            if($im !== false)
                $imgbytes = $img;
        }
    }
    $db->queryDirect(sprintf("UPDATE tvrage SET description = %s, genre = %s, country = %s, imgdata = %s WHERE rageid = %d", $db->escapeString(substr($desc, 0, 10000)), $db->escapeString(substr($genre, 0, 64)),  $db->escapeString($country), $db->escapeString($imgbytes), $rageid));
    $name = $db->query("Select releasetitle from tvrage where rageid = " . $rageid);
    echo $name[0]['releasetitle'] . "\n";
    $diff = floor((microtime(true) - $starttime) * 1000000);
    if (1000000 - $diff > 0)
    {
        usleep(1000000 - $diff);
    }
    if ($loop++ == 1000)
    {
        $tvrage->updateSchedule();
        $loop = 0;
    }
}
