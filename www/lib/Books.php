<?php

//require_once nZEDb_LIB . 'framework/db.php';
//require_once nZEDb_LIB . 'amazon.php';
//require_once nZEDb_LIB . 'category.php';
//require_once nZEDb_LIB . 'releaseimage.php';
//require_once nZEDb_LIB . 'site.php';
//require_once nZEDb_LIB . 'ColorCLI.php';

/*
 * Class for fetching book info from amazon.com.
 */

class Books
{

	function __construct($echooutput = false)
	{
		$this->echooutput = $echooutput;
		$s = new Sites();
		$site = $s->get();
		$this->pubkey = $site->amazonpubkey;
		$this->privkey = $site->amazonprivkey;
		$this->asstag = $site->amazonassociatetag;
		$this->bookqty = (!empty($site->maxbooksprocessed)) ? $site->maxbooksprocessed : 300;
		$this->sleeptime = (!empty($site->amazonsleep)) ? $site->amazonsleep : 1000;
		$this->imgSavePath = nZEDb_WWW . 'covers/book/';
		$this->db = new DB();
		$this->bookreqids = ($site->book_reqids == NULL || $site->book_reqids == "") ? 8010 : $site->book_reqids;
		$this->cleanbooks = ($site->lookupbooks == 2 ) ? 260 : 256;
		$this->c = new ColorCLI();
	}

