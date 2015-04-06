<?php
require_once './config.php';

use nzedb\db\Settings;

// new to get information on books groups

$page  = new AdminPage();
$id    = 0;
$error = '';

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'submit':
		$error = "";

		if (!empty($_POST['book_reqids'])) {
			// book_reqids is an array it needs to be a comma separated string, make it so.
			$_POST['book_reqids'] = is_array($_POST['book_reqids']) ?
				implode(', ', $_POST['book_reqids']) : $_POST['book_reqids'];
		}
		// update site table as always
		$ret = $page->settings->update($_POST);

		if (is_int($ret)) {
			// TODO convert to switch
			if ($ret == Settings::ERR_BADUNRARPATH) {
				$error = "The unrar path does not point to a valid binary";
			} else {
				if ($ret == Settings::ERR_BADFFMPEGPATH) {
					$error = "The ffmpeg path does not point to a valid binary";
				} else {
					if ($ret == Settings::ERR_BADMEDIAINFOPATH) {
						$error = "The mediainfo path does not point to a valid binary";
					} else {
						if ($ret == Settings::ERR_BADNZBPATH) {
							$error = "The nzb path does not point to an existing directory";
						} else {
							if ($ret == Settings::ERR_DEEPNOUNRAR) {
								$error = "Deep password check requires a valid path to unrar binary";
							} else {
								if ($ret == Settings::ERR_BADTMPUNRARPATH) {
									$error = "The temp unrar path is not a valid directory";
								} else {
									if ($ret == Settings::ERR_BADNZBPATH_UNREADABLE) {
										$error = "The nzb path cannot be read from. Check the permissions.";
									} else {
										if ($ret == Settings::ERR_BADNZBPATH_UNSET) {
											$error = "The nzb path is required, please set it.";
										} else {
											if ($ret == Settings::ERR_BAD_COVERS_PATH) {
												$error = 'The covers&apos; path is required and must exist. Please set it.';
											} else {
												if ($ret == Settings::ERR_BAD_YYDECODER_PATH) {
													$error = 'The yydecoder&apos;s path must exist. Please set it or leave it empty.';
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		if ($error == "") {
			$site     = $ret;
			$returnid = $site['id'];
			header("Location:" . WWW_TOP . "/site-edit.php?id=" . $returnid);
		} else {
			$page->smarty->assign('error', $error);
			$page->smarty->assign('settings', $page->settings->rowsToArray($_POST));
		}
		break;

	case 'view':
	default:
		$page->title = "Site Edit";
		$site        = $page->settings;
		$page->smarty->assign('site', $site);
		$page->smarty->assign('settings', $site->getSettingsAsTree());
		break;
}

if ($error === '') {
	$page->smarty->assign('error', '');
}

$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['Yes', 'No']);

$page->smarty->assign('passwd_ids', [1, 0]);
$page->smarty->assign('passwd_names', ['Deep (requires unrar)', 'None']);

/*0 = English, 2 = Danish, 3 = French, 1 = German*/
$page->smarty->assign('langlist_ids', [0, 2, 3, 1]);
$page->smarty->assign('langlist_names', ['English', 'Danish', 'French', 'German']);

$page->smarty->assign('imdblang_ids',
					  [
						  'en', 'da', 'nl', 'fi', 'fr', 'de', 'it', 'tlh', 'no', 'po', 'ru', 'es',
						  'sv'
					  ]);
$page->smarty->assign('imdblang_names',
					  [
						  'English', 'Danish', 'Dutch', 'Finnish', 'French', 'German', 'Italian',
						  'Klingon', 'Norwegian', 'Polish', 'Russian', 'Spanish', 'Swedish'
					  ]);

$page->smarty->assign('imdb_urls', [0, 1]);
$page->smarty->assign('imdburl_names', ['imdb.com', 'akas.imdb.com']);

$page->smarty->assign('rottentomatoquality_ids', ['thumbnail', 'profile', 'detailed', 'original']);
$page->smarty->assign('rottentomatoquality_names',
					  ['Thumbnail', 'Profile', 'Detailed', 'Original']);

$page->smarty->assign('menupos_ids', [0, 1, 2]);
$page->smarty->assign('menupos_names', ['Right', 'Left', 'Top']);

$page->smarty->assign('sabintegrationtype_ids',
					  [
						  SABnzbd::INTEGRATION_TYPE_USER, SABnzbd::INTEGRATION_TYPE_SITEWIDE,
						  SABnzbd::INTEGRATION_TYPE_NONE
					  ]);
$page->smarty->assign('sabintegrationtype_names', ['User', 'Site-wide', 'None (Off)']);

$page->smarty->assign('sabapikeytype_ids', [SABnzbd::API_TYPE_NZB, SABnzbd::API_TYPE_FULL]);
$page->smarty->assign('sabapikeytype_names', ['Nzb Api Key', 'Full Api Key']);

$page->smarty->assign('sabpriority_ids',
					  [
						  SABnzbd::PRIORITY_FORCE, SABnzbd::PRIORITY_HIGH, SABnzbd::PRIORITY_NORMAL,
						  SABnzbd::PRIORITY_LOW
					  ]);
$page->smarty->assign('sabpriority_names', ['Force', 'High', 'Normal', 'Low']);

$page->smarty->assign('newgroupscan_names', ['Days', 'Posts']);
$page->smarty->assign('registerstatus_ids',
					  [
						  Settings::REGISTER_STATUS_API_ONLY, Settings::REGISTER_STATUS_OPEN,
						  Settings::REGISTER_STATUS_INVITE, Settings::REGISTER_STATUS_CLOSED
					  ]);
$page->smarty->assign('registerstatus_names', ['API Only', 'Open', 'Invite', 'Closed']);
$page->smarty->assign('passworded_ids', [0, 1, 10]);
$page->smarty->assign('passworded_names',
					  [
						  'Don\'t show passworded or potentially passworded',
						  'Don\'t show passworded', 'Show everything'
					  ]);

//$page->smarty->assign('grabnzbs_ids', array(0,1,2));
//$page->smarty->assign('grabnzbs_names', array('Disabled', 'Primary NNTP Provider', 'Alternate NNTP Provider'));

$page->smarty->assign('missed_parts_ids', [0, 1, 2]);
$page->smarty->assign('missed_parts_names', ['Disabled', 'Part Repair', 'Part Repair Threaded']);

$page->smarty->assign('lookupbooks_ids', [0, 1, 2]);
$page->smarty->assign('lookupbooks_names',
					  ['Disabled', 'Lookup All Books', 'Lookup Renamed Books']);

$page->smarty->assign('lookupgames_ids', [0, 1, 2]);
$page->smarty->assign('lookupgames_names',
					  ['Disabled', 'Lookup All Consoles', 'Lookup Renamed Consoles']);

$page->smarty->assign('lookupmusic_ids', [0, 1, 2]);
$page->smarty->assign('lookupmusic_names',
					  ['Disabled', 'Lookup All Music', 'Lookup Renamed Music']);

$page->smarty->assign('lookupmovies_ids', [0, 1, 2]);
$page->smarty->assign('lookupmovies_names',
					  ['Disabled', 'Lookup All Movies', 'Lookup Renamed Movies']);

$page->smarty->assign('lookuptv_ids', [0, 1, 2]);
$page->smarty->assign('lookuptv_names', ['Disabled', 'Lookup All TV', 'Lookup Renamed TV']);

$page->smarty->assign('lookup_reqids_ids', [0, 1, 2]);
$page->smarty->assign('lookup_reqids_names',
					  ['Disabled', 'Lookup Request IDs', 'Lookup Request IDs Threaded']);

$page->smarty->assign('coversPath', nZEDb_COVERS);

// return a list of audiobooks, ebooks, technical and foreign books
$result = $page->settings->query("SELECT id, title FROM category WHERE id IN (3030, 8010, 8040, 8060)");

// setup the display lists for these categories, this could have been static, but then if names changed they would be wrong
$book_reqids_ids   = [];
$book_reqids_names = [];
foreach ($result as $bookcategory) {
	$book_reqids_ids[]   = $bookcategory["id"];
	$book_reqids_names[] = $bookcategory["title"];
}

// convert from a string array to an int array as we want to use int
$book_reqids_ids = array_map(create_function('$value', 'return (int)$value;'), $book_reqids_ids);
$page->smarty->assign('book_reqids_ids', $book_reqids_ids);
$page->smarty->assign('book_reqids_names', $book_reqids_names);

// convert from a list to an array as we need to use an array, but teh sites table only saves strings
$books_selected = explode(",", $page->settings->getSetting('book_reqids'));

// convert from a string array to an int array
$books_selected = array_map(create_function('$value', 'return (int)$value;'), $books_selected);
$page->smarty->assign('book_reqids_selected', $books_selected);

$page->smarty->assign('loggingopt_ids', [0, 1, 2, 3]);
$page->smarty->assign('loggingopt_names',
					  ['Disabled', 'Log in DB only', 'Log both DB and file', 'Log only in file']);

$themelist = [];
$themes    = scandir(nZEDb_WWW . "/themes");
foreach ($themes as $theme) {
	if (strpos($theme, ".") === false && is_dir(nZEDb_WWW . "/themes/" . $theme)) {
		$themelist[] = $theme;
	}
}

$page->smarty->assign('themelist', $themelist);

$page->content = $page->smarty->fetch('site-edit.tpl');
$page->render();
