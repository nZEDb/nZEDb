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
		 const NUMTOPROCESSPERTIME = 125;
		 
		 function Books($echooutput=false)
		 {
			$this->echooutput = $echooutput;
			$s = new Sites();
			$site = $s->get();
			$this->pubkey = $site->amazonpubkey;
			$this->privkey = $site->amazonprivkey;
			$this->asstag = $site->amazonassociatetag;
			$this->imgSavePath = WWW_DIR.'covers/book/';
		}
		
		public function getBookInfoByName($author, $title)
		{
			$db = new DB();
			return $db->queryOneRow(sprintf("SELECT * FROM bookinfo where author like %s and title like %s", $db->escapeString("%".$author."%"),  $db->escapeString("%".$title."%")));
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
		
		public function processBookReleases()
		{
			$ret = 0;
			$db = new DB();
			
			$res = $db->queryDirect(sprintf("SELECT name, ID from releases where bookinfoID IS NULL and categoryID in ( select ID from category where parentID = %d ) ORDER BY id DESC LIMIT %d", Category::CAT_PARENT_BOOKS, Books::NUMTOPROCESSPERTIME));
			if ($db->getNumRows($res) > 0)
			{
				if ($this->echooutput)
					echo "\nProcessing ".$db->getNumRows($res)." book releases.\n";
				
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
						//$db->query(sprintf("UPDATE releases SET bookinfoID = %d WHERE ID = %d", $bookId, $arr["ID"]));
						
					}
					else
					{
						// Could not parse release title.
						//$db->query(sprintf("UPDATE releases SET bookinfoID = %d WHERE ID = %d", -2, $arr["ID"]));
					}
				}
			}
		}
		
		public function parseTitle($releasename)
		{
			$result = array();
			
			// Get name and author of the book from the search name
			
			if(preg_match('/"(?P<author>.+)\s\-\s(?P<title>.+)\s(\(|\[).+"/i', $releasename, $matches))
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
			else if(preg_match('/"(?P<author>.+)\s\-\s(?P<title>.+)\.[\w]+"/i', $releasename, $matches))
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
			
			$query = sprintf("
			INSERT INTO bookinfo  (`title`, `author`, `asin`, `isbn`, `ean`, `url`, `salesrank`, `publisher`, `publishdate`, `pages`, `overview`, `cover`, `createddate`, `updateddate`)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %d, now(), now())
				ON DUPLICATE KEY UPDATE  `title` = %s,  `author` = %s,  `asin` = %s,  `isbn` = %s,  `ean` = %s,  `url` = %s,  `salesrank` = %s,  `publisher` = %s,  `publishdate` = %s,  `pages` = %s,  `overview` = %s, `cover` = %d,  createddate = now(),  updateddate = now()", 
			$db->escapeString($book['title']), $db->escapeString($book['author']), $db->escapeString($book['asin']), $db->escapeString($book['isbn']),
			$db->escapeString($book['ean']), $db->escapeString($book['url']), $book['salesrank'], $db->escapeString($book['publisher']),
			$db->escapeString($book['publishdate']), $book['pages'], $db->escapeString($book['overview']), $book['cover'], 
			$db->escapeString($book['title']), $db->escapeString($book['author']), $db->escapeString($book['asin']), $db->escapeString($book['isbn']),
			$db->escapeString($book['ean']), $db->escapeString($book['url']), $book['salesrank'], $db->escapeString($book['publisher']),
			$db->escapeString($book['publishdate']), $book['pages'], $db->escapeString($book['overview']), $book['cover']);
			
			$bookId = $db->queryInsert($query);

			if ($bookId) 
			{
				if ($this->echooutput)
					echo "Added/updated book: ".$book['author']." - ".$book['title'].".\n";

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
