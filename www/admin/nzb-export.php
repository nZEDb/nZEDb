<?php
require_once './config.php';




require_once nZEDb_LIB . 'Util.php';

$db = new DB();

if (empty($argc))
	$page = new AdminPage();

$rel = new Releases();

if (!empty($argc) || $page->isPostBack() )
{
	$retval = $postto = $postfrom = $group = $path = "";
	$strTerminator = "<br />";

	if (!empty($argc))
	{
		$strTerminator = "\n";
		if (isset($argv[1]))
			$path = $argv[1];
		if (isset($argv[2]))
			$postfrom = $argv[2];
		if (isset($argv[3]))
			$postto = $argv[3];
		if (isset($argv[4]))
			$group = $argv[4];
	}
	else
	{
		$strTerminator = "<br />";
		$path = $_POST["folder"];
		if (isset($_POST["postfrom"]))
			$postfrom = $_POST["postfrom"];
		if (isset($_POST["postto"]))
			$postto = $_POST["postto"];
		if (isset($_POST["group"]))
			$group = $_POST["group"];
	}

	if ($path != "")
	{
		if (substr($path, strlen($path) - 1) != '/')
			$path = $path."/";

		$releases = $rel->getForExport($postfrom, $postto, $group);
		$s = new Sites();
		$nzb = new NZB;
		$site = $s->get();
		$nzbCount = 0;
		$total = count($releases);

		foreach ($releases as $release)
		{
			$catname = safeFilename($release["catName"]);
			if (!file_exists($path.$catname))
				mkdir($path.$catname);

			ob_start();
			@readgzfile($nzb->getNZBPath($release["guid"], $site->nzbsplitlevel));
			$nzbfile = ob_get_contents();
			ob_end_clean();
			$fh = fopen($path.$catname."/".safeFilename($release["searchname"]).".nzb", 'w');
			fwrite($fh, $nzbfile);
			fclose($fh);
			$nzbCount++;

			if ($nzbCount % 10 == 0)
				echo "Exported ".$nzbCount." of ".$total." nzbs\n";
		}

		$retval.= 'Processed '.$nzbCount.' nzbs';

		if (!empty($argc))
			exit('Processed '.$nzbCount.' nzbs.');
	}
	else
		exit('No export path specified.');

	$page->smarty->assign('folder', $path);
	$page->smarty->assign('output', $retval);
	$page->smarty->assign('fromdate', $postfrom);
	$page->smarty->assign('todate', $postto);
	$page->smarty->assign('group', $group);

}
else
{
	$page->smarty->assign('fromdate', $rel->getEarliestUsenetPostDate());
	$page->smarty->assign('todate', $rel->getLatestUsenetPostDate());
}

$page->title = "Export Nzbs";
$grouplist = $rel->getReleasedGroupsForSelect(true);
$page->smarty->assign('grouplist', $grouplist);
$page->content = $page->smarty->fetch('nzb-export.tpl');
$page->render();

?>
