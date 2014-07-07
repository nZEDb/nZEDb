<?php
$page->title = "Terms and Conditions";
$page->meta_title = $page->pdo->getSetting('title') . " - Terms and conditions";
$page->meta_keywords = "terms,conditions";
$page->meta_description = "Terms and Conditions for " . $page->pdo->getSetting('title');

$page->content = $page->smarty->fetch('terms.tpl');

$page->render();
