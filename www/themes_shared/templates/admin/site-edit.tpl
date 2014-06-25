<h1>{$page->title}</h1>
<form action="{$SCRIPT_NAME}?action=submit" method="post">
{if $error != ''}
	<div class="error">{$error}</div>
{/if}
<fieldset>
	<legend>Main Site Settings, Html Layout, Tags</legend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="title">Title:</label></td>
			<td>
				<input id="title" class="long" name="title" type="text" value="{$fsite->title}"/>

				<div class="hint">Displayed around the site and contact form as the name for the site.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="strapline">Strapline:</label></td>
			<td>
				<input id="strapline" class="long" name="strapline" type="text" value="{$fsite->strapline}"/>

				<div class="hint">Displayed in the header on every public page.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="metatitle">Meta Title:</label></td>
			<td>
				<input id="metatitle" class="long" name="metatitle" type="text" value="{$fsite->metatitle}"/>

				<div class="hint">Stem meta-tag appended to all page title tags.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="metadescription">Meta Description:</label></td>
			<td>
				<textarea id="metadescription" name="metadescription">{$fsite->metadescription}</textarea>

				<div class="hint">Stem meta-description appended to all page meta description tags.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="metakeywords">Meta Keywords:</label></td>
			<td>
				<textarea id="metakeywords" name="metakeywords">{$fsite->metakeywords}</textarea>

				<div class="hint">Stem meta-keywords appended to all page meta keyword tags.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="footer">Footer:</label></td>
			<td>
				<textarea id="footer" name="footer">{$fsite->footer}</textarea>

				<div class="hint">Displayed in the footer section of every public page.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="style">Default Home Page:</label></td>
			<td>
				<input id="home_link" class="long" name="home_link" type="text" value="{$fsite->home_link}"/>

				<div class="hint">The relative path to a the landing page shown when a user logs in, or clicks the home
					link.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width: 180px;"><label for="coverspath">Cover&apos;s path:</label></td>
			<td>
				<input id="coverspath" class="long" name="coverspath" type="text" value="{$coversPath}"/>

				<div class="hint">The absolute path to the place covers will be stored.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="codename">Code Name:</label></td>
			<td>
				<input id="codename" name="code" type="text" value="{$fsite->code}"/>
				<input type="hidden" name="id" value="{$fsite->id}"/>

				<div class="hint">A just for fun value shown in debug and not on public pages.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="style">Theme:</label></td>
			<td>
				{html_options style="width:180px;" class="siteeditstyle" id="style" name='style' values=$themelist output=$themelist selected=$fsite->style}
				<div class="hint">The theme folder which will be loaded for css and images. (Use / for default)</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="style">User Menu Position:</label></td>
			<td>
				{html_options style="width:180px;" class="siteeditmenuposition" id="menuposition" name='menuposition' values=$menupos_ids output=$menupos_names selected=$fsite->menuposition}
				<div class="hint">Where the menu should appear. Moving the menu to the top will require using a theme
					which widens the content panel. (not currently functional)
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="style">Dereferrer Link:</label></td>
			<td>
				<input id="dereferrer_link" class="long" name="dereferrer_link" type="text"
					   value="{$fsite->dereferrer_link}"/>

				<div class="hint">Optional URL to prepend to external links</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="email">Email:</label></td>
			<td>
				<input id="email" class="long" name="email" type="text" value="{$fsite->email}"/>

				<div class="hint">Shown in the contact us page, and where the contact html form is sent to.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="tandc">Terms and Conditions:</label></td>
			<td>
				<textarea id="tandc" name="tandc">{$fsite->tandc}</textarea>

				<div class="hint">Text displayed in the terms and conditions page.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="loggingopt">Logging Option:</label></td>
			<td>
				{html_options style="width:180px;" class="loggingopt" id="loggingopt" name='loggingopt' values=$loggingopt_ids output=$loggingopt_names selected=$fsite->loggingopt}
				<div class="hint">Where you would like to log failed logins to the site.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="logfile">Logfile Location:</label></td>
			<td>
				<input id="logfile" class="long" name="logfile" type="text" value="{$fsite->logfile}"/>

				<div class="hint">Location of log file (MUST be set if logging to file is set).</div>
			</td>
		</tr>
	</table>
</fieldset>
<fieldset>
	<legend>Language/Categorization options</legend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="categorizeforeign">Categorize Foreign:</label></td>
			<td>
				{html_radios id="categorizeforeign" name='categorizeforeign' values=$yesno_ids output=$yesno_names selected=$fsite->categorizeforeign separator='<br />'}
				<div class="hint">This only works if the above is set to english. Whether to send foreign movies/tv to
					foreign sections or not. If set to true they will go in foreign categories.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="catwebdl">Categorize WEB-DL:</label></td>
			<td>
				{html_radios id="catwebdl" name='catwebdl' values=$yesno_ids output=$yesno_names selected=$fsite->catwebdl separator='<br />'}
				<div class="hint">Whether to send WEB-DL to the WEB-DL section or not. If set to true they will go in
					WEB-DL category, false will send them in HD TV.<br/>This will also make them inaccessible to
					Sickbeard and possibly Couchpotato.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="imdburl">IMDB.com:</label></td>
			<td>
				{html_options style="width:180px;" class="imdburl" id="imdburl" name='imdburl' values=$imdb_urls output=$imdburl_names selected=$fsite->imdburl}
				<div class="hint">Akas.imdb.com returns titles in their original title, imdb.com returns titles based on
					your IP address (if you are in france, you will get french titles).
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="imdblanguage">IMDB/Tmdb Language:</label></td>
			<td>
				{html_options style="width:180px;" class="imdblanguage" id="imdblanguage" name='imdblanguage' values=$imdblang_ids output=$imdblang_names selected=$fsite->imdblanguage}
				<div class="hint">Which language to lookup when sending requests to IMDB/Tmdb. (If akas.imdb.com is set,
					imdb still returns the original titles.)
				</div>
			</td>
		</tr>
	</table>
