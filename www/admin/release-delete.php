<?php

require_once './config.php';
//require_once nZEDb_LIB . 'releases.php';
//require_once nZEDb_LIB . 'adminpage.php';

$page = new AdminPage(true);

if (isset($_GET['id'])) {
    $releases = new Releases();
    $releases->delete($_GET['id']);
}

if (isset($_GET['from'])) {
    $referrer = $_GET['from'];
} else {
    $referrer = $_SERVER['HTTP_REFERER'];
}
header("Location: " . $referrer);
