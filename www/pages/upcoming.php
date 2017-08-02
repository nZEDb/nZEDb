<?php

use nzedb\Movie;

if (!$page->users->isLoggedIn()) {
    $page->show403();
}

$m = new Movie(['Settings' => $page->settings]);

if (!isset($_GET["id"])) {
    $_GET["id"] = 1;
}
$selection = (isset($_GET['id'])) ? htmlspecialchars($_GET['id']) : 1;
$sort = (isset($_GET['sort'])) ? htmlspecialchars($_GET['sort']) : 'date';

$user = $page->users->getById($page->users->currentUserId());
$cpapi = $user['cp_api'];
$cpurl = $user['cp_url'];
$page->smarty->assign('cpapi', $cpapi);
$page->smarty->assign('cpurl', $cpurl);

$data = $m->getUpcoming($selection, 'tmdb', $sort);
//print_r(json_decode($data["info"])->movies);die();

if (!$data) {
    $page->smarty->assign("nodata", "No upcoming data.");
} else {
    foreach ($data as $mid => $movie) {
        $movie->release_ts = strtotime($movie->release_date);

        $data[$mid] = $movie;
    }
    $page->smarty->assign('date_cutoff', strtotime('6 months ago'));

    $page->smarty->assign('selection', $selection);
    $page->smarty->assign('data', $data);
    $page->smarty->assign('imgbase', 'https://image.tmdb.org/t/p/w154/');
    $page->smarty->assign('poster_size', 'w154');

    switch ($_GET["id"]) {
        case Movie::SRC_INTHEATRE;
            $page->title = "In Theater";
            break;
        case Movie::SRC_UPCOMING;
            $page->title = "Upcoming";
            break;
    }
    $page->meta_title = "View upcoming theatre releases";
    $page->meta_keywords = "view,series,theatre,dvd";
    $page->meta_description = "View upcoming theatre releases";
}

$page->content = $page->smarty->fetch('upcoming.tpl');
$page->render();
