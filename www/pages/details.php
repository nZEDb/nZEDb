<?php
require_once(WWW_DIR."/lib/releases.php");

if (!$users->isLoggedIn())
	$page->show403();

if (isset($_GET["id"]))
{
	$releases = new Releases;
	$data = $releases->getByGuid($_GET["id"]);

	if (!$data)
		$page->show404();

	require_once(WWW_DIR."/lib/releasecomments.php");
	$rc = new ReleaseComments;
	if ($page->isPostBack())
			$rc->addComment($data["ID"], $_POST["txtAddComment"], $users->currentUserId(), $_SERVER['REMOTE_ADDR']);

	$nfo = $releases->getReleaseNfo($data["ID"], false);
	require_once(WWW_DIR."/lib/releaseextra.php");
	$re = new ReleaseExtra;
	$reVideo = $re->getVideo($data["ID"]);
	$reAudio = $re->getAudio($data["ID"]);
	$reSubs = $re->getSubs($data["ID"]);
	$comments = $rc->getComments($data["ID"]);
	$similars = $releases->searchSimilar($data["ID"], $data["searchname"], 6, $page->userdata["categoryexclusions"]);

	$rage = $ani = $mov = $mus = $con = $boo = '';
	if ($data["rageID"] != '')
	{
		require_once(WWW_DIR."/lib/tvrage.php");
		$tvrage = new TvRage;
		$rageinfo = $tvrage->getByRageID($data["rageID"]);
		if (count($rageinfo) > 0)
		{
			$seriesnames = $seriesdescription = $seriescountry = $seriesgenre = $seriesimg = $seriesid = array();
			foreach($rageinfo as $r)
			{
				$seriesnames[] = $r['releasetitle'];
				if (!empty($r['description']))
					$seriesdescription[] = $r['description'];

				if (!empty($r['country']))
					$seriescountry[] = $r['country'];

				if (!empty($r['genre']))
					$seriesgenre[] = $r['genre'];

				if (!empty($r['imgdata']))
				{
					$seriesimg[] = $r['imgdata'];
					$seriesid[] = $r['ID'];
				}
			}
			$rage = array('releasetitle' => array_shift($seriesnames),
						'description' => array_shift($seriesdescription),
						'country' => array_shift($seriescountry),
						'genre' => array_shift($seriesgenre),
						'imgdata' => array_shift($seriesimg),
						'ID'=>array_shift($seriesid)
						);
		}
	}

	if ($data["anidbID"] > 0)
	{
		require_once(WWW_DIR."/lib/anidb.php");
		$AniDB = new AniDB;
		$ani = $AniDB->getAnimeInfo($data["anidbID"]);
	}

	if ($data['imdbID'] != '')
	{
		require_once(WWW_DIR."/lib/movie.php");
		$movie = new Movie();
		$mov = $movie->getMovieInfo($data['imdbID']);

		if ($mov)
		{
			$mov['title'] = str_replace(array('/', '\\'), '', $mov['title']);
			$mov['actors'] = $movie->makeFieldLinks($mov, 'actors');
			$mov['genre'] = $movie->makeFieldLinks($mov, 'genre');
			$mov['director'] = $movie->makeFieldLinks($mov, 'director');
		}
	}

	if ($data['musicinfoID'] != '')
	{
		require_once(WWW_DIR."/lib/music.php");
		$music = new Music();
		$mus = $music->getMusicInfo($data['musicinfoID']);
	}

	if ($data['consoleinfoID'] != '')
	{
		require_once(WWW_DIR."/lib/console.php");
		$c = new Console();
		$con = $c->getConsoleInfo($data['consoleinfoID']);
	}

	if ($data['bookinfoID'] != '')
	{
		require_once(WWW_DIR."/lib/books.php");
		$b = new Books();
		$boo = $b->getBookInfo($data['bookinfoID']);
	}

	require_once(WWW_DIR."/lib/releasefiles.php");
	$rf = new ReleaseFiles;
	$releasefiles = $rf->get($data["ID"]);

	require_once(WWW_DIR."/lib/predb.php");
	$predb = new Predb();
	$pre = $predb->getForRelease($data['ID']);

	$page->smarty->assign('releasefiles',$releasefiles);
	$page->smarty->assign('release',$data);
	$page->smarty->assign('reVideo',$reVideo);
	$page->smarty->assign('reAudio',$reAudio);
	$page->smarty->assign('reSubs',$reSubs);
	$page->smarty->assign('nfo',$nfo);
	$page->smarty->assign('rage',$rage);
	$page->smarty->assign('movie',$mov);
	$page->smarty->assign('anidb',$ani);
	$page->smarty->assign('music',$mus);
	$page->smarty->assign('con',$con);
	$page->smarty->assign('boo',$boo);
	$page->smarty->assign('pre',$pre);
	$page->smarty->assign('comments',$comments);
	$page->smarty->assign('similars',$similars);
	$page->smarty->assign('searchname',$releases->getSimilarName($data['searchname']));

	$page->meta_title = "View NZB";
	$page->meta_keywords = "view,nzb,description,details";
	$page->meta_description = "View NZB for".$data["searchname"] ;

	$page->content = $page->smarty->fetch('viewnzb.tpl');
	$page->render();
}

?>