</fieldset>
<fieldset>
	<legend>Google Adsense, Analytics and 3rd Party Banners</legend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="google_analytics_acc">Google Analytics:</label></td>
			<td>
				<input id="google_analytics_acc" name="google_analytics_acc" type="text"
					   value="{$fsite->google_analytics_acc}"/>

				<div class="hint">e.g. UA-xxxxxx-x</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="google_adsense_acc">Google Adsense:</label></td>
			<td>
				<input id="google_adsense_acc" name="google_adsense_acc" type="text"
					   value="{$fsite->google_adsense_acc}"/>

				<div class="hint">e.g. pub-123123123123123</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="google_adsense_search">Google Adsense Search:</label></td>
			<td>
				<input id="google_adsense_search" name="google_adsense_search" type="text"
					   value="{$fsite->google_adsense_search}"/>

				<div class="hint">The ID of the google search ad panel displayed at the bottom of the left menu.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="adheader">Advert Space in Banner:</label></td>
			<td>
				<textarea rows="3" placeholder="Place your ad banner code here." id="adheader"
						  name="adheader">{$fsite->adheader}</textarea>

				<div class="hint">The banner slot in the header.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="adbrowse">Advert Space in Browse List:</label></td>
			<td>
				<textarea rows="3" placeholder="Place your ad banner code here." id="adbrowse"
						  name="adbrowse">{$fsite->adbrowse}</textarea>

				<div class="hint">The banner slot in the header.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="addetail">Advert Space in Detail View:</label></td>
			<td>
				<textarea rows="3" placeholder="Place your ad banner code here." id="addetail"
						  name="addetail">{$fsite->addetail}</textarea>

				<div class="hint">The banner slot in the release details view.</div>
			</td>
		</tr>
	</table>
</fieldset>
<fieldset>
	<legend>3<sup>rd</sup> Party API Keys</legend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="tmdbkey">TMDB Key:</label></td>
			<td>
				<input id="tmdbkey" class="long" name="tmdbkey" type="text" value="{$fsite->tmdbkey}"/>

				<div class="hint">The api key used for access to tmdb.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="rottentomatokey">Rotten Tomatoes Key:</label></td>
			<td>
				<input id="rottentomatokey" class="long" name="rottentomatokey" type="text"
					   value="{$fsite->rottentomatokey}"/>
				{html_options style="width:180px;" id="rottentomatoquality" name='rottentomatoquality' values=$rottentomatoquality_ids output=$rottentomatoquality_names selected=$fsite->rottentomatoquality}
				<div class="hint">The api key used for access to rotten tomatoes. Select the quality of the images to
					display in Upcoming.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="amazonpubkey">Amazon Public Key:</label></td>
			<td>
				<input id="amazonpubkey" class="long" name="amazonpubkey" type="text" value="{$fsite->amazonpubkey}"/>

				<div class="hint">The amazon public api key. Used for music/book lookups.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="amazonprivkey">Amazon Private Key:</label></td>
			<td>
				<input id="amazonprivkey" class="long" name="amazonprivkey" type="text"
					   value="{$fsite->amazonprivkey}"/>

				<div class="hint">The amazon private api key. Used for music/book lookups.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="amazonassociatetag">Amazon Associate Tag:</label></td>
			<td>
				<input id="amazonassociatetag" class="long" name="amazonassociatetag" type="text"
					   value="{$fsite->amazonassociatetag}"/>

				<div class="hint">The amazon associate tag. Used for music/book lookups.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="trakttvkey">Trakt.tv API key:</label></td>
			<td>
				<input id="trakttvkey" class="long" name="trakttvkey" type="text" value="{$fsite->trakttvkey}"/>

				<div class="hint">The trakt.tv api key. Used for movie and tv lookups.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="anidbkey">Anidb API key:</label></td>
			<td>
				<input id="anidbkey" class="long" name="anidbkey" type="text" value="{$fsite->anidbkey}"/>

				<div class="hint">The Anidb api key. Used for Anime lookups.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="fanarttvkey">Fanart.tv API key:</label></td>
			<td>
				<input id="fanarttvkey" class="long" name="fanarttvkey" type="text" value="{$fsite->fanarttvkey}"/>

				<div class="hint">The Fanart.tv api key. Used for Fanart.tv lookups. Fanart.tv would appreciate it if
					you use this service to help them out by adding high quality images not already available on TMDB.
				</div>
			</td>
		</tr>
	</table>
