<?php
require_once './config.php';

use Cake\ORM\TableRegistry;

$page = new AdminPage();
$id   = 0;

// Set the current action.
$action = $_REQUEST['action'] ?? 'view';

$table = TableRegistry::getTableLocator()->get('Groups');
// Get the valid columns for this table
$columns = array_flip($table->getSchema()->columns());

switch ($action) {
	case 'submit':
		// Only allow entries whose keys are valid columns.
		$data = array_intersect_key($_POST, $columns);

		if (empty($_POST['id'])) {
			try {
				$entity = $table->newEntity($data);
				if ($table->save($entity) === false) {
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
			$entity = $table->get($data['id']);
			$table->patchEntity($entity, $data);

			if ($table->save($entity) === false) {
				throw new \ErrorException('Failed to update the group info to the database');
			}
		}

		header('Location:' . WWW_TOP . '/group-list.php');
		break;

	case 'view':
	default:
		// Only allow entries whose keys are valid columns.
		$data = array_intersect_key($_GET, $columns);
		if (isset($data['id'])) {
				$page->title = 'Newsgroup Edit';
				$id          = $data['id'];
				$entity      = $table->getAllByID($id);
			} else {
				$page->title = 'Newsgroup Add';
				$entity = [
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
			$page->smarty->assign('group', $entity);
		break;
}

$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['Yes', 'No']);

$page->content = $page->smarty->fetch('group-edit.tpl');
$page->render();
