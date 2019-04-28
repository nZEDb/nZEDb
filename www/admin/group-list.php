<?php
require_once './config.php';

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

$page = new AdminPage();
$page->title = $page->meta_title = 'Group List';

$pageno = $_REQUEST['pageno'] ?? 1;

$groupname = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname'])) ? $_REQUEST['groupname'] : '';

$conditions = empty($groupname) ? [] : ['Groups.name LIKE' => "%$groupname%"];

$groups = TableRegistry::getTableLocator()->get('Groups');
$query = $groups->find('all', [
	'conditions' => $conditions,
]);
$count = $query->count();

$query = $groups->find('all')
	->select([
		'id',
		'name',
		'backfill_target',
		'first_record',
		'first_record_postdate',
		'last_record',
		'last_record_postdate',
		'last_updated',
		'minfilestoformrelease',
		'minsizetoformrelease',
		'active',
		'backfill',
		'description',
	])
	->where($conditions)
	->order('name')
	->limit(ITEMS_PER_PAGE)
	->page($pageno);

$groupsearch = empty($_REQUEST['groupname']) ? '' : '&amp;groupname=' . $_REQUEST['groupname'];

$page->smarty->assign(
	[
		'groupname'        => $groupname,
		'pagecurrent'      => (int)$pageno,
		'pagerlast'        => (int)($count / ITEMS_PER_PAGE) + 1,
		'pagerquerybase'   => WWW_TOP . '/group-list.php?pageno=',
		'pagerquerysuffix' => $groupsearch,
		'pagertotalitems'  => $count,
		'results'          => $query->all(),
		'tz'               => ConnectionManager::get('default')->config()['timezone'],
	]
);

// Pager must be set outside the main assignment, so it can receive the scope of those variables.
$page->smarty->assign('pager', $page->smarty->fetch('paginate.tpl'));

$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();

?>