</fieldset>
<fieldset>
	<legend>3<sup>rd</sup> Party Application Paths</legend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="unrarpath">Unrar Path:</label></td>
			<td>
				<input id="unrarpath" class="long" name="unrarpath" type="text" value="{$fsite->unrarpath}"/>

				<div class="hint">The path to an unrar binary, used in deep password detection and media info grabbing.
					<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/unrar.exe</span>
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="tmpunrarpath">Temp Unrar File Path:</label></td>
			<td>
				<input id="tmpunrarpath" class="long" name="tmpunrarpath" type="text" value="{$fsite->tmpunrarpath}"/>

				<div class="hint">The path to where unrar puts files. WARNING: This directory will have its contents
					deleted.
					<br/>Use forward slashes in windows <span style="font-family:courier;">c:/temp/path/stuff/will/be/unpacked/to</span>
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="zippath">7za Path:</label></td>
			<td>
				<input id="zippath" class="long" name="zippath" type="text" value="{$fsite->zippath}"/>

				<div class="hint">The path to the 7za (7zip command line in windows) binary, used for grabbing nfos from
					compressed zip files.
					<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/7z.exe</span>
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="yydecoderpath">yEnc Type:</label></td>
			<td>
				<input id="yydecoderpath" class="long" name="yydecoderpath" type="text"
					   value="{$fsite->yydecoderpath}"/>

				<div class="hint">
					Leaving this empty will use PHP to decode yEnc, which is slow.
					<br/>Putting the path to yydecode will use yydecode, which is faster than PHP. <a
							style="color:#0082E1"
							href="http://sourceforge.net/projects/yydecode/files/yydecode/0.2.10/">Download yydecode on
						sourceforce.</a>
					<br/>Putting in <strong style="color:#ac2925">simple_php_yenc_decode</strong> will use that
					extension which is even faster <strong>(you must have the extension)</strong>. <a
							style="color:#0082E1" href="https://github.com/kevinlekiller/simple_php_yenc_decode">View
						simple_php_yenc_decode on github.</a>
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="mediainfopath">Mediainfo Path:</label></td>
			<td>
				<input id="mediainfopath" class="long" name="mediainfopath" type="text"
					   value="{$fsite->mediainfopath}"/>

				<div class="hint">The path to the <a href="http://mediainfo.sourceforge.net">mediainfo</a> binary. Used
					for deep file media analysis. Use empty path to disable mediainfo checks
					<br/>Use forward slashes in windows <span
							style="font-family:courier;">c:/path/to/mediainfo.exe</span></div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="ffmpegpath">FFmpeg or Avconv Path:</label></td>
			<td>
				<input id="ffmpegpath" class="long" name="ffmpegpath" type="text" value="{$fsite->ffmpegpath}"/>

				<div class="hint">The path to the <a href="http://www.ffmpeg.org/">ffmpeg</a> or <a
							href="https://libav.org/">avconv</a> binary. Used for making thumbnails and video/audio
					previews. Use empty path to disable thumbnailing.
					<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/ffmpeg.exe</span>
				</div>
			</td>
		</tr>
	</table>
</fieldset>
<fieldset>
	<legend>SABnzbd Integration Settings</legend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="sabintegrationtype">Integration Type:</label></td>
			<td>
				{html_radios id="sabintegrationtype" name='sabintegrationtype' values=$sabintegrationtype_ids output=$sabintegrationtype_names selected=$fsite->sabintegrationtype separator='<br />'}
				<div class="hint">
					Whether to allow integration with a SAB install and if so what type of integration.<br/>
					<strong>Setting this to integrated also disables NZBGet from being selectable to the
						user.</strong><br/>
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="saburl">SABnzbd Url:</label></td>
			<td>
				<input id="saburl" class="long" name="saburl" type="text" value="{$fsite->saburl}"/>

				<div class="hint">The url of the SAB installation, for example: http://localhost:8080/sabnzbd/</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="sabapikey">SABnzbd Api Key:</label></td>
			<td>
				<input id="sabapikey" class="long" name="sabapikey" type="text" value="{$fsite->sabapikey}"/>

				<div class="hint">The Api key of the SAB installation. Can be the full api key or the nzb api key (as of
					SAB 0.6)
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="sabapikeytype">Api Key Type:</label></td>
			<td>
				{html_radios id="sabapikeytype" name='sabapikeytype' values=$sabapikeytype_ids output=$sabapikeytype_names selected=$fsite->sabapikeytype separator='<br />'}
				<div class="hint">Select the type of api key you entered in the above setting</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="sabpriority">Priority Level:</label></td>
			<td>
				{html_options style="width:180px;" id="sabpriority" name='sabpriority' values=$sabpriority_ids output=$sabpriority_names selected=$fsite->sabpriority}
				<div class="hint">Set the priority level for NZBs that are added to your queue</div>
			</td>
		</tr>
	</table>
