<?php
require_once(WWW_DIR.'lib/framework/db.php');
require_once(WWW_DIR.'lib/category.php');
require_once(WWW_DIR.'lib/site.php');


class XXX
{
	function XXX($echooutput=false)
	{
		$s = new Sites();
		$site = $s->get();
		$this->aniqty = (!empty($site->maxanidbprocessed)) ? $site->maxanidbprocessed : 100;
		$this->echooutput = $echooutput;
	}

	// process a group of previously unprcoessed PC Releases, as in postprocess
	public function processXXXReleases($threads=1, $hours=0)
	{
		$db = new DB();
		$threads--;
		if($hours == 0) 
		  // only select items that have not been process via an NFO or predb, and for which rageid is -1
		  $results = $db->query(sprintf('SELECT r.id, r.searchname FROM releases r, category c WHERE c.id = r.categoryid AND c.parentid=%s AND r.preid is NULL AND r.nfostatus != 1 AND rageid = -1 ORDER BY postdate DESC LIMIT %d OFFSET %d', Category::CAT_PARENT_XXX, $this->aniqty, floor(($this->aniqty) * ($threads * 1.5))));		  
		else
		  // only select items that have not been process via an NFO or predb, and for which rageid is -1 and within 6 hours
		  $results = $db->query(sprintf('SELECT r.id, r.searchname FROM releases r, category c WHERE c.id = r.categoryid AND c.parentid=%s AND r.preid is NULL AND r.nfostatus != 1 AND rageid = -1 AND r.adddate > ( NOW( ) - INTERVAL 6 HOUR ) ORDER BY postdate DESC LIMIT %d OFFSET %d', Category::CAT_PARENT_XXX, $this->aniqty, floor(($this->aniqty) * ($threads * 1.5))));		  


		// need to be placed within a loop
		if (count($results) > 0)
		{
			if ($this->echooutput)
				echo 'Processing '.count($results)." XXX releases.\n";

			foreach ($results as $arr)
			{

				// clean up the release name to ensure we get a good chance at getting a valid filename
				$cleanFilename = $this->parseTitle($arr['searchname'], $arr['id']);
			}	// foreach
		}	// if
	}


	public function cleanFilename($searchname)
	{
		$searchname = preg_replace('/^Arigatou[._ ]|\]BrollY\]|[._ ]v(er[._ ]?)?\d|Complete[._ ](?=Movie)|((HD)?DVD|B(luray|[dr])(rip)?)|Rs?\d|[xh][._ ]?264|A(C3|52)| \d+[pi]\s|[SD]ub(bed)?|Creditless/i', ' ', $searchname);

		$searchname = preg_replace('/(\[|\()(?!\d{4}\b)[^\]\)]+(\]|\))/', '', $searchname);
		$searchname = (preg_match_all('/[._ ]-[._ ]/', $searchname, $count) >= 2) ? preg_replace('/[^-]+$/i', '', $searchname) : $searchname;
		$searchname = preg_replace('/( S\d+ ?E\d+|Movie ?(\d+|[ivx]+))(.*$)/i', '${1}', $searchname);
		$searchname = preg_replace('/ ([12][890]\d{2})\b/i', ' (${1})', $searchname);
		$searchname = str_ireplace('\'', '`', $searchname);
		
		$cleanFilename = preg_replace('/ (NC)?Opening ?/i', ' OP', $searchname);
		$cleanFilename = preg_replace('/ (NC)?(Ending|Credits|Closing) ?/i', ' ED', $cleanFilename);
		$cleanFilename = preg_replace('/ (Trailer|TR(?= ?\d)) ?/i', ' T', $cleanFilename);
		$cleanFilename = preg_replace('/ (Promo|P(?= ?\d)) ?/i', ' PV', $cleanFilename);
		$cleanFilename = preg_replace('/ (Special|Extra|SP(?= ?\d)) ?(?! ?[a-z])/i', ' S', $cleanFilename);
		$cleanFilename = preg_replace('/ Vol(ume)? ?(?=\d)/i', ' Vol', $cleanFilename);

		// extra cleanup to get a valid title
		// remove trailing and leading spaces from teh title
		$cleanFilename = trim($cleanFilename);
		// remove trailing -
		$cleanFilename = preg_replace('/-$/i', '', $cleanFilename);
		// replace multiple spaces with a single one
		$cleanFilename = preg_replace('/\s+/i', ' ', $cleanFilename);
		// replace tailing \d\d\d with nothing
		$cleanFilename = preg_replace('/\d\d\d+/i', '', $cleanFilename);
		// extra cleanup to get a valid title

		$cleanFilename = trim($cleanFilename);

		return $cleanFilename;
	}

	// Convert the post name to the search name
	public function parseTitle($searchname, $releaseID)
	{
		$noforeign = 'Japanese|jap|German|Danish|Flemish|Dutch|French|Swe(dish|sub)|Deutsch|Norwegian';
		$other = 'uncensored';

		$searchnameOrg = $searchname;
		preg_match('/' . $noforeign . '/i', $searchname, $foreign);
		preg_match('/[^ -- ]' . $other . '/i', $searchname, $other);
	
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
			// remove rar
			$searchname = preg_replace('/\.rar$/i', '', $searchname);
			// remove jpg
			$searchname = preg_replace('/\.jpg$/i', '', $searchname);
			// remove vol0+1 type data
			$searchname = preg_replace('/\.vol\d+\+\d+$/i', '', $searchname);
			// remove .nfo extention
			$searchname = preg_replace('/(\.nfo)$/i', '', $searchname);
			// remove any . with space
			$searchname = preg_replace('/\./i', '$1 $2', $searchname);
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

		// fix extentions
		$searchname = preg_replace('/ (wmv|mp4|avi|mkv|flv|divx|dvx|ts|asf|swf|h264|ogv)/i', '.$1', $searchname);
			
		// if a forgien ad it to teh name now as a suffix, since that is helpful
		if(isset($foreign[0]))
			$searchname = $searchname . ' -- ' . $foreign[0];

		if(isset($other[0]))
			$searchname = $searchname . ' -- ' . $other[0];

		$db = new DB();
		// default is rageid to be -1, since we will never us it and -2 is not found we can use it to determine if it is processed
		$db->queryExec(sprintf("UPDATE releases SET searchname = %s, rageid = -2  WHERE id = %d", $db->escapeString($searchname), $releaseID));
		return $searchname;		
	}
}
