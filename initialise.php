<?php
require_once 'SPLClassLoader.php';
require_once 'constants.php';

$classLoader = new SplClassLoader('nzedb', [__DIR__ . DIRECTORY_SEPARATOR . 'nzedb']);
$classLoader->register();

?>
