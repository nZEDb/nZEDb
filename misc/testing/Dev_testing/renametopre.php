<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/consoletools.php");
require_once(WWW_DIR."lib/namecleaning.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/releases.php");

if (!(isset($argv[1]) && ($argv[1]=="full" || is_numeric($argv[1]))))
	exit("To run this script on full db:\nphp renametopredb.php full\n\nTo run against last x(where x is numeric) hours:\nphp renametopre.php x\n");

preName($argv);

function preName($argv)
{
	$db = new DB();
	$groups = new Groups();
	$category = new Category();
	$updated = 0;
	$cleaned = 0;
	$counter=1;
	$n = "\n";
	echo "Resetting blank searchnames\n";
	$bad = $db->prepare("UPDATE releases SET searchname = name WHERE searchname = ''");
	$bad->execute();
	$tot = $bad->rowCount();
	if ($tot > 0)
		echo $tot." Releases had no searchname\n";
	echo "Getting work\n";
	if (isset($argv[1]) && $argv[1]=="full")
		$res = $db->prepare("select id, name, searchname, groupid, categoryid from releases where reqidstatus != 1 and ( relnamestatus in (0, 1, 7, 20, 21, 22) or categoryid between 7000 and 7999) and nzbstatus = 1");
	elseif (isset($argv[1]) && is_numeric($argv[1]))
		$res = $db->prepare(sprintf("select id, name, searchname, groupid, categoryid from releases where reqidstatus != 1 and ( relnamestatus in (0, 1, 7, 20, 21, 22) or categoryid between 7000 and 7999) and nzbstatus = 1 and adddate > NOW() - INTERVAL %d HOUR", $argv[1]));
	$res->execute();
	$total = $res->rowCount();
	if ($total > 0)
	{
		$consoletools = new ConsoleTools();
		foreach ($res as $row)
		{
		   if($cleanerName = releaseCleaner($row['name'], $row['groupid'], $row['id']))
			{
				if ( $cleanerName != $row['name'] && $cleanerName != '' )
				{
					$determinedcat = $category->determineCategory($cleanerName, $row["groupid"]);
					$run = $db->prepare(sprintf("UPDATE releases set relnamestatus = 16, searchname = %s, categoryid = %d where id = %d", $db->escapeString($cleanerName), $determinedcat, $row['id']));
					$run->execute();
					$groupname = $groups->getByNameByID($row["groupid"]);
					$oldcatname = $category->getNameByID($row["categoryid"]);
					$newcatname = $category->getNameByID($determinedcat);
					/*echo	$n."New name:  ".$cleanerName.$n.
						"Old name:  ".$row["searchname"].$n.
						"New cat:   ".$newcatname.$n.
						"Old cat:   ".$oldcatname.$n.
						"Group:     ".$groupname.$n.
						"Method:    "."jonnyboy's regexes".$n.
						"ReleaseID: ". $row["id"].$n;*/
					$updated++;
				}
			}
			//if ( $cleanerName == $row['name'])
				//$db->queryExec(sprintf("UPDATE releases set relnamestatus = 16 where id = %d", $row['id']));
			$consoletools->overWrite("Renamed NZBs: [".$updated."] ".$consoletools->percentString($counter++,$total));
		}
	}
	echo "\n".$updated." out of ".$total." Releases renamed\n";
	echo "Categorizing all non-categorized releases in other->misc using usenet subject. This can take a while, be patient.\n";
	$timestart = TIME();
	if (isset($argv[1]) && $argv[1]=="full")
		$relcount = categorizeRelease("name", "WHERE categoryID = 7010", true);
	else
		$relcount = categorizeRelease("name", "WHERE categoryID = 7010 AND adddate > NOW() - INTERVAL 12 HOUR", true);
	$consoletools = new ConsoleTools();
	$time = $consoletools->convertTime(TIME() - $timestart);
	echo "\n"."Finished categorizing ".$relcount." releases in ".$time." seconds, using the usenet subject.\n";

	echo "Categorizing all non-categorized releases in other->misc using searchname. This can take a while, be patient.\n";
	$timestart = TIME();
	if (isset($argv[1]) && $argv[1]=="full")
		$relcount = categorizeRelease("searchname", "WHERE categoryID = 7010", true);
	else
		$relcount = categorizeRelease("searchname", "WHERE categoryID = 7010 AND adddate > NOW() - INTERVAL 12 HOUR", true);
	$consoletools = new ConsoleTools();
	$time = $consoletools->convertTime(TIME() - $timestart);
	echo "\n"."Finished categorizing ".$relcount." releases in ".$time." seconds, using the searchname.\n";
}

	// Categorizes releases.
	// $type = name or searchname
	// Returns the quantity of categorized releases.
	function categorizeRelease($type, $where="", $echooutput=false)
	{
		$db = new DB();
		$cat = new Category();
		$consoletools = new consoleTools();
		$relcount = 0;
		$resrel = $db->query("SELECT id, ".$type.", groupid FROM releases ".$where);
		$total = count($resrel);
		if (count($resrel) > 0)
		{
			foreach ($resrel as $rowrel)
			{
				$catId = $cat->determineCategory($rowrel[$type], $rowrel['groupid']);
				$db->queryExec(sprintf("UPDATE releases SET categoryid = %d WHERE id = %d", $catId, $rowrel['id']));
				$relcount ++;
				if ($echooutput)
					$consoletools->overWrite("Categorizing:".$consoletools->percentString($relcount,$total));
			}
		}
		if ($echooutput !== false && $relcount > 0)
			echo "\n";
		return $relcount;
	}


