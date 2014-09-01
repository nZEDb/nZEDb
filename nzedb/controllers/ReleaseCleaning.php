<?php
/*
 * Cleans names for releases/imports/namefixer.
 * Names of group functions should match between CollectionsCleaning and this file
 */

use nzedb\db\Settings;

class ReleaseCleaning
{
	/**
	 * @var string
	 */
	public $subject = '';

	/**
	 * @var string
	 */
	public $groupName = '';

	/**
	 * @var string
	 */
	public $size = '';

	/**
	 * @var string
	 */
	public $fromName = '';

	/**
	 * @var string
	 */
	public $e0;

	/**
	 * @var string
	 */
	public $e1;

	/**
	 * @var string
	 */
	public $e2;

	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @param nzedb\db\Settings $settings
	 */
	public function __construct($settings = null)
	{
		// Extensions.
		$this->e0 = CollectionsCleaning::regexFileExtensionString;
		$this->e1 = CollectionsCleaning::regexFileExtensionString . CollectionsCleaning::regexEndString;
		$this->e2 = CollectionsCleaning::regexFileExtensionString . CollectionsCleaning::regexSizeString . CollectionsCleaning::regexEndString;
		$this->pdo = ($settings instanceof Settings ? $settings : new Settings());
	}

	public function releaseCleaner($subject, $fromName, $size, $groupName, $usepre = false)
	{
		$match = $matches = array();
		$this->groupName = $groupName;
		$this->subject = $subject;
		$this->fromName = $fromName;
		$this->size = $size;
		// Get pre style name from releases.name
		if (preg_match_all('/([\w\(\)]+[\s\._-]([\w\(\)]+[\s\._-])+[\w\(\)]+-\w+)/', $this->subject, $matches)) {
			foreach ($matches as $match) {
				foreach ($match as $val) {
					$title = $this->pdo->queryOneRow("SELECT title, id from predb WHERE title = " . $this->pdo->escapeString(trim($val)));
					if ($title !== false) {
						return array(
							"cleansubject" => $title['title'],
							"properlynamed" => true,
							"increment" => false,
							"predb" => $title['id'],
							"requestid" => false
						);
					}
				}
			}
		}
		// Get pre style name from requestid
		if (preg_match('/^\[ ?(\d{4,6}) ?\]/', $this->subject, $match) ||
			preg_match('/^REQ\s*(\d{4,6})/i', $this->subject, $match) ||
			preg_match('/^(\d{4,6})-\d{1}\[/', $this->subject, $match) ||
			preg_match('/(\d{4,6}) -/', $this->subject, $match)
		) {
			$title = $this->pdo->queryOneRow(
				sprintf(
					'SELECT p.title , p.id from predb p INNER JOIN groups g on g.id = p.group_id
								WHERE p.requestid = %d and g.name = %s', $match[1], $this->pdo->escapeString($this->groupName)
				)
			);
			//check for predb title matches against other groups where it matches relative size / fromname
			//known crossposted requests only atm
			$reqGname = '';
			switch ($this->groupName) {
				case 'alt.binaries.etc':
					if ($this->fromName === 'kingofpr0n (brian@iamking.ws)') {
						$reqGname = 'alt.binaries.teevee';
					}
					break;
				case 'alt.binaries.mom':
					if ($this->fromName === 'Yenc@power-post.org (Yenc-PP-A&A)' || $this->fromName === 'yEncBin@Poster.com (yEncBin)') {
						$reqGname = 'alt.binaries.moovee';
					}
					break;
				case 'alt.binaries.hdtv.x264':
					if ($this->fromName === 'moovee@4u.tv (moovee)') {
						$reqGname = 'alt.binaries.moovee';
					}
					break;
			}
			if ($title === false && !empty($reqGname)) {
				$title = $this->pdo->queryOneRow(
					sprintf(
						"SELECT p.title as title, p.id as id from predb p INNER JOIN groups g on g.id = p.group_id
									WHERE p.requestid = %d and g.name = %s", $match[1], $this->pdo->escapeString($reqGname)
					)
				);
			}
			if ($title !== false) {
				return array(
					"cleansubject" => $title['title'],
					"properlynamed" => true,
					"increment" => false,
					"predb" => $title['id'],
					"requestid" => true
				);
			}
		}
		if ($usepre === true) {
			return false;
		}
		//if www.town.ag releases check against generic_town regexes
		if (preg_match('/www\.town\.ag/i', $this->subject)) {
			return $this->generic_town();
		}
		switch ($groupName) {
			case 'alt.binaries.0day.stuffz':
				return $this->_0daystuffz();
			case 'alt.binaries.3d':
				return $this->_3d();
			case 'alt.binaries.anime':
				return $this->anime();
			case 'alt.binaries.ath':
				return $this->ath();
			case 'alt.binaries.audio.warez':
				return $this->audio_warez();
			case 'alt.binaries.b4e':
				return $this->b4e();
			case 'alt.binaries.barbarella':
				return $this->barbarella();
			case 'alt.binaries.big':
				return $this->big();
			case 'alt.binaries.bloaf':
				return $this->bloaf();
			case 'alt.binaries.blu-ray':
				return $this->blu_ray();
			case 'alt.binaries.boneless':
				return $this->boneless();
			case 'alt.binaries.british.drama':
				return $this->british_drama();
			case 'alt.binaries.bungabunga':
				return $this->bungabunga();
			case 'alt.binaries.cavebox':
				return $this->cavebox();
			case 'alt.binaries.cats':
				return $this->cats();
			case 'alt.binaries.cbt':
				return $this->cbt();
			case 'alt.binaries.cbts':
				return $this->cbts();
			case 'alt.binaries.cd.image':
				return $this->cd_image();
			case 'alt.binaries.cd.lossless':
				return $this->cd_lossless();
			case 'alt.binaries.chello':
				return $this->chello();
			case 'alt.binaries.classic.tv.shows':
				return $this->classic_tv_shows();
			case 'alt.binaries.comics':
				return $this->comics();
			case 'alt.binaries.comics.dcp':
				return $this->comics_dcp();
			case 'alt.binaries.comp':
				return $this->comp();
			case 'alt.binaries.cores':
				return $this->cores();
			case 'alt.binaries.console.ps3':
				return $this->console_ps3();
			case 'alt.binaries.dc':
				return $this->dc();
			case 'alt.binaries.divx.french':
				return $this->divx_french();
			case 'alt.binaries.documentaries':
				return $this->documentaries();
			case 'alt.binaries.dvd':
				return $this->dvd();
			case 'alt.binaries.dvd.movies':
				return $this->dvd_movies();
			case 'alt.binaries.dvdr':
				return $this->dvdr();
			case 'alt.binaries.dvd-german':
				return $this->dvd_german();
			case 'alt.binaries.dvd-r':
				return $this->dvd_r();
			case 'alt.binaries.ebook':
				return $this->ebook();
			case 'alt.binaries.e-book':
				return $this->e_book();
			case 'alt.binaries.e-book.flood':
				return $this->e_book_flood();
			case 'alt.binaries.e-book.magazines':
				return $this->ebook_magazines();
			case 'alt.binaries.e-book.technical':
				return $this->ebook_technical();
			case 'alt.binaries.e-book.rpg':
				return $this->e_book_rpg();
			case 'alt.binaries.erotica':
				return $this->erotica();
			case 'alt.binaries.etc':
				return $this->etc();
			case 'alt.binaries.ftn':
				return $this->ftn();
			case 'alt.binaries.fz':
				return $this->fz();
			case 'alt.binaries.game':
				return $this->game();
			case 'alt.binaries.games':
				return $this->games();
			case 'alt.binaries.games.dox':
				return $this->games_dox();
			case 'alt.binaries.games.xbox360':
				return $this->games_xbox360();
			case 'alt.binaries.german.movies':
				return $this->german_movies();
			case 'alt.binaries.ghosts':
				return $this->ghosts();
			case 'alt.binaries.hdtv':
				return $this->hdtv();
			case 'alt.binaries.hdtv.x264':
				return $this->hdtv_x264();
			case 'alt.binaries.highspeed':
				return $this->highspeed();
			case 'alt.binaries.inner-sanctum':
				return $this->inner_sanctum();
			case 'alt.binaries.mojo':
				return $this->mojo();
			case 'alt.binaries.mom':
				return $this->mom();
			case 'alt.binaries.moovee':
				return $this->moovee();
			case 'alt.binaries.movies':
				return $this->movies();
			case 'alt.binaries.movies.divx':
				return $this->movies_divx();
			case 'alt.binaries.movies.x264':
				return $this->movies_x264();
			case 'alt.binaries.mp3':
				return $this->mp3();
			case 'alt.binaries.mp3.complete_cd':
				return $this->mp3_complete_cd();
			case 'alt.binaries.mp3.full_albums':
				return $this->mp3_full_albums();
			case 'alt.binaries.multimedia':
				return $this->multimedia();
			case 'alt.binaries.multimedia.anime':
				return $this->multimedia_anime();
			case 'alt.binaries.multimedia.anime.highspeed':
				return $this->multimedia_anime_highspeed();
			case 'alt.binaries.multimedia.documentaries':
				return $this->multimedia_documentaries();
			case 'alt.binaries.multimedia.scifi':
				return $this->multimedia_scifi();
			case 'alt.binaries.music':
				return $this->music();
			case 'alt.binaries.music.mp3':
				return $this->music_mp3();
			case 'alt.binaries.multimedia.vintage-film.pre-1960':
				return $this->multimedia_vintage_film_pre_1960();
			case 'alt.binaries.nl':
				return $this->nl();
			case 'alt.binaries.pictures.erotica.anime':
				return $this->pictures_erotica_anime();
			case 'alt.binaries.ps3':
				return $this->ps3();
			case 'alt.binaries.series.tv.french':
				return $this->series_tv_french();
			case 'alt.binaries.sony.psp':
				return $this->sony_psp();
			case 'alt.binaries.sound.mp3':
				return $this->sound_mp3();
			case 'alt.binaries.sound.mp3.complete_cd':
				return $this->sound_mp3_complete_cd();
			case 'alt.binaries.sounds.flac':
				return $this->sounds_flac();
			case 'alt.binaries.sounds.lossless':
				return $this->sounds_lossless();
			case 'alt.binaries.sounds.mp3':
				return $this->sounds_mp3();
			case 'alt.binaries.sounds.mp3.complete_cd':
				return $this->sounds_mp3_complete_cd();
			case 'alt.binaries.sounds.mp3.dance':
				return $this->sounds_mp3_dance();
			case 'alt.binaries.sounds.mp3.electronic':
				return $this->sounds_mp3_electronic();
			case 'alt.binaries.teevee':
				return $this->teevee();
			case 'alt.binaries.test':
				return $this->test();
			case 'alt.binaries.town':
				return $this->town();
			case 'alt.binaries.town.cine':
				return $this->town_cine();
			case 'alt.binaries.town.xxx':
				return $this->town_xxx();
			case 'alt.binaries.tun':
				return $this->tun();
			case 'alt.binaries.tv':
				return $this->tv();
			case 'alt.binaries.tvseries':
				return $this->tvseries();
			case 'alt.binaries.games.wii':
				return $this->games_wii();
			case 'alt.binaries.tv.deutsch':
				return $this->tv_deutsch();
			case 'alt.binaries.u4e':
				return $this->u4e();
			case 'alt.binaries.u-4all':
				return $this->u_4all();
			case 'alt.binaries.usenetrevolution':
				return $this->usenetrevolution();
			case 'alt.binaries.warez':
				return $this->warez();
			case 'alt.binaries.warez.0-day':
				return $this->warez_0_day();
			case 'alt.binaries.wii':
				return $this->wii();
			case 'alt.binaries.wii.gamez':
				return $this->wii_gamez();
			case 'alt.binaries.worms':
				return $this->worms();
			case 'alt.binaries.x':
				return $this->x();
			case 'alt.binaries.x264':
				return $this->x264();
			case 'alt.binaries.xbox360':
				return $this->xbox360();
			case 'dk.binaer.tv':
				return $this->dk_tv();
			default:
				return $this->generic();
		}
	}

	public function _0daystuffz()
	{
		//ArcSoft.TotalMedia.Theatre.v5.0.1.87-Lz0 - [08/35] - "ArcSoft.TotalMedia.Theatre.v5.0.1.87-Lz0.vol43+09.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //rld-tcavu1 [5/6] - "rld-tcavu1.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(DVD Shrink.ss) [1/1] - "DVD Shrink.ss.rar" yEnc
		if (preg_match('/^\((.+?)\) \[\d+(\/\d+] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //WinASO.Registry.Optimizer.4.8.0.0(1/4) - "WinASO_RO_v4.8.0.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?)\(\d+\/\d+\) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function _3d()
	{
		//(084/113) "The Little Mermaid - 3D HSBS (1989) 1080p BluRay - Multi - P3n6u1n.part082.rar" - 10.56 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //"pandas.3d.back.to.the.wild.h-sbs.fs-3d.r22" - 1.55 GB - yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function anime()
	{
		//([AST] One Piece Episode 301-350 [720p]) [007/340] - "One Piece episode 301-350.part006.rar" yEnc
		if (preg_match('/^\((\[.+?\] .+?)\) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[REPOST][ New Doraemon 2013.05.03 Episode 328 (TV Asahi) 1080i HDTV MPEG2 AAC-DoraClub.org ] [35/61] - "doraclub.org-doraemon-20130503-b8de1f8e.r32" yEnc
		if (preg_match('/^\[.+?\]\[ (.+?) \] \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[De.us] Suzumiya Haruhi no Shoushitsu (1920x1080 h.264 Dual-Audio FLAC 10-bit) [017CB24D] [000/357] - "[De.us] Suzumiya Haruhi no Shoushitsu (1920x1080 h.264 Dual-Audio FLAC 10-bit) [017CB24D].nzb" yEnc
		if (preg_match('/^\[.+?\] (.+?) \[[A-F0-9]+\] \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[eraser] Ghost in the Shell ARISE - border_1 Ghost Pain (BD 720p Hi444PP LC-AAC Stereo) - [01/65] - "[eraser] Ghost in the Shell ARISE - border_1 Ghost Pain (BD 720p Hi444PP LC-AAC Stereo) .md5" yEnc
		if (preg_match('/^\[.+?\] (.+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/27) - Maid.Sama.Jap.dubbed.german.english.subbed - "01 Misaki ist eine Maid!.divx" - 6,44 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\) - (.+?) - ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ New Doraemon 2013.06.14 Episode 334 (TV Asahi) 1080i HDTV MPEG2 AAC-DoraClub.org ] [01/60] - "doraclub.org-doraemon-20130614-fae28cec.nfo" yEnc
		if (preg_match('/^\[ (.+?) \] \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Overman King Gainer [Dual audio, EngSub] Exiled Destiny - [002/149] - "Overman King Gainer.part001.rar" yEnc
		if (preg_match('/^(.+? \[Dual [aA]udio, EngSub\] .+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ ANIME ] [3/5] - "[HorribleSubs] Ai-Mai-Mi - Mousou Catastrophie - 04 [480p].part2.rar" - 26,44 MB yEnc
		if (preg_match('/^.+?-\[\s*ANIME\s*\] \[\d+\/\d+\] - "(.*)" - \d+[,.]\d+ [mMkKgG][bB][\s]*yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function ath()
	{
		//[3/3 Karel Gott - Die Biene Maja Original MP3 Karel Gott - Die Biene Maja Original MP3.mp3.vol0+1.PAR2" yEnc
		if (preg_match('/^\[\d+\/\d+ ([a-zA-Z0-9]+ .+?)\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //8b33bf5960714efbe6cfcf13dd0f618f - (01/55) - "8b33bf5960714efbe6cfcf13dd0f618f.par2" yEnc
		if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}\..+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //nmlsrgnb - [04/37] - "htwlngmrstdsntdnh.part03.rar" yEnc
		if (preg_match('/^([a-z]+) - \[\d+\/\d+\] - "[a-z]+\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //>>>>>Hell-of-Usenet>>>>> - [01/33] - "Cassadaga Hier lebt der Teufel 2011 German AC3 DVDRip XViD iNTERNAL-VhV.par2" yEnc
		if (preg_match('/^>+Hell-of-Usenet(\.org)?>+( -)? \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '( - \d+[.,]\d+ [kKmMgG][bB])? yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //1dbo1u5ce6182436yb2eo (001/105) "1dbo1u5ce6182436yb2eo.par2" yEnc
		if (preg_match('/^([a-z0-9]{10,}) \(\d+\/\d+\) "[a-z0-9]{10,}\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<>>>kosova-shqip.eu<<< Deep SWG - 90s Club Megamix 2011 >>>kosova-shqip.eu<<<<<< - (2/4) - "Deep SWG - 90s Club Megamix 2011.rar" yEnc
		if (preg_match('/^<<<>>>kosova-shqip\.eu<<< (.+?) >>>kosova-shqip.eu<<<<<< - \(\d+\/\d+\) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<Have Fun> "Invader.German.2012.PAL.DVDR-MORTAL.nfo" SpongeBoZZ yEnc
		if (preg_match('/^<Have Fun> "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' SpongeBoZZ yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Old Dad uppt Taffe Mädels XivD LD HDTV Rip oben Kleine Einblendug German 01/43] - "Taffe Mädels.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?\s{2,}|Old Dad uppt\s+)(.+?) \d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(????) Rom.S02E09.Deus.Impeditio.Esuritori.Nullus Rom.S02E09.Deus.Impeditio.Esuritori.Nullus.avi.vol007+08.PAR2" yEnc
		if (preg_match('/^\(\?+\) (.+) .+?([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})" yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function audiobooks()
	{
		//WEB Griffin - [06/26] - "The Outlaws-Part06.mp3" yEnc
		//Re: WEB Griffin - [18/26] - "The Outlaws.par2" yEnc
		if (preg_match('/^(Re: )?([\w\s]+) - \[\d+\/\d+\] - "(.+)[\.-]([Pp]art|vol|jpg|sfv|mp3|nzb|nfo|rar).*" yEnc$/', $this->subject, $match)) {
			return $match[2] . ", " . $match[3];
		}
		//WEB Griffin - [06/26] - "The Outlaws-Part06.mp3" yEnc
		//Re: WEB Griffin - [18/26] - "The Outlaws.par2" yEnc
		if (preg_match('/^(Re: )?([\w\s]+) - \[\d+\/\d+\] - "(.+)[\.-]([Pp]ar2).*" yEnc$/', $this->subject, $match)) {
			return $match[2] . ", " . $match[3];
		} //[05/13] - "Into the Fire-Part03.mp3" yEnc
		if (preg_match('/^\[\d+\/\d+\] - "(.+)-Part\d+\..+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[Repost - Original Damaged]  [35/86] - "Mark Twain - Personal Recollections of Joan of Arc Vol 1 and Vol 2 - 33 - 32 - Tinsel Trappings of Nobility.mp3" yEnc
		if (preg_match('/^\[Repost.+\]\s+\[\d+\/\d+\] - "(.+)\.(part|vol|jpg|sfv|mp3|nzb|nfo|rar).*" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[Repost - Original Damaged]  [35/86] - "Mark Twain - Personal Recollections of Joan of Arc Vol 1 and Vol 2 - 33 - 32 - Tinsel Trappings of Nobility.mp3" yEnc
		if (preg_match('/^\[Repost.+\]\s+\[\d+\/\d+\] - "(.+)\.(par2).*" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [12/19] - "Notorious Nineteen.part10.rar" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - "(.+)\.(part|vol|jpg|sfv|mp3|nzb|nfo|rar).*" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [12/19] - "Notorious Nineteen.part10.rar" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - "(.+)\.(par2).*" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(08/10) "The Vampire Diaries - Kampen.vol63+15.par2" - 313,91 MB yEnc
		if (preg_match('/^\(\d+\/\d+\) "(.+)[\.-](part|vol|jpg|sfv|mp3|nzb|nfo|rar).*" - \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/10) "The Vampire Diaries - Raseriet.par2" - 296,93 MB yEnc
		if (preg_match('/^(Re: )?\(\d+\/\d+\) "(.+)[\.-](par2).*" - \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //Margaret Weis - Dragonlance Kr?niker Bind 1-6 [Danish AudioBook] [02/18] - "DL-CRON.r00" yEnc
		if (preg_match('/^(.+) \[Danish.+\] \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //David Baldacci - The Forgotten 128 kbps stereo [03/24] - "The Forgotten 01.m4b" yEnc
		if (preg_match('/^([\w\s\d-]+) \d+ kbps stereo \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//ASOS0010 [03/40] - "ASOS.r01" yEnc
		//FFTT0010 (01/22) "FFTT.r10" yEnc
		if (preg_match('/(\w{4}\d{4}) [\[\(]\d+\/\d+[\]\)][ -]+"\w{4}\..+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Christopher Moore - Practrcal Demonkeeping (1992) [New Post] [04/15] "Christopher Moore - Practical Demonkeeping 01.mp3" yEnc
		if (preg_match('/^(.+) \[New Post\] \[\d+\/\d+\] ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Christopher Moore - Coyote Blue (1994) NMR [04/17] "Christopher Moore - Coyote Blue  01.mp3" yEnc
		if (preg_match('/^(.+) NMR \[\d+\/\d+\] ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Christopher Moore - Island of the Sequined Love Nun (1997) [09/19] "Christopher Moore - Island of the Sequined Love Nun 06.mp3" yEnc
		if (preg_match('/^(.+\(\d{4}\)) \[\d+\/\d+\] ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //NR - Jonathan Maberry - Extinction Machine - [26/98] - "Extinction Machine - 26-89.mp3" yEnc
		if (preg_match('/^NR - (.+) - \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[Seizing the Enigma - David Kahn (Unabridged)(NMR)] - [06/29] - "DKSTE.part03.rar" yEnc
		if (preg_match('/^\[(.+)\] - \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[116/194] - "David Baldacci - SK 03 Simple Genius - 21 - SK 03 Simple Genius - 021.mp3" yEnc
		if (preg_match('/\[\d+\/\d+\] - "(.+) - \d+ -.+-.+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Michael Moss - Salt Sugar Fat - How the Food Giants Hooked Us [03/20] "Salt Sugar Fat - How the Food Giants Hooked Us 01.mp3" yEnc
		if (preg_match('/^([\s\w\d-]+) \[\d+\/\d+\][ -]+".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(TTC Audio - Impossible - Physics Beyond the Edge) [01/34] - "TTC Audio - Impossible - Physics Beyond the Edge.sfv" yEnc
		if (preg_match('/^\((TTC Audio - .+)\) \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //TTC Audio - Impossible - Physics Beyond the Edge) (proper pars) [0/8] - "(proper par2s) TTC Audio - Impossible - Physics Beyond the Edge.nzb" yEnc
		if (preg_match('/^(TTC Audio - .+)\)\s\(.+\[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function audio_warez()
	{
		//[#nzbx.audio/EFnet]-[1681]-[MagicScore.Note.v7.084-UNION]-[02/12] - "u-msn7.r00" yEnc
		if (preg_match('/^(Re: )?\[.+?\]-\[\d+\]-\[(.+?)\]-\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//MacProVideo.com.Pro.Tools8.101.Core.Pro.Tools8.TUTORiAL-DYNAMiCS [2 of 50] "dyn-mpvprtls101.sfv" yEnc
		//Native.Instruments.Komplete.7.VSTi.RTAS.AU.DVDR.D02-DYNAMiCS[01/13] - "dyn.par2" yEnc
		//Native.Instruments.Komplete.7.VSTi.RTAS.AU.DVDR.DYNAMiCS.NZB.ONLY [02/13] - "dyn.vol0000+001.PAR2" yEnc
		if (preg_match('/^([\w.-]+) ?\[\d+( of |\/)\d+\] ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //REQ : VSL Stuff ~ Here's PreSonus Studio One 1.5.2 for OS X [16 of 22] "a-p152x.rar" yEnc
		if (preg_match('/^REQ : .+? ~ (.+?) \[\d+ of \d+\] ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Eminem - Recovery (2010) - [1/1] - "Eminem - Recovery (2010).rar" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [1/1] - "Dust in the Wind - the Violin Solo.rar" yEnc
		if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Native Instruments Battery 3 incl Library ( VST DX RTA )( windows ) Libraries [1/1] - "Native Instruments Battery 2 + 3 SERIAL KEY KEYGEN.nfo" yEnc
		if (preg_match('/^(.+?) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} /* TODO: REFRESH : Tonehammer Ambius 1 'Transmissions' ~ REQ: SAMPLE LOGIC SYNERGY [1 of 52] "dynamics.nfo" yEnc */
		else {
			return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
		}
	}

	public function b4e()
	{
		//"B4E-vip2851.r83" yEnc
		if (preg_match('/^"(B4E-vip\d+)\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[02/12] - "The.Call.GERMAN.2013.DL.AC3.Dubbed.720p.BluRay.x264 (Avi-RiP ).rar" yEnc
		if (preg_match('/^\[\d+\/\d+\] - "(.+?) \(.+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //- "as-jew3.vol03+3.PAR2" - yEnc
		if (preg_match('/^- "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function barbarella()
	{
		//ACDSee.Video.Converter.Pro.v3.5.41.Incl.Keymaker-CORE - [1/7] - "ACDSee.Video.Converter.Pro.v3.5.41.Incl.Keymaker-CORE.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Die.Nacht.Der.Creeps.THEATRICAL.GERMAN.1986.720p.BluRay.x264-GH - "gh-notcreepskf720.nfo" yEnc
		//The.Fast.and.the.Furious.Tokyo.Drift.2006.German.1080p.BluRay.x264.iNTERNAL-MWS  - "mws-tfatftd-1080p.nfo" yEnc
		if (preg_match('/^([\w.-]+)\s+-\s+".+?" yEnc$/', $this->subject, $match)) {
			return$match[1];
		}
		//CorelDRAW Technical Suite X6-16.3.0.1114 x32-x64<><>DRM<><> - (10/48)  "CorelDRAW Technical Suite X6-16.3.0.1114 x32-x64.part09.rar" - 2,01 GB - yEnc
		//AnyDVD_7.1.9.3_-_HD-BR - Beta<>give-me-all.org<>DRM<><> - (1/3)  "AnyDVD_7.1.9.3_-_HD-BR - Beta.par2" - 14,53 MB - yEnc
		//Android Softarchive.net Collection Pack 27^^give-me-all.org^^^^DRM^^^^ - (01/26)  "Android Softarchive.net Collection Pack 27.par2" - 1,01 GB - yEnc
		//WIN7_ULT_SP1_x86_x64_IE10_19_05_13_TRIBAL <> give-me-all.org <> DRM <> <> PW <> - (154/155)  "WIN7_ULT_SP1_x86_x64_IE10_19_05_13_TRIBAL.vol57+11.par2" - 7,03 GB - yEnc
		//[Android].Ultimate.iOS7.Apex.Nova.Theme.v1.45 <> DRM <> - (1/3)  "[Android].Ultimate.iOS7.Apex.Nova.Theme.v1.45.par2" - 21,14 MB - yEnc
		if (preg_match('/^(\[[A-Za-z]+\]\.|reup\.)?([a-zA-Z0-9].+?)([\^<> ]+give-me-all\.org[\^<> ]+|[\^<> ]+)DRM[\^<> ]+.+? - \(\d+\/\d+\)  ".+?" - .+? yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(004/114) - Description - "Pluralsight.net XAML Patterns (10).rar" - 532,92 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) - .+? - "(.+?)( \(\d+\))?' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(01/12) - "TransX - Living on a Video 1993.part01.rar" - 561,55 MB - TransX - Living on a Video 1993.[Lossless] Highh Quality yEnc
		//(59/81) "1973 .Lee.Jun.Fan.DVD9.untouched.z46" - 7,29 GB - Lee.Jun.Fan.sein.Film.DVD9.untouched yEnc
		if (preg_match('/^\(\d+\/\d+\)( -)? ".+?" - \d+[,.]\d+ [mMkKgG][bB] - (.+?) yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //>>> www.lords-of-usenet.org <<<  "Der Schuh Des Manitu.par2" DVD5  [001/158] - 4,29 GB yEnc
		if (preg_match('/^>>> www\.lords-of-usenet\.org <<<.+? "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' .+? \[\d+\/\d+\] - .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//NEUES 4y - [@ usenet-4all.info - powered by ssl.news -] [5,58 GB] [002/120] "DovakinPack.part002.rar" yEnc
		//NEUES 4y (PW)  [@ usenet-4all.info - powered by ssl.news -] [7,05 GB] [014/152] "EngelsGleich.part014.rar" yEnc
		if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+\/\d+\] "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		}
		//Old Dad uppt   Die Schatzinsel Teil 1+Teil2  AC3 DVD Rip German XviD Wp 01/33] - "upp11.par2" yEnc
		//Old Dad uppt Scary Movie5 WEB RiP Line XviD German 01/24] - "Scary Movie 5.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?\s{2,}|Old Dad uppt\s+)(.+?) \d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//>>>  20,36 MB   "Winamp.Pro.v5.70.3392.Incl.Keygen-FFF.par2"   552 B yEnc
		//..:[DoAsYouLike]:..    9,64 MB    "Snooper 1.39.5.par2"    468 B yEnc
		if (preg_match('/^.+?\s{2,}\d+[,.]\d+ [mMkKgG][bB]\s{2,}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '\s{2,}(\d+ B|\d+[,.]\d+ [mMkKgG][bB]) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(MKV - DVD - Rip - German - English - Italiano) - "CALIGULA (1982) UNCUT.sfv" yEnc
		if (preg_match('/^\(.+?\) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"sre56565ztrtzuzi8inzufft.par2" yEnc
		if (preg_match('/^"([a-z0-9]+)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function big()
	{
		//Girls.against.Boys.2012.German.720p.BluRay.x264-ENCOUNTERS - "encounters-giagbo_720p.nfo" yEnc
		if (preg_match('/^([\w.-]+) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//wtvrwschdhfthj - [001/246] - "dtstchhtmrrnvn.par2" yEnc
		//oijhuiurfjvbklk - [01/18] - "tb5-3ioewr90f.par2" yEnc
		if (preg_match('/^([a-z]{3,}) - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(08/22) - "538D7B021B362A4300D1C0D84DD17E6D.r06" yEnc
		if (preg_match('/^\(\d+\/\d+\) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(????) [02/71] - "Lasting Weep (1969-1971).part.par2" yEnc
		if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//(01/59) "ThienSuChungQuy_II_E16.avi.001" - 1,49 GB - yEnc
		//(058/183) "LS_HoangChui_2xdvd5.part057.rar" - 8,36 GB -re yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] -(re)? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[AoU] Upload#00287 - [04/43] - "Upload-ZGT1-20130525.part03.rar" yEnc
		if (preg_match('/^(\[[a-zA-Z]+\] .+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(nate) [01/27] - "nate_light_13.05.23.par2" yEnc
		if (preg_match('/^\([a-z]+\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //""Absolute Database Component for BCBuilder 4-6 MultiUser Edit 4.85.rar"" yEnc
		if (preg_match('/^""([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //781e1d8dccc641e8df6530edb7679a0e - (26/30) - "781e1d8dccc641e8df6530edb7679a0e.rar" yEnc
		if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}.+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function bloaf()
	{
		//36c1d5d4eaf558126c67f00be46f77b6 - (01/22) - "36c1d5d4eaf558126c67f00be46f77b6.par2" yEnc
		if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}.+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[10/17] - "EGk13kQ1c8.part09.rar" - 372.48 MB <-> usenet-space-cowboys.info <-> powered by secretusenet.com <-> yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] .+? usenet-space.+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Neu bei Bitfighter vom 23-07-2013) - "01 - Sido - Bilder Im Kopf.mp3" yEnc
		if (preg_match('/^\((.+?)\) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(2/8) "Mike.und.Molly.S01E22.Maennergespraeche.GERMAN.DL.DUBBED.720p.BluRay.x264-TVP.part1.rar" - 1023,92 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //4y (PW)   [@ usenet-4all.info - powered by ssl.news -] [27,35 GB] [001/118] "1f8867bb6f89491793d3.part001.rar" yEnc
		if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+\/\d+\] "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Bennos Special Tools DVD - Die Letzte <> DRM <><> PW <> - (002/183)  "Bennos Special Tools DVD - Die Letzte.nfo" - 8,28 GB - yEnc
		if (preg_match('/^(\[[A-Za-z]+\]\.)?([a-zA-Z0-9].+?)([\^<> ]+give-me-all\.org[\^<> ]+|[\^<> ]+)DRM[\^<> ]+.+? - \(\d+\/\d+\)\s+".+?" - .+? yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//(1/9) - CyberLink.PhotoDirector.4.Ultra.4.0.3306.Multilingual - "CyberLink.PhotoDirector.4.Ultra.4.0.3306.Multilingual.par2" - 154,07 MB - yEnc
		//(1/5) - Mac.DVDRipper.Pro.4.0.8.Mac.OS.X- "Mac.DVDRipper.Pro.4.0.8.Mac.OS.X.rar" - 24,12 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) - (.+?) ?- ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[3/3 Helene Fischer - Die Biene Maja 2013 MP3 Helene Fischer - Die Biene Maja 2013 MP3.mp3.vol0+1.PAR2" yEnc
		if (preg_match('/^\[\d+\/\d+ (.+?)\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Uploader.Presents-Mutter.und.Sohn.German.2013.DVDRiP.x264-XFi[01/27]"xf-mutterusohn.nfo" yEnc
		if (preg_match('/^Uploader\.Presents-(.+)\[\d+\/\d+\]".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function blu_ray()
	{
		//"786936833607.MK.A.part086.rar" yEnc
		if (preg_match('/^"(\d+\.MK\.[A-Z])\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [001/107] - "260713thbldnstnsclw.par2" yEnc
		if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "([a-z0-9]+)\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[www.allyourbasearebelongtous.pw]-[The Place Beyond the Pines 2012 1080p US Blu-ray AVC DTS-HD MA 5.1-HDWinG]-[03/97] "tt1817273-us-hdwing-bd.r00" - 46.51 GB - yEnc
		if (preg_match('/^\[www\..+?\]-\[(.+?)\]-\[\d+\/\d+\] ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(01/71)  - "EwRQCtU4BnaeXmT48hbg7bCn.par2" - 54,15 GB - yEnc
		//(63/63) "dfbgfdgtghtghthgGPGEIBPBrwg34t05.rev" - 10.67 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\)(\s+ -)? "([a-zA-Z0-9]+?)\d*\..+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //[01/67] - "O3tk4u681gd767Y.par2" yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([a-zA-Z0-9]+)\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //209a212675ba31ca24a8 [usenet-4all.info] [powered by ssl-news] [21,59 GB] [002/223] "209a212675ba31ca24a8.part001.rar" yEnc
		if (preg_match('/^([a-z0-9]+) \[.+?\] \[.+?\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+\/\d+\] ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //TIS97CC - "tis97cc.par2" yEnc
		if (preg_match('/^([A-Z0-9]+) - "[a-z0-9]+\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //AsianDVDClub.org - Sengoku Basara: Season 2 (2010) AVC 1080p BD50+BD25 - Disc 1 of 2 [001/112] - "adc-71029a.nfo" yEnc
		if (preg_match('/^AsianDVDClub\.org - (.+) \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //AsianDVDClub.org - Laputa: Castle in the Sky (1986) AVC 1080p BD50 - File 001 of 113: "adc-laputa.nfo" yEnc
		if (preg_match('/^AsianDVDClub\.org - (.+) ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //THE_PACIFIC_DISC_6_FULL_BLURAY_BD50_EX3 - [5/6] - "THE PACIFIC DISC 6 FULL BLURAY.part07.rar" yEnc
		if (preg_match(
			'/^[\w-\. ]+[ _-]{0,3}[\(\[]\d+\/\d+[\)\]][ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject,
			$match
		)
		) {
			return $match[1];
		} //LOST SEASON1 DISC4 REENCODED BD25 DJMASTURBEAT (151/154) "LOST SEASON1 DISC4 BD25 DJMASTURBEAT.vol3312+131.par2" - 26.91 GB - yEnc
		if (preg_match('/^[\w-\. ]+[ _-]{0,3}[\(\[]\d+\/\d+[\)\]][ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function boneless()
	{
		//4Etmo7uBeuTW[047/106] - "006dEbPcea29U6K.part046.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9]+)\[\d+\/\d+\] - "[a-zA-Z0-9]+\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(PC-ISO) ( Trials.Fusion.Riders.of.the.Rustlands-SKIDROW ) [10/40] - "sr-tfrotr.part01.rar" yEnc
		if (preg_match('/^\(PC-ISO\)[-_ ]{0,3}\( ([\w.,& ()\[\]\'\`-]{8,}?\b) \) \[\d+\/\d+\][-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //( Overlord II RELOADED ) - [013/112] - "rld-olii.part001.rar" yEnc
		if (preg_match('/^\( ([\w. -]{8,}) \)[-_ ]{0,3}\[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(68/89) "dz1R2wT8hH1iQEA28gRvm.part67.rar" - 7,91 GB - yEnc
		//(01/14)  - "JrjCY4pUjQ9qUqQ7jx6k2VLF.par2" - 4,39 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\)\s+(- )?"([a-zA-Z0-9]+)\..+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(110320152518519) [22/78] - "110320152518519.part21.rar" yEnc
		if (preg_match('/^\((\d+)\) \[\d+\/\d+\] - "\d+\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //1VSXrAZPD - [123/177] - "1VSXrAZPD.part122.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9]+) - \[\d+\/\d+\] - "[a-zA-Z0-9]+\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //( Peter Gabriel Albums 24x +17 Singles = 71x cd By Dready Niek )  ( ** By Dready Niek ** ) [113/178] - "Peter Gabriel Albums 24x +17 Singles = 71CDs By Dready Niek (1977-2010).part112.rar" yEnc
		if (preg_match('/^\( (.+?) \)\s+\( .+?\) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Tarja - Colours In The Dark (2013) "00. Tarja-Colours In The Dark.m3u" yEnc
		if (preg_match('/^([A-Za-z0-9].+? \((19|20)\d\d\)) "\d{2}\. .+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"BB636.part14.rar" - (15/39) - yEnc
		if (preg_match('/^"([a-zA-Z0-9]+)' . $this->e0 . ' - \(\d+\/\d+\) - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Lutheria - FC Twente TV Special - Ze wilde op voetbal [16/49] - "Lutheria - FC Twente TV Special - Ze wilde op voetbal.part16.rar" yEnc
		if (preg_match('/^([-a-zA-Z0-9 ]+) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Pee Mak Prakanong - 2013 - Thailand - ENG Subs - "Pee Mak Prakanong.2013.part22.rar" yEnc
		if (preg_match('/^([-a-zA-Z0-9 ]+) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(????) [011/161] - "flynns-image-redux.part010.rar" yEnc
		//(Dgpc) [000/110] - "Teen Wolf - Seizoen.3 - Dvd.2 (NLsub).nzb" yEnc
		if (preg_match('/^\((\?{4}|[a-zA-Z]+)\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //("Massaladvd5Kilusadisc4S1.par2" - 4,55 GB -) "Massaladvd5Kilusadisc4S1.par2" - 4,55 GB - yEnc
		if (preg_match('/^\("([a-z0-9A-Z]+).+?" - \d+[,.]\d+ [mMkKgG][bB] -\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"par.4kW9beE.1.vol122+21.par2" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [01/19] - "Age.of.Dinosaurs.German.AC3.HDRip.x264-FuN.par2" yEnc
		//>>>>>Hell-of-Usenet.org>>>>> - [01/35] - "Female.Agents.German.2008.AC3.DVDRip.XviD.iNTERNAL-VideoStar.par2" yEnc
		if (preg_match('/^.+?\.(info|org)>+ - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		}
		//[010/101] - "Bf56a8aR-20743f8D-Vf7a11fD-d7c6c0.part09.rar" yEnc
		//[1/9] - "fdbvgdfbdfb.part.par2" yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[LB] - [063/112] - "RVL-GISSFBD.part063.rar" yEnc
		if (preg_match('/^\[[A-Z]+\] - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //!!www.usenet4all.eu!! - Acceptance.2009.COMPLETE.NTSC.DVDR-D0PE[001/100] - #34;d0pe-a.nfo#34; yEnc
		if (preg_match('/^!!www\.usenet4all\.eu!![ _-]{0,3}(.+)\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //NCIS.S11E03.HDTV.x264-LOL - "NCIS.S11E03.HDTV.x264-LOL.part.par2" yEnc
		if (preg_match('/^([A-Za-z0-9][a-zA-Z0-9.-]{6,})\s+- ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//place2home.net - Call.of.Duty.Ghosts.XBOX360-iMARS - [095/101] - "imars-codghosts-360b.vol049+33.par2" yEnc
		//Place2home.net - Diablo_III_USA_RF_XBOX360-PROTOCOL - "d3-ptc.r34" yEnc
		if (preg_match('/^place2home\.net - (.*?) - (\[\d+\/\d+\] - )?".+?" yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		//[scnzbefnet][500934] Super.Fun.Night.S01E09.720p.HDTV.X264-DIMENSION [1/19] - "bieber.109.720p-dimension.sfv" yEnc
		//REPOST: [scnzbefnet][500025] Major.Crimes.S02E13.720p.HDTV.x264-IMMERSE [16/33] - "major.crimes.s02e13.720p.hdtv.x264-immerse.r24" yEnc
		if (preg_match('/^(REPOST: )?\[scnzbefnet\]\[(\d+)\] (.+?) \[\d+\/(\d+\]) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //[scnzbefnet] Murdoch.Mysteries.S07E09.HDTV.x264-KILLERS [1/20] - "murdoch.mysteries.s07e09.hdtv.x264-killers.r13" yEnc
		if (preg_match('/^\[scnzbefnet\] (.+?) \[\d+\/(\d+\]) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Ancient.Aliens.S03E05.Aliens.and.Mysterious.Rituals.720p.HDTV.x264.AC3.2Ch.REPOST) [41/42] - "Ancient.Aliens.S03E05.Aliens.and.Mysterious.Rituals.720p.HDTV.x264.AC3.2Ch.REPOST.vol071+66.PAR2" yEnc
		if (preg_match('/^(\((.+?)\) \[)\d+(\/\d+] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(01/48) - [Lords-of-Usenet] <<Partner of SSL-News.info>> presents Sons.of.Anarchy.S02E03.Unten.am.Fluss.GERMAN.DUBBED.720p.BLURAY.x264-ZZGtv -"zzgtv-soa-s02e03.par2" - 1,84 GB
		if (preg_match('/^\(\d+\/\d+\) - \[Lords-of-Usenet\] <<Partner of SSL-News.info>> presents (.+) -".+" - .+ yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //Doobz Europa_Universalis_IV_Conquest_of_Paradise-FLT [10/54] - "flt-eucp.001" yEnc
		if (preg_match('/^Doobz ([a-zA-z-_]+) \[\d+\/(\d+\]) - ".+' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(aangemeld bij usenet collector) [001/125] - "Northern_Exposure_Season_1_dvd_1.par2" yEnc
		if (preg_match('/^\(aangemeld bij usenet collector\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[15/16] - "Person.of.Interest.S03E09.480p.HDTV.x264-mSD.vol0+1.par2" - 177,28 MB <-> usenet-space-cowboys.info <-> powered by secretusenet.com <-> yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] .+usenet-space-cowboys\.info.+ yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //brothers-of-usenet.info&net-empfehlen-ssl-news.info Black Mirror s01e01 - Der wille des volkes - [09/15] - "Black Mirror - S01E01 - Der Wille des Volkes.vol00+01.par2" yEnc
		if (preg_match('/^brothers-of-usenet\.info.+ - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(024/264) "Enf.S04E02.G.D.H.X-M.vol51+46.PAR2" - 8,27 GB -Enf.S04.G.D.H.X yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] .+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //- "Auction Hunters S04E04.HDTV.x264-StarryNights1.nzb" yEnc
		if (preg_match('/.*"([\w.,& ()\[\]\'\`-]{8,}?\b.?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function british_drama()
	{
		//Coronation Street 03.05.2012 [XviD] [01/23] - "coronation.street.03.05.12.[ws.pdtv].par2" yEnc
		//Coronation Street 04.05.2012 - Part 1 [XviD] [01/23] - "coronation.street.04.05.12.part.1.[ws.pdtv].par2" yEnc
		if (preg_match('/^([a-zA-Z0-9].+? \[XviD\]) \[\d\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//The Prisoner E06-09 [001/152] - "06 The General.mkv.001" yEnc
		//Danger Man S2E05-08 [075/149] - "7.The colonel's daughter.avi.001" yEnc
		if (preg_match('/^([a-zA-Z0-9]+ .+? (S\d+)?E\d+-\d\d) \[\d+\/\d+\] - "\d(\d |\.).+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Wizards Vs Aliens - 1x06 - Rebel Magic, Part Two [XviD][00/27] - "wizards.vs.aliens.106.rebel.magic.part.two.[ws.pdtv].nzb" yEnc
		if (preg_match('/^.+?[-_ ]{0,3}\d+[xX]\d+[-_ ]{0,3}.+?\[\d+\/\d+\][-_ ]{0,3}.+?"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Vera.3x03.Young.Gods.720p.HDTV.x264-FoV - "vera.3x03.young_gods.720p_hdtv_x264-fov.r00" yEnc
		if (preg_match('/^[a-zA-Z0-9-\.]+ - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Heartbeat 9x23 Wise Guys.avi.015  yEnc
		if (preg_match('/^(.+ \d+x\d+.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Minder S10E10 [00/52] - "S10E10.nzb" yEnc
		if (preg_match('/^(.+ S\d+E\d+)[-_ ]{0,3}\[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Rosemary & Thyme.106..The Tree of Death....Read txt file for more info about this posting - File 03 of 51 - "Rosemary & Thyme.106.The Tree of Death.part01.rar" yEnc
		if (preg_match('/^.+File \d+ of \d+ - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //The.Tudors.S01E04.2007.720p[33/57] - "The.Tudors.S01E04.2007.720p.part31.rar" yEnc
		if (preg_match('/^.+\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Happy_Halloween_The Ice House (1978).par2  yEnc
		if (preg_match('/^([\w. &()-]{8,}?\b)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Rosemary & Thyme - 2x02 Orpheus In The Undergrowth.par2"  yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[01/45] - "Minder S01E08.sfv" yEnc
		if (preg_match('/^\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function bungabunga()
	{
		//[01/29] - "Bellflower.2011.German.AC3.BDRip.XviD-EPHEMERiD.par2" - 1,01 GB yEnc
		//(3/9) - "Microsoft Frontpage 2003 - 4 Town-Up from Kraenk.rar.par2" - 181,98 MB - yEnc
		if (preg_match('/^[\[(]\d+\/\d+[\])] - "([A-Z0-9].{2,}?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB]( -)? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function cavebox()
	{
		//(www.Thunder-News.org) )Panamericana.E02.Von.Alaska.nach.Feuerland.GERMAN.DOKU.WS.dTV.XViD-SiTiN( (Sponsored by AstiNews) - (05/34) - #34;sitin-panamericanae02-xvid.r00#34; yEnc
		if (preg_match('/^\(www\.Thunder-News\.org\) ?\)(.+)\( \(Sponsored.+\)[ _-]{0,3}\(\d+\/\d+\)[ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function cats()
	{
		//Pb7cvL3YiiOu06dsYPzEfpSvvTul[02/37] - "Fkq33mlTVyHHJLm0gJNU.par2" yEnc
		//DLJorQ37rMDvc [01/16] - "DLJorQ37rMDvc.part1.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9]{5,}) ?\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function cbt()
	{
		//(WinEdt.v8.0.Build.20130513.Cracked-EAT) [01/10] - "eatwedt8.nfo" yEnc
		if (preg_match('/^\(([a-zA-Z0-9-\.\&_ ]+)\) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//[ ABCAsiaPacific.com - Study English IELTS Preparation (2006) ] AVI.PDF (17/34) - "abcap-senglishielts.r16" yEnc
		//[ Ask Video - The Studio Edge 101 Planning a Recording Studio ] MP4.x264 (00/21) - "syn-avtse101.nzb" yEnc
		//[ Brian Tracy and Colin Rose - Accelerated Learning Techniques (2003) ] MP3.PDF (00/14) - "btcr-accltech.nzb" yEnc
		//[ Lynda.com - Advanced Modeling in Revit Architecture (2012) ] DVD.ISO (41/53) - "i-lcamira.r38" yEnc
		//[ Morgan Kaufmann - Database Design Know It All (2008) ] TRUE.PDF (0/5) - "Morgan.Kaufmann.Database.Design.Know.It.All.Nov.2008.eBook-DDU.nzb" yEnc
		//[ VertexPusher - Vol. 2 Lighting, Shading and Rendering (2012) ] MP4.x264 (05/20) - "vp-c4dlsar.r03" yEnc
		if (preg_match('/^\[ ([a-zA-Z0-9-\.\&\(\)\,_ ]+) \] [a-zA-Z0-9]{3,4}\.[a-zA-Z0-9]{3,4} \(\d+\/(\d+\)) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"MythBusters S10E02 720p HDTV x264-DHD.rar"  yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' {1,2}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //2nd Annual American CountryMusicAwards 2011 HDTV XviD 2HD.rar  yEnc
		if (preg_match('/^(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) {1,2}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [005/358] - "WLAN - professionell absichern.rar" Sayco  yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' Sayco  yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function cbts()
	{
		//"softWoRx.Suite.2.0.0.25.x32-TFT.rar" yEnc
		if (preg_match('/"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function cd_image()
	{
		//[www.drlecter.tk]-[The_Night_of_the_Rabbit-FLT]-[01/67] "Dr.Lecter.nfo" - 5.61 GB - yEnc
		if (preg_match('/^\[www\..+?\]-\[(.+?)\]-\[\d+\/\d+\] ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Slender.The.Arrival-WaLMaRT.PC - [01/26] - "wmt-stal.nfo" - yEnc
		//The.Night.of.the.Rabbit-FLT - [03/66] - "flt-notr.r00" - FAiRLIGHT - 5,10 GB - yEnc
		//Resident.Evil.Revelations-FLT - PC GAME - [03/97] - "Resident.Evil.Revelations-FLT.r00" - FAiRLIGHT - yEnc
		//Afterfall.Insanity.Dirty.Arena.Edition-WaLMaRT - [MULTI6][PCDVD] - [02/45] - "wmt-adae.r00" - PC GAME - yEnc
		if (preg_match('/^([a-zA-Z0-9.-]{10,}) -( PC GAME -| [A-Z0-9\[\]]+ -)? \[\d+\/\d+\] - ".+?" - (.+? - (\d+[,.]\d+ [mMkKgG][bB] - )?)?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[01/46] - Crashtime 5 Undercover RELOADED - "rld-ct5u.nfo" - PC - yEnc
		//[01/76] - Of.Orcs.And.Men-SKIDROW - "sr-oforcsandmen.nfo" - yEnc
		//PC Game - [01/71] - MotoGP 13-RELOADED Including NoDVD Fix - "MotoGP 13-RELOADED Including NoDVD Fix nfo" - yEnc
		if (preg_match('/^(PC Game - )?\[\d+\/\d+\] - (.+?) - ".+?" -( .+? -)? yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//Magrunner Dark Pulse FLT (FAiRLIGHT) - [02/70] - "flt-madp par2" - PC - yEnc
		//MotoGP 13 RELOADED - [01/71] - "rld-motogp13 nfo" - PC - yEnc
		//Dracula 4: Shadow of the Dragon FAiRLIGHT - [01/36] - "flt-drc4 nfo" - PC - yEnc
		if (preg_match('/^([A-Za-z0-9][a-zA-Z0-9: ]{8,}(-[a-zA-Z]+)?)( \(.+?\)| - [\[A-Z0-9\]]+)? - \[\d+\/\d+\] - ".+?" - .+? - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[NEW PC GAME] - Lumber.island-WaLMaRT - "wmt-lisd.nfo" - [01/18] - yEnc
		//Trine.2.Complete.Story-SKIDROW - "sr-trine2completestory.nfo" - [01/78] - yEnc
		if (preg_match('/^(\[[A-Z ]+\] - )?([a-zA-Z0-9.-]{10,}) - ".+?" - \[\d+\/\d+\] - yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //Uploader.Presents-Metal.Gear.Rising.Revengeance-RELOADED(51/65]"rld-megerire.r48" yEnc
		if (preg_match('/^Uploader\.Presents-(.+)[\(\[]\d+\/\d+\]".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function cd_lossless()
	{
		//Flac Flood - Modern Talking - China In Her Eyes (CDM) - "1 - Modern Talking - China In Her Eyes (feat. Eric Singleton) (Video Version).flac" (01/14) (23,64 MB)   136,66 MB yEnc
		////Flac Flood Modern Talking - America - "1 - Modern Talking - Win The Race.flac" (01/18) (29,12 MB) 549,35 MB yEnc
		if (preg_match('/^Flac Flood( -)? (.+?) - ".+?" \(\d+\/\d+\) .+? yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//Cannonball Adderley - Nippon Soul [01/17] "00 - Cannonball Adderley - Nippon Soul.nfo" yEnc
		//Black Tie White Noise [01/24] - "00 - David Bowie - Black Tie White Noise.nfo" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?) \[\d+\/\d+\]( -)? "\d{2,} - .+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//The Allman Brothers Band - Statesboro Blues [Swingin' Pig - Bootleg] [1970 April 4] - File 09 of 19: Statesboro Blues.cue yEnc
		//[1977] Joan Armatrading - Show Some Emotion - File 15 of 20: 06 Joan Armatrading - Opportunity.flac yEnc
		if (preg_match('/^((\[\d{4}\] )?[a-zA-Z0-9].+?) - File \d+ of \d+: .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //The Allman Brothers Band - The Fillmore Concerts [1971] - 06 The Allman Brothers Band - Done Somebody Wrong.flac yEnc
		if (preg_match('/^([A-Z0-9].+? - [A-Z0-9].+? \[\d{4}\]) - \d{2,} .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //The Velvet Underground - Peel Slow And See (Box Set) Disc 5 of 5 - 13 The Velvet Underground - Oh Gin.flac yEnc
		if (preg_match('/^([A-Z0-9].+? - [A-Z0-9].+? Disc \d+ of \d+) - [A-Z0-9].+?\..+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(28/55) "Ivan Neville - If My Ancestors Could See Me Now.par2" - 624,44 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function chello()
	{
		//0F623Uv71RHKt0jzA7inbGZLk00[2/5] - "l2iOkRvy80bgLrZm1xxw.par2" yEnc
		//GMC2G8KixJKy [01/15] - "GMC2G8KixJKy.part1.rar" yEnc
		if (preg_match('/^([A-Za-z0-9]{5,}) ?\[\d+\/\d+\] - "[A-Za-z0-9]{3,}.+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Imactools.Cefipx.v3.20.MacOSX.Incl.Keyfilemaker-NOY [03/10] - "parfile.vol000+01.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9][a-zA-Z0-9.-]+) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Siberian Mouses LS, BD models and special... [150/152] - "Xlola - Luba, Sasha & Vika.avi.jpg" yEnc
		if (preg_match('/^([A-Za-z0-9-]+ .+?)[. ]\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function classic_tv_shows()
	{
		//Re: REQ: All In The Family - "Archie Bunkers Place 1x01 Archies New Partner part 1.nzb" yEnc
		if (preg_match('/^Re: REQ: (.+? - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Per REQ - "The.Wild.Wild.West.S03E11.The.Night.of.the.Cut-Throats.DVDRip.XVID-tz.par2" 512x384 [01/40] yEnc
		if (preg_match('/^Per REQ - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' .+? \[\d+\/\d+\] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //By req: "Dennis The Menace - 4x25 - Dennis and the Homing Pigeons.part05.rar" yEnc
		if (preg_match('/^By req: "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //I Spy HQ DVDRips "I Spy - 3x26 Pinwheel.part10.rar" [13/22] yEnc
		if (preg_match('/^[a-zA-Z ]+HQ DVDRips "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' \[\d+\/\d+\] yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Sledge Hammer! S2D2 [016/138] - "SH! S2 D2.ISO.016" yEnc
		//Sledge Hammer! S2D2 [113/138] - "SH! S2 D2.ISO.1132 yEnc
		//Lost In Space - Season 1 - [13/40] - "S1E02 - The Derelict.avi" yEnc
		if (preg_match('/^([a-zA-Z0-9].+? (S\d+D\d+|- Season \d+))( -)? \[\d+\/\d+\] - ".+?"? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Night Flight TV Show rec 1991-01-12 (02/54) - "night flight rec 1991-01-12.nfo" yEnc
		//Night Flight TV Show rec 1991-05-05 [NEW PAR SET] (1/9) - "night flight rec 1991-05-05.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9].+? \d{4}-\d\d-\d\d)( \[.+?\])? \(\d+\/\d+\) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//The.Love.Boat.S05E08 [01/31] - "The.Love.Boat.S05E08.Chefs.Special.Kleinschmidt.New.Beginnings.par2" yEnc
		//Barney.Miller.S08E05.Stress.Analyzer [01/18] - "Barney.Miller.S08E05.Stress.Analyzer.VHSTVRip.DivX.par2" yEnc
		if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9.-]+S\d+E\d+([a-zA-Z0-9.]+)? \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		}
		//"Batman - S1E13-The Thirteenth Hat.par2" yEnc
		//"The Munsters - 1x01 Munster Masquerade.part.par" HQ DVDRip[02/16] yEnc
		if (preg_match('/^(Re: )?"(.+?)(\.avi|\.mkv)?' . $this->e0 . '( HQ DVDRip\[\d+\/\d+\])? yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//Re: Outside Edge series 1 - [01/20] - "Outside Edge S01.nfo" yEnc
		//Green Acres Season 1 [01/87] - "Green Acres Season 1.par2" yEnc
		//MASH Season 1 - [01/54] - "MASH - Season 01.par2" yEnc
		if (preg_match('/^(Re: )?[a-zA-Z0-9]+.+? (series|Season) \d+ (- )?\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[4];
		}
		//Rich.Little.Show - 1x12 - Season.and.Series.Finale - [02/33] - "Rich Little Show - 1x12 - Bill Bixby.avi.002" yEnc
		//Rich.Little.Show - 1x11 - [01/33] - "Rich Little Show - 1x11 - Jessica Walter.avi.001" yEnc
		//REQ - Banacek - 2x07 - [02/61] - "Banacek - 2x07 - Fly Me - If You Can Find Me.avi.002" yEnc
		if (preg_match('/^(REQ - )?[A-Z0-9a-z][A-Z0-9a-z.]+ - \d+x\d+ (- [A-Z0-9a-z.]+ )?- \[\d+\/\d+\] - "(.+?)(\.avi|\.mkv)?' . $this->e1, $this->subject, $match)) {
			return $match[3];
		} //Handyman Shows-TOH-S32E10 - File 01 of 32 - yEnc
		if (preg_match('/^Handyman Shows-(.+) - File \d+ of \d+ - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//'Mission: Impossible' - 1x09 - NTSC - DivX - 28 of 48 - "MI-S01E09.r23" yEnc
		//'Mission: Impossible' - 1x09 - NTSC - DivX - 01 of 48 - "MI-S01E09.nfo" (1/1)
		if (preg_match('/^([a-zA-Z0-9 -_\.:]+) - \d+( of \d+)[-_ ]{0,3}".+?' . $this->e0 . ' (\(\d+\/\d+\) )?(yEnc)?$/', $this->subject, $match)) {
			return $match[1];
		}
		//"Batman - S2E58-Ice Spy.par2"yEnc
		//"Black Sheep Squadron 1x03 Best Three Out of Five.par2"
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '(yEnc)?( )?$/', $this->subject, $match)) {
			return $match[1];
		} //"Guns of Will Sonnett - 1x04.mp4" (Not My Rip)Guns Of Will Sonnett Season 1 1 - 26 Mp4 With Pars yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' \(Not My Rip\).+ \d+ (- \d+) .+ yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/10) "Watch_With_Mother-Bill_And_Ben-1953_02_12-Scarecrow-VHSRip-XviD.avi" - 162.20 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(Our Gang - Little Rascals  DVDRips)  "Our Gang -  The Lucky Corner (1936).part0.sfv" [01/19] yEnc
		//(Our Gang - Little Rascals  DVDRips)  "Our Gang -  Wild Poses (1933).part.par" [02/20] Last One I Have! yEnc
		if (preg_match('/^\(.+\)  "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' \[\d+\/(\d+\]) (Last One I Have! )?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[EnJoY] =>A Blast from Usenet Past (1/3)<= [00/14] - "Mcdonalds Training Film - 1972 (Vhs-Mpg).part.nzb" yEnc
		if (preg_match('/^.+ Usenet Past .+\[\d+\/(\d+\]) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //<OPA_TV> [01/12] - "Yancy Derringer - 03 - Geheime Fracht.par2" yEnc
		if (preg_match('/^<OPA_TV> \[\d+\/(\d+\]) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		}
		//77 Sunset Strip 409 [1 of 23] "77 Sunset Strip 409 The Missing Daddy Caper.avi.vol63+34.par2" yEnc
		//Barney.Miller.NZBs [001/170] - "Barney.Miller.S01E01.Ramon.nzb" yEnc
		if (preg_match('/^.+ [\[\(]\d+( of |\/)(\d+[\]\)])[-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[3];
		}
		//All in the Family - missing eps - DVDRips  "All in the Family - 6x23 Gloria & Mike's House Guests.part5.rar" [08/16] yEnc
		//Amos 'n' Andy - more shows---read info.txt  "Amos 'n' Andy S01E00 Introduction of the Cast.mkv.001" (002/773) yEnc
		if (preg_match('/^.+[-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' [\[\(]\d+\/(\d+[\]\)]) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Andy Griffith Show,The   1x05....Irresistible Andy - (DVD).part04.rar
		if (preg_match('/^(.+\d+x\d+.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})( yEnc)?( (Series|Season) Finale)?$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function comics()
	{
		//(The Walking Dead Comics Volume 1) [10/18] - "walking dead 002.cbr" yEnc
		if (preg_match('/^\((.+)\)[ -]+\[\d+\/\d+\][ -]+".+"[ -]+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //The Strain -1-9 [Comic] [1/2] - "TSC.nfo" yEnc
		if (preg_match('/^(.+)[ -]+\[COMIC\][ -]+\[\d+\/\d+\][ -]+".+"[ -]+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} // Return anything between the quotes.
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			if (strlen($match[1]) > 7 && !preg_match('/\.vol.+/', $match[1])) {
				return $match[1];
			} else {
				return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
			}
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function comics_dcp()
	{
		//New Releases 2014.3.20 - "The Silver Surfer 012 (1970-01) (digital) (milton19-Empire).cbr" yEnc
		if (preg_match('/\d{4}\.(\d{2}\.){2} - "(.+?)\.cb[rz]" yEnc$/i', $this->subject, $match)) {
			return array('cleansubject' => $match[2], 'properlynamed' => false);
		}
		//Re: Req: Dark Tower - The Battle of Jericho Hill 05 (of 05) TIA - File 1 of 1 - yEnc
		if (preg_match('/(Req?: )+(.+?) - File \d+ of \d+ - yEnc$/i', $this->subject, $match)) {
			return array('cleansubject' => $match[2], 'properlynamed' => false);
		}
		//Amazing Spider-man 306 || FB-DCP scan || 1988 || - "Amazing Spider-man 306 (1988)(FB-DCP)(C2C).CBR" [3/7] yEnc
		if (preg_match('/\|\| \d{4} \|\| - "(.+?)\.cb[rz]" \[\d+\/\d+\] yEnc$/i', $this->subject, $match)) {
			return array('cleansubject' => $match[1], 'properlynamed' => false);
		}
		//All-Star Squadron Preview 00 (1981) [HQ rescan] [1/5] - All-Star Squadron Preview 00 (Aug 1981) [HQ rescan] [RexTyler].cbr yEnc
		//Mad Color Classics (1st time as true CBR) - [1/1] Mad Color Classics 04 {FIXED} (c2c) [True CBR by RexTyler].cbr yEnc
		//Comico Christmas Special (1988) - [1/5] Comico Christmas Special (1988) [starv].cbr.par2 yEnc
		if (preg_match('/\s+\[\d+\/\d+\]\s+(-\s+)?([A-Z0-9].+?)(\[.*?(starv|RexTyler).*?\])?\.cb[rz](\.par2)?\s+yEnc$/i', $this->subject, $match)) {
			return array('cleansubject' => $match[2], 'properlynamed' => false);
		}
		//0-Day 2013.8.28 - "Ultimate Comics Spider-Man 026 (2013) complete/unbroken - File 1 of 1 - Ultimate Comics Spider-Man 026 (2013) (Digital) (Zone-Empire).cbr yEnc
		//Ultimate Comics Spider-Man 026 - File 1 of 1 - Ultimate Comics Spider-Man 026 (2013) (Digital) (Zone-Empire).rar yEnc
		if (preg_match('/\s+-\s+File \d+ of \d+\s+-\s+([A-Z0-9].+?)\.(cb[rz]|rar)\s+yEnc$/i', $this->subject, $match)) {
			return array('cleansubject' => $match[1], 'properlynamed' => false);
		}
		//Grimm Fairy Tales Myths & Legends 12 - File 1 of 1 - yEnc
		if (preg_match('/^([a-z0-9].+?)\s+-\s+File \d+ of \d+\s+-\s+yEnc$/i', $this->subject, $match)) {
			return array('cleansubject' => $match[1], 'properlynamed' => false);
		}
		// Return anything between the quotes.
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			if (strlen($match[1]) > 7 && !preg_match('/\.vol.+/', $match[1])) {
				return array('cleansubject' => $match[1], 'properlynamed' => false);
			} else {
				return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
			}
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function comp()
	{
		//(45/74) NikJosuf post Magento tutorials "43 - Theming Magento 19 - Adding a Responsive Slideshow.mp4" yEnc
		if (preg_match('/^\(\d+\/\d+\) .+? post (.+?) ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Photo Mechanic 5.0 build 13915 (1/6) "Photo Mechanic 5.0 build 13915 (1).par2" - 32,97 MB - yEnc
		if (preg_match('/^(.{5,}?) \(\d+\/\d+\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Advanced SystemCare Pro 6.3.0.269 Final ML Incl Serial) [01/10] - "Advanced SystemCare Pro 6.3.0.269 Final ML Incl Serial.nfo" yEnc
		if (preg_match('/^\(([a-zA-Z0-9. ]{10,}?)\) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[01/21 Geroellheimer - S01E03 - Swimming Pool Geroellheimer - S01E03 - Swimming Pool.mp4.001" yEnc
		if (preg_match('/^\[\d+\/\d+ (.+?)(\.(part\d*|rar|avi|iso|mp4|mkv|mpg))?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return implode(' ', array_intersect_key(explode(' ', $match[1]), array_unique(array_map('strtolower', explode(' ', $match[1])))));
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function console_ps3()
	{
		//Railfan_JPN_JB_PS3-Caravan [02/88] - "cvn-railfjb.par2" yEnc
		//Madagascar.Kartz.German.JB.PS3-ATAX [01/40] - "atax-mkgjp.nfo"
		//Saints_Row_The_Third_The_Full_Package_EUR-PS3-SPLiT [61/87] - "split-sr3fullpps3.r58" yEnc
		if (preg_match('/^([\w.]+?-?PS3-[a-zA-Z0-9]+) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(4168) [00/24] - "Legend.Of.The.Guardians.Owls.GaHoole.USA.JB.PS3-PSFR33.nzb" yEnc
		if (preg_match('/^\(\d+\) \[\d+\/\d+\] - "([\w.]{10,}?PS3-[A-Za-z0-9]+?)\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[4230]-[ABGX.net]-[ Air_Conflicts_Pacific_Carriers_USA_PS3-CLANDESTiNE ] (01/54) "clan-aircpc.nfo" yEnc
		if (preg_match('/^\[\d+\]-\[.+?\]-\[ (.+?) \] \(\d+\/\d+\) ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //.: Birds_of_Steel_PS3-ABSTRAKT :. - .:www.thunder-news.org:. - .:sponsored by secretusenet.com:. - "as-bos.r39" yEnc
		if (preg_match('/\.: (.+):. - .:www\.thunder-news\.org:. - .:sponsored by secretusenet\.com:\. - ("|#34;).+("|#34;).+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Armored_Core_V_PS3-ANTiDOTE__www.realmom.info__.r00" (03/78) 3,32 GB yEnc
		if (preg_match('/^"(.+)__www.realmom.info__.+" \(\d+\/(\d+\)) \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Ace.Combat.Assault.Horizon.PS3-DUPLEX__www.realmom.info__.nfo"  7,14 GB yEnc
		if (preg_match('/^"(.+)__www.realmom.info__.+"  (\d+[.,]\d+ [kKmMgG][bB]) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Angry Birds USA PSN PSP-NRP.exe" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function cores()
	{
		//Film - [13/59] - "Jerry Maguire (1996) 1080p DTS multisub HUN HighCode-PHD.part13.rar" yEnc
		//Film - "Phone.booth.2003.RERIP.Bluray.1080p.DTS-HD.x264-Grym.part001.rar" yEnc
		if (preg_match('/^Film - (\[\d+\/\d+\] - )?"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //[Art-Of-Use.Net] :: [AUTO] :: - [34/36] - "ImmoralLive.13.11.10.Immoral.Orgies.Rikki.Six.Carmen.Callaway.And.Amanda.Tate.XXX.1080p.MP4-KTR.vol15+16.par2" yEnc
		if (preg_match('/^\[Art-Of-Use\.Net\] :: \[.+?\] :: - \[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //brothers-of-usenet.info.net Partner von---- SSL-News.info-----  brothers-of-usenet.info.net Partner von---- SSL-News.info-----AVG.Internet.Security.2014.Build.4335.x86.x64 - "AVG.Internet.Security.2014.Build.4335.vol00+01.PAR2" - 315,68 MB - yEnc
		if (preg_match('/^brothers-of-usenet\.info\.net.+SSL-News\.info-----(.+) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //>GOU<< ZDF.History.Das.Geiseldrama.von.Gladbeck.GERMAN.DOKU.720p.HDTV.x264-TVP >>www.SSL-News.info< - (02/35) - "tvp-gladbeck-720p.nfo" yEnc
		if (preg_match('/^>+GOU<+ (.+?) >+www\..+?<+ - \(\d+\/\d+\) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<usenet-space-cowboys.info>>> USC <<<Powered by https://secretusenet.com>>> [22/26] - "Zombie.Tycoon.2.Brainhovs.Revenge-SKIDROW.vol00+1.par2" - 1,85 GB yEnc
		if (preg_match('/^<<<usenet-space-cowboys\.info.+secretusenet\.com>>> \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //::: Usejunkies.tk ::: - [01/11] - "DJ Shog - DNA - HD 720p.par2" - 47,76 MB yEnc
		if (preg_match('/^.+Usejunkies.tk.+ \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(5/9) :...Usejunkies.tk...:----"Leawo.Blu-ray.Ripper.v4.4.0.rar.vol003+04.par2" - 30.94 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\).+Usejunkies.tk.+"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(Grimms03e10) [6/9] - "Grimm_S03E10_Eyes_of_the_Beholder.avi.vol007+08.PAR2"Grimm S03E10  yEnc
		if (preg_match('/^\(.+s\d+e\d+\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Jipejans post voor u op www.Dreamplace.biz - [010/568] - "Alien-Antology-DC-Special-Edition-1979-1997-1080p-GER-HUN-HighCode.part009.rar" yEnc
		//Egbert47 post voor u op www.nzbworld.me - [01/21] - "100 Hits - Lady Sings The Blues 2006 (5cd's).par2" yEnc
		if (preg_match('/^[a-zA-Z0-9]+ post voor u op www\..+? - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>>> usenet4ever.info <<<+>>> secretusenet.com <<< "Weltnaturerbe USA Grand Canyon Nationalpark 2012 3D Blu-ray untouched  - DarKneSS.part039.rar" - DarKneSS yEnc
		if (preg_match('/^>+ .+?\.info [<>+]+ .+?\.com <+ "(.+?)\s+- .*?' . $this->e0 . ' - .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Old Dad uppt   Der gro?e Gatsby   BD Rip AC3 Line XvidD German  01/57] - "Der gro?e Gatsby.par2" yEnc
		if (preg_match('/^Old\s+Dad\s+uppt?\s*?(.+?)( mp4| )?\[?\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return preg_replace('/\s\s+/', ' ', $match[1]);
		}
		//panter - [46/60] - "68645-Busty Beauties Car Wash XXX 3D BD26.part45.rar" yEnc
		//Wildrose - [01/57] - "49567-Kleine Rode Tractor Buitenpret.par2" yEnc
		if (preg_match('/^[A-Za-z]+ - \[\d+\/\d+\] - "\d+-(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<kere.ws> - FLAC - 1330543524 - Keziah_Jones-Femiliarise-PROMO_CDS-FLAC-2003-oNePiEcE - [01/11] - "00-keziah_jones-femiliarise-promo_cds-flac-2003-1.jpg" yEnc
		if (preg_match('/^<kere\.ws>[ _-]{0,3}\w+(-\w+)?[ _-]{0,3}\d+[ _-]{0,3}(.+) - \[\d+\/\d+\][ _-]{0,3}("|#34;).+?("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //Doobz Europa_Universalis_IV_Conquest_of_Paradise-FLT [10/54] - "flt-eucp.001" yEnc
		if (preg_match('/^Doobz ([a-zA-z-_]+) \[\d+\/(\d+\]) - ".+' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(aangemeld bij usenet collector) [001/125] - "Northern_Exposure_Season_1_dvd_1.par2" yEnc
		if (preg_match('/^\(aangemeld bij usenet collector\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[15/16] - "Person.of.Interest.S03E09.480p.HDTV.x264-mSD.vol0+1.par2" - 177,28 MB <-> usenet-space-cowboys.info <-> powered by secretusenet.com <-> yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] .+usenet-space-cowboys\.info.+ yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //brothers-of-usenet.info&net-empfehlen-ssl-news.info Black Mirror s01e01 - Der wille des volkes - [09/15] - "Black Mirror - S01E01 - Der Wille des Volkes.vol00+01.par2" yEnc
		if (preg_match('/^brothers-of-usenet\.info.+ - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(024/264) "Enf.S04E02.G.D.H.X-M.vol51+46.PAR2" - 8,27 GB -Enf.S04.G.D.H.X yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] .+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[01/10] - "Wondershare.Video.Converter.Ultimate.v6.7.1.0.Multilanguage.par2" - 45,44 MB yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //"8 Wenn ich einmal gross bin .mp3" Koelschefetz postet.Die Filue -Immer Wigger  yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Adobe Photoshop Lightroom v5.2 - FiNaL - Multilingual [WIN].vol037+32.PAR2  yEnc
		if (preg_match('/^(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) {0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function dc()
	{
		//brothers-of-usenet.info&net-empfehlen-ssl-news.info (02/51) "Paul.Panzer.-.Hart.Backbord.2012.German.PAL.DVDR-icq4711.part01.rar" - 4,33 GB yEnc
		if (preg_match('/^brothers-of-usenet.+? \(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"The.Crow.1994.German.DL.PAL.HD2DVD.DVDR-Braunbaer.par2" yEnc
		if (preg_match('/^"([\w.]{10,}-[a-zA-Z0-9]+)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Eragon postet  The Secret of Crickley Hall  S01E02  german Sub hardcodet      [02/28] - "the_secret_of_crickley_hall.1x02.hdtv_x264-fov_arc.par2" yEnc
		if (preg_match('/^[A-Z0-9].+? postet\s+.+?\s+\[\d+\/\d+\] - "([\w.-]{10,}?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Eragon postet Hart of Dixie S02E13 german Sub hardcodet. [02/21] - "hart of dixie S02E13.par2" yEnc
		if (preg_match('/^[A-Z0-9].+? postet\s+(.+?)\.?\s+\[\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>GOU<< - "Internet Download Manager 6.15 Build 1.rar" yEnc
		if (preg_match('/^>GOU<< - "(.+?)\.rar" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Die.wahren.Faelle.des.NCIS.S01E06.Mord.ohne.Skrupel.GERMAN.DOKU.WS.BDRip.XviD-MiSFiTS - "misfits-therealnciss01e06-xvid.par2" yEnc
		if (preg_match('/^([\w.]{8,}-[a-zA-Z0-9]+) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Double.Team.1997.German.FSK18.AC3.DVDRiP.XViD"team-xvid.oppo.par2" yEnc
		if (preg_match('/^([\w.]{10,})".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function divx_french()
	{
		//.oO "20.Years.After.German.2008.AC3.BDRip.XviD.INTERNAL-ARC__www.realmom.info__.nfo" Oo. [02/39] 1,43 GB yEnc
		if (preg_match('/^\.oO "(.+)__www.realmom.info__.+" Oo. \[\d+\/\d+\] \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function documentaries()
	{
		//#sterntuary - Alex Jones Radio Show - "05-03-2009_INFO_BAK_ALJ.nfo" yEnc
		if (preg_match('/^#sterntuary - (.+? - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//(08/25) "Wild Russia 5 of 6 The Secret Forest 2009.part06.rar" - 47.68 MB - 771.18 MB - yEnc
		//(01/24) "ITV Wild Britain With Ray Mears 1 of 6 Deciduous Forest 2011.nfo" - 4.34 kB - 770.97 MB - yEnc
		//(24/24) "BBC Great British Garden Revival 03 of 10 Cottage Gardens And House Plants 2013.vol27+22.PAR2" - 48.39 MB - 808.88 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "((BBC|ITV) )?(.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|") - \d.+? - (\d.+? -)? yEnc$/', $this->subject, $match)) {
			return $match[4];
		} //"Fat Head 2009 Tom Naughton (why we get fat documentary) XVID.par2" (01/66) Paleo Low Carb Gluten-Free Diet yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' \(\d+\/\d+\).+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[David.Attenboroughs.Natural.Curiosities.2013.5of5.Seeing.the.Pattern.720p.HDTV.x264.AAC-MVGroup]-[03/21] - "danc-e05.r00" yEnc
		if (preg_match('/^\[(.+?MVGroup)\]-\[\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Battlefield S1 S01E04 "Battlefield.104.The.Battle.of.Stalingrad.nfo" yEnc
		if (preg_match('/^Battlefield S\d S\d+E\d+ "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //BBC The Space Race 1to4of4 (2005) <docu-drama> - "BBC.SR01.par2" yEnc
		if (preg_match('/^(.+?) <docu-drama> - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[ BBC.Time.Machine.Part.1.of.3.2004.DVDRip.x264.AAC.Cht-Eng-Sub.D5799E84-GFC ] [01/17] - "gfc-tm1.sfv" yEnc
		//[ Discovery.Channel.Discovery.Atlantis.1996.DVDRip.X264.AC3-xslidian ] [01/12] - "d-da.sfv" yEnc
		//[ Mount.Fuji.2008.blu-ray.x264.720p.AC3-CHD ] [01/48] - "chd-mfj.sfv" yEnc
		if (preg_match('/^\[ (.+?) \] \[\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Rough Cut - Woodworking with Tommy Mac - Pilgrim Blanket Chest (1600s) DVDrip DivX - (02-17) "Rough.Cut-Woodworking.with.Tommy.Mac-Pilgrim.Blanket.Chest.1600s-DVDrip.DivX.2010.par2" - yEnc yEnc
		if (preg_match('/.*[\(\[]\d+-(\d+[\)\]])[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}("|#34;)).+?yEnc$/', $this->subject, $match)) {
			return $match[3];
		}
		//Asia This Week (NHK World, 19 & 20 July 2013) - 'Malala's movement for girls' education + Japan seeks imports from Southeast Asia - soccer players' - (02|14) - "ATW-2013-07-20.par2" yEnc
		//Asia Biz Forecast (NHK World, 6 & 7 July 2013) - 'China: limits of growth + Japan: remote access' - (05|14) - "ABF-2013-07-07.part3.rar" yEnc
		if (preg_match('/(Asia.+) - [\(\[]\d+(\|\d+[\)\]])[-_ ]{0,3}("|#34;).+?(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}("|#34;)).+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Asia Biz Forecast (NHK World, 16-17 June 2012) - "Japan seeks energy options" - File 01 of 14  - ABF-2012-06-16.nfo  (yEnc
		if (preg_match('/(Asia.+) - File \d+ of (\d+)[-_ ]{0,3}.+?(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}).+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Dark MatterDark Energy S02E06 - "Dark Matter_Dark Energy S02E06 - The Universe - History Channel.part1.rar"  51.0 MBytes yEnc
		if (preg_match('/^.+? S\d+E\d+ - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '  (\d+[,.]\d+ [kKmMgG][bB]ytes) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //-'- Doc2011 -'- 01/16 - Glorious.Accidents.2011.par2 yEnc
		if (preg_match('/^.+Doc2011.+ \d+\/\d+ - (.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"BBC.The.Treasures.of.Ancient.Rome.1of3.Warts.n.All.720p.HDTV.x264.AAC.MVGroup.org.mkv.025" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' {1,2}yEnc$/', $this->subject, $match)) {
			return preg_replace('/MVGroup.+/', 'MVGroup', $match[1]);
		} //"Time Team - 2012 - 05 - Beadnell, Northumbria.par2" -  [01 of 84 - 64 kb] yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' -  \[\d+ of \d+ - \d+([,.]\d+)? [kKmMgG][bB]\] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Lang Lang - Mendelssohn.m4v"  [2/9]  yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[-_ ]{0,3}\[\d+\/\d+\][-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(06/24) "BBC Upgrade Me 2009.part04.rar" - 47.68 MB - 807.79 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] .+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(35/45) - Keating Pt4 - "Keating Pt4.part34.rar" - 1.77 GB - yEnc
		if (preg_match('/\(\d+\/(\d+\) - .+) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(World Air Routes - WESTJET - B737-700) [028/109] - "World Air Routes - WESTJET - B737-700.part027.rar" yEnc
		if (preg_match('/^\(.+?\)\[\d+\/(\d+\][-_ ]{0,3}.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //Beyond Vanilla (2010) Documentary DVDrip XviD-Uncut - (02/22) "Beyond.Vanilla.2010.Documentary.DVDrip.XviD-Uncut.par2" - yenc yEnc
		if (preg_match('/(.+?) - [\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}("|#34;).+?(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Michael Woods, The Story Of India - Beginnings 1of6.avi.013 - [14/62] yEnc
		if (preg_match('/^(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) - \[\d+\/\d+\] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //PBS Nature The Private Life of Deer (01/23) - "PBS Nature 2013 The Private Life of Deer 720p x264 AAC.part01.rar" - 1.41 GB yEnc
		if (preg_match('/^PBS.+\(\d+\/\d+\) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //PBS - Inside Nature's Giants - Big Cats [100 of 101] "PBS - Inside Nature's Giants - Big Cats.m4v.vol970+143.par2" yEnc
		if (preg_match('/^PBS.+\[\d+ of \d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //59/63 "Gates Of Heaven.vol023+19.PAR2" yEnc
		if (preg_match('/^\d+\/\d+ "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Asia Biz Forecast (NHK World, 9 & 10 November 2013) - 'Mizuho: Shadow banking' (01|14) - "ABF-2013-11-09.nfo" yEnc
		if (preg_match('/^(.+?) \(\d+\|\d+\) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //TG1 "Top.gear.the.perfect.road.trip.2013.vol0000+128.PAR2" :: [01/10] yEnc
		if (preg_match('/^[\w ]+"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '(.+[\(\[]\d+\/\d+[\)\]])?[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[Walmart - The High Cost of Low Prices]-[28/59] - "wthcolp.part26.rar" yEnc
		if (preg_match('/^\[([\w- ]+)\]-\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[SR09-0013] - "America Revealed E02 Nation On The Move (2012) x264 720p AC3 5.1.nfo" yEnc
		if (preg_match('/^\[[\w- ]+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Panzer III - Medium Tank The German War Files - Panzer III - Germany's WWII Medium Tank.part05.rar" yEnc
		if (preg_match('/^([\w- ]+.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(World Air Routes - Turkish Airlines B777-300ER) [001/114] - "World Air Routes - Turkish Airlines B777-300ER.par2" yEnc
		if (preg_match('/^\(World Air Routes.+\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(????) [001/137] - "BBC.Attenborough.Zoo.Quest.Assorted.WebRips.par2" yEnc
		if (preg_match('/\(\?+\) \[\d+\/\d+\] - "(.+)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //dokus4alle presents (01/52) "BBC.Tweed.1of3.Trouble.Looms.H264.AAC.nfo" - 603.24 MB - yEnc
		if (preg_match('/^dokus4alle presents \(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //[U4A]-[After Porn Ends DOCU WEBRip X264 AAC]-[01/65] - "after.porn.ends.par2" yEnc
		if (preg_match('/^\[U4A\]-\[(.+)\]-\[\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function dvd()
	{
		//thnx to original poster [00/98] - "2669DFKKFD2008.nzb ` 2669DFKKFD2008 " yEnc
		if (preg_match('/^thnx to original poster \[\d+(\/\d+\] - ".+?)(\.part\d*|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4})("| `).+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function dvd_movies()
	{
		//Skata - Clouzot - Messa Da Requiem (49 / 79) - "Skata - Clouzot - MDR.part47.rar" yEnc
		if (preg_match('/^Skata - (.+) \(\d+ \/ \d+\) - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [02361/43619] - "18j  Amy-superlange Beine.exe" yEnc
		if (preg_match('/\(\?+\) \[\d+\/\d+\] - "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Mutant.Chronicles.German.2008.AC3.DVDRip.XviD.(01/40) "Mutant.Chronicles.German.2008.AC3.DVDRip.XviD.nfo" yEnc
		if (preg_match('/.*"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function dvdr()
	{
		//Golem.The.Petrified.Garden.1993.NTSC.DVDR-FiCODVDR [001/111] - #34;ficodvdr-golempet.nfo#34; yEnc
		if (preg_match('/^([a-zA-Z].+) \[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function dvd_german()
	{
		//.oO "20.Years.After.German.2008.AC3.BDRip.XviD.INTERNAL-ARC__www.realmom.info__.nfo" Oo. [02/39] 1,43 GB yEnc
		if (preg_match('/^\.oO "(.+)__www.realmom.info__.+" Oo. \[\d+\/\d+\] \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function dvd_r()
	{
		//katanxya "katanxya7221.par2" yEnc
		if (preg_match('/^katanxya "(katanxya\d+)/', $this->subject, $match)) {
			return $match[1];
		} //[01/52] - "H1F3E_20130715_005.par2" - 4.59 GB yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([A-Z0-9](19|20)\d\d[01]\d[123]\d_\d+\.).+?" - \d+[,.]\d+ [mMkKgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function ebook()
	{
		//New eBooks 8 June 2013 - "Melody Carlson - [Carter House Girls 08] - Last Dance (mobi).rar"
		if (preg_match('/^New eBooks.+[ _-]{0,3}("|#34;)([\w., &\'()-]{8,}?\b)\.(par|vol|rar|nfo).*?("|#34;)/i', $this->subject, $match)) {
			return $match[2];
		} //Rowwendees post voor u op www.nzbworld.me - [0/6] - "Animaniacs - Lights, Camera, Action!.nzb" yEnc (1/1)
		if (preg_match('/www.nzbworld.me - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' yEnc/', $this->subject, $match)) {
			return $match[1];
		} //Re: Tune In: The Beatles: All These Years (Mark Lewisohn) - Lewisohn, Mark - Tune In- the Beatles- All These Years, Volume 01 -  [epub].rar  yEnc
		//Re: REQ: Robert Edsel The Monuments Men - Edsel, Robert M - The Monuments Men- Allied Heroes, Nazi Thieves, and the Greatest Treasure Hunt in History (Retail) [epub].rar  yEnc
		if (preg_match('/^Re:(Req:)? [\w:()\?\' -]+ - ([\w ,.()\[\]-]{8,}?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[2];
		}
		if (preg_match('/^\(Nora Roberts\)"([\w., &\'()-]{8,}?\b)\.(epub|mobi|html|pdf|azw)" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Zelazny works) [36/39] - "Roger Zelazny - The Furies.mobi" yEnc
		if (preg_match('/\((.+works)\) \[\d+\/(\d+\]) - ([\w., &\'()-]{8,}?\b)\.(mobi|pdf|epub|html|azw)" yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //(Joan D Vinge sampler) [17/17] - "Joan D Vinge - World's End.txt" yEnc
		if (preg_match('/^\([a-zA-Z ]+ sampler\) \[\d+(\/\d+\]) - "([\w., &\'()-]{8,}?\b)\.(txt|pdf|mobi|epub|azw)" yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//New - Retail - Juvenile Fiction - "Magic Tree House #47_ Abe Lincoln at Last! - Mary Pope Osborne & Sal Murdocca.epub" yEnc
		//New - Retail - "Linda Howard - Cover of Night.epub" yEnc
		//New - Retail - "Kylie Logan_Button Box Mystery 01 - Button Holed.epub" yEnc
		if (preg_match('/^New - Retail -( Juvenile Fiction -)? "([\w., &\'()-]{8,}?\b)\.(txt|pdf|mobi|epub|azw)" yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(No. 1 Ladies Detective Agency) [04/13] - "Alexander McCall Smith - No 1-12 - The Saturday Big Tent Wedding Party.mobi" yEnc
		if (preg_match('/^\(No\. 1 Ladies Detective Agency\) \[\d+(\/\d+\]) - "([\w., &\'()-]{8,}?\b)\.(txt|pdf|mobi|epub|azw)" yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//[25/33] Philip Jose Farmer - Toward the Beloved City [ss].mobi
		//[2/4] Graham Masterton - Descendant.mobi
		if (preg_match('/^\[\d+\/(\d+\]) ([\w., &\'()-]{8,}?\b)\.(txt|pdf|mobi|epub|azw)/', $this->subject, $match)) {
			return $match[2];
		}
		//(NordicAlbino) [01/10] - "SWHQ_NA_675qe0033102suSmzSE.sfv" yEnc
		//365 Sex Positions A New Way Every Day for a Steamy Erotic Year [eBook] - (1/5) "365.Sex.Positions.A.New.Way.Every.Day.for.a.Steamy.Erotic.Year.eBook.nfo" - yenc yEnc
		if (preg_match('/(.+)[-_ ]{0,3}[\(\[]\d+\/\d+[\)\]][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '([-_ ]{0,3}yEnc){1,2}$/i', $this->subject, $match)) {
			return $match[2];
		} //[001/125] (NL Epub Wierook Set 49) - "Abulhawa, Susan - Litteken van David_Ochtend in Jenin.epub" yEnc
		if (preg_match('/^\[\d+\/\d+\] .+? - "([\w., &\'()-]{8,}?\b)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(1/1) "Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW.pdf" - 162,82 MB - (Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW) yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w., &\'()-]{8,}?\b)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(1/7) "0865779767.epub" - 88,93 MB - "Anatomic Basis of Neurologic Diagnosis - epub" yEnc
		if (preg_match('/^\(\d+\/\d+\) ".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")([-_ ]{0,3}\d+[.,]\d+ [kKmMgG][bB])?[-_ ]{0,3}"([\w., &\'()-]{8,}?\b)" yEnc$/', $this->subject, $match)) {
			return $match[4];
		}
		//Re: REQ: Jay Lake's Mainspring series/trilogy (see titles inside) - "Lake, Jay - Clockwork Earth 03 - Pinion [epub].rar"  405.6 kBytes yEnc
		//Attn: Brownian - "del Rey, Maria - Paradise Bay (FBS).rar" yEnc
		//New Scan "Herbert, James - Sepulchre (html).rar" yEnc
		if (preg_match('/^(Attn:|Re: REQ:|New Scan).+?[-_ ]{0,3}"([\w., &\'()-]{8,}?\b)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}(\d+[.,]\d+ [kKmMgG][bB](ytes)?)? yEnc$/i', $this->subject, $match)) {
			return $match[2];
		}
		//"Gabaldon, Diana - Outlander [5] The Fiery Cross.epub" yEnc
		//Kiny Friedman "Friedman, Kinky - Prisoner of Vandam Street_ A Novel, The.epub" yEnc
		if (preg_match('/.*"([\w., &\'()-]{8,}?\b)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Patterson flood - Mobi -  15/45  "James Patterson - AC 13 - Double Cross.mobi"
		if (preg_match('/(.+?)[-_ ]{0,4}\d+\/\d+[-_ ]{0,3}([\w. &\'()\[\]-]{8,}?\b.?)\.(txt|pdf|mobi|epub|azw)"( \(\d+\/\d+\))?( )?$/', $this->subject, $match)) {
			return $match[2];
		} //--- Michael Dobbs - House of Cards.mobi  yEnc
		if (preg_match('/^--- ([\w., &\'()-]{8,}?\b)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //'Steel's Edge - Ilona Andrews.epub'  yEnc
		if (preg_match('/^\'([\w. &\'()\[\]-]{8,}?\b.?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})\'[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[1 of 1] - Howard Gordon - Gideon's War & Hard Target.epub  yEnc
		if (preg_match('/^\[\d+ of \d+\][- ]{0,3}([\w. &\'()\[\]-]{8,}?\b.?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //1 Playboy-Ausgabe vom Jnner 1953 [RARITT].rar  yEnc
		if (preg_match('/^([\w. &\'\[\]-]{8,}?\b.?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Re: Req: Please, does anyone have Scott Berg's Wilson biography? MTIA... - A. Scott Berg - Wilson.epub  yEnc
		if (preg_match('/^.+ - ([\w.,& ()\[\]\'\`-]{8,}?\b.?)\.[A-Za-z0-9]{2,4}[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //REQ: "Keep it Pithy" by Bill O'Reilly "Keep It Pithy - Bill O'Reilly.epub"yEnc
		if (preg_match('/.*"([\w.,& ()\[\]\'\`-]{8,}?\b.?)\.[A-Za-z0-9]{2,4}"[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //For Your Eyes Only - Ian Fleming.epub - answering my own request  yEnc
		if (preg_match('/^([\w.,& ()\[\]\'\`-]{8,}?\b.?)\.[A-Za-z0-9]{2,4}.+[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function e_book()
	{
		//New eBooks 8 June 2013 - "Melody Carlson - [Carter House Girls 08] - Last Dance (mobi).rar"
		if (preg_match('/^New eBooks.+[ _-]{0,3}("|#34;)(.+?.+)\.(par|vol|rar|nfo).*?("|#34;)/i', $this->subject, $match)) {
			return $match[2];
		}
		if (preg_match('/^\(Nora Roberts\)"(.+?)\.(epub|mobi|html|pdf|azw)" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Doctor Who - Target Books [128/175] - "DW125_ Terror of the Vervoids - Pip Baker.mobi" yEnc
		if (preg_match('/^Doctor Who - Target Books \[\d+\/(\d+\]) - "DW[0-9]{0,3}[-_ ]{0,3}(.+?)\.(txt|pdf|mobi|epub|azw)" yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(American Curves - Summer 2012) [01/10] - "AMECURSUM12.par2" yEnc
		if (preg_match('/^\(([a-zA-Z0-9 -]+)\) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//(NordicAlbino) [01/10] - "SWHQ_NA_675qe0033102suSmzSE.sfv" yEnc
		//365 Sex Positions A New Way Every Day for a Steamy Erotic Year [eBook] - (1/5) "365.Sex.Positions.A.New.Way.Every.Day.for.a.Steamy.Erotic.Year.eBook.nfo" - yenc yEnc
		if (preg_match('/(.+)[-_ ]{0,3}[\(\[]\d+\/\d+[\)\]][-_ ]{0,3}"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//[1/8] - "Robin Lane Fox - Travelling heroes.epub" yEnc
		//(1/1) "Unintended Consequences - John Ross.nzb" - 8.67 kB - yEnc
		if (preg_match('/^[\(\[]\d+\/\d+[\)\]][-_ ]{0,3}"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")([-_ ]{0,3}\d+[.,]\d+ [kKmMgG][bB])?[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ Mega Dating and Sex Advice Ebooks - Tips and Tricks for Men PDF ] - "Vatsyayana - The Kama Sutra.pdf.rar" - (54/58) yEnc
		if (preg_match('/^[\(\[] .+? [\)\][-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //WWII in Photos - "WWII in Photos_05_Conflict Spreads Around the Globe - The Atlantic.epub" yEnc
		if (preg_match('/^(WWII in Photos)[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //Various ebooks on History pdf format  "Chelsea House Publishing Discovering U.S. History Vol. 8, World War I and the Roaring Twenties - 1914-1928 (2010).pdf"  [1 of 1] yEnc
		if (preg_match('/^.+?"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}\[\d+ of (\d+\])[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //A few things -  [4 of 13] "Man From U.N.C.L.E. 08 - The Monster Wheel Affair - David McDaniel.epub" yEnc
		if (preg_match('/.+[\(\[]\d+ of \d+[\)\]] "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //DDR Kochbuch 1968-wir kochen gut [1/1] - "DDR Kochbuch 1968-wir kochen gut.pdf" toby042002
		if (preg_match('/.+[\(\[]\d+\/\d+[\)\]] - "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") toby\d+$/', $this->subject, $match)) {
			return $match[1];
		} //Pottermore UK retail - "Harry Potter and the Goblet of Fire - J.K. Rowling.epub" (05/14) - 907.57 kB - yEnc
		if (preg_match('/^.+?[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") [\(\[]\d+\/\d+[\)\]] ([-_ ]{0,3}\d+[.,]\d+ [kKmMgG][bB])?[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[001/125] (NL Epub Wierook Set 49) - "Abulhawa, Susan - Litteken van David_Ochtend in Jenin.epub" yEnc
		if (preg_match('/^\[\d+\/\d+\] .+? - "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(1/1) "Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW.pdf" - 162,82 MB - (Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW) yEnc
		if (preg_match('/^\(\d+\/\d+\) "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(1/7) "0865779767.epub" - 88,93 MB - "Anatomic Basis of Neurologic Diagnosis - epub" yEnc
		if (preg_match('/^\(\d+\/\d+\) ".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")([-_ ]{0,3}\d+[.,]\d+ [kKmMgG][bB])?[-_ ]{0,3}"(.+?)" yEnc$/', $this->subject, $match)) {
			return $match[4];
		}
		//Re: REQ: Jay Lake's Mainspring series/trilogy (see titles inside) - "Lake, Jay - Clockwork Earth 03 - Pinion [epub].rar"  405.6 kBytes yEnc
		//Attn: Brownian - "del Rey, Maria - Paradise Bay (FBS).rar" yEnc
		//New Scan "Herbert, James - Sepulchre (html).rar" yEnc
		if (preg_match('/^(Attn:|Re: REQ:|New Scan).+?[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}(\d+[.,]\d+ [kKmMgG][bB](ytes)?)? yEnc$/i', $this->subject, $match)) {
			return $match[2];
		}
		//"Gabaldon, Diana - Outlander [5] The Fiery Cross.epub" yEnc
		//Kiny Friedman "Friedman, Kinky - Prisoner of Vandam Street_ A Novel, The.epub" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Patterson flood - Mobi -  15/45  "James Patterson - AC 13 - Double Cross.mobi"
		if (preg_match('/(.+?)[-_ ]{0,3}\d+\/(\d+[-_ ]{0,3}".+?)\.(txt|pdf|mobi|epub|azw)"( \(\d+\/\d+\))?( )?$/', $this->subject, $match)) {
			return $match[2];
		} //04/63  Brave New World Revisited - Aldous Huxley.mobi  yEnc
		if (preg_match('/\d+\/\d+[-_ ]{0,3}(.+)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //- Campbell, F.E. - Susan - HIT 125.rar  BDSM Themed Adult Erotica - M/F F/F - Rtf & Pdf
		if (preg_match('/^- (.+?)\.(par|vol|rar|nfo)[-_ ]{0,3}(.+)/', $this->subject, $match)) {
			return $match[1];
		} //"D. F. Jones - 03 - Colossus and The Crab.epub" (1/3)
		if (preg_match('/^"(.+?)\.(txt|pdf|mobi|epub|azw)" \(\d+\/(\d+\))/', $this->subject, $match)) {
			return $match[1];
		} //"D. F. Jones - 01 - Colossus.epub" (note the space on the end)
		if (preg_match('/^"(.+?)\.(txt|pdf|mobi|epub|azw|lit|rar|nfo|par)" $/', $this->subject, $match)) {
			return $match[1];
		} //[01/19] - "13_X_Panzer_Tracts_EBook.nfo " yEnc
		if (preg_match('/^\[\d*+\/(\d+\]) - "(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4} "|") yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//[09/14] Sven Hassel - Legion of the Damned 09, Reign of Hell.mobi  sven hassel as requested (1/7) yEnc
		//[1/1] Alex Berenson - John Wells 05, The Secret Soldier.mobi (space at end)
		if (preg_match('/^\[\d+\/(\d+\]) (.+?)\.(txt|pdf|mobi|epub|azw|lit|rar|nfo|par).+?(yEnc)?$/', $this->subject, $match)) {
			return $match[2];
		}
		//[1/1] - "Die Kunst der Fotografie Lehrbuch und Bildband f  r ambitionierte Fotografen.rar"
		//[1/1] - "Demonic_ How the Liberal Mob Is Endanger - Coulter, Ann.mobi" (note space at end)
		//[1/1] - "Paris in Love_ A Memoir - Eloisa James.mobi"  1861K
		if (preg_match('/^\[\d+\/(\d+\]) - "(.+?)\.(txt|pdf|mobi|epub|azw|lit|rar|nfo|par)"(  \d+K)?( )?$/', $this->subject, $match)) {
			return $match[2];
		} //002/240  Swordships.of.Scorpio.(Dray.Prescot).-.Alan.Burt.Akers.epub
		if (preg_match('/^\d+\/(\d+)[-_ ]{0,3}(.+?)\.(txt|pdf|mobi|epub|azw|lit|rar|nfo|par)$/', $this->subject, $match)) {
			return $match[2];
		} //Akers Alan Burt - Dray Prescot Saga 14 - Krozair von Kregen.rar yEnc
		if (preg_match('/^([a-zA-Z0-9. ].+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|) yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function e_book_flood()
	{
		//New eBooks 8 June 2013 - "Melody Carlson - [Carter House Girls 08] - Last Dance (mobi).rar"
		if (preg_match('/^New eBooks.+[ _-]{0,3}("|#34;)(.+?.+)\.(par|vol|rar|nfo).*?("|#34;)/i', $this->subject, $match)) {
			return $match[2];
		}
		//World War II History - "Spies of the Balkans - Alan Furst.mobi" yEnc
		//True Crime  "T. J. English - Havana Nocturne (v5.0).mobi" yEnc
		//E C Tubb Flood - "E C Tubb - Dumarest 31 The Temple of Truth.epub" - yEnc
		if (preg_match('/^[A-Za-z ]+[-_ ]{0,3}"(.+?)\.(txt|pdf|mobi|epub|azw)"[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //SFF Dump - "Thomas M. Disch - Camp Concentration.epub" (1033/1217) - 226.47 kB - yEnc
		if (preg_match('/^SFF Dump - "(.+?)\.(txt|pdf|mobi|epub|azw)" \(\d+\/\d+\) - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(American Curves - Summer 2012) [01/10] - "AMECURSUM12.par2" yEnc
		if (preg_match('/^\(([a-zA-Z0-9 -]+)\) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Patterson flood - Mobi -  15/45  "James Patterson - AC 13 - Double Cross.mobi"
		if (preg_match('/(.+?)[-_ ]{0,3}\d+\/(\d+[-_ ]{0,3}".+?)\.(txt|pdf|mobi|epub|azw)"( \(\d+\/\d+\))?( )?$/', $this->subject, $match)) {
			return $match[2];
		}
		//Re: REQ: Jay Lake's Mainspring series/trilogy (see titles inside) - "Lake, Jay - Clockwork Earth 03 - Pinion [epub].rar"  405.6 kBytes yEnc
		//Attn: Brownian - "del Rey, Maria - Paradise Bay (FBS).rar" yEnc
		//New Scan "Herbert, James - Sepulchre (html).rar" yEnc
		if (preg_match('/^(Attn:|Re: REQ:|New Scan).+?[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}(\d+[.,]\d+ [kKmMgG][bB](ytes)?)? yEnc$/i', $this->subject, $match)) {
			return $match[2];
		}
		//"Gabaldon, Diana - Outlander [5] The Fiery Cross.epub" yEnc
		//Kiny Friedman "Friedman, Kinky - Prisoner of Vandam Street_ A Novel, The.epub" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[001/125] (NL Epub Wierook Set 49) - "Abulhawa, Susan - Litteken van David_Ochtend in Jenin.epub" yEnc
		if (preg_match('/^\[\d+\/\d+\] .+? - "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(1/1) "Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW.pdf" - 162,82 MB - (Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW) yEnc
		if (preg_match('/^\(\d+\/\d+\) "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(1/7) "0865779767.epub" - 88,93 MB - "Anatomic Basis of Neurologic Diagnosis - epub" yEnc
		if (preg_match('/^\(\d+\/\d+\) ".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")([-_ ]{0,3}\d+[.,]\d+ [kKmMgG][bB])?[-_ ]{0,3}"(.+?)" yEnc$/', $this->subject, $match)) {
			return $match[4];
		}
		//Re: REQ: Jay Lake's Mainspring series/trilogy (see titles inside) - "Lake, Jay - Clockwork Earth 03 - Pinion [epub].rar"  405.6 kBytes yEnc
		//Attn: Brownian - "del Rey, Maria - Paradise Bay (FBS).rar" yEnc
		//New Scan "Herbert, James - Sepulchre (html).rar" yEnc
		if (preg_match('/^(Attn:|Re: REQ:|New Scan).+?[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}(\d+[.,]\d+ [kKmMgG][bB](ytes)?)? yEnc$/i', $this->subject, $match)) {
			return $match[2];
		}
		//*FULL REPOST* New eBooks 26 Nov 2012 & 20% PAR2 Set -  "Elisabeth Kyle - The Captain's House (siPDF).rar"
		//*REPOST* New eBooks 23 Nov 2012 -  "Charles Culver - [The 11th Floor 02] - Awakening (mobi).rar"
		if (preg_match('/^\*(FULL )?REPOST\* New eBooks.+[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")$/', $this->subject, $match)) {
			return $match[2];
		}
		//1 - 5 July 2013 - Search for number at end of title - Bevin Alexander - How Hitler Could Have Won World War II- The Fatal Errors That Lead to Nazi Defeat (epub).rar - 14418-25255-6053.rar.txt yEnc
		//10 July 2013 - Search 4 Numeric String at End of Subject - Andew Hodges - Alan Turing- The Enigma (Centenary Edition) (kf8 mobi).rar = 21317-25234-21710.rar.txt yEnc
		if (preg_match('/^.+?Search (for|4) (number|Numeric String) at end of (title|Subject)[-_ ]{0,3}(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4})[-\=_ ]{0,3}\d+[-_ ]{0,3}.+?yEnc$/i', $this->subject, $match)) {
			return $match[4];
		}
		//"Gabaldon, Diana - Outlander [5] The Fiery Cross.epub" yEnc
		//Kiny Friedman "Friedman, Kinky - Prisoner of Vandam Street_ A Novel, The.epub" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Back to Pakistan_ A Fifty-Year Journey - Leslie Noyes Mass.pdf"  2778K
		if (preg_match('/^"(.+)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}\d+[kKmMgG]$/', $this->subject, $match)) {
			return $match[1];
		}
		//[002/182] A. E. Van Vogt - The Anarchistic Colossus.mobi  mobi flood
		//[002/115] Alan Dean Foster - Alien.mobi  sf single author flood
		if (preg_match('/^\[\d+\/\d+\][-_ ]{0,3}(.+)\.(txt|pdf|mobi|epub|azw)[-_ ]{0,3}.+flood( )?$/', $this->subject, $match)) {
			return $match[1];
		} //[2/4] Graham Masterton - Descendant.mobi
		if (preg_match('/^\[\d+\/(\d+\]) (.+?)\.(txt|pdf|mobi|epub|azw)/', $this->subject, $match)) {
			return $match[2];
		}
		//14. Alexander Kent - [Bolitho 12] - Signal Close Action (v2.0) (epub).epubAs Req Alexander Kent  yEnc
		//22.The Darkening Sea - Alexander Kent.epubAs Req Alexander Kent  yEnc
		if (preg_match('/^\d+\. {0,1}(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})As Req.+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //reposting 2012-10-31 new ebooks with 35.74 pars - AD&D - [Tomes] - The Rod of Seven Parts - Douglas Niles (mobi).rar  yEnc
		if (preg_match('/^reposting \d+-\d+-\d+ new ebooks.+ pars[- ]{0,3}(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})  yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //15 - Men at Arms.pdf  yEnc
		if (preg_match('/^\d+ - (.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})  yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		//Filling Req for Delderfield- To Serve Them All My Days - R F Delderfield.mobi [6/6] -  yEnc
		//Filling Request - Armistead Maupin - 1 - Tales of the City.mobi [1/8] -  yEnc
		if (preg_match('/^Filling Req(uest)?( for \w+)?[- ]{0,3}(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) \[\d+\/\d+\] -  yEnc$/i', $this->subject, $match)) {
			return $match[3];
		} //ATTN:Fozz E Bear Rogue - Danielle Steel.epub  yEnc
		if (preg_match('/^ATTN:Fozz E Bear (.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})  yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //As Requested - Silent Spring - Rachel Carson.epub [1/2] -  yEnc
		if (preg_match('/^As Requested - (.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) \[\d+\/\d+\] -  yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //Attn: fastpiety - your requests - Countess of Carnarvon - Lady Almina and the Real Downton Abbey- The Lost Legacy of Highclere Castle (epub).epub [1/1] -  yEnc
		if (preg_match('/^Attn: fastpiety - your requests[- ]{0,3}(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) \[\d+\/\d+\] -  yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //Amanda Ashley - [Night 02] - Night's Touch (v5.0) (epub).epub  yEnc
		if (preg_match('/^(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})  yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //A Game of Thrones 01- George R. R. Martin.mobi_as found  yEnc
		if (preg_match('/^([\w.,& ()\[\]\'\`-]{8,}?\b.?)\.[A-Za-z0-9]{2,4}_as found[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Ben Carson - America the Beautiful- Rediscovering What Made This Nation Great (epub).epub - File 1 of 1 - yEnc
		if (preg_match('/^([\w.,& ()\[\]\'\`-]{8,}?\b.?)\.[A-Za-z0-9]{2,4}[- ]{0,3}File \d+ of \d+[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Re: Attn Reg143- (Morse 7) The Secret of Annexe 3 - Colin Dexter.mobi [11/14] -  yEnc
		if (preg_match('/^Re: Attn Reg143- ([\w.,& ()\[\]\'\`-]{8,}?\b.?)\.[A-Za-z0-9]{2,4}[- ]{0,3}\[\d+\/\d+\][- ]{0,4}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //New eBooks 20 Aug 2012 - File 001 of 409 - yEnc
		if (preg_match('/^([\w.,& ()\[\]\'\`-]{8,}?)[- ]{0,3}File \d+ of \d+[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Re: Request  - 05 L. E. Modesitt - Princeps.mobi [5/7] -  yEnc
		if (preg_match('/^Re: Request[- ]{0,4}\d+[- ]{0,3}([\w.,& ()\[\]\'\`-]{8,}?\b.?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}\[\d+\/\d+\][- ]{0,4}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function e_book_rpg()
	{
		//ATTN: falsifies RE: REQ:-Pathfinder RPG anything at all TIA [362/408] - "Pathfinder_-_PZO1110B_-_Pathfinder_RPG_-_Beta_Playtest_-_Prestige_Enhancement.pdf" yEnc
		if (preg_match('/^.+?\[\d+\/(\d+\]) - "(.+?)\.(txt|pdf|mobi|epub|azw)" yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function ebook_magazines()
	{
		// [Top.Gear.South.Africa-February.2014] - "Top.Gear.South.Africa-February.2014.pdf.vol00+1.par2" yEnc  - 809.32 KB
		if (preg_match('/^\[(.+?)\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function ebook_technical()
	{
		//ASST NEW MTLS 13 MAR 2012 A  -  [106/116] - "The Elements of Style, Illus. - W. Strunk Jr., E. White, M. Kalman (Penguin, 2005) WW.pdf" yEnc
		if (preg_match('/ASST NEW MTLS.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function erotica()
	{
		//[######]-[FULL]-[#a.b.teevee@EFNet]-[ Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD ] [1/5] - "Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD.nfo" yEnc
		if (preg_match('/\[#+\]-\[.+?\]-\[.+?\]-\[ (.+?) \][- ]\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Brazilian.Transsexuals.SR.UD.12.28.13.HD.720p.HDL [19 of 24] "JhoanyWilkerXmasLD_1_hdmp4.mp4.vol00+1.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9._-]+)[-_ ]{0,3}[\(\[]\d+ of (\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //NihilCumsteR [1/8] - "Conysgirls.cumpilation.xxx.NihilCumsteR.par2" yEnc
		if (preg_match('/^NihilCumsteR \[\d+\/\d+\] - "(.+?)NihilCumsteR\./', $this->subject, $match)) {
			return $match[1];
		} //"Lesbian seductions 26.part.nzb" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[01/13] - "Mahina.Zaltana.14.01.17.720p.x264-DFury.7z.001" yEnc
		if (preg_match('/^\[\d+\/\d+\][- ]{0,3}"([\w.()-]{8,}?\b)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>>>>>Hell-of-Usenet.org>>>>> - [01/23] - "Cum Hunters 3 XXX.par2" yEnc
		if (preg_match('/^[><]+Hell-of-Usenet\.org[<>]+ - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Lesbian Crush Diaries 5 XXX DVDRip x264-Pr0nStarS - (01/26) "Lesbian.Crush.Diaries.5.XXX.DVDRip.x264-Pr0nStarS.nfo" - yenc
		if (preg_match('/^[A-Z0-9][a-zA-Z0-9 -]{6,}? - \(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - yenc yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Megan Coxxx Takes Out Her Favourite Strap On Dildos And Plays With Her Girlfriend Re - File 01 of 67 - "Toy_Stories.r00.par2" yEnc
		if (preg_match('/^([A-Z0-9][a-zA-Z0-9 ]{6,}?) - File \d+ of \d+ - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[02/21] - "Staendig Feucht.part01.rar" - 493.38 MB ....::::UR-powered by SecretUsenet.com::::.... yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Big Tits in Sport 12 (2013) XXX DVDRip x264-CHiKANi - (03/39) "Big.Tits.in.Sport.12.XXX.DVDRip.x264-CHiKANi.part01.rar" - yenc yEnc
		if (preg_match('/^([A-Z0-9].{5,}?) - \(\d+\/\d+\) "[A-Z0-9].{5,}?" - yenc yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Babysitters_a_Slut_4_Scene_4.part01.rar"_SpotBots yEnc
		if (preg_match('/^"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<>>CowboyUp2012 XXX><<<Is.Not.Force.It.My.Younger.SOE-806.Jav.Censored.DVDRip.XviD-MotTto>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "Is.Not.Force.It.My.Younger.SOE-806.Jav.Censored.DVDRip.XviD-MotTto.part01.rar" >< 01/15 (1,39
		if (preg_match('/^.+?usenet-space.+?Powered by.+? "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+? \d+\/(\d+.+?)$/', $this->subject, $match)) {
			return $match[1];
		} //Fake Taxi E58 - Taylor[01/23] - "faket.e58.taylor.part01.rar" yEnc
		if (preg_match('/^(Fake Taxi E\d+.+?)\[\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //PNG170514XW042SOE9[01/35] - "PNG170514XW042SOE9.jpg" yEnc
		if (preg_match('/^[\w.,& ()\[\]\'\`-]{8,}?\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			if (!preg_match('/^\[ ?\d+ ?\]/', $this->subject)) {
				return $match[1];
			}
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function etc()
	{
		//[scnzbefnet] Were.the.Millers.2013.EXTENDED.720p.BluRay.x264-SPARKS [01/61] - "were.the.millers.2013.extended.720p.bluray.x264-sparks.nfo" yEnc
		if (preg_match('/^\[scnzbefnet\] (.+?) \[\d+\/(\d+\]) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //..:[DoAsYouLike]:..    116,12 MB    "Graphite v8.9.17 SP4R4.nfo"    3,00 kB yEnc
		if (preg_match('/^.+\[DoAsYouLike\].+\d+[.,]\d+ [kKmMgG][bB].+"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '    (\d+[.,])?\d+ ([kKmMgG])?[bB][- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(047/550) "Enf.S02E02.G.D.D.W.7.H.x2-e.vol105+99.PAR2" - 36,13 GB -Enf.S02.G.D.D.W.7.H.x2-e yEnc
		//[3/3] - "Mayle Peter - Der Coup von Marseille.epub.vol0+1.par2" - 956,83 kB {UR} yEnc
		if (preg_match('/^[\(\[]\d+\/\d+[\)\]][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<Old but Sold>>>  <<< >< >< "German Top 50 ODC - 12.08.2013.nfo" >< 02/33 (541,61 MB) >< 10,93 kB > yEnc
		if (preg_match('/^.+Old but Sold.+>< "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' >< \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\).+ yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<THOR for Usenet-Space-Cowboys>>> - 1Password V1.0.9.236 Setup Key.PAR2  yEnc
		if (preg_match('/^.+Usenet-Space-Cowboys.+> - ([A-Z0-9][a-zA-Z0-9\. ]{6,})([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}).+ yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //704900999555666777123978 - [398/415] - "Jormungand Complete [720p] mHD.part31.rar" yEnc
		if (preg_match('/^\d+[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Man.Of.Steel.2013.BRRip.x264.AC3-UNiQUE - [02/38] - "Man.Of.Steel.2013.BRRip.x264.AC3-UNiQUE.part01.rar" yEnc
		if (preg_match('/^[\w. -]+[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"([\w.()-]{8,}?\b)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<<<< old but sold >>>> < USC> <"K11 - Kommissare im Einsatz (DE) NDS.part3.rar">[04/11] 74,54 MB yEnc
		if (preg_match('/^.+old but sold.+([\w. ()-]{8,}?\b)' . $this->e0 . '>\[\d+\/\d+\][- ]{0,3}\d+[.,]\d+ [kKmMgG][bB][- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Uploader.Presents-ACDC.Let.There.Be.Rock.1980.720p.BluRay.DD5.1.x264-DON(00/47]"rock.ac3.720p.nzb" yEnc
		if (preg_match('/^Uploader\.Presents-([\w. ()-]{8,}?\b)[\(\[]\d+\/\d+\]".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Bitsum.Technologies.Process.Lasso.Pro.v6.5.00.Cracked-EAT - [3/4] - "eat.vol0+1.par2" yEnc
		if (preg_match('/^([\w.()-]{8,})[- ]{0,3}\[\d+\/\d+\][- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<<<usenet-space-cowboys.info>>> USC <<<Powered by https://secretusenet.com><Medieval Wii Pal>< "Medieval Wii Pal.vol108+29.par2" >< 60/60 (4,82 GB) >< 95,77 MB > yEnc
		if (preg_match('/^.+?usenet-space.+?Powered by.+? "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+? \d+\/(\d+.+?)$/', $this->subject, $match)) {
			return $match[1];
		} //<<<< old but sold >>>> < USC> <"K11 - Kommissare im Einsatz (DE) NDS.part3.rar">[04/11] 74,54 MB yEnc
		if (preg_match('/^.+old but sold.+([\w. ()-]{8,}?\b)' . $this->e0 . '>\[\d+\/\d+\][- ]{0,3}\d+[.,]\d+ [kKmMgG][bB][- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Uploader.Presents-ACDC.Let.There.Be.Rock.1980.720p.BluRay.DD5.1.x264-DON(00/47]"rock.ac3.720p.nzb" yEnc
		if (preg_match('/^Uploader\.Presents-([\w. ()-]{8,}?\b)[\(\[]\d+\/\d+\]".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Bitsum.Technologies.Process.Lasso.Pro.v6.5.00.Cracked-EAT - [3/4] - "eat.vol0+1.par2" yEnc
		if (preg_match('/^([\w.()-]{8,})[- ]{0,3}\[\d+\/\d+\][- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<<<usenet-space-cowboys.info>>> USC <<<Powered by https://secretusenet.com><Medieval Wii Pal>< "Medieval Wii Pal.vol108+29.par2" >< 60/60 (4,82 GB) >< 95,77 MB > yEnc
		if (preg_match('/^.+?usenet-space.+?Powered by.+? ([\w. ()-]{8,}?\b)' . $this->e0 . '.+? \d+\/(\d+.+?)$/', $this->subject, $match)) {
			return $match[1];
		} //W33d5.S01.720p.BluRay.x264 - "W33d5.S01.720p.BluRay.x264.part63.rar" yEnc
		if (preg_match('/^[\w. -]+[- ]{0,3}"([\w.()-]{8,}?\b)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Man.Of.Steel.2013.BRRip.x264.AC3-UNiQUE - [02/38] - "Man.Of.Steel.2013.BRRip.x264.AC3-UNiQUE.part01.rar" yEnc
		if (preg_match('/^[\w. -]+[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"([\w.()-]{8,}?\b)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>GOU<< XUS Clock Plus v1.5.0 with Key [TorDigger].PAR2 >>www.SSL-News.info<  yEnc
		if (preg_match('/^>GOU<< ([\w.\[\]() -]{8,}?\b)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rarr|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}).+www\.SSL-News\.info<[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Man.Of.Steel.2013.BRRip.x264.AC3-UNiQUE - [02/38] - "Man.Of.Steel.2013.BRRip.x264.AC3-UNiQUE.part01.rar" yEnc
		if (preg_match('/^[\w. -]+[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"([\w.()-]{8,}?\b)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>GOU<< XUS Clock Plus v1.5.0 with Key [TorDigger].PAR2 >>www.SSL-News.info<  yEnc
		if (preg_match('/^>GOU<< ([\w.\[\]() -]{8,}?\b)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rarr|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}).+www\.SSL-News\.info<[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Drains_READNFO-VACE.nfo" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function ftn()
	{
		//(aangemeld bij usenet collector) [181/190] - "Ally_McBeal_Season_3_Dvd_4.vol0176+176.par2" yEnc
		//Usenet collector(aangemeld bij usenet collector) [001/124] - "Northern_Exposure_Season_4_dvd_2.par2" yEnc
		if (preg_match('/^(Usenet collector)?\(aangemeld.+\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //"Family Games-OUTLAWS.nfo" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Adobe Photoshop Lightroom v5.2 - FiNaL - Multilingual [WIN].vol037+32.PAR2  yEnc
		if (preg_match('/^(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) {0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Wondershare AllMyTube 3.8.0.4 + Patch) [01/12] - "Wondershare AllMyTube 3.8.0.4 + Patch.nfo" yEnc
		if (preg_match('/^\(([\w+ .()-]{8,})\)[- ]{0,3}\[\d+\/\d+\][- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(????) [10/26] - "The.Young.Riders.S01E02.480pWEB-DL.AAC2.0.H.264-AJP69.part09.rar" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function fz()
	{
		//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
		if (preg_match('/^>ghost-of-usenet\.org>(.+?)<.+?> ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function game()
	{
		//[192474]-[MP3]-[a.b.inner-sanctumEFNET]-[ Newbie_Nerdz_-_I_Cant_Forget_that_Girl_EP-(IM005)-WEB-2012-YOU ] [17/17] - "newbie_nerdz_-_i_cant_forget_that_girl_ep-(im005)-web-2012-you.nfo" yEnc
		if (preg_match('/(\[[\d#]+\]-\[.+?\]-\[.+?\]-)\[ (.+?) \][- ]\[\d+\/\d+\] - "(.+?)" yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function games()
	{
		//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
		if (preg_match('/^>ghost-of-usenet\.org>(.+?)<.+?> ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
		if (preg_match('/^<ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ Dawn.of.Fantasy.Kingdom.Wars-PROPHET ] - [12/52] - "ppt-dfkw.part04.rar" yEnc
		if (preg_match('/^\[ ([-.a-zA-Z0-9]+) \] - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [11/17] - "Reload.Outdoor.Action.Target.Down.GERMAN-0x0007.vol003+004.PAR2" yEnc
		if (preg_match('/\.net <<<Partner von SSL-News\.info>>> - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[162198]-[FULL]-[a.b.teevee]-[ MasterChef.Junior.S01E07.720p.HDTV.X264-DIMENSION ]-[09/54] - "masterchef.junior.107.720p-dimension.nfo" yEnc
		if (preg_match('/\[[\d#]+\]-\[.+?\]-\[.+?\]-\[ (.+?) \][- ]\[\d+\/(\d+\])[ -]{0,3}("|#34;).+?/', $this->subject, $match)) {
			return $match[1];
		} //"A.Stroke.of.Fate.Operation.Valkyrie-SKIDROW__www.realmom.info__.nfo" (02/38) 1,34 GB yEnc
		if (preg_match('/^"(.+)__www.realmom.info__.+" \(\d+\/\d+\) \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Mad.Men.S06E11.HDTV.x264-2HD.par2" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"Marvels.Agents.of.S.H.I.E.L.D.S01E07.HDTV.XviD-FUM.avi.nfo" [09/16] yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\[\d+\/(\d+\])[ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [03/20] - "Weblinger - The.Haunted.House.Mysteries.v1.0-ZEKE.part01.rar" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//(001/132) "Harry.Potter.And.The.Goblet.Of.Fire.2005.810p.BluRay.x264.DTS.PRoDJi.nfo" - 8,71 GB - yEnc
		//(01/11) - Description - "ba588f108dbd068dc93e4b0182de652d.par2" - 696,63 MB - yEnc
		//(01/11) "Microsoft Games for Windows 8 v1.2.par2" - 189,87 MB - [REPOST] yEnc
		//(01/24) "ExBrULlNjyRPMdxqSlJKEtAYSncStZs3.nfo" - 3.96 kB - 404.55 MB - yEnc
		//(01/44) - - "Wii_2688_R_Knorloading.par2" - 1,81 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\)( - Description)?[-_ ]{0,5}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '( - \d+([.,]\d+ [kKmMgG])?[bB])? - \d+([.,]\d+ [kKmMgG])?[bB][-_ ]{0,3}(\[REPOST\] )?yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(01/59) - [Lords-of-Usenet] presents Sins.of.a.Solar.Empire.Rebellion.Forbidden.Worlds-RELOADED - "rld-soaserfw.nfo" - yEnc
		if (preg_match('/^\(\d+\/\d+\) - \[Lords-of-Usenet\] presents (.+?)[-_ ]{0,3}".+?' . $this->e0 . ' - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(19/28) "sr-joedanger.rar" - 816,05 MB -Joe.Danger-SKIDROW yEnc
		//(39/40) "flt-ts31554.vol061+57.PAR2" - 1,43 GB -The_Sims_3_v1.55.4-FLTDOX yEnc
		if (preg_match('/^\(\d+\/(\d+\))[-_ ]{0,3}".+?' . $this->e0 . ' - \d+([.,]\d+ [kKmMgG])?[bB] -([a-zA-Z0-9-_\.]+) yEnc$/', $this->subject, $match)) {
			return $match[8];
		} //[02/17] - "Castle.Of.Illusion.Starring.Mickey.Mouse.PSN.PS3-DUPLEX.nfo" yEnc
		if (preg_match('/^[\(\[]\d+\/\d+[\)\]][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[PROPER] FIFA.14.PAL.XBOX360-iNSOMNi [008/100]- "ins-fifa14pal.r05" yEnc
		if (preg_match('/^\[PROPER\] ([a-zA-Z0-9-_\.]+) [\(\[]\d+\/\d+[\)\]][-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<<<< Alien Zombie Death v2 EUR PSN PSP-PLAYASiA >>>> < USC> <"Alien Zombie Death v2 EUR PSN PSP-PLAYASiA.part4.rar">[06/16] 153,78 MB yEnc
		if (preg_match('/^<<<< ([a-zA-Z0-9-_ ]+) >>>> < USC> <".+?' . $this->e0 . '>\[\d+\/(\d+\]) \d+([.,]\d+ [kKmMgG])?[bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<usenet-space-cowboys.info>>> fuzzy <<<Powered by https://secretusenet.com><Adventures To Go EUR PSP-ZER0>< "Adventures To Go EUR PSP-ZER0.nfo" >< 2/6 (195,70 MB) >< 10,70 kB > yEnc
		if (preg_match('/^<<<.+\.info>>> fuzzy <<<Powered by .+secretusenet\.com><([a-zA-Z0-9-_ ]+)>< ".+?' . $this->e0 . ' >< \d+\/(\d+) \(\d+([.,]\d+ [kKmMgG])?[bB]\) >< \d+([.,]\d+ [kKmMgG])?[bB] > yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //FTDWORLD.NET| Grand.Theft.Auto.V.XBOX360-QUACK [020/195]- "gtavdisc1.r17" yEnc
		if (preg_match('/^FTDWORLD\.NET\| ([a-zA-Z0-9 -_\.]+) \[\d+\/(\d+\])- ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(FIFA 14 Demo XBOX) [001/163] - "FIFA 14 Demo.part.par2" yEnc
		if (preg_match('/^\(([a-zA-Z0-9 -_\.]+)\) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[16/62]  (CastleStorm.XBLA.XBOX360-MoNGoLS) - "mgl-cast.part15.rar" yEnc
		if (preg_match('/^\[\d+\/(\d+\])  \(([a-zA-Z0-9 -_\.]+)\) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //GOGDump Wing Commander - Privateer (1993) [GOG] [03/14] - "Wing Commander - Privateer (1993) [GOG].part2.rar" yEnc
		if (preg_match('/^GOGDump (.+) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Uploader.Presents-Metal.Gear.Rising.Revengeance-RELOADED(51/65]"rld-megerire.r48" yEnc
		if (preg_match('/^Uploader\.Presents-(.+)[\(\[]\d+\/\d+\]".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Uploader.Presents-LEGO.The.Hobbit-RELOADED (????) [01/90] - "rld-legoho.nfo" yEnc
		if (preg_match('/^Uploader\.Presents-(.+?) \(\?+\) \[\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //( Overlord II RELOADED ) - [013/112] - "rld-olii.part001.rar" yEnc
		if (preg_match('/^\( ([\w. -]{8,}) \)[-_ ]{0,3}\[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Baku.No.JAP.Working.PSP-PaL - [1/7] - "Baku.No.JAP.Working.PSP-PaL.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9 -\._]+) - \[\d+\/(\d+\])[-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function games_dox()
	{
		//[142961]-[MP3]-[a.b.inner-sanctumEFNET]-[ Pascal_and_Pearce-Passport-CDJUST477-2CD-2011-1REAL ] [28/36] - "Pascal_and_Pearce-Passport-CDJUST477-2CD-2011-1REAL.par2" yEnc
		if (preg_match('/(\[[\d#]+\]-\[.+?\]-\[.+?\]-)\[ (.+?) \][- ]\[\d+\/\d+\] - "(.+?)" yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//[NEW DOX] The.King.of.Fighters.XIII.Update.v1.1c-RELOADED [1/6] - "The.King.of.Fighters.XIII.Update.v1.1c-RELOADED.par2" yEnc
		//[NEW DOX] Crysis.3.Crackfix.3.INTERNAL-RELOADED [00/12] ".nzb"  yEnc
		if (preg_match('/^\[NEW DOX\][ _-]{0,3}(.+?)[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[NEW DOX] Minecraft.1.6.2.Installer.Updated.Server.List  - "Minecraft 1 6 2 Cracked Installer Updater Serverlist.nfo" - yEnc
		if (preg_match('/^\[NEW DOX\][ _-]{0,3}(.+?)[ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[ Assassins.Creed.3.UPDATE 1.01.CRACK.READNFO-P2P  00/17 ] "Assassins.Creed.3.UPDATE 1.01.nzb" yEnc
		if (preg_match('/^\[ ([a-zA-Z0-9-\._ ]+)  \d+\/(\d+ \]) ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//[01/16] - GRID.2.Update.v1.0.83.1050.Incl.DLC-RELOADED - "reloaded.nfo" - yEnc
		//[12/17] - Call.of.Juarez.Gunslinger.Update.v1.03-FTS - "fts-cojgsu103.vol00+01.PAR2" - PC - yEnc
		if (preg_match('/^\[\d+\/(\d+\]) - ([a-zA-Z0-9-\.\&_ ]+) - ".+?' . $this->e0 . '( - PC)? - yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //[36/48] NASCAR.The.Game.2013.Update.2-SKIDROW - "sr-nascarthegame2013u2.r33" yEnc
		if (preg_match('/^\[\d+\/(\d+\]) ([a-zA-Z0-9-\._ ]+) ".+?' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //[Grand_Theft_Auto_Vice_City_1.1_Blood_NoCD_Patch-gimpsRus]- "grugtavc11bcd.nfo" yEnc
		if (preg_match('/^\[([a-zA-Z0-9-\._ ]+)\]- ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[OLD DOX] (0001/2018) - "18.Wheels.of.Steel.American.Long.Haul.CHEAT.CODES-RETARDS.7z" - 1,44 GB - yEnc
		if (preg_match('/^\[OLD DOX\][ _-]{0,3}\(\d+\/\d+\)[ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[-_ ]{0,3}\d+[,.]\d+ [mMkKgG][bB][-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Endless.Space.Disharmony.v1.1.1.Update-SKIDROW - [1/6] - "Endless.Space.Disharmony.v1.1.1.Update-SKIDROW.nfo" - yEnc
		if (preg_match('/^([a-zA-Z0-9-\._ ]+) - \[\d+\/(\d+\]) - ".+?' . $this->e0 . '{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(F.E.A.R.3.Update.1-SKIDROW) [01/12] - "F.E.A.R.3.Update.1-SKIDROW.par2" yEnc
		if (preg_match('/^\(([a-zA-Z0-9-\._ ]+)\) \[\d+\/(\d+\]) - ".+?' . $this->e0 . '{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Company.of.Heroes.2.Update.v3.0.0.9704.Incl.DLC.GERMAN-0x0007) - "0x0007.nfo" yEnc
		if (preg_match('/^\(([a-zA-Z0-9-\._ ]+)\) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function games_wii()
	{
		//"National.Geographic.Challenge.USA.WII-dumpTruck__www.realmom.info__.jpg" (003/111) 4,81 GB yEnc
		if (preg_match('/^"(.+)__www.realmom.info__.+" \(\d+\/\d+\) \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[Cabelas_North_American_Adventure_USA_WII-ZRY-Scrubbed-xeroxmalf]-[#a.b.g.w@efnet]-[www.abgx.net]-[01/27] - "Cabelas_North_American_Adventure_USA_WII-ZRY-Scrubbed-xeroxmalf.par2" yEnc
		if (preg_match('/^\[(.+)\]-\[#a.b.g.w@efnet\]-\[www.abgx.net\]-\[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//4300 World.Sports.Competition.USA.VC.Wii-OneUp....... AmigaOS4.1 RULEZ [0/9] - "4300.nzb" yEnc
		//4300 1695.World.Sports.Competition.USA.VC.Wii-OneUp....... AmigaOS4.1 RULEZ [0/9] - "4300.nzb" yEnc
		if (preg_match('/^\d+ (\d+\.)?(.+-OneUp).+ \[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //6717 - Baseball.Stars.2.PAL.VC.NG.Wii-OneUp - 01/11 - "1u-baseball-stars-2-pal.nfo" yEnc
		if (preg_match('/^\d+ - (.+-OneUp).+ \d+\/\d+ - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[2103]-[abgx.net] Harvey_Birdman_Attorney_At_Law-USA-WII [000/104] - "Harvey_Birdman_Attorney_At_Law-USA-WII.nzb" yEnc
		if (preg_match('/^\[\d+\]-\[abgx.net\] (.+) \[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(3790)-[abgx.net]-[Samurai_Warriors_Katana_USA-WII]-[000/105] - "3790.nzb" yEnc
		if (preg_match('/^\(\d+\)-\[abgx.net\]-(.+)-\[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[REQ# 7134] Full 105 Cocoto_Magic_Circus_PAL_Wii-OE PAL "oe-magic.r00" 4.57 GB yEnc
		if (preg_match('/^\[REQ# \d+\] Full \d+ (.+) PAL ("|#34;).+("|#34;) \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[11614]-[#a.b.g.wii@efnet]-[ EA.Sports.Active.NFL.Training.Camp.USA.WII-ProCiSiON ]-[01/95] - "xxx-nflt.nfo" yEnc
		if (preg_match('/\[[\d#]+\]-\[.+?\]-\[ (.+?) \][- ]\[\d+\/\d+\][ -]{0,3}".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[8524]-[#a.b.g.wii@EFNet]-[FULL]-[Fantastic_Four_Rise_Of_The_Silver_Surfer_NTSC_Wii-VORTEX]-[001/104] - "vortex-ffrotss.wii.nfo" yEnc
		if (preg_match('/\[[\d#]+\]-\[.+?\]-\[.+?\]-\[(.+?)\][- ]\[\d+\/\d+\][ -]{0,3}".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[13118]-[abgx]-[FULL]-[Doods_Big_Adventure_PAL_WII-PLAYME]-po0p?!-[000/103] - "13118.nzb" yEnc
		if (preg_match('/^\[\d+]-\[abgx\]-\[FULL\]-\[(.+)-PLAYME\]-po0p.+-\[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[13208]-[#ab@EFNet]-[FULL]-[Calvin_Tuckers_Farm_Animal_Racing_PAL_Wii-WiiERD]-po0p!-[072/112] - "w-ctfar.r68" yEnc
		if (preg_match('/^\[\d+]-\[#ab@EFNet\]-\[FULL\]-\[(.+)-WiiERD\]-po0p.+-\[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(www.Thunder-News.org) >Winter.Stars.USA.WII-dumpTruck< <Sponsored by AstiNews> - (001/112) - "dumptruck-winterstars.par2" yEnc
		if (preg_match('/^\(www\.Thunder-News\.org\) ?>(.+)< ?<Sponsored.+>[ _-]{0,3}(\(\d+\/\d+\)|\[\d+\/\d+\])?[ _-]{0,5}("|#34;).+("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //[9987]-[#a.b.g.wii@efnet]-[ Tales.of.Elastic.Boy.Mission.1.USA.WiiWare.Wii-OneUp ]-[01/12] - #34;1u-tales-of-elastic-boy-mission-1.nfo#34; yEnc
		if (preg_match('/^\[\d+]-\[#a\.b\.g\.wii@efnet\]-\[ ?(.+) ?\]-\[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[2207] Swing_Golf_Pangya_JPN_Wii-Caravan NTSC-J DVD5 [001/102] - "cvn-sgp.nfo" yEnc
		if (preg_match('/^\[\d+\] (.+) NTSC-J DVD5 \[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[3867] FaceBreaker.K.O.Party.PAL.Wii-RANT <>000/110<> "3867.nzb" yEnc
		if (preg_match('/^\[\d+\] (.+) <>\d+\/\d+<> ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[COMPRESSED] Family_Feud_2010_Edition_USA_Wii-QwiiF [01/26] - "Family_Feud_2010_Edition_USA_Wii-QwiiF.par2" yEnc
		if (preg_match('/^\[COMPRESSED\] (.+) \[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[COMPRESSED] Rooms.The.Main.Building.USA.WII-SUSHi - su-rousa.par2 [01/18] (1/1) (1/1) yEnc
		if (preg_match('/^\[COMPRESSED\] (.+) - .+ \[\d+\/\d+\] .+ yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //WII4U - thinkSMART.Family.USA.WII-dumpTruck - [01/15] - "dumptruck-tf.par2" yEnc
		if (preg_match('/^WII4U - (.+) - \[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<Old but Sold>>> <<< >< >< "Rogue Trooper The Quartz Zone Massacre (2009)PAL Wii" >< 037/131 () >< > yEnc
		if (preg_match('/^<<<Old but Sold>>> <<< >< >< ("|#34;)(.+)("|#34;) >< \d+\/\d+ \(\) >< > yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //[6840]-[abgx@Efnet]-[My.Fitness.Coach.NTSC-WII-ProCiSiON] - (001/110) "xxx-mfc.par2" - 4.76 GB yEnc
		if (preg_match('/^\[\d+\]-\[abgx@Efnet\]-\[(.+)\] - \(\d+\/\d+\) ".+".+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[6820]-[abgx@Efnet]-[Full]-[Cotton_Fantastic_Night_Dreams_NTSC_RF_TG16-CD.iNJECT_iNTERNAL_VC_Wii-0RANGECHiCKEN]- [01/16] - 0c-cotton-rf.nfo (1/1) (1/1) yEnc
		if (preg_match('/^\[\d+\]-\[abgx@Efnet\]-\[Full\]-\[(.+)\][ -]+\[\d+\/\d+\] .+ yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[3488] [#alt.binaries.games.wii@efnet] [Blades.Of.Steel.USA.VC.Wii-DiPLODOCUS] [0/8] 3488.nzb (1/1) (1/1) yEnc
		if (preg_match('/^\[\d+\] \[#alt\.binaries\.games\.wii@efnet\] \[(.+)\] \[\d+\/\d+\].+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<Little.League.World.Series.Double.Play.USA.WII-dumpTruck> [001/110] - "Little.League.World.Series.Double.Play.USA.WII-dumpTruck.par2" yEnc
		if (preg_match('/^<(.+)> \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function games_xbox360()
	{
		//Uploader.Presents-Injustice.Gods.Among.Us.Ultimate.Edition.XBOX360-COMPLEX(02/92]"complex-injustice.ultimate.nfo" yEnc
		//Uploader.Presents-Need.For.Speed.Rivals.XBOX360-PROTOCOL[10/94]"nfs.r-ptc.r07" yEnc
		if (preg_match('/^Uploader.Presents-(.+?)[\(\[]\d+\/\d+\]".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//place2home.net - Call.of.Duty.Ghosts.XBOX360-iMARS - [095/101] - "imars-codghosts-360b.vol049+33.par2" yEnc
		//Place2home.net - Diablo_III_USA_RF_XBOX360-PROTOCOL - "d3-ptc.r34" yEnc
		if (preg_match('/^place2home\.net - (.*?) - (\[\d+\/\d+\] - )?".+?" yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //"Arcana_Heart_3_PAL_XBOX360-ZER0__www.realmom.info__.nfo" (02/89) 7,58 GB yEnc
		if (preg_match('/^"(.+)(__www\.realmom\.info__)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") [\[\(]\d+\/(\d+[\]\)]) \d+[,.]\d+ [mMkKgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/15) "Mass.Effect.3.Collectors.Edition.DLC.JTAG-XPG.par2" - 747.42 MB - yEnc
		if (preg_match('/^[\[\(]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(????) [00/28] - "Farming.Simulator.XBOX360.JTAG.RGH.nzb" yEnc
		if (preg_match('/^\(.+\)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(14227) BloodRayne_Betrayal_XBLA_XBOX360-XBLAplus [01/25] - "xp-blobe.nfo" yEnc
		if (preg_match('/^\(\d+\)[-_ ]{0,3}(.+)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(14811) [#alt.binaries.games.xbox360@EFNet]-[AMY_XBLA_XBOX360-XBLAplus]-[]-  "xp-amyxb.nfo"  yEnc
		if (preg_match('/^(\(\d+\))[-_ ]{0,3}\[.+EFNet\][-_ ]{0,3}\[(.+)\][-_ ]{0,3}\[\][-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(14872) [#alt.binaries.games.xbox360@EFNet]-[BlazBlue_CS_System_Version_Data_Pack_1.03-DLC_XBOX360]-  "xp-bbcssvdp103.nfo"  yEnc
		if (preg_match('/^(\(\d+\))[-_ ]{0,3}\[.+EFNet\][-_ ]{0,3}\[(.+)\][-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(44/82) - Fuzion_Frenzy_2_REPACK-USA-XBOX360-DAGGER - "ff2r-dgr.041" - 6.84 GB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[-_ ]{0,3}(.+?)[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//[  14047  ] - [ ABGX@EFNET ] - [  Rock.Band.Pearl.Jam.Ten.DLC.XBOX360-FYK ALL DLC    ] -  (01/46) "rbpjtdlc-fyk.nfo" - 526,92 MB - yEnc
		//[  14046  ] - [ ABGX@EFNET ] - [  Rock_Band-2011-07-19-DLC_XBOX360-XBLAplus ALL   ] -  (01/12) "xp-rb-2011-07-19.nfo" - 198,70 MB - yEnc
		//[ 14102 ] -[ ABGX.NET ] - [ F1.2010.XBOX360-COMPLEX NTSC DVD9  ] -  (01/79) "cpx-f12010.nfo" - 6,57 GB - yEnc
		if (preg_match('/^\[[-_ ]{0,3}(\d+)[-_ ]{0,3}\][-_ ]{0,3}\[ ABGX.+\][-_ ]{0,3}\[[-_ ]{0,3}(.+)[-_ ]{0,4}\][-_ ]{0,4}\(\d+\/\d+\)[-_ ]{0,3}"(.+?)(\.part\d*|\.rar)?(\.vol.+\(\d+\\d+\)"|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}\d+[,.]\d+ [mMkKgG][bB][-_ ]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[2];
		}
		//[ 17956]-[FULL]-[ abgx360EFNet ]-[ F1_2012_JPN_XBOX360-Caravan ]-[78/99] - "cvn-f12012j.r75" yEnc
		//[ 17827]-[FULL]-[ #abgx360@EFNet ]-[ Capcom_Arcade_Cabinet_XBLA_XBOX360-XBLAplus ]-[01/34] - "xp-capac.nfo" yEnc
		if (preg_match('/^\[[-_ ]{0,3}(\d+)[-_ ]{0,3}\][-_ ]{0,3}\[FULL\][-_ ]{0,3}\[ (abgx360EFNet|#abgx360@EFNet) \][-_ ]{0,3}\[[-_ ]{0,3}(.+)[-_ ]{0,3}\][-_ ]{0,3}\[\d+\/\d+\][-_ ]{0,3}"(.+?)(\.part\d*|\.rar)?(\.vol.+\(\d+\\d+\)"|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[3];
		} //[ GAMERZZ ] - [ Grand.Theft.Auto.V.XBOX360-COMPLEX ] [159/170] - "complex-gta5.vol000+18.par2" yEnc
		if (preg_match('/^\[[-_ ]{0,3}GAMERZZ[-_ ]{0,3}\][-_ ]{0,3}\[[-_ ]{0,3}(.+)[-_ ]{0,3}\][-_ ]{0,3}\[\d+\/(\d+\])[-_ ]{0,3}"(.+?)(\.part\d*|\.rar)?(\.vol.+\(\d+\\d+\)"|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //"Mass.Effect.3.From.Ashes.DLC.XBOX360-MoNGoLS.nfo" yEnc
		if (preg_match('/.*"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function german_movies()
	{
		//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
		if (preg_match('/^>ghost-of-usenet\.org>(.+?)<.+?> ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
		if (preg_match('/^<ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function ghosts()
	{
		//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
		if (preg_match('/^<ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(116/175) "Embrace.of.the.Vampire.1995.1080p.BluRay.x264.YIFY.part115.rar" - 1,60 GB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) ("|#34;)(.+)(\.[vol|part].+)?\.(par2|nfo|rar|nzb)("|#34;) - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/i', $this->subject, $match)) {
			return $match[3];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function hdtv()
	{
		//[ TrollHD ] - [ 0270/2688 ] - "Tour De France 2013 1080i HDTV MPA 2.0 MPEG2-TrollHD.part0269.rar" yEnc
		//[17/48] - "Oprah's Next Chapter S02E37 Lindsay Lohan 1080i HDTV DD5.1 MPEG2-TrollHD.part16.rar" yEnc
		//[02/29] - "Fox Sports 1 on 1 - Tom Brady 720p HDTV DD5.1 MPEG2-DON.part01.rar" yEnc
		if (preg_match('/^(\[ TrollHD \] - )?[\[\(][-_ ]{0,3}\d+\/(\d+[-_ ]{0,3}[\)\]]) - "(.+?) MPEG2-(DON|TrollHD)\..+?" yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //.oO "20.Years.After.German.2008.AC3.BDRip.XviD.INTERNAL-ARC__www.realmom.info__.nfo" Oo. [02/39] 1,43 GB yEnc
		if (preg_match('/^\.oO "(.+)__www.realmom.info__.+" Oo. \[\d+\/\d+\] \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ 09821 ] - [ TrollHD ] - [ 00/54 ] - "Chopped CQ0808H My Way 1080i HDTV DD5.1 MPEG2-TrollHD.nzb" yEnc
		if (preg_match('/^\[ \d+ \] - \[ TrollHD \] - \[ \d+\/\d+ ] - "(.+)\..+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ TrollHD ] - [ 01/19 ] - "America's Test Kitchen From Cook's Illustrated - Ultimate Grilled Turkey Burgers 480i PDTV DD2.0 MPEG2-TrollSD.par2" yEnc
		if (preg_match('/^\[ TrollHD \] - \[ \d+\/\d+ \] - "(.+)\.par.+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //NBC Nightly News - Flash Video - 11-20-2013 [13/15] - "NBC Nightly News 11-20-2013.flv.vol03+04.par2" yEnc
		if (preg_match('/^NBC.+\[\d+\/\d+\] - "(.+)\.vol.+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Newseros.com) Cueva 1080p [02/42] - "idonhom-mirc.part01.rar" yEnc
		if (preg_match('/\(Newseros\.com\) .+? \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //It's.Always.Sunny.in.Philadelphia.S07E09.The.Gang.Gets.Trapped.BluRay.1080p.Remux.AVC.DTSHD-MA.5.1-PtZ-BeyondHD.me [047/123] - "It's.Always.Sunny.in.Philadelphia.S07E09.The.Gang.Gets.Trapped.BluRay.1080p.Remux.AVC.DTSHD-MA.5.1-PtZ-BeyondHD.me.r44" yEnc
		if (preg_match('/^.+S\d+E\d+.+\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(Alpha.House.S01E08.WEBRip.1080p.x264.DD5.1-Absinth) [14/24] - "Alpha.House.S01E08.WEBRip.1080p.x264.DD5.1-Absinth.part13.rar" yEnc
		if (preg_match('/^\(.+\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(024/264) "Enf.S04E02.G.D.H.X-M.vol51+46.PAR2" - 8,27 GB -Enf.S04.G.D.H.X yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] .+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[01/10] - "Wondershare.Video.Converter.Ultimate.v6.7.1.0.Multilanguage.par2" - 45,44 MB yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //(01/60) "Micro Monsters With David Attenborough 2013 3D HSBS DTS5.1 EP5 - Family-3D4U.par2" yEnc
		if (preg_match('/^[\(\[]\d+\/\d+[\)\]] "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[ TrollHD ] - [ 01/32 ] - "Demi Lovato - 2vLive Concert 2013-10-28 PROPER [see NFO] 720p WEB-CAP AAC2.0 H.264-TrollHD.nfo" yEnc
		if (preg_match('/^\[ TrollHD \] - [\(\[] \d+\/\d+ [\)\]] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[00245] [ PPKORE ] [44/57] - "The L Word S04E08 1080p WEB-DL DD5 1 H 264-PPKORE.vol014+14.par2" yEnc
		if (preg_match('/\[\d+\][- ]{0,3}\[ PPKORE \][- ]{0,3}\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Adobe Photoshop Lightroom v5.2 - FiNaL - Multilingual [WIN].vol037+32.PAR2  yEnc
		if (preg_match('/^(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) {0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Bad.Penny.2013.720p.HDTV.x264-GBL "bad.penny.2013.720p.hdtv.x264-gbl.par2" yEnc
		if (preg_match('/^[\w-\.]+ "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //The.Colbert.Report.2013.07.29.The.Lumineers.WEBRip.AAC2.0.H.264-DCK [01/11] - "The.Colbert.Report.2013.07.29.The.Lumineers.WEBRip.AAC2.0.H.264-DCK.mp4"  yEnc
		if (preg_match('/^[\w-\. ]+\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"8 Wenn ich einmal gross bin .mp3" Koelschefetz postet.Die Filue -Immer Wigger  yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Elysium  (AVCHD)  2013  (720p)  Untouched  (Postet by Edition50)    [01/169] - "file.xyz" yEnc
		//Frozen Ground  (AVCHD)  2013  (720p)  postet by Edition50 - [01/158] - "file.xyz" yEnc - [000/158] - "FROZEN GROUND  (AVCHD)  2013  (720p)  Untouched.nzb" yEnc
		//Cabin in the Woods  (AVCHD)  2013  (720p)  Postet by (Edition50) [01/159] - "file.xyz" yEnc
		if (preg_match('/^(.+?)[- ]{0,3}\(?[Pp]ostet +by +\(?Edition50\)?[- ]{0,4}\[\d+\/\d+\][- ]{0,3}".+?' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Dark Zero Thirty (2013)  AVCHD (720p)  Untouched [01/151] - "ZERO DARK THIRTY  (2013)  AVCHD  720p)  Untouched.par2" yEnc
		if (preg_match('/^(.+?)[- ]{0,3}[\(\[]\d+\/\d+[\)\]][- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function hdtv_x264()
	{
		//(23/36) "Love.Is.In.The.Meadow.S08E08.HDTV.720p.x264.ac3.part22.rar" - 2,80 GB - yEnc
		//AMEa(01/49) - AME- "Arbitrage 2013 DTS HD MSTR 5.1 MKV h264 1080p German by AME.nfo" - 7,87 GB - yEnc
		if (preg_match('/^(AMEa)?\(\d+\/\d+\)( - AME-)? "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[3];
		}
		//Hard.Target.1993.1080p.Bluray.X264-BARC0DE - [36/68] - "BARC0DE1080pHTAR.r22" yEnc
		//Goddess.2013.720p.BDRip.x264.AC3-noOne  [086/100] - "Goddess.2013.720p.BDRip.x264.AC3-noOne.part84.rar" yEnc
		if (preg_match('/^([A-Z0-9a-z][A-Za-z0-9.-]+) -? \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //I Love Democracy - Norwegen - Doku -  2012 - (German)  - AC3 HD720p  Avi by Waldorf -  [02/71] - "Waldorf.jpg" yEnc
		if (preg_match('/(.+?)\s+(Avi )?by Waldorf\s+-\s+\[\d+\/\d+\]\s+-\s+".+?"\s+yEnc$/', $this->subject, $match)) {
			return preg_replace('/\s\s+/', ' ', $match[1]);
		}
		//Season of the Witch 2011 - "Season.of.the.Witch.2011.1080p.BluRay.DTS.x264-CyTSuNee.part005.rar" yEnc
		//Film - "Alien Antology DC Special Edition 1979-1997 1080p GER HUN HighCode.part001.rar" yEnc
		//Austex Memorandum   "Austex Memorandum 700877270640835.z17" yEnc
		if (preg_match('/^[A-Z][a-zA-Z0-9 ]+ [- ] "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"Ninja-Revenge Will Rise UC-Pittis AVCHD-ADD.English.dtsHR.nfo.txt" (01/55) yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' \(\d+\/\d+\) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ The.Looney.Tunes.Show.S02E20.480p.WEB-DL.AAC2.0.H.264-YFN ] - [01/19] - "The.Looney.Tunes.Show.S02E20.The.Shell.Game.480p.WEB-DL.AAC2.0.H.264-YFN.nfo" yEnc
		if (preg_match('/^\[ ([A-Za-z0-9.-]{7,}) \] - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //.oO "20.Years.After.German.2008.AC3.BDRip.XviD.INTERNAL-ARC__www.realmom.info__.nfo" Oo. [02/39] 1,43 GB yEnc
		if (preg_match('/^\.oO "(.+)__www.realmom.info__.+" Oo. \[\d+\/\d+\] \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[8370]-[#alt.binaries.hdtv.x264@EFNet]-[Mr.Brooks.2007.720p.BluRay.DTS.x264-ESiR]-[00/86] "Mr.Brooks.2007.720p.BluRay.DTS.x264-ESiR.nzb" yEnc
		if (preg_match('/^\[\d+\][ -]\[#.+\][ -]\[(.+)\][ -]\[\d+\/\d+\][ -]{0,3}("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(The.Ghost.Writer.2010.hd.for.ipad.NLSUB) [01/21] - "The.Ghost.Writer.2010.hd.for.ipad.NLSUB.part01.rar" yEnc
		if (preg_match('/^\((.+)\) \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ The Amazing Race S23 720p WEB-DL AAC2.0 H.264 ] - [01/40] - "The.Amazing.Race.S23E08.720p.WEB-DL.AAC2.0.H.264-KiNGS.nfo" yEnc
		if (preg_match('/^\[ ([\w\s\.-]+) \] - \[\d+\/\d+\][ -]*".+"\s*yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Uploader.Presents-Phantom.2013.German.AC3D.BluRay.1080p.x264-IND[01/62]"phantom.1080p.par2" yEnc
		if (preg_match('/^Uploader\.Presents-(.+)\[\d+\/\d+\]".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[01/18] - "ROH.2013.11.16.#113.WEB-DL.h264-COiL.sfv" yEnc
		if (preg_match('/^\[\d+\/\d+\] - "(.+WEB-DL.+)\.sfv" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //-{NR.C}- - [00/96] - "Being.Liverpool.S01.720p.HDTV.x264-DHD.nzb" yEnc
		if (preg_match('/^-{NR\.C}- - \[\d+\/(\d+\]) - ("|#34;)(.+)(\.[vol|part].+)?\.(par2|nfo|rar|nzb)("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //- [34/69] - "Zero.Charisma.2013.WEB-DL.DD5.1.H.264-HaB.part33.rar" yEnc
		if (preg_match('/^- \[\d+\/(\d+\]) - "(.+?)(\.part\d*|\.rar|\.pdf)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //-=www.hotrodpage.info=- Makaveli -=HoTCreWTeam=- Post: - [000/192] - "Hop (2011) 1080p AVCHD.nzb" yEnc
		if (preg_match('/.+www\.hotrodpage\.info.+\[\d+\/(\d+\]) - "(.+?)(\.part\d*|\.rar|\.pdf)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //-4y (PW)   [ usenet-4all.info - powered by ssl.news -] [12,40 GB] [49/57] "43842168c542ed3.vol000+01.par2" yEnc
		if (preg_match('/^.+?\[(\d+[.,]\d+ [kKmMgG][bB])\] \[\d+\/(\d+\][-_ ]{0,3}.+?)[-_ ]{0,3}"(.+?)(\.part\d*|\.rar|\.pdf)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //!MR [01/49] - "Persuasion 2007.par2" EN MKV yEnc
		if (preg_match('/.*[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[3];
		}
		//Wonders.of.the.Universe.S02E03.1080p.HDTV.x264.AC3.mp4 [1 of 54] "Wonders.of.the.Universe.S02E03.The.Known.and.the.Unknown.1080p.HDTV.x264.AC3-tNe.mp4.001" yEnc
		//Wilfred Season 2 - US - 720p WEB-DL [28 of 43] "Wilfred.US.S02E01.Progress.720p.WEB-DL.DD5.1.H264-NTb.mkv.001" yEnc
		if (preg_match('/^.+ ?\[\d+( of |\/)\d+\] ("|#34;)(.+?)(\.part\d*|\.rar)?(\.[A-Za-z0-9]{2,4})?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //The.Walking.Dead.S02E13.720p.WEB-DL.AAC2.0.H.264-CtrlHD -Kopimi- - 01/37 - "The.Walking.Dead.S02E13.Beside.the.Dying.Fire.720p.WEB-DL.AAC2.0.H.264-CtrlHD.nfo" yEnc
		if (preg_match('/^.+ ?\d+( of |\/)\d+ - ("|#34;)(.+?)(\.part\d*|\.rar)?(\.[A-Za-z0-9]{2,4})?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //The.Guild.S05E12.Grande.Finale.1080p.WEB-DL.x264.AC3.PSIV - "The.Guild.S05E12.Grande.Finale.1080p.WEB-DL.x264.AC3.PSIV.nfo" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function highspeed()
	{
		//Old Dad uppt 18 und immer (noch) Jungfrau DvD Rip AC3 XviD German 02/34] - "18 und immer (noch) Jungfrau.part01.rar" yEnc
		//Old Dad uppt In ihrem Haus DVD Ripp AC3 German Xvid [01/31] - "In ihrem Haus.par2" yEnc
		//Old Dad uppt Eine offene Rechnung XviD German DVd Rip[02/41] - "Eine offene Rechnung.part01.rar" yEnc
		//Old Dad uppMiss Marple: Der Wachsblumenstrauß , Wunschpost Xvid German10/29] - "Miss Marple Der Wachsblumenstrauß.part09.rar" yEnc
		if (preg_match('/^Old\s+Dad\s+uppt? ?(.+?)( mp4| )?\[?\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[03/61] - "www.realmom.info - xvid - xf-fatalmovecd1.r00" - 773,34 MB - yEnc
		//[40/54] - "Mankind.Die.Geschichte.der.Menschheit.S01E12.Das.Ende.der.Reise.GERMAN.DUBBED.DL.DOKU.1080p.BluRay.x264-TVP.part39.rar" - 4,79 GB yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB]( -)? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[02/10] - "Fast.And.Furious.6 (2013).German.720p.CAM.MD-MW upp.by soV1-soko.rar" yEnc
		if (preg_match('/^\[\d+\/\d+\] - "(.+?) upp.by.+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>ghost-of-usenet.org>The A-Team S01-S05(Folgen einzelnd ladbar)<Sponsored by Astinews< (1930/3217) "isd-ateamxvid-s04e21.r19" yEnc
		if (preg_match('/^>ghost-of-usenet\.org>(.+?)\(.+?\).+? \(\d+\/\d+\) ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //www.usenet-town.com [Sponsored by Astinews] (103/103) "Intimate.Enemies.German.2007.AC3.[passwort protect].vol60+21.PAR2" yEnc
		if (preg_match('/^www\..+? \[Sponsored.+?\] \(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Das.Schwergewicht.German.DL.720p.BluRay.x264-ETM - "etm-schwergewicht-720p.part20.rar" yEnc
		if (preg_match('/^([A-Za-z0-9][a-zA-Z0-9.-]{6,})\s+- ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ TiMnZb ] [ www.binnfo.in ] [REPOST] [01/46] - "Columbo - S07 E05 - Des sourires et des armes.nfo" yEnc
		if (preg_match('/^\[ .+? \] \[ www\..+? \]( \[.+?\])? \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //< "Burn.Notice.S04E17.Out.of.the.Fire.GERMAN.DUBBED.DL.720p.WebHD.x264-TVP.par2" >< 01/17 (1.54 GB) >< 11.62 kB > yEnc
		if (preg_match('/^< "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' >< \d+\/\d+ \(.+?\) >< .+? > yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Batman postet 30 Nights of Paranormal Activity with the Devil Inside AC3 XviD German [01/24] - "30 Nights of Paranormal Activity with the Devil Inside.par2" yEnc
		if (preg_match('/^[A-Za-z0-9]+ postet (.+?) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[04/20 Geroellheimer - S03E19 - Freudige ?berraschung Geroellheimer - S03E19 - Freudige ?berraschung.mp4.004" yEnc
		if (preg_match('/^\[\d+\/\d+ (.+?)(\.(part\d*|rar|avi|iso|mp4|mkv|mpg))?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return implode(' ', array_intersect_key(explode(' ', $match[1]), array_unique(array_map('strtolower', explode(' ', $match[1])))));
		} //"Homeland.S01.Complete.German.WS.DVDRiP.XViD-ETM.part001.rar" yEnc
		if (preg_match('/^"(.+?)(\.(part\d*|rar|avi|mp4|mkv|mpg))?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			if (strlen($match[1]) > 7 && !preg_match('/\.vol.+/', $match[1])) {
				return $match[1];
			} else {
				return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
			}
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function inner_sanctum()
	{
		//[ nEwZ[NZB].iNFO - [ Zed--The_Invitation-WEB-2010-WUS ] - File [12/13]: "08-zed--the_river.mp3" yEnc
		if (preg_match('/^\[ nEwZ\[NZB\]\.iNFO( \])?[-_ ]{0,3}\[ (\d+[-_])?(.+?) \][-_ ]{0,3}(File )?[\(\[]\d+\/(\d+[\)\]]): "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?(yEnc)?$/', $this->subject, $match)) {
			return $match[3];
		} //nEwZ[NZB].iNFO - VA-Universal_Music_Sampler_07_February-PROMO-CDR-FLAC-2013-WRE - File [6/9]: "01-alesso-years_(hard_rock_sofa_remix).flac"
		if (preg_match('/^nEwZ\[NZB\]\.iNFO[-_ ]{0,3} (.+?) [-_ ]{0,3}(File )?[\(\[]\d+\/(\d+[\)\]]): "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")$/', $this->subject, $match)) {
			return $match[1];
		} //..:[DoAsYouLike]:..   1,11 GB   "KGMmDSSHBWnxV4g7Vbq5.part01.rar"   47,68 MB yEnc
		if (preg_match('/.+[DoAsYouLike\].?[ _-]{0,3}\d+[,.]\d+ [mMkKgG][bB][-_ ]{0,3}"(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[ _-]{0,3}\d+([,.]\d+ [mMkKgG])? [bB][-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/10) "LeeDrOiD HD V3.3.2-Port-R4-A2SD.par2" - 357.92 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)( - Description -)? "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '( - \d+[,.]\d+ [mMkKgG][bB])? - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //"Ashlar-Vellum Graphite v8 2 2 WinAll Incl Keygen-CRD.par2" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(VA-I_Love_Yaiza_Vol.1-WEB-2012-ServerLab) [01/11] - ".sfv" yEnc
		if (preg_match('/^\(([a-zA-Z0-9._-]+)\) \[\d+\/(\d+\]) - ".+?([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(17/41) - "3-8139g0m530.017" yEnc
		if (preg_match('/^[\[\(]\d+( of |\/)(\d+[\]\)])[-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[3];
		} //nEwZNZB.wz.cz - The.Block.AU.Sky.High.S07E12.WS.PDTV.XviD.BF1 - File [01/22]: "The.Block.AU.Sky.High.S07E12.WS.PDTV.XviD.BF1.part01.rar" yEnc
		if (preg_match('/^nEwZNZB\.wz\.cz - (.+?) - File \[\d+\/\d+]: ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //nEwZ[NZB]_wz_cz - u.s.drug.wars.s02e06.hdtv.x264-mtg - File [02/28]: "eII34BvTxvDMSJZ2ulvh.r00" yEnc
		if (preg_match('/^nEwZ\[NZB\]_wz_cz - (.+?) - File \[\d+\/\d+]: ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //nEwZNZB.wz.cz - Club_FG-SAT-05-22 - File 4 of 4: "01-jean_jerome-club_fg-05-22-2013-tdmlive.mp3" yEnc
		if (preg_match('/^nEwZNZB\.wz\.cz - (.+?) - File \d+ of \d+: ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Big.Brother.IL.S05E01.WS.PDTV.XviD-Silver007 [1/1] - "Big.Brother.IL.S05E01.WS.PDTV.XviD-Silver007.avi" yEnc
		if (preg_match('/^[a-zA-Z0-9._-]+ \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match) && !preg_match('/[a-fA-F0-9]{32}/', $this->subject)) {
			return $match[1];
		}
		//[ f680631754c469e49d3447bf0beadb8e ] [1/8] - "00-chris_carreiro-dirty-web-2014.m3u" yEnc
//		if (preg_match('/^\[ [a-f0-9]{32} \] \[\d+\/\d+\] - "\d+[-_](.+?)\.[a-z0-9]{3,4}" yEnc$/i', $this->subject, $match)) {
//			return $match[1];
//		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function mojo()
	{
		//[17/61] - "www.realmom.info - xvid - xf-devilstomb.r14" - 773,11 MB - yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function mom()
	{
		//[usenet4ever.info] und [SecretUsenet.com] - 96e323468c5a8a7b948c06ec84511839-u4e - "96e323468c5a8a7b948c06ec84511839-u4e.par2" yEnc
		if (preg_match('/^\[usenet4ever\.info\] und \[SecretUsenet\.com\] - (.+?)-u4e - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [01/26] - "Be.Cool.German.AC3.HDRip.x264-FuN.par2" yEnc
		if (preg_match('/^\[Art-of-Usenet\] ([a-fA-F0-9]+) \[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //(????) [03/20] - "Weblinger - The.Haunted.House.Mysteries.v1.0-ZEKE.part01.rar" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //ShesGonnaSquirt.12.12.13.Sindy.Lange.My.Knight.In.Squirting.Armor.XXX.1080p.x264 - [1/1] - "ShesGonnaSquirt.12.12.13.Sindy.Lange.My.Knight.In.Squirting.Armor.XXX.1080p.x264.rar" yEnc
		if (preg_match('/[\w.-]{8,}[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[Art-of-Usenet] dea75eb65e65c56197d749d57919806d [01/19] - "dea75eb65e65c56197d749d57919806d.par2" yEnc
		if (preg_match('/^\[Art-of-Usenet\] .+? \[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
		if (preg_match('/^<ghost-of-usenet\.org>(.+?) \[\d+\/\d+\] - ".+?" .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //.oO "20.Years.After.German.2008.AC3.BDRip.XviD.INTERNAL-ARC__www.realmom.info__.nfo" Oo. [02/39] 1,43 GB yEnc
		if (preg_match('/^\.oO "(.+)__www.realmom.info__.+" Oo. \[\d+\/\d+\] \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<kere.ws> - 0DAY - 1331086126 - Robokill.Rescue.Titan.Prime.v1.1.MacOSX.Cracked-CORE - [1/9] - "Robokill.Rescue.Titan.Prime.v1.1.MacOSX.Cracked-CORE.par2" yEnc
		if (preg_match('/^<kere\.ws>[ _-]{0,3}\w+(-\w+)?[ _-]{0,3}\d+[ _-]{0,3}(.+) - \[\d+\/\d+\][ _-]{0,3}("|#34;).+?("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //[A_New_Found_Glory-Its_All_About_The_Girls-Reissue-CDEP-FLAC-2003-JLM] [www.usenet4ever.info by Secretusenet] -  "00-a_new_found_glory-its_all_about_the_girls-reissue-cdep-flac-2003.jpg" yEnc
		if (preg_match('/^\[(.+?)\][ _-]{0,3}\[www\.usenet4ever\.info by Secretusenet][ _-]{0,3} ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//MoM100060 - "Florian_Arndt-Trix-(BBM36)-WEB-2012-UKHx__www.realmom.info__.nfo" [2/7] 29,04 MB yEnc
		//"Alan.Wake.v1.02.16.4261.Update-SKIDROW__www.realmom.info__.nfo" (02/17) 138,07 MB yEnc
		if (preg_match('/^(Mom\d+[ _-]{0,3})?"(.+?)__www\.realmom\.info__' . $this->e0 . '[ _-]{0,3}[\(\[]\d+\/(\d+[\)\]]) \d+[.,]\d+ [kKmMgG][bB] yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //"The.Draughtsmans.Contract.1982.576p.BluRay.DD2.0.x264-EA"(15/56) "The.Draughtsmans.Contract.1982.576p.BluRay.DD2.0.x264-EA.part13.rar" - 2.37 GB yEnc
		if (preg_match('/^"(.+?)"\(\d+\/(\d+\))[ _-]{0,3}".+?' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB] yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //(01/29) - Description - "Revolution.2012.S01E06.HDTV.x264-LOL.nfo" - 317.24 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[ _-]{0,3}Description[ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //(02/17) - [Lords-of-Usenet] <<Partner of SSL-News.info>> i8dewFjzft94BW71EI0s -"19913.r00" - 928,75 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[ _-]{0,3}\[Lords-of-Usenet\][ _-]{0,3}<<Partner of SSL-News\.info>>[ _-]{0,3}(.+?)[ _-]{0,3}".+?' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //[002/161] - "Rayman_Legends_USA_PS3-CLANDESTiNE.nfo" yEnc
		if (preg_match('/^\[\d+\/\d+\][ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//NetVideoGirls.13.08.05.Julia.XXX.MP4-KTR - [1/1] - "NetVideoGirls.13.08.05.Julia.XXX.MP4-KTR.rar" yEnc
		//FemaleAgent.E136.Rollie.XXX.1080p.MOV-KTR - [1/1] - "FemaleAgent.E136.Rollie.XXX.1080p.MOV-KTR.rar" yEnc
		//Penthouse.13.07.13.Natalia.Starr.Pet.Of.Month.July.2013.XXX.3D.WMV-TBP - [1/1] - "Penthouse.13.07.13.Natalia.Starr.Pet.Of.Month.July.2013.XXX.3D.WMV-TBP.rar" yEnc
		if (preg_match('/^([a-z0-9].+?\.XXX.*?\.[a-z0-9]{3,4}-[a-z0-9]+) - \[\d+\/\d+\] - ".+?" yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //happy birthday brothers - brothers-of-usenet.info&net-empfehlen-ssl-news - 2brokegirls-s03e12-Die.franzoesische.Versuchung.48 - [01/12] - "2brokegirls-s03e12-Die.franzoesische.Versuchung.480p.par2" yEnc
		if (preg_match('/brothers-of-usenet\.info&net-empfehlen-ssl-news.+\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Eragon postet   Pfeil   S01E18   german                      [01/19] - "Pfeil S01E18.par2" yEnc
		if (preg_match('/^Eragon postet.+\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"[Fansub-Resistance] Naruto Shippuuden 345 xvid (848x480).vol25+15.PAR2" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"Die.Sopranos.S05E02.German.AVC-TVS.vol063+60.par2" (repack180414) yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //LOL.3E05.720p.WEB.DL.nHD.x264-NhaNc3 [64/71] - "LOL.3E05.720p.WEB.DL.nHD.x264-NhaNc3.sfv" yEnc
		if (preg_match('/^([\w.-]{8,})[- ]{0,3}\[\d+\/(\d+\])[- ]{0,3}".+?' . $this->e1, $this->subject, $match)
			&& !preg_match('/^(\d{4,6})-\d{1}\[/', $this->subject)
		) {
			return $match[1];
		} //Uploader.Presents-LaLa.2014.Englisch.R6.HDCAM.x264.AC3-SmY(06/56]"SmY-I,F.2014.part05.rar" yEnc
		if (preg_match('/^Uploader\.Presents-(.+)[\[\(]\d+\/\d+[\]\)]".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(21/27) - Description Lava.Land.2013.3D.1080p.Bluray.HOU.X264.DL-zman - "Lava.Land.2013.3D.1080.HOU.DL-zman.part20.rar" - 8,85 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[- ]{0,3}Description ([\w.,& ()\[\]\'\`-]{8,}?)[- ]{0,3}".+?' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //- "z7QxDdQ5Qs63v8B8.01" - 374,56 MB - yEnc
		//- Description - "le gars et son arc 2x21.vol06+05.PAR2" - 273,04 MB - yEnc
		if (preg_match('/^- (Description - )?"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[2];
		} //::: Usejunkies.tk ::: - [11/15] - "Saitenfeuer - Kein Zurueck 2013.vol00+1.par2" - 116,42 MB yEnc
		if (preg_match('/^.+Usejunkies\.tk.+\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //:.Usejunkies.tk.:---- - [4/8] - "MKTO - MKTO.vol00+5.par2" yEnc
		if (preg_match('/^.+Usejunkies\.tk.+\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"8ÃÂdTbOMaÃÂ¥RuieuD4gÃÂ¥jÃÂwOÃÂ¥bmS72ADÃÂM1b2ÃÂ¦efz9q4yW4O7exV.vol037+6.par2" yEnc
		if (preg_match('/^"(.{8,}?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(08/12) [ArtofUsenet] >>> www.art-of-use.net <<< "Alles Atze - S01E05 - Die Anatolische Fliege.vol01+02.par2" - 245,19 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) \[ArtofUsenet\].+www\.art-of-use\.net.+"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(1/3) "Die drei Fragezeichen - Folge 162 - Der schreiende Nebel.rar" - 121,56 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function moovee()
	{
		//\[ENJOY\]-\[FULL\]-\[.+\]-\[ (.+) \]-\[\d+\/\d+\]-".+" yEnc$/
		if (preg_match('/\[ENJOY\]-\[FULL\]-\[.+\]-\[ (.+) \]-\[\d+\/\d+\]-".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} ///PROUT Movie (2010) NTSC DVD [157/15768] - "PROUTmovie_NTSC.part155.rar" yEnc
		if (preg_match('/^(.+DVD.*) \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[ Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE ]-[01/44] - "Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE.par2" yEnc
		//[134863]-[ Rihanna.No.Regrets.2013.720p.WEB-DL.DD5.1.H.264-PZK ]-[52/52] - "Rihanna.No.Regrets.2013.720p.WEB-DL.DD5.1.H.264-PZK.vol127+76.par2" yEnc
		//[Hard.Target.1993.UNRATED.720p.BluRay.x264-88keyz] - [001/101] - "Hard.Target.1993.UNRATED.720p.BluRay.x264-88keyz.nfo"
		//[ Fast.And.Furious.6.2013.720p.WEB-DL.AAC2.0.H.264-HDCLUB ]-[REAL]-[01/54] - "Fast.And.Furious.6.2013.720p.WEB-DL.AAC2.0.H.264-HDCLUB.nfo" yEnc
		if (preg_match('/^(\[\d+\]-)?\[ ?([a-zA-Z0-9.-]{6,}) ?\](-\[REAL\])? ?- ?\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //"Nights.of.Cabiria.1957.NTSC.DVD.x264-Tree"(23/57) "Nights.of.Cabiria.1957.NTSC.DVD.x264-Tree.part22.rar" - 2.40 GB yEnc
		if (preg_match('/^"(.+(DVD|BluRay|BRRip).+)"\(\d+\/\d+\) ".+".+[GMK]B yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //(????) [0/1] - "A.Good.Day.to.Die.Hard.2013.nzb" yEnc
		if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[xxxxx]-[#a.b.moovee@EFNet]-[ xxxxx ]-[02/66] - "tulob88.part01.rar" yEnc
		if (preg_match('/^\[x+\]-\[.+?\]-\[ x+ \]-\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//Groove.2000.iNTERNAL.DVDRip.XviD-UBiK - [001/111] - "ubik-groove-cd1.par2" yEnc
		//Antony.and.Cleopatra.1972.720p.WEB-DL.H264-brento -[35/57] - "Antony.and.Cleopatra.1972.720p.WEB-DL.AAC2.0.H.264-brento.part34.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9._-]+) - ?\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Iron.Man.3.2013.R5.DVDRip.XviD-AsA) (01/26) - "Iron.Man.3.2013.R5.DVDRip.XviD-AsA.part01.part.sfv" yEnc
		if (preg_match('/^\(([a-zA-Z0-9.-]+)\) \(\d+\/\d+\) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Classic Surf) Morning.Of.The.Earth.1971 [03/29] - "Morning.Of.The.Earth.1971.part02.rar" yEnc
		if (preg_match('/^\([a-zA-Z0-9].+?\) ([a-zA-Z0-9.-]+) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[51/62] Morrissey.25.Live.Concert.2013.BDRip.x264-N0TSC3N3 - "n0tsc3n3-morrissey.25.live.2013.bdrip.x264.rar" yEnc
		if (preg_match('/^\[\d+\/\d+\] (.+) - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[AD120512-00006]-[UnOFFSc3n4iT]-[0131105] Chloe.Tra.Seduzione.E.Inganno.2009.iTALiAN.DVDRip.XviD-TRL [19/41] - "trl-chltsdzn.part18.rar" yEnc
		if (preg_match('/^\[\w+-\w+\]-\[\w+\]-\[\d+\] (.+) \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Breathe.In.2013.BRRip.x264-4UN [01/39] - "Breathe.In.2013.BRRip.x264-4UN.nfo" yEnc
		if (preg_match('/^(.+x264.+) \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //The.CyB3rMaFiA.PimPs.YouR.RiDE.WiTH [REPACK] [40/42] - "d2p5uypp7yn3drpk1080417.vol255+064.par2" yEnc
		if (preg_match('/^(.+) \[REPACK\] \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ Oklahoma\!.1955.720p.WEB-DL.AAC2.0.H.264-CtrlHD ]-[32/55] - "Oklahoma.1955.720p.WEB-DL.AAC2.0.H.264-CtrlHD.part31.rar" yEnc
		if (preg_match('/\[ (.+WEB-DL.+) \]-\[\d+\/\d+\] - "(.+)\.(mp4|mkv|ts|rar|par.+)" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //REQ: working copy of "That Darn Cat 1997 Xvid-Any grp" Plz Ty  (47/60) "geckos-ghr2011-xvid.r44" - 744,19 MB - Gun.Hill.Road.2011.LIMITED.DVDRip.XviD-GECKOS yEnc
		if (preg_match('/^REQ:.+".+".+\(\d+\/\d+\) ".+" - \d+[,.]\d+ [MGK]B - (.+) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(T.U.F The real thing I Frankenstein 2014 NL Subs T.U.F) [01/52] - "I Frankenstein 2014 NL Subs.par2" yEnc
		if (preg_match('/^\(T\.U\.F The real thing (.+?) T\.U\.F\)/', $this->subject, $match)) {
			return $match[1];
		} //(Ringside.The.Best.of.Mike.Tyson.DVDRip.x264.AC3-FCZ)[01/68] - "Ringside.The.Best.of.Mike.Tyson.DVDRip.1.of.2.x264.AC3-FCZ.nfo" yEnc
		if (preg_match('/^\(([\w.-]+)\)\[\d+\/(\d+\])[- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function movies()
	{
		//Underworld.Evolution.2006.480p.BDRip.XviD.AC3-AsA - [000/143] - "asa.nzb" yEnc
		if (preg_match('/^([a-z].+) - \[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} ///^Have Fun - ("|#34;)(.+)\.nfo("|#34;) Ph4let0ast3r yEnc$/i
		if (preg_match('/^Have Fun - ("|#34;)(.+)\.nfo("|#34;) Ph4let0ast3r yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //(01/34) "Sniper.Reloaded.2011.BluRay.810p.DTS.x264-PRoDJi.Turkish.Audio.par2" - 139,30 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) ("|#34;)(.+)\.(par2|nfo|rar|nzb)("|#34;) - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //"Discovery.Channel.Tsunami.Facing.The.Wave.720p.HDTV.x264-PiX.rar"
		if (preg_match('/^("|#34;)(.+)\.rar("|#34;)$/i', $this->subject, $match)) {
			return $match[2];
		} //Saw.VII.2010.720p.Bluray.x264.DTS-HDChina Saw.VII.2010.720p.Bluray.x264.DTS-HDChina.nzb
		if (preg_match('/^([a-z].+) .+\.(par2|nfo|rar|nzb)$/i', $this->subject, $match)) {
			return $match[1];
		} //(????) [1/1] - "The Secret Life of Walter Mitty 2013 CAM AAC x264-SSDD.mp4" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(001/114) - Description - "The.Chronicles.of.Riddick.2004.DC.BluRay.1080p.DTS.par2" - 10,50 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[-_ ]{0,3}Description[-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //[00/56] - "The.Last.Days.On.Mars.720p.BluRay.x264-DR.nzb" yEnc
		if (preg_match('/^\[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[REUP] 6.Degress.of.Hell.2012.1080p.BluRay.DTS-HD.x264-BARC0DE - [03/50] - "BARC0DE.vol00+1.PAR2" yEnc
		//[REUP]Home.Of.The.Brave.2006.1080p.BluRay.DTS-HD.x264-BARC0DE - [03/38] - "BARC0DE.vol00+1.PAR2" yEnc
		if (preg_match('/^\[REUP\]( )?(.+?) - \[\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //- Description - "The.Legend.of.Hercules.2014.720p.BluRay.x264.YIFY.mp4.01" - 795,28 MB -  yEnc
		if (preg_match('/^- Description - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '([- ]{0,3}\d+[.,]\d+ [kKmMgG][bB])[- ]{0,4}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Star.Trek.Into.Darkness.2013.3D.HOU.BDRip.1080p-FAGGOTS [431/432] - "stid3d.vol124+128.par2" yEnc
		if (preg_match('/^([\w.()-]{8,}?)[- ]{0,3}\[\d+\/\d+\][- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(130/138) - Captain America The Winter Soldier 2014 NEW (FIXED) 720p CAM x264 Pimp4003 - "wXZ6LxFt.zip.130" - 2.02 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[- ]{0,3}([\w.() -]{8,}?\b)[- ]{0,3}".+?' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //22.Jump.Street.2014.720p.BluRay.x264-tpU.vol000+01.PAR2 [73/84] - "22.Jump.Street.2014.720p.BluRay.x264-tpU.vol000+01.PAR2" yEnc
		if (preg_match('/^.+\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function movies_divx()
	{
		//(www.Thunder-News.org) >CD2< <Sponsored by Secretusenet> - "exvid-emma-cd2.par2" yEnc
		if (preg_match('/^\(www\.Thunder-News\.org\) .+? - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Movieland Post Voor FTN - [01/43] - "movieland0560.par2" yEnc
		if (preg_match('/^[a-zA-Z ]+Post Voor FTN - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Disney short films collection by mayhem masta"1923 - Alice's Wonderland.vol15+7.par2" yEnc
		if (preg_match('/.+?by mayhem masta"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(http://dream-of-usenet.info) - [01/43] - "Nicht.auflegen.2002.German.DL.AC3.BDRip.XviD-iNCEPTiON.nfo" yEnc
		if (preg_match('/^\(.+usenet\.info\)[ -]{0,3}\[\d+\/\d+\][ -]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[######]-[FULL]-[#hdtv@LinkNet]-[Sesame.Street.S41E03.1080i.HDTV.DD5.1.MPEG2-TrollHD]-[00/51] - "Sesame Street S41E03 Chicken When It Comes to Thunderstorms 1080i HDTV DD5.1 MPEG2-TrollHD.nzb" yEnc
		if (preg_match('/\[#]+\]-\[.+\]-\[.+\]-\[(.+)\][- ]\[\d+\/\d+\][ -]{0,3}".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ Rules.of.Engagement.S06E12.720p.WEB-DL.DD5.1.H.264-CtrlHD ]-[01/24] - "Rules.of.Engagement.S06E12.720p.WEB-DL.DD5.1.H.264-CtrlHD.nfo" yEnc
		if (preg_match('/^\[ ([a-zA-Z0-9.-]{6,}) \]-\[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function movies_x264()
	{
		//http://nzbroyalty.com - House.of.The.Rising.sun.2011.BluRay.720p.DTS.x264-CHD - [00/48] - "House.of.The.Rising.sun.2011.BluRay.720p.DTS.x264-CHD.nzb" yEnc
		if (preg_match('/^http:\/\/nzbroyalty\.com - (.+?) - \[\d+\/(\d+\]) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Scream.4.2011.WS.720p.BluRay.X264-AMIABLE - [000/106] - "Scream.4.2011.WS.720p.BluRay.X264-AMIABLE.nzb" yEnc
		if (preg_match('/^([a-zA-Z0-9._-]+ - ?\[)\d+\/(\d+\]) - "(.+?)\.(nzb|rar|par2)" yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //The Beaver 2011 720p BluRay DD5.1 x264-CtrlHD - [00/65] - "The Beaver 2011 720p BluRay DD5.1 x264-CtrlHD.nzb" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?)( - )\[\d+(\/\d+\] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"The.Hudsucker.Proxy.1994.1080p.Blu-ray.Remux.AVC.DTS.HD.MA.2.0-KRaLiMaRKo"(127/132) "The.Hudsucker.Proxy.1994.1080p.Blu-ray.Remux.AVC.DTS.HD.MA.2.0-KRaLiMaRKo.vol379+20.par2" - 24.61 GB - yEnc
		if (preg_match('/("|#34;)(.+)("|#34;)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}("|#34;).+?(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)[-_ ]{0,3}\d+[.,]\d+ [kKmMgG][bB][-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(Amour.2012.1080p.BluRay.x264-EbP)(002/337) "Amour.2012.1080p.BluRay.x264-EbP.part001.rar" - 16.58 GB - yEnc
		if (preg_match('/^\([\w.-]+\)[\(\[]\d+\/\d+[\]\)] "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(La.pianiste.(aka.The.Piano.Teacher).(2001).720p.BluRay.AC3.x264-MandR) [085/101] - "La.pianiste.(aka.The.Piano.Teacher).(2001).720p.BluRay.AC3.x264-MandR.part084.rar" yEnc
		if (preg_match('/^\([\w .()-]+\) [\(\[]\d+\/\d+[\]\)] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[00/56] - "The.Last.Days.On.Mars.720p.BluRay.x264-DR.nzb" yEnc
		if (preg_match('/^\[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //< Michael.Jackson.Bad.25.2012.720p.BluRay.x264-PHD > - "Michael.Jackson.Bad.25.2012.720p.BluRay.x264-PHD.par2" (01/64) yEnc
		if (preg_match('/^< [\w.() -]+ > - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' \(\d+\/\d+\) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //:Waga.Haha.no.Ki.2012.BluRay.720p.DTS.x264 - Chronicle.Of.My.Mother.2012 - [55/56] - "waha.720p.vol063+64.par2" yEnc
		if (preg_match('/^:([\w.-]+)[- ]{0,3}[\w.-]+[- ]{0,3}\[\d+\/\d+\][- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"Cat Ballou (1965) Multi-Dub AVC 480p.MKV.001" 01 of 11  yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' \d+ of \d+[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Fly.With.The.Gold.2012.720p.BluRay.x264-WiKi [03/73] - "wiki-fwtg.par2" yEnc
		if (preg_match('/^([\w.-]+)[- ]{0,3}\[\d+\/(\d+\])[- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Parker.2013.MULTi.720p.BluRay.x264-GAIA - "Parker.2013.MULTi.720p.BluRay.x264-GAIA.nfo" yEnc
		if (preg_match('/^([\w.-]+)[- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function mp3()
	{
		//"The Absence - Riders Of The Plague" [00/14] - "the_absence-riders_of_the_plague.nzb" yEnc
		if (preg_match('/"([\w. -]{8,})"[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}".+' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//( Albert Cummings Albums 6x By Dready Niek (1999-2012) )  ( ** By Dready Niek ** ) [11/20] - "Albert Cummings Albums 6x By Dready Niek (1999-2012).part10.rar" yEnc
		//( Fat Freddy's Drop - Blackbird (2013) -- By Dready Niek ) -- By Dready Niek ) [01/15] - "Fat Freddy's Drop - Blackbird (2013) -- By Dready Niek.par2" yEnc
		if (preg_match('/\( ([\w. -]{8,}) \)[-_ ]{0,3}( |\().+\)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<Old but Sold>>>  <<< >< >< "German Top 50 ODC - 12.08.2013.nfo" >< 02/33 (541,61 MB) >< 10,93 kB > yEnc
		if (preg_match('/^.+Old but Sold.+>< "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' >< \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\).+ yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Musikjunkie-The.Voice.Of.Germany.2013.The.Best.of.Liveshows.4.CD.Box.Set.VBR [15/28]"voice.part13.rar" yEnc
		if (preg_match('/^Musikjunkie-([\w. -]{8,})[- ]{0,3}\[\d+\/\d+\]".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Metallica - Ride The Lightning    "01 - Fight Fire With Fire.mp3" yEnc
		if (preg_match('/^([\w. -]{8,})[-_ ]{0,3}("|#34;)(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //::: Usejunkies.tk ::: - [08/11] - "DJ Shog - DNA - HD 720p.vol00+1.par2" - 47,76 MB yEnc
		if (preg_match('/^.+Usejunkies\.tk.+[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //"AnyDVD 7.0.0.0.rar" poc  yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[- ]{0,3}poc[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"A Produce & Loren Nerell - Intangible.nzb" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(01/12) "Sunfly Hits April (2013) SF326 [Skytwohigh].par2" - 109.59 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(01/15) - Description - "Goldene Schlager - Erinnerungen Folge 3.par2" - 126,08 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[- ]{0,3}Description[- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(Musica - House&Dance - mix 1 - april 2014 1) [00/45] - "Musica - House&Dance mix 1 april.nzb" yEnc
		if (preg_match('/^\(([\w.,& ()\[\]\'\`-]{8,}?\b.?)\) \[\d+\/\d+\][- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[04/17] - "Schlager.am.laufenden.Band.-.Vol.13.part02.rar" - 622,46 MB yEnc
		if (preg_match('/^\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //"Karate Andi - Pilsator Platin   2014.nfo.nfo Karate Andi - Pilsator Platin   2014 by PsyDealer  yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) [\w.,& ()\[\]\'\`-]{8,}?\b.?( by )?PsyDealer  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [089/100] - "090-florence_and_the_machine_-_spectrum_(say_my_name)_(calvin_harris_edit).mp3 Top 100 Single Charts 13.05.2013"  yEnc
		if (preg_match('/^\(\?+\)[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"[\w.,& ()\[\]\'\`-]{8,}?\b.?\.[A-Za-z0-9]{2,4} (Top \d+ Single Charts \d+\.\d+\.\d+)"[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [02/26] - "8Cursed.rar" yEnc
		if (preg_match('/^\(\?+\)[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(????) [00/34] - "The Official UK Top 40 Singles Chart 15-06-2014.nzb"otfINWnjfg7856fghj  yEnc
		if (preg_match('/^\(\?+\)[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '\w+[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/24) VA - Now Thats What I Call Disco 2013 - "VA - Now Thats What I Call Disco 2013.7z.001" - 487.23 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) ([\w.,& ()\[\]\'\`-]{8,}?)[- ]{0,3}"[\w.,& ()\[\]\'\`-]{8,}?\b.?' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(102400) [06/13] - "Time Life - The Teen Years.part05.rar" yEnc
		if (preg_match('/^\(102400\) \[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(18 Gouden Piraten Hits Deel 2 [03/11] - "18 Gouden Piraten Hits Deel 2.part2.rar" yEnc
		if (preg_match('/^\([\w.,& ()\[\]\'\`-]{8,} \[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(Amigos - Ihre Lieblingshits - Folge 2 - 2013 - by Taekwondo145)[00/62] - "Amigos - Ihre Lieblingshits - Folge 2 - 2013.nzb" yEnc
		if (preg_match('/^\([\w.,& ()\[\]\'\`-]{8,}\)\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(German TOP100 Single Jahrescharts-2013 - by Taekwondo456([67/70] - "German TOP100 Single Jahrescharts-2013.vol041+41.PAR2" yEnc
		if (preg_match('/^\([\w.,& ()\[\]\'\`-]{8,} - by Taekwondo(\d+\()?\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //(Liedjes uit de film De Grote Patriottische Oorlog..o.a. Rod McK )  ( ** By Dready Niek ** ) [02/11] - "Liedjes uit de film De Grote Patriottische Oorlog..o.a. Rod McKuen - By Dready Niek.part1.rar" yEnc
		if (preg_match('/^\( ?([\w.,& ()\[\]\'\`-]{8,}) ?\)[- ]{0,3}\(.+Dready Niek.+\)[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"[\w.,& ()\[\]\'\`-]{8,}?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[21/23] - "JNEQ3_20130413_028.vol0+1.par2" - 282,65 MB <-> Partner of secretusenet.com <-> yEnc
		if (preg_match('/^\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB] <-> Partner of secretusenet\.com <->[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Yogi_and_Husky--Nu_Sound_EP-(RSR021)-WEB-2012-dh - "Yogi_and_Husky--Nu_Sound_EP-(RSR021)-WEB-2012-dh.r00" yEnc
		if (preg_match('/^[\w.,& ()\[\]\'\`-]{8,}?[- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Zido.Alben.All.in.One.Read.NFO-Ren & Stimpy - [35/43] - "Zydo.part33.rar" yEnc
		if (preg_match('/^([\w.,& ()\[\]\'\`-]{8,}?)[- ]{0,3}\[\d+\/\d+\][- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //! ! !! - h311b0y101022014 - !! ! ! [01/14] - "h311b0y101022014.par2" yEnc
		if (preg_match('/(h311b0y|Hellboy).+\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function mp3_complete_cd()
	{
		//(05/10) - [Lords-of-usenet] <<Partner of SSL-News.info>>  "Wynardtage Praise The Fallen(2007).vol00+01.PAR2" - 132,64 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[ _-]{0,3}\[Lords-of-usenet\][ _-]{0,3}<<Partner of SSL-News.info>>[ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //(06/11) - <www.lords-of-usenet.org><by Nerts> - "Diens - Schwarzmale.vol00+01.PAR2" - 141,07 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[ _-]{0,3}<www\.lords-of-usenet\.org><by Nerts>[ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //<www.Lords-Of-Usenet.org><by Nerts> (09/18) - "Mantus - Fatum (2013) [2CD].FH.vol00+2.PAR2" - 336,39 MB - yEnc
		if (preg_match('/^<www\.lords-of-usenet\.org><by Nerts>[ _-]{0,3}\(\d+\/\d+\)[ _-]{0,3}[ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //(08/15) "Noemi-Red.(Unreleased).2006.by.NYCrules.vol000+01.PAR2" - 179,66 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		//[16_Bit_Lolitas-Warung_Brazil_002-2CD-2012-iHF] [www.usenet4ever.info by Secretusenet] -  "000-16_bit_lolitas-warung_brazil_002-2cd-2012-ihf.sfv" yEnc
		//[3RD_Prototype_-_On_My_Way-(TB7368)-WEB-2013-FMC] [www.usenet4ever.info by Secretusenet] -  "01-3rd_prototype_-_deafback-when_you_are_in_the_dark_(deafback_remix).mp3" yEnc
		//[Armin_Van_Buuren_Feat._Fiora-Waiting_For_The_Night-(ARMD1140)-WEB-2013-UKHx] [www.usenet4ever.info by Secretusenet] -  "00-armin_van_buuren_feat._fiora-waiting_for_the_night-(armd1140)-web-2013-ukhx.m3u" yEnc
		if (preg_match('/^\[([a-zA-Z0-9-_\(\)\.]+)\] \[www\.usenet4ever\.info by Secretusenet\] -  "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>ghost-of-usenet.org<< 16_Bit_Lolitas-Warung_Brazil_002-2CD-2012-iHF >>www.SSL-News.info> -  "101-16_bit_lolitas-warung_brazil_002_cd1.mp3" yEnc
		if (preg_match('/^>ghost-of-usenet\.org<< ([a-zA-Z0-9-_\(\)\.]+) >>www\.SSL-News\.info> -  "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//Blok_B-Bienvenue_Dans_Mon_Blok_Vol.1-2005-BZ_INT [20 of 27] "01-gangsta.mp3" yEnc
		//DJ Neev - HedKandi_2013-06-08 (Ministry of Sound Radio) [01/13] - "DJ Neev - HedKandi_2013-06-08 (Ministry of Sound Radio).par2" yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\(\)\.]+) \[\d+(\/| of )(\d+\])[-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(www.Thunder-News.org) >Dee_And_Crane-Let_The_Music_Play-WEB-2012-UKHx< <Sponsored by Secretusenet> - "05-dee_and_crane-let_the_music_play_(jay_frogs_keep_the_groove_remix_edit).mp3" yEnc
		if (preg_match('/^\(www\.Thunder-News\.org\) >(.+?)< <Sponsored by Secretusenet> - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[1/1] - (MP3 ALBUM CHARTS) - "Andreas Gabalier - Volksrock'n'roller-Live.rar" yEnc
		if (preg_match('/^\[\d+\/\d+\] - \((MP3 )?ALBUM CHARTS\) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '  yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //"Miley Cyrus - Bangerz (Deluxe Version).nzb" - yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //VA 200 NRJ 2014 CD2 mp3_320kbps[18/27]VA 200 NRJ 2014 CD2 mp3_320kbps"(217) [Capital Cities] Safe And Sound.mp3"  yEnc
		if (preg_match('/^.+\[\d+\/\d+\](.+)"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function mp3_full_albums()
	{
		//. - [05/10] - "Blues 'N Trouble - With Friends Like These [1989].vol00+01.par2" yEnc
		if (preg_match('/^\. - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(001/122) "[www.Lords-of-Usenet.org]_[Partner von SSL-News]_Blind_Guardian-Discographie.par2" - 2,20 GB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "\[www\.Lords-of-Usenet\.org\]_\[Partner von SSL-News\]_(.+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//(06/10) "Pink Floyd - Dark Side Of The Moon [MFSL UDCD 517].vol00+01.PAR2"- - 67,88 MB - Pink Floyd - Dark Side Of The Moon [MFSL UDCD 517] yEnc
		//(07/11) "VA - Twilight - New Moon - Ost.vol00+01.PAR2"- - 93,69 MB - VA - Twilight - New Moon - Ost yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,4}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}.+?yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(Kitty Samtpfote) [01/12] - "Electronic Music of the 80s.Michael Garrison - In The Regions Of Sunreturn and beyond 1991.par2 . http://usenet4ever.info_Sponsored by www.Secretusenet.com  " yEnc
		if (preg_match('/^\(.+\) \[\d+\/(\d+\]) - "(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) . http:\/\/usenet4ever\.info_Sponsored by www\.Secretusenet\.com  " yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(www.Thunder-News.org) >Boehse Onkelz - Discography< <Sponsored by AstiNews> - (113/145) - "Boehse Onkelz - Discography.s10" yEnc
		if (preg_match('/^\(.+\) >(.+?)< <Sponsored by AstiNews> - \(\d+\/(\d+\)) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[00021]-["1999 Alphaville - Dreamscapes.part069.rar"[ yEnc
		if (preg_match('/^\[\d+\]-\["([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '\[ yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(nzbDMZ) [0/2] - "Miles Crossing - Miles Crossing (2011).nzb" yEnc
		if (preg_match('/^\(.+\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[06/10] - "Jeff Healey - Legacy Volume One [The Singles].vol00+01.PAR2" yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //00008 "2003 Camouflage - Sensor.par2" yEnc
		if (preg_match('/^\d+ "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Alex Oriental Experience_-_Live II (Live II) [1/9] - "01_Red_Dress.mp3" yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\(\)\.]+) \[\d+(\/| of )(\d+\])[-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function multimedia()
	{
		//Escort.2006.DUTCH.WEB-RiP.x264-DLH - [01/23] - "Escort.2006.DUTCH.WEB-RiP.x264-DLH.par2" yEnc
		//Tusenbroder.S01E05.PDTV.XViD.SWEDiSH-NTV  [01/69] - "ntv-tusenbroder.s01e05.nfo" yEnc
		if (preg_match('/^([A-Z0-9a-z.-]{10,})\s+(- )?\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[####]-[FULL]-[#a.b.teevee@EFNet]-[ Tosh.0.S02E14.720p.HDTV.x264-aAF ]-[10/21] - "aaf-tosh.0.s02e14.720p.r07" yEnc
		if (preg_match('/\[[#]+\]-\[.+?\]-\[.+?\]-\[ ([\w.,& ()\[\]\'\`-]{8,}?) \][- ]\[\d+\/\d+\][ -]{0,3}("|#34;).+?/', $this->subject, $match)) {
			return $match[1];
		} //[ Top.Chef.S11E12.Mississippi.Mud.Bugs.720p.WEB-DL.AAC2.0.H.264-TVSmash ] - [13/38] - "tc1112-720p.r10" yEnc
		if (preg_match('/^\[ ?([\w.,& ()\[\]\'\`-]{8,}?) ?\][-_ ]{0,3}\[\d+\/\d+\][-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Handyman Shows-TOH-S32E10 - File 01 of 32 - yEnc
		if (preg_match('/^Handyman Shows-(.+) - File \d+ of \d+ - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //- "Auction Hunters S04E04.HDTV.x264-StarryNights1.nzb" yEnc
		if (preg_match('/.*"([\w.,& ()\[\]\'\`-]{8,}?\b.?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //homeland.s02e12.1080p.bluray-bia.r08 - [011/111]  yEnc
		if (preg_match('/^([\w.,& ()\[\]\'\`-]{8,}?\b.?)\.[A-Za-z0-9]{2,4} - \[\d+\/(\d+\])  yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function multimedia_anime()
	{
		//High School DxD New 01 (480p|.avi|xvid|mp3) ~bY Hatsuyuki [01/18] - "[Hatsuyuki]_High_School_DxD_New_01_[848x480][76B2BB8C].avi.001" yEnc
		if (preg_match('/.+? \((360|480|720|1080)p\|.+? ~bY .+? \[\d+\/\d+\] - "(.+?\[[A-F0-9]+\].+?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		}

		//[Hatsuyuki]_Seirei_Tsukai_no_Blade_Dance_-_03_[720p] [E18FCA59] [01/18]
		if (preg_match('/(\[[a-z0-9-]+\][\w_-]+_\d+_\[\d{3,4}[ip]\]\s\[[A-F0-9]{8}\])\s\[\d+\/\d+\]/i', $this->subject, $match)) {
			return $match[2];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function multimedia_anime_highspeed()
	{
		//High School DxD New 01 (480p|.avi|xvid|mp3) ~bY Hatsuyuki [01/18] - "[Hatsuyuki]_High_School_DxD_New_01_[848x480][76B2BB8C].avi.001" yEnc
		if (preg_match('/.+? \((360|480|720|1080)p\|.+? ~bY .+? \[\d+\/\d+\] - "(.+?\[[A-F0-9]+\].+?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function multimedia_documentaries()
	{
		//"Universe S4E08.part40.rar" - [41 of 76 - 10013 kb] yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \[\d+ of \d+ - \d+ [kKmMgG][bB]\] yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function multimedia_scifi()
	{
		//some m4vs - "SilverHawks_v1eps01_The Origin Story.par2" yEnc
		if (preg_match('/^some m4vs - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function music()
	{
		//( 80's Giga Hits Collection (32 CDs) By Dready Niek )  By Dready Niek ) [44/54] - "80's Giga Hits Collection (32 CDs) By Dready Niek.part43.rar" yEnc
		if (preg_match('/^.+By Dready Niek \) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(00/24]  Marco Mengoni - Prontoacorrere (2013) "Marco Mengoni - Prontoacorrere (2013).nzb" - nightsteff  yEnc
		if (preg_match('/^\(\d+\/\d+\]  (.+?) ".+?' . $this->e0 . ' - nightsteff  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(80's Disco-Soul-Funk) [136/426] - ["Level 42 - Lessons In Love.mp3"] yEnc
		if (preg_match('/^\((.+)\) \[\d+\/\d+\] - \[".+?' . $this->e0 . '\] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [001/153] - "C4 House Party Horse Meat Disco Set 6.nfo" C4 House Party Horse Meat Disco Set 6 yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [19/22] - C.K.N. Demo 85  "19-rotten system.mp3" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - (.+)[ -]{0,3}".+?' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Jungle Fever Tapepacks) [67/79] - "Jungle Fever Tapepacks.part65.rar" yEnc
		if (preg_match('/^\((.+)\) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[1/8] - "Black Market Flowers - Bind (1993).sfv" yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[DreamPieter] (Glen Tipton - Two solo albums) [04/23] - "Glenn Tipton - Baptizm of Fire - 04 - Fuel Me Up.mp3" yEnc
		if (preg_match('/^\[DreamPieter\] \((.+)\) \[\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<<< <ghost-of-usenet.org> <"Dream Dance Vol. 21-30 - 20CDs MP3 - Ghost.part20.rar"> >www.SSL-News.info<  - (22/32) - 2,45 GB yEnc
		if (preg_match('/^.+ghost-of-usenet\.org.+"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '> >www\.SSL-News\.info<  - \(\d+\/\d+\) - \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Musikjunkie-The.Voice.Of.Germany.2013.The.Best.of.Liveshows.4.CD.Box.Set.VBR [15/28]"voice.part13.rar" yEnc
		if (preg_match('/^Musikjunkie-([\pL\pN\pM\pP. -]{8,})[- ]{0,3}\[\d+\/\d+\]".+?' . $this->e1 . 'u', $this->subject, $match)) {
			return $match[1];
		} //"8 Wenn ich einmal gross bin .mp3" Koelschefetz postet.Die Filue -Immer Wigger  yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Der etwas andere Mix - Deutscher Foxmix 14 (by dem verrÃ¼ckten Lordi) (3/8) "Der etwas andere Mix - Deutscher Foxmix 14.txt" - 271,24 MB 11.11.03 yEnc
		if (preg_match('/^.+\(by dem verrÃ¼ckten Lordi\)[- ]{0,3}\(\d+\/\d+\)[- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB].+[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(01/10) "MylÃ¨ne Farmer - Monkey me (2012 Bluray Audio).rar" - 1,56 GB - yEnc
		//(3/7) "DJ Taz3 - Your Choice Sendung Silvester 2012.mp3.vol00+01.PAR2" - 119,50 MB -RadioShows und Livesets yEnc
		if (preg_match('/^\(\d+\/\d+\)[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB][- ]{0,3}.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[00/20]  Nomadi - Terzo Tempo (2012) "Nomadi - Terzo Tempo (2012).nzb" - nightsteff  yEnc
		//[02/56] - The Beatles - The Decca Tapes "Beatles - The Decca Tapes.part01.rar" yEnc
		if (preg_match('/^[\(\[]\d+\/\d+[\]\)][ -]{0,3}(.+) ".+?' . $this->e0 . '([ -]{0,3}nightsteff)?[ -]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[Marillion - Live From Cadogan Hall 2011 (DVD 1)] [981/98] - "Marillion - Live From Cadogan Hall 2011 (DVD 1).par2" yEnc
		if (preg_match('/^\[.+\] \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //XTC - NMR - 2001 - Homegrown - The Wasp Star Home Demos [22 of 34] "2001 - Homegrown - The Wasp Star Home Demos.vol00+01.PAR2" yEnc
		if (preg_match('/^.+ \[\d+ of \d+\] "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Wendy O Williams Bump n Grind - "Wendy.O.Williams.Bump.n.Grind.nfo"  yEnc
		if (preg_match('/^.+[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //The Amity Affliction - Chasing Ghosts_lossless (01/11) - "The Amity Affliction - Chasing Ghosts_lossless.par2" - 321,40 MB - codari_4_usenetrevolution.info-Partner of SSL-News yEnc
		if (preg_match('/^.+ \(\d+\/\d+\) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB].+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Bob Marley & The Wailers - African Herbsman.nzb 0/8  yEnc
		if (preg_match('/^([\w.,& ()\[\]\'\`-]{8,}?\b.?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) \d+\/\d+[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //DOG DAYS' Season 2 ED Single - Natsu no Yakusoku (Horie Yui)[KICM-1402].- DOG DAYS' Season 2 ED Single - Natsu no Yakusoku (Horie Yui)[KICM-1402].nzb [00/13]  yEnc
		if (preg_match('/^[\w. &()\[\]\'-]+?\.- ([\w.,& ()\[\]\'\`-]{8,}?\b.?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) \[\d+\/\d+\][- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	// a.b.music.mp3
	public function music_mp3()
	{
		//(00/20]  Nomadi - Terzo Tempo (2012) "Nomadi - Terzo Tempo (2012).nzb" - nightsteff  yEnc
		if (preg_match('/^[\(\[]\d+\/\d+[\]\)][ -]{0,3}(.+) ".+?' . $this->e0 . '[ -]{0,3}nightsteff[ -]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [0/8] - Crionics Post - Alice In Chains - Dirt REPOST"Alice In Chains - Dirt.nzb" yEnc
		if (preg_match('/^.+?\[\d+\/(\d+\][-_ ]{0,3}.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //(????) [001/153] - "C4 House Party Horse Meat Disco Set 6.nfo" C4 House Party Horse Meat Disco Set 6 yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [19/22] - C.K.N. Demo 85  "19-rotten system.mp3" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - (.+)[ -]{0,3}".+?' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(BierbauchFreddy Quwinn seine grÃ¶Ãten Coversongs Vol2) [22/60] - "040c - Freddy Quinn - Don't Forbid Me (1957) (NH 22 639, EPH 20 575).mp3" yEnc
		if (preg_match('/^\((.+)\) \[\d+\/\d+\][ -]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[ Spectrum (ft. Matthew Koma) (Extended Mix) - Zedd ] - [2012] - [256 Kbps MP3] [1 of 7] "Spectrum (ft. Matthew Koma) [Extended Mix].mp3" yEnc
		if (preg_match('/^\[ (.+) \] - \[\d+\] - \[\d+ Kbps MP3\] \[\d+ of \d+\] ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//[1/1] - (150 MP3 Album Charts) - "Atlantean Kodex - The White Goddess.rar"  yEnc
		//[1/1] - (MP3 Album Charts) - "Black Sabbath - 13.rar"  yEnc
		//[1/1] - (Top100 Album Charts) - "Bastille - Pompeii.rar"  yEnc
		//[1/1] - (Top100 Charts) - "Beatrice Egli - Gluecksgefuehle.rar"  yEnc
		//[1/1] - (Top100 Single Charts) -  "Alicia Keys - Girl On Fire.rar"  yEnc
		if (preg_match('/^\[\d+\/\d+\][ -]{0,3}\(((Top)?\d+ )?(MP3 )?((Album|Single) )?Charts\)[ -]{0,4}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[6];
		}
		//[1/1] - Album Top 100 - "Amy MacDonald - Life In A Beautiful Light.rar"  yEnc
		//[1/1] - Top 100 Album Charts 2012 - "Cro - Einmal Um Die Welt.rar"  yEnc
		if (preg_match('/^\[\d+\/\d+\][ -]{0,3}(Album )?Top \d+( Album Charts \d+)?[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[3];
		}
		//[1/1] (Album Top 100 - 2012) - "Cro - Einmal Um Die Welt.rar"  yEnc
		//[1/1] (Album Top 100 2012) - "Cro - Einmal Um Die Welt.rar"  yEnc
		//[1/1] (Album Top 100) - "Cro - Raop.rar"  yEnc
		if (preg_match('/^\[\d+\/\d+\][ -]{0,3}\(Album Top \d+(( -)? \d+)?\)[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //100 Greatest One Hit Wonders 80s[mp3].rar  yEnc
		if (preg_match('/^(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(001/225) "Dead Can Dance (mp3 - 320Kbs - CBR).part001.rar" - 1.11 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //[1/1] (MP3 Album)- "Agnes Obel - Aventine.rar"  yEnc
		if (preg_match('/^[\(\[]\d+\/\d+[\]\)][ -]{0,3}\(MP3 Album\)[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[ech0park]-[spotnet]-[Snow Patrol 2012 Tour Sampler - 2012-iND] [02/20] - "02-snow_patrol-ill_never_let_go.mp3" yEnc
		if (preg_match('/^\[ech0park\]-\[spotnet\]-\[(.+?)\] \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<< http://usenet-piraten.info >> - [13/15] - "Monster Cyborg - Overkill (mp3).vol0+1.par2" yEnc
		if (preg_match('/^.+usenet-piraten\.info.+[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[1/8] - "Nightwish - Storytime (Retail-Single 2011).sfv" -  ShapterV yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[- ]{0,4}ShapterV yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //100 Rock - Vol.04<>usenet-piraten.info<>www.newsconnection.eu<> - (1/5)  "100 Rock - Vol.04.par2" - 101,65 MB - yEnc
		if (preg_match('/^.+usenet-piraten\.info.+newsconnection\.eu.+[- ]{0,3}\(\d+\/\d+\)[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //40 Italo Dance Tunes (2CD) - "00. 40 Italo Dance Tunes 2011.nfo" [02/ 50] nightsteff  yEnc
		if (preg_match('/^(.+?)[- ]{0,3}".+?' . $this->e0 . '[- ]{0,3}\[\d+\/ (\d+\]) nightsteff[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Bud Spencer & Terence Hill - Greatest Hits Vol 1 (1995) "04 - Just A Good Boy.mp3" nightsteff  yEnc
		if (preg_match('/^(.+?) ".+?' . $this->e0 . '[ -]{0,3}nightsteff  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Attn: bearcat - Avenged Sevenfold - Avenged Sevenfold 320[17/18] - .vol15+16.par2 5.9Mb yEnc
		if (preg_match('/^Attn: \w+ - (.+?)\[\d+\/(\d+\]) - .+?([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}\d+[.,]\d+[kKmMgG][bB][- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} else {
			return $this->generic();
		}
	}

	public function multimedia_vintage_film_pre_1960()
	{
		//WE.WANT.OUR.MUMMY.(THE.THREE.STOOGES).(1939) [08/15] - "The.Three.Stooges.S06E02.We.Want.Our.Mummy.DVDRip.XviD-DEiMOS.part6.rar" yEnc
		if (preg_match('/^\S+ \[\d+\/\d+\] - ("|#34;)(\S+)\.part\d+\.rar("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //[Hepburn-Tracy Movie Pack] [4of8] Xvid 700mb ENG No-Subs - [00/62] - "State of the Union (1948).nzb" yEnc
		if (preg_match('/^\[.+\] \[\d+of\d+\] .+ - \[\d+\/\d+\] - ("|#34;)(.+)\.nzb("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //{Dracula #01}  Dracula (1931) - Xvid DVDRip 624x464 NTSC FS Dual Audio - [00/15] - "DRACULA.nzb" yEnc
		if (preg_match('/^\{.+\} (.+ \(\d{4}\)) .+ \[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //The Incredible Shrinking Man (1957) - Xvid DVDRip 688x384 NTSC WS - [00/16] - "TISM.nzb" yEnc
		if (preg_match('/^(.+ \(\d{4}\)) - .+ - \[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Sudden.Danger.1955.VHSRip.XviD-KG [01/56] - "Sudden.Danger.1955.VHSRip.XviD-KG.nfo" yEnc
		if (preg_match('/^(.+\d{4})\.(VHSRip|COLOR).+\[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //W.C. Fields - You Can't Cheat An Honest Man - 1939 - DVDRip - Xvid - [00/68] - "you can't cheat an honest man 1939.nzb" yEnc
		if (preg_match('/^.+ - (.+) -( \d{4}) - DVDRip.+ \[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //-=>EnJoY!<-=->Experimental/Avant-Garde/Artistic Shorts<-=->(Day1/?) [00/26] - "Unseen Cinema - Disc I - The Mechanized Eye - Experiments in Technique and Form - 1894-1941 (480p,x264).nzb" yEnc
		if (preg_match('/^.+enjoy\!.+\[\d+\/\d+\] - ("|#34;)(.+)\.(nzb|nfo|part\d+\.rar|part\.PAR2)("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //Goodbye.Charlie.1964.COLOR.TVRip.XviD-CG [01/56] - "Goodbye.Charlie.1964.COLOR.TVRip.XviD-CG.nfo" yEnc
		if (preg_match('/^(.+\d{4})(\.COLOR)?\.(TV|VHS|VHSTV)Rip.+ \[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //The.Adventures.of.Sadie.(Our.Girl.Friday).1953.COLOR.DVDRip.XviD-CG [01/69] - "Our.Girl.Friday.1953.COLOR.DVDRip.XviD-CG.nfo" yEnc
		if (preg_match('/^(\S+\d{4})\.COLOR\.DVDRip.+ \[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Singin' in the Rain (1952) AVC 480p.MKV.001" 01 of 15
		if (preg_match('/^("|#34;)(.+)\.MKV\.\d+("|#34;) \d+ of \d+$/', $this->subject, $match)) {
			return $match[2];
		} //RANDOLPH.SCOTT.-.PARIS.CALLING.(1941) [42/49] - "Paris.Calling.1941.VHSRip.AVC-DD.vol001+002.par2" yEnc
		if (preg_match('/^.+\(\d{4}\)(\.EXTRA\.PARS|\.\[REPOST\])? \[\d+(\/| of )\d+\][ -]+("|#34;)(.+)(\.vol\d+\+\d+\.par2|-\[big.+\]\.nfo|\.nzb|\.nfo| \- .+.avi\.\d+)("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //RANDOLPH.SCOTT.-.PARIS.CALLING.(1941) [42/49] - "Paris.Calling.1941.VHSRip.AVC-DD.vol001+002.par2" yEnc
		if (preg_match('/^.+\(\d{4}\)(\.EXTRA\.PARS|\.\[REPOST\])? \[\d+(\/| of )\d+\][ -]+("|#34;)(.+)(\.vol\d+\+\d+\.par2|-\[big.+\]\.nfo|\.nzb|\.nfo| \- .+.avi\.\d+|\.par2)("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //Mickey.1918.SILENT.DVDRip.XviD-KG [01/70] - "Mickey.1918.SILENT.DVDRip.XviD-KG.nfo" yEnc
		if (preg_match('/^.+\d{4}\.(SILENT|DVDRip).+ \[\d+\/\d+\] - ("|#34;)(.+)\.nfo("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //More Assorted Goodies - REQ:Decasia [01/16] - "The Bullwinkle Show with Occasional Rare Bullwinkle Puppet - BnW.part.nzb" yEnc
		if (preg_match('/^(More Assorted Goodies|Even More Offerings) - REQ:.+ \[\d+\/\d+\] - ("|#34;)(.+)\.part\.nzb("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //The Birth of a Nation - 1915 [1 of 71] "The Birth of a Nation - 1915.part01.rar" yEnc
		if (preg_match('/^(.+) -( \d{4}) \[\d+ of \d+\] ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Musicals [05/10] - "The band wagon 1953.nzb" yEnc
		if (preg_match('/^Musicals(\.NZBs)? \[\d+\/\d+\] - ("|#34;)(.+)\.nzb("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //Torchy Runs for Mayor (Maypo) [13 of 24] "Torchy Runs for Mayor.avi.013" yEnc
		if (preg_match('/^(Torchy.+) \[\d+ of \d+\] ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[01/27] - "Monkey Business (1952).avi.001" (1/130)
		if (preg_match('/^\[\d+\/\d+\] - ("|#34;)(.+ \(\d{4}\))\.avi\.\d+("|#34;)( \(\d+\/\d+\))?$/', $this->subject, $match)) {
			return $match[2];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function nl()
	{
		//(????) [01/46] - "NCIS.S11E02.Past.Present.and.Future.1080p.WEB-DL.DD5.1.H.264-CtrlHD.par2" yEnc
		if (preg_match('/\((\d+|\?+)\) \[\d+\/\d+\].*"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //JN - [1/3] - "Operatie Teheran-Joel C Rosenberg JN.par2" yEnc
		if (preg_match('/^\w+[-_ ]{0,3}[\[\(]\d+\/\d+[\]\)][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //( Overlord II RELOADED ) - [013/112] - "rld-olii.part001.rar" yEnc
		if (preg_match('/^\( ([\w. -]{8,}) \)[-_ ]{0,3}\[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Batman.Arkham-Origins.Initiation-RELOADED - [006/296] - "r.vol010+08.PAR2" yEnc
		if (preg_match('/^([\w. &()-]{8,}?\b)[- ]{0,3}\[\d+\/(\d+\])[- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(PC-ISO) ( Trials.Fusion.Riders.of.the.Rustlands-SKIDROW ) [10/40] - "sr-tfrotr.part01.rar" yEnc
		if (preg_match('/^\(PC-ISO\)[-_ ]{0,3}\( ([\w.,& ()\[\]\'\`-]{8,}?\b) \) \[\d+\/\d+\][-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function pictures_erotica_anime()
	{
		// [ABPEA - Original] Arima Zin - Tennen Koiiro Alcohol [BB, Boy] - 005 of 229 - yEnc "Tennen_Koiiro_Alcohol-005.jpg" to [ABPEA - Original] Arima Zin - Tennen Koiiro Alcohol [BB, Boy]
		if (preg_match('/(.*)\s+-\s+\d+\s+of\s+\d+\s+-\s+yEnc\s+".*"/i', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function ps3()
	{
		//[4197] [036/103] - "ant-mgstlcd2.r34" yEnc
		if (preg_match('/^(\[\d+\] )\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function series_tv_french()
	{
		//(01/34) "Merlin.2008.1x04.la.vengeance.de.nimue.par2" - 388,38 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB]( -)? yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //Breaking.Bad.S02.MULTi.720p.BluRay.AC3.x264-BoO [749/883] - "212ACS3517.part01.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9._-]+)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Dawson.Saison2.DVDRIP.x264.FRENCH [111 of 196] "Dawson.S2.E22.Tout feu, tout flambe.m4v.003" yEnc
		if (preg_match('/^([a-zA-Z0-9._-]+)[-_ ]{0,3}[\(\[]\d+ of (\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[01/22] - "Unnatural.History.1x03.Espion.En.Sommeil.FR.LD.par2" yEnc
		if (preg_match('/^[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[MagNeum 3.14 S1 D.V.D + par2][1148/1167] - "ZDFRIKK8470DO776.D7P" yEnc
		if (preg_match('/^\[(.+?)\][-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function sony_psp()
	{
		//(01/10) "Assassins Creed - Bloodlines [EUR].par2" - 408,46 MB yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+([.,]\d+ [kKmMgG])?[bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(20444) FIFA_12_EUR_GERMAN_PSP-ABSTRAKT [01/50] - "as-f12g.nfo" yEnc
		if (preg_match('/^\(\d+\) ([a-zA-Z0-9 -_\.]+) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function sound_mp3()
	{
		//- codari_4_usenetrevolution.info-Partner of SSL-News UK.Single.Charts.Top.40  [01/25] - "UK.Single.Charts.Top.40.par2" - 301,70 MB - yEnc
		if (preg_match('/.+[-_ ]{0,3}[\(\[]\d+\/\d+[\)\]][-_ ]{0,3}"(.+)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Terraplane Sun - Funnel of Love.mp3" - 21.55 MB - (1/6) - yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}\(\d+\/(\d+\))[ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //trtk09920 - [01/12] - "Guido Negraszus - Night Cafe Iii (Freedom Travellers) (2012)(320).par2" yEnc
		if (preg_match('/^trtk\d+[ -]{0,3}\[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(????) [001/153] - "C4 House Party Horse Meat Disco Set 6.nfo" C4 House Party Horse Meat Disco Set 6 yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [19/22] - C.K.N. Demo 85  "19-rotten system.mp3" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - (.+)[ -]{0,3}".+?' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(LUNATIC SOUL - IMPRESSIONS) [00/18] - "Lunatic Soul - Impressions 2011.nzb" yEnc
		if (preg_match('/^\(.+\)[ -]{0,3}\[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[1/8] - "Black Market Flowers - Bind (1993).sfv" yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//[1/1] - (150 MP3 Album Charts) - "Atlantean Kodex - The White Goddess.rar"  yEnc
		//[1/1] - (MP3 Album Charts) - "Black Sabbath - 13.rar"  yEnc
		//[1/1] - (Top100 Album Charts) - "Bastille - Pompeii.rar"  yEnc
		//[1/1] - (Top100 Charts) - "Beatrice Egli - Gluecksgefuehle.rar"  yEnc
		//[1/1] - (Top100 Single Charts) -  "Alicia Keys - Girl On Fire.rar"  yEnc
		if (preg_match('/^\[\d+\/\d+\][ -]{0,3}\(((Top)?\d+ )?(MP3 )?((Album|Single) )?Charts\)[ -]{0,4}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[6];
		} //[1/1] - (Album Top 100) - "[Dance] David Guetta - One Love (2010) .rar"  yEnc
		//[1/1] - (Album Top 100) - "Aerosmith - Music From Another Dimension.rar"  yEnc
		//[1/1] - Album Top 100 - "ACDC - Live At River Plate.rar"  yEnc
		//[1/1] (Album Top 100 - 2012) - "Alicia Keys - Girl On Fire.rar"  yEnc
		//[1/1] (Album Top 100 2012) - "Asaf Avidan And The Mojos - One Day.rar"  yEnc
		if (preg_match('/^\[\d+\/\d+\][ -]{0,3}(\()?(Album|Single) Top \d+ ([- ]{0,2}\d+)?(\))? - "(\[.+?\] )?(.+?)' . $this->e0 . ' {1,4}yEnc$/i', $this->subject, $match)) {
			return $match[6];
		} //[1/1] - Top 100 Album Charts 2012 - "Aura Dione feat. Rock Mafia - Friends.rar"  yEnc
		if (preg_match('/^\[\d+\/\d+\][ -]{0,3}Top \d+ Album Charts \d+[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' {1,4}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //<>usenet-piraten.info<>partner<>ssl-news.info<> - [10/10] - "Overexposed (Deluxe Version).vol31+23.par2" yEnc
		if (preg_match('/^.+usenet-piraten\.info.+ - \[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(1/1) "Adam Levine (Maroon 5) & Alicia Keys - Daylight & Girl on fire LIVE 55TH GRAMMY AWARDS 320Kpbs.mp3" yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(1/4) - VERBAteamÂ present - "Avril Lavigne - Rock 'N Roll (Official Audio).mp3" - 5,80 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) - VERBAteamÂ present - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //[1/1] - (Album Top 1000) - "Davis, Miles - Complete Live at the Plugged Nickel 1965.rar"  yEnc
		if (preg_match('/^\[\d+\/\d+\][ -]{0,3}\(Album Top \d+\)[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[1/1] - Album Top 100 - "Rammstein - Made In Germany 1995-2011.rar"  yEnc
		if (preg_match('/^\[\d+\/\d+\][ -]{0,3}Album Top \d+[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Andrea Berg - My Danish Collection (2013) by dem verrÃ¼ckten Lordi (14/27) "Andrea Berg - My Danish Collection (2013).par2" - 132,74 MB  150920134 yEnc
		//Der Deutsche Beat Mix Teil 2 auf wunsch (by dem verrÃ¼ckten Lordi) (2/9) "Der Deutsche Beat Mix Teil 3 Back.jpg" - 117,84 MB 13.11.05 yEnc
		if (preg_match('/^(.+?) (\()?by dem verrÃ¼ckten Lordi(\))? {1,2}\(\d+\/\d+\) ".+?' . $this->e0 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB].+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //Christian Anders - Tief in dir  (15/24) "Christian Anders - Tief In Dir Back.jpg" - 58,56 MB by dem verrÃ¼ckten Lordi 0703123 yEnc
		if (preg_match('/^(.+?)  \(\d+\/\d+\) ".+?' . $this->e0 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB] {1,2}by dem verrÃ¼ckten Lordi.+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //Der etwas andere Mix - Wilde Herzenmix (auf wunsch) neu (by dem verrÃ¼ckten Lordi) (1/8) "Der etwas andere Mix - Wilde Herzenmix.par2" yEnc
		if (preg_match('/^Der etwas.+ - (.+) \(\d+\/\d+\) ".+?' . $this->e0 . '.+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //Full Discography - The Cranberries (01/47) "Full Discography - The Cranberries.par2" - 3,52 GB  2812111 yEnc
		if (preg_match('/^(.+?) \(\d+\/\d+\) ".+?' . $this->e0 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB] {1,2}\d+ yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //jean ferrat  annÃ©e 1967 Ã  1969  meil29 "17 Rien Ã  voir.mp3" yEnc
		if (preg_match('/^(.+?) meil29 ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Selected Songs by Various Artists - Depeche Mode - Personal Jesus (Acoustic Version).mp3  yEnc
		if (preg_match('/^Selected Songs by Various Artists - (.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})  yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function sound_mp3_complete_cd()
	{
		//(05/10) - [Lords-of-usenet] <<Partner of SSL-News.info>>  "Wynardtage Praise The Fallen(2007).vol00+01.PAR2" - 132,64 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[ _-]{0,3}\[Lords-of-usenet\][ _-]{0,3}<<Partner of SSL-News.info>>[ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //(06/11) - <www.lords-of-usenet.org><by Nerts> - "Diens - Schwarzmale.vol00+01.PAR2" - 141,07 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[ _-]{0,3}<www\.lords-of-usenet\.org><by Nerts>[ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //trtk09920 - [01/12] - "Guido Negraszus - Night Cafe Iii (Freedom Travellers) (2012)(320).par2" yEnc
		if (preg_match('/^trtk\d+[ -]{0,3}\[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(????) [01/31] - "Adrian von Ziegler - Discography - 2010-2013.par2" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<www.Lords-Of-Usenet.org><by Nerts> (09/18) - "Mantus - Fatum (2013) [2CD].FH.vol00+2.PAR2" - 336,39 MB - yEnc
		if (preg_match('/^<www\.lords-of-usenet\.org><by Nerts>[ _-]{0,3}\(\d+\/\d+\)[ _-]{0,3}[ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //(08/15) "Noemi-Red.(Unreleased).2006.by.NYCrules.vol000+01.PAR2" - 179,66 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		//[16_Bit_Lolitas-Warung_Brazil_002-2CD-2012-iHF] [www.usenet4ever.info by Secretusenet] -  "000-16_bit_lolitas-warung_brazil_002-2cd-2012-ihf.sfv" yEnc
		//[3RD_Prototype_-_On_My_Way-(TB7368)-WEB-2013-FMC] [www.usenet4ever.info by Secretusenet] -  "01-3rd_prototype_-_deafback-when_you_are_in_the_dark_(deafback_remix).mp3" yEnc
		//[Armin_Van_Buuren_Feat._Fiora-Waiting_For_The_Night-(ARMD1140)-WEB-2013-UKHx] [www.usenet4ever.info by Secretusenet] -  "00-armin_van_buuren_feat._fiora-waiting_for_the_night-(armd1140)-web-2013-ukhx.m3u" yEnc
		if (preg_match('/^\[([a-zA-Z0-9-_\(\)\.]+)\] \[www\.usenet4ever\.info by Secretusenet\] -  "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>ghost-of-usenet.org<< 16_Bit_Lolitas-Warung_Brazil_002-2CD-2012-iHF >>www.SSL-News.info> -  "101-16_bit_lolitas-warung_brazil_002_cd1.mp3" yEnc
		if (preg_match('/^>ghost-of-usenet\.org<< ([a-zA-Z0-9-_\(\)\.]+) >>www\.SSL-News\.info> -  "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//Blok_B-Bienvenue_Dans_Mon_Blok_Vol.1-2005-BZ_INT [20 of 27] "01-gangsta.mp3" yEnc
		//DJ Neev - HedKandi_2013-06-08 (Ministry of Sound Radio) [01/13] - "DJ Neev - HedKandi_2013-06-08 (Ministry of Sound Radio).par2" yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\(\)\.]+) \[\d+(\/| of )(\d+\])[-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(www.Thunder-News.org) >Dee_And_Crane-Let_The_Music_Play-WEB-2012-UKHx< <Sponsored by Secretusenet> - "05-dee_and_crane-let_the_music_play_(jay_frogs_keep_the_groove_remix_edit).mp3" yEnc
		if (preg_match('/^\(www\.Thunder-News\.org\) >(.+?)< <Sponsored by Secretusenet> - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[1/1] - (MP3 ALBUM CHARTS) - "Andreas Gabalier - Volksrock'n'roller-Live.rar" yEnc
		if (preg_match('/^\[\d+\/\d+\] - \((MP3 )?ALBUM CHARTS\) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '  yEnc$/i', $this->subject, $match)) {
			return $match[2];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function sounds_flac()
	{
		//[32974]-[FULL]-[#a.b.flac]-[ Tenniscoats-Tokinouta-JP-CD-FLAC-2011-BCC ]-[04/28] - "00-tenniscoats-tokinouta-jp-cd-flac-2011.nfo" yEnc
		if (preg_match('/^\[\d+\]-\[.+?\]-\[.+?\]-\[ (.+?) \]-\[\d+\/\d+] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[:SEK9:][FLAC/Metal]-[:Burn_The_Priest-Burn_The_Priest-Remastered-CD-FLAC-2005-TiLLMYDEATH:]-[01/18]-"00-burn_the_priest-burn_the_priest-remastered-cd-flac-2005-proof.jpg" yEnc
		if (preg_match('/^\[:.+:\]\[FLAC.+\]-\[:(.+):\]-\[\d+\/\d+\]-".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function sounds_lossless()
	{
		//http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
		if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//Rush - Sector One 24-96  (000/229] - ".nzb" yEnc
		//Stevie Ray Vaughan - Couldn't Stand the Weather  (01/19] - ".sfv" yEnc
		if (preg_match('/^([a-zA-Z0-9]+.+? - .+?)\s+\(\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //trtk07073 - [18/26] - "1990 Santana - Spirits Dancing In The Flesh (flac).part17.rar" yEnc
		if (preg_match('/^trtk\d+ - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //COMPLETE REPOST Magma - NMR - 1974 - Wurdah Itah [01 of 23] "1974 - Wurdah Itah.par2" yEnc
		if (preg_match('/^COMPLETE REPOST (.+? - )NMR -( \d{4}) - (.+?) \[\d+ of \d+\] ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3] . "(" . $match[2] . ")";
		} //Sensation - VA - Source Of Light (2CD 2012) [02 of 67] - "00 - Sensation - VA - Source Of Light (2CD 2012) [nmr].txt" yEnc
		if (preg_match('/^([A-Z0-9].+? - VA - .+?) \[\d+ of \d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Ryan McGarvey - Forward In Reverse [01/21] - "00 - Ryan Mcgarvey - Forward in Reverse.nfo" yEnc
		//JFC - The Timerewinder (NMR) [01/15] - "00 - The Timerewinder.nfo" yEnc
		//The Brothers Johnson - 1981 - Winners (2011 expanded remastered) [01/31] - "01 - The Real Thing.flac" yEnc
		//Jermaine Jackson - 1980 - Let's Get Serious [00/23] - "Jermaine Jackson - 1980 - Let's Get Serious.nzb" yEnc
		if (preg_match('/^([A-Z0-9][A-Za-z0-9 ]{2,} -( \d{4} -)? [A-Z0-9].+?( \(.+?\))?) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Miles Davis - In A Silent Way (1969) [2013 - HDTracks 24-176,4] - "iasw24-176.par2" yEnc
		//Bob James & David Sanborn - Quartette Humaine (2013) [HDTracks 24-88,2] - "qh24-88.par2" yEnc
		if (preg_match('/^([A-Z0-9].+? - [A-Z0-9].+? \(\d{4}\) \[.*?HDTracks.+?\]) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Sonny Landreth - 2010 - Mississippi Blues - 04 of 29 - 00 - Mississippi Blues.sfv yEnc
		//Fruteland Jackson - 1996 - ... Is All I Crave - 08 of 20 - 00 - Fruteland Jackson - ... Is All I Crave.log yEnc
		if (preg_match('/^([A-Z0-9].+? - \d{4} - .+?) - \d+ of \d+ - \d+ - .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(VA - Cafe Del Mar Dreams 5-2012-Samfie Man) [37/38] - "VA - Cafe Del Mar Dreams 5-2012-Samfie Man.vol063+040.par2" yEnc
		if (preg_match('/^\((VA - .+?)\) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(152092XA20) [08/16] - "Guns and Roses - Use Your Illusion I - 08-Back Off Bitch.flac" yEnc
		if (preg_match('/^\([A-Z0-9]+\) \[\d+\/\d+\] - "(.+?) - \d+-.+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Eros_Ramazzotti-Eros-IT-CD-FLAC-1997-FADA[04/26] - "00-eros_ramazzotti-eros-1997-fada.sfv" yEnc
		if (preg_match('/^([\w-]{5,})\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//.: VA-Liquid_Music-The_Future_House_Compilation-CD-FLAC-1993-WRS :. - .:www.thunder-news.org:. - .:sponsored by secretusenet.com:. - "00-va-liquid_music-the_future_house_compilation-cd-flac-1993-wrs.nfo" yEnc
		//.:Bruce_BecVar-Arriba-CD-FLAC-1993-JLM:. - .:thunder-news.org:. - .:sponsored by secretusenet.com:. - "00-bruce_becvar-arriba-cd-flac-1993.m3u" yEnc
		if (preg_match('/^.:[-_ ]{0,3}(.+?)[-_ ]{0,3}:..+?thunder-news\.org.+?secretusenet\.com:. - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //_ "CARUSO & GIGLI - O Sole Mio - The  Unknown.nzb" yEnc
		if (preg_match('/^[-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//"Art Tatum - The Complete Trio Sessions with Tiny Grimes & Slam Stewart - Vol 1.NFO" - 1 of 6 (1/1)
		//"Galen Jeter and the Dallas Jazz Orchestra - Thank You, Leon.NFO" - 1 of 5 (1/1) (1/1)
		if (preg_match('/^[-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[-_ ]{0,3}\d+ (of \d+)( \(\d+\/\d+\)){1,2} (yEnc)?$/', $this->subject, $match)) {
			return $match[1];
		} //"Doc Watson - 1973 - The Essential Doc Watson - 01 - Tom Dooley.flac" - 406.64 MB - yEnc
		if (preg_match('/^[-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function sounds_mp3()
	{
		//(dream-of-usenet.info) - [04/15] - "Enya-And_Winter_Came...-2008.part2.rar" yEnc
		if (preg_match('/^\(dream-of-usenet\.info\) - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
		if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //trtk09920 - [01/12] - "Guido Negraszus - Night Cafe Iii (Freedom Travellers) (2012)(320).par2" yEnc
		if (preg_match('/^trtk\d+[ -]{0,3}\[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>>> CREATIVE COMMONS NZB <<< "dexter romweber duo-lookout" - File 1 of 9: "creative_commons_nzb_dexter_romweber_duo-lookout.rar" yEnc
		if (preg_match('/^>>> CREATIVE COMMONS NZB <<< "(.+?)" - File \d+ of \d+: ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<usenet-space-cowboys.info>>>  <<<Powered by https://secretusenet.com>< "Justin_Bieber-Believe_Acoustic-2013-pLAN9_usenet-space-cowbys.info.rar" >< 4/6 (78.65 MB) >< 60.84 MB > yEnc
		if (preg_match('/^.+?usenet-space.+?Powered by.+? "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+? \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\) .+? \d+[.,]\d+ [kKmMgG][bB] .+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"The Absence - Riders Of The Plague" [00/14] - "the_absence-riders_of_the_plague.nzb" yEnc
		if (preg_match('/"(.+)"[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//( Albert Cummings Albums 6x By Dready Niek (1999-2012) )  ( ** By Dready Niek ** ) [11/20] - "Albert Cummings Albums 6x By Dready Niek (1999-2012).part10.rar" yEnc
		//( Fat Freddy's Drop - Blackbird (2013) -- By Dready Niek ) -- By Dready Niek ) [01/15] - "Fat Freddy's Drop - Blackbird (2013) -- By Dready Niek.par2" yEnc
		if (preg_match('/\( (.+?)\)[-_ ]{0,3}( |\().+\)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //( Addison_Road-Addison_Road-2008 ) [01/10] - "01. Addison Road - This Could Be Our Day.mp3" yEnc
		if (preg_match('/\( (.+?) \)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [0/8] - Crionics Post - Alice In Chains - Dirt REPOST"Alice In Chains - Dirt.nzb" yEnc
		if (preg_match('/^.+?\[\d+\/(\d+\][-_ ]{0,3}.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //(????) [001/153] - "C4 House Party Horse Meat Disco Set 6.nfo" C4 House Party Horse Meat Disco Set 6 yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [19/22] - C.K.N. Demo 85  "19-rotten system.mp3" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - (.+)[ -]{0,3}".+?' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(03/11) "Europe - Discography (1983 - 2009) (320 kbps CBR)  www.brothers-of-usenet.org - empfehlen - Newsconnection.par2" yEnc
		//(03/11) "Evanescence Diskographie (1998-2011) www.brothers-of-usenet.org - empfehlen - Newsconnection.par2" yEnc
		if (preg_match('/^\(\d+\/\d+\)[ -]{0,3}"(.+?) {1,3}www\.brothers-of-usenet\.org - empfehlen - Newsconnection' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(107/123) - "Mark.EG.M.Zone.Rave.Tape.Packs.Hard.Trance.1990s.vol006+04.PAR2" - 11.39 GB yEnc
		//(12/16) "Horrid Henry The Movie - Original Soundtrack.vol00+01.PAR2" - 102.32 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //[002/123] - "Mark.EG.M.Zone.Rave.Tape.Packs.Hard.Trance.1990s.part001.rar" yEnc
		if (preg_match('/^\[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //< usenetrevolution > <-> Partner of ssl-news.info <-> Anastacia.-.It's.a.Mans.World  [04/15] - "Anastacia.-.It's.a.Mans.World.part01.rar" - 100,47 MB - yEnc
		if (preg_match('/^.+usenetrevolution.+Partner of ssl-news\.info.+\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //<<<Old but Sold>>>  <<< >< >< "German Top 50 ODC - 12.08.2013.nfo" >< 02/33 (541,61 MB) >< 10,93 kB > yEnc
		if (preg_match('/^.+Old but Sold.+>< "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' >< \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\).+ yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<< <ghost-of-usenet.org> <"MC Basstard Diskographie 16CDs 2000-2011 MP3 - Ghost.part08.rar"> >www.SSL-News.info<  - (10/43) - 1,69 GB yEnc
		//<<< <ghost-of-usenet.org> >"UltraTraxx Rare Remixes - Vol 011 MP3 192kbps.par2"> >www.SSL-News.info<  - (1/9) - 120,82 MB yEnc
		if (preg_match('/^.+ghost-of-usenet.org[<>] [><]"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '> >www\.SSL-News\.info<  - \(\d+\/\d+\)[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB][- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //BY REQ:........! - "Keith Whitley - All American Country - .par2" [06/22]  yEnc
		if (preg_match('/^BY REQ.+ - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' \[\d+\/\d+\]  yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //Der etwas andere Mix - Wilde Herzenmix (auf wunsch) neu (by dem verrÃ¼ckten Lordi) (1/8) "Der etwas andere Mix - Wilde Herzenmix.par2" yEnc
		if (preg_match('/^Der etwas.+ \(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //DJ Inferno Beatport Xtreme September 2011[63/66] - "DJ Inferno Beatport Xtreme September 2011.vol073+55.PAR2" upp o-o  yEnc
		//Kastelruther Spatzen - Weihnachten Bei Uns Daheim (2011) (22/25) "Kastelruther Spatzen - Weihnachten Bei Uns Daheim (2011).vol00+1.PAR2" - 113,03 MB -  Tapier 13.11.02 yEnc
		if (preg_match('/^.+[\[\(]\d+\/\d+[\)\]][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //"8 Wenn ich einmal gross bin .mp3" Koelschefetz postet.Die Filue -Immer Wigger  yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Queens Of The Stone Age - Rated R (2000) (10th Anniversary Deluxe Edition 2010) [EAC/Lame V0] "QU2 - Queens of the Stone Age - Rated R.M3u" yEnc
		if (preg_match('/^.+"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //squeeze-east side story-nmr- [01/14] - 01-squeeze-in quintessence.mp3  yEnc
		if (preg_match('/^(.+?)- [\[\(]\d+\/\d+[\)\]][ -]{0,3}\d\d.+?([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})  yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function sounds_mp3_complete_cd()
	{
		//(01/11) "Der Kleine Vampir Komplett - Folge 01 bis 18 www.brothers-of-usenet.org - empfehlen - Newsconnection.eu.nfo" yEnc
		if (preg_match('/^\(\d+\/\d+\) "(.+?) www\.brothers-of-usenet\.org - empfehlen - Newsconnection(\.eu)?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(01/15) "Dafuniks - Enter The Sideshow Groove.rar.001"by_Funkeefly yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . 'by_Funkeefly yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //trtk09920 - [01/12] - "Guido Negraszus - Night Cafe Iii (Freedom Travellers) (2012)(320).par2" yEnc
		if (preg_match('/^trtk\d+[ -]{0,3}\[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(01/20) "Oz Discography by Grave Digger.par2" - 445,72 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(D The Best Of Leon Russell [23/28] - The Best Of Leon Russell.vol00+01.PAR2  yEnc
		if (preg_match('/^\(D(\))? (.+) {1,2}\[\d+\/\d+\] - .+?([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) {1,2}yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //<<< <ghost-of-usenet.org> <"Dream Dance Vol. 21-30 - 20CDs MP3 - Ghost.part20.rar"> >www.SSL-News.info<  - (22/32) - 2,45 GB yEnc
		if (preg_match('/^.+ghost-of-usenet\.org.+"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '> >www\.SSL-News\.info<  - \(\d+\/\d+\) - \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<Cowboyup35>>><<<Adele - Rolling In The Deep.mp4>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "Adele - Rolling In The Deep.mp4" >< 2/5 (77,13 MB) >< 63,94 MB > yEnc
		//<<<Thor0103>>><<<Amorphis_-_Tuonela-Digipak-1999-kispatak>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "09_rusty_moon-kispatak.mp3.par2" >< 01/26 (143,73 MB) >< 6,10 kB > yEnc
		if (preg_match('/^<<<.+>>><<<(.+)>>>usenet-space-cowboys\.info.+secretusenet\.com>< ".+?' . $this->e0 . ' >< \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\) >< \d+[.,]\d+ [kKmMgG][bB] > yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //<<<usenet-space-cowboys.info>>> THOR <<<Powered by https://secretusenet.com>< "Bangbros_Vs_Hansebanger_-_Kiezstyle-Promo-Vinyl-2006-ToTo_usenet-space-cowbys.info.avi" >< 3/5 (31,12 MB) >< 11,21 MB > yEnc
		if (preg_match('/^<<<usenet-space-cowboys\.info>>>.+secretusenet\.com>< "(.+?)-\w+_usenet-space-cowbys\.info' . $this->e0 . ' >< \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\) >< \d+[.,]\d+ [kKmMgG][bB] > yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //<<<Usenet-Space-Cowboys.info>>><<<Fuzzy>>> <Powered by https://secretusenet.com> < "Alice_Cooper-Trash-1989-Wyse.nfo" >< 2/8 (89,39 MB) >< 17,21 kB > yEnc
		if (preg_match('/^<<<usenet-space-cowboys\.info>>>.+secretusenet\.com> < "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' >< \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\) >< \d+[.,]\d+ [kKmMgG][bB] > yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //>>> www.lords-of-usenet.org <<< "Chris De Burgh - The Getaway.par2" - [1/9] - [115,51 MB] yEnc
		//>>> www.lords-of-usenet.org <<< "Das Deutsche Schlager Magazin Die Maxis 1 2CD.par2" [01/12] - 205,70 MB yEnc
		if (preg_match('/^>>> www.lords-of-usenet\.org <<< "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[-_ ]{0,3}\[\d+\/\d+\] - (\[)?\d+[.,]\d+ [kKmMgG][bB](\])? yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //[BFMP3] [Barrelhouse_Time Frames.nzb] [00/18] yEnc
		if (preg_match('/^\[.+?\][-_ ]{0,3}\[([\w.,& ()\[\]\'\`-]{8,}?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}\])[-_ ]{0,3}[\(\[]\d+\/\d+[\)\]][-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Metallica - Ride The Lightning    "01 - Fight Fire With Fire.mp3" yEnc
		if (preg_match('/^([\w.,& ()\[\]\'\`-]{8,}?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/11) "Der Kleine Vampir Komplett - Folge 01 bis 18 www.brothers-of-usenet.org - empfehlen - Newsconnection.eu.nfo" yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?) www\.brothers-of-usenet\.org - empfehlen - Newsconnection\.eu' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //The Refreshments - [1/9] - "The Refreshments - RockÂ´n Roll Christmas [2003].par2" yEnc
		if (preg_match('/([\w.,& ()\[\]\'\`-]{8,}?)[-_ ]{0,3}[\(\[]\d+\/\d+[\)\]][-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //Longines Symphonette-Far Away Places-(LP2 of 2)-Far Away Places-LP 2.par2 yEnc
		if (preg_match('/^([\w.,& ()\[\]\'\`-]{8,}?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Reader's Digest- Mario Lanza-The Very Best of Mario Lanza(CD3 of 3)-CD 3.par2  yEnc
		if (preg_match('/^Reader\'s Digest- {0,1}(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[01/10] - "Acdc - Fly On The Wall - 1985 -01 - Fly On The Wall -Creative7164.mp3"  yEnc
		if (preg_match('/^\[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?)[ -]{0,3}\d+[\w.,& ()\[\]\'\`-]{8,}?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[1/7] ->> "God Forbid 2009 - Reject the Sickness.par2" <<- ShapterV  yEnc
		if (preg_match('/^\[\d+\/\d+\] ->> "([\w.,& ()\[\]\'\`-]{8,}?)' . $this->e0 . ' <<- ShapterV  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Glenn Miller-The Missing Chapters Vol. 6-Blue Champagne--Glenn Miller-The Missing Chapters Vol. 6-Blue Champagne.par2  yEnc
		if (preg_match('/^([\w.,& ()\[\]\'\`-]{8,}?)--[\w.,& ()\[\]\'\`-]{8,}?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function sounds_mp3_dance()
	{
		//[2707]Solarstone-Solarstone_Collected_Vol_1-ARDI3177-WEB-2012-TraX "02-solarstone_feat_kym_marsh-day_by_day_(red_jerry_smackthe_bigot_up_remix).mp3" - yEnc
		if (preg_match('/^\[\d+\](.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function sounds_mp3_electronic()
	{
		//(03/10) "Washing Machines - Planet-E CH-Basel Ultimate Hardcore - 4.9.1993.vol00+01.PAR2" - 232.39 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(2/7) "Cosmic Baby live at Energy 1995.vol00+01.PAR2" - 0 B - yEnc
		if (preg_match('/^\(\d+\/\d+\)[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[- ]{0,3}\d+ [bB][- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[0sk]-[2002-12-06]-[idm]-[vinyl]-[4/5]-[Maps_And_Diagrams_-_Ti_Sta_Bene_Marone-(cact_001)-Vinyl-2002]--cact 001.sfv  yEnc
		if (preg_match('/^\[0sk\]-\[\d+-\d+-\d+\]-\[.+\]-\[.+\]-\[\d+\/\d+\]-\[([\w.,& ()\[\]\'\`-]{8,})\]--.+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[10_years---happy_bday_absme]-[cd]-[04/15]-[va_-_spacewars_01-(plkcd003)-cd-1996-nwd]-01-Nuw Idol - Union Of Ilk -nwd-.mp3  yEnc
		if (preg_match('/^\[.+\]-\[.+\]-\[\d+\/\d+\]-\[([\w.,& ()\[\]\'\`-]{8,})\]-.+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Alec Empire - Low On Ice  06 - Untitled.mp3  yEnc
		if (preg_match('/^([\w.,& ()\[\]\'\`-]{8,}?)[ -]{0,3}\d+[ -]{0,3}[\w.,& ()\[\]\'\`-]{8,}?\b.?([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[ -]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Selected Songs by Various Artists - Collide - Razor Sharp.mp3  yEnc
		if (preg_match('/^Selected Songs by Various Artists - ([\w.,& ()\[\]\'\`-]{8,}?\b.?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[ -]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function teevee()
	{
		//[######]-[FULL]-[#a.b.teevee@EFNet]-[ Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD ] [1/5] - "Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD.nfo" yEnc
		if (preg_match('/^\[#+\]-\[.+?\]-\[.+?\]-\[ (.+?) \][- ]\[\d+\/\d+\][ -]{0,3}("|#34;).+?("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[34148]-[FULL]-[#a.b.teevee@EFNet]-[Batman.The.Animated.Series.S04E01.DVDRiP.XviD-PyRo]-[00/35] "Batman.The.Animated.Series.S04E01.DVDRiP.XviD-PyRo.nzb" yEnc
		if (preg_match('/^\[#+\]-\[.+?\]-\[.+?\]-\[(.+?)\][- ]\[\d+\/\d+\][ -]{0,3}".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[38722]-[#a.b.foreign@EFNet]-[ Game.Of.Thrones.S01E01.Der.Winter.Naht.GERMAN.DL.WS.1080p.HDTV.x264-MiSFiTS ]-[01/37] - "misfits-gameofthrones1080-s01e01-sample-sample.par2" yEnc
		if (preg_match('/^\[#+\]-\[.+?\]-\[ (.+?) \][- ]\[\d+\/\d+\][ -]{0,3}".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[#a.b.teevee] Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD - [01/24] - "Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD.nfo" yEnc
		if (preg_match('/^\[#+\]-\[.+?\]-\[.+?\]-\[ (.+?) \][- ]\[\d+\/\d+\][ -]{0,3}("|#34;).+?("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[17319]-[FULL]-[#a.b.teevee@EFNet]-[ CSI.New.York.S05E22.720p.HDTV.X264-DIMENSION ]-[01/34] "csi.new.york.522.720p-dimension.nfo" (1/1) (1/1
		if (preg_match('/\[#+\]-\[.+?\]-\[.+?\]-\[ ?(.+?) ?\][- ]\[\d+\/\d+\][ -]{0,3}("|#34;).+?("|#34;) \(\d+\/\d+\) \(\d+\/\d+$/', $this->subject, $match)) {
			return $match[1];
		}
		//(01/37) "Entourage S08E08.part01.rar" - 349,20 MB - yEnc
		//(01/24) "EGtnu7OrLNQMO2pDbgpDrBL8SnjZDpab.nfo" - 686 B - 338.74 MB - yEnc (1/1)
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d.+?B - (\d.+?B -)? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/28) - Continuum.S02E13.Second.Time.1080p.WEB-DL.AAC2.0.H264 - "Continuum.S02E13.Second.Time.1080p.WEB-DL.AAC2.0.H264.par2" - 1.75 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\) - ([\w.-]{5,}) - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[01/42] - "King.And.Maxwell.S01E08.1080p.WEB-DL.DD5.1.H264-Abjex.par2" yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([A-Za-z0-9.-]+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(01/15) - "Oz.S01E01.The.Routine.par2" - 380.18 MB yEnc
		//(01/87) "Homeland.S03E05.Mini.720p.WEB.DL.DD5.1.H.264-ITSat.par2" - 773,24 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(????) [10/26] - "The.Young.Riders.S01E02.480pWEB-DL.AAC2.0.H.264-AJP69.part09.rar" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(Dgpc) [01/19] - "The.Xtra.Factor.S10E04.PDTVx264.JIVE.par2" yEnc
		if (preg_match('/^\(Dgpc\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[ Anthony.Bourdain.Parts.Unknown.S01.480p.HDTV.x264-mSD ] MKV.h264 (03/17) - "Anthony.Bourdain.Parts.Unknown.S01E01.480p.HDTV.x264-mSD.mkv" yEnc
		if (preg_match('/^\[ (.+?) \] .+ \(\d+\/\d+\) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[01/31] Unsealed.Alien.Files.S02E02.The.Kecksburg.Incident.720p.HDTV.x264-N0TSC3N3 - "n0tsc3n3-unsealed.alien.files.s02e02.the.kecksburg.incident.720p.hdtv.x264.par2"  yEnc
		if (preg_match('/^\[\d+\/\d+\] (.+S\d+E\d+.+?) - ".+?' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //The Colbert Report [00/12] "The.Colbert.Report.2014.01.20.Scott.Stossel.720p.WEB-DL.AAC2.0.H.264-monkee.nzb" yEnc
		//Colbert [01/10] "The.Colbert.Report.2013.12.19.Ben Stiller.720p.WEB-DL.AAC2.0.H264-zee.rar" yEnc
		//Whale Wars (01/30) "Whale.Wars.S06.A.Commander.Rises.PDTV.XviD-ZEN.par2" yEnc
		if (preg_match('/^[\w ]+[\(\[]\d+\/\d+[\)\]] "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //. - [01/42] - "Under.the.Dome.S01E03.Manhunt.1080p.WEB-DL.DD5.1.H.264-NTb.par2" yEnc
		if (preg_match('/^\. - \[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//House.Hunters.International.S05E502.720p.hdtv.x264 [01/27] - "House.Hunters.International.S05E502.720p.hdtv.x264.nfo" yEnc
		//Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS - [01/33] - "Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS.par2" yEnc
		if (preg_match('/^(Re: )?([\w._-]{8,})([{}A-Z_]+)?( -)? \[\d+(\/| of )\d+\]( -)? ".+?" yEnc$/', $this->subject, $match)) {
			if (strlen($match[2]) !== 32) //don't match hashed names
			{
				return $match[2];
			}
		} //Silent Witness S15E02 Death has no dominion.par2 [01/44] - yEnc
		if (preg_match('/^([a-zA-Z0-9 ]+)(\.part\d*|\.rar)?(\.vol.+? |\.[A-Za-z0-9]{2,4} )\[\d+\/\d+\] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(bf1) [03/31] - "The.Block.AU.Sky.High.S07E61.WS.PDTV.XviD.BF1.part01.sfv" yEnc (1/1)
		if (preg_match('/^\(bf1\) \[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"Moving.On.S05E05.HDTV.x264.part19.rar" - (21/28) - Per REQ  yEnc
		if (preg_match('/^"([\w._-]{8,}?\b)' . $this->e0 . '[- ]{0,3}\(\d+\/\d+\)[-_ ]{0,3}[\w. -]+[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [01/20] - "Boxing.Cinnamon vs El Perro.8th.March.2014.PDTV.x264-Sir.Paul.par2" Cinnamon vs El Perro  yEnc
		if (preg_match('/^\(\?+\)[-_ ]{0,3}\[\d+\/\d+\][-_ ]{0,3}"([\w._-]{8,}?\b)' . $this->e0 . '[-_ ]{0,3}[\w. -]+[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[REPOST]-"True.Blood.S06E01-E10.HDTV.x264.part094.rar" yEnc
		if (preg_match('/^\[[\w. -]+\][-_ ]{0,3}"([\w._-]{8,}?\b)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(Pretty.Little.Liars.S04E11.Bring.Down.the.Hoe.1080p.WEB-DL.DD5.1.H.264-BS) [31/45] - ""Pretty.Little.Liars.S04E11.Bring.Down.the.Hoe.1080p.WEB-DL.DD5.1.H.264-BS.part29.rar"" yEnc yEnc
		if (preg_match('/^\([\w. -]{8,}\)[-_ ]{0,3}\[\d+\/\d+\][-_ ]{0,3}""([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '"[-_ ]{0,3}[\w. -]+[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[]-[FULL]-[a.b.teevee]-[Hannibal.S01.Special.Ceuf.720p.WEB-DL.DD5.1.H-264-NTb]-[03/40] - "Hannibal.S01.Special.Ceuf.720p.WEB-DL.DD5.1.H-264-NTb.part01.rar"  yEnc
		if (preg_match('/^[\w(). -]+[-_ ]{0,3}\[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[Ax.Men.S06E18.Risking.it.All.720p.WEB-DL.AAC.2.0.H.264-AKS74u]-[07/68] "Ax.Men.S06E18.Risking.it.All.720p.WEB-DL.AAC.2.0.H.264-AKS74u.r04" - 1.46 GB yEnc
		if (preg_match('/^\[[\w. -]{8,}\][-_ ]{0,3}\[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //[ As requested ] Pawn.Stars.S07E15.Colt.To.The.Touch.720p.WEB-DL.AAC2.0.H.264.par2  yEnc
		if (preg_match('/^\[ As requested \][-_ ]{0,3}([\w. -]+)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Alaska.The.Last.Frontier.S02E15.HDTV.XviD-AFG.nzb  yEnc
		if (preg_match('/^([\w.-]+)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //09/13 The.Exes.S03E10.My.Ex-Boyfriends.Wedding.WEBRip.x264.AAC-NoGRP.vol007+05.PAR2  yEnc
		if (preg_match('/^\d+\/\d+[-_ ]{0,3}([\w. -]+)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[14/16] - Game.of.Thrones.S03E04.720p to 640x480 for size.vol11+08.PAR2  yEnc
		//[22/39] LEGO.Batman.The.Movie.2013.1080p.WEB-DL.DD5.1.H.264-YFN.part21.rar  yEnc
		if (preg_match('/^\[\d+\/\d+\][-_ ]{0,3}([\w. -]+)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(tv) ncis.1021.hdtv-lol [1/8] - "ncis.1021.hdtv-lol.sfv" yEnc
		if (preg_match('/^\[\]-\[FULL\]-\[a\.b\.teevee\]-\[[\w.-]+\][-_ ]{0,3}\[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(05/29) - LoK214 - "The.Legend.of.Korra.S02E14.Light.in.the.Dark.1080p.WEB-DL.DD5.1.H.264-BS.part04.rar" - 1,01 GB - yEnc
		//(34/45) -Continuum.S02E11.1080p.WEB-DL.AAC2.0.H264- "Continuum.S02E11.1080p.WEB-DL.AAC2.0.H264.part33.rar" - 1.76 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[-_ ]{0,3}[\w. -]+[-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //[ TVPower ] - "Dexter.S07E10.720p.HDTV.x264-NLsubs.par2" yEnc
		//[ TVPower ] - [010/101] - "Desperate.Housewives.S08Disc2.NLsubs.part009.rar" yEnc
		if (preg_match('/^\[ [A-Za-z]+ \] - (\[\d+\/\d+\] - )?"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //[www.allyourbasearebelongtous.pw]-[WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams]-[03/69] "WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams.r00" - 1.58 GB - yEnc
		if (preg_match('/^\[.+?\]-\[(.+?)\]-\[\d+\/\d+\] ".+?" - \d+([.,]\d+ [kKmMgG])?[bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(www.Thunder-News.org) >CD1< <Sponsored by Secretusenet> - "moovee-fastest.cda.par2" yEnc
		if (preg_match('/^\(www\..+?\) .+? <Sponsored.+?> - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<<<Pitbull>>> usenet-space-cowboys.info <<<Powered by https://secretusenet.com>< "S05E03 Pack die Badehose ein_usenet-space-cowbys.info.par2" >< 01/10 (411,16 MB) >< 3,48 kB > yEnc
		if (preg_match('/\.info .+?Powered by .+?\.com "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' .+? \d+\/\d+ \(\d+[,.]\d+ [mMkKgG][bB]\) .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Newport Harbor The Real Orange County - S01E01 - A Black & White Affair [01/11] - "Newport Harbor The Real Orange County - S01E01 - A Black & White Affair.mkv" yEnc
		if (preg_match('/^([a-zA-Z0-9]+ .+? - S\d+E\d+ - .+?) \[\d+\/\d+\] - ".+?\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(www.Thunder-News.org) >Robin.Hood.S02E04.Der.Todesengel.German.WS.DVDRip.XviD-GTVG< <Sponsored by Secretusenet> -  "gtvg-rh.xvid.s02e04.jpg" yEnc
		if (preg_match('/^\(www\.Thunder-News\.org\) ?>(.+)< ?<Sponsored.+>[ _-]{0,3}(\(\d+\/\d+\)|\[\d+\/\d+\])?[ _-]{0,5}("|#34;).+("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //.: Breaking.Bad.S04E10.Prost.German.WS.DVDRip.XviD-GTVG :. - .:www.thunder-news.org:. - .:sponsored by secretusenet.com:. - "gtvg-bb.xvid.s04e10_poster.jpg" yEnc
		if (preg_match('/^\.: (.+):. - .:www\.thunder-news\.org:. - .:sponsored by secretusenet\.com:\. - ("|#34;).+("|#34;).+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[FULL]-[#a.b.teevee@EFNet] - Celemony Melodyne Studio Edition 3.2 (MAC OSX).rar
		if (preg_match('/\[FULL\]-\[.+.EFNet\] - (.+)\.rar$/', $this->subject, $match)) {
			return $match[1];
		} //(00912/17663) "Afghanistan The Great Game With Rory Stewart Part1 HDTV XviD-AFG.rar" - 344,98 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\) ("|#34;)(.+?)\.rar("|#34;) - .+ - yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //[84491]-[FULL]-[#a.b.teevee@EFNet]-[ Tennis.Australian.Open.2012.Mens.1st.Round.Lleyton.Hewitt.vs.Cedrik-Marcel.Stebe.720p.HDTV.x264-LMAO ]-[04/97] - "tennis.australian.open.2012.mens.1st.round.lleyton.hewitt.vs.cedrik-marcel.stebe.720p.hdtv.x264-lmao.r01
		if (preg_match('/^\[[\d#]+\]-\[.+?\]-\[.+?\]-\[ (.+?) \][- ]\[\d+\/\d+\][ -]{0,3}("|#34;).+?/', $this->subject, $match)) {
			return $match[1];
		} //(((Nimue)))(((Hawaii.Five.0.S02E12.Der.Mann.im.Bunker.GERMAN.DUBBED.HDTVRiP.XviD-SOF))) usenet-space-cowboys.info (((Powered by https://secretusenet.com)( #34;hawaii.five.0.s02e12.avi#34; )( 02/11 (762,35 MB) )( 349,83 MB ) yEnc
		if (preg_match('/^\(\(\((Nimue|CowboyUp26-1208)\)\)\)\(\(\((.+)\)\)\).+yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //__www.realmom.info__ - 56 Downloader (XMLBar) - DOWNLOAD EVERY VIDEO!.rar
		if (preg_match('/^__www\.realmom\.info__ - (.+)\.(rar$|rar yEnc$)/', $this->subject, $match)) {
			return $match[1];
		} //(My.Name.Is.Earl.S03.DVDRip.XviD-ORPHEUS-NODLABS.PARS)[000/197] - "My.Name.Is.Earl.S03.DVDRip.XviD-ORPHEUS-NODLABS.PARS.nzb" yEnc
		if (preg_match('/^\((.+)\)\[\d+\/\d+\] - ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Curb.Your.Enthusiasm.S08.DVDRiP.XviD.COMPLETE.REPACK-CLuE - "sample-curb.your.enthusiasm.s08.dvdrip.xvid.complete.repack-clue.avi.vol1+2.PAR2"  770.0 KBytes yEnc
		if (preg_match('/^([a-zA-Z].+) - ("|#34;).+("|#34;)  \d+[,.]\d+ [mMkKgG][bB]ytes yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<Thor>>><<<Chuck S04E20 Chuck gegen die Familie Volkoff German Dubbed DL 720p BluRay x264-idTV>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "idtv-chuck_s04e20_720p-sample.mkv" >< 03/61 (2,39 GB) >< 21,89 MB > yEnc
		if (preg_match('/^<<<(Thor(\d+)?)>>><<<(.+)>>>usenet-space-cowboys.+<<<Powered.+>< ("|#34;).+("|#34;).+> yEnc$/i', $this->subject, $match)) {
			return $match[3];
		} //(Public) (FULL) (a.b.teevee@EFNet) [04/13] (????) [00/27] - "DC.Guy.in.a.Ceelo.Green.Hood..S01E07.720p.HDTV.X264-DIMENSION.nzb" yEnc
		if (preg_match('/\(Public\).*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //>ghost-of-usenet.org<< Suburgatory.S01E13.Sex.und.die.Vorstadt.GERMAN.DUBBED.DL.720p.WebHD.x264-TVP >>www.SSL-News.info> -  "tvp-suburgatory-s01e13-720p.nfo" yEnc
		if (preg_match('/^>ghost-of-usenet\.org<< ?(.+) ?>>www.+>[ _-]{0,3}("|#34;)?.+("|#34;)? ?yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //Touch.S02E09.PROPER.1080p.WEB-DL.DD5.1.H.264-LFF NoAds [01/30] - "Touch.S02E09.PROPER.1080p.WEB-DL.DD5.1.H.264-LFF.nfo" yEnc
		if (preg_match('/^(.+?) NoAds \[\d+\/\d+\][ -]{0,3}("|#34;).+?("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<Thor1602>>><<<NCIS.Los.Angeles.S03E02.Die.Cyberattacke.GERMAN.DUBBED.HDTVRiP.XviD-SOF>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "sof-ncis.los.angeles.s03e02.die.cyberattacke.(2010).hdtvdubbed.nfo" >< 02/28 (419,00 MB) >< 4,18
		if (preg_match('/^<<<(Thor(\d+)?)>>><<<(.+)>>>.+<<<.+>< ("|#34;).+("|#34;).+/', $this->subject, $match)) {
			return $match[1];
		} //AS REQ: "Game.of.Thrones.Season.3.480p.HDTV.H264.part52.rar" yEnc
		if (preg_match('/^AS REQ: "(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//<<<Nimue>>><<<Die.Geschichte.der.Fliegerei.E05.Von.der.Luffahrt.zur.Raumfahrt.GERMAN.DOKU.FS.DVDRip.XviD-NGE>>> usenet-space-cowboys.info <<<Powered by https://secretusenet.com>< "nge-dgdf-e05-xvid.r04" >< 07/39 (1,51 GB) >< 47,68 MB > yEnc
		//<<<CowboyUp26-0706>>><<<Spartacus.S02E09.Monster.GERMAN.DUBBED.720p.HDTV.x264-ZZGtv>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "zzgtv-spartacus-s02e09.r00" >< 04/43 (1,72 GB) >< 47,68 MB > yEnc
		//<<<Nimue>>><<<Die.Geschichte.der.Fliegerei.E05.Von.der.Luffahrt.zur.Raumfahrt.GERMAN.DOKU.FS.DVDRip.XviD-NGE>>> usenet-space-cowboys.info <<<Powered by https://secretusenet.com>< "nge-dgdf-e05-xvid.r04" >< 07/39 (1,51 GB) >< 47,68 MB > yEnc
		//<<<Hustensaft1402>>><<<CSI.Las.Vegas.S10E21.Schutz.und.Racheengel.German.DL.1080p.BluRay.x264-ETM>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "etm-csi_las_vegas_s10e21-1080p.r12.vol000+01.par2" >< 39/49 (3,86 GB) >< 3,32 MB > yEnc
		if (preg_match('/^<<<(Hustensaft\d+|Nimue|CowboyUp\d+(-\d+)?)>>><<<(.+)>>>.+<<<Powered by.+yEnc$/i', $this->subject, $match)) {
			return $match[3];
		} //[ Ugly.Betty.S02E13.DVDRip.XviD-SAiNTS ] - [01/39] - "Ugly.Betty.S02E13.DVDRip.XviD-SAiNTS.par2" yEnc
		if (preg_match('/^\[ ([a-zA-Z].+) \] - \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Wainy Days.S02E07.DVDRip.x264-PiNK) [01/10] - "Wainy Days.S02E07.DVDRip.x264-PiNK.par2" yEnc
		if (preg_match('/^\(([\w.,& ()\[\]\'\`-]{8,}?)\) \[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //[Mercy.S01.720p.WEB-DL.DD5.1.h.264-LP-MMI]-[ Mercy.S01E18.Of Course I'm Not.720p.WEB-DL.DD5.1.H.264-LP ]-[01/24] - "Mercy.S01E18.Of Course I'm Not.720p.WEB-DL.DD5.1.H.264-LP.par2" yEnc
		if (preg_match('/^\[.+\][ -]{0,3}\[ (.+) \][ -]{0,3}\[\d+\/\d+\][ -]{0,3}("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //REPOST: [ Th3.V@mpir3.Di@ri35.S05E02.HDTV.X264-DIMENSION ] - [31/33] - "Th3.V@mpir3.Di@ri35.S05E02.HDTV.X264-DIMENSION.vol050+051.par2" yEnc
		if (preg_match('/^(REPOST: )?\[ (.+) \] - \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //[#a.b.teevee] Covert.Affairs.S04E11.720p.WEB-DL.DD5.1.H.264-XEON - [11/40] - "Covert.Affairs.S04E11.720p.WEB-DL.DD5.1.H.264-XEON.part09.rar" yEnc
		if (preg_match('/^\[#a\.b\.teevee\] (.+) - \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Breaking Bad S6E03 Confessions enjoy! [7 of 46] "03 Confessions (1080p HD).mov.007" yEnc
		if (preg_match('/^(.+) Confessions.+\[\d+ of \d+\] ".+\(((1080|720).+[HS]D)\).+" yEnc$/', $this->subject, $match)) {
			return $match[1] . "-" . $match[2];
		} //[51/62] Morrissey.25.Live.Concert.2013.BDRip.x264-N0TSC3N3 - "n0tsc3n3-morrissey.25.live.2013.bdrip.x264.rar" yEnc
		if (preg_match('/^\[\d+\/\d+\] (.+) - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/19) - Description - "How I Met Your Mother S05E24 - Doppelgangers.part01.rar" - 175,05 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\)[ -]+Description[ -]+"(.+)\.part\d+\.rar"[ -]+\d+[.,]\d+ [kKmMgG][bB][ -]+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(23/23) -The.Mindy.Project.S02E09.720p.WEB-DL.DD5.1.H.264 - "The.Mindy.Project.S02E09.720p.WEB-DL.DD5.1.H.264.vol31+29.PAR2" - 768.86 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) -(.+) - ".+" - \d+[.,]\d+ [MGK]B - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //The Mindy Project s02e06 720p WEB-DL - [02/24] - "The Mindy Project S02E06 720p WEB-DL.part01.rar" yEnc
		if (preg_match('/^(.+[Ss]\d+.+) - \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ Breaking.Bad.S05E16.BDRip.x264-DEMAND ] [30/30] - "breaking.bad.s05e16.bdrip.x264-demand.vol42+37.PAR2" yEnc
		if (preg_match('/^\[ ?(.+(S\d+|READ\.NFO).+) ?\] \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[32/44] - "Scandal.S03E07.Everything's.Coming.Up.Mellie.1080p.WEB-DL.DD5.1.H.264-ECI.part31.rar" yEnc
		if (preg_match('/^\[\d+\/\d+\] - "(.+S\d+.+)\.par.+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<UHQ>< Welcome.to.the.Jungle.German.2003.DVD9.AC3.DL.1080p.BluRay.x264-MOViESTARS >- [93/95] - "moviestars-wttj.1080p.vol15+16.par2" yEnc
		if (preg_match('/^<UHQ>< (.+) >- \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Sam & Cat S01E06 BabysitterWar [1 of 9] "Sam & Cat - S01E06 - BabysitterWar-ORIG.mp4" yEnc
		if (preg_match('/[Ss]\d+[Ee]\d+.+\[\d+ of \d+\] "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Sam & Cat - S01E10 - BabysittingCommercial - "Sam & Cat - S01E10 - BabysittingCommercial.m4v" yEnc
		if (preg_match('/[Ss]\d+[Ee]\d+.+- "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[12/22] - "stargate_sg-1.6x12.unnatural_selection.ws_dvdrip_xvid-fov.avi"  yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //For teevee - "Silent.Witness.S17E05.480p.HDTV.x264-mSD.mkv.sfv" [01/23] yEnc
		if (preg_match('/^For teevee - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' \[\d+\/\d+\][ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Mad.Men.S06E11.HDTV.x264-2HD.par2" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"Marvels.Agents.of.S.H.I.E.L.D.S01E07.HDTV.XviD-FUM.avi.nfo" [09/16] yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\[\d+\/(\d+\])[ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Public Enemies (2009).720p.x264.English Subtitles.Dolby Digital 5.1.mkv [04/55]"Public Enemies sample.mkv" yEnc
		if (preg_match('/^(.+(1080|720).+)\..+\[\d+\/\d+\]".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //-=->EnJoY!<-=- (Day1/5) [01/17] - "The Machine that Changed the World S01E01 - Giant Brains - 1992 (480p,x264).nfo" yEnc
		//-=>EnJoY!<-=->Req:The Animated Alias:Tribunal (2004)<=- [01/17] - "Da Vinci's Demons - S01E07 - The Hierophant (480p,x264).nfo" yEnc
		if (preg_match('/^.+EnJoY\!.+\[\d+\/\d+\][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //The.Colbert.Report.2013.07.29.The.Lumineers.WEBRip.AAC2.0.H.264-DCK [01/11] - "The.Colbert.Report.2013.07.29.The.Lumineers.WEBRip.AAC2.0.H.264-DCK.mp4"  yEnc
		if (preg_match('/^[\w. -]+\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //the.lockerbie.bombing.720p.HDTV.x264-fatboy  - "the.lockerbie.bombing.720p.HDTV.x264-fatboy.nfo" - 829.12 MB - yEnc
		if (preg_match('/^[\w. -]+[-_ ]{0,4}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //The.Vampire.Diaries.S05E13.HDTV.X264"The.Vampire.Diaries.S05E13.HDTV.X264.vol40+36.PAR2" yEnc
		if (preg_match('/^[\w. -]+[-_ ]{0,4}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[Doomsday.Preppers.S03E11.720p.HDTV.x264-TTVa] - NOT ORIGINAL RARS, REPACKED. Sorry. - (18/35) "Doomsday.Preppers.S03E11.720p.HDTV.x264-TTVa.r16" - 1.31 GB - yEnc
		if (preg_match('/^\[[\w.-]+\][-_ ]{0,3}.+[-_ ]{0,3}\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //[CONAN] Conan.2013.12.12.Adam.Levine.WEBRiP.x264-CoCo [Missed Conan Episodes] - [22/32] - "conan.2013.12.12.adam.levine.webrip.x264-coco.r18" yEnc
		if (preg_match('/^\[[\w. -]+\][-_ ]{0,3}.+[-_ ]{0,3}[\[\(]\d+\/\d+[\]\)][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Dave Gorman Modern Life is Goodish Series 1 - [114 of 126] - "Dave Gorman Modern Life is Goodish S01E06.mkv.008" yEnc
		if (preg_match('/^[\w -]+[-_ ]{0,3}[\[\(]\d+ of \d+[\]\)][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(1/9) "Tronens_Spil_S03E08.par2" yEnc
		if (preg_match('/^[\[\(]\d+\/\d+[\]\)][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //07/14  "Sister.Wives.S06E06.STV.XviD-kralcx.vol000+01.PAR2" yEnc
		if (preg_match('/^\d+\/\d+[- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //07/14  "Sister.Wives.S06E06.STV.XviD-kralcx.vol000+01.PAR2" yEnc
		if (preg_match('/^\d+\/\d+[- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[ 749,75 MB ]-[ 01/20 ]-"choir.of.young.believers-this.is.for.the.white.in.your.eyes.live.2009.pdtv.x264.nfo" yEnc
		if (preg_match('/^\[ \d+[.,]\d+ [kKmMgG][bB] \][-_ ]{0,3}\[ \d+\/\d+ \][-_ ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<Enjoy> Hannibal.S01E04.mp4  yEnc
		if (preg_match('/^<Enjoy> ([\w. ()-]{8,}?\b)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //A5F - [ NOVA.S40E19.Manhunt.Boston.Bombers.HDTV.x264-A5F ] - [01/43] - "NOVA.S40E19.Manhunt.Boston.Bombers.HDTV.x264-A5F.sfv" (not.teevee) yEnc
		if (preg_match('/^A5F[- ]{0,3}\[ ([\w. ()-]{8,}?\b) \][- ]{0,3}\[\d+\/\d+\][- ]{0,3}".+?' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Arrested.Development.S04E08.x264(04/164) "Arrested.Development.S04E08.WEBRiP.x264.part03.rar" - 197.21 MB - yEnc
		if (preg_match('/^[\w. ()-]{8,}?\b\(\d+\/\d+\)[- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //Divers (12/42) -"Juste.Pour.Rire.2013.Gala.JF.Mercier.FRENCH.720p.HDTV.x264-QuebecRules.part11.rar" yEnc
		//Par le chapeau (06/43) - "8C7D59F472E03.part04.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9 ]+) \(\d+\/\d+\) - ?".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[140022]-[04] - [01/40] - "140022-04.nfo" yEnc
		if (preg_match('/\[\d+\]-\[.+\] - \[\d+\/\d+\] - "\d+-.+" yEnc/', $this->subject)) {
			return array("cleansubject" => $this->subject, "properlynamed" => false, "ignore" => true);
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function test()
	{
		//brothers-of-usenet.net)(Sons.of.Anarchy.S03E03.Fuersorge.GERMAN.DUBBED.720p.BLURAY.x264-ZZGtv))(07/31) #34;zzgtv-soa-s03e03.part05.rar#34; - 2,02 GB - yEnc
		if (preg_match('/^brothers-of-usenet.net\)\((.+)\)\)\(\d+\/\d+\) ("|#34;).+("|#34;).+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //brothers-of-usenet.net(Sons.of.Anarchy.S03E03.Fuersorge.GERMAN.DUBBED.720p.BLURAY.x264-ZZGtv))(07/31) #34;zzgtv-soa-s03e03.part05.rar#34; - 2,02 GB - yEnc
		elseif (preg_match('/^brothers-of-usenet.net\((.+)\)\)\(\d+\/\d+\) ("|#34;).+("|#34;).+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //brothers-of-usenet.net><Sons.of.Anarchy.S03E02.Letzte.Oelung.GERMAN.DUBBED.720p.BLURAY.x264-ZZGtv>>(01/31) "zzgtv-soa-s03e02.nfo" - 1,98 GB - yEnc
		elseif (preg_match('/^brothers-of-usenet.net><(.+)>>\(\d+\/\d+\) ("|#34;).+("|#34;).+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function town()
	{
		//[01/29] - "Bellflower.2011.German.AC3.BDRip.XviD-EPHEMERiD.par2" - 1,01 GB yEnc
		//(3/9) - "Microsoft Frontpage 2003 - 4 Town-Up from Kraenk.rar.par2" - 181,98 MB - yEnc
		if (preg_match('/^[\[(]\d+\/\d+[\])] - "([A-Z0-9].{2,}?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB]( -)? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Armored_Core_V_PS3-ANTiDOTE__www.realmom.info__.r00" (03/78) 3,32 GB yEnc
		if (preg_match('/^"(.+)__www.realmom.info__.+" \(\d+\/(\d+\)) \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function town_cine()
	{
		//[01/29] - "Bellflower.2011.German.AC3.BDRip.XviD-EPHEMERiD.par2" - 1,01 GB yEnc
		//(3/9) - "Microsoft Frontpage 2003 - 4 Town-Up from Kraenk.rar.par2" - 181,98 MB - yEnc
		if (preg_match('/^[\[(]\d+\/\d+[\])] - "([A-Z0-9].{2,}?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB]( -)? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function town_xxx()
	{
		//[01/29] - "Bellflower.2011.German.AC3.BDRip.XviD-EPHEMERiD.par2" - 1,01 GB yEnc
		//(3/9) - "Microsoft Frontpage 2003 - 4 Town-Up from Kraenk.rar.par2" - 181,98 MB - yEnc
		if (preg_match('/^[\[(]\d+\/\d+[\])] - "([A-Z0-9].{2,}?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB]( -)? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function tun()
	{
		/*
		// Leaving here to show that you can get the names from these.
		// Useless since all these are passworded and if there is a backlog in post proc, sickbeard will pull these and they will fail since they are passworded.
		//[ nEwZ[NZB].iNFO - [ QmlnTW91dGhmdWxzLjEzLjA3LjA4LkNocmlzc3kuR3JlZW5lLlhYWC43MjBwLk1QNC1LVFI= ] - File [06/48]: "b582519da4d849df003559fc4ae45219.nfo" yEnc
		if (preg_match('/^\[ nEwZ\[NZB\]\.iNFO - \[ ([a-z0-9A-Z]{3,}=) \] - File \[\d+\/\d+\]: ".+?" yEnc$/', $this->subject, $match))
		return base64_decode($match[1]);
		//[PRiVATE] VHdpc3R5cy5jb21fMTMuMDguMDkuQWlkZW4uQXNobGV5LkVsbGUuQWxleGFuZHJhLldoYXQuWW91ci5GdXR1cmUuSG9sZHMuWFhYLklNQUdFU0VULUZ1R0xp [06/10] - "89857cebff1efd7927ebddf30281b0e4.part2.rar" yEnc
		if (preg_match('/^\[PRiVATE\] ([a-z0-9A-Z]{4,}=*) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match))
		return base64_decode($match[1]);
		else
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
		*/
		return $this->generic();
	}

	public function tv()
	{
		//#a.b.mm@efnet - req 86820 - World.Series.of.Poker.2009.E05.Celebrity.No-Limit.Holdem.Part.1.of.2.HDTV.XviD-FQM - [26/28] - "world.series.of.poker.2009.e05.hdtv.xvid-fqm.vol07+08.par2" yEnc
		//#a.b.mm@efnet - req 60243 - Dawn.Porter.Mail.Order.Bride.PROPER.WS.PDTV.XviD-NOsegmenT - [01/28] - dawn.porter.mail.order.bride.proper.ws.pdtv.xvid-nosegment.nfo yEnc
		//a.b.mm@EFNet - Req 58360 - Seinfeld.7x02.The_Postponement.DVDRip_XviD-FoV - [01/21] "seinfeld.7x02.the_postponement.dvdrip_xvid-fov.nfo" yEnc
		//#a.b.mm@efnet - req xxxxx - Spin.City.S02E04.PROPER.DVDRiP.XViD-NODLABS - [07/28] - "spin.city.s02e04.proper.dvdrip.xvid.subs-nodlabs.vol0+1.par2" yEnc
		//#a.b.mm@EFNeT - REQ 58160 - How.I.Met.Your.Mother.S03E19.DVDRip.XviD-ORPHEUS - 00/26 - how.i.met.your.mother.s03e19.dvdrip.xvid-orpheus.nzb yEnc
		//#a.b.mm@EFNeT - REQ 81686 - Burn.Notice.S02E01.720p.HDTV.X264-2HD- 00/42 - Burn.Notice.S02E01.720p.HDTV.X264-2HD.nzb yEnc
		if (preg_match('/^#?.+req (\d+|x+)[ -]{1,}(.+)\s*- ?\[?\d+\/\d+\]?[ -]{1,3}"?.+"? yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //www.Bin-Req.net Presents: #57629 - Man.vs.Wild.S05E03.Bears.Essentials.HDTV.XviD-GNARLY - [01/37] - man.vs.wild.s05e03.bears.essentials.hdtv.xvid-gnarly.sample.avi (1/27) yEnc
		if (preg_match('/^www\.Bin-Req\.net Presents: #\d+[ -]{1,3}(.+) - \[\d+\/\d+\].+\(\d+\/\d+\) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(The.Legend.Of.Korra.S01E08.When.Extremes.Meet.720p.HDTV.h264) (00/41) - "The.Legend.Of.Korra.S01E08.When.Extremes.Meet.720p.HDTV.h264.nzb" yEnc
		if (preg_match('/^\(([a-zA-Z].+)\) \(\d+\/\d+\) - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //NCIS.S11E08.HDTV.x264-LOL - "NCIS.S11E08.HDTV.x264-LOL.part.par2" yEnc
		if (preg_match('/^([A-Za-z0-9][a-zA-Z0-9.-]{6,})\s+- ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //>ghost-of-usenet.org<24.S07E01.German.SATRip.XviD-ITG>Sponsored by Astinews< (02/27) "itg-24-s07e01.nfo" yEnc
		if (preg_match('/^>ghost-of-usenet\.org< ?(.+)>Sponsored.+< ?\(\d+\/\d+\)[ _-]{0,3}("|#34;)?.+("|#34;)?[ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //World's.Strongest.Man.2013.Episode1.Webrip [01/46] - "2013x01.nfo" yEnc
		if (preg_match('/^([\w.\' -]{8,}) [\[\(]\d+\/\d+[\]\)] - ".+?' . $this->e0 . '([- ]{0,3}[\w.-]+)?[ _-]{0,3}yEncs$/', $this->subject, $match)) {
			return $match[1];
		} //Handyman Shows-TOH-S32E10 - File 01 of 32 - yEnc
		if (preg_match('/^Handyman Shows-(.+) - File \d+ of \d+ - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} ///^Handyman Shows.+"(.+)\.(par2|nfo|avi|nzb)" yEnc$/
		if (preg_match('/^Handyman Shows.+"(.+)\.(par2|nfo|avi|nzb)" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Kung Fu S02E15 A Dream WIthin a Dream.par2  yEnc
		//Kids In The Hall S01E15.par2  yEnc
		if (preg_match('/^((Kung Fu|Kids In The Hall) S\d+\E\d+.+)\.(par2|avi)\s+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Project.Runway.Canada.S02E05.HDTV.DivX-JWo - 00/33 - project.runway.canada.205.nzb yEnc
		//Yu-Gi-Oh.S03.DVDRip.AAC2.0.x264-DarkDream - [000/306] - "Yu-Gi-Oh.S03.DVDRip.AAC2.0.x264-DarkDream" yEnc
		if (preg_match('/^([a-zA-Z][\w\d\.-]+) - \[?\d+\/\d+\]? - "?.+\.?(par2|nzb|nfo|avi)?"? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Borgen.2x02.A.Bruxelles.Non.Ti.Sentono.Urlare.ITA.BDMux.x264-NovaRip [02/22] - "borgen.2x02.ita.bdmux.x264-novarip.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9.-]+) \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(bf1) [03/31] - "The.Block.AU.Sky.High.S07E56.WS.PDTV.XviD.BF1.part01.sfv" yEnc
		//[REPOST] - [01/33] - "Breaking.Bad.S05E16.Felina.1080p.WEB-DL.DD5.1.H.264-BS.par2" yEnc
		if (preg_match('/^[\[\(]\w+[\]\)][- ]{0,3}\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(????) [01/11] - "The Carrie Diaries - S02E01 - HDTV x264-LOL.sfvCarrieDiariesS02E01" yEnc
		if (preg_match('/^\(\?+\)[- ]{0,3}[\(\[]\d+\/\d+[\]\)][- ]{0,3}"(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})Carrie.+"[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(noname 0307) NCIS.S11E14.HDTV.x264-LOL[rarbg].vol047+40.PAR2  yEnc
		if (preg_match('/^\([\w. ]+\)[- ]{0,3}([\w+. -]{8,})(\[\w+\])?([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[014/144] - The Bible (2013) - S1E01 - In the Beginning AVC 480p.vol31+27.PAR2  yEnc
		if (preg_match('/^\[\d+\/\d+\][- ]{0,3}([\w.() -]{8,}?\b)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Saturday Morning Classic Return-to-the-Planet-of-the-Apes-S01E03 - Lagoon of Peril.avi.001  yEnc
		if (preg_match('/^Saturday Morning Classic ([\w.()-]{8,}?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"NCIS S11E21" yEnc - [01/21] - "NCIS.S11E21.hdtv-lol.par2" yEnc
		if (preg_match('/^"[\w ]+" yEnc[- ]{0,3}\[\d+\/\d+\][- ]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[ Best.Friends.Forever.S01E06.720p.WEB-DL.DD5.1.H.264-HWD ]-[01/25] - "Best.Friends.Forever.S01E06.720p.WEB-DL.DD5.1.H.264-HWD.nfo" yEnc
		if (preg_match('/^(\[\d+\]-)?\[ ?([a-zA-Z0-9.-]{6,}) ?\](-\[REAL\])? ?- ?\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //"Arrow S02E07" yEnc
		if (preg_match('/^"([\w. -]{8,})"[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function tvseries()
	{
		//Mr.Sunshine.1x10.Ben.E.Vivian.ITA.DVDMux.XviD-NovaRip [01/14] - "mr.sunshine.1x10.ita.dvdmux.xvid-novarip.nfo" yEnc
		if (preg_match('/^(.+\d+x\d+.+)\s*\[\d+\/\d+\][ -]*".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Ancient.Aliens.S06E03.The.Anunnaki.Connection.HDTV.x264-SOL - [13/28] - "Ancient.Aliens.S06E03.The.Anunnaki.Connection.HDTV.x264-SOL.r10" yEnc
		if (preg_match('/^(.+S\d+E\d+.+)[ -]+\[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Moonlight Post Voor Dutch Release Crew [077/110] - "HLVRSM87654_S2D4.part76.rar" Wij Zoeken Nog Stafleden Meld Je Bij De Staf yEnc yEnc
		if (preg_match('/^([\w\s]+)\[\d+\/\d+\] - ".+" [\w\s]+ yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[QWERTY] "The.Real.Housewives.of.Atlanta.S06E02.HDTV.x264-CRiMSON.mp4" yEnc
		//[QWERTY] "Air.Crash.Investigation.S10E06.HDTV.x264-TViLLAGE.mp4" - 6.76 GB - yEnc
		if (preg_match('/\[QWERTY\] "(.+)\.(mp4|mkv|ts)"( - \d+[.,]\d+ GB)?[ -]+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(01/32) - "Adam.Devines.House.Party.S01E04.HDTV.x264-YesTV.mp4" - 9.98 GB - yEnc
		//(01/24) "BBC Dr Who An Unearthly Child S01E04 The Firemaker 1963.nfo" - 3.86 kB - 329.37 MB - yEnc
		if (preg_match('/\(\d+\/\d+\)[ -]*"(BBC )?(.+)\.(mp4|mkv|ts|nfo|par|par2)".+[GKM]B - yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //Touched.By.An.Angel.S09 (001/179) - Description - "Season 9.par2" - 8.10 GB - yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\.]+) \(\d+\/\d+\) - Description - "(.+)".+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/31) - Balika.Vadhu.s01e1379.2013.09.07.720p.web-dl.x264 - "Balika.Vadhu.s01e1379.2013.09.07.720p.web-dl.x264.nfo" - 340.72 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) - (.+) - "(.+)".+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Israeli AutoRarPar0002  [54/55] - "Hameofefim.Hanoazim.E02.PDTV.XviD-Sweet-Star.vol063+64.par2" yEnc
		if (preg_match('/\Israeli.+\[\d+\/\d+\] - "(.+)\.(mp4|mkv|ts|vol.+|)" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Israeli AutoRarPar0021  [07/44] - "Hameofefim.Hanoazim.E23.PDTV.XviD-Sweet-Star.part06.rar" yEnc
		if (preg_match('/\Israeli.+\[\d+\/\d+\] - "(.+)\.(mp4|mkv|ts|par.+)" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //AutoRarPar0005  [44/45] - "Prisoners of War (Hatufim) S01 E05 - Xvid - Hardcoded Subs - Sno.vol063+64.par2" yEnc
		if (preg_match('/AutoRarPar\d+\s+\[\d+\/\d+\] - "(.+S\d+\s*E\d+.+)\.[vol|part].+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ Skins UK - S02 - Season 2 DVDRip ] AVI.XviD (06/20) - "Skins.UK.S02E04.DVDRip.XviD-Affinity.rar" yEnc
		if (preg_match('/\[ .+S\d+.+ \] \w+\.\w+ \(\d+\/\d+\) - "(.+)\.(mp4|mkv|ts|rar)" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ gtvg-mm.xvid.s03 [ PWP ] - [ TV ] - [ DBS 0883 ] - [ "gtvg-mm.xvid.s03e11.r09" ] 7,32 GB yEnc
		if (preg_match('/^\[ .+[sS]\d+ \[ \w+ \] - \[ \w+ \] - \[ \w+ \d+ \] - \[ "(.+[sS]\d+[eE]\d+).+".+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//"Forbrydelsen.II.S01E03.2009.DVDRip.MULTi.DD5.1.x264.nzb" - 213.54 kB - yEnc
		//"Futurama S07E01 The Bots And The Bees.vol26+23.PAR2" - 8.49 MB - 193.51 MB - yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '( - \d+([.,]\d+ [kKmMgG])?[bB])? - \d+([.,]\d+ [kKmMgG])?[bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Rijdende.Rechter.-.19x01.-.Huisbiggen.1080p.MKV-BNABOYZ.part38.rar" - [40/56] - yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \[\d+\/(\d+\]) - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(003/104) "blackcave1001.part002.rar" - 4,83 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Chuck - Season2 (015/164) - "2x04.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\.]+) \(\d+(\/\d+\)) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //X-Men Evolution - 2000 -  [01/20] - "X-Men Evolution - 3x03 - Mainstream.par2" yEnc
		if (preg_match('/^[a-zA-Z0-9 -_\.]+ \[\d+\/(\d+\]) - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		}
		//'X-Files' Season 1 XviD RETRY  "Files101.par2" 004/387
		//'X-Files' Season 5 XviD "Files502.par2" 018/321 yEnc
		//'X-Files' Season 2 XviD  "Files223.part2.rar" 356/401  yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\.]+) (RETRY)?[-_ ]{0,3}".+?' . $this->e0 . ' \d+(\/\d+)( {0,2}yEnc)?$/', $this->subject, $match)) {
			return $match[1];
		} //"the.tudors.s03e03.nfo" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//(08/25) "Wild Russia 5 of 6 The Secret Forest 2009.part06.rar" - 47.68 MB - 771.18 MB - yEnc
		//(01/24) "ITV Wild Britain With Ray Mears 1 of 6 Deciduous Forest 2011.nfo" - 4.34 kB - 770.97 MB - yEnc
		//(24/24) "BBC Great British Garden Revival 03 of 10 Cottage Gardens And House Plants 2013.vol27+22.PAR2" - 48.39 MB - 808.88 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "((BBC|ITV) )?(.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|") - \d.+? - (\d.+? -)? yEnc$/', $this->subject, $match)) {
			return $match[4];
		} //[ Angel.S01.NTSC.DVDRip.DD2.0.x264.CRF-OtakuLoser ]-[003/550] - "Angel.S01E01.City.Of.NTSC.DVDRip.DD2.0.CRF.x264-OtakuLoser.part01.rar" yEnc
		if (preg_match('/^(.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //FIRST.WAVE.NTSC.DVD.DD2.0 - [121/512] - "FIRST_WAVE_SEASON_1_DVD2.r26" - 44,33 GB - yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\.]+) - \[\d+(\/\d+\]) - ".+?' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(03/19) "Damages S04E02 FR.avi" - 5,49 GB yEnc
		if (preg_match('/^\(\d+(\/\d+\)) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[2];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function tv_deutsch()
	{
		//>ghost-of-usenet.org<< Roseanne.S03E02.Die.lieben.Verwandten.German.1990.FS.DVDRip.XviD-GM4FS >>www.SSL-News.info> - (01/32) - "gm4fs-roseannedxvid-s03e02-sample.par2" yEnc
		if (preg_match('/^>ghost-of-usenet\.org<< ?(.+) ?>>www.+>[ _-]{0,3}("|#34;)?.+("|#34;)? ?yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function u4e()
	{
		//"Angry Birds USA PSN PSP-NRP.exe" yEnc
		if (preg_match('/^"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function u_4all()
	{
		//Breakin.1984.German.DL.720p.HDTV.x264-msd [ich for usenet-4all.info] [ich25729] [powered by ssl-news.info] (01/99) "ich25729.par2" yEnc
		if (preg_match('/^(.+)\[.+?usenet-4all\.info\][ _-]{0,3}\[.+\][ _-]{0,3}\(\d+\/\d+\) ("|#34;).+("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function usenetrevolution()
	{
		//[3/3] - "Berwein Saskia - Leitner und Grohmann 02 - Herzenskaelte.epub.vol0+1.par2" - 860,05 kB {UR - powered by secretusenet.com} yEnc
		if (preg_match('/^\[\d+\/\d+\][ _-]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB].+secretusenet\.com.+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		//- UR-powered by secretusenet.com - [3/3] - "Berg Ellen - Zur Hoelle mit Seniorentellern!.vol0+1.par2" - 1,24 MB yEnc
		if (preg_match('/^.secretusenet\.com[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}"([\w.,&! ()\[\]\'-]{8,}?\b.?)' . $this->e0 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB][- ]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		//- "Alcohol 120 Retail v2.0.2.5629German-UR.info.rar" - (2/3) -  13,35 MB -...:::UR.info-SecretUsenet.com:::... yEnc
		if (preg_match('/^"([\w.,&! ()\[\]\'-]{8,}?\b.?)' . $this->e0 . '[ _-]{0,3}\(\d+\/\d+\)[ _-]{0,3}[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB].+secretusenet\.com.+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function warez()
	{
		//BabysitterMovies.13.03.11.Babysitter.Jocelyn.Pink.XXX.HR.WMV-VSEX - [7/7] - "BabysitterMovies.13.03.11.Babysitter.Jocelyn.Pink.XXX.HR.WMV-VSEX.rar.vol15+5.par2" yEnc
		if (preg_match('/^([a-zA-Z].+) - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //ATKExotics.13.01.06.Janea.Toys.XXX.1080p.x264-SEXORS - [1/7] - #34;ATKExotics.13.01.06.Janea.Toys.XXX.1080p.x264-SEXORS.rar#34; yEnc
		if (preg_match('/^([a-z].+) - \[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		//-Panzer.Command.Kharkov-SKIDROW - [1/7] - "-Panzer.Command.Kharkov-SKIDROW.rar" yEnc
		//-AssMasterpiece.12.07.09.Alexis.Monroe.XXX.1080p.x264-SEXORS - [1/7] - #34;-AssMasterpiece.12.07.09.Alexis.Monroe.XXX.1080p.x264-SEXORS.rar#34; yEnc
		if (preg_match('/.*[\(\[]\d+\/\d+[\)\]][-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //- "JH2U0H5FIK8TO7YK3Q.part12.rar" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //( XS Video Converter Ultimate 7.7.0 Build 20121226 ) - yEnc
		if (preg_match('/^\( (.+?) \) - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function warez_0_day()
	{
		//BabysitterMovies.13.03.11.Babysitter.Jocelyn.Pink.XXX.HR.WMV-VSEX - [7/7] - "BabysitterMovies.13.03.11.Babysitter.Jocelyn.Pink.XXX.HR.WMV-VSEX.rar.vol15+5.par2" yEnc
		if (preg_match('/^([a-zA-Z].+) - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //ATKExotics.13.01.06.Janea.Toys.XXX.1080p.x264-SEXORS - [1/7] - #34;ATKExotics.13.01.06.Janea.Toys.XXX.1080p.x264-SEXORS.rar#34; yEnc
		if (preg_match('/^([a-z].+) - \[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		//-Panzer.Command.Kharkov-SKIDROW - [1/7] - "-Panzer.Command.Kharkov-SKIDROW.rar" yEnc
		//-AssMasterpiece.12.07.09.Alexis.Monroe.XXX.1080p.x264-SEXORS - [1/7] - #34;-AssMasterpiece.12.07.09.Alexis.Monroe.XXX.1080p.x264-SEXORS.rar#34; yEnc
		if (preg_match('/.*[\(\[]\d+\/\d+[\)\]][-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //- "JH2U0H5FIK8TO7YK3Q.part12.rar" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //( XS Video Converter Ultimate 7.7.0 Build 20121226 ) - yEnc
		if (preg_match('/^\( (.+?) \) - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function wii()
	{
		//<kere.ws/illuminatenboard.org>Guitar.Hero.5-Vampire.Weekend.The.Kids.Dont.Stand.a.Chance.PAL.DLC.Wii-OneUp>(01/13) "1u-gh5-the-kids-dont-stand-a-chance-pal.nfo" yEnc
		if (preg_match('/^<kere\.ws.+>(.+)>\(\d+\/\d+\) ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Alice_In_Wonderland_PAL_Wii-BAHAMUT) [000/101] - "b-alicew.nzb" (1/4yEnc
		if (preg_match('/^\(([a-zA-Z].+)\) \[\d+\/\d+\] - ".+".+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(13/24) "amateur.-.Deutsches.Girl.-.Erster.User.Anal.Fick.rar" - 2,40 GB -md-hobbys.com yEnc
		if (preg_match('/^\(\d+\/\d+\) "(.+)\.(rar|par2|avi|URL|jpg)".+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[000/104] - "Story_Hour_Adventures_USA_WII-ZRY.nzb" - Story_Hour_Adventures_USA_WII-ZRY (1/9yEnc
		if (preg_match('/^\[\d+\/\d+\] - ".+" - ([A-z0-9_-]+).+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //www.theplace4you.org - [066/104] - "1234 Star.Wars.The.Force.Unleashed.2.PAL.Wii.part065.rar" yEnc
		if (preg_match('/^www\.theplace4you\.org.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<www.illuminatenboard.org>>Super_Monkey_Ball_PAL_MULTi5_NGC_WORKING_iNTERNAL_For_Wii-SUNSHiNE>(01/35) "shine-gmbp.nfo" yEnc
		if (preg_match('/^<<www\.illuminatenboard\.org>>(.+)>\(\d+\/\d+\) ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(-=  R-KNORLOADING POST  =-: Super Paper MarioPAL FTD-NR: 874068   =-   R-KNORLOADING   -= ) [01/19] - "R-KNORLOADING POST super paper mario PAL.sfv" yEnc
		if (preg_match('/^\(-=\s+R-KNORLOADING POST\s+=-: (.+) FTD-NR: \d+\s+=-.+-= \) \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //With Wii-Studio you can convert movie and music files easily so you can play it on Wii (2009) - "S.A.D.-Wii.Studio.v1.0.7.1127-incl.Patch.nfo" - (01/18) yEnc
		if (preg_match('/^.+"(.+)\.nfo" - \(\d+\/\d+\) yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function wii_gamez()
	{
		//Wicked_Monster_Blast_USA_WII-ZRY - [1/105] - "Wicked_Monster_Blast_USA_WII-ZRY.par2" yEnc
		if (preg_match('/^(\w.+) - \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function worms()
	{
		//[U4A] 2 Dudes and a Dream 2009 BRRip XvidHD 720p-NPW[01/36] - "2 Dudes and a Dream 2009 BRRip XvidHD 720p-NPW-Sample.avi" yEnc
		if (preg_match('/^(\[U4A]) (.+?)\[\d+(\/\d+\]) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//(38/57) "Fright.Night.2.New.Blood.2013.UNRATED.BluRay.810p.DTS.x264-PRoDJi.part26.rar" - 4,81 GB - yEnc
		//(14/20) "Jack.the.Giant.Slayer.2013.AC3.192Kbps.23fps.2ch.TR.Audio.BluRay-Demuxed.by.par2" - 173,15 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) ("|#34;)(.+)(\.[vol|part].+)?\.(par2|nfo|rar|nzb)("|#34;) - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/i', $this->subject, $match)) {
			return $match[3];
		} //(01/40) - Description - "Cloudy.with.a.Chance.of.Meatballs.2009.AC3.23Fps.640Kbps.6Ch.TR.Audio.BD-Demuxed.by.DWA.nfo" - 454,60 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) - Description - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //(151/161) "Troy.2004.Directors.Cut.1080p.BluRay.x264.DTS.vol064+21.PAR2.bad" - 18,64 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		}
		//[Macguffin Proudly Presents] (33/75) -Reservoir Dogs 1992 720p BDRip AC3 x264 MacGuffin - "macguffin-redog720p.r30"  - 3.06 GB - yEnc
		//[Request][MacGuffin Proudly Presents](056/229) - LOTR The Trilogy EXTENDED REPOST 720p BRRip AC3 x264 MacGuffin - "macguffin-tfotr720p.r53" - 18.54 GB - yEnc
		//[REPOST][MacGuffin Proudly Presents](44/54) - Flight 2012 720p BRRip REPOST AC3 x264 MacGuffin - "macguffin-flit720p.rar" - 4.09 GB - yEnc
		if (preg_match('/^(\[(Request|Repost)\])?\[Macguffin Proudly Presents\][- ]{0,3}\(\d+\/\d+\)[- ]{0,3}(.+?) MacGuffin[- ]{0,3}".+' . $this->e0 . '[- ]{0,4}\d+[.,]\d+ [kKmMgG][bB][- ]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[3];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function x()
	{
		//!!www.usenet4all.eu!! - Acceptance.2009.COMPLETE.NTSC.DVDR-D0PE[001/100] - #34;d0pe-a.nfo#34; yEnc
		if (preg_match('/^!!www\.usenet4all\.eu!![ _-]{0,3}(.+)\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function x264()
	{
		//"Batman-8 TDKR-Pittis AVCHD-ADD.English.dtsHDma.part013.rar" (042/197) yEnc
		if (preg_match('/^"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}") \(\d+\/(\d+\)) yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(001/108) "Wizards.of.Waverly.Place.720p.S04E01.by.sugarr.par2" - 5,15 GB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB]( -)? yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //[NZBsRus.com]-[Arthur.Christmas.2011.BDRip.AC3-5.1.x264-AKS74u] [18/74] - "ac-bdrip-aks74u.r15" yEnc
		if (preg_match('/^\[NZBsRus\.com.+\]-\[(.+)\][-_ ]{0,3}\[\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[Entourage.S05.720p.WEB-DL.DD5.1.H.264-BTN]-[167/193] - "Entourage.S05E11.Playn.With.Fire.720p.WEB-DL.DD5.1.h.264-BTN.part05.rar" yEnc
		if (preg_match('/^\[.+\]-\[\d+\/\d+\] - "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //CTW487678426 | Get.the.Gringo.1080p.BluRay.x264.DTS-HDChina.r43-CTW  yEnc
		if (preg_match('/^CTW\d+ \| (.+)  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [0/8] - Crionics Post - Alice In Chains - Dirt REPOST"Alice In Chains - Dirt.nzb" yEnc
		if (preg_match('/^.+?\[\d+\/(\d+\][-_ ]{0,3}.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //(????) [001/153] - "C4 House Party Horse Meat Disco Set 6.nfo" C4 House Party Horse Meat Disco Set 6 yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\][ -]{0,3}"([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [19/22] - C.K.N. Demo 85  "19-rotten system.mp3" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/\d+\] - (.+)[ -]{0,3}".+?' . $this->e0 . '.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function xbox360()
	{
		//a.b.g.xbox360 presents [ReqID: 8747][Lego_Star_Wars_The_Complete_Saga_USA_XBOX360-PROTOCOL] [01/80] - "ptc-swcs.nfo" yEnc
		if (preg_match('/^a\.b\.g\.xbox360 presents \[ReqID: \d+\]\[(.+)\] \[\d+\/\d+\] - ".+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function dk_tv()
	{
		//Store.Boligdroemme.S02E06.DANiS H.HDTV.x264-TVBYEN - [01/28] - "store.boligdroemme.s02e06.danis h.hdtv.x264-tvbyen.nfo" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	public function generic_town()
	{
		//<TOWN><www.town.ag > <download all our files with>>> www.ssl-news.info <<< > [05/87] - "Deep.Black.Ass.5.XXX.1080p.WEBRip.x264-TBP.part03.rar" - 7,87 GB yEnc
		//<TOWN><www.town.ag > <partner of www.ssl-news.info > [02/24] - "Dragons.Den.UK.S11E02.HDTV.x264-ANGELiC.nfo" - 288,96 MB yEnc
		//<TOWN><www.town.ag > <SSL - News.Info> [6/6] - "TTT.Magazine.2013.08.vol0+1.par2" - 33,47 MB yEnc
		if (preg_match('/^<TOWN>.+?town\.ag.+?(www\..+?|News)\.[iI]nfo.+? \[\d+\/\d+\]( -)? "(.+?)(-sample)?' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB]M? yEnc$/', $this->subject, $match)) {
			return $match[3];
		} //<TOWN><www.town.ag > <download all our files with>>> www.ssl-news.info <<< >IP Scanner Pro 3.21-Sebaro - [1/3] - "IP Scanner Pro 3.21-Sebaro.rar" yEnc
		if (preg_match('/^<TOWN>.+?town\.ag.+?(www\..+?|News)\.[iI]nfo.+? \[\d+\/\d+\]( -)? "(.+?)(-sample)?' . $this->e1, $this->subject, $match)) {
			return $match[3];
		} //(05/10) -<TOWN><www.town.ag > <partner of www.ssl-news.info > - "D.Olivier.Wer Boeses.saet-gsx-.part4.rar" - 741,51 kB - yEnc
		if (preg_match('/^\(\d+\/\d+\) -<TOWN><www\.town\.ag >\s+<partner.+> - ("|#34;)([\w. ()-]{8,}?\b)(\.par2|-\.part\d+\.rar|\.nfo)("|#34;) - \d+[.,]\d+ [kKmMgG][bB]( -)? yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ MOVIE ] [14/19] - "Night.Vision.2011.DVDRip.x264-IGUANA.part12.rar" - 660,80 MB yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}\[ .* \] \[\d+\/\d+\][ _-]{0,3}("|#34;)(.+)((\.part\d+\.rar)|(\.vol\d+\+\d+\.par2))("|#34;)[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ MOVIE ] [01/84] - "The.Butterfly.Effect.2.2006.1080p.BluRay.x264-LCHD.par2" - 7,49 GB yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}\[ .* \] \[\d+\/\d+\][ _-]{0,3}("|#34;)(.+)\.(par2|rar|nfo|nzb)("|#34;)[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ] [22/22] - "Arsenio.Hall.2013.09.11.Magic.Johnson.720p.HDTV.x264-2HD.vol31+11.par2" - 1,45 GB yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}(\[ TV \] )?\[\d+\/\d+\][ _-]{0,3}("|#34;)(.+)((\.part\d+\.rar)|(\.vol\d+\+\d+\.par2)|\.nfo|\.vol\d+\+\.par2)("|#34;)[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[3];
		} //[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ] [01/28] - "Arsenio.Hall.2013.09.18.Dr.Phil.McGraw.HDTV.x264-2HD.par2" - 352,58 MB yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}(\[ TV \] )?\[\d+\/\d+\][ _-]{0,3}("|#34;)(.+)\.par2("|#34;)[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[3];
		} //4675.-.Wedding.Planner.multi3.(EU) <TOWN><www.town.ag > <partner of www.ssl-news.info > <Games-NDS >  [01/10] - "4675.-.Wedding.Planner.multi3.(EU).par2" - 72,80 MB - yEnc
		if (preg_match('/^\d+\.-\.(.+) <TOWN><www\.town\.ag >\s+<partner .+>\s+<.+>\s+\[\d+\/\d+\] - ("|#34;).+("|#34;).+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//4675.-.Wedding.Planner.multi3.(EU) <TOWN><www.town.ag > <partner of www.ssl-news.info > <Games-NDS >  [01/10] - "4675.-.Wedding.Planner.multi3.(EU).par2" - 72,80 MB - yEnc
		// Some have no yEnc
		if (preg_match('/^\d+\.-\.(.+) <TOWN><www\.town\.ag >\s+<partner .+>\s+<.+>\s+\[\d+\/\d+\] - ("|#34;).+/', $this->subject, $match)) {
			return $match[1];
		} //Marco.Fehr.-.In.the.Mix.at.Der.Club-09-01-SAT-2012-XDS <TOWN><www.town.ag > <partner of www.ssl-news.info >  [01/13] - "Marco.Fehr.-.In.the.Mix.at.Der.Club-09-01-SAT-2012-XDS.par2" - 92,12 MB - yEnc
		if (preg_match('/^(\w.+) <TOWN><www\.town\.ag >\s+<partner.+>\s+\[\d+\/\d+\] - ("|#34;).+("|#34;).+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Marco.Fehr.-.In.the.Mix.at.Der.Club-09-01-SAT-2012-XDS <TOWN><www.town.ag > <partner of www.ssl-news.info >  [01/13] - "Marco.Fehr.-.In.the.Mix.at.Der.Club-09-01-SAT-2012-XDS.par2" - 92,12 MB - yEnc
		// Some have no yEnc
		if (preg_match('/^(\w.+) <TOWN><www\.town\.ag >\s+<partner.+>\s+\[\d+\/\d+\] - ("|#34;).+/', $this->subject, $match)) {
			return $match[1];
		} //<TOWN><www.town.ag > <partner of www.ssl-news.info > JetBrains.IntelliJ.IDEA.v11.1.4.Ultimate.Edition.MacOSX.Incl.Keymaker-EMBRACE  [01/18] - "JetBrains.IntelliJ.IDEA.v11.1.4.Ultimate.Edition.MacOSX.Incl.Keymaker-EMBRACE.par2" - 200,77 MB - yEnc
		if (preg_match('/^<TOWN><www\.town\.ag >\s+<partner .+>\s+(.+)\s+\[\d+\/\d+\] - ("|#34;).+("|#34;).+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//<TOWN><www.town.ag > <partner of www.ssl-news.info > JetBrains.IntelliJ.IDEA.v11.1.4.Ultimate.Edition.MacOSX.Incl.Keymaker-EMBRACE  [01/18] - "JetBrains.IntelliJ.IDEA.v11.1.4.Ultimate.Edition.MacOSX.Incl.Keymaker-EMBRACE.par2" - 200,77 MB - yEnc
		// Some have no yEnc
		if (preg_match('/^<TOWN><www\.town\.ag >\s+<partner .+>\s+(.+)\s+\[\d+\/\d+\] - ("|#34;).+/', $this->subject, $match)) {
			return $match[1];
		} //<TOWN><www.town.ag > <partner of www.ssl-news.info > [01/18] - "2012-11.-.Supurbia.-.Volume.Tw o.Digital-1920.K6-Empire.par2" - 421,98 MB yEnc
		if (preg_match('/^[ <\[]{0,2}TOWN[ >\]]{0,2}[ _-]{0,3}[ <\[]{0,2}www\.town\.ag[ >\]]{0,2}[ _-]{0,3}[ <\[]{0,2}partner of www.ssl-news\.info[ >\]]{0,2}[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;)(.+)\.(par|vol|rar|nfo).*?("|#34;).+?yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //<TOWN> www.town.ag > sponsored by www.ssl-news.info > (1/3) "HolzWerken_40.par2" - 43,89 MB - yEnc
		if (preg_match('/^<TOWN> www\.town\.ag > sponsored by www\.ssl-news\.info > \(\d+\/\d+\) "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(1/9)<<<www.town.ag>>> sponsored by ssl-news.info<<<[HorribleSubs]_AIURA_-_01_[480p].mkv "[HorribleSubs]_AIURA_-_01_[480p].par2" yEnc
		if (preg_match('/^\(\d+\/\d+\).+?www\.town\.ag.+?sponsored by (www\.)?ssl-news\.info<+?.+? "([\w.,& ()\[\]\'\`-]{8,}?\b.?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //[ TOWN ]-[ www.town.ag ]-[ Assassins.Creed.IV.Black.Flag.XBOX360-COMPLEX ]-[ partner of www.ssl-news.info ] [074/195]- "complex-ac4.bf.d1.r71" yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ (.+?) \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}\[\d+\/(\d+\])[ _-]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //(TOWN)(www.town.ag ) (partner of www.ssl-news.info ) Twinz-Conversation-CD-FLAC-1995-CUSTODES  [01/23] - #34;Twinz-Conversation-CD-FLAC-1995-CUSTODES.par2#34; - 266,00 MB - yEnc
		if (preg_match('/^\(TOWN\)\(www\.town\.ag \)[ _-]{0,3}\(partner of www\.ssl-news\.info \)[ _-]{0,3} (.+?) \[\d+\/(\d+\][ _-]{0,3}("|#34;).+?)\.(par2|rar|nfo|nzb)("|#34;)[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<TOWN><www.town.ag > <partner of www.ssl-news.info > Greek.S04E06.Katerstimmung.German.DL.Dubbed.WEB-DL.XviD-GEZ  [01/22] - "Greek.S04E06.Katerstimmung.German.DL.Dubbed.WEB-DL.XviD-GEZ.par2" - 526,99 MB - yEnc
		if (preg_match('/^<TOWN><www\.town\.ag > <partner of www\.ssl-news\.info > (.+) \[\d+\/\d+\][ _-]{0,3}("|#34;).+?("|#34;).+?yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
	}

	// Run at the end because this can be dangerous. In the future it's better to make these per group. There should not be numbers after yEnc because we remove them as well before inserting (even when importing).
	public function generic()
	{
		// This regex gets almost all of the predb release names also keep in mind that not every subject ends with yEnc, some are truncated, because of the 255 character limit and some have extra charaters tacked onto the end, like (5/10).
		if (preg_match('/^\[\d+\][- ]{0,3}(\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3}\[[- #@\.\w]+\][- ]{0,3}|\[[- #@\.\w]+\][- ]{0,3}\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3}|\[.+?efnet\][- ]{0,3}|\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[- ]{0,3})(\[FULL\])?[- ]{0,3}(\[ )?(\[)? ?(\/sz\/)?(F: - )?(?P<title>[- _!@\.\'\w\(\)~]{10,}) ?(\])?[- ]{0,3}(\[)? ?(REPOST|REPACK|SCENE|EXTRA PARS|REAL)? ?(\])?[- ]{0,3}?(\[\d+[-\/~]\d+\])?[- ]{0,3}["|#34;]*.+["|#34;]* ?[yEnc]{0,4}/i', $this->subject, $match)) {
			return $match['title'];
		}
		return array("cleansubject" => $this->releaseCleanerHelper($this->subject), "properlynamed" => false);
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
		$cleanerName = trim(utf8_encode(preg_replace('/[^(\x20-\x7F)]*/', '', $cleanerName)));
		return $cleanerName;
	}
}