</fieldset>
<fieldset>
<legend>Usenet Settings</legend>
<table class="input">
<tr>
	<td style="width:180px;"><label for="nzbpath">Nzb File Path:</label></td>
	<td>
		<input id="nzbpath" class="long" name="nzbpath" type="text" value="{$fsite->nzbpath}"/>

		<div class="hint">The directory where nzb files will be stored.</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="minfilestoformrelease">Minimum Files to Make a Release:</label></td>
	<td>
		<input class="short" id="minfilestoformrelease" name="minfilestoformrelease" type="text"
			   value="{$fsite->minfilestoformrelease}"/>

		<div class="hint">The minimum number of files to make a release. i.e. if set to two, then releases which only
			contain one file will not be created.
		</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="minsizetoformrelease">Minimum File Size to Make a Release:</label></td>
	<td>
		<input class="small" id="minsizetoformrelease" name="minsizetoformrelease" type="text"
			   value="{$fsite->minsizetoformrelease}"/>

		<div class="hint">The minimum total size in bytes to make a release. If set to 0, then ignored. Only deletes
			during release creation.
		</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="maxsizetoformrelease">Maximum File Size to Make a Release:</label></td>
	<td>
		<input class="small" id="maxsizetoformrelease" name="maxsizetoformrelease" type="text"
			   value="{$fsite->maxsizetoformrelease}"/>

		<div class="hint">The maximum total size in bytes to make a release. If set to 0, then ignored. Only deletes
			during release creation.
		</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="maxsizetopostprocess">Maximum Release Size to Post Process:</label></td>
	<td>
		<input class="short" id="maxsizetopostprocess" name="maxsizetopostprocess" type="text" value="{$fsite->maxsizetopostprocess}"/>
		<div class="hint">The maximum size in gigabytes to post process (additional) a release. If set to 0, then ignored.</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="minsizetopostprocess">Minimum Release Size to Post Process:</label></td>
	<td>
		<input class="short" id="minsizetopostprocess" name="minsizetopostprocess" type="text" value="{$fsite->minsizetopostprocess}"/>
		<div class="hint">The minimum size in megabytes to post process (additional) a release. If set to 0, then ignored.</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="checkpasswordedrar">Check For Passworded Releases:</label></td>
	<td>
		{html_radios id="checkpasswordedrar" name='checkpasswordedrar' values=$passwd_ids output=$passwd_names selected=$fsite->checkpasswordedrar separator='<br />'}
		<div class="hint">Whether to attempt to peek into every release, to see if rar files are password
			protected.<br/></div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="deletepasswordedrelease">Delete Passworded Releases:</label></td>
	<td>
		{html_radios id="deletepasswordedrelease" name='deletepasswordedrelease' values=$yesno_ids output=$yesno_names selected=$fsite->deletepasswordedrelease separator='<br />'}
		<div class="hint">Whether to delete releases which are passworded.<br/></div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="deletepossiblerelease">Delete Possibly Passworded Releases:</label></td>
	<td>
		{html_radios id="deletepossiblerelease" name='deletepossiblerelease' values=$yesno_ids output=$yesno_names selected=$fsite->deletepossiblerelease separator='<br />'}
		<div class="hint">Whether to delete releases which are potentially passworded.<br/></div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="showpasswordedrelease">Show Passworded Releases:</label></td>
	<td>
		{html_options style="width:180px;" id="showpasswordedrelease" name='showpasswordedrelease' values=$passworded_ids output=$passworded_names selected=$fsite->showpasswordedrelease}
		<div class="hint">Whether to show passworded or potentially passworded releases in browse, search, api and rss
			feeds. Potentially passworded means releases which contain .cab or .ace files which are typically password
			protected.
		</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="processjpg">Process JPG:</label></td>
	<td>
		{html_radios id="processjpg" name='processjpg' values=$yesno_ids output=$yesno_names selected=$fsite->processjpg separator='<br />'}
		<div class="hint">Whether to attempt to retrieve a JPG file while additional post processing, these are usually
			on XXX releases.<br/></div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="processvideos">Process Video Samples:</label></td>
	<td>
		{html_radios id="processvideos" name='processvideos' values=$yesno_ids output=$yesno_names selected=$fsite->processvideos separator='<br />'}
		<div class="hint">Whether to attempt to process a video sample, these videos are very short 1-3 seconds, 100KB
			on average, in ogv format. You must have ffmpeg for this.<br/></div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="segmentstodownload">Number of Segments to download for video/jpg
			samples:</label></td>
	<td>
		<input class="short" id="segmentstodownload" name="segmentstodownload" type="text"
			   value="{$fsite->segmentstodownload}"/>

		<div class="hint">The maximum number of segments to download to generate the sample video file or jpg sample
			image. (Default 2)
		</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="ffmpeg_duration">Video sample file duration for ffmpeg:</label></td>
	<td>
		<input class="short" id="ffmpeg_duration" name="ffmpeg_duration" type="text" value="{$fsite->ffmpeg_duration}"/>

		<div class="hint">The maximum duration (In Seconds) for ffmpeg to generate the sample for. (Default 5)</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="processaudiosample">Process Audio Samples:</label></td>
	<td>
		{html_radios id="processaudiosample" name='processaudiosample' values=$yesno_ids output=$yesno_names selected=$fsite->processaudiosample separator='<br />'}
		<div class="hint">Whether to attempt to process a audio sample, they will be up to 30 seconds, in ogg format.
			You must have ffmpeg for this.<br/></div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="lookuppar2">Lookup PAR2:</label></td>
	<td>
		{html_radios id="lookuppar2" name='lookuppar2' values=$yesno_ids output=$yesno_names selected=$fsite->lookuppar2 separator='<br />'}
		<div class="hint">Whether to attempt to find a better name for releases in misc->other using the PAR2 file.<br/><strong>NOTE:
				this can be slow depending on the group!</strong></div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="addpar2">Add PAR2 contents to file contents:</label></td>
	<td>
		{html_radios id="addpar2" name='addpar2' values=$yesno_ids output=$yesno_names selected=$fsite->addpar2 separator='<br />'}
		<div class="hint">When going through PAR2 files, add them to the RAR file content list of the NZB.</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="lookupnfo">Lookup NFO:</label></td>
	<td>
		{html_radios id="lookupnfo" name='lookupnfo' values=$yesno_ids output=$yesno_names selected=$fsite->lookupnfo separator='<br />'}
		<div class="hint">Whether to attempt to retrieve an nfo file from usenet when processing binaries.<br/><strong>NOTE:
				disabling nfo lookups will disable movie lookups.</strong></div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="lookuptvrage">Lookup TV Rage:</label></td>
	<td>
		{html_radios id="lookuptvrage" name='lookuptvrage' values=$yesno_ids output=$yesno_names selected=$fsite->lookuptvrage separator='<br />'}
		<div class="hint">Whether to attempt to lookup tv rage ids on the web when processing binaries.</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="lookupimdb">Lookup Movies:</label></td>
	<td>
		{html_radios id="lookupimdb" name='lookupimdb' values=$yesno_ids output=$yesno_names selected=$fsite->lookupimdb separator='<br />'}
		<div class="hint">Whether to attempt to lookup film information from IMDB or TheMovieDB when processing
			binaries.
		</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="lookupanidb">Lookup AniDB:</label></td>
	<td>
		{html_radios id="lookupanidb" name='lookupanidb' values=$yesno_ids output=$yesno_names selected=$fsite->lookupanidb separator='<br />'}
		<div class="hint">Whether to attempt to lookup anime information from AniDB when processing binaries. Currently
			it is not recommend to enable this.
		</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="lookupmusic">Lookup Music:</label></td>
	<td>
		{html_options style="width:180px;" id="lookupmusic" name='lookupmusic' values=$lookupmusic_ids output=$lookupmusic_names selected=$fsite->lookupmusic}
		<div class="hint">Whether to attempt to lookup music information from Amazon when processing binaries.</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="lookupgames">Lookup Games:</label></td>
	<td>
		{html_options style="width:180px;" id="lookupgames" name='lookupgames' values=$lookupgames_ids output=$lookupgames_names selected=$fsite->lookupgames}
		<div class="hint">Whether to attempt to lookup game information from Amazon when processing binaries.</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="lookupbooks">Lookup Books:</label></td>
	<td>
		{html_options style="width:180px;" id="lookupbooks" name='lookupbooks' values=$lookupbooks_ids output=$lookupbooks_names selected=$fsite->lookupbooks}
		<div class="hint">Whether to attempt to lookup book information from Amazon when processing binaries.</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="book_reqids">Type of books to look up:</label></td>
	<td>
		{html_options_multiple id="book_reqids" name='book_reqids' values=$book_reqids_ids output=$book_reqids_names selected=$book_reqids_selected}
		<div class="hint">Categories of Books to lookup information for (only work if Lookup Books is set to yes).</div>
		</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="lookup_reqids">Lookup Request IDs:</label></td>
	<td>
		{html_options style="width:180px;" id="lookup_reqids" name='lookup_reqids' values=$lookup_reqids_ids output=$lookup_reqids_names selected=$fsite->lookup_reqids}
		<div class="hint">Whether to attempt to lookup Request IDs using the Request ID link below.</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="style">Request ID Link:</label></td>
	<td>
		<input id="request_url" class="long" name="request_url" type="text" value="{$fsite->request_url}"/>

		<div class="hint">Optional URL to lookup Request IDs. [REQUEST_ID] gets replaced with the request ID from the
			post. [GROUP_NM] Gets replaced with the group name.
		</div>
	</td>
