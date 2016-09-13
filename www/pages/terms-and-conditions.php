<?php

use app\models\Settings;

$page->title = "Terms and Conditions";
$page->meta_title = Settings::value('title') . " - Terms and conditions";
$page->meta_keywords = "terms,conditions";
$page->meta_description = "Terms and Conditions for " . Settings::value('title');

$page->content = $page->smarty->fetch('terms.tpl');

$page->render();

?>
