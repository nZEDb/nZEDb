<?php
require_once './config.php';

use Cake\ORM\TableRegistry;
use nzedb\Binaries;
use nzedb\Category;

$page = new AdminPage();
$bin  = new Binaries(['Settings' => $page->settings]);
$errors = '';
$regex = ['id' => '', 'groupname' => '', 'regex' => '', 'description' => ''];

$table = TableRegistry::getTableLocator()->get('Binaryblacklist');
// Get the valid columns for this table
$columns = array_flip($table->getSchema()->columns());

$action = $_REQUEST['action'] ?? 'view';
switch ($action) {
	case 'submit':
		// Only allow entries whose keys are valid columns.
		$data = array_intersect_key($_POST, $columns);

		if ($data['id'] === '') {
			try {
				$entity = $table->newEntity($data);
				if ($table->save($entity) === false) {
					throw new \ErrorException('Failed to save new regex to the database');
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
				throw new \ErrorException('Failed to update the info to the database');
			}
		}

		if ($entity->hasErrors(false)) {
			foreach ($entity->getErrors() as $field => $error) {
				$errors .= "Field: $field\n";
				foreach ($error as $type => $reason) {
					$errors .=  $reason . PHP_EOL;
				}
			}
		}

		header('Location:' . WWW_TOP . '/binaryblacklist-list.php');
		break;

	case 'addtest':
		if (isset($_GET['regex'], $_GET['groupname'])) {
			$regex += [
				'groupname' => $_GET['groupname'],
				'regex' => $_GET['regex'],
				'ordinal' => '1',
				'status'    => '1'
			];
		}
		break;

	case 'view':
	default:
		// Only allow entries whose keys are valid columns.
		$data = array_intersect_key($_GET, $columns);
		if (isset($data['id'])) {
				$page->title = 'Binary Black/Whitelist Edit';
				$regex = $bin->getBlacklistByID($data['id']);
			} else {
				$page->title = 'Binary Black/Whitelist Add';
				$regex += [
					'status' => 1,
					'optype' => 1,
					'msgcol' => 1
				];
			}
		break;
}

$page->smarty->assign([
		'error'        => $errors,
		'regex'        => $regex,
		'status_ids'   => [Category::STATUS_ACTIVE, Category::STATUS_INACTIVE],
		'status_names' => ['Yes', 'No'],
		'optype_ids'   => [1, 2],
		'optype_names' => ['Black', 'White'],
		'msgcol_ids'   => [
			Binaries::BLACKLIST_FIELD_SUBJECT,
			Binaries::BLACKLIST_FIELD_FROM,
			Binaries::BLACKLIST_FIELD_MESSAGEID
		],
		'msgcol_names' => ['Subject', 'Poster', 'MessageId']
	]
);

$page->content = $page->smarty->fetch('binaryblacklist-edit.tpl');
$page->render();
