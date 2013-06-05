<?php
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/releasefiles.php");
require_once(WWW_DIR."/lib/releasecomments.php");
require_once(WWW_DIR."/lib/releaseextra.php");
require_once(WWW_DIR."/lib/tvrage.php");
require_once(WWW_DIR."/lib/anidb.php");

if (!$users->isLoggedIn())
	$page->show403();

if (isset($_GET["id"]))
{
	$releases = new Releases;
	$rc = new ReleaseComments;
	$re = new ReleaseExtra;
	$tvrage = new TvRage;
	$AniDB = new AniDB;
	$data = $releases->getByGuid($_GET["id"]);

	if (!$data)
		$page->show404();

	if ($page->isPostBack())
			$rc->addComment($data["ID"], $_POST["txtAddComment"], $users->currentUserId(), $_SERVER['REMOTE_ADDR']); 
	
	$nfo = $releases->getReleaseNfo($data["ID"], false);
	$reVideo = $re->getVideo($data["ID"]);
	$reAudio = $re->getAudio($data["ID"]);
	$reSubs = $re->getSubs($data["ID"]);
	$comments = $rc->getComments($data["ID"]);
	$similars = $releases->searchSimilar($data["ID"], $data["searchname"], 6, $page->userdata["categoryexclusions"]);
	
	$rage = '';
	if ($data["rageID"] != '')
	{
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
					
				if (!empty($r['imgdata'])) {
					$seriesimg[] = $r['imgdata'];
					$seriesid[] = $r['ID'];
				}
			}
			$rage = array(
				'releasetitle' => array_shift($seriesnames), 
				'description' => array_shift($seriesdescription), 
				'country' => array_shift($seriescountry), 
				'genre' => array_shift($seriesgenre), 
				'imgdata' => array_shift($seriesimg), 
				'ID'=>array_shift($seriesid)
			);
		}
	}
	
	$anidb = '';
	$AniDBAPIArray = '';
	if ($data["anidbID"] > 0)
	{
		$AniDBAPIArray = $AniDB->getAnimeInfo($data["anidbID"]);
	}
	
	$mov = '';
	if ($data['imdbID'] != '') {
		require_once(WWW_DIR."/lib/movie.php");
		$movie = new Movie();
		$mov = $movie->getMovieInfo($data['imdbID']);
		
		if ($mov) {
			$mov['title'] = str_replace(array('/', '\\'), '', $mov['title']);
			$mov['actors'] = $movie->makeFieldLinks($mov, 'actors');
			$mov['genre'] = $movie->makeFieldLinks($mov, 'genre');
			$mov['director'] = $movie->makeFieldLinks($mov, 'director');
		}
	}
	
	$mus = '';
	if ($data['musicinfoID'] != '') {
		require_once(WWW_DIR."/lib/music.php");
		$music = new Music();
		$mus = $music->getMusicInfo($data['musicinfoID']);
	}	
	
	$con = '';
	if ($data['consoleinfoID'] != '') {
		require_once(WWW_DIR."/lib/console.php");
		$c = new Console();
		$con = $c->getConsoleInfo($data['consoleinfoID']);
	}		
	
	$boo = '';
	if ($data['bookinfoID'] != '') {
		require_once(WWW_DIR."/lib/books.php");
		$b = new Books();
		$boo = $b->getBookInfo($data['bookinfoID']);
	}
	
	$rf = new ReleaseFiles;
	$releasefiles = $rf->get($data["ID"]);
	
	$page->smarty->assign('releasefiles',$releasefiles);
	$page->smarty->assign('release',$data);
	$page->smarty->assign('reVideo',$reVideo);
	$page->smarty->assign('reAudio',$reAudio);
	$page->smarty->assign('reSubs',$reSubs);
	$page->smarty->assign('nfo',$nfo);
	$page->smarty->assign('rage',$rage);
	$page->smarty->assign('movie',$mov);
	$page->smarty->assign('anidb',$AniDBAPIArray);
	$page->smarty->assign('music',$mus);
	$page->smarty->assign('con',$con);
	$page->smarty->assign('boo',$boo);
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
