<?php
require_once './config.php';

use app\models\Groups as Group;
use nzedb\Groups;

$page   = new AdminPage();
$groups = new Groups(['Settings' => $page->settings]);
$id     = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'submit':
		if (empty($_POST['id'])) {
			// Add a new group.
			if ($_POST['name'] = Group::isValidName($_POST['name'])) {
				// Only allow entries whose keys are valid columns.
				$data = array_intersect_key($_POST, Group::schema()->fields());
				try {
					$newGroup = Group::create($data);
				} catch (\InvalidArgumentException $e) {
					throw new \InvalidArgumentException($e->getMessage() .
						PHP_EOL .
						'Thrown in group-edit.php');
				}

				$newGroup->save();
			}
		} else {
			// Update an existing group.
			$groups->update($_POST);
		}
		header("Location:" . WWW_TOP . "/group-list.php");
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "Newsgroup Edit";
			$id          = $_GET["id"];
			$group       = Group::getAllByID($id);
		} else {
			$page->title = "Newsgroup Add";
			$group = [
				'id'                    => '',
				'name'                  => '',
				'description'           => '',
				'minfilestoformrelease' => 0,
				'active'                => 0,
				'backfill'              => 0,
				'minsizetoformrelease'  => 0,
				'first_record'          => 0,
				'last_record'           => 0,
				'backfill_target'       => 0
			];
		}
		$page->smarty->assign('group', $group);
		break;
}

$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['Yes', 'No']);

$page->content = $page->smarty->fetch('group-edit.tpl');
$page->render();
