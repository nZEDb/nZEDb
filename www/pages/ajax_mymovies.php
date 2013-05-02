<?php
/*require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."/lib/TMDb.php");
require_once(WWW_DIR."/lib/movie.php");
require_once(WWW_DIR."/lib/usermovies.php");*/

require_once("/var/www/nZEDb/www/lib/site.php");
require_once("/var/www/nZEDb/www/lib/TMDb.php");
require_once("/var/www/nZEDb/www/lib/movie.php");
require_once("/var/www/nZEDb/www/lib/usermovies.php");

if (!$users->isLoggedIn())
	$page->show403();

$um = new UserMovies();


if (isset($_REQUEST["del"]))
{
	$usermovies = $um->delMovie($users->currentUserId(), $_REQUEST["del"]);
}
elseif (isset($_REQUEST["add"]))
{
	//
	// derive cats from user preferences
	//
	$cats = array();
	$cats[] = "2030";
	$cats[] = "2040";

	$m = new Movie(false);
	$mi = $m->getMovieInfo($_REQUEST["add"]);
	if (!$mi)
		$m->updateMovieInfo($_REQUEST["add"]);
		
	$usermovies = $um->addMovie($users->currentUserId(), $_REQUEST["add"], $cats);
}
else
{
	if (!isset($_REQUEST["id"]))
		$page->show404();
	
	$s = new Sites();
	$site = $s->get();
	$tmdb = new TMDb($site->tmdbkey, 'en'/*Language, make this changebale in the future.*/);
	$m = new Movie(false);
	
	if (is_numeric($_REQUEST["id"]))
	{
		$movie = $m->fetchTmdbProperties($_REQUEST["id"]);
		if($movie !== false)
		{
			$obj = array($movie);
			//print_r($obj);
		}
	}
	else
	{
		$searchm = $tmdb->searchMovie($_REQUEST["id"]);
		if($searchm !== false)
		{
			if(isset($searchm['results']))
			{
				//print_r($searchm);
				$obj = array();
				$limit = 0;
				foreach($searchm['results'] as $movie)
				{
					$limit++;
					$movieinfo = $m->fetchTmdbProperties($movie['id'], true);
					if($movieinfo !== false)
					{
						$obj[] = $movieinfo;
					}
					if ($limit > 4)
						break;
				}
				//print_r($obj);
			}
		}
	}
	$imdbids = array();
	
	if (isset($obj) && count($obj) > 0)
	{
		foreach ($obj as $movie)
		{
			if (isset($movie['title']) && isset($movie['imdb_id']))
			{
				$imdbids[] = str_replace("tt", "", $movie['imdb_id']);
			}
			else
			{
				// no results
			}
		}

		if (count($imdbids) == 0)
		{
			print "<h3 style='padding-top:30px;'>No results found</h3>";
		}
		else
		{
			$ourmovieimdbs = array();
			if (count($imdbids) > 0)
			{
				$m = new Movie();
				$allmovies = $m->getMovieInfoMultiImdb($imdbids);
				foreach ($allmovies as $ourmovie)
					if ($ourmovie["relimdb"] != "")
						$ourmovieimdbs[$ourmovie["imdbID"]] = $ourmovie["imdbID"];
			}
			
			$userimdbs = array();
			$usermovies = $um->getMovies($users->currentUserId());
			foreach ($usermovies as $umovie)
				$userimdbs[$umovie["imdbID"]] = $umovie["imdbID"];
			
			$page->smarty->assign('data', $obj);
			$page->smarty->assign('ourmovies', $ourmovieimdbs);
			$page->smarty->assign('userimdbs', $userimdbs);
			
			print $page->smarty->fetch('mymovielist.tpl');
		}
	}
}
?>
