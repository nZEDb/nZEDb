<?php
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/predb.php");


//
//	Cleans names for collections/releases/imports/namefixer.
//
class nameCleaning
{
	
	//
	//	Cleans usenet subject before inserting, used for sha1 hash and release name.
	//
	public function collectionsCleaner($subject, $groupName="")
	{
		$cleansubject = array();
		
		
		if ($groupName === "alt.binaries.0day.stuffz")
		{
			//ArcSoft.TotalMedia.Theatre.v5.0.1.87-Lz0 - [08/35] - "ArcSoft.TotalMedia.Theatre.v5.0.1.87-Lz0.vol43+09.par2" yEnc
			if (preg_match('/^([a-zA-Z0-9].+?)( - )\[\d+(\/\d+\] - ").+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1].$match[2].$match[3];
				$cleansubject["clean"] = $match[1];
				return $cleansubject;
			}
			//rld-tcavu1 [5/6] - "rld-tcavu1.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?)( )\[\d+(\/\d+\] - ").+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1].$match[3];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//(DVD Shrink.ss) [1/1] - "DVD Shrink.ss.rar" yEnc
			else if (preg_match('/^(\((.+?\))) \[\d+(\/\d+] - ").+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1].$match[3];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//WinASO.Registry.Optimizer.4.8.0.0(1/4) - "WinASO_RO_v4.8.0.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?)\(\d+(\/\d+\) - ").+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1].$match[2];
				$cleansubject["clean"] = $match[1];
				return $cleansubject;
			}
			else
				return false;
		}
		
		else if ($groupName === "alt.binaries.anime")
		{
			//([AST] One Piece Episode 301-350 [720p]) [007/340] - "One Piece episode 301-350.part006.rar" yEnc
			if (preg_match('/^\(((\[.+?\] .+?)\) \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//[REPOST][ New Doraemon 2013.05.03 Episode 328 (TV Asahi) 1080i HDTV MPEG2 AAC-DoraClub.org ] [35/61] - "doraclub.org-doraemon-20130503-b8de1f8e.r32" yEnc
			else if (preg_match('/^(\[.+?\]\[ (.+?) \] \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//[De.us] Suzumiya Haruhi no Shoushitsu (1920x1080 h.264 Dual-Audio FLAC 10-bit) [017CB24D] [000/357] - "[De.us] Suzumiya Haruhi no Shoushitsu (1920x1080 h.264 Dual-Audio FLAC 10-bit) [017CB24D].nzb" yEnc
			else if (preg_match('/^(\[.+?\] (.+? \[[A-F0-9]+\]) \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//[eraser] Ghost in the Shell ARISE - border_1 Ghost Pain (BD 720p Hi444PP LC-AAC Stereo) - [01/65] - "[eraser] Ghost in the Shell ARISE - border_1 Ghost Pain (BD 720p Hi444PP LC-AAC Stereo) .md5" yEnc
			else if (preg_match('/^(\[.+?\] (.+?) - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//(01/27) - Maid.Sama.Jap.dubbed.german.english.subbed - "01 Misaki ist eine Maid!.divx" - 6,44 GB - yEnc
			else if (preg_match('/^\(\d+(\/\d+\) - (.+?) - ").+?" - \d+,\d+ [kKmMgG][bB] - yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//[ New Doraemon 2013.06.14 Episode 334 (TV Asahi) 1080i HDTV MPEG2 AAC-DoraClub.org ] [01/60] - "doraclub.org-doraemon-20130614-fae28cec.nfo" yEnc
			else if (preg_match('/^(\[ (.+?) \] \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//<TOWN> www.town.ag > sponsored by www.ssl-news.info > (1/3) "HolzWerken_40.par2" - 43,89 MB - yEnc
			else if (preg_match('/^(<TOWN> www\.town\.ag > sponsored by www\.ssl-news\.info > \(\d+\/\d+\) "(.+?))(\.part\d+)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+,\d+ [kKmMgG][bB] - yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			else
				return false;
		}
		
		else if ($groupName === "alt.binaries.moovee")
		{
			//[134551]-[FULL]-[#a.b.moovee]-[ Bittersweet.1995.DVDRip.XviD-FiCO ]-[20/70] - "fico-bitter.r06" yEnc
			if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[ (.+?) \]-)\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//[42788]-[#altbin@EFNet]-[Full]- "margin-themasterb-xvid.par2" yEnc
			else if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]- )"(.+?)(\.part\d+)?(\.part\d+)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			else
				return false;
		}
		
		else if ($groupName === "alt.binaries.teevee")
		{
			//[278997]-[FULL]-[#a.b.erotica]-[ chi-the.walking.dead.xxx ]-[06/51] - "chi-the.walking.dead.xxx-s.mp4" yEnc
			if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[ (.+?) \]-)\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//ah63jka93jf0jh26ahjas558 - [01/22] - "ah63jka93jf0jh26ahjas558.par2" yEnc
			else if (preg_match('/^(([a-z0-9]+) - )\[\d+\/\d+\] - "[a-z0-9]+\..+?" yEnc$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//fhdbg34rgjdsfd008c (42/43) "fhdbg34rgjdsfd008c.vol062+64.par2" - 3,68 GB - yEnc
			else if (preg_match('/^(([a-z0-9]+) )\(\d+\/\d+\) ".+?"( - )\d+,\d+ [kKmMgG][bB]( - yEnc)$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1].$match[3].$match[4];
				$cleansubject["clean"] = $match[2];
				return $cleansubject;
			}
			//t2EI3CdWdF0hi5b8L9tkx[08/52] - "t2EI3CdWdF0hi5b8L9tkx.part07.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9]+)\[\d+\/\d+\]( - )".+?"( yEnc)$/', $subject, $match))
			{
				$cleansubject["hash"] = $match[1].$match[2].$match[3];
				$cleansubject["clean"] = $match[1];
				return $cleansubject;
			}
			else
				return false;
		}
		else
			return false;
		
			
	}

	//
	//	Cleans usenet subject before inserting, used for collectionhash. Fallback from collectionsCleaner.
	//
	public function collectionsCleanerHelper($subject, $type)
	{
		/* This section is more generic, it will work on most releases. */
		//Parts/files
		$cleansubject = preg_replace('/((( \(\d\d\) -|(\d\d)? - \d\d\.|\d{4} \d\d -) | - \d\d-| \d\d\. [a-z]).+| \d\d of \d\d| \dof\d)\.mp3"?|(\(|\[|\s)\d{1,4}(\/|(\s|_)of(\s|_)|\-)\d{1,4}(\)|\]|\s|$|:)|\(\d{1,3}\|\d{1,3}\)|\-\d{1,3}\-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s|\d{1,3} - of \d{1,3}/i', ' ', $subject);
		//Anything between the quotes. Too much variance within the quotes, so remove it completely.
		$cleansubject = preg_replace('/\".+\"/i', ' ', $cleansubject);
		//File extensions - If it was not quotes.
		$cleansubject = preg_replace('/(-? [a-z0-9]+-?|\(?\d{4}\)?(_|-)[a-z0-9]+)\.jpg"?| [a-z0-9]+\.mu3"?|((\d{1,3})?\.part(\d{1,5})?|\d{1,5} ?|sample|- Partie \d+)?\.(7z|\d{3}(?=(\s|"))|avi|diz|docx?|epub|idx|iso|jpg|m3u|m4a|mds|mkv|mobi|mp4|nfo|nzb|par(\s?2|")|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|xls|zip|z{2,3})"?|(\s|(\d{2,3})?\-)\d{2,3}\.mp3|\d{2,3}\.pdf|\.part\d{1,4}\./i', ' ', $cleansubject);
		//File Sizes - Non unique ones.
		$cleansubject = preg_replace('/\d{1,3}(,|\.|\/)\d{1,3}\s(k|m|g)b|(\])?\s\d{1,}KB\s(yENC)?|"?\s\d{1,}\sbytes?|(\-\s)?\d{1,}(\.|,)?\d{1,}\s(g|k|m)?B\s\-?(\s?yenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s|yEnc \d+k$|{\d+ yEnc bytes}|yEnc \d+ |\(\d+ ?(k|m|g)?b(ytes)?\) yEnc/i', ' ', $cleansubject);
		//Random stuff.
		$cleansubject = preg_replace('/AutoRarPar\d{1,5}|\(\d+\)( |  )yEnc|\d+(Amateur|Classic)| \d{4,}[a-z]{4,} |part\d+/i', ' ', $cleansubject);
		$cleansubject = utf8_encode(trim(preg_replace('/\s\s+/i', ' ', $cleansubject)));

		if ($type == "split")
		{
			$one = $two = "";
			if (preg_match('/"(.+?)\.[a-z0-9].+?"/i', $subject, $matches))
				$one = $matches[1];
			else if(preg_match('/s\d{1,3}[.-_ ]?(e|d)\d{1,3}|EP[\.\-_ ]?\d{1,3}[\.\-_ ]|[a-z0-9\.\-_ \(\[\)\]{}<>,"\'\$^\&\*\!](19|20)\d\d[a-z0-9\.\-_ \(\[\)\]{}<>,"\'\$^\&\*\!]/i', $subject, $matches2))
				$two = $matches2[0];
			return $cleansubject.$one.$two;
		}
		else if ($type !== "split" && (strlen($cleansubject) <= 7 || preg_match('/^[a-z0-9 \-\$]{1,9}$/i', $cleansubject)))
		{
			$one = $two = "";
			if (preg_match('/.+?"(.+?)".+?".+?".+/', $subject, $matches))
				$one = $matches[1];
			else if (preg_match('/(^|.+)"(.+?)(\d{2,3} ?\(\d{4}\).+?)?\.[a-z0-9].+?"/i', $subject, $matches))
				$one = $matches[2];
			if(preg_match('/s\d{1,3}[.-_ ]?(e|d)\d{1,3}|EP[\.\-_ ]?\d{1,3}[\.\-_ ]|[a-z0-9\.\-_ \(\[\)\]{}<>,"\'\$^\&\*\!](19|20)\d\d[a-z0-9\.\-_ \(\[\)\]{}<>,"\'\$^\&\*\!]/i', $subject, $matches2))
				$two = $matches2[0];
			if ($one == "" && $two == "")
			{
				$newname = preg_replace('/[a-z0-9]/i', '', $subject);
				if (preg_match('/[\!@#\$%\^&\*\(\)\-={}\[\]\|\\:;\'<>\,\?\/_ ]{1,3}/', $newname, $matches3))
					return $cleansubject.$matches3[0];
			}
			else
				return $cleansubject.$one.$two;
		}
		else
			return $cleansubject;
	}

	//
	//	Cleans a usenet subject before inserting, used for searchname. Also used for imports.
	//	Some of these also have MD5 Hashes, I will comment where they do.
	//
	public function releaseCleaner($subject, $groupID="")
	{
		if ($groupID !== "")
		{
			$groups = new Groups();
			$groupName = $groups->getByNameByID($groupID);
			$predb = new Predb();
			
			/* First, try to do regex that can match on many groups. */
			
			//[278997]-[FULL]-[#a.b.erotica]-[ chi-the.walking.dead.xxx ]-[06/51] - "chi-the.walking.dead.xxx-s.mp4" yEnc
			if (preg_match('/^\[\d+\]-\[.+?\]-\[.+?\]-\[ (.+?) \]-\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			{
				$cleanerName = $match[1];
				if (empty($cleanerName))
					return $this->releaseCleanerHelper($subject);
				else
					return $cleanerName;
			}
			//>ghost-of-usenet.org>Udo Lindenberg & Alla Borissowna Pugatschowa - Songs Instead Of Letters [01/11] - "ul_abp.nfo" yEnc
			elseif (preg_match('/^>ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			{
				$cleanerName = $match[1];
				if (empty($cleanerName))
					return $this->releaseCleanerHelper($subject);
				else
					return $cleanerName;
			}
			//<<< <ghost-of-usenet.org> <"ABBYY.FineReader.v11.0.102.583.Corporate.Edition.MULTiLANGUAGE-PillePalle.7z.007"> >www.SSL-News.info< - - 397,31 MB yEnc
			elseif (preg_match('/.+?<ghost-of-usenet\.org>( <[a-zA-Z]+>)? <"(.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|")> >www\..+? yEnc$/', $subject, $match))
			{
				$cleanerName = $match[2];
				if (empty($cleanerName))
					return $this->releaseCleanerHelper($subject);
				else
					return $cleanerName;
			}
			//<ghost-of-usenet.org>Das.Glueck.dieser.Erde.S01E04.German.WS.DVDRiP.XViD-AMBASSADOR<>www.SSL-News.info< "ar-dgde-s01e04-xvid-sample.avi" yEnc
			elseif (preg_match('/^<ghost-of-usenet\.org>(.+?)<>www\..+? ".+?" yEnc$/', $subject, $match))
			{
				$cleanerName = $match[2];
				if (empty($cleanerName))
					return $this->releaseCleanerHelper($subject);
				else
					return $cleanerName;
			}
			
			/* Now, we try to match on individual groups. */
			elseif (preg_match('/alt\.binaries\.erotica$/', $groupName))
			{
				$cleanerName = "";
				//<TOWN><www.town.ag > <download all our files with>>> www.ssl-news.info <<< > [01/28] - "TayTO-heyzo_hd_0317_full.par2" - 2,17 GB yEnc
				if (preg_match('/^<TOWN><www\.town\.ag > <download all our files with>>> www\.ssl-news\.info <<< > \[\d+\/\d+\] - "(.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|") - /', $subject, $match))
					$cleanerName = $match[1];
				//NihilCumsteR [1/8] - "Conysgirls.cumpilation.xxx.NihilCumsteR.par2" yEnc
				else if (preg_match('/^NihilCumsteR.+?"(.+?)NihilCumsteR\./', $subject, $match))
					$cleanerName = $match[1];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $subject;
				else
					return $cleanerName;
			}
			elseif (preg_match('/alt\.binaries\.fz$/', $groupName))
			{
				$cleanerName = "";
				//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
				if (preg_match('/^>ghost-of-usenet\.org>(.+?)<.+?> ".+?" yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $subject;
				else
					return $cleanerName;
			}
			elseif (preg_match('/alt\.binaries\.games$/', $groupName))
			{
				$cleanerName = "";
				//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
				if (preg_match('/^>ghost-of-usenet\.org>(.+?)<.+?> ".+?" yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
				else if (preg_match('/^<ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $subject;
				else
					return $cleanerName;
			}
			elseif (preg_match('/alt\.binaries\.german\.movies$/', $groupName))
			{
				$cleanerName = "";
				//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
				if (preg_match('/^>ghost-of-usenet\.org>(.+?)<.+?> ".+?" yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $subject;
				else
					return $cleanerName;
			}
			elseif (preg_match('/alt\.binaries\.ghosts$/', $groupName))
			{
				$cleanerName = "";
				//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
				if (preg_match('/^<ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $subject;
				else
					return $cleanerName;
			}
			elseif (preg_match('/alt\.binaries\.inner-sanctum$/', $groupName))
			{
				$cleanerName = "";
				//ea17079f47de702eead5114038355a70 [1/9] - "00-da_morty_-_boondock_sampler_02-(tbr002)-web-2013-srg.m3u" yEnc
/* $match[1] = MD5 */
				if (preg_match('/^([a-fA-F0-9]+) \[\d+\/\d+\] - ".+?(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|") yEnc$/', $subject, $match))
					$cleanerName = $match[2];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $subject;
				else
					return $cleanerName;
			}
			elseif (preg_match('/alt\.binaries\.mom$/', $groupName))
			{
				$cleanerName = "";
				//[usenet4ever.info] und [SecretUsenet.com] - 96e323468c5a8a7b948c06ec84511839-u4e - "96e323468c5a8a7b948c06ec84511839-u4e.par2" yEnc
/* $match[1] = MD5 */
				if (preg_match('/^\[usenet4ever\.info\] und \[SecretUsenet\.com\] - (.+?)-u4e - ".+?" yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				//brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [01/26] - "Be.Cool.German.AC3.HDRip.x264-FuN.par2" yEnc
				else if (preg_match('/\.net <<<Partner von SSL-News\.info>>> - \[\d+\/\d+\] - "(.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|") yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
				else if (preg_match('/^<ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $subject;
				else
					return $cleanerName;
			}
			elseif (preg_match('/alt\.binaries\.moovee$/', $groupName))
			{
				$cleanerName = "";
				//[42788]-[#altbin@EFNet]-[Full]- "margin-themasterb-xvid.par2" yEnc
				if (preg_match('/^\[\d+\]-\[.+?\]-\[.+?\]- "(.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|") yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $subject;
				else
					return $cleanerName;
			}
			elseif (preg_match('/alt\.binaries\.mp3\.complete_cd$/', $groupName))
			{
				$cleanerName = "";
				//[052713]-[#eos@EFNet]-[All_Shall_Perish-Montreal_QUE_0628-2007-EOS]-[09/14] "06-all_shall_perish-deconstruction-eos.mp3" yEnc
				if (preg_match('/^\[(\d+)\]-\[.+?\]-\[(.+?)\]-\[\d+\/\d+\] ".+?" yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $this->releaseCleanerHelper($subject);
				else
					return $cleanerName;
			}
			elseif (preg_match('/alt\.binaries\.multimedia\.anime(\.highspeed)?/', $groupName))
			{
				$cleanerName = "";
				//High School DxD New 01 (480p|.avi|xvid|mp3) ~bY Hatsuyuki [01/18] - "[Hatsuyuki]_High_School_DxD_New_01_[848x480][76B2BB8C].avi.001" yEnc
				if (preg_match('/.+? \((360|480|720|1080)p\|.+? ~bY .+? \[\d+\/\d+\] - "(.+?\[[A-F0-9]+\].+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|") yEnc$/', $subject, $match))
					$cleanerName = $match[2];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $subject;
				else
					return $cleanerName;
			}
			elseif (preg_match('/alt\.binaries\.teevee$/', $groupName))
			{
				$cleanerName = "";
				//(01/37) "Entourage S08E08.part01.rar" - 349,20 MB - yEnc
				if (preg_match('/^\(\d+\/\d+\) "(.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|") - \d.+? - (\d.+? -)? yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				//ah63jka93jf0jh26ahjas558 - [01/22] - "ah63jka93jf0jh26ahjas558.par2" yEnc
				else if (preg_match('/^([a-z0-9]+) - \[\d+\/\d+\] - "[a-z0-9]+\..+?" yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $this->releaseCleanerHelper($subject);
				else
					return $cleanerName;
			}
			elseif (preg_match('/alt\.binaries\.tv$/', $groupName))
			{
				$cleanerName = "";
				//Borgen.2x02.A.Bruxelles.Non.Ti.Sentono.Urlare.ITA.BDMux.x264-NovaRip [02/22] - "borgen.2x02.ita.bdmux.x264-novarip.par2" yEnc
				if (preg_match('/^([a-zA-Z0-9.\-]+) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
					$cleanerName = $match[1];
				//(bf1) [03/31] - "The.Block.AU.Sky.High.S07E56.WS.PDTV.XviD.BF1.part01.sfv" yEnc
				else if (preg_match('/^\(bf1\) \[\d+\/\d+\] - "(.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|") yEnc$/', $subject, $match))
					$cleanerName = $match[2];
				else
					$cleanerName = $this->releaseCleanerHelper($subject);

				if (empty($cleanerName))
					return $subject;
				else
					return $cleanerName;
			}
			else
				return $this->releaseCleanerHelper($subject);
		}
		else
			return $this->releaseCleanerHelper($subject);
	}
	
	public function releaseCleanerHelper($subject)
	{
		//File and part count.
		$cleanerName = preg_replace('/(File )?(\(|\[|\s)\d{1,4}(\/|(\s|_)of(\s|_)|\-)\d{1,4}(\)|\]|\s|$|:)|\(\d{1,3}\|\d{1,3}\)|\-\d{1,3}\-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s|\d{1,3} - of \d{1,3}/i', ' ', $subject);
		//Size.
		$cleanerName = preg_replace('/\d{1,3}(\.|,)\d{1,3}\s(K|M|G)B|\d{1,}(K|M|G)B|\d{1,}\sbytes?|(\-\s)?\d{1,}(\.|,)?\d{1,}\s(g|k|m)?B\s\-(\syenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s|\(\d+K\)\syEnc|yEnc \d+k$/i', ' ', $cleanerName);
		//Extensions.
		$cleanerName = preg_replace('/ [a-z0-9]+\.jpg|((\d{1,3})?\.part(\d{1,5})?|\d{1,5}|sample)?\.(7z|\d{3}(?=(\s|"))|avi|epub|idx|iso|jpg|m4a|mds|mkv|mobi|mp4|nfo|nzb|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|par(\s?2|")|zip|z{2})"?|(\s|(\d{2,3})?\-)\d{2,3}\.mp3|\d{2,3}\.pdf|yEnc|\.part\d{1,4}\./i', ' ', $cleanerName);
		//Books + Music.
		$cleanerName = preg_replace('/((\d{1,2}-\d{1-2})?-[a-z0-9]+)?\.scr|Ebook\-[a-z0-9]+|\((\d+ )ebooks\)|\(ebooks[\.\-_ ](collection|\d+)\)|\([a-z]{3,9} \d{1,2},? 20\d\d\)|\(\d{1,2} [a-z]{3,9} 20\d\d|\[ATTN:.+?\]|ATTN: [a-z]{3,13} |ATTN:(macserv 100|Test)|ATTN: .+? - ("|:)|ATTN .+?:|\((bad conversion|Day\d{1,}\/\?|djvu|fixed|pdb|tif)\)|by [a-z0-9]{3,15}$|^Dutch(:| )|enjoy!|(\*| )enjoy(\*| )|^ePub |\(EPUB\+MOBI\)|(Flood )?Please - All I have|isbn\d+|New Ebooks \d{1,2} [a-z]{3,9} (19|20)\d\d( part \d)?|\[(MF|Ssc)\]|^New Version( - .+? - )?|^NEW( [a-z]+( Paranormal Romance|( [a-z]+)?:|,| ))?(?![\.\-_ ]York)|[\.\-_ ]NMR \d{2,3}|( |\[)NMR( |\])|\[op.+?\d\]|\[Orion_Me\]|\[ORLY\]|Please\.\.\.|R4 - Book of the Week|Re: |READNFO|Req: |Req\.|!<-- REQ:|^Request|Requesting|Should I continue posting these collections\?|\[Team [a-z0-9]+\]|[\.\-_ ](Thanks|TIA!)[\.\-_ ]|\(v\.?\d+\.\d+[a-z]?\)|par2 set|\.(j|f|m|a|s|o|n|d)[a-z]{2,8}\.20\d\d/i', ' ', $cleanerName);
		//Unwanted stuff.
		$cleanerName = preg_replace('/sample("| )?$|"sample|\(\?\?\?\?\)|\[AoU\]|AsianDVDClub\.org|AutoRarPar\d{1,5}|brothers\-of\-usenet\.(info|net)(\/\.net)?|~bY ([a-z]{3,15}|c-w)|By request|DVD-Freak|Ew-Free-Usenet-\d{1,5}|for\.usenet4ever\.info|ghost-of-usenet.org<<|GOU<<|(http:\/\/www\.)?friends-4u\.nl|\[\d+\]-\[abgxEFNET\]-|\[[a-z\d]+\]\-\[[a-z\d]+\]-\[FULL\]-|\[\d{3,}\]-\[FULL\]-\[(a\.b| abgx).+?\]|\[\d{1,}\]|\-\[FULL\].+?#a\.b[\w.#!@$%^&*\(\){}\|\\:"\';<>,?~` ]+\]|Lords-of-Usenet(\] <> presents)?|nzbcave\.co\.uk( VIP)?|(Partner (of|von) )?SSL\-News\.info>> presents|\/ post: |powere?d by (4ux(\.n\)?l)?|the usenet)|(www\.)?ssl-news(\.info)?|SSL - News\.Info|usenet-piraten\.info|\-\s\[.+?\]\s<>\spresents|<.+?https:\/\/secretusenet\.com>|SECTIONED brings you|team-hush\.org\/| TiMnZb |<TOWN>|www\.binnfo\.in|www\.dreameplace\.biz|wwwworld\.me|www\.town\.ag|(Draak48|Egbert47|jipenjans|Taima) post voor u op|Dik Trom post voor|Sponsored\.by\.SecretUsenet\.com|(::::)?UR-powered by SecretUsenet.com(::::)?|usenet4ever\.info|(www\.)?usenet-4all\.info|www\.torentz\.3xforum\.ro|usenet\-space\-cowboys\.info|> USC <|SecretUsenet\.com|Thanks to OP|\] und \[|www\.michael-kramer\.com|(http:\\\\\\\\)?www(\.| )[a-z0-9]+(\.| )(co(\.| )cc|com|info|net|org)|zoekt nog posters\/spotters|>> presents|Z\[?NZB\]?(\.|_)wz(\.|_)cz|partner[\.\-_ ]of([\.\-_ ]www)?/i', ' ', $cleanerName);
		//Change [pw] to passworded.
		$cleanerName = str_replace(array('[pw]', '[PW]', ' PW ', '(Password)'), ' PASSWORDED ', $cleanerName);
		//Replaces some characters with 1 space.
		$cleanerName = str_replace(array(".", "_", '-', "|", "<", ">", '"', "=", '[', "]", "(", ")", "{", "}", "*", ";", ":", ",", "'", "~", "/", "&", "+"), " ", $cleanerName);
		//Replace multiple spaces with 1 space
		$cleanerName = trim(preg_replace('/\s\s+/i', ' ', $cleanerName));
		//Remove the double name.
		$cleanerName = implode(' ', array_intersect_key(explode(' ', $cleanerName), array_unique(array_map('strtolower', explode(' ', $cleanerName)))));

		if (empty($cleanerName)) {return $subject;}
		else {return $cleanerName;}
	}

	//
	//	Cleans release name for the namefixer class.
	//
	public function fixerCleaner($name)
	{
		//Extensions.
		$cleanerName = preg_replace('/ [a-z0-9]+\.jpg|((\d{1,3})?\.part(\d{1,5})?|\d{1,5}|sample)?\.(7z|\d{3}(?=(\s|"))|avi|epub|idx|iso|jpg|m4a|mds|mkv|mobi|mp4|nfo|nzb|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|par(\s?2|")|zip|z{2})"?|(\s|(\d{2,3})?\-)\d{2,3}\.mp3|\d{2,3}\.pdf|yEnc|\.part\d{1,4}\./i', ' ', $name);
		//Replaces some characters with 1 space.
		$cleanerName = str_replace(array(".", "_", '-', "|", "<", ">", '"', "=", '[', "]", "(", ")", "{", "}", "*", ";", ":", ",", "'", "~", "/", "&", "+"), " ", $cleanerName);
		//Replace multiple spaces with 1 space
		$cleanerName = preg_replace('/\s\s+/i', ' ', $cleanerName);
		//Remove Release Name
		$cleanerName = preg_replace('/^Release Name/i', ' ', $cleanerName);
		//Remove invalid characters.
		$cleanerName = trim(utf8_encode(preg_replace('/[^(\x20-\x7F)]*/','', $cleanerName)));

		return $cleanerName;
	}
}
