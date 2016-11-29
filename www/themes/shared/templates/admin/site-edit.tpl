<div>
<table border="0" width="100%">
	<tr>
		<td><h1>{$page->title}</h1></td>
		<td></td>
		<td align="right" class="top-nav">
			<form id="site_jumpbox" action="#">
				<select>
					<option value="site_externalapis">3rd Party API Keys</option>
					<option value="site_externalapps">3rd Party Application Paths</option>
					<option value="site_developerset">Developer Settings</option>
					<option value="site_googlestatbanner">Google Adsense, Analytics and 3rd Party Banners</option>
					<option value="site_languagecat">Language/Categorization options</option>
					<option value="site_main">Main Site Settings, Html layout, Tags</option>
					<option value="site_movietrailerset">Movie Trailer Settings</option>
					<option value="site_nfoprocessing">NFO Processing Settings</option>
					<option value="site_nzbsettings">NZB Settings</option>
					<option value="site_partrepair">Part Repair Settings</option>
					<option value="site_postadditional">Post Process Additional Settings - Rar/ZIP file processing</option>
					<option value="site_postsettings">Post Process Settings</option>
					<option value="site_releaseset">Release Settings</option>
					<option value="site_requidset">RequestID Settings</option>
					<option value="site_sabnzbd">SABnzbd Integration Settings</option>
					<option value="site_threadsettings">Threaded Settings</option>
					<option value="site_usenetset">Usenet Settings</option>
					<option value="site_usersettings">User Settings</option>
					<option value="site_variousset">Various Settings</option>
				</select>
			</form>
		</td>
	</tr>
