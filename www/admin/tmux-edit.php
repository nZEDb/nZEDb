<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/tmux.php");
require_once(WWW_DIR."/lib/sabnzbd.php");

$page = new AdminPage();
$tmux = new Tmux();
$id = 0;

// set the current action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch($action)
{
    case 'submit':

		$error = "";
		$ret = $tmux->update($_POST);
		$page->title = "Tmux Settings Edit";
		$settings = $tmux->get();
		$page->smarty->assign('ftmux', $settings);

        break;
    case 'view':
    default:

			$page->title = "Tmux Settings Edit";
			$settings = $tmux->get();
			$page->smarty->assign('ftmux', $settings);

      break;
}

$page->smarty->assign('yesno_names', array('yes', 'no'));
$page->smarty->assign('truefalse_names', array('TRUE', 'FALSE'));

$page->smarty->assign('backfill_ids', array(0,1,3,2));
$page->smarty->assign('backfill_names', array('Disabled', 'Interval-Newest Complete', 'Interval-Oldest Complete', 'All'));

$page->content = $page->smarty->fetch('tmux-edit.tpl');
$page->render();


