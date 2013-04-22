<?php

require("config.php");
require_once(WWW_DIR."/lib/postprocess.php");

$postprocess = new PostProcess(true);
$postprocess->processNfos();

