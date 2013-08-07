<?php
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/predb.php");


//
//	Cleans names for collections/releases/imports/namefixer.
//
class nameCleaning
{
	//
	//	Cleans a usenet subject returning something that can tie many articles together.
	//
	//	$subject = The usenet subject, ending with yEnc (part count removed from the end).
	//	$groupName = The name of the group for the article.
	//	$nofiles = Wether the article has a filecount or not.
	//
	//	First, try against groups with strict regex.
	//	If that fails, try against more generic regex.
	//	$nofiles can help with bunched releases, by having its own set of regex.
	//
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
			else if (preg_match('/^<TOWN> www\.town\.ag > sponsored by www\.ssl-news\.info > \(\d+(\/\d+\) ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
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
			else if (preg_match('/^(>+Hell-of-Usenet(\.org)?>+( -)? \[)\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")( - \d+[.,]\d+ [kKmMgG][bB])? yEnc$/', $subject, $match))
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
			else if (preg_match('/^\((\?{4}\) \[)\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			else if (preg_match('/^(- ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			else if (preg_match('/^\(\d+(\/\d+\) - .+? - ".+?)( \(\d+\))?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(59/81) "1973 .Lee.Jun.Fan.DVD9.untouched.z46" - 7,29 GB - Lee.Jun.Fan.sein.Film.DVD9.untouched yEnc
			//(01/12) - "TransX - Living on a Video 1993.part01.rar" - 561,55 MB - TransX - Living on a Video 1993.[Lossless] Highh Quality yEnc
			else if (preg_match('/^\(\d+\/\d+\)( -)? ".+?" - \d+[,.]\d+ [mMkKgG]([bB] - .+?) yEnc$/', $subject, $match))
				return $match[2];
			//>>> www.lords-of-usenet.org <<<  "Der Schuh Des Manitu.par2" DVD5  [001/158] - 4,29 GB yEnc
			else if (preg_match('/^(>>> www\.lords-of-usenet\.org <<<.+? ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") .+? \[\d+\/\d+\] - .+? yEnc$/', $subject, $match))
				return $match[1];
			//NEUES 4y - [@ usenet-4all.info - powered by ssl.news -] [5,58 GB] [002/120] "DovakinPack.part002.rar" yEnc
			//NEUES 4y (PW)  [@ usenet-4all.info - powered by ssl.news -] [7,05 GB] [014/152] "EngelsGleich.part014.rar" yEnc
			else if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+(\/\d+\] ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[2];
			//Old Dad uppt   Die Schatzinsel Teil 1+Teil2  AC3 DVD Rip German XviD Wp 01/33] - "upp11.par2" yEnc
			//Old Dad uppt Scary Movie5 WEB RiP Line XviD German 01/24] - "Scary Movie 5.par2" yEnc
			else if (preg_match('/^(([a-zA-Z0-9].+?\s{2,}|Old Dad uppt\s+)(.+?) )\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//>>>  20,36 MB   "Winamp.Pro.v5.70.3392.Incl.Keygen-FFF.par2"   552 B yEnc
			//..:[DoAsYouLike]:..    9,64 MB    "Snooper 1.39.5.par2"    468 B yEnc
			else if (preg_match('/^.+?\s{2,}\d+[,.]\d+ [mMkKgG]([bB]\s{2,}".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")\s{2,}(\d+ B|\d+[,.]\d+ [mMkKgG][bB]) yEnc$/', $subject, $match))
				return$match[1];
			//(MKV - DVD - Rip - German - English - Italiano) - "CALIGULA (1982) UNCUT.sfv" yEnc
			else if (preg_match('/^(\(.+?\) - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//"sre56565ztrtzuzi8inzufft.par2" yEnc
			else if (preg_match('/^"([a-z0-9]+)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			else if (preg_match('/^\(\d+(\/\d+\) - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//(????) [02/71] - "Lasting Weep (1969-1971).part.par2" yEnc
			else if (preg_match('/^(\(\?{4}\) \[)\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//(01/59) "ThienSuChungQuy_II_E16.avi.001" - 1,49 GB - yEnc
			//(058/183) "LS_HoangChui_2xdvd5.part057.rar" - 8,36 GB -re yEnc
			else if (preg_match('/^\(\d+(\/\d+\) ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] -(re)? yEnc$/', $subject, $match))
				return $match[1];
			//[AoU] Upload#00287 - [04/43] - "Upload-ZGT1-20130525.part03.rar" yEnc
			else if (preg_match('/^(\[[a-zA-Z]+\] .+? - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return$match[1];
			//(nate) [01/27] - "nate_light_13.05.23.par2" yEnc
			else if (preg_match('/^\([a-z]+\) \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//""Absolute Database Component for BCBuilder 4-6 MultiUser Edit 4.85.rar"" yEnc
			else if (preg_match('/^("".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")" yEnc$/', $subject, $match))
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
			else if (preg_match('/^\[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] .+? usenet-space.+?yEnc$/', $subject, $match))
				return $match[1];
			//(Neu bei Bitfighter vom 23-07-2013) - "01 - Sido - Bilder Im Kopf.mp3" yEnc
			else if (preg_match('/^(\(.+?\) - ").+?" yEnc$/', $subject, $match))
				return $match[1];
			//(2/8) "Mike.und.Molly.S01E22.Maennergespraeche.GERMAN.DL.DUBBED.720p.BluRay.x264-TVP.part1.rar" - 1023,92 MB - yEnc
			else if (preg_match('/^\(\d+(\/\d+\) ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//4y (PW)   [@ usenet-4all.info - powered by ssl.news -] [27,35 GB] [001/118] "1f8867bb6f89491793d3.part001.rar" yEnc
			else if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+(\/\d+\] ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			else if (preg_match('/^\(\d+(\/\d+\)(\s+ -)? "[a-zA-Z0-9]+?)(\d+)?\..+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
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
			else if (preg_match('/^.+?<<\d+\/\d+>> "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/', $subject, $match))
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
			else if (preg_match('/^([A-Za-z0-9].+? \((19|20)\d\d\) ")\d{2}\. .+?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//"BB636.part14.rar" - (15/39) - yEnc
			else if (preg_match('/^"([a-zA-Z0-9]+)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \(\d+\/\d+\) - yEnc$/', $subject, $match))
				return $match[1];
			//Lutheria - FC Twente TV Special - Ze wilde op voetbal [16/49] - "Lutheria - FC Twente TV Special - Ze wilde op voetbal.part16.rar" yEnc
			// panter - [001/101] - "74518-The Hunters (2011).par2" yEnc
			else if (preg_match('/^[-a-zA-Z0-9 ]+ \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//Pee Mak Prakanong - 2013 - Thailand - ENG Subs - "Pee Mak Prakanong.2013.part22.rar" yEnc
			//P2H - "AMHZQHPHDUZZJSFZ.vol181+33.par2" yEnc
			else if (preg_match('/^([-a-zA-Z0-9 ]+ - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//(????) [011/161] - "flynns-image-redux.part010.rar" yEnc
			//(Dgpc) [000/110] - "Teen Wolf - Seizoen.3 - Dvd.2 (NLsub).nzb" yEnc
			else if (preg_match('/^\((\?{4}|[a-zA-Z]+)\) \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//("Massaladvd5Kilusadisc4S1.par2" - 4,55 GB -) "Massaladvd5Kilusadisc4S1.par2" - 4,55 GB - yEnc
			else if (preg_match('/^\("([a-z0-9A-Z]+).+?" - \d+[,.]\d+ [mMkKgG][bB] -\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//"par.4kW9beE.1.vol122+21.par2" yEnc
			else if (preg_match('/^"(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [01/19] - "Age.of.Dinosaurs.German.AC3.HDRip.x264-FuN.par2" yEnc
			//>>>>>Hell-of-Usenet.org>>>>> - [01/35] - "Female.Agents.German.2008.AC3.DVDRip.XviD.iNTERNAL-VideoStar.par2" yEnc
			else if (preg_match('/^(.+?\.(info|org)>+ - \[)\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1].$match[3];
			//[010/101] - "Bf56a8aR-20743f8D-Vf7a11fD-d7c6c0.part09.rar" yEnc
			//[1/9] - "fdbvgdfbdfb.part.par2" yEnc
			else if (preg_match('/^\[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//[LB] - [063/112] - "RVL-GISSFBD.part063.rar" yEnc
			else if (preg_match('/^(\[[A-Z]+\] - \[)\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//(¯`*•.¸(PWP).•*´¯) (071/100) "JUST4US_025.part070.rar" - 22,50 GB – yEnc
			else if (preg_match('/^\(.+?\(PWP\).+?\) \(\d+(\/\d+\) ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") .+? \d+[,.]\d+ [mMkKgG][bB] .+ yEnc$/', $subject, $match))
				return $match[1];
			//thnx to original poster [00/98] - "2669DFKKFD2008.nzb ` 2669DFKKFD2008 " yEnc
			else if (preg_match('/^thnx to original poster \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4})("| `).+? yEnc$/', $subject, $match))
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
			else if (preg_match('/^\(\d+(\/\d+\) ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
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
			if (preg_match('/^(Re: REQ: .+? - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//Per REQ - "The.Wild.Wild.West.S03E11.The.Night.of.the.Cut-Throats.DVDRip.XVID-tz.par2" 512x384 [01/40] yEnc
			else if (preg_match('/^(Per REQ - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") .+? \[\d+\/\d+\] yEnc$/', $subject, $match))
				return $match[1];
			//By req: "Dennis The Menace - 4x25 - Dennis and the Homing Pigeons.part05.rar" yEnc
			else if (preg_match('/^(By req: ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//I Spy HQ DVDRips "I Spy - 3x26 Pinwheel.part10.rar" [13/22] yEnc
			else if (preg_match('/^([a-zA-Z ]+HQ DVDRips ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") \[\d+\/\d+\] yEnc$/', $subject, $match))
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
			else if (preg_match('/^(".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//Re: Outside Edge series 1 - [01/20] - "Outside Edge S01.nfo" yEnc
			else if (preg_match('/^(Re: )?([a-zA-Z0-9]+ .+? series \d+ - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[2];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.documentaries")
		{
			//#sterntuary - Alex Jones Radio Show - "05-03-2009_INFO_BAK_ALJ.nfo" yEnc
			if (preg_match('/^(#sterntuary - .+? - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.dvd")
		{
			//thnx to original poster [00/98] - "2669DFKKFD2008.nzb ` 2669DFKKFD2008 " yEnc
			if (preg_match('/^thnx to original poster \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4})("| `).+? yEnc$/', $subject, $match))
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
			else if (preg_match('/^<TOWN><www\.town\.ag > <download all our files with>>> www\.ssl-news\.info <<< > \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - /', $subject, $match))
				return $match[1];
			//NihilCumsteR [1/8] - "Conysgirls.cumpilation.xxx.NihilCumsteR.par2" yEnc
			else if (preg_match('/^NihilCumsteR \[\d+\/\d+\] - "(.+?NihilCumsteR\.)/', $subject, $match))
				return $match[1];
			//"Lesbian seductions 26.part.nzb" yEnc
			else if (preg_match('/^(".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.etc")
		{
			//7000999555666777123754 - [334/389] - "The Secret of Bible & Jesus. Beyond The Da Vinci Code - YouTube.3gp" yEnc
			if (preg_match('/^(\d+ - \[)\d+\/\d+\] - ".+?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
		else if ($groupName === "alt.binaries.highspeed")
		{
			//[03/61] - "www.realmom.info - xvid - xf-fatalmovecd1.r00" - 773,34 MB - yEnc
			if (preg_match('/^\[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//www.usenet-town.com [Sponsored by Astinews] (103/103) "Intimate.Enemies.German.2007.AC3.[passwort protect].vol60+21.PAR2" yEnc
			else if (preg_match('/^www\..+? \[Sponsored.+?\] \(\d+(\/\d+\) ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.inner-sanctum")
		{
			////ea17079f47de702eead5114038355a70 [1/9] - "00-da_morty_-_boondock_sampler_02-(tbr002)-web-2013-srg.m3u" yEnc
			if (preg_match('/^([a-fA-F0-9]+) \[\d+\/\d+\] - ".+?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.mojo")
		{
			//[17/61] - "www.realmom.info - xvid - xf-devilstomb.r14" - 773,11 MB - yEnc
			if (preg_match('/^\[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
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
			else if (preg_match('/(.+?\.net <<<Partner von SSL-News\.info>>> - \[)\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
			else if (preg_match('/^(<ghost-of-usenet\.org>.+? \[)\d+\/\d+\] - ".+?" .+? yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.moovee")
		{
			//[133170]-[FULL]-[#a.b.moovee]-[ Hansel.And.Gretel.Witch.Hunters.DVDR-iGNiTiON ]-[032/117] "ign-witchhunters.r24" yEnc
			//Re: [133388]-[FULL]-[#a.b.moovee]-[ Familiar.Grounds.2011.DVDRip.XViD-TWiST ]-[01/59] - "twist-xvid-terrainsconus.nfo" yEnc
			//[134212]-[FULL]-[#a.b.moovee]-[ Monsters.Inc.2001.1080p.BluRay.x264-CiNEFiLE ] [80/83] - "monsters.inc.2001.1080p.bluray.x264-cinefile.vol015+16.par2" yEnc
			if (preg_match('/(\[\d+\]-\[.+?\]-\[.+?\]-\[ .+? \](-| ))\[\d+\/\d+\]( -)? ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[42788]-[#altbin@EFNet]-[Full]- "margin-themasterb-xvid.par2" yEnc
			else if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]- ").+?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//[ Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE ]-[01/44] - "Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE.par2" yEnc
			//[ Admission.2013.720p.WEB-DL.DD5.1.H.264-HD4FUN ] - [01/82] - "Admission.2013.720p.WEB-DL.DD5.1.H.264-HD4FUN.nfo" yEnc
			else if (preg_match('/^(\[ [a-zA-Z0-9.-]+ \] ?- ?\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(????) [0/1] - "A.Good.Day.to.Die.Hard.2013.nzb" yEnc
			else if (preg_match('/^\(\?{4}\) \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//[xxxxx]-[#a.b.moovee@EFNet]-[ xxxxx ]-[02/66] - "tulob88.part01.rar" yEnc
			else if (preg_match('/^\[x+\]-\[.+?\]-\[ x+ \]-\[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//Groove.2000.iNTERNAL.DVDRip.XviD-UBiK - [001/111] - "ubik-groove-cd1.par2" yEnc
			//Antony.and.Cleopatra.1972.720p.WEB-DL.H264-brento -[35/57] - "Antony.and.Cleopatra.1972.720p.WEB-DL.AAC2.0.H.264-brento.part34.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9._-]+ - ?\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[133668] - p00okjiue34635xxzx$$Â£Â£zll-b.vol3+2.PAR2 - [005/118]  yEnc
			else if (preg_match('/^(\[\d+\] - [a-z0-9]+.+?)(\.part(\d+)?|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4}) - \[\d+\/\d+\]\s+yEnc$/', $subject, $match))
				return $match[1];
			//[134517]-[01/76] - "Lara Croft Tomb Raider 2001 720p BluRay DTS x264-RightSiZE.nfo" yEnc
			else if (preg_match('/^\[\d+\]-\[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//(Iron.Man.3.2013.R5.DVDRip.XviD-AsA) (01/26) - "Iron.Man.3.2013.R5.DVDRip.XviD-AsA.part01.part.sfv" yEnc
			else if (preg_match('/^(\([a-zA-Z0-9.-]+\) \()\d+\/\d+\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(Classic Surf) Morning.Of.The.Earth.1971 [03/29] - "Morning.Of.The.Earth.1971.part02.rar" yEnc
			else if (preg_match('/^(\([a-zA-Z0-9].+?\) [a-zA-Z0-9.-]+ \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.movies.divx")
		{
			//(www.Thunder-News.org) >CD2< <Sponsored by Secretusenet> - "exvid-emma-cd2.par2" yEnc
			if (preg_match('/^(\(www\.Thunder-News\.org\) .+? - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//Movieland Post Voor FTN - [01/43] - "movieland0560.par2" yEnc
			if (preg_match('/^([a-zA-Z ]+Post Voor FTN - \[\d+\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//Disney short films collection by mayhem masta"1923 - Alice's Wonderland.vol15+7.par2" yEnc
			else if (preg_match('/(.+?by mayhem masta".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			if (preg_match('/(.+? \((360|480|720|1080)p\|.+? ~bY .+? \[)\d+\/\d+\] - ".+?\[[A-F0-9]+\].+?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.multimedia.anime.highspeed")
		{
			//High School DxD New 01 (480p|.avi|xvid|mp3) ~bY Hatsuyuki [01/18] - "[Hatsuyuki]_High_School_DxD_New_01_[848x480][76B2BB8C].avi.001" yEnc
			if (preg_match('/(.+? \((360|480|720|1080)p\|.+? ~bY .+? \[)\d+\/\d+\] - ".+?\[[A-F0-9]+\].+?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[2];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.multimedia.documentaries")
		{
			//"Universe S4E08.part40.rar" - [41 of 76 - 10013 kb] yEnc
			if (preg_match('/^(".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \[\d+ of \d+ - \d+ [kKmMgG][bB]\] yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.multimedia.scifi")
		{
			//some m4vs - "SilverHawks_v1eps01_The Origin Story.par2" yEnc
			if (preg_match('/^(some m4vs - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.ps3")
		{
			//[4197] [036/103] - "ant-mgstlcd2.r34" yEnc
			if (preg_match('/^\[\d+\] \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.sounds.lossless")
		{
			//http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
			if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->collectionsCleanerHelper($subject, $groupName, $nofiles);
		}
		else if ($groupName === "alt.binaries.sounds.mp3")
		{
			//(dream-of-usenet.info) - [04/15] - "Enya-And_Winter_Came...-2008.part2.rar" yEnc
			if (preg_match('/^\(dream-of-usenet\.info\) - \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
			else if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//>>> CREATIVE COMMONS NZB <<< "dexter romweber duo-lookout" - File 1 of 9: "creative_commons_nzb_dexter_romweber_duo-lookout.rar" yEnc
			else if (preg_match('/^(>>> CREATIVE COMMONS NZB <<< ".+?" - File )\d+ of \d+: ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<<<usenet-space-cowboys.info>>>  <<<Powered by https://secretusenet.com>< "Justin_Bieber-Believe_Acoustic-2013-pLAN9_usenet-space-cowbys.info.rar" >< 4/6 (78.65 MB) >< 60.84 MB > yEnc
			else if (preg_match('/^(.+?usenet-space.+?Powered by.+? ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|").+? \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\) .+? \d+[.,]\d+ [kKmMgG][bB] .+?yEnc$/', $subject, $match))
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
			else if (preg_match('/^\(\d+(\/\d+\) ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d.+?B - (\d.+?B -)? yEnc$/', $subject, $match))
				return $match[1];
			//Divers (12/42) -"Juste.Pour.Rire.2013.Gala.JF.Mercier.FRENCH.720p.HDTV.x264-QuebecRules.part11.rar" yEnc
			//Par le chapeau (06/43) - "8C7D59F472E03.part04.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9 ]+ \()\d+(\/\d+\) - ?".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1].$match[2];
			//House.Hunters.International.S05E502.720p.hdtv.x264 [01/27] - "House.Hunters.International.S05E502.720p.hdtv.x264.nfo" yEnc
			//Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS - [01/33] - "Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS.par2" yEnc
			else if (preg_match('/^(Re: )?([a-zA-Z0-9._-]+([{}A-Z_]+)?( -)? \[)\d+(\/| of )\d+\]( -)? ".+?" yEnc$/', $subject, $match))
				return $match[2];
			//Silent Witness S15E02 Death has no dominion.par2 [01/44] - yEnc
			else if (preg_match('/^([a-zA-Z0-9 ]+)(\.part(\d+)?|\.rar)?(\.vol.+? |\.[A-Za-z0-9]{2,4} )\[\d+(\/\d+\] - yEnc)$/', $subject, $match))
				return $match[1].$match[5];
			//(bf1) [03/31] - "The.Block.AU.Sky.High.S07E61.WS.PDTV.XviD.BF1.part01.sfv" yEnc (1/1)
			else if (preg_match('/^\(bf1\) \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//[ TVPower ] - "Dexter.S07E10.720p.HDTV.x264-NLsubs.par2" yEnc
			//[ TVPower ] - [010/101] - "Desperate.Housewives.S08Disc2.NLsubs.part009.rar" yEnc
			else if (preg_match('/^(\[ [A-Za-z]+ \] - (\[\d+\/\d+\] - )?".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//[www.allyourbasearebelongtous.pw]-[WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams]-[03/69] "WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams.r00" - 1.58 GB - yEnc
			else if (preg_match('/^(\[.+?\]-\[.+?\]-\[)\d+\/\d+\] ".+?" - \d+([.,]\d+ [kKmMgG])?[bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(www.Thunder-News.org) >CD1< <Sponsored by Secretusenet> - "moovee-fastest.cda.par2" yEnc
			else if (preg_match('/^(\(www\..+?\) .+? <Sponsored.+?> - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//<<<Pitbull>>> usenet-space-cowboys.info <<<Powered by https://secretusenet.com>< "S05E03 Pack die Badehose ein_usenet-space-cowbys.info.par2" >< 01/10 (411,16 MB) >< 3,48 kB > yEnc
			else if (preg_match('/(\.info .+?Powered by .+?\.com ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") .+? \d+\/\d+ \(\d+[,.]\d+ [mMkKgG][bB]\) .+? yEnc$/', $subject, $match))
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
			if (preg_match('/town\.ag.+?download all our files with.+?www\..+?\.info.+? \[\d+(\/\d+\] - ".+?)(-sample)?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $subject, $match))
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
			else if (preg_match('/^\(bf1\) \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			$cleansubject = preg_replace('/((( \(\d\d\) -|(\d\d)? - \d\d\.|\d{4} \d\d -) | - \d\d-| \d\d\. [a-z]).+| \d\d of \d\d| \dof\d)\.mp3"?|(\(|\[|\s)\d{1,4}(\/|(\s|_)of(\s|_)|-)\d{1,4}(\)|\]|\s|$|:)|\(\d{1,3}\|\d{1,3}\)|[^\d]{4}-\d{1,3}-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s|\d{1,3} - of \d{1,3}/i', ' ', $subject);
			// File extensions.
			$cleansubject = preg_replace('/(-sample|-thumb)*(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")/i', ' ', $cleansubject);
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

			// If the subject is too similar to another because it is so short, try to extract non letters/numbers.
			if (strlen($cleansubject) <= 10 || preg_match('/^[-a-z0-9$ ]{1,7}yEnc$/i', $cleansubject))
			{
				$newname = preg_replace('/[a-z0-9]|(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[a-z0-9]{2,4}"|")/i', '', $subject);
				return $cleansubject.$newname;
			}
			else
				return $cleansubject;
		}
	}


	//
	//	Cleans a usenet subject before inserting, used for searchname. Also used for imports.
	//
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
			else if (preg_match('/^<TOWN> www\.town\.ag > sponsored by www\.ssl-news\.info > \(\d+\/\d+\) "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(1/9)<<<www.town.ag>>> sponsored by ssl-news.info<<<[HorribleSubs]_AIURA_-_01_[480p].mkv "[HorribleSubs]_AIURA_-_01_[480p].par2" yEnc
			else if (preg_match('/^\(\d+\/\d+\).+?www\.town\.ag.+?sponsored by (www\.)?ssl-news\.info<+?.+? "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[2];
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
			else if (preg_match('/^>+Hell-of-Usenet(\.org)?>+( -)? \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")( - \d+[.,]\d+ [kKmMgG][bB])? yEnc$/', $subject, $match))
				return $match[3];
			//1dbo1u5ce6182436yb2eo (001/105) "1dbo1u5ce6182436yb2eo.par2" yEnc
			else if (preg_match('/^([a-z0-9]{10,}) \(\d+\/\d+\) "[a-z0-9]{10,}\..+?" yEnc$/', $subject, $match))
				return $match[1];
			//<<<>>>kosova-shqip.eu<<< Deep SWG - 90s Club Megamix 2011 >>>kosova-shqip.eu<<<<<< - (2/4) - "Deep SWG - 90s Club Megamix 2011.rar" yEnc
			else if (preg_match('/^<<<>>>kosova-shqip\.eu<<< (.+?) >>>kosova-shqip.eu<<<<<< - \(\d+\/\d+\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<Have Fun> "Invader.German.2012.PAL.DVDR-MORTAL.nfo" SpongeBoZZ yEnc
			else if (preg_match('/^<Have Fun> "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") SpongeBoZZ yEnc$/', $subject, $match))
				return $match[1].$match[2];
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
			else if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			else if (preg_match('/^- "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			else if (preg_match('/^\(\d+\/\d+\) - .+? - "(.+?)( \(\d+\))?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(01/12) - "TransX - Living on a Video 1993.part01.rar" - 561,55 MB - TransX - Living on a Video 1993.[Lossless] Highh Quality yEnc
			//(59/81) "1973 .Lee.Jun.Fan.DVD9.untouched.z46" - 7,29 GB - Lee.Jun.Fan.sein.Film.DVD9.untouched yEnc
			else if (preg_match('/^\(\d+\/\d+\)( -)? ".+?" - \d+[,.]\d+ [mMkKgG][bB] - (.+?) yEnc$/', $subject, $match))
				return $match[2];
			//>>> www.lords-of-usenet.org <<<  "Der Schuh Des Manitu.par2" DVD5  [001/158] - 4,29 GB yEnc
			else if (preg_match('/^>>> www\.lords-of-usenet\.org <<<.+? "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") .+? \[\d+\/\d+\] - .+? yEnc$/', $subject, $match))
				return $match[1];
			//NEUES 4y - [@ usenet-4all.info - powered by ssl.news -] [5,58 GB] [002/120] "DovakinPack.part002.rar" yEnc
			//NEUES 4y (PW)  [@ usenet-4all.info - powered by ssl.news -] [7,05 GB] [014/152] "EngelsGleich.part014.rar" yEnc
			else if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+\/\d+\] "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[2];
			//Old Dad uppt   Die Schatzinsel Teil 1+Teil2  AC3 DVD Rip German XviD Wp 01/33] - "upp11.par2" yEnc
			//Old Dad uppt Scary Movie5 WEB RiP Line XviD German 01/24] - "Scary Movie 5.par2" yEnc
			else if (preg_match('/^([a-zA-Z0-9].+?\s{2,}|Old Dad uppt\s+)(.+?) \d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[2];
			//>>>  20,36 MB   "Winamp.Pro.v5.70.3392.Incl.Keygen-FFF.par2"   552 B yEnc
			//..:[DoAsYouLike]:..    9,64 MB    "Snooper 1.39.5.par2"    468 B yEnc
			else if (preg_match('/^.+?\s{2,}\d+[,.]\d+ [mMkKgG][bB]\s{2,}"(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")\s{2,}(\d+ B|\d+[,.]\d+ [mMkKgG][bB]) yEnc$/', $subject, $match))
				return$match[1];
			//(MKV - DVD - Rip - German - English - Italiano) - "CALIGULA (1982) UNCUT.sfv" yEnc
			else if (preg_match('/^\(.+?\) - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//"sre56565ztrtzuzi8inzufft.par2" yEnc
			else if (preg_match('/^"([a-z0-9]+)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			else if (preg_match('/^\(\d+\/\d+\) - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//(????) [02/71] - "Lasting Weep (1969-1971).part.par2" yEnc
			else if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//(01/59) "ThienSuChungQuy_II_E16.avi.001" - 1,49 GB - yEnc
			//(058/183) "LS_HoangChui_2xdvd5.part057.rar" - 8,36 GB -re yEnc
			else if (preg_match('/^\(\d+\/\d+\) "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] -(re)? yEnc$/', $subject, $match))
				return $match[1];
			//[AoU] Upload#00287 - [04/43] - "Upload-ZGT1-20130525.part03.rar" yEnc
			else if (preg_match('/^(\[[a-zA-Z]+\] .+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return$match[1];
			//(nate) [01/27] - "nate_light_13.05.23.par2" yEnc
			else if (preg_match('/^\([a-z]+\) \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//""Absolute Database Component for BCBuilder 4-6 MultiUser Edit 4.85.rar"" yEnc
			else if (preg_match('/^""(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")" yEnc$/', $subject, $match))
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
			else if (preg_match('/^\[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] .+? usenet-space.+?yEnc$/', $subject, $match))
				return $match[1];
			//(Neu bei Bitfighter vom 23-07-2013) - "01 - Sido - Bilder Im Kopf.mp3" yEnc
			else if (preg_match('/^\((.+?)\) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(2/8) "Mike.und.Molly.S01E22.Maennergespraeche.GERMAN.DL.DUBBED.720p.BluRay.x264-TVP.part1.rar" - 1023,92 MB - yEnc
			else if (preg_match('/^\(\d+\/\d+\) "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//4y (PW)   [@ usenet-4all.info - powered by ssl.news -] [27,35 GB] [001/118] "1f8867bb6f89491793d3.part001.rar" yEnc
			else if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+\/\d+\] "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			else if (preg_match('/^\(\d+\/\d+\)(\s+ -)? "([a-zA-Z0-9]+?)(\d+)?\..+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
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
			else if (preg_match('/^([A-Za-z0-9].+? \((19|20)\d\d\)) "\d{2}\. .+?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//"BB636.part14.rar" - (15/39) - yEnc
			else if (preg_match('/^"([a-zA-Z0-9]+)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \(\d+\/\d+\) - yEnc$/', $subject, $match))
				return $match[1];
			//Lutheria - FC Twente TV Special - Ze wilde op voetbal [16/49] - "Lutheria - FC Twente TV Special - Ze wilde op voetbal.part16.rar" yEnc
			else if (preg_match('/^([-a-zA-Z0-9 ]+) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//Pee Mak Prakanong - 2013 - Thailand - ENG Subs - "Pee Mak Prakanong.2013.part22.rar" yEnc
			else if (preg_match('/^([-a-zA-Z0-9 ]+) - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(????) [011/161] - "flynns-image-redux.part010.rar" yEnc
			//(Dgpc) [000/110] - "Teen Wolf - Seizoen.3 - Dvd.2 (NLsub).nzb" yEnc
			else if (preg_match('/^\(\?{4}|[a-zA-Z]+\) \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//("Massaladvd5Kilusadisc4S1.par2" - 4,55 GB -) "Massaladvd5Kilusadisc4S1.par2" - 4,55 GB - yEnc
			else if (preg_match('/^\("([a-z0-9A-Z]+).+?" - \d+[,.]\d+ [mMkKgG][bB] -\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//"par.4kW9beE.1.vol122+21.par2" yEnc
			else if (preg_match('/^"(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [01/19] - "Age.of.Dinosaurs.German.AC3.HDRip.x264-FuN.par2" yEnc
			//>>>>>Hell-of-Usenet.org>>>>> - [01/35] - "Female.Agents.German.2008.AC3.DVDRip.XviD.iNTERNAL-VideoStar.par2" yEnc
			else if (preg_match('/^.+?\.(info|org)>+ - \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[2];
			//[010/101] - "Bf56a8aR-20743f8D-Vf7a11fD-d7c6c0.part09.rar" yEnc
			//[1/9] - "fdbvgdfbdfb.part.par2" yEnc
			else if (preg_match('/^\[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//[LB] - [063/112] - "RVL-GISSFBD.part063.rar" yEnc
			else if (preg_match('/^\[[A-Z]+\] - \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			if (preg_match('/^\[\d+\]-\[.+?\]-\[.+?\]-\[ (.+?) \] ?- ?\[\d+\/\d+\] (- )?"(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			else if (preg_match('/^\(\d+\/\d+\) "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
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
			if (preg_match('/^Re: REQ: (.+? - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//Per REQ - "The.Wild.Wild.West.S03E11.The.Night.of.the.Cut-Throats.DVDRip.XVID-tz.par2" 512x384 [01/40] yEnc
			else if (preg_match('/^Per REQ - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") .+? \[\d+\/\d+\] yEnc$/', $subject, $match))
				return $match[1];
			//By req: "Dennis The Menace - 4x25 - Dennis and the Homing Pigeons.part05.rar" yEnc
			else if (preg_match('/^By req: "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//I Spy HQ DVDRips "I Spy - 3x26 Pinwheel.part10.rar" [13/22] yEnc
			else if (preg_match('/^[a-zA-Z ]+HQ DVDRips "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") \[\d+\/\d+\] yEnc$/', $subject, $match))
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
			else if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9.-]+S\d+E\d+ \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//"Batman - S1E13-The Thirteenth Hat.par2" yEnc
			//"The Munsters - 1x01 Munster Masquerade.part.par" HQ DVDRip[02/16] yEnc
			else if (preg_match('/^"(.+?)(\.avi|\.mkv)?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")( HQ DVDRip\[\d+\/\d+\])? yEnc$/', $subject, $match))
				return $match[1];
			//Re: Outside Edge series 1 - [01/20] - "Outside Edge S01.nfo" yEnc
			else if (preg_match('/^(Re: )?[a-zA-Z0-9]+ .+? series \d+ - \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[2];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.documentaries")
		{
			//#sterntuary - Alex Jones Radio Show - "05-03-2009_INFO_BAK_ALJ.nfo" yEnc
			if (preg_match('/^#sterntuary - (.+? - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.dvd")
		{
			//thnx to original poster [00/98] - "2669DFKKFD2008.nzb ` 2669DFKKFD2008 " yEnc
			if (preg_match('/^thnx to original poster \[\d+(\/\d+\] - ".+?)(\.part(\d+)?|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4})("| `).+? yEnc$/', $subject, $match))
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
			//<TOWN><www.town.ag > <download all our files with>>> www.ssl-news.info <<< > [01/28] - "TayTO-heyzo_hd_0317_full.par2" - 2,17 GB yEnc
			else if (preg_match('/^<TOWN><www\.town\.ag > <download all our files with>>> www\.ssl-news\.info <<< > \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - /', $subject, $match))
				return $match[1];
			//NihilCumsteR [1/8] - "Conysgirls.cumpilation.xxx.NihilCumsteR.par2" yEnc
			else if (preg_match('/^NihilCumsteR \[\d+\/\d+\] - "(.+?)NihilCumsteR\./', $subject, $match))
				return $match[1];
			//"Lesbian seductions 26.part.nzb" yEnc
			else if (preg_match('/^"(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
		else if ($groupName === "alt.binaries.highspeed")
		{
			//[03/61] - "www.realmom.info - xvid - xf-fatalmovecd1.r00" - 773,34 MB - yEnc
			if (preg_match('/^\[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
				return $match[1];
			//>ghost-of-usenet.org>The A-Team S01-S05(Folgen einzelnd ladbar)<Sponsored by Astinews< (1930/3217) "isd-ateamxvid-s04e21.r19" yEnc
			else if (preg_match('/^>ghost-of-usenet\.org>(.+?)\(.+?\).+? \(\d+\/\d+\) ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//www.usenet-town.com [Sponsored by Astinews] (103/103) "Intimate.Enemies.German.2007.AC3.[passwort protect].vol60+21.PAR2" yEnc
			else if (preg_match('/^www\..+? \[Sponsored.+?\] \(\d+\/\d+\) "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.inner-sanctum")
		{
			//ea17079f47de702eead5114038355a70 [1/9] - "00-da_morty_-_boondock_sampler_02-(tbr002)-web-2013-srg.m3u" yEnc
			if (preg_match('/^[a-fA-F0-9]+ \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.mojo")
		{
			//[17/61] - "www.realmom.info - xvid - xf-devilstomb.r14" - 773,11 MB - yEnc
			if (preg_match('/^\[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $subject, $match))
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
			else if (preg_match('/\.net <<<Partner von SSL-News\.info>>> - \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			if (preg_match('/\[\d+\]-\[.+?\]-\[.+?\]-\[ (.+?) \](-| )\[\d+\/\d+\]( -)? ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[42788]-[#altbin@EFNet]-[Full]- "margin-themasterb-xvid.par2" yEnc
			else if (preg_match('/^\[\d+\]-\[.+?\]-\[.+?\]- "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//[ Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE ]-[01/44] - "Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE.par2" yEnc
			//[ Admission.2013.720p.WEB-DL.DD5.1.H.264-HD4FUN ] - [01/82] - "Admission.2013.720p.WEB-DL.DD5.1.H.264-HD4FUN.nfo" yEnc
			else if (preg_match('/^\[ ([a-zA-Z0-9.-]+) \] ?- ?\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(????) [0/1] - "A.Good.Day.to.Die.Hard.2013.nzb" yEnc
			else if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//[xxxxx]-[#a.b.moovee@EFNet]-[ xxxxx ]-[02/66] - "tulob88.part01.rar" yEnc
			else if (preg_match('/^\[x+\]-\[.+?\]-\[ x+ \]-\[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//Groove.2000.iNTERNAL.DVDRip.XviD-UBiK - [001/111] - "ubik-groove-cd1.par2" yEnc
			//Antony.and.Cleopatra.1972.720p.WEB-DL.H264-brento -[35/57] - "Antony.and.Cleopatra.1972.720p.WEB-DL.AAC2.0.H.264-brento.part34.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9._-]+) - ?\[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//[133668] - p00okjiue34635xxzx$$Â£Â£zll-b.vol3+2.PAR2 - [005/118]  yEnc
			else if (preg_match('/^(\[\d+\] - [a-z0-9]+.+?)(\.part(\d+)?|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4}) - \[\d+\/\d+\]\s+yEnc$/', $subject, $match))
				return $match[1];
			//[134517]-[01/76] - "Lara Croft Tomb Raider 2001 720p BluRay DTS x264-RightSiZE.nfo" yEnc
			else if (preg_match('/^\[\d+\]-\[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			//(www.Thunder-News.org) >CD2< <Sponsored by Secretusenet> - "exvid-emma-cd2.par2" yEnc
			if (preg_match('/^\(www\.Thunder-News\.org\) .+? - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//Movieland Post Voor FTN - [01/43] - "movieland0560.par2" yEnc
			if (preg_match('/^[a-zA-Z ]+Post Voor FTN - \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//Disney short films collection by mayhem masta"1923 - Alice's Wonderland.vol15+7.par2" yEnc
			else if (preg_match('/.+?by mayhem masta"(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
			if (preg_match('/.+? \((360|480|720|1080)p\|.+? ~bY .+? \[\d+\/\d+\] - "(.+?\[[A-F0-9]+\].+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[2];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.multimedia.anime.highspeed")
		{
			//High School DxD New 01 (480p|.avi|xvid|mp3) ~bY Hatsuyuki [01/18] - "[Hatsuyuki]_High_School_DxD_New_01_[848x480][76B2BB8C].avi.001" yEnc
			if (preg_match('/.+? \((360|480|720|1080)p\|.+? ~bY .+? \[\d+\/\d+\] - "(.+?\[[A-F0-9]+\].+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[2];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.multimedia.documentaries")
		{
			//"Universe S4E08.part40.rar" - [41 of 76 - 10013 kb] yEnc
			if (preg_match('/^"(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \[\d+ of \d+ - \d+ [kKmMgG][bB]\] yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.multimedia.scifi")
		{
			//some m4vs - "SilverHawks_v1eps01_The Origin Story.par2" yEnc
			if (preg_match('/^some m4vs - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.ps3")
		{
			//[4197] [036/103] - "ant-mgstlcd2.r34" yEnc
			if (preg_match('/^(\[\d+\] )\[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1].$match[2];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.sounds.lossless")
		{
			//http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
			if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.sounds.mp3")
		{
			//(dream-of-usenet.info) - [04/15] - "Enya-And_Winter_Came...-2008.part2.rar" yEnc
			if (preg_match('/^\(dream-of-usenet\.info\) - \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
			else if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//>>> CREATIVE COMMONS NZB <<< "dexter romweber duo-lookout" - File 1 of 9: "creative_commons_nzb_dexter_romweber_duo-lookout.rar" yEnc
			else if (preg_match('/^>>> CREATIVE COMMONS NZB <<< "(.+?)" - File \d+ of \d+: ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//<<<usenet-space-cowboys.info>>>  <<<Powered by https://secretusenet.com>< "Justin_Bieber-Believe_Acoustic-2013-pLAN9_usenet-space-cowbys.info.rar" >< 4/6 (78.65 MB) >< 60.84 MB > yEnc
			else if (preg_match('/^.+?usenet-space.+?Powered by.+? "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|").+? \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\) .+? \d+[.,]\d+ [kKmMgG][bB] .+?yEnc$/', $subject, $match))
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
			else if (preg_match('/^\(\d+\/\d+\) "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d.+?B - (\d.+?B -)? yEnc$/', $subject, $match))
				return $match[1];
			//Divers (12/42) -"Juste.Pour.Rire.2013.Gala.JF.Mercier.FRENCH.720p.HDTV.x264-QuebecRules.part11.rar" yEnc
			//Par le chapeau (06/43) - "8C7D59F472E03.part04.rar" yEnc
			else if (preg_match('/^([a-zA-Z0-9 ]+) \(\d+\/\d+\) - ?".+?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//House.Hunters.International.S05E502.720p.hdtv.x264 [01/27] - "House.Hunters.International.S05E502.720p.hdtv.x264.nfo" yEnc
			//Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS - [01/33] - "Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS.par2" yEnc
			else if (preg_match('/^(Re: )?([a-zA-Z0-9._-]+)([{}A-Z_]+)?( -)? \[\d+(\/| of )\d+\]( -)? ".+?" yEnc$/', $subject, $match))
				return $match[2];
			//Silent Witness S15E02 Death has no dominion.par2 [01/44] - yEnc
			else if (preg_match('/^([a-zA-Z0-9 ]+)(\.part(\d+)?|\.rar)?(\.vol.+? |\.[A-Za-z0-9]{2,4} )\[\d+\/\d+\] - yEnc$/', $subject, $match))
				return $match[1];
			//(bf1) [03/31] - "The.Block.AU.Sky.High.S07E61.WS.PDTV.XviD.BF1.part01.sfv" yEnc (1/1)
			else if (preg_match('/^\(bf1\) \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//[ TVPower ] - "Dexter.S07E10.720p.HDTV.x264-NLsubs.par2" yEnc
			//[ TVPower ] - [010/101] - "Desperate.Housewives.S08Disc2.NLsubs.part009.rar" yEnc
			else if (preg_match('/^\[ [A-Za-z]+ \] - (\[\d+\/\d+\] - )?"(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[2];
			//[www.allyourbasearebelongtous.pw]-[WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams]-[03/69] "WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams.r00" - 1.58 GB - yEnc
			else if (preg_match('/^\[.+?\]-\[(.+?)\]-\[\d+\/\d+\] ".+?" - \d+([.,]\d+ [kKmMgG])?[bB] - yEnc$/', $subject, $match))
				return $match[1];
			//(www.Thunder-News.org) >CD1< <Sponsored by Secretusenet> - "moovee-fastest.cda.par2" yEnc
			else if (preg_match('/^\(www\..+?\) .+? <Sponsored.+?> - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
				return $match[1];
			//<<<Pitbull>>> usenet-space-cowboys.info <<<Powered by https://secretusenet.com>< "S05E03 Pack die Badehose ein_usenet-space-cowbys.info.par2" >< 01/10 (411,16 MB) >< 3,48 kB > yEnc
			else if (preg_match('/\.info .+?Powered by .+?\.com "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") .+? \d+\/\d+ \(\d+[,.]\d+ [mMkKgG][bB]\) .+? yEnc$/', $subject, $match))
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
			if (preg_match('/town\.ag.+?download all our files with.+?www\..+?\.info.+? \[\d+\/\d+\] - "(.+?)(-sample)?(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") - \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $subject, $match))
				return $match[1];
			else
				return $this->releaseCleanerHelper($subject);
		}
		else if ($groupName === "alt.binaries.tv")
		{
			//Borgen.2x02.A.Bruxelles.Non.Ti.Sentono.Urlare.ITA.BDMux.x264-NovaRip [02/22] - "borgen.2x02.ita.bdmux.x264-novarip.par2" yEnc
			if (preg_match('/^([a-zA-Z0-9.-]+) \[\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
				return $match[1];
			//(bf1) [03/31] - "The.Block.AU.Sky.High.S07E56.WS.PDTV.XviD.BF1.part01.sfv" yEnc
			else if (preg_match('/^\(bf1\) \[\d+\/\d+\] - "(.+?)(\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $subject, $match))
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
		else
			return $this->releaseCleanerHelper($subject);
	}
	
	public function releaseCleanerHelper($subject)
	{
		/*//File and part count.
		$cleanerName = preg_replace('/(File ?)?(\(|\[|\s|Part)\d{1,4}(\/|(\s|_)of(\s|_)|-)\d{1,4}(\)|\]|\s|$|:)|\(\d{1,3}\|\d{1,3}\)|-\d{1,3}-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s|\d{1,3} - of \d{1,3}/i', ' ', $subject);
		//Size.
		$cleanerName = preg_replace('/\d+[.,]\d+ [KMG]B|\d+[KMG]B|\d+ bytes?|[- ]?\d+[.,]?\d+ [KMG]?B -( yEnc)?| \(\d+[.,]\d+ [KMG]B\) |\(\d+K\) yEnc|yEnc \d+k$/i', ' ', $cleanerName);
		//Extensions.
		$cleanerName = preg_replace('/(\.avi|\.part(\d+)?|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")/', '"', $cleanerName);
		$cleanerName = preg_replace('/ [a-z0-9]+\.jpg |\.avi\.\d+ |((\d{1,3})?\.part(\d{1,5})?|\d{1,5}|sample)?\.(7z|\d{3}(?=")|avi|epub|idx|iso|jpg|m4a|mds|mkv|mobi|mp4|nfo|nzb|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|par\s?2|zip|z{2}) |(\s|(\d{2,3})?-)\d{2,3}\.mp3 |\d{2,3}\.pdf |\.part\d{1,4}\./i', ' ', $cleanerName);
		//Remove multiple chars.
		$cleanerName = preg_replace('/-{2,}|~{2,}|`{2,}|!{2,}|@{2,}|#{2,}|\${2,}|%{2,}|\^{2,}|&{2,}|\*{2,}|\({2,}|\){2,}|_{2,}|\+{2,}|={2,}|\{{2,}|\}{2,}|\[{2,}|\]{2,}|\|{2,}|\\{2,}|:{2,}|;{2,}|"{2,}|\'{2,}|<{2,}|>{2,}|,{2,}|\.{2,}|\?{2,}|\/{2,}/i', ' ', $cleanerName);
		//Remove some starting stuff.
		$cleanerName = preg_replace('/^(ATTN:|Re: ?|(As ?)?Req?(:| ?-)?)|^[^a-z0-9]+/i', ' ', $cleanerName);
		//Multiple spaces.*/
		$cleanerName = preg_replace('/(- )?yEnc$/', '', $subject);
		$cleanerName = trim(preg_replace('/\s\s+/', ' ', $cleanerName));
		
		/*//Extensions.
		$cleanerName = preg_replace('/ [a-z0-9]+\.jpg|((\d{1,3})?\.part(\d{1,5})?|\d{1,5}|sample)?\.(7z|\d{3}(?=(\s|"))|avi|epub|idx|iso|jpg|m4a|mds|mkv|mobi|mp4|nfo|nzb|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|par(\s?2|")|zip|z{2})"?|(\s|(\d{2,3})?-)\d{2,3}\.mp3|\d{2,3}\.pdf|yEnc|\.part\d{1,4}\./i', ' ', $cleanerName);
		//Books + Music.
		$cleanerName = preg_replace('/((\d{1,2}-\d{1-2})?-[a-z0-9]+)?\.scr|Ebook-[a-z0-9]+|\((\d+ )ebooks\)|\(ebooks[-._ ](collection|\d+)\)|\([a-z]{3,9} \d{1,2},? 20\d\d\)|\(\d{1,2} [a-z]{3,9} 20\d\d|\[ATTN:.+?\]|ATTN: [a-z]{3,13} |ATTN:(macserv 100|Test)|ATTN: .+? - ("|:)|ATTN .+?:|\((bad conversion|Day\d+\/\?|djvu|fixed|pdb|tif)\)|by [a-z0-9]{3,15}$|^Dutch(:| )|enjoy!|(\*| )enjoy(\*| )|^ePub |\(EPUB\+MOBI\)|(Flood )?Please - All I have|isbn\d+|New Ebooks \d{1,2} [a-z]{3,9} (19|20)\d\d( part \d)?|\[(MF|Ssc)\]|^New Version( - .+? - )?|^NEW( [a-z]+( Paranormal Romance|( [a-z]+)?:|,| ))?(?![-._ ]York)|[-._ ]NMR \d{2,3}|( |\[)NMR( |\])|\[op.+?\d\]|\[Orion_Me\]|\[ORLY\]|Please\.\.\.|R4 - Book of the Week|Re: |READNFO|Req: |Req\.|!<-- REQ:|^Request|Requesting|Should I continue posting these collections\?|\[Team [a-z0-9]+\]|[-._ ](Thanks|TIA!)[-._ ]|\(v\.?\d+\.\d+[a-z]?\)|par2 set|\.(j|f|m|a|s|o|n|d)[a-z]{2,8}\.20\d\d/i', ' ', $cleanerName);
		//Unwanted stuff.
		$cleanerName = preg_replace('/sample[ "]?$|"sample|\(\?{4}\)|\[AoU\]|AsianDVDClub\.org|AutoRarPar\d{1,5}|brothers-of-usenet\.(info|net)(\/\.net)?|~bY ([a-z]{3,15}|c-w)|By request|DVD-Freak|Ew-Free-Usenet-\d{1,5}|for\.usenet4ever\.info|ghost-of-usenet.org<<|GOU<<|(http:\/\/www\.)?friends-4u\.nl|\[\d+\]-\[abgxEFNET\]-|\[[a-z\d]+\]-\[[a-z\d]+\]-\[FULL\]-|\[\d{3,}\]-\[FULL\]-\[(a\.b| abgx).+?\]|\[\d+\]|-\[FULL\].+?#a\.b[\w.#!@$%^&*(){}|\\:"\';<>,?~` ]+\]|Lords-of-Usenet(\] <> presents)?|nzbcave\.co\.uk( VIP)?|(Partner (of|von) )?SSL-News\.info>> presents|\/ post: |powere?d by (4ux(\.n\)?l)?|the usenet)|(www\.)?ssl-news(\.info)?|SSL - News\.Info|usenet-piraten\.info|-\s\[.+?\]\s<>\spresents|<.+?https:\/\/secretusenet\.com>|SECTIONED brings you|team-hush\.org\/| TiMnZb |<TOWN>|www\.binnfo\.in|www\.dreameplace\.biz|wwwworld\.me|www\.town\.ag|(Draak48|Egbert47|jipenjans|Taima) post voor u op|Dik Trom post voor|Sponsored\.by\.SecretUsenet\.com|(::::)?UR-powered by SecretUsenet.com(::::)?|usenet4ever\.info|(www\.)?usenet-4all\.info|www\.torentz\.3xforum\.ro|usenet-space-cowboys\.info|> USC <|SecretUsenet\.com|Thanks to OP|\] und \[|www\.michael-kramer\.com|(http:\\\\\\\\)?www(\.| )[a-z0-9]+(\.| )(co(\.| )cc|com|info|net|org)|zoekt nog posters\/spotters|>> presents|Z\[?NZB\]?(\.|_)wz(\.|_)cz|partner[-._ ]of([-._ ]www)?/i', ' ', $cleanerName);
		//Change [pw] to passworded.
		$cleanerName = str_replace(array('[pw]', '[PW]', ' PW ', '(Password)'), ' PASSWORDED ', $cleanerName);
		//Replaces some characters with 1 space.
		$cleanerName = str_replace(array(".", "_", '-', "|", "<", ">", '"', "=", '[', "]", "(", ")", "{", "}", "*", ";", ":", ",", "'", "~", "/", "&", "+"), " ", $cleanerName);
		//Replace multiple chars with 1 space
		$cleanerName = trim(preg_replace('/\s\s+/i', ' ', $cleanerName));
		//Remove the double name.
		$cleanerName = implode(' ', array_intersect_key(explode(' ', $cleanerName), array_unique(array_map('strtolower', explode(' ', $cleanerName)))));*/

		if (empty($cleanerName)) {return $subject;}
		else {return $cleanerName;}
	}

	//
	//	Cleans release name for the namefixer class.
	//
	public function fixerCleaner($name)
	{
		//Extensions.
		$cleanerName = preg_replace('/ [a-z0-9]+\.jpg|((\d{1,3})?\.part(\d{1,5})?|\d{1,5}|sample)?\.(7z|\d{3}(?=(\s|"))|avi|epub|idx|iso|jpg|m4a|mds|mkv|mobi|mp4|nfo|nzb|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|par(\s?2|")|zip|z{2})"?|(\s|(\d{2,3})?-)\d{2,3}\.mp3|\d{2,3}\.pdf|yEnc|\.part\d{1,4}\./i', ' ', $name);
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
