<?php
$page->title = "API Overview";
$page->meta_title = "API Help Topics";
$page->meta_keywords = "view,nzb,api,details,help,json,rss,atom";
$page->meta_description = "View description of the site Nzb Api.";
$page->content = $page->smarty->fetch('apidesc.tpl');
$page->render();