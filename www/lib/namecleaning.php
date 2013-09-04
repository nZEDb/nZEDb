<?php
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/predb.php");


//
//	Cleans names for collections/releases/imports/namefixer.
//
class nameCleaning
{
	function nameCleaning()
	{
		// Extensions.
		$this->e0 = '([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")';
		$this->e1 = $this->e0.' yEnc$/';
	}

	/*
		Cleans a usenet subject returning something that can tie many articles together.

		$subject = The usenet subject, ending with yEnc (part count removed from the end).
		$groupName = The name of the group for the article.
		$nofiles = Wether the article has a filecount or not.

		First, try against groups with strict regex.
		If that fails, try against more generic regex.
		$nofiles can help with bunched releases, by having its own set of regex.

		Example: Take the following subjects:
		[134787]-[FULL]-[#a.b.moovee]-[ Trance.2013.DVDRiP.XViD-SML ]-[01/46] - "tranceb-xvid-sml.par2" yEnc
		[134787]-[FULL]-[#a.b.moovee]-[ Trance.2013.DVDRiP.XViD-SML ]-[02/46] - "tranceb-xvid-sml.r00" yEnc

		Return something like this :
		[134787]-[FULL]-[#a.b.moovee]-[ Trance.2013.DVDRiP.XViD-SML ]-[/46] - "tranceb-xvid-sml." yEnc
	*/

