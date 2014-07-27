<?php
$page->title = "RSS Info";
$page->meta_title = "RSS Help Topics";
$page->meta_keywords = "view,nzb,api,details,help,json,rss,atom";
$page->meta_description = "View description of the site Nzb RSS.";

$page->content = $page->smarty->fetch('rssdesc.tpl');
$page->render();