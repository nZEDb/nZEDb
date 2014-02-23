<?php
require_once './config.php';


// new to get information on books groups


$page = new AdminPage();
$sites = new Sites();
$id = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch($action)
{
	case 'submit':
		$error = "";
		// book_reqids is an array it needs to be a comma seperated string, make it so
		$book_ids = implode(', ', $_POST['book_reqids']);
		// save it back
		$_POST['book_reqids'] = $book_ids;
		// update site table as always
		$ret = $sites->update($_POST);

		if (is_int($ret))
		{
			if ($ret == Sites::ERR_BADUNRARPATH)
				$error = "The unrar path does not point to a valid binary";
			else if ($ret == Sites::ERR_BADFFMPEGPATH)
				$error = "The ffmpeg path does not point to a valid binary";
			else if ($ret == Sites::ERR_BADMEDIAINFOPATH)
				$error = "The mediainfo path does not point to a valid binary";
			else if ($ret == Sites::ERR_BADNZBPATH)
				$error = "The nzb path does not point to an existing directory";
			else if ($ret == Sites::ERR_DEEPNOUNRAR)
				$error = "Deep password check requires a valid path to unrar binary";
			else if ($ret == Sites::ERR_BADTMPUNRARPATH)
				$error = "The temp unrar path is not a valid directory";
			else if ($ret == Sites::ERR_BADNZBPATH_UNREADABLE) {
				$error = "The nzb path cannot be read from. Check the permissions.";
			} else if ($ret == Sites::ERR_BADNZBPATH_UNSET) {
				$error = "The nzb path is required, please set it.";
			}
		}

		if ($error == "")
		{
			$site = $ret;
			$returnid = $site->id;
			header("Location:".WWW_TOP."/site-edit.php?id=".$returnid);
		}
		else
		{
			$page->smarty->assign('error', $error);
			$site = $sites->row2Object($_POST);
			$page->smarty->assign('fsite', $site);
		}
		break;

	case 'view':
	default:
		$page->title = "Site Edit";
		$site = $sites->get();
		$page->smarty->assign('fsite', $site);
		break;
}

$page->smarty->assign('yesno_ids', array(1,0));
$page->smarty->assign('yesno_names', array('Yes', 'No'));

$page->smarty->assign('passwd_ids', array(1,0));
$page->smarty->assign('passwd_names', array('Deep (requires unrar)', 'None'));

/*0 = English, 2 = Danish, 3 = French, 1 = German*/
$page->smarty->assign('langlist_ids', array(0,2,3,1));
$page->smarty->assign('langlist_names', array('English', 'Danish', 'French', 'German'));

$page->smarty->assign('imdblang_ids', array('en', 'da', 'nl', 'fi', 'fr', 'de', 'it', 'tlh', 'no', 'po', 'ru', 'es', 'sv'));
$page->smarty->assign('imdblang_names', array('English', 'Danish', 'Dutch', 'Finnish', 'French', 'German', 'Italian', 'Klingon', 'Norwegian', 'Polish', 'Russian', 'Spanish', 'Swedish'));

$page->smarty->assign('imdb_urls', array(0,1));
$page->smarty->assign('imdburl_names', array('imdb.com', 'akas.imdb.com'));

$page->smarty->assign('rottentomatoquality_ids', array('thumbnail', 'profile', 'detailed', 'original'));
$page->smarty->assign('rottentomatoquality_names', array('Thumbnail', 'Profile', 'Detailed', 'Original'));

$page->smarty->assign('menupos_ids', array(0,1,2));
$page->smarty->assign('menupos_names', array('Right', 'Left', 'Top'));

$page->smarty->assign('sabintegrationtype_ids', array(SABnzbd::INTEGRATION_TYPE_USER, SABnzbd::INTEGRATION_TYPE_SITEWIDE, SABnzbd::INTEGRATION_TYPE_NONE));
$page->smarty->assign('sabintegrationtype_names', array( 'User', 'Site-wide', 'None (Off)'));

