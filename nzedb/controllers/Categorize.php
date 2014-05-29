<?php

/**
 * Categorizing of releases by name/group.
 *
 * Class Categorize
 */
class Categorize extends Category
{
	/**
	 * @var bool
	 */
	protected $categorizeForeign;

	/**
	 * @var bool
	 */
	protected $catWebDL;

	/**
	 * Temporary category while we sort through the name.
	 * @var int
	 */
	protected $tmpCat = Category::CAT_MISC;

	/**
	 * Release name to sort through.
	 * @var string
	 */
	protected $releaseName;

	/**
	 * Group ID of the releasename we are sorting through.
	 * @var int
	 */
	protected $groupID;

	/**
	 * Construct.
	 */
	public function __construct()
	{
		$s = new Sites();
		$site = $s->get();
		$this->categorizeForeign = ($site->categorizeforeign == "0") ? false : true;
		$this->catWebDL = ($site->catwebdl == "0") ? false : true;
		parent::__construct();
	}

	/**
	 * Look up the site to see which language of categorizing to use.
	 * Then work out which category is applicable for either a group or a binary.
	 * Returns Category::CAT_MISC if no category is appropriate.
	 *
	 * @param string     $releaseName The name to parse.
	 * @param string|int $groupID     The groupID.
	 *
	 * @return int The categoryID.
	 */
	public function determineCategory($releaseName = '', $groupID)
	{
		$this->releaseName = $releaseName;
		$this->groupID     = $groupID;
		$this->tmpCat      = Category::CAT_MISC;

		if ($this->isMisc())    { return $this->tmpCat; }

		// Note that in byGroup() some overrides occur...
		if ($this->byGroup())   { return $this->tmpCat; }

		//Try against all functions, if still nothing, return Cat Misc.
		if ($this->isPC())      { return $this->tmpCat; }
		if ($this->isXXX())     { return $this->tmpCat; }
		if ($this->isTV())      { return $this->tmpCat; }
		if ($this->isMusic())   { return $this->tmpCat; }
		if ($this->isMovie())   { return $this->tmpCat; }
		if ($this->isConsole()) { return $this->tmpCat; }
		if ($this->isBook())    { return $this->tmpCat; }

		return $this->tmpCat;
	}

