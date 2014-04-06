<?php
/* This script is designed to gather all show data from anidb and add it to the anidb table for nZEDb, as part of this process we need the number of PI queries that can be executed max and whether or not we want debuging the first argument if unset will try to do the entire list (a good way to get banned), the second option can be blank or true for debugging.
 * IF you are using this script then then you also want to edit anidb.php in www/lib and locate "604800" and replace it with 1204400, this will make sure it never tries to connect to anidb as this will fail
 */
 
 require dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\DB;
use nzedb\utility;

class AniDBstandAlone
{
	const CLIENTVER = 1;

	function __construct($echooutput=false)
	{
		$s = new Sites();
		$this->site = $s->get();
		$this->aniqty = (!empty($this->site->maxanidbprocessed)) ? $this->site->maxanidbprocessed : 100;
		$this->echooutput = $echooutput;
		$this->imgSavePath = nZEDb_COVERS . 'anime' . DS;
		$this->APIKEY = $this->site->anidbkey;
		$this->db = new DB();
	}

	// get the titles list this is done only once a week
	public function animetitlesUpdate()
	{
		$db = $this->db;
		if ($this->APIKEY == '')
		{
			echo "You need an API key from anidb.net to use this\n";
			return;
		}

		$lastUpdate = $db->queryOneRow('SELECT unixtime as utime FROM animetitles LIMIT 1');
		if (isset($lastUpdate['utime']) && (time() - $lastUpdate['utime']) < 604800)
		{
			if ($this->echooutput)
				echo "Last update occured less that 7 days ago, skiping update\n";
			return;
		}

		if ($this->echooutput)
			echo "Updating animetitles.\n";

		$zh = gzopen('http://anidb.net/api/anime-titles.dat.gz', 'r');

		preg_match_all('/(\d+)\|\d\|.+\|(.+)/', gzread($zh, '10000000'), $animetitles);
		if (!$animetitles)
			return false;

		$db->queryExec('DELETE FROM animetitles WHERE anidbid IS NOT NULL');

		if ($this->echooutput)
			echo "Total of ".count($animetitles[1])." titles to add\n";

		for ($i = 0; $i < count($animetitles[1]); $i++)
		{
			$db->queryInsert(sprintf('INSERT INTO animetitles (anidbid, title, unixtime) VALUES (%d, %s, %d)', $animetitles[1][$i], $db->escapeString(html_entity_decode($animetitles[2][$i], ENT_QUOTES, 'UTF-8')), time()));
			if ($i % 2500 == 0 && $this->echooutput)
				echo "Completed Processing ", $i, " titles\n";
		}

		gzclose($zh);

		if ($this->echooutput)
			echo "Completed animetitles update.\n\n";
	}
// ===================================================================================
	public function MygetAniDBInfo($exitcount)
	{
		$db = $this->db;
		$ri = new ReleaseImage();
		
		$animetitles = $db->query('SELECT DISTINCT anidbid FROM animetitles');
		echo 'Total of '.count($animetitles)." distinct titles present in animetitles\n";
	
		$anidbtitles = $db->query('SELECT DISTINCT anidbid FROM anidb');
		echo 'Total of '.count($anidbtitles)." distinct titles present in anidb\n";
	
// useless but just keep it for the record
//		$anidbjointitles = $db->query('SELECT * FROM animetitles INNER JOIN anidb ON animetitles.anidbid = anidb.anidbid');
//		echo 'Total of '.count($anidbjointitles)." titles in both anidb and animetitles\n";
		
// useless but just keep it for the record
//		$anidbmissingtitles = $db->query('SELECT * FROM animetitles WHERE anidbid NOT IN (SELECT animetitles.anidbid FROM animetitles INNER JOIN anidb ON animetitles.anidbid = anidb.anidbid)');
//		echo 'Total of '.count($anidbmissingtitles)." missing titles in anidb table\n";

// used in stage 0:
		$anidremoved = $db->query('SELECT anidbid FROM anidb WHERE anidbid NOT IN (SELECT animetitles.anidbid FROM animetitles INNER JOIN anidb ON animetitles.anidbid = anidb.anidbid)');
		echo 'Total of '.count($anidremoved)." anime titles no longer in animetitles. Will be removed from anidb. \n";

// used in stage 1:
		$anidbmissingdistincttitles = $db->query('SELECT DISTINCT anidbid FROM animetitles WHERE anidbid NOT IN (SELECT animetitles.anidbid FROM animetitles INNER JOIN anidb ON animetitles.anidbid = anidb.anidbid)');
		echo 'Total of '.count($anidbmissingdistincttitles)." missing distinct titles in anidb table\n";

// anidb is always distinct!
// select running and not updated for more than 7 days, oldest first
// used in stage 2:
		$anidbrunningtitles = $db->query('SELECT anidbid FROM anidb WHERE (startdate < CURDATE() AND (enddate > CURDATE() OR enddate IS NULL)) AND (unixtime < UNIX_TIMESTAMP(NOW()- INTERVAL 7 DAY)) ORDER BY unixtime');
		echo 'Total of '.count($anidbrunningtitles)." running anime titles in anidb table not updated for 7 day's \n";

// used in stage 3:
		$anidboldtitles = $db->query('SELECT anidbid FROM anidb WHERE (unixtime < UNIX_TIMESTAMP(NOW()- INTERVAL 60 DAY)) ORDER BY unixtime');
		echo 'Total of '.count($anidboldtitles)." anime titles in anidb table not updated for 60 day's \n";

				
// debug. Show the data for 20 sec before starting
		echo "Starting in 20 sec...\n";
		sleep(20);
// end debug
		
		// now do this list:
// 0) remove removed anidbid's from anidb nnot in animetitles, as these can't be updated
// 1) insert missing titles until exitcount reached
// 2) update running shows until exitcount reached		
// 3) update show data older than xxx day's until exitcount reached		
// todo: what to do with anidb.anidbid no longer available in animetitles.anidbid?? ( I have 6 so far)

// running series:
// anidb.startdate NULL AND enddate NULL =>> ignore?? (why?) Can only be updated in stage 3!!!!
// anidb.startdate > CURDATE(); // start date in the future thus it is not in progress as it has not started yet ==> ignore
// anidb.startdate < CURDATE() AND (enddate IS NULL OR enddate > CURDATE()) => running show without enddate or date in future		

		// loop counter (for ALL stages, so don't reset between stage's!)
		$i = 0;
		
// Stage 0: remove anidbid no longer in animetitles
		foreach($anidremoved as $value)
		{
			$anidbid = (int)$value['anidbid'];

			if ($this->echooutput)
				echo 'Removing AniDB ID '.$anidbid."\n";

				$this->deleteTitle($anidbid);

				$image_file = $this->imgSavePath . $anidbid;

					// if the image is present we need to remove it
					if (!file_exists($image_file) )
					{
					}
					
		} // end stage 0 foreach($anidremoved as $value)
		
		
// Stage 1: insert missing:
		// now add and update shows as needed
		// TODO: THIS SHOULD BE A FUNCTION ALSO USED IN STEP 2 AND 3!?!
		foreach($anidbmissingdistincttitles as $value)
		{
			$anidbid = (int)$value['anidbid'];

			if ($this->echooutput)
				echo 'Adding AniDB ID '.$anidbid."\n";

				// actually get the information on this anime from anidb
				$AniDBAPIArray = $this->AniDBAPI($anidbid);

				// if it is false we can simply exit
				if ($AniDBAPIArray['banned'])
				{
					if ($this->echooutput)
						echo "AniDB Banned, import will fail, please wait 24 hours before retrying\n";
					return;
				}

				// increment i on a API access
				$i++;

				$this->addTitle($AniDBAPIArray);

				if ($AniDBAPIArray['picture'])
				{
					// save the image to the covers page
					$ri->saveImage($AniDBAPIArray['anidbid'], 'http://img7.anidb.net/pics/anime/'.$AniDBAPIArray['picture'], $this->imgSavePath);
				}

			// update how many we have done of the total to do in this session
			if ($i != 0 && $this->echooutput)
				echo 'Processed '.$i." anidb entries of a total possible of $exitcount for this session\n";

			// every 10 records sleep for 4 minutes before continuing
			if ($i % 10 == 0 && $i != 0)
				{
				$sleeptime=180 + rand(30, 90);

					if ($this->echooutput)
						echo "Start waitloop for ".$sleeptime." sec to prevent banning.\n";

				sleep($sleeptime);
				}

			// using exitcount if this number of API calls is reached exit
			if ($i >= $exitcount)
				return;

		} // end 		foreach($anidbmissingdistincttitles as $value)
// end stage 1

// Stage 2: update running series in anidb
//  as we used query before new series were added, we only update series already existing in db
		foreach($anidbrunningtitles as $value)
		{
			$anidbid = (int)$value['anidbid'];

			if ($this->echooutput)
				echo 'Updating AniDB ID '.$anidbid."\n";

				// actually get the information on this anime from anidb
				$AniDBAPIArrayNew = $this->AniDBAPI($anidbid);

				// if it is false we can simply exit
				if ($AniDBAPIArrayNew['banned'])
				{
					if ($this->echooutput)
						echo "AniDB Banned, import will fail, please wait 24 hours before retrying\n";
					return;
				}

				// increment i on a API access
				$i++;

					// update the stored information with updated data
					$this->updateTitle($AniDBAPIArrayNew['anidbid'], $AniDBAPIArrayNew['title'],
						$AniDBAPIArrayNew['type'],
						$AniDBAPIArrayNew['startdate'], $AniDBAPIArrayNew['enddate'],
						$AniDBAPIArrayNew['related'], $AniDBAPIArrayNew['creators'],
						$AniDBAPIArrayNew['description'], $AniDBAPIArrayNew['rating'],
						$AniDBAPIArrayNew['categories'], $AniDBAPIArrayNew['characters'],
						$AniDBAPIArrayNew['epnos'], $AniDBAPIArrayNew['airdates'],
						$AniDBAPIArrayNew['episodetitles']);

					$image_file = $this->imgSavePath . $anidbid;

					// if the image is present we do not need to replace it
					if (!file_exists($image_file) )
					{
						if ($AniDBAPIArrayNew['picture'])
						{
							// save the image to the covers page
							$ri->saveImage($AniDBAPIArrayNew['anidbid'],
								'http://img7.anidb.net/pics/anime/'.$AniDBAPIArrayNew['picture'], $this->imgSavePath);
						}
					}
					
			// update how many we have done of the total to do in this session
			if ($i != 0 && $this->echooutput)
				echo 'Processed '.$i." anidb entries of a total possible of $exitcount for this session\n";

			// every 10 records sleep for 4 minutes before continuing
			if ($i % 10 == 0 && $i != 0)
				{
				$sleeptime=180 + rand(30, 90);

					if ($this->echooutput)
						echo "Start waitloop for ".$sleeptime." sec to prevent banning.\n";

				sleep($sleeptime);
				}

			// using exitcount if this number of API calls is reached exit
			if ($i >= $exitcount)
				return;

		} // end stage 2		foreach($anidbrunningtitles as $value)


// now for stage 3: update rest of records not updated for a loooooong time
// same as step2: but other for loop (so we need to make a proper function out of this?!)
		foreach($anidboldtitles as $value)
		{
			$anidbid = (int)$value['anidbid'];

			if ($this->echooutput)
				echo 'Updating AniDB ID '.$anidbid."\n";

				// actually get the information on this anime from anidb
				$AniDBAPIArrayNew = $this->AniDBAPI($anidbid);

				// if it is false we can simply exit
				if ($AniDBAPIArrayNew['banned'])
				{
					if ($this->echooutput)
						echo "AniDB Banned, import will fail, please wait 24 hours before retrying\n";
					return;
				}

				// increment i on a API access
				$i++;

					// update the stored information with updated data
					$this->updateTitle($AniDBAPIArrayNew['anidbid'], $AniDBAPIArrayNew['title'],
						$AniDBAPIArrayNew['type'],
						$AniDBAPIArrayNew['startdate'], $AniDBAPIArrayNew['enddate'],
						$AniDBAPIArrayNew['related'], $AniDBAPIArrayNew['creators'],
						$AniDBAPIArrayNew['description'], $AniDBAPIArrayNew['rating'],
						$AniDBAPIArrayNew['categories'], $AniDBAPIArrayNew['characters'],
						$AniDBAPIArrayNew['epnos'], $AniDBAPIArrayNew['airdates'],
						$AniDBAPIArrayNew['episodetitles']);

					$image_file = $this->imgSavePath . $anidbid;

					// if the image is present we do not need to replace it
					if (!file_exists($image_file) )
					{
						if ($AniDBAPIArrayNew['picture'])
						{
							// save the image to the covers page
							$ri->saveImage($AniDBAPIArrayNew['anidbid'],
								'http://img7.anidb.net/pics/anime/'.$AniDBAPIArrayNew['picture'], $this->imgSavePath);
						}
					}
					
			// update how many we have done of the total to do in this session
			if ($i != 0 && $this->echooutput)
				echo 'Processed '.$i." anidb entries of a total possible of $exitcount for this session\n";

			// every 10 records sleep for 4 minutes before continuing
			if ($i % 10 == 0 && $i != 0)
				{
				$sleeptime=180 + rand(30, 90);

					if ($this->echooutput)
						echo "Start waitloop for ".$sleeptime." sec to prevent banning.\n";

				sleep($sleeptime);
				}

			// using exitcount if this number of API calls is reached exit
			if ($i >= $exitcount)
				return;

		} // end stage 3		foreach($anidboldtitles as $value)

// debug test for exitcount
echo "Still in function MygetAniDBInfo($exitcount) \n";
// debug

	} // end public function MygetAniDBInfo($exitcount)
// ===================================================================================

	
	// update the actual anidb info list as needed
	public function getAniDBInfo($exitcount)
	{
		$db = $this->db;
		$ri = new ReleaseImage();

		// get an unordered list as anidb dislikes order sets
		$animetitles = $db->query('SELECT DISTINCT anidbid FROM animetitles');

		if ($exitcount == 0)
			$exitcount = 120;

		if ($this->echooutput)
			echo 'Processing '.$exitcount." anidb entries for this session\n";

		if ($this->echooutput)
			echo 'Total of '.count($animetitles)." titles present\n";

		// loop counter
		$i = 0;

		// remove anything that has not been updated in 60 days as we can assume this are too old to be valid or in progress after 7
		foreach ($animetitles as $value)
		{
			$anidbid = (int)$value['anidbid'];

			// get the information on the anime title as stored in the DB
			$AniDBAPIArrayOld = $this->getAnimeInfo($anidbid);
			if ($AniDBAPIArrayOld == false)
			{
				// entry is not present, report it in debug, but do nothing else with it
				if ($this->echooutput)
					echo 'AnimeTitle Record '.$anidbid." is not present in anidb table yet, skipping\n";
			}
			else
			{
				if ($this->echooutput)
					echo 'AnimeTitle Record '.$anidbid." is already present in anidb table, processing for possible removal.\n";

				$AniDBAPIArrayOld['AnimeInProgress'] = false;

				// get start and end dates in unix time
				$anidbstartdate = strtotime($AniDBAPIArrayOld['startdate']);
				$anidbenddate = strtotime($AniDBAPIArrayOld['enddate']);

				// if the current time is less than the endtime or the endtime is 0 then the show likely has not finsihed.
				if ($anidbstartdate == "" && $anidbenddate == "")
				{
					// ignore as there is no start date or end date listed, assume not in progress
					$AniDBAPIArrayOld['AnimeInProgress'] = false;
				}
				else if ($anidbstartdate > time())
				{
					// start date in the future thus it is not in progress as it has not started yet
					$AniDBAPIArrayOld['AnimeInProgress'] = false;
				}
				else if ($anidbstartdate != "" && $anidbenddate == "")
				{
					// in this case anime may not have a end date and should be considered in progress as teh end date is just unknown
					$AniDBAPIArrayOld['AnimeInProgress'] = true;
				}
				else if ($anidbenddate > time())
				{
					// anime has end date in the future, is considered in progress
					$AniDBAPIArrayOld['AnimeInProgress'] = true;
				}

				// determine if we need to remove the record from the list
				// first if the record is over 60 days old replace it no matter what
				// second if in progress and the record is over 7 days old then replace it,since new information is likely available
// no removing!!!
//				if (($AniDBAPIArrayOld && (time() - $AniDBAPIArrayOld['unixtime']) > 5184000) || ($AniDBAPIArrayOld['AnimeInProgress'] == true && (time() - $AniDBAPIArrayOld['unixtime']) > 604800))
//				{
//					if ($this->echooutput)
//						echo 'Removing OLD DB record '.$anidbid."\n";
//
					// this means in teh next section we only need to deal with new entries
//					$this->deleteTitle($anidbid);
//				}
			} 	// else defined
		}

		// now add and update shows as needed
		foreach($animetitles as $value)
		{
			$anidbid = (int)$value['anidbid'];

			if ($this->echooutput)
				echo 'Testing AniDB ID '.$anidbid."\n";

			$exists = $db->queryOneRow(sprintf('SELECT COUNT(*) as num FROM `anidb` WHERE `anidbid` = %d', $anidbid));

			if ( (int)$exists['num'] == 0 )
			{
				if ($this->echooutput)
					echo 'Adding AniDB ID '.$anidbid."\n";

				// actually get the information on this anime from anidb
				$AniDBAPIArray = $this->AniDBAPI($anidbid);

				// if it is false we can simply exit
				if ($AniDBAPIArray['banned'])
				{
					if ($this->echooutput)
						echo "AniDB Banned, import will fail, please wait 24 hours before retrying\n";
					return;
				}

				// increment i on a API access
				$i++;

				$this->addTitle($AniDBAPIArray);

				if ($AniDBAPIArray['picture'])
				{
					// save the image to the covers page
					$ri->saveImage($AniDBAPIArray['anidbid'], 'http://img7.anidb.net/pics/anime/'.$AniDBAPIArray['picture'], $this->imgSavePath);
				}
			}	// if new (AKA not present)

/* Holding on to this in case we want it again as it has some uses, but currently we mange this in the first foreach statement, so there is no need for it any longer
			else
			{
				if ($this->echooutput)
					echo "\tAniDB ", $anidbid, " already exists, seeing if update is needed\n";

				// get the information on the anime title as stored in the DB
				$AniDBAPIArrayOld = $this->getAnimeInfo($anidbid);

				// check the last update time is more than a 21 days old
				$lastUpdate = ((isset($AniDBAPIArrayOld['unixtime']) && (time() - $AniDBAPIArrayOld['unixtime']) > 1814400));

				// if it's been long enough do another update
				if ($lastUpdate)
				{
					if ($this->echooutput)
						echo "\t\tAniDB ", $anidbid, " requires a update check\n";

					// actually get the information on this anime from anidb
					$AniDBAPIArrayNew = $this->AniDBAPI($anidbid);

					// if it is false we can simply exit
					if ($AniDBAPIArrayNew['banned'])
					{
						echo "\tAniDB Banned, import will fail, please wait 24 hours before retrying\n";
						return;
					}

					// increment i on a API access
					$i++;


					// update the stored information with updated data
					$this->updateTitle($AniDBAPIArrayNew['anidbid'], $AniDBAPIArrayNew['title'],
						$AniDBAPIArrayNew['type'],
						$AniDBAPIArrayNew['startdate'], $AniDBAPIArrayNew['enddate'],
						$AniDBAPIArrayNew['related'], $AniDBAPIArrayNew['creators'],
						$AniDBAPIArrayNew['description'], $AniDBAPIArrayNew['rating'],
						$AniDBAPIArrayNew['categories'], $AniDBAPIArrayNew['characters'],
						$AniDBAPIArrayNew['epnos'], $AniDBAPIArrayNew['airdates'],
						$AniDBAPIArrayNew['episodetitles']);

					$image_file = $this->imgSavePath . $anidbid;

					// if the image is present we do not need to replace it
					if (!file_exists($image_file) )
					{
						if ($AniDBAPIArrayNew['picture'])
						{
							// save the image to the covers page
							$ri->saveImage($AniDBAPIArrayNew['anidbid'],
								'http://img7.anidb.net/pics/anime/'.$AniDBAPIArrayNew['picture'], $this->imgSavePath);
						}
					}
				}
				else
				{
					if ($this->echooutput)
						echo "\t\tAniDB ", $anidbid, " no update required existiung record is under 21 days old\n";
				}
			}
Holding on to this in case we want it again as it has some uses, but currently we mange this in the first foreach statement, so there is no need for it any longer */

			// update how many we have done of the total to do in this session
			if ($i != 0 && $this->echooutput)
				echo 'Processed '.$i." anidb entries of a total possible of $exitcount for this session\n";

			// every 10 records sleep for 4 minutes before continuing
			if ($i % 10 == 0 && $i != 0)
				{
				$sleeptime=180 + rand(30, 90);

					if ($this->echooutput)
						echo "Start waitloop for ".$sleeptime." sec to prevent banning.\n";

				sleep($sleeptime);
				}

			// using exitcount if this number of API calls is reached exit
			if ($i >= $exitcount)
				return;

		}	// foreach
	}


