<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/releaseregex.php");
require_once(WWW_DIR."/lib/groups.php");

$page = new AdminPage();

$reg = new ReleaseRegex();

$page->title = "Release Regex List";

$reggrouplist = $reg->getGroupsForSelect();
$page->smarty->assign('reggrouplist', $reggrouplist);	

$group="-1";
if (isset($_REQUEST["group"]))
	$group = $_REQUEST["group"];
	
$page->smarty->assign('selectedgroup', $group);	

$regexlist = $reg->get(false, $group, true);
$page->smarty->assign('regexlist', $regexlist);	

$page->content = $page->smarty->fetch('regex-list.tpl');
$page->render();

?>