	public function getBookInfo($id)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf('SELECT bookinfo.* FROM bookinfo WHERE bookinfo.id = %d', $id));
	}

	public function getBookInfoByName($author, $title)
	{
		$db = $this->db;
		$like = 'ILIKE';
		if ($db->dbSystem() == 'mysql') {
			$like = 'LIKE';
		}
		return $db->queryOneRow(sprintf('SELECT * FROM bookinfo WHERE author LIKE %s AND title %s %s', $db->escapeString('%' . $author . '%'), $like, $db->escapeString('%' . $title . '%')));
	}

	public function getRange($start, $num)
	{
		$db = $this->db;

		if ($start === false) {
			$limit = '';
		} else {
			$limit = ' LIMIT ' . $num . ' OFFSET ' . $start;
		}

		return $db->query(' SELECT * FROM bookinfo ORDER BY createddate DESC' . $limit);
	}

	public function getCount()
	{
		$db = $this->db;
		$res = $db->queryOneRow('SELECT COUNT(id) AS num FROM bookinfo');
		return $res['num'];
	}

	public function getBookCount($cat, $maxage = -1, $excludedcats = array())
	{
		$db = $this->db;

		$browseby = $this->getBrowseBy();

		$catsrch = '';
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = ' (';
			foreach ($cat as $category) {
				if ($category != -1) {
					$categ = new Category();
					if ($categ->isParent($category)) {
						$children = $categ->getChildren($category);
						$chlist = '-99';
						foreach ($children as $child) {
							$chlist .= ', ' . $child['id'];
						}

						if ($chlist != '-99') {
							$catsrch .= ' r.categoryid IN (' . $chlist . ') OR ';
						}
					} else {
						$catsrch .= sprintf(' r.categoryid = %d OR ', $category);
					}
				}
			}
			$catsrch .= '1=2 )';
		}

		if ($maxage > 0) {
			if ($db->dbSystem() == 'mysql') {
				$maxage = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else if ($db->dbSystem() == 'pgsql') {
				$maxage = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		} else {
			$maxage = '';
		}

		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND r.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$res = $db->queryOneRow(sprintf("SELECT COUNT(r.id) AS num FROM releases r "
				. "INNER JOIN bookinfo boo ON boo.id = r.bookinfoid AND boo.title != '' "
				. "WHERE (r.bitwise & 256) = 256 AND  r.passwordstatus <= (SELECT value FROM site WHERE setting='showpasswordedrelease') "
				. "AND %s %s %s %s", $browseby, $catsrch, $maxage, $exccatlist));
		return $res['num'];
	}

	public function getBookRange($cat, $start, $num, $orderby, $maxage = -1, $excludedcats = array())
	{
		$db = $this->db;

		$browseby = $this->getBrowseBy();

		if ($start === false) {
			$limit = '';
		} else {
			$limit = ' LIMIT ' . $num . ' OFFSET ' . $start;
		}

		$catsrch = '';
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = ' (';
			foreach ($cat as $category) {
				if ($category != -1) {
					$categ = new Category();
					if ($categ->isParent($category)) {
						$children = $categ->getChildren($category);
						$chlist = '-99';
						foreach ($children as $child) {
							$chlist .= ', ' . $child['id'];
						}

						if ($chlist != '-99') {
							$catsrch .= ' r.categoryid IN (' . $chlist . ') OR ';
						}
					} else {
						$catsrch .= sprintf(' r.categoryid = %d OR ', $category);
					}
				}
			}
			$catsrch .= '1=2)';
		}

		$maxage = '';
		if ($maxage > 0) {
			if ($db->dbSystem() == 'mysql') {
				$maxage = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else if ($db->dbSystem() == 'pgsql') {
				$maxage = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND r.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$order = $this->getBookOrder($orderby);
		return $db->query(sprintf("SELECT r.*, r.id as releaseid, boo.*, groups.name AS group_name, "
					. "CONCAT(cp.title, ' > ', c.title) AS category_name, "
					. "CONCAT(cp.id, ',', c.id) AS category_ids, rn.id AS nfoid "
					. "FROM releases r LEFT OUTER JOIN groups ON groups.id = r.groupid "
					. "INNER JOIN bookinfo boo ON boo.id = r.bookinfoid "
					. "LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL "
					. "LEFT OUTER JOIN category c ON c.id = r.categoryid "
					. "LEFT OUTER JOIN category cp ON cp.id = c.parentid "
					. "WHERE (r.bitwise & 256) = 256 AND r.passwordstatus <= (SELECT value FROM site WHERE setting='showpasswordedrelease') "
					. "AND %s %s %s %s ORDER BY %s %s" . $limit, $browseby, $catsrch, $maxage, $exccatlist, $order[0], $order[1]));
	}

	public function getBookOrder($orderby)
	{
		$order = ($orderby == '') ? 'r.postdate' : $orderby;
		$orderArr = explode('_', $order);
		switch ($orderArr[0]) {
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
		return array('title_asc', 'title_desc', 'posted_asc', 'posted_desc', 'size_asc', 'size_desc', 'files_asc',
			'files_desc', 'stats_asc', 'stats_desc', 'releasedate_asc', 'releasedate_desc', 'author_asc', 'author_desc');
	}

	public function getBrowseByOptions()
	{
		return array('author' => 'author', 'title' => 'title');
	}

	public function getBrowseBy()
	{
		$db = $this->db;

		$like = 'ILIKE';
		if ($db->dbSystem() == 'mysql') {
			$like = 'LIKE';
		}

		$browseby = ' ';
		$browsebyArr = $this->getBrowseByOptions();
		foreach ($browsebyArr as $bbk => $bbv) {
			if (isset($_REQUEST[$bbk]) && !empty($_REQUEST[$bbk])) {
				$bbs = stripslashes($_REQUEST[$bbk]);
				$browseby .= 'boo.' . $bbv . ' ' . $like . ' (' . $db->escapeString('%' . $bbs . '%') . ') AND ';
			}
		}
		return $browseby;
	}

	public function fetchAmazonProperties($title)
	{
		$obj = new AmazonProductAPI($this->pubkey, $this->privkey, $this->asstag);
		try {
			$result = $obj->searchProducts($title, AmazonProductAPI::BOOKS, 'TITLE');
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}

	public function processBookReleases()
	{
		$db = $this->db;

		// include results for all book types selected in the site edit UI, this could be audio, ebooks, foregin or technical currently
		$res = $db->queryDirect(sprintf('SELECT searchname, id, categoryid FROM releases WHERE (bitwise & %d) = %d AND bookinfoid IS NULL AND categoryid in (%s) ORDER BY POSTDATE DESC LIMIT %d', $this->cleanbooks, $this->cleanbooks, $this->bookreqids, $this->bookqty));

		if ($res->rowCount() > 0) {
			if ($this->echooutput) {
				echo $this->c->header("\nProcessing " . $res->rowCount() . ' book release(s).');
			}

			foreach ($res as $arr) {
				$startTime = microtime(true);
				$usedAmazon = false;
				// audiobooks are also books and should be handled in an identical manor, even though it falls under a music category
				if ($arr['categoryid'] == '3030') {
					// audiobook
					$bookInfo = $this->parseTitle($arr['searchname'], $arr['id'], 'audiobook');
				} else {
					// ebook
					$bookInfo = $this->parseTitle($arr['searchname'], $arr['id'], 'ebook');
				}

				if ($bookInfo !== false) {
					if ($this->echooutput) {
						echo $this->c->headerOver('Looking up: ') . $this->c->primary($bookInfo);
					}

					// Do a local lookup first
					$bookCheck = $this->getBookInfoByName('', $bookInfo);

					if ($bookCheck === false) {
						$bookId = $this->updateBookInfo($bookInfo);
						$usedAmazon = true;
						if ($bookId === false) {
							$bookId = -2;
						}
					} else {
						$bookId = $bookCheck['id'];
					}

					// Update release.
					$db->queryExec(sprintf('UPDATE releases SET bookinfoid = %d WHERE id = %d', $bookId, $arr['id']));
				}
				// Could not parse release title.
				else {
					$db->queryExec(sprintf('UPDATE releases SET bookinfoid = %d WHERE id = %d', -2, $arr['id']));
					echo '.';
				}
				// Sleep to not flood amazon.
				$diff = floor((microtime(true) - $startTime) * 1000000);
				if ($this->sleeptime * 1000 - $diff > 0 && $usedAmazon === true) {
					usleep($this->sleeptime * 1000 - $diff);
				}
			}
		} else
		if ($this->echooutput) {
			echo $this->c->header('No book releases to process.');
		}
	}

	public function parseTitle($release_name, $releaseID, $releasetype)
	{
		$a = preg_replace('/\d{1,2} \d{1,2} \d{2,4}|(19|20)\d\d|anybody got .+?[a-z]\? |[-._ ](Novel|TIA)([-._ ]|$)|( |\.)HQ(-|\.| )|[\(\)\.\-_ ](AVI|DOC|EPUB|LIT|MOBI|NFO|(si)?PDF|RTF|TXT)(?![a-z0-9])|compleet|DAGSTiDNiNGEN|DiRFiX|\+ extra|r?e ?Books?([\.\-_ ]English|ers)?|ePu(b|p)s?|html|mobi|^NEW[\.\-_ ]|PDF([\.\-_ ]English)?|Please post more|Post description|Proper|Repack(fix)?|[\.\-_ ](Chinese|English|French|German|Italian|Retail|Scan|Swedish)|^R4 |Repost|Skytwohigh|TIA!+|TruePDF|V413HAV|(would someone )?please (re)?post.+? "|with the authors name right/i', '', $release_name);
		$b = preg_replace('/^(As Req |conversion |eq |Das neue Abenteuer \d+|Fixed version( ignore previous post)?|Full |Per Req As Found|(\s+)?R4 |REQ |revised |version |\d+(\s+)?$)|(COMPLETE|INTERNAL|RELOADED| (AZW3|eB|docx|ENG?|exe|FR|Fix|gnv64|MU|NIV|R\d\s+\d{1,2} \d{1,2}|R\d|Req|TTL|UC|v(\s+)?\d))(\s+)?$/i', '', $a);
		$releasename = trim(preg_replace('/\s\s+/i', ' ', $b));

		// the default existing type was ebook, this handles that in the same manor as before
		if ($releasetype == 'ebook') {
			if (preg_match('/^([a-z0-9] )+$|ArtofUsenet|ekiosk|(ebook|mobi).+collection|erotica|Full Video|ImwithJamie|linkoff org|Mega.+pack|^[a-z0-9]+ (?!((January|February|March|April|May|June|July|August|September|O(c|k)tober|November|De(c|z)ember)))[a-z]+( (ebooks?|The))?$|NY Times|(Book|Massive) Dump|Sexual/i', $releasename)) {
				echo $this->c->headerOver('Changing category to misc books: ') . $this->c->primary($releasename);
				$db = $this->db;
				$db->queryExec(sprintf('UPDATE releases SET categoryid = %d WHERE id = %d', 8050, $releaseID));
				return false;
			} else if (preg_match('/^([a-z0-9ü!]+ ){1,2}(N|Vol)?\d{1,4}(a|b|c)?$|^([a-z0-9]+ ){1,2}(Jan( |unar|$)|Feb( |ruary|$)|Mar( |ch|$)|Apr( |il|$)|May(?![a-z0-9])|Jun( |e|$)|Jul( |y|$)|Aug( |ust|$)|Sep( |tember|$)|O(c|k)t( |ober|$)|Nov( |ember|$)|De(c|z)( |ember|$))/i', $releasename) && !preg_match('/Part \d+/i', $releasename)) {
				echo $this->c->headerOver('Changing category to magazines: ') . $this->c->primary($releasename);
				$db = $this->db;
				$db->queryExec(sprintf('UPDATE releases SET categoryid = %d WHERE id = %d', 8030, $releaseID));
				return false;
			} else if (!empty($releasename) && !preg_match('/^[a-z0-9]+$|^([0-9]+ ){1,}$|Part \d+/i', $releasename)) {
				return $releasename;
			} else {
				return false;
			}
		} else if ($releasetype == 'audiobook') {
			if (!empty($releasename) && !preg_match('/^[a-z0-9]+$|^([0-9]+ ){1,}$|Part \d+/i', $releasename)) {
				// we can skip category for audiobooks, since we already know it, so as long as the release name is valid return it so that it is postprocessed by amazon.  In the future, determining the type of audiobook could be added (Lecture or book), since we can skip lookups on lectures, but for now handle them all the same way
				return $releasename;
			} else {
				return false;
			}
		}
	}

	public function updateBookInfo($bookInfo = '', $amazdata = null)
	{
		$db = $this->db;
		$ri = new ReleaseImage();

		$book = array();

		if ($bookInfo != '') {
			$amaz = $this->fetchAmazonProperties($bookInfo);
		} else if ($amazdata != null) {
			$amaz = $amazdata;
		}

		if (!$amaz) {
			return false;
		}

		$book['title'] = (string) $amaz->Items->Item->ItemAttributes->Title;

		$book['author'] = (string) $amaz->Items->Item->ItemAttributes->Author;

		$book['asin'] = (string) $amaz->Items->Item->ASIN;

		$book['isbn'] = (string) $amaz->Items->Item->ItemAttributes->ISBN;
		if ($book['isbn'] == '') {
			$book['isbn'] = 'null';
		}

		$book['ean'] = (string) $amaz->Items->Item->ItemAttributes->EAN;
		if ($book['ean'] == '') {
			$book['ean'] = 'null';
		}

		$book['url'] = (string) $amaz->Items->Item->DetailPageURL;
		$book['url'] = str_replace("%26tag%3Dws", "%26tag%3Dopensourceins%2D21", $book['url']);

		$book['salesrank'] = (string) $amaz->Items->Item->SalesRank;
		if ($book['salesrank'] == '') {
			$book['salesrank'] = 'null';
		}

		$book['publisher'] = (string) $amaz->Items->Item->ItemAttributes->Publisher;
		if ($book['publisher'] == '') {
			$book['publisher'] = 'null';
		}

		$book['publishdate'] = date('Y-m-d', strtotime((string) $amaz->Items->Item->ItemAttributes->PublicationDate));
		if ($book['publishdate'] == '') {
			$book['publishdate'] = 'null';
		}

		$book['pages'] = (string) $amaz->Items->Item->ItemAttributes->NumberOfPages;
		if ($book['pages'] == '') {
			$book['pages'] = 'null';
		}

		if (isset($amaz->Items->Item->EditorialReviews->EditorialReview->Content)) {
			$book['overview'] = strip_tags((string) $amaz->Items->Item->EditorialReviews->EditorialReview->Content);
			if ($book['overview'] == '') {
				$book['overview'] = 'null';
			}
		} else {
			$book['overview'] = 'null';
		}

		if (isset($amaz->Items->Item->BrowseNodes->BrowseNode->Name)) {
			$book['genre'] = (string) $amaz->Items->Item->BrowseNodes->BrowseNode->Name;
			if ($book['genre'] == '') {
				$book['genre'] = 'null';
			}
		} else {
			$book['genre'] = 'null';
		}

		$book['coverurl'] = (string) $amaz->Items->Item->LargeImage->URL;
		if ($book['coverurl'] != '') {
			$book['cover'] = 1;
		} else {
			$book['cover'] = 0;
		}

		if ($db->dbSystem() == 'mysql') {
			$bookId = $db->queryInsert(sprintf("INSERT INTO bookinfo (title, author, asin, isbn, ean, url, salesrank, publisher, publishdate, pages, overview, genre, cover, createddate, updateddate) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, now(), now()) ON DUPLICATE KEY UPDATE title = %s, author = %s, asin = %s, isbn = %s, ean = %s, url = %s, salesrank = %s, publisher = %s, publishdate = %s, pages = %s, overview = %s, genre = %s, cover = %d, createddate = NOW(), updateddate = NOW()", $db->escapeString($book['title']), $db->escapeString($book['author']), $db->escapeString($book['asin']), $db->escapeString($book['isbn']), $db->escapeString($book['ean']), $db->escapeString($book['url']), $book['salesrank'], $db->escapeString($book['publisher']), $db->escapeString($book['publishdate']), $book['pages'], $db->escapeString($book['overview']), $db->escapeString($book['genre']), $book['cover'], $db->escapeString($book['title']), $db->escapeString($book['author']), $db->escapeString($book['asin']), $db->escapeString($book['isbn']), $db->escapeString($book['ean']), $db->escapeString($book['url']), $book['salesrank'], $db->escapeString($book['publisher']), $db->escapeString($book['publishdate']), $book['pages'], $db->escapeString($book['overview']), $db->escapeString($book['genre']), $book['cover']));
		} else if ($db->dbSystem() == 'pgsql') {
			$check = $db->queryOneRow(sprintf('SELECT id FROM bookinfo WHERE asin = %s', $db->escapeString($book['asin'])));
			if ($check === false) {
				$bookId = $db->queryInsert(sprintf("INSERT INTO bookinfo (title, author, asin, isbn, ean, url, salesrank, publisher, publishdate, pages, overview, genre, cover, createddate, updateddate) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, now(), now())", $db->escapeString($book['title']), $db->escapeString($book['author']), $db->escapeString($book['asin']), $db->escapeString($book['isbn']), $db->escapeString($book['ean']), $db->escapeString($book['url']), $book['salesrank'], $db->escapeString($book['publisher']), $db->escapeString($book['publishdate']), $book['pages'], $db->escapeString($book['overview']), $db->escapeString($book['genre']), $book['cover']));
			} else {
				$bookId = $check['id'];
				$db->queryExec(sprintf('UPDATE bookinfo SET title = %s, author = %s, asin = %s, isbn = %s, ean = %s, url = %s, salesrank = %s, publisher = %s, pages = %s, overview = %s, genre = %s, cover = %d, updateddate = NOW() WHERE id = %d', $db->escapeString($book['title']), $db->escapeString($book['author']), $db->escapeString($book['asin']), $db->escapeString($book['isbn']), $db->escapeString($book['ean']), $db->escapeString($book['url']), $book['salesrank'], $db->escapeString($book['publisher']), $db->escapeString($book['publishdate']), $book['pages'], $db->escapeString($book['overview']), $db->escapeString($book['genre']), $book['cover'], $bookId));
			}
		}

		if ($bookId) {
			if ($this->echooutput) {
				echo $this->c->header("\nAdded/updated book: ");
				if ($book['author'] !== '') {
					echo $this->c->alternateOver("   Author: ") . $this->c->primary($book['author']);
				}
				echo $this->c->alternateOver("   Title: ") . $this->c->primary(" " . $book['title']);
				if ($book['genre'] !== 'null') {
					echo $this->c->alternateOver("   Genre: ") . $this->c->primary(" " . $book['genre'] . "\n");
				} else {
					echo "\n\n";
				}
			}

			$book['cover'] = $ri->saveImage($bookId, $book['coverurl'], $this->imgSavePath, 250, 250);
		} else {
			if ($this->echooutput) {
				echo $this->c->header('Nothing to update: ') . $this->c->header($book['author'] . ' - ' . $book['title']);
			}
		}
		return $bookId;
	}

}