	public function collectionsCleaner($subject, $groupID="", $nofiles=false)
	{
		$groups = new Groups();
		$groupName = $groups->getByNameByID($groupID);

		if ($groupName === "alt.binaries.0day.stuffz")
		{
			//ArcSoft.TotalMedia.Theatre.v5.0.1.87-Lz0 - [08/35] - "ArcSoft.TotalMedia.Theatre.v5.0.1.87-Lz0.vol43+09.par2" yEnc
			if (preg_match('/^([a-zA-Z0-9].+?)( - )\[\d+(\/\d+\] - ").+?" yEnc$/', $subject, $match))
				return $match[1].$match[2].$match[3];
			//rld-tcavu1 [5/6] - "rld-tcavu1.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?) \[\d+(\/\d+\] - ").+?" yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//(DVD Shrink.ss) [1/1] - "DVD Shrink.ss.rar" yEnc
			else if (preg_match('/^(\(.+?\) \[)\d+(\/\d+] - ").+?" yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//WinASO.Registry.Optimizer.4.8.0.0(1/4) - "WinASO_RO_v4.8.0.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?)\(\d+(\/\d+\) - ").+?" yEnc$/', $subject, $match))
				return $match[1].$match[2];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.anime")
		{
			//([AST] One Piece Episode 301-350 [720p]) [007/340] - "One Piece episode 301-350.part006.rar" yEnc
			if (preg_match('/^(\(\[.+?\] .+?\) \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[REPOST][ New Doraemon 2013.05.03 Episode 328 (TV Asahi) 1080i HDTV MPEG2 AAC-DoraClub.org ] [35/61] - "doraclub.org-doraemon-20130503-b8de1f8e.r32" yEnc
			else if (preg_match('/^(\[.+?\]\[ .+? \] \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[De.us] Suzumiya Haruhi no Shoushitsu (1920x1080 h.264 Dual-Audio FLAC 10-bit) [017CB24D] [000/357] - "[De.us] Suzumiya Haruhi no Shoushitsu (1920x1080 h.264 Dual-Audio FLAC 10-bit) [017CB24D].nzb" yEnc
			else if (preg_match('/^(\[.+?\] .+? \[[A-F0-9]+\] \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[eraser] Ghost in the Shell ARISE - border_1 Ghost Pain (BD 720p Hi444PP LC-AAC Stereo) - [01/65] - "[eraser] Ghost in the Shell ARISE - border_1 Ghost Pain (BD 720p Hi444PP LC-AAC Stereo) .md5" yEnc
			else if (preg_match('/^(\[.+?\] .+? - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(01/27) - Maid.Sama.Jap.dubbed.german.english.subbed - "01 Misaki ist eine Maid!.divx" - 6,44 GB - yEnc
			else if (preg_match('/^\(\d+(\/\d+\) - .+? - ").+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//[ New Doraemon 2013.06.14 Episode 334 (TV Asahi) 1080i HDTV MPEG2 AAC-DoraClub.org ] [01/60] - "doraclub.org-doraemon-20130614-fae28cec.nfo" yEnc
			else if (preg_match('/^(\[ .+? \] \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<TOWN> www.town.ag > sponsored by www.ssl-news.info > (1/3) "HolzWerken_40.par2" - 43,89 MB - yEnc
			//<TOWN> www.town.ag > sponsored by www.ssl-news.info > (1/5) "[HorribleSubs]_Aku_no_Hana_-_01_[480p].par2" - 157,13 MB - yEnc
			else if (preg_match('/^<TOWN> www\.town\.ag > sponsored by www\.ssl-news\.info > \(\d+(\/\d+\) ".+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(1/9)<<<www.town.ag>>> sponsored by ssl-news.info<<<[HorribleSubs]_AIURA_-_01_[480p].mkv "[HorribleSubs]_AIURA_-_01_[480p].par2" yEnc
			else if (preg_match('/^\(\d+\/\d+\)(.+?www\.town\.ag.+?sponsored by (www\.)?ssl-news\.info<+?.+?) ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//blazedazer_NAN000010 [140/245] - "blazedazer_NAN000010.part138.rar" yEnc
			else if (preg_match('/^(blazedazer_.+? \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.ath")
		{
			//[3/3 Karel Gott - Die Biene Maja Original MP3 Karel Gott - Die Biene Maja Original MP3.mp3.vol0+1.PAR2" yEnc
			if (preg_match('/^\[\d+\/\d+ ([a-zA-Z0-9]+ .+?)\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//8b33bf5960714efbe6cfcf13dd0f618f - (01/55) - "8b33bf5960714efbe6cfcf13dd0f618f.par2" yEnc
			else if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}\..+" yEnc$/', $subject, $match))
				return $match[1];
			//nmlsrgnb - [04/37] - "htwlngmrstdsntdnh.part03.rar" yEnc
			else if (preg_match('/^([a-z]+ - \[)\d+\/\d+\] - "[a-z]+\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//>>>>>Hell-of-Usenet>>>>> - [01/33] - "Cassadaga Hier lebt der Teufel 2011 German AC3 DVDRip XViD iNTERNAL-VhV.par2" yEnc
			else if (preg_match('/^(>+Hell-of-Usenet(\.org)?>+( -)? \[)\d+\/\d+\] - "(.+?)'.$this->e0.'( - \d+[.,]\d+ [kKmMgG][bB])? yEnc$/', $subject, $match))
				return $match[1].$match[3];
			//1dbo1u5ce6182436yb2eo (001/105) "1dbo1u5ce6182436yb2eo.par2" yEnc
			else if (preg_match('/^([a-z0-9]{10,}) \(\d+\/\d+\) "[a-z0-9]{10,}\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//<<<>>>kosova-shqip.eu<<< Deep SWG - 90s Club Megamix 2011 >>>kosova-shqip.eu<<<<<< - (2/4) - "Deep SWG - 90s Club Megamix 2011.rar" yEnc
			else if (preg_match('/^(<<<>>>kosova-shqip\.eu<<< .+? >>>kosova-shqip.eu<<<<<< - \()\d+\/\d+\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<Have Fun> [02/39] - SpongeBoZZ yEnc
			else if (preg_match('/^(<Have Fun> \[)\d+(\/\d+\] - .+? )yEnc$/', $subject, $match))
				return $match[1].$match[2];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.audio.warez")
		{
			//[#nzbx.audio/EFnet]-[1681]-[MagicScore.Note.v7.084-UNION]-[02/12] - "u-msn7.r00" yEnc
			if (preg_match('/^(Re: )?(\[.+?\]-\[\d+\]-\[.+?\]-\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[2];
			//MacProVideo.com.Pro.Tools8.101.Core.Pro.Tools8.TUTORiAL-DYNAMiCS [2 of 50] "dyn-mpvprtls101.sfv" yEnc
			//Native.Instruments.Komplete.7.VSTi.RTAS.AU.DVDR.D02-DYNAMiCS[01/13] - "dyn.par2" yEnc
			//Native.Instruments.Komplete.7.VSTi.RTAS.AU.DVDR.DYNAMiCS.NZB.ONLY [02/13] - "dyn.vol0000+001.PAR2" yEnc
			else if (preg_match('/^([\w.-]+ ?\[)\d+( of |\/)\d+\] ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//REQ : VSL Stuff ~ Here's PreSonus Studio One 1.5.2 for OS X [16 of 22] "a-p152x.rar" yEnc
			else if (preg_match('/^(REQ : .+? ~ .+? \[)\d+ of \d+\] ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Eminem - Recovery (2010) - [1/1] - "Eminem - Recovery (2010).rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+? - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(????) [1/1] - "Dust in the Wind - the Violin Solo.rar" yEnc
			else if (preg_match('/^\((\?{4}\) \[)\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1].$match[2];
			//Native Instruments Battery 3 incl Library ( VST DX RTA )( windows ) Libraries [1/1] - "Native Instruments Battery 2 + 3 SERIAL KEY KEYGEN.nfo" yEnc
			else if (preg_match('/^(.+? \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			/*TODO: REFRESH : Tonehammer Ambius 1 'Transmissions' ~ REQ: SAMPLE LOGIC SYNERGY [1 of 52] "dynamics.nfo" yEnc*/
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.b4e")
		{
			//"B4E-vip2851.r83" yEnc
			if (preg_match('/^("B4E-vip\d+)\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//[02/12] - "The.Call.GERMAN.2013.DL.AC3.Dubbed.720p.BluRay.x264 (Avi-RiP ).rar" yEnc
			else if (preg_match('/^\[\d+(\/\d+\] - ".+? \().+?" yEnc$/', $subject, $match))
				return $match[1];
			//- "as-jew3.vol03+3.PAR2" - yEnc
			else if (preg_match('/^(- ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.barbarella")
		{
			//ACDSee.Video.Converter.Pro.v3.5.41.Incl.Keymaker-CORE - [1/7] - "ACDSee.Video.Converter.Pro.v3.5.41.Incl.Keymaker-CORE.par2" yEnc
			if (preg_match('/^([a-zA-Z0-9].+? - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Die.Nacht.Der.Creeps.THEATRICAL.GERMAN.1986.720p.BluRay.x264-GH - "gh-notcreepskf720.nfo" yEnc
			//The.Fast.and.the.Furious.Tokyo.Drift.2006.German.1080p.BluRay.x264.iNTERNAL-MWS  - "mws-tfatftd-1080p.nfo" yEnc
			if (preg_match('/^([\w.-]+\s+-\s+").+?" yEnc$/', $subject, $match))
				return $cleansubject["hash"] = $match[1];
			//CorelDRAW Technical Suite X6-16.3.0.1114 x32-x64<><>DRM<><> - (10/48)  "CorelDRAW Technical Suite X6-16.3.0.1114 x32-x64.part09.rar" - 2,01 GB - yEnc
			//AnyDVD_7.1.9.3_-_HD-BR - Beta<>give-me-all.org<>DRM<><> - (1/3)  "AnyDVD_7.1.9.3_-_HD-BR - Beta.par2" - 14,53 MB - yEnc
			//Android Softarchive.net Collection Pack 27^^give-me-all.org^^^^DRM^^^^ - (01/26)  "Android Softarchive.net Collection Pack 27.par2" - 1,01 GB - yEnc
			//WIN7_ULT_SP1_x86_x64_IE10_19_05_13_TRIBAL <> give-me-all.org <> DRM <> <> PW <> - (154/155)  "WIN7_ULT_SP1_x86_x64_IE10_19_05_13_TRIBAL.vol57+11.par2" - 7,03 GB - yEnc
			//[Android].Ultimate.iOS7.Apex.Nova.Theme.v1.45 <> DRM <> - (1/3)  "[Android].Ultimate.iOS7.Apex.Nova.Theme.v1.45.par2" - 21,14 MB - yEnc
			else if (preg_match('/^((\[[A-Za-z]+\]\.)?[a-zA-Z0-9].+?([\^<> ]+give-me-all\.org[\^<> ]+|[\^<> ]+)DRM[\^<> ]+.+? - \()\d+\/\d+\)  ".+?" - .+? yEnc$/', $subject, $match))
				return $match[1];
			//(004/114) - Description - "Pluralsight.net XAML Patterns (10).rar" - 532,92 MB - yEnc
			else if (preg_match('/^\(\d+(\/\d+\) - .+? - ".+?)( \(\d+\))?'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(59/81) "1973 .Lee.Jun.Fan.DVD9.untouched.z46" - 7,29 GB - Lee.Jun.Fan.sein.Film.DVD9.untouched yEnc
			//(01/12) - "TransX - Living on a Video 1993.part01.rar" - 561,55 MB - TransX - Living on a Video 1993.[Lossless] Highh Quality yEnc
			else if (preg_match('/^\(\d+\/\d+\)( -)? ".+?" - \d+[,.]\d+ [mMkKgG]([bB] - .+?) yEnc$/', $subject, $match))
				return $match[2];
			//>>> www.lords-of-usenet.org <<<  "Der Schuh Des Manitu.par2" DVD5  [001/158] - 4,29 GB yEnc
			else if (preg_match('/^(>>> www\.lords-of-usenet\.org <<<.+? ".+?)'.$this->e0.' .+? \[\d+\/\d+\] - .+? yEnc$/', $subject, $match))
				return $match[1];
			//NEUES 4y - [@ usenet-4all.info - powered by ssl.news -] [5,58 GB] [002/120] "DovakinPack.part002.rar" yEnc
			//NEUES 4y (PW)  [@ usenet-4all.info - powered by ssl.news -] [7,05 GB] [014/152] "EngelsGleich.part014.rar" yEnc
			else if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+(\/\d+\] ".+?)'.$this->e1, $subject, $match))
				return $match[2];
			//Old Dad uppt   Die Schatzinsel Teil 1+Teil2  AC3 DVD Rip German XviD Wp 01/33] - "upp11.par2" yEnc
			//Old Dad uppt Scary Movie5 WEB RiP Line XviD German 01/24] - "Scary Movie 5.par2" yEnc
			else if (preg_match('/^(([a-zA-Z0-9].+?\s{2,}|Old Dad uppt\s+)(.+?) )\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//>>>  20,36 MB   "Winamp.Pro.v5.70.3392.Incl.Keygen-FFF.par2"   552 B yEnc
			//..:[DoAsYouLike]:..    9,64 MB    "Snooper 1.39.5.par2"    468 B yEnc
			else if (preg_match('/^.+?\s{2,}\d+[,.]\d+ [mMkKgG]([bB]\s{2,}".+?)'.$this->e0.'\s{2,}(\d+ B|\d+[,.]\d+ [mMkKgG][bB]) yEnc$/', $subject, $match))
				return$match[1];
			//(MKV - DVD - Rip - German - English - Italiano) - "CALIGULA (1982) UNCUT.sfv" yEnc
			else if (preg_match('/^(\(.+?\) - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//"sre56565ztrtzuzi8inzufft.par2" yEnc
			else if (preg_match('/^"([a-z0-9]+)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.big")
		{
			//Girls.against.Boys.2012.German.720p.BluRay.x264-ENCOUNTERS - "encounters-giagbo_720p.nfo" yEnc
			if (preg_match('/^([\w.-]+ - ").+?" yEnc$/', $subject, $match))
				return$match[1];
			//wtvrwschdhfthj - [001/246] - "dtstchhtmrrnvn.par2" yEnc
			//oijhuiurfjvbklk - [01/18] - "tb5-3ioewr90f.par2" yEnc
			else if (preg_match('/^([a-z]{3,} - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(08/22) - "538D7B021B362A4300D1C0D84DD17E6D.r06" yEnc
			else if (preg_match('/^\(\d+(\/\d+\) - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//(????) [02/71] - "Lasting Weep (1969-1971).part.par2" yEnc
			else if (preg_match('/^(\(\?{4}\) \[)\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1].$match[2];
			//(01/59) "ThienSuChungQuy_II_E16.avi.001" - 1,49 GB - yEnc
			//(058/183) "LS_HoangChui_2xdvd5.part057.rar" - 8,36 GB -re yEnc
			else if (preg_match('/^\(\d+(\/\d+\) ".+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] -(re)? yEnc$/', $subject, $match))
				return $match[1];
			//[AoU] Upload#00287 - [04/43] - "Upload-ZGT1-20130525.part03.rar" yEnc
			else if (preg_match('/^(\[[a-zA-Z]+\] .+? - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return$match[1];
			//(nate) [01/27] - "nate_light_13.05.23.par2" yEnc
			else if (preg_match('/^\([a-z]+\) \[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//""Absolute Database Component for BCBuilder 4-6 MultiUser Edit 4.85.rar"" yEnc
			else if (preg_match('/^("".+?)'.$this->e0.'" yEnc$/', $subject, $match))
				return $match[1];
			//781e1d8dccc641e8df6530edb7679a0e - (26/30) - "781e1d8dccc641e8df6530edb7679a0e.rar" yEnc
			else if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}.+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.bloaf")
		{
			//36c1d5d4eaf558126c67f00be46f77b6 - (01/22) - "36c1d5d4eaf558126c67f00be46f77b6.par2" yEnc
			if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}.+?" yEnc$/', $subject, $match))
				return $match[1];
			//[10/17] - "EGk13kQ1c8.part09.rar" - 372.48 MB <-> usenet-space-cowboys.info <-> powered by secretusenet.com <-> yEnc
			else if (preg_match('/^\[\d+(\/\d+\] - ".+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] .+? usenet-space.+?yEnc$/', $subject, $match))
				return $match[1];
			//(Neu bei Bitfighter vom 23-07-2013) - "01 - Sido - Bilder Im Kopf.mp3" yEnc
			else if (preg_match('/^(\(.+?\) - ").+?" yEnc$/', $subject, $match))
				return $match[1];
			//(2/8) "Mike.und.Molly.S01E22.Maennergespraeche.GERMAN.DL.DUBBED.720p.BluRay.x264-TVP.part1.rar" - 1023,92 MB - yEnc
			else if (preg_match('/^\(\d+(\/\d+\) ".+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//4y (PW)   [@ usenet-4all.info - powered by ssl.news -] [27,35 GB] [001/118] "1f8867bb6f89491793d3.part001.rar" yEnc
			else if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+(\/\d+\] ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Bennos Special Tools DVD - Die Letzte <> DRM <><> PW <> - (002/183)  "Bennos Special Tools DVD - Die Letzte.nfo" - 8,28 GB - yEnc
			else if (preg_match('/^((\[[A-Za-z]+\]\.)?[a-zA-Z0-9].+?([\^<> ]+give-me-all\.org[\^<> ]+|[\^<> ]+)DRM[\^<> ]+.+? - \()\d+\/\d+\)\s+".+?" - .+? - yEnc/', $subject, $match))
				return $match[1];
			//(1/9) - CyberLink.PhotoDirector.4.Ultra.4.0.3306.Multilingual - "CyberLink.PhotoDirector.4.Ultra.4.0.3306.Multilingual.par2" - 154,07 MB - yEnc
			//(1/5) - Mac.DVDRipper.Pro.4.0.8.Mac.OS.X- "Mac.DVDRipper.Pro.4.0.8.Mac.OS.X.rar" - 24,12 MB - yEnc
			else if (preg_match('/^\(\d+(\/\d+\) - .+? ?- ").+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//[3/3 Helene Fischer - Die Biene Maja 2013 MP3 Helene Fischer - Die Biene Maja 2013 MP3.mp3.vol0+1.PAR2" yEnc
			else if (preg_match('/^\[\d+(\/\d+ .+?\.).+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.blu-ray")
		{
			//"786936833607.MK.A.part086.rar" yEnc
			if (preg_match('/^"(\d+\.MK\.[A-Z])\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//(????) [001/107] - "260713thbldnstnsclw.par2" yEnc
			else if (preg_match('/^(\(\?{4}\) \[)\d+\/\d+\] - "([a-z0-9]+)\..+?" yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//[www.allyourbasearebelongtous.pw]-[The Place Beyond the Pines 2012 1080p US Blu-ray AVC DTS-HD MA 5.1-HDWinG]-[03/97] "tt1817273-us-hdwing-bd.r00" - 46.51 GB - yEnc
			else if (preg_match('/^(\[www\..+?\]-\[.+?\]-\[)\d+\/\d+\] ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(01/71)  - "EwRQCtU4BnaeXmT48hbg7bCn.par2" - 54,15 GB - yEnc
			//(63/63) "dfbgfdgtghtghthgGPGEIBPBrwg34t05.rev" - 10.67 GB - yEnc
			else if (preg_match('/^\(\d+(\/\d+\)(\s+ -)? "[a-zA-Z0-9]+?)\d*\..+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//[01/67] - "O3tk4u681gd767Y.par2" yEnc
			else if (preg_match('/^\[\d+(\/\d+\] - "[a-zA-Z0-9]+\.).+?" yEnc$/', $subject, $match))
				return $match[1];
			//209a212675ba31ca24a8 [usenet-4all.info] [powered by ssl-news] [21,59 GB] [002/223] "209a212675ba31ca24a8.part001.rar" yEnc
			else if (preg_match('/^([a-z0-9]+ \[.+?\] \[.+?\] \[)\d+[,.]\d+ [mMkKgG][bB]\] \[\d+\/\d+\] ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//TIS97CC - "tis97cc.par2" yEnc
			else if (preg_match('/^([A-Z0-9]+ - "[a-z0-9]+\.).+?" yEnc$/', $subject, $match))
				return $match[1];
			//<<OBLIVION - Untouched>><<TLR for usenet-4all.info>><<www.ssl-news.info>><<13/14>> "xxtxxlxxrxxbdxx05.vol421+98.par2" - 2,54 GB - yEnc
			else if (preg_match('/^.+?<<\d+\/\d+>> "(.+?)'.$this->e0.' - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.boneless")
		{
			//4Etmo7uBeuTW[047/106] - "006dEbPcea29U6K.part046.rar" yEnc
			if (preg_match('/^([a-zA-Z0-9]+)\[\d+(\/\d+\] - "[a-zA-Z0-9]+\.).+?" yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//(68/89) "dz1R2wT8hH1iQEA28gRvm.part67.rar" - 7,91 GB - yEnc
			//(01/14)  - "JrjCY4pUjQ9qUqQ7jx6k2VLF.par2" - 4,39 GB - yEnc
			else if (preg_match('/^\(\d+(\/\d+\)\s+(- )?"[a-zA-Z0-9]+\.).+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(110320152518519) [22/78] - "110320152518519.part21.rar" yEnc
			else if (preg_match('/^(\(\d+\) \[)\d+\/\d+\] - "\d+\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//1VSXrAZPD - [123/177] - "1VSXrAZPD.part122.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9]+ - \[)\d+\/\d+\] - "[a-zA-Z0-9]+\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//( Peter Gabriel Albums 24x +17 Singles = 71x cd By Dready Niek )  ( ** By Dready Niek ** ) [113/178] - "Peter Gabriel Albums 24x +17 Singles = 71CDs By Dready Niek (1977-2010).part112.rar" yEnc
			else if (preg_match('/^(\( .+? \)\s+\( .+?\) \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Tarja - Colours In The Dark (2013) "00. Tarja-Colours In The Dark.m3u" yEnc
			else if (preg_match('/^([A-Za-z0-9].+? \((19|20)\d\d\) ")\d{2}\. .+?'.$this->e1, $subject, $match))
				return $match[1];
			//"BB636.part14.rar" - (15/39) - yEnc
			else if (preg_match('/^"([a-zA-Z0-9]+)'.$this->e0.' - \(\d+\/\d+\) - yEnc$/', $subject, $match))
				return $match[1];
			//Lutheria - FC Twente TV Special - Ze wilde op voetbal [16/49] - "Lutheria - FC Twente TV Special - Ze wilde op voetbal.part16.rar" yEnc
			// panter - [001/101] - "74518-The Hunters (2011).par2" yEnc
			else if (preg_match('/^[-a-zA-Z0-9 ]+ \[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Pee Mak Prakanong - 2013 - Thailand - ENG Subs - "Pee Mak Prakanong.2013.part22.rar" yEnc
			//P2H - "AMHZQHPHDUZZJSFZ.vol181+33.par2" yEnc
			else if (preg_match('/^([-a-zA-Z0-9 ]+ - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//(????) [011/161] - "flynns-image-redux.part010.rar" yEnc
			//(Dgpc) [000/110] - "Teen Wolf - Seizoen.3 - Dvd.2 (NLsub).nzb" yEnc
			else if (preg_match('/^\((\?{4}|[a-zA-Z]+)\) \[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1].$match[2];
			//("Massaladvd5Kilusadisc4S1.par2" - 4,55 GB -) "Massaladvd5Kilusadisc4S1.par2" - 4,55 GB - yEnc
			else if (preg_match('/^\("([a-z0-9A-Z]+).+?" - \d+[,.]\d+ [mMkKgG][bB] -\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//"par.4kW9beE.1.vol122+21.par2" yEnc
			else if (preg_match('/^"(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [01/19] - "Age.of.Dinosaurs.German.AC3.HDRip.x264-FuN.par2" yEnc
			//>>>>>Hell-of-Usenet.org>>>>> - [01/35] - "Female.Agents.German.2008.AC3.DVDRip.XviD.iNTERNAL-VideoStar.par2" yEnc
			else if (preg_match('/^(.+?\.(info|org)>+ - \[)\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1].$match[3];
			//[010/101] - "Bf56a8aR-20743f8D-Vf7a11fD-d7c6c0.part09.rar" yEnc
			//[1/9] - "fdbvgdfbdfb.part.par2" yEnc
			else if (preg_match('/^\[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//[LB] - [063/112] - "RVL-GISSFBD.part063.rar" yEnc
			else if (preg_match('/^(\[[A-Z]+\] - \[)\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1].$match[2];
			//(¯`*•.¸(PWP).•*´¯) (071/100) "JUST4US_025.part070.rar" - 22,50 GB – yEnc
			else if (preg_match('/^\(.+?\(PWP\).+?\) \(\d+(\/\d+\) ".+?)'.$this->e0.' .+? \d+[,.]\d+ [mMkKgG][bB] .+ yEnc$/', $subject, $match))
				return $match[1];
			//thnx to original poster [00/98] - "2669DFKKFD2008.nzb ` 2669DFKKFD2008 " yEnc
			else if (preg_match('/^thnx to original poster \[\d+(\/\d+\] - ".+?)(\.part\d*|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4})("| `).+? yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.british.drama")
		{
			//Coronation Street 03.05.2012 [XviD] [01/23] - "coronation.street.03.05.12.[ws.pdtv].par2" yEnc
			//Coronation Street 04.05.2012 - Part 1 [XviD] [01/23] - "coronation.street.04.05.12.part.1.[ws.pdtv].par2" yEnc
			if (preg_match('/^([a-zA-Z0-9].+? \[XviD\] \[)\d\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//The Prisoner E06-09 [001/152] - "06 The General.mkv.001" yEnc
			//Danger Man S2E05-08 [075/149] - "7.The colonel's daughter.avi.001" yEnc
			else if (preg_match('/^([a-zA-Z0-9]+ .+? (S\d+)?E\d+-\d\d \[)\d+\/\d+\] - "\d(\d |\.).+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.cats")
		{
			//Pb7cvL3YiiOu06dsYPzEfpSvvTul[02/37] - "Fkq33mlTVyHHJLm0gJNU.par2" yEnc
			//DLJorQ37rMDvc [01/16] - "DLJorQ37rMDvc.part1.rar" yEnc
			if (preg_match('/^([a-zA-Z0-9]{5,} ?\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.cd.image")
		{
			//[27849]-[altbinEFNet]-[Full]- "ppt-sogz.001" - 7,62 GB - yEnc
			//[27925]--[altbinEFNet]-[Full]- "unl_p2rd.par2" yEnc
			//[27608]-[FULL]-[#altbin@EFNet]-[007/136] "27608-1.005" yEnc
			if (preg_match('/^(\[\d+\]-+\[.+?\]-\[.+?\]-)(\[\d+\/\d+\])? ".+?"( - \d+[,.]\d+ [mMkKgG][bB] -)? yEnc$/', $subject, $match))
				return $match[1];
			//[27930]-[FULL]-[altbinEFNet]-[ Ubersoldier.UNCUT.PATCH-RELOADED ]-[3/5] "rld-usuc.par2" yEnc
			//[27607]-[#altbin@EFNet]-[Full]-[ Cars.Radiator.Springs.Adventure.READNFO-CRIME ] - [02/49] - "crm-crsa.par2" yEnc
			else if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[ .+? \] ?- ?\[)\d+\/\d+\] (- )?".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[27575]-[#altbin@EFNet]-[Full]-[CD1]-[01/58] - "CD1.par2" yEnc
			//[27575]-[altbinEFNet]-[Full]-[CD3]-[00/59] - "dev-gk3c.sfv" yEnc
			else if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[.+?\]-\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(27608-1) [2/5] - "skidrow.nfo" yEnc
			else if (preg_match('/^(\(\d+(-\d+)?\) \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[www.drlecter.tk]-[The_Night_of_the_Rabbit-FLT]-[01/67] "Dr.Lecter.nfo" - 5.61 GB - yEnc
			else if (preg_match('/^(\[www\..+?\]-\[.+?\]-\[)\d+\/\d+\] ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//Slender.The.Arrival-WaLMaRT.PC - [01/26] - "wmt-stal.nfo" - yEnc
			//The.Night.of.the.Rabbit-FLT - [03/66] - "flt-notr.r00" - FAiRLIGHT - 5,10 GB - yEnc
			//Resident.Evil.Revelations-FLT - PC GAME - [03/97] - "Resident.Evil.Revelations-FLT.r00" - FAiRLIGHT - yEnc
			//Afterfall.Insanity.Dirty.Arena.Edition-WaLMaRT - [MULTI6][PCDVD] - [02/45] - "wmt-adae.r00" - PC GAME - yEnc
			else if (preg_match('/^([a-zA-Z0-9.-]{10,} -( PC GAME -| [A-Z0-9\[\]]+ -)? \[)\d+\/\d+\] - ".+?" - (.+? - (\d+[,.]\d+ [mMkKgG][bB] - )?)?yEnc$/', $subject, $match))
				return $match[1];
			//[01/46] - Crashtime 5 Undercover RELOADED - "rld-ct5u.nfo" - PC - yEnc
			//[01/76] - Of.Orcs.And.Men-SKIDROW - "sr-oforcsandmen.nfo" - yEnc
			//PC Game - [01/71] - MotoGP 13-RELOADED Including NoDVD Fix - "MotoGP 13-RELOADED Including NoDVD Fix nfo" - yEnc
			else if (preg_match('/^(PC Game - )?\[\d+(\/\d+\] - .+? - ").+?" -( .+? -)? yEnc$/', $subject, $match))
				return $match[2];
			//Magrunner Dark Pulse FLT (FAiRLIGHT) - [02/70] - "flt-madp par2" - PC - yEnc
			//MotoGP 13 RELOADED - [01/71] - "rld-motogp13 nfo" - PC - yEnc
			//Dracula 4: Shadow of the Dragon FAiRLIGHT - [01/36] - "flt-drc4 nfo" - PC - yEnc
			else if (preg_match('/^([A-Za-z0-9][a-zA-Z0-9: ]{8,}(-[a-zA-Z]+)?( \(.+?\)| - [\[A-Z0-9\]]+)? - \[)\d+\/\d+\] - ".+?" - .+? - yEnc$/', $subject, $match))
				return $match[1];
			//[NEW PC GAME] - Lumber.island-WaLMaRT - "wmt-lisd.nfo" - [01/18] - yEnc
			//Trine.2.Complete.Story-SKIDROW - "sr-trine2completestory.nfo" - [01/78] - yEnc
			else if (preg_match('/^((\[[A-Z ]+\] - )?[a-zA-Z0-9.-]{10,} - ").+?" - \[\d+\/\d+\] - yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.cd.lossless")
		{
			//Flac Flood - Modern Talking - China In Her Eyes (CDM) - "1 - Modern Talking - China In Her Eyes (feat. Eric Singleton) (Video Version).flac" (01/14) (23,64 MB)   136,66 MB yEnc
			//Flac Flood Modern Talking - America - "1 - Modern Talking - Win The Race.flac" (01/18) (29,12 MB) 549,35 MB yEnc
			if (preg_match('/^(Flac Flood( -)? .+? - ").+?" \(\d+\/\d+\) .+? yEnc$/', $subject, $match))
				return $match[1];
			//Cannonball Adderley - Nippon Soul [01/17] "00 - Cannonball Adderley - Nippon Soul.nfo" yEnc
			//Black Tie White Noise [01/24] - "00 - David Bowie - Black Tie White Noise.nfo" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+? \[)\d+\/\d+\]( -)? "\d{2,} - .+?" yEnc$/', $subject, $match))
				return $match[1];
			//[1977] Joan Armatrading - Show Some Emotion - File 15 of 20: 06 Joan Armatrading - Opportunity.flac yEnc
			//The Allman Brothers Band - Statesboro Blues [Swingin' Pig - Bootleg] [1970 April 4] - File 09 of 19: Statesboro Blues.cue yEnc
			else if (preg_match('/^((\[\d{4}\] )?[a-zA-Z0-9].+? - File )\d+ of \d+: .+? yEnc$/', $subject, $match))
				return $match[1];
			//The Allman Brothers Band - The Fillmore Concerts [1971] - 06 The Allman Brothers Band - Done Somebody Wrong.flac yEnc
			else if (preg_match('/^([A-Z0-9].+? - [A-z0-9].+? \[\d{4}\] - )\d{2,} .+? yEnc$/', $subject, $match))
				return $match[1];
			//The Velvet Underground - Peel Slow And See (Box Set) Disc 5 of 5 - 13 The Velvet Underground - Oh Gin.flac yEnc
			else if (preg_match('/^([A-Z0-9].+? - [A-Z0-9].+? Disc \d+ of \d+ - )[A-Z0-9].+?\..+? yEnc$/', $subject, $match))
				return $match[1];
			//(28/55) "Ivan Neville - If My Ancestors Could See Me Now.par2" - 624,44 MB - yEnc
			else if (preg_match('/^\(\d+(\/\d+\) ".+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.chello")
		{
			//0F623Uv71RHKt0jzA7inbGZLk00[2/5] - "l2iOkRvy80bgLrZm1xxw.par2" yEnc
			//GMC2G8KixJKy [01/15] - "GMC2G8KixJKy.part1.rar" yEnc
			if (preg_match('/^([A-Za-z0-9]{5,} ?\[)\d+\/\d+\] - "[A-Za-z0-9]{3,}.+?" yEnc$/', $subject, $match))
				return $match[1];
			//Imactools.Cefipx.v3.20.MacOSX.Incl.Keyfilemaker-NOY [03/10] - "parfile.vol000+01.par2" yEnc
			else if (preg_match('/^([a-zA-Z0-9][a-zA-Z0-9.-]+ \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Siberian Mouses LS, BD models and special... [150/152] - "Xlola - Luba, Sasha & Vika.avi.jpg" yEnc
			else if (preg_match('/^([A-Za-z0-9-]+ .+?[. ]\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.classic.tv.shows")
		{
			//Re: REQ: All In The Family - "Archie Bunkers Place 1x01 Archies New Partner part 1.nzb" yEnc
			if (preg_match('/^(Re: REQ: .+? - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Per REQ - "The.Wild.Wild.West.S03E11.The.Night.of.the.Cut-Throats.DVDRip.XVID-tz.par2" 512x384 [01/40] yEnc
			else if (preg_match('/^(Per REQ - ".+?)'.$this->e0.' .+? \[\d+\/\d+\] yEnc$/', $subject, $match))
				return $match[1];
			//By req: "Dennis The Menace - 4x25 - Dennis and the Homing Pigeons.part05.rar" yEnc
			else if (preg_match('/^(By req: ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//I Spy HQ DVDRips "I Spy - 3x26 Pinwheel.part10.rar" [13/22] yEnc
			else if (preg_match('/^([a-zA-Z ]+HQ DVDRips ".+?)'.$this->e0.' \[\d+\/\d+\] yEnc$/', $subject, $match))
				return $match[1];
			//Sledge Hammer! S2D2 [016/138] - "SH! S2 D2.ISO.016" yEnc
			//Sledge Hammer! S2D2 [113/138] - "SH! S2 D2.ISO.1132 yEnc
			//Lost In Space - Season 1 - [13/40] - "S1E02 - The Derelict.avi" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+? (S\d+D\d+|- Season \d+ -) \[)\d+\/\d+\] - ".+?"? yEnc$/', $subject, $match))
				return $match[1];
			//Night Flight TV Show rec 1991-01-12 (02/54) - "night flight rec 1991-01-12.nfo" yEnc
			//Night Flight TV Show rec 1991-05-05 [NEW PAR SET] (1/9) - "night flight rec 1991-05-05.par2" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+? \d{4}-\d\d-\d\d( \[.+?\])? \()\d+\/\d+\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//The.Love.Boat.S05E08 [01/31] - "The.Love.Boat.S05E08.Chefs.Special.Kleinschmidt.New.Beginnings.par2" yEnc
			else if (preg_match('/^([a-zA-Z0-9][a-zA-Z0-9.-]+ \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//"Batman - S1E13-The Thirteenth Hat.par2" yEnc
			else if (preg_match('/^(".+?)(\.part\d*|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//Re: Outside Edge series 1 - [01/20] - "Outside Edge S01.nfo" yEnc
			else if (preg_match('/^(Re: )?([a-zA-Z0-9]+ .+? series \d+ - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[2];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.comp")
		{
			//Sims3blokjesremover [0/0] -3162   "Sims3blokjesremover.nzb" yEnc
			//xSIMS_The_Sims_3_Censor_Remover_v2.91
			if (preg_match('/^([\w.]+\s+\[)\d+\/\d+\] -\d+\s+".+?" yEnc$/i', $subject, $match))
				return $match[1];
			//Photo Mechanic 5.0 build 13915  (1/6) "Photo Mechanic 5.0 build 13915  (1).par2" - 32,97 MB - yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?\s+\()\d+\/\d+\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(45/74) NikJosuf post Magento tutorials "43 - Theming Magento 19 - Adding a Responsive Slideshow.mp4" yEnc
			else if (preg_match('/^\(\d+(\/\d+\) .+? post .+? ").+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.console.ps3")
		{
			//[4062]-[ABGX.net] - "unlimited-skyrim.legendary.multi4.ps3.par2" - 17.10 GB - yEnc
			if (preg_match('/^(\[\d+\]-\[ABGX\.(net|NET)\] - ").+?(" - \d+[,.]\d+ [kKmMgG][bB] - )yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//[4017]-[abgx]- "duplex.nfo" yEnc
			else if (preg_match('/^(\[\d+\]-\[abgx\] - ").+?" yEnc$/', $subject, $match))
				return $match[1];
			//[4197] [036/103] - "ant-mgstlcd2.r34" yEnc
			else if (preg_match('/^(\[\d+\] \[\d+\/\d+\] - ").+?" yEnc$/', $subject, $match))
				return $match[1];
			//Musou_Orochi_Z_JPN_PS3-JPMORGAN [62/62] - "jpmorgan.nfo" yEnc
			else if (preg_match('/([A-Z0-9]\w{10,}-?PS3-[a-zA-Z0-9]+ \[)\d+\/\d+\] - ".+?" $/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.country.mp3")
		{
			//Attn: wulf109 - Jim Reeves - There's Someone Who Loves You - 01 - Anna Marie.mp3 yEnc
			//Attn: wulf109 - Jim Reeves - There's Someone Who Loves You - Front.jpg yEnc
			if (preg_match('/^(Attn: .+? - .+? - .+? - )(\d+ - )?.+?\.[A-Za-z0-9]{2,4} yEnc$/', $subject, $match))
				return $match[1];
			//Jo Dee Messina - A Joyful Noise    "01 - Winter Wonderland.mp3" yEnc
			//Karen Lynne - 2000 - Six Days in December   "Pat Drummond and Karen Lynne - 01 - The Rush.mp3" yEnc
			else if (preg_match('/^([A-Z0-9].{3,} -( (19|20)\d\d - )?[A-Z0-9].{3,}\s+")[A-Z0-9].{3,} - \d+ - [A-Z0-9].+?\.[A-Za-z0-9]{2,4}" yEnc$/', $subject, $match))
				return $match[1];
			//"Heather Myles - Highways and Honky Tonks - 05 - True Love.mp3" yEnc
			//"Reba McEntire - The Secret Of Giving - A Christmas Collection - 09 - This Christmas.mp3" yEnc
			//]"Heather Myles - Highways and Honky Tonks - 05 - True Love.mp3" yEnc
			//"Reba McEntire - Moments & Memories - The Best Of Reba - Australian-back.jpg" yEnc
			//"Reba McEntire - The Secret Of Giving - A Christmas Collection - 01 - This Is My Prayer For You.mp3" yEnc
			//"Reba McEntire - American Legends-Best Of The Early Years - 02 - You Really Better Love Me After This.Mp3" yEnc
			else if (preg_match('/^((\]?"[A-Z0-9].{3,} - )+?([A-Z0-9].{3,}? - )+?)(\d\d - )?[a-zA-Z0-9].+?\.[A-Za-z0-9]{2,4}" yEnc$/', $subject, $match))
				return $match[1];
			//"Reba McEntire - Duets.m3u" yEnc
			//"Reba McEntire - Greatest Hits Volume Two - back.jpg" yEnc
			//"Reba McEntire - American Legends-Best Of The Early Years.m3u" yEnc
			//"Jason Allen - 00 - nfo.txt" yEnc
			//"Sean Ofarrell-Life Is A Teacher -  07 - Here Again.MP3" yEnc
			else if (preg_match('/^("[A-Z0-9].{3,}? - )(([A-Z0-9][^-]{3,}?|\s*\d\d) - )?[a-zA-Z0-9].{2,}?\.[A-Za-z0-9]{2,4}?" yEnc$/', $subject, $match))
				return $match[1];
			//]"Heather_Myles_-_Highways_And_Honky_Tonks-back.jpg" yEnc
			else if (preg_match('/^(\]"[\w-]{10,}?)-[a-zA-Z0-9]+\.[a-zA-Z0-9]{2,4}" yEnc$/', $subject, $match))
				return $match[1].' - ';
			//Merv & Maria - Chasing Rainbows  Merv & Maria - 01 - Sowin' Love.mp3" yEnc
			else if (preg_match('/^([A-Z0-9].{3,}? - [A-Z0-9].{3,}? - )\d\d - [a-zA-Z0-9].{2,}?\.[A-Za-z0-9]{2,4}?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.dc")
		{
			//Eragon postet    Horror     S01 E01   german Sub    [02/22] - "kopiert neu.par2" yEnc
			//Eragon postet   Rapunzel  S02E12   german Sub  hardcodet   [02/18] - "Rapunzel S02E12 HDTV x264-LOL ger subbed.par2" yEnc
			if (preg_match('/^([A-Z0-9].+? postet\s+.+?\s+\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.documentaries")
		{
			//#sterntuary - Alex Jones Radio Show - "05-03-2009_INFO_BAK_ALJ.nfo" yEnc
			if (preg_match('/^(#sterntuary - .+? - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.downunder")
		{
			//RWlgVffClWxD0vXT1peIwb9DubTLMiYm3nvD1aMMDe[04/16] - "A9jFik7Fk4hCG4GWuxAg.r02" yEnc
			if (preg_match('/^([a-zA-Z0-9]{5,}\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.dvd")
		{
			//thnx to original poster [00/98] - "2669DFKKFD2008.nzb ` 2669DFKKFD2008 " yEnc
			if (preg_match('/^thnx to original poster \[\d+(\/\d+\] - ".+?)(\.part\d*|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4})("| `).+? yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.dvd-r")
		{
			//katanxya "katanxya7221.par2" yEnc
			if (preg_match('/^katanxya "katanxya\d+/', $subject, $match))
				return $match[0];
			//[01/52] - "H1F3E_20130715_005.par2" - 4.59 GB yEnc
			else if (preg_match('/^\[\d+\/\d+\] - "([A-Z0-9](19|20)\d\d[01]\d[123]\d_\d+\.).+?" - \d+[,.]\d+ [mMkKgG][bB] yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.erotica")
		{
			//[278997]-[FULL]-[#a.b.erotica]-[ chi-the.walking.dead.xxx ]-[06/51] - "chi-the.walking.dead.xxx-s.mp4" yEnc
			//[######]-[FULL]-[#a.b.teevee@EFNet]-[ Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD ] [1/5] - "Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD.nfo" yEnc
			//Re: [147053]-[FULL]-[#a.b.teevee]-[ Top_Gear.20x04.HDTV_x264-FoV ]-[11/59] - "top_gear.20x04.hdtv_x264-fov.r00" yEnc (01/20)
			if (preg_match('/(\[[\d#]+\]-\[.+?\]-\[.+?\]-\[ .+? \][- ]\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<TOWN><www.town.ag > <download all our files with>>> www.ssl-news.info <<< > [01/28] - "TayTO-heyzo_hd_0317_full.par2" - 2,17 GB yEnc
			else if (preg_match('/^<TOWN><www\.town\.ag > <download all our files with>>> www\.ssl-news\.info <<< > \[\d+(\/\d+\] - ".+?)'.$this->e0.' - /', $subject, $match))
				return $match[1];
			//NihilCumsteR [1/8] - "Conysgirls.cumpilation.xxx.NihilCumsteR.par2" yEnc
			else if (preg_match('/^NihilCumsteR \[\d+\/\d+\] - "(.+?NihilCumsteR\.)/', $subject, $match))
				return $match[1];
			//"Lesbian seductions 26.part.nzb" yEnc
			else if (preg_match('/^(".+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.etc")
		{
			//7000999555666777123754 - [334/389] - "The Secret of Bible & Jesus. Beyond The Da Vinci Code - YouTube.3gp" yEnc
			if (preg_match('/^(\d+ - \[)\d+\/\d+\] - ".+?'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.font")
		{
			//RWlgVffClWxD0vXT1peIwb9DubTLMiYm3nvD1aMMDe[04/16] - "A9jFik7Fk4hCG4GWuxAg.r02" yEnc
			if (preg_match('/^([a-zA-Z0-9]{5,}\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.fz")
		{
			//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
			if (preg_match('/^(>ghost-of-usenet\.org>.+?<.+?> ").+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.games")
		{
			//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
			if (preg_match('/^(>ghost-of-usenet\.org>.+?<.+?> ").+?" yEnc$/', $subject, $match))
				return $match[1];
			//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
			else if (preg_match('/^(<ghost-of-usenet\.org>.+? \[)\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
				return $match[1];
			//[ Dawn.of.Fantasy.Kingdom.Wars-PROPHET ] - [12/52] - "ppt-dfkw.part04.rar" yEnc
			else if (preg_match('/^(\[ [-.a-zA-Z0-9]+ \] - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.german.movies")
		{
			//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
			if (preg_match('/^(>ghost-of-usenet\.org>.+?<.+?> ").+?" yEnc$/', $subject, $match))
				return $match[1];
			//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
			else if (preg_match('/^(<ghost-of-usenet\.org>.+? \[)\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.ghosts")
		{
			//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
			if (preg_match('/^(<ghost-of-usenet\.org>.+? \[)\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.hdtv.x264")
		{
			//(23/36) "Love.Is.In.The.Meadow.S08E08.HDTV.720p.x264.ac3.part22.rar" - 2,80 GB - yEnc
			if (preg_match('/^\(\d+(\/\d+\) ".+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//Hard.Target.1993.1080p.Bluray.X264-BARC0DE - [36/68] - "BARC0DE1080pHTAR.r22" yEnc
			//Goddess.2013.720p.BDRip.x264.AC3-noOne  [086/100] - "Goddess.2013.720p.BDRip.x264.AC3-noOne.part84.rar" yEnc
			else if (preg_match('/^([A-Z0-9a-z][A-Za-z0-9.-]+ -? \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//I Love Democracy - Norwegen - Doku -  2012 - (German)  - AC3 HD720p  Avi by Waldorf -  [02/71] - "Waldorf.jpg" yEnc
			else if (preg_match('/(.+?\s+by Waldorf\s+-\s+\[)\d+\/\d+\]\s+-\s+".+?"\s+yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.highspeed")
		{
			//Old Dad uppt 18 und immer (noch) Jungfrau DvD Rip AC3 XviD German 02/34] - "18 und immer (noch) Jungfrau.part01.rar" yEnc
			//Old Dad uppt In ihrem Haus DVD Ripp AC3 German Xvid [01/31] - "In ihrem Haus.par2" yEnc
			//Old Dad uppt Eine offene Rechnung XviD German DVd Rip[02/41] - "Eine offene Rechnung.part01.rar" yEnc
			//Old Dad uppMiss Marple: Der Wachsblumenstrauß , Wunschpost Xvid German10/29] - "Miss Marple Der Wachsblumenstrauß.part09.rar" yEnc
			if (preg_match('/^(Old\s+Dad\s+uppt?.+?)( mp4| )?\[?\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[03/61] - "www.realmom.info - xvid - xf-fatalmovecd1.r00" - 773,34 MB - yEnc
			else if (preg_match('/^\[\d+(\/\d+\] - ".+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//www.usenet-town.com [Sponsored by Astinews] (103/103) "Intimate.Enemies.German.2007.AC3.[passwort protect].vol60+21.PAR2" yEnc
			else if (preg_match('/^www\..+? \[Sponsored.+?\] \(\d+(\/\d+\) ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//(????) [3/4] - "0024456.pdf.par2" yEnc
			else if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "(.+?)(\.part\d*|\.rar|\.pdf)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.inner-sanctum")
		{
			////ea17079f47de702eead5114038355a70 [1/9] - "00-da_morty_-_boondock_sampler_02-(tbr002)-web-2013-srg.m3u" yEnc
			if (preg_match('/^([a-fA-F0-9]+) \[\d+\/\d+\] - ".+?'.$this->e1, $subject, $match))
				return $match[1];
			//[30762]-[android]-[ Fairway.Solitaire.v1.91.1-AnDrOiD ] [01/10] - "AnDrOiD.nfo" yEnc
			else if (preg_match('/^(\[\d+\]-\[.+?\]-\[ .+? \] \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.milo")
		{
			//RWlgVffClWxD0vXT1peIwb9DubTLMiYm3nvD1aMMDe[04/16] - "A9jFik7Fk4hCG4GWuxAg.r02" yEnc
			//H8XxBd44qXBGk [05/15] - "H8XxBd44qXBGk.part5.rar" yEnc
			if (preg_match('/^([a-zA-Z0-9]{5,} ?\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.mojo")
		{
			//[17/61] - "www.realmom.info - xvid - xf-devilstomb.r14" - 773,11 MB - yEnc
			if (preg_match('/^\[\d+(\/\d+\] - ".+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//RWlgVffClWxD0vXT1peIwb9DubTLMiYm3nvD1aMMDe[04/16] - "A9jFik7Fk4hCG4GWuxAg.r02" yEnc
			//3JgtmNAbZbJ6Q [14/21] - "parfile.par2" yEnc
			else if (preg_match('/^([a-zA-Z0-9]{5,} ?\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.mom")
		{
			//[usenet4ever.info] und [SecretUsenet.com] - 96e323468c5a8a7b948c06ec84511839-u4e - "96e323468c5a8a7b948c06ec84511839-u4e.par2" yEnc
			if (preg_match('/^(\[usenet4ever\.info\] und \[SecretUsenet\.com\] - .+?-u4e - ").+?" yEnc$/', $subject, $match))
				return $match[1];
			//brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [01/26] - "Be.Cool.German.AC3.HDRip.x264-FuN.par2" yEnc
			else if (preg_match('/(.+?\.net <<<Partner von SSL-News\.info>>> - \[)\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1].$match[2];
			//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
			else if (preg_match('/^(<ghost-of-usenet\.org>.+? \[)\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
				return $match[1];
			//brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [21/22] - "e4e4ztb54238ibftu.vol127+128.par2" yEnc
			else if (preg_match('/^brothers-of-usenet.info\/\.net <<<Partner von SSL-News.info>>> - \[\d+\/\d+\] - "(.+?)(\.vol|\.par).+?" yEnc$/', $subject, $match))
				return $match[1];
			//58600-0[51/51] - "58600-0.vol0+1.par2" yEnc
			else if (preg_match('/^(\d+)\-\d+\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.moovee")
		{
			//[133170]-[FULL]-[#a.b.moovee]-[ Hansel.And.Gretel.Witch.Hunters.DVDR-iGNiTiON ]-[032/117] "ign-witchhunters.r24" yEnc
			//Re: [133388]-[FULL]-[#a.b.moovee]-[ Familiar.Grounds.2011.DVDRip.XViD-TWiST ]-[01/59] - "twist-xvid-terrainsconus.nfo" yEnc
			//[134212]-[FULL]-[#a.b.moovee]-[ Monsters.Inc.2001.1080p.BluRay.x264-CiNEFiLE ] [80/83] - "monsters.inc.2001.1080p.bluray.x264-cinefile.vol015+16.par2" yEnc
			//[134912]-[FULL]-[#a.b.moovee]-[ Epic.2013.DVDRip.X264-SPARKS ]-[01/70]- "epic.2013.dvdrip.x264-sparks.nfo" yEnc
			if (preg_match('/(\[\d+\]-\[.+?\]-\[.+?\]-\[ .+? \](-| ))\[\d+\/\d+\][ -]* ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[42788]-[#altbin@EFNet]-[Full]- "margin-themasterb-xvid.par2" yEnc
			else if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]- ").+?'.$this->e1, $subject, $match))
				return $match[1];
			//[ Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE ]-[01/44] - "Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE.par2" yEnc
			//[ Admission.2013.720p.WEB-DL.DD5.1.H.264-HD4FUN ] - [01/82] - "Admission.2013.720p.WEB-DL.DD5.1.H.264-HD4FUN.nfo" yEnc
			else if (preg_match('/^(\[ [a-zA-Z0-9.-]+ \] ?- ?\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(????) [0/1] - "A.Good.Day.to.Die.Hard.2013.nzb" yEnc
			else if (preg_match('/^\(\?{4}\) \[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//[xxxxx]-[#a.b.moovee@EFNet]-[ xxxxx ]-[02/66] - "tulob88.part01.rar" yEnc
			else if (preg_match('/^\[x+\]-\[.+?\]-\[ x+ \]-\[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Groove.2000.iNTERNAL.DVDRip.XviD-UBiK - [001/111] - "ubik-groove-cd1.par2" yEnc
			//Antony.and.Cleopatra.1972.720p.WEB-DL.H264-brento -[35/57] - "Antony.and.Cleopatra.1972.720p.WEB-DL.AAC2.0.H.264-brento.part34.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9._-]+ - ?\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[133668] - p00okjiue34635xxzx$$Â£Â£zll-b.vol3+2.PAR2 - [005/118]  yEnc
			else if (preg_match('/^(\[\d+\] - [a-z0-9]+.+?)(\.part\d*|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4}) - \[\d+\/\d+\]\s+yEnc$/', $subject, $match))
				return $match[1];
			//-[004/115] - "134218-0.par2" yEnc
			//[134824]-[001/117] - "134824-0.0" yEnc
			//[134510]-[REPOST]-[001/110] - "134510-rp-0.0" yEnc
			else if (preg_match('/^((\[\d+\])?-(\[REPOST\])?\[)\d+(\/\d+\] - "\d+-)\d\..+?" yEnc$/', $subject, $match))
				return $match[1].$match[4];
			//[134517]-[01/76] - "Lara Croft Tomb Raider 2001 720p BluRay DTS x264-RightSiZE.nfo" yEnc
			else if (preg_match('/^\[\d+\]-\[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//(Iron.Man.3.2013.R5.DVDRip.XviD-AsA) (01/26) - "Iron.Man.3.2013.R5.DVDRip.XviD-AsA.part01.part.sfv" yEnc
			else if (preg_match('/^(\([a-zA-Z0-9.-]+\) \()\d+\/\d+\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(Classic Surf) Morning.Of.The.Earth.1971 [03/29] - "Morning.Of.The.Earth.1971.part02.rar" yEnc
			else if (preg_match('/^(\([a-zA-Z0-9].+?\) [a-zA-Z0-9.-]+ \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			
			else if (preg_match('/^(\[\d+\]-\[.+?\]-\[)\d+\/\d+\] - "\d+-.+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.movies.divx")
		{
			//(www.Thunder-News.org) >CD2< <Sponsored by Secretusenet> - "exvid-emma-cd2.par2" yEnc
			if (preg_match('/^(\(www\.Thunder-News\.org\) .+? - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Movieland Post Voor FTN - [01/43] - "movieland0560.par2" yEnc
			if (preg_match('/^([a-zA-Z ]+Post Voor FTN - \[\d+\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Disney short films collection by mayhem masta"1923 - Alice's Wonderland.vol15+7.par2" yEnc
			else if (preg_match('/(.+?by mayhem masta".+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.mp3.complete_cd")
		{
			//[052713]-[#eos@EFNet]-[All_Shall_Perish-Montreal_QUE_0628-2007-EOS]-[09/14] "06-all_shall_perish-deconstruction-eos.mp3" yEnc
			if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[)\d+\/\d+\] ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.multimedia")
		{
			//Escort.2006.DUTCH.WEB-RiP.x264-DLH - [01/23] - "Escort.2006.DUTCH.WEB-RiP.x264-DLH.par2" yEnc
			//Tusenbroder.S01E05.PDTV.XViD.SWEDiSH-NTV  [01/69] - "ntv-tusenbroder.s01e05.nfo" yEnc
			if (preg_match('/^([A-Z0-9a-z.-]{10,}\s+(- )?\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.multimedia.anime")
		{
			//High School DxD New 01 (480p|.avi|xvid|mp3) ~bY Hatsuyuki [01/18] - "[Hatsuyuki]_High_School_DxD_New_01_[848x480][76B2BB8C].avi.001" yEnc
			if (preg_match('/(.+? \((360|480|720|1080)p\|.+? ~bY .+? \[)\d+\/\d+\] - ".+?\[[A-F0-9]+\].+?'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.multimedia.anime.highspeed")
		{
			//High School DxD New 01 (480p|.avi|xvid|mp3) ~bY Hatsuyuki [01/18] - "[Hatsuyuki]_High_School_DxD_New_01_[848x480][76B2BB8C].avi.001" yEnc
			if (preg_match('/(.+? \((360|480|720|1080)p\|.+? ~bY .+? \[)\d+\/\d+\] - ".+?\[[A-F0-9]+\].+?'.$this->e1, $subject, $match))
				return $match[2];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.multimedia.documentaries")
		{
			//"Universe S4E08.part40.rar" - [41 of 76 - 10013 kb] yEnc
			if (preg_match('/^(".+?)'.$this->e0.' - \[\d+ of \d+ - \d+ [kKmMgG][bB]\] yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.multimedia.scifi")
		{
			//some m4vs - "SilverHawks_v1eps01_The Origin Story.par2" yEnc
			if (preg_match('/^(some m4vs - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.ps3")
		{
			//[4197] [036/103] - "ant-mgstlcd2.r34" yEnc
			if (preg_match('/^\[\d+\] \[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.sounds.flac")
		{
			//[32974]-[FULL]-[#a.b.flac]-[ Tenniscoats-Tokinouta-JP-CD-FLAC-2011-BCC ]-[04/28] - "00-tenniscoats-tokinouta-jp-cd-flac-2011.nfo" yEnc
			if (preg_match('/^\[\d+\]-\[[a-zA-Z]+\]-\[\#.+?\]-\[(.+?)\]-\[\d.?\/\d.?] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[33008]-[FULL]-[a b flac]-[ Moby-Destroyed-Deluxe_Edition-2CD-FLAC-2011-WRE ]-[02/37] - "000-moby-destroyed-deluxe_edition-2cd-2011 nfo" yEnc
			else if (preg_match('/^\[\d+\]-\[[a-zA-Z]+\]-\[.+?\]-\[(.+?)\]-\[\d.?\/\d.?] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.sounds.lossless")
		{
			//http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
			if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Sonny Landreth - 2010 - Mississippi Blues - 04 of 29 - 00 - Mississippi Blues.sfv yEnc
			//Fruteland Jackson - 1996 - ... Is All I Crave - 08 of 20 - 00 - Fruteland Jackson - ... Is All I Crave.log yEnc
			else if (preg_match('/^([A-Z0-9].+? - \d{4} - .+? - )\d+ of \d+ - \d+ - .+? yEnc$/', $subject, $match))
				return $match[1];
			//Restless Breed00/27] - ".nzb" yEnc
			else if (preg_match('/^(.+?[a-zA-Z0-9][^\[( ])\d{2,}(\/\d+\] - ").+?" yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//(Rolling Stones) [01/28] - "Bell Center, Montreal, QC - 09-06-2013 (alt source sb remaster).par2" yEnc
			else if (preg_match('/^\([A-Z0-9][a-zA-Z0-9 ]+\) \[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.sounds.mp3")
		{
			//(dream-of-usenet.info) - [04/15] - "Enya-And_Winter_Came...-2008.part2.rar" yEnc
			if (preg_match('/^\(dream-of-usenet\.info\) - \[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
			else if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//>>> CREATIVE COMMONS NZB <<< "dexter romweber duo-lookout" - File 1 of 9: "creative_commons_nzb_dexter_romweber_duo-lookout.rar" yEnc
			else if (preg_match('/^(>>> CREATIVE COMMONS NZB <<< ".+?" - File )\d+ of \d+: ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<<<usenet-space-cowboys.info>>>  <<<Powered by https://secretusenet.com>< "Justin_Bieber-Believe_Acoustic-2013-pLAN9_usenet-space-cowbys.info.rar" >< 4/6 (78.65 MB) >< 60.84 MB > yEnc
			else if (preg_match('/^(.+?usenet-space.+?Powered by.+? ".+?)'.$this->e0.'.+? \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\) .+? \d+[.,]\d+ [kKmMgG][bB] .+?yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.teevee")
		{
			//[278997]-[FULL]-[#a.b.erotica]-[ chi-the.walking.dead.xxx ]-[06/51] - "chi-the.walking.dead.xxx-s.mp4" yEnc
			//[######]-[FULL]-[#a.b.teevee@EFNet]-[ Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD ] [1/5] - "Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD.nfo" yEnc
			//Re: [147053]-[FULL]-[#a.b.teevee]-[ Top_Gear.20x04.HDTV_x264-FoV ]-[11/59] - "top_gear.20x04.hdtv_x264-fov.r00" yEnc (01/20)
			if (preg_match('/(\[[\d#]+\]-\[.+?\]-\[.+?\]-\[ .+? \][- ]\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[#a.b.teevee] Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD - [01/24] - "Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD.nfo" yEnc
			else if (preg_match('/^(\[#a\.b\.teevee\] .+? - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//ah63jka93jf0jh26ahjas558 - [01/22] - "ah63jka93jf0jh26ahjas558.par2" yEnc
			else if (preg_match('/^([a-z0-9]+ - )\[\d+\/\d+\] - "[a-z0-9]+\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//fhdbg34rgjdsfd008c (42/43) "fhdbg34rgjdsfd008c.vol062+64.par2" - 3,68 GB - yEnc
			else if (preg_match('/^([a-z0-9]+ \()\d+\/\d+\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//t2EI3CdWdF0hi5b8L9tkx[08/52] - "t2EI3CdWdF0hi5b8L9tkx.part07.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9]+)\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(01/37) "Entourage S08E08.part01.rar" - 349,20 MB - yEnc
			//(01/24) "EGtnu7OrLNQMO2pDbgpDrBL8SnjZDpab.nfo" - 686 B - 338.74 MB - yEnc (1/1)
			else if (preg_match('/^\(\d+(\/\d+\) ".+?)'.$this->e0.' - \d.+?B - (\d.+?B -)? yEnc$/', $subject, $match))
				return $match[1];
			//[01/42] - "King.And.Maxwell.S01E08.1080p.WEB-DL.DD5.1.H264-Abjex.par2" yEnc
			else if (preg_match('/^\[\d+(\/\d+\] - "[A-Za-z0-9.-]+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Divers (12/42) -"Juste.Pour.Rire.2013.Gala.JF.Mercier.FRENCH.720p.HDTV.x264-QuebecRules.part11.rar" yEnc
			//Par le chapeau (06/43) - "8C7D59F472E03.part04.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9 ]+ \()\d+(\/\d+\) - ?".+?)'.$this->e1, $subject, $match))
				return $match[1].$match[2];
			//House.Hunters.International.S05E502.720p.hdtv.x264 [01/27] - "House.Hunters.International.S05E502.720p.hdtv.x264.nfo" yEnc
			//Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS - [01/33] - "Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS.par2" yEnc
			else if (preg_match('/^(Re: )?([a-zA-Z0-9._-]+([{}A-Z_]+)?( -)? \[)\d+(\/| of )\d+\]( -)? ".+?" yEnc$/', $subject, $match))
				return $match[2];
			//Silent Witness S15E02 Death has no dominion.par2 [01/44] - yEnc
			else if (preg_match('/^([a-zA-Z0-9 ]+)(\.part\d*|\.rar)?(\.vol.+? |\.[A-Za-z0-9]{2,4} )\[\d+(\/\d+\] - yEnc)$/', $subject, $match))
				return $match[1].$match[4];
			//(bf1) [03/31] - "The.Block.AU.Sky.High.S07E61.WS.PDTV.XviD.BF1.part01.sfv" yEnc (1/1)
			else if (preg_match('/^\(bf1\) \[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//[ TVPower ] - "Dexter.S07E10.720p.HDTV.x264-NLsubs.par2" yEnc
			//[ TVPower ] - [010/101] - "Desperate.Housewives.S08Disc2.NLsubs.part009.rar" yEnc
			else if (preg_match('/^(\[ [A-Za-z]+ \] - (\[\d+\/\d+\] - )?".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//[www.allyourbasearebelongtous.pw]-[WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams]-[03/69] "WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams.r00" - 1.58 GB - yEnc
			else if (preg_match('/^(\[.+?\]-\[.+?\]-\[)\d+\/\d+\] ".+?" - \d+([.,]\d+ [kKmMgG])?[bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(www.Thunder-News.org) >CD1< <Sponsored by Secretusenet> - "moovee-fastest.cda.par2" yEnc
			else if (preg_match('/^(\(www\..+?\) .+? <Sponsored.+?> - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//<<<Pitbull>>> usenet-space-cowboys.info <<<Powered by https://secretusenet.com>< "S05E03 Pack die Badehose ein_usenet-space-cowbys.info.par2" >< 01/10 (411,16 MB) >< 3,48 kB > yEnc
			else if (preg_match('/(\.info .+?Powered by .+?\.com ".+?)'.$this->e0.' .+? \d+\/\d+ \(\d+[,.]\d+ [mMkKgG][bB]\) .+? yEnc$/', $subject, $match))
				return $match[1];
			//Newport Harbor The Real Orange County - S01E01 - A Black & White Affair [01/11] - "Newport Harbor The Real Orange County - S01E01 - A Black & White Affair.mkv" yEnc
			else if (preg_match('/^([a-zA-Z0-9]+ .+? - S\d+E\d+ - .+? \[)\d+\/\d+\] - ".+?\..+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.town")
		{
			//<TOWN><www.town.ag > <download all our files with>>> www.ssl-news.info <<< > [05/87] - "Deep.Black.Ass.5.XXX.1080p.WEBRip.x264-TBP.part03.rar" - 7,87 GB yEnc
			if (preg_match('/town\.ag.+?download all our files with.+?www\..+?\.info.+? \[\d+(\/\d+\] - ".+?)(-sample)?'.$this->e0.' - \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.tun")
		{
			//[PRiVATE] UmVndWxhci5TaG93LlMwMkUyOC4xMDgwcC5CbHVSYXkueDI2NC1ERWlNT1M= [06/32] - "89769f0736162e1cb113655cb10e42ff.part02.rar" yEnc
			if (preg_match('/^(\[PRiVATE\] [a-z0-9A-Z]+=+ \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.tv")
		{
			//Borgen.2x02.A.Bruxelles.Non.Ti.Sentono.Urlare.ITA.BDMux.x264-NovaRip [02/22] - "borgen.2x02.ita.bdmux.x264-novarip.par2" yEnc
			if (preg_match('/^([a-zA-Z0-9.-]+ \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(bf1) [03/31] - "The.Block.AU.Sky.High.S07E56.WS.PDTV.XviD.BF1.part01.sfv" yEnc
			else if (preg_match('/^\(bf1\) \[\d+(\/\d+\] - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "dk.binaer.tv")
		{
			//Store.Boligdroemme.S02E06.DANiS H.HDTV.x264-TVBYEN - [01/28] - "store.boligdroemme.s02e06.danis h.hdtv.x264-tvbyen.nfo" yEnc
			if (preg_match('/^([a-zA-Z0-9].+? - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else
			return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
	}

	//
	//	Cleans usenet subject before inserting, used for collectionhash. If no regexes matched on collectionsCleaner.
	//
	public function collectionsCleanerHelper($subject, $groupName, $nofiles)
	{
		// For non music groups.
		if (!preg_match('/\.(flac|lossless|mp3|music|sounds)/', $groupName))
		{
			// File/part count.
			$cleansubject = preg_replace('/((( \(\d\d\) -|(\d\d)? - \d\d\.|\d{4} \d\d -) | - \d\d-| \d\d\. [a-z]).+| \d\d of \d\d| \dof\d)\.mp3"?|(\)|\(|\[|\s)\d{1,5}(\/|(\s|_)of(\s|_)|-)\d{1,5}(\)|\]|\s|$|:)|\(\d{1,3}\|\d{1,3}\)|[^\d]{4}-\d{1,3}-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s|\d{1,3} - of \d{1,3}/i', ' ', $subject);
			// File extensions.
			$cleansubject = preg_replace('/'.$this->e0.'/i', ' ', $cleansubject);
			// File extensions - If it was not in quotes.
			$cleansubject = preg_replace('/(-? [a-z0-9]+-?|\(?\d{4}\)?(_|-)[a-z0-9]+)\.jpg"?| [a-z0-9]+\.mu3"?|((\d{1,3})?\.part(\d{1,5})?|\d{1,5} ?|sample|- Partie \d+)?\.(7z|\d{3}(?=(\s|"))|avi|diz|docx?|epub|idx|iso|jpg|m3u|m4a|mds|mkv|mobi|mp4|nfo|nzb|par(\s?2|")|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|xls|zip|z{2,3})"?|(\s|(\d{2,3})?-)\d{2,3}\.mp3|\d{2,3}\.pdf|\.part\d{1,4}\./i', ' ', $cleansubject);
			// File Sizes - Non unique ones.
			$cleansubject = preg_replace('/\d{1,3}(,|\.|\/)\d{1,3}\s(k|m|g)b|(\])?\s\d+KB\s(yENC)?|"?\s\d+\sbytes?|[- ]?\d+(\.|,)?\d+\s(g|k|m)?B\s-?(\s?yenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s|yEnc \d+k$|{\d+ yEnc bytes}|yEnc \d+ |\(\d+ ?(k|m|g)?b(ytes)?\) yEnc/i', ' ', $cleansubject);
			// Random stuff.
			$cleansubject = preg_replace('/AutoRarPar\d{1,5}|\(\d+\)( |  )yEnc|\d+(Amateur|Classic)| \d{4,}[a-z]{4,} |part\d+/i', ' ', $cleansubject);
			// Multi spaces.
			return utf8_encode(trim(preg_replace('/\s\s+/i', ' ', $cleansubject)));

		}
		// Music groups.
		else
		{
			// Try some music group regexes.
			$musicsubject = $this->musicSubject($subject);
			if ($musicsubject !== false)
				return $musicsubject;
			else
			{
				// Parts/files
				$cleansubject = preg_replace('/((( \(\d\d\) -|(\d\d)? - \d\d\.|\d{4} \d\d -) | - \d\d-| \d\d\. [a-z]).+| \d\d of \d\d| \dof\d)\.mp3"?|(\(|\[|\s)\d{1,4}(\/|(\s|_)of(\s|_)|-)\d{1,4}(\)|\]|\s|$|:)|\(\d{1,3}\|\d{1,3}\)|-\d{1,3}-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s|\d{1,3} - of \d{1,3}/i', ' ', $subject);
				// Anything between the quotes. Too much variance within the quotes, so remove it completely.
				$cleansubject = preg_replace('/".+"/i', ' ', $cleansubject);
				// File extensions - If it was not in quotes.
				$cleansubject = preg_replace('/(-? [a-z0-9]+-?|\(?\d{4}\)?(_|-)[a-z0-9]+)\.jpg"?| [a-z0-9]+\.mu3"?|((\d{1,3})?\.part(\d{1,5})?|\d{1,5} ?|sample|- Partie \d+)?\.(7z|\d{3}(?=(\s|"))|avi|diz|docx?|epub|idx|iso|jpg|m3u|m4a|mds|mkv|mobi|mp4|nfo|nzb|par(\s?2|")|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|xls|zip|z{2,3})"?|(\s|(\d{2,3})?-)\d{2,3}\.mp3|\d{2,3}\.pdf|\.part\d{1,4}\./i', ' ', $cleansubject);
				// File Sizes - Non unique ones.
				$cleansubject = preg_replace('/\d{1,3}(,|\.|\/)\d{1,3}\s(k|m|g)b|(\])?\s\d+KB\s(yENC)?|"?\s\d+\sbytes?|[- ]?\d+[.,]?\d+\s(g|k|m)?B\s-?(\s?yenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s|yEnc \d+k$|{\d+ yEnc bytes}|yEnc \d+ |\(\d+ ?(k|m|g)?b(ytes)?\) yEnc/i', ' ', $cleansubject);
				// Random stuff.
				$cleansubject = preg_replace('/AutoRarPar\d{1,5}|\(\d+\)( |  )yEnc|\d+(Amateur|Classic)| \d{4,}[a-z]{4,} |part\d+/i', ' ', $cleansubject);
				// Multi spaces.
				$cleansubject = utf8_encode(trim(preg_replace('/\s\s+/i', ' ', $cleansubject)));

				// If the subject is too similar to another because it is so short, try to extract info from the subject.
				if (strlen($cleansubject) <= 10 || preg_match('/^[-a-z0-9$ ]{1,7}yEnc$/i', $cleansubject))
				{
					$x = '';
					if (preg_match('/.*("[A-Z0-9]+).*?"/i', $subject, $match))
						$x = $match[1];
					if (preg_match_all('/[^A-Z0-9]/i', $subject, $match1))
					{
						$start = 0;
						foreach ($match1[0] as $add)
						{
							if ($start > 2)
								break;
							$x .= $add;
							$start++;
						}
					}
					$newname = preg_replace('/".+?"/', '', $subject);
					$newname = preg_replace('/[a-z0-9]|'.$this->e0.'/i', '', $newname);
					return $cleansubject.$newname.$x;
				}
				else
					return $cleansubject;
			}
		}
	}

	// Regexes for music groups.
	public function musicSubject($subject)
	{
		//Broderick_Smith-Unknown_Country-2009-404 "00-broderick_smith-unknown_country-2009.sfv" yEnc
		if (preg_match('/^(\w{10,}-[a-zA-Z0-9]+ ")\d\d-.+?" yEnc$/', $subject, $match))
			return $match[1];
		else
			return false;
	}

	/*
		Cleans a usenet subject before inserting, used for searchname. Also used for imports.

		Example: Take the following subject:
		[134787]-[FULL]-[#a.b.moovee]-[ Trance.2013.DVDRiP.XViD-SML ]-[02/46] - "tranceb-xvid-sml.r00" yEnc

		Return: Trance.2013.DVDRiP.XViD-SML
	*/
	public function releaseCleaner($subject, $groupID)
	{
		$groups = new Groups();
		$groupName = $groups->getByNameByID($groupID);


		if ($groupName === "alt.binaries.0day.stuffz")
		{
			//ArcSoft.TotalMedia.Theatre.v5.0.1.87-Lz0 - [08/35] - "ArcSoft.TotalMedia.Theatre.v5.0.1.87-Lz0.vol43+09.par2" yEnc
			if (preg_match('/^([a-zA-Z0-9].+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//rld-tcavu1 [5/6] - "rld-tcavu1.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(DVD Shrink.ss) [1/1] - "DVD Shrink.ss.rar" yEnc
			else if (preg_match('/^\((.+?)\) \[\d+(\/\d+] - ").+?" yEnc$/', $subject, $match))
				return $match[1];
			//WinASO.Registry.Optimizer.4.8.0.0(1/4) - "WinASO_RO_v4.8.0.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?)\(\d+\/\d+\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.anime")
		{
			//([AST] One Piece Episode 301-350 [720p]) [007/340] - "One Piece episode 301-350.part006.rar" yEnc
			if (preg_match('/^\((\[.+?\] .+?)\) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[REPOST][ New Doraemon 2013.05.03 Episode 328 (TV Asahi) 1080i HDTV MPEG2 AAC-DoraClub.org ] [35/61] - "doraclub.org-doraemon-20130503-b8de1f8e.r32" yEnc
			else if (preg_match('/^\[.+?\]\[ (.+?) \] \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[De.us] Suzumiya Haruhi no Shoushitsu (1920x1080 h.264 Dual-Audio FLAC 10-bit) [017CB24D] [000/357] - "[De.us] Suzumiya Haruhi no Shoushitsu (1920x1080 h.264 Dual-Audio FLAC 10-bit) [017CB24D].nzb" yEnc
			else if (preg_match('/^\[.+?\] (.+?) \[[A-F0-9]+\] \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[eraser] Ghost in the Shell ARISE - border_1 Ghost Pain (BD 720p Hi444PP LC-AAC Stereo) - [01/65] - "[eraser] Ghost in the Shell ARISE - border_1 Ghost Pain (BD 720p Hi444PP LC-AAC Stereo) .md5" yEnc
			else if (preg_match('/^\[.+?\] (.+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(01/27) - Maid.Sama.Jap.dubbed.german.english.subbed - "01 Misaki ist eine Maid!.divx" - 6,44 GB - yEnc
			else if (preg_match('/^\(\d+\/\d+\) - (.+?) - ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//[ New Doraemon 2013.06.14 Episode 334 (TV Asahi) 1080i HDTV MPEG2 AAC-DoraClub.org ] [01/60] - "doraclub.org-doraemon-20130614-fae28cec.nfo" yEnc
			else if (preg_match('/^\[ (.+?) \] \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<TOWN> www.town.ag > sponsored by www.ssl-news.info > (1/3) "HolzWerken_40.par2" - 43,89 MB - yEnc
			else if (preg_match('/^<TOWN> www\.town\.ag > sponsored by www\.ssl-news\.info > \(\d+\/\d+\) "(.+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(1/9)<<<www.town.ag>>> sponsored by ssl-news.info<<<[HorribleSubs]_AIURA_-_01_[480p].mkv "[HorribleSubs]_AIURA_-_01_[480p].par2" yEnc
			else if (preg_match('/^\(\d+\/\d+\).+?www\.town\.ag.+?sponsored by (www\.)?ssl-news\.info<+?.+? "(.+?)'.$this->e1, $subject, $match))
				return $match[2];
			//Overman King Gainer [Dual audio, EngSub] Exiled Destiny - [002/149] - "Overman King Gainer.part001.rar" yEnc
			else if (preg_match('/^(.+? \[Dual [aA]udio, EngSub\] .+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.ath")
		{
			//[3/3 Karel Gott - Die Biene Maja Original MP3 Karel Gott - Die Biene Maja Original MP3.mp3.vol0+1.PAR2" yEnc
			if (preg_match('/^\[\d+\/\d+ ([a-zA-Z0-9]+ .+?)\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//8b33bf5960714efbe6cfcf13dd0f618f - (01/55) - "8b33bf5960714efbe6cfcf13dd0f618f.par2" yEnc
			else if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}\..+" yEnc$/', $subject, $match))
				return $match[1];
			//nmlsrgnb - [04/37] - "htwlngmrstdsntdnh.part03.rar" yEnc
			else if (preg_match('/^([a-z]+) - \[\d+\/\d+\] - "[a-z]+\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//>>>>>Hell-of-Usenet>>>>> - [01/33] - "Cassadaga Hier lebt der Teufel 2011 German AC3 DVDRip XViD iNTERNAL-VhV.par2" yEnc
			else if (preg_match('/^>+Hell-of-Usenet(\.org)?>+( -)? \[\d+\/\d+\] - "(.+?)'.$this->e0.'( - \d+[.,]\d+ [kKmMgG][bB])? yEnc$/', $subject, $match))
				return $match[3];
			//1dbo1u5ce6182436yb2eo (001/105) "1dbo1u5ce6182436yb2eo.par2" yEnc
			else if (preg_match('/^([a-z0-9]{10,}) \(\d+\/\d+\) "[a-z0-9]{10,}\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//<<<>>>kosova-shqip.eu<<< Deep SWG - 90s Club Megamix 2011 >>>kosova-shqip.eu<<<<<< - (2/4) - "Deep SWG - 90s Club Megamix 2011.rar" yEnc
			else if (preg_match('/^<<<>>>kosova-shqip\.eu<<< (.+?) >>>kosova-shqip.eu<<<<<< - \(\d+\/\d+\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<Have Fun> "Invader.German.2012.PAL.DVDR-MORTAL.nfo" SpongeBoZZ yEnc
			else if (preg_match('/^<Have Fun> "(.+?)'.$this->e0.' SpongeBoZZ yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//Old Dad uppt Taffe Mädels XivD LD HDTV Rip oben Kleine Einblendug German 01/43] - "Taffe Mädels.par2" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?\s{2,}|Old Dad uppt\s+)(.+?) \d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[2];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.audio.warez")
		{
			//[#nzbx.audio/EFnet]-[1681]-[MagicScore.Note.v7.084-UNION]-[02/12] - "u-msn7.r00" yEnc
			if (preg_match('/^(Re: )?\[.+?\]-\[\d+\]-\[(.+?)\]-\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[2];
			//MacProVideo.com.Pro.Tools8.101.Core.Pro.Tools8.TUTORiAL-DYNAMiCS [2 of 50] "dyn-mpvprtls101.sfv" yEnc
			//Native.Instruments.Komplete.7.VSTi.RTAS.AU.DVDR.D02-DYNAMiCS[01/13] - "dyn.par2" yEnc
			//Native.Instruments.Komplete.7.VSTi.RTAS.AU.DVDR.DYNAMiCS.NZB.ONLY [02/13] - "dyn.vol0000+001.PAR2" yEnc
			else if (preg_match('/^([\w.-]+) ?\[\d+( of |\/)\d+\] ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//REQ : VSL Stuff ~ Here's PreSonus Studio One 1.5.2 for OS X [16 of 22] "a-p152x.rar" yEnc
			else if (preg_match('/^REQ : .+? ~ (.+?) \[\d+ of \d+\] ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Eminem - Recovery (2010) - [1/1] - "Eminem - Recovery (2010).rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(????) [1/1] - "Dust in the Wind - the Violin Solo.rar" yEnc
			else if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Native Instruments Battery 3 incl Library ( VST DX RTA )( windows ) Libraries [1/1] - "Native Instruments Battery 2 + 3 SERIAL KEY KEYGEN.nfo" yEnc
			else if (preg_match('/^(.+?) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			/*TODO: REFRESH : Tonehammer Ambius 1 'Transmissions' ~ REQ: SAMPLE LOGIC SYNERGY [1 of 52] "dynamics.nfo" yEnc*/
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.b4e")
		{
			//"B4E-vip2851.r83" yEnc
			if (preg_match('/^"(B4E-vip\d+)\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//[02/12] - "The.Call.GERMAN.2013.DL.AC3.Dubbed.720p.BluRay.x264 (Avi-RiP ).rar" yEnc
			else if (preg_match('/^\[\d+\/\d+\] - "(.+?) \(.+?" yEnc$/', $subject, $match))
				return $match[1];
			//- "as-jew3.vol03+3.PAR2" - yEnc
			else if (preg_match('/^- "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.barbarella")
		{
			//ACDSee.Video.Converter.Pro.v3.5.41.Incl.Keymaker-CORE - [1/7] - "ACDSee.Video.Converter.Pro.v3.5.41.Incl.Keymaker-CORE.par2" yEnc
			if (preg_match('/^([a-zA-Z0-9].+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Die.Nacht.Der.Creeps.THEATRICAL.GERMAN.1986.720p.BluRay.x264-GH - "gh-notcreepskf720.nfo" yEnc
			//The.Fast.and.the.Furious.Tokyo.Drift.2006.German.1080p.BluRay.x264.iNTERNAL-MWS  - "mws-tfatftd-1080p.nfo" yEnc
			if (preg_match('/^([\w.-]+)\s+-\s+".+?" yEnc$/', $subject, $match))
				return $cleansubject["hash"] = $match[1];
			//CorelDRAW Technical Suite X6-16.3.0.1114 x32-x64<><>DRM<><> - (10/48)  "CorelDRAW Technical Suite X6-16.3.0.1114 x32-x64.part09.rar" - 2,01 GB - yEnc
			//AnyDVD_7.1.9.3_-_HD-BR - Beta<>give-me-all.org<>DRM<><> - (1/3)  "AnyDVD_7.1.9.3_-_HD-BR - Beta.par2" - 14,53 MB - yEnc
			//Android Softarchive.net Collection Pack 27^^give-me-all.org^^^^DRM^^^^ - (01/26)  "Android Softarchive.net Collection Pack 27.par2" - 1,01 GB - yEnc
			//WIN7_ULT_SP1_x86_x64_IE10_19_05_13_TRIBAL <> give-me-all.org <> DRM <> <> PW <> - (154/155)  "WIN7_ULT_SP1_x86_x64_IE10_19_05_13_TRIBAL.vol57+11.par2" - 7,03 GB - yEnc
			//[Android].Ultimate.iOS7.Apex.Nova.Theme.v1.45 <> DRM <> - (1/3)  "[Android].Ultimate.iOS7.Apex.Nova.Theme.v1.45.par2" - 21,14 MB - yEnc
			else if (preg_match('/^(\[[A-Za-z]+\]\.|reup\.)?([a-zA-Z0-9].+?)([\^<> ]+give-me-all\.org[\^<> ]+|[\^<> ]+)DRM[\^<> ]+.+? - \(\d+\/\d+\)  ".+?" - .+? yEnc$/', $subject, $match))
				return $match[2];
			//(004/114) - Description - "Pluralsight.net XAML Patterns (10).rar" - 532,92 MB - yEnc
			else if (preg_match('/^\(\d+\/\d+\) - .+? - "(.+?)( \(\d+\))?'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(01/12) - "TransX - Living on a Video 1993.part01.rar" - 561,55 MB - TransX - Living on a Video 1993.[Lossless] Highh Quality yEnc
			//(59/81) "1973 .Lee.Jun.Fan.DVD9.untouched.z46" - 7,29 GB - Lee.Jun.Fan.sein.Film.DVD9.untouched yEnc
			else if (preg_match('/^\(\d+\/\d+\)( -)? ".+?" - \d+[,.]\d+ [mMkKgG][bB] - (.+?) yEnc$/', $subject, $match))
				return $match[2];
			//>>> www.lords-of-usenet.org <<<  "Der Schuh Des Manitu.par2" DVD5  [001/158] - 4,29 GB yEnc
			else if (preg_match('/^>>> www\.lords-of-usenet\.org <<<.+? "(.+?)'.$this->e0.' .+? \[\d+\/\d+\] - .+? yEnc$/', $subject, $match))
				return $match[1];
			//NEUES 4y - [@ usenet-4all.info - powered by ssl.news -] [5,58 GB] [002/120] "DovakinPack.part002.rar" yEnc
			//NEUES 4y (PW)  [@ usenet-4all.info - powered by ssl.news -] [7,05 GB] [014/152] "EngelsGleich.part014.rar" yEnc
			else if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+\/\d+\] "(.+?)'.$this->e1, $subject, $match))
				return $match[2];
			//Old Dad uppt   Die Schatzinsel Teil 1+Teil2  AC3 DVD Rip German XviD Wp 01/33] - "upp11.par2" yEnc
			//Old Dad uppt Scary Movie5 WEB RiP Line XviD German 01/24] - "Scary Movie 5.par2" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?\s{2,}|Old Dad uppt\s+)(.+?) \d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[2];
			//>>>  20,36 MB   "Winamp.Pro.v5.70.3392.Incl.Keygen-FFF.par2"   552 B yEnc
			//..:[DoAsYouLike]:..    9,64 MB    "Snooper 1.39.5.par2"    468 B yEnc
			else if (preg_match('/^.+?\s{2,}\d+[,.]\d+ [mMkKgG][bB]\s{2,}"(.+?)'.$this->e0.'\s{2,}(\d+ B|\d+[,.]\d+ [mMkKgG][bB]) yEnc$/', $subject, $match))
				return$match[1];
			//(MKV - DVD - Rip - German - English - Italiano) - "CALIGULA (1982) UNCUT.sfv" yEnc
			else if (preg_match('/^\(.+?\) - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//"sre56565ztrtzuzi8inzufft.par2" yEnc
			else if (preg_match('/^"([a-z0-9]+)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.big")
		{
			//Girls.against.Boys.2012.German.720p.BluRay.x264-ENCOUNTERS - "encounters-giagbo_720p.nfo" yEnc
			if (preg_match('/^([\w.-]+) - ".+?" yEnc$/', $subject, $match))
				return$match[1];
			//wtvrwschdhfthj - [001/246] - "dtstchhtmrrnvn.par2" yEnc
			//oijhuiurfjvbklk - [01/18] - "tb5-3ioewr90f.par2" yEnc
			else if (preg_match('/^([a-z]{3,}) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(08/22) - "538D7B021B362A4300D1C0D84DD17E6D.r06" yEnc
			else if (preg_match('/^\(\d+\/\d+\) - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//(????) [02/71] - "Lasting Weep (1969-1971).part.par2" yEnc
			else if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//(01/59) "ThienSuChungQuy_II_E16.avi.001" - 1,49 GB - yEnc
			//(058/183) "LS_HoangChui_2xdvd5.part057.rar" - 8,36 GB -re yEnc
			else if (preg_match('/^\(\d+\/\d+\) "(.+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] -(re)? yEnc$/', $subject, $match))
				return $match[1];
			//[AoU] Upload#00287 - [04/43] - "Upload-ZGT1-20130525.part03.rar" yEnc
			else if (preg_match('/^(\[[a-zA-Z]+\] .+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return$match[1];
			//(nate) [01/27] - "nate_light_13.05.23.par2" yEnc
			else if (preg_match('/^\([a-z]+\) \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//""Absolute Database Component for BCBuilder 4-6 MultiUser Edit 4.85.rar"" yEnc
			else if (preg_match('/^""(.+?)'.$this->e0.'" yEnc$/', $subject, $match))
				return $match[1];
			//781e1d8dccc641e8df6530edb7679a0e - (26/30) - "781e1d8dccc641e8df6530edb7679a0e.rar" yEnc
			else if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}.+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.bloaf")
		{
			//36c1d5d4eaf558126c67f00be46f77b6 - (01/22) - "36c1d5d4eaf558126c67f00be46f77b6.par2" yEnc
			if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}.+?" yEnc$/', $subject, $match))
				return $match[1];
			//[10/17] - "EGk13kQ1c8.part09.rar" - 372.48 MB <-> usenet-space-cowboys.info <-> powered by secretusenet.com <-> yEnc
			else if (preg_match('/^\[\d+\/\d+\] - "(.+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] .+? usenet-space.+?yEnc$/', $subject, $match))
				return $match[1];
			//(Neu bei Bitfighter vom 23-07-2013) - "01 - Sido - Bilder Im Kopf.mp3" yEnc
			else if (preg_match('/^\((.+?)\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(2/8) "Mike.und.Molly.S01E22.Maennergespraeche.GERMAN.DL.DUBBED.720p.BluRay.x264-TVP.part1.rar" - 1023,92 MB - yEnc
			else if (preg_match('/^\(\d+\/\d+\) "(.+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//4y (PW)   [@ usenet-4all.info - powered by ssl.news -] [27,35 GB] [001/118] "1f8867bb6f89491793d3.part001.rar" yEnc
			else if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+\/\d+\] "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Bennos Special Tools DVD - Die Letzte <> DRM <><> PW <> - (002/183)  "Bennos Special Tools DVD - Die Letzte.nfo" - 8,28 GB - yEnc
			else if (preg_match('/^(\[[A-Za-z]+\]\.)?([a-zA-Z0-9].+?)([\^<> ]+give-me-all\.org[\^<> ]+|[\^<> ]+)DRM[\^<> ]+.+? - \(\d+\/\d+\)\s+".+?" - .+? yEnc$/', $subject, $match))
				return $match[2];
			//(1/9) - CyberLink.PhotoDirector.4.Ultra.4.0.3306.Multilingual - "CyberLink.PhotoDirector.4.Ultra.4.0.3306.Multilingual.par2" - 154,07 MB - yEnc
			//(1/5) - Mac.DVDRipper.Pro.4.0.8.Mac.OS.X- "Mac.DVDRipper.Pro.4.0.8.Mac.OS.X.rar" - 24,12 MB - yEnc
			else if (preg_match('/^\(\d+\/\d+\) - (.+?) ?- ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//[3/3 Helene Fischer - Die Biene Maja 2013 MP3 Helene Fischer - Die Biene Maja 2013 MP3.mp3.vol0+1.PAR2" yEnc
			else if (preg_match('/^\[\d+\/\d+ (.+?)\..+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.blu-ray")
		{
			//"786936833607.MK.A.part086.rar" yEnc
			if (preg_match('/^"(\d+\.MK\.[A-Z])\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//(????) [001/107] - "260713thbldnstnsclw.par2" yEnc
			else if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "([a-z0-9]+)\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//[www.allyourbasearebelongtous.pw]-[The Place Beyond the Pines 2012 1080p US Blu-ray AVC DTS-HD MA 5.1-HDWinG]-[03/97] "tt1817273-us-hdwing-bd.r00" - 46.51 GB - yEnc
			else if (preg_match('/^\[www\..+?\]-\[(.+?)\]-\[\d+\/\d+\] ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(01/71)  - "EwRQCtU4BnaeXmT48hbg7bCn.par2" - 54,15 GB - yEnc
			//(63/63) "dfbgfdgtghtghthgGPGEIBPBrwg34t05.rev" - 10.67 GB - yEnc
			else if (preg_match('/^\(\d+\/\d+\)(\s+ -)? "([a-zA-Z0-9]+?)\d*\..+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[2];
			//[01/67] - "O3tk4u681gd767Y.par2" yEnc
			else if (preg_match('/^\[\d+\/\d+\] - "([a-zA-Z0-9]+)\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//209a212675ba31ca24a8 [usenet-4all.info] [powered by ssl-news] [21,59 GB] [002/223] "209a212675ba31ca24a8.part001.rar" yEnc
			else if (preg_match('/^([a-z0-9]+) \[.+?\] \[.+?\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+\/\d+\] ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//TIS97CC - "tis97cc.par2" yEnc
			else if (preg_match('/^([A-Z0-9]+) - "[a-z0-9]+\..+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.boneless")
		{
			//4Etmo7uBeuTW[047/106] - "006dEbPcea29U6K.part046.rar" yEnc
			if (preg_match('/^([a-zA-Z0-9]+)\[\d+\/\d+\] - "[a-zA-Z0-9]+\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//(68/89) "dz1R2wT8hH1iQEA28gRvm.part67.rar" - 7,91 GB - yEnc
			//(01/14)  - "JrjCY4pUjQ9qUqQ7jx6k2VLF.par2" - 4,39 GB - yEnc
			else if (preg_match('/^\(\d+\/\d+\)\s+(- )?"([a-zA-Z0-9]+)\..+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[2];
			//(110320152518519) [22/78] - "110320152518519.part21.rar" yEnc
			else if (preg_match('/^\((\d+)\) \[\d+\/\d+\] - "\d+\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//1VSXrAZPD - [123/177] - "1VSXrAZPD.part122.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9]+) - \[\d+\/\d+\] - "[a-zA-Z0-9]+\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//( Peter Gabriel Albums 24x +17 Singles = 71x cd By Dready Niek )  ( ** By Dready Niek ** ) [113/178] - "Peter Gabriel Albums 24x +17 Singles = 71CDs By Dready Niek (1977-2010).part112.rar" yEnc
			else if (preg_match('/^\( (.+?) \)\s+\( .+?\) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Tarja - Colours In The Dark (2013) "00. Tarja-Colours In The Dark.m3u" yEnc
			else if (preg_match('/^([A-Za-z0-9].+? \((19|20)\d\d\)) "\d{2}\. .+?'.$this->e1, $subject, $match))
				return $match[1];
			//"BB636.part14.rar" - (15/39) - yEnc
			else if (preg_match('/^"([a-zA-Z0-9]+)'.$this->e0.' - \(\d+\/\d+\) - yEnc$/', $subject, $match))
				return $match[1];
			//Lutheria - FC Twente TV Special - Ze wilde op voetbal [16/49] - "Lutheria - FC Twente TV Special - Ze wilde op voetbal.part16.rar" yEnc
			else if (preg_match('/^([-a-zA-Z0-9 ]+) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Pee Mak Prakanong - 2013 - Thailand - ENG Subs - "Pee Mak Prakanong.2013.part22.rar" yEnc
			else if (preg_match('/^([-a-zA-Z0-9 ]+) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(????) [011/161] - "flynns-image-redux.part010.rar" yEnc
			//(Dgpc) [000/110] - "Teen Wolf - Seizoen.3 - Dvd.2 (NLsub).nzb" yEnc
			else if (preg_match('/^\((\?{4}|[a-zA-Z]+)\) \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[2];
			//("Massaladvd5Kilusadisc4S1.par2" - 4,55 GB -) "Massaladvd5Kilusadisc4S1.par2" - 4,55 GB - yEnc
			else if (preg_match('/^\("([a-z0-9A-Z]+).+?" - \d+[,.]\d+ [mMkKgG][bB] -\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//"par.4kW9beE.1.vol122+21.par2" yEnc
			else if (preg_match('/^"(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [01/19] - "Age.of.Dinosaurs.German.AC3.HDRip.x264-FuN.par2" yEnc
			//>>>>>Hell-of-Usenet.org>>>>> - [01/35] - "Female.Agents.German.2008.AC3.DVDRip.XviD.iNTERNAL-VideoStar.par2" yEnc
			else if (preg_match('/^.+?\.(info|org)>+ - \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[2];
			//[010/101] - "Bf56a8aR-20743f8D-Vf7a11fD-d7c6c0.part09.rar" yEnc
			//[1/9] - "fdbvgdfbdfb.part.par2" yEnc
			else if (preg_match('/^\[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//[LB] - [063/112] - "RVL-GISSFBD.part063.rar" yEnc
			else if (preg_match('/^\[[A-Z]+\] - \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.british.drama")
		{
			//Coronation Street 03.05.2012 [XviD] [01/23] - "coronation.street.03.05.12.[ws.pdtv].par2" yEnc
			//Coronation Street 04.05.2012 - Part 1 [XviD] [01/23] - "coronation.street.04.05.12.part.1.[ws.pdtv].par2" yEnc
			if (preg_match('/^([a-zA-Z0-9].+? \[XviD\]) \[\d\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//The Prisoner E06-09 [001/152] - "06 The General.mkv.001" yEnc
			//Danger Man S2E05-08 [075/149] - "7.The colonel's daughter.avi.001" yEnc
			else if (preg_match('/^([a-zA-Z0-9]+ .+? (S\d+)?E\d+-\d\d) \[\d+\/\d+\] - "\d(\d |\.).+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.cats")
		{
			//Pb7cvL3YiiOu06dsYPzEfpSvvTul[02/37] - "Fkq33mlTVyHHJLm0gJNU.par2" yEnc
			//DLJorQ37rMDvc [01/16] - "DLJorQ37rMDvc.part1.rar" yEnc
			if (preg_match('/^([a-zA-Z0-9]{5,}) ?\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.cd.image")
		{
			//[27930]-[FULL]-[altbinEFNet]-[ Ubersoldier.UNCUT.PATCH-RELOADED ]-[3/5] "rld-usuc.par2" yEnc
			//[27607]-[#altbin@EFNet]-[Full]-[ Cars.Radiator.Springs.Adventure.READNFO-CRIME ] - [02/49] - "crm-crsa.par2" yEnc
			//[27774]-[FULL]-[altbinEFNet]-[ DVD4 ]-[01/61] "unl-totwar.sfv" yEnc
			if (preg_match('/^\[\d+\]-\[.+?\]-\[.+?\]-\[ (.+?) \] ?- ?\[\d+\/\d+\] (- )?"(.+?)'.$this->e1, $subject, $match))
			{
				if (strlen($match[1]) > 7)
					return $match[1];
				else
					return $match[3];
			}
			//[www.drlecter.tk]-[The_Night_of_the_Rabbit-FLT]-[01/67] "Dr.Lecter.nfo" - 5.61 GB - yEnc
			else if (preg_match('/^\[www\..+?\]-\[(.+?)\]-\[\d+\/\d+\] ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//Slender.The.Arrival-WaLMaRT.PC - [01/26] - "wmt-stal.nfo" - yEnc
			//The.Night.of.the.Rabbit-FLT - [03/66] - "flt-notr.r00" - FAiRLIGHT - 5,10 GB - yEnc
			//Resident.Evil.Revelations-FLT - PC GAME - [03/97] - "Resident.Evil.Revelations-FLT.r00" - FAiRLIGHT - yEnc
			//Afterfall.Insanity.Dirty.Arena.Edition-WaLMaRT - [MULTI6][PCDVD] - [02/45] - "wmt-adae.r00" - PC GAME - yEnc
			else if (preg_match('/^([a-zA-Z0-9.-]{10,}) -( PC GAME -| [A-Z0-9\[\]]+ -)? \[\d+\/\d+\] - ".+?" - (.+? - (\d+[,.]\d+ [mMkKgG][bB] - )?)?yEnc$/', $subject, $match))
				return $match[1];
			//[01/46] - Crashtime 5 Undercover RELOADED - "rld-ct5u.nfo" - PC - yEnc
			//[01/76] - Of.Orcs.And.Men-SKIDROW - "sr-oforcsandmen.nfo" - yEnc
			//PC Game - [01/71] - MotoGP 13-RELOADED Including NoDVD Fix - "MotoGP 13-RELOADED Including NoDVD Fix nfo" - yEnc
			else if (preg_match('/^(PC Game - )?\[\d+\/\d+\] - (.+?) - ".+?" -( .+? -)? yEnc$/', $subject, $match))
				return $match[2];
			//Magrunner Dark Pulse FLT (FAiRLIGHT) - [02/70] - "flt-madp par2" - PC - yEnc
			//MotoGP 13 RELOADED - [01/71] - "rld-motogp13 nfo" - PC - yEnc
			//Dracula 4: Shadow of the Dragon FAiRLIGHT - [01/36] - "flt-drc4 nfo" - PC - yEnc
			else if (preg_match('/^([A-Za-z0-9][a-zA-Z0-9: ]{8,}(-[a-zA-Z]+)?)( \(.+?\)| - [\[A-Z0-9\]]+)? - \[\d+\/\d+\] - ".+?" - .+? - yEnc$/', $subject, $match))
				return $match[1];
			//[NEW PC GAME] - Lumber.island-WaLMaRT - "wmt-lisd.nfo" - [01/18] - yEnc
			//Trine.2.Complete.Story-SKIDROW - "sr-trine2completestory.nfo" - [01/78] - yEnc
			else if (preg_match('/^(\[[A-Z ]+\] - )?([a-zA-Z0-9.-]{10,}) - ".+?" - \[\d+\/\d+\] - yEnc$/', $subject, $match))
				return $match[2];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.cd.lossless")
		{
			//Flac Flood - Modern Talking - China In Her Eyes (CDM) - "1 - Modern Talking - China In Her Eyes (feat. Eric Singleton) (Video Version).flac" (01/14) (23,64 MB)   136,66 MB yEnc
			////Flac Flood Modern Talking - America - "1 - Modern Talking - Win The Race.flac" (01/18) (29,12 MB) 549,35 MB yEnc
			if (preg_match('/^Flac Flood( -)? (.+?) - ".+?" \(\d+\/\d+\) .+? yEnc$/', $subject, $match))
				return $match[2];
			//Cannonball Adderley - Nippon Soul [01/17] "00 - Cannonball Adderley - Nippon Soul.nfo" yEnc
			//Black Tie White Noise [01/24] - "00 - David Bowie - Black Tie White Noise.nfo" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?) \[\d+\/\d+\]( -)? "\d{2,} - .+?" yEnc$/', $subject, $match))
				return $match[1];
			//The Allman Brothers Band - Statesboro Blues [Swingin' Pig - Bootleg] [1970 April 4] - File 09 of 19: Statesboro Blues.cue yEnc
			//[1977] Joan Armatrading - Show Some Emotion - File 15 of 20: 06 Joan Armatrading - Opportunity.flac yEnc
			else if (preg_match('/^((\[\d{4}\] )?[a-zA-Z0-9].+?) - File \d+ of \d+: .+? yEnc$/', $subject, $match))
				return $match[1];
			//The Allman Brothers Band - The Fillmore Concerts [1971] - 06 The Allman Brothers Band - Done Somebody Wrong.flac yEnc
			else if (preg_match('/^([A-Z0-9].+? - [A-Z0-9].+? \[\d{4}\]) - \d{2,} .+? yEnc$/', $subject, $match))
				return $match[1];
			//The Velvet Underground - Peel Slow And See (Box Set) Disc 5 of 5 - 13 The Velvet Underground - Oh Gin.flac yEnc
			else if (preg_match('/^([A-Z0-9].+? - [A-Z0-9].+? Disc \d+ of \d+) - [A-Z0-9].+?\..+? yEnc$/', $subject, $match))
				return $match[1];
			//(28/55) "Ivan Neville - If My Ancestors Could See Me Now.par2" - 624,44 MB - yEnc
			else if (preg_match('/^\(\d+\/\d+\) "(.+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.chello")
		{
			//0F623Uv71RHKt0jzA7inbGZLk00[2/5] - "l2iOkRvy80bgLrZm1xxw.par2" yEnc
			//GMC2G8KixJKy [01/15] - "GMC2G8KixJKy.part1.rar" yEnc
			if (preg_match('/^([A-Za-z0-9]{5,}) ?\[\d+\/\d+\] - "[A-Za-z0-9]{3,}.+?" yEnc$/', $subject, $match))
				return $match[1];
			//Imactools.Cefipx.v3.20.MacOSX.Incl.Keyfilemaker-NOY [03/10] - "parfile.vol000+01.par2" yEnc
			else if (preg_match('/^([a-zA-Z0-9][a-zA-Z0-9.-]+) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Siberian Mouses LS, BD models and special... [150/152] - "Xlola - Luba, Sasha & Vika.avi.jpg" yEnc
			else if (preg_match('/^([A-Za-z0-9-]+ .+?)[. ]\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.classic.tv.shows")
		{
			//Re: REQ: All In The Family - "Archie Bunkers Place 1x01 Archies New Partner part 1.nzb" yEnc
			if (preg_match('/^Re: REQ: (.+? - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Per REQ - "The.Wild.Wild.West.S03E11.The.Night.of.the.Cut-Throats.DVDRip.XVID-tz.par2" 512x384 [01/40] yEnc
			else if (preg_match('/^Per REQ - "(.+?)'.$this->e0.' .+? \[\d+\/\d+\] yEnc$/', $subject, $match))
				return $match[1];
			//By req: "Dennis The Menace - 4x25 - Dennis and the Homing Pigeons.part05.rar" yEnc
			else if (preg_match('/^By req: "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//I Spy HQ DVDRips "I Spy - 3x26 Pinwheel.part10.rar" [13/22] yEnc
			else if (preg_match('/^[a-zA-Z ]+HQ DVDRips "(.+?)'.$this->e0.' \[\d+\/\d+\] yEnc$/', $subject, $match))
				return $match[1];
			//Sledge Hammer! S2D2 [016/138] - "SH! S2 D2.ISO.016" yEnc
			//Sledge Hammer! S2D2 [113/138] - "SH! S2 D2.ISO.1132 yEnc
			//Lost In Space - Season 1 - [13/40] - "S1E02 - The Derelict.avi" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+? (S\d+D\d+|- Season \d+))( -)? \[\d+\/\d+\] - ".+?"? yEnc$/', $subject, $match))
				return $match[1];
			//Night Flight TV Show rec 1991-01-12 (02/54) - "night flight rec 1991-01-12.nfo" yEnc
			//Night Flight TV Show rec 1991-05-05 [NEW PAR SET] (1/9) - "night flight rec 1991-05-05.par2" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+? \d{4}-\d\d-\d\d)( \[.+?\])? \(\d+\/\d+\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//The.Love.Boat.S05E08 [01/31] - "The.Love.Boat.S05E08.Chefs.Special.Kleinschmidt.New.Beginnings.par2" yEnc
			//Barney.Miller.S08E05.Stress.Analyzer [01/18] - "Barney.Miller.S08E05.Stress.Analyzer.VHSTVRip.DivX.par2" yEnc
			else if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9.-]+S\d+E\d+([a-zA-Z0-9.]+)? \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[2];
			//"Batman - S1E13-The Thirteenth Hat.par2" yEnc
			//"The Munsters - 1x01 Munster Masquerade.part.par" HQ DVDRip[02/16] yEnc
			else if (preg_match('/^(Re: )?"(.+?)(\.avi|\.mkv)?'.$this->e0.'( HQ DVDRip\[\d+\/\d+\])? yEnc$/', $subject, $match))
				return $match[2];
			//Re: Outside Edge series 1 - [01/20] - "Outside Edge S01.nfo" yEnc
			//Green Acres Season 1 [01/87] - "Green Acres Season 1.par2" yEnc
			//MASH Season 1 - [01/54] - "MASH - Season 01.par2" yEnc
			else if (preg_match('/^(Re: )?[a-zA-Z0-9]+.+? (series|Season) \d+ (- )?\[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[4];
			//Rich.Little.Show - 1x12 - Season.and.Series.Finale - [02/33] - "Rich Little Show - 1x12 - Bill Bixby.avi.002" yEnc
			//Rich.Little.Show - 1x11 - [01/33] - "Rich Little Show - 1x11 - Jessica Walter.avi.001" yEnc
			//REQ - Banacek - 2x07 - [02/61] - "Banacek - 2x07 - Fly Me - If You Can Find Me.avi.002" yEnc
			else if (preg_match('/^(REQ - )?[A-Z0-9a-z][A-Z0-9a-z.]+ - \d+x\d+ (- [A-Z0-9a-z.]+ )?- \[\d+\/\d+\] - "(.+?)(\.avi|\.mkv)?'.$this->e1, $subject, $match))
				return $match[3];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.comics.dcp")
		{
			// Return anything between the quotes.
			if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $subject, $match))
			{
				if (strlen($match[1]) > 7 && !preg_match('/\.vol.+/', $match[1]))
					return $match[1];
				else
					return $this->releaseCleanerHelper($subject);
			}
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.comp")
		{
			//(45/74) NikJosuf post Magento tutorials "43 - Theming Magento 19 - Adding a Responsive Slideshow.mp4" yEnc
			if (preg_match('/^\(\d+\/\d+\) .+? post (.+?) ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Photo Mechanic 5.0 build 13915 (1/6) "Photo Mechanic 5.0 build 13915 (1).par2" - 32,97 MB - yEnc
			else if (preg_match('/^(.{5,}?) \(\d+\/\d+\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(Advanced SystemCare Pro 6.3.0.269 Final ML Incl Serial) [01/10] - "Advanced SystemCare Pro 6.3.0.269 Final ML Incl Serial.nfo" yEnc
			else if (preg_match('/^\(([a-zA-Z0-9. ]{10,}?)\) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[01/21 Geroellheimer - S01E03 - Swimming Pool Geroellheimer - S01E03 - Swimming Pool.mp4.001" yEnc
			else if (preg_match('/^\[\d+\/\d+ (.+?)(\.(part\d*|rar|avi|iso|mp4|mkv|mpg))?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return implode(' ', array_intersect_key(explode(' ', $match[1]), array_unique(array_map('strtolower', explode(' ', $match[1])))));
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.console.ps3")
		{
			//Railfan_JPN_JB_PS3-Caravan [02/88] - "cvn-railfjb.par2" yEnc
			//Madagascar.Kartz.German.JB.PS3-ATAX [01/40] - "atax-mkgjp.nfo"
			//Saints_Row_The_Third_The_Full_Package_EUR-PS3-SPLiT [61/87] - "split-sr3fullpps3.r58" yEnc
			if (preg_match('/^([\w.]+?-?PS3-[a-zA-Z0-9]+) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(4168) [00/24] - "Legend.Of.The.Guardians.Owls.GaHoole.USA.JB.PS3-PSFR33.nzb" yEnc
			else if (preg_match('/^\(\d+\) \[\d+\/\d+\] - "([\w.]{10,}?PS3-[A-Za-z0-9]+?)\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//[4230]-[ABGX.net]-[ Air_Conflicts_Pacific_Carriers_USA_PS3-CLANDESTiNE ] (01/54) "clan-aircpc.nfo" yEnc
			else if (preg_match('/^\[\d+\]-\[.+?\]-\[ (.+?) \] \(\d+\/\d+\) ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.cores")
		{
			//Film - [13/59] - "Jerry Maguire (1996) 1080p DTS multisub HUN HighCode-PHD.part13.rar" yEnc
			//Film - "Phone.booth.2003.RERIP.Bluray.1080p.DTS-HD.x264-Grym.part001.rar" yEnc
			if (preg_match('/^Film - (\[\d+\/\d+\] - )?"(.+?)'.$this->e1, $subject, $match))
				return $match[2];
			//>GOU<< ZDF.History.Das.Geiseldrama.von.Gladbeck.GERMAN.DOKU.720p.HDTV.x264-TVP >>www.SSL-News.info< - (02/35) - "tvp-gladbeck-720p.nfo" yEnc
			else if (preg_match('/^>+GOU<+ (.+?) >+www\..+?<+ - \(\d+\/\d+\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Jipejans post voor u op www.Dreamplace.biz - [010/568] - "Alien-Antology-DC-Special-Edition-1979-1997-1080p-GER-HUN-HighCode.part009.rar" yEnc
			//Egbert47 post voor u op www.nzbworld.me - [01/21] - "100 Hits - Lady Sings The Blues 2006 (5cd's).par2" yEnc
			else if (preg_match('/^[a-zA-Z0-9]+ post voor u op www\..+? - \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//>>> usenet4ever.info <<<+>>> secretusenet.com <<< "Weltnaturerbe USA Grand Canyon Nationalpark 2012 3D Blu-ray untouched  - DarKneSS.part039.rar" - DarKneSS yEnc
			else if (preg_match('/^>+ .+?\.info [<>+]+ .+?\.com <+ "(.+?)\s+- .*?'.$this->e0.' - .+? yEnc$/', $subject, $match))
				return $match[1];
			//Old Dad uppt   Der gro�e Gatsby   BD Rip AC3 Line XvidD German  01/57] - "Der gro�e Gatsby.par2" yEnc
			else if (preg_match('/^Old\s+Dad\s+uppt?\s*?(.+?)( mp4| )?\[?\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return preg_replace('/\s\s+/', ' ', $match[1]);
			//panter - [46/60] - "68645-Busty Beauties Car Wash XXX 3D BD26.part45.rar" yEnc
			//Wildrose - [01/57] - "49567-Kleine Rode Tractor Buitenpret.par2" yEnc
			else if (preg_match('/^[A-Za-z]+ - \[\d+\/\d+\] - "\d+-(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.dc")
		{
			//brothers-of-usenet.info&net-empfehlen-ssl-news.info (02/51) "Paul.Panzer.-.Hart.Backbord.2012.German.PAL.DVDR-icq4711.part01.rar" - 4,33 GB yEnc
			if (preg_match('/^brothers-of-usenet.+? \(\d+\/\d+\) "(.+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] yEnc$/', $subject, $match))
				return $match[1];
			//"The.Crow.1994.German.DL.PAL.HD2DVD.DVDR-Braunbaer.par2" yEnc
			else if (preg_match('/^"([\w.]{10,}-[a-zA-Z0-9]+)'.$this->e1, $subject, $match))
				return $match[1];
			//Eragon postet  The Secret of Crickley Hall  S01E02  german Sub hardcodet      [02/28] - "the_secret_of_crickley_hall.1x02.hdtv_x264-fov_arc.par2" yEnc
			else if (preg_match('/^[A-Z0-9].+? postet\s+.+?\s+\[\d+\/\d+\] - "([\w.-]{10,}?)'.$this->e1, $subject, $match))
				return $match[1];
			//Eragon postet Hart of Dixie S02E13 german Sub hardcodet. [02/21] - "hart of dixie S02E13.par2" yEnc
			else if (preg_match('/^[A-Z0-9].+? postet\s+(.+?)\.?\s+\[\d+\/\d+\] - ".+?'.$this->e1, $subject, $match))
				return $match[1];
			//>GOU<< - "Internet Download Manager 6.15 Build 1.rar" yEnc
			else if (preg_match('/^>GOU<< - "(.+?)\.rar" yEnc$/', $subject, $match))
				return $match[1];
			//Die.wahren.Faelle.des.NCIS.S01E06.Mord.ohne.Skrupel.GERMAN.DOKU.WS.BDRip.XviD-MiSFiTS - "misfits-therealnciss01e06-xvid.par2" yEnc
			else if (preg_match('/^([\w.]{8,}-[a-zA-Z0-9]+) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Double.Team.1997.German.FSK18.AC3.DVDRiP.XViD"team-xvid.oppo.par2" yEnc
			else if (preg_match('/^([\w.]{10,})".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.documentaries")
		{
			//#sterntuary - Alex Jones Radio Show - "05-03-2009_INFO_BAK_ALJ.nfo" yEnc
			if (preg_match('/^#sterntuary - (.+? - ".+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.dvd")
		{
			//thnx to original poster [00/98] - "2669DFKKFD2008.nzb ` 2669DFKKFD2008 " yEnc
			if (preg_match('/^thnx to original poster \[\d+(\/\d+\] - ".+?)(\.part\d*|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4})("| `).+? yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.dvd-r")
		{
			//katanxya "katanxya7221.par2" yEnc
			if (preg_match('/^katanxya "(katanxya\d+)/', $subject, $match))
				return $match[1];
			//[01/52] - "H1F3E_20130715_005.par2" - 4.59 GB yEnc
			else if (preg_match('/^\[\d+\/\d+\] - "([A-Z0-9](19|20)\d\d[01]\d[123]\d_\d+\.).+?" - \d+[,.]\d+ [mMkKgG][bB] yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.erotica")
		{
			//[278997]-[FULL]-[#a.b.erotica]-[ chi-the.walking.dead.xxx ]-[06/51] - "chi-the.walking.dead.xxx-s.mp4" yEnc
			//[######]-[FULL]-[#a.b.teevee@EFNet]-[ Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD ] [1/5] - "Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD.nfo" yEnc
			if (preg_match('/\[[\d#]+\]-\[.+?\]-\[.+?\]-\[ (.+?) \][- ]\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<TOWN><www.town.ag > <download all our files with>>> www.ssl-news.info <<< > [05/87] - "Deep.Black.Ass.5.XXX.1080p.WEBRip.x264-TBP.part03.rar" - 7,87 GB yEnc
			//<TOWN><www.town.ag > <partner of www.ssl-news.info > [02/24] - "Dragons.Den.UK.S11E02.HDTV.x264-ANGELiC.nfo" - 288,96 MB yEnc
			//<TOWN><www.town.ag > <SSL - News.Info> [6/6] - "TTT.Magazine.2013.08.vol0+1.par2" - 33,47 MB yEnc
			else if (preg_match('/^<TOWN>.+?town\.ag.+?(www\..+?|News)\.[iI]nfo.+? \[\d+\/\d+\]( -)? "(.+?)(-sample)?'.$this->e0.' - \d+[.,]\d+ [kKmMgG][bB]M? yEnc$/', $subject, $match))
				return $match[3];
			//NihilCumsteR [1/8] - "Conysgirls.cumpilation.xxx.NihilCumsteR.par2" yEnc
			else if (preg_match('/^NihilCumsteR \[\d+\/\d+\] - "(.+?)NihilCumsteR\./', $subject, $match))
				return $match[1];
			//"Lesbian seductions 26.part.nzb" yEnc
			else if (preg_match('/^"(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//>>>>>Hell-of-Usenet.org>>>>> - [01/23] - "Cum Hunters 3 XXX.par2" yEnc
			else if (preg_match('/^[><]+Hell-of-Usenet\.org[<>]+ - \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Lesbian Crush Diaries 5 XXX DVDRip x264-Pr0nStarS - (01/26) "Lesbian.Crush.Diaries.5.XXX.DVDRip.x264-Pr0nStarS.nfo" - yenc
			else if (preg_match('/^[A-Z0-9][a-zA-Z0-9 -]{6,}? - \(\d+\/\d+\) "(.+?)'.$this->e0.' - yenc yEnc$/', $subject, $match))
				return $match[1];
			//Megan Coxxx Takes Out Her Favourite Strap On Dildos And Plays With Her Girlfriend Re - File 01 of 67 - "Toy_Stories.r00.par2" yEnc
			else if (preg_match('/^([A-Z0-9][a-zA-Z0-9 ]{6,}?) - File \d+ of \d+ - ".+?'.$this->e1, $subject, $match))
				return $match[1];
			//[02/21] - "Staendig Feucht.part01.rar" - 493.38 MB ....::::UR-powered by SecretUsenet.com::::.... yEnc
			else if (preg_match('/^\[\d+\/\d+\] - "(.+?)'.$this->e0.' - \d+[.,]\d+ [kKmMgG][bB] .+? yEnc$/', $subject, $match))
				return $match[1];
			//Big Tits in Sport 12 (2013) XXX DVDRip x264-CHiKANi - (03/39) "Big.Tits.in.Sport.12.XXX.DVDRip.x264-CHiKANi.part01.rar" - yenc yEnc
			else if (preg_match('/^([A-Z0-9].{5,}?) - \(\d+\/\d+\) "[A-Z0-9].{5,}?" - yenc yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.fz")
		{
			//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
			if (preg_match('/^>ghost-of-usenet\.org>(.+?)<.+?> ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.games")
		{
			//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
			if (preg_match('/^>ghost-of-usenet\.org>(.+?)<.+?> ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
			else if (preg_match('/^<ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
				return $match[1];
			//[ Dawn.of.Fantasy.Kingdom.Wars-PROPHET ] - [12/52] - "ppt-dfkw.part04.rar" yEnc
			else if (preg_match('/^\[ ([-.a-zA-Z0-9]+) \] - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.german.movies")
		{
			//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
			if (preg_match('/^>ghost-of-usenet\.org>(.+?)<.+?> ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
			else if (preg_match('/^<ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.ghosts")
		{
			//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
			if (preg_match('/^<ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.hdtv")
		{
			//[ TrollHD ] - [ 0270/2688 ] - "Tour De France 2013 1080i HDTV MPA 2.0 MPEG2-TrollHD.part0269.rar" yEnc
			//[17/48] - "Oprah's Next Chapter S02E37 Lindsay Lohan 1080i HDTV DD5.1 MPEG2-TrollHD.part16.rar" yEnc
			//[02/29] - "Fox Sports 1 on 1 - Tom Brady 720p HDTV DD5.1 MPEG2-DON.part01.rar" yEnc
			if (preg_match('/^(\[ TrollHD \] - \[ \d+\/\d+\]|\[\d+\/\d+\]) - "(.+? MPEG2-(DON|TrollHD))\..+?" yEnc$/', $subject, $match))
				return $match[2];
			
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.hdtv.x264")
		{
			//[134914]-[#a.b.moovee@EFNet]-[ Magic.Magic.2013.720p.WEB-DL.DD5.1.H.264-fiend ]-[01/74] - "Magic.Magic.2013.720p.WEB-DL.DD5.1.H.264-fiend.par2" yEnc
			//[134863]-[ Rihanna.No.Regrets.2013.720p.WEB-DL.DD5.1.H.264-PZK ]-[01/52] - "Rihanna.No.Regrets.2013.720p.WEB-DL.DD5.1.H.264-PZK.nfo" yEnc
			if (preg_match('/^\[\d+\](-\[.+?\])?-\[ (.+?) \]-\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[2];
			//(23/36) "Love.Is.In.The.Meadow.S08E08.HDTV.720p.x264.ac3.part22.rar" - 2,80 GB - yEnc
			//AMEa(01/49) - AME- "Arbitrage 2013 DTS HD MSTR 5.1 MKV h264 1080p German by AME.nfo" - 7,87 GB - yEnc
			else if (preg_match('/^(AMEa)?\(\d+\/\d+\)( - AME-)? "(.+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[3];
			//Hard.Target.1993.1080p.Bluray.X264-BARC0DE - [36/68] - "BARC0DE1080pHTAR.r22" yEnc
			//Goddess.2013.720p.BDRip.x264.AC3-noOne  [086/100] - "Goddess.2013.720p.BDRip.x264.AC3-noOne.part84.rar" yEnc
			else if (preg_match('/^([A-Z0-9a-z][A-Za-z0-9.-]+) -? \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//I Love Democracy - Norwegen - Doku -  2012 - (German)  - AC3 HD720p  Avi by Waldorf -  [02/71] - "Waldorf.jpg" yEnc
			else if (preg_match('/(.+?)\s+(Avi )?by Waldorf\s+-\s+\[\d+\/\d+\]\s+-\s+".+?"\s+yEnc$/', $subject, $match))
				return preg_replace('/\s\s+/', ' ', $match[1]);
			//Season of the Witch 2011 - "Season.of.the.Witch.2011.1080p.BluRay.DTS.x264-CyTSuNee.part005.rar" yEnc
			//Film - "Alien Antology DC Special Edition 1979-1997 1080p GER HUN HighCode.part001.rar" yEnc
			//Austex Memorandum   "Austex Memorandum 700877270640835.z17" yEnc
			else if (preg_match('/^[A-Z][a-zA-Z0-9 ]+ [- ] "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//"Ninja-Revenge Will Rise UC-Pittis AVCHD-ADD.English.dtsHR.nfo.txt" (01/55) yEnc
			else if (preg_match('/^"(.+?)'.$this->e0.' \(\d+\/\d+\) yEnc$/', $subject, $match))
				return $match[1];
			//[ The.Looney.Tunes.Show.S02E20.480p.WEB-DL.AAC2.0.H.264-YFN ] - [01/19] - "The.Looney.Tunes.Show.S02E20.The.Shell.Game.480p.WEB-DL.AAC2.0.H.264-YFN.nfo" yEnc
			else if (preg_match('/^\[ ([A-Za-z0-9.-]{7,}) \] - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.highspeed")
		{
			//Old Dad uppt 18 und immer (noch) Jungfrau DvD Rip AC3 XviD German 02/34] - "18 und immer (noch) Jungfrau.part01.rar" yEnc
			//Old Dad uppt In ihrem Haus DVD Ripp AC3 German Xvid [01/31] - "In ihrem Haus.par2" yEnc
			//Old Dad uppt Eine offene Rechnung XviD German DVd Rip[02/41] - "Eine offene Rechnung.part01.rar" yEnc
			//Old Dad uppMiss Marple: Der Wachsblumenstrauß , Wunschpost Xvid German10/29] - "Miss Marple Der Wachsblumenstrauß.part09.rar" yEnc
			if (preg_match('/^Old\s+Dad\s+uppt? ?(.+?)( mp4| )?\[?\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[03/61] - "www.realmom.info - xvid - xf-fatalmovecd1.r00" - 773,34 MB - yEnc
			//[40/54] - "Mankind.Die.Geschichte.der.Menschheit.S01E12.Das.Ende.der.Reise.GERMAN.DUBBED.DL.DOKU.1080p.BluRay.x264-TVP.part39.rar" - 4,79 GB yEnc
			else if (preg_match('/^\[\d+\/\d+\] - "(.+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB]( -)? yEnc$/', $subject, $match))
				return $match[1];
			//[02/10] - "Fast.And.Furious.6 (2013).German.720p.CAM.MD-MW upp.by soV1-soko.rar" yEnc
			else if (preg_match('/^\[\d+\/\d+\] - "(.+?) upp.by.+?'.$this->e1, $subject, $match))
				return $match[1];
			//>ghost-of-usenet.org>The A-Team S01-S05(Folgen einzelnd ladbar)<Sponsored by Astinews< (1930/3217) "isd-ateamxvid-s04e21.r19" yEnc
			else if (preg_match('/^>ghost-of-usenet\.org>(.+?)\(.+?\).+? \(\d+\/\d+\) ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//www.usenet-town.com [Sponsored by Astinews] (103/103) "Intimate.Enemies.German.2007.AC3.[passwort protect].vol60+21.PAR2" yEnc
			else if (preg_match('/^www\..+? \[Sponsored.+?\] \(\d+\/\d+\) "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Das.Schwergewicht.German.DL.720p.BluRay.x264-ETM - "etm-schwergewicht-720p.part20.rar" yEnc
			else if (preg_match('/^([A-Za-z0-9][a-zA-Z0-9.-]{6,})\s+- ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[ TiMnZb ] [ www.binnfo.in ] [REPOST] [01/46] - "Columbo - S07 E05 - Des sourires et des armes.nfo" yEnc
			else if (preg_match('/^\[ .+? \] \[ www\..+? \]( \[.+?\])? \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[2];
			//< "Burn.Notice.S04E17.Out.of.the.Fire.GERMAN.DUBBED.DL.720p.WebHD.x264-TVP.par2" >< 01/17 (1.54 GB) >< 11.62 kB > yEnc
			else if (preg_match('/^< "(.+?)'.$this->e0.' >< \d+\/\d+ \(.+?\) >< .+? > yEnc$/', $subject, $match))
				return $match[1];
			//Batman postet 30 Nights of Paranormal Activity with the Devil Inside AC3 XviD German [01/24] - "30 Nights of Paranormal Activity with the Devil Inside.par2" yEnc
			else if (preg_match('/^[A-Za-z0-9]+ postet (.+?) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[04/20 Geroellheimer - S03E19 - Freudige �berraschung Geroellheimer - S03E19 - Freudige �berraschung.mp4.004" yEnc
			else if (preg_match('/^\[\d+\/\d+ (.+?)(\.(part\d*|rar|avi|iso|mp4|mkv|mpg))?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return implode(' ', array_intersect_key(explode(' ', $match[1]), array_unique(array_map('strtolower', explode(' ', $match[1])))));
			//"Homeland.S01.Complete.German.WS.DVDRiP.XViD-ETM.part001.rar" yEnc
			else if (preg_match('/^"(.+?)(\.(part\d*|rar|avi|mp4|mkv|mpg))?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
			{
				if (strlen($match[1]) > 7 && !preg_match('/\.vol.+/', $match[1]))
					return $match[1];
				else
					return $this->releaseCleanerHelper($subject);
			}
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.inner-sanctum")
		{
			//ea17079f47de702eead5114038355a70 [1/9] - "00-da_morty_-_boondock_sampler_02-(tbr002)-web-2013-srg.m3u" yEnc
			if (preg_match('/^[a-fA-F0-9]+ \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//[30762]-[android]-[ Fairway.Solitaire.v1.91.1-AnDrOiD ] [01/10] - "AnDrOiD.nfo" yEnc
			else if (preg_match('/^\[\d+\]-\[.+?\]-\[ (.+?) \] \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.mojo")
		{
			//[17/61] - "www.realmom.info - xvid - xf-devilstomb.r14" - 773,11 MB - yEnc
			if (preg_match('/^\[\d+\/\d+\] - "(.+?)'.$this->e0.' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.mom")
		{
			//[usenet4ever.info] und [SecretUsenet.com] - 96e323468c5a8a7b948c06ec84511839-u4e - "96e323468c5a8a7b948c06ec84511839-u4e.par2" yEnc
			if (preg_match('/^\[usenet4ever\.info\] und \[SecretUsenet\.com\] - (.+?)-u4e - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [01/26] - "Be.Cool.German.AC3.HDRip.x264-FuN.par2" yEnc
			else if (preg_match('/\.net <<<Partner von SSL-News\.info>>> - \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
			else if (preg_match('/^<ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.moovee")
		{
			//[133170]-[FULL]-[#a.b.moovee]-[ Hansel.And.Gretel.Witch.Hunters.DVDR-iGNiTiON ]-[032/117] "ign-witchhunters.r24" yEnc
			//Re: [133388]-[FULL]-[#a.b.moovee]-[ Familiar.Grounds.2011.DVDRip.XViD-TWiST ]-[01/59] - "twist-xvid-terrainsconus.nfo" yEnc
			//[134212]-[FULL]-[#a.b.moovee]-[ Monsters.Inc.2001.1080p.BluRay.x264-CiNEFiLE ] [80/83] - "monsters.inc.2001.1080p.bluray.x264-cinefile.vol015+16.par2" yEnc
			//[135106]-[FULL]-[a.b.mooveeEFNet]-[ Bottle.Rocket.1996.720p.BluRay.DTS.x264-DON ]- [001/105] - "Bottle.Rocket.1996.720p.BluRay.DTS.x264-DON.nfo" yEnc
			//[134914]-[a.b.mooveeEFNet]-[ Magic.Magic.2013.720p.WEB-DL.DD5.1.H.264-fiend ]-[74/74] - "Magic.Magic.2013.720p.WEB-DL.DD5.1.H.264-fiend.vol255+096.par2" yEnc
			if (preg_match('/\[\d+\]-(\[.+?\]-)?\[.+?\]-\[ (.+?) \][- ]+\[\d+\/\d+\]( -)? ".+?" yEnc$/', $subject, $match))
				return $match[2];
			//[42788]-[#altbin@EFNet]-[Full]- "margin-themasterb-xvid.par2" yEnc
			else if (preg_match('/^\[\d+\]-\[.+?\]-\[.+?\]- "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//[ Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE ]-[01/44] - "Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE.par2" yEnc
			//[134863]-[ Rihanna.No.Regrets.2013.720p.WEB-DL.DD5.1.H.264-PZK ]-[52/52] - "Rihanna.No.Regrets.2013.720p.WEB-DL.DD5.1.H.264-PZK.vol127+76.par2" yEnc
			//[Hard.Target.1993.UNRATED.720p.BluRay.x264-88keyz] - [001/101] - "Hard.Target.1993.UNRATED.720p.BluRay.x264-88keyz.nfo"
			//[ Fast.And.Furious.6.2013.720p.WEB-DL.AAC2.0.H.264-HDCLUB ]-[REAL]-[01/54] - "Fast.And.Furious.6.2013.720p.WEB-DL.AAC2.0.H.264-HDCLUB.nfo" yEnc
			else if (preg_match('/^(\[\d+\]-)?\[ ?([a-zA-Z0-9.-]{6,}) ?\](-\[REAL\])? ?- ?\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[2];
			//(????) [0/1] - "A.Good.Day.to.Die.Hard.2013.nzb" yEnc
			else if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//[xxxxx]-[#a.b.moovee@EFNet]-[ xxxxx ]-[02/66] - "tulob88.part01.rar" yEnc
			else if (preg_match('/^\[x+\]-\[.+?\]-\[ x+ \]-\[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Groove.2000.iNTERNAL.DVDRip.XviD-UBiK - [001/111] - "ubik-groove-cd1.par2" yEnc
			//Antony.and.Cleopatra.1972.720p.WEB-DL.H264-brento -[35/57] - "Antony.and.Cleopatra.1972.720p.WEB-DL.AAC2.0.H.264-brento.part34.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9._-]+) - ?\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[133668] - p00okjiue34635xxzx$$Â£Â£zll-b.vol3+2.PAR2 - [005/118]  yEnc
			else if (preg_match('/^(\[\d+\] - [a-z0-9]+.+?)(\.part\d*|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4}) - \[\d+\/\d+\]\s+yEnc$/', $subject, $match))
				return $match[1];
			//[134517]-[01/76] - "Lara Croft Tomb Raider 2001 720p BluRay DTS x264-RightSiZE.nfo" yEnc
			else if (preg_match('/^\[\d+\]-\[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//(Iron.Man.3.2013.R5.DVDRip.XviD-AsA) (01/26) - "Iron.Man.3.2013.R5.DVDRip.XviD-AsA.part01.part.sfv" yEnc
			else if (preg_match('/^\(([a-zA-Z0-9.-]+)\) \(\d+\/\d+\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(Classic Surf) Morning.Of.The.Earth.1971 [03/29] - "Morning.Of.The.Earth.1971.part02.rar" yEnc
			else if (preg_match('/^\([a-zA-Z0-9].+?\) ([a-zA-Z0-9.-]+) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.movies.divx")
		{
			//[134787]-[FULL]-[#a.b.moovee]-[ Trance.2013.DVDRiP.XViD-SML ]-[1/2] - "tranceb-xvid-sml.jpg" yEnc
			if (preg_match('/^\[\d+\]-\[.+?\]-\[.+?\]-\[ (.+?) \]-\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(www.Thunder-News.org) >CD2< <Sponsored by Secretusenet> - "exvid-emma-cd2.par2" yEnc
			else if (preg_match('/^\(www\.Thunder-News\.org\) .+? - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Movieland Post Voor FTN - [01/43] - "movieland0560.par2" yEnc
			else if (preg_match('/^[a-zA-Z ]+Post Voor FTN - \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Disney short films collection by mayhem masta"1923 - Alice's Wonderland.vol15+7.par2" yEnc
			else if (preg_match('/.+?by mayhem masta"(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.mp3.complete_cd")
		{
			//[052713]-[#eos@EFNet]-[All_Shall_Perish-Montreal_QUE_0628-2007-EOS]-[09/14] "06-all_shall_perish-deconstruction-eos.mp3" yEnc
			if (preg_match('/^\[\d+\]-\[.+?\]-\[(.+?)\]-\[\d+\/\d+\] ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.multimedia")
		{
			//Escort.2006.DUTCH.WEB-RiP.x264-DLH - [01/23] - "Escort.2006.DUTCH.WEB-RiP.x264-DLH.par2" yEnc
			//Tusenbroder.S01E05.PDTV.XViD.SWEDiSH-NTV  [01/69] - "ntv-tusenbroder.s01e05.nfo" yEnc
			if (preg_match('/^([A-Z0-9a-z.-]{10,})\s+(- )?\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.multimedia.anime")
		{
			//High School DxD New 01 (480p|.avi|xvid|mp3) ~bY Hatsuyuki [01/18] - "[Hatsuyuki]_High_School_DxD_New_01_[848x480][76B2BB8C].avi.001" yEnc
			if (preg_match('/.+? \((360|480|720|1080)p\|.+? ~bY .+? \[\d+\/\d+\] - "(.+?\[[A-F0-9]+\].+?)'.$this->e1, $subject, $match))
				return $match[2];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.multimedia.anime.highspeed")
		{
			//High School DxD New 01 (480p|.avi|xvid|mp3) ~bY Hatsuyuki [01/18] - "[Hatsuyuki]_High_School_DxD_New_01_[848x480][76B2BB8C].avi.001" yEnc
			if (preg_match('/.+? \((360|480|720|1080)p\|.+? ~bY .+? \[\d+\/\d+\] - "(.+?\[[A-F0-9]+\].+?)'.$this->e1, $subject, $match))
				return $match[2];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.multimedia.documentaries")
		{
			//"Universe S4E08.part40.rar" - [41 of 76 - 10013 kb] yEnc
			if (preg_match('/^"(.+?)'.$this->e0.' - \[\d+ of \d+ - \d+ [kKmMgG][bB]\] yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.multimedia.scifi")
		{
			//some m4vs - "SilverHawks_v1eps01_The Origin Story.par2" yEnc
			if (preg_match('/^some m4vs - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.ps3")
		{
			//[4197] [036/103] - "ant-mgstlcd2.r34" yEnc
			if (preg_match('/^(\[\d+\] )\[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1].$match[2];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.sounds.lossless")
		{
			//http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
			if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Rush - Sector One 24-96  (000/229] - ".nzb" yEnc
			//Stevie Ray Vaughan - Couldn't Stand the Weather  (01/19] - ".sfv" yEnc
			else if (preg_match('/^([a-zA-Z0-9]+.+? - .+?)\s+\(\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//trtk07073 - [18/26] - "1990 Santana - Spirits Dancing In The Flesh (flac).part17.rar" yEnc
			else if (preg_match('/^trtk\d+ - \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//COMPLETE REPOST Magma - NMR - 1974 - Wurdah Itah [01 of 23] "1974 - Wurdah Itah.par2" yEnc
			else if (preg_match('/^COMPLETE REPOST (.+? - )NMR -( \d{4}) - (.+?) \[\d+ of \d+\] ".+?" yEnc$/', $subject, $match))
				return $match[1].$match[3]."(".$match[2].")";
			//Sensation - VA - Source Of Light (2CD 2012) [02 of 67] - "00 - Sensation - VA - Source Of Light (2CD 2012) [nmr].txt" yEnc
			else if (preg_match('/^([A-Z0-9].+? - VA - .+?) \[\d+ of \d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Ryan McGarvey - Forward In Reverse [01/21] - "00 - Ryan Mcgarvey - Forward in Reverse.nfo" yEnc
			//JFC - The Timerewinder (NMR) [01/15] - "00 - The Timerewinder.nfo" yEnc
			//The Brothers Johnson - 1981 - Winners (2011 expanded remastered) [01/31] - "01 - The Real Thing.flac" yEnc
			//Jermaine Jackson - 1980 - Let's Get Serious [00/23] - "Jermaine Jackson - 1980 - Let's Get Serious.nzb" yEnc
			else if (preg_match('/^([A-Z0-9][A-Za-z0-9 ]{2,} -( \d{4} -)? [A-Z0-9].+?( \(.+?\))?) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Miles Davis - In A Silent Way (1969) [2013 - HDTracks 24-176,4] - "iasw24-176.par2" yEnc
			//Bob James & David Sanborn - Quartette Humaine (2013) [HDTracks 24-88,2] - "qh24-88.par2" yEnc
			else if (preg_match('/^([A-Z0-9].+? - [A-Z0-9].+? \(\d{4}\) \[.*?HDTracks.+?\]) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Sonny Landreth - 2010 - Mississippi Blues - 04 of 29 - 00 - Mississippi Blues.sfv yEnc
			//Fruteland Jackson - 1996 - ... Is All I Crave - 08 of 20 - 00 - Fruteland Jackson - ... Is All I Crave.log yEnc
			else if (preg_match('/^([A-Z0-9].+? - \d{4} - .+?) - \d+ of \d+ - \d+ - .+? yEnc$/', $subject, $match))
				return $match[1];
			//(VA - Cafe Del Mar Dreams 5-2012-Samfie Man) [37/38] - "VA - Cafe Del Mar Dreams 5-2012-Samfie Man.vol063+040.par2" yEnc
			else if (preg_match('/^\((VA - .+?)\) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(152092XA20) [08/16] - "Guns and Roses - Use Your Illusion I - 08-Back Off Bitch.flac" yEnc
			else if (preg_match('/^\([A-Z0-9]+\) \[\d+\/\d+\] - "(.+?) - \d+-.+?" yEnc$/', $subject, $match))
				return $match[1];
			//Eros_Ramazzotti-Eros-IT-CD-FLAC-1997-FADA[04/26] - "00-eros_ramazzotti-eros-1997-fada.sfv" yEnc
			else if (preg_match('/^([\w-]{5,})\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.sounds.mp3")
		{
			//(dream-of-usenet.info) - [04/15] - "Enya-And_Winter_Came...-2008.part2.rar" yEnc
			if (preg_match('/^\(dream-of-usenet\.info\) - \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
			else if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//>>> CREATIVE COMMONS NZB <<< "dexter romweber duo-lookout" - File 1 of 9: "creative_commons_nzb_dexter_romweber_duo-lookout.rar" yEnc
			else if (preg_match('/^>>> CREATIVE COMMONS NZB <<< "(.+?)" - File \d+ of \d+: ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<<<usenet-space-cowboys.info>>>  <<<Powered by https://secretusenet.com>< "Justin_Bieber-Believe_Acoustic-2013-pLAN9_usenet-space-cowbys.info.rar" >< 4/6 (78.65 MB) >< 60.84 MB > yEnc
			else if (preg_match('/^.+?usenet-space.+?Powered by.+? "(.+?)'.$this->e0.'.+? \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\) .+? \d+[.,]\d+ [kKmMgG][bB] .+?yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.teevee")
		{
			//[278997]-[FULL]-[#a.b.erotica]-[ chi-the.walking.dead.xxx ]-[06/51] - "chi-the.walking.dead.xxx-s.mp4" yEnc
			//[######]-[FULL]-[#a.b.teevee@EFNet]-[ Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD ] [1/5] - "Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD.nfo" yEnc
			if (preg_match('/\[[\d#]+\]-\[.+?\]-\[.+?\]-\[ (.+?) \][- ]\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[#a.b.teevee] Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD - [01/24] - "Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD.nfo" yEnc
			else if (preg_match('/^\[#a\.b\.teevee\] (.+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//ah63jka93jf0jh26ahjas558 - [01/22] - "ah63jka93jf0jh26ahjas558.par2" yEnc
			else if (preg_match('/^([a-z0-9]+) - \[\d+\/\d+\] - "[a-z0-9]+\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//fhdbg34rgjdsfd008c (42/43) "fhdbg34rgjdsfd008c.vol062+64.par2" - 3,68 GB - yEnc
			else if (preg_match('/^([a-z0-9]+) \(\d+\/\d+\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//t2EI3CdWdF0hi5b8L9tkx[08/52] - "t2EI3CdWdF0hi5b8L9tkx.part07.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9]+)\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(01/37) "Entourage S08E08.part01.rar" - 349,20 MB - yEnc
			//(01/24) "EGtnu7OrLNQMO2pDbgpDrBL8SnjZDpab.nfo" - 686 B - 338.74 MB - yEnc (1/1)
			else if (preg_match('/^\(\d+\/\d+\) "(.+?)'.$this->e0.' - \d.+?B - (\d.+?B -)? yEnc$/', $subject, $match))
				return $match[1];
			//(01/28) - Continuum.S02E13.Second.Time.1080p.WEB-DL.AAC2.0.H264 - "Continuum.S02E13.Second.Time.1080p.WEB-DL.AAC2.0.H264.par2" - 1.75 GB - yEnc
			else if (preg_match('/^\(\d+\/\d+\) - ([\w.-]{5,}) - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//[01/42] - "King.And.Maxwell.S01E08.1080p.WEB-DL.DD5.1.H264-Abjex.par2" yEnc
			else if (preg_match('/^\[\d+\/\d+\] - "([A-Za-z0-9.-]+?)'.$this->e1, $subject, $match))
				return $match[1];
			//Divers (12/42) -"Juste.Pour.Rire.2013.Gala.JF.Mercier.FRENCH.720p.HDTV.x264-QuebecRules.part11.rar" yEnc
			//Par le chapeau (06/43) - "8C7D59F472E03.part04.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9 ]+) \(\d+\/\d+\) - ?".+?'.$this->e1, $subject, $match))
				return $match[1];
			//House.Hunters.International.S05E502.720p.hdtv.x264 [01/27] - "House.Hunters.International.S05E502.720p.hdtv.x264.nfo" yEnc
			//Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS - [01/33] - "Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS.par2" yEnc
			else if (preg_match('/^(Re: )?([a-zA-Z0-9._-]+)([{}A-Z_]+)?( -)? \[\d+(\/| of )\d+\]( -)? ".+?" yEnc$/', $subject, $match))
				return $match[2];
			//Silent Witness S15E02 Death has no dominion.par2 [01/44] - yEnc
			else if (preg_match('/^([a-zA-Z0-9 ]+)(\.part\d*|\.rar)?(\.vol.+? |\.[A-Za-z0-9]{2,4} )\[\d+\/\d+\] - yEnc$/', $subject, $match))
				return $match[1];
			//(bf1) [03/31] - "The.Block.AU.Sky.High.S07E61.WS.PDTV.XviD.BF1.part01.sfv" yEnc (1/1)
			else if (preg_match('/^\(bf1\) \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//[ TVPower ] - "Dexter.S07E10.720p.HDTV.x264-NLsubs.par2" yEnc
			//[ TVPower ] - [010/101] - "Desperate.Housewives.S08Disc2.NLsubs.part009.rar" yEnc
			else if (preg_match('/^\[ [A-Za-z]+ \] - (\[\d+\/\d+\] - )?"(.+?)'.$this->e1, $subject, $match))
				return $match[2];
			//[www.allyourbasearebelongtous.pw]-[WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams]-[03/69] "WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams.r00" - 1.58 GB - yEnc
			else if (preg_match('/^\[.+?\]-\[(.+?)\]-\[\d+\/\d+\] ".+?" - \d+([.,]\d+ [kKmMgG])?[bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(www.Thunder-News.org) >CD1< <Sponsored by Secretusenet> - "moovee-fastest.cda.par2" yEnc
			else if (preg_match('/^\(www\..+?\) .+? <Sponsored.+?> - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			//<<<Pitbull>>> usenet-space-cowboys.info <<<Powered by https://secretusenet.com>< "S05E03 Pack die Badehose ein_usenet-space-cowbys.info.par2" >< 01/10 (411,16 MB) >< 3,48 kB > yEnc
			else if (preg_match('/\.info .+?Powered by .+?\.com "(.+?)'.$this->e0.' .+? \d+\/\d+ \(\d+[,.]\d+ [mMkKgG][bB]\) .+? yEnc$/', $subject, $match))
				return $match[1];
			//Newport Harbor The Real Orange County - S01E01 - A Black & White Affair [01/11] - "Newport Harbor The Real Orange County - S01E01 - A Black & White Affair.mkv" yEnc
			else if (preg_match('/^([a-zA-Z0-9]+ .+? - S\d+E\d+ - .+?) \[\d+\/\d+\] - ".+?\..+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.town")
		{
			//<TOWN><www.town.ag > <download all our files with>>> www.ssl-news.info <<< > [05/87] - "Deep.Black.Ass.5.XXX.1080p.WEBRip.x264-TBP.part03.rar" - 7,87 GB yEnc
			//<TOWN><www.town.ag > <partner of www.ssl-news.info > [02/24] - "Dragons.Den.UK.S11E02.HDTV.x264-ANGELiC.nfo" - 288,96 MB yEnc
			//<TOWN><www.town.ag > <SSL - News.Info> [6/6] - "TTT.Magazine.2013.08.vol0+1.par2" - 33,47 MB yEnc
			if (preg_match('/^<TOWN>.+?town\.ag.+?(www\..+?|News)\.[iI]nfo.+? \[\d+\/\d+\]( -)? "(.+?)(-sample)?'.$this->e0.' - \d+[.,]\d+ [kKmMgG][bB]M? yEnc$/', $subject, $match))
				return $match[3];
			//[01/29] - "Bellflower.2011.German.AC3.BDRip.XviD-EPHEMERiD.par2" - 1,01 GB yEnc
			//(3/9) - "Microsoft Frontpage 2003 - 4 Town-Up from Kraenk.rar.par2" - 181,98 MB - yEnc
			else if (preg_match('/^[\[(]\d+\/\d+[\])] - "([A-Z0-9].{2,}?)'.$this->e0.' - \d+[.,]\d+ [kKmMgG][bB]( -)? yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		/*else if ($groupName === "alt.binaries.tun")
		{
			* Leaving here to show that you can get the names from these. 
			* Useless since all these are passworded and if there is a backlog in post proc, sickbeard will pull these and they will fail since they are passworded.
			//[ nEwZ[NZB].iNFO - [ QmlnTW91dGhmdWxzLjEzLjA3LjA4LkNocmlzc3kuR3JlZW5lLlhYWC43MjBwLk1QNC1LVFI= ] - File [06/48]: "b582519da4d849df003559fc4ae45219.nfo" yEnc
			if (preg_match('/^\[ nEwZ\[NZB\]\.iNFO - \[ ([a-z0-9A-Z]{3,}=) \] - File \[\d+\/\d+\]: ".+?" yEnc$/', $subject, $match))
				return base64_decode($match[1]);
			//[PRiVATE] VHdpc3R5cy5jb21fMTMuMDguMDkuQWlkZW4uQXNobGV5LkVsbGUuQWxleGFuZHJhLldoYXQuWW91ci5GdXR1cmUuSG9sZHMuWFhYLklNQUdFU0VULUZ1R0xp [06/10] - "89857cebff1efd7927ebddf30281b0e4.part2.rar" yEnc
			else if (preg_match('/^\[PRiVATE\] ([a-z0-9A-Z]{4,}=*) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return base64_decode($match[1]);
			else
				return $this->releaseCleanerHelper($subject);
		}*/
		else if ($groupName === "alt.binaries.tv")
		{
			//Borgen.2x02.A.Bruxelles.Non.Ti.Sentono.Urlare.ITA.BDMux.x264-NovaRip [02/22] - "borgen.2x02.ita.bdmux.x264-novarip.par2" yEnc
			if (preg_match('/^([a-zA-Z0-9.-]+) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(bf1) [03/31] - "The.Block.AU.Sky.High.S07E56.WS.PDTV.XviD.BF1.part01.sfv" yEnc
			else if (preg_match('/^\(bf1\) \[\d+\/\d+\] - "(.+?)'.$this->e1, $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "dk.binaer.tv")
		{
			//Store.Boligdroemme.S02E06.DANiS H.HDTV.x264-TVBYEN - [01/28] - "store.boligdroemme.s02e06.danis h.hdtv.x264-tvbyen.nfo" yEnc
			if (preg_match('/^([a-zA-Z0-9].+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		// Run at the end because this can be dangerous. In the future it's better to make these per group. There should not be numbers after yEnc because we remove them as well before inserting (even when importing).
		// This regex gets almost all of the predb release names also keep in mind that not every subject ends with yEnc, some are truncated, because of the 255 character limit and some have extra charaters tacked onto the end, like (5/10).
		else if (preg_match('/^\[\d+\][- ]{0,3}(\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3}\[[- #@\.\w]+\][- ]{0,3}|\[[- #@\.\w]+\][- ]{0,3}\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3}|\[.+?efnet\][- ]{0,3}|\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3})(\[FULL\])?[- ]{0,3}(\[ )?(\[)? ?(\/sz\/)?(F: - )?(?P<title>[- _!@\.\'\w\(\)~]{10,}) ?(\])?[- ]{0,3}(\[)? ?(REPOST|REPACK|SCENE|EXTRA PARS|REAL)? ?(\])?[- ]{0,3}?(\[\d+[-\/~]\d+\])?[- ]{0,3}["|#34;]*.+["|#34;]* ?[yEnc]{0,4}/i', $subject, $match))
			return $match['title'];
		else
			return $this->releaseCleanerHelper($subject);
	}

	public function releaseCleanerHelper($subject)
	{
		$cleanerName = preg_replace('/(- )?yEnc$/', '', $subject);
		return trim(preg_replace('/\s\s+/', ' ', $cleanerName));
	}

	//
	//	Cleans release name for the namefixer class.
	//
	public function fixerCleaner($name)
	{
		//Extensions.
		$cleanerName = preg_replace('/([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}$|$)/i', ' ', $name);
		//Remove stuff from the start.
		$cleanerName = preg_replace('/^(Release Name|sample-)/i', ' ', $cleanerName);
		//Replace multiple spaces with 1 space
		$cleanerName = preg_replace('/\s\s+/i', ' ', $cleanerName);
		//Remove invalid characters.
		$cleanerName = trim(utf8_encode(preg_replace('/[^(\x20-\x7F)]*/','', $cleanerName)));

		return $cleanerName;
	}
}