</tr>

<tr>
	<td style="width:180px;"><label for="request_hours">Max hours to recheck Request IDs:</label></td>
	<td>
		<input id="request_hours" class="short" name="request_hours" type="text" value="{$fsite->request_hours}"/>

		<div class="hint">The maximum hours after a release is added to recheck for a Request ID match.</div>
	</td>
</tr>

<tr>
	<td style="width:180px;"><label for="newgroupscanmethod">Where to start new groups:</label></td>
	<td>
		{html_radios id="newgroupscanmethod" name='newgroupscanmethod' values=$yesno_ids output=$newgroupscan_names selected=$fsite->newgroupscanmethod separator='<br />'}
		<input class="short" id="newgroupdaystoscan" name="newgroupdaystoscan" type="text"
			   value="{$fsite->newgroupdaystoscan}"/> Days or
		<input class="small" id="newgroupmsgstoscan" name="newgroupmsgstoscan" type="text"
			   value="{$fsite->newgroupmsgstoscan}"/> Posts<br/>

		<div class="hint">Scan back X (posts/days) for each new group? Can backfill to scan further.</div>
	</td>
</tr>
<tr>
	<td style="width:180px;"><label for="safebackfilldate">Safe Backfill Date:</label></td>
	<td>
		<input class="small" id="safebackfilldate" name="safebackfilldate" type="text"
			   value="{$fsite->safebackfilldate}"/>

		<div class="hint">The target date for safe backfill. Format: YYYY-MM-DD</div>
	</td>
