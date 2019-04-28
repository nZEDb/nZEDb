<?php
require_once './config.php';

use Cake\ORM\TableRegistry;
use nzedb\Groups;

$page   = new AdminPage();
$groups = new Groups(['Settings' => $page->settings]);
$id     = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'submit':
		// Get the valid columns for this table
		$columns = array_flip(TableRegistry::getTableLocator()->get('Groups')->getSchema()->columns());
		// Only allow entries whose keys are valid columns.
		$data = array_intersect_key($_POST, $columns);

		$groups = TableRegistry::getTableLocator()->get('Groups');

		if (empty($_POST['id'])) {
			try {
				$group = $groups->newEntity($data);
				// Save the new group.
				if ($groups->save($group) === false) {
					throw new \ErrorException('Failed to save new group to the database');
				}
			} catch (\Exception $e) {
				throw new \RuntimeException(
					$e->getMessage(),
					$e->getCode(),
					$e
				);
			}
		} else { // Update an existing group.
			$group = $groups->get($data['id']);
			$groups->patchEntity($group, $data);

			if ($groups->save($group) === false) {
				throw new \ErrorException('Failed to save new group to the database');
			}
		}

		header('Location:' . WWW_TOP . '/group-list.php');
		break;

	case 'view':
	default:
		if (isset($_GET['id'])) {
			$page->title = 'Newsgroup Edit';
			$id          = $_GET['id'];
			$group       = Group::getAllByID($id);
		} else {
			$page->title = 'Newsgroup Add';
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