</table>
</div>
<form id="site_settings" action="{$SCRIPT_NAME}?action=submit" method="post">
	{if $error != ''}
		<div class="error">{$error}</div>
	{/if}

	<fieldset id="site_main">
		<legend>Main Site Settings, Html Layout, Tags</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="title">Title:</label></td>
				<td>
					<input id="title" class="long" name="site.main.title" type="text" value="{$site->title}"/>
					<div class="hint">Displayed around the site and contact form as the name for the site.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="strapline">Strapline:</label></td>
				<td>
					<input id="strapline" class="long" name="site.main.strapline" type="text" value="{$site->strapline}"/>
					<div class="hint">Displayed in the header on every public page.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="metatitle">Meta Title:</label></td>
				<td>
					<input id="metatitle" class="long" name="site.main.metatitle" type="text" value="{$site->metatitle}"/>
					<div class="hint">Stem meta-tag appended to all page title tags.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="metadescription">Meta Description:</label></td>
				<td>
					<textarea id="metadescription" name="site.main.metadescription">{$site->metadescription}</textarea>
					<div class="hint">Stem meta-description appended to all page meta description tags.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="metakeywords">Meta Keywords:</label></td>
				<td>
					<textarea id="metakeywords" name="site.main.metakeywords">{$site->metakeywords}</textarea>
					<div class="hint">Stem meta-keywords appended to all page meta keyword tags.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="footer">Footer:</label></td>
				<td>
					<textarea id="footer" name="site.main.footer">{$site->footer}</textarea>
					<div class="hint">Displayed in the footer section of every public page.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="home_link">Default Home Page:</label></td>
				<td>
					<input id="home_link" class="long" name="site.main.home_link" type="text" value="{$site->home_link}"/>
					<div class="hint">The relative path to a the landing page shown when a user logs in, or clicks the home link.</div>
				</td>
			</tr>
			<tr>
				<td style="width: 180px;"><label for="coverspath">Cover&apos;s path:</label></td>
				<td>
					<input id="coverspath" class="long" name="site.main.coverspath" type="text" value="{$coversPath}"/>
					<div class="hint">The absolute path to the place covers will be stored.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="codename">Code Name:</label></td>
				<td>
					<input id="codename" name="site.main.code" type="text" value="{$site->code}"/>
					<input type="hidden" name="id" value="{$site->id}"/>
					<div class="hint">A just for fun value shown in debug and not on public pages.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="style">Theme:</label></td>
				<td>
					{html_options style="width:180px;" class="siteeditstyle" id="style" name='site.main.style' values=$themelist output=$themelist selected=$site->style}
					<div class="hint">The theme folder which will be loaded for css and images. (Use / for default)</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="style">User Menu Position:</label></td>
				<td>
					{html_options style="width:180px;" class="siteeditmenuposition" id="menuposition" name='site.main.menuposition' values=$menupos_ids output=$menupos_names selected=$site->menuposition}
					<div class="hint">Where the menu should appear. Moving the menu to the top will require using a theme
						which widens the content panel. (not currently functional)
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="dereferrer_link">Dereferrer Link:</label></td>
				<td>
					<input id="dereferrer_link" class="long" name="site.main.dereferrer_link" type="text" value="{$site->dereferrer_link}"/>
					<div class="hint">Optional URL to prepend to external links</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="email">Email:</label></td>
				<td>
					<input id="email" class="long" name="site.main.email" type="text" value="{$site->email}"/>
					<div class="hint">Shown in the contact us page, and where the contact html form is sent to.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="tandc">Terms and Conditions:</label></td>
				<td>
					<textarea id="tandc" name="site.main.tandc">{$site->tandc}</textarea>
					<div class="hint">Text displayed in the terms and conditions page.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="loggingopt">Logging Option:</label></td>
				<td>
					{html_options style="width:180px;" class="loggingopt" id="loggingopt" name='site.main.loggingopt' values=$loggingopt_ids output=$loggingopt_names selected=$site->loggingopt}
					<div class="hint">Where you would like to log failed logins to the site.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="logfile">Logfile Location:</label></td>
				<td>
					<input id="logfile" class="long" name="site.main.logfile" type="text" value="{$site->logfile}"/>
					<div class="hint">Location of log file (MUST be set if logging to file is set).</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_languagecat">
		<legend>Language/Categorization options</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="categorizeforeign">Categorize Foreign:</label></td>
				<td>
					{html_radios id="categorizeforeign" name='indexer.categorise.categorizeforeign'
					values=$yesno_ids output=$yesno_names selected=$site->categorizeforeign separator='<br />'}
					<div class="hint">This only works if the above is set to english. Whether to send foreign movies/tv to
						foreign sections or not. If set to true they will go in foreign categories.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="catwebdl">Categorize WEB-DL:</label></td>
				<td>
					{html_radios id="catwebdl" name='indexer.categorise.catwebdl' values=$yesno_ids output=$yesno_names selected=$site->catwebdl separator='<br />'}
					<div class="hint">Whether to send WEB-DL to the WEB-DL section or not. If set to true they will go in
						WEB-DL category, false will send them in HD TV.<br/>This will also make them inaccessible to
						Sickbeard and possibly Couchpotato.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="imdburl">IMDB.com:</label></td>
				<td>
					{html_options style="width:180px;" class="imdburl" id="imdburl" name='indexer.categorise.imdburl' values=$imdb_urls output=$imdburl_names selected=$site->imdburl}
					<div class="hint">Akas.imdb.com returns titles in their original title, imdb.com returns titles based on
						your IP address (if you are in france, you will get french titles).
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="imdblanguage">IMDB/Tmdb Language:</label></td>
				<td>
					{html_options style="width:180px;" class="imdblanguage" id="imdblanguage" name='indexer.categorise.imdblanguage' values=$imdblang_ids output=$imdblang_names selected=$site->imdblanguage}
					<div class="hint">Which language to lookup when sending requests to IMDB/Tmdb. (If akas.imdb.com is set,
						imdb still returns the original titles.)
					</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_googlestatbanner">
		<legend>Google Adsense, Analytics and 3rd Party Banners</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="google_analytics_acc">Google Analytics:</label></td>
				<td>
					<input id="google_analytics_acc" name="site.google.google_analytics_acc" type="text" value="{$site->google_analytics_acc}"/>
					<div class="hint">e.g. UA-xxxxxx-x</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="google_adsense_acc">Google Adsense:</label></td>
				<td>
					<input id="google_adsense_acc" name="site.google.google_adsense_acc" type="text" value="{$site->google_adsense_acc}"/>
					<div class="hint">e.g. pub-123123123123123</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="google_adsense_search">Google Adsense Search:</label></td>
				<td>
					<input id="google_adsense_search" name="site.google.google_adsense_search" type="text" value="{$site->google_adsense_search}"/>
					<div class="hint">The ID of the google search ad panel displayed at the bottom of the left menu.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="adheader">Advert Space in Banner:</label></td>
				<td>
					<textarea rows="3" placeholder="Place your ad banner code here." id="adheader" name="site.google.adheader">{$site->adheader}</textarea>
					<div class="hint">The banner slot in the header.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="adbrowse">Advert Space in Browse List:</label></td>
				<td>
					<textarea rows="3" placeholder="Place your ad banner code here." id="adbrowse" name="site.google.adbrowse">{$site->adbrowse}</textarea>
					<div class="hint">The banner slot in the header.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="addetail">Advert Space in Detail View:</label></td>
				<td>
					<textarea rows="3" placeholder="Place your ad banner code here." id="addetail" name="site.google.addetail">{$site->addetail}</textarea>
					<div class="hint">The banner slot in the release details view.</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_externalapis">
		<legend>3<sup>rd</sup> Party API Keys</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="tmdbkey">TMDB Key:</label></td>
				<td>
					<input id="tmdbkey" class="long" name="APIs..tmdbkey" type="text" value="{$site->tmdbkey}"/>

					<div class="hint">The api key used for access to tmdb.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="rottentomatokey">Rotten Tomatoes Key:</label></td>
				<td>
					<input id="rottentomatokey" class="long" name="APIs..rottentomatokey" type="text" value="{$site->rottentomatokey}"/>
					<div class="hint">The api key used for access to rotten tomatoes.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="amazonpubkey">Amazon Public Key:</label></td>
				<td>
					<input id="amazonpubkey" class="long" name="APIs..amazonpubkey" type="text" value="{$site->amazonpubkey}"/>
					<div class="hint">The amazon public api key. Used for music/book lookups.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="amazonprivkey">Amazon Private Key:</label></td>
				<td>
					<input id="amazonprivkey" class="long" name="APIs..amazonprivkey" type="text" value="{$site->amazonprivkey}"/>
					<div class="hint">The amazon private api key. Used for music/book lookups.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="amazonassociatetag">Amazon Associate Tag:</label></td>
				<td>
					<input id="amazonassociatetag" class="long" name="APIs..amazonassociatetag" type="text" value="{$site->amazonassociatetag}"/>
					<div class="hint">The amazon associate tag. Used for music/book lookups.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="giantbombkey">Giant Bomb API Key:</label></td>
					<td>
						<input id="giantbombkey" class="long" name="APIs..giantbombkey" type="text" value="{$site->giantbombkey}"/>
						<div class="hint">The giantbomb key. Used for game lookups.</div>
					</td>
				</tr>
			<tr>
				<td style="width:180px;"><label for="trakttvclientkey">Trakt.tv API Client ID:</label></td>
				<td>
					<input id="trakttvclientkey" class="long" name="APIs..trakttvclientkey" type="text" value="{$site->trakttvclientkey}"/>
					<div class="hint">The Trakt.tv API v2 Client ID (SHA256 hash - 64 characters long string). Used for movie and tv lookups.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="anidbkey">Anidb API key:</label></td>
				<td>
					<input id="anidbkey" class="long" name="APIs..anidbkey" type="text" value="{$site->anidbkey}"/>
					<div class="hint">The Anidb api key. Used for Anime lookups.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="fanarttvkey">Fanart.tv API key:</label></td>
				<td>
					<input id="fanarttvkey" class="long" name="APIs..fanarttvkey" type="text" value="{$site->fanarttvkey}"/>
					<div class="hint">The Fanart.tv api key. Used for Fanart.tv lookups. Fanart.tv would appreciate it if
						you use this service to help them out by adding high quality images not already available on TMDB.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="recaptchasitekey">ReCaptcha Site Key:</label></td>
				<td>
					<input id="recaptchasitekey" class="long" name="APIs.recaptcha.sitekey" type="text" value="{$site->recaptchasitekey}"/>
					<div class="hint">Register your application with ReCaptcha <a href="https://www.google.com/recaptcha" target="_blank">here</a>
						to get your site and secret keys. Adding these keys will place ReCaptch's on all user-input forms. (Login, Register, Contact Us, Forgot Password)
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="recaptchasecretkey">ReCaptcha Secret key:</label></td>
				<td>
					<input id="recaptchasecretkey" class="long" name="APIs.recaptcha.secretkey" type="text" value="{$site->recaptchasecretkey}"/>
					<div class="hint">This is the required secret key for ReCaptcha to work, see the above setting hint for further information.
					</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_externalapps">
		<legend>3<sup>rd</sup> Party Application Paths</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="unrarpath">Unrar Path:</label></td>
				<td>
					<input id="unrarpath" class="long" name="apps..unrarpath" type="text" value="{$site->unrarpath}"/>
					<div class="hint">The path to an unrar binary, used in deep password detection and media info grabbing.
						<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/unrar.exe</span>
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="tmpunrarpath">Temp Unrar File Path:</label></td>
				<td>
					<input id="tmpunrarpath" class="long" name="..tmpunrarpath" type="text" value="{$site->tmpunrarpath}"/>
					<div class="hint">The path to where unrar puts files. WARNING: This directory will have its contents deleted.
						<br/>Use forward slashes in windows <span style="font-family:courier;">c:/temp/path/stuff/will/be/unpacked/to</span>
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="zippath">7za Path:</label></td>
				<td>
					<input id="zippath" class="long" name="apps..7zippath" type="text" value="{$site->zippath}"/>
					<div class="hint">The path to the 7za (7zip command line in windows) binary, used for grabbing nfos from
						compressed zip files.
						<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/7z.exe</span>
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="yydecoderpath">yEnc Type:</label></td>
				<td>
					<input id="yydecoderpath" class="long" name="apps..yydecoderpath" type="text" value="{$site->yydecoderpath}"/>
					<div class="hint">
						If you use a binary to decode yEnc, put the full path (including filename) to it here.
						<br/>><a style="color:#0082E1" href="http://sourceforge.net/projects/yydecode/files/yydecode/0.2.10/">
						Download yydecode from sourceforge.</a>
						<br/>Leave this empty if you use either of the php extensions (<a style="color:#0082E1"
						href="https://github.com/kevinlekiller/simple_php_yenc_decode">simple_php_yenc_decode.</a>)
						we provide. They will be automatically detected and used if installed correctly.
						<br/> The default is to use PHP code which is <strong>slow</strong>, so one of the
						alternatives is recommended.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="mediainfopath">Mediainfo Path:</label></td>
				<td>
					<input id="mediainfopath" class="long" name="apps..mediainfopath" type="text" value="{$site->mediainfopath}"/>
					<div class="hint">The path to the <a href="http://mediainfo.sourceforge.net">mediainfo</a> binary. Used
						for deep file media analysis. Use empty path to disable mediainfo checks
						<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/mediainfo.exe</span></div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="ffmpegpath">FFmpeg or Avconv Path:</label></td>
				<td>
					<input id="ffmpegpath" class="long" name="apps..ffmpegpath" type="text" value="{$site->ffmpegpath}"/>
					<div class="hint">The path to the <a href="http://www.ffmpeg.org/">ffmpeg</a> or <a
								href="https://libav.org/">avconv</a> binary. Used for making thumbnails and video/audio
						previews.
						<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/ffmpeg.exe</span>
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="timeoutpath">GNU Timeout Path:</label></td>
				<td>
					<input id="timeoutpath" class="long" name="apps..timeoutpath" type="text" value="{$site->timeoutpath}"/>
					<div class="hint">The path to the <a href="http://linux.die.net/man/1/timeout">timeout</a> binary.
						This is used to limit the amount of time unrar/7zip/mediainfo/ffmpeg/avconv can run.
						You can the time limit in the process additional section.
						You can leave this empty to disable this.
						<br/>Use forward slashes in windows <span style="font-family:courier;">c:/path/to/timeout.exe</span>
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="magic_file_path">Magic Numbers file:</label></td>
				<td>
					<input id="magic_file_path"
						class="long"
						name="apps.indexer.magic_file_path"
						type="text"
						value="{$settings['apps']['indexer']['magic_file_path']['value']}" />

					<div class="hint">{$settings['apps']['indexer']['magic_file_path']['hint']}</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_sabnzbd">
		<legend>SABnzbd Integration Settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="sabintegrationtype">Integration Type:</label></td>
				<td>
					{html_radios id="sabintegrationtype" name='apps.sabnzbplus.integrationtype' values=$sabintegrationtype_ids output=$sabintegrationtype_names selected=$site->sabintegrationtype separator='<br />'}
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
					<input id="saburl" class="long" name="apps.sabnzbplus.url" type="text" value="{$site->saburl}"/>
					<div class="hint">The url of the SAB installation, for example: http://localhost:8080/sabnzbd/</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="sabapikey">SABnzbd Api Key:</label></td>
				<td>
					<input id="sabapikey" class="long" name="apps.sabnzbplus.apikey" type="text" value="{$site->sabapikey}"/>
					<div class="hint">The Api key of the SAB installation. Can be the full api key or the nzb api key (as of
						SAB 0.6)
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="sabapikeytype">Api Key Type:</label></td>
				<td>
					{html_radios id="sabapikeytype" name='apps.sabnzbplus.apikeytype' values=$sabapikeytype_ids output=$sabapikeytype_names selected=$site->sabapikeytype separator='<br />'}
					<div class="hint">Select the type of api key you entered in the above setting</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="sabpriority">Priority Level:</label></td>
				<td>
					{html_options style="width:180px;" id="sabpriority" name='apps.sabnzbplus.priority' values=$sabpriority_ids output=$sabpriority_names selected=$site->sabpriority}
					<div class="hint">Set the priority level for NZBs that are added to your queue</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_releaseset">
	<legend>Release Settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="minfilestoformrelease">Minimum Files to Make a Release:</label></td>
				<td>
					<input class="short" id="minfilestoformrelease" name=".release.minfilestoformrelease"
							type="text" value="{$site->minfilestoformrelease}"/>
					<div class="hint">The minimum number of files to make a release. i.e. if set to two, then releases which only
						contain one file will not be created.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="minsizetoformrelease">Minimum File Size to Make a Release:</label></td>
				<td>
					<input class="small" id="minsizetoformrelease" name=".release.minsizetoformrelease" type="text" value="{$site->minsizetoformrelease}"/>
					<div class="hint">The minimum total size in bytes to make a release. If set to 0, then ignored. Only deletes
						during release creation.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxsizetoformrelease">Maximum File Size to Make a Release:</label></td>
				<td>
					<input class="small" id="maxsizetoformrelease" name="..maxsizetoformrelease" type="text" value="{$site->maxsizetoformrelease}"/>
					<div class="hint">The maximum total size in bytes to make a release. If set to 0, then ignored. Only deletes
						during release creation.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="deletepasswordedrelease">Delete Passworded Releases:</label></td>
				<td>
					{html_radios id="deletepasswordedrelease" name='..deletepasswordedrelease' values=$yesno_ids output=$yesno_names selected=$site->deletepasswordedrelease separator='<br />'}
					<div class="hint">Whether to delete releases which are passworded.<br/></div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="deletepossiblerelease">Delete Possibly Passworded Releases:</label></td>
				<td>
					{html_radios id="deletepossiblerelease" name='..deletepossiblerelease' values=$yesno_ids output=$yesno_names selected=$site->deletepossiblerelease separator='<br />'}
					<div class="hint">Whether to delete releases which are potentially passworded. This applies to your post process additional inner file blacklist.<br/></div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="showpasswordedrelease">Show Passworded Releases:</label></td>
				<td>
					{html_options style="width:180px;" id="showpasswordedrelease" name='..showpasswordedrelease' values=$passworded_ids output=$passworded_names selected=$site->showpasswordedrelease}
					<div class="hint">Whether to show passworded or potentially passworded releases in browse, search, api and rss
						feeds. Potentially passworded means releases which contain .cab or .ace files which are
						typically password protected. (*yes): Unprocessed releases are hidden. (*no): Unprocessed releases are displayed.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="releaseretentiondays">Release Retention:</label></td>
				<td>
					<input class="short" id="releaseretentiondays" name="..releaseretentiondays" type="text" value="{$site->releaseretentiondays}"/>
					<div class="hint">!!THIS IS NOT HEADER RETENTION!! The number of days releases will be retained for use throughout site. Set to 0 to disable.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="miscotherretentionhours">Misc->Other Retention Hours:</label></td>
				<td>
					<input class="short" id="miscotherretentionhours" name="..miscotherretentionhours" type="text" value="{$site->miscotherretentionhours}"/>
					<div class="hint">The number of hours releases categorized as Misc->Other will be retained. Set to 0 to disable.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="mischashedretentionhours">Misc->Hashed Retention Hours:</label></td>
				<td>
					<input class="short" id="mischashedretentionhours" name="..mischashedretentionhours" type="text" value="{$site->mischashedretentionhours}"/>
					<div class="hint">The number of hours releases categorized as Misc->Hashed will be retained. Set to 0 to disable.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="releasecompletion">Release Completion:</label></td>
				<td>
					<input class="short" id="releasecompletion" name="..releasecompletion" type="text"  value="{$site->releasecompletion}"/>
					<div class="hint">The minimum completion % to keep a release. Set to 0 to disable.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="grabstatus">Update grabs:</label></td>
				<td>
					{html_radios id="grabstatus" name='..grabstatus' values=$yesno_ids output=$yesno_names selected=$site->grabstatus separator='<br />'}
					<div class="hint">Whether to update download counts when someone downloads a release.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="crossposttime">Crossposted Time Check:</label></td>
				<td>
					<input class="short" id="crossposttime" name="..crossposttime" type="text" value="{$site->crossposttime}"/>
					<div class="hint">The time in hours to check for crossposted releases - this will delete 1 of the releases if the 2 are posted by the same person in the same time period.</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_requidset">
		<legend>RequestID Settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="lookup_reqids">Lookup Request IDs:</label></td>
				<td>
					{html_options style="width:180px;" id="lookup_reqids" name='..lookup_reqids' values=$lookup_reqids_ids output=$lookup_reqids_names selected=$site->lookup_reqids}
					<div class="hint">Whether to attempt to lookup Request IDs using the Request ID link below. This will rename your releases to proper PreDB names.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="request_url">Request ID Link:</label></td>
				<td>
					<input id="request_url" class="long" name="..request_url" type="text" value="{$site->request_url}"/>
					<div class="hint">Optional URL to lookup Request IDs. [REQUEST_ID] gets replaced with the request ID from the
						post. [GROUP_NM] Gets replaced with the group name.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="request_hours">Max hours to recheck Request IDs:</label></td>
				<td>
					<input id="request_hours" class="short" name="..request_hours" type="text" value="{$site->request_hours}"/>
					<div class="hint">The maximum hours after a release is added to recheck for a Request ID match.</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_nzbsettings">
	<legend>NZB Settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="nzbpath">Nzb File Path:</label></td>
				<td>
					<input id="nzbpath" class="long" name="..nzbpath" type="text" value="{$site->nzbpath}"/>
					<div class="hint">The directory where nzb files will be stored.
						<br /><strong>If you change this, you must move your NZB files to the new folder!</strong>
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="nzbsplitlevel">Nzb File Path Level Deep:</label></td>
				<td>
					<input id="nzbsplitlevel" class="short" name="..nzbsplitlevel" type="text" value="{$site->nzbsplitlevel}"/>
					<div class="hint">Levels deep to store the nzb Files.
						<br /><strong>If you change this you must run the misc/testing/NZBs/nzb-reorg.php script!</strong>
					</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_postsettings">
	<legend>Post Process Settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="lookuppar2">Lookup PAR2:</label></td>
				<td>
					{html_radios id="lookuppar2" name='..lookuppar2' values=$yesno_ids output=$yesno_names selected=$site->lookuppar2 separator='<br />'}
					<div class="hint">Whether to attempt to find a better name for releases in misc->other using the PAR2 file.<br/>
						<strong>NOTE: this can be slow depending on the group!</strong>
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="addpar2">Add PAR2 contents to file contents:</label></td>
				<td>
					{html_radios id="addpar2" name='..addpar2' values=$yesno_ids output=$yesno_names selected=$site->addpar2 separator='<br />'}
					<div class="hint">When going through PAR2 files, add them to the RAR file content list of the NZB.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="lookupxxx">Lookup XXX:</label></td>
				<td>
					{html_radios id="lookupxxx" name='..lookupxxx' values=$yesno_ids output=$yesno_names selected=$site->lookupxxx separator='<br />'}
					<div class="hint">Whether to attempt to lookup film information from ADE or Popporn.</div>
				</td>
			</tr>
			<tr>
			<tr>
				<td style="width:180px;"><label for="lookupanidb">Lookup AniDB:</label></td>
				<td>
					{html_radios id="lookupanidb" name='..lookupanidb' values=$yesno_ids output=$yesno_names selected=$site->lookupanidb separator='<br />'}
					<div class="hint">Whether to attempt to lookup anime information from AniDB. Currently it is not recommend to enable this.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="lookupmusic">Lookup Music:</label></td>
				<td>
					{html_options style="width:180px;" id="lookupmusic" name='..lookupmusic' values=$lookupmusic_ids output=$lookupmusic_names selected=$site->lookupmusic}
					<div class="hint">Whether to attempt to lookup music information from Amazon.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="lookupgames">Lookup Games:</label></td>
				<td>
					{html_options style="width:180px;" id="lookupgames" name='..lookupgames' values=$lookupgames_ids output=$lookupgames_names selected=$site->lookupgames}
					<div class="hint">Whether to attempt to lookup game information from Amazon.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="lookupbooks">Lookup Books:</label></td>
				<td>
					{html_options style="width:180px;" id="lookupbooks" name='..lookupbooks' values=$lookupbooks_ids output=$lookupbooks_names selected=$site->lookupbooks}
					<div class="hint">Whether to attempt to lookup book information from Amazon.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="lookupimdb">Lookup Movies:</label></td>
				<td>
					{html_options style="width:180px;" id="lookupimdb" name='..lookupimdb' values=$lookupmovies_ids output=$lookupmovies_names selected=$site->lookupimdb}
					<div class="hint">Whether to attempt to lookup film information from IMDB or TheMovieDB.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="lookuptvrage">Lookup TV:</label></td>
				<td>
					{html_options style="width:180px;" id="lookuptvrage" name='..lookuptvrage' values=$lookuptv_ids output=$lookuptv_names selected=$site->lookuptvrage}
					<div class="hint">Whether to attempt to lookup tv rage ids on the web.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="book_reqids">Type of books to look up:</label></td>
				<td>
					{html_options_multiple id="book_reqids" name='..book_reqids' values=$book_reqids_ids output=$book_reqids_names selected=$book_reqids_selected}
					<div class="hint">Categories of Books to lookup information for (only work if Lookup Books is set to yes).</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxrageprocessed">Maximum TVRage per run:</label></td>
				<td>
					<input class="short" id="maxrageprocessed" name="..maxrageprocessed" type="text" value="{$site->maxrageprocessed}"/>
					<div class="hint">The maximum amount of TV shows to process with TVRage per run. This does not use an
						NNTP connection or query Amazon.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maximdbprocessed">Maximum movies per run:</label></td>
				<td>
					<input class="short" id="maximdbprocessed" name="..maximdbprocessed" type="text" value="{$site->maximdbprocessed}"/>
					<div class="hint">The maximum amount of movies to process with IMDB per run. This does not use an NNTP
						connection or query Amazon.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxxxxprocessed">Maximum xxx per run:</label></td>
				<td>
					<input class="short" id="maxxxxprocessed" name="..maxxxxprocessed" type="text" value="{$site->maxxxxprocessed}"/>
					<div class="hint">The maximum amount of XXX to process per run. This does not use an NNTP
						connection or query Amazon.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxanidbprocessed">Maximum anidb per run:</label></td>
				<td>
					<input class="short" id="maxanidbprocessed" name="..maxanidbprocessed" type="text" value="{$site->maxanidbprocessed}"/>
					<div class="hint">The maximum amount of anime to process with anidb per run. This does not use an NNTP
						connection or query Amazon.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxmusicprocessed">Maximum music per run:</label></td>
				<td>
					<input class="short" id="maxmusicprocessed" name="..maxmusicprocessed" type="text" value="{$site->maxmusicprocessed}"/>
					<div class="hint">The maximum amount of music to process with amazon per run. This does not use an NNTP connection.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxgamesprocessed">Maximum games per run:</label></td>
				<td>
					<input class="short" id="maxgamesprocessed" name="..maxgamesprocessed" type="text" value="{$site->maxgamesprocessed}"/>
					<div class="hint">The maximum amount of games to process with amazon per run. This does not use an NNTP connection.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxbooksprocessed">Maximum books per run:</label></td>
				<td>
					<input class="short" id="maxbooksprocessed" name="..maxbooksprocessed" type="text" value="{$site->maxbooksprocessed}"/>
					<div class="hint">The maximum amount of books to process with amazon per run. This does not use an NNTP connection</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="amazonsleep">Amazon sleep time:</label></td>
				<td>
					<input class="short" id="amazonsleep" name="..amazonsleep" type="text" value="{$site->amazonsleep}"/>
					<div class="hint">Sleep time in milliseconds to wait in between amazon requests. If you thread
						post-proc, multiply by the number of threads. ie Postprocessing Threads = 12, Amazon sleep time =
						12000<br/><a href="https://affiliate-program.amazon.com/gp/advertising/api/detail/faq.html">https://affiliate-program.amazon.com/gp/advertising/api/detail/faq.html</a>
					</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_nfoprocessing">
	<legend>NFO Processing Settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="lookupnfo">Lookup NFO:</label></td>
				<td>
					{html_radios id="lookupnfo" name='..lookupnfo' values=$yesno_ids output=$yesno_names selected=$site->lookupnfo separator='<br />'}
					<div class="hint">Whether to attempt to retrieve an nfo file from usenet.<br/>
						<strong>NOTE: disabling nfo lookups will disable movie lookups.</strong>
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxnfoprocessed">Maximum NFO files per run:</label></td>
				<td>
					<input class="short" id="maxnfoprocessed" name="..maxnfoprocessed" type="text" value="{$site->maxnfoprocessed}"/>
					<div class="hint">The maximum amount of NFO files to process per run. This uses NNTP an connection, 1
						per thread. This does not query Amazon.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxsizetoprocessnfo">Maximum Release Size to process NFOs:</label></td>
				<td>
					<input class="short" id="maxsizetoprocessnfo" name="..maxsizetoprocessnfo" type="text" value="{$site->maxsizetoprocessnfo}"/>
					<div class="hint">The maximum size in gigabytes of a release to process it for NFOs. If set to 0, then ignored.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="minsizetoprocessnfo">Minimum Release Size to process NFOs:</label></td>
				<td>
					<input class="short" id="minsizetoprocessnfo" name="..minsizetoprocessnfo" type="text" value="{$site->minsizetoprocessnfo}"/>
					<div class="hint">The minimum size in megabytes of a release to process it for NFOs. If set to 0, then ignored.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxnforetries">Maximum amount of times to redownload a NFO:</label></td>
				<td>
					<input class="short" id="maxnforetries" name="..maxnforetries" type="text" value="{$site->maxnforetries}"/>
					<div class="hint">How many times to retry when a NFO fails to download. If set to 0, we will not retry. The max is 7.</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_postadditional">
	<legend>Post Process Additional Settings - Rar/ZIP file processing</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="maxsizetopostprocess">Maximum Release Size to Post Process:</label></td>
				<td>
					<input class="short" id="maxsizetopostprocess" name="..maxsizetopostprocess" type="text" value="{$site->maxsizetopostprocess}"/>
					<div class="hint">The maximum size in gigabytes to post process (additional) a release. If set to 0, then ignored.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="minsizetopostprocess">Minimum Release Size to Post Process:</label></td>
				<td>
					<input class="short" id="minsizetopostprocess" name="..minsizetopostprocess" type="text" value="{$site->minsizetopostprocess}"/>
					<div class="hint">The minimum size in megabytes to post process (additional) a release. If set to 0, then ignored.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="checkpasswordedrar">Check For Passworded Releases:</label></td>
				<td>
					{html_radios id="checkpasswordedrar" name='..checkpasswordedrar' values=$passwd_ids output=$passwd_names selected=$site->checkpasswordedrar separator='<br />'}
					<div class="hint">Whether to attempt to peek into every release, to see if rar/zip files are password protected.<br/></div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="fetchlastcompressedfiles">Download last compressed file:</label></td>
				<td>
					{html_radios id="fetchlastcompressedfiles" name='archive.fetch.end' values=$yesno_ids output=$yesno_names selected=$site->fetchlastcompressedfiles separator='<br />'}
					<div class="hint">Try to download the last rar or zip file? (This is good if most of the files are at the end.) Note: The first rar/zip is still downloaded.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="processaudiosample">Process Audio Samples:</label></td>
				<td>
					{html_radios id="processaudiosample" name='..processaudiosample' values=$yesno_ids output=$yesno_names selected=$site->processaudiosample separator='<br />'}
					<div class="hint">Whether to attempt to process a audio sample, they will be up to 30 seconds, in ogg format. You must have ffmpeg for this.<br/></div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="processjpg">Process JPG Pictures:</label></td>
				<td>
					{html_radios id="processjpg" name='..processjpg' values=$yesno_ids output=$yesno_names selected=$site->processjpg separator='<br />'}
					<div class="hint">Whether to attempt to retrieve a JPG file while additional post processing, these are usually on XXX releases.<br/></div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="processthumbnails">Process Video Thumbnails:</label></td>
				<td>
					{html_radios id="processthumbnails" name='..processthumbnails' values=$yesno_ids output=$yesno_names selected=$site->processthumbnails separator='<br />'}
					<div class="hint">Whether to attempt to process a video thumbnail image. You must have ffmpeg for this.<br/></div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="processvideos">Process Video Samples:</label></td>
				<td>
					{html_radios id="processvideos" name='..processvideos' values=$yesno_ids output=$yesno_names selected=$site->processvideos separator='<br />'}
					<div class="hint">Whether to attempt to process a video sample, these videos are very short 1-10 seconds, 100KB on average, in ogv format. You must have ffmpeg for this.<br/></div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="segmentstodownload">Number of Segments to download for video/jpg samples:</label></td>
				<td>
					<input class="short" id="segmentstodownload" name="..segmentstodownload" type="text" value="{$site->segmentstodownload}"/>
					<div class="hint">The maximum number of articles to download to generate the sample video file or jpg sample image, the higher this is set, the potential of getting longer videos is higher. (Default 2)
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="ffmpeg_duration">Video sample file duration for ffmpeg:</label></td>
				<td>
					<input class="short" id="ffmpeg_duration" name="..ffmpeg_duration" type="text" value="{$site->ffmpeg_duration}"/>
					<div class="hint">The maximum duration (In Seconds) for ffmpeg to generate the video sample for. (Default 5)</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="timeoutseconds">Time in seconds to kill unrar/7zip/mediainfo/ffmpeg/avconv:</label></td>
				<td>
					<input class="short" id="timeoutseconds" name="..timeoutseconds" type="text" value="{$site->timeoutseconds}"/>
					<div class="hint">How much time to wait for unrar/7zip/mediainfo/ffmpeg/avconv before killing it, set to 0 to disable.
						60 is a good value. Requires the GNU Timeout path to be set.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxaddprocessed">Maximum add PP per run:</label></td>
				<td>
					<input class="short" id="maxaddprocessed" name="..maxaddprocessed" type="text" value="{$site->maxaddprocessed}"/>
					<div class="hint">The maximum amount of releases to process for passwords/previews/mediainfo per run.
						Every release gets processed here. This uses NNTP an connection, 1 per thread. This does not query
						Amazon.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxpartsprocessed">Maximum add PP parts downloaded:</label></td>
				<td>
					<input class="short" id="maxpartsprocessed" name="..maxpartsprocessed" type="text" value="{$site->maxpartsprocessed}"/>
					<div class="hint">How many articles to download for a RAR/ZIP. The more articles, the more data you get from the extracted RAR/ZIP, so longer video samples for example.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="passchkattempts">Maximum failed part downloads:</label></td>
				<td>
					<input class="short" id="passchkattempts" name="..passchkattempts" type="text" value="{$site->passchkattempts}"/>
					<div class="hint">How many times to download a different when failing and article fails to download for a RAR/ZIP. The minimum is 1.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="extractusingrarinfo">Extract RAR/ZIP using rarinfo:</label></td>
				<td>
					{html_radios id="extractusingrarinfo" name='..extractusingrarinfo' values=$yesno_ids output=$yesno_names selected=$site->extractusingrarinfo separator='<br />'}
					<div class="hint">Whether to use rarinfo or 7zip/unrar directly to decompress zip/rar files.
						<br /><strong>Using rarinfo is faster, but the extracted files are not as large which leads to smaller video samples/ potentially black preview screenshots.</strong>
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxnestedlevels">How many levels deep to go into nested rar/zip files:</label></td>
				<td>
					<input class="short" id="maxnestedlevels" name="..maxnestedlevels" type="text" value="{$site->maxnestedlevels}"/>
					<div class="hint">If a rar/zip has rar/zip inside of it, how many times should we go in those inner rar/zip files.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="innerfileblacklist">Inner file black list Regex:</label></td>
				<td>
					<textarea rows="3" placeholder="Example: /setup\.exe|password\.url/i" id="innerfileblacklist" name="indexer.ppa.innerfileblacklist">{$site->innerfileblacklist}</textarea>
					<div class="hint">You can add a regex here to set releases to potentially passworded when a file name inside a rar/zip matches this regex.
						<br /><strong>You must ensure this regex is valid, a non valid regex will cause errors during processing!</strong>
					</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_usenetset">
		<legend>Usenet Settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="newgroupscanmethod">Where to start new groups:</label></td>
				<td>
					{html_radios id="newgroupscanmethod" name='..newgroupscanmethod' values=$yesno_ids output=$newgroupscan_names selected=$site->newgroupscanmethod separator='<br />'}
					<input class="short" id="newgroupdaystoscan" name="..newgroupdaystoscan" type="text" value="{$site->newgroupdaystoscan}"/> Days or
					<input class="small" id="newgroupmsgstoscan" name="..newgroupmsgstoscan" type="text" value="{$site->newgroupmsgstoscan}"/> Posts<br/>
					<div class="hint">Scan back X (posts/days) for each new group? Can backfill to scan further. Posts is recommended, it's faster.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="safebackfilldate">Safe Backfill Date:</label></td>
				<td>
					<input class="small" id="safebackfilldate" name="..safebackfilldate" type="text" value="{$site->safebackfilldate}"/>
					<div class="hint">The target date for safe backfill. Format: YYYY-MM-DD</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="disablebackfillgroup">Auto disable groups during Backfill:</label></td>
				<td>
					{html_radios id="disablebackfillgroup" name='..disablebackfillgroup' values=$yesno_ids output=$yesno_names selected=$site->disablebackfillgroup separator='<br />'}
					<div class="hint">Whether to disable a group automatically during backfill if the target date has been reached.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="partretentionhours">Part Retention Hours:</label></td>
				<td>
					<input class="short" id="partretentionhours" name="..partretentionhours" type="text" value="{$site->partretentionhours}"/>
					<div class="hint">The number of hours incomplete parts and binaries will be retained.</div>
				</td>
			</tr>
			<tr>
				<td><label for="max_headers_iteration">Max headers iteration</label>:</td>
				<td>
					<input class="small"
						id="max_headers_iteration"
						name="max.headers.iteration"
						type="text"
						value="{$site->max_headers_iteration}" />

					<div class="hint">The maximum number of headers that update binaries sees as the total range. This ensure that a total of no more than this is attempted to be downloaded at one time per group.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxmssgs">Max Headers:</label></td>
				<td>
					<input class="short" id="maxmssgs" name="..maxmssgs" type="text" value="{$site->maxmssgs}"/>
					<div class="hint">The maximum number of headers to fetch at a time from the server. Raising this higher increases RAM usage and possibly decreases performance.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="compressedheaders">Use Compressed Headers:</label></td>
				<td>
					{html_radios id="compressedheaders" name='..compressedheaders' values=$yesno_ids output=$yesno_names selected=$site->compressedheaders separator='<br />'}
					<div class="hint">Some servers allow headers to be sent over in a compressed format. This uses about 10x less bandwidth and can speed up the download speed.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="nntpretries">NNTP Retry Attempts:</label></td>
				<td>
					<input class="short" id="nntpretries" name="..nntpretries" type="text" value="{$site->nntpretries}"/>
					<div class="hint">The maximum number of retry attempts to connect to nntp provider. On error, each retry
						takes approximately 5 seconds nntp returns reply. (Default 10)
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="nntpproxy">Use NNTP Proxy:</label></td>
				<td>
					{html_radios id="nntpproxy" name='..nntpproxy' values=$yesno_ids output=$yesno_names selected=$site->nntpproxy separator='<br />'}
					<div class="hint">
						<strong>nntpproxy does not support the POST usenet command which is required for uploading usenet comments when using sharing.</strong>
						Using the NNTP Proxy for nZEDb can improve performance of nZEDb. It uses
						connection pooling which not only give more control over the number of connections to use but also
						reduces time for connection setup/teardown. The proxy also takes care of compressed headers for you.
						To use this featrure you will need to install socketpool (sudo pip install socketpool or sudo easy_install socketpool)
						(ensure python2 is default) and edit the configuration file (nntpproxy.conf and nntpproxy_a.conf) in the
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
					{html_radios id="alternate_nntp" name='..alternate_nntp' values=$yesno_ids output=$yesno_names selected=$site->alternate_nntp separator='<br />'}
					<div class="hint">This sets Postproccessing Additional/Nfo to use the alternate NNTP provider as set in
						config.php.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="delaytime">Delay Time Check:</label></td>
				<td>
					<input class="short" id="delaytime" name="..delaytime" type="text" value="{$site->delaytime}"/>
					<div class="hint">The time in hours to wait, since last activity, before releases without parts counts
						in the subject are are created<br \> Setting this below 2 hours could create incomplete releases..
					</div>
				</td>
			</tr>
			<tr>
			<td style="width:180px;"><label for="collection_timeout">Collection Timeout Check:</label></td>
			<td>
				<input class="short" id="collection_timeout" name="indexer.processing.collection_timeout" type="text" value="{$site->collection_timeout}"/>
				<div class="hint">How many hours to wait before converting a collection into a release that is considered "stuck". <br \> Default value is 48 hours.
				</div>
			</td>
		</tr>
		</table>
	</fieldset>

	<fieldset id="site_partrepair">
		<legend>Part Repair Settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="partrepair">Part Repair:</label></td>
				<td>
					{html_radios id="partrepair" name='..partrepair' values=$yesno_ids output=$yesno_names selected=$site->partrepair separator='<br />'}
					<div class="hint">Whether to attempt to repair parts or not, increases backfill/binaries updating time.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="safepartrepair">Part Repair for Backfill Scripts:</label></td>
				<td>
					{html_radios id="safepartrepair" name='..safepartrepair' values=$yesno_ids output=$yesno_names selected=$site->safepartrepair separator='<br />'}
					<div class="hint">Whether to put unreceived parts into missed_parts table when running binaries(safe) or backfill scripts.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxpartrepair">Maximum repair per run:</label></td>
				<td>
					<input class="short" id="maxpartrepair" name="..maxpartrepair" type="text" value="{$site->maxpartrepair}"/>
					<div class="hint">The maximum amount of articles to attempt to repair at a time. If you notice that you
						are getting a lot of parts into the missed_parts table, it is possible that you USP is not keeping up
						with the requests. Try to reduce the threads to safe scripts, stop using safe scripts or stop using
						nntpproxy until improves. Ar least until the cause can be determined.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="partrepairmaxtries">Maximum repair tries:</label></td>
				<td>
					<input class="short" id="partrepairmaxtries" name="..partrepairmaxtries" type="text" value="{$site->partrepairmaxtries}"/>
					<div class="hint">Maximum amount of times to try part repair.</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_variousset">
		<legend>Various settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="tablepergroup">Table Per Group:</label></td>
				<td>
					{html_radios id="tablepergroup" name='..tablepergroup' values=$yesno_ids output=$yesno_names selected=$site->tablepergroup separator='<br />'}
					<div class="hint">This uses separate tables for collections, binaries and parts for each group.<br/>This
						requires you to run convert_to_tpg.php or reset_truncate.php.<br/>This requires that you also run
						.../misc/update/nix/multiprocessing/releases.php (tmux does this automatically).
						<br/>Run: show variables like '%open%files%'; results should be higher than 10k, twice that if you
						are using TokuDB.;
						<br/><b>You may need to increase table_open_cache, open_files_limit and max_allowed_packet in
							my.cnf. Also, you may need to add the following to /etc/security/limits.conf<br/>mysql soft
							nofile 24000<br/>mysql hard nofile 32000</b>
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="maxnzbsprocessed">Maximum NZBs stage5:</label></td>
				<td>
					<input class="short" id="maxnzbsprocessed" name="..maxnzbsprocessed" type="text" value="{$site->maxnzbsprocessed}"/>
					<div class="hint">The maximum amount of NZB files to create on stage 5 at a time in update_releases. If more are to be created it will loop stage 5 until none remain.</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_movietrailerset">
		<legend>Movie Trailer settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="trailers_display">Fetch/Display Movie Trailers:</label></td>
				<td>
					{html_radios id="trailers_display" name='site.trailers.trailers_display' values=$yesno_ids output=$yesno_names selected=$site->trailers_display separator='<br />'}
					<div class="hint">Fetch and display trailers from TraktTV (Requires API key) and/or TrailerAddict on the details page?</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="trailers_size_x">Max trailer width:</label></td>
				<td>
					<input class="short" id="trailers_size_x" name="site.trailers.trailers_size_x" type="text" value="{$site->trailers_size_x}"/>
					<div class="hint">Maximum width in pixels for the trailer window. (Default: 480)</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="trailers_size_y">Max trailer height:</label></td>
				<td>
					<input class="short" id="trailers_size_y" name="site.trailers.trailers_size_y" type="text" value="{$site->trailers_size_y}"/>
					<div class="hint">Maximum height in pixels for the trailer window. (Default: 345)</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_developerset">
		<legend>Developer Settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="showdroppedyencparts">Log Dropped Headers:</label></td>
				<td>
					{html_radios id="showdroppedyencparts" name='..showdroppedyencparts' values=$yesno_ids output=$yesno_names selected=$site->showdroppedyencparts separator='<br />'}
					<div class="hint">For developers. Whether to log all headers that have 'yEnc' and are dropped. Logged to not_yenc/groupname.dropped.txt.</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_threadsettings">
		<legend>Threaded Settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="binarythreads">Update Binaries Threads:</label></td>
				<td>
					<input class="short" id="binarythreads" name="..binarythreads" type="text" value="{$site->binarythreads}"/>
					<div class="hint">The number of threads for update_binaries. If you notice that you are getting a lot of
						parts into the missed_parts table, it is possible that you USP is not keeping up with the requests.
						Try to reduce the threads to safe scripts, stop using safe scripts or stop using nntpproxy until
						improves. Ar least until the cause can be determined.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="backfillthreads">Backfill Threads:</label></td>
				<td>
					<input class="short" id="backfillthreads" name="..backfillthreads" type="text" value="{$site->backfillthreads}"/>
					<div class="hint">The number of threads for backfill.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="releasesthreads">Update Releases Threads:</label></td>
				<td>
					<input class="short" id="releasesthreads" name="..releasesthreads" type="text" value="{$site->releasesthreads}"/>
					<div class="hint">The number of threads for update_releases. This is only for tablepergroup.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="nzbthreads">Import-nzb Threads:</label></td>
				<td>
					<input class="short" id="nzbthreads" name="..nzbthreads" type="text" value="{$site->nzbthreads}"/>
					<div class="hint">The number of threads for import-nzb(bulk). This will thread each subfolder.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="postthreads">Postprocessing Additional Threads:</label></td>
				<td>
					<input class="short" id="postthreads" name="..postthreads" type="text" value="{$site->postthreads}"/>
					<div class="hint">The number of threads for additional postprocessing. This includes deep rar
						inspection, preview and sample creation processing.
						The max is 16, if you set anything higher it will use 16.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="nfothreads">NFO Threads:</label></td>
				<td>
					<input class="short" id="nfothreads" name="..nfothreads" type="text" value="{$site->nfothreads}"/>
					<div class="hint">The number of threads for nfo postprocessing.
						The max is 16, if you set anything higher it will use 16.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="reqidthreads">Request ID Threads:</label></td>
				<td>
					<input class="short" id="reqidthreads" name="..reqidthreads" type="text" value="{$site->reqidthreads}"/>
					<div class="hint">The number of threads for local Request ID processing.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="postthreadsnon">Postprocessing Non-Amazon Threads:</label></td>
				<td>
					<input class="short" id="postthreadsnon" name="..postthreadsnon" type="text" value="{$site->postthreadsnon}"/>
					<div class="hint">The number of threads for non-amazon postprocessing. This includes movies, anime and
						tv lookups. The max is 16, if you set anything higher it will use 16.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="fixnamethreads">fixReleaseNames Threads:</label></td>
				<td>
					<input class="short" id="fixnamethreads" name="..fixnamethreads" type="text" value="{$site->fixnamethreads}"/>
					<div class="hint">The number of threads for fixReleasesNames. This includes md5, nfos and filenames.
					</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="fixnamesperrun">fixReleaseNames per Run:</label></td>
				<td>
					<input class="short" id="fixnamesperrun" name="..fixnamesperrun" type="text" value="{$site->fixnamesperrun}"/>
					<div class="hint">The maximum number of releases to check per run(threaded script only).</div>
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset id="site_usersettings">
		<legend>User Settings</legend>
		<table class="input">
			<tr>
				<td style="width:180px;"><label for="registerstatus">Registration Status:</label></td>
				<td>
					{html_radios id="registerstatus" name='..registerstatus' values=$registerstatus_ids output=$registerstatus_names selected=$site->registerstatus separator='<br />'}
					<div class="hint">The status of registrations to the site.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="storeuserips">Store User Ip:</label></td>
				<td>
					{html_radios id="storeuserips" name='..storeuserips' values=$yesno_ids output=$yesno_names selected=$site->storeuserips separator='<br />'}
					<div class="hint">Whether to store the users ip address when they signup or login.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="privateprofiles">Private Profiles:</label></td>
				<td>
					{html_radios id="privateprofiles" name='..privateprofiles' values=$yesno_ids output=$yesno_names selected=$site->privateprofiles separator='<br />'}
					<div class="hint">Should we <strong>disallow</strong> users from accessing profiles other than their own? (regardless of this setting admin/mod can access).</div>
				</td>
			</tr>
		</table>
	</fieldset>
	<input type="submit" value="Save Site Settings"/>
</form>
