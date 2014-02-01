<?php
$page->title = "Terms and Conditions";
$page->meta_title = $page->site->title . " - Terms and conditions";
$page->meta_keywords = "terms,conditions";
$page->meta_description = "Terms and Conditions for " . $page->site->title;

$page->content = $page->smarty->fetch('terms.tpl');

$page->render();
