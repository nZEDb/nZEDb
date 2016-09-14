<?php

use app\models\Settings;

$page->title = "Terms and Conditions";
$page->meta_title = Settings::value('site.main.title') . " - Terms and conditions";
$page->meta_keywords = "terms,conditions";
$page->meta_description = "Terms and Conditions for " . Settings::value('site.main.title');

$page->content = $page->smarty->fetch('terms.tpl');

$page->render();

?>
