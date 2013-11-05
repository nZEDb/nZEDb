<?php
require_once(WWW_DIR.'lib/framework/db.php');
require_once(WWW_DIR.'lib/category.php');
require_once(WWW_DIR.'lib/site.php');
// SELECT name FROM releases r, category c WHERE c.id = r.categoryid AND c.parentid=6000


class PC
{
	function PC($echooutput=false)
	{
		$s = new Sites();
		$site = $s->get();
		$this->aniqty = (!empty($site->maxanidbprocessed)) ? $site->maxanidbprocessed : 100;
		$this->echooutput = $echooutput;
	}

	// process a group of previously unprcoessed PC Releases, as in postprocess
	public function processPCReleases($threads=1, $hours=0)
	{
		$db = new DB();
		$threads--;
		
		$results = "";
		if($hours == 0) 
		  // only select items that have not been process via an NFO or predb, and for which rageid is -1
		  $results = $db->query(sprintf('SELECT r.id, r.searchname FROM releases r, category c WHERE c.id = r.categoryid AND c.parentid=%s AND r.preid is NULL AND r.nfostatus != 1 AND rageid = -1 ORDER BY postdate DESC LIMIT %d OFFSET %d', Category::CAT_PARENT_PC, $this->aniqty, floor(($this->aniqty) * ($threads * 1.5))));		  
		else
		  // only select items that have not been process via an NFO or predb, and for which rageid is -1 and within 6 hours
		  $results = $db->query(sprintf('SELECT r.id, r.searchname FROM releases r, category c WHERE c.id = r.categoryid AND c.parentid=%s AND r.preid is NULL AND r.nfostatus != 1 AND rageid = -1 AND r.adddate > ( NOW( ) - INTERVAL 6 HOUR ) ORDER BY postdate DESC LIMIT %d OFFSET %d', Category::CAT_PARENT_PC, $this->aniqty, floor(($this->aniqty) * ($threads * 1.5))));		  

		// need to be placed within a loop
		if (count($results) > 0)
		{
			if ($this->echooutput)
				echo 'Processing '.count($results)." PC releases.\n";

			foreach ($results as $arr)
			{

				// clean up the release name to ensure we get a good chance at getting a valid filename
				$cleanFilename = $this->parseTitle($arr['searchname'], $arr['id']);
			}	// foreach
		}	// if
	}


	public function cleanFilename($cleanFilename)
	{		
		// extra cleanup to get a valid title
		// remove trailing and leading spaces from teh title
		$cleanFilename = trim($cleanFilename);
		// remove trailing -
		$cleanFilename = preg_replace('/-$/i', '', $cleanFilename);
		// replace multiple spaces with a single one
		$cleanFilename = preg_replace('/\s+/i', ' ', $cleanFilename);
		// replace tailing \d\d\d with nothing
		$cleanFilename = preg_replace('/\d\d\d+/i', '', $cleanFilename);
		// remove any . with space
		$cleanFilename = preg_replace('/\./i', ' ', $cleanFilename);
		//replace spaces between digits with .'s
		$cleanFilename = preg_replace('/(\d) (\d)/i', '$1.$2', $cleanFilename);
		// remove any remaining multiple spaces
		$cleanFilename = preg_replace('/\s+/i', ' ', $cleanFilename);

		$cleanFilename = trim($cleanFilename);

		return $cleanFilename;
	}

	// Convert the post name to the search name
	public function parseTitle($searchname, $releaseID)
	{
		$noforeign = 'Japanese|jap|German|Danish|Flemish|Dutch|French|Swe(dish|sub)|Deutsch|Norwegian';

		$searchnameOrg = $searchname;
		preg_match('/' . $noforeign . '/i', $searchname, $foreign);
	
		// extra cleanup to get a more valid title
		// extract the part of the string in quotes (normally the name if present)
		$namematch = "";
		preg_match('/".*?"/', $searchname, $namematch);

		// if "'s are  present process
		// in a quick check of 2000 anime entires this works 98.5% of the time without falling back
		if(isset($namematch[0])) 
		{
			// start by getting rid of the exterior "'s
			$searchname= $namematch[0];
			$searchname = preg_replace('/(^"|"$)/', '', $searchname);

			// remove any _'s and replace with spaces
			$searchname = preg_replace('/_/i', ' ', $searchname);
			// remove .par2
			$searchname = preg_replace('/\.par2$/i', '', $searchname);
			// remove parts
			$searchname = preg_replace('/\.part\d+.rar$/i', '', $searchname);
			// remove rara
			$searchname = preg_replace('/\.rar$/i', '', $searchname);
			// remove vol0+1 type data
			$searchname = preg_replace('/\.vol\d+\+\d+$/i', '', $searchname);
			// remove .nfo extention
			$searchname = preg_replace('/(\.nfo)$/i', '', $searchname);
			// remove any . with space
			$searchname = preg_replace('/\./i', ' ', $searchname);
			//replace spaces between digits with .'s
			$searchname = preg_replace('/(\d) (\d)/i', '$1.$2', $searchname);
			// remove any remaining multiple spaces
			$searchname = preg_replace('/\s+/i', ' ', $searchname);
		}
		else 
		{
			if ($this->echooutput)
				echo "\tFalling back to Pure REGEX method to determine name\n";

			// if no "'s were found then fall back to cleanFilename;
			$searchname =  $this->cleanFilename($searchname);
		}
		
		// if name lookup failed use teh orginal
		if($searchname == '')
			$searchname = $searchnameOrg;

		// if a forgien ad it to teh name now as a suffix, since that is helpful
		if(isset($foreign[0]))
			$searchname = $foreign[0] . ' -- ' . $searchname;

		$db = new DB();
		// default is rageid to be -1, since we will never us it and -2 is not found we can use it to determine if it is processed
		$db->queryExec(sprintf("UPDATE releases SET searchname = %s, rageid = -2 WHERE id = %d", $db->escapeString($searchname), $releaseID));
		return $searchname;		
	}
}
