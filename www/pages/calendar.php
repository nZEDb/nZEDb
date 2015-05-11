<?php

use nzedb\TvRage;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$tvrage = new TvRage(['Settings' => $page->settings]);

$date = date("Y-m-d");
if (isset($_GET["date"])) {
	$date = $_GET["date"];
}

$timestamp = mktime(0, 0, 0, substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4));
// Make it 7 days before, 7 days after.
$start = $timestamp - (86400 * 7);
$cal = array();
for ($i = 0; $i <= 13; $i++) {
	$start = $start + 86400;
	$cal[] = date("Y-m-d", $start);
}

$prettydate = date("l, jS F Y", $timestamp);
$prepretty = date("l, jS F Y", ($timestamp - 86400));
$nxtpretty = date("l, jS F Y", ($timestamp + 86400));
$predaydata = $tvrage->getCalendar(date("Y-m-d", ($timestamp - 86400)));
$nxtdaydata = $tvrage->getCalendar(date("Y-m-d", ($timestamp + 86400)));
$daydata = $tvrage->getCalendar($date);

$page->title = 'Calendar';
$page->meta_title = "View Calendar";
$page->meta_keywords = "view,calendar,tv,";
$page->meta_description = "View Calendar";

$page->smarty->assign('date', $prettydate);
$page->smarty->assign('predate', $prepretty);
$page->smarty->assign('nxtdate', $nxtpretty);
$page->smarty->assign('daydata', $daydata);
$page->smarty->assign('predata', $predaydata);
$page->smarty->assign('nxtdata', $nxtdaydata);
$page->smarty->assign('cal', $cal);
$page->content = $page->smarty->fetch('viewcalendar.tpl');
$page->render();