	public function addTitle($AniDBAPIArray)
	{
		$db = $this->db;

/*
		if ($this->echooutput)
                        echo sprintf("INSERT INTO anidb VALUES ('', %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d)", $AniDBAPIArray['anidbid'], $db->escapeString($AniDBAPIArray['title']), $db->escapeString($AniDBAPIArray['type']),(empty($AniDBAPIArray['startdate']) ? 'null' : $db->escapeString($AniDBAPIArray['startdate'])), (empty($AniDBAPIArray['enddate']) ? 'null' : $db->escapeString($AniDBAPIArray['enddate'])), $db->escapeString($AniDBAPIArray['related']), $db->escapeString($AniDBAPIArray['creators']), $db->escapeString($AniDBAPIArray['description']), $db->escapeString($AniDBAPIArray['rating']), $db->escapeString($AniDBAPIArray['picture']), $db->escapeString($AniDBAPIArray['categories']), $db->escapeString($AniDBAPIArray['characters']), $db->escapeString($AniDBAPIArray['epnos']), $db->escapeString($AniDBAPIArray['airdates']), $db->escapeString($AniDBAPIArray['episodetitles']), time());
*/
		// ad missing imdb and tvid id's remove the old id column
                $db->queryInsert(sprintf("INSERT INTO anidb VALUES (%d, 0, 0, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d)", $AniDBAPIArray['anidbid'], $db->escapeString($AniDBAPIArray['title']), $db->escapeString($AniDBAPIArray['type']), (empty($AniDBAPIArray['startdate']) ? 'null' : $db->escapeString($AniDBAPIArray['startdate'])), (empty($AniDBAPIArray['enddate']) ? 'null' : $db->escapeString($AniDBAPIArray['enddate'])), $db->escapeString($AniDBAPIArray['related']), $db->escapeString($AniDBAPIArray['creators']), $db->escapeString($AniDBAPIArray['description']), $db->escapeString($AniDBAPIArray['rating']), $db->escapeString($AniDBAPIArray['picture']), $db->escapeString($AniDBAPIArray['categories']), $db->escapeString($AniDBAPIArray['characters']), $db->escapeString($AniDBAPIArray['epnos']), $db->escapeString($AniDBAPIArray['airdates']), $db->escapeString($AniDBAPIArray['episodetitles']), time()));
	}