function releaseCleaner($subject, $groupid, $id)
{
	$groups = new Groups();
	$groupName = $groups->getByNameByID($groupid);

	if ($groupName === "alt.binaries.classic.tv.shows")
	{
		if (preg_match('/^(?P<title>.+?\d+x\d+.+?)[ -]{0,3}\[\d+\/\d+\] - ".+?" (yEnc|rar|par2)$/i', $subject, $match))
		{
			$cleanerName = preg_replace('/^REQ[ -]{0,3}/i', '', preg_replace('/\.+$/', '', trim($match['title'])));
			if (!empty($cleanerName))
				return $cleanerName;
		}
		//YANCY DERRINGER 109 Memo to a Firing Squad [1 of 13] "YANCY DERRINGER 109 Memo to a Firing Squad.vol127+73.par2" yEnc
		elseif (preg_match('/^(?P<title>Yancy.+?) \[\d+ of \d+\].+?".+?" yEnc/i', $subject, $match))
		{
			$cleanerName = $match['title'];
			if (!empty($cleanerName))
				return $cleanerName;
		}
		//"Yancy Derringer - E-27-Duel At The Oaks.part01.rar" yEnc
		elseif (preg_match('/"(?P<title>Yancy.+?).(par|zip|rar|nfo|txt).+?" yEnc/i', $subject, $match))
		{
			$cleanerName = $match['title'];
			if (!empty($cleanerName))
				return $cleanerName;
		}
		//[Gunsmoke Season 16 Episode 02  Avi Xvid][00/24] yEnc
		elseif (preg_match('/^7?\[(?P<title>.+?) ?(Avi Xvid)?\]\[\d+\/\d+\] yEnc/i', $subject, $match))
		{
			$cleanerName = $match['title'];
			if (!empty($cleanerName))
				return $cleanerName;
		}
		//(Gunsmoke Season 5 Episode 18 - 10 par files) [00/17] - "Gunsmoke S05E18 - Big Tom.avi.nzb" yEnc
		elseif (preg_match('/\((?P<title>.+?)\) \[\d+\/\d+\][ -]{0,3}".+?" yEnc/i', $subject, $match))
		{
			$cleanerName = $match['title'];
			if (!empty($cleanerName))
				return $cleanerName;
		}

	}
	//[39975]-[FULL]-[#a.b.foreign@EFNet]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[01/34] - "epz-the.cape.s01e10-sample.avi" yEnc
	//[39975]-[FULL]-[#a.b.foreign@EFNet]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[01/34] - #34;epz-the.cape.s01e10-sample.avi#34; yEnc
	//[39975]-[#a.b.foreign@EFNet]-[FULL]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[01/34] - "epz-the.cape.s01e10-sample.avi" yEnc
	//[39975]-[#a.b.foreign@EFNet]-[FULL]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[01/34] - #34;epz-the.cape.s01e10-sample.avi#34; yEnc
	//[39975]-[FULL]-[#a.b.foreign@EFNet]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[REPOST]-[01/34] - "epz-the.cape.s01e10-sample.avi" yEnc
	//[39975]-[FULL]-[#a.b.foreign@EFNet]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[REPOST]-[01/34] - #34;epz-the.cape.s01e10-sample.avi#34; yEnc
	//[39975]-[#a.b.foreign@EFNet]-[FULL]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[REPOST]-[01/34] - "epz-the.cape.s01e10-sample.avi" yEnc
	//[39975]-[#a.b.foreign@EFNet]-[FULL]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[REPOST]-[01/34] - #34;epz-the.cape.s01e10-sample.avi#34; yEnc
	//[37090]-[#a.b.foreign@EFNet]-[ Alarm.fuer.Cobra.11.S30E06.German.SATRip.XviD-ITG ]-[04/33] - "itg-c11-s30e06-sample-sample.vol3+2.par2" yEnc
	//[270512]-[FULL]-[Koh.Lanta.La.Revanche.Des.Heros.Cambodge.E08.FRENCH.720p.HDTV.x264-TTHD] [01/75] - "kohlanta.cambodge.e08.720p.hdtv.x264-sample.mkv" yEnc
	//The most matches, run first
	//1392665 out of 5397880 Releases renamed
	if (preg_match('/^\[\d*\][- ]{0,3}(\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3}\[[- #@\.\w]+\][- ]{0,3}|\[[- #@\.\w]+\][- ]{0,3}\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3}|\[.+?efnet\][- ]{0,3}|\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3})(\[(FULL|REPOST)\])?[- ]{0,3}(\[ )?(\[)? ?(\/sz\/)?(F: - )?(?P<title>[- _!@\.\'\w\(\)~]{10,}) ?(\])?[- ]{0,3}(\[)? ?(REPOST|REPACK|SCENE|EXTRA PARS|REAL)? ?(\])?[- ]{0,3}?(\[\d+[-\/~]\d+\])?[- ]{0,3}["|#34;]*.+["|#34;]* ?[yEnc]{0,4}/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//Same as above without leading [
	if (preg_match('/^\d*\][- ]{0,3}(\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3}\[[- #@\.\w]+\][- ]{0,3}|\[[- #@\.\w]+\][- ]{0,3}\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3}|\[.+?efnet\][- ]{0,3}|\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3})(\[(FULL|REPOST)\])?[- ]{0,3}(\[ )?(\[)? ?(\/sz\/)?(F: - )?(?P<title>[- _!@\.\'\w\(\)~]{10,}) ?(\])?[- ]{0,3}(\[)? ?(REPOST|REPACK|SCENE|EXTRA PARS|REAL)? ?(\])?[- ]{0,3}?(\[\d+[-\/~]\d+\])?[- ]{0,3}["|#34;]*.+["|#34;]* ?[yEnc]{0,4}/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[2410]-[abgx.net]-[My_Weight_Loss_Coach_USA_NDS-CNBS]-[2/4]-"cnbs-mwlc.par2" yEnc
	//64153 out of 3767201
	elseif (preg_match('/^\[\d+\][ -]{0,3}\[.+\][ -]{0,3}\[(?P<title>.+?)\][ -]{0,3}\[\d+\/\d+\][ -]{0,3}["|#34;].+["|#34;][ -]{0,3}yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<kere.ws> - MViD - 1341305405 - Metallica.Orion.Music.Festival.2012.AC3.HDTV.720p.x264-TSCC - [01/89] - "Metallica.Orion.Music.Festival.2012.AC3.HDTV.720p.x264-TSCC-thumb.jpg" yEnc
	//60110 out of 3860631 Releases renamed
	elseif (preg_match('/^<kere\.ws> - \w+(-\w+)? - \d+ - (?P<title>.+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<TOWN><www.town.ag > <partner of www.ssl-news.info > [06/13] - "Grojband.S01E23E24.HDTV.x264-W4F.part04.rar" - 129,94 MB yEnc
	//42745 out of 3733698 Releases renamed
	elseif (preg_match('/^<TOWN><www.town.ag > <partner of www.ssl-news.info > \[\d+\/\d+\] - "(?P<title>.+?)\.(par|vol|rar|nfo).*?" - .+? yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
  	//<kere.ws> - TV - 1338205816 - Der.letzte.Bulle.S03E12.Ich.sags.nicht.weiter.German.DVDRip.XviD-iNTENTiON - [01/43] - "itn-der.letzte.bulle.s03e12.xvid-sample-sample.par2" yEnc (1/1)
	//30655 out of 3898036 Releases renamed
	elseif (preg_match('/^<kere.ws> - (TV|Filme) - \d+ - (?P<title>.+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<TOWN><www.town.ag > <partner of www.ssl-news.info > Once.Upon.a.Time.S02E01.720p.HDTV.X264-DIMENSION  [01/25] - "Once.Upon.a.Time.S02E01.720p.HDTV.X264-DIMENSION.par2" - 1,03 GB - yEnc
	//27627 out of 3690953 Releases renamed
	elseif (preg_match('/^<TOWN><www.town.ag > <partner of www.ssl-news.info > (?P<title>.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//(www.Thunder-News.org) >Zwei.Singles.im.Doppelbett.S01E06.Feiern.oder.Fischen.GERMAN.WS.dTV.XviD-FKKTV< <Sponsored by AstiNews> - (01/26) - "fkktv-almost_perfect-s01e06-sample.par2" yEnc
	//(www.Thunder-News.org)>Yu-Gi-Oh 1x29 - Duel Indentity (Part 1)<<Sponsored by Secretusenet> - [01/10] - "Yu-Gi-Oh 1x29 - Duel Indentity (Part 1).par2" yEnc (1/1)
	//24037 out of 3969392 Releases renamed
	elseif (preg_match('/^\(www\.Thunder-News\.org\) ?>(?P<title>.+?)< ?<Sponsored.+?> - (\(\d+\/\d+\)|\[\d+\/\d+\]) - ".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//>ghost-of-usenet.org<< 360.Grad.Geo.Reportage.-.Die.letzten.Kamelkarawanen.der.Sahara.GERMAN.DOKU.WS.720p.HDTV.x264-MiSFiTS >>www.SSL-News.info> -  "misfits-kamelkarawanen.r14" yEnc
	//21446 out of 3703048
	elseif (preg_match('/^>ghost-of-usenet\.org<< ?(?P<title>.+) ?>>www.+>[ -]{0,3}("|#34;)?.+("|#34;)? ?yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[ 14293 ] - [ TrollHD ] - [ 00/22 ] - "Last Man Standing S01E15 House of Spirits 720p HDTV DD5.1 MPEG2-TrollHD.nzb" yEnc (1/1)
	//21064 out of 3919100 Releases renamed
	elseif (preg_match('/^\[ ?\d+\ ?] - \[ ?TrollHD ?\] -  ?\[ ? ?\d+\/\d+ ? ?\] - "(?P<title>.+?)" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[ nEwZ[NZB].iNFO ] - [ The.Half.Hour.S02E11.Lil.Rel.Howery.HDTV.x264-YesTV ] - File [13/19]: "the.half.hour.0211-yestv.r10" yEnc
	//20902 out of 3788646 Releases renamed
	elseif (preg_match('/^\[ nEwZ\[NZB\]\.iNFO \] - \[ (?P<title>.+?) \] - File \[\d+\/\d+\]: ".+?" yEnc$/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//Alias.4x05.Benvenuti.Al.Liberty.Village.ITA-ENG.720p.DLMux.h264-NovaRip [01/40] - "alias.4x05.ita-eng.720p.dlmux.h264-novarip.nfo" yEnc
	//19622 out of 3989014 Releases renamed
	elseif (preg_match('/^(?P<title>.+?Novarip) \[\d+\/\d+\] - ".+?" yEnc$/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//(1/9) - [Lords-of-Usenet] <<Partner of SSL-News.info>> presents Breaking.In.S02E13.Episode.XIII.GERMAN.Dubbed.DVDRiP.XviD-idTV -"19104.par2" - 179,52 MB - yEnc
	//18195 out of 3767725 Releases renamed
	elseif (preg_match('/^\(\d+\/\d+\) - \[Lords-of-Usenet\] (<<|\(\()(Partner|Sponsor).+?(>>|\)\)) presents (?P<title>.+?) -("|#34;).+?("|#34;) .+? yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//(????) [02656/43619] - "2 Schulerinnen-Wer ist d.Klassenbeste beim Wixen.exe" yEnc
	//15074 out of 4004705 Releases renamed
	elseif (preg_match('/^\(\?+\) \[\d+\/\d+\][ -]{0,3}"(?P<title>.+?)" yEnc$/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//(www.Thunder-News.org) >Die.Schatzsucher.Helden.unter.Tage.S01E07.Wintereinbruch.GERMAN.DOKU.WS.SATRip.XviD-TVP< <Sponsored by AstiNews> -  - "tvp-coal-s01e07-xvid.vol15+12.par2" yEnc (1/25)
	//(www.Thunder-News.org) >The.Borgias.S02E08.HDTV.x264-ASAP< <Sponsored by Secretusenet> -  "the.borgias.s02e08.hdtv.x264-asap.nfo" yEnc
	//14982 out of 3934082 Releases renamed
	elseif (preg_match('/^\(www\.Thunder-News\.org\) ?>(?P<title>.+?)< <Sponsored.+?> -  -? ?".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[foreign]-[ PowNews.S02E87.DUTCH.WS.PDTV.XviD-iFH ] [01/24] - #34;PowNews.S02E87.DUTCH.WS.PDTV.XviD-iFH.par2#34; yEnc
	//[foreign]-[ El.Barco.S03E13.SPANiSH.HDTV.x264-FCC ] [01/44] - "El.Barco.S03E13.SPANiSH.HDTV.x264-FCC.par2" yEnc
	//11286 out of 3799939 Releases renamed
	elseif (preg_match('/^\[foreign\]-\[ (?P<title>.+?) \][- ]?\[\d+\/\d+\] - ("|#34;).+?("|#34;) yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//(www.Thunder-News.org) )Aus.Versehen.gluecklich.S01E02.Alles.ueber.Zack.GERMAN.DUBBED.WS.DVDRip.XviD-TVP( (Sponsored by AstiNews) - (03/20) - #34;tvp-gluecklich-s01e02-xvid-sample.avi#34; yEnc
	//11273 out of 3945355 Releases renamed
	elseif (preg_match('/^\(www\.Thunder-News\.org\) ?\)(?P<title>.+?)\( \(Sponsored.+?\) - \(\d+\/\d+\) - ("|#34;).+?("|#34;) yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<<<usenet-space-cowboys.info>>> THOR <<<Powered by https://secretusenet.com>< "Trucker.in.gefaehrlicher.Mission.S01E01.Abenteuer.Himalaja.GERMAN.DUBBED.DOKU.WS.HDTVRip.XviD-TVP_usenet-space-cowbys.info.avi" >< 03/15 (404.96 MB) >< 11.21 MB > yEnc
	//7519 out of 3590865 Releases renamed
	elseif (preg_match('/^<<<usenet-space-cowboys.info>>>.+?>< "(?P<title>.+?)_usenet-space-cowbys.+?" >< \d+\/\d+ \(.+?\) ><.+?> yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<kere.ws/illuminatenboard.org> - ID - 1291273600 - Schluessel.zur.Vergangenheit.Das.Bermudadreieck.GERMAN.DOKU.720p.HDTV.x264-TVP [01/30] - "1291273600.par2" yEnc (1/1) (1/1)
	//6689 out of 3867320 Releases renamed
	elseif (preg_match('/^<kere\.ws\/illuminatenboard\.org> - ID - \d+ - (?P<title>.+?) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//Korn.Live.On.The.Other.Side.2006.Blu-ray.1080p.AVC.DTS-HD.5.1-TrollHD [der.Angler fuer usenet-4all.info]-[powered by U4all]-(01/84) "Korn.Live.On.The.Other.Side.2006.Blu-ray.1080p.AVC.DTS-HD.5.1-TrollHD.par2" yEnc
	//5289 out of 3742592 Releases renamed
	elseif (preg_match('/^(?P<title>.+?)\[.+?usenet-4all.info\]-\[.+?\]-\(\d+\/\d+\) ".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[Lords-of-Usenet.org] <Sponsored by SSL-News.info> proudly presents: V.2009.S01E10.German.Dubbed.BDRip.XviD-MiRAMAX [01/28] - "mm-v-s01e10.nfo" yEnc
	//5138 out of 3662650 Releases renamed
	elseif (preg_match('/^\[Lords-of-Usenet\.org\]( |_)<Sponsored.+?> proudly presents:(?P<title>.+?) \[\d+\/\d+\] - ("|#34;).+?("|#34;) yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[:SEK9:][TV]-[:Cow.And.Chicken.S04E12.Part.1.DUTCH.PDTV.XViD-SPiROTV:]-[1/4]-"Cow.And.Chicken.S04E12.Part.1.DUTCH.PDTV.XViD-SPiROTV.par2" yEnc (1/1)
	//5033 out of 3749523 Releases renamed
	elseif (preg_match('/^\[:sek9:\]\[[-\w]+\]-\[:(?P<title>.+?):\]-\[\d+\/\d+\]-".+?" yEnc$/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//View.from.the.Top.Flight.Girls.2003.German.DL.720p.WEB-DL.h264-msd [ich for usenet-4all.info] [ich25882] [powered by ssl-news.info] (01/70) "ich25882.par2" yEnc
	//3500 out of 3737198 Releases renamed
	elseif (preg_match('/^(?P<title>.+?)\[.+?usenet-4all.info\](-| )?\[.+?\](-| )?\(\d+\/\d+\) ".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<<<Thor2204>>><<<Nam.Dienst.im.Vietnam.S02E04.Der.Gefreite.Martsen.GERMAN.FS.DVDRip.xviD-aWake>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "awa-namdivs02e04.nfo" >< 02/31 (432,76 MB) >< 8,86 kB > yEnc (1/1)
	//3792 out of 3656696 Releases renamed
	elseif (preg_match('/^<<<(Thor2204|Thor)>>><<<(?P<title>.+?)>>>usenet-space-cowboys.+?<<<Powered.+?>< ".+?".+? > yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<<<Nimue>>><<<Terra.Xpress.Achtung.extrem.giftig.GERMAN.DOKU.HDTV.720p.x264-iNFOTv>>> usenet-space-cowboys.info <<<Powered by https://secretusenet.com>< "infotv-terra.xpress_ahegiftig_720p.nfo" >< 02/28 (1000,46 MB) >< 1,70 kB > yEnc (1/1)
	//3035 out of 3652246 Releases renamed
	elseif (preg_match('/^<<<Nimue>>><<<(?P<title>.+?)>>>.+?Powered by.+? yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[Lie.to.me.S02E01.Gespalten.GERMAN.DUBBED.DL.WS.720p.HDTV.PROPER.x264-euHD]-[ich for usenet-4all.info]-[ich14126]-[powered by Dreamload.com] (001/108) "ich14126.par2" yEnc (1/1)
	//1898 out of 3744490 Releases renamed
	elseif (preg_match('/^\[(?P<title>.+?)\]-\[.+?usenet-4all.info]-\[.+?\]-\[.+?\] \(\d+\/\d+\) ".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<<<Friends.S01E13>>>CowBoyUp26<<<Powered by https://secretusenet.com>< "Friends.S01E13.Der.Superbusen.German.FS.DVDRiP.XviD.INTERNAL-MOViESToRE_usenet-space-cowboys.info.nfo" >< 02/10 (256,07 MB) >< 7,51 kB > yEnc (1/1)
	//1598 out of 3649211 Releases renamed
	elseif (preg_match('/^<<<.+?>>>CowBoyUp26<<<Powered by.+?>< "(?P<title>.+?)_usenet-space-cowboys.+?" >< \d+\/\d+ \(.+?\) >< .+? > yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<kere.ws> The.Middle.S01E12.HDTV.XviD-P0W4 [01/21] - "the.middle.s01e12.hdtv.par2" yEnc
	//582 out of 3800521 Releases renamed
	elseif (preg_match('/^<kere\.ws> (?P<title>.+?) \[\d+\/\d+\] - ".+?/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
   	//panter - [40/41] - "675367-Monte Carlo 2011 BRRip XviD AC3 REFiLL NL ingebakken.vol069+69.PAR2" yEnc
   	//480 out of 4005215 Releases renamed
	elseif (preg_match('/^(\(snake\)|panter|wildrose|shadowman|P2H)[ -]{0,3}(\[\d+\/\d+\])?[ -]{0,3}"(info-|P2H-)?(?P<title>.+?)( |\.| \.)?(part\d+\.rar|vol\d+\+\d+\.par2|rar\.vol\d+\+\d+\.PAR2|par2|rar|rar\.par2|mkv\.par2|(avi|dvd5|mkv)\.part\d+\.rar|nfo|zip|nzb)" yEnc$/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[www.allyourbasearebelongtous.pw]-[Mike.And.Molly.S03.NTSC.DVDR-ToF]-[002/106] "mam.s3d1.tof.par2" - 4.85 GB - yEnc
	//324 out of 3989549 Releases renamed
	elseif (preg_match('/^\[www.allyourbasearebelongtous.pw\]-\[(?P<title>.+?)\] ".+?" - .+? yEnc$/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//( Criminal.Minds.S06E16.Am.Ende.des.Traums.GERMAN.DUBBED.WS.DVDRiP.XviD-SOF ) )ghost-of-usenet.org( - (05/34) )www.SSL-News.info( - #34;sof-criminal.minds.s06e16.r00#34; yEnc
	//211 out of 3989225 Releases renamed
	elseif (preg_match('/^\( ?(?P<title>.+? ?)\) ?\)ghost-of-usenet.org\([- ]{0,3}\(\d+\/\d+\) ?\).+?["|#34;]*.+["|#34;]* ?yEnc$/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//(Public) (FULL) (a.b.teevee@EFNet) [04/13] (????) [001/101] - "S01E10.720p.HDTV.X264-DIMENSION (1).nzb" yEnc
	//82 out of 3989631 Releases renamed
	elseif (preg_match('/^\(Public\) ?\(FULL\) ?\(.+?efnet\) ?\[\d+\/\d+\] ?\(\?+\) ?\[\d+\/\d+\][ -]{0,3}"(?P<title>.+?) ?(\(1\))?( |\.| \.)?(part\d+\.rar|vol\d+\+\d+\.par2|rar\.vol\d+\+\d+\.PAR2|par2|rar|rar\.par2|mkv\.par2|(avi|dvd5|mkv)\.part\d+\.rar|nfo|zip|nzb)" yEnc/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//P2H - Angry_Birds_Trilogy_EUR_3DS-ABSTRAKT - "as-abt.par2" yEnc
	//30 out of 4004735 Releases renamed
	elseif (preg_match('/^P2H - (?P<title>.+?) - ".+?" yEnc$/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<kere.ws> [ The.Middle.S03.COMPLETE.720p.WEB-DL.DD5.1.H.264-EbP ]-[644/911] "The.Middle.S03E18.720p.WEB-DL.DD5.1.H.264-EbP.par2" yEnc
	//61 out of 3867381 Releases renamed
	elseif (preg_match('/^<kere\.ws> \[ (?P<title>.+?) \]-\[\d+\/\d+\] ".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//(01/68) - "melodifestivalen.2013.deltavling.2.swedish.720p.hdtv.x264-xd2v.nfo" 2,90 GB - [Foreign] Melodifestivalen.2013.Deltavling.2.SWEDiSH.720p.HDTV.x264-xD2V yEnc
	//3 out of 3788653 Releases renamed
	elseif (preg_match('/\(\d+\/\d+\) - ".+?" .+? \[Foreign\] (?P<title>.+?) yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[foreign]-[ Dicte.S01E05.DANiSH.HDTV.x264-TVBYEN ] - "Dicte.S01E05.DANiSH.HDTV.x264-TVBYEN.nfo"
	//4 out of 3788650 Releases renamed
	elseif (preg_match('/^\[foreign\]- ?\[ (?P<title>.+?) \] - ("|#34;).+?("|#34;)/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[ nEwZ[NZB].iNFO - [ The.Middle.S04E14.The.Smile.GERMAN.DUBBED.WS.WEBRip.XviD-TVP ] - File [03/12]: "tvp-themiddle-s04e14-xvid.r01" yEnc
	//19 out of 3767744 Releases renamed
	elseif (preg_match('/^\[ nEwZ\[NZB\]\.iNFO - \[ (?P<title>.+?) \] - File \[\d+\/\d+\]: ("|#34;).+?("|#34;) yEnc$/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[Lords-of-Usenet.org]_[Partner von SSL-News.info](001/179) "Flashpoint Staffel 5 HDTV 720p engl. + dt. Sub.par2" yEnc
	//7 out of 3749530 Releases renamed
	elseif (preg_match('/^\[Lords-of-Usenet\.org\]( |_)\[Partner.+?\]\(\d+\/\d+\) "(?P<title>.+?)" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//Being.Erica.S03E01.Nicht.mehr.allein.German.DD20.Dubbed.DL.720p.iTunesHD.AVC-TVS [ich for usenet-4all.info]-[ich18707]- "ich18707.nfo" yEnc (1/105)
	//105 out of 3737303 Releases renamed
	elseif (preg_match('/^(?P<title>.+?)\[.+?usenet-4all.info\]-\[.+?\]- ".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//(02/13) "Lipstick.Jungle.S01E02.Nichts.ist.heilig.GERMAN.DUBBED.DL.WS.720p.HDTV.x264-euHD www.brothers-of-usenet.org - empfehlen - Newsconnection.eu.part1.rar" yEnc
	//423 out of 3663326 Releases renamed
	elseif (preg_match('/^\(\d+\/\d+\) "(?P<title>.+?) www.brothers-of-usenet.org .+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[Lords-of-Usenet]_[Partner von SSL-News.info](412/554) "Spartacus Vengeance Staffel 2 HDTV 720p engl. + dt. Sub.part040.rar" yEnc
	//253 out of 3662903 Releases renamed
	elseif (preg_match('/^\[Lords-of-Usenet\]( |_)(<<|\(\(|\[)(Partner|Sponsor).+?(>>|\)\)|\])\(\d+\/\d+\) "(?P<title>.+?)" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[Lords-of-Usenet.org] <<Sponsored by SSL-News.info>> - Lie.to.me.Staffel2.DVD3.GERMAN.2009.WS.DL.DVDR-aWake- (03/99) - "awa-lietomes02d03.r00" - 4,46 GB - yEnc
	//94 out of 3657512 Releases renamed
	elseif (preg_match('/^\[Lords-of-Usenet\.org\]( |_)<?<Sponsored.+?>>? - (?P<title>.+?)-? \(\d+\/\d+\) - ("|#34;).+?("|#34;) - .+? yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[www.Lords-of-Usenet.org]_[Sposnored by SSL_News.info](001/193) "Emergency.Room Staffel 4 DL.German.Dubbed.720p.WEB-DL.x264-FREAKS E12-E22.par2" yEnc
	//602 out of 3657418 Releases renamed
	elseif (preg_match('/\[www\.Lords-of-Usenet\.org\]_\[.+?\]\(\d+\/\d+\) "(?P<title>.+?)" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[Lords-of-Usenet.org]_<Sponsored_by_SSL-News_info>_Proudly_presents_Herzflimmern.S01E30.Die.Klinik.am.See.GERMAN.WS.dTV.XViD-SiTiN [01/28] - "sitin-hf-s01e30-xvid.nfo" yEnc
	//120 out of 3656816 Releases renamed
	elseif (preg_match('/^\[Lords-of-Usenet\.org\]( |_)<?<Sponsored.+?>>?_(?P<title>.+?) \[\d+\/\d+\] - ("|#34;).+?("|#34;) yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//Brothers-of-Usenet.org - Newsconnection.eu "Emergency.Room.S02DVD2.DVDR.German.DL.BoU"[086/100] - "BoU-ER-S2D2.part084.rar" yEnc
	//531 out of 3652904 Releases renamed
	elseif (preg_match('/^Brothers-of-Usenet.org .+? "(?P<title>.+?)"\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<<<MetalDept>>><<<Vallorch - Neverfade (2013)>>>Best Fucking Metal<<< "Vallorch - Neverfade (2013).par2">[01/14] 142,47 MB yEnc
	//568 out of 5398477 Releases renamed
	elseif (preg_match('/^<<<MetalDept>>><<<(?P<title>.+?)>>>.+?<<< ".+?">\[\d+\/\d+\].+? yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<<<MetalDept>>><<<Amberian Dawn - Re-Evolution - 2013 (320 kbps)>>>usenet-of-outlaws.info-Powered by SecretUsenet.com<<< "Amberian Dawn - Re-Evolution - 2013 (320 kbps).par2">[01/16] 161,76 MB
	//238 out of 5398477 Releases renamed
	elseif (preg_match('/^<<<MetalDept>>><<<(?P<title>.+?)>>>usenet-of-outlaws.info.+?<<< ".+?">\[\d+\/\d+\].+?/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//Brothers-of-Usenet.org (265/323) "Mayday-Alarm.im.Cockpit.S04E10.Geisterflug.Helios.522.German.DL.Doku.WS.SatRip.XviD-fBi.par2" - 6,00 GB Newsconnection.eu yEnc
	//127 out of 3652373 Releases renamed
	elseif (preg_match('/^Brothers-of-Usenet.org \(\d+\/\d+\) "(?P<title>.+?)\.(par|rar|nfo|vol).+?" - .+? yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[NZBMatrix.com]-[ The.Sopranos.S01.iNTERNAL.WS.AC3.DVDRip.XviD-SAiNTS ] [647/799] - "the.sopranos.s01e11.ws.ac3.dvdrip.xvid-saints.part29.rar" yEnc
	//108 out of 3583346 Releases renamed
	elseif (preg_match('/^\[NZBMatrix\.com\]-\[ (?P<title>.+?) \] \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[ VintageReduction ]-[ the.jeselnik.offensive.s01e01.THIRTY.TO.ONE.file.size.reduction.Please.Read ]-[01/16] - "the.burn.with.jeff.ross.s02e01.25.to.1.reduction.by.vintage.PAR2" yEnc (1/1)
	//1 out of 3583238 Releases renamed
	elseif (preg_match('/^\[ VintageReduction \]-\[ (?P<title>.+?) \][- ]?\[\d+\/\d+\] - .+? yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//>ghost-of-usenet.org<Die.Bill.Cosby.Show.S07.German.xvid>Sponsored by Astinews< (529/576) "fkktv-cosby-s07e23.nfo" yEnc
	//1798 out of 3681602
	elseif (preg_match('/^>ghost-of-usenet\.org< ?(?P<title>.+)>Sponsored.+< ?\(\d+\/\d+\)[ -]{0,3}("|#34;)?.+("|#34;)?[ -]{0,3}yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//< Grimm.S01E10.German.Subbed.HDTV.XviD-LOL.by.GhostUp10 > >ghost-of-usenet.org< - (01/27) >www.SSL-News.info< - "gu10maerchen110.par2" yEnc
	//862 out of 3679804
	elseif (preg_match('/^<[ -]{0,3}(?P<title>.+)\.by\.GhostUp10[ -]{0,3}> ?>ghost-of-usenet\.org<[ -]{0,3}\(\d+\/\d+\)[ -]{0,3}>www.+<[ -]{0,3}("|#34;)?.+("|#34;)?[ -]{0,3}yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//(((CowboyUp2012)))(((Hooded_Fang-Tosta_Mista-2012-SO)))usenet-space-cowboys.info(((Powered by https://secretusenet.com)( #34;Hooded_Fang-Tosta_Mista-2012-SO.rar#34; )( 3/4 (48,14 MB) )( 44,83 MB ) yEnc
	//
	elseif (preg_match('/^\(\(\(CowboyUp2012\)\)\)[ -]{0,3}\(\(\((?P<title>.+)\)\)\)[ -]{0,3}.+yEnc$/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<<<CowboyUp2012 Serie>>><<<Galileo.Big.Pictures.Die.Extremsten.Bilder.der.Welt.GERMAN.DOKU.WS.SATRiP.XviD-TVP>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "tvp-galileo-pictures-extreme-xvid.r24" >< 27/69 (1,18 GB) >< 19,07 MB > yEnc
	//
	elseif (preg_match('/^<<<CowboyUp2012.+>>>[ -]{0,3}<<<(?P<title>.+)>>>[ -]{0,3}.+yEnc$/i', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[ TOWN ]-[ www.town.ag ]-[ Breaking.Bad.S05E14.HDTV.x264-ASAP ]-[01/39]- "breaking.bad.s05e14.hdtv.x264-asap.nfo" yEnc
	elseif (preg_match('/^\[ TOWN \][ -]{0,3}\[ www\.town\.ag \][ -]{0,3}\[ (?P<title>.+?) \][ -]{0,3}\[\d+\/\d+\][ -]{0,3}".+?\.(par|vol|rar|nfo).*?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ PR0N ] [17/21] - "SexVideoCasting.13.09.30.Judy.Smile.XXX.1080p.MP4-SEXORS.vol00+1.par2" - 732,59 MB yEnc
	//[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ MUSIC ] [04/26] - "VA_-_Top_30_Dance_Club_Play-2013-SL.part02.rar" - 325,10 MB yEnc
	elseif (preg_match('/^\[ TOWN \][ -]{0,3}\[ www\.town\.ag \][ -]{0,3}\[ partner of www\.ssl-news\.info \][ -]{0,3}\[ .*? \] \[\d+\/\d+\][ -]{0,3}"(?P<title>.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|")[ -]{0,3}/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ] [21/21] - "CzechCasting.13.09.23.Edita.1446.XXX.720p.MP4-SEXORS.vol3+3.par2" - 242,32 MB yEnc
	elseif (preg_match('/^\[ TOWN \][ -]{0,3}\[ www\.town\.ag \][ -]{0,3}\[ partner of www\.ssl-news\.info \][ -]{0,3}\[\d+\/\d+\][ -]{0,3}"(?P<title>.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|")[ -]{0,3}.+? yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//<TOWN><www.town.ag > Breaking.Bad.S05E16.720p.WEB-DL.DD5.1.H.264-BS <partner of www.ssl-news.info > [04/51]- "Breaking.Bad.S05E16.Felina.720p.WEB-DL.DD5.1.H.264-BS.r01" yEnc
	elseif (preg_match('/^<TOWN><www.town.ag >[ -]{0,3}(?P<title>.+?)[ -]{0,3}<partner of www\.ssl-news\.info >[ -]{0,3}\[\d+\/\d+\][ -]{0,3}".+?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//FÃ¼r brothers-of-usenet.net - [01/10] - "Costume.Quest.Language.Changer.DOX-RAiN.par2" yEnc
	elseif (preg_match('/^.+?brothers-of-usenet\.net[ -]{0,3}\[\d+\/\d+\][ -]{0,3}"(?P<title>.+?)\.(par|vol|rar|nfo).*?" yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[Charlie.Valentine.2009.German.DTS.DL.1080p.BluRay.x264-SoW]-[ZED for usenet-4all.info]-[zed7930]-[powered by Dreamload.com] (05/72) #34;zed7930.part03.rar" yEnc
	//Pusher.II.2004.German.1080p.BluRay.x264-DETAiLS [ZED for usenet-4all.info]-[zed15024]-(03/92) #34;zed15024.part01.rar#34; yEnc
	elseif (preg_match('/^\[?(?P<title>.+?)\]?[ -]{0,3}\[ZED for usenet-4all.info\][ -]{0,3}\[.+?\][ -]{0,3}\(\d+\/\d+\)[ -]{0,3}("|#34;).+?("|#34;) yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//3000 Fiction Ebooks Collection - "Anthony Piers - Incarnations Of Immortality 2 - Bearing an Hourglass [uc].txt" yEnc
	elseif (preg_match('/^3000 Fiction Ebooks Collection[ -]{0,3}("|#34;)(?P<title>.+?)\.(txt|pdf|lit|doc|rtf|chm|par2)("|#34;) yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//[united-forums.co.uk] NDS Roms 0501-0750 [039/262] - "0537 - Kirarin x Revolution - Kira Kira Idol Audition (J) -WWW.UNITED-FORUMS.CO.UK-.7z" yEnc
	elseif (preg_match('/^\[united-forums.co.uk\].+?\[\d+\/\d+\][ -]{0,3}("|#34;)?(.+?)( -WWW.UNITED-FORUMS.CO.UK)?(\.|-|_)+(rar|zip|7z)("|#34;)? yEnc$/', $subject, $match))
	{
		$cleanerName = $match['title'];
		if (!empty($cleanerName))
			return $cleanerName;
	}
	//Digitalmagazin.info.2011.01.25.GERMAN.RETAiL.eBOOk-sUppLeX.rar
	//no match is spaces
	elseif (strlen($subject) > 20 && !preg_match('/\s/', $subject) && preg_match('/(?P<title>[\w-\._]*)\.(rar|par|par2|part\d+)$/', $subject, $match))
	{
		if (strlen($match['title']) > 15)
		{
			$cleanerName = $match['title'];
			if (!empty($cleanerName))
				return $cleanerName;
		}
	}
	else
	{
		$db = new DB();
		$namecleaning = new nameCleaning();
		$propername = true;
		$cleanName = "";
		$category = new Category();
		if ($cleanerName = $namecleaning->releaseCleaner($subject, $groupid))
		{
			if (!is_array($cleanerName))
				$cleanName = $cleanerName;
			else
			{
				$cleanName = $cleanerName['cleansubject'];
				$propername = $cleanerName['properlynamed'];
			}
			$determinedcat = $category->determineCategory($cleanName, $groupid);
			if (!empty($cleanName) && $cleanName != $subject && $propername === true)
				$db->queryExec(sprintf("UPDATE releases SET relnamestatus = 6, searchname = %s, categoryid = %d WHERE id = %d", $db->escapeString($cleanName), $determinedcat, $id));
			elseif (!empty($cleanName) && $cleanName != $subject && $propername === false)
				$db->queryExec(sprintf("UPDATE releases SET searchname = %s, categoryid = %d WHERE id = %d", $db->escapeString($cleanName), $determinedcat, $id));
		}
	}
}