$page->smarty->assign('sabapikeytype_ids', array(SABnzbd::API_TYPE_NZB,SABnzbd::API_TYPE_FULL));
$page->smarty->assign('sabapikeytype_names', array('Nzb Api Key', 'Full Api Key'));

$page->smarty->assign('sabpriority_ids', array(SABnzbd::PRIORITY_FORCE, SABnzbd::PRIORITY_HIGH, SABnzbd::PRIORITY_NORMAL, SABnzbd::PRIORITY_LOW));
$page->smarty->assign('sabpriority_names', array('Force', 'High', 'Normal', 'Low'));

$page->smarty->assign('newgroupscan_names', array('Days','Posts'));
$page->smarty->assign('registerstatus_ids', array(Sites::REGISTER_STATUS_API_ONLY, Sites::REGISTER_STATUS_OPEN, Sites::REGISTER_STATUS_INVITE, Sites::REGISTER_STATUS_CLOSED));
$page->smarty->assign('registerstatus_names', array('API Only', 'Open', 'Invite', 'Closed'));
$page->smarty->assign('passworded_ids', array(0,1,10));
$page->smarty->assign('passworded_names', array('Don\'t show passworded or potentially passworded', 'Don\'t show passworded', 'Show everything'));

$page->smarty->assign('grabnzbs_ids', array(0,1,2));
$page->smarty->assign('grabnzbs_names', array('Disabled', 'Primary NNTP Provider', 'Alternate NNTP Provider'));

$page->smarty->assign('partrepair_ids', array(0,1,2));
$page->smarty->assign('partrepair_names', array('Disabled', 'Part Repair', 'Part Repair Threaded'));

$page->smarty->assign('lookupbooks_ids', array(0,1,2));
$page->smarty->assign('lookupbooks_names', array('Disabled', 'Lookup All Books', 'Lookup Renamed Books'));

$page->smarty->assign('lookupgames_ids', array(0,1,2));
$page->smarty->assign('lookupgames_names', array('Disabled', 'Lookup All Consoles', 'Lookup Renamed Consoles'));

$page->smarty->assign('lookupmusic_ids', array(0,1,2));
$page->smarty->assign('lookupmusic_names', array('Disabled', 'Lookup All Music', 'Lookup Renamed Music'));

$page->smarty->assign('lookup_reqids_ids', array(0,1,2));
$page->smarty->assign('lookup_reqids_names', array('Disabled', 'Lookup Request IDs', 'Lookup Request IDs Threaded'));

// return a list of audiobooks, ebooks, technical and foreign books
$db = new DB();
$result = $db->query("SELECT id, title FROM category WHERE id in (3030, 8010, 8040, 8060)");

// setup the display lists for these categories, this could have been static, but then if names changed they would be wrong
$book_reqids_ids = array();
$book_reqids_names = array();
foreach ($result as $bookcategory)
{
	$book_reqids_ids[]   = $bookcategory["id"];
	$book_reqids_names[] = $bookcategory["title"];
}

// convert from a string array to an int array as we want to use int
$book_reqids_ids = array_map(create_function('$value', 'return (int)$value;'), $book_reqids_ids);
$page->smarty->assign('book_reqids_ids', $book_reqids_ids);
$page->smarty->assign('book_reqids_names', $book_reqids_names);

// convert from a list to an array as we need to use an array, but teh sites table only saves strings
$books_selected = explode(",", $site->book_reqids);

// convert from a string array to an int array
$books_selected = array_map(create_function('$value', 'return (int)$value;'), $books_selected);
$page->smarty->assign('book_reqids_selected', $books_selected);

$page->smarty->assign('loggingopt_ids', array(0,1,2,3));
$page->smarty->assign('loggingopt_names', array ('Disabled', 'Log in DB only', 'Log both DB and file', 'Log only in file'));

$themelist = array();
$themes = scandir(nZEDb_WWW."/themes");
foreach ($themes as $theme)
{
	if (strpos($theme, ".") === false && is_dir(nZEDb_WWW."/themes/".$theme))
		$themelist[] = $theme;
}

$page->smarty->assign('themelist', $themelist);

$page->content = $page->smarty->fetch('site-edit.tpl');
$page->render();

?>
