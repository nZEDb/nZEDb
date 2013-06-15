<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/amazon.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/releaseimage.php");
require_once(WWW_DIR."/lib/site.php");

	/*
	 *	Class for fetching book info from amazon.com.
	 */
	 
	 class Books
	 {
		 function Books($echooutput=false)
		 {
			$this->echooutput = $echooutput;
			$s = new Sites();
			$site = $s->get();
			$this->pubkey = $site->amazonpubkey;
			$this->privkey = $site->amazonprivkey;
			$this->asstag = $site->amazonassociatetag;
			$this->bookqty = (!empty($site->maxbooksprocessed)) ? $site->maxbooksprocessed : 300;
			$this->sleeptime = (!empty($site->amazonsleep)) ? $site->amazonsleep : 1000;
			
			$this->imgSavePath = WWW_DIR.'covers/book/';
		}
		
		public function getBookInfo($id)
		{
			$db = new DB();
			return $db->queryOneRow(sprintf("SELECT bookinfo.* FROM bookinfo where bookinfo.ID = %d ", $id));
		}
		
		public function getBookInfoByName($author, $title)
		{
			$db = new DB();
			return $db->queryOneRow(sprintf("SELECT * FROM bookinfo where author like %s and title like %s", $db->escapeString("%".$author."%"),  $db->escapeString("%".$title."%")));
		}
		
		public function getRange($start, $num)
		{		
			$db = new DB();
		
			if ($start === false)
				$limit = "";
			else
				$limit = " LIMIT ".$start.",".$num;
		
			return $db->query(" SELECT * FROM bookinfo ORDER BY createddate DESC".$limit);		
		}
		
		public function getCount()
		{			
			$db = new DB();
			$res = $db->queryOneRow("select count(ID) as num from bookinfo");		
			return $res["num"];
		}
		
		public function getBookCount($cat, $maxage=-1, $excludedcats=array())
		{
			$db = new DB();
		
			$browseby = $this->getBrowseBy();
		
			$catsrch = "";
			if (count($cat) > 0 && $cat[0] != -1)
			{
				$catsrch = " (";
				foreach ($cat as $category)
				{
					if ($category != -1)
					{
						$categ = new Category();
						if ($categ->isParent($category))
						{
							$children = $categ->getChildren($category);
							$chlist = "-99";
							foreach ($children as $child)
								$chlist.=", ".$child["ID"];

							if ($chlist != "-99")
									$catsrch .= " r.categoryID in (".$chlist.") or ";
						}
						else
						{
							$catsrch .= sprintf(" r.categoryID = %d or ", $category);
						}
					}
				}
				$catsrch.= "1=2 )";
			}			

			if ($maxage > 0)
				$maxage = sprintf(" and r.postdate > now() - interval %d day ", $maxage);
			else
				$maxage = "";		
		
			$exccatlist = "";
			if (count($excludedcats) > 0)
				$exccatlist = " and r.categoryID not in (".implode(",", $excludedcats).")";
		
			$sql = sprintf("select count(r.ID) as num from releases r inner join bookinfo b on b.ID = r.bookinfoID and b.title != '' where r.passwordstatus <= (select value from site where setting='showpasswordedrelease') and %s %s %s %s", $browseby, $catsrch, $maxage, $exccatlist);
			$res = $db->queryOneRow($sql);		
			return $res["num"];	
		}
		
		public function getBookRange($cat, $start, $num, $orderby, $maxage=-1, $excludedcats=array())
		{	
			$db = new DB();
		
			$browseby = $this->getBrowseBy();
		
			if ($start === false)
				$limit = "";
			else
				$limit = " LIMIT ".$start.",".$num;
		
			$catsrch = "";
			if (count($cat) > 0 && $cat[0] != -1)
			{
				$catsrch = " (";
				foreach ($cat as $category)
				{
					if ($category != -1)
					{
						$categ = new Category();
						if ($categ->isParent($category))
						{
							$children = $categ->getChildren($category);
							$chlist = "-99";
							foreach ($children as $child)
								$chlist.=", ".$child["ID"];

							if ($chlist != "-99")
								$catsrch .= " r.categoryID in (".$chlist.") or ";
						}
						else
						{
							$catsrch .= sprintf(" r.categoryID = %d or ", $category);
						}
					}
				}
				$catsrch.= "1=2 )";
			}	
		
			$maxage = "";
			if ($maxage > 0)
				$maxage = sprintf(" and r.postdate > now() - interval %d day ", $maxage);

			$exccatlist = "";
			if (count($excludedcats) > 0)
				$exccatlist = " and r.categoryID not in (".implode(",", $excludedcats).")";
			
			$order = $this->getBookOrder($orderby);
			$sql = sprintf(" SELECT r.*, r.ID as releaseID, boo.*, groups.name as group_name, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, rn.ID as nfoID from releases r left outer join groups on groups.ID = r.groupID inner join bookinfo boo on boo.ID = r.bookinfoID left outer join releasenfo rn on rn.releaseID = r.ID and rn.nfo is not null left outer join category c on c.ID = r.categoryID left outer join category cp on cp.ID = c.parentID where r.passwordstatus <= (select value from site where setting='showpasswordedrelease') and %s %s %s %s order by %s %s".$limit, $browseby, $catsrch, $maxage, $exccatlist, $order[0], $order[1]);
			return $db->query($sql);		
		}
		
		public function getBookOrder($orderby)
		{
			$order = ($orderby == '') ? 'r.postdate' : $orderby;
			$orderArr = explode("_", $order);
			switch($orderArr[0]) {
				case 'title':
					$orderfield = 'boo.title';
				break;
				case 'author':
					$orderfield = 'boo.title';
				break;
				case 'publishdate':
					$orderfield = 'boo.publishdate';
				break;
				case 'size':
					$orderfield = 'r.size';
				break;
				case 'files':
					$orderfield = 'r.totalpart';
				break;
				case 'stats':
					$orderfield = 'r.grabs';
				break;
				case 'posted': 
				default:
					$orderfield = 'r.postdate';
				break;
		}
		$ordersort = (isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc';
		return array($orderfield, $ordersort);
	}
		
		public function getBookOrdering()
		{
			return array('title_asc', 'title_desc', 'posted_asc', 'posted_desc', 'size_asc', 'size_desc', 'files_asc', 'files_desc', 'stats_asc', 'stats_desc', 'releasedate_asc', 'releasedate_desc', 'author_asc', 'author_desc');
		}
		
		public function getBrowseByOptions()
		{
			return array('author'=>'author', 'title'=>'title');
		}
		
		public function getBrowseBy()
		{
			$db = new DB();
		
			$browseby = ' ';
			$browsebyArr = $this->getBrowseByOptions();
			foreach ($browsebyArr as $bbk=>$bbv) {
				if (isset($_REQUEST[$bbk]) && !empty($_REQUEST[$bbk])) {
					$bbs = stripslashes($_REQUEST[$bbk]);
					$browseby .= "boo.$bbv LIKE(".$db->escapeString('%'.$bbs.'%').") AND ";
				}
			}
			return $browseby;
		}
		
		public function fetchAmazonProperties($title)
		{
			$obj = new AmazonProductAPI($this->pubkey, $this->privkey, $this->asstag);
			try
			{
				$result = $obj->searchProducts($title, AmazonProductAPI::BOOKS, "TITLE");
			}
			catch(Exception $e)
			{
				$result = false;
			}
			return $result;
		}
		
		public function processBookReleases($threads=1)
		{
			$threads--;
			$ret = 0;
			$db = new DB();
			
			$res = $db->queryDirect(sprintf("SELECT searchname, ID from releases where bookinfoID IS NULL and nzbstatus = 1 and categoryID = 8010 order by postdate desc LIMIT %d,%d", floor(($this->bookqty) * ($threads * 1.5)), $this->bookqty));
			if ($db->getNumRows($res) > 0)
			{
				if ($this->echooutput)
					echo "Processing ".$db->getNumRows($res)." book releases.\n";
				
				while ($arr = $db->fetchAssoc($res)) 
				{
					$bookInfo = $this->parseTitle($arr['searchname'], $arr['ID']);
					if ($bookInfo !== false)
					{
						if ($this->echooutput)
							echo 'Looking up: '.$bookInfo."\n";
						
						$bookId = $this->updateBookInfo($bookInfo);
						if ($bookId === false)
						{
							$bookId = -2;
						}
						
						// Update release.
						$db->query(sprintf("UPDATE releases SET bookinfoID = %d WHERE ID = %d", $bookId, $arr["ID"]));
						
					}
					else
					{
						// Could not parse release title.
						$db->query(sprintf("UPDATE releases SET bookinfoID = %d WHERE ID = %d", -2, $arr["ID"]));
					}
					usleep($this->sleeptime*1000);
				}
			}
		}
		
		public function parseTitle($releasename, $releaseID)
		{
			$releasename = preg_replace('/\d{1,2} \d{1,2} \d{2,4}|(19|20)\d\d|anybody got .+?[a-z]\? |[\.\-_ ](Novel|TIA)([\.\-_ ]|$)|( |\.)HQ(-|\.| )|[\(\)\.\-_ ](AVI|DOC|EPUB|LIT|MOBI|NFO|(si)?PDF|RTF|TXT)(?![a-z0-9])|compleet|DAGSTiDNiNGEN|DiRFiX|\+ extra|r?e ?Books?([\.\-_ ]English|ers)?|ePu(b|p)s?|html|mobi|^NEW[\.\-_ ]|PDF([\.\-_ ]English)?|Please post more|Post description|Proper|Repack(fix)?|[\.\-_ ](Chinese|English|French|German|Italian|Retail|Scan|Swedish)|^R4 |Repost|Skytwohigh|TIA!+|TruePDF|V413HAV|(would someone )?please (re)?post.+? "|with the authors name right/i', '', $releasename);
			$releasename = preg_replace('/^(As Req |conversion |eq |Das neue Abenteuer \d+|Fixed version( ignore previous post)?|Full |Per Req As Found|(\s+)?R4 |REQ |revised |version |\d+(\s+)?$)|(COMPLETE|INTERNAL|RELOADED| (AZW3|eB|docx|ENG?|exe|FR|Fix|gnv64|MU|NIV|R\d\s+\d{1,2} \d{1,2}|R\d|Req|TTL|UC|v(\s+)?\d))(\s+)?$/i', '', $releasename);
			$releasename = trim(preg_replace('/\s\s+/i', ' ', $releasename));
			
			if (preg_match('/^([a-z0-9] )+$|ArtofUsenet|ekiosk|(ebook|mobi).+collection|erotica|Full Video|ImwithJamie|linkoff org|Mega.+pack|^[a-z0-9]+ (?!((January|February|March|April|May|June|July|August|September|O(c|k)tober|November|De(c|z)ember)))[a-z]+( (ebooks?|The))?$|NY Times|(Book|Massive) Dump|Sexual/i', $releasename))
			{
				echo "Changing category to misc books: ".$releasename."\n";
				$db = new DB();
				$db->query(sprintf("UPDATE releases SET categoryID = %d WHERE ID = %d", 8050, $releaseID));
				return false;
			}
			else if (preg_match('/^([a-z0-9ü!]+ ){1,2}(N|Vol)?\d{1,4}(a|b|c)?$|^([a-z0-9]+ ){1,2}(Jan( |unar|$)|Feb( |ruary|$)|Mar( |ch|$)|Apr( |il|$)|May(?![a-z0-9])|Jun( |e|$)|Jul( |y|$)|Aug( |ust|$)|Sep( |tember|$)|O(c|k)t( |ober|$)|Nov( |ember|$)|De(c|z)( |ember|$))/i', $releasename) && !preg_match('/Part \d+/i', $releasename))
			{
				echo "Changing category to magazines: ".$releasename."\n";
				$db = new DB();
				$db->query(sprintf("UPDATE releases SET categoryID = %d WHERE ID = %d", 8030, $releaseID));
				return false;
			}
			else if (!empty($releasename) && !preg_match('/^[a-z0-9]+$|^([0-9]+ ){1,}$|Part \d+/i', $releasename))
				return $releasename;
			else
				return false;
		}
		
		public function updateBookInfo($bookInfo = '', $amazdata = null)
		{
			$db = new DB();
			$ri = new ReleaseImage();
		
			$book = array();

			if ($bookInfo != '')
				$amaz = $this->fetchAmazonProperties($bookInfo);
			elseif ($amazdata != null)
				$amaz = $amazdata;

			if (!$amaz) 
				return false;
				
			$book['title'] = (string) $amaz->Items->Item->ItemAttributes->Title;
				
			$book['author'] = (string) $amaz->Items->Item->ItemAttributes->Author;
				
			$book['asin'] = (string) $amaz->Items->Item->ASIN;
			
			$book['isbn'] = (string) $amaz->Items->Item->ItemAttributes->ISBN;
			if ($book['isbn'] == "")
				$book['isbn'] = 'null';
				
			$book['ean'] = (string) $amaz->Items->Item->ItemAttributes->EAN;
			if ($book['ean'] == "")
				$book['ean'] = 'null';
			
			$book['url'] = (string) $amaz->Items->Item->DetailPageURL;
			$book['url'] = str_replace("%26tag%3Dws", "%26tag%3Dopensourceins%2D21", $book['url']);
			
			$book['salesrank'] = (string) $amaz->Items->Item->SalesRank;
			if ($book['salesrank'] == "")
				$book['salesrank'] = 'null';
				
			$book['publisher'] = (string) $amaz->Items->Item->ItemAttributes->Publisher;
			if ($book['publisher'] == "")
				$book['publisher'] = 'null';
			
			$book['publishdate'] = date("Y-m-d", strtotime((string) $amaz->Items->Item->ItemAttributes->PublicationDate));
			if ($book['publishdate'] == "")
				$book['publishdate'] = 'null';
			
			$book['pages'] = (string) $amaz->Items->Item->ItemAttributes->NumberOfPages;
			if ($book['pages'] == "")
				$book['pages'] = 'null';
			
			if(isset($amaz->Items->Item->EditorialReviews->EditorialReview->Content))
			{
				$book['overview'] = strip_tags((string) $amaz->Items->Item->EditorialReviews->EditorialReview->Content);
				if ($book['overview'] == "")
					$book['overview'] = 'null';
			}
			else
				$book['overview'] = 'null';
			
			if(isset($amaz->Items->Item->BrowseNodes->BrowseNode->Name))
			{
				$book['genre'] = (string) $amaz->Items->Item->BrowseNodes->BrowseNode->Name;
				if ($book['genre'] == "")
					$book['genre'] = 'null';
			}
			else
				$book['genre'] = 'null';
			
			$book['coverurl'] = (string) $amaz->Items->Item->LargeImage->URL;
			if ($book['coverurl'] != "")
				$book['cover'] = 1;
			else
				$book['cover'] = 0;
			
			$query = sprintf("INSERT INTO bookinfo  (`title`, `author`, `asin`, `isbn`, `ean`, `url`, `salesrank`, `publisher`, `publishdate`, `pages`, `overview`, `genre`, `cover`, `createddate`, `updateddate`) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, now(), now()) ON DUPLICATE KEY UPDATE  `title` = %s,  `author` = %s,  `asin` = %s,  `isbn` = %s,  `ean` = %s,  `url` = %s,  `salesrank` = %s,  `publisher` = %s,  `publishdate` = %s,  `pages` = %s,  `overview` = %s, `genre` = %s, `cover` = %d,  createddate = now(),  updateddate = now()", $db->escapeString($book['title']), $db->escapeString($book['author']), $db->escapeString($book['asin']), $db->escapeString($book['isbn']), $db->escapeString($book['ean']), $db->escapeString($book['url']), $book['salesrank'], $db->escapeString($book['publisher']), $db->escapeString($book['publishdate']), $book['pages'], $db->escapeString($book['overview']), $db->escapeString($book['genre']), $book['cover'], $db->escapeString($book['title']), $db->escapeString($book['author']), $db->escapeString($book['asin']), $db->escapeString($book['isbn']), $db->escapeString($book['ean']), $db->escapeString($book['url']), $book['salesrank'], $db->escapeString($book['publisher']), $db->escapeString($book['publishdate']), $book['pages'], $db->escapeString($book['overview']), $db->escapeString($book['genre']), $book['cover']);
			
			$bookId = $db->queryInsert($query);

			if ($bookId) 
			{
				if ($this->echooutput)
				{
					echo "Added/updated book: ";
					if ($book['author'] !== "")
						echo "Author: ".$book['author'].", ";
					echo "Title: ".$book['title'];
					if ($book['genre'] !== "null")
						echo ", Genre: ".$book['genre'].".\n";
					else
						echo ".\n";
				}

				$book['cover'] = $ri->saveImage($bookId, $book['coverurl'], $this->imgSavePath, 250, 250);
			} 
			else 
			{
				if ($this->echooutput)
					echo "Nothing to update: ".$book['author']." - ".$book['title'].".\n";
			}
			return $bookId;
		}
	}