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
			$db = new Db;
		
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
		
		public function processBookReleases($threads=0)
		{
			$ret = 0;
			$db = new DB();
			
			$res = $db->queryDirect(sprintf("SELECT name, ID from releases where bookinfoID IS NULL and categoryID in ( select ID from category where parentID = %d ) ORDER BY id DESC LIMIT %d,%d", Category::CAT_PARENT_BOOKS, floor(($this->bookqty) * ($threads * 1.25)), $this->bookqty));
			if ($db->getNumRows($res) > 0)
			{
				if ($this->echooutput)
					echo "Processing ".$db->getNumRows($res)." book releases.\n";
				
				while ($arr = $db->fetchAssoc($res)) 
				{
					$bookInfo = $this->parseTitle($arr['name']);
					if ($bookInfo !== false)
					{
						if ($this->echooutput)
							echo 'Looking up: '.$bookInfo['author']." - ".$bookInfo['title']."\n";
						
						// Check for existing book entry.
						$bookCheck = $this->getBookInfoByName($bookInfo['author'], $bookInfo['title']);
						
						if ($bookCheck === false)
						{
							$bookId = $this->updateBookInfo($bookInfo);
							if ($bookId === false)
							{
								$bookId = -2;
							}
						}
						else 
						{
							$bookId = $bookCheck["ID"];
						}
						
						// Update release.
						$db->query(sprintf("UPDATE releases SET bookinfoID = %d WHERE ID = %d", $bookId, $arr["ID"]));
						
					}
					else
					{
						// Could not parse release title.
						$db->query(sprintf("UPDATE releases SET bookinfoID = %d WHERE ID = %d", -2, $arr["ID"]));
					}
				}
			}
		}
		
		public function parseTitle($releasename)
		{
			$result = array();
			
			// Get name and author of the book from the name
			// Author/Series not in file name - Rice, Anne - Mayfair Witches [1 of 3] "1 - The Witching Hour.htm" yEnc
			if(preg_match('/^.+?\-\s(?P<author>.+?)\s\-.+?\s"\d+\s\-\s(?P<title>.+?)(\sv\d.+|\s\-\s.+|\.\w+")/i', $releasename, $matches))
			{
				if (isset($matches['author']))
				{
					$author = $matches['author'];
					// Replace dots or underscores with spaces.
					$result['author'] = preg_replace('/(\.|_|\%20)/', ' ', $author);
				}
				if (isset($matches['title']))
				{
					$title = $matches['title'];
					// Replace dots or underscores with spaces.
					$result['title'] = preg_replace('/(\.|_|\%20)/', ' ', $title);
				}
			
				$result['release'] = $releasename;
				array_map("trim", $result);
				
				if (isset($result['title']) && !empty($result['title']) && isset($result['author']) && !empty($result['author']))
					return $result;
				else
					return false;
			}
			// Author/Series not in file name - O'Brian, Patrick [1 of 20] "01 - Master & Commander v1.1.txt" yEnc
			if(preg_match('/^.+?\s\-\s(?P<author>.+?)\[.+\s\-\s(?P<title>.+?)(\.(doc|epub|mobi|rtf|txt)"|\sv\d.+)/i', $releasename, $matches))
			{
				if (isset($matches['author']))
				{
					$author = $matches['author'];
					// Replace dots or underscores with spaces.
					$result['author'] = preg_replace('/(\.|_|\%20)/', ' ', $author);
				}
				if (isset($matches['title']))
				{
					$title = $matches['title'];
					// Replace dots or underscores with spaces.
					$result['title'] = preg_replace('/(\.|_|\%20)/', ' ', $title);
				}
			
				$result['release'] = $releasename;
				array_map("trim", $result);
				
				if (isset($result['title']) && !empty($result['title']) && isset($result['author']) && !empty($result['author']))
					return $result;
				else
					return false;
			}
			
			$releasename = preg_replace('/\s\-\s\[.+?\]\s\-\s|\.(7z|epub|flac|jpg|m3um|mobi|mp3|nzb|nfo|par2|png|php|rar|rtf|sfv|txt|zip)|^(attn(:|\s)|by\s(req(quest)?(\sattn)?)|\s\(ed\)\s|re:(\sattn:|\sreq:?)?|repost:?(\sby\sreq:)?|req:)|txt\s\-|\-\.?(nzb|sfv)|\s(\-|,)$/i', '', $releasename);
			
			$releasename = str_replace('--', '-', $releasename);
			
			$releasename = str_replace(array('[', ']'), '', $releasename);
			
			$releasename = trim(preg_replace('/\s\s+/', ' ', $releasename));
			
			// "Maud Hart Lovelace - [Betsy-Tacy 07-08] - Betsy Was a Junior & Betsy and Joe (retail) (epub).rar"
			if(preg_match('/"(?P<author>.+?)\s\-\s\[.+?\]\s\-\s(?P<title>.+?)(\s\[|\-\s|;\s|\s\-|\s\()/i', $releasename, $matches))
			{
				if (isset($matches['author']))
				{
					$author = $matches['author'];
					// Replace dots or underscores with spaces.
					$result['author'] = preg_replace('/(\.|_|\%20)/', ' ', $author);
				}
				if (isset($matches['title']))
				{
					$title = $matches['title'];
					// Replace dots or underscores with spaces.
					$result['title'] = preg_replace('/(\.|_|\%20)/', ' ', $title);
				}
			
				$result['release'] = $releasename;
				array_map("trim", $result);
				
				if (isset($result['title']) && !empty($result['title']) && isset($result['author']) && !empty($result['author']))
					return $result;
				else
					return false;
			}
			// "Maud Hart Lovelace - Betsy Was a Junior & Betsy and Joe (retail) (epub).rar"
			else if(preg_match('/"(?P<author>.+?)\s\-\s(?P<title>.+?)(\-\s|\s(\(|\[)).+?"/i', $releasename, $matches))
			{
				if (isset($matches['author']))
				{
					$author = $matches['author'];
					// Replace dots or underscores with spaces.
					$result['author'] = preg_replace('/(\.|_|\%20)/', ' ', $author);
				}
				if (isset($matches['title']))
				{
					$title = $matches['title'];
					// Replace dots or underscores with spaces.
					$result['title'] = preg_replace('/(\.|_|\%20)/', ' ', $title);
				}
			
				$result['release'] = $releasename;
				array_map("trim", $result);
				
				if (isset($result['title']) && !empty($result['title']) && isset($result['author']) && !empty($result['author']))
					return $result;
				else
					return false;
			}
			// "Maud Hart Lovelace - Betsy Was a Junior & Betsy and Joe.mobi"
			else if(preg_match('/"(?P<author>.+?)\s\-\s(?P<title>.+?)\.[\w]+"/i', $releasename, $matches))
			{
				if (isset($matches['author']))
				{
					$author = $matches['author'];
					// Replace dots or underscores with spaces.
					$result['author'] = preg_replace('/(\.|_|\%20)/', ' ', $author);
				}
				if (isset($matches['title']))
				{
					$title = $matches['title'];
					// Replace dots or underscores with spaces.
					$result['title'] = preg_replace('/(\.|_|\%20)/', ' ', $title);
				}
			
				$result['release'] = $releasename;
				array_map("trim", $result);
			
				if (isset($result['title']) && !empty($result['title']) && isset($result['author']) && !empty($result['author']))
					return $result;
				else
					return false;
			}
			// "Betsy Was a Junior & Betsy and Joe by Maud Hart Lovelace(retail).rar"
			else if(preg_match('/"(?P<title>.+?)(\.|\s)by(\.|\s)(?P<author>.+?)(\[|\().+?"/i', $releasename, $matches))
			{
				if (isset($matches['author']))
				{
					$author = $matches['author'];
					// Replace dots or underscores with spaces.
					$result['author'] = preg_replace('/(\.|_|\%20)/', ' ', $author);
				}
				if (isset($matches['title']))
				{
					$title = $matches['title'];
					// Replace dots or underscores with spaces.
					$result['title'] = preg_replace('/(\.|_|\%20)/', ' ', $title);
				}
			
				$result['release'] = $releasename;
				array_map("trim", $result);
			
				if (isset($result['title']) && !empty($result['title']) && isset($result['author']) && !empty($result['author']))
					return $result;
				else
					return false;
			}
			else
				return false;
		}

		public function updateBookInfo($bookInfo)
		{
			$db = new DB();
			$ri = new ReleaseImage();
		
			$book = array();
			$amaztitle = $bookInfo['author']." ".$bookInfo['title'];
			$amaz = $this->fetchAmazonProperties($amaztitle);
			if (!$amaz) 
				return false;
				
			$book['title'] = (string) $amaz->Items->Item->ItemAttributes->Title;
			if (empty($con['title']))
				$book['title'] = $bookInfo['title'];
				
			$book['author'] = (string) $amaz->Items->Item->ItemAttributes->Author;
			if (empty($con['author']))
				$book['author'] = $bookInfo['author'];
				
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
			
			$book['publishdate'] = (string) $amaz->Items->Item->ItemAttributes->PublicationDate;
			if ($book['publishdate'] == "")
				$book['publishdate'] = 'null';
			
			$book['pages'] = (string) $amaz->Items->Item->ItemAttributes->NumberOfPages;
			if ($book['pages'] == "")
				$book['pages'] = 'null';
				
			if(isset($amaz->Items->Item->ItemAttributes->EditorialReviews->EditorialReview->Content))
			{
				$book['overview'] = (string) $amaz->Items->Item->ItemAttributes->EditorialReviews->EditorialReview->Content;
				if ($book['overview'] == "")
					$book['overview'] = 'null';
			}
			else
			{
				$book['overview'] = 'null';
			}
			
			$book['coverurl'] = (string) $amaz->Items->Item->LargeImage->URL;
			if ($book['coverurl'] != "")
				$book['cover'] = 1;
			else
				$book['cover'] = 0;
			
			$query = sprintf("INSERT INTO bookinfo  (`title`, `author`, `asin`, `isbn`, `ean`, `url`, `salesrank`, `publisher`, `publishdate`, `pages`, `overview`, `cover`, `createddate`, `updateddate`) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, now(), now()) ON DUPLICATE KEY UPDATE  `title` = %s,  `author` = %s,  `asin` = %s,  `isbn` = %s,  `ean` = %s,  `url` = %s,  `salesrank` = %s,  `publisher` = %s,  `publishdate` = %s,  `pages` = %s,  `overview` = %s, `cover` = %d,  createddate = now(),  updateddate = now()", $db->escapeString($book['title']), $db->escapeString($book['author']), $db->escapeString($book['asin']), $db->escapeString($book['isbn']), $db->escapeString($book['ean']), $db->escapeString($book['url']), $book['salesrank'], $db->escapeString($book['publisher']), $db->escapeString($book['publishdate']), $book['pages'], $db->escapeString($book['overview']), $book['cover'], $db->escapeString($book['title']), $db->escapeString($book['author']), $db->escapeString($book['asin']), $db->escapeString($book['isbn']), $db->escapeString($book['ean']), $db->escapeString($book['url']), $book['salesrank'], $db->escapeString($book['publisher']), $db->escapeString($book['publishdate']), $book['pages'], $db->escapeString($book['overview']), $book['cover']);
			
			$bookId = $db->queryInsert($query);

			if ($bookId) 
			{
				if ($this->echooutput)
					echo "Added/updated book: ".$book['author']." - ".$book['title']."\n";

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