</tr>
</table>
</fieldset>
<fieldset>
	<legend>Advanced Settings - For advanced users</legend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="nzbsplitlevel">Nzb File Path Level Deep:</label></td>
			<td>
				<input id="nzbsplitlevel" class="short" name="nzbsplitlevel" type="text"
					   value="{$fsite->nzbsplitlevel}"/>

				<div class="hint">Levels deep to store the nzb Files.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="releaseretentiondays">Release Retention:</label></td>
			<td>
				<input class="short" id="releasedays" name="releaseretentiondays" type="text"
					   value="{$fsite->releaseretentiondays}"/>

				<div class="hint">!!THIS IS NOT HEADER RETENTION!! The number of days releases will be retained for use
					throughout site. Set to 0 to disable.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="partretentionhours">Part Retention Hours:</label></td>
			<td>
				<input class="short" id="parthours" name="partretentionhours" type="text"
					   value="{$fsite->partretentionhours}"/>

				<div class="hint">The number of hours incomplete parts and binaries will be retained.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="miscotherretentionhours">Misc->Other Retention Hours:</label></td>
			<td>
				<input class="short" id="miscotherhours" name="miscotherretentionhours" type="text"
					   value="{$fsite->miscotherretentionhours}"/>

				<div class="hint">The number of hours releases categorized as Misc->Other will be retained. Set to 0 to
					disable.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="releasecompletion">Release Completion:</label></td>
			<td>
				<input class="short" id="releasecompletion" name="releasecompletion" type="text"
					   value="{$fsite->releasecompletion}"/>

				<div class="hint">The minimum completion % to keep a release. Set to 0 to disable.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="delaytime">Delay Time Check:</label></td>
			<td>
				<input class="short" id="delaytime" name="delaytime" type="text" value="{$fsite->delaytime}"/>

				<div class="hint">The time in hours to wait, since last activity, before releases without parts counts
					in the subject are are created<br \> Setting this below 2 hours could create incomplete releases..
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="crossposttime">Crossposted Time Check:</label></td>
			<td>
				<input class="short" id="crossposttime" name="crossposttime" type="text"
					   value="{$fsite->crossposttime}"/>

				<div class="hint">The time in hours to check for crossposted releases.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="maxmssgs">Max Messages:</label></td>
			<td>
				<input class="short" id="maxmssgs" name="maxmssgs" type="text" value="{$fsite->maxmssgs}"/>

				<div class="hint">The maximum number of messages to fetch at a time from the server. Only raise this if
					you have php set right and lots of RAM.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="maxnzbsprocessed">Maximum NZBs stage5:</label></td>
			<td>
				<input class="short" id="maxnzbsprocessed" name="maxnzbsprocessed" type="text"
					   value="{$fsite->maxnzbsprocessed}"/>

				<div class="hint">The maximum amount of NZB files to create on stage 5 in update_releases.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="maxpartrepair">Maximum repair per run:</label></td>
			<td>
				<input class="short" id="maxpartrepair" name="maxpartrepair" type="text"
					   value="{$fsite->maxpartrepair}"/>

				<div class="hint">The maximum amount of articles to attempt to repair at a time. If you notice that you
					are getting a lot of parts into the partrepair table, it is possible that you USP is not keeping up
					with the requests. Try to reduce the threads to safe scripts, stop using safe scripts or stop using
					nntpproxy until improves. Ar least until the cause can be determined.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="partrepair">Part Repair:</label></td>
			<td>
				{html_radios id="partrepair" name='partrepair' values=$yesno_ids output=$yesno_names selected=$fsite->partrepair separator='<br />'}
				<div class="hint">Whether to attempt to repair parts or not, increases backfill/binaries updating
					time.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="safepartrepair">Part Repair for Backfill Scripts:</label></td>
			<td>
				{html_radios id="safepartrepair" name='safepartrepair' values=$yesno_ids output=$yesno_names selected=$fsite->safepartrepair separator='<br />'}
				<div class="hint">Whether to put unreceived parts into partrepair table when running binaries(safe) or
					backfill scripts.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="grabstatus">Update grabs:</label></td>
			<td>
				{html_radios id="grabstatus" name='grabstatus' values=$yesno_ids output=$yesno_names selected=$fsite->grabstatus separator='<br />'}
				<div class="hint">Whether to update download counts when someone downloads a release.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="showdroppedyencparts">Log Dropped Headers:</label></td>
			<td>
				{html_radios id="showdroppedyencparts" name='showdroppedyencparts' values=$yesno_ids output=$yesno_names selected=$fsite->showdroppedyencparts separator='<br />'}
				<div class="hint">For developers. Whether to log all headers that have 'yEnc' and are dropped. Logged to
					not_yenc/groupname.dropped.txt.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="tablepergroup">Table Per Group:</label></td>
			<td>
				{html_radios id="tablepergroup" name='tablepergroup' values=$yesno_ids output=$yesno_names selected=$fsite->tablepergroup separator='<br />'}
				<div class="hint">This uses separate tables for collections, binaries and parts for each group.<br/>This
					requires you to run convert_to_tpg.php or reset_truncate.php.<br/>This requires that you also run
					releases_threaded.py.
					<br/>Run: show variables like '%open%files%'; results should be higher than 10k, twice that if you
					are using TokuDB.;
					<br/><b>You may need to increase table_open_cache, open_files_limit and max_allowed_packet in
						my.cnf. Also, you may need to add the following to /etc/security/limits.conf<br/>mysql soft
						nofile 24000<br/>mysql hard nofile 32000</b>
				</div>
			</td>
		</tr>
	</table>