	/**
	 * Determine category by group name.
	 *
	 * @return bool
	 */
	public function byGroup()
	{
		$group = $this->db->queryOneRow(sprintf('SELECT LOWER(name) AS name FROM groups WHERE id = %d', $this->groupID));
		if ($group !== false) {
			$group = $group['name'];

			if ($group === 'alt.binaries.0day.stuffz') {
				if ($this->isBook()) {
					return true;
				} else if ($this->isPC()) {
					return true;
				}
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if ($group === 'alt.binaries.audio.warez') {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.erotica\.|cartoons\.french\.|dvd\.|multimedia\.)?anime(\.highspeed|\.repost|s-fansub|\.german)?/', $group)) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if ($this->categorizeForeign && $group === 'alt.binaries.cartoons.french') {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if ($group === 'alt.binaries.cd.image.linux') {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if ($group === 'alt.binaries.cd.lossless') {
				if ($this->categorizeForeign && $this->isMusicForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if ($group === 'alt.binaries.classic.tv.shows') {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(comics\.dcp|pictures\.comics\.(complete|dcp|reposts?))/', $group)) {
				if ($this->categorizeForeign && $this->isBookForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}

			if ($group === 'alt.binaries.console.ps3') {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}

			if ($group === 'alt.binaries.cores') {
				if ($this->isXxx()) {
					return true;
				}
				return false;
			}

			if (preg_match('/alt\.binaries(\.(19\d0s|country|sounds?(\.country|\.19\d0s)?))?\.mp3(\.[a-z]+)?/i', $group)) {
				if ($this->isMusic()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.dvd(\-?r)?(\.(movies|))?$/i', $group)) {
				if ($this->isMovie()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MISC;
				return true;
			}

			if ($this->categorizeForeign && preg_match('/alt\.binaries\.(dvdnordic\.org|nordic\.(dvdr?|xvid))|dk\.(binaer|binaries)\.film(\.divx)?/', $group)) {
				$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
				return true;
			}

			if ($group === 'alt.binaries.documentaries') {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?books?((\.|\-)(technical|textbooks))/', $group)) {
				if ($this->categorizeForeign && $this->isBookForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}

			if ($group === 'alt.binaries.e-book.magazines') {
				if ($this->categorizeForeign && $this->isBookForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_BOOKS_MAGAZINES;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $group)) {
				if ($this->is0day()) {
					return true;
				}
				if ($this->isConsole()) {
					return true;
				}

				if ($this->isBook()) {
					return true;
				}

				if ($this->categorizeForeign && $this->isBookForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MISC;
				return true;
			}

			if (preg_match('/alt\.binaries\..*(erotica|ijsklontje|xxx)/', $group)) {
				if ($this->isXxx()) {
					return true;
				}
				$this->tmpCat = Category::CAT_XXX_OTHER;
				return true;
			}

			if ($group === 'alt.binaries.games.dox') {
				$this->tmpCat = Category::CAT_PC_GAMES;
				return true;
			}

			if (preg_match('/alt.binaries.cd.images?.games/', $group)) {
				if ($this->isConsole()) {
					return true;
				}
				$this->tmpCat = Category::CAT_PC_GAMES;
				return true;
			}

			if (preg_match('/alt\.binaries(\.games)?\.nintendo(\.)?ds/', $group)) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}

			if ($group === 'alt.binaries.games.wii') {
				if ($this->isGameWiiWare()) {
					return true;
				}
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}

			if ($group === 'alt.binaries.games.xbox') {
				if ($this->isGameXBOX360DLC()) {
					return true;
				}
				if ($this->isGameXBOX360()) {
					return true;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX;
				return true;
			}

			if ($group === 'alt.binaries.games.xbox360') {
				if ($this->isGameXBOX360DLC()) {
					return true;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}

			if ($group === 'alt.binaries.inner-sanctum') {
				if ($this->isMusic()) {
					return true;
				}
				if (preg_match('/-+(19|20)\d\d-\(?(album.*?|back|cover|front)\)?-+/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_OTHER;
					return true;
				} else if (preg_match('/(19|20)\d\d$/', $this->releaseName) && ctype_lower(preg_replace('/[^a-z]/i', '', $this->releaseName))) {
					$this->tmpCat = Category::CAT_MUSIC_OTHER;
					return true;
				}
				return false;
			}

			if (preg_match('/alt\.binaries\.ipod\.videos\.tvshows/', $group)) {
				$this->tmpCat = Category::CAT_TV_OTHER;
				return true;
			}

			if ($group === 'alt.binaries.mac') {
				$this->tmpCat = Category::CAT_PC_MAC;
				return true;
			}

			if ($group === 'alt.binaries.mma') {
				if ($this->is0day()) {
					return true;
				}
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if ($group === 'alt.binaries.moovee') {
				//check if it's TV first as some tv posted in moovee
				if ($this->isTV()) {
					return true;
				}
				// Check the movie isn't an HD release before blindly assigning SD
				if ($this->isMovieHD()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MOVIE_SD;
				return true;
			}

			if ($group === 'alt.binaries.mpeg.video.music') {
				if ($this->categorizeForeign && $this->isMusicForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}

			if ($group === 'alt.binaries.multimedia.documentaries') {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.sports(\.boxing)?/', $group)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if ($group === 'alt.binaries.music.opera') {
				if ($this->categorizeForeign && $this->isMusicForeign()) {
					return true;
				}

				if (preg_match('/720p|[-._ ]mkv/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_VIDEO;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.music/', $group)) {
				if ($this->categorizeForeign && $this->isMusicForeign()) {
					return true;
				}
				if ($this->isMusic()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/audiobook/', $group)) {
				if ($this->categorizeForeign && $this->isMusicForeign()) {
					return true;
				}

				$this->tmpCat = Category::CAT_MUSIC_AUDIOBOOK;
				return true;
			}

			if ($group === 'alt.binaries.pro-wrestling') {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.(flac(\.jazz)|jpop|lossless(\.[a-z0-9]+)?)|alt\.binaries\.(cd\.lossless|music\.flac)/i', $group)) {
				if ($this->categorizeForeign && $this->isMusicForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if ($group === 'alt.binaries.sounds.whitburn.pop') {
				if ($this->categorizeForeign && $this->isMusicForeign()) {
					return true;
				}

				if (!preg_match('/[-._ ]scans[-._ ]/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_MP3;
					return true;
				}
				return false;
			}

			if ($group === 'alt.binaries.sony.psp') {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}

			if ($group === 'alt.binaries.warez') {
				if ($this->isTV()) {
					return true;
				}

				if ($this->isPC()) {
					return true;
				}
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if ($group === 'alt.binaries.warez.smartphone') {
				if ($this->isPhone()) {
					return true;
				}
				$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
				return true;
			}

			if ($this->categorizeForeign && $group === 'db.binaer.tv') {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	//
	// Beginning of functions to determine category by release name.
	//

	//	TV.
	public function isTV()
	{
//		if (/*!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}|season|episode/i', $this->releaseName) &&*/ preg_match('/part[-._ ]?\d/i', $this->releaseName)) {
//			return false;
//		}

		if (preg_match('/Daily[-_\.]Show|Nightly News|(\d\d-){2}[12]\d{3}|[12]\d{3}(\.\d\d){2}|\d+x\d+|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[-._ ](\dx\d\d|C4TV|Complete[-._ ]Season|DSR|(D|H|P)DTV|EP[-._ ]?\d{1,3}|S\d{1,3}.+Extras|SUBPACK|Season[-._ ]\d{1,2}|WEB\-DL|WEBRip)([-._ ]|$)|TV[-._ ](19|20)\d\d|TrollHD/i', $this->releaseName)
			&& !preg_match('/[-._ ](flac|imageset|mp3|xxx)[-._ ]|[ .]exe$/i', $this->releaseName)) {

			if ($this->isOtherTV()) {
				return true;
			}

			if ($this->categorizeForeign && $this->isForeignTV()) {
				return true;
			}

			if ($this->isSportTV()) {
				return true;
			}

			if ($this->isDocumentaryTV()) {
				return true;
			}

			if ($this->catWebDL && $this->isWEBDL()) {
				return true;
			}

			if ($this->isHDTV()) {
				return true;
			}

			if ($this->isSDTV()) {
				return true;
			}

			if ($this->isAnimeTV()) {
				return true;
			}

			if ($this->isOtherTV2()) {
				return true;
			}

			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}

		if (preg_match('/[-._ ]((19|20)\d\d[-._ ]\d{1,2}[-._ ]\d{1,2}[-._ ]VHSRip|Indy[-._ ]?Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW[-._ ]TV|(Per|Post)\-Show|PPV|WrestleMania|WCW|WEB[-._ ]HD|WWE[-._ ](Monday|NXT|RAW|Smackdown|Superstars|WrestleMania))[-._ ]/i', $this->releaseName)) {
			if ($this->isSportTV()) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isOtherTV()
	{
		if (preg_match('/[-._ ](S\d{1,3}.+Extras|SUBPACK)[-._ ]|News/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isForeignTV()
	{
		if (!preg_match('/[-._ ](NHL|stanley.+cup)[-._ ]/', $this->releaseName)) {
			if (preg_match('/[-._ ](chinese|dk|fin|french|ger|heb|ita|jap|kor|nor|nordic|nl|pl|swe)[-._ ]?(sub|dub)(ed|bed|s)?|<German>/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish).+(720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid|x264)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid).+(brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/(S\d\d[EX]\d\d|DOCU(MENTAIRE)?|TV)?[-._ ](FRENCH|German|Dutch)[-._ ](720p|1080p|dv(b|d)r(ip)?|LD|HD\-?TV|TV[-._ ]?RIP|x264)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ]FastSUB|NL|nlvlaams|patrfa|RealCO|Seizoen|slosinh|Videomann|Vostfr|xslidian[-._ ]|x264\-iZU/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isSportTV()
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])/i', $this->releaseName)) {
			if (preg_match('/[-._ ]?(Bellator|bundesliga|EPL|ESPN|FIA|la[-._ ]liga|MMA|motogp|NFL|NCAA|PGA|red[-._ ]bull.+race|Sengoku|Strikeforce|supercup|uefa|UFC|wtcc|WWE)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(DTM|FIFA|formula[-._ ]1|indycar|Rugby|NASCAR|NBA|NHL|NRL|netball[-._ ]anz|ROH|SBK|Superleague|The[-._ ]Ultimate[-._ ]Fighter|TNA|V8[-._ ]Supercars|WBA|WrestleMania)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(AFL|Grand Prix|Indy[-._ ]Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW|Poker|PWX|Rugby|WCW)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(Horse)[-._ ]Racing[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}
		}
		return false;
	}

	public function isDocumentaryTV()
	{
		if (preg_match('/[-._ ](Docu|Documentary)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
			return true;
		}
		return false;
	}

	public function isWEBDL()
	{
		if (preg_match('/web[-._ ]dl/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_WEBDL;
			return true;
		}
		return false;
	}

	public function isHDTV()
	{
		if (preg_match('/1080(i|p)|720p/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_HD;
			return true;
		}
		if ($this->catWebDL == false) {
			if (preg_match('/web[-._ ]dl/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_HD;
				return true;
			}
		}
		return false;
	}

	public function isSDTV()
	{
		if (preg_match('/(360|480|576)p|Complete[-._ ]Season|dvdr(ip)?|dvd5|dvd9|SD[-._ ]TV|TVRip|NTSC|BDRip|hdtv|xvid/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/((H|P)D[-._ ]?TV|DSR|WebRip)[-._ ]x264/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|\s\d{3,4}\s/i', $this->releaseName)) {
			if (preg_match('/(H|P)D[-._ ]?TV|BDRip[-._ ]x264/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}
		}
		return false;
	}

	public function isAnimeTV()
	{
		if (preg_match('/[-._ ]Anime[-._ ]|^\(\[AST\]\s|\[HorribleSubs\]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_ANIME;
			return true;
		}
		return false;
	}

	public function isOtherTV2()
	{
		if (preg_match('/[-._ ]s\d{1,3}[-._ ]?(e|d)\d{1,3}([-._ ]|$)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	//  Movies.
	public function isMovie()
	{
		if (preg_match('/[-._ ]AVC|[-._ ]|[BH][DR]RIP|Bluray|BD[-._ ]?(25|50)?|\bBR\b|Camrip|[-._ ]\d{4}[-._ ].+(720p|1080p|Cam)|DIVX|[-._ ]DVD[-._ ]|DVD-?(5|9|R|Rip)|Untouched|VHSRip|XVID|[-._ ](DTS|TVrip)[-._ ]/i', $this->releaseName) && !preg_match('/auto(cad|desk)|divx[-._ ]plus|[-._ ]exe$|[-._ ](jav|XXX)[-._ ]|SWE6RUS|\wXXX(1080p|720p|DVD)|Xilisoft/i', $this->releaseName)) {
			if ($this->categorizeForeign && $this->isMovieForeign()) {
				return true;
			}
			if ($this->isMovieDVD()) {
				return true;
			}
			if ($this->isMovieSD()) {
				return true;
			}
			if ($this->isMovie3D()) {
				return true;
			}
			if ($this->isMovieBluRay()) {
				return true;
			}
			if ($this->isMovieHD()) {
				return true;
			}
			if ($this->isMovieOther()) {
				return true;
			}
		}

		return false;
	}

	public function isMovieForeign()
	{
		if (preg_match('/(danish|flemish|Deutsch|dutch|french|german|nl[-._ ]?sub(bed|s)?|\.NL|norwegian|swedish|swesub|spanish|Staffel)[-._ ]|\(german\)|Multisub/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/Castellano/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/(720p|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|XVID)[-._ ](Dutch|French|German|ITA)|\(?(Dutch|French|German|ITA)\)?[-._ ](720P|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|HD[-._ ]|XVID)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		return false;
	}

	public function isMovieDVD()
	{
		if (preg_match('/(dvd\-?r|[-._ ]dvd|dvd9|dvd5|[-._ ]r5)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_DVD;
			return true;
		}
		return false;
	}

	public function isMovieSD()
	{
		if (preg_match('/(divx|dvdscr|extrascene|dvdrip|\.CAM|vhsrip|xvid)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		return false;
	}

	public function isMovie3D()
	{
		if (preg_match('/[-._ ]3D\s?[\.\-_\[ ](1080p|(19|20)\d\d|AVC|BD(25|50)|Blu[-._ ]?ray|CEE|Complete|GER|MVC|MULTi|SBS|H(-)?SBS)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_3D;
			return true;
		}
		return false;
	}

	public function isMovieBluRay()
	{
		if (preg_match('/bluray\-|[-._ ]bd?[-._ ]?(25|50)|blu-ray|Bluray\s\-\sUntouched|[-._ ]untouched[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_BLURAY;
			return true;
		}
		return false;
	}

	public function isMovieHD()
	{
		if (preg_match('/720p|1080p|AVC|VC1|VC\-1|web\-dl|wmvhd|x264|XvidHD|bdrip/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_HD;
			return true;
		}
		return false;
	}

	public function isMovieOther()
	{
		if (preg_match('/[-._ ]cam[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_OTHER;
			return true;
		}
		return false;
	}

	//  PC.
	public function isPC()
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[^a-z0-9](FLAC|Imageset|MP3|Nintendo|PDTV|PS[23P]|SWE6RUS|UMD(RIP)?|WII|x264|XBOX|XXX)[^a-z0-9]/i', $this->releaseName)) {
			if ($this->isPhone()) {
				return true;
			}
			if ($this->isMac()) {
				return true;
			}
			if ($this->isISO()) {
				return true;
			}
			if ($this->is0day()) {
				return true;
			}
			if ($this->isPCGame()) {
				return true;
			}
		}
		return false;
	}

	public function isPhone()
	{
		if (preg_match('/[^a-z0-9](IPHONE|ITOUCH|IPAD)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_PHONE_IOS;
			return true;
		}

		if (preg_match('/[-._ ]?(ANDROID)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_PHONE_ANDROID;
			return true;
		}

		if (preg_match('/[^a-z0-9](symbian|xscale|wm5|wm6)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
			return true;
		}
		return false;
	}

	public function isISO()
	{
		if (preg_match('/\biso\b/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_ISO;
			return true;
		}
		return false;
	}

	public function is0day()
	{
		if (preg_match('/[-._ ]exe$|[-._ ](utorrent|Virtualbox)[-._ ]|incl.+crack| DRM$|>DRM</i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}

		if (preg_match('/[-._ ]((32|64)bit|converter|i\d86|key(gen|maker)|freebsd|GAMEGUiDE|hpux|irix|linux|multilingual|Patch|Pro v\d{1,3}|portable|regged|software|solaris|template|unix|win2kxp2k3|win64|win(2k|32|64|all|dows|nt(2k)?(xp)?|xp)|win9x(me|nt)?|x(32|64|86))[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}

		if (preg_match('/\b(Adobe|auto(cad|desk)|-BEAN|Cracked|Cucusoft|CYGNUS|Divx[-._ ]Plus|\.(deb|exe)|DIGERATI|FOSI|Key(filemaker|gen|maker)|Lynda\.com|lz0|MULTiLANGUAGE|MultiOS|-(iNViSiBLE|SPYRAL|SUNiSO|UNION|TE)|v\d{1,3}.*?Pro|[-._ ]v\d{1,3}[-._ ]|\(x(64|86)\)|Xilisoft)\b/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}
		return false;
	}

	public function isMac()
	{
		if (preg_match('/\bmac(\.|\s)?osx\b/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_MAC;
			return true;
		}
		return false;
	}

	public function isPCGame()
	{
		if (preg_match('/[^a-z0-9](0x0007|ALiAS|BACKLASH|BAT|CPY|FAS(DOX|iSO)|FLT([-._ ]|COGENT)|FLTDOX|Games|GENESIS|HI2U|INLAWS|JAGUAR|MAZE|MONEY|OUTLAWS|PPTCLASSiCS|PC Game|PROPHET|RAiN|Razor1911|RELOADED|RiTUELYPOGEiOS|Rip-UNLEASHED|SKIDROW|TiNYiSO|CODEX)[^a-z0-9]?/', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_GAMES;
			return true;
		}
		return false;
	}

	//	XXX.
	public function isXxx()
	{
		if (preg_match('/\bXXX\b|(a\.b\.erotica|ClubSeventeen|Cum(ming|shot)|Err?oticax?|Porn(o|lation)?|Imageset|lesb(ians?|os?)|mastur(bation|e?bate)|My_Stepfather_Made_Me|nympho?|pictures\.erotica\.anime|sexontv|slut|Squirt|SWE6RUS|Transsexual|whore)/i', $this->releaseName)) {
			if ($this->isXxxPack()) {
				return true;
			}
			if ($this->isXxx264()) {
				return true;
			}
			if ($this->isXxxXvid()) {
				return true;
			}
			if ($this->isXxxImageset()) {
				return true;
			}
			if ($this->isXxxWMV()) {
				return true;
			}
			if ($this->isXxxDVD()) {
				return true;
			}
			if ($this->isXxxOther()) {
				return true;
			}
			$this->tmpCat = Category::CAT_XXX_OTHER;
			return true;
		}
		return false;
	}

	public function isXxx264()
	{
		if (preg_match('/720p|1080(hd|[ip])|[xh][^a-z0-9]?264/i', $this->releaseName) && !preg_match('/\bwmv\b/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_X264;
			return true;
		}
		return false;
	}

	public function isXxxWMV()
	{
		if (preg_match('/(\d{2}\.\d{2}\.\d{2})|([ex]\d{2,})|[^a-z0-9](f4v|flv|isom|(issue\.\d{2,})|mov|mp(4|eg)|multiformat|pack-|realmedia|uhq|wmv)[^a-z0-9]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_WMV;
			return true;
		}
		return false;
	}

	public function isXxxXvid()
	{
		if (preg_match('/(b[dr]|dvd)rip|detoxication|divx|nympho|pornolation|swe6|tesoro|xvid/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_XVID;
			return true;
		}
		return false;
	}

	public function isXxxDVD()
	{
		if (preg_match('/dvdr[^i]|dvd[59]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_DVD;
			return true;
		}
		return false;
	}

	public function isXxxImageset()
	{
		if (preg_match('/IMAGESET|ABPEA/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_IMAGESET;
			return true;
		}
		return false;
	}

	public function isXxxPack()
	{
		if (preg_match('/[ .]PACK[ .]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_PACKS;
			return true;
		}
		return false;
	}

	public function isXxxOther()
	{
		// If nothing else matches, then try these words.
		if (preg_match('/[-._ ]Brazzers|Creampie|[-._ ]JAV[-._ ]|North\.Pole|She[-._ ]?Male|Transsexual/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_OTHER;
			return true;
		}
		return false;
	}

	//	Console.
	public function isConsole()
	{
		if ($this->isGameNDS()) {
			return true;
		}
		if ($this->isGamePS3()) {
			return true;
		}
		if ($this->isGamePSP()) {
			return true;
		}
		if ($this->isGameWiiWare()) {
			return true;
		}
		if ($this->isGameWii()) {
			return true;
		}
		if ($this->isGameNGC()) {
			return true;
		}
		if ($this->isGameXBOX360DLC()) {
			return true;
		}
		if ($this->isGameXBOX360()) {
			return true;
		}
		if ($this->isGameXBOX()) {
			return true;
		}
		if ($this->isGameOther()) {
			return true;
		}
		return false;
	}

	public function isGameNDS()
	{
		if (preg_match('/NDS|[\. ]nds|nintendo.+3ds|[_\.]3DS-/', $this->releaseName)) {
			if (preg_match('/\((DE|DSi(\sEnhanched)?|_NDS-|EUR?|FR|GAME|HOL|JP|JPN|NL|NTSC|PAL|KS|USA?)\)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}
			if (preg_match('/(EUR|FR|GAME|HOL|JP|JPN|NL|NTSC|PAL|KS|USA)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}
		}
		return false;
	}

	public function isGameNGC()
	{
		if (preg_match('/_N?G(AME)?C(UBE)?-/i', $this->releaseName)) {
			if (preg_match('/_(EUR?|FR|GAME|HOL|JP|JPN|NL|NTSC|PAL|KS|USA?)_/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_OTHER;
				return true;
			}
			if (preg_match('/-(((STAR|DEATH|STINKY|MOON|HOLY|G)?CUBE(SOFT)?)|(DARKFORCE|DNL|GP|ICP|iNSOMNIA|JAY|LaKiTu|METHS|NOMIS|QUBiSM|PANDORA|REACT0R|SUNSHiNE|SAVEPOiNT|SYNDiCATE|WAR3X|WRG))/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_OTHER;
				return true;
			}
		}
		return false;
	}

	public function isGamePS3()
	{
		if (preg_match('/PS3/i', $this->releaseName)) {
			if (preg_match('/ANTiDOTE|DLC|DUPLEX|EUR?|Googlecus|GOTY|\-HR|iNSOMNi|JAP|JPN|KONDIOS|\[PS3\]|PSN/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
			if (preg_match('/AGENCY|APATHY|Caravan|MULTi|NRP|NTSC|PAL|SPLiT|STRiKE|USA?|ZRY/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
		}
		return false;
	}

	public function isGamePSP()
	{
		if (preg_match('/PSP/i', $this->releaseName)) {
			if (preg_match('/[-._ ](BAHAMUT|Caravan|EBOOT|EMiNENT|EUR?|EvoX|GAME|GHS|Googlecus|HandHeld|\-HR|JAP|JPN|KLOTEKLAPPERS|KOR|NTSC|PAL)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}
			if (preg_match('/[-._ ](Dynarox|HAZARD|ITALIAN|KLB|KuDoS|LIGHTFORCE|MiRiBS|POPSTATiON|(PLAY)?ASiA|PSN|PSX2PSP|SPANiSH|SUXXORS|UMD(RIP)?|USA?|YARR)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}
		}
		return false;
	}

	public function isGameWiiWare()
	{
		if (preg_match('/(Console|DLC|VC).+[-._ ]WII|(Console|DLC|VC)[-._ ]WII|WII[-._ ].+(Console|DLC|VC)|WII[-._ ](Console|DLC|VC)|WIIWARE/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_GAME_WIIWARE;
			return true;
		}
		return false;
	}

	public function isGameWii()
	{
		if (preg_match('/WII/i', $this->releaseName)) {
			if (preg_match('/[-._ ](Allstars|BiOSHOCK|dumpTruck|DNi|iCON|JAP|NTSC|PAL|ProCiSiON|PROPER|RANT|REV0|SUNSHiNE|SUSHi|TMD|USA?)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
			if (preg_match('/[-._ ](APATHY|BAHAMUT|DMZ|ERD|GAME|JPN|LoCAL|MULTi|NAGGERS|OneUp|PLAYME|PONS|Scrubbed|VORTEX|ZARD|ZER0)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
			if (preg_match('/[-._ ](ALMoST|AMBITION|Caravan|CLiiCHE|DRYB|HaZMaT|KOR|LOADER|MARVEL|PROMiNENT|LaKiTu|LOCAL|QwiiF|RANT)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
		}
		return false;
	}

	public function isGameXBOX360DLC()
	{
		if (preg_match('/DLC.+xbox360|xbox360.+DLC|XBLA.+xbox360|xbox360.+XBLA/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_GAME_XBOX360DLC;
			return true;
		}
		return false;
	}

	public function isGameXBOX360()
	{
		if (preg_match('/XBOX360/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_GAME_XBOX360;
			return true;
		}
		if (preg_match('/x360/i', $this->releaseName)) {
			if (preg_match('/Allstars|ASiA|CCCLX|COMPLEX|DAGGER|GLoBAL|iMARS|JAP|JPN|MULTi|NTSC|PAL|REPACK|RRoD|RF|SWAG|USA?/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}
			if (preg_match('/DAMNATION|GERMAN|GOTY|iNT|iTA|JTAG|KINECT|MARVEL|MUX360|RANT|SPARE|SPANISH|VATOS|XGD/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}
		}
		return false;
	}

	public function isGameXBOX()
	{
		if (preg_match('/XBOX/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_GAME_XBOX;
			return true;
		}
		return false;
	}

	public function isGameOther()
	{
		if (preg_match('/PSX|PS2/', $this->releaseName)) {
			if (preg_match('/(EUR|FR|GAME|HOL|JP|JPN|NL|NTSC|PAL|KS|USA)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_OTHER;
				return true;
			}
		}
		return false;
	}

	//	Music.
	public function isMusic()
	{
		if ($this->isMusicVideo()) {
			return true;
		}
		if ($this->isAudiobook()) {
			return true;
		}
		if ($this->isMusicLossless()) {
			return true;
		}
		if ($this->isMusicMP3()) {
			return true;
		}
		if ($this->isMusicOther()) {
			return true;
		}
		return false;
	}

	public function isMusicForeign()
	{
		if ($this->categorizeForeign) {
			if (preg_match('/[ \-\._](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish|bl|cz|de|es|fr|ger|heb|hu|hun|it(a| 19|20\d\d)|jap|ko|kor|nl|pl|se)[ \-\._]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_MUSIC_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isAudiobook()
	{
		if ($this->categorizeForeign) {
			if (preg_match('/Audiobook/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_MUSIC_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isMusicVideo()
	{
		if (preg_match('/(720P|x264)\-(19|20)\d\d\-[a-z0-9]{1,12}/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}
		}
		if (preg_match('/[a-z0-9]{1,12}\-(19|20)\d\d\-(720P|x264)/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}
		}
		return false;
	}

	public function isMusicLossless()
	{
		if (preg_match('/\[(19|20)\d\d\][-._ ]\[FLAC\]|(\(|\[)flac(\)|\])|FLAC\-(19|20)\d\d\-[a-z0-9]{1,12}|\.flac"|(19|20)\d\d\sFLAC|[-._ ]FLAC.+(19|20)\d\d[-._ ]| FLAC$/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}
		}
		return false;
	}

	public function isMusicMP3()
	{
		if (preg_match('/[a-z0-9]{1,12}\-(19|20)\d\d\-[a-z0-9]{1,12}|[\.\-\(\[_ ]\d{2,3}k[\.\-\)\]_ ]|\((192|256|320)\)|(320|cd|eac|vbr).+mp3|(cd|eac|mp3|vbr).+320|FIH\_INT|\s\dCDs|[-._ ]MP3[-._ ]|MP3\-\d{3}kbps|\.(m3u|mp3)"|NMR\s\d{2,3}\skbps|\(320\)\.|\-\((Bootleg|Promo)\)|\.mp3$|\-\sMP3\s(19|20)\d\d|\(vbr\)|rip(192|256|320)|[-._ ](CDR|WEB).+(19|20)\d\d/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}
		}
		if (preg_match('/\s(19|20)\d\d\s([a-z0-9]{3}|[a-z]{2,})$|\-(19|20)\d\d\-(C4|MTD)(\s|\.)|[-._ ]FM.+MP3[-._ ]|-web-(19|20)\d\d(\.|\s|$)|[-._ ](SAT|WEB).+(19|20)\d\d([-._ ]|$)|[-._ ](19|20)\d\d.+(SAT|WEB)([-._ ]|$)| MP3$/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}
		}
		return false;
	}

	public function isMusicOther()
	{
		if (preg_match('/(19|20)\d\d\-(C4)$|[-._ ]\d?CD[-._ ](19|20)\d\d|\(\d\-?CD\)|\-\dcd\-|\d[-._ ]Albums|Albums.+(EP)|Bonus.+Tracks|Box.+?CD.+SET|Discography|D\.O\.M|Greatest\sSongs|Live.+(Bootleg|Remastered)|Music.+Vol|(\(|\[|\s)NMR(\)|\]|\s)|Promo.+CD|Reggaeton|Tiesto.+Club|Vinyl\s2496|\WV\.A\.|^\(VA\s|^VA[-._ ]/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_OTHER;
				return true;
			}
		} else if (preg_match('/\(pure_fm\)|-+\(?(2lp|cd[ms]([-_ .][a-z]{2})?|cover|ep|ltd_ed|mix|original|ost|.*?(edit(ion)?|remix(es)?|vinyl)|web)\)?-+((19|20)\d\d|you$)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MUSIC_OTHER;
			return true;
		}
		return false;
	}

	//	Books.
	public function isBook()
	{
		if (!preg_match('/AVI[-._ ]PDF|\.exe|Full[-._ ]Video/i', $this->releaseName)) {
			if ($this->isComic()) {
				return true;
			}
			if ($this->isTechnicalBook()) {
				return true;
			}
			if ($this->isMagazine()) {
				return true;
			}
			if ($this->isBookOther()) {
				return true;
			}
			if ($this->isEBook()) {
				return true;
			}
		}
		return false;
	}

	public function isBookForeign()
	{
		if ($this->categorizeForeign) {
			if (preg_match('/[ \-\._](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_BOOKS_FOREIGN;
				return true;
			}
		}

		return false;
	}

	public function isComic()
	{
		if (preg_match('/[\. ](cbr|cbz)|[\( ]c2c|cbr|cbz[\) ]|comix|^\(comic|[\.\-_\(\[ ]comics?[-._ ]|comic.+book|covers.+digital|DC.+(Adventures|Universe)|digital.+(son|zone)|Graphic.+Novel|[\.\-_h ]manga|Total[-._ ]Marvel/i', $this->releaseName)) {
			if ($this->isBookForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}
		}
		return false;
	}

	public function isTechnicalBook()
	{
		if (preg_match('/^\(?(atz|bb|css|c ?t|Drawing|Gabler|IOS|Iphone|Lynda|Manning|Medic(al|ine)|MIT|No[-._ ]Starch|Packt|Peachpit|Pragmatic|Revista|Servo|SmartBooks|Spektrum|Strata|Sybex|Syngress|Vieweg|Wiley|Woods|Wrox)[-._ ]|[-._ ](Ajax|CSS|DIY|Javascript|(My|Postgre)?SQL|XNA)[-._ ]|3DS\.\-_ ]Max|Academic|Adobe|Algebra|Analysis|Appleworks|Archaeology|Bitdefender|Birkhauser|Britannica|[-._ ]C\+\+|C[-._ ](\+\+|Sharp|Plus)|Chemistry|Circuits|Cook(book|ing)|(Beginners?|Complete|Communications|Definitive|Essential|Hackers?|Practical|Professionals?)[-._ ]Guide|Developer|Diagnostic|Disassembl(er|ing|y)|Debugg(er|ing)|Dreamweaver|Economics|Education|Electronics|Enc(i|y)clopedia|Engineer(ing|s)|Essays|Exercizes|For.+Beginners|Focal[-._ ]Press|For[-._ ]Dummies|FreeBSD|Fundamentals[-._ ]of[-._ ]|(Galileo|Island)[-._ ]Press|Geography|Grammar|Guide[-._ ](For|To)|Hacking|Google|Handboo?k|How[-._ ](It|To)|Intoduction[-._ ]to|Iphone|jQuery|Lessons[-._ ]In|Learning|LibreOffice|Linux|Manual|Marketing|Masonry|Mathematic(al|s)?|Medical|Microsoft|National[-._ ]Academies|Nero[-._ ]\d+|OReilly|OS[-._ ]X[-._ ]|Official[-._ ]Guide|Open(GL|Office)|Pediatric|Periodic.+Table|Photoshop|Physics|Power(PC|Point|Shell)|Programm(ers?|ier||ing)|Raspberry.+Pi|Remedies|Service\s?Manual|SitePoint|Sketching|Statistics|Stock.+Market|Students|Theory|Training|Tutsplus|Ubuntu|Understanding[-._ ](and|Of|The)|Visual[-._ ]Studio|Textbook|VMWare|wii?max|Windows[-._ ](8|7|Vista|XP)|^Wood[-._ ]|Woodwork|WordPress|Work(book|shop)|Youtube/i', $this->releaseName)) {
			if ($this->isBookForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}
		}
		return false;
	}

	public function isMagazine()
	{
		if (preg_match('/[a-z\-\._ ][-._ ](January|February|March|April|May|June|July|August|September|October|November|December)[-._ ](\d{1,2},)?20\d\d[-._ ]|^\(.+[ .]\d{1,2}[ .]20\d\d[ .].+\.scr|[-._ ](Catalogue|FHM|NUTS|Pictorial|Tatler|XXX)[-._ ]|^\(?(Allehanda|Club|Computer([a-z0-9]+)?|Connect \d+|Corriere|ct|Diario|Digit(al)?|Esquire|FHM|Gadgets|Galileo|Glam|GQ|Infosat|Inked|Instyle|io|Kicker|Liberation|New Scientist|NGV|Nuts|Popular|Professional|Reise|Sette(tv)?|Springer|Stuff|Studentlitteratur|Vegetarian|Vegetable|Videomarkt|Wired)[-._ ]|Brady(.+)?Games|Catalog|Columbus.+Dispatch|Correspondenten|Corriere[-._ ]Della[-._ ]Sera|Cosmopolitan|Dagbladet|Digital[-._ ]Guide|Economist|Eload ?24|ExtraTime|Fatto[-._ ]Quotidiano|Flight[-._ ](International|Journal)|Finanzwoche|France.+Football|Foto.+Video|Games?(Master|Markt|tar|TM)|Gardening|Gazzetta|Globe[-._ ]And[-._ ]Mail|Guitar|Heimkino|Hustler|La.+(Lettura|Rblica|Stampa)|Le[-._ ](Monde|Temps)|Les[-._ ]Echos|e?Magazin(es?)?|Mac(life|welt)|Marie.+Claire|Maxim|Men.+(Health|Fitness)|Motocross|Motorcycle|Mountain[-._ ]Bike|MusikWoche|National[-._ ]Geographic|New[-._ ]Yorker|PC([-._ ](Gamer|Welt|World)|Games|Go|Tip)|Penthouse|Photograph(er|ic)|Playboy|Posten|Quotidiano|(Golf|Readers?).+Digest|SFX[-._ ]UK|Recipe(.+Guide|s)|SkyNews|Sport[-._ ]?Week|Strategy.+Guide|TabletPC|Tattoo[-._ ]Life|The[-._ ]Guardian|Tageszeitung|Tid(bits|ning)|Top[-._ ]Gear[-._ ]|Total[-._ ]Guitar|Travel[-._ ]Guides?|Tribune[-._ ]De[-._ ]|US[-._ ]Weekly|USA[-._ ]Today|Vogue|Verlag|Warcraft|Web.+Designer|What[-._ ]Car|Zeitung/i', $this->releaseName)) {
			if ($this->isBookForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_MAGAZINES;
				return true;
			}
		}
		return false;
	}

	public function isBookOther()
	{
		if (preg_match('/"\d\d-\d\d-20\d\d\./i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_BOOKS_OTHER;
			return true;
		}
		return false;
	}

	public function isEBook()
	{
		if (preg_match('/^ePub|[-._ ](Ebook|E?\-book|\) WW|Publishing)|[\.\-_\(\[ ](epub|html|mobi|pdf|rtf|tif|txt)[\.\-_\)\] ]|[\. ](doc|epub|mobi|pdf)(?![\w .])/i', $this->releaseName)) {
			if ($this->isBookForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_EBOOK;
				return true;
			}
		}
		return false;
	}

	//	Misc, all hash/misc go in other misc.
	public function isMisc()
	{
		if (!preg_match('/[^a-z0-9]((480|720|1080)[ip]|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ]))[^a-z0-9]/i', $this->releaseName)) {
			if (preg_match('/[a-z0-9]{20,}/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_MISC;
				return true;
			} else if (preg_match('/^[A-Z0-9]{1,}$/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_MISC;
				return true;
			}
		}
		return false;
	}
}