	public function updateTitle($anidbID, $title, $type, $startdate, $enddate, $related, $creators, $description, $rating, $categories, $characters, $epnos, $airdates, $episodetitles)
	{
		$db = $this->db;

/*
		if ($this->echooutput)
                        echo sprintf('UPDATE anidb SET title = %s, type = %s, startdate = %s, enddate = %s, related = %s, creators = %s, description = %s, rating = %s, categories = %s, characters = %s, epnos = %s, airdates = %s, episodetitles = %s, unixtime = %d WHERE anidbid = %d', $db->escapeString($title), $db->escapeString($type), (empty($AniDBAPIArray['startdate']) ? 'null' : $db->escapeString($AniDBAPIArray['startdate'])), (empty($AniDBAPIArray['enddate']) ? 'null' : $db->escapeString($AniDBAPIArray['enddate'])), $db->escapeString($related), $db->escapeString($creators), $db->escapeString($description), $db->escapeString($rating), $db->escapeString($categories), $db->escapeString($characters), $db->escapeString($epnos), $db->escapeString($airdates), $db->escapeString($episodetitles), time(), $anidbID), "\n";
*/

                $db->queryExec(sprintf('UPDATE anidb SET title = %s, type = %s, startdate = %s, enddate = %s, related = %s, creators = %s, description = %s, rating = %s, categories = %s, characters = %s, epnos = %s, airdates = %s, episodetitles = %s, unixtime = %d WHERE anidbid = %d', $db->escapeString($title), $db->escapeString($type), (empty($AniDBAPIArray['startdate']) ? 'null' : $db->escapeString($AniDBAPIArray['startdate'])), (empty($AniDBAPIArray['enddate']) ? 'null' : $db->escapeString($AniDBAPIArray['enddate'])), $db->escapeString($related), $db->escapeString($creators), $db->escapeString($description), $db->escapeString($rating), $db->escapeString($categories), $db->escapeString($characters), $db->escapeString($epnos), $db->escapeString($airdates), $db->escapeString($episodetitles), time(), $anidbID));
	}