</fieldset>
<fieldset>
	<legend>Advanced - Postprocessing Settings</legend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="maxaddprocessed">Maximum add PP per run:</label></td>
			<td>
				<input class="short" id="maxaddprocessed" name="maxaddprocessed" type="text"
					   value="{$fsite->maxaddprocessed}"/>

				<div class="hint">The maximum amount of releases to process for passwords/previews/mediainfo per run.
					Every release gets processed here. This uses NNTP an connection, 1 per thread. This does not query
					Amazon.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="maxpartsprocessed">Maximum add PP parts downloaded:</label></td>
			<td>
				<input class="short" id="maxpartsprocessed" name="maxpartsprocessed" type="text"
					   value="{$fsite->maxpartsprocessed}"/>

				<div class="hint">If a part fails to download while post processing, this will retry up to the amount
					you set, then give up.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="passchkattempts">Maximum add PP parts checked:</label></td>
			<td>
				<input class="short" id="passchkattempts" name="passchkattempts" type="text"
					   value="{$fsite->passchkattempts}"/>

				<div class="hint">This overrides the above setting if set above 1. How many parts to check for a
					password before giving up. This slows down post processing massively, better to leave it 1.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="maxnfoprocessed">Maximum NFO files per run:</label></td>
			<td>
				<input class="short" id="maxnfoprocessed" name="maxnfoprocessed" type="text"
					   value="{$fsite->maxnfoprocessed}"/>

				<div class="hint">The maximum amount of NFO files to process per run. This uses NNTP an connection, 1
					per thread. This does not query Amazon.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="maxrageprocessed">Maximum TVRage per run:</label></td>
			<td>
				<input class="short" id="maxrageprocessed" name="maxrageprocessed" type="text"
					   value="{$fsite->maxrageprocessed}"/>

				<div class="hint">The maximum amount of TV shows to process with TVRage per run. This does not use an
					NNTP connection or query Amazon.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="maximdbprocessed">Maximum movies per run:</label></td>
			<td>
				<input class="short" id="maximdbprocessed" name="maximdbprocessed" type="text"
					   value="{$fsite->maximdbprocessed}"/>

				<div class="hint">The maximum amount of movies to process with IMDB per run. This does not use an NNTP
					connection or query Amazon.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="maxanidbprocessed">Maximum anidb per run:</label></td>
			<td>
				<input class="short" id="maxanidbprocessed" name="maxanidbprocessed" type="text"
					   value="{$fsite->maxanidbprocessed}"/>

				<div class="hint">The maximum amount of anime to process with anidb per run. This does not use an NNTP
					connection or query Amazon.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="maxmusicprocessed">Maximum music per run:</label></td>
			<td>
				<input class="short" id="maxmusicprocessed" name="maxmusicprocessed" type="text"
					   value="{$fsite->maxmusicprocessed}"/>

				<div class="hint">The maximum amount of music to process with amazon per run. This does not use an NNTP
					connection.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="maxgamesprocessed">Maximum games per run:</label></td>
			<td>
				<input class="short" id="maxgamesprocessed" name="maxgamesprocessed" type="text"
					   value="{$fsite->maxgamesprocessed}"/>

				<div class="hint">The maximum amount of games to process with amazon per run. This does not use an NNTP
					connection.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="maxbooksprocessed">Maximum books per run:</label></td>
			<td>
				<input class="short" id="maxbooksprocessed" name="maxbooksprocessed" type="text"
					   value="{$fsite->maxbooksprocessed}"/>

				<div class="hint">The maximum amount of books to process with amazon per run. This does not use an NNTP
					connection
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="fixnamesperrun">fixReleaseNames per Run:</label></td>
			<td>
				<input class="short" id="fixnamesperrun" name="fixnamesperrun" type="text"
					   value="{$fsite->fixnamesperrun}"/>

				<div class="hint">The maximum number of releases to check per run(threaded script only).</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="amazonsleep">Amazon sleep time:</label></td>
			<td>
				<input class="short" id="amazonsleep" name="amazonsleep" type="text" value="{$fsite->amazonsleep}"/>

				<div class="hint">Sleep time in milliseconds to wait in between amazon requests. If you thread
					post-proc, multiply by the number of threads. ie Postprocessing Threads = 12, Amazon sleep time =
					12000<br/><a href="https://affiliate-program.amazon.com/gp/advertising/api/detail/faq.html">https://affiliate-program.amazon.com/gp/advertising/api/detail/faq.html</a>
				</div>
			</td>
		</tr>
	</table>
