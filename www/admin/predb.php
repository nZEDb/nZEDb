<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use app\models\Predb;

$page = new AdminPage();
$page->title = "Browse PreDb";
$page->meta_title = "View PreDb info";
$page->meta_keywords = "view,predb,info,description,details";
$page->meta_description = "View PreDb info";

$count = Predb::findRangeCount($_REQUEST['presearch']);
$lastsearch = isset($_REQUEST['presearch']) ? $_REQUEST['presearch'] : '';

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'lastsearch'       => $lastsearch,
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'            => $page->smarty->fetch("paginate.tpl"),
		'pagerquerybase'   => WWW_TOP . "/predb.php?" . $lastsearch . 'page=',
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
		'results'          => Predb::findRange($pageno, ITEMS_PER_PAGE, $_REQUEST['presearch'])->to('array'),
		'tz'               => \lithium\data\Connections::config()['default']['object']->timezone(),
	]
);

$page->content = $page->smarty->fetch('predb.tpl');
$page->render();

?>