	public function deleteTitle($anidbID)
	{
		$db = $this->db;

/*
			if ($this->echooutput)
				echo sprintf('DELETE FROM anidb WHERE anidbid = %d', $anidbID), "\n";
*/

		$db->queryExec(sprintf('DELETE FROM anidb WHERE anidbid = %d', $anidbID));
	}


	public function getAnimeInfo($anidbID)
	{

		$db = $this->db;
		$animeInfo = $db->query(sprintf('SELECT * FROM anidb WHERE anidbid = %d', $anidbID));

		return isset($animeInfo[0]) ? $animeInfo[0] : false;
	}


	public function AniDBAPI($anidbID)
	{
		$ch = curl_init('http://api.anidb.net:9001/httpapi?request=anime&client='.$this->APIKEY.'&clientver='.self::CLIENTVER.'&protover=1&aid='.$anidbID);
		if ($this->echooutput)
			echo 'http://api.anidb.net:9001/httpapi?request=anime&client='.$this->APIKEY.'&clientver='.self::CLIENTVER.'&protover=1&aid='.$anidbID."\n";

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

		$apiresponse = curl_exec($ch);

		if ($this->echooutput)
			echo "Response: '".$apiresponse."'\n";

		if (!$apiresponse)
			return false;
		curl_close($ch);

		//TODO: SimpleXML - maybe not.

		$AniDBAPIArray['anidbid'] = $anidbID;

		// if we are banned simply return false
		if (preg_match("/\<error\>Banned\<\/error\>/",$apiresponse))
		{
			$AniDBAPIArray['banned'] = true;
			return $AniDBAPIArray;
		}
		else
			$AniDBAPIArray['banned'] = false;

		preg_match_all('/<title xml:lang="x-jat" type="(?:official|main)">(.+)<\/title>/i', $apiresponse, $title);
		$AniDBAPIArray['title'] = isset($title[1][0]) ? $title[1][0] : '';

		preg_match_all('/<(type|(?:start|end)date)>(.+)<\/\1>/i', $apiresponse, $type_startenddate);
		$AniDBAPIArray['type'] = isset($type_startenddate[2][0]) ? $type_startenddate[2][0] : '';

                // new checks for correct start and enddate
                // Warning: missing date info is added to januari and day 01 (2008 -> 2008-01-01)
                if (isset($type_startenddate[2][1]))
                {
                        if (($timestamp = strtotime($type_startenddate[2][1])) === false)
                        {
                        // Timestamp not good->make it null";
//                                echo "Null date ".$type_startenddate[2][1]."\n";
                                $AniDBAPIArray['startdate']="";
                        }
                // Startdate valid for php, convert in case only year or month is given to sql date
//                        echo "Convert time. Org one: ".$type_startenddate[2][1]."\n";
                        $AniDBAPIArray['startdate'] = date('Y-m-d', strtotime($type_startenddate[2][1]));
                }
                else
                {
                        $AniDBAPIArray['startdate'] = "";
//                        echo "Null date ".$type_startenddate[2][1]."\n";
                }

                if (isset($type_startenddate[2][2]))
                {
                        if (($timestamp = strtotime($type_startenddate[2][2])) === false)
                        {
                                // Timestamp not good->make it null";
                                echo "Null date ".$type_startenddate[2][2]."\n";
                                $AniDBAPIArray['enddate']="";
                        }
                        // Startdate valid for php, convert in case only year or month is given to sql date
//                        echo "Convert time. Org one: ".$type_startenddate[2][2]."\n";
                        $AniDBAPIArray['enddate'] = date('Y-m-d', strtotime($type_startenddate[2][2]));
                }
                else
                {
//                        echo "Null date ".$type_startenddate[2][2]."\n";
                        $AniDBAPIArray['enddate'] = "";
                }

		preg_match_all('/<anime id="\d+" type=".+">([^<]+)<\/anime>/is', $apiresponse, $related);
		$AniDBAPIArray['related'] = isset($related[1]) ? implode($related[1], '|') : '';

		preg_match_all('/<name id="\d+" type=".+">([^<]+)<\/name>/is', $apiresponse, $creators);
		$AniDBAPIArray['creators'] = isset($creators[1]) ? implode($creators[1], '|') : '';

		preg_match('/<description>([^<]+)<\/description>/is', $apiresponse, $description);
		$AniDBAPIArray['description'] = isset($description[1]) ? $description[1] : '';

		preg_match('/<permanent count="\d+">(.+)<\/permanent>/i', $apiresponse, $rating);
		$AniDBAPIArray['rating'] = isset($rating[1]) ? $rating[1] : '';

		preg_match('/<picture>(.+)<\/picture>/i', $apiresponse, $picture);
		$AniDBAPIArray['picture'] = isset($picture[1]) ? $picture[1] : '';

		preg_match_all('/<category id="\d+" parentid="\d+" hentai="(?:true|false)" weight="\d+">\s+<name>([^<]+)<\/name>/is', $apiresponse, $categories);
		$AniDBAPIArray['categories'] = isset($categories[1]) ? implode($categories[1], '|') : '';

		preg_match_all('/<character id="\d+" type=".+" update="\d{4}-\d{2}-\d{2}">\s+<name>([^<]+)<\/name>/is', $apiresponse, $characters);
		$AniDBAPIArray['characters'] = isset($characters[1]) ? implode($characters[1], '|') : '';

		// if there are no episodes defined this can throw an error we should catch and handle this, but currently we do not
		preg_match('/<episodes>\s+<episode.+<\/episodes>/is', $apiresponse, $episodes);
		preg_match_all('/<epno>(.+)<\/epno>/i', $episodes[0], $epnos);
		$AniDBAPIArray['epnos'] = isset($epnos[1]) ? implode($epnos[1], '|') : '';
		preg_match_all('/<airdate>(.+)<\/airdate>/i', $episodes[0], $airdates);
		$AniDBAPIArray['airdates'] = isset($airdates[1]) ? implode($airdates[1], '|') : '';
		preg_match_all('/<title xml:lang="en">(.+)<\/title>/i', $episodes[0], $episodetitles);
		$AniDBAPIArray['episodetitles'] = isset($episodetitles[1]) ? implode($episodetitles[1], '|') : '';

// old		sleep(10 + rand(2, 10)); //to comply with flooding rule (2, + 8 to be extra safe, then a random delay)

// new
				$sleeptime=10 + rand(2, 10);

					if ($this->echooutput)
						echo "Start waitloop for ".$sleeptime." sec to comply with flooding rule (2, + 8 to be extra safe, then a random delay).\n";

				sleep($sleeptime);
// end new		
		
		return $AniDBAPIArray;
	}
} // end class AniDBstandAlone

if (isset($argv[1]) && is_numeric($argv[1]))
{
	// create a new AniDB object
	$anidb = new AniDBstandAlone(true);

	// next get the title list and populate the DB
// update animetitles once a week.
//	$anidb->animetitlesUpdate();

	// sleep between 1 and 3 minutes before it starts, this way if from a cron process the start times are random
	if (isset($argv[2]) && $argv[2] == 'cron')
		sleep(rand(60, 180));

	// then get the titles, this is where we will make the real changes
	if (isset($argv[1]))
	{
		// we do not always want the same number so add between 1 and 12 to it
// org		$anidb->getAniDBInfo((int)$argv[1] + rand(1, 12));
// i want exact match, no rnd():
//		$anidb->getAniDBInfo((int)$argv[1]);

// use my own version :P
echo "Start MygetAniDBInfo \n";
		$anidb->MygetAniDBInfo((int)$argv[1]);
echo "End MygetAniDBInfo \n";

	
		}
}
else
	echo "This script is designed to gather all show data from anidb and add it to the anidb table for nZEDb, as part of this process we need the number of PI queries that can be executed max.\nTo execute this script run:\nphp populate_anidb.php 30\n";