</fieldset>
<fieldset>
	<legend>Connection Settings</legend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="compressedheaders">Use Compressed Headers:</label></td>
			<td>
				{html_radios id="compressedheaders" name='compressedheaders' values=$yesno_ids output=$yesno_names selected=$fsite->compressedheaders separator='<br />'}
				<div class="hint">Some servers allow headers to be sent over in a compressed format. If enabled this
					will use much less bandwidth, but processing times may increase.<br/>
					If you notice that update binaries or backfill seems to hang, look in htop and see if a group is
					being processed. If so, first try disabling compressed headers and let run until it processes the
					group at least once, then you can re-enable compressed headers.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="nntpretries">NNTP Retry Attempts:</label></td>
			<td>
				<input class="short" id="nntpretries" name="nntpretries" type="text" value="{$fsite->nntpretries}"/>

				<div class="hint">The maximum number of retry attmpts to connect to nntp provider. On error, each retry
					takes approximately 5 seconds nntp returns reply. (Default 10)
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="nntpproxy">Use NNTP Proxy:</label></td>
			<td>
				{html_radios id="nntpproxy" name='nntpproxy' values=$yesno_ids output=$yesno_names selected=$fsite->nntpproxy separator='<br />'}
				<div class="hint">Using the NNTP Proxy for nZEDb can improve performance of nZEDb dramatically. It uses
					connection pooling which not only give more control over the number of connections to use but also
					reduces time for connection setup/teardown. The proxy also takes care of compressed headers for you.
					To use this featrure you will need to install pynntp (sudo pip install pynntp or sudo easy_install
					pynntp) and socketpool (sudo pip install socketpool or sudo easy_install socketpool) (ensure python2
					is default) and edit the configuration file (nntpproxy.conf and nntpproxy_a.conf) in the
					update_scripts/python_scripts/lib (copy sample) directory and finally edit your www/config.php file
					to use the proxy (username and password are ignored by the proxy - make then anything you like - the
					proxy doesn't use ssl either). Make sure you turn off the use compressed headers option here in site
					preferences (the proxy uses compressed headers by default and passes on decompressed data).
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="alternate_nntp">Alternate NNTP Provider:</label></td>
			<td>
				{html_radios id="alternate_nntp" name='alternate_nntp' values=$yesno_ids output=$yesno_names selected=$fsite->alternate_nntp separator='<br />'}
				<div class="hint">This sets Postproccessing Additional/Nfo to use the alternate NNTP provider as set in
					config.php.
				</div>
			</td>
		</tr>
	</table>
</fieldset>
<fieldset>
	<legend>Advanced - Threaded Settings</legend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="binarythreads">Update Binaries Threads:</label></td>
			<td>
				<input class="short" id="binarythreads" name="binarythreads" type="text"
					   value="{$fsite->binarythreads}"/>

				<div class="hint">The number of threads for update_binaries. If you notice that you are getting a lot of
					parts into the partrepair table, it is possible that you USP is not keeping up with the requests.
					Try to reduce the threads to safe scripts, stop using safe scripts or stop using nntpproxy until
					improves. Ar least until the cause can be determined.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="backfillthreads">Backfill Threads:</label></td>
			<td>
				<input class="short" id="backfillthreads" name="backfillthreads" type="text"
					   value="{$fsite->backfillthreads}"/>

				<div class="hint">The number of threads for backfill.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="releasesthreads">Update Releases Threads:</label></td>
			<td>
				<input class="short" id="releasesthreads" name="releasesthreads" type="text"
					   value="{$fsite->releasesthreads}"/>

				<div class="hint">The number of threads for update_releases. This is only for tablepergroup.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="nzbthreads">Import-nzb Threads:</label></td>
			<td>
				<input class="short" id="nzbthreads" name="nzbthreads" type="text" value="{$fsite->nzbthreads}"/>

				<div class="hint">The number of threads for import-nzb(bulk). This will thread each subfolder.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="postthreads">Postprocessing Additional Threads:</label></td>
			<td>
				<input class="short" id="postthreads" name="postthreads" type="text" value="{$fsite->postthreads}"/>

				<div class="hint">The number of threads for additional postprocessing. This includes deep rar
					inspection, preview and sample creation and nfo processing.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="postthreadsnon">Postprocessing Non-Amazon Threads:</label></td>
			<td>
				<input class="short" id="postthreadsnon" name="postthreadsnon" type="text"
					   value="{$fsite->postthreadsnon}"/>

				<div class="hint">The number of threads for non-amazon postprocessing. This includes movies, anime and
					tv lookups.
				</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="fixnamethreads">fixReleaseNames Threads:</label></td>
			<td>
				<input class="short" id="fixnamethreads" name="fixnamethreads" type="text"
					   value="{$fsite->fixnamethreads}"/>

				<div class="hint">The number of threads for fixReleasesNames. This includes md5, nfos and filenames.
				</div>
			</td>
		</tr>
	</table>
</fieldset>
<fieldset>
	<legend>User Settings</legend>
	<table class="input">
		<tr>
			<td style="width:180px;"><label for="registerstatus">Registration Status:</label></td>
			<td>
				{html_radios id="registerstatus" name='registerstatus' values=$registerstatus_ids output=$registerstatus_names selected=$fsite->registerstatus separator='<br />'}
				<div class="hint">The status of registrations to the site.</div>
			</td>
		</tr>
		<tr>
			<td style="width:180px;"><label for="storeuserips">Store User Ip:</label></td>
			<td>
				{html_radios id="storeuserips" name='storeuserips' values=$yesno_ids output=$yesno_names selected=$fsite->storeuserips separator='<br />'}
				<div class="hint">Whether to store the users ip address when they signup or login.</div>
			</td>
		</tr>
	</table>
</fieldset>
<input type="submit" value="Save Site Settings"/>
</form>