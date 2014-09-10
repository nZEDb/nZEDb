<?php

/**
 * Cleans names for collections/imports/namefixer.
 *
 * Class CollectionsCleaning
 */
class CollectionsCleaning
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
	 * Used for matching file extension endings in article subjects.
	 * @const
	 * @string
	 */
	const regexFileExtensionString = '([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")';

	/**
	 * Used for matching endings in article subjects.
	 * @const
	 * @string
	 */
	const regexEndString = '[- ]{0,3}yEnc$/';

	/**
	 * Used for matching size strings in article subjects.
	 * @example ' - 365.15 KB - '
	 * @const
	 * @string
	 */
	const regexSizeString = '[- ]{0,3}\d+([.,]\d+)? [kKmMgG][bB][- ]{0,3}';

	/**
	 *
	 */
	public function __construct()
	{
		// Extensions.
		$this->e0 = self::regexFileExtensionString;
		$this->e1 = self::regexFileExtensionString . self::regexEndString;
		$this->e2 = self::regexFileExtensionString . self::regexSizeString . self::regexEndString;
	}

	/**
	 * Cleans a usenet subject returning a string that can be used to "merge" files together, a pretty subject, a categoryID and the name status.
	 *
	 * @param string $subject   Subject to parse.
	 * @param string $groupName Group to work in.
	 *
	 * @return string
	 */
	public function collectionsCleaner($subject, $groupName)
	{
		$this->subject = $subject;
		$this->groupName = $groupName;
		switch ($groupName) {
			case 'alt.binaries.0day.stuffz':
				return $this->_0daystuffz();
			case 'alt.binaries.3d':
				return $this->_3d();
			case 'alt.binaries.anime':
				return $this->anime();
			case 'alt.binaries.astronomy':
				return $this->astronomy();
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
			case 'alt.binaries.comp':
				return $this->comp();
			case 'alt.binaries.cores':
				return $this->cores();
			case 'alt.binaries.console.ps3':
				return $this->console_ps3();
			case 'alt.binaries.country.mp3':
				return $this->country_mp3();
			case 'alt.binaries.dc':
				return $this->dc();
			case 'alt.binaries.documentaries':
				return $this->documentaries();
			case 'alt.binaries.downunder':
				return $this->downunder();
			case 'alt.binaries.dvd':
				return $this->dvd();
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
			case 'alt.binaries.e-book.rpg':
				return $this->e_book_rpg();
			case 'alt.binaries.erotica':
				return $this->erotica();
			case 'alt.binaries.etc':
				return $this->etc();
			case 'alt.binaries.font':
				return $this->font();
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
			case 'alt.binaries.milo':
				return $this->milo();
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
			case 'alt.binaries.mp3.audiobooks':
				return $this->sounds_audiobooks();
			case 'alt.binaries.sound.audiobooks':
				return $this->sounds_audiobooks();
			case 'alt.binaries.sounds.audiobooks.repost':
				return $this->sounds_audiobooks();
			case 'alt.binaries.sounds.mp3.audiobooks':
				return $this->sounds_audiobooks();
			case 'alt.binaries.teevee':
				return $this->teevee();
			case 'alt.binaries.town':
				return $this->town();
			case 'alt.binaries.tun':
				return $this->tun();
			case 'alt.binaries.tv':
				return $this->tv();
			case 'alt.binaries.tvseries':
				return $this->tvseries();
			case 'alt.binaries.warez':
				return $this->warez();
			case 'alt.binaries.warez.0-day':
				return $this->warez_0day();
			case 'alt.binaries.worms':
				return $this->worms();
			case 'alt.binaries.x264':
				return $this->x264();
			case 'dk.binaer.tv':
				return $this->dk_tv();
			default:
				return $this->generic();
		}
	}

	// a.b.0daystuffz
	protected function _0daystuffz()
	{
		//ArcSoft.TotalMedia.Theatre.v5.0.1.87-Lz0 - [08/35] - "ArcSoft.TotalMedia.Theatre.v5.0.1.87-Lz0.vol43+09.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?)( - )\[\d+(\/\d+\] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2] . $match[3];
		}
		//rld-tcavu1 [5/6] - "rld-tcavu1.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?) \[\d+(\/\d+\] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//(DVD Shrink.ss) [1/1] - "DVD Shrink.ss.rar" yEnc
		if (preg_match('/^(\((.+?)\) \[)\d+(\/\d+] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		}
		//WinASO.Registry.Optimizer.4.8.0.0(1/4) - "WinASO_RO_v4.8.0.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?)\(\d+(\/\d+\) - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.3d
	protected function _3d()
	{
		//ugMBqtZw3vFnmrmr16EQhaAz9mMri9mD - File 01 of 98: "1vJ7wswi9ZG6YrzE5OGBFPKeuRv9X86xgYdK.par2" yEnc
		if (preg_match('/^(.+)File \d+ (of \d+): ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//999XL[074/103] - "LLKJ678CCDE1223ss.part073.rar" yEnc
		if (preg_match('/^(.+)\[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.anime
	protected function anime()
	{
		//([AST] One Piece Episode 301-350 [720p]) [007/340] - "One Piece episode 301-350.part006.rar" yEnc
		if (preg_match('/^(\((\[.+?\] .+?)\) \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[REPOST][ New Doraemon 2013.05.03 Episode 328 (TV Asahi) 1080i HDTV MPEG2 AAC-DoraClub.org ] [35/61] - "doraclub.org-doraemon-20130503-b8de1f8e.r32" yEnc
		if (preg_match('/^(\[.+?\]\[ (.+?) \] \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[De.us] Suzumiya Haruhi no Shoushitsu (1920x1080 h.264 Dual-Audio FLAC 10-bit) [017CB24D] [000/357] - "[De.us] Suzumiya Haruhi no Shoushitsu (1920x1080 h.264 Dual-Audio FLAC 10-bit) [017CB24D].nzb" yEnc
		if (preg_match('/^(\[.+?\] (.+?) \[[A-F0-9]+\] \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[eraser] Ghost in the Shell ARISE - border_1 Ghost Pain (BD 720p Hi444PP LC-AAC Stereo) - [01/65] - "[eraser] Ghost in the Shell ARISE - border_1 Ghost Pain (BD 720p Hi444PP LC-AAC Stereo) .md5" yEnc
		if (preg_match('/^(\[.+?\] (.+?) - \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(01/27) - Maid.Sama.Jap.dubbed.german.english.subbed - "01 Misaki ist eine Maid!.divx" - 6,44 GB - yEnc
		if (preg_match('/^\(\d+(\/\d+\) - (.+?) - ").+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[ New Doraemon 2013.06.14 Episode 334 (TV Asahi) 1080i HDTV MPEG2 AAC-DoraClub.org ] [01/60] - "doraclub.org-doraemon-20130614-fae28cec.nfo" yEnc
		if (preg_match('/^(\[ (.+?) \] \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//<TOWN> www.town.ag > sponsored by www.ssl-news.info > (1/3) "HolzWerken_40.par2" - 43,89 MB - yEnc
		//<TOWN> www.town.ag > sponsored by www.ssl-news.info > (1/5) "[HorribleSubs]_Aku_no_Hana_-_01_[480p].par2" - 157,13 MB - yEnc
		if (preg_match('/^<TOWN> www\.town\.ag > sponsored by www\.ssl-news\.info > \(\d+(\/\d+\) ".+?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(1/9)<<<www.town.ag>>> sponsored by ssl-news.info<<<[HorribleSubs]_AIURA_-_01_[480p].mkv "[HorribleSubs]_AIURA_-_01_[480p].par2" yEnc
		if (preg_match('/^\(\d+\/\d+\)(.+?www\.town\.ag.+?sponsored by (www\.)?ssl-news\.info<+?(.+?)) ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Overman King Gainer [Dual audio, EngSub] Exiled Destiny - [002/149] - "Overman King Gainer.part001.rar" yEnc
		if (preg_match('/^(.+? \[Dual [aA]udio, EngSub\] .+?) - \[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//blazedazer_NAN000010 [140/245] - "blazedazer_NAN000010.part138.rar" yEnc
		if (preg_match('/^((blazedazer_.+?) \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}

		return $this->generic();
	}

	// a.b.astronomy
	protected function astronomy()
	{
		//58600-0[51/51] - "58600-0.vol0+1.par2" yEnc
		if (preg_match('/^(\d+-)\d+\[\d+(\/\d+\] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ TV ] [01/16] - "Jono.And.Ben.At.Ten.S02E14.PDTV.x264-FiHTV.par2" - 198,25 MB yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}\[ .* \] \[\d+\/(\d+\][ _-]{0,3}("|#34;).+)\.(par2|rar|nfo|nzb)("|#34;)[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.ath
	protected function ath()
	{
		//[3/3 Karel Gott - Die Biene Maja Original MP3 Karel Gott - Die Biene Maja Original MP3.mp3.vol0+1.PAR2" yEnc
		if (preg_match('/^\[\d+\/\d+ ([a-zA-Z0-9]+ .+?)\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ TV ] [01/16] - "Jono.And.Ben.At.Ten.S02E14.PDTV.x264-FiHTV.par2" - 198,25 MB yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}\[ .* \] \[\d+\/(\d+\][ _-]{0,3}("|#34;).+)\.(par2|rar|nfo|nzb)("|#34;)[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //8b33bf5960714efbe6cfcf13dd0f618f - (01/55) - "8b33bf5960714efbe6cfcf13dd0f618f.par2" yEnc
		if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}\..+" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//nmlsrgnb - [04/37] - "htwlngmrstdsntdnh.part03.rar" yEnc
		if (preg_match('/^([a-z]+ - \[)\d+\/\d+\] - "([a-z]+)\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//>>>>>Hell-of-Usenet>>>>> - [01/33] - "Cassadaga Hier lebt der Teufel 2011 German AC3 DVDRip XViD iNTERNAL-VhV.par2" yEnc
		if (preg_match('/^(>+Hell-of-Usenet(\.org)?>+( -)? \[)\d+\/\d+\] - "(.+?)' . $this->e0 . '( - \d+[.,]\d+ [kKmMgG][bB])? yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		}
		//1dbo1u5ce6182436yb2eo (001/105) "1dbo1u5ce6182436yb2eo.par2" yEnc
		if (preg_match('/^([a-z0-9]{10,}) \(\d+\/\d+\) "[a-z0-9]{10,}\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//<<<>>>kosova-shqip.eu<<< Deep SWG - 90s Club Megamix 2011 >>>kosova-shqip.eu<<<<<< - (2/4) - "Deep SWG - 90s Club Megamix 2011.rar" yEnc
		if (preg_match('/^(<<<>>>kosova-shqip\.eu<<< (.+?) >>>kosova-shqip.eu<<<<<< - \()\d+\/\d+\) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//<Have Fun> [02/39] - SpongeBoZZ yEnc
		if (preg_match('/^(<Have Fun> \[)\d+(\/\d+\] - .+? )yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//Old Dad uppt Taffe Mädels XivD LD HDTV Rip oben Kleine Einblendug German 01/43] - "Taffe Mädels.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?\s{2,}|Old Dad uppt\s+)(.+?) \d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[28/55] - "XzrgzBWoRqtcuBF.part27.rar" - 4,78 GB >>>UpperTeam for Usenet-Space-Cowboys.info and Secretusenet.com<<< yEnc
		if (preg_match('/^\[\d+\/(\d+\][ _-]{0,3}".+?)' . $this->e0 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB].+UpperTeam.+ Secretusenet\.com.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.audio.warez
	protected function audio_warez()
	{
		//[#nzbx.audio/EFnet]-[1681]-[MagicScore.Note.v7.084-UNION]-[02/12] - "u-msn7.r00" yEnc
		if (preg_match('/^(Re: )?(\[.+?\]-\[\d+\]-\[(.+?)\]-\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//MacProVideo.com.Pro.Tools8.101.Core.Pro.Tools8.TUTORiAL-DYNAMiCS [2 of 50] "dyn-mpvprtls101.sfv" yEnc
		//Native.Instruments.Komplete.7.VSTi.RTAS.AU.DVDR.D02-DYNAMiCS[01/13] - "dyn.par2" yEnc
		//Native.Instruments.Komplete.7.VSTi.RTAS.AU.DVDR.DYNAMiCS.NZB.ONLY [02/13] - "dyn.vol0000+001.PAR2" yEnc
		if (preg_match('/^(([\w.-]+) ?\[)\d+( of |\/)\d+\] ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//REQ : VSL Stuff ~ Here's PreSonus Studio One 1.5.2 for OS X [16 of 22] "a-p152x.rar" yEnc
		if (preg_match('/^(REQ : .+? ~ (.+?) \[)\d+ of \d+\] ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Eminem - Recovery (2010) - [1/1] - "Eminem - Recovery (2010).rar" yEnc
		if (preg_match('/^(([a-zA-Z0-9].+?) - \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(????) [1/1] - "Dust in the Wind - the Violin Solo.rar" yEnc
		if (preg_match('/^(\(\?{4}\) \[)\d+(\/\d+\] - "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//Native Instruments Battery 3 incl Library ( VST DX RTA )( windows ) Libraries [1/1] - "Native Instruments Battery 2 + 3 SERIAL KEY KEYGEN.nfo" yEnc
		if (preg_match('/^((.+?) \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.b4e
	protected function b4e()
	{
		//"B4E-vip2851.r83" yEnc
		if (preg_match('/^("(B4E-vip\d+))\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[02/12] - "The.Call.GERMAN.2013.DL.AC3.Dubbed.720p.BluRay.x264 (Avi-RiP ).rar" yEnc
		if (preg_match('/^\[\d+(\/\d+\] - "(.+?) \().+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//- "as-jew3.vol03+3.PAR2" - yEnc
		if (preg_match('/^(- "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.barbarella
	protected function barbarella()
	{
		//ACDSee.Video.Converter.Pro.v3.5.41.Incl.Keymaker-CORE - [1/7] - "ACDSee.Video.Converter.Pro.v3.5.41.Incl.Keymaker-CORE.par2" yEnc
		if (preg_match('/^(([a-zA-Z0-9].+?) - \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Die.Nacht.Der.Creeps.THEATRICAL.GERMAN.1986.720p.BluRay.x264-GH - "gh-notcreepskf720.nfo" yEnc
		//The.Fast.and.the.Furious.Tokyo.Drift.2006.German.1080p.BluRay.x264.iNTERNAL-MWS  - "mws-tfatftd-1080p.nfo" yEnc
		if (preg_match('/^(([\w.-]+)\s+-\s+").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//CorelDRAW Technical Suite X6-16.3.0.1114 x32-x64<><>DRM<><> - (10/48)  "CorelDRAW Technical Suite X6-16.3.0.1114 x32-x64.part09.rar" - 2,01 GB - yEnc
		//AnyDVD_7.1.9.3_-_HD-BR - Beta<>give-me-all.org<>DRM<><> - (1/3)  "AnyDVD_7.1.9.3_-_HD-BR - Beta.par2" - 14,53 MB - yEnc
		//Android Softarchive.net Collection Pack 27^^give-me-all.org^^^^DRM^^^^ - (01/26)  "Android Softarchive.net Collection Pack 27.par2" - 1,01 GB - yEnc
		//WIN7_ULT_SP1_x86_x64_IE10_19_05_13_TRIBAL <> give-me-all.org <> DRM <> <> PW <> - (154/155)  "WIN7_ULT_SP1_x86_x64_IE10_19_05_13_TRIBAL.vol57+11.par2" - 7,03 GB - yEnc
		//[Android].Ultimate.iOS7.Apex.Nova.Theme.v1.45 <> DRM <> - (1/3)  "[Android].Ultimate.iOS7.Apex.Nova.Theme.v1.45.par2" - 21,14 MB - yEnc
		if (preg_match('/^(((\[[A-Za-z]+\]\.)?[a-zA-Z0-9].+?) ?([\^<> ]+give-me-all\.org[\^<> ]+|[\^<> ]+)DRM[\^<> ]+.+? - \()\d+\/\d+\)  ".+?" - .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(004/114) - Description - "Pluralsight.net XAML Patterns (10).rar" - 532,92 MB - yEnc
		if (preg_match('/^\(\d+(\/\d+\) - .+? - "(.+?))( \(\d+\))?' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(59/81) "1973 .Lee.Jun.Fan.DVD9.untouched.z46" - 7,29 GB - Lee.Jun.Fan.sein.Film.DVD9.untouched yEnc
		//(01/12) - "TransX - Living on a Video 1993.part01.rar" - 561,55 MB - TransX - Living on a Video 1993.[Lossless] Highh Quality yEnc
		if (preg_match('/^\(\d+\/\d+\)( -)? ".+?" - \d+[,.]\d+ [mMkKgG]([bB] - (.+?)) yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//>>> www.lords-of-usenet.org <<<  "Der Schuh Des Manitu.par2" DVD5  [001/158] - 4,29 GB yEnc
		if (preg_match('/^(>>> www\.lords-of-usenet\.org <<<.+? "(.+?))' . $this->e0 . ' .+? \[\d+\/\d+\] - .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//NEUES 4y - [@ usenet-4all.info - powered by ssl.news -] [5,58 GB] [002/120] "DovakinPack.part002.rar" yEnc
		//NEUES 4y (PW)  [@ usenet-4all.info - powered by ssl.news -] [7,05 GB] [014/152] "EngelsGleich.part014.rar" yEnc
		if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+(\/\d+\] "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[2];
		}
		//Old Dad uppt   Die Schatzinsel Teil 1+Teil2  AC3 DVD Rip German XviD Wp 01/33] - "upp11.par2" yEnc
		//Old Dad uppt Scary Movie5 WEB RiP Line XviD German 01/24] - "Scary Movie 5.par2" yEnc
		if (preg_match('/^(([a-zA-Z0-9].+?\s{2,}|Old Dad uppt\s+)(.+?) )\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//>>>  20,36 MB   "Winamp.Pro.v5.70.3392.Incl.Keygen-FFF.par2"   552 B yEnc
		//..:[DoAsYouLike]:..    9,64 MB    "Snooper 1.39.5.par2"    468 B yEnc
		if (preg_match('/^.+?\s{2,}\d+[,.]\d+ [mMkKgG][bB]\s{2,}"(.+?_)' . $this->e0 . '\s{2,}(\d+ B|\d+[,.]\d+ [mMkKgG][bB]) yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(MKV - DVD - Rip - German - English - Italiano) - "CALIGULA (1982) UNCUT.sfv" yEnc
		if (preg_match('/^(\(.+?\) - "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//"sre56565ztrtzuzi8inzufft.par2" yEnc
		if (preg_match('/^"([a-z0-9]+)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.big
	protected function big()
	{
		//Girls.against.Boys.2012.German.720p.BluRay.x264-ENCOUNTERS - "encounters-giagbo_720p.nfo" yEnc
		if (preg_match('/^(([\w.-]+) - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//wtvrwschdhfthj - [001/246] - "dtstchhtmrrnvn.par2" yEnc
		//oijhuiurfjvbklk - [01/18] - "tb5-3ioewr90f.par2" yEnc
		if (preg_match('/^(([a-z]{3,}) - \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(08/22) - "538D7B021B362A4300D1C0D84DD17E6D.r06" yEnc
		if (preg_match('/^\(\d+(\/\d+\) - "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//(????) [02/71] - "Lasting Weep (1969-1971).part.par2" yEnc
		if (preg_match('/^(\(\?{4}\) \[)\d+(\/\d+\] - "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//(01/59) "ThienSuChungQuy_II_E16.avi.001" - 1,49 GB - yEnc
		//(058/183) "LS_HoangChui_2xdvd5.part057.rar" - 8,36 GB -re yEnc
		if (preg_match('/^\(\d+(\/\d+\) "(.+?))' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] -(re)? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[AoU] Upload#00287 - [04/43] - "Upload-ZGT1-20130525.part03.rar" yEnc
		if (preg_match('/^(\[[a-zA-Z]+\] .+? - \[)\d+\/\d+\] - "(.+?)" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(nate) [01/27] - "nate_light_13.05.23.par2" yEnc
		if (preg_match('/^\([a-z]+\) \[\d+(\/\d+\] - "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//""Absolute Database Component for BCBuilder 4-6 MultiUser Edit 4.85.rar"" yEnc
		if (preg_match('/^(""(.+?))' . $this->e0 . '" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//781e1d8dccc641e8df6530edb7679a0e - (26/30) - "781e1d8dccc641e8df6530edb7679a0e.rar" yEnc
		if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}.+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.bloaf
	protected function bloaf()
	{
		//36c1d5d4eaf558126c67f00be46f77b6 - (01/22) - "36c1d5d4eaf558126c67f00be46f77b6.par2" yEnc
		if (preg_match('/^([a-f0-9]{32}) - \(\d+\/\d+\) - "[a-f0-9]{32}.+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[10/17] - "EGk13kQ1c8.part09.rar" - 372.48 MB <-> usenet-space-cowboys.info <-> powered by secretusenet.com <-> yEnc
		if (preg_match('/^\[\d+(\/\d+\] - "(.+?))' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] .+? usenet-space.+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(Neu bei Bitfighter vom 23-07-2013) - "01 - Sido - Bilder Im Kopf.mp3" yEnc
		if (preg_match('/^(\((.+?)\) - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(2/8) "Mike.und.Molly.S01E22.Maennergespraeche.GERMAN.DL.DUBBED.720p.BluRay.x264-TVP.part1.rar" - 1023,92 MB - yEnc
		if (preg_match('/^\(\d+(\/\d+\) "(.+?))' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//4y (PW)   [@ usenet-4all.info - powered by ssl.news -] [27,35 GB] [001/118] "1f8867bb6f89491793d3.part001.rar" yEnc
		if (preg_match('/^.+? (-|\(PW\))\s+\[.+? -\] \[\d+[,.]\d+ [mMkKgG][bB]\] \[\d+(\/\d+\] "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[2];
		}
		//Bennos Special Tools DVD - Die Letzte <> DRM <><> PW <> - (002/183)  "Bennos Special Tools DVD - Die Letzte.nfo" - 8,28 GB - yEnc
		if (preg_match('/^((\[[A-Za-z]+\]\.)?([a-zA-Z0-9].+?)([\^<> ]+give-me-all\.org[\^<> ]+|[\^<> ]+)DRM[\^<> ]+.+? - \()\d+\/\d+\)\s+".+?" - .+? - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(1/9) - CyberLink.PhotoDirector.4.Ultra.4.0.3306.Multilingual - "CyberLink.PhotoDirector.4.Ultra.4.0.3306.Multilingual.par2" - 154,07 MB - yEnc
		//(1/5) - Mac.DVDRipper.Pro.4.0.8.Mac.OS.X- "Mac.DVDRipper.Pro.4.0.8.Mac.OS.X.rar" - 24,12 MB - yEnc
		if (preg_match('/^\(\d+(\/\d+\) - (.+?) ?- ").+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[3/3 Helene Fischer - Die Biene Maja 2013 MP3 Helene Fischer - Die Biene Maja 2013 MP3.mp3.vol0+1.PAR2" yEnc
		if (preg_match('/^\[\d+(\/\d+ (.+?)\.).+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.blu-ray
	protected function blu_ray()
	{
		//"786936833607.MK.A.part086.rar" yEnc
		if (preg_match('/^"(\d+\.MK\.[A-Z])\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(????) [001/107] - "260713thbldnstnsclw.par2" yEnc
		if (preg_match('/^(\(\?{4}\) \[)\d+\/\d+\] - "([a-z0-9]+)\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[www.allyourbasearebelongtous.pw]-[The Place Beyond the Pines 2012 1080p US Blu-ray AVC DTS-HD MA 5.1-HDWinG]-[03/97] "tt1817273-us-hdwing-bd.r00" - 46.51 GB - yEnc
		if (preg_match('/^(\[www\..+?\]-\[(.+?)\]-\[)\d+\/\d+\] ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(01/71)  - "EwRQCtU4BnaeXmT48hbg7bCn.par2" - 54,15 GB - yEnc
		//(63/63) "dfbgfdgtghtghthgGPGEIBPBrwg34t05.rev" - 10.67 GB - yEnc
		if (preg_match('/^\(\d+(\/\d+\)(\s+ -)? "[a-zA-Z0-9]+?)\d*\..+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[01/67] - "O3tk4u681gd767Y.par2" yEnc
		if (preg_match('/^\[\d+(\/\d+\] - "([a-zA-Z0-9]+)\.).+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//209a212675ba31ca24a8 [usenet-4all.info] [powered by ssl-news] [21,59 GB] [002/223] "209a212675ba31ca24a8.part001.rar" yEnc
		if (preg_match('/^(([a-z0-9]+) \[.+?\] \[.+?\] \[)\d+[,.]\d+ [mMkKgG][bB]\] \[\d+\/\d+\] ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//TIS97CC - "tis97cc.par2" yEnc
		if (preg_match('/^(([A-Z0-9]+) - "[a-z0-9]+\.).+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//<<OBLIVION - Untouched>><<TLR for usenet-4all.info>><<www.ssl-news.info>><<13/14>> "xxtxxlxxrxxbdxx05.vol421+98.par2" - 2,54 GB - yEnc
		if (preg_match('/^.+?<<\d+\/\d+>> "(.+?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.boneless
	protected function boneless()
	{
		//4Etmo7uBeuTW[047/106] - "006dEbPcea29U6K.part046.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9]+)\[\d+(\/\d+\] - "[a-zA-Z0-9]+\.).+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //( Overlord II RELOADED ) - [013/112] - "rld-olii.part001.rar" yEnc
		if (preg_match('/^\( ([\w. -]{8,}) \)[-_ ]{0,3}\[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//(68/89) "dz1R2wT8hH1iQEA28gRvm.part67.rar" - 7,91 GB - yEnc
		//(01/14)  - "JrjCY4pUjQ9qUqQ7jx6k2VLF.par2" - 4,39 GB - yEnc
		if (preg_match('/^\(\d+(\/\d+\)\s+(- )?"([a-zA-Z0-9]+)\.).+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(110320152518519) [22/78] - "110320152518519.part21.rar" yEnc
		if (preg_match('/^(\((\d+)\) \[)\d+\/\d+\] - "\d+\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//A.Fantastic.Fear.of.Everything.2012.AC3.BDRiP.XviD-IJf.nfo [01/63] - "A.Fantastic.Fear.of.Everything.2012.AC3.BDRiP.XviD-IJf.nfo" yEnc
		if (preg_match('/^([\w. &()\[\]\'-]{8,}?\b.?)\.[A-Za-z0-9]{2,4}[- ]{0,3}\[\d+\/(\d+\])[- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//1VSXrAZPD - [123/177] - "1VSXrAZPD.part122.rar" yEnc
		if (preg_match('/^(([a-zA-Z0-9]+) - \[)\d+\/\d+\] - "[a-zA-Z0-9]+\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//( Peter Gabriel Albums 24x +17 Singles = 71x cd By Dready Niek )  ( ** By Dready Niek ** ) [113/178] - "Peter Gabriel Albums 24x +17 Singles = 71CDs By Dready Niek (1977-2010).part112.rar" yEnc
		if (preg_match('/^(\( (.+?) \)\s+\( .+?\) \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Tarja - Colours In The Dark (2013) "00. Tarja-Colours In The Dark.m3u" yEnc
		if (preg_match('/^(([A-Za-z0-9].+?) \((19|20)\d\d\) ")\d{2}\. .+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//"BB636.part14.rar" - (15/39) - yEnc
		if (preg_match('/^"([a-zA-Z0-9]+)' . $this->e0 . ' - \(\d+\/\d+\) - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Lutheria - FC Twente TV Special - Ze wilde op voetbal [16/49] - "Lutheria - FC Twente TV Special - Ze wilde op voetbal.part16.rar" yEnc
		// panter - [001/101] - "74518-The Hunters (2011).par2" yEnc
		if (preg_match('/^[-a-zA-Z0-9 ]+ \[\d+(\/\d+\] - "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//Pee Mak Prakanong - 2013 - Thailand - ENG Subs - "Pee Mak Prakanong.2013.part22.rar" yEnc
		//P2H - "AMHZQHPHDUZZJSFZ.vol181+33.par2" yEnc
		if (preg_match('/^([-a-zA-Z0-9 ]+ - "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//(????) [011/161] - "flynns-image-redux.part010.rar" yEnc
		//(Dgpc) [000/110] - "Teen Wolf - Seizoen.3 - Dvd.2 (NLsub).nzb" yEnc
		if (preg_match('/^\((\?{4}|[a-zA-Z]+)\) \[\d+(\/\d+\] - "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//("Massaladvd5Kilusadisc4S1.par2" - 4,55 GB -) "Massaladvd5Kilusadisc4S1.par2" - 4,55 GB - yEnc
		if (preg_match('/^\("([a-z0-9A-Z]+).+?" - \d+[,.]\d+ [mMkKgG][bB] -\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//"par.4kW9beE.1.vol122+21.par2" yEnc
		if (preg_match('/^"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [01/19] - "Age.of.Dinosaurs.German.AC3.HDRip.x264-FuN.par2" yEnc
		//>>>>>Hell-of-Usenet.org>>>>> - [01/35] - "Female.Agents.German.2008.AC3.DVDRip.XviD.iNTERNAL-VideoStar.par2" yEnc
		if (preg_match('/^(.+?\.(info|org)>+ - \[)\d+\/\d+\] - "(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[3];
		}
		//[010/101] - "Bf56a8aR-20743f8D-Vf7a11fD-d7c6c0.part09.rar" yEnc
		//[1/9] - "fdbvgdfbdfb.part.par2" yEnc
		if (preg_match('/^\[\d+(\/\d+\] - "(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//[LB] - [063/112] - "RVL-GISSFBD.part063.rar" yEnc
		if (preg_match('/^(\[[A-Z]+\] - \[)\d+\/\d+\] - "(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//(¯`*.¸(PWP).*´¯) (071/100) "JUST4US_025.part070.rar" - 22,50 GB  yEnc
		if (preg_match('/^\(.+?\(PWP\).+?\) \(\d+(\/\d+\) "(.+?))' . $this->e0 . ' .+? \d+[,.]\d+ [mMkKgG][bB] .+ yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//thnx to original poster [00/98] - "2669DFKKFD2008.nzb ` 2669DFKKFD2008 " yEnc
		if (preg_match('/^thnx to original poster \[\d+(\/\d+\] - "(.+?))(\.part\d*|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4})("| `).+? yEnc$/', $this->subject, $match)) {
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
			return $match[2] . $match[3] . $match[4];
		} //[scnzbefnet] Murdoch.Mysteries.S07E09.HDTV.x264-KILLERS [1/20] - "murdoch.mysteries.s07e09.hdtv.x264-killers.r13" yEnc
		if (preg_match('/^\[scnzbefnet\] (.+?) \[\d+\/(\d+\]) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(Ancient.Aliens.S03E05.Aliens.and.Mysterious.Rituals.720p.HDTV.x264.AC3.2Ch.REPOST) [41/42] - "Ancient.Aliens.S03E05.Aliens.and.Mysterious.Rituals.720p.HDTV.x264.AC3.2Ch.REPOST.vol071+66.PAR2" yEnc
		if (preg_match('/^(\((.+?)\) \[)\d+(\/\d+] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //Doobz Europa_Universalis_IV_Conquest_of_Paradise-FLT [10/54] - "flt-eucp.001" yEnc
		if (preg_match('/^Doobz ([a-zA-z-_]+) \[\d+\/(\d+\]) - ".+' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Outlast.Whistleblower-RELOADED - [014/119] - "rld-outwhistle.part001.rar" yEnc
		if (preg_match('/^([\w. -]{8,}) - \[\d+\/(\d+\])[- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Charlies.Angels.2000.iNTERNAL.DVDRip.XviD-Vmr.part44.rar [46/66] - "Charlies.Angels.2000.iNTERNAL.DVDRip.XviD-Vmr.part44.rar" yEnc
		if (preg_match('/^.+\[\d+\/(\d+\]) - "(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.british.drama
	protected function british_drama()
	{
		//Coronation Street 03.05.2012 [XviD] [01/23] - "coronation.street.03.05.12.[ws.pdtv].par2" yEnc
		//Coronation Street 04.05.2012 - Part 1 [XviD] [01/23] - "coronation.street.04.05.12.part.1.[ws.pdtv].par2" yEnc
		if (preg_match('/^([a-zA-Z0-9].+? \[XviD\] \[)\d\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//The Prisoner E06-09 [001/152] - "06 The General.mkv.001" yEnc
		//Danger Man S2E05-08 [075/149] - "7.The colonel's daughter.avi.001" yEnc
		if (preg_match('/^([a-zA-Z0-9]+ .+? (S\d+)?E\d+-\d\d \[)\d+\/\d+\] - "\d(\d |\.).+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Wizards Vs Aliens - 1x06 - Rebel Magic, Part Two [XviD][00/27] - "wizards.vs.aliens.106.rebel.magic.part.two.[ws.pdtv].nzb" yEnc
		if (preg_match('/^.+?\[\d+\/(\d+\][-_ ]{0,3}.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //Vera.3x03.Young.Gods.720p.HDTV.x264-FoV - "vera.3x03.young_gods.720p_hdtv_x264-fov.r00" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.cats
	protected function cats()
	{
		//Pb7cvL3YiiOu06dsYPzEfpSvvTul[02/37] - "Fkq33mlTVyHHJLm0gJNU.par2" yEnc
		//DLJorQ37rMDvc [01/16] - "DLJorQ37rMDvc.part1.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9]{5,} ?\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.cbt
	protected function cbt()
	{
		//(WinEdt.v8.0.Build.20130513.Cracked-EAT) [01/10] - "eatwedt8.nfo" yEnc
		if (preg_match('/^\(([a-zA-Z0-9-\.\&_ ]+)\) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[ ABCAsiaPacific.com - Study English IELTS Preparation (2006) ] AVI.PDF (17/34) - "abcap-senglishielts.r16" yEnc
		//[ Ask Video - The Studio Edge 101 Planning a Recording Studio ] MP4.x264 (00/21) - "syn-avtse101.nzb" yEnc
		//[ Brian Tracy and Colin Rose - Accelerated Learning Techniques (2003) ] MP3.PDF (00/14) - "btcr-accltech.nzb" yEnc
		//[ Lynda.com - Advanced Modeling in Revit Architecture (2012) ] DVD.ISO (41/53) - "i-lcamira.r38" yEnc
		//[ Morgan Kaufmann - Database Design Know It All (2008) ] TRUE.PDF (0/5) - "Morgan.Kaufmann.Database.Design.Know.It.All.Nov.2008.eBook-DDU.nzb" yEnc
		//[ VertexPusher - Vol. 2 Lighting, Shading and Rendering (2012) ] MP4.x264 (05/20) - "vp-c4dlsar.r03" yEnc
		if (preg_match('/^\[ ([a-zA-Z0-9-\.\&\(\)\,_ ]+) \] [a-zA-Z0-9]{3,4}\.[a-zA-Z0-9]{3,4} \(\d+\/(\d+\)) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.cbts
	protected function cbts()
	{
		//"softWoRx.Suite.2.0.0.25.x32-TFT.rar" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.cd.image
	protected function cd_image()
	{
		//[27849]-[altbinEFNet]-[Full]- "ppt-sogz.001" - 7,62 GB - yEnc
		//[27925]--[altbinEFNet]-[Full]- "unl_p2rd.par2" yEnc
		//[27608]-[FULL]-[#altbin@EFNet]-[007/136] "27608-1.005" yEnc
		if (preg_match('/^(\[\d+\]-+\[.+?\]-\[.+?\]-)(\[\d+\/\d+\])? ".+?"( - \d+[,.]\d+ [mMkKgG][bB] -)? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[27930]-[FULL]-[altbinEFNet]-[ Ubersoldier.UNCUT.PATCH-RELOADED ]-[3/5] "rld-usuc.par2" yEnc
		//[27607]-[#altbin@EFNet]-[Full]-[ Cars.Radiator.Springs.Adventure.READNFO-CRIME ] - [02/49] - "crm-crsa.par2" yEnc
		if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[ .+? \] ?- ?\[)\d+\/\d+\] (- )?".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[27575]-[#altbin@EFNet]-[Full]-[CD1]-[01/58] - "CD1.par2" yEnc
		//[27575]-[altbinEFNet]-[Full]-[CD3]-[00/59] - "dev-gk3c.sfv" yEnc
		if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[.+?\]-\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(27608-1) [2/5] - "skidrow.nfo" yEnc
		if (preg_match('/^(\(\d+(-\d+)?\) \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[www.drlecter.tk]-[The_Night_of_the_Rabbit-FLT]-[01/67] "Dr.Lecter.nfo" - 5.61 GB - yEnc
		if (preg_match('/^(\[www\..+?\]-\[.+?\]-\[)\d+\/\d+\] ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Slender.The.Arrival-WaLMaRT.PC - [01/26] - "wmt-stal.nfo" - yEnc
		//The.Night.of.the.Rabbit-FLT - [03/66] - "flt-notr.r00" - FAiRLIGHT - 5,10 GB - yEnc
		//Resident.Evil.Revelations-FLT - PC GAME - [03/97] - "Resident.Evil.Revelations-FLT.r00" - FAiRLIGHT - yEnc
		//Afterfall.Insanity.Dirty.Arena.Edition-WaLMaRT - [MULTI6][PCDVD] - [02/45] - "wmt-adae.r00" - PC GAME - yEnc
		if (preg_match('/^([a-zA-Z0-9.-]{10,} -( PC GAME -| [A-Z0-9\[\]]+ -)? \[)\d+\/\d+\] - ".+?" - (.+? - (\d+[,.]\d+ [mMkKgG][bB] - )?)?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[01/46] - Crashtime 5 Undercover RELOADED - "rld-ct5u.nfo" - PC - yEnc
		//[01/76] - Of.Orcs.And.Men-SKIDROW - "sr-oforcsandmen.nfo" - yEnc
		//PC Game - [01/71] - MotoGP 13-RELOADED Including NoDVD Fix - "MotoGP 13-RELOADED Including NoDVD Fix nfo" - yEnc
		if (preg_match('/^(PC Game - )?\[\d+(\/\d+\] - .+? - ").+?" -( .+? -)? yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//Magrunner Dark Pulse FLT (FAiRLIGHT) - [02/70] - "flt-madp par2" - PC - yEnc
		//MotoGP 13 RELOADED - [01/71] - "rld-motogp13 nfo" - PC - yEnc
		//Dracula 4: Shadow of the Dragon FAiRLIGHT - [01/36] - "flt-drc4 nfo" - PC - yEnc
		if (preg_match('/^([A-Za-z0-9][a-zA-Z0-9: ]{8,}(-[a-zA-Z]+)?( \(.+?\)| - [\[A-Z0-9\]]+)? - \[)\d+\/\d+\] - ".+?" - .+? - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[NEW PC GAME] - Lumber.island-WaLMaRT - "wmt-lisd.nfo" - [01/18] - yEnc
		//Trine.2.Complete.Story-SKIDROW - "sr-trine2completestory.nfo" - [01/78] - yEnc
		if (preg_match('/^((\[[A-Z ]+\] - )?[a-zA-Z0-9.-]{10,} - ").+?" - \[\d+\/\d+\] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Uploader.Presents-Need.For.Speed.Rivals.XBOX360-PROTOCOL[10/94]"nfs.r-ptc.r07" yEnc
		if (preg_match('/^(Uploader.Presents)[- ](.+?)[\(\[]\d+\/\d+\]".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.cd.lossless
	protected function cd_lossless()
	{
		//Flac Flood - Modern Talking - China In Her Eyes (CDM) - "1 - Modern Talking - China In Her Eyes (feat. Eric Singleton) (Video Version).flac" (01/14) (23,64 MB)   136,66 MB yEnc
		//Flac Flood Modern Talking - America - "1 - Modern Talking - Win The Race.flac" (01/18) (29,12 MB) 549,35 MB yEnc
		if (preg_match('/^(Flac Flood( -)? .+? - ").+?" \(\d+\/\d+\) .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Cannonball Adderley - Nippon Soul [01/17] "00 - Cannonball Adderley - Nippon Soul.nfo" yEnc
		//Black Tie White Noise [01/24] - "00 - David Bowie - Black Tie White Noise.nfo" yEnc
		if (preg_match('/^([a-zA-Z0-9].+? \[)\d+\/\d+\]( -)? "\d{2,} - .+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[1977] Joan Armatrading - Show Some Emotion - File 15 of 20: 06 Joan Armatrading - Opportunity.flac yEnc
		//The Allman Brothers Band - Statesboro Blues [Swingin' Pig - Bootleg] [1970 April 4] - File 09 of 19: Statesboro Blues.cue yEnc
		if (preg_match('/^((\[\d{4}\] )?[a-zA-Z0-9].+? - File )\d+ of \d+: .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //The Allman Brothers Band - The Fillmore Concerts [1971] - 06 The Allman Brothers Band - Done Somebody Wrong.flac yEnc
		if (preg_match('/^([A-Z0-9].+? - [A-z0-9].+? \[\d{4}\] - )\d{2,} .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //The Velvet Underground - Peel Slow And See (Box Set) Disc 5 of 5 - 13 The Velvet Underground - Oh Gin.flac yEnc
		if (preg_match('/^([A-Z0-9].+? - [A-Z0-9].+? Disc \d+ of \d+ - )[A-Z0-9].+?\..+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(28/55) "Ivan Neville - If My Ancestors Could See Me Now.par2" - 624,44 MB - yEnc
		if (preg_match('/^\(\d+(\/\d+\) ".+?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.chello
	protected function chello()
	{
		//0F623Uv71RHKt0jzA7inbGZLk00[2/5] - "l2iOkRvy80bgLrZm1xxw.par2" yEnc
		//GMC2G8KixJKy [01/15] - "GMC2G8KixJKy.part1.rar" yEnc
		if (preg_match('/^([A-Za-z0-9]{5,} ?\[)\d+\/\d+\] - "[A-Za-z0-9]{3,}.+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Imactools.Cefipx.v3.20.MacOSX.Incl.Keyfilemaker-NOY [03/10] - "parfile.vol000+01.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9][a-zA-Z0-9.-]+ \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Siberian Mouses LS, BD models and special... [150/152] - "Xlola - Luba, Sasha & Vika.avi.jpg" yEnc
		if (preg_match('/^([A-Za-z0-9-]+ .+?[. ]\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.classic.tv.shows
	protected function classic_tv_shows()
	{
		//Re: REQ: All In The Family - "Archie Bunkers Place 1x01 Archies New Partner part 1.nzb" yEnc
		if (preg_match('/^(Re: REQ: .+? - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Per REQ - "The.Wild.Wild.West.S03E11.The.Night.of.the.Cut-Throats.DVDRip.XVID-tz.par2" 512x384 [01/40] yEnc
		if (preg_match('/^(Per REQ - ".+?)' . $this->e0 . ' .+? \[\d+\/\d+\] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //By req: "Dennis The Menace - 4x25 - Dennis and the Homing Pigeons.part05.rar" yEnc
		if (preg_match('/^(By req: ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //I Spy HQ DVDRips "I Spy - 3x26 Pinwheel.part10.rar" [13/22] yEnc
		if (preg_match('/^([a-zA-Z ]+HQ DVDRips ".+?)' . $this->e0 . ' \[\d+\/\d+\] yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Sledge Hammer! S2D2 [016/138] - "SH! S2 D2.ISO.016" yEnc
		//Sledge Hammer! S2D2 [113/138] - "SH! S2 D2.ISO.1132 yEnc
		//Lost In Space - Season 1 - [13/40] - "S1E02 - The Derelict.avi" yEnc
		if (preg_match('/^([a-zA-Z0-9].+? (S\d+D\d+|- Season \d+ -) \[)\d+\/\d+\] - ".+?"? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Night Flight TV Show rec 1991-01-12 (02/54) - "night flight rec 1991-01-12.nfo" yEnc
		//Night Flight TV Show rec 1991-05-05 [NEW PAR SET] (1/9) - "night flight rec 1991-05-05.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9].+? \d{4}-\d\d-\d\d( \[.+?\])? \()\d+\/\d+\) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //The.Love.Boat.S05E08 [01/31] - "The.Love.Boat.S05E08.Chefs.Special.Kleinschmidt.New.Beginnings.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9][a-zA-Z0-9.-]+ \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Batman - S1E13-The Thirteenth Hat.par2" yEnc
		if (preg_match('/^(".+?)(\.part\d*|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Re: Outside Edge series 1 - [01/20] - "Outside Edge S01.nfo" yEnc
		if (preg_match('/^(Re: )?([a-zA-Z0-9]+ .+? series \d+ - \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//'Mission: Impossible' - 1x09 - NTSC - DivX - 28 of 48 - "MI-S01E09.r23" yEnc
		//'Mission: Impossible' - 1x09 - NTSC - DivX - 01 of 48 - "MI-S01E09.nfo" (1/1)
		if (preg_match('/^([a-zA-Z0-9 -_\.:]+) - \d+( of \d+)[-_ ]{0,3}".+?' . $this->e0 . ' (\(\d+\/\d+\) )?(yEnc)?$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//"Batman - S2E58-Ice Spy.par2"yEnc
		//"Black Sheep Squadron 1x03 Best Three Out of Five.par2"
		if (preg_match('/^"(.+?)' . $this->e0 . '(yEnc)?( )?$/', $this->subject, $match)) {
			return $match[1];
		} //"Guns of Will Sonnett - 1x04.mp4" (Not My Rip)Guns Of Will Sonnett Season 1 1 - 26 Mp4 With Pars yEnc
		if (preg_match('/^"(.+?)' . $this->e0 . ' \(Not My Rip\).+ \d+ (- \d+) .+ yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		} //(01/10) "Watch_With_Mother-Bill_And_Ben-1953_02_12-Scarecrow-VHSRip-XviD.avi" - 162.20 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\) ".+?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(Our Gang - Little Rascals  DVDRips)  "Our Gang -  The Lucky Corner (1936).part0.sfv" [01/19] yEnc
		//(Our Gang - Little Rascals  DVDRips)  "Our Gang -  Wild Poses (1933).part.par" [02/20] Last One I Have! yEnc
		if (preg_match('/^\(.+\)  "(.+?)' . $this->e0 . ' \[\d+\/(\d+\]) (Last One I Have! )?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		} //[EnJoY] =>A Blast from Usenet Past (1/3)<= [00/14] - "Mcdonalds Training Film - 1972 (Vhs-Mpg).part.nzb" yEnc
		if (preg_match('/^.+ Usenet Past .+\[\d+\/(\d+\]) - "(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //<OPA_TV> [01/12] - "Yancy Derringer - 03 - Geheime Fracht.par2" yEnc
		if (preg_match('/^<OPA_TV> \[\d+\/(\d+\]) - "(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//77 Sunset Strip 409 [1 of 23] "77 Sunset Strip 409 The Missing Daddy Caper.avi.vol63+34.par2" yEnc
		//Barney.Miller.NZBs [001/170] - "Barney.Miller.S01E01.Ramon.nzb" yEnc
		if (preg_match('/^.+ [\[\(]\d+( of |\/)(\d+[\]\)])[-_ ]{0,3}"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[2] . $match[3];
		}
		//All in the Family - missing eps - DVDRips  "All in the Family - 6x23 Gloria & Mike's House Guests.part5.rar" [08/16] yEnc
		//Amos 'n' Andy - more shows---read info.txt  "Amos 'n' Andy S01E00 Introduction of the Cast.mkv.001" (002/773) yEnc
		if (preg_match('/^.+[-_ ]{0,3}"(.+?)' . $this->e0 . ' [\[\(]\d+\/(\d+[\]\)]) yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		} //Andy Griffith Show,The   1x05....Irresistible Andy - (DVD).part04.rar
		if (preg_match('/^(.+\d+x\d+.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})( yEnc)?( (Series|Season) Finale)?$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.comp
	protected function comp()
	{
		//Sims3blokjesremover [0/0] -3162   "Sims3blokjesremover.nzb" yEnc
		//xSIMS_The_Sims_3_Censor_Remover_v2.91
		if (preg_match('/^([\w.]+\s+\[)\d+\/\d+\] -\d+\s+".+?" yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //Photo Mechanic 5.0 build 13915  (1/6) "Photo Mechanic 5.0 build 13915  (1).par2" - 32,97 MB - yEnc
		if (preg_match('/^([a-zA-Z0-9].+?\s+\()\d+\/\d+\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(45/74) NikJosuf post Magento tutorials "43 - Theming Magento 19 - Adding a Responsive Slideshow.mp4" yEnc
		if (preg_match('/^\(\d+(\/\d+\) .+? post .+? ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.cores
	protected function cores()
	{
		//Film - [13/59] - "Jerry Maguire (1996) 1080p DTS multisub HUN HighCode-PHD.part13.rar" yEnc
		//Film - "Phone.booth.2003.RERIP.Bluray.1080p.DTS-HD.x264-Grym.part001.rar" yEnc
		if (preg_match('/^Film - \[\d+\/(\d+\] - )?"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[Art-Of-Use.Net] :: [AUTO] :: - [34/36] - "ImmoralLive.13.11.10.Immoral.Orgies.Rikki.Six.Carmen.Callaway.And.Amanda.Tate.XXX.1080p.MP4-KTR.vol15+16.par2" yEnc
		if (preg_match('/^\[Art-Of-Use\.Net\] :: \[.+?\] :: - \[\d+\/(\d+\][-_ ]{0,3}"(.+?))' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //brothers-of-usenet.info.net Partner von---- SSL-News.info-----  brothers-of-usenet.info.net Partner von---- SSL-News.info-----AVG.Internet.Security.2014.Build.4335.x86.x64 - "AVG.Internet.Security.2014.Build.4335.vol00+01.PAR2" - 315,68 MB - yEnc
		if (preg_match('/^brothers-of-usenet\.info\.net.+SSL-News\.info-----(.+) - "(.+?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		} //>GOU<< ZDF.History.Das.Geiseldrama.von.Gladbeck.GERMAN.DOKU.720p.HDTV.x264-TVP >>www.SSL-News.info< - (02/35) - "tvp-gladbeck-720p.nfo" yEnc
		if (preg_match('/^>+GOU<+ (.+?) >+www\..+?<+ - \(\d+\/\d+\) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<usenet-space-cowboys.info>>> USC <<<Powered by https://secretusenet.com>>> [22/26] - "Zombie.Tycoon.2.Brainhovs.Revenge-SKIDROW.vol00+1.par2" - 1,85 GB yEnc
		if (preg_match('/^<<<usenet-space-cowboys\.info.+secretusenet\.com>>> \[\d+\/(\d+\] - ".+?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		}
		//Jipejans post voor u op www.Dreamplace.biz - [010/568] - "Alien-Antology-DC-Special-Edition-1979-1997-1080p-GER-HUN-HighCode.part009.rar" yEnc
		//Egbert47 post voor u op www.nzbworld.me - [01/21] - "100 Hits - Lady Sings The Blues 2006 (5cd's).par2" yEnc
		if (preg_match('/^[a-zA-Z0-9]+ post voor u op www\..+? - \[\d+\/\d+\] - "(.+?)' . $this->e1, $this->subject, $match)) {
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
		} //[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ MOVIE ] [14/19] - "Night.Vision.2011.DVDRip.x264-IGUANA.part12.rar" - 660,80 MB yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}\[ .* \] \[\d+\/(\d+\][ _-]{0,3}("|#34;).+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ] [01/28] - "Shadowrun_Returns_MULTi4-iNLAWS.par2" - 1,11 GB yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ partner of www\.ssl-news\.info \] \[\d+\/(\d+\][ _-]{0,3}("|#34;).+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //<kere.ws> - FLAC - 1330543524 - Keziah_Jones-Femiliarise-PROMO_CDS-FLAC-2003-oNePiEcE - [01/11] - "00-keziah_jones-femiliarise-promo_cds-flac-2003-1.jpg" yEnc
		if (preg_match('/^<kere\.ws>[ _-]{0,3}\w+(-\w+)?[ _-]{0,3}\d+[ _-]{0,3}(.+) - \[\d+\/\d+\][ _-]{0,3}("|#34;).+?("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ] [26/26] - "Legally.Brown.S01E06.HDTV.x264-BWB.vol7+4.par2" - 332,80 MB yEnc
		if (preg_match('/.*[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})[-_ ]{0,3}(\d+[.,]\d+ [kKmMgG][bB](ytes)?)? yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Doobz Europa_Universalis_IV_Conquest_of_Paradise-FLT [10/54] - "flt-eucp.001" yEnc
		if (preg_match('/^Doobz ([a-zA-z-_]+) \[\d+\/(\d+\]) - ".+' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[01/10] - "Wondershare.Video.Converter.Ultimate.v6.7.1.0.Multilanguage.par2" - 45,44 MB yEnc
		if (preg_match('/^\[\d+\/(\d+\] - ".+?)' . $this->e0 . '.+yEnc$/i', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	protected function pictures_erotica_anime()
	{
		// pictures.erotica.anime has really only header we care about in the form
		// [ABPEA - Original] Arima Zin - Tennen Koiiro Alcohol [BB, Boy] - 005 of 229 - yEnc "Tennen_Koiiro_Alcohol-005.jpg"
		// in this case we only need the title of the manga, not which image we are viewing or the post file name
		if (preg_match('/(.*)\s+-\s+\d+\s+of\s+\d+\s+-\s+yEnc\s+".*"/i', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.console.ps3
	protected function console_ps3()
	{
		//[4062]-[ABGX.net] - "unlimited-skyrim.legendary.multi4.ps3.par2" - 17.10 GB - yEnc
		if (preg_match('/^(\[\d+\]-\[ABGX\.(net|NET)\] - ").+?(" - \d+[,.]\d+ [kKmMgG][bB] - )yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[4017]-[abgx]- "duplex.nfo" yEnc
		if (preg_match('/^(\[\d+\]-\[abgx\] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[4197] [036/103] - "ant-mgstlcd2.r34" yEnc
		if (preg_match('/^(\[\d+\] \[\d+\/\d+\] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Musou_Orochi_Z_JPN_PS3-JPMORGAN [62/62] - "jpmorgan.nfo" yEnc
		if (preg_match('/([A-Z0-9]\w{10,}-?PS3-[a-zA-Z0-9]+ \[)\d+\/\d+\] - ".+?" $/', $this->subject, $match)) {
			return $match[1];
		}
		//"Armored_Core_V_PS3-ANTiDOTE__www.realmom.info__.r00" (03/78) 3,32 GB yEnc
		if (preg_match('/^"(.+)__www.realmom.info__.+" \(\d+\/(\d+\)) \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//"Ace.Combat.Assault.Horizon.PS3-DUPLEX__www.realmom.info__.nfo"  7,14 GB yEnc
		if (preg_match('/^"(.+)__www.realmom.info__.+"  (\d+[.,]\d+ [kKmMgG][bB]) yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.country.mp3
	protected function country_mp3()
	{
		//Attn: wulf109 - Jim Reeves - There's Someone Who Loves You - 01 - Anna Marie.mp3 yEnc
		//Attn: wulf109 - Jim Reeves - There's Someone Who Loves You - Front.jpg yEnc
		if (preg_match('/^(Attn: .+? - .+? - .+? - )(\d+ - )?.+?\.[A-Za-z0-9]{2,4} yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Jo Dee Messina - A Joyful Noise    "01 - Winter Wonderland.mp3" yEnc
		//Karen Lynne - 2000 - Six Days in December   "Pat Drummond and Karen Lynne - 01 - The Rush.mp3" yEnc
		if (preg_match('/^([A-Z0-9].{3,} -( (19|20)\d\d - )?[A-Z0-9].{3,}\s+")[A-Z0-9].{3,} - \d+ - [A-Z0-9].+?\.[A-Za-z0-9]{2,4}" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//"Heather Myles - Highways and Honky Tonks - 05 - True Love.mp3" yEnc
		//"Reba McEntire - The Secret Of Giving - A Christmas Collection - 09 - This Christmas.mp3" yEnc
		//]"Heather Myles - Highways and Honky Tonks - 05 - True Love.mp3" yEnc
		//"Reba McEntire - Moments & Memories - The Best Of Reba - Australian-back.jpg" yEnc
		//"Reba McEntire - The Secret Of Giving - A Christmas Collection - 01 - This Is My Prayer For You.mp3" yEnc
		//"Reba McEntire - American Legends-Best Of The Early Years - 02 - You Really Better Love Me After This.Mp3" yEnc
		if (preg_match('/^((\]?"[A-Z0-9].{3,} - )+?([A-Z0-9].{3,}? - )+?)(\d\d - )?[a-zA-Z0-9].+?\.[A-Za-z0-9]{2,4}" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//"Reba McEntire - Duets.m3u" yEnc
		//"Reba McEntire - Greatest Hits Volume Two - back.jpg" yEnc
		//"Reba McEntire - American Legends-Best Of The Early Years.m3u" yEnc
		//"Jason Allen - 00 - nfo.txt" yEnc
		//"Sean Ofarrell-Life Is A Teacher -  07 - Here Again.MP3" yEnc
		if (preg_match('/^("[A-Z0-9].{3,}? - )(([A-Z0-9][^-]{3,}?|\s*\d\d) - )?[a-zA-Z0-9].{2,}?\.[A-Za-z0-9]{2,4}?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //]"Heather_Myles_-_Highways_And_Honky_Tonks-back.jpg" yEnc
		if (preg_match('/^(\]"[\w-]{10,}?)-[a-zA-Z0-9]+\.[a-zA-Z0-9]{2,4}" yEnc$/', $this->subject, $match)) {
			return $match[1] . ' - ';
		} //Merv & Maria - Chasing Rainbows  Merv & Maria - 01 - Sowin' Love.mp3" yEnc
		if (preg_match('/^([A-Z0-9].{3,}? - [A-Z0-9].{3,}? - )\d\d - [a-zA-Z0-9].{2,}?\.[A-Za-z0-9]{2,4}?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.dc
	protected function dc()
	{
		//Eragon postet    Horror     S01 E01   german Sub    [02/22] - "kopiert neu.par2" yEnc
		//Eragon postet   Rapunzel  S02E12   german Sub  hardcodet   [02/18] - "Rapunzel S02E12 HDTV x264-LOL ger subbed.par2" yEnc
		if (preg_match('/^([A-Z0-9].+? postet\s+.+?\s+\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.documentaries
	protected function documentaries()
	{
		//#sterntuary - Alex Jones Radio Show - "05-03-2009_INFO_BAK_ALJ.nfo" yEnc
		if (preg_match('/^(#sterntuary - .+? - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//(08/25) "Wild Russia 5 of 6 The Secret Forest 2009.part06.rar" - 47.68 MB - 771.18 MB - yEnc
		//(01/24) "ITV Wild Britain With Ray Mears 1 of 6 Deciduous Forest 2011.nfo" - 4.34 kB - 770.97 MB - yEnc
		//(24/24) "BBC Great British Garden Revival 03 of 10 Cottage Gardens And House Plants 2013.vol27+22.PAR2" - 48.39 MB - 808.88 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "((BBC|ITV) )?(.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|") - \d.+? - (\d.+? -)? yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //(World Air Routes - WESTJET - B737-700) [028/109] - "World Air Routes - WESTJET - B737-700.part027.rar" yEnc
		if (preg_match('/^.+?\[\d+\/(\d+\][-_ ]{0,3}.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //Beyond Vanilla (2010) Documentary DVDrip XviD-Uncut - (02/22) "Beyond.Vanilla.2010.Documentary.DVDrip.XviD-Uncut.par2" - yenc yEnc
		if (preg_match('/.*[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //Rough Cut - Woodworking with Tommy Mac - Pilgrim Blanket Chest (1600s) DVDrip DivX - (02-17) "Rough.Cut-Woodworking.with.Tommy.Mac-Pilgrim.Blanket.Chest.1600s-DVDrip.DivX.2010.par2" - yEnc yEnc
		if (preg_match('/.*[\(\[]\d+-(\d+[\)\]])[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}("|#34;)).+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		}
		//Asia This Week (NHK World, 19 & 20 July 2013) - 'Malala's movement for girls' education + Japan seeks imports from Southeast Asia - soccer players' - (02|14) - "ATW-2013-07-20.par2" yEnc
		//Asia Biz Forecast (NHK World, 6 & 7 July 2013) - 'China: limits of growth + Japan: remote access' - (05|14) - "ABF-2013-07-07.part3.rar" yEnc
		if (preg_match('/(.+) - [\(\[]\d+(\|\d+[\)\]])[-_ ]{0,3}("|#34;).+?(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}("|#34;)).+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Asia Biz Forecast (NHK World, 16-17 June 2012) - "Japan seeks energy options" - File 01 of 14  - ABF-2012-06-16.nfo  (yEnc
		if (preg_match('/(.+) - File \d+ of (\d+)[-_ ]{0,3}.+?(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}).+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Dark MatterDark Energy S02E06 - "Dark Matter_Dark Energy S02E06 - The Universe - History Channel.part1.rar"  51.0 MBytes yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")  (\d+[,.]\d+ [kKmMgG][bB]ytes) yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //(35/45) - Keating Pt4 - "Keating Pt4.part34.rar" - 1.77 GB - yEnc
		if (preg_match('/\(\d+\/(\d+\) - .+) - "(.+?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.downunder
	protected function downunder()
	{
		//RWlgVffClWxD0vXT1peIwb9DubTLMiYm3nvD1aMMDe[04/16] - "A9jFik7Fk4hCG4GWuxAg.r02" yEnc
		if (preg_match('/^([a-zA-Z0-9]{5,}\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.dvd
	protected function dvd()
	{
		//thnx to original poster [00/98] - "2669DFKKFD2008.nzb ` 2669DFKKFD2008 " yEnc
		if (preg_match('/^(thnx to original poster )?\[\d+(\/\d+\] - ".+?)(\.part\d*|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4})("| `).* yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		return $this->generic();
	}

	// a.b.dvd-r
	protected function dvd_r()
	{
		//katanxya "katanxya7221.par2" yEnc
		if (preg_match('/^katanxya "katanxya\d+/', $this->subject, $match)) {
			return $match[0];
		} //[01/52] - "H1F3E_20130715_005.par2" - 4.59 GB yEnc
		if (preg_match('/^\[\d+\/\d+\] - "([A-Z0-9](19|20)\d\d[01]\d[123]\d_\d+\.).+?" - \d+[,.]\d+ [mMkKgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	//a.b.ebook
	protected function ebook()
	{
		//New eBooks 8 June 2013 - "Melody Carlson - [Carter House Girls 08] - Last Dance (mobi).rar"
		if (preg_match('/^New eBooks.+[ _-]{0,3}("|#34;)(.+?.+)\.(par|vol|rar|nfo).*?("|#34;)/i', $this->subject, $match)) {
			return $match[2];
		}
		//(Nora Roberts)"Black Rose - Nora Roberts.epub" yEnc
		//Rowwendees post voor u op www.nzbworld.me - [0/6] - "Animaniacs - Lights, Camera, Action!.nzb" yEnc (1/1)
		if (preg_match('/www.nzbworld.me - \[\d+\/(\d+\] - ".+?)' . $this->e0 . ' yEnc/', $this->subject, $match)) {
			return $match[1];
		}
		if (preg_match('/^\(Nora Roberts\)"(.+?)\.(epub|mobi|html|pdf|azw)" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//<TOWN><www.town.ag > <download all our files with>>>  www.ssl-news.info <<< > [02/19] - "2013.AUG.non-fiction.NEW.releases.part.1.(PDF)-MiMESiS.part01.rar" - 1,31 GB yEnc
		//<TOWN><www.town.ag > <partner of www.ssl-news.info > [3/3] - "Career.Secrets.Exposed.by.Gavin.F..Redelman_.RedStarResume.vol0+1.par2" - 8,16 MB yEnc
		if (preg_match('/town\.ag.+?(download all our files with|partner of).+?www\..+?\.info.+? \[\d+\/(\d+\] - ".+?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(Zelazny works) [36/39] - "Roger Zelazny - The Furies.mobi" yEnc
		if (preg_match('/\((.+works)\) \[\d+\/(\d+\]) - "(.+?)\.(mobi|pdf|epub|html|azw)" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2] . $match[3];
		} //(Joan D Vinge sampler) [17/17] - "Joan D Vinge - World's End.txt" yEnc
		if (preg_match('/^\([a-zA-Z ]+ sampler\) \[\d+(\/\d+\]) - "(.+?)\.(txt|pdf|mobi|epub|azw)" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//New - Retail - Juvenile Fiction - "Magic Tree House #47_ Abe Lincoln at Last! - Mary Pope Osborne & Sal Murdocca.epub" yEnc
		//New - Retail - "Linda Howard - Cover of Night.epub" yEnc
		//New - Retail - "Kylie Logan_Button Box Mystery 01 - Button Holed.epub" yEnc
		if (preg_match('/^New - Retail -( Juvenile Fiction -)? "(.+?)\.(txt|pdf|mobi|epub|azw)" yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //(No. 1 Ladies Detective Agency) [04/13] - "Alexander McCall Smith - No 1-12 - The Saturday Big Tent Wedding Party.mobi" yEnc
		if (preg_match('/^\(No\. 1 Ladies Detective Agency\) \[\d+(\/\d+\]) - "(.+?)\.(txt|pdf|mobi|epub|azw)" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[25/33] Philip Jose Farmer - Toward the Beloved City [ss].mobi
		//[2/4] Graham Masterton - Descendant.mobi
		if (preg_match('/^\[\d+\/(\d+\]) (.+?)\.(txt|pdf|mobi|epub|azw)/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//(NordicAlbino) [01/10] - "SWHQ_NA_675qe0033102suSmzSE.sfv" yEnc
		//365 Sex Positions A New Way Every Day for a Steamy Erotic Year [eBook] - (1/5) "365.Sex.Positions.A.New.Way.Every.Day.for.a.Steamy.Erotic.Year.eBook.nfo" - yenc yEnc
		if (preg_match('/(.+)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]][-_ ]{0,3}".+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[001/125] (NL Epub Wierook Set 49) - "Abulhawa, Susan - Litteken van David_Ochtend in Jenin.epub" yEnc
		if (preg_match('/^\[\d+\/(\d+\] .+?) - "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(1/1) "Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW.pdf" - 162,82 MB - (Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW) yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(1/7) "0865779767.epub" - 88,93 MB - "Anatomic Basis of Neurologic Diagnosis - epub" yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[5];
		}
		//Re: REQ: Jay Lake's Mainspring series/trilogy (see titles inside) - "Lake, Jay - Clockwork Earth 03 - Pinion [epub].rar"  405.6 kBytes yEnc
		//Attn: Brownian - "del Rey, Maria - Paradise Bay (FBS).rar" yEnc
		//New Scan "Herbert, James - Sepulchre (html).rar" yEnc
		if (preg_match('/^(Attn:|Re:|REQ:|New Scan).+?[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}(\d+[.,]\d+ [kKmMgG][bB](ytes)?)? yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//"Gabaldon, Diana - Outlander [5] The Fiery Cross.epub" yEnc
		//Kiny Friedman "Friedman, Kinky - Prisoner of Vandam Street_ A Novel, The.epub" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Patterson flood - Mobi -  15/45  "James Patterson - AC 13 - Double Cross.mobi"
		if (preg_match('/(.+?)[-_ ]{0,3}\d+\/(\d+[-_ ]{0,3}".+?)\.(txt|pdf|mobi|epub|azw)"( \(\d+\/\d+\))?( )?$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	//a.b.e-book
	protected function e_book()
	{
		//New eBooks 8 June 2013 - "Melody Carlson - [Carter House Girls 08] - Last Dance (mobi).rar"
		if (preg_match('/^New eBooks.+[ _-]{0,3}("|#34;)(.+?.+)\.(par|vol|rar|nfo).*?("|#34;)/i', $this->subject, $match)) {
			return $match[2];
		} //(Nora Roberts)"Black Rose - Nora Roberts.epub" yEnc
		if (preg_match('/^\(Nora Roberts\)"(.+?)\.(epub|mobi|html|pdf|azw)" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //<TOWN><www.town.ag > <download all our files with>>>  www.ssl-news.info <<< > [02/19] - "2013.AUG.non-fiction.NEW.releases.part.1.(PDF)-MiMESiS.part01.rar" - 1,31 GB yEnc
		if (preg_match('/town\.ag.+?download all our files with.+?www\..+?\.info.+? \[\d+(\/\d+\] - ".+?)(-sample)?' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Doctor Who - Target Books [128/175] - "DW125_ Terror of the Vervoids - Pip Baker.mobi" yEnc
		if (preg_match('/^Doctor Who - Target Books \[\d+\/(\d+\]) - "DW[0-9]{0,3}[-_ ]{0,3}(.+?)\.(txt|pdf|mobi|epub|azw)" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(American Curves - Summer 2012) [01/10] - "AMECURSUM12.par2" yEnc
		if (preg_match('/^\(([a-zA-Z0-9 -]+)\) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//(NordicAlbino) [01/10] - "SWHQ_NA_675qe0033102suSmzSE.sfv" yEnc
		//365 Sex Positions A New Way Every Day for a Steamy Erotic Year [eBook] - (1/5) "365.Sex.Positions.A.New.Way.Every.Day.for.a.Steamy.Erotic.Year.eBook.nfo" - yenc yEnc
		if (preg_match('/(.+)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]][-_ ]{0,3}".+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[1/8] - "Robin Lane Fox - Travelling heroes.epub" yEnc
		//(1/1) "Unintended Consequences - John Ross.nzb" - 8.67 kB - yEnc
		if (preg_match('/^[\(\[]\d+\/(\d+[\)\]][-_ ]{0,3}".+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")([-_ ]{0,3}\d+[.,]\d+ [kKmMgG][bB])?[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ Mega Dating and Sex Advice Ebooks - Tips and Tricks for Men PDF ] - "Vatsyayana - The Kama Sutra.pdf.rar" - (54/58) yEnc
		if (preg_match('/^[\(\[] .+? [\)\][-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //WWII in Photos - "WWII in Photos_05_Conflict Spreads Around the Globe - The Atlantic.epub" yEnc
		if (preg_match('/^(WWII in Photos)[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Various ebooks on History pdf format  "Chelsea House Publishing Discovering U.S. History Vol. 8, World War I and the Roaring Twenties - 1914-1928 (2010).pdf"  [1 of 1] yEnc
		if (preg_match('/^.+?"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}\[\d+ of (\d+\])[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //A few things -  [4 of 13] "Man From U.N.C.L.E. 08 - The Monster Wheel Affair - David McDaniel.epub" yEnc
		if (preg_match('/.+[\(\[]\d+ of (\d+[\)\]] ".+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //DDR Kochbuch 1968-wir kochen gut [1/1] - "DDR Kochbuch 1968-wir kochen gut.pdf" toby042002
		if (preg_match('/.+[\(\[]\d+\/(\d+[\)\]] - ".+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") toby\d+$/', $this->subject, $match)) {
			return $match[1];
		} //Pottermore UK retail - "Harry Potter and the Goblet of Fire - J.K. Rowling.epub" (05/14) - 907.57 kB - yEnc
		if (preg_match('/^.+?[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") [\(\[]\d+\/(\d+[\)\]]) ([-_ ]{0,3}\d+[.,]\d+ [kKmMgG][bB])?[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //[001/125] (NL Epub Wierook Set 49) - "Abulhawa, Susan - Litteken van David_Ochtend in Jenin.epub" yEnc
		if (preg_match('/^\[\d+\/(\d+\] .+?) - "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(1/1) "Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW.pdf" - 162,82 MB - (Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW) yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(1/7) "0865779767.epub" - 88,93 MB - "Anatomic Basis of Neurologic Diagnosis - epub" yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[5];
		}
		//Re: REQ: Jay Lake's Mainspring series/trilogy (see titles inside) - "Lake, Jay - Clockwork Earth 03 - Pinion [epub].rar"  405.6 kBytes yEnc
		//Attn: Brownian - "del Rey, Maria - Paradise Bay (FBS).rar" yEnc
		//New Scan "Herbert, James - Sepulchre (html).rar" yEnc
		if (preg_match('/^(Attn:|Re:|REQ:|New Scan).+?[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}(\d+[.,]\d+ [kKmMgG][bB](ytes)?)? yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//"Gabaldon, Diana - Outlander [5] The Fiery Cross.epub" yEnc
		//Kiny Friedman "Friedman, Kinky - Prisoner of Vandam Street_ A Novel, The.epub" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Patterson flood - Mobi -  15/45  "James Patterson - AC 13 - Double Cross.mobi"
		if (preg_match('/(.+?)[-_ ]{0,3}\d+\/(\d+[-_ ]{0,3}".+?)\.(txt|pdf|mobi|epub|azw)"( \(\d+\/\d+\))?( )?$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //04/63  Brave New World Revisited - Aldous Huxley.mobi  yEnc
		if (preg_match('/\d+\/(\d+[-_ ]{0,3}.+)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //- Campbell, F.E. - Susan - HIT 125.rar  BDSM Themed Adult Erotica - M/F F/F - Rtf & Pdf
		if (preg_match('/^- (.+?)\.(par|vol|rar|nfo)[-_ ]{0,3}(.+)/', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //"D. F. Jones - 03 - Colossus and The Crab.epub" (1/3)
		if (preg_match('/^"(.+?)\.(txt|pdf|mobi|epub|azw)" \(\d+\/(\d+\))/', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //"D. F. Jones - 01 - Colossus.epub" (note the space on the end)
		if (preg_match('/^"(.+?)\.(txt|pdf|mobi|epub|azw|lit|rar|nfo|par)" $/', $this->subject, $match)) {
			return $match[1];
		} //[01/19] - "13_X_Panzer_Tracts_EBook.nfo " yEnc
		if (preg_match('/^\[\d*+\/(\d+\]) - "(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4} "|") yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[09/14] Sven Hassel - Legion of the Damned 09, Reign of Hell.mobi  sven hassel as requested (1/7) yEnc
		//[1/1] Alex Berenson - John Wells 05, The Secret Soldier.mobi (space at end)
		if (preg_match('/^\[\d+\/(\d+\]) (.+?)\.(txt|pdf|mobi|epub|azw|lit|rar|nfo|par).+?(yEnc)?$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[1/1] - "Die Kunst der Fotografie Lehrbuch und Bildband f  r ambitionierte Fotografen.rar"
		//[1/1] - "Demonic_ How the Liberal Mob Is Endanger - Coulter, Ann.mobi" (note space at end)
		//[1/1] - "Paris in Love_ A Memoir - Eloisa James.mobi"  1861K
		if (preg_match('/^\[\d+\/(\d+\]) - "(.+?)\.(txt|pdf|mobi|epub|azw|lit|rar|nfo|par)"(  \d+K)?( )?$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //002/240  Swordships.of.Scorpio.(Dray.Prescot).-.Alan.Burt.Akers.epub
		if (preg_match('/^\d+\/(\d+)[-_ ]{0,3}(.+?)\.(txt|pdf|mobi|epub|azw|lit|rar|nfo|par)$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Akers Alan Burt - Dray Prescot Saga 14 - Krozair von Kregen.rar yEnc
		if (preg_match('/^([a-zA-Z0-9. ].+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|) yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	//a.b.e-book.flood
	protected function e_book_flood()
	{
		//New eBooks 8 June 2013 - "Melody Carlson - [Carter House Girls 08] - Last Dance (mobi).rar"
		if (preg_match('/^New eBooks.+[ _-]{0,3}("|#34;)(.+?.+)\.(par|vol|rar|nfo).*?("|#34;)/i', $this->subject, $match)) {
			return $match[2];
		} //<TOWN><www.town.ag > <download all our files with>>>  www.ssl-news.info <<< > [02/19] - "2013.AUG.non-fiction.NEW.releases.part.1.(PDF)-MiMESiS.part01.rar" - 1,31 GB yEnc
		if (preg_match('/town\.ag.+?download all our files with.+?www\..+?\.info.+? \[\d+(\/\d+\] - ".+?)(-sample)?' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
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
			return $match[1] . $match[2];
		} //Patterson flood - Mobi -  15/45  "James Patterson - AC 13 - Double Cross.mobi"
		if (preg_match('/(.+?)[-_ ]{0,3}\d+\/(\d+[-_ ]{0,3}".+?)\.(txt|pdf|mobi|epub|azw)"( \(\d+\/\d+\))?( )?$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[001/125] (NL Epub Wierook Set 49) - "Abulhawa, Susan - Litteken van David_Ochtend in Jenin.epub" yEnc
		if (preg_match('/^\[\d+\/(\d+\] .+?) - "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(1/1) "Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW.pdf" - 162,82 MB - (Radiological Imaging of the Kidney - E. Quaia (Springer, 2011) WW) yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(1/7) "0865779767.epub" - 88,93 MB - "Anatomic Basis of Neurologic Diagnosis - epub" yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[5];
		}
		//Re: REQ: Jay Lake's Mainspring series/trilogy (see titles inside) - "Lake, Jay - Clockwork Earth 03 - Pinion [epub].rar"  405.6 kBytes yEnc
		//Attn: Brownian - "del Rey, Maria - Paradise Bay (FBS).rar" yEnc
		//New Scan "Herbert, James - Sepulchre (html).rar" yEnc
		if (preg_match('/^(Attn:|Re:|REQ:|New Scan).+?[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}(\d+[.,]\d+ [kKmMgG][bB](ytes)?)? yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
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
		if (preg_match('/^\[\d+\/(\d+\][-_ ]{0,3}.+)\.(txt|pdf|mobi|epub|azw)[-_ ]{0,3}.+flood( )?$/', $this->subject, $match)) {
			return $match[1];
		} //[2/4] Graham Masterton - Descendant.mobi
		if (preg_match('/^\[\d+\/(\d+\]) (.+?)\.(txt|pdf|mobi|epub|azw)/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	protected function ebook_magazines()
	{
		// e-book.magazines has really only header we care about in the form
		// [Top.Gear.South.Africa-February.2014] - "Top.Gear.South.Africa-February.2014.pdf.vol00+1.par2" yEnc  - 809.32 KB
		if (preg_match('/^(\[.+?\] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	//a.b.e-book.rpg
	protected function e_book_rpg()
	{
		//ATTN: falsifies RE: REQ:-Pathfinder RPG anything at all TIA [362/408] - "Pathfinder_-_PZO1110B_-_Pathfinder_RPG_-_Beta_Playtest_-_Prestige_Enhancement.pdf" yEnc
		if (preg_match('/^.+?\[\d+\/(\d+\]) - "(.+?)\.(txt|pdf|mobi|epub|azw)" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.erotica
	protected function erotica()
	{
		//[f3a543495657d38c361dbe767a8506df] - sandramilka01-casting [10/25] - "sandramilka01-casting.part08.rar" yEnc
		if (preg_match('/\[([a-fA-F0-9]+)][-_ ]{0,3}.+?[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[278997]-[FULL]-[#a.b.erotica]-[ chi-the.walking.dead.xxx ]-[06/51] - "chi-the.walking.dead.xxx-s.mp4" yEnc
		//[######]-[FULL]-[#a.b.teevee@EFNet]-[ Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD ] [1/5] - "Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD.nfo" yEnc
		//Re: [147053]-[FULL]-[#a.b.teevee]-[ Top_Gear.20x04.HDTV_x264-FoV ]-[11/59] - "top_gear.20x04.hdtv_x264-fov.r00" yEnc (01/20)
		if (preg_match('/(\[[\d#]+\]-\[.+?\]-\[.+?\])-\[ (.+?) \][- ]\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //<TOWN><www.town.ag > <download all our files with>>> www.ssl-news.info <<< > [01/28] - "TayTO-heyzo_hd_0317_full.par2" - 2,17 GB yEnc
		if (preg_match('/^<TOWN><www\.town\.ag > <download all our files with>>> www\.ssl-news\.info <<< > \[\d+(\/\d+\] - ".+?)' . $this->e0 . ' - /', $this->subject, $match)) {
			return $match[1];
		} //NihilCumsteR [1/8] - "Conysgirls.cumpilation.xxx.NihilCumsteR.par2" yEnc
		if (preg_match('/^NihilCumsteR \[\d+\/\d+\] - "(.+?NihilCumsteR\.)/', $this->subject, $match)) {
			return $match[1];
		} //Brazilian.Transsexuals.SR.UD.12.28.13.HD.720p.HDL [19 of 24] "JhoanyWilkerXmasLD_1_hdmp4.mp4.vol00+1.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9._-]+)[-_ ]{0,3}[\(\[]\d+ of (\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //"Lesbian seductions 26.part.nzb" yEnc
		if (preg_match('/^(".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //..::kleverig.eu::.. [001/141] - "ZYGBUTD5TPgMdjjxnvrl.par2" - 13,28 GB yEnc
		if (preg_match('/(.+)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]][-_ ]{0,3}("|#34;).+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		//"Babysitters_a_Slut_4_Scene_4.part01.rar"_SpotBots yEnc
		//- "JH2U0H5FIK8TO7YK3Q.part12.rar" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //<<<>>CowboyUp2012 XXX><<<Is.Not.Force.It.My.Younger.SOE-806.Jav.Censored.DVDRip.XviD-MotTto>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "Is.Not.Force.It.My.Younger.SOE-806.Jav.Censored.DVDRip.XviD-MotTto.part01.rar" >< 01/15 (1,39
		if (preg_match('/^(.+?usenet-space.+?Powered by.+? ".+?)' . $this->e0 . '.+? \d+\/(\d+.+?)$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.etc
	protected function etc()
	{
		//7000999555666777123754 - [334/389] - "The Secret of Bible & Jesus. Beyond The Da Vinci Code - YouTube.3gp" yEnc
		if (preg_match('/^(\d+ - \[)\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[scnzbefnet] Were.the.Millers.2013.EXTENDED.720p.BluRay.x264-SPARKS [01/61] - "were.the.millers.2013.extended.720p.bluray.x264-sparks.nfo" yEnc
		if (preg_match('/^\[scnzbefnet\] (.+?) \[\d+\/(\d+\]) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.font
	protected function font()
	{
		//RWlgVffClWxD0vXT1peIwb9DubTLMiYm3nvD1aMMDe[04/16] - "A9jFik7Fk4hCG4GWuxAg.r02" yEnc
		if (preg_match('/^([a-zA-Z0-9]{5,}\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.fz
	protected function fz()
	{
		//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
		if (preg_match('/^(>ghost-of-usenet\.org>.+?<.+?> ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.game
	protected function game()
	{
		//[192474]-[MP3]-[a.b.inner-sanctumEFNET]-[ Newbie_Nerdz_-_I_Cant_Forget_that_Girl_EP-(IM005)-WEB-2012-YOU ] [17/17] - "newbie_nerdz_-_i_cant_forget_that_girl_ep-(im005)-web-2012-you.nfo" yEnc
		if (preg_match('/(\[[\d#]+\]-\[.+?\]-\[.+?\])-\[ (.+?) \][- ]\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.games
	protected function games()
	{
		//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
		if (preg_match('/^(>ghost-of-usenet\.org>.+?<.+?> ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //( Overlord II RELOADED ) - [013/112] - "rld-olii.part001.rar" yEnc
		if (preg_match('/^\( ([\w. -]{8,}) \)[-_ ]{0,3}\[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
		if (preg_match('/^(<ghost-of-usenet\.org>.+? \[)\d+\/\d+\] - ".+?" .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ Dawn.of.Fantasy.Kingdom.Wars-PROPHET ] - [12/52] - "ppt-dfkw.part04.rar" yEnc
		if (preg_match('/^(\[ [-.a-zA-Z0-9]+ \] - \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [11/17] - "Reload.Outdoor.Action.Target.Down.GERMAN-0x0007.vol003+004.PAR2" yEnc
		if (preg_match('/\.net <<<Partner von SSL-News\.info>>> - \[\d+\/\d+\] - "(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[162198]-[FULL]-[a.b.teevee]-[ MasterChef.Junior.S01E07.720p.HDTV.X264-DIMENSION ]-[09/54] - "masterchef.junior.107.720p-dimension.nfo" yEnc
		if (preg_match('/(\[[\d#]+\]-\[.+?\]-\[.+?\])-\[ (.+?) \][- ]\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //"A.Stroke.of.Fate.Operation.Valkyrie-SKIDROW__www.realmom.info__.nfo" (02/38) 1,34 GB yEnc
		if (preg_match('/^"(.+)__www.realmom.info__.+" \(\d+\/(\d+\)) \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //"Mad.Men.S06E11.HDTV.x264-2HD.par2" yEnc
		if (preg_match('/^"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"Marvels.Agents.of.S.H.I.E.L.D.S01E07.HDTV.XviD-FUM.avi.nfo" [09/16] yEnc
		if (preg_match('/^"(.+?)' . $this->e0 . '[ _-]{0,3}\[\d+\/(\d+\])[ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		} //(????) [03/20] - "Weblinger - The.Haunted.House.Mysteries.v1.0-ZEKE.part01.rar" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/(\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//(001/132) "Harry.Potter.And.The.Goblet.Of.Fire.2005.810p.BluRay.x264.DTS.PRoDJi.nfo" - 8,71 GB - yEnc
		//(01/11) - Description - "ba588f108dbd068dc93e4b0182de652d.par2" - 696,63 MB - yEnc
		//(01/11) "Microsoft Games for Windows 8 v1.2.par2" - 189,87 MB - [REPOST] yEnc
		//(01/24) "ExBrULlNjyRPMdxqSlJKEtAYSncStZs3.nfo" - 3.96 kB - 404.55 MB - yEnc
		//(01/44) - - "Wii_2688_R_Knorloading.par2" - 1,81 GB - yEnc
		if (preg_match('/^\(\d+\/\d+\)( - Description)?[-_ ]{0,5}"(.+?)' . $this->e0 . '( - \d+([.,]\d+ [kKmMgG])?[bB])? - \d+([.,]\d+ [kKmMgG])?[bB][-_ ]{0,3}(\[REPOST\] )?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/59) - [Lords-of-Usenet] presents Sins.of.a.Solar.Empire.Rebellion.Forbidden.Worlds-RELOADED - "rld-soaserfw.nfo" - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) - \[Lords-of-Usenet\] presents (.+?)[-_ ]{0,3}".+?' . $this->e0 . ' - yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//(19/28) "sr-joedanger.rar" - 816,05 MB -Joe.Danger-SKIDROW yEnc
		//(39/40) "flt-ts31554.vol061+57.PAR2" - 1,43 GB -The_Sims_3_v1.55.4-FLTDOX yEnc
		if (preg_match('/^\(\d+\/(\d+\))[-_ ]{0,3}".+?' . $this->e0 . ' - \d+([.,]\d+ [kKmMgG])?[bB] -([a-zA-Z0-9-_\.]+) yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[8];
		} //[02/17] - "Castle.Of.Illusion.Starring.Mickey.Mouse.PSN.PS3-DUPLEX.nfo" yEnc
		if (preg_match('/^[\(\[]\d+\/(\d+[\)\]][-_ ]{0,3}".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[PROPER] FIFA.14.PAL.XBOX360-iNSOMNi [008/100]- "ins-fifa14pal.r05" yEnc
		if (preg_match('/^\[PROPER\] ([a-zA-Z0-9-_\.]+) [\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //<<<< Alien Zombie Death v2 EUR PSN PSP-PLAYASiA >>>> < USC> <"Alien Zombie Death v2 EUR PSN PSP-PLAYASiA.part4.rar">[06/16] 153,78 MB yEnc
		if (preg_match('/^<<<< ([a-zA-Z0-9-_ ]+) >>>> < USC> <".+?' . $this->e0 . '>\[\d+\/(\d+\]) \d+([.,]\d+ [kKmMgG])?[bB] yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		} //<<<usenet-space-cowboys.info>>> fuzzy <<<Powered by https://secretusenet.com><Adventures To Go EUR PSP-ZER0>< "Adventures To Go EUR PSP-ZER0.nfo" >< 2/6 (195,70 MB) >< 10,70 kB > yEnc
		if (preg_match('/^<<<.+\.info>>> fuzzy <<<Powered by .+secretusenet\.com><([a-zA-Z0-9-_ ]+)>< ".+?' . $this->e0 . ' >< \d+\/(\d+) \(\d+([.,]\d+ [kKmMgG])?[bB]\) >< \d+([.,]\d+ [kKmMgG])?[bB] > yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		} //Baku.No.JAP.Working.PSP-PaL - [1/7] - "Baku.No.JAP.Working.PSP-PaL.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9 -\._]+) - \[\d+\/(\d+\])[-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //<TOWN> www.town.ag > sponsored by www.ssl-news.info > (53/86) "Afro_Samurai_NTSC_PROPER_XBOX360-GameStop.part51.rar" - 7.74 GB - yEnc
		if (preg_match('/^<TOWN>.+?town\.ag.+?(www\..+?|news)\.[iI]nfo.+? \(\d+\/(\d+\)) "(.+?)(-sample)?' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB]? - yEnc$/', $this->subject, $match)) {
			return $match[2] . $match[3];
		} //FTDWORLD.NET| Grand.Theft.Auto.V.XBOX360-QUACK [020/195]- "gtavdisc1.r17" yEnc
		if (preg_match('/^FTDWORLD\.NET\| ([a-zA-Z0-9 -_\.]+) \[\d+\/(\d+\])- ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(FIFA 14 Demo XBOX) [001/163] - "FIFA 14 Demo.part.par2" yEnc
		if (preg_match('/^\(([a-zA-Z0-9 -_\.]+)\) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[16/62]  (CastleStorm.XBLA.XBOX360-MoNGoLS) - "mgl-cast.part15.rar" yEnc
		if (preg_match('/^\[\d+\/(\d+\])  \(([a-zA-Z0-9 -_\.]+)\) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //GOGDump Wing Commander - Privateer (1993) [GOG] [03/14] - "Wing Commander - Privateer (1993) [GOG].part2.rar" yEnc
		if (preg_match('/^GOGDump (.+) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Uploader.Presents-Need.For.Speed.Rivals.XBOX360-PROTOCOL[10/94]"nfs.r-ptc.r07" yEnc
		if (preg_match('/^(Uploader.Presents)[- ](.+?)[\(\[]\d+\/\d+\]".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} // 	(1/8) - Description - "90b66b2e31053401d808cf413b28f2f0.par2" - 698,44 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[-_ ]{0,3}Description[-_ ]{0,3}"\w+' . $this->e2, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.games.dox
	protected function games_dox()
	{
		//[142961]-[MP3]-[a.b.inner-sanctumEFNET]-[ Pascal_and_Pearce-Passport-CDJUST477-2CD-2011-1REAL ] [28/36] - "Pascal_and_Pearce-Passport-CDJUST477-2CD-2011-1REAL.par2" yEnc
		if (preg_match('/(\[[\d#]+\]-\[.+?\]-\[.+?\])-\[ (.+?) \][- ]\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[NEW DOX] The.King.of.Fighters.XIII.Update.v1.1c-RELOADED [1/6] - "The.King.of.Fighters.XIII.Update.v1.1c-RELOADED.par2" yEnc
		//[NEW DOX] Crysis.3.Crackfix.3.INTERNAL-RELOADED [00/12] ".nzb"  yEnc
		if (preg_match('/^\[NEW DOX\][ _-]{0,3}(.+?)[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}"(.+?)' . $this->e0 . '[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		// for some reason the nzb is posted separately with different a file count - removing it for now to combine them
		//[NEW DOX] Minecraft.1.6.2.Installer.Updated.Server.List  - "Minecraft 1 6 2 Cracked Installer Updater Serverlist.nfo" - yEnc
		if (preg_match('/^\[NEW DOX\][ _-]{0,3}(.+?)[ _-]{0,3}"(.+?)' . $this->e0 . '[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ Assassins.Creed.3.UPDATE 1.01.CRACK.READNFO-P2P  00/17 ] "Assassins.Creed.3.UPDATE 1.01.nzb" yEnc
		if (preg_match('/^\[ ([a-zA-Z0-9-\._ ]+)  \d+\/(\d+ \]) ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[01/16] - GRID.2.Update.v1.0.83.1050.Incl.DLC-RELOADED - "reloaded.nfo" - yEnc
		//[12/17] - Call.of.Juarez.Gunslinger.Update.v1.03-FTS - "fts-cojgsu103.vol00+01.PAR2" - PC - yEnc
		//[4/5] - Dungeons.&.Dragons.HD.Chronicles.of.Mystara.Update.2-FTS - "fts-ddcomu2.vol0+1.PAR2" - PC - yEnc
		if (preg_match('/^\[\d+\/(\d+\]) - ([a-zA-Z0-9-\.\&_ ]+) - ".+?' . $this->e0 . '( - PC)? - yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[36/48] NASCAR.The.Game.2013.Update.2-SKIDROW - "sr-nascarthegame2013u2.r33" yEnc
		if (preg_match('/^\[\d+\/(\d+\]) ([a-zA-Z0-9-\._ ]+) ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[Grand_Theft_Auto_Vice_City_1.1_Blood_NoCD_Patch-gimpsRus]- "grugtavc11bcd.nfo" yEnc
		if (preg_match('/^\[([a-zA-Z0-9-\._ ]+)\]- ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[OLD DOX] (0001/2018) - "18.Wheels.of.Steel.American.Long.Haul.CHEAT.CODES-RETARDS.7z" - 1,44 GB - yEnc
		if (preg_match('/^\[OLD DOX\][ _-]{0,3}\(\d+\/(\d+\)[ _-]{0,3}".+?)' . $this->e0 . '[-_ ]{0,3}\d+[,.]\d+ [mMkKgG][bB][-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Endless.Space.Disharmony.v1.1.1.Update-SKIDROW - [1/6] - "Endless.Space.Disharmony.v1.1.1.Update-SKIDROW.nfo" - yEnc
		if (preg_match('/^([a-zA-Z0-9-\._ ]+) - \[\d+\/(\d+\]) - ".+?' . $this->e0 . '{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(F.E.A.R.3.Update.1-SKIDROW) [01/12] - "F.E.A.R.3.Update.1-SKIDROW.par2" yEnc
		if (preg_match('/^\(([a-zA-Z0-9-\._ ]+)\) \[\d+\/(\d+\]) - ".+?' . $this->e0 . '{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(Company.of.Heroes.2.Update.v3.0.0.9704.Incl.DLC.GERMAN-0x0007) - "0x0007.nfo" yEnc
		if (preg_match('/^\(([a-zA-Z0-9-\._ ]+)\) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.games.xbox360
	protected function games_xbox360()
	{
		//Uploader.Presents-Injustice.Gods.Among.Us.Ultimate.Edition.XBOX360-COMPLEX(02/92]"complex-injustice.ultimate.nfo" yEnc
		//Uploader.Presents-Need.For.Speed.Rivals.XBOX360-PROTOCOL[10/94]"nfs.r-ptc.r07" yEnc
		if (preg_match('/^(Uploader.Presents)[- ](.+?)[\(\[]\d+\/\d+\]".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//place2home.net - Call.of.Duty.Ghosts.XBOX360-iMARS - [095/101] - "imars-codghosts-360b.vol049+33.par2" yEnc
		//Place2home.net - Diablo_III_USA_RF_XBOX360-PROTOCOL - "d3-ptc.r34" yEnc
		if (preg_match('/^place2home\.net - (.*?) - (\[\d+\/\d+\] - )?".+?" yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //"Arcana_Heart_3_PAL_XBOX360-ZER0__www.realmom.info__.nfo" (02/89) 7,58 GB yEnc
		if (preg_match('/^"(.+)(__www\.realmom\.info__)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}") [\[\(]\d+\/(\d+[\]\)]) \d+[,.]\d+ [mMkKgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[5];
		} //(01/15) "Mass.Effect.3.Collectors.Edition.DLC.JTAG-XPG.par2" - 747.42 MB - yEnc
		if (preg_match('/^[\[\(]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(????) [00/28] - "Farming.Simulator.XBOX360.JTAG.RGH.nzb" yEnc
		if (preg_match('/^\(.+\)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(14227) BloodRayne_Betrayal_XBLA_XBOX360-XBLAplus [01/25] - "xp-blobe.nfo" yEnc
		if (preg_match('/^\(\d+\)[-_ ]{0,3}(.+)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(14811) [#alt.binaries.games.xbox360@EFNet]-[AMY_XBLA_XBOX360-XBLAplus]-[]-  "xp-amyxb.nfo"  yEnc
		if (preg_match('/^(\(\d+\))[-_ ]{0,3}\[.+EFNet\][-_ ]{0,3}\[(.+)\][-_ ]{0,3}\[\][-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(14872) [#alt.binaries.games.xbox360@EFNet]-[BlazBlue_CS_System_Version_Data_Pack_1.03-DLC_XBOX360]-  "xp-bbcssvdp103.nfo"  yEnc
		if (preg_match('/^(\(\d+\))[-_ ]{0,3}\[.+EFNet\][-_ ]{0,3}\[(.+)\][-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(44/82) - Fuzion_Frenzy_2_REPACK-USA-XBOX360-DAGGER - "ff2r-dgr.041" - 6.84 GB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[-_ ]{0,3}(.+?)[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[  14047  ] - [ ABGX@EFNET ] - [  Rock.Band.Pearl.Jam.Ten.DLC.XBOX360-FYK ALL DLC    ] -  (01/46) "rbpjtdlc-fyk.nfo" - 526,92 MB - yEnc
		//[  14046  ] - [ ABGX@EFNET ] - [  Rock_Band-2011-07-19-DLC_XBOX360-XBLAplus ALL   ] -  (01/12) "xp-rb-2011-07-19.nfo" - 198,70 MB - yEnc
		//[ 14102 ] -[ ABGX.NET ] - [ F1.2010.XBOX360-COMPLEX NTSC DVD9  ] -  (01/79) "cpx-f12010.nfo" - 6,57 GB - yEnc
		if (preg_match('/^\[[-_ ]{0,3}(\d+)[-_ ]{0,3}\][-_ ]{0,3}\[ ABGX.+\][-_ ]{0,3}\[[-_ ]{0,3}(.+)[-_ ]{0,4}\][-_ ]{0,4}\(\d+\/\d+\)[-_ ]{0,3}"(.+?)(\.part\d*|\.rar)?(\.vol.+\(\d+\\d+\)"|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}\d+[,.]\d+ [mMkKgG][bB][-_ ]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[ 17956]-[FULL]-[ abgx360EFNet ]-[ F1_2012_JPN_XBOX360-Caravan ]-[78/99] - "cvn-f12012j.r75" yEnc
		//[ 17827]-[FULL]-[ #abgx360@EFNet ]-[ Capcom_Arcade_Cabinet_XBLA_XBOX360-XBLAplus ]-[01/34] - "xp-capac.nfo" yEnc
		if (preg_match('/^\[[-_ ]{0,3}(\d+)[-_ ]{0,3}\][-_ ]{0,3}\[FULL\][-_ ]{0,3}\[ (abgx360EFNet|#abgx360@EFNet) \][-_ ]{0,3}\[[-_ ]{0,3}(.+)[-_ ]{0,3}\][-_ ]{0,3}\[\d+\/\d+\][-_ ]{0,3}"(.+?)(\.part\d*|\.rar)?(\.vol.+\(\d+\\d+\)"|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[3];
		}
		//[19672]-[FULL]-[abgx360EFNet]-[01/29] "mgl-cont.par2" yEnc
		if (preg_match('/^\[(\d+)\][-_ ]{0,3}\[FULL\][-_ ]{0,3}\[(abgx360EFNet|#abgx360@EFNet)\][-_ ]{0,3}\[\d+\/\d+\][-_ ]{0,3}"(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")[- ]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //[ GAMERZZ ] - [ Grand.Theft.Auto.V.XBOX360-COMPLEX ] [159/170] - "complex-gta5.vol000+18.par2" yEnc
		if (preg_match('/^\[[-_ ]{0,3}GAMERZZ[-_ ]{0,3}\][-_ ]{0,3}\[[-_ ]{0,3}(.+)[-_ ]{0,3}\][-_ ]{0,3}\[\d+\/(\d+\])[-_ ]{0,3}"(.+?)(\.part\d*|\.rar)?(\.vol.+\(\d+\\d+\)"|\.[A-Za-z0-9]{2,4}")[-_ ]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[ TOWN ]-[ www.town.ag ]-[ Assassins.Creed.IV.Black.Flag.XBOX360-COMPLEX ]-[ partner of www.ssl-news.info ] [074/195]- "complex-ac4.bf.d1.r71" yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ (.+?) \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}\[\d+\/(\d+\])[ _-]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.german.movies
	protected function german_movies()
	{
		//>ghost-of-usenet.org>Monte.Cristo.GERMAN.2002.AC3.DVDRiP.XviD.iNTERNAL-HACO<HAVE FUN> "haco-montecristo-xvid-a.par2" yEnc
		if (preg_match('/^(>ghost-of-usenet\.org>.+?<.+?> ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //( Overlord II RELOADED ) - [013/112] - "rld-olii.part001.rar" yEnc
		if (preg_match('/^\( ([\w. -]{8,}) \)[-_ ]{0,3}\[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
		if (preg_match('/^(<ghost-of-usenet\.org>.+? \[)\d+\/\d+\] - ".+?" .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.ghosts
	protected function ghosts()
	{
		//<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
		if (preg_match('/^(<ghost-of-usenet\.org>.+? \[)\d+\/\d+\] - ".+?" .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(14/20) "Jack.the.Giant.Slayer.2013.AC3.192Kbps.23fps.2ch.TR.Audio.BluRay-Demuxed.by.par2" - 173,15 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) ("|#34;)(.+)(\.[vol|part].+)?\.(par2|nfo|rar|nzb)("|#34;) - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[3];
		}
		return $this->generic();
	}

	// a.b.hdtv
	protected function hdtv()
	{
		//[ TrollHD ] - [ 0270/2688 ] - "Tour De France 2013 1080i HDTV MPA 2.0 MPEG2-TrollHD.part0269.rar" yEnc
		//[17/48] - "Oprah's Next Chapter S02E37 Lindsay Lohan 1080i HDTV DD5.1 MPEG2-TrollHD.part16.rar" yEnc
		//[02/29] - "Fox Sports 1 on 1 - Tom Brady 720p HDTV DD5.1 MPEG2-DON.part01.rar" yEnc
		if (preg_match('/^(\[ TrollHD \] - )?[\[\(][-_ ]{0,3}\d+\/(\d+[-_ ]{0,3}[\)\]]) - "(.+?) MPEG2-(DON|TrollHD)\..+?" yEnc$/', $this->subject, $match)) {
			return $match[2] . $match[3];
		} //Covert.Affairs.S05E06.Embassy.Row.1080p.WEB-DL.DD5.1.H.264-NTb.vol093+82.par2 - [41/42] - "Covert.Affairs.S05E06.Embassy.Row.1080p.WEB-DL.DD5.1.H.264-NTb.vol093+82.par2" yEnc
		if (preg_match('/.* - \[\d+\/(\d+\]) - "(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //"bhuhirawniowrj3io2o34.vol061+4.par2" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}(\.txt)?").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.hdtv.x264
	protected function hdtv_x264()
	{
		//[133170]-[FULL]-[#a.b.moovee]-[ Hansel.And.Gretel.Witch.Hunters.DVDR-iGNiTiON ]-[032/117] "ign-witchhunters.r24" yEnc
		//Re: [133388]-[FULL]-[#a.b.moovee]-[ Familiar.Grounds.2011.DVDRip.XViD-TWiST ]-[01/59] - "twist-xvid-terrainsconus.nfo" yEnc
		//[134212]-[FULL]-[#a.b.moovee]-[ Monsters.Inc.2001.1080p.BluRay.x264-CiNEFiLE ] [80/83] - "monsters.inc.2001.1080p.bluray.x264-cinefile.vol015+16.par2" yEnc
		//[134912]-[FULL]-[#a.b.moovee]-[ Epic.2013.DVDRip.X264-SPARKS ]-[01/70]- "epic.2013.dvdrip.x264-sparks.nfo" yEnc
		if (preg_match('/(\[\d+\]-\[.+?\]-\[.+?\]-\[ .+? \](-| ))\[\d+\/\d+\][ -]* ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(23/36) "Love.Is.In.The.Meadow.S08E08.HDTV.720p.x264.ac3.part22.rar" - 2,80 GB - yEnc
		if (preg_match('/^\(\d+(\/\d+\) ".+?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//Hard.Target.1993.1080p.Bluray.X264-BARC0DE - [36/68] - "BARC0DE1080pHTAR.r22" yEnc
		//Goddess.2013.720p.BDRip.x264.AC3-noOne  [086/100] - "Goddess.2013.720p.BDRip.x264.AC3-noOne.part84.rar" yEnc
		if (preg_match('/^([A-Z0-9a-z][A-Za-z0-9.-]+ -? \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //I Love Democracy - Norwegen - Doku -  2012 - (German)  - AC3 HD720p  Avi by Waldorf -  [02/71] - "Waldorf.jpg" yEnc
		if (preg_match('/(.+?\s+by Waldorf\s+-\s+\[)\d+\/\d+\]\s+-\s+".+?"\s+yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //-{NR.C}- - [00/96] - "Being.Liverpool.S01.720p.HDTV.x264-DHD.nzb" yEnc
		if (preg_match('/^-{NR\.C}- - \[\d+\/(\d+\]) - ("|#34;)(.+)(\.[vol|part].+)?\.(par2|nfo|rar|nzb)("|#34;) yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //- [34/69] - "Zero.Charisma.2013.WEB-DL.DD5.1.H.264-HaB.part33.rar" yEnc
		if (preg_match('/^- \[\d+\/(\d+\]) - "(.+?)(\.part\d*|\.rar|\.pdf)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //-=www.hotrodpage.info=- Makaveli -=HoTCreWTeam=- Post: - [000/192] - "Hop (2011) 1080p AVCHD.nzb" yEnc
		if (preg_match('/.+www\.hotrodpage\.info.+\[\d+\/(\d+\]) - "(.+?)(\.part\d*|\.rar|\.pdf)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //-4y (PW)   [ usenet-4all.info - powered by ssl.news -] [12,40 GB] [49/57] "43842168c542ed3.vol000+01.par2" yEnc
		if (preg_match('/^.+?\[(\d+[.,]\d+ [kKmMgG][bB])\] \[\d+\/(\d+\][-_ ]{0,3}.+?)[-_ ]{0,3}"(.+?)(\.part\d*|\.rar|\.pdf)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2] . $match[3];
		} //!MR [01/49] - "Persuasion 2007.par2" EN MKV yEnc
		if (preg_match('/.*[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		}
		//Wonders.of.the.Universe.S02E03.1080p.HDTV.x264.AC3.mp4 [1 of 54] "Wonders.of.the.Universe.S02E03.The.Known.and.the.Unknown.1080p.HDTV.x264.AC3-tNe.mp4.001" yEnc
		//Wilfred Season 2 - US - 720p WEB-DL [28 of 43] "Wilfred.US.S02E01.Progress.720p.WEB-DL.DD5.1.H264-NTb.mkv.001" yEnc
		if (preg_match('/^.+ ?\[\d+( of |\/)(\d+\] ("|#34;).+?)(\.part\d*|\.rar)?(\.[A-Za-z0-9]{2,4})?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //The.Walking.Dead.S02E13.720p.WEB-DL.AAC2.0.H.264-CtrlHD -Kopimi- - 01/37 - "The.Walking.Dead.S02E13.Beside.the.Dying.Fire.720p.WEB-DL.AAC2.0.H.264-CtrlHD.nfo" yEnc
		if (preg_match('/^.+ ?\d+( of |\/)(\d+ - ("|#34;).+?)(\.part\d*|\.rar)?(\.[A-Za-z0-9]{2,4})?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //The.Guild.S05E12.Grande.Finale.1080p.WEB-DL.x264.AC3.PSIV - "The.Guild.S05E12.Grande.Finale.1080p.WEB-DL.x264.AC3.PSIV.nfo" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.highspeed
	protected function highspeed()
	{
		//Old Dad uppt 18 und immer (noch) Jungfrau DvD Rip AC3 XviD German 02/34] - "18 und immer (noch) Jungfrau.part01.rar" yEnc
		//Old Dad uppt In ihrem Haus DVD Ripp AC3 German Xvid [01/31] - "In ihrem Haus.par2" yEnc
		//Old Dad uppt Eine offene Rechnung XviD German DVd Rip[02/41] - "Eine offene Rechnung.part01.rar" yEnc
		//Old Dad uppMiss Marple: Der Wachsblumenstrauß , Wunschpost Xvid German10/29] - "Miss Marple Der Wachsblumenstrauß.part09.rar" yEnc
		if (preg_match('/^(Old\s+Dad\s+uppt?.+?)( mp4| )?\[?\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[03/61] - "www.realmom.info - xvid - xf-fatalmovecd1.r00" - 773,34 MB - yEnc
		if (preg_match('/^\[\d+(\/\d+\] - ".+?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //www.usenet-town.com [Sponsored by Astinews] (103/103) "Intimate.Enemies.German.2007.AC3.[passwort protect].vol60+21.PAR2" yEnc
		if (preg_match('/^www\..+? \[Sponsored.+?\] \(\d+(\/\d+\) ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(????) [3/4] - "0024456.pdf.par2" yEnc
		if (preg_match('/^\(\?{4}\) \[\d+\/\d+\] - "(.+?)(\.part\d*|\.rar|\.pdf)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.inner-sanctum
	protected function inner_sanctum()
	{
		//[ 9970e7535ccc06155129f5971ff575e2 ] [23/30] - "19-sub6_-_blox_loggers_(mr.what_remix)-psycz_int.mp3" yEnc
		if (preg_match('/\[ (.+) \][-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} ////ea17079f47de702eead5114038355a70 [1/9] - "00-da_morty_-_boondock_sampler_02-(tbr002)-web-2013-srg.m3u" yEnc
		if (preg_match('/(.+)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[ 9970e7535ccc06155129f5971ff575e2 ] [23/30] - "19-sub6_-_blox_loggers_(mr.what_remix)-psycz_int.mp3" yEnc
		if (preg_match('/^\[ ([a-fA-F0-9]+) \] \[\d+\/\d+\] - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[30762]-[android]-[ Fairway.Solitaire.v1.91.1-AnDrOiD ] [01/10] - "AnDrOiD.nfo" yEnc
		if (preg_match('/^(\[\d+\]-\[.+?\]-\[ .+? \] \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ nEwZ[NZB].iNFO - [ Zed--The_Invitation-WEB-2010-WUS ] - File [12/13]: "08-zed--the_river.mp3" yEnc
		if (preg_match('/^\[ nEwZ\[NZB\]\.iNFO( \])?[-_ ]{0,3}\[ (.+?) \][-_ ]{0,3}(File )?[\(\[]\d+\/(\d+[\)\]]): "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?(yEnc)?$/', $this->subject, $match)) {
			return $match[2] . $match[4];
		} //nEwZ[NZB].iNFO - VA-Universal_Music_Sampler_07_February-PROMO-CDR-FLAC-2013-WRE - File [6/9]: "01-alesso-years_(hard_rock_sofa_remix).flac"
		if (preg_match('/^nEwZ\[NZB\]\.iNFO[-_ ]{0,3} (.+?) [-_ ]{0,3}(File )?[\(\[]\d+\/(\d+[\)\]]): "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //..:[DoAsYouLike]:..   1,11 GB   "KGMmDSSHBWnxV4g7Vbq5.part01.rar"   47,68 MB yEnc
		if (preg_match('/.+[DoAsYouLike\].?[ _-]{0,3}\d+[,.]\d+ [mMkKgG][bB][-_ ]{0,3}"(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[ _-]{0,3}\d+([,.]\d+ [mMkKgG])? [bB][-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //..:[DoAsYouLike]:..   1,11 GB   "KGMmDSSHBWnxV4g7Vbq5.part01.rar"   47,68 MB yEnc
		if (preg_match('/.+[DoAsYouLike\].?[ _-]{0,3}\d+[,.]\d+ [mMkKgG][bB][-_ ]{0,3}"(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")[ _-]{0,3}\d+([,.]\d+ [mMkKgG])? [bB][-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/10) "LeeDrOiD HD V3.3.2-Port-R4-A2SD.par2" - 357.92 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))( - Description -)? "(.+?)' . $this->e0 . '( - \d+[,.]\d+ [mMkKgG][bB])? - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //(VA-I_Love_Yaiza_Vol.1-WEB-2012-ServerLab) [01/11] - ".sfv" yEnc
		if (preg_match('/^\(([a-zA-Z0-9._-]+)\) \[\d+\/(\d+\]) - ".+?([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|") yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(17/41) - "3-8139g0m530.017" yEnc
		if (preg_match('/^[\[\(]\d+( of |\/)(\d+[\]\)])[-_ ]{0,3}"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[2] . $match[3];
		} //[153618]-[#a.b.inner-sanctum@EFNET]-[MetaProducts.DiskWatchman.v2.0.240.Incl.Keygen.And.Patch.REPACK-Lz0]-[0/6] - MetaProducts.DiskWatchman.v2.0.240.Incl.Keygen.And.Patch.REPACK-Lz0.nzb yEnc
		if (preg_match('/^\[\d+\]-\[.+?\]-\[(.+?)\][-_ ]{0,3}\[\d+\/(\d+\]) - .+? yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.milo
	protected function milo()
	{
		//RWlgVffClWxD0vXT1peIwb9DubTLMiYm3nvD1aMMDe[04/16] - "A9jFik7Fk4hCG4GWuxAg.r02" yEnc
		//H8XxBd44qXBGk [05/15] - "H8XxBd44qXBGk.part5.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9]{5,} ?\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.mojo
	protected function mojo()
	{
		//[17/61] - "www.realmom.info - xvid - xf-devilstomb.r14" - 773,11 MB - yEnc
		if (preg_match('/^\[\d+(\/\d+\] - ".+?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//RWlgVffClWxD0vXT1peIwb9DubTLMiYm3nvD1aMMDe[04/16] - "A9jFik7Fk4hCG4GWuxAg.r02" yEnc
		//3JgtmNAbZbJ6Q [14/21] - "parfile.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9]{5,} ?\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.mom
	protected function mom()
	{
		//[usenet4ever.info] und [SecretUsenet.com] - 96e323468c5a8a7b948c06ec84511839-u4e - "96e323468c5a8a7b948c06ec84511839-u4e.par2" yEnc
		if (preg_match('/^(\[usenet4ever\.info\] und \[SecretUsenet\.com\] - .+?-u4e - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [01/26] - "Be.Cool.German.AC3.HDRip.x264-FuN.par2" yEnc
		if (preg_match('/(.+?\.net <<<Partner von SSL-News\.info>>> - \[)\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //62544-8[30/78] - "62544-0.35" yEnc
		if (preg_match('/^(\d+-\d)\[\d+\/(\d+\])[- ]{0,3}"\d+-\d.+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[Art-of-Usenet] dea75eb65e65c56197d749d57919806d [01/19] - "dea75eb65e65c56197d749d57919806d.par2" yEnc
		if (preg_match('/^\[Art-of-Usenet\] ([a-fA-F0-9]+) \[\d+\/(\d+\][-_ ]{0,3}".+?)' . $this->e1, $this->subject, $match)) {
			return $match[2];
		} //<ghost-of-usenet.org>XCOM.Enemy.Unknown.Deutsch.Patch.TokZic [0/9] - "XCOM Deutsch.nzb" ein CrazyUpp yEnc
		if (preg_match('/^(<ghost-of-usenet\.org>.+? \[)\d+\/\d+\] - ".+?" .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //brothers-of-usenet.info/.net <<<Partner von SSL-News.info>>> - [21/22] - "e4e4ztb54238ibftu.vol127+128.par2" yEnc
		if (preg_match('/^brothers-of-usenet.info\/\.net <<<Partner von SSL-News.info>>> - \[\d+\/\d+\] - "(.+?)(\.vol|\.par).+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //58600-0[51/51] - "58600-0.vol0+1.par2" yEnc
		if (preg_match('/^(\d+)\-\d+\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ MOVIE ] [14/19] - "Night.Vision.2011.DVDRip.x264-IGUANA.part12.rar" - 660,80 MB yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}\[\d+\/(\d+\][ _-]{0,3}".+)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //[A_New_Found_Glory-Its_All_About_The_Girls-Reissue-CDEP-FLAC-2003-JLM] [www.usenet4ever.info by Secretusenet] -  "00-a_new_found_glory-its_all_about_the_girls-reissue-cdep-flac-2003.jpg" yEnc
		if (preg_match('/^\[(.+?)\][ _-]{0,3}\[www\.usenet4ever\.info by Secretusenet][ _-]{0,3} ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//MoM100060 - "Florian_Arndt-Trix-(BBM36)-WEB-2012-UKHx__www.realmom.info__.nfo" [2/7] 29,04 MB yEnc
		//"Alan.Wake.v1.02.16.4261.Update-SKIDROW__www.realmom.info__.nfo" (02/17) 138,07 MB yEnc
		if (preg_match('/^(Mom\d+[ _-]{0,3})?"(.+?)__www\.realmom\.info__' . $this->e0 . '[ _-]{0,3}[\(\[]\d+\/(\d+[\)\]]) \d+[.,]\d+ [kKmMgG][bB] yEnc$/i', $this->subject, $match)) {
			return $match[2] . $match[8];
		} //"The.Draughtsmans.Contract.1982.576p.BluRay.DD2.0.x264-EA"(15/56) "The.Draughtsmans.Contract.1982.576p.BluRay.DD2.0.x264-EA.part13.rar" - 2.37 GB yEnc
		if (preg_match('/^"(.+?)"\(\d+\/(\d+\))[ _-]{0,3}".+?' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB] yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(01/29) - Description - "Revolution.2012.S01E06.HDTV.x264-LOL.nfo" - 317.24 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[ _-]{0,3}Description[ _-]{0,3}"(.+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(02/17) - [Lords-of-Usenet] <<Partner of SSL-News.info>> i8dewFjzft94BW71EI0s -"19913.r00" - 928,75 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[ _-]{0,3}\[Lords-of-Usenet\][ _-]{0,3}<<Partner of SSL-News\.info>>[ _-]{0,3}(.+?)[ _-]{0,3}".+?' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[002/161] - "Rayman_Legends_USA_PS3-CLANDESTiNE.nfo" yEnc
		if (preg_match('/^\[\d+\/(\d+\][ _-]{0,3}".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(4n4o8xq59kxq40bk8ukf3o4d163.163) [162/282] - "4n4o8xq59kxq40bk8ukf3o4d163.163" yEnc
		if (preg_match('/^\((\w+?)\d{3}\.[A-Za-z0-9]{3,4}\)[- ]{0,3}\[\d+\/(\d+\])[- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //<-{Retrouvez-nous sur NextDownload}-> > >"Next1023"[15/24] - "Next1023.9.vol001+001.par2" yEnc
		if (preg_match('/^<-{Retrouvez-nous sur NextDownload}-> > >"(\w+)"\[\d+\/(\d+\])[ _-]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[28/55] - "XzrgzBWoRqtcuBF.part27.rar" - 4,78 GB >>>UpperTeam for Usenet-Space-Cowboys.info and Secretusenet.com<<< yEnc
		if (preg_match('/^\[\d+\/(\d+\][ _-]{0,3}".+?)' . $this->e0 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB].+UpperTeam.+ Secretusenet\.com.+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.moovee
	protected function moovee()
	{
		//[133170]-[FULL]-[#a.b.moovee]-[ Hansel.And.Gretel.Witch.Hunters.DVDR-iGNiTiON ]-[032/117] "ign-witchhunters.r24" yEnc
		//Re: [133388]-[FULL]-[#a.b.moovee]-[ Familiar.Grounds.2011.DVDRip.XViD-TWiST ]-[01/59] - "twist-xvid-terrainsconus.nfo" yEnc
		//[134212]-[FULL]-[#a.b.moovee]-[ Monsters.Inc.2001.1080p.BluRay.x264-CiNEFiLE ] [80/83] - "monsters.inc.2001.1080p.bluray.x264-cinefile.vol015+16.par2" yEnc
		//[134912]-[FULL]-[#a.b.moovee]-[ Epic.2013.DVDRip.X264-SPARKS ]-[01/70]- "epic.2013.dvdrip.x264-sparks.nfo" yEnc
		if (preg_match('/(\[\d+\]-\[.+?\]-\[.+?\]-\[ .+? \](-| ))\[\d+\/\d+\][ -]* ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[42788]-[#altbin@EFNet]-[Full]- "margin-themasterb-xvid.par2" yEnc
		if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]- ").+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//[ Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE ]-[01/44] - "Hammer.of.the.Gods.2013.720p.WEB-DL.DD5.1.H.264-ELiTE.par2" yEnc
		//[ Admission.2013.720p.WEB-DL.DD5.1.H.264-HD4FUN ] - [01/82] - "Admission.2013.720p.WEB-DL.DD5.1.H.264-HD4FUN.nfo" yEnc
		if (preg_match('/^(\[ [a-zA-Z0-9.-]+ \] ?- ?\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [0/1] - "A.Good.Day.to.Die.Hard.2013.nzb" yEnc
		if (preg_match('/^\(\?{4}\) \[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[xxxxx]-[#a.b.moovee@EFNet]-[ xxxxx ]-[02/66] - "tulob88.part01.rar" yEnc
		if (preg_match('/^\[x+\]-\[.+?\]-\[ x+ \]-\[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//Groove.2000.iNTERNAL.DVDRip.XviD-UBiK - [001/111] - "ubik-groove-cd1.par2" yEnc
		//Antony.and.Cleopatra.1972.720p.WEB-DL.H264-brento -[35/57] - "Antony.and.Cleopatra.1972.720p.WEB-DL.AAC2.0.H.264-brento.part34.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9._-]+ - ?\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[133668] - p00okjiue34635xxzx$$Â£Â£zll-b.vol3+2.PAR2 - [005/118]  yEnc
		if (preg_match('/^(\[\d+\] - [a-z0-9]+.+?)(\.part\d*|\.rar)?(\.vol.+?|\.[A-Za-z0-9]{2,4}) - \[\d+\/\d+\]\s+yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//-[004/115] - "134218-0.par2" yEnc
		//[134824]-[001/117] - "134824-0.0" yEnc
		//[134510]-[REPOST]-[001/110] - "134510-rp-0.0" yEnc
		if (preg_match('/^((\[\d+\])?-(\[REPOST\])?\[)\d+(\/\d+\] - "\d+-)\d\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //[134517]-[01/76] - "Lara Croft Tomb Raider 2001 720p BluRay DTS x264-RightSiZE.nfo" yEnc
		if (preg_match('/^\[\d+\]-\[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(Iron.Man.3.2013.R5.DVDRip.XviD-AsA) (01/26) - "Iron.Man.3.2013.R5.DVDRip.XviD-AsA.part01.part.sfv" yEnc
		if (preg_match('/^(\([a-zA-Z0-9.-]+\) \()\d+\/\d+\) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Classic Surf) Morning.Of.The.Earth.1971 [03/29] - "Morning.Of.The.Earth.1971.part02.rar" yEnc
		if (preg_match('/^(\([a-zA-Z0-9].+?\) [a-zA-Z0-9.-]+ \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} // No example????? Probably : [32432]-[Something]-[4/24] - "02312-1.nzb" yEnc
		if (preg_match('/^(\[\d+\]-\[.+?\]-\[)\d+\/\d+\] - "\d+-.+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(Ringside.The.Best.of.Mike.Tyson.DVDRip.x264.AC3-FCZ)[01/68] - "Ringside.The.Best.of.Mike.Tyson.DVDRip.1.of.2.x264.AC3-FCZ.nfo" yEnc
		if (preg_match('/^\(([\w.-]+)\)\[\d+\/(\d+\])[- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	protected function movies()
	{
		//Underworld.Evolution.2006.480p.BDRip.XviD.AC3-AsA - [000/143] - "asa.nzb" yEnc
		if (preg_match('/^([a-z].+) - \[\d+\/(\d+\])[ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		} ///^Have Fun - ("|#34;)(.+)\.nfo("|#34;) Ph4let0ast3r yEnc$/i
		if (preg_match('/^Have Fun - ("|#34;)(.+)\.nfo("|#34;) Ph4let0ast3r yEnc$/i', $this->subject, $match)) {
			return $match[2];
		} //(01/34) "Sniper.Reloaded.2011.BluRay.810p.DTS.x264-PRoDJi.Turkish.Audio.par2" - 139,30 MB - yEnc
		if (preg_match('/^\(\d+\/\d+\) ("|#34;)(.+)\.(par2|nfo|rar|nzb)("|#34;) - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //"Discovery.Channel.Tsunami.Facing.The.Wave.720p.HDTV.x264-PiX.rar"
		if (preg_match('/^("|#34;)(.+)\.rar("|#34;)$/i', $this->subject, $match)) {
			return $match[2];
		} //Saw.VII.2010.720p.Bluray.x264.DTS-HDChina Saw.VII.2010.720p.Bluray.x264.DTS-HDChina.nzb
		if (preg_match('/^([a-z].+) .+\.(par2|nfo|rar|nzb)$/i', $this->subject, $match)) {
			return $match[1];
		} //(????) [1/1] - "The Secret Life of Walter Mitty 2013 CAM AAC x264-SSDD.mp4" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/(\d+\])[-_ ]{0,3}"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(001/114) - Description - "The.Chronicles.of.Riddick.2004.DC.BluRay.1080p.DTS.par2" - 10,50 GB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[-_ ]{0,3}Description[-_ ]{0,3}"(.+?)' . $this->e2, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[00/56] - "The.Last.Days.On.Mars.720p.BluRay.x264-DR.nzb" yEnc
		if (preg_match('/^\[\d+\/(\d+\])[-_ ]{0,3}"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[REUP] 6.Degress.of.Hell.2012.1080p.BluRay.DTS-HD.x264-BARC0DE - [03/50] - "BARC0DE.vol00+1.PAR2" yEnc
		//[REUP]Home.Of.The.Brave.2006.1080p.BluRay.DTS-HD.x264-BARC0DE - [03/38] - "BARC0DE.vol00+1.PAR2" yEnc
		if (preg_match('/^\[REUP\]( )?(.+?) - \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[2] . $match[3];
		} //22.Jump.Street.2014.720p.BluRay.x264-tpU.vol000+01.PAR2 [73/84] - "22.Jump.Street.2014.720p.BluRay.x264-tpU.vol000+01.PAR2" yEnc
		if (preg_match('/^.+\[\d+\/(\d+\]) - "(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //- Description - "The.Legend.of.Hercules.2014.720p.BluRay.x264.YIFY.mp4.01" - 795,28 MB -  yEnc
		if (preg_match('/^- Description - "([\w. -]{8,}?)' . $this->e0 . '([- ]{0,3}\d+[.,]\d+ [kKmMgG][bB])[- ]{0,4}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		}
		return $this->generic();
	}

	// a.b.movies.divx
	protected function movies_divx()
	{
		//(www.Thunder-News.org) >CD2< <Sponsored by Secretusenet> - "exvid-emma-cd2.par2" yEnc
		if (preg_match('/^(\(www\.Thunder-News\.org\) .+? - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//Movieland Post Voor FTN - [01/43] - "movieland0560.par2" yEnc
		if (preg_match('/^([a-zA-Z ]+Post Voor FTN - \[\d+\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Disney short films collection by mayhem masta"1923 - Alice's Wonderland.vol15+7.par2" yEnc
		if (preg_match('/(.+?by mayhem masta".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	//a.b.movies.x264
	protected function movies_x264()
	{
		//http://nzbroyalty.com - House.of.The.Rising.sun.2011.BluRay.720p.DTS.x264-CHD - [00/48] - "House.of.The.Rising.sun.2011.BluRay.720p.DTS.x264-CHD.nzb" yEnc
		if (preg_match('/^http:\/\/nzbroyalty\.com - (.+?) - \[\d+\/(\d+\]) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Scream.4.2011.WS.720p.BluRay.X264-AMIABLE - [000/106] - "Scream.4.2011.WS.720p.BluRay.X264-AMIABLE.nzb" yEnc
		if (preg_match('/^([a-zA-Z0-9._-]+ - ?\[)\d+\/(\d+\]) - "(.+?)\.(nzb|rar|par2)" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //The Beaver 2011 720p BluRay DD5.1 x264-CtrlHD - [00/65] - "The Beaver 2011 720p BluRay DD5.1 x264-CtrlHD.nzb" yEnc
		if (preg_match('/^([a-zA-Z0-9].+?)( - )\[\d+(\/\d+\] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2] . $match[3];
		} //"The.Hudsucker.Proxy.1994.1080p.Blu-ray.Remux.AVC.DTS.HD.MA.2.0-KRaLiMaRKo"(127/132) "The.Hudsucker.Proxy.1994.1080p.Blu-ray.Remux.AVC.DTS.HD.MA.2.0-KRaLiMaRKo.vol379+20.par2" - 24.61 GB - yEnc
		if (preg_match('/("|#34;)(.+)("|#34;)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}("|#34;).+?(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)[-_ ]{0,3}\d+[.,]\d+ [kKmMgG][bB][-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[2] . $match[4];
		} //Fly.With.The.Gold.2012.720p.BluRay.x264-WiKi [03/73] - "wiki-fwtg.par2" yEnc
		if (preg_match('/^([\w.-]+)[- ]{0,3}\[\d+\/(\d+\])[- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.mp3
	protected function mp3()
	{
		//"The Absence - Riders Of The Plague" [00/14] - "the_absence-riders_of_the_plague.nzb" yEnc
		if (preg_match('/"(.+)"[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//( Albert Cummings Albums 6x By Dready Niek (1999-2012) )  ( ** By Dready Niek ** ) [11/20] - "Albert Cummings Albums 6x By Dready Niek (1999-2012).part10.rar" yEnc
		//( Fat Freddy's Drop - Blackbird (2013) -- By Dready Niek ) -- By Dready Niek ) [01/15] - "Fat Freddy's Drop - Blackbird (2013) -- By Dready Niek.par2" yEnc
		if (preg_match('/(.+)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Metallica - Ride The Lightning    "01 - Fight Fire With Fire.mp3" yEnc
		if (preg_match('/^(.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.mp3.complete_cd
	protected function mp3_complete_cd()
	{
		//[052713]-[#eos@EFNet]-[All_Shall_Perish-Montreal_QUE_0628-2007-EOS]-[09/14] "06-all_shall_perish-deconstruction-eos.mp3" yEnc
		if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[)\d+\/\d+\] ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(05/10) - [Lords-of-usenet] <<Partner of SSL-News.info>>  "Wynardtage Praise The Fallen(2007).vol00+01.PAR2" - 132,64 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[ _-]{0,3}\[Lords-of-usenet\][ _-]{0,3}<<Partner of SSL-News.info>>[ _-]{0,3}"(.+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(06/11) - <www.lords-of-usenet.org><by Nerts> - "Diens - Schwarzmale.vol00+01.PAR2" - 141,07 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[ _-]{0,3}<www\.lords-of-usenet\.org><by Nerts>[ _-]{0,3}"(.+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //<www.Lords-Of-Usenet.org><by Nerts> (09/18) - "Mantus - Fatum (2013) [2CD].FH.vol00+2.PAR2" - 336,39 MB - yEnc
		if (preg_match('/^<www\.lords-of-usenet\.org><by Nerts>[ _-]{0,3}\(\d+\/(\d+\))[ _-]{0,3}"(.+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(08/15) "Noemi-Red.(Unreleased).2006.by.NYCrules.vol000+01.PAR2" - 179,66 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[ _-]{0,3}"(.+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[16_Bit_Lolitas-Warung_Brazil_002-2CD-2012-iHF] [www.usenet4ever.info by Secretusenet] -  "000-16_bit_lolitas-warung_brazil_002-2cd-2012-ihf.sfv" yEnc
		//[3RD_Prototype_-_On_My_Way-(TB7368)-WEB-2013-FMC] [www.usenet4ever.info by Secretusenet] -  "01-3rd_prototype_-_deafback-when_you_are_in_the_dark_(deafback_remix).mp3" yEnc
		//[Armin_Van_Buuren_Feat._Fiora-Waiting_For_The_Night-(ARMD1140)-WEB-2013-UKHx] [www.usenet4ever.info by Secretusenet] -  "00-armin_van_buuren_feat._fiora-waiting_for_the_night-(armd1140)-web-2013-ukhx.m3u" yEnc
		if (preg_match('/^\[([a-zA-Z0-9-_\(\)\.]+)\] \[www\.usenet4ever\.info by Secretusenet\] -  "(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>ghost-of-usenet.org<< 16_Bit_Lolitas-Warung_Brazil_002-2CD-2012-iHF >>www.SSL-News.info> -  "101-16_bit_lolitas-warung_brazil_002_cd1.mp3" yEnc
		if (preg_match('/^>ghost-of-usenet\.org<< ([a-zA-Z0-9-_\(\)\.]+) >>www\.SSL-News\.info> -  "(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//Blok_B-Bienvenue_Dans_Mon_Blok_Vol.1-2005-BZ_INT [20 of 27] "01-gangsta.mp3" yEnc
		//DJ Neev - HedKandi_2013-06-08 (Ministry of Sound Radio) [01/13] - "DJ Neev - HedKandi_2013-06-08 (Ministry of Sound Radio).par2" yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\(\)\.]+) \[\d+(\/| of )(\d+\])[-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[3];
		} //(www.Thunder-News.org) >Dee_And_Crane-Let_The_Music_Play-WEB-2012-UKHx< <Sponsored by Secretusenet> - "05-dee_and_crane-let_the_music_play_(jay_frogs_keep_the_groove_remix_edit).mp3" yEnc
		if (preg_match('/^\(www\.Thunder-News\.org\) >(.+?)< <Sponsored by Secretusenet> - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //VA 200 NRJ 2014 CD2 mp3_320kbps[18/27]VA 200 NRJ 2014 CD2 mp3_320kbps"(217) [Capital Cities] Safe And Sound.mp3"  yEnc
		if (preg_match('/^.+\[\d+\/(\d+\].+)"(.+?)' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.mp3.full_albums
	protected function mp3_full_albums()
	{
		//. - [05/10] - "Blues 'N Trouble - With Friends Like These [1989].vol00+01.par2" yEnc
		if (preg_match('/^\. - \[\d+\/(\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(001/122) "[www.Lords-of-Usenet.org]_[Partner von SSL-News]_Blind_Guardian-Discographie.par2" - 2,20 GB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "\[www\.Lords-of-Usenet\.org\]_\[Partner von SSL-News\]_(.+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//(06/10) "Pink Floyd - Dark Side Of The Moon [MFSL UDCD 517].vol00+01.PAR2"- - 67,88 MB - Pink Floyd - Dark Side Of The Moon [MFSL UDCD 517] yEnc
		//(07/11) "VA - Twilight - New Moon - Ost.vol00+01.PAR2"- - 93,69 MB - VA - Twilight - New Moon - Ost yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "(.+?)' . $this->e0 . '[ _-]{0,4}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}.+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(Kitty Samtpfote) [01/12] - "Electronic Music of the 80s.Michael Garrison - In The Regions Of Sunreturn and beyond 1991.par2 . http://usenet4ever.info_Sponsored by www.Secretusenet.com  " yEnc
		if (preg_match('/^\(.+\) \[\d+\/(\d+\]) - "(.+?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) . http:\/\/usenet4ever\.info_Sponsored by www\.Secretusenet\.com  " yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(www.Thunder-News.org) >Boehse Onkelz - Discography< <Sponsored by AstiNews> - (113/145) - "Boehse Onkelz - Discography.s10" yEnc
		if (preg_match('/^\(.+\) >(.+?)< <Sponsored by AstiNews> - \(\d+\/(\d+\)) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[00021]-["1999 Alphaville - Dreamscapes.part069.rar"[ yEnc
		if (preg_match('/^\[(\d+\]-\[".+?)' . $this->e0 . '\[ yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(nzbDMZ) [0/2] - "Miles Crossing - Miles Crossing (2011).nzb" yEnc
		if (preg_match('/^\(.+\) \[\d+\/(\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[06/10] - "Jeff Healey - Legacy Volume One [The Singles].vol00+01.PAR2" yEnc
		if (preg_match('/^\[\d+\/(\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //00008 "2003 Camouflage - Sensor.par2" yEnc
		if (preg_match('/^(\d+ ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //Alex Oriental Experience_-_Live II (Live II) [1/9] - "01_Red_Dress.mp3" yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\(\)\.]+) \[\d+(\/| of )(\d+\])[-_ ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[3];
		}
		return $this->generic();
	}

	// a.b.multimedia
	protected function multimedia()
	{
		//Escort.2006.DUTCH.WEB-RiP.x264-DLH - [01/23] - "Escort.2006.DUTCH.WEB-RiP.x264-DLH.par2" yEnc
		//Tusenbroder.S01E05.PDTV.XViD.SWEDiSH-NTV  [01/69] - "ntv-tusenbroder.s01e05.nfo" yEnc
		if (preg_match('/^([A-Z0-9a-z.-]{10,}\s+(- )?\[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //- "Auction Hunters S04E04.HDTV.x264-StarryNights1.nzb" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //homeland.s02e12.1080p.bluray-bia.r08 - [011/111]  yEnc
		if (preg_match('/^(.+?)\.[A-Za-z0-9]{2,4} - \[\d+\/(\d+\])  yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(TOWN)(www.town.ag ) (partner of www.ssl-news.info ) Twinz-Conversation-CD-FLAC-1995-CUSTODES  [01/23] - #34;Twinz-Conversation-CD-FLAC-1995-CUSTODES.par2#34; - 266,00 MB - yEnc
		if (preg_match('/^\(TOWN\)\(www\.town\.ag \)[ _-]{0,3}\(partner of www\.ssl-news\.info \)[ _-]{0,3} (.+?) \[\d+\/(\d+\][ _-]{0,3}("|#34;).+?)\.(par2|rar|nfo|nzb)("|#34;)[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[2];
		}
		return $this->generic();
	}

	// a.b.multimedia.anime
	protected function multimedia_anime()
	{
		//High School DxD New 01 (480p|.avi|xvid|mp3) ~bY Hatsuyuki [01/18] - "[Hatsuyuki]_High_School_DxD_New_01_[848x480][76B2BB8C].avi.001" yEnc
		if (preg_match('/(.+? \((360|480|720|1080)p\|.+? ~bY .+? \[)\d+\/\d+\] - ".+?\[[A-F0-9]+\].+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//[Hatsuyuki]_Seirei_Tsukai_no_Blade_Dance_-_03_[720p] [E18FCA59] [01/18]
		if (preg_match('/\[[a-z0-9-]+\][\w_-]+_\d+_\[\d{3,4}[ip]\]\s\[[A-F0-9]{8}\]\s\[\d+\/\d+\].*/i', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.multimedia.anime.highspeed
	protected function multimedia_anime_highspeed()
	{
		//High School DxD New 01 (480p|.avi|xvid|mp3) ~bY Hatsuyuki [01/18] - "[Hatsuyuki]_High_School_DxD_New_01_[848x480][76B2BB8C].avi.001" yEnc
		if (preg_match('/(.+? \((360|480|720|1080)p\|.+? ~bY .+? \[)\d+\/\d+\] - ".+?\[[A-F0-9]+\].+?' . $this->e1, $this->subject, $match)) {
			return $match[2];
		}
		return $this->generic();
	}

	// a.b.multimedia.documentaries
	protected function multimedia_documentaries()
	{
		//"Universe S4E08.part40.rar" - [41 of 76 - 10013 kb] yEnc
		if (preg_match('/^(".+?)' . $this->e0 . ' - \[\d+ of \d+ - \d+ [kKmMgG][bB]\] yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.multimedia.scifi
	protected function multimedia_scifi()
	{
		//some m4vs - "SilverHawks_v1eps01_The Origin Story.par2" yEnc
		if (preg_match('/^(some m4vs - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.music
	protected function music()
	{
		//[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ MOVIE ] [14/19] - "Night.Vision.2011.DVDRip.x264-IGUANA.part12.rar" - 660,80 MB yEnc
		if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}\[ .* \] \[\d+\/(\d+\][ _-]{0,3}("|#34;).+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //( 80's Giga Hits Collection (32 CDs) By Dready Niek )  By Dready Niek ) [44/54] - "80's Giga Hits Collection (32 CDs) By Dready Niek.part43.rar" yEnc
		if (preg_match('/^.+By Dready Niek \) \[\d+\/(\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(00/24]  Marco Mengoni - Prontoacorrere (2013) "Marco Mengoni - Prontoacorrere (2013).nzb" - nightsteff  yEnc
		if (preg_match('/^\(\d+\/(\d+\]  .+?) ".+?' . $this->e0 . ' - nightsteff  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(80's Disco-Soul-Funk) [136/426] - ["Level 42 - Lessons In Love.mp3"] yEnc
		if (preg_match('/^\((.+)\) \[\d+\/(\d+\]) - \[".+?' . $this->e0 . '\] yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(Jungle Fever Tapepacks) [67/79] - "Jungle Fever Tapepacks.part65.rar" yEnc
		if (preg_match('/^\((.+)\) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[1/8] - "Black Market Flowers - Bind (1993).sfv" yEnc
		if (preg_match('/^\[\d+\/(\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[DreamPieter] (Glen Tipton - Two solo albums) [04/23] - "Glenn Tipton - Baptizm of Fire - 04 - Fuel Me Up.mp3" yEnc
		if (preg_match('/^\[DreamPieter\] \((.+)\) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(01/10) "MylÃ¨ne Farmer - Monkey me (2012 Bluray Audio).rar" - 1,56 GB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)[ -]{0,3}".+?)' . $this->e2, $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.music.mp3
	protected function music_mp3()
	{
		//(00/20]  Nomadi - Terzo Tempo (2012) "Nomadi - Terzo Tempo (2012).nzb" - nightsteff  yEnc
		if (preg_match('/^[\(\[]\d+\/(\d+[\]\)][ -]{0,3}.+) ".+?' . $this->e0 . '[ -]{0,3}nightsteff  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(????) [19/22] - C.K.N. Demo 85  "19-rotten system.mp3" yEnc
		if (preg_match('/^\(\?+\) \[\d+\/(\d+\][ -]{0,3}.+)  ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(BierbauchFreddy Quwinn seine grÃ¶Ãten Coversongs Vol2) [22/60] - "040c - Freddy Quinn - Don't Forbid Me (1957) (NH 22 639, EPH 20 575).mp3" yEnc
		if (preg_match('/^\((.+)\) \[\d+\/(\d+\])[ -]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[ Spectrum (ft. Matthew Koma) (Extended Mix) - Zedd ] - [2012] - [256 Kbps MP3] [1 of 7] "Spectrum (ft. Matthew Koma) [Extended Mix].mp3" yEnc
		if (preg_match('/^\[ (.+) \] - \[\d+\] - \[\d+ Kbps MP3\] \[\d+ of (\d+\]) ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//[1/1] - (150 MP3 Album Charts) - "Atlantean Kodex - The White Goddess.rar"  yEnc
		//[1/1] - (MP3 Album Charts) - "Black Sabbath - 13.rar"  yEnc
		//[1/1] - (Top100 Album Charts) - "Bastille - Pompeii.rar"  yEnc
		//[1/1] - (Top100 Charts) - "Beatrice Egli - Gluecksgefuehle.rar"  yEnc
		if (preg_match('/^\[\d+\/(\d+\][ -]{0,3}\(((Top)?\d+ )?(MP3 )?(Album )?Charts\)[ -]{0,3}".+?)' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[1/1] - Album Top 100 - "Amy MacDonald - Life In A Beautiful Light.rar"  yEnc
		//[1/1] - Top 100 Album Charts 2012 - "Cro - Einmal Um Die Welt.rar"  yEnc
		if (preg_match('/^\[\d+\/(\d+\][ -]{0,3}(Album )?Top \d+( Album Charts \d+)?[ -]{0,3}".+?)' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//[1/1] (Album Top 100 - 2012) - "Cro - Einmal Um Die Welt.rar"  yEnc
		//[1/1] (Album Top 100 2012) - "Cro - Einmal Um Die Welt.rar"  yEnc
		//[1/1] (Album Top 100) - "Cro - Raop.rar"  yEnc
		if (preg_match('/^\[\d+\/(\d+\][ -]{0,3}\(Album Top \d+(( -)? \d+)?\)[ -]{0,3}".+?)' . $this->e0 . '  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ech0park]-[spotnet]-[Snow Patrol 2012 Tour Sampler - 2012-iND] [02/20] - "02-snow_patrol-ill_never_let_go.mp3" yEnc
		if (preg_match('/^\[ech0park\]-\[spotnet\]-\[(.+?)\] \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //40 Italo Dance Tunes (2CD) - "00. 40 Italo Dance Tunes 2011.nfo" [02/ 50] nightsteff  yEnc
		if (preg_match('/^(.+?)[- ]{0,3}".+?' . $this->e0 . '[- ]{0,3}\[\d+\/ (\d+\]) nightsteff[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		} //Bud Spencer & Terence Hill - Greatest Hits Vol 1 (1995) "04 - Just A Good Boy.mp3" nightsteff  yEnc
		if (preg_match('/^(.+?) ".+?' . $this->e0 . '[ -]{0,3}nightsteff  yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Attn: bearcat - Avenged Sevenfold - Avenged Sevenfold 320[17/18] - .vol15+16.par2 5.9Mb yEnc
		if (preg_match('/^Attn: \w+ - (.+?)\[\d+\/(\d+\]) - .+?([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}\d+[.,]\d+[kKmMgG][bB][- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	public function nl()
	{
		//(????) [01/46] - "NCIS.S11E02.Past.Present.and.Future.1080p.WEB-DL.DD5.1.H.264-CtrlHD.par2" yEnc
		if (preg_match('/\((\d+|\?+)\) \[\d+\/(\d+\]).*"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[2] . $match[3];
		} //JN - [1/3] - "Operatie Teheran-Joel C Rosenberg JN.par2" yEnc
		if (preg_match('/^\w+[-_ ]{0,3}[\[\(]\d+\/(\d+[\]\)])[-_ ]{0,3}"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //( Overlord II RELOADED ) - [013/112] - "rld-olii.part001.rar" yEnc
		if (preg_match('/^\( ([\w. -]{8,}) \)[-_ ]{0,3}\[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Batman.Arkham-Origins.Initiation-RELOADED - [006/296] - "r.vol010+08.PAR2" yEnc
		if (preg_match('/^([\w. &()-]{8,}?\b)[- ]{0,3}\[\d+\/(\d+\])[- ]{0,3}".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.ps3
	protected function ps3()
	{
		//[4197] [036/103] - "ant-mgstlcd2.r34" yEnc
		if (preg_match('/^\[\d+\] \[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.series.tv.french
	protected function series_tv_french()
	{
		//(01/34) "Merlin.2008.1x04.la.vengeance.de.nimue.par2" - 388,38 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "(.+?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB]( -)? yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Breaking.Bad.S02.MULTi.720p.BluRay.AC3.x264-BoO [749/883] - "212ACS3517.part01.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9._-]+)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Dawson.Saison2.DVDRIP.x264.FRENCH [111 of 196] "Dawson.S2.E22.Tout feu, tout flambe.m4v.003" yEnc
		if (preg_match('/^([a-zA-Z0-9._-]+)[-_ ]{0,3}[\(\[]\d+ of (\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[01/22] - "Unnatural.History.1x03.Espion.En.Sommeil.FR.LD.par2" yEnc
		if (preg_match('/^[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[MagNeum 3.14 S1 D.V.D + par2][1148/1167] - "ZDFRIKK8470DO776.D7P" yEnc
		if (preg_match('/^\[(.+?)\][-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}"(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.sony.psp
	protected function sony_psp()
	{
		//(01/10) "Assassins Creed - Bloodlines [EUR].par2" - 408,46 MB yEnc
		if (preg_match('/^\(\d+\/(\d+\) ".+?)' . $this->e0 . ' - \d+([.,]\d+ [kKmMgG])?[bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(20444) FIFA_12_EUR_GERMAN_PSP-ABSTRAKT [01/50] - "as-f12g.nfo" yEnc
		if (preg_match('/^\(\d+\) ([a-zA-Z0-9 -_\.]+) \[\d+\/(\d+\]) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.sound.mp3
	protected function sound_mp3()
	{
		//- codari_4_usenetrevolution.info-Partner of SSL-News UK.Single.Charts.Top.40  [01/25] - "UK.Single.Charts.Top.40.par2" - 301,70 MB - yEnc
		if (preg_match('/.+[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]][-_ ]{0,3}".+)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Terraplane Sun - Funnel of Love.mp3" - 21.55 MB - (1/6) - yEnc
		if (preg_match('/^"(.+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}\(\d+\/(\d+\))[ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		} //jean ferrat  annÃ©e 1967 Ã  1969  meil29 "17 Rien Ã  voir.mp3" yEnc
		if (preg_match('/^(.+? meil29) ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.sounds.flac
	protected function sounds_flac()
	{
		//[32974]-[FULL]-[#a.b.flac]-[ Tenniscoats-Tokinouta-JP-CD-FLAC-2011-BCC ]-[04/28] - "00-tenniscoats-tokinouta-jp-cd-flac-2011.nfo" yEnc
		if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[.+?\]-\[)\d+\/\d+] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.sounds.lossless
	protected function sounds_lossless()
	{
		//http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
		if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//Sonny Landreth - 2010 - Mississippi Blues - 04 of 29 - 00 - Mississippi Blues.sfv yEnc
		//Fruteland Jackson - 1996 - ... Is All I Crave - 08 of 20 - 00 - Fruteland Jackson - ... Is All I Crave.log yEnc
		if (preg_match('/^([A-Z0-9].+? - \d{4} - .+? - )\d+ of \d+ - \d+ - .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Restless Breed00/27] - ".nzb" yEnc
		if (preg_match('/^(.+?[a-zA-Z0-9][^\[( ])\d{2,}(\/\d+\] - ").+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(Rolling Stones) [01/28] - "Bell Center, Montreal, QC - 09-06-2013 (alt source sb remaster).par2" yEnc
		if (preg_match('/^\([A-Z0-9][a-zA-Z0-9 ]+\) \[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//.: VA-Liquid_Music-The_Future_House_Compilation-CD-FLAC-1993-WRS :. - .:www.thunder-news.org:. - .:sponsored by secretusenet.com:. - "00-va-liquid_music-the_future_house_compilation-cd-flac-1993-wrs.nfo" yEnc
		//.:Bruce_BecVar-Arriba-CD-FLAC-1993-JLM:. - .:thunder-news.org:. - .:sponsored by secretusenet.com:. - "00-bruce_becvar-arriba-cd-flac-1993.m3u" yEnc
		if (preg_match('/^.:[-_ ]{0,3}(.+?)[-_ ]{0,3}:..+?thunder-news\.org.+?secretusenet\.com:. - "(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //_ "CARUSO & GIGLI - O Sole Mio - The  Unknown.nzb" yEnc
		if (preg_match('/^[-_ ]{0,3}"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//"Art Tatum - The Complete Trio Sessions with Tiny Grimes & Slam Stewart - Vol 1.NFO" - 1 of 6 (1/1)
		//"Galen Jeter and the Dallas Jazz Orchestra - Thank You, Leon.NFO" - 1 of 5 (1/1) (1/1)
		if (preg_match('/^[-_ ]{0,3}"(.+?)' . $this->e0 . '[-_ ]{0,3}\d+ (of \d+)( \(\d+\/\d+\)){1,2} (yEnc)?$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		} //"Doc Watson - 1973 - The Essential Doc Watson - 01 - Tom Dooley.flac" - 406.64 MB - yEnc
		if (preg_match('/^[-_ ]{0,3}"(.+?)' . $this->e0 . '[ _-]{0,3}\d+[.,]\d+ [kKmMgG][bB][ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.sounds.mp3.audiobooks
	protected function sounds_audiobooks()
	{
		// currently these are teh same as mp3, but in the future these should be modified to be unique to audiobooks
		//(dream-of-usenet.info) - [04/15] - "Enya-And_Winter_Came...-2008.part2.rar" yEnc
		if (preg_match('/^\(dream-of-usenet\.info\) - \[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
		if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>>> CREATIVE COMMONS NZB <<< "dexter romweber duo-lookout" - File 1 of 9: "creative_commons_nzb_dexter_romweber_duo-lookout.rar" yEnc
		if (preg_match('/^(>>> CREATIVE COMMONS NZB <<< ".+?" - File )\d+ of \d+: ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<usenet-space-cowboys.info>>>  <<<Powered by https://secretusenet.com>< "Justin_Bieber-Believe_Acoustic-2013-pLAN9_usenet-space-cowbys.info.rar" >< 4/6 (78.65 MB) >< 60.84 MB > yEnc
		if (preg_match('/^(.+?usenet-space.+?Powered by.+? ".+?)' . $this->e0 . '.+? \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\) .+? \d+[.,]\d+ [kKmMgG][bB] .+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.sounds.mp3
	protected function sounds_mp3()
	{
		//(dream-of-usenet.info) - [04/15] - "Enya-And_Winter_Came...-2008.part2.rar" yEnc
		if (preg_match('/^\(dream-of-usenet\.info\) - \[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //http://dream-of-usenet.org empfehlen newsconnection.eu - [02/32] - "Adam_Ant-Manners_and_Physique-(MCAD-6315)-CD-FLAC-1989-2Eleven.par2" yEnc
		if (preg_match('/^http:\/\/dream-of-usenet\.org .+? - \[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //>>> CREATIVE COMMONS NZB <<< "dexter romweber duo-lookout" - File 1 of 9: "creative_commons_nzb_dexter_romweber_duo-lookout.rar" yEnc
		if (preg_match('/^(>>> CREATIVE COMMONS NZB <<< ".+?" - File )\d+ of \d+: ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //<<<usenet-space-cowboys.info>>>  <<<Powered by https://secretusenet.com>< "Justin_Bieber-Believe_Acoustic-2013-pLAN9_usenet-space-cowbys.info.rar" >< 4/6 (78.65 MB) >< 60.84 MB > yEnc
		if (preg_match('/^(.+?usenet-space.+?Powered by.+? ".+?)' . $this->e0 . '.+? \d+\/\d+ \(\d+[.,]\d+ [kKmMgG][bB]\) .+? \d+[.,]\d+ [kKmMgG][bB] .+?yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"The Absence - Riders Of The Plague" [00/14] - "the_absence-riders_of_the_plague.nzb" yEnc
		if (preg_match('/"(.+)"[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//( Albert Cummings Albums 6x By Dready Niek (1999-2012) )  ( ** By Dready Niek ** ) [11/20] - "Albert Cummings Albums 6x By Dready Niek (1999-2012).part10.rar" yEnc
		//( Fat Freddy's Drop - Blackbird (2013) -- By Dready Niek ) -- By Dready Niek ) [01/15] - "Fat Freddy's Drop - Blackbird (2013) -- By Dready Niek.par2" yEnc
		if (preg_match('/\( (.+?)\)[-_ ]{0,3}( |\().+\)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		} //( Addison_Road-Addison_Road-2008 ) [01/10] - "01. Addison Road - This Could Be Our Day.mp3" yEnc
		if (preg_match('/\( (.+?) \)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}".+(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(????) [0/8] - Crionics Post - Alice In Chains - Dirt REPOST"Alice In Chains - Dirt.nzb" yEnc
		if (preg_match('/^.+?\[\d+\/(\d+\][-_ ]{0,3}.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.sounds.mp3.complete_cd
	protected function sounds_mp3_complete_cd()
	{
		//The Refreshments - [1/9] - "The Refreshments - RockÂ´n Roll Christmas [2003].par2" yEnc
		if (preg_match('/(.+)[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]][-_ ]{0,3}".+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[BFMP3] [Barrelhouse_Time Frames.nzb] [00/18] yEnc
		if (preg_match('/^\[(.+?)\][-_ ]{0,3}\[(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}\])[-_ ]{0,3}[\(\[]\d+\/(\d+[\)\]])[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2] . $match[5];
		} //Metallica - Ride The Lightning    "01 - Fight Fire With Fire.mp3" yEnc
		if (preg_match('/^(.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(01/11) "Der Kleine Vampir Komplett - Folge 01 bis 18 www.brothers-of-usenet.org - empfehlen - Newsconnection.eu.nfo" yEnc
		if (preg_match('/^\(\d+\/(\d+\) ".+?) www\.brothers-of-usenet\.org - empfehlen - Newsconnection(\.eu)?' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //(D The Best Of Leon Russell [23/28] - The Best Of Leon Russell.vol00+01.PAR2  yEnc
		if (preg_match('/^\(D(\))? (.+) {1,2}\[\d+\/(\d+\]) - .+?([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4}) {1,2}yEnc$/', $this->subject, $match)) {
			return $match[2] . $match[3];
		}
		return $this->generic();
	}

	// a.b.sounds.mp3.dance
	protected function sounds_mp3_dance()
	{
		//[2707]Solarstone-Solarstone_Collected_Vol_1-ARDI3177-WEB-2012-TraX "02-solarstone_feat_kym_marsh-day_by_day_(red_jerry_smackthe_bigot_up_remix).mp3" - yEnc
		if (preg_match('/^\[\d+\](.+?)[-_ ]{0,3}("|#34;)(.+?)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.sounds.mp3.electronic
	protected function sounds_mp3_electronic()
	{
		//(03/10) "Washing Machines - Planet-E CH-Basel Ultimate Hardcore - 4.9.1993.vol00+01.PAR2" - 232.39 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[ -]{0,3}"([\w. &()\[\]\'-]{8,}?\b.?)' . $this->e2, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(2/7) "Cosmic Baby live at Energy 1995.vol00+01.PAR2" - 0 B - yEnc
		if (preg_match('/^\(\d+\/(\d+\))[ -]{0,3}"([\w. &()\[\]\'-]{8,}?\b.?)' . $this->e0 . '[- ]{0,3}\d+ [bB][- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[0sk]-[2002-12-06]-[idm]-[vinyl]-[4/5]-[Maps_And_Diagrams_-_Ti_Sta_Bene_Marone-(cact_001)-Vinyl-2002]--cact 001.sfv  yEnc
		if (preg_match('/^\[0sk\]-\[\d+-\d+-\d+\]-\[.+\]-\[.+\]-\[\d+\/(\d+\])-\[([\w. &()\[\]\'-]{8,})\]--.+? yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[10_years---happy_bday_absme]-[cd]-[04/15]-[va_-_spacewars_01-(plkcd003)-cd-1996-nwd]-01-Nuw Idol - Union Of Ilk -nwd-.mp3  yEnc
		if (preg_match('/^\[.+\]-\[.+\]-\[\d+\/(\d+\])-\[([\w. &()\[\]\'-]{8,})\]-.+? yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Alec Empire - Low On Ice  06 - Untitled.mp3  yEnc
		if (preg_match('/^([\w. &()\[\]\'-]{8,}?)[ -]{0,3}\d+[ -]{0,3}[\w., &()\[\]\'-]{8,}?\b.?([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[ -]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.teevee
	protected function teevee()
	{
		//[278997]-[FULL]-[#a.b.erotica]-[ chi-the.walking.dead.xxx ]-[06/51] - "chi-the.walking.dead.xxx-s.mp4" yEnc
		//[######]-[FULL]-[#a.b.teevee@EFNet]-[ Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD ] [1/5] - "Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD.nfo" yEnc
		//Re: [147053]-[FULL]-[#a.b.teevee]-[ Top_Gear.20x04.HDTV_x264-FoV ]-[11/59] - "top_gear.20x04.hdtv_x264-fov.r00" yEnc (01/20)
		if (preg_match('/(\[[\d#]+\]-\[.+?\]-\[.+?\])-\[ (.+?) \][- ]\[\d+\/\d+\][ -]{0,3}".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[185409]-[FULL]-[a.b.teeveeEFNet]-[ Dragon.Ball.Z.S03E24.1080p.WS.BluRay.x264-CCAT ]-"dragon.ball.z.s03e24.1080p.ws.bluray.x264-ccat.nfo" yEnc
		if (preg_match('/(\[[\d#]+\]-\[.+?\]-\[.+?\])-\[ (.+?) \][ -]{0,3}".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //[#a.b.teevee] Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD - [01/24] - "Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD.nfo" yEnc
		if (preg_match('/^(\[#a\.b\.teevee\] .+? - \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //ah63jka93jf0jh26ahjas558 - [01/22] - "ah63jka93jf0jh26ahjas558.par2" yEnc
		if (preg_match('/^([a-z0-9]+ - )\[\d+\/\d+\] - "[a-z0-9]+\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //fhdbg34rgjdsfd008c (42/43) "fhdbg34rgjdsfd008c.vol062+64.par2" - 3,68 GB - yEnc
		if (preg_match('/^([a-z0-9]+ \()\d+\/\d+\) ".+?" - \d+[,.]\d+ [mMkKgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //t2EI3CdWdF0hi5b8L9tkx[08/52] - "t2EI3CdWdF0hi5b8L9tkx.part07.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9]+)\[\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//(01/37) "Entourage S08E08.part01.rar" - 349,20 MB - yEnc
		//(01/24) "EGtnu7OrLNQMO2pDbgpDrBL8SnjZDpab.nfo" - 686 B - 338.74 MB - yEnc (1/1)
		if (preg_match('/^\(\d+(\/\d+\) ".+?)' . $this->e0 . ' - \d.+?B - (\d.+?B -)? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[01/42] - "King.And.Maxwell.S01E08.1080p.WEB-DL.DD5.1.H264-Abjex.par2" yEnc
		if (preg_match('/^\[\d+(\/\d+\] - "[A-Za-z0-9.-]+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//Divers (12/42) -"Juste.Pour.Rire.2013.Gala.JF.Mercier.FRENCH.720p.HDTV.x264-QuebecRules.part11.rar" yEnc
		//Par le chapeau (06/43) - "8C7D59F472E03.part04.rar" yEnc
		if (preg_match('/^([a-zA-Z0-9 ]+ \()\d+(\/\d+\) - ?".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//House.Hunters.International.S05E502.720p.hdtv.x264 [01/27] - "House.Hunters.International.S05E502.720p.hdtv.x264.nfo" yEnc
		//Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS - [01/33] - "Criminal.Minds.S03E01.Doubt.PROPER.DVDRip.XviD-SAiNTS.par2" yEnc
		if (preg_match('/^(Re: )?([a-zA-Z0-9._-]+([{}A-Z_]+)?( -)? \[)\d+(\/| of )\d+\]( -)? ".+?" yEnc$/', $this->subject, $match)) {
			return $match[2];
		} //[ Anthony.Bourdain.Parts.Unknown.S01.480p.HDTV.x264-mSD ] MKV.h264 (03/17) - "Anthony.Bourdain.Parts.Unknown.S01E01.480p.HDTV.x264-mSD.mkv" yEnc
		if (preg_match('/^\[ (.+?) \] .+ \(\d+\/(\d+\)) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Silent Witness S15E02 Death has no dominion.par2 [01/44] - yEnc
		if (preg_match('/^([a-zA-Z0-9 ]+)(\.part\d*|\.rar)?(\.vol.+? |\.[A-Za-z0-9]{2,4} )\[\d+(\/\d+\] - yEnc)$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //(bf1) [03/31] - "The.Block.AU.Sky.High.S07E61.WS.PDTV.XviD.BF1.part01.sfv" yEnc (1/1)
		if (preg_match('/^\(bf1\) \[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//[ TVPower ] - "Dexter.S07E10.720p.HDTV.x264-NLsubs.par2" yEnc
		//[ TVPower ] - [010/101] - "Desperate.Housewives.S08Disc2.NLsubs.part009.rar" yEnc
		if (preg_match('/^(\[ [A-Za-z]+ \] - (\[\d+\/\d+\] - )?".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[www.allyourbasearebelongtous.pw]-[WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams]-[03/69] "WWE.Monday.Night.Raw.2013.07.22.HDTV.x264-IWStreams.r00" - 1.58 GB - yEnc
		if (preg_match('/^(\[.+?\]-\[.+?\]-\[)\d+\/\d+\] ".+?" - \d+([.,]\d+ [kKmMgG])?[bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(www.Thunder-News.org) >CD1< <Sponsored by Secretusenet> - "moovee-fastest.cda.par2" yEnc
		if (preg_match('/^(\(www\..+?\) .+? <Sponsored.+?> - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //<<<Pitbull>>> usenet-space-cowboys.info <<<Powered by https://secretusenet.com>< "S05E03 Pack die Badehose ein_usenet-space-cowbys.info.par2" >< 01/10 (411,16 MB) >< 3,48 kB > yEnc
		if (preg_match('/(\.info .+?Powered by .+?\.com ".+?)' . $this->e0 . ' .+? \d+\/\d+ \(\d+[,.]\d+ [mMkKgG][bB]\) .+? yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //Newport Harbor The Real Orange County - S01E01 - A Black & White Affair [01/11] - "Newport Harbor The Real Orange County - S01E01 - A Black & White Affair.mkv" yEnc
		if (preg_match('/^([a-zA-Z0-9]+ .+? - S\d+E\d+ - .+? \[)\d+\/\d+\] - ".+?\..+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Mad.Men.S06E11.HDTV.x264-2HD.par2" yEnc
		if (preg_match('/^"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //"Marvels.Agents.of.S.H.I.E.L.D.S01E07.HDTV.XviD-FUM.avi.nfo" [09/16] yEnc
		if (preg_match('/^"(.+?)' . $this->e0 . '[ _-]{0,3}\[\d+\/(\d+\])[ _-]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		} //[185891]-[ Suits.S01E01.720p.BluRay.X264-REWARD ]- "Suits.S01E01.720p.BluRay.par2" yEnc
		if (preg_match('/^\[\d+\]-\[ (.+?) \]- ".+?" yEnc$/i', $this->subject, $match)) {
			return $match[1];
		} //.. - [01/42] - "Under.the.Dome.S01E04.Outbreak.1080p.WEB-DL.DD5.1.H.264-NTb.par2" yEnc
		if (preg_match('/^\.\.[- ]{0,3}\[\d+\/(\d+\])[- ]{0,3}"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.town
	protected function town()
	{
		//<TOWN><www.town.ag > <download all our files with>>> www.ssl-news.info <<< > [05/87] - "Deep.Black.Ass.5.XXX.1080p.WEBRip.x264-TBP.part03.rar" - 7,87 GB yEnc
		if (preg_match('/town\.ag.+?download all our files with.+?www\..+?\.info.+? \[\d+(\/\d+\] - ".+?)(-sample)?' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		//"Armored_Core_V_PS3-ANTiDOTE__www.realmom.info__.r00" (03/78) 3,32 GB yEnc
		if (preg_match('/^"(.+)__www.realmom.info__.+" \(\d+\/(\d+\)) \d+[.,]\d+ [kKmMgG][bB] yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.tun
	protected function tun()
	{
		//[PRiVATE] UmVndWxhci5TaG93LlMwMkUyOC4xMDgwcC5CbHVSYXkueDI2NC1ERWlNT1M= [06/32] - "89769f0736162e1cb113655cb10e42ff.part02.rar" yEnc
		if (preg_match('/^(\[PRiVATE\] [a-z0-9A-Z]+=+ \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //[ nEwZ[NZB].iNFO - [ Zed--The_Invitation-WEB-2010-WUS ] - File [12/13]: "08-zed--the_river.mp3" yEnc
		if (preg_match('/^\[ nEwZ\[NZB\]\.iNFO( \])?[-_ ]{0,3}\[ (.+?) \][-_ ]{0,3}(File )?[\(\[]\d+\/(\d+[\)\]]): "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}").+?yEnc$/', $this->subject, $match)) {
			return $match[2] . $match[4];
		} //nEwZ[NZB].iNFO - VA-Universal_Music_Sampler_07_February-PROMO-CDR-FLAC-2013-WRE - File [6/9]: "01-alesso-years_(hard_rock_sofa_remix).flac"
		if (preg_match('/^nEwZ\[NZB\]\.iNFO[-_ ]{0,3} (.+?) [-_ ]{0,3}(File )?[\(\[]\d+\/(\d+[\)\]]): "(.+)(\.part\d*|\.rar)?(\.vol.+ \(\d+\/\d+\) "|\.[A-Za-z0-9]{2,4}")$/', $this->subject, $match)) {
			return $match[1] . $match[3];
		}
		return $this->generic();
	}

	// a.b.tv
	protected function tv()
	{
		//Borgen.2x02.A.Bruxelles.Non.Ti.Sentono.Urlare.ITA.BDMux.x264-NovaRip [02/22] - "borgen.2x02.ita.bdmux.x264-novarip.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9.-]+ \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //(bf1) [03/31] - "The.Block.AU.Sky.High.S07E56.WS.PDTV.XviD.BF1.part01.sfv" yEnc
		if (preg_match('/^\(bf1\) \[\d+(\/\d+\] - ".+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		} //[014/144] - The Bible (2013) - S1E01 - In the Beginning AVC 480p.vol31+27.PAR2  yEnc
		if (preg_match('/^\[\d+\/(\d+\])[- ]{0,3}([\w.() -]{8,}?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		} //Saturday Morning Classic Return-to-the-Planet-of-the-Apes-S01E03 - Lagoon of Peril.avi.001  yEnc
		if (preg_match('/^Saturday Morning Classic ([\w. -]{8,}?)([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev|\.vol.+?|\.[A-Za-z0-9]{2,4})[- ]{0,3}yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	// a.b.tvseries
	protected function tvseries()
	{
		//"Forbrydelsen.II.S01E03.2009.DVDRip.MULTi.DD5.1.x264.nzb" - 213.54 kB - yEnc
		//"Futurama S07E01 The Bots And The Bees.vol26+23.PAR2" - 8.49 MB - 193.51 MB - yEnc
		if (preg_match('/^"(.+?)' . $this->e0 . '( - \d+([.,]\d+ [kKmMgG])?[bB])? - \d+([.,]\d+ [kKmMgG])?[bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //"Rijdende.Rechter.-.19x01.-.Huisbiggen.1080p.MKV-BNABOYZ.part38.rar" - [40/56] - yEnc
		if (preg_match('/^"(.+?)' . $this->e0 . ' - \[\d+\/(\d+\]) - yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[7];
		} //(003/104) "blackcave1001.part002.rar" - 4,83 GB - yEnc
		if (preg_match('/^\(\d+\/(\d+\) ".+?)' . $this->e0 . ' - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //X-Men Evolution - 2000 -  [01/20] - "X-Men Evolution - 3x03 - Mainstream.par2" yEnc
		if (preg_match('/^[a-zA-Z0-9 -_\.]+ \[\d+\/(\d+\]) - "(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		//'X-Files' Season 1 XviD RETRY  "Files101.par2" 004/387
		//'X-Files' Season 5 XviD "Files502.par2" 018/321 yEnc
		//'X-Files' Season 2 XviD  "Files223.part2.rar" 356/401  yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\.]+) (RETRY)?[-_ ]{0,3}".+?' . $this->e0 . ' \d+(\/\d+)( {0,2}yEnc)?$/', $this->subject, $match)) {
			return $match[1] . $match[8];
		} //"the.tudors.s03e03.nfo" yEnc
		if (preg_match('/^"(.+?)' . $this->e1, $this->subject, $match)) {
			return $match[1];
		}
		//(08/25) "Wild Russia 5 of 6 The Secret Forest 2009.part06.rar" - 47.68 MB - 771.18 MB - yEnc
		//(01/24) "ITV Wild Britain With Ray Mears 1 of 6 Deciduous Forest 2011.nfo" - 4.34 kB - 770.97 MB - yEnc
		//(24/24) "BBC Great British Garden Revival 03 of 10 Cottage Gardens And House Plants 2013.vol27+22.PAR2" - 48.39 MB - 808.88 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "((BBC|ITV) )?(.+?)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|") - \d.+? - (\d.+? -)? yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //Chuck - Season2 (015/164) - "2x04.par2" yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\.]+) \(\d+(\/\d+\)) - ".+?' . $this->e1, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //FIRST.WAVE.NTSC.DVD.DD2.0 - [121/512] - "FIRST_WAVE_SEASON_1_DVD2.r26" - 44,33 GB - yEnc
		if (preg_match('/^([a-zA-Z0-9 -_\.]+) - \[\d+(\/\d+\]) - ".+?' . $this->e2, $this->subject, $match)) {
			return $match[1] . $match[2];
		} //(03/19) "Damages S04E02 FR.avi" - 5,49 GB yEnc
		if (preg_match('/^\(\d+(\/\d+\)) "(.+?)' . $this->e2, $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	//a.b.warez
	protected function warez()
	{
		//-Panzer.Command.Kharkov-SKIDROW - [1/7] - "-Panzer.Command.Kharkov-SKIDROW.rar" yEnc
		//-AssMasterpiece.12.07.09.Alexis.Monroe.XXX.1080p.x264-SEXORS - [1/7] - #34;-AssMasterpiece.12.07.09.Alexis.Monroe.XXX.1080p.x264-SEXORS.rar#34; yEnc
		if (preg_match('/.*[\(\[]\d+\/(\d+[\)\]][-_ ]{0,3}("|#34;).+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //- "JH2U0H5FIK8TO7YK3Q.part12.rar" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //( XS Video Converter Ultimate 7.7.0 Build 20121226 ) - yEnc
		if (preg_match('/^\( (.+?) \) - yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	//a.b.warez.0day
	protected function warez_0day()
	{
		//-Panzer.Command.Kharkov-SKIDROW - [1/7] - "-Panzer.Command.Kharkov-SKIDROW.rar" yEnc
		//-AssMasterpiece.12.07.09.Alexis.Monroe.XXX.1080p.x264-SEXORS - [1/7] - #34;-AssMasterpiece.12.07.09.Alexis.Monroe.XXX.1080p.x264-SEXORS.rar#34; yEnc
		if (preg_match('/.*[\(\[]\d+\/(\d+[\)\]][-_ ]{0,3}("|#34;).+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4})("|#34;)(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //- "JH2U0H5FIK8TO7YK3Q.part12.rar" yEnc
		if (preg_match('/.*"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}")(.+?)yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //( XS Video Converter Ultimate 7.7.0 Build 20121226 ) - yEnc
		if (preg_match('/^\( (.+?) \) - yEnc$/', $this->subject, $match)) {
			return $match[1];
		} //BabysitterMovies.13.03.11.Babysitter.Jocelyn.Pink.XXX.HR.WMV-VSEX - [7/7] - "BabysitterMovies.13.03.11.Babysitter.Jocelyn.Pink.XXX.HR.WMV-VSEX.rar.vol15+5.par2" yEnc
		if (preg_match('/^([a-zA-Z].+) - \[\d+\/(\d+\]) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// a.b.worms
	protected function worms()
	{
		//[U4A] 2 Dudes and a Dream 2009 BRRip XvidHD 720p-NPW[01/36] - "2 Dudes and a Dream 2009 BRRip XvidHD 720p-NPW-Sample.avi" yEnc
		if (preg_match('/^(\[U4A]) (.+?)\[\d+(\/\d+\]) - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2] . $match[3];
		}
		//(12/57) "Fright.Night.2.New.Blood.2013.UNRATED.BluRay.810p.DTS.x264-PRoDJi.nfo" - 4,81 GB - yEnc
		//(14/20) "Jack.the.Giant.Slayer.2013.AC3.192Kbps.23fps.2ch.TR.Audio.BluRay-Demuxed.by.par2" - 173,15 MB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) ("|#34;)(.+)(\.[vol|part].+)?\.(par2|nfo|rar|nzb)("|#34;) - \d+[.,]\d+ [kKmMgG][bB] - yEnc$/i', $this->subject, $match)) {
			return $match[1] . $match[3];
		}
		return $this->generic();
	}

	// a.b.x264
	protected function x264()
	{
		//"Batman-8 TDKR-Pittis AVCHD-ADD.English.dtsHDma.part013.rar" (042/197) yEnc
		if (preg_match('/^"(.+?)(\.part\d*|\.rar)?(\.vol.+?"|\.[A-Za-z0-9]{2,4}") \(\d+\/(\d+\)) yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[4];
		} //(001/108) "Wizards.of.Waverly.Place.720p.S04E01.by.sugarr.par2" - 5,15 GB - yEnc
		if (preg_match('/^\(\d+\/(\d+\)) "(.+?)' . $this->e0 . ' - \d+[,.]\d+ [mMkKgG][bB]( -)? yEnc$/', $this->subject, $match)) {
			return $match[1] . $match[2];
		}
		return $this->generic();
	}

	// dk.binaer.tv
	protected function dk_tv()
	{
		//Store.Boligdroemme.S02E06.DANiS H.HDTV.x264-TVBYEN - [01/28] - "store.boligdroemme.s02e06.danis h.hdtv.x264-tvbyen.nfo" yEnc
		if (preg_match('/^([a-zA-Z0-9].+? - \[)\d+\/\d+\] - ".+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		}
		return $this->generic();
	}

	//	Cleans usenet subject before inserting, used for collectionhash. If no regexes matched on collectionsCleaner.
	protected function generic()
	{
		// For non music groups.
		if (!preg_match('/\.(flac|lossless|mp3|music|sounds)/', $this->groupName)) {
			// File/part count.
			$cleanSubject = preg_replace('/((( \(\d\d\) -|(\d\d)? - \d\d\.|\d{4} \d\d -) | - \d\d-| \d\d\. [a-z]).+| \d\d of \d\d| \dof\d)\.mp3"?|(\)|\(|\[|\s)\d{1,5}(\/|(\s|_)of(\s|_)|-)\d{1,5}(\)|\]|\s|$|:)|\(\d{1,3}\|\d{1,3}\)|[^\d]{4}-\d{1,3}-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s|\d{1,3} - of \d{1,3}/i', ' ', $this->subject);
			// File extensions.
			$cleanSubject = preg_replace('/' . $this->e0 . '/i', ' ', $cleanSubject);
			// File extensions - If it was not in quotes.
			$cleanSubject = preg_replace('/(-? [a-z0-9]+-?|\(?\d{4}\)?(_|-)[a-z0-9]+)\.jpg"?| [a-z0-9]+\.mu3"?|((\d{1,3})?\.part(\d{1,5})?|\d{1,5} ?|sample|- Partie \d+)?\.(7z|\d{3}(?=(\s|"))|avi|diz|docx?|epub|idx|iso|jpg|m3u|m4a|mds|mkv|mobi|mp4|nfo|nzb|par(\s?2|")|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|xls|zip|z{2,3})"?|(\s|(\d{2,3})?-)\d{2,3}\.mp3|\d{2,3}\.pdf|\.part\d{1,4}\./i', ' ', $cleanSubject);
			// File Sizes - Non unique ones.
			$cleanSubject = preg_replace('/\d{1,3}(,|\.|\/)\d{1,3}\s(k|m|g)b|(\])?\s\d+KB\s(yENC)?|"?\s\d+\sbytes?|[- ]?\d+(\.|,)?\d+\s(g|k|m)?B\s-?(\s?yenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s|yEnc \d+k$|{\d+ yEnc bytes}|yEnc \d+ |\(\d+ ?(k|m|g)?b(ytes)?\) yEnc$/i', ' ', $cleanSubject);
			// Random stuff.
			$cleanSubject = preg_replace('/AutoRarPar\d{1,5}|\(\d+\)( |  )yEnc|\d+(Amateur|Classic)| \d{4,}[a-z]{4,} |part\d+/i', ' ', $cleanSubject);
			// Multi spaces.
			return utf8_encode(trim(preg_replace('/\s\s+/', ' ', $cleanSubject)));
		} // Music groups.
		else {
			// Try some music group regexes.
			$musicSubject = $this->musicSubject();
			if ($musicSubject !== false) {
				return $musicSubject;
				// Parts/files
			} else {
				$cleanSubject = preg_replace('/((( \(\d\d\) -|(\d\d)? - \d\d\.|\d{4} \d\d -) | - \d\d-| \d\d\. [a-z]).+| \d\d of \d\d| \dof\d)\.mp3"?|(\(|\[|\s)\d{1,4}(\/|(\s|_)of(\s|_)|-)\d{1,4}(\)|\]|\s|$|:)|\(\d{1,3}\|\d{1,3}\)|-\d{1,3}-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s|\d{1,3} - of \d{1,3}/i', ' ', $this->subject);
			}
			// Anything between the quotes. Too much variance within the quotes, so remove it completely.
			$cleanSubject = preg_replace('/".+"/i', ' ', $cleanSubject);
			// File extensions - If it was not in quotes.
			$cleanSubject = preg_replace('/(-? [a-z0-9]+-?|\(?\d{4}\)?(_|-)[a-z0-9]+)\.jpg"?| [a-z0-9]+\.mu3"?|((\d{1,3})?\.part(\d{1,5})?|\d{1,5} ?|sample|- Partie \d+)?\.(7z|\d{3}(?=(\s|"))|avi|diz|docx?|epub|idx|iso|jpg|m3u|m4a|mds|mkv|mobi|mp4|nfo|nzb|par(\s?2|")|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|xls|zip|z{2,3})"?|(\s|(\d{2,3})?-)\d{2,3}\.mp3|\d{2,3}\.pdf|\.part\d{1,4}\./i', ' ', $cleanSubject);
			// File Sizes - Non unique ones.
			$cleanSubject = preg_replace('/\d{1,3}(,|\.|\/)\d{1,3}\s(k|m|g)b|(\])?\s\d+KB\s(yENC)?|"?\s\d+\sbytes?|[- ]?\d+[.,]?\d+\s(g|k|m)?B\s-?(\s?yenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s|yEnc \d+k$|{\d+ yEnc bytes}|yEnc \d+ |\(\d+ ?(k|m|g)?b(ytes)?\) yEnc$/i', ' ', $cleanSubject);
			// Random stuff.
			$cleanSubject = preg_replace('/AutoRarPar\d{1,5}|\(\d+\)( |  )yEnc|\d+(Amateur|Classic)| \d{4,}[a-z]{4,} |part\d+/i', ' ', $cleanSubject);
			// Multi spaces.
			$cleanSubject = utf8_encode(trim(preg_replace('/\s\s+/i', ' ', $cleanSubject)));
			// If the subject is too similar to another because it is so short, try to extract info from the subject.
			if (strlen($cleanSubject) <= 10 || preg_match('/^[-a-z0-9$ ]{1,7}yEnc$/i', $cleanSubject)) {
				$x = '';
				if (preg_match('/.*("[A-Z0-9]+).*?"/i', $this->subject, $match)) {
					$x = $match[1];
				}
				if (preg_match_all('/[^A-Z0-9]/i', $this->subject, $match1)) {
					$start = 0;
					foreach ($match1[0] as $add) {
						if ($start > 2) {
							break;
						}
						$x .= $add;
						$start++;
					}
				}
				$newName = preg_replace('/".+?"/', '', $this->subject);
				$newName = preg_replace('/[a-z0-9]|' . $this->e0 . '/i', '', $newName);
				return $cleanSubject . $newName . $x;
			} else {
				return $cleanSubject;
			}
		}
	}

	// Generic regexes for music groups.
	protected function musicSubject()
	{
		//Broderick_Smith-Unknown_Country-2009-404 "00-broderick_smith-unknown_country-2009.sfv" yEnc
		if (preg_match('/^(\w{10,}-[a-zA-Z0-9]+ ")\d\d-.+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} else {
			return false;
		}
	}
}
