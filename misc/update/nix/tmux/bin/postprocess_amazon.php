<?php

require_once dirname(__FILE__) . '/../../../config.php';

$c = new ColorCLI();

$postprocess = new PostProcess(true);
$postprocess->processBooks();
$postprocess->processMusic();
$postprocess->processGames();
$postprocess->processConsoles();
$postprocess->processXXX();