<?php
if (!$users->isLoggedIn())
	$page->show403();

if (!isset($_REQUEST["id"]))
	$page->show404();

require_once(WWW_DIR."/lib/releaseextra.php");
$re = new ReleaseExtra();
$redata = $re->getBriefByGuid($_REQUEST["id"]);

if (!$redata)
	print "No media info";
else
{
	print "<table>\n";
	if ($redata["videocodec"] != "" && $redata["containerformat"] != "") 
	{
		$redata["videocodec"] = $re->makeCodecPretty($redata["videocodec"]);
		print "<tr><th>Format:</th><td>".htmlentities($redata["videocodec"], ENT_QUOTES)." - ".htmlentities($redata["containerformat"], ENT_QUOTES)."</td></tr>\n";
	}
	if ($redata["videoduration"] != "")
		print "<tr><th>Duration:</th><td>".htmlentities($redata["videoduration"], ENT_QUOTES)."</td></tr>\n";
	if ($redata["size"] != "")
		print "<tr><th>Resolution:</th><td>".htmlentities($redata["size"], ENT_QUOTES)."</td></tr>\n";
	if ($redata["videoaspect"] != "")
		print "<tr><th>Aspect Ratio:</th><td>".htmlentities($redata["videoaspect"], ENT_QUOTES)."</td></tr>\n";
	if ($redata["audio"] != "" && $redata["audio"] != ", ")
		print "<tr><th>Audio Languages:</th><td>".htmlentities($redata["audio"], ENT_QUOTES)."</td></tr>\n";
    if ($redata["audiobitrate"] != "" && $redata["audiobitrate"] != ", ")
        print "<tr><th>Audio Bitrate:</th><td>".htmlentities($redata["audiobitrate"], ENT_QUOTES)."</td></tr>\n";
    if ($redata["audioformat"] != "" && $redata["audioformat"] != ", ")
        print "<tr><th>Audio Format:</th><td>".htmlentities($redata["audioformat"], ENT_QUOTES)."</td></tr>\n";
    if ($redata["audiomode"] != "" && $redata["audiomode"] != ", ")
        print "<tr><th>Audio Mode:</th><td>".htmlentities($redata["audiomode"], ENT_QUOTES)."</td></tr>\n";
    if ($redata["audiobitratemode"] != "" && $redata["audiobitratemode"] != ", ")
        print "<tr><th>Audio Bitrate Mode:</th><td>".htmlentities($redata["audiobitratemode"], ENT_QUOTES)."</td></tr>\n";
	if ($redata["subs"] != "")
		print "<tr><th>Subtitles:</th><td>".htmlentities($redata["subs"], ENT_QUOTES)."</td></tr>\n";
	print "</table>";
}
