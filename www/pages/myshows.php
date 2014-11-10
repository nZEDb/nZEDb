<?php

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$us = new UserSeries(['Settings' => $page->settings]);

$action = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
$rid = isset($_REQUEST['subpage']) ? $_REQUEST['subpage'] : '';

switch ($action) {
	case 'delete':
		$show = $us->getShow($page->users->currentUserId(), $rid);

		if (!$show) {
			$page->show404('Not subscribed');
		} else {
			$us->delShow($page->users->currentUserId(), $rid);
		}

		if (isset($_REQUEST['from'])) {
			header("Location:" . WWW_TOP . $_REQUEST['from']);
		} else {
			header("Location:" . WWW_TOP . "/myshows");
		}
		break;
	case 'add':
	case 'doadd':
		$show = $us->getShow($page->users->currentUserId(), $rid);

		if ($show) {
			$page->show404('Already subscribed');
		} else {
			$show = $us->pdo->queryOneRow(sprintf("SELECT releasetitle FROM tvrage_titles WHERE rageid = %d", $rid));
			if (!$show) {
				$page->show404('Seriously?');
			}
		}

		if ($action == 'doadd') {
			$category = (isset($_REQUEST['category']) && is_array($_REQUEST['category']) && !empty($_REQUEST['category'])) ? $_REQUEST['category'] : array();
			$us->addShow($page->users->currentUserId(), $rid, $category);
			if (isset($_REQUEST['from'])) {
				header("Location:" . $_REQUEST['from']);
			} else {
				header("Location:" . WWW_TOP . "/myshows");
			}
		} else {
			$cat = new Category(['Settings' => $page->settings]);
			$tmpcats = $cat->getChildren(Category::CAT_PARENT_TV);
			$categories = array();
			foreach ($tmpcats as $c) {
				$categories[$c['id']] = $c['title'];
			}

			$page->smarty->assign('type', 'add');
			$page->smarty->assign('cat_ids', array_keys($categories));
			$page->smarty->assign('cat_names', $categories);
			$page->smarty->assign('cat_selected', array());
			$page->smarty->assign('rid', $rid);
			$page->smarty->assign('show', $show);
			if (isset($_REQUEST['from'])) {
				$page->smarty->assign('from', $_REQUEST['from']);
			}
			$page->content = $page->smarty->fetch('myshows-add.tpl');
			$page->render();
		}
		break;
	case 'edit':
	case 'doedit':
		$show = $us->getShow($page->users->currentUserId(), $rid);

		if (!$show) {
			$page->show404();
		}

		if ($action == 'doedit') {
			$category = (isset($_REQUEST['category']) && is_array($_REQUEST['category']) && !empty($_REQUEST['category'])) ? $_REQUEST['category'] : array();
			$us->updateShow($page->users->currentUserId(), $rid, $category);
			if (isset($_REQUEST['from'])) {
				header("Location:" . WWW_TOP . $_REQUEST['from']);
			} else {
				header("Location:" . WWW_TOP . "/myshows");
			}
		} else {
			$cat = new Category(['Settings' => $page->settings]);

			$tmpcats = $cat->getChildren(Category::CAT_PARENT_TV);
			$categories = array();
			foreach ($tmpcats as $c) {
				$categories[$c['id']] = $c['title'];
			}

			$page->smarty->assign('type', 'edit');
			$page->smarty->assign('cat_ids', array_keys($categories));
			$page->smarty->assign('cat_names', $categories);
			$page->smarty->assign('cat_selected', explode('|', $show['categoryid']));
			$page->smarty->assign('rid', $rid);
			$page->smarty->assign('show', $show);
			if (isset($_REQUEST['from'])) {
				$page->smarty->assign('from', $_REQUEST['from']);
			}
			$page->content = $page->smarty->fetch('myshows-add.tpl');
			$page->render();
		}
		break;
	case 'browse':

		$page->title = "Browse My Shows";
		$page->meta_title = "My Shows";
		$page->meta_keywords = "search,add,to,cart,nzb,description,details";
		$page->meta_description = "Browse Your Shows";

		$shows = $us->getShows($page->users->currentUserId());

		$releases = new Releases(['Settings' => $page->settings]);
		$browsecount = $releases->getShowsCount($shows, -1, $page->userdata["categoryexclusions"]);

		$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
		$ordering = $releases->getBrowseOrdering();
		$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

		$results = array();
		$results = $releases->getShowsRange($shows, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"]);

		$page->smarty->assign('pagertotalitems', $browsecount);
		$page->smarty->assign('pageroffset', $offset);
		$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
		$page->smarty->assign('pagerquerybase', WWW_TOP . "/myshows/browse?ob=" . $orderby . "&amp;offset=");
		$page->smarty->assign('pagerquerysuffix', "#results");

		$pager = $page->smarty->fetch("pager.tpl");
		$page->smarty->assign('pager', $pager);

		foreach ($ordering as $ordertype) {
			$page->smarty->assign('orderby' . $ordertype, WWW_TOP . "/myshows/browse?ob=" . $ordertype . "&amp;offset=0");
		}

		$page->smarty->assign('lastvisit', $page->userdata['lastlogin']);

		$page->smarty->assign('results', $results);

		$page->smarty->assign('shows', true);

		$page->content = $page->smarty->fetch('browse.tpl');
		$page->render();
		break;
	default:

		$page->title = "My Shows";
		$page->meta_title = "My Shows";
		$page->meta_keywords = "search,add,to,cart,nzb,description,details";
		$page->meta_description = "Manage Your Shows";

		$cat = new Category(['Settings' => $page->settings]);
		$tmpcats = $cat->getChildren(Category::CAT_PARENT_TV);
		$categories = array();
		foreach ($tmpcats as $c) {
			$categories[$c['id']] = $c['title'];
		}

		$shows = $us->getShows($page->users->currentUserId());
		$results = array();
		foreach ($shows as $showk => $show) {
			$showcats = explode('|', $show['categoryid']);
			if (is_array($showcats) && sizeof($showcats) > 0) {
				$catarr = array();
				foreach ($showcats as $scat) {
					if (!empty($scat)) {
						$catarr[] = $categories[$scat];
					}
				}
				$show['categoryNames'] = implode(', ', $catarr);
			} else {
				$show['categoryNames'] = '';
			}

			$results[$showk] = $show;
		}
		$page->smarty->assign('shows', $results);

		$page->content = $page->smarty->fetch('myshows.tpl');
		$page->render();
		break;
}